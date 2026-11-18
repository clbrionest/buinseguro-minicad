<div wire:poll.6s="checkDispatches"
     x-data="{
         sirenInterval: null,
         audioCtx: null,
         audioUnlocked: false,
         needsTouchToPlay: false,
         wavDataUri: null,

         init() {
             this.wavDataUri = this.generateAlertWavDataUri();
             if (this.$refs.iosAlertAudio && this.wavDataUri) {
                 this.$refs.iosAlertAudio.src = this.wavDataUri;
             }

             const unlockHandler = () => {
                 this.unlockIosAudio();
             };

             ['touchstart', 'touchend', 'click', 'keydown'].forEach(evt => {
                 window.addEventListener(evt, unlockHandler, { passive: true });
             });
         },

         generateAlertWavDataUri() {
             try {
                 const sampleRate = 22050;
                 const durationSeconds = 1.6;
                 const numSamples = Math.floor(sampleRate * durationSeconds);
                 const buffer = new ArrayBuffer(44 + numSamples * 2);
                 const view = new DataView(buffer);

                 const writeString = (offset, str) => {
                     for (let i = 0; i < str.length; i++) {
                         view.setUint8(offset + i, str.charCodeAt(i));
                     }
                 };

                 writeString(0, 'RIFF');
                 view.setUint32(4, 36 + numSamples * 2, true);
                 writeString(8, 'WAVE');
                 writeString(12, 'fmt ');
                 view.setUint32(16, 16, true);
                 view.setUint16(20, 1, true);
                 view.setUint16(22, 1, true);
                 view.setUint32(24, sampleRate, true);
                 view.setUint32(28, sampleRate * 2, true);
                 view.setUint16(32, 2, true);
                 view.setUint16(34, 16, true);
                 writeString(36, 'data');
                 view.setUint32(40, numSamples * 2, true);

                 // Patrón de 4 pulsos de alerta operativa (880Hz - 660Hz - 880Hz - 1046Hz)
                 for (let i = 0; i < numSamples; i++) {
                     const t = i / sampleRate;
                     let freq = 0;
                     if (t < 0.32) freq = 880;
                     else if (t >= 0.38 && t < 0.70) freq = 660;
                     else if (t >= 0.76 && t < 1.08) freq = 880;
                     else if (t >= 1.14 && t < 1.52) freq = 1046.5;

                     let sample = 0;
                     if (freq > 0) {
                         const fundamental = Math.sin(2 * Math.PI * freq * t);
                         const harmonic = 0.35 * Math.sin(4 * Math.PI * freq * t);
                         sample = Math.max(-1, Math.min(1, (fundamental + harmonic) * 0.75));
                     }
                     view.setInt16(44 + i * 2, sample * 32767, true);
                 }

                 const bytes = new Uint8Array(buffer);
                 let binary = '';
                 for (let i = 0; i < bytes.byteLength; i++) {
                     binary += String.fromCharCode(bytes[i]);
                 }
                 return 'data:audio/wav;base64,' + btoa(binary);
             } catch (e) {
                 return null;
             }
         },

         unlockIosAudio() {
             try {
                 const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                 if (AudioContextClass) {
                     if (!this.audioCtx) {
                         this.audioCtx = new AudioContextClass();
                     }
                     if (this.audioCtx.state === 'suspended') {
                         this.audioCtx.resume();
                     }
                 }

                 const audioEl = this.$refs.iosAlertAudio;
                 if (audioEl && !this.audioUnlocked) {
                     audioEl.muted = true;
                     const playPromise = audioEl.play();
                     if (playPromise !== undefined) {
                         playPromise.then(() => {
                             audioEl.pause();
                             audioEl.currentTime = 0;
                             audioEl.muted = false;
                             this.audioUnlocked = true;
                             if (this.needsTouchToPlay) {
                                 this.needsTouchToPlay = false;
                                 this.playSirenCycle();
                             }
                         }).catch(() => {
                             audioEl.muted = false;
                         });
                     }
                 } else if (this.needsTouchToPlay) {
                     this.needsTouchToPlay = false;
                     this.playSirenCycle();
                 }
             } catch (_) {}
         },

         playSirenCycle() {
             try {
                 // 1. Reproducir mediante elemento <audio> HTML5 (compatible con canal multimedia en iPhone/iOS)
                 const audioEl = this.$refs.iosAlertAudio;
                 if (audioEl && this.wavDataUri) {
                     audioEl.muted = false;
                     audioEl.volume = 1.0;
                     audioEl.currentTime = 0;
                     const p = audioEl.play();
                     if (p !== undefined) {
                         p.then(() => {
                             this.needsTouchToPlay = false;
                             this.audioUnlocked = true;
                         }).catch(() => {
                             this.needsTouchToPlay = true;
                         });
                     }
                 }

                 // 2. Respaldo simultáneo con Web Audio API
                 const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                 if (AudioContextClass) {
                     if (!this.audioCtx) {
                         this.audioCtx = new AudioContextClass();
                     }
                     if (this.audioCtx.state === 'suspended') {
                         this.audioCtx.resume();
                     }
                     if (this.audioCtx.state === 'running') {
                         const now = this.audioCtx.currentTime;
                         const playTone = (freq, startTime, duration) => {
                             const osc = this.audioCtx.createOscillator();
                             const gain = this.audioCtx.createGain();
                             osc.type = 'triangle';
                             osc.frequency.setValueAtTime(freq, startTime);
                             gain.gain.setValueAtTime(0.01, startTime);
                             gain.gain.linearRampToValueAtTime(0.28, startTime + 0.03);
                             gain.gain.linearRampToValueAtTime(0.28, startTime + duration - 0.03);
                             gain.gain.linearRampToValueAtTime(0.001, startTime + duration);
                             osc.connect(gain);
                             gain.connect(this.audioCtx.destination);
                             osc.start(startTime);
                             osc.stop(startTime + duration);
                         };
                         playTone(880, now, 0.32);
                         playTone(660, now + 0.38, 0.32);
                         playTone(880, now + 0.76, 0.32);
                         playTone(1046.5, now + 1.14, 0.38);
                     }
                 }

                 if (navigator.vibrate) {
                     navigator.vibrate([400, 150, 400, 150, 500]);
                 }
             } catch (e) {
                 console.warn('CAD Alert audio warning:', e);
             }
         },

         startSiren() {
             this.stopSiren();
             this.playSirenCycle();
             this.sirenInterval = setInterval(() => {
                 this.playSirenCycle();
             }, 2400);
         },

         stopSiren() {
             if (this.sirenInterval) {
                 clearInterval(this.sirenInterval);
                 this.sirenInterval = null;
             }
             const audioEl = this.$refs.iosAlertAudio;
             if (audioEl) {
                 try {
                     audioEl.pause();
                     audioEl.currentTime = 0;
                 } catch (_) {}
             }
             this.needsTouchToPlay = false;
             if (navigator.vibrate) {
                 navigator.vibrate(0);
             }
         }
     }"
     x-on:play-emergency-siren.window="startSiren()"
     x-on:stop-emergency-siren.window="stopSiren()">

    {{-- Reproductor de audio nativo oculto para compatibilidad con Safari / iOS iPhone --}}
    <audio x-ref="iosAlertAudio" preload="auto" playsinline class="hidden"></audio>

    @if($showEmergencyModal && $alertProcedure)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             @click="if (needsTouchToPlay) { unlockIosAudio(); }"
             role="alertdialog"
             aria-modal="true"
             aria-labelledby="assignment-alert-title">
            <div class="w-full max-w-md bg-slate-900 text-white rounded-2xl border-2 {{ ($alertProcedure['kind'] ?? 'procedure') === 'patrol' ? 'border-brand-cobalt-mid' : 'border-brand-lime' }} shadow-2xl overflow-hidden">
                <div class="{{ ($alertProcedure['kind'] ?? 'procedure') === 'patrol' ? 'bg-brand-cobalt' : 'bg-blue-700' }} px-5 py-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-lime opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-brand-lime"></span>
                        </span>
                        <h3 id="assignment-alert-title" class="text-sm font-black uppercase tracking-wider text-white">
                            🔔 {{ $alertProcedure['header_title'] ?? 'Procedimiento / Ruta Asignada' }}
                        </h3>
                    </div>
                    <span class="bg-black/30 text-white font-mono text-xs font-black px-2 py-0.5 rounded">
                        {{ $alertProcedure['badge'] ?? ('#' . $alertProcedure['id']) }}
                    </span>
                </div>

                <div class="p-5 space-y-3">
                    {{-- Aviso para iOS si Safari bloqueó el sonido antes del primer toque --}}
                    <button type="button"
                            x-show="needsTouchToPlay"
                            style="display: none;"
                            @click.stop="unlockIosAudio()"
                            class="w-full py-2 px-3 rounded-xl bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center gap-2 animate-pulse">
                        <i class="fa-solid fa-volume-high"></i>
                        <span>Tocar aquí para activar sonido de alerta en iPhone</span>
                    </button>

                    <div class="flex items-center justify-between bg-slate-800/90 border border-slate-700 rounded-xl px-3.5 py-2.5">
                        <span class="text-xs font-bold text-slate-300 uppercase">Unidad Asignada:</span>
                        <span class="text-sm font-black text-brand-lime flex items-center gap-1.5">
                            <i class="fa-solid fa-car-on"></i>
                            <span>{{ $alertProcedure['device_name'] }}</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-brand-lime-light block">
                            {{ ($alertProcedure['kind'] ?? 'procedure') === 'patrol' ? 'Tipo de Patrullaje / Ruta' : 'Procedimiento Asignado' }}
                        </span>
                        <div class="text-lg font-black text-white leading-snug mt-0.5">
                            {{ $alertProcedure['type'] }}
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/80 space-y-1">
                        <div class="text-xs font-bold text-brand-lime-light uppercase">
                            📍 {{ $alertProcedure['sector'] }}
                        </div>
                        <div class="text-sm font-extrabold text-white">
                            {{ $alertProcedure['address'] }}
                        </div>
                        @if(!empty($alertProcedure['reference']))
                            <div class="text-xs text-slate-300">
                                {{ $alertProcedure['reference'] }}
                            </div>
                        @endif
                    </div>

                    <div class="text-[11px] text-slate-400 font-mono flex items-center justify-between pt-1">
                        <span>Hora de asignación:</span>
                        <strong class="text-white">{{ $alertProcedure['dispatched_time'] }} hrs</strong>
                    </div>

                    <div class="pt-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button type="button"
                                wire:click="acknowledgeDispatch"
                                @click="stopSiren()"
                                class="min-h-[44px] w-full rounded-xl border border-slate-600 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-4 py-2.5 transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-volume-xmark"></i>
                            <span>Enterado / Silenciar</span>
                        </button>

                        <a href="{{ $alertProcedure['url'] }}"
                           wire:click="acknowledgeDispatch"
                           @click="stopSiren()"
                           class="min-h-[44px] w-full rounded-xl bg-brand-lime hover:bg-brand-lime-mid text-brand-black font-black text-xs px-4 py-2.5 shadow-lg transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-arrow-right"></i>
                            <span>{{ $alertProcedure['action_label'] ?? 'Ir al Detalle →' }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

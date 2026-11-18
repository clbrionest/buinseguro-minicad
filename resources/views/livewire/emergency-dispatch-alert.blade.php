<div wire:poll.6s="checkDispatches"
     x-data="{
         sirenInterval: null,
         audioCtx: null,
         playSirenCycle() {
             try {
                 const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                 if (!AudioContextClass) return;
                 if (!this.audioCtx) {
                     this.audioCtx = new AudioContextClass();
                 }
                 if (this.audioCtx.state === 'suspended') {
                     this.audioCtx.resume();
                 }

                 const now = this.audioCtx.currentTime;
                 const playHiLo = (freq, startTime, duration) => {
                     const osc = this.audioCtx.createOscillator();
                     const gain = this.audioCtx.createGain();

                     osc.type = 'sawtooth';
                     osc.frequency.setValueAtTime(freq, startTime);

                     gain.gain.setValueAtTime(0.01, startTime);
                     gain.gain.linearRampToValueAtTime(0.24, startTime + 0.04);
                     gain.gain.linearRampToValueAtTime(0.24, startTime + duration - 0.04);
                     gain.gain.linearRampToValueAtTime(0.001, startTime + duration);

                     osc.connect(gain);
                     gain.connect(this.audioCtx.destination);

                     osc.start(startTime);
                     osc.stop(startTime + duration);
                 };

                 // Sirena de emergencia Hi-Lo (Dos tonos alternados de alta alerta)
                 playHiLo(880, now, 0.35);
                 playHiLo(660, now + 0.38, 0.35);
                 playHiLo(880, now + 0.76, 0.35);
                 playHiLo(660, now + 1.14, 0.45);

                 if (navigator.vibrate) {
                     navigator.vibrate([400, 150, 400, 150, 500]);
                 }
             } catch (e) {
                 console.warn('CAD Emergency Siren audio warning:', e);
             }
         },
         startSiren() {
             this.stopSiren();
             this.playSirenCycle();
             this.sirenInterval = setInterval(() => {
                 this.playSirenCycle();
             }, 2200);
         },
         stopSiren() {
             if (this.sirenInterval) {
                 clearInterval(this.sirenInterval);
                 this.sirenInterval = null;
             }
             if (navigator.vibrate) {
                 navigator.vibrate(0);
             }
         }
     }"
     x-on:play-emergency-siren.window="startSiren()"
     x-on:stop-emergency-siren.window="stopSiren()">

    @if($showEmergencyModal && $alertProcedure)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs" role="alertdialog" aria-modal="true" aria-labelledby="emergency-dispatch-title">
            <div class="w-full max-w-md bg-slate-900 text-white rounded-2xl border-2 border-red-500 shadow-2xl overflow-hidden animate-pulse-once">
                <div class="bg-red-600 px-5 py-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-white"></span>
                        </span>
                        <h3 id="emergency-dispatch-title" class="text-sm font-black uppercase tracking-wider text-white">
                            🚨 Despacho de Emergencia a Unidad
                        </h3>
                    </div>
                    <span class="bg-black/30 text-white font-mono text-xs font-black px-2 py-0.5 rounded">
                        #{{ $alertProcedure['id'] }}
                    </span>
                </div>

                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between bg-slate-800/90 border border-slate-700 rounded-xl px-3.5 py-2.5">
                        <span class="text-xs font-bold text-slate-300 uppercase">Unidad Despachada:</span>
                        <span class="text-sm font-black text-brand-lime flex items-center gap-1.5">
                            <i class="fa-solid fa-car-on"></i>
                            <span>{{ $alertProcedure['device_name'] }}</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-red-400 block">Tipificación del Procedimiento</span>
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
                                Ref: {{ $alertProcedure['reference'] }}
                            </div>
                        @endif
                    </div>

                    <div class="text-[11px] text-slate-400 font-mono flex items-center justify-between pt-1">
                        <span>Hora despacho Central:</span>
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
                           class="min-h-[44px] w-full rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs px-4 py-2.5 shadow-lg transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Ver Procedimiento →</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

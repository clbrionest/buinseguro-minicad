<div
    x-data="{
        ringingInterval: null,
        autoDismissTimer: null,
        countdownInterval: null,
        remainingSeconds: 10,
        audioContext: null,

        ringBurst() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                if (!this.audioContext) {
                    this.audioContext = new AudioCtx();
                }
                if (this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }

                const now = this.audioContext.currentTime;
                const playTone = (freq1, freq2, start, duration) => {
                    const osc1 = this.audioContext.createOscillator();
                    const osc2 = this.audioContext.createOscillator();
                    const gain = this.audioContext.createGain();

                    osc1.type = 'sine';
                    osc2.type = 'sine';
                    osc1.frequency.setValueAtTime(freq1, start);
                    osc2.frequency.setValueAtTime(freq2, start);

                    gain.gain.setValueAtTime(0, start);
                    gain.gain.linearRampToValueAtTime(0.18, start + 0.05);
                    gain.gain.linearRampToValueAtTime(0.18, start + duration - 0.05);
                    gain.gain.linearRampToValueAtTime(0, start + duration);

                    osc1.connect(gain);
                    osc2.connect(gain);
                    gain.connect(this.audioContext.destination);

                    osc1.start(start);
                    osc2.start(start);
                    osc1.stop(start + duration);
                    osc2.stop(start + duration);
                };

                // Cadencia de timbre telefónico: dos pulsos (0.8s) separados por un intervalo corto
                playTone(440, 480, now, 0.8);
                playTone(440, 480, now + 1.2, 0.8);
            } catch (err) {
                console.warn('CAD Ringtone audio error:', err);
            }
        },

        startRingtone(durationSec = 10) {
            this.stopRingtone();
            const seconds = Number(durationSec) > 0 ? Number(durationSec) : 10;
            this.remainingSeconds = seconds;

            this.ringBurst();
            this.ringingInterval = setInterval(() => {
                this.ringBurst();
            }, 3500);

            this.countdownInterval = setInterval(() => {
                if (this.remainingSeconds > 0) {
                    this.remainingSeconds--;
                }
            }, 1000);

            // Detener alerta sonora y cerrar popup automáticamente tras 10 segundos si no fue atendida
            this.autoDismissTimer = setTimeout(() => {
                this.stopRingtone();
                $wire.autoDismiss();
            }, seconds * 1000);
        },

        stopRingtone() {
            if (this.ringingInterval) {
                clearInterval(this.ringingInterval);
                this.ringingInterval = null;
            }
            if (this.autoDismissTimer) {
                clearTimeout(this.autoDismissTimer);
                this.autoDismissTimer = null;
            }
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
                this.countdownInterval = null;
            }
        }
    }"
    x-on:play-ringtone.window="startRingtone($event.detail?.duration ?? 10)"
    x-on:stop-ringtone.window="stopRingtone()"
>
    @if($showModal && !$notificationsMuted)
        <div class="fixed bottom-8 right-8 z-50 bg-white dark:bg-gray-800 border-l-8 border-green-500 shadow-2xl rounded-lg p-6 w-96 animate-bounce-short">
            
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <span class="relative flex h-4 w-4">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wider">¡Llamada Entrante!</h3>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-700"
                          title="Se cerrará automáticamente si no es atendida">
                        <span x-text="remainingSeconds">10</span>s
                    </span>
                    <button wire:click="dismiss" @click="stopRingtone()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            {{-- Barra visual de cuenta regresiva (10 segundos) --}}
            <div class="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-green-500 transition-all duration-1000 ease-linear"
                     :style="'width: ' + Math.max(0, (remainingSeconds / 10) * 100) + '%'"></div>
            </div>
            
            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded border border-gray-200 dark:border-gray-600 text-center">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Teléfono Origen</p>
                <p class="font-black text-3xl text-gray-900 dark:text-white tracking-widest">{{ $callerId }}</p>
                @if($extension)
                    <p class="text-xs font-semibold text-green-700 dark:text-green-400 mt-1">Anexo Receptor: 📞 {{ $extension }}</p>
                @endif
                <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200">
                        {{ $previousCallsCount }} llamadas previas
                    </span>
                    @if($prankCallsCount > 0)
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-red-100 dark:bg-red-900/60 text-red-800 dark:text-red-200 animate-pulse">
                            ⚠️ {{ $prankCallsCount }} Pitanza/Broma
                        </span>
                    @endif
                </div>
            </div>

            @if(count($recentProcedures) > 0)
                <div class="mb-4 p-2.5 bg-blue-50 dark:bg-gray-700/70 border border-blue-200 dark:border-gray-600 rounded text-xs">
                    <p class="font-bold text-blue-900 dark:text-blue-300 uppercase text-[10px] mb-1.5">📋 Últimos Procedimientos de este número:</p>
                    <div class="space-y-1.5 max-h-28 overflow-y-auto">
                        @foreach($recentProcedures as $proc)
                            <div class="bg-white dark:bg-gray-800 p-1.5 rounded border border-blue-100 dark:border-gray-600 flex justify-between items-center">
                                <div class="truncate pr-2">
                                    <span class="font-bold text-gray-800 dark:text-gray-200">#{{ $proc['id'] }} {{ $proc['type'] }}</span>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $proc['address'] }}</div>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $proc['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <div class="space-y-2">
                <button wire:click="createProcedure" @click="stopRingtone()" class="w-full bg-green-600 hover:bg-green-700 text-white font-black py-3 px-4 rounded-lg shadow-md flex items-center justify-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    ATENDER Y CREAR CASO
                </button>

                <button type="button"
                        wire:click="toggleNotifications"
                        @click="stopRingtone()"
                        class="w-full text-[11px] font-bold text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 py-1 transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-bell-slash" aria-hidden="true"></i>
                    <span>Desactivar notificaciones de llamadas</span>
                </button>
            </div>
        </div>

        <style>
            .animate-bounce-short {
                animation: bounce-short 0.5s ease-out 3;
            }
            @keyframes bounce-short {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10%); }
            }
        </style>
    @endif
</div>

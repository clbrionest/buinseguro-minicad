<div wire:poll.15s>
    @if($unattendedCount > 0)
        <div class="max-w-[1600px] mx-auto px-3 sm:px-6 pt-3">
            <div class="bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/80 text-amber-950 dark:text-amber-100 rounded-xl px-4 py-2.5 shadow-xs flex flex-wrap items-center justify-between gap-3"
                 role="alert">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-phone-slash text-sm" aria-hidden="true"></i>
                    </div>
                    <div class="text-xs leading-snug">
                        <span class="font-extrabold block sm:inline">
                            @if($unattendedCount === 1)
                                Tienes 1 llamada sin atender en el sistema
                            @else
                                Tienes {{ $unattendedCount }} llamadas sin atender en el sistema
                            @endif
                        </span>
                        <span class="text-amber-800/90 dark:text-amber-200/80 sm:ml-1">
                            (sin caso asociado ni tipificación registrada).
                        </span>
                    </div>
                </div>

                <a href="{{ route('reports.calls', ['estado' => 'unlinked']) }}"
                   class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs px-3 py-1.5 rounded-lg shadow-xs transition shrink-0">
                    <i class="fa-solid fa-headset text-[11px]" aria-hidden="true"></i>
                    <span>Atender Llamadas Pendientes →</span>
                </a>
            </div>
        </div>
    @endif
</div>

<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        \App\Models\AuditLog::record(
            'auth.login',
            'Autenticación',
            'Inicio de sesión exitoso en Consola CAD (' . (auth()->user()->email ?? '') . ').',
            auth()->user()
        );

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div x-data="{
        clock: '',
        dateStr: '',
        showFullChangelog: false,
        init() {
            const update = () => {
                const now = new Date();
                this.clock = now.toLocaleTimeString('es-CL', { hour12: false });
                this.dateStr = now.toLocaleDateString('es-CL', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
            };
            update();
            setInterval(update, 1000);
        }
    }">
    <!-- Top Header with Institutional Shield Logo & Live Clock -->
    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
        <div class="w-12 h-12 rounded-xl bg-white p-1.5 flex items-center justify-center shadow-xs border border-ui-border shrink-0">
            <img src="{{ asset('escudo.svg') }}" alt="Escudo Municipalidad de Buin" class="h-full w-auto object-contain">
        </div>
        <div class="min-w-0 flex-1">
            <h2 class="text-base sm:text-lg font-extrabold text-ui-fg tracking-tight flex items-center gap-2">
                <span>MiniCAD</span>
                <span class="bg-brand-lime text-brand-black text-[10px] font-black font-mono px-2 py-0.5 rounded shadow-2xs" title="Versión SemVer Actual">
                    {{ $cadVersionTag ?? 'v1.3.0' }}
                </span>
            </h2>
            <p class="text-xs text-ui-muted truncate">Dirección de Seguridad Pública • Municipalidad de Buin</p>
        </div>
        <div class="bg-ui-subtle border border-ui-border px-2.5 py-1 rounded-lg text-right shrink-0">
            <span x-text="clock" class="font-mono cad-tabular text-xs sm:text-sm font-black text-brand-cobalt dark:text-brand-lime-mid block leading-none"></span>
            <span x-text="dateStr" class="text-[9px] sm:text-[10px] text-ui-muted uppercase font-bold block mt-0.5"></span>
        </div>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-ui-fg mb-1.5">
                <i class="fa-solid fa-envelope text-ui-muted mr-1" aria-hidden="true"></i> Correo Institucional
            </label>
            <div class="relative">
                <input wire:model="form.email"
                       id="email"
                       type="email"
                       name="email"
                       required
                       autofocus
                       autocomplete="username"
                       placeholder="nombre.apellido@buinseguro.cl"
                       class="w-full rounded-lg bg-ui-subtle border border-ui-border text-ui-fg placeholder-ui-muted text-sm px-3.5 py-2.5 focus:border-ui-focus focus:ring-2 focus:ring-ui-focus/30 transition" />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-ui-fg mb-1.5">
                <i class="fa-solid fa-key text-ui-muted mr-1" aria-hidden="true"></i> Contraseña de Operador
            </label>
            <div class="relative">
                <input wire:model="form.password"
                       id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="w-full rounded-lg bg-ui-subtle border border-ui-border text-ui-fg placeholder-ui-muted text-sm px-3.5 py-2.5 focus:border-ui-focus focus:ring-2 focus:ring-ui-focus/30 transition" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded bg-ui-subtle border-ui-border text-brand-cobalt focus:ring-ui-focus" name="remember">
                <span class="ms-2 text-xs font-semibold text-ui-fg">Mantener sesión en consola</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-semibold text-brand-cobalt dark:text-brand-cobalt-mid hover:underline transition" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidó su clave?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="w-full bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white dark:bg-brand-lime dark:hover:bg-brand-lime-mid dark:text-brand-black font-extrabold text-sm uppercase tracking-wider py-3 px-4 rounded-lg shadow-md flex items-center justify-center gap-2 transition disabled:opacity-50">
                <span wire:loading.remove><i class="fa-solid fa-right-to-bracket mr-1" aria-hidden="true"></i> Iniciar Turno en MiniCAD</span>
                <span wire:loading><i class="fa-solid fa-circle-notch fa-spin mr-1" aria-hidden="true"></i> Autenticando...</span>
            </button>
        </div>
    </form>

    <!-- Bottom Info Banner -->
    <div class="mt-6 pt-4 border-t border-ui-border flex items-start gap-2.5 text-[11px] text-ui-muted">
        <i class="fa-solid fa-circle-info text-brand-cobalt dark:text-brand-lime mt-0.5" aria-hidden="true"></i>
        <span>Al ingresar, recuerde verificar la recepción de su <strong>Turno de Sala</strong> y el estado de los equipos de la Sala de Monitoreo</span>
    </div>

    <!-- Bloque de Versión SemVer y Resumen de Cambios (Sincronizado desde main) -->
    @if(!empty($cadLatestRelease))
        <div class="mt-4 pt-3.5 border-t border-ui-border">
            <div class="bg-ui-subtle/80 border border-ui-border rounded-xl p-3 text-left">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="bg-brand-cobalt text-brand-white dark:bg-brand-lime dark:text-brand-black font-mono font-black text-[10px] px-2 py-0.5 rounded">
                            {{ $cadVersionTag ?? 'v1.3.0' }}
                        </span>
                        <span class="text-[11px] font-extrabold text-ui-fg truncate">
                            {{ $cadLatestRelease['title'] ?? 'Novedades del Sistema' }}
                        </span>
                    </div>
                    <span class="text-[10px] font-mono text-ui-muted shrink-0">{{ $cadLatestRelease['date'] ?? '' }}</span>
                </div>

                <!-- Lista resumida de últimos cambios -->
                <ul class="space-y-1.5 text-[11px] text-ui-muted">
                    @foreach(array_slice($cadLatestRelease['changes'] ?? [], 0, 3) as $chg)
                        <li class="flex items-start gap-1.5 leading-snug">
                            <span class="mt-0.5 inline-block px-1.5 py-0.2 rounded text-[9px] font-black uppercase shrink-0 {{ ($chg['type'] ?? '') === 'feat' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : (($chg['type'] ?? '') === 'fix' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-blue-500/15 text-blue-600 dark:text-blue-400') }}">
                                {{ strtoupper($chg['type'] ?? 'cambio') }}
                            </span>
                            <span class="text-ui-fg/90">{{ $chg['description'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>

                <!-- Botón para ver el historial de PRs y versiones anteriores -->
                @if(!empty($cadReleases) && count($cadReleases) > 1)
                    <div class="mt-2.5 pt-2 border-t border-ui-border/70">
                        <button type="button"
                                @click="showFullChangelog = !showFullChangelog"
                                class="w-full flex items-center justify-between text-[10px] font-bold text-brand-cobalt dark:text-brand-lime hover:underline">
                            <span>
                                <i class="fa-solid fa-code-branch mr-1" aria-hidden="true"></i>
                                <span x-text="showFullChangelog ? 'Ocultar historial de versiones (PRs)' : 'Ver historial completo de cambios ({{ count($cadReleases) }} versiones)'"></span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-[9px] transition-transform" :class="showFullChangelog ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>

                        <div x-show="showFullChangelog"
                             x-transition
                             style="display: none;"
                             class="mt-2.5 space-y-2.5 max-h-48 overflow-y-auto pr-1 border-t border-ui-border/60 pt-2">
                            @foreach(array_slice($cadReleases, 1) as $rel)
                                <div class="text-[10px] bg-ui-card p-2 rounded-lg border border-ui-border">
                                    <div class="flex items-center justify-between font-bold text-ui-fg mb-1">
                                        <span class="font-mono text-brand-cobalt dark:text-brand-lime">v{{ $rel['version'] }} — {{ $rel['title'] ?? '' }}</span>
                                        <span class="font-mono text-ui-muted">{{ $rel['date'] }}</span>
                                    </div>
                                    @if(!empty($rel['pr_refs']))
                                        <div class="flex flex-wrap gap-1 mb-1">
                                            @foreach($rel['pr_refs'] as $prRef)
                                                <span class="bg-ui-subtle border border-ui-border px-1.5 py-0.2 rounded font-mono text-[9px] text-ui-muted">{{ $prRef }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <ul class="space-y-0.5 text-ui-muted list-disc list-inside">
                                        @foreach($rel['changes'] ?? [] as $c)
                                            <li>{{ $c['description'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

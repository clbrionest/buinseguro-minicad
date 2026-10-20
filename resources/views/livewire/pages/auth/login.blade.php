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
    <!-- Top Header & Live Clock -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
        <div>
            <h2 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-blue-500"></i>
                Ingreso a Consola CAD
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Identifíquese con sus credenciales institucionales</p>
        </div>
        <div class="bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg text-right">
            <span x-text="clock" class="font-mono text-sm font-black text-emerald-400 block leading-none"></span>
            <span x-text="dateStr" class="text-[10px] text-slate-500 uppercase font-bold block mt-0.5"></span>
        </div>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                <i class="fa-solid fa-envelope text-slate-400 mr-1"></i> Correo Institucional
            </label>
            <div class="relative">
                <input wire:model="form.email"
                       id="email"
                       type="email"
                       name="email"
                       required
                       autofocus
                       autocomplete="username"
                       placeholder="operador@buin.cl"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 transition" />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                <i class="fa-solid fa-key text-slate-400 mr-1"></i> Contraseña de Operador
            </label>
            <div class="relative">
                <input wire:model="form.password"
                       id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 transition" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded bg-slate-950 border-slate-700 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-xs font-semibold text-slate-300">Mantener sesión en consola</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-semibold text-blue-400 hover:text-blue-300 transition" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidó su clave?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black text-sm uppercase tracking-wider py-3 px-4 rounded-xl shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2 transition disabled:opacity-50">
                <span wire:loading.remove><i class="fa-solid fa-right-to-bracket mr-1"></i> Iniciar Turno en Central</span>
                <span wire:loading><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Autenticando...</span>
            </button>
        </div>
    </form>

    <!-- Bottom Info Banner -->
    <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-start gap-2.5 text-[11px] text-slate-400">
        <i class="fa-solid fa-circle-info text-blue-400 mt-0.5"></i>
        <span>Al ingresar, recuerde verificar la recepción de su <strong>Turno de Sala</strong> y el estado de su anexo telefónico PBX.</span>
    </div>
</div>

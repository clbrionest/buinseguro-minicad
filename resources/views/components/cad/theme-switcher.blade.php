@props([
    'compact' => false,
])

<div
    x-data="{
        pref: 'system',
        init() {
            try {
                const stored = localStorage.getItem('cad-theme');
                const docPref = document.documentElement.dataset.themePreference;
                this.pref = ['light', 'dark', 'system'].includes(stored)
                    ? stored
                    : (['light', 'dark', 'system'].includes(docPref) ? docPref : 'system');
            } catch (_) {
                this.pref = 'system';
            }
        },
        choose(mode) {
            this.pref = mode;
            if (window.Alpine && window.Alpine.store('appearance')) {
                window.Alpine.store('appearance').set(mode);
            } else {
                try { localStorage.setItem('cad-theme', mode); } catch (_) {}
                const isDark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
                document.documentElement.dataset.themePreference = mode;
            }
        }
    }"
    role="group"
    aria-label="Selector de apariencia del sistema"
    {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg p-0.5 bg-[#1D1F59]/80 dark:bg-[#10131D] border border-white/15 text-xs']) }}
>
    <button
        type="button"
        @click="choose('light')"
        :aria-pressed="pref === 'light'"
        title="Apariencia Clara"
        aria-label="Apariencia Clara"
        :class="pref === 'light'
            ? 'bg-brand-lime text-brand-black font-extrabold shadow-xs'
            : 'text-white/80 hover:text-white hover:bg-white/10 font-semibold'"
        class="px-2 py-1 rounded-md inline-flex items-center gap-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-lime"
    >
        <i class="fa-solid fa-sun text-[11px]" aria-hidden="true"></i>
        @if(!$compact)
            <span class="hidden xl:inline text-[11px]">Claro</span>
        @endif
    </button>

    <button
        type="button"
        @click="choose('dark')"
        :aria-pressed="pref === 'dark'"
        title="Apariencia Oscura"
        aria-label="Apariencia Oscura"
        :class="pref === 'dark'
            ? 'bg-brand-lime text-brand-black font-extrabold shadow-xs'
            : 'text-white/80 hover:text-white hover:bg-white/10 font-semibold'"
        class="px-2 py-1 rounded-md inline-flex items-center gap-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-lime"
    >
        <i class="fa-solid fa-moon text-[11px]" aria-hidden="true"></i>
        @if(!$compact)
            <span class="hidden xl:inline text-[11px]">Oscuro</span>
        @endif
    </button>

    <button
        type="button"
        @click="choose('system')"
        :aria-pressed="pref === 'system'"
        title="Apariencia Automática (Sistema)"
        aria-label="Apariencia Automática según el sistema operativo"
        :class="pref === 'system'
            ? 'bg-brand-lime text-brand-black font-extrabold shadow-xs'
            : 'text-white/80 hover:text-white hover:bg-white/10 font-semibold'"
        class="px-2 py-1 rounded-md inline-flex items-center gap-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-lime"
    >
        <i class="fa-solid fa-desktop text-[11px]" aria-hidden="true"></i>
        @if(!$compact)
            <span class="hidden xl:inline text-[11px]">Auto</span>
        @endif
    </button>
</div>

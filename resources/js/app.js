import './echo';

/**
 * BUIN SEGURO CAD — Design System v1.0
 * Store de apariencia ('light' | 'dark' | 'system') integrado con Alpine.js de Livewire 3
 * sin invocar un segundo Alpine.start().
 */
const registerAppearanceStore = (AlpineInstance) => {
    if (!AlpineInstance || AlpineInstance.store('appearance')) {
        return;
    }

    AlpineInstance.store('appearance', {
        preference: 'system',
        resolved: 'light',

        init() {
            try {
                const stored = localStorage.getItem('cad-theme');
                const serverPref = document.documentElement.dataset.themePreference;
                if (['light', 'dark', 'system'].includes(stored)) {
                    this.preference = stored;
                } else if (['light', 'dark', 'system'].includes(serverPref)) {
                    this.preference = serverPref;
                    localStorage.setItem('cad-theme', serverPref);
                } else {
                    this.preference = 'system';
                }
            } catch (_) {
                this.preference = 'system';
            }

            this.apply();

            if (typeof window !== 'undefined' && window.matchMedia) {
                const mq = window.matchMedia('(prefers-color-scheme: dark)');
                const onSystemChange = () => {
                    if (this.preference === 'system') {
                        this.apply();
                    }
                };
                if (mq.addEventListener) {
                    mq.addEventListener('change', onSystemChange);
                }
            }
        },

        set(value) {
            if (!['light', 'dark', 'system'].includes(value)) return;
            this.preference = value;
            try {
                localStorage.setItem('cad-theme', value);
            } catch (_) {}
            this.apply();
            this.syncToServer(value);
        },

        apply() {
            let isDark = false;
            try {
                isDark =
                    this.preference === 'dark' ||
                    (this.preference === 'system' &&
                        window.matchMedia &&
                        window.matchMedia('(prefers-color-scheme: dark)').matches);
            } catch (_) {
                isDark = false;
            }
            this.resolved = isDark ? 'dark' : 'light';
            document.documentElement.dataset.theme = this.resolved;
            document.documentElement.dataset.themePreference = this.preference;
        },

        syncToServer(value) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isAuth = document.documentElement.dataset.authenticated === 'true';
            if (!csrf || !isAuth) return;

            fetch('/profile/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ theme: value }),
            }).catch(() => {});
        },
    });
};

document.addEventListener('alpine:init', () => {
    registerAppearanceStore(window.Alpine);
});

if (typeof window !== 'undefined' && window.Alpine) {
    registerAppearanceStore(window.Alpine);
}

document.addEventListener('livewire:navigated', () => {
    if (window.Alpine && window.Alpine.store('appearance')) {
        window.Alpine.store('appearance').apply();
    }
});

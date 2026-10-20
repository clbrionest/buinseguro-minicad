import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Models/**/*.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    cobalt: '#2C2E83',
                    'cobalt-soft': '#EEF0FB',
                    'cobalt-light': '#D5D8F4',
                    'cobalt-mid': '#858BD1',
                    'cobalt-deep': '#1D1F59',
                    lime: '#94C11E',
                    'lime-soft': '#F5FAE9',
                    'lime-light': '#E8F3CD',
                    'lime-mid': '#C5DF81',
                    'lime-deep': '#5B7812',
                    white: '#FFFFFF',
                    silver: '#CDC7C6',
                    black: '#08080A',
                },
                ui: {
                    app: 'var(--ui-app)',
                    card: 'var(--ui-card)',
                    sidebar: 'var(--ui-sidebar)',
                    subtle: 'var(--ui-subtle)',
                    fg: 'var(--ui-fg)',
                    muted: 'var(--ui-muted)',
                    border: 'var(--ui-border)',
                    primary: 'var(--ui-primary)',
                    'primary-fg': 'var(--ui-primary-fg)',
                    focus: 'var(--ui-focus)',
                },
                priority: {
                    critical: '#DC3545',
                    high: '#EA8C23',
                    warning: '#B98500',
                    normal: '#64748B',
                },
                state: {
                    progress: '#2563EB',
                    completed: '#16835D',
                    neutral: '#64748B',
                },
            },
        },
    },

    plugins: [forms],
};

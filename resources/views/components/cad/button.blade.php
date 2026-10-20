@props([
    'variant' => 'primary', // primary | accent | secondary | danger | ghost
    'size' => 'md',         // sm | md | lg
    'loading' => false,
    'disabled' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $variantClasses = match($variant) {
        'primary'   => 'bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white dark:bg-brand-cobalt-mid dark:hover:bg-brand-cobalt-light dark:text-brand-black border border-transparent shadow-xs',
        'accent'    => 'bg-brand-lime hover:bg-brand-lime-mid text-brand-black border border-brand-lime-deep/20 shadow-xs font-extrabold',
        'secondary' => 'bg-ui-card hover:bg-ui-subtle text-ui-fg border border-ui-border shadow-xs',
        'danger'    => 'bg-priority-critical hover:opacity-90 text-brand-white border border-transparent shadow-xs',
        'ghost'     => 'bg-transparent hover:bg-ui-subtle text-ui-fg border border-transparent',
        default     => 'bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white dark:bg-brand-cobalt-mid dark:text-brand-black',
    };

    $sizeClasses = match($size) {
        'sm' => 'px-2.5 py-1.5 text-xs rounded-md gap-1.5 min-h-[32px]',
        'lg' => 'px-5 py-2.5 text-sm rounded-lg gap-2 min-h-[44px]',
        default => 'px-3.5 py-2 text-xs rounded-lg gap-1.5 min-h-[38px]',
    };

    $baseClasses = 'inline-flex items-center justify-center font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ui-focus disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap ' . $variantClasses . ' ' . $sizeClasses;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled || $loading)
        {{ $attributes->merge(['class' => $baseClasses]) }}
    >
        @if($loading)
            <i class="fa-solid fa-circle-notch fa-spin text-xs" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </button>
@endif

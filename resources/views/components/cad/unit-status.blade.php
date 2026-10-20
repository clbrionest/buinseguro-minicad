@props([
    'status' => 'Disponible (QAP)',
    'activeCount' => 0,
])

@php
    $cfg = match($status) {
        'Disponible (QAP)' => [
            'classes' => 'bg-[#16835D]/15 text-[#11684A] border-[#16835D]/35 dark:bg-[#16835D]/25 dark:text-[#6EE7B7] dark:border-[#16835D]/50',
            'icon'    => 'fa-solid fa-circle-check',
        ],
        'En Procedimiento' => [
            'classes' => 'bg-[#2563EB]/15 text-[#1D4ED8] border-[#2563EB]/35 dark:bg-[#2563EB]/25 dark:text-[#93C5FD] dark:border-[#2563EB]/50',
            'icon'    => 'fa-solid fa-car-on',
        ],
        'Punto Fijo' => [
            'classes' => 'bg-brand-cobalt/12 text-brand-cobalt border-brand-cobalt/30 dark:bg-brand-cobalt-mid/25 dark:text-brand-cobalt-light dark:border-brand-cobalt-mid/50',
            'icon'    => 'fa-solid fa-location-pin',
        ],
        'En Colación' => [
            'classes' => 'bg-[#B98500]/15 text-[#8A6300] border-[#B98500]/40 dark:bg-[#B98500]/25 dark:text-[#F4CB5C] dark:border-[#B98500]/50',
            'icon'    => 'fa-solid fa-mug-saucer',
        ],
        'En Mantención' => [
            'classes' => 'bg-[#DC3545]/15 text-[#DC3545] border-[#DC3545]/40 dark:bg-[#DC3545]/25 dark:text-[#FF8A93] dark:border-[#DC3545]/50',
            'icon'    => 'fa-solid fa-screwdriver-wrench',
        ],
        default => [
            'classes' => 'bg-ui-subtle text-ui-muted border-ui-border',
            'icon'    => 'fa-solid fa-circle-minus',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] font-bold whitespace-nowrap ' . $cfg['classes']]) }}>
    <i class="{{ $cfg['icon'] }} text-[10px]" aria-hidden="true"></i>
    <span>{{ $status }}</span>
    @if($activeCount > 0)
        <span class="font-mono cad-tabular">({{ $activeCount }})</span>
    @endif
</span>

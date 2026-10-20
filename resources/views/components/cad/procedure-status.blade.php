@props([
    'status' => 'Pendiente',
])

@php
    $cfg = match($status) {
        'Pendiente' => [
            'classes' => 'bg-[#64748B]/15 text-[#475569] border-[#64748B]/35 dark:bg-[#64748B]/25 dark:text-[#CBD5E1] dark:border-[#64748B]/50',
            'icon'    => 'fa-regular fa-clock',
            'label'   => 'Pendiente (Sin asignar)',
        ],
        'En Camino' => [
            'classes' => 'bg-brand-cobalt/12 text-brand-cobalt border-brand-cobalt/30 dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light dark:border-brand-cobalt-mid/40',
            'icon'    => 'fa-solid fa-route',
            'label'   => 'En Camino (Despachado)',
        ],
        'En el Lugar' => [
            'classes' => 'bg-[#2563EB]/12 text-[#1D4ED8] border-[#2563EB]/35 dark:bg-[#2563EB]/25 dark:text-[#93C5FD] dark:border-[#2563EB]/50',
            'icon'    => 'fa-solid fa-location-crosshairs',
            'label'   => 'En el Lugar (En curso)',
        ],
        'Derivado' => [
            'classes' => 'bg-[#EA8C23]/15 text-[#B86308] border-[#EA8C23]/40 dark:bg-[#EA8C23]/25 dark:text-[#F8B86E] dark:border-[#EA8C23]/50',
            'icon'    => 'fa-solid fa-share-from-square',
            'label'   => 'Derivado',
        ],
        'Cerrado', 'Resuelto' => [
            'classes' => 'bg-[#16835D]/15 text-[#11684A] border-[#16835D]/35 dark:bg-[#16835D]/25 dark:text-[#6EE7B7] dark:border-[#16835D]/50',
            'icon'    => 'fa-solid fa-circle-check',
            'label'   => $status,
        ],
        'Cancelado' => [
            'classes' => 'bg-[#64748B]/15 text-[#475569] border-[#64748B]/35 dark:bg-[#64748B]/25 dark:text-[#CBD5E1] dark:border-[#64748B]/50',
            'icon'    => 'fa-solid fa-ban',
            'label'   => 'Cancelado',
        ],
        default => [
            'classes' => 'bg-ui-subtle text-ui-fg border-ui-border',
            'icon'    => 'fa-solid fa-circle-dot',
            'label'   => $status,
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs font-bold whitespace-nowrap ' . $cfg['classes']]) }}>
    <i class="{{ $cfg['icon'] }} text-[11px]" aria-hidden="true"></i>
    <span>{{ $status }}</span>
</span>

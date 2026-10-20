@props([
    'label' => '',
    'value' => '0',
    'unit' => null,
    'period' => null,
    'tone' => 'cobalt', // cobalt | lime | critical | high | warning | progress | completed | neutral
    'icon' => 'fa-solid fa-chart-simple',
    'active' => false,
    'interactive' => false,
])

@php
    $accentStyles = match($tone) {
        'critical'  => [
            'border' => 'border-l-priority-critical',
            'iconBg' => 'bg-priority-critical/10 text-priority-critical',
            'valTxt' => 'text-priority-critical',
            'ring'   => 'ring-priority-critical',
        ],
        'high'      => [
            'border' => 'border-l-priority-high',
            'iconBg' => 'bg-priority-high/15 text-priority-high',
            'valTxt' => 'text-priority-high',
            'ring'   => 'ring-priority-high',
        ],
        'warning'   => [
            'border' => 'border-l-priority-warning',
            'iconBg' => 'bg-priority-warning/15 text-priority-warning',
            'valTxt' => 'text-priority-warning',
            'ring'   => 'ring-priority-warning',
        ],
        'progress'  => [
            'border' => 'border-l-state-progress',
            'iconBg' => 'bg-state-progress/15 text-state-progress',
            'valTxt' => 'text-state-progress',
            'ring'   => 'ring-state-progress',
        ],
        'completed' => [
            'border' => 'border-l-state-completed',
            'iconBg' => 'bg-state-completed/15 text-state-completed',
            'valTxt' => 'text-state-completed',
            'ring'   => 'ring-state-completed',
        ],
        'lime'      => [
            'border' => 'border-l-brand-lime',
            'iconBg' => 'bg-brand-lime/20 text-brand-lime-deep dark:text-brand-lime-mid',
            'valTxt' => 'text-brand-lime-deep dark:text-brand-lime-mid',
            'ring'   => 'ring-brand-lime',
        ],
        default     => [
            'border' => 'border-l-brand-cobalt dark:border-l-brand-cobalt-mid',
            'iconBg' => 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light',
            'valTxt' => 'text-ui-fg',
            'ring'   => 'ring-brand-cobalt dark:ring-brand-cobalt-mid',
        ],
    };

    $tag = $interactive ? 'button' : 'div';
@endphp

<{{ $tag }}
    @if($interactive) type="button" @endif
    {{ $attributes->merge([
        'class' => 'text-left w-full rounded-lg border border-ui-border border-l-4 ' . $accentStyles['border'] . ' bg-ui-card p-4 text-ui-fg shadow-xs flex justify-between items-center transition ' .
            ($interactive ? 'hover:bg-ui-subtle cursor-pointer ' : '') .
            ($active ? 'ring-2 ' . $accentStyles['ring'] . ' bg-ui-subtle' : '')
    ]) }}
>
    <div class="min-w-0 pr-2">
        <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-ui-muted truncate">{{ $label }}</span>
        </div>
        <div class="mt-1 flex items-baseline gap-1.5">
            <span class="text-2xl sm:text-3xl font-black font-mono cad-tabular {{ $accentStyles['valTxt'] }}">
                {{ $value }}
            </span>
            @if($unit)
                <span class="text-xs font-bold text-ui-muted">{{ $unit }}</span>
            @endif
        </div>
        @if($period)
            <span class="text-[11px] font-semibold text-ui-muted block mt-0.5">{{ $period }}</span>
        @endif
        {{ $slot }}
    </div>

    <div class="w-11 h-11 rounded-lg flex items-center justify-center shrink-0 {{ $accentStyles['iconBg'] }}">
        <i class="{{ $icon }} text-lg" aria-hidden="true"></i>
    </div>
</{{ $tag }}>

@props([
    'priority' => 'normal', // critical | high | warning | normal
    'label' => null,
    'sla' => null,
])

@php
    if (is_array($sla)) {
        $slaColor = $sla['color'] ?? 'gray';
        $priority = match($slaColor) {
            'red'    => 'critical',
            'orange' => 'high',
            'yellow' => 'warning',
            'green'  => 'normal',
            default  => 'normal',
        };
        $label = $label ?? ($sla['label'] ?? 'Normal');
    }

    $normPriority = strtolower((string) $priority);
    if (in_array($normPriority, ['alta', 'critica', 'crítica', 'red', 'critical'])) {
        $normPriority = 'critical';
    } elseif (in_array($normPriority, ['high', 'orange'])) {
        $normPriority = 'high';
    } elseif (in_array($normPriority, ['media', 'yellow', 'warning', 'advertencia'])) {
        $normPriority = 'warning';
    } else {
        $normPriority = 'normal';
    }

    $cfg = match($normPriority) {
        'critical' => [
            'bg'   => 'bg-[#DC3545]/12 text-[#DC3545] border-[#DC3545]/35 dark:bg-[#DC3545]/20 dark:text-[#FF8A93] dark:border-[#DC3545]/50',
            'icon' => 'fa-solid fa-triangle-exclamation',
            'text' => $label ?: 'Crítico / Alerta SLA',
        ],
        'high' => [
            'bg'   => 'bg-[#EA8C23]/12 text-[#B86308] border-[#EA8C23]/40 dark:bg-[#EA8C23]/20 dark:text-[#F8B86E] dark:border-[#EA8C23]/50',
            'icon' => 'fa-solid fa-circle-exclamation',
            'text' => $label ?: 'Prioridad Alta',
        ],
        'warning' => [
            'bg'   => 'bg-[#B98500]/12 text-[#8A6300] border-[#B98500]/40 dark:bg-[#B98500]/20 dark:text-[#F4CB5C] dark:border-[#B98500]/50',
            'icon' => 'fa-solid fa-clock',
            'text' => $label ?: 'Atención SLA',
        ],
        default => [
            'bg'   => 'bg-ui-subtle text-ui-fg border-ui-border',
            'icon' => 'fa-solid fa-shield-check',
            'text' => $label ?: 'En Rango SLA',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs font-bold cad-tabular whitespace-nowrap ' . $cfg['bg']]) }}>
    <i class="{{ $cfg['icon'] }} text-[11px]" aria-hidden="true"></i>
    <span>{{ $cfg['text'] }}</span>
</span>

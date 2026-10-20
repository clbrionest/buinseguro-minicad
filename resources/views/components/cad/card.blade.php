@props([
    'heading' => null,
    'subtitle' => null,
    'padding' => 'p-4 sm:p-5',
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-ui-border bg-ui-card text-ui-fg shadow-xs']) }}>
    @if($heading || isset($actions))
        <div class="px-4 sm:px-5 py-3.5 border-b border-ui-border flex flex-wrap items-center justify-between gap-2">
            <div>
                @if($heading)
                    <h3 class="text-sm font-bold text-ui-fg tracking-tight">{{ $heading }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-ui-muted mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</section>

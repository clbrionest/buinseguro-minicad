@props([
    'icon' => 'fa-solid fa-inbox',
    'title' => 'Sin registros para mostrar',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'py-10 px-6 text-center rounded-lg border border-dashed border-ui-border bg-ui-subtle/60 text-ui-fg']) }}>
    <div class="w-12 h-12 mx-auto mb-3 rounded-lg bg-brand-cobalt/10 dark:bg-brand-cobalt-mid/20 text-brand-cobalt dark:text-brand-cobalt-light flex items-center justify-center">
        <i class="{{ $icon }} text-xl" aria-hidden="true"></i>
    </div>
    <h4 class="text-sm font-bold text-ui-fg">{{ $title }}</h4>
    @if($description)
        <p class="text-xs text-ui-muted mt-1 max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if(!$slot->isEmpty())
        <div class="mt-4 flex justify-center">
            {{ $slot }}
        </div>
    @endif
</div>

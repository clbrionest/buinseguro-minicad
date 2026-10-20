@props([
    'options' => [],
    'placeholder' => 'Seleccione o escriba para buscar...',
    'searchPlaceholder' => 'Escriba parte del nombre para filtrar...',
    'emptyText' => 'Sin coincidencias para la búsqueda',
    'allowClear' => true,
    'allowCustom' => false,
    'disabled' => false,
    'wireAction' => null,
    'dark' => false,
])

@php
    $wireModelAttr = $attributes->wire('model');
    $wireModel = $wireModelAttr->value() ?: null;
    $isLive = $wireModelAttr->hasModifier('live');

    // Normalizar opciones a formato uniforme: [['value' => '...', 'label' => '...', 'meta' => '...'], ...]
    $normalizedOptions = [];
    foreach ($options as $key => $item) {
        if (is_array($item)) {
            $normalizedOptions[] = [
                'value' => (string) ($item['value'] ?? $item['id'] ?? ''),
                'label' => (string) ($item['label'] ?? $item['name'] ?? ''),
                'meta'  => (string) ($item['meta'] ?? ''),
            ];
        } elseif (is_object($item)) {
            $normalizedOptions[] = [
                'value' => (string) ($item->value ?? $item->id ?? ''),
                'label' => (string) ($item->label ?? $item->name ?? ''),
                'meta'  => (string) ($item->meta ?? ''),
            ];
        } else {
            // Si es lista indexada [0 => ' Ruidos'] vs asociativa [id => 'Nombre']
            $val = is_int($key) ? (string) $item : (string) $key;
            $normalizedOptions[] = [
                'value' => $val,
                'label' => (string) $item,
                'meta'  => '',
            ];
        }
    }
@endphp

<div
    x-data="{
        open: false,
        search: '',
        highlightIndex: 0,
        options: @js($normalizedOptions),
        wireModel: @js($wireModel),
        isLive: @js($isLive),
        wireAction: @js($wireAction),
        allowCustom: @js((bool) $allowCustom),
        disabled: @js((bool) $disabled),
        value: '',

        init() {
            if (this.wireModel && typeof $wire !== 'undefined') {
                const initial = $wire.get(this.wireModel);
                this.value = initial !== null && initial !== undefined ? String(initial) : '';
                this.$watch(() => $wire.get(this.wireModel), (newVal) => {
                    this.value = newVal !== null && newVal !== undefined ? String(newVal) : '';
                });
            }
        },

        normalizeStr(str) {
            return String(str || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        },

        get filteredOptions() {
            const q = this.normalizeStr(this.search);
            if (!q) return this.options;
            const terms = q.split(/\s+/).filter(Boolean);
            return this.options.filter(opt => {
                const haystack = this.normalizeStr(opt.label + ' ' + (opt.meta || ''));
                return terms.every(t => haystack.includes(t));
            });
        },

        get selectedOption() {
            if (this.value === '' || this.value === null || this.value === undefined) return null;
            const found = this.options.find(o => String(o.value) === String(this.value));
            if (found) return found;
            if (this.allowCustom) {
                return { value: String(this.value), label: String(this.value), meta: '' };
            }
            return null;
        },

        toggleDropdown() {
            if (this.disabled) return;
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.highlightIndex = 0;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        },

        selectOption(opt) {
            if (this.disabled) return;
            this.value = String(opt.value);
            this.open = false;
            this.search = '';

            if (this.wireModel && typeof $wire !== 'undefined') {
                $wire.set(this.wireModel, opt.value, this.isLive);
            }
            if (this.wireAction && typeof $wire !== 'undefined') {
                $wire.call(this.wireAction, opt.value);
            }
        },

        clearSelection() {
            if (this.disabled) return;
            this.value = '';
            this.search = '';
            if (this.wireModel && typeof $wire !== 'undefined') {
                $wire.set(this.wireModel, '', this.isLive);
            }
        },

        moveHighlight(delta) {
            const len = this.filteredOptions.length;
            if (len === 0) return;
            this.highlightIndex = (this.highlightIndex + delta + len) % len;
            this.$nextTick(() => {
                const listEl = this.$refs.optionsList;
                const activeEl = listEl?.querySelector('[data-highlighted=\'true\']');
                if (activeEl && listEl) {
                    activeEl.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        selectHighlighted() {
            const list = this.filteredOptions;
            if (list.length > 0 && list[this.highlightIndex]) {
                this.selectOption(list[this.highlightIndex]);
            } else if (this.allowCustom && this.search.trim() !== '') {
                const customVal = this.search.trim();
                this.selectOption({ value: customVal, label: customVal, meta: '' });
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.stop="open = false"
    {{ $attributes->except(['wire:model', 'wire:model.live']) }}
    class="relative w-full"
>
    {{-- Botón principal estilo Select2 / CAD --}}
    <div
        @click="toggleDropdown()"
        :class="[
            disabled ? 'opacity-60 cursor-not-allowed bg-slate-100' : 'cursor-pointer',
            open ? 'ring-2 ring-blue-500 border-blue-500' : '',
            @js($dark)
                ? 'bg-slate-950 border-slate-700 text-white hover:border-slate-500'
                : 'bg-white border-slate-300 text-slate-800 hover:border-blue-400'
        ]"
        class="w-full rounded-lg border shadow-sm px-3 py-2 text-sm flex items-center justify-between gap-2 transition select-none min-h-[38px]"
    >
        <div class="truncate flex-1 text-left">
            <template x-if="selectedOption">
                <div class="flex items-center gap-1.5 truncate">
                    <span class="font-bold truncate" x-text="selectedOption.label"></span>
                    <template x-if="selectedOption.meta">
                        <span class="text-[11px] opacity-75 font-semibold truncate" x-text="'(' + selectedOption.meta + ')'"></span>
                    </template>
                </div>
            </template>
            <template x-if="!selectedOption">
                <span class="{{ $dark ? 'text-slate-400' : 'text-slate-400' }} font-normal truncate block">
                    {{ $placeholder }}
                </span>
            </template>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if($allowClear)
                <button
                    type="button"
                    x-show="selectedOption && !disabled"
                    @click.stop="clearSelection()"
                    class="text-slate-400 hover:text-rose-500 px-1 rounded transition"
                    title="Limpiar selección"
                >
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            @endif
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180 text-blue-500' : ''"></i>
        </div>
    </div>

    {{-- Panel Desplegable con Buscador Instantáneo --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        style="display: none;"
        class="absolute left-0 right-0 mt-1 rounded-xl bg-white border border-slate-300 shadow-2xl z-50 overflow-hidden text-slate-800"
    >
        {{-- Barra de búsqueda --}}
        <div class="p-2 bg-slate-50 border-b border-slate-200">
            <div class="relative flex items-center">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3 pointer-events-none"></i>
                <input
                    x-ref="searchInput"
                    type="text"
                    x-model="search"
                    @input="highlightIndex = 0"
                    @keydown.arrow-down.prevent="moveHighlight(1)"
                    @keydown.arrow-up.prevent="moveHighlight(-1)"
                    @keydown.enter.prevent="selectHighlighted()"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full pl-8 pr-7 py-1.5 text-xs font-medium bg-white border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                >
                <button
                    type="button"
                    x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    class="absolute right-2 text-slate-400 hover:text-slate-600"
                >
                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                </button>
            </div>
            <div class="flex justify-between items-center mt-1 px-1 text-[10px] text-slate-400 font-semibold">
                <span x-text="filteredOptions.length + ' resultado(s)'"></span>
                <span>↑↓ navegar · Enter elegir</span>
            </div>
        </div>

        {{-- Lista de resultados filtrados --}}
        <ul
            x-ref="optionsList"
            class="max-h-60 overflow-y-auto divide-y divide-slate-100 py-1 text-xs"
        >
            <template x-for="(opt, idx) in filteredOptions" :key="opt.value + '_' + idx">
                <li
                    @click="selectOption(opt)"
                    @mouseenter="highlightIndex = idx"
                    :data-highlighted="highlightIndex === idx"
                    :class="[
                        String(value) === String(opt.value)
                            ? 'bg-blue-600 text-white font-bold'
                            : (highlightIndex === idx ? 'bg-blue-50 text-blue-900 font-semibold' : 'text-slate-700 hover:bg-slate-50')
                    ]"
                    class="px-3.5 py-2 cursor-pointer flex items-center justify-between gap-2 transition-colors"
                >
                    <div class="truncate">
                        <span x-text="opt.label"></span>
                        <template x-if="opt.meta">
                            <span
                                :class="String(value) === String(opt.value) ? 'text-blue-100' : 'text-slate-400'"
                                class="ml-1.5 text-[11px] font-normal"
                                x-text="'· ' + opt.meta"
                            ></span>
                        </template>
                    </div>
                    <template x-if="String(value) === String(opt.value)">
                        <i class="fa-solid fa-check text-[11px] shrink-0"></i>
                    </template>
                </li>
            </template>

            <template x-if="allowCustom && search.trim() !== ''">
                <li
                    @click="selectOption({ value: search.trim(), label: search.trim(), meta: '' })"
                    class="px-3.5 py-2 cursor-pointer bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold flex items-center justify-between gap-2 border-t border-indigo-200"
                >
                    <span class="truncate">
                        <i class="fa-solid fa-pen-to-square mr-1.5 text-indigo-600"></i>
                        Usar texto libre: "<span x-text="search.trim()"></span>"
                    </span>
                    <span class="text-[10px] uppercase bg-indigo-200 text-indigo-900 px-1.5 py-0.5 rounded">Enter</span>
                </li>
            </template>

            <template x-if="filteredOptions.length === 0 && (!allowCustom || search.trim() === '')">
                <li class="px-4 py-6 text-center text-slate-400 italic text-xs">
                    <i class="fa-solid fa-magnifying-glass-minus block text-base mb-1 opacity-50"></i>
                    <span>{{ $emptyText }}</span>
                </li>
            </template>
        </ul>
    </div>
</div>

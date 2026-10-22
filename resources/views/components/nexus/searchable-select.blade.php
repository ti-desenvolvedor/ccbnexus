@props([
    'options' => [],
    'valueKey' => 'id',
    'labelKey' => 'name',
    'placeholder' => 'Selecione...',
    'searchPlaceholder' => 'Buscar...',
    'emptyMessage' => 'Nenhum resultado encontrado',
    'emptyLabel' => null,
    'disabled' => false,
    'disabledHint' => null,
    'multiple' => false,
    'preview' => 8,
    'id' => null,
])

@php
    $normalized = collect($options)->map(function ($item) use ($valueKey, $labelKey) {
        if (is_array($item)) {
            return [
                'value' => (string) ($item['value'] ?? $item[$valueKey] ?? ''),
                'label' => (string) ($item['label'] ?? $item[$labelKey] ?? ''),
                'disabled' => (bool) ($item['disabled'] ?? false),
            ];
        }

        return [
            'value' => (string) data_get($item, $valueKey),
            'label' => (string) data_get($item, $labelKey),
            'disabled' => false,
        ];
    })->filter(fn (array $item) => $item['label'] !== '')->values();

    if ($emptyLabel !== null && $normalized->where('value', '')->isEmpty()) {
        $normalized->prepend([
            'value' => '',
            'label' => (string) $emptyLabel,
            'disabled' => false,
        ]);
    }

    $normalized = $normalized->values()->all();
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) || $attributes->has('disabled');
    $isMultiple = filter_var($multiple, FILTER_VALIDATE_BOOLEAN);
    $wireModel = $attributes->wire('model');
    $hasWireModel = $wireModel->value() !== null;
    $optionsKey = substr(md5(json_encode($normalized)), 0, 10);
    $controlId = $id ?: 'ns-'.substr(md5(($hasWireModel ? $wireModel->value() : 'static').$optionsKey), 0, 10);
    $componentKey = 'ns-'.($hasWireModel ? $wireModel->value() : 'static').'-'.$optionsKey.'-'.($isDisabled ? 'd' : 'e');
    $ariaLabel = $attributes->get('aria-label');
@endphp

<div
    wire:key="{{ $componentKey }}"
    @if ($hasWireModel)
        x-data="nexusSearchableSelect({
            options: @js($normalized),
            selected: @entangle($wireModel),
            placeholder: @js($placeholder),
            searchPlaceholder: @js($searchPlaceholder),
            emptyMessage: @js($emptyMessage),
            disabled: @js($isDisabled),
            multiple: @js($isMultiple),
            previewLimit: @js((int) $preview),
        })"
    @else
        x-data="nexusSearchableSelect({
            options: @js($normalized),
            selected: null,
            placeholder: @js($placeholder),
            searchPlaceholder: @js($searchPlaceholder),
            emptyMessage: @js($emptyMessage),
            disabled: @js($isDisabled),
            multiple: @js($isMultiple),
            previewLimit: @js((int) $preview),
        })"
    @endif
    x-on:click.window="closeIfOutside($event)"
    {{ $attributes->whereDoesntStartWith('wire:model')->except(['aria-label', 'disabled'])->merge(['class' => 'relative min-w-0']) }}
>
    <button
        type="button"
        id="{{ $controlId }}"
        x-ref="trigger"
        x-on:click="toggle()"
        x-on:keydown="onTriggerKeydown($event)"
        x-bind:disabled="disabled"
        x-bind:aria-expanded="open"
        aria-haspopup="listbox"
        aria-controls="{{ $controlId }}-list"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if ($isDisabled && filled($disabledHint)) title="{{ $disabledHint }}" @endif
        class="flex min-h-11 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
    >
        <span class="truncate" x-text="selectedLabel || placeholder" x-bind:class="selectedLabel ? '' : 'text-slate-600 dark:text-slate-300'"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-600 transition-transform dark:text-slate-300" x-bind:class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-ref="panel"
            x-cloak
            x-show="open"
            x-transition.opacity.duration.150ms
            x-bind:style="panelStyle"
            class="fixed z-[80] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_16px_40px_-12px_rgba(15,23,42,0.35)] dark:border-slate-700 dark:bg-slate-900"
        >
            <div class="border-b border-slate-200 p-2 dark:border-slate-800">
                <input
                    type="search"
                    x-ref="search"
                    x-model="search"
                    x-on:keydown.arrow-down.prevent="highlightNext()"
                    x-on:keydown.arrow-up.prevent="highlightPrev()"
                    x-on:keydown.enter.prevent="chooseHighlighted()"
                    x-on:keydown.escape.prevent="close(true)"
                    x-bind:placeholder="searchPlaceholder"
                    x-bind:aria-controls="'{{ $controlId }}-list'"
                    class="min-h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-300"
                    autocomplete="off"
                />
            </div>
            <ul id="{{ $controlId }}-list" role="listbox" class="max-h-80 overflow-y-auto py-1" x-bind:aria-multiselectable="multiple">
                <template x-for="(option, index) in visible" :key="option.value + '-' + index">
                    <li>
                        <button
                            type="button"
                            role="option"
                            x-bind:disabled="option.disabled"
                            x-bind:aria-selected="isChosen(option)"
                            x-bind:data-highlighted="index === highlighted ? '1' : '0'"
                            x-on:mouseenter="highlighted = index"
                            x-on:click="choose(option)"
                            class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm text-slate-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-slate-100"
                            x-bind:class="{
                                'bg-slate-100 dark:bg-slate-800': index === highlighted && !isChosen(option),
                                'bg-primary-50 font-medium text-primary-900 dark:bg-slate-800 dark:text-primary-100': isChosen(option),
                            }"
                        >
                            <span x-show="multiple" class="grid h-4 w-4 shrink-0 place-items-center rounded border border-slate-300 dark:border-slate-600" x-bind:class="isChosen(option) && 'border-primary-600 bg-primary-600 text-white'">
                                <svg x-show="isChosen(option)" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span class="truncate" x-text="option.label"></span>
                        </button>
                    </li>
                </template>
                <li x-show="visible.length === 0" class="px-3 py-4 text-center text-sm text-slate-600 dark:text-slate-300" x-text="emptyMessage"></li>
            </ul>
            <p x-show="moreCount > 0" class="border-t border-slate-200 px-3 py-2 text-xs text-slate-600 dark:border-slate-800 dark:text-slate-300">
                <span x-show="!search.trim()">Mais <span x-text="moreCount"></span>. Digite para filtrar.</span>
                <span x-show="search.trim()">Há mais resultados. Continue digitando.</span>
            </p>
        </div>
    </template>
</div>

@once
    <style>[x-cloak]{display:none !important}</style>
    <script>
        (function () {
            const factory = (config) => ({
                open: false,
                search: '',
                highlighted: 0,
                panelStyle: '',
                options: config.options || [],
                selected: config.selected ?? null,
                placeholder: config.placeholder || 'Selecione...',
                searchPlaceholder: config.searchPlaceholder || 'Buscar...',
                emptyMessage: config.emptyMessage || 'Nenhum resultado encontrado',
                disabled: !!config.disabled,
                multiple: !!config.multiple,
                previewLimit: Number(config.previewLimit || 8),

                init() {
                    this.$watch('search', () => {
                        const next = this.visible.findIndex((option) => !option.disabled);
                        this.highlighted = next < 0 ? 0 : next;
                    });
                    this.onReflow = () => {
                        if (this.open) this.place();
                    };
                    window.addEventListener('scroll', this.onReflow, true);
                    window.addEventListener('resize', this.onReflow);
                },

                destroy() {
                    window.removeEventListener('scroll', this.onReflow, true);
                    window.removeEventListener('resize', this.onReflow);
                },

                fold(value) {
                    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                },

                coerce(value) {
                    return String(value ?? '');
                },

                get matched() {
                    const term = this.fold(this.search.trim());
                    if (!term) return this.options;
                    return this.options.filter((option) => this.fold(option.label).includes(term));
                },

                get visible() {
                    const cap = this.search.trim() ? 40 : this.previewLimit;
                    return this.matched.slice(0, cap);
                },

                get moreCount() {
                    const cap = this.search.trim() ? 40 : this.previewLimit;
                    return Math.max(0, this.matched.length - cap);
                },

                get selectedLabel() {
                    if (this.multiple) {
                        const chosen = this.chosenValues();
                        if (!chosen.length) return '';
                        const labels = chosen
                            .map((value) => this.options.find((option) => String(option.value) === String(value))?.label)
                            .filter(Boolean);
                        if (!labels.length) return chosen.length + ' selecionados';
                        if (labels.length <= 2) return labels.join(', ');
                        return labels.length + ' selecionados';
                    }

                    const value = this.selected === null || this.selected === undefined ? '' : String(this.selected);
                    if (value === '') return '';
                    return this.options.find((option) => String(option.value) === value)?.label || '';
                },

                chosenValues() {
                    return Array.isArray(this.selected) ? this.selected : [];
                },

                isChosen(option) {
                    if (this.multiple) {
                        return this.chosenValues().some((value) => String(value) === String(option.value));
                    }
                    const value = this.selected === null || this.selected === undefined ? '' : String(this.selected);
                    return String(option.value) === value;
                },

                toggle() {
                    if (this.disabled) return;
                    if (this.open) {
                        this.close(true);
                        return;
                    }
                    this.openPanel();
                },

                openPanel(seed = '') {
                    if (this.disabled) return;
                    this.open = true;
                    this.search = seed;
                    this.highlighted = Math.max(0, this.visible.findIndex((option) => !option.disabled));
                    this.$nextTick(() => {
                        this.place();
                        this.$refs.search?.focus();
                        if (seed) this.$refs.search?.setSelectionRange(seed.length, seed.length);
                    });
                },

                close(restoreFocus = false) {
                    this.open = false;
                    this.search = '';
                    this.highlighted = 0;
                    if (restoreFocus) this.$nextTick(() => this.$refs.trigger?.focus());
                },

                closeIfOutside(event) {
                    if (!this.open) return;
                    const target = event.target;
                    if (this.$refs.trigger?.contains(target) || this.$refs.panel?.contains(target)) return;
                    this.close();
                },

                place() {
                    const trigger = this.$refs.trigger;
                    if (!trigger) return;
                    const rect = trigger.getBoundingClientRect();
                    const width = Math.min(Math.max(rect.width, 240), window.innerWidth - 16);
                    let left = rect.left;
                    if (left + width > window.innerWidth - 8) {
                        left = Math.max(8, window.innerWidth - width - 8);
                    }
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const openUp = spaceBelow < 280 && rect.top > spaceBelow;
                    this.panelStyle = openUp
                        ? `left:${left}px;width:${width}px;bottom:${Math.max(8, window.innerHeight - rect.top + 6)}px;top:auto;`
                        : `left:${left}px;width:${width}px;top:${rect.bottom + 6}px;bottom:auto;`;
                },

                onTriggerKeydown(event) {
                    if (this.disabled) return;
                    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        this.openPanel();
                        return;
                    }
                    if (event.key.length === 1 && !event.altKey && !event.ctrlKey && !event.metaKey) {
                        event.preventDefault();
                        this.openPanel(event.key);
                    }
                },

                highlightNext() {
                    this.moveHighlight(1);
                },

                highlightPrev() {
                    this.moveHighlight(-1);
                },

                moveHighlight(step) {
                    const list = this.visible;
                    if (!list.length) return;
                    let index = this.highlighted;
                    for (let attempt = 0; attempt < list.length; attempt++) {
                        index = (index + step + list.length) % list.length;
                        if (!list[index].disabled) {
                            this.highlighted = index;
                            this.$nextTick(() => {
                                this.$refs.panel?.querySelector('[data-highlighted="1"]')?.scrollIntoView({ block: 'nearest' });
                            });
                            return;
                        }
                    }
                },

                chooseHighlighted() {
                    const option = this.visible[this.highlighted] || this.visible.find((item) => !item.disabled);
                    if (option) this.choose(option);
                },

                choose(option) {
                    if (!option || option.disabled) return;
                    if (this.multiple) {
                        const list = this.chosenValues().map((value) => this.coerce(value));
                        const value = this.coerce(option.value);
                        const index = list.findIndex((item) => String(item) === String(value));
                        if (index >= 0) list.splice(index, 1);
                        else list.push(value);
                        this.selected = list;
                        this.$dispatch('nexus-select', this.selected);
                        return;
                    }
                    this.selected = this.coerce(option.value);
                    this.$dispatch('nexus-select', this.selected);
                    this.close(true);
                },
            });

            window.nexusSearchableSelect = factory;

            const register = () => {
                if (!window.Alpine || window.Alpine.__nexusSearchableSelect) return;
                window.Alpine.__nexusSearchableSelect = true;
                window.Alpine.data('nexusSearchableSelect', factory);
            };

            document.addEventListener('alpine:init', register);
            register();
        })();
    </script>
@endonce

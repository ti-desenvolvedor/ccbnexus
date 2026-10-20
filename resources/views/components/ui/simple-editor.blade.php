@props([
    'model',
    'value' => '',
    'fields' => [],
    'live' => false,
    'placeholder' => 'Escreva aqui…',
    'bodyClass' => 'min-h-40',
])

<div
    class="simple-editor"
    x-data="{
        model: @js($model),
        fields: @js(array_values($fields)),
        live: @js((bool) $live),
        fieldsOpen: false,
        timer: null,
        lastSent: null,
        dirty: false,
        init() {
            this.$nextTick(() => {
                const initial = this.$refs.store.value || '';
                this.lastSent = initial;
                this.dirty = false;
                this.paint(initial);
            });
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('submit', () => this.sync(), true);
            }
        },
        onLoad(event) {
            if ((event.detail?.target ?? '') !== this.model) {
                return;
            }
            const html = event.detail?.html ?? '';
            this.dirty = false;
            this.lastSent = html;
            this.paint(html);
            if (this.$refs.store.value !== html) {
                this.$refs.store.value = html;
            }
        },
        paint(html) {
            const value = html || '';
            const box = this.$refs.box;
            if (!box || document.activeElement === box) {
                return;
            }
            if (/<\/?[a-z][\s\S]*>/i.test(value)) {
                box.innerHTML = value;
                return;
            }
            box.innerHTML = value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        },
        current() {
            const box = this.$refs.box;
            const text = (box.innerText || '').replace(/\u00a0/g, ' ').trim();
            return text === '' ? '' : box.innerHTML;
        },
        sync() {
            if (!this.dirty) {
                return this.$refs.store.value;
            }
            const value = this.current();
            const store = this.$refs.store;
            if (store.value === value) {
                return value;
            }
            store.value = value;
            store.dispatchEvent(new Event('input', { bubbles: true }));
            return value;
        },
        push() {
            const value = this.sync();
            if (!this.live || value === this.lastSent) {
                return;
            }
            this.lastSent = value;
            this.$wire.set(this.model, value);
        },
        queue() {
            this.dirty = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.push(), 400);
        },
        format(command) {
            this.dirty = true;
            this.$refs.box.focus();
            document.execCommand(command, false, null);
            this.push();
        },
        insertToken(token) {
            this.dirty = true;
            const box = this.$refs.box;
            box.focus();
            const inserted = document.execCommand('insertText', false, token);
            if (!inserted) {
                const sel = window.getSelection();
                if (sel && sel.rangeCount && box.contains(sel.anchorNode)) {
                    const range = sel.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(document.createTextNode(token));
                    range.collapse(false);
                } else {
                    box.append(document.createTextNode(token));
                }
            }
            this.fieldsOpen = false;
            this.push();
        },
    }"
    x-on:simple-editor-load.window="onLoad($event)"
>
    <textarea x-ref="store" wire:model="{{ $model }}" class="hidden" tabindex="-1" aria-hidden="true" rows="1">{{ $value }}</textarea>

    <div wire:ignore class="rounded-xl border border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap items-center gap-1 rounded-t-xl border-b border-slate-200 bg-slate-50 px-2 py-1.5 dark:border-slate-800 dark:bg-slate-900">
            <button type="button" class="min-h-11 rounded-lg px-3 text-sm font-bold text-slate-700 hover:bg-white dark:text-slate-100 dark:hover:bg-slate-800" @mousedown.prevent="format('bold')">Negrito</button>
            <button type="button" class="min-h-11 rounded-lg px-3 text-sm italic text-slate-700 hover:bg-white dark:text-slate-100 dark:hover:bg-slate-800" @mousedown.prevent="format('italic')">Itálico</button>
            <button type="button" class="min-h-11 rounded-lg px-3 text-sm text-slate-700 underline hover:bg-white dark:text-slate-100 dark:hover:bg-slate-800" @mousedown.prevent="format('underline')">Sublinhado</button>
            <button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-slate-700 hover:bg-white dark:text-slate-100 dark:hover:bg-slate-800" @mousedown.prevent="format('insertUnorderedList')">Marcadores</button>
            @if (count($fields) > 0)
                <div class="relative z-20" @click.outside="fieldsOpen = false">
                    <button type="button" class="rounded-lg bg-white px-2 py-1 text-xs font-semibold text-primary-700 ring-1 ring-slate-200 hover:bg-primary-50 dark:bg-slate-950 dark:text-primary-300 dark:ring-slate-700" @click="fieldsOpen = !fieldsOpen">
                        Inserir campo
                    </button>
                    <div x-show="fieldsOpen" x-cloak class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-950">
                        <template x-for="field in fields" :key="field.token">
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-900" @mousedown.prevent="insertToken(field.token)">
                                <span class="block" x-text="field.label"></span>
                                <span class="block text-xs text-slate-400" x-text="field.token"></span>
                            </button>
                        </template>
                    </div>
                </div>
            @endif
        </div>
        <div
            x-ref="box"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            data-placeholder="{{ $placeholder }}"
            class="simple-editor-body {{ $bodyClass }} max-h-96 overflow-y-auto rounded-b-xl px-3 py-2 text-sm leading-relaxed text-slate-800 outline-none dark:bg-slate-950 dark:text-slate-100 [&_b]:font-bold [&_em]:italic [&_i]:italic [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-bold [&_u]:underline [&_ul]:my-1 [&_ul]:list-disc [&_ul]:pl-5"
            @input="queue()"
            @blur="push()"
            @keydown.enter.stop
        ></div>
    </div>
    <style>
        .simple-editor-body:empty:before {
            content: attr(data-placeholder);
            color: rgb(148 163 184);
        }
    </style>
</div>

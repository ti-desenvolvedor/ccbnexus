import './bootstrap';
import { registerProfilePhotoCrop } from './profile-photo-crop';

function createNexusStore() {
    return {
        sidebarOpen: false,
        sidebarDensity: 'expanded', // expanded|icons
        sidebarPeek: false,
        sidebarHoverCapable: false,
        sidebarPeekTimer: null,
        sidebarListenersBound: false,
        theme: 'light', // light|dark
        palette: 'blue', // blue|navy|green|green_dark|red|red_dark|orange|brown
        path: '/',
        menu: {
            org: true,
            ops: false,
            agenda: false,
            secretaria: false,
            catalog: false,
            facilitator: true,
        },
        goChord: null,
        goChordTimer: null,

        boot(hints = null) {
            this.path = String(window?.location?.pathname || '/');

            const storedTheme = localStorage.getItem('nexus.theme');
            const storedPalette = localStorage.getItem('nexus.palette');
            const accountTheme = hints?.theme;
            const accountPalette = hints?.palette;

            this.theme = storedTheme || accountTheme || 'light';
            this.palette = storedPalette || accountPalette || 'blue';
            this.applySidebarDensity(hints?.sidebar === 'icons' ? 'icons' : 'expanded');
            this.sidebarHoverCapable = typeof window.matchMedia === 'function'
                && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.bindSidebarChrome();

            try {
                const raw = localStorage.getItem('nexus.menu');
                if (raw) {
                    const parsed = JSON.parse(raw);
                    this.menu = { ...this.menu, ...parsed };
                }
            } catch {
                // ignore invalid JSON
            }

            if (hints && typeof hints === 'object') {
                if (hints.org) {
                    this.menu.org = true;
                }
                if (hints.ops) {
                    this.menu.ops = true;
                }
                if (hints.agenda) {
                    this.menu.agenda = true;
                }
                if (hints.secretaria) {
                    this.menu.secretaria = true;
                }
                if (hints.facilitator) {
                    this.menu.facilitator = true;
                }
                localStorage.setItem('nexus.menu', JSON.stringify(this.menu));
            }

            this.syncMenuWithPath();

            this.applyTheme();
            this.applyPalette();
        },

        setPath(pathname) {
            this.path = String(pathname || '/');
            this.syncMenuWithPath();
        },

        isActivePrefix(prefixes) {
            const p = String(this.path || '/');
            const list = Array.isArray(prefixes) ? prefixes : [prefixes];
            return list.some((x) => {
                const pref = String(x || '');
                if (!pref) return false;
                return p === pref || p.startsWith(pref + '/') || p.startsWith(pref);
            });
        },

        isActiveExact(pathname) {
            return String(this.path || '/') === String(pathname || '/');
        },

        syncMenuWithPath() {
            const hints = nexusHintsFromPathname(this.path);

            if (hints.org) {
                this.menu.org = true;
            }
            if (hints.ops) {
                this.menu.ops = true;
            }
            if (hints.agenda) {
                this.menu.agenda = true;
            }
            if (hints.secretaria) {
                this.menu.secretaria = true;
            }
            if (hints.catalog) {
                this.menu.catalog = true;
            }
            if (hints.facilitator) {
                this.menu.facilitator = true;
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            this.syncSidebarScrollLock();
        },

        closeSidebar() {
            this.sidebarOpen = false;
            this.syncSidebarScrollLock();
        },

        syncSidebarScrollLock() {
            if (typeof document === 'undefined') {
                return;
            }
            const lock = this.sidebarOpen && !window.matchMedia('(min-width: 1024px)').matches;
            document.documentElement.style.overflow = lock ? 'hidden' : '';
        },

        ensureSidebarExpandedForNavigation() {
            if (typeof window === 'undefined') {
                return;
            }

            this.closeSidebar();
            if (!this.sidebarHoverCapable) {
                this.sidebarPeek = false;
            }
        },

        applySidebarDensity(mode) {
            if (mode !== 'icons' && mode !== 'expanded') {
                return;
            }

            this.sidebarDensity = mode;
            this.sidebarPeek = false;
            clearTimeout(this.sidebarPeekTimer);

            try {
                localStorage.setItem('nexus.sidebar', this.sidebarDensity);
            } catch {
                // ignore private mode
            }
        },

        isSidebarRail() {
            return this.sidebarDensity === 'icons' && !this.sidebarPeek;
        },

        armSidebarPeek() {
            if (this.sidebarDensity !== 'icons') {
                return;
            }

            clearTimeout(this.sidebarPeekTimer);
            this.sidebarPeek = true;
        },

        releaseSidebarPeek() {
            if (this.sidebarDensity !== 'icons' || !this.sidebarHoverCapable) {
                return;
            }

            clearTimeout(this.sidebarPeekTimer);
            this.sidebarPeekTimer = setTimeout(() => {
                this.sidebarPeek = false;
            }, 160);
        },

        bindSidebarChrome() {
            if (this.sidebarListenersBound || typeof document === 'undefined') {
                return;
            }

            this.sidebarListenersBound = true;

            document.addEventListener('pointerdown', (event) => {
                if (this.sidebarDensity !== 'icons' || !this.sidebarPeek || this.sidebarHoverCapable) {
                    return;
                }

                const aside = document.querySelector('.nexus-sidebar');
                if (aside && !aside.contains(event.target)) {
                    this.sidebarPeek = false;
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.sidebarPeek && this.sidebarDensity === 'icons') {
                    this.sidebarPeek = false;
                }
            });
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('nexus.theme', this.theme);
            this.applyTheme();
        },

        setTheme(theme) {
            this.theme = theme === 'dark' ? 'dark' : 'light';
            localStorage.setItem('nexus.theme', this.theme);
            this.applyTheme();
        },

        setPalette(palette) {
            const allowed = [
                'blue',
                'navy',
                'green',
                'green_dark',
                'red',
                'red_dark',
                'orange',
                'brown',
            ];
            this.palette = allowed.includes(palette) ? palette : 'blue';
            localStorage.setItem('nexus.palette', this.palette);
            this.applyPalette();
        },

        toggleMenu(key) {
            if (!Object.prototype.hasOwnProperty.call(this.menu, key)) {
                this.menu[key] = false;
            }

            this.closeSidebar();
            this.armSidebarPeek();

            const subMenuKeys = ['catalog'];
            const isSubMenu = subMenuKeys.includes(key);
            const next = !this.menu[key];

            if (!isSubMenu) {
                this.menu.org = false;
                this.menu.ops = false;
                this.menu.agenda = false;
                this.menu.secretaria = false;
                this.menu.facilitator = false;
            }
            this.menu[key] = next;
            localStorage.setItem('nexus.menu', JSON.stringify(this.menu));
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
        },

        applyPalette() {
            document.documentElement.dataset.palette = this.palette;
        },
    };
}

function nexusCommandPalette(commands = []) {
    return {
        open: false,
        cheatsheet: false,
        query: '',
        active: 0,
        commands,
        chord: null,
        chordTimer: null,
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.commands;
            return this.commands.filter((c) => {
                const hay = `${c.label} ${c.keywords || ''}`.toLowerCase();
                return hay.includes(q);
            });
        },
        openPalette() {
            this.open = true;
            this.query = '';
            this.active = 0;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        close() {
            this.open = false;
            this.query = '';
        },
        move(delta) {
            const len = this.filtered.length;
            if (!len) return;
            this.active = (this.active + delta + len) % len;
        },
        activate() {
            const cmd = this.filtered[this.active];
            if (!cmd?.href) return;
            this.close();
            if (window.Livewire?.navigate) {
                window.Livewire.navigate(cmd.href);
            } else {
                window.location.href = cmd.href;
            }
        },
        isTypingTarget(el) {
            if (!el) return false;
            const tag = (el.tagName || '').toLowerCase();
            return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
        },
        go(href) {
            if (!href) return;
            if (window.Livewire?.navigate) {
                window.Livewire.navigate(href);
            } else {
                window.location.href = href;
            }
        },
        resolveChord(second) {
            const map = {
                gd: this.commands.find((c) => c.id === 'dash')?.href,
                ge: this.commands.find((c) => c.id === 'events')?.href,
                gr: this.commands.find((c) => c.id === 'reservations')?.href,
                gu: this.commands.find((c) => c.id === 'users')?.href,
                ne: this.commands.find((c) => c.id === 'new-event')?.href,
                nr: this.commands.find((c) => c.id === 'new-res')?.href,
            };
            return map[`${this.chord}${second}`] || null;
        },
        onGlobalKey(e) {
            if (window.Alpine?.store('nexusDialog')?.open) {
                return;
            }
            if (this.isTypingTarget(e.target) && e.key !== 'Escape') {
                return;
            }
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.openPalette();
                return;
            }
            if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.openPalette();
                return;
            }
            if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                this.cheatsheet = !this.cheatsheet;
                return;
            }
            if (e.key === 'Escape') {
                this.close();
                this.cheatsheet = false;
                this.chord = null;
                return;
            }
            const key = e.key.toLowerCase();
            if (this.chord) {
                const href = this.resolveChord(key);
                this.chord = null;
                clearTimeout(this.chordTimer);
                if (href) {
                    e.preventDefault();
                    this.go(href);
                }
                return;
            }
            if (key === 'g' || key === 'n') {
                this.chord = key;
                clearTimeout(this.chordTimer);
                this.chordTimer = setTimeout(() => {
                    this.chord = null;
                }, 900);
            }
        },
    };
}

function nexusFormProgress() {
    return {
        filled: 0,
        total: 0,
        percent: 0,
        label: '',
        _timer: null,

        init() {
            const form = this.$el.closest('form');
            const tick = () => this.measure();
            tick();
            if (form) {
                form.addEventListener('input', tick);
                form.addEventListener('change', tick);
                this._observer = new MutationObserver(tick);
                this._observer.observe(form, {
                    subtree: true,
                    childList: true,
                    attributes: true,
                    attributeFilter: ['data-progress-value'],
                });
            }
            const hook = () => {
                if (!window.Livewire || this._hooked) {
                    return;
                }
                this._hooked = true;
                Livewire.hook('morph.updated', tick);
            };
            hook();
            document.addEventListener('livewire:init', hook, { once: true });
            this._timer = setInterval(tick, 400);
        },

        destroy() {
            clearInterval(this._timer);
            this._observer?.disconnect();
        },

        measure() {
            const form = this.$el.closest('form');
            if (!form) {
                return;
            }

            const fields = this.fields(form);
            const total = fields.length;
            const filled = fields.filter((field) => this.isFilled(field)).length;
            this.total = total;
            this.filled = filled;
            this.percent = total === 0 ? 0 : Math.round((filled / total) * 100);
            const left = total - filled;
            this.label = left === 0
                ? 'Tudo preenchido'
                : (left === 1 ? 'Falta 1 campo' : `Faltam ${left} campos`);
        },

        fields(form) {
            const marked = [...form.querySelectorAll('[data-progress-field]')].filter((field) => !this.$el.contains(field));
            if (form.dataset.progress === 'marked' || marked.length > 0) {
                return marked;
            }

            return [...form.querySelectorAll('input, textarea, select')].filter((field) => {
                if (this.$el.contains(field) || field.closest('[data-progress=skip]')) {
                    return false;
                }
                const type = (field.getAttribute('type') || '').toLowerCase();
                if (['hidden', 'checkbox', 'radio', 'submit', 'button', 'search', 'file'].includes(type)) {
                    return false;
                }
                const holder = field.closest('div');
                const label = holder ? holder.querySelector('label') : null;
                const text = `${label ? label.textContent : ''} ${field.getAttribute('placeholder') || ''}`;
                return !/opcional/i.test(text);
            });
        },

        isFilled(field) {
            if (field.hasAttribute('data-progress-value')) {
                return field.getAttribute('data-progress-value').trim() !== '';
            }
            return String(field.value || '').trim() !== '';
        },
    };
}

function nexusPasswordMeter(inputId, min, strongLength) {
    return {
        level: '',
        label: '',
        note: '',
        barStyle: 'width:0%',
        labelStyle: '',
        value: '',
        leaked: null,
        checking: false,
        timer: null,
        init() {
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            const form = input.closest('form');
            if (form && form.dataset.passwordGuard !== '1') {
                form.dataset.passwordGuard = '1';
                form.addEventListener('submit', (event) => {
                    if (form.dataset.passwordBlocked === '1') {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    }
                }, true);
            }
            const run = () => {
                const next = input.value || '';
                if (next === this.value && this.level !== '') {
                    return;
                }
                this.onInput(next);
            };
            input.addEventListener('input', run);
            input.addEventListener('change', run);
            if (window.Livewire?.hook) {
                window.Livewire.hook('morph.updated', () => {
                    if (document.body.contains(input)) {
                        run();
                    }
                });
            }
            run();
        },
        onInput(value) {
            this.value = value;
            this.leaked = null;
            this.checking = false;
            window.clearTimeout(this.timer);
            const level = this.score(value);
            this.checking = level !== '' && level !== 'weak';
            this.paint(level, null);
            if (!this.checking) {
                this.note = level === 'weak' ? 'Senha fraca não pode ser guardada.' : '';
                this.syncForm();
                return;
            }
            this.timer = window.setTimeout(() => this.lookup(value), 250);
        },
        score(value) {
            if (value === '') {
                return '';
            }
            const length = Array.from(value).length;
            const hasLetter = /\p{L}/u.test(value);
            const hasNumber = /\p{N}/u.test(value);
            const hasSymbol = /[\p{Z}\p{S}\p{P}]/u.test(value);
            const hasMixed = /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value);
            if (length < min || !hasLetter || !hasNumber || !hasSymbol || !hasMixed) {
                return 'weak';
            }
            if (length >= strongLength) {
                return 'strong';
            }
            return 'medium';
        },
        paint(level, leaked) {
            this.level = level;
            this.leaked = leaked;
            if (level === '') {
                this.label = '';
                this.note = '';
                this.barStyle = 'width:0%';
                this.syncForm();
                return;
            }
            if (this.checking && leaked !== true) {
                this.label = 'Analisando senha…';
                this.note = '';
                this.labelStyle = 'color:#334155';
                this.barStyle = 'width:35%;background:#64748b';
                this.syncForm();
                return;
            }
            if (leaked === true) {
                this.checking = false;
                this.label = 'Vazada';
                this.note = 'Senha vazada não pode ser guardada. Escolha outra.';
                this.barStyle = 'width:100%;background:#e11d48';
                this.labelStyle = 'color:#be123c';
                this.syncForm();
                return;
            }
            const tones = {
                weak: ['Fraca', '33%', '#e11d48', '#be123c'],
                medium: ['Média', '66%', '#d97706', '#b45309'],
                strong: ['Forte', '100%', '#059669', '#047857'],
            };
            const tone = tones[level] || tones.weak;
            this.label = tone[0];
            this.barStyle = `width:${tone[1]};background:${tone[2]}`;
            this.labelStyle = `color:${tone[3]}`;
            this.syncForm();
        },
        syncForm() {
            const form = this.$el?.closest('form');
            if (!form) {
                return;
            }
            const blocked = this.value !== '' && (this.level === 'weak' || this.leaked === true || this.checking);
            form.dataset.passwordBlocked = blocked ? '1' : '0';
            form.querySelectorAll('[type="submit"]').forEach((button) => {
                button.disabled = blocked;
                button.style.opacity = blocked ? '0.45' : '';
                button.style.cursor = blocked ? 'not-allowed' : '';
            });
        },
        async lookup(value) {
            if (value !== this.value) {
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            try {
                const response = await fetch(this.$el.dataset.passwordCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ password: value }),
                });
                if (value !== this.value) {
                    return;
                }
                if (!response.ok) {
                    this.checking = false;
                    this.paint(this.level, null);
                    this.note = 'Não foi possível verificar vazamentos agora. A verificação repete ao guardar.';
                    this.syncForm();
                    return;
                }
                const data = await response.json();
                if (value !== this.value) {
                    return;
                }
                this.checking = false;
                this.paint(data.level || this.level, data.leaked);
                if (data.leaked === false) {
                    this.note = '';
                }
                if (data.leaked === null) {
                    this.note = 'Não foi possível verificar vazamentos agora. A verificação repete ao guardar.';
                }
                this.syncForm();
            } catch (error) {
                if (value === this.value) {
                    this.checking = false;
                    this.note = 'Não foi possível verificar vazamentos agora. A verificação repete ao guardar.';
                    this.syncForm();
                }
            }
        },
    };
}

function registerNexusAlpine(Alpine) {
    if (!Alpine) {
        return;
    }

    if (!Alpine.store('nexus')) {
        Alpine.store('nexus', createNexusStore());
    }

    if (!Alpine.store('nexusDialog')) {
        Alpine.store('nexusDialog', createDialogStore());
    }

    Alpine.data('nexusCommandPalette', nexusCommandPalette);
    Alpine.data('nexusFormProgress', nexusFormProgress);
    Alpine.data('nexusPasswordMeter', nexusPasswordMeter);

    registerProfilePhotoCrop(Alpine);
}

// Livewire v3 fornece Alpine; registar no alpine:init (e fallback se já existir).
document.addEventListener('alpine:init', () => {
    registerNexusAlpine(window.Alpine);
});

if (window.Alpine) {
    registerNexusAlpine(window.Alpine);
}

function nexusHintsFromPathname(pathname) {
    const p = String(pathname || '/');
    return {
        org: p.startsWith('/organization') || p.startsWith('/users') || p.startsWith('/access'),
        ops: p.startsWith('/infrastructure'),
        agenda: p.startsWith('/agenda') || p.startsWith('/reports'),
        secretaria: p.startsWith('/secretaria'),
        catalog: p.startsWith('/agenda/public-catalog'),
        facilitator: p.startsWith('/facilitator') || p.startsWith('/access') || p.startsWith('/agenda/approvals'),
    };
}

function nexusSyncAfterNavigation() {
    try {
        const Alpine = window.Alpine;
        const store = Alpine?.store('nexus');
        if (!store) return;

        const pathname = window.location?.pathname;
        store.setPath(pathname);
        store.closeSidebar();
    } catch {
        // ignore
    }
}

// Livewire v3 navigation doesn't always re-run Alpine x-init.
// Ensure store state is synced to current pathname.
document.addEventListener('livewire:navigated', nexusSyncAfterNavigation);
window.addEventListener('livewire:navigated', nexusSyncAfterNavigation);

function createDialogStore() {
    return {
        open: false,
        mode: 'ok',
        title: '',
        message: '',
        confirmLabel: 'OK',
        cancelLabel: 'Cancelar',
        tone: 'info',
        icon: 'info',
        queue: [],
        _resolve: null,
        _returnFocus: null,

        ask(spec) {
            return new Promise((resolve) => {
                this.queue.push({ spec, resolve });
                this.pump();
            });
        },

        pump() {
            if (this.open || this.queue.length === 0) {
                return;
            }

            const next = this.queue.shift();
            const spec = next.spec || {};
            this.mode = spec.mode === 'confirm' ? 'confirm' : 'ok';
            this.title = spec.title || (this.mode === 'ok' ? 'Aviso' : 'Confirmar');
            this.message = spec.message || '';
            this.confirmLabel = spec.confirmLabel || (this.mode === 'ok' ? 'OK' : 'Confirmar');
            this.cancelLabel = spec.cancelLabel || 'Cancelar';
            this.tone = ['danger', 'warning', 'success', 'info'].includes(spec.tone) ? spec.tone : 'info';
            this.icon = spec.icon || (this.tone === 'danger' ? 'danger' : (this.tone === 'warning' ? 'alert' : (this.tone === 'success' ? 'check' : (this.mode === 'ok' ? 'info' : 'help'))));
            this._resolve = next.resolve;
            this._returnFocus = document.activeElement;
            this.open = true;
        },

        accept() {
            this.finish(true);
        },

        dismiss() {
            this.finish(this.mode === 'ok');
        },

        finish(value) {
            const resolve = this._resolve;
            const back = this._returnFocus;
            this.open = false;
            this._resolve = null;
            this._returnFocus = null;
            if (typeof resolve === 'function') {
                resolve(value);
            }
            if (back && typeof back.focus === 'function') {
                back.focus();
            }
            queueMicrotask(() => this.pump());
        },
    };
}

function nexusDialogSpec(raw, fallbackMode) {
    const text = String(raw ?? '').replaceAll('\\n', '\n').trim();

    if (text.includes('This page has expired')) {
        return {
            mode: 'confirm',
            title: 'A página expirou',
            message: 'Recarregue para continuar de onde estava.',
            confirmLabel: 'Recarregar',
            cancelLabel: 'Agora não',
            tone: 'warning',
            icon: 'refresh',
            reload: true,
        };
    }

    const questionAt = text.indexOf('?');
    const title = questionAt > 0 ? text.slice(0, questionAt + 1) : (fallbackMode === 'ok' ? 'Aviso' : 'Confirmar');
    const message = questionAt > 0 ? text.slice(questionAt + 1).trim() : (fallbackMode === 'ok' ? text : (questionAt === 0 ? text : ''));
    const visual = nexusDialogVisual(text, fallbackMode);

    if (fallbackMode === 'ok') {
        return {
            mode: 'ok',
            title: questionAt > 0 ? title : 'Aviso',
            message: questionAt > 0 ? message : text,
            confirmLabel: 'OK',
            tone: visual.tone,
            icon: visual.icon,
            reload: false,
        };
    }

    return {
        mode: 'confirm',
        title,
        message: message || (questionAt > 0 ? '' : text || 'Quer continuar?'),
        confirmLabel: 'Confirmar',
        cancelLabel: 'Cancelar',
        tone: visual.tone,
        icon: visual.icon,
        reload: false,
    };
}

function nexusDialogVisual(text, fallbackMode) {
    const t = String(text || '').toLowerCase();

    if (/foto/.test(t) && /remover|excluir/.test(t)) {
        return { icon: 'photo', tone: 'danger' };
    }
    if (/redefini/.test(t) || (/e-?mail/.test(t) && /enviar|enviad/.test(t))) {
        return { icon: 'mail', tone: 'info' };
    }
    if (/rejeitar|reprovar/.test(t)) {
        return { icon: 'reject', tone: 'danger' };
    }
    if (/\baprovar\b|fica publicado/.test(t)) {
        return { icon: 'check', tone: 'success' };
    }
    if (/cancelar/.test(t)) {
        return { icon: 'ban', tone: 'danger' };
    }
    if (/excluir|apagar|eliminar|remover/.test(t)) {
        return { icon: 'trash', tone: 'danger' };
    }
    if (/aten[cç][aã]o|cuidado|pendente|incompleto|inv[aá]lid/.test(t)) {
        return { icon: 'alert', tone: 'warning' };
    }
    if (/salvo|guardad|enviad|sucesso|verificad/.test(t)) {
        return { icon: 'check', tone: 'success' };
    }

    return fallbackMode === 'ok'
        ? { icon: 'info', tone: 'info' }
        : { icon: 'help', tone: 'info' };
}

function nexusDialog() {
    return window.Alpine?.store?.('nexusDialog') ?? null;
}

function whenNexusDialog(callback) {
    const store = nexusDialog();
    if (store) {
        callback(store);
        return;
    }

    document.addEventListener('alpine:init', () => {
        const ready = nexusDialog();
        if (ready) {
            callback(ready);
        }
    }, { once: true });
}

function installNexusDialogs() {
    if (window.__nexusDialogsInstalled) {
        return;
    }
    window.__nexusDialogsInstalled = true;

    let bypassConfirm = false;

    window.alert = (message) => {
        whenNexusDialog((store) => {
            store.ask(nexusDialogSpec(message, 'ok'));
        });
    };

    window.confirm = (message) => {
        const spec = nexusDialogSpec(message, 'confirm');
        whenNexusDialog((store) => {
            store.ask(spec).then((ok) => {
                if (ok && spec.reload) {
                    window.location.reload();
                }
            });
        });
        return false;
    };

    window.prompt = (message) => {
        window.alert(message || 'Informe o valor na tela, sem a janela do navegador.');
        return null;
    };

    document.addEventListener('click', (event) => {
        if (bypassConfirm) {
            return;
        }

        const target = event.target;
        const el = target instanceof Element ? target.closest('[wire\\:confirm]') : null;
        if (!el) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const spec = nexusDialogSpec(el.getAttribute('wire:confirm'), 'confirm');
        whenNexusDialog((store) => {
            store.ask(spec).then((ok) => {
                if (!ok) {
                    return;
                }
                if (spec.reload) {
                    window.location.reload();
                    return;
                }

                bypassConfirm = true;
                const original = el.__livewire_confirm;
                el.__livewire_confirm = (action) => {
                    if (typeof action === 'function') {
                        action();
                    }
                };
                try {
                    el.click();
                } finally {
                    if (original) {
                        el.__livewire_confirm = original;
                    } else {
                        delete el.__livewire_confirm;
                    }
                    bypassConfirm = false;
                }
            });
        });
    }, true);
}

installNexusDialogs();

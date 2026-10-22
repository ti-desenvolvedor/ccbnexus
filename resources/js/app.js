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

            this.theme = accountTheme || storedTheme || 'light';
            this.palette = accountPalette || storedPalette || 'blue';
            this.applySidebarDensity(hints?.sidebar === 'icons' ? 'icons' : 'expanded');
            this.sidebarHoverCapable = typeof window.matchMedia === 'function'
                && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.bindSidebarChrome();

            if (accountTheme) {
                localStorage.setItem('nexus.theme', this.theme);
            }
            if (accountPalette) {
                localStorage.setItem('nexus.palette', this.palette);
            }

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
        },

        closeSidebar() {
            this.sidebarOpen = false;
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

function registerNexusAlpine(Alpine) {
    if (!Alpine) {
        return;
    }

    if (!Alpine.store('nexus')) {
        Alpine.store('nexus', createNexusStore());
    }

    Alpine.data('nexusCommandPalette', nexusCommandPalette);

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
    } catch {
        // ignore
    }
}

// Livewire v3 navigation doesn't always re-run Alpine x-init.
// Ensure store state is synced to current pathname.
document.addEventListener('livewire:navigated', nexusSyncAfterNavigation);
window.addEventListener('livewire:navigated', nexusSyncAfterNavigation);

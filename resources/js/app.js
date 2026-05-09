import './bootstrap';

function createNexusStore() {
    return {
        sidebarOpen: false,
        theme: 'light', // light|dark
        palette: 'blue', // blue|navy|green|green_dark|red|red_dark|orange|brown
        path: '/',
        menu: {
            org: true,
            ops: false,
            agenda: false,
        },

        boot(hints = null) {
            this.path = String(window?.location?.pathname || '/');
            this.theme = localStorage.getItem('nexus.theme') || 'light';
            this.palette = localStorage.getItem('nexus.palette') || 'blue';

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

            // If user is inside a section, ensure its group is open.
            if (hints.org) {
                this.menu.org = true;
            }
            if (hints.ops) {
                this.menu.ops = true;
            }
            if (hints.agenda) {
                this.menu.agenda = true;
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
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
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
                return;
            }

            this.ensureSidebarExpandedForNavigation();

            const next = !this.menu[key];
            this.menu.org = false;
            this.menu.ops = false;
            this.menu.agenda = false;
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

// Register store when Alpine initializes (Livewire v3 provides Alpine globally).
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (Alpine && !Alpine.store('nexus')) {
        Alpine.store('nexus', createNexusStore());
    }
});

function nexusHintsFromPathname(pathname) {
    const p = String(pathname || '/');
    return {
        org: p.startsWith('/organization') || p.startsWith('/users') || p.startsWith('/access'),
        ops: p.startsWith('/infrastructure'),
        agenda: p.startsWith('/agenda') || p.startsWith('/reports'),
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

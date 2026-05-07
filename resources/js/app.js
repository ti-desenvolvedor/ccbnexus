import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('nexus', {
        sidebarOpen: false,
        sidebarCollapsed: false,
        theme: 'light', // light|dark
        primary: 'blue', // blue|green|purple
        sidebarSkin: 'dark', // light|dark (independent from global theme)
        menu: {
            org: true,
            ops: false,
            agenda: false,
        },

        boot(hints = null) {
            this.sidebarCollapsed = localStorage.getItem('nexus.sidebarCollapsed') === '1';
            this.theme = localStorage.getItem('nexus.theme') || 'light';
            this.primary = localStorage.getItem('nexus.primary') || 'blue';
            this.sidebarSkin = localStorage.getItem('nexus.sidebarSkin') || 'dark';

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

            const routeWantsFullNav =
                hints &&
                typeof hints === 'object' &&
                (hints.org === true || hints.ops === true || hints.agenda === true);

            if (
                routeWantsFullNav &&
                typeof window !== 'undefined' &&
                window.matchMedia('(min-width: 1024px)').matches
            ) {
                this.sidebarCollapsed = false;
                localStorage.setItem('nexus.sidebarCollapsed', '0');
            }

            this.applyTheme();
            this.applyPrimary();
            this.applySidebarSkin();
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        closeSidebar() {
            this.sidebarOpen = false;
        },

        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('nexus.sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
        },

        ensureSidebarExpandedForNavigation() {
            if (typeof window === 'undefined') {
                return;
            }

            this.closeSidebar();

            if (!window.matchMedia('(min-width: 1024px)').matches) {
                return;
            }

            if (!this.sidebarCollapsed) {
                return;
            }

            this.sidebarCollapsed = false;
            localStorage.setItem('nexus.sidebarCollapsed', '0');
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('nexus.theme', this.theme);
            this.applyTheme();
        },

        setPrimary(primary) {
            this.primary = primary;
            localStorage.setItem('nexus.primary', this.primary);
            this.applyPrimary();
        },

        setSidebarSkin(skin) {
            this.sidebarSkin = skin === 'light' ? 'light' : 'dark';
            localStorage.setItem('nexus.sidebarSkin', this.sidebarSkin);
            this.applySidebarSkin();
        },

        toggleMenu(key) {
            if (!Object.prototype.hasOwnProperty.call(this.menu, key)) {
                return;
            }

            this.ensureSidebarExpandedForNavigation();

            this.menu[key] = !this.menu[key];
            localStorage.setItem('nexus.menu', JSON.stringify(this.menu));
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
        },

        applyPrimary() {
            document.documentElement.dataset.primary = this.primary;
        },

        applySidebarSkin() {
            document.documentElement.dataset.sidebarSkin = this.sidebarSkin;
        },
    });
});

Livewire.start();

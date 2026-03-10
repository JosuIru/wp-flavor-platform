/**
 * Flavor Admin Shell - JavaScript
 *
 * Componente Alpine.js para el sistema de navegación admin elegante
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

/**
 * Corregir layout del editor de posts/CPTs
 * Elimina padding/margin inline que WordPress o jQuery UI Sortable añaden
 * y que interfieren con el Shell de Flavor.
 * Definida al principio para estar disponible desde cualquier punto.
 */
function fixPostEditorLayout() {
    // Solo ejecutar si el shell está activo
    if (!document.body.classList.contains('fls-shell-active')) {
        return;
    }

    const selectoresElementosEditor = [
        '#normal-sortables',
        '#advanced-sortables',
        '#side-sortables',
        '.meta-box-sortables',
        '#postbox-container-1',
        '#postbox-container-2',
        '#post-body',
        '#post-body.columns-2',
        '.postbox-container'
    ];

    const propiedadesALimpiar = [
        'paddingLeft',
        'paddingRight',
        'marginLeft',
        'marginRight'
    ];

    selectoresElementosEditor.forEach(selector => {
        document.querySelectorAll(selector).forEach(elemento => {
            propiedadesALimpiar.forEach(propiedad => {
                if (elemento.style[propiedad]) {
                    elemento.style[propiedad] = '';
                }
            });
        });
    });
}

// Hacer la función disponible globalmente
window.fixPostEditorLayout = fixPostEditorLayout;

/**
 * Índice de búsqueda para Quick Search
 */
let searchIndex = [];

/**
 * Construir índice de búsqueda desde el DOM
 */
function buildSearchIndex() {
    searchIndex = [];

    // Obtener todos los items del menú
    document.querySelectorAll('.fls-shell__menu-link').forEach(link => {
        const slug = link.dataset.slug;
        const label = link.dataset.label || link.querySelector('.fls-shell__menu-text')?.textContent?.trim();
        const icon = link.dataset.icon || 'dashicons-admin-page';
        const section = link.closest('.fls-shell__section')?.querySelector('.fls-shell__section-title')?.textContent?.trim();

        if (slug && label) {
            searchIndex.push({
                slug,
                label,
                icon,
                section,
                url: link.href,
                type: 'page',
                keywords: [slug, label, section].filter(Boolean).join(' ').toLowerCase()
            });
        }
    });

    // Obtener submenús
    document.querySelectorAll('.fls-shell__submenu-link').forEach(link => {
        const slug = link.href.match(/page=([^&]+)/)?.[1];
        const label = link.querySelector('.fls-shell__submenu-text')?.textContent?.trim();
        const icon = link.querySelector('.dashicons')?.className.match(/dashicons-[\w-]+/)?.[0] || 'dashicons-arrow-right-alt2';
        const parentSection = link.closest('.fls-shell__menu-item')?.querySelector('.fls-shell__menu-text')?.textContent?.trim();

        if (slug && label) {
            searchIndex.push({
                slug,
                label,
                icon,
                section: parentSection,
                url: link.href,
                type: 'subpage',
                keywords: [slug, label, parentSection].filter(Boolean).join(' ').toLowerCase()
            });
        }
    });

    // Obtener favoritos
    document.querySelectorAll('.fls-shell__quick-link').forEach(link => {
        const slug = link.dataset.slug;
        const label = link.querySelector('.fls-shell__quick-text')?.textContent?.trim();
        const icon = link.querySelector('.dashicons')?.className.match(/dashicons-[\w-]+/)?.[0] || 'dashicons-star-filled';

        if (slug && label && !searchIndex.find(item => item.slug === slug)) {
            searchIndex.push({
                slug,
                label,
                icon,
                section: 'Favoritos',
                url: link.href,
                type: 'favorite',
                keywords: [slug, label, 'favorito'].join(' ').toLowerCase()
            });
        }
    });

    // Añadir acciones rápidas
    const quickActions = [
        { slug: 'action-dashboard', label: 'Ir al Dashboard', icon: 'dashicons-dashboard', url: flavorAdminShell.dashboardUrl, section: 'Acciones' },
        { slug: 'action-wp', label: 'Volver a WordPress', icon: 'dashicons-wordpress', url: flavorAdminShell.wpDashboardUrl, section: 'Acciones' },
    ];

    quickActions.forEach(action => {
        searchIndex.push({
            ...action,
            type: 'action',
            keywords: [action.slug, action.label, 'acción'].join(' ').toLowerCase()
        });
    });
}

/**
 * Componente Alpine.js para Quick Search
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('flavorShellSearch', () => ({
        searchOpen: false,
        query: '',
        results: [],
        activeIndex: 0,
        activeResult: null,

        init() {
            // Escuchar evento para abrir búsqueda
            window.addEventListener('open-shell-search', () => this.openSearch());

            // Construir índice inicial
            setTimeout(() => buildSearchIndex(), 100);
        },

        toggleSearch() {
            if (this.searchOpen) {
                this.closeSearch();
            } else {
                this.openSearch();
            }
        },

        openSearch() {
            this.searchOpen = true;
            this.query = '';
            this.results = [];
            this.activeIndex = 0;
            this.activeResult = null;

            // Reconstruir índice por si cambió el menú
            buildSearchIndex();

            // Focus en input
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        closeSearch() {
            this.searchOpen = false;
            this.query = '';
            this.results = [];
        },

        search() {
            const queryLower = this.query.toLowerCase().trim();

            if (!queryLower) {
                this.results = [];
                this.activeResult = null;
                return;
            }

            // Filtrar resultados
            const filtered = searchIndex.filter(item => {
                return item.keywords.includes(queryLower) ||
                       item.label.toLowerCase().includes(queryLower) ||
                       item.slug.toLowerCase().includes(queryLower);
            });

            // Ordenar por relevancia
            this.results = filtered.sort((a, b) => {
                const aStartsWith = a.label.toLowerCase().startsWith(queryLower);
                const bStartsWith = b.label.toLowerCase().startsWith(queryLower);

                if (aStartsWith && !bStartsWith) return -1;
                if (!aStartsWith && bStartsWith) return 1;

                return a.label.localeCompare(b.label);
            }).slice(0, 10);

            this.activeIndex = 0;
            this.activeResult = this.results[0] || null;
        },

        get groupedResults() {
            const groups = {};

            this.results.forEach(result => {
                const section = result.section || 'Resultados';
                if (!groups[section]) {
                    groups[section] = { label: section, items: [] };
                }
                groups[section].items.push(result);
            });

            return Object.values(groups);
        },

        navigateResults(direction) {
            if (this.results.length === 0) return;

            this.activeIndex += direction;

            if (this.activeIndex < 0) {
                this.activeIndex = this.results.length - 1;
            } else if (this.activeIndex >= this.results.length) {
                this.activeIndex = 0;
            }

            this.activeResult = this.results[this.activeIndex];
        },

        selectResult() {
            if (this.activeResult) {
                this.trackVisit(this.activeResult);
                window.location.href = this.activeResult.url;
            }
        },

        isActive(result) {
            return this.activeResult?.slug === result.slug;
        },

        setActive(result) {
            this.activeResult = result;
            this.activeIndex = this.results.indexOf(result);
        },

        async trackVisit(result) {
            // Trackear visita vía AJAX
            try {
                const formData = new FormData();
                formData.append('action', 'flavor_shell_track_visit');
                formData.append('slug', result.slug);
                formData.append('label', result.label);
                formData.append('icon', result.icon);
                formData.append('nonce', flavorAdminShell.nonce);

                fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                // Silently fail
            }
        }
    }));
});

/**
 * Componente Alpine.js para el Shell
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('flavorShell', () => ({
        // Estado
        collapsed: localStorage.getItem('flavorShellCollapsed') === 'true',
        mobileOpen: false,
        darkMode: document.body.classList.contains('fls-shell-dark'),
        vistaOpen: false,
        userMenuOpen: false,

        /**
         * Inicialización
         */
        init() {
            // Aplicar estado inicial
            this.updateBodyClasses();

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => this.handleKeyboard(e));

            // Cerrar mobile menu al hacer resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 782 && this.mobileOpen) {
                    this.mobileOpen = false;
                }
            });

            // Wrap del contenido de WordPress
            this.wrapWPContent();
        },

        /**
         * Toggle colapsar/expandir sidebar
         */
        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('flavorShellCollapsed', this.collapsed);
            this.updateBodyClasses();

            // Re-ejecutar fix de layout al colapsar/expandir
            setTimeout(() => window.fixPostEditorLayout(), 50);
        },

        /**
         * Toggle mobile menu
         */
        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
        },

        /**
         * Cerrar mobile menu
         */
        closeMobile() {
            this.mobileOpen = false;
        },

        /**
         * Toggle dark mode
         */
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            this.updateBodyClasses();
            localStorage.setItem('flavorShellDarkMode', this.darkMode);
        },

        /**
         * Desactivar shell
         */
        async disableShell() {
            if (!confirm(flavorAdminShell.i18n.disableShell + '?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'flavor_toggle_admin_shell');
                formData.append('shell_action', 'disable');
                formData.append('nonce', flavorAdminShell.nonce);

                const response = await fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Recargar la página para volver al admin normal de WP
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error disabling shell:', error);
            }
        },

        /**
         * Ir al dashboard de WordPress
         */
        goToWPDashboard() {
            window.location.href = flavorAdminShell.wpDashboardUrl;
        },

        /**
         * Actualizar clases del body
         */
        updateBodyClasses() {
            const body = document.body;

            // Collapsed state
            if (this.collapsed) {
                body.classList.add('fls-shell-collapsed');
            } else {
                body.classList.remove('fls-shell-collapsed');
            }

            // Dark mode
            const shellElement = document.querySelector('.fls-shell');
            if (shellElement) {
                if (this.darkMode) {
                    shellElement.classList.add('fls-shell-dark');
                } else {
                    shellElement.classList.remove('fls-shell-dark');
                }
            }
        },

        /**
         * Manejar atajos de teclado
         */
        handleKeyboard(e) {
            // Ctrl/Cmd + B: Toggle collapse
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                this.toggleCollapse();
                return;
            }

            // Escape: Cerrar mobile menu
            if (e.key === 'Escape' && this.mobileOpen) {
                this.closeMobile();
                return;
            }

            // Arrow navigation en el menu
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                const focusedElement = document.activeElement;
                if (focusedElement && focusedElement.closest('.fls-shell__nav')) {
                    e.preventDefault();
                    this.navigateMenu(e.key === 'ArrowDown' ? 1 : -1);
                }
            }
        },

        /**
         * Navegación con flechas en el menú
         */
        navigateMenu(direction) {
            const menuLinks = document.querySelectorAll('.fls-shell__menu-link');
            const currentIndex = Array.from(menuLinks).findIndex(
                link => link === document.activeElement
            );

            let newIndex = currentIndex + direction;

            if (newIndex < 0) {
                newIndex = menuLinks.length - 1;
            } else if (newIndex >= menuLinks.length) {
                newIndex = 0;
            }

            menuLinks[newIndex]?.focus();
        },

        /**
         * Verificar si un item está activo
         */
        isActive(slug) {
            return flavorAdminShell.currentPage === slug;
        },

        /**
         * Obtener URL de admin
         */
        getAdminUrl(slug) {
            return `admin.php?page=${slug}`;
        },

        /**
         * Wrap del contenido de WordPress para ajustar layout
         */
        wrapWPContent() {
            // El CSS ya maneja el padding, pero podemos añadir
            // transiciones suaves aquí si es necesario
            const wpContent = document.getElementById('wpcontent');
            if (wpContent) {
                wpContent.style.transition = 'padding-left 250ms ease';
            }

            // Eliminar padding inline de elementos del editor de posts/CPTs
            // que WordPress añade y que interfiere con el Shell
            window.fixPostEditorLayout();

            // Observar cambios en el DOM por si WordPress añade estilos después
            const observadorLayoutEditor = new MutationObserver(() => {
                window.fixPostEditorLayout();
            });

            const postStuff = document.getElementById('poststuff');
            if (postStuff) {
                observadorLayoutEditor.observe(postStuff, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['style']
                });
            }
        },

        /**
         * Cambiar vista del panel de administración (vistas del sistema)
         */
        async cambiarVista(vista) {
            if (!vista) return;

            try {
                const formData = new FormData();
                formData.append('action', 'flavor_cambiar_vista_admin');
                formData.append('vista', vista);
                formData.append('nonce', flavorAdminShell.nonceVista || flavorAdminShell.nonce);

                const response = await fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    console.error('Error cambiando vista:', data.data?.message);
                }
            } catch (error) {
                console.error('Error cambiando vista:', error);
            }
        },

        /**
         * Cambiar a vista personalizada
         */
        async cambiarVistaPersonalizada(viewId) {
            if (!viewId) return;

            try {
                const formData = new FormData();
                formData.append('action', 'flavor_shell_switch_view');
                formData.append('view_id', viewId);
                formData.append('nonce', flavorAdminShell.nonce);

                const response = await fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    console.error('Error cambiando vista:', data.data?.message);
                }
            } catch (error) {
                console.error('Error cambiando vista:', error);
            }
        },

        /**
         * Toggle favorito de una página
         */
        async toggleFavorite(slug, label, icon) {
            try {
                const formData = new FormData();
                formData.append('action', 'flavor_shell_toggle_favorite');
                formData.append('slug', slug);
                formData.append('label', label);
                formData.append('icon', icon);
                formData.append('nonce', flavorAdminShell.nonce);

                const response = await fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar UI
                    const menuLink = document.querySelector(`.fls-shell__menu-link[data-slug="${slug}"]`);
                    const favBtn = menuLink?.querySelector('.fls-shell__menu-fav');
                    const favIcon = favBtn?.querySelector('.dashicons');

                    if (favBtn && favIcon) {
                        if (data.data.is_favorite) {
                            favBtn.classList.add('fls-shell__menu-fav--active');
                            favIcon.classList.remove('dashicons-star-empty');
                            favIcon.classList.add('dashicons-star-filled');
                        } else {
                            favBtn.classList.remove('fls-shell__menu-fav--active');
                            favIcon.classList.remove('dashicons-star-filled');
                            favIcon.classList.add('dashicons-star-empty');
                        }
                    }

                    // Mostrar notificación breve
                    this.showToast(data.data.message);

                    // Recargar para actualizar sección de favoritos
                    if (data.data.action === 'added' || data.data.action === 'removed') {
                        setTimeout(() => window.location.reload(), 500);
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
            }
        },

        /**
         * Añadir a favoritos desde recientes
         */
        async addToFavorites(slug, label, icon) {
            await this.toggleFavorite(slug, label, icon);
        },

        /**
         * Quitar de favoritos
         */
        async removeFavorite(slug) {
            try {
                const formData = new FormData();
                formData.append('action', 'flavor_shell_toggle_favorite');
                formData.append('slug', slug);
                formData.append('nonce', flavorAdminShell.nonce);

                const response = await fetch(flavorAdminShell.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast(data.data.message);
                    setTimeout(() => window.location.reload(), 300);
                }
            } catch (error) {
                console.error('Error removing favorite:', error);
            }
        },

        /**
         * Mostrar toast notification
         */
        showToast(message) {
            // Crear toast si no existe
            let toast = document.querySelector('.fls-shell-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.className = 'fls-shell-toast';
                document.body.appendChild(toast);
            }

            toast.textContent = message;
            toast.classList.add('fls-shell-toast--visible');

            setTimeout(() => {
                toast.classList.remove('fls-shell-toast--visible');
            }, 2000);
        }
    }));
});

/**
 * Inicialización adicional después de que el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', () => {
    // Añadir soporte para focus visible
    document.body.classList.add('fls-shell-js-ready');

    // Restaurar dark mode desde localStorage si está guardado
    const savedDarkMode = localStorage.getItem('flavorShellDarkMode');
    if (savedDarkMode === 'true') {
        const shellElement = document.querySelector('.fls-shell');
        if (shellElement) {
            shellElement.classList.add('fls-shell-dark');
        }
    }

    // Fix para el editor de posts/CPTs - ejecutar independientemente de Alpine
    if (document.body.classList.contains('fls-shell-active')) {
        // Ejecutar inmediatamente
        fixPostEditorLayout();

        // Ejecutar después de pequeños delays (WordPress/jQuery UI pueden añadir estilos tarde)
        setTimeout(fixPostEditorLayout, 100);
        setTimeout(fixPostEditorLayout, 300);
        setTimeout(fixPostEditorLayout, 500);
        setTimeout(fixPostEditorLayout, 1000);

        // Observar cambios en el DOM
        const postStuff = document.getElementById('poststuff');
        if (postStuff) {
            const observadorMutaciones = new MutationObserver(() => {
                fixPostEditorLayout();
            });
            observadorMutaciones.observe(postStuff, {
                attributes: true,
                subtree: true,
                attributeFilter: ['style']
            });
        }
    }
});

// También ejecutar cuando la página esté completamente cargada
window.addEventListener('load', () => {
    if (document.body.classList.contains('fls-shell-active')) {
        fixPostEditorLayout();
        // Delay adicional por si jQuery UI Sortable se inicializa tarde
        setTimeout(fixPostEditorLayout, 200);

        // Iniciar polling de badges
        initBadgePolling();

        // Construir índice de búsqueda
        buildSearchIndex();
    }
});

/**
 * Sistema de actualización automática de badges
 */
const BADGE_POLL_INTERVAL = 5 * 60 * 1000; // 5 minutos
let badgePollTimer = null;

function initBadgePolling() {
    // Primera actualización después de 5 minutos
    badgePollTimer = setInterval(updateBadges, BADGE_POLL_INTERVAL);

    // También actualizar cuando la ventana vuelve a estar visible
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            updateBadges();
        }
    });
}

async function updateBadges() {
    if (typeof flavorAdminShell === 'undefined') {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'flavor_shell_get_badges');
        formData.append('nonce', flavorAdminShell.nonce);

        const response = await fetch(flavorAdminShell.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            applyBadgeUpdates(data.data.badges, data.data.aggregated);
        }
    } catch (error) {
        console.warn('Error actualizando badges del shell:', error);
    }
}

function applyBadgeUpdates(badges, aggregated) {
    // Combinar badges individuales y agregados
    const allBadges = { ...badges, ...aggregated };

    // Actualizar badges existentes
    document.querySelectorAll('.fls-shell__menu-badge[data-slug], .fls-shell__submenu-badge[data-slug]').forEach(badgeEl => {
        const slug = badgeEl.dataset.slug;
        const badgeData = allBadges[slug];

        if (badgeData) {
            const currentCount = parseInt(badgeEl.textContent.replace('+', ''), 10) || 0;
            const newCount = badgeData.count;

            if (currentCount !== newCount) {
                // Actualizar contenido
                badgeEl.textContent = newCount > 99 ? '99+' : newCount;

                // Actualizar severidad
                badgeEl.className = badgeEl.className.replace(/fls-shell__(menu|submenu)-badge--(info|warning|danger)/g, '');
                const baseClass = badgeEl.classList.contains('fls-shell__submenu-badge')
                    ? 'fls-shell__submenu-badge'
                    : 'fls-shell__menu-badge';
                badgeEl.classList.add(`${baseClass}--${badgeData.severity}`);

                // Animación de actualización
                badgeEl.classList.add(`${baseClass}--updated`);
                setTimeout(() => {
                    badgeEl.classList.remove(`${baseClass}--updated`);
                }, 300);
            }
        } else {
            // Badge ya no existe, ocultarlo
            badgeEl.style.display = 'none';
        }
    });

    // Añadir nuevos badges que no existían
    Object.entries(allBadges).forEach(([slug, badgeData]) => {
        const existingBadge = document.querySelector(`[data-slug="${slug}"]`);

        if (!existingBadge) {
            // Buscar el link del menú correspondiente
            const menuLink = document.querySelector(`.fls-shell__menu-link[href*="page=${slug}"], .fls-shell__submenu-link[href*="page=${slug}"]`);

            if (menuLink) {
                const isSubmenu = menuLink.classList.contains('fls-shell__submenu-link');
                const baseClass = isSubmenu ? 'fls-shell__submenu-badge' : 'fls-shell__menu-badge';

                const newBadge = document.createElement('span');
                newBadge.className = `${baseClass} ${baseClass}--${badgeData.severity} ${baseClass}--updated`;
                newBadge.dataset.slug = slug;
                newBadge.textContent = badgeData.count > 99 ? '99+' : badgeData.count;

                menuLink.appendChild(newBadge);

                setTimeout(() => {
                    newBadge.classList.remove(`${baseClass}--updated`);
                }, 300);
            }
        }
    });
}

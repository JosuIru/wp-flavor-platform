/**
 * Flavor Admin Shell - JavaScript
 *
 * Componente Alpine.js para el sistema de navegación admin elegante
 *
 * @package FlavorChatIA
 * @since 3.2.0
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
            this.fixPostEditorLayout();

            // Observar cambios en el DOM por si WordPress añade estilos después
            const observer = new MutationObserver(() => {
                this.fixPostEditorLayout();
            });

            const postStuff = document.getElementById('poststuff');
            if (postStuff) {
                observer.observe(postStuff, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['style']
                });
            }
        },

        /**
         * Corregir layout del editor de posts/CPTs
         */
        fixPostEditorLayout() {
            // Elementos que WordPress puede añadir padding-left inline
            const elementsToFix = [
                '#normal-sortables',
                '#advanced-sortables',
                '#side-sortables',
                '.meta-box-sortables',
                '#postbox-container-1',
                '#postbox-container-2'
            ];

            elementsToFix.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    if (el.style.paddingLeft) {
                        el.style.paddingLeft = '';
                    }
                });
            });
        },

        /**
         * Cambiar vista del panel de administración
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

        // Ejecutar después de un pequeño delay (WordPress puede añadir estilos tarde)
        setTimeout(fixPostEditorLayout, 100);
        setTimeout(fixPostEditorLayout, 500);

        // Observar cambios en el DOM
        const postStuff = document.getElementById('poststuff');
        if (postStuff) {
            const observer = new MutationObserver(() => {
                fixPostEditorLayout();
            });
            observer.observe(postStuff, {
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
    }
});

/**
 * Corregir layout del editor de posts/CPTs
 * Elimina padding-left inline que WordPress añade y que interfiere con el Shell
 */
function fixPostEditorLayout() {
    const elementsToFix = [
        '#normal-sortables',
        '#advanced-sortables',
        '#side-sortables',
        '.meta-box-sortables',
        '#postbox-container-1',
        '#postbox-container-2',
        '#post-body'
    ];

    elementsToFix.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            if (el.style.paddingLeft) {
                el.style.paddingLeft = '';
            }
            if (el.style.marginLeft) {
                el.style.marginLeft = '';
            }
        });
    });
}

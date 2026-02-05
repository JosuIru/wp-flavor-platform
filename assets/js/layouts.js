/**
 * Flavor Layouts - Frontend JavaScript
 * Interactividad para menús y footers
 */

(function() {
    'use strict';

    const FlavorLayouts = {
        config: window.flavorLayoutConfig || {},

        /**
         * Inicialización
         */
        init() {
            this.initStickyHeader();
            this.initMobileMenu();
            this.initSidebar();
            this.initBottomNav();
            this.initFullscreenMenu();
            this.initUserMenu();
            this.initSearch();
            this.initScrollEffects();
            this.initForms();
        },

        /**
         * Header sticky con efecto de scroll
         */
        initStickyHeader() {
            const header = document.querySelector('.flavor-menu--sticky');
            if (!header) return;

            let lastScrollY = window.scrollY;
            let ticking = false;

            const updateHeader = () => {
                const scrollY = window.scrollY;

                // Añadir clase cuando se hace scroll
                if (scrollY > 50) {
                    header.classList.add('is-scrolled');
                } else {
                    header.classList.remove('is-scrolled');
                }

                // Ocultar/mostrar en scroll (opcional)
                if (this.config.menuSettings?.hide_on_scroll) {
                    if (scrollY > lastScrollY && scrollY > 100) {
                        header.style.transform = 'translateY(-100%)';
                    } else {
                        header.style.transform = 'translateY(0)';
                    }
                }

                lastScrollY = scrollY;
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            }, { passive: true });
        },

        /**
         * Menú móvil toggle
         */
        initMobileMenu() {
            const toggles = document.querySelectorAll('.flavor-menu__toggle');
            const mobileMenu = document.querySelector('.flavor-menu__mobile');

            if (!toggles.length || !mobileMenu) return;

            toggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const isOpen = mobileMenu.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen);
                    mobileMenu.setAttribute('aria-hidden', !isOpen);

                    // Animar barras del hamburger
                    toggle.classList.toggle('is-active');

                    // Prevenir scroll del body
                    document.body.style.overflow = isOpen ? 'hidden' : '';
                });
            });

            // Cerrar al hacer click en un enlace
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.remove('is-open');
                    toggles.forEach(t => {
                        t.classList.remove('is-active');
                        t.setAttribute('aria-expanded', 'false');
                    });
                    document.body.style.overflow = '';
                });
            });
        },

        /**
         * Sidebar lateral
         */
        initSidebar() {
            const sidebarToggle = document.querySelector('.flavor-menu__sidebar-toggle');
            const sidebar = document.querySelector('.flavor-sidebar');
            const overlay = document.querySelector('.flavor-sidebar__overlay');
            const closeBtn = document.querySelector('.flavor-sidebar__close');

            if (!sidebarToggle || !sidebar) return;

            const openSidebar = () => {
                sidebar.classList.add('is-open');
                sidebar.setAttribute('aria-hidden', 'false');
                overlay?.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            };

            const closeSidebar = () => {
                sidebar.classList.remove('is-open');
                sidebar.setAttribute('aria-hidden', 'true');
                overlay?.classList.remove('is-open');
                document.body.style.overflow = '';
            };

            sidebarToggle.addEventListener('click', openSidebar);
            closeBtn?.addEventListener('click', closeSidebar);
            overlay?.addEventListener('click', closeSidebar);

            // Cerrar con ESC
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                    closeSidebar();
                }
            });

            // Swipe para cerrar en móvil
            let touchStartX = 0;
            let touchEndX = 0;

            sidebar.addEventListener('touchstart', (event) => {
                touchStartX = event.changedTouches[0].screenX;
            }, { passive: true });

            sidebar.addEventListener('touchend', (event) => {
                touchEndX = event.changedTouches[0].screenX;
                const position = sidebar.dataset.position || 'left';

                if (position === 'left' && touchEndX < touchStartX - 50) {
                    closeSidebar();
                } else if (position === 'right' && touchEndX > touchStartX + 50) {
                    closeSidebar();
                }
            }, { passive: true });
        },

        /**
         * Bottom navigation
         */
        initBottomNav() {
            const bottomNav = document.querySelector('.flavor-bottom-nav');
            if (!bottomNav) return;

            // Ocultar/mostrar al hacer scroll
            if (bottomNav.classList.contains('flavor-bottom-nav--hide-on-scroll')) {
                let lastScrollY = window.scrollY;
                let ticking = false;

                const updateNav = () => {
                    const scrollY = window.scrollY;

                    if (scrollY > lastScrollY && scrollY > 100) {
                        bottomNav.style.transform = 'translateY(100%)';
                    } else {
                        bottomNav.style.transform = 'translateY(0)';
                    }

                    lastScrollY = scrollY;
                    ticking = false;
                };

                window.addEventListener('scroll', () => {
                    if (!ticking) {
                        window.requestAnimationFrame(updateNav);
                        ticking = true;
                    }
                }, { passive: true });
            }

            // Marcar item activo basado en URL
            const currentPath = window.location.pathname;
            bottomNav.querySelectorAll('.flavor-bottom-nav__item').forEach(item => {
                const href = item.getAttribute('href');
                if (href && currentPath.startsWith(href) && href !== '/') {
                    item.classList.add('is-active');
                } else if (href === '/' && currentPath === '/') {
                    item.classList.add('is-active');
                }
            });
        },

        /**
         * Menú fullscreen (minimal)
         */
        initFullscreenMenu() {
            const hamburger = document.querySelector('.flavor-menu__hamburger');
            const fullscreenMenu = document.querySelector('.flavor-fullscreen-menu');

            if (!hamburger || !fullscreenMenu) return;

            hamburger.addEventListener('click', () => {
                const isOpen = fullscreenMenu.classList.toggle('is-open');
                hamburger.classList.toggle('is-active');
                hamburger.setAttribute('aria-expanded', isOpen);
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            // Cerrar al hacer click en enlaces
            fullscreenMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    fullscreenMenu.classList.remove('is-open');
                    hamburger.classList.remove('is-active');
                    hamburger.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                });
            });

            // Cerrar con ESC
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && fullscreenMenu.classList.contains('is-open')) {
                    fullscreenMenu.classList.remove('is-open');
                    hamburger.classList.remove('is-active');
                    hamburger.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                }
            });
        },

        /**
         * Menú de usuario dropdown
         */
        initUserMenu() {
            const userMenus = document.querySelectorAll('.flavor-user-menu');

            userMenus.forEach(menu => {
                const toggle = menu.querySelector('.flavor-user-menu__toggle');
                const dropdown = menu.querySelector('.flavor-user-menu__dropdown');

                if (!toggle || !dropdown) return;

                // Toggle en click para móvil
                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isOpen = dropdown.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen);
                });

                // Cerrar al hacer click fuera
                document.addEventListener('click', (event) => {
                    if (!menu.contains(event.target)) {
                        dropdown.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        },

        /**
         * Botón de búsqueda
         */
        initSearch() {
            const searchToggles = document.querySelectorAll('.flavor-search-toggle');

            searchToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    // Crear modal de búsqueda si no existe
                    let searchModal = document.querySelector('.flavor-search-modal');

                    if (!searchModal) {
                        searchModal = this.createSearchModal();
                        document.body.appendChild(searchModal);
                    }

                    searchModal.classList.add('is-open');
                    searchModal.querySelector('input').focus();
                    document.body.style.overflow = 'hidden';
                });
            });
        },

        /**
         * Crear modal de búsqueda
         */
        createSearchModal() {
            const modal = document.createElement('div');
            modal.className = 'flavor-search-modal';
            modal.innerHTML = `
                <div class="flavor-search-modal__backdrop"></div>
                <div class="flavor-search-modal__content">
                    <form class="flavor-search-form" action="${window.location.origin}" method="get">
                        <input type="search" name="s" class="flavor-search-input" placeholder="Buscar..." autocomplete="off">
                        <button type="submit" class="flavor-search-submit">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </form>
                    <button type="button" class="flavor-search-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `;

            // Cerrar modal
            const closeModal = () => {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            };

            modal.querySelector('.flavor-search-modal__backdrop').addEventListener('click', closeModal);
            modal.querySelector('.flavor-search-close').addEventListener('click', closeModal);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });

            // Añadir estilos inline para el modal
            const style = document.createElement('style');
            style.textContent = `
                .flavor-search-modal {
                    position: fixed;
                    inset: 0;
                    z-index: 10000;
                    display: flex;
                    align-items: flex-start;
                    justify-content: center;
                    padding-top: 15vh;
                    opacity: 0;
                    visibility: hidden;
                    transition: 0.2s ease;
                }
                .flavor-search-modal.is-open {
                    opacity: 1;
                    visibility: visible;
                }
                .flavor-search-modal__backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.7);
                }
                .flavor-search-modal__content {
                    position: relative;
                    width: 90%;
                    max-width: 600px;
                }
                .flavor-search-form {
                    display: flex;
                    background: #fff;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                }
                .flavor-search-input {
                    flex: 1;
                    padding: 20px 24px;
                    border: none;
                    font-size: 18px;
                    outline: none;
                }
                .flavor-search-submit {
                    padding: 0 24px;
                    background: var(--flavor-primary, #3b82f6);
                    border: none;
                    color: #fff;
                    cursor: pointer;
                }
                .flavor-search-close {
                    position: absolute;
                    top: -48px;
                    right: 0;
                    background: none;
                    border: none;
                    color: #fff;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 8px;
                }
            `;
            document.head.appendChild(style);

            return modal;
        },

        /**
         * Efectos de scroll (transparencia, parallax)
         */
        initScrollEffects() {
            const transparentHeader = document.querySelector('.flavor-menu--transparent');

            if (transparentHeader) {
                const updateTransparency = () => {
                    if (window.scrollY > 100) {
                        transparentHeader.classList.add('is-scrolled');
                    } else {
                        transparentHeader.classList.remove('is-scrolled');
                    }
                };

                window.addEventListener('scroll', updateTransparency, { passive: true });
                updateTransparency();
            }
        },

        /**
         * Inicializar formularios AJAX
         */
        initForms() {
            // Newsletter forms
            document.querySelectorAll('.flavor-newsletter-form').forEach(form => {
                form.addEventListener('submit', this.handleNewsletterSubmit.bind(this));
            });

            // Contact forms
            document.querySelectorAll('.flavor-contact-form').forEach(form => {
                form.addEventListener('submit', this.handleContactSubmit.bind(this));
            });
        },

        /**
         * Manejar envío de newsletter
         */
        handleNewsletterSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const button = form.querySelector('button[type="submit"]');
            const buttonText = button.querySelector('.flavor-button__text');
            const buttonLoading = button.querySelector('.flavor-button__loading');
            const messageEl = form.querySelector('.flavor-form-message');
            const emailInput = form.querySelector('input[type="email"]');
            const email = emailInput?.value;

            if (!email) {
                this.showFormMessage(messageEl, window.flavorLayouts?.i18n?.invalid_email || 'Por favor, introduce un email válido', 'error');
                return;
            }

            // Show loading state
            button.disabled = true;
            if (buttonText) buttonText.style.display = 'none';
            if (buttonLoading) buttonLoading.style.display = 'inline-block';

            const formData = new FormData();
            formData.append('action', 'flavor_newsletter_subscribe');
            formData.append('email', email);
            formData.append('nonce', window.flavorLayouts?.nonce || '');
            formData.append('source', form.dataset.source || 'footer');
            formData.append('page_url', window.location.href);

            fetch(window.flavorLayouts?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showFormMessage(messageEl, data.data.message, 'success');
                    emailInput.value = '';
                } else {
                    this.showFormMessage(messageEl, data.data?.message || 'Error', 'error');
                }
            })
            .catch(() => {
                this.showFormMessage(messageEl, window.flavorLayouts?.i18n?.error || 'Error de conexión', 'error');
            })
            .finally(() => {
                button.disabled = false;
                if (buttonText) buttonText.style.display = '';
                if (buttonLoading) buttonLoading.style.display = 'none';
            });
        },

        /**
         * Manejar envío de formulario de contacto
         */
        handleContactSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const button = form.querySelector('button[type="submit"]');
            const messageEl = form.querySelector('.flavor-form-message');

            const name = form.querySelector('input[name="name"]')?.value;
            const email = form.querySelector('input[name="email"]')?.value;
            const phone = form.querySelector('input[name="phone"]')?.value || '';
            const subject = form.querySelector('input[name="subject"]')?.value || '';
            const message = form.querySelector('textarea[name="message"]')?.value;

            if (!name || !email || !message) {
                this.showFormMessage(messageEl, window.flavorLayouts?.i18n?.required_fields || 'Completa los campos requeridos', 'error');
                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');

            const formData = new FormData();
            formData.append('action', 'flavor_contact_submit');
            formData.append('nonce', window.flavorLayouts?.nonce || '');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('source', form.dataset.source || 'footer');
            formData.append('page_url', window.location.href);

            fetch(window.flavorLayouts?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showFormMessage(messageEl, data.data.message, 'success');
                    form.reset();
                } else {
                    this.showFormMessage(messageEl, data.data?.message || 'Error', 'error');
                }
            })
            .catch(() => {
                this.showFormMessage(messageEl, window.flavorLayouts?.i18n?.error || 'Error de conexión', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.classList.remove('is-loading');
            });
        },

        /**
         * Mostrar mensaje en formulario
         */
        showFormMessage(element, message, type) {
            if (!element) return;

            element.textContent = message;
            element.className = 'flavor-form-message flavor-form-message--' + type;
            element.style.display = 'block';

            // Auto-hide después de 5 segundos
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FlavorLayouts.init());
    } else {
        FlavorLayouts.init();
    }

    // Exponer para uso externo
    window.FlavorLayouts = FlavorLayouts;

})();

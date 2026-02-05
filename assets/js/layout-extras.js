/**
 * Layout Extras - JavaScript adicional
 * Funcionalidades: dark mode, back to top, cookie banner,
 * announcement bar, page transitions, etc.
 */

(function() {
    'use strict';

    const FlavorExtras = {
        config: window.flavorExtrasConfig || {},

        /**
         * Inicialización
         */
        init() {
            this.initDarkMode();
            this.initBackToTop();
            this.initAnnouncementBar();
            this.initCookieBanner();
            this.initPageTransitions();
            this.initSmoothScroll();
            this.initLazyLoading();
            this.initParallax();
        },

        /**
         * ====== DARK MODE ======
         */
        initDarkMode() {
            if (!this.config.darkMode?.enabled) return;

            // Verificar preferencia guardada o del sistema
            const savedTheme = localStorage.getItem('flavor-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            let currentTheme = savedTheme;
            if (!currentTheme && this.config.darkMode.auto) {
                currentTheme = prefersDark ? 'dark' : 'light';
            }

            if (currentTheme) {
                document.documentElement.setAttribute('data-theme', currentTheme);
            }

            // Toggle buttons
            document.querySelectorAll('.flavor-dark-mode-toggle').forEach(toggle => {
                toggle.addEventListener('click', () => this.toggleDarkMode());
            });

            // Escuchar cambios en preferencia del sistema
            if (this.config.darkMode.auto) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!localStorage.getItem('flavor-theme')) {
                        document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
                    }
                });
            }
        },

        toggleDarkMode() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('flavor-theme', newTheme);

            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('flavor-theme-change', {
                detail: { theme: newTheme }
            }));
        },

        /**
         * ====== BACK TO TOP ======
         */
        initBackToTop() {
            const button = document.querySelector('.flavor-back-to-top');
            if (!button) return;

            const showAfter = this.config.backToTop?.show_after || 300;
            let ticking = false;

            const checkScroll = () => {
                if (window.scrollY > showAfter) {
                    button.classList.add('is-visible');
                } else {
                    button.classList.remove('is-visible');
                }
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(checkScroll);
                    ticking = true;
                }
            }, { passive: true });

            button.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Mostrar botón inicialmente
            button.style.display = '';
            checkScroll();
        },

        /**
         * ====== ANNOUNCEMENT BAR ======
         */
        initAnnouncementBar() {
            const bar = document.querySelector('.flavor-announcement-bar');
            if (!bar) return;

            const closeBtn = bar.querySelector('.flavor-announcement-bar__close');
            if (!closeBtn) return;

            closeBtn.addEventListener('click', () => {
                bar.style.height = bar.offsetHeight + 'px';
                bar.offsetHeight; // Force reflow
                bar.style.height = '0';
                bar.style.padding = '0';
                bar.style.overflow = 'hidden';
                bar.style.transition = 'all 0.3s ease';

                setTimeout(() => {
                    bar.remove();
                }, 300);

                // Guardar en cookie via AJAX
                if (this.config.ajaxUrl) {
                    fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=flavor_dismiss_announcement&nonce=${this.config.nonce}`
                    });
                }
            });
        },

        /**
         * ====== COOKIE BANNER ======
         */
        initCookieBanner() {
            const banner = document.querySelector('.flavor-cookie-banner');
            if (!banner) return;

            const acceptBtn = banner.querySelector('.flavor-cookie-banner__accept');
            const declineBtn = banner.querySelector('.flavor-cookie-banner__decline');

            const hideBanner = () => {
                banner.style.transform = 'translateY(100%)';
                banner.style.transition = 'transform 0.3s ease';
                setTimeout(() => banner.remove(), 300);
            };

            const saveCookiePreference = (accept) => {
                if (this.config.ajaxUrl) {
                    fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=flavor_accept_cookies&accept=${accept}&nonce=${this.config.nonce}`
                    });
                }

                // Dispatch event
                document.dispatchEvent(new CustomEvent('flavor-cookies-consent', {
                    detail: { accepted: accept }
                }));
            };

            acceptBtn?.addEventListener('click', () => {
                saveCookiePreference(true);
                hideBanner();
            });

            declineBtn?.addEventListener('click', () => {
                saveCookiePreference(false);
                hideBanner();
            });
        },

        /**
         * ====== PAGE TRANSITIONS ======
         */
        initPageTransitions() {
            // Añadir clase de transición al contenido principal
            const mainContent = document.querySelector('main, .site-content, #content');
            if (mainContent) {
                mainContent.classList.add('flavor-page-transition');
            }

            // Animación al hacer click en enlaces internos
            document.querySelectorAll('a[href^="/"], a[href^="' + window.location.origin + '"]').forEach(link => {
                // Excluir enlaces con target, hash, o que abren en nueva ventana
                if (link.target === '_blank' || link.getAttribute('href').startsWith('#')) return;

                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');

                    // No animar si es el mismo URL
                    if (href === window.location.href) return;

                    e.preventDefault();

                    // Fade out
                    if (mainContent) {
                        mainContent.style.opacity = '0';
                        mainContent.style.transform = 'translateY(-10px)';
                        mainContent.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                    }

                    setTimeout(() => {
                        window.location.href = href;
                    }, 200);
                });
            });
        },

        /**
         * ====== SMOOTH SCROLL ======
         */
        initSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    const targetId = anchor.getAttribute('href');
                    if (targetId === '#') return;

                    const target = document.querySelector(targetId);
                    if (!target) return;

                    e.preventDefault();

                    const headerHeight = document.querySelector('.flavor-header')?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - headerHeight - 20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Actualizar URL sin scroll
                    history.pushState(null, '', targetId);
                });
            });
        },

        /**
         * ====== LAZY LOADING ======
         */
        initLazyLoading() {
            // Usar Intersection Observer para lazy loading
            if (!('IntersectionObserver' in window)) return;

            const lazyImages = document.querySelectorAll('img[data-src], .flavor-lazy');

            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;

                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }

                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }

                        img.classList.remove('flavor-lazy');
                        img.classList.add('flavor-lazy--loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        },

        /**
         * ====== PARALLAX ======
         */
        initParallax() {
            const parallaxElements = document.querySelectorAll('[data-parallax]');
            if (!parallaxElements.length) return;

            let ticking = false;

            const updateParallax = () => {
                const scrollY = window.scrollY;

                parallaxElements.forEach(el => {
                    const speed = parseFloat(el.dataset.parallax) || 0.5;
                    const rect = el.getBoundingClientRect();
                    const visible = rect.top < window.innerHeight && rect.bottom > 0;

                    if (visible) {
                        const yPos = (scrollY - el.offsetTop) * speed;
                        el.style.transform = `translateY(${yPos}px)`;
                    }
                });

                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            }, { passive: true });
        },

        /**
         * ====== UTILITIES ======
         */

        /**
         * Crear elemento skeleton loader
         */
        createSkeleton(type = 'text', count = 1) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flavor-skeleton-wrapper';

            for (let i = 0; i < count; i++) {
                const skeleton = document.createElement('div');
                skeleton.className = `flavor-skeleton flavor-skeleton--${type}`;
                wrapper.appendChild(skeleton);
            }

            return wrapper;
        },

        /**
         * Mostrar toast notification
         */
        showToast(message, type = 'info', duration = 4000) {
            let container = document.getElementById('flavor-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'flavor-toast-container';
                container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:100001;display:flex;flex-direction:column;gap:12px;';
                document.body.appendChild(container);
            }

            const icons = {
                success: 'yes-alt',
                error: 'warning',
                warning: 'info',
                info: 'info'
            };

            const toast = document.createElement('div');
            toast.className = `flavor-toast flavor-toast--${type}`;
            toast.innerHTML = `
                <span class="dashicons dashicons-${icons[type] || 'info'}"></span>
                ${message}
            `;
            toast.style.cssText = `
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#1f2937'};
                color: #fff;
                padding: 14px 20px;
                border-radius: 8px;
                font-size: 14px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        },

        /**
         * Detectar si es móvil
         */
        isMobile() {
            return window.innerWidth <= 768 ||
                /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },

        /**
         * Debounce function
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Throttle function
         */
        throttle(func, limit) {
            let inThrottle;
            return function executedFunction(...args) {
                if (!inThrottle) {
                    func(...args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FlavorExtras.init());
    } else {
        FlavorExtras.init();
    }

    // Exponer globalmente
    window.FlavorExtras = FlavorExtras;

})();

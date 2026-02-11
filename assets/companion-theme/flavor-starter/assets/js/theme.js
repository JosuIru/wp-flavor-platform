/**
 * Flavor Starter Theme JavaScript
 *
 * @package Flavor_Starter
 */

(function() {
    'use strict';

    /**
     * Toggle del menú móvil
     */
    function initMobileMenu() {
        var toggle = document.getElementById('mobile-menu-toggle');
        var menu = document.getElementById('mobile-menu');

        if (!toggle || !menu) return;

        toggle.addEventListener('click', function() {
            var isExpanded = toggle.getAttribute('aria-expanded') === 'true';

            // Toggle visibility
            menu.classList.toggle('hidden');

            // Update ARIA
            toggle.setAttribute('aria-expanded', !isExpanded);

            // Animate hamburger icon
            toggle.classList.toggle('is-active');
        });

        // Cerrar menú al hacer clic en un enlace
        var menuLinks = menu.querySelectorAll('a');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('is-active');
            });
        });

        // Cerrar menú al redimensionar a desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('is-active');
            }
        });
    }

    /**
     * Smooth scroll para anclas internas
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');

                if (targetId === '#') return;

                var target = document.querySelector(targetId);

                if (target) {
                    e.preventDefault();

                    var headerHeight = document.querySelector('header')?.offsetHeight || 0;
                    var targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Actualizar URL sin scroll
                    history.pushState(null, null, targetId);
                }
            });
        });
    }

    /**
     * Header sticky con efecto de scroll
     */
    function initStickyHeader() {
        var header = document.querySelector('header.sticky');

        if (!header) return;

        var lastScroll = 0;
        var scrollThreshold = 100;

        window.addEventListener('scroll', function() {
            var currentScroll = window.pageYOffset;

            // Añadir sombra cuando hay scroll
            if (currentScroll > 10) {
                header.classList.add('shadow-md');
            } else {
                header.classList.remove('shadow-md');
            }

            // Ocultar/mostrar header en scroll
            if (currentScroll > scrollThreshold) {
                if (currentScroll > lastScroll) {
                    // Scrolling down
                    header.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up
                    header.style.transform = 'translateY(0)';
                }
            } else {
                header.style.transform = 'translateY(0)';
            }

            lastScroll = currentScroll;
        }, { passive: true });
    }

    /**
     * Lazy loading de imágenes (fallback para navegadores antiguos)
     */
    function initLazyLoading() {
        if ('loading' in HTMLImageElement.prototype) {
            // El navegador soporta lazy loading nativo
            var images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(function(img) {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
            });
        } else {
            // Fallback con IntersectionObserver
            var lazyImages = document.querySelectorAll('img[data-src]');

            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                lazyImages.forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        }
    }

    /**
     * Inicialización cuando el DOM está listo
     */
    function init() {
        initMobileMenu();
        initSmoothScroll();
        initStickyHeader();
        initLazyLoading();
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/**
 * Dashboard Components JS
 *
 * Interactividad para componentes de dashboard
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

(function($) {
    'use strict';

    const FlavorDashboard = {

        /**
         * Inicializar
         */
        init: function() {
            this.initCollapsibles();
            this.initDismissibles();
            this.initTooltips();
            this.initCounters();
            this.initCharts();
        },

        /**
         * Secciones colapsables
         */
        initCollapsibles: function() {
            $('.dm-section__toggle').on('click', function() {
                const $section = $(this).closest('.dm-section');
                $section.toggleClass('dm-section--collapsed');

                // Guardar estado en localStorage
                const sectionId = $section.data('section-id');
                if (sectionId) {
                    const isCollapsed = $section.hasClass('dm-section--collapsed');
                    localStorage.setItem('dm-section-' + sectionId, isCollapsed ? '1' : '0');
                }
            });

            // Restaurar estados guardados
            $('.dm-section[data-section-id]').each(function() {
                const sectionId = $(this).data('section-id');
                const isCollapsed = localStorage.getItem('dm-section-' + sectionId) === '1';
                if (isCollapsed) {
                    $(this).addClass('dm-section--collapsed');
                }
            });
        },

        /**
         * Alertas descartables
         */
        initDismissibles: function() {
            $('.dm-alert__close').on('click', function() {
                const $alert = $(this).closest('.dm-alert');
                $alert.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Tooltips básicos
         */
        initTooltips: function() {
            // Mostrar valor en hover de mini-charts
            $('.dm-mini-chart__bar').on('mouseenter', function() {
                const value = $(this).data('value');
                if (value !== undefined) {
                    $(this).attr('title', value);
                }
            });

            // Tooltips para stat cards
            $('[data-tooltip]').each(function() {
                const tooltip = $(this).data('tooltip');
                if (tooltip && !$(this).attr('title')) {
                    $(this).attr('title', tooltip);
                }
            });
        },

        /**
         * Animación de contadores
         */
        initCounters: function() {
            $('.dm-stat-card__value').each(function() {
                const $el = $(this);
                const text = $el.text().trim();

                // Solo animar números
                const number = parseFloat(text.replace(/[^0-9.-]/g, ''));
                if (!isNaN(number) && number > 0) {
                    $el.data('target', number);
                    $el.text('0');
                }
            });

            // Observer para animar cuando entren en viewport
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.animateCounter($(entry.target));
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });

                $('.dm-stat-card__value[data-target]').each(function() {
                    observer.observe(this);
                });
            }
        },

        /**
         * Animar contador individual
         */
        animateCounter: function($el) {
            const target = $el.data('target');
            const duration = 1000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            let step = 0;

            const timer = setInterval(() => {
                step++;
                current += increment;

                if (step >= steps) {
                    current = target;
                    clearInterval(timer);
                }

                // Formatear número
                const formatted = this.formatNumber(current);
                $el.text(formatted);
            }, duration / steps);
        },

        /**
         * Formatear número
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return Math.round(num).toString();
        },

        /**
         * Mini charts interactivos
         */
        initCharts: function() {
            $('.dm-mini-chart__bar').on('click', function() {
                // Resaltar barra seleccionada
                $(this).siblings().css('opacity', '0.5');
                $(this).css('opacity', '1');

                // Reset después de 2 segundos
                setTimeout(() => {
                    $(this).parent().find('.dm-mini-chart__bar').css('opacity', '1');
                }, 2000);
            });
        },

        /**
         * Refrescar dashboard (para futuras implementaciones AJAX)
         */
        refresh: function(moduleId) {
            console.log('Refreshing dashboard for module:', moduleId);
            // TODO: Implementar actualización AJAX
        },

        /**
         * Actualizar stat card
         */
        updateStatCard: function($card, newValue) {
            const $value = $card.find('.dm-stat-card__value');
            $value.data('target', newValue);
            this.animateCounter($value);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorDashboard.init();
    });

    // Exponer globalmente
    window.FlavorDashboard = FlavorDashboard;

})(jQuery);

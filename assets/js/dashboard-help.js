/**
 * Dashboard Help System - JavaScript
 *
 * Maneja la interactividad de la sección de ayuda colapsable,
 * tooltips y modales de video.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

(function($) {
    'use strict';

    /**
     * Dashboard Help Controller
     */
    const DashboardHelp = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Toggle sección de ayuda
            $(document).on('click', '.dm-help__header', this.toggleHelp.bind(this));
            $(document).on('keydown', '.dm-help__header', this.handleKeydown.bind(this));

            // Dismiss ayuda
            $(document).on('click', '.dm-help__dismiss', this.dismissHelp.bind(this));

            // Restaurar ayuda
            $(document).on('click', '.dm-help__restore-btn', this.restoreHelp.bind(this));

            // Video modal
            $(document).on('click', '.dm-help__video-btn', this.openVideoModal.bind(this));
            $(document).on('click', '.dm-video-modal__close, .dm-video-modal', this.closeVideoModal.bind(this));
            $(document).on('click', '.dm-video-modal__content', function(e) {
                e.stopPropagation();
            });

            // Cerrar modal con Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    DashboardHelp.closeVideoModal();
                }
            });
        },

        /**
         * Toggle sección de ayuda
         */
        toggleHelp: function(e) {
            // No toggle si se hizo clic en un botón
            if ($(e.target).closest('.dm-help__toggle, .dm-help__dismiss').length) {
                return;
            }

            const $help = $(e.currentTarget).closest('.dm-help');
            this.toggle($help);
        },

        /**
         * Toggle con animación
         */
        toggle: function($help) {
            const isCollapsed = $help.hasClass('dm-help--collapsed');
            const $header = $help.find('.dm-help__header');
            const $content = $help.find('.dm-help__content');

            if (isCollapsed) {
                $help.removeClass('dm-help--collapsed');
                $header.attr('aria-expanded', 'true');
                $content.slideDown(300);
            } else {
                $help.addClass('dm-help--collapsed');
                $header.attr('aria-expanded', 'false');
                $content.slideUp(300);
            }
        },

        /**
         * Manejar teclas
         */
        handleKeydown: function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleHelp(e);
            }
        },

        /**
         * Ocultar ayuda permanentemente
         */
        dismissHelp: function(e) {
            e.stopPropagation();

            const $help = $(e.currentTarget).closest('.dm-help');
            const moduleId = $help.data('module');

            // Animar salida
            $help.slideUp(300, function() {
                $help.addClass('dm-help--dismissed');
            });

            // Guardar preferencia via AJAX
            if (typeof flavorDashboardHelp !== 'undefined') {
                $.post(flavorDashboardHelp.ajaxUrl, {
                    action: 'flavor_dismiss_help',
                    module: moduleId,
                    nonce: flavorDashboardHelp.nonce
                });

                // Mostrar notificación
                this.showNotice(flavorDashboardHelp.i18n.dismissed);
            }
        },

        /**
         * Restaurar ayuda
         */
        restoreHelp: function(e) {
            const $help = $(e.currentTarget).closest('.dm-help');
            const moduleId = $help.data('module');

            $help.removeClass('dm-help--dismissed').slideDown(300);

            // Actualizar preferencia via AJAX
            if (typeof flavorDashboardHelp !== 'undefined') {
                $.post(flavorDashboardHelp.ajaxUrl, {
                    action: 'flavor_restore_help',
                    module: moduleId,
                    nonce: flavorDashboardHelp.nonce
                });
            }
        },

        /**
         * Mostrar notificación temporal
         */
        showNotice: function(message) {
            const $notice = $('<div class="dm-notice dm-notice--info">' +
                '<span class="dashicons dashicons-info"></span>' +
                message +
                '</div>');

            $notice.css({
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                background: '#1e293b',
                color: '#f8fafc',
                padding: '12px 20px',
                borderRadius: '8px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                zIndex: 100000,
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                fontSize: '13px'
            });

            $('body').append($notice);

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Abrir modal de video
         */
        openVideoModal: function(e) {
            e.preventDefault();

            const videoUrl = $(e.currentTarget).data('video');
            if (!videoUrl) return;

            // Crear modal
            const $modal = $('<div class="dm-video-modal">' +
                '<div class="dm-video-modal__content">' +
                    '<div class="dm-video-modal__header">' +
                        '<span class="dm-video-modal__title">' +
                            (typeof flavorDashboardHelp !== 'undefined' ? flavorDashboardHelp.i18n.videoTitle : 'Tutorial') +
                        '</span>' +
                        '<button type="button" class="dm-video-modal__close">' +
                            '<span class="dashicons dashicons-no-alt"></span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="dm-video-modal__body">' +
                        '<iframe src="' + this.getEmbedUrl(videoUrl) + '" allowfullscreen></iframe>' +
                    '</div>' +
                '</div>' +
            '</div>');

            $('body').append($modal);

            // Animar entrada
            setTimeout(function() {
                $modal.addClass('dm-video-modal--active');
            }, 10);

            // Prevenir scroll del body
            $('body').css('overflow', 'hidden');
        },

        /**
         * Cerrar modal de video
         */
        closeVideoModal: function() {
            const $modal = $('.dm-video-modal');

            if ($modal.length) {
                $modal.removeClass('dm-video-modal--active');

                setTimeout(function() {
                    $modal.remove();
                    $('body').css('overflow', '');
                }, 300);
            }
        },

        /**
         * Obtener URL de embed para YouTube/Vimeo
         */
        getEmbedUrl: function(url) {
            // YouTube
            const youtubeMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/);
            if (youtubeMatch) {
                return 'https://www.youtube.com/embed/' + youtubeMatch[1] + '?autoplay=1&rel=0';
            }

            // Vimeo
            const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) {
                return 'https://player.vimeo.com/video/' + vimeoMatch[1] + '?autoplay=1';
            }

            // Si ya es URL de embed, devolverla
            return url;
        },

        /**
         * Inicializar tooltips avanzados
         */
        initTooltips: function() {
            // Los tooltips básicos funcionan con CSS puro
            // Este método es para funcionalidad adicional si se necesita

            // Posicionar tooltips para evitar que salgan de la pantalla
            $(document).on('mouseenter', '.dm-tooltip', function() {
                const $tooltip = $(this);
                const $after = $tooltip.find('::after');

                // Si el tooltip sale por la derecha, cambiarlo a la izquierda
                const rect = this.getBoundingClientRect();
                const tooltipWidth = 280; // max-width del tooltip

                if (rect.right + tooltipWidth > window.innerWidth) {
                    $tooltip.attr('data-position', 'left');
                }

                if (rect.left - tooltipWidth < 0) {
                    $tooltip.attr('data-position', 'right');
                }
            });
        }
    };

    /**
     * AJAX handlers para WordPress
     */
    if (typeof wp !== 'undefined') {
        // Handler para dismiss
        $(document).on('wp-ajax-flavor_dismiss_help', function(e, data) {
            if (data.success) {
                console.log('Help dismissed for module:', data.module);
            }
        });
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        DashboardHelp.init();
    });

    // Exponer para uso externo si es necesario
    window.FlavorDashboardHelp = DashboardHelp;

})(jQuery);

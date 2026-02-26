/**
 * JavaScript para Funcionalidades Compartidas
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorFeaturesHandler = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.initRatings();
            this.trackViews();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Botones de acción (favoritos, follow, bookmark)
            $(document).on('click', '.flavor-feature-btn', this.handleButtonClick.bind(this));

            // Estrellas de rating
            $(document).on('click', '.flavor-stars .star', this.handleRatingClick.bind(this));
            $(document).on('mouseenter', '.flavor-stars .star', this.handleRatingHover.bind(this));
            $(document).on('mouseleave', '.flavor-stars', this.handleRatingLeave.bind(this));
        },

        /**
         * Manejar click en botones
         */
        handleButtonClick: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const $container = $btn.closest('.flavor-entity-features');
            const entityType = $container.data('entity-type');
            const entityId = $container.data('entity-id');
            const action = $btn.data('action');

            if (!FlavorFeatures.isLoggedIn) {
                this.showLoginRequired();
                return;
            }

            // Añadir animación
            $btn.addClass('animating');
            setTimeout(() => $btn.removeClass('animating'), 300);

            this.sendInteraction(entityType, entityId, action, null, function(response) {
                if (response.success) {
                    const status = response.data.status;

                    if (status === 'added') {
                        $btn.addClass('active');
                    } else if (status === 'removed') {
                        $btn.removeClass('active');
                    }

                    // Actualizar contador
                    if (response.data.counts && response.data.counts[action]) {
                        $btn.find('.count').text(response.data.counts[action].count);
                    }

                    // Actualizar texto si es necesario
                    if (action === 'follow') {
                        const isActive = $btn.hasClass('active');
                        $btn.find('.dashicons')
                            .removeClass('dashicons-plus dashicons-yes')
                            .addClass(isActive ? 'dashicons-yes' : 'dashicons-plus');
                        $btn.contents().filter(function() {
                            return this.nodeType === 3;
                        }).first().replaceWith(isActive ? ' Siguiendo ' : ' Seguir ');
                    }
                }
            });
        },

        /**
         * Manejar click en estrella
         */
        handleRatingClick: function(e) {
            const $star = $(e.currentTarget);
            const $container = $star.closest('.flavor-entity-features');
            const entityType = $container.data('entity-type');
            const entityId = $container.data('entity-id');
            const value = $star.data('value');

            if (!FlavorFeatures.isLoggedIn) {
                this.showLoginRequired();
                return;
            }

            const $starsContainer = $star.closest('.flavor-stars');
            $starsContainer.data('current', value);

            this.sendInteraction(entityType, entityId, 'rating', value, function(response) {
                if (response.success && response.data.counts && response.data.counts.rating) {
                    const avg = response.data.counts.rating.avg;
                    const count = response.data.counts.rating.count;

                    // Actualizar estrellas
                    $starsContainer.find('.star').each(function(i) {
                        $(this).toggleClass('filled', (i + 1) <= Math.round(avg));
                    });

                    // Actualizar info
                    $starsContainer.siblings('.rating-info').text(
                        avg.toFixed(1) + ' (' + count + ' votos)'
                    );
                }
            });
        },

        /**
         * Hover en estrellas
         */
        handleRatingHover: function(e) {
            const $star = $(e.currentTarget);
            const value = $star.data('value');
            const $starsContainer = $star.closest('.flavor-stars');

            $starsContainer.find('.star').each(function(i) {
                $(this).toggleClass('hover', (i + 1) <= value);
            });
        },

        /**
         * Leave en contenedor de estrellas
         */
        handleRatingLeave: function(e) {
            const $starsContainer = $(e.currentTarget);
            const current = $starsContainer.data('current') || 0;

            $starsContainer.find('.star').removeClass('hover').each(function(i) {
                $(this).toggleClass('filled', (i + 1) <= current);
            });
        },

        /**
         * Inicializar ratings existentes
         */
        initRatings: function() {
            $('.flavor-stars').each(function() {
                const $this = $(this);
                const current = $this.data('current') || 0;

                $this.find('.star').each(function(i) {
                    $(this).toggleClass('filled', (i + 1) <= current);
                });
            });
        },

        /**
         * Trackear vistas
         */
        trackViews: function() {
            $('.flavor-entity-features').each(function() {
                const $container = $(this);
                const entityType = $container.data('entity-type');
                const entityId = $container.data('entity-id');

                // Enviar vista (no requiere login)
                $.ajax({
                    url: FlavorFeatures.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'flavor_feature_action',
                        feature_action: 'view',
                        entity_type: entityType,
                        entity_id: entityId,
                        nonce: FlavorFeatures.nonce
                    }
                });
            });
        },

        /**
         * Enviar interacción al servidor
         */
        sendInteraction: function(entityType, entityId, action, value, callback) {
            $.ajax({
                url: FlavorFeatures.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'flavor_feature_action',
                    feature_action: action,
                    entity_type: entityType,
                    entity_id: entityId,
                    value: value,
                    nonce: FlavorFeatures.nonce
                },
                success: callback,
                error: function() {
                    alert(FlavorFeatures.strings.error);
                }
            });
        },

        /**
         * Mostrar mensaje de login requerido
         */
        showLoginRequired: function() {
            // Crear modal simple
            const $modal = $('<div class="flavor-login-modal">' +
                '<div class="flavor-login-modal-content">' +
                '<p>' + FlavorFeatures.strings.loginRequired + '</p>' +
                '<a href="' + (FlavorFeatures.loginUrl || '/wp-login.php') + '" class="button">Iniciar sesión</a>' +
                '<button class="close-modal">×</button>' +
                '</div></div>');

            $('body').append($modal);

            $modal.on('click', '.close-modal, .flavor-login-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('close-modal')) {
                    $modal.remove();
                }
            });

            // Estilos inline del modal
            $modal.css({
                position: 'fixed',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                background: 'rgba(0,0,0,0.5)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                zIndex: 99999
            });

            $modal.find('.flavor-login-modal-content').css({
                background: '#fff',
                padding: '30px',
                borderRadius: '8px',
                textAlign: 'center',
                position: 'relative',
                maxWidth: '400px'
            });

            $modal.find('.close-modal').css({
                position: 'absolute',
                top: '10px',
                right: '10px',
                border: 'none',
                background: 'none',
                fontSize: '24px',
                cursor: 'pointer'
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if (typeof FlavorFeatures !== 'undefined') {
            FlavorFeaturesHandler.init();
        }
    });

    // Exponer para uso externo
    window.FlavorFeaturesHandler = FlavorFeaturesHandler;

})(jQuery);

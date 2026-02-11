/**
 * Panel de Gestión Unificado - JavaScript
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

(function($) {
    'use strict';

    window.FlavorGestionPanel = {

        /**
         * Inicializa el panel
         */
        init: function() {
            this.bindEvents();
            this.initAutoRefresh();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            // Refresh manual de estadísticas
            $(document).on('click', '.flavor-refresh-stats', this.refreshStats.bind(this));

            // Expandir/colapsar categorías
            $(document).on('click', '.flavor-gestion-categoria h2', this.toggleCategoria.bind(this));
        },

        /**
         * Auto-refresh de estadísticas cada 60 segundos
         */
        initAutoRefresh: function() {
            if ($('.flavor-gestion-stats-grid').length) {
                setInterval(this.refreshStats.bind(this), 60000);
            }
        },

        /**
         * Refresca estadísticas via AJAX
         */
        refreshStats: function() {
            var $grid = $('.flavor-gestion-stats-grid');
            if (!$grid.length) return;

            $grid.css('opacity', '0.6');

            $.ajax({
                url: flavorGestionData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_gestion_stats',
                    nonce: flavorGestionData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.estadisticas) {
                        FlavorGestionPanel.updateStatsUI(response.data.estadisticas);
                    }
                },
                complete: function() {
                    $grid.css('opacity', '1');
                }
            });
        },

        /**
         * Actualiza la UI de estadísticas
         */
        updateStatsUI: function(stats) {
            var $grid = $('.flavor-gestion-stats-grid');
            if (!$grid.length || !stats.length) return;

            // Actualizar valores existentes o reconstruir
            stats.forEach(function(stat, index) {
                var $card = $grid.find('.flavor-gestion-stat-card').eq(index);
                if ($card.length) {
                    $card.find('.stat-value').text(stat.valor);
                    $card.find('.stat-label').text(stat.label);
                }
            });
        },

        /**
         * Toggle de categoría (expandir/colapsar)
         */
        toggleCategoria: function(e) {
            var $header = $(e.currentTarget);
            var $modulos = $header.next('.flavor-gestion-modulos');

            $modulos.slideToggle(200);
            $header.toggleClass('collapsed');
        },

        /**
         * Muestra notificación toast
         */
        showToast: function(message, type) {
            type = type || 'info';

            var $toast = $('<div class="flavor-toast flavor-toast--' + type + '">' + message + '</div>');
            $('body').append($toast);

            setTimeout(function() {
                $toast.addClass('show');
            }, 10);

            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorGestionPanel.init();
    });

})(jQuery);

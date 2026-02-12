/**
 * Flavor Unified Dashboard JavaScript
 */

(function($) {
    'use strict';

    const FlavorUnifiedDashboard = {

        init: function() {
            this.initTabs();
            this.initQuickLinks();
            this.initSearch();
        },

        /**
         * Inicializar navegación por tabs
         */
        initTabs: function() {
            $('.category-tab').on('click', function(e) {
                e.preventDefault();

                const category = $(this).data('category');

                // Actualizar tabs activos
                $('.category-tab').removeClass('active');
                $(this).addClass('active');

                // Actualizar contenido
                $('.category-content').removeClass('active');
                $(`.category-content[data-category="${category}"]`).addClass('active');

                // Actualizar URL sin recargar
                const url = new URL(window.location);
                url.searchParams.set('cat', category);
                window.history.pushState({}, '', url);
            });
        },

        /**
         * Inicializar quick links
         */
        initQuickLinks: function() {
            $('.toggle-quick-links').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const menu = $(this).siblings('.quick-links-menu');

                // Cerrar otros menús abiertos
                $('.quick-links-menu').not(menu).removeClass('show');

                // Toggle este menú
                menu.toggleClass('show');
            });

            // Cerrar al hacer click fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.quick-links').length) {
                    $('.quick-links-menu').removeClass('show');
                }
            });
        },

        /**
         * Inicializar búsqueda (futuro)
         */
        initSearch: function() {
            // TODO: Implementar búsqueda de módulos
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        FlavorUnifiedDashboard.init();
    });

})(jQuery);

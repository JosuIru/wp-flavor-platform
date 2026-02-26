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
            this.initViewToggle();
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
        },

        /**
         * Inicializar toggle de vista grid/list
         */
        initViewToggle: function() {
            const storageKey = 'flavor_dashboard_view';
            const savedView = localStorage.getItem(storageKey) || 'list';

            // Aplicar vista guardada al cargar
            this.applyView(savedView);

            // Manejar clicks en botones de vista
            $(document).on('click', '.fl-view-btn, .fud-view-btn', function(e) {
                e.preventDefault();

                const viewType = $(this).data('view');
                const container = $(this).closest('.fl-view-toggle, .fud-view-toggle');

                // Actualizar estados de botones
                container.find('.fl-view-btn, .fud-view-btn').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');

                // Aplicar vista
                FlavorUnifiedDashboard.applyView(viewType);

                // Guardar preferencia
                localStorage.setItem(storageKey, viewType);
            });
        },

        /**
         * Aplicar modo de vista al contenedor
         */
        applyView: function(viewType) {
            const gridContainer = $('.fud-modules-grid, .fl-modules-grid, .modules-grid');

            if (viewType === 'grid') {
                gridContainer.removeClass('view-list').addClass('view-grid');
            } else {
                gridContainer.removeClass('view-grid').addClass('view-list');
            }

            // Actualizar botones activos
            $('.fl-view-btn, .fud-view-btn').each(function() {
                const isActive = $(this).data('view') === viewType;
                $(this).toggleClass('active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        FlavorUnifiedDashboard.init();
    });

})(jQuery);

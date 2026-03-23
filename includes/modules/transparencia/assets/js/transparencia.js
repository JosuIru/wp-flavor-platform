/**
 * JavaScript del Módulo de Transparencia
 *
 * @package FlavorChatIA
 * @subpackage Transparencia
 */

(function($) {
    'use strict';

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        initTransparencia();
    });

    /**
     * Inicializa el módulo de transparencia
     */
    function initTransparencia() {
        // Filtros
        initFilters();
        
        // Expandir/colapsar secciones
        initCollapsibles();
        
        // Búsqueda
        initSearch();
    }

    /**
     * Inicializa los filtros
     */
    function initFilters() {
        $('.transparencia-filter-group select, .transparencia-filter-group input').on('change', function() {
            var $form = $(this).closest('form');
            if ($form.length) {
                $form.submit();
            }
        });
    }

    /**
     * Inicializa secciones colapsables
     */
    function initCollapsibles() {
        $('.transparencia-collapsible-header').on('click', function() {
            var $content = $(this).next('.transparencia-collapsible-content');
            var $icon = $(this).find('.collapse-icon');
            
            $content.slideToggle(200);
            $icon.toggleClass('rotated');
        });
    }

    /**
     * Inicializa búsqueda en tiempo real
     */
    function initSearch() {
        var $searchInput = $('.transparencia-search-input');
        var searchTimeout;
        
        $searchInput.on('input', function() {
            var query = $(this).val().toLowerCase();
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                filterItems(query);
            }, 300);
        });
    }

    /**
     * Filtra items por búsqueda
     */
    function filterItems(query) {
        $('.transparencia-searchable').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    }

})(jQuery);

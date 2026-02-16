/**
 * Visual Builder Pro - Autocompletado de Enlaces
 *
 * Búsqueda de posts y páginas para campos de URL
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Componente Alpine para el autocompletado de enlaces
 */
function vbpLinkAutocomplete() {
    return {
        isOpen: false,
        isLoading: false,
        searchQuery: '',
        results: [],
        activeIndex: -1,
        debounceTimer: null,
        field: '',
        itemIndex: null,

        init: function() {
            // Observar cambios en la consulta de búsqueda
            this.$watch('searchQuery', this.handleSearch.bind(this));
        },

        handleSearch: function(value) {
            var self = this;

            // Cancelar búsqueda anterior
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Limpiar si está vacío o es muy corto
            if (!value || value.length < 2) {
                this.results = [];
                this.isOpen = false;
                return;
            }

            // Si ya parece una URL completa, no buscar
            if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('mailto:') || value.startsWith('tel:')) {
                this.results = [];
                this.isOpen = false;
                return;
            }

            // Debounce 300ms
            this.debounceTimer = setTimeout(function() {
                self.performSearch(value);
            }, 300);
        },

        performSearch: function(query) {
            var self = this;
            this.isLoading = true;
            this.isOpen = true;

            // Realizar búsqueda via REST API
            fetch(VBP_Config.restUrl + 'search-posts?search=' + encodeURIComponent(query), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                self.results = data.results || [];
                self.activeIndex = -1;
                self.isLoading = false;
            })
            .catch(function(error) {
                console.error('Error buscando posts:', error);
                self.results = [];
                self.isLoading = false;
            });
        },

        selectResult: function(result) {
            this.searchQuery = result.url;
            this.results = [];
            this.isOpen = false;

            // Emitir evento para actualizar el modelo
            this.$dispatch('link-selected', { url: result.url });
        },

        handleKeydown: function(event) {
            if (!this.isOpen || this.results.length === 0) return;

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.activeIndex = Math.min(this.activeIndex + 1, this.results.length - 1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.activeIndex = Math.max(this.activeIndex - 1, 0);
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.activeIndex >= 0 && this.results[this.activeIndex]) {
                        this.selectResult(this.results[this.activeIndex]);
                    }
                    break;
                case 'Escape':
                    this.isOpen = false;
                    break;
            }
        },

        closeDropdown: function() {
            // Pequeño delay para permitir clicks en resultados
            var self = this;
            setTimeout(function() {
                self.isOpen = false;
            }, 200);
        },

        getTypeLabel: function(type) {
            var labels = {
                'post': 'Entrada',
                'page': 'Página',
                'flavor_landing': 'Landing',
                'category': 'Categoría'
            };
            return labels[type] || type;
        }
    };
}

// Registrar componente globalmente
window.vbpLinkAutocomplete = vbpLinkAutocomplete;

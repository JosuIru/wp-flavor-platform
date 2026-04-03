/**
 * Visual Builder Pro - Autocompletado de Enlaces (Mejorado)
 *
 * Búsqueda de posts, páginas, archivos y validación de URLs
 * Incluye soporte para anclas (#), mailto:, tel:, y archivos de Media Library
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

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
        showAdvanced: false,
        validationError: '',

        // Tipos de enlaces especiales
        linkTypes: {
            url: { icon: '🔗', label: 'URL' },
            page: { icon: '📄', label: 'Página' },
            post: { icon: '📝', label: 'Entrada' },
            file: { icon: '📎', label: 'Archivo' },
            anchor: { icon: '⚓', label: 'Ancla' },
            email: { icon: '✉️', label: 'Email' },
            phone: { icon: '📞', label: 'Teléfono' }
        },

        init: function() {
            var self = this;
            // Observar cambios en la consulta de búsqueda
            this.$watch('searchQuery', this.handleSearch.bind(this));
        },

        /**
         * Validar si una URL es válida
         * @param {string} url - URL a validar
         * @returns {object} - { valid: boolean, type: string, error: string }
         */
        validateUrl: function(url) {
            if (!url || typeof url !== 'string') {
                return { valid: false, type: null, error: '' };
            }

            url = url.trim();

            // URL vacía es válida (campo opcional)
            if (url === '') {
                return { valid: true, type: 'empty', error: '' };
            }

            // Ancla interna (#seccion)
            if (url.startsWith('#')) {
                if (url.length < 2) {
                    return { valid: false, type: 'anchor', error: 'Ancla incompleta' };
                }
                // Validar formato de ancla (solo alfanuméricos, guiones y guiones bajos)
                var anchorPart = url.substring(1);
                if (!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(anchorPart)) {
                    return { valid: false, type: 'anchor', error: 'Formato de ancla inválido' };
                }
                return { valid: true, type: 'anchor', error: '' };
            }

            // Email (mailto:)
            if (url.startsWith('mailto:')) {
                var email = url.substring(7);
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    return { valid: false, type: 'email', error: 'Email inválido' };
                }
                return { valid: true, type: 'email', error: '' };
            }

            // Teléfono (tel:)
            if (url.startsWith('tel:')) {
                var phone = url.substring(4);
                // Permitir números, +, espacios y guiones
                if (!/^[+\d\s-()]+$/.test(phone) || phone.replace(/\D/g, '').length < 6) {
                    return { valid: false, type: 'phone', error: 'Teléfono inválido' };
                }
                return { valid: true, type: 'phone', error: '' };
            }

            // URL relativa (comienza con /)
            if (url.startsWith('/')) {
                return { valid: true, type: 'relative', error: '' };
            }

            // URL absoluta
            if (url.startsWith('http://') || url.startsWith('https://')) {
                try {
                    new URL(url);
                    return { valid: true, type: 'absolute', error: '' };
                } catch (e) {
                    return { valid: false, type: 'absolute', error: 'URL mal formada' };
                }
            }

            // Podría ser una búsqueda o URL sin protocolo
            // Si contiene . podría ser un dominio
            if (url.indexOf('.') !== -1 && url.indexOf(' ') === -1) {
                try {
                    new URL('https://' + url);
                    return { valid: true, type: 'domain', error: '', suggestion: 'https://' + url };
                } catch (e) {
                    // No es un dominio válido
                }
            }

            // Parece ser una consulta de búsqueda
            return { valid: true, type: 'search', error: '' };
        },

        handleSearch: function(value) {
            var self = this;

            // Cancelar búsqueda anterior
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Validar URL
            var validation = this.validateUrl(value);
            this.validationError = validation.error;

            // Limpiar si está vacío o es muy corto
            if (!value || value.length < 2) {
                this.results = [];
                this.isOpen = false;
                return;
            }

            // Si ya parece una URL completa, no buscar
            if (value.startsWith('http://') || value.startsWith('https://') ||
                value.startsWith('mailto:') || value.startsWith('tel:') ||
                value.startsWith('#') || value.startsWith('/')) {
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
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                self.results = data.results || [];

                // Añadir opciones especiales si la consulta parece serlo
                self.addSpecialOptions(query);

                self.activeIndex = -1;
                self.isLoading = false;
            })
            .catch(function(error) {
                vbpLog.error('Error buscando posts:', error);
                self.results = [];
                self.isLoading = false;
            });
        },

        /**
         * Añadir opciones especiales basadas en la consulta
         */
        addSpecialOptions: function(query) {
            var lowerQuery = query.toLowerCase();

            // Si parece email, sugerir mailto:
            if (lowerQuery.indexOf('@') !== -1) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(query)) {
                    this.results.unshift({
                        title: 'Enlace de email: ' + query,
                        url: 'mailto:' + query,
                        type: 'email',
                        icon: '✉️'
                    });
                }
            }

            // Si parece teléfono, sugerir tel:
            if (/^[+\d\s-()]{6,}$/.test(query)) {
                this.results.unshift({
                    title: 'Enlace de teléfono: ' + query,
                    url: 'tel:' + query.replace(/\s/g, ''),
                    type: 'phone',
                    icon: '📞'
                });
            }
        },

        selectResult: function(result) {
            this.searchQuery = result.url;
            this.results = [];
            this.isOpen = false;
            this.validationError = '';

            // Emitir evento para actualizar el modelo
            this.$dispatch('link-selected', { url: result.url, title: result.title });
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
                'category': 'Categoría',
                'attachment': 'Archivo',
                'email': 'Email',
                'phone': 'Teléfono'
            };
            return labels[type] || type;
        },

        getTypeIcon: function(type) {
            var icons = {
                'post': '📝',
                'page': '📄',
                'flavor_landing': '🎯',
                'category': '📁',
                'attachment': '📎',
                'email': '✉️',
                'phone': '📞'
            };
            return icons[type] || '🔗';
        },

        /**
         * Abrir Media Library para seleccionar archivo
         */
        openFileSelector: function() {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar archivo',
                    button: { text: 'Usar archivo' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    if (attachment && attachment.url) {
                        self.searchQuery = attachment.url;
                        self.$dispatch('link-selected', { url: attachment.url, title: attachment.title || attachment.filename });
                    }
                });

                frame.open();
            } else {
                alert('La biblioteca de medios no está disponible');
            }
        },

        /**
         * Insertar ancla (solicitar nombre de ancla)
         */
        insertAnchor: function() {
            var anchorName = prompt('Nombre del ancla (sin #):');
            if (anchorName && /^[a-zA-Z][a-zA-Z0-9_-]*$/.test(anchorName)) {
                this.searchQuery = '#' + anchorName;
                this.$dispatch('link-selected', { url: '#' + anchorName });
            } else if (anchorName) {
                this.validationError = 'El ancla debe comenzar con letra y solo contener letras, números, - y _';
            }
        },

        /**
         * Insertar mailto:
         */
        insertEmail: function() {
            var email = prompt('Dirección de email:');
            if (email) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(email)) {
                    this.searchQuery = 'mailto:' + email;
                    this.$dispatch('link-selected', { url: 'mailto:' + email });
                } else {
                    this.validationError = 'Formato de email inválido';
                }
            }
        },

        /**
         * Insertar tel:
         */
        insertPhone: function() {
            var phone = prompt('Número de teléfono:');
            if (phone) {
                var cleanPhone = phone.replace(/\s/g, '');
                if (/^[+\d-()]+$/.test(cleanPhone) && cleanPhone.replace(/\D/g, '').length >= 6) {
                    this.searchQuery = 'tel:' + cleanPhone;
                    this.$dispatch('link-selected', { url: 'tel:' + cleanPhone });
                } else {
                    this.validationError = 'Formato de teléfono inválido';
                }
            }
        },

        /**
         * Toggle panel avanzado
         */
        toggleAdvanced: function() {
            this.showAdvanced = !this.showAdvanced;
        },

        /**
         * Verificar si la URL actual tiene error de validación
         */
        hasValidationError: function() {
            return this.validationError && this.validationError.length > 0;
        },

        /**
         * Obtener clase CSS según el estado de validación
         */
        getValidationClass: function() {
            if (!this.searchQuery) return '';
            var validation = this.validateUrl(this.searchQuery);
            if (!validation.valid) return 'vbp-link-invalid';
            if (validation.type === 'search') return 'vbp-link-searching';
            return 'vbp-link-valid';
        }
    };
}

// Registrar componente globalmente
window.vbpLinkAutocomplete = vbpLinkAutocomplete;

/**
 * Visual Builder Pro - Breadcrumbs de Navegación
 * Muestra la ruta del elemento seleccionado
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.data('vbpBreadcrumbs', function() {
        return {
            path: [],

            init: function() {
                var self = this;

                // Observar cambios en la selección
                this.$watch('$store.vbp.selection.elementIds', function(ids) {
                    self.updatePath(ids);
                });
            },

            updatePath: function(selectedIds) {
                if (!selectedIds || selectedIds.length === 0) {
                    this.path = [{ id: null, name: 'Documento', type: 'document', icon: '📄' }];
                    return;
                }

                var store = Alpine.store('vbp');
                var elementId = selectedIds[0];
                var element = store.getElement ? store.getElement(elementId) : null;

                if (!element) {
                    this.path = [{ id: null, name: 'Documento', type: 'document', icon: '📄' }];
                    return;
                }

                // Construir path
                this.path = [
                    { id: null, name: 'Documento', type: 'document', icon: '📄' }
                ];

                // Agregar el elemento actual
                this.path.push({
                    id: element.id,
                    name: element.name || this.getTypeName(element.type),
                    type: element.type,
                    icon: this.getTypeIcon(element.type)
                });
            },

            getTypeName: function(type) {
                var names = {
                    'text': 'Texto',
                    'heading': 'Encabezado',
                    'image': 'Imagen',
                    'button': 'Botón',
                    'divider': 'Divisor',
                    'spacer': 'Espaciador',
                    'hero': 'Hero',
                    'features': 'Características',
                    'testimonials': 'Testimonios',
                    'pricing': 'Precios',
                    'cta': 'CTA',
                    'faq': 'FAQ',
                    'contact': 'Contacto',
                    'gallery': 'Galería',
                    'container': 'Contenedor',
                    'columns': 'Columnas',
                    'row': 'Fila',
                    'social-icons': 'Redes Sociales',
                    'icon-box': 'Caja de Icono',
                    'video': 'Video',
                    'map': 'Mapa',
                    'form': 'Formulario'
                };
                return names[type] || type;
            },

            getTypeIcon: function(type) {
                var icons = {
                    'text': 'T',
                    'heading': 'H',
                    'image': '🖼',
                    'button': '▢',
                    'divider': '—',
                    'spacer': '↕',
                    'hero': '🎯',
                    'features': '⚡',
                    'testimonials': '💬',
                    'pricing': '💰',
                    'cta': '📢',
                    'faq': '❓',
                    'contact': '✉',
                    'gallery': '🖼',
                    'container': '☐',
                    'columns': '▥',
                    'row': '▤',
                    'social-icons': '🔗',
                    'icon-box': '📦',
                    'video': '🎬',
                    'map': '🗺',
                    'form': '📝'
                };
                return icons[type] || '📦';
            },

            selectElement: function(item) {
                if (item.id === null) {
                    // Deseleccionar todo
                    Alpine.store('vbp').clearSelection();
                } else {
                    // Seleccionar elemento
                    Alpine.store('vbp').setSelection([item.id]);
                }
            },

            isLast: function(index) {
                return index === this.path.length - 1;
            }
        };
    });

    /**
     * Control de Zoom
     */
    Alpine.data('vbpZoomControl', function() {
        return {
            presets: [25, 50, 75, 100, 125, 150, 200],

            get zoom() {
                return Alpine.store('vbp').zoom || 100;
            },

            set zoom(value) {
                Alpine.store('vbp').zoom = value;
            },

            zoomIn: function() {
                this.zoom = Math.min(200, this.zoom + 10);
            },

            zoomOut: function() {
                this.zoom = Math.max(25, this.zoom - 10);
            },

            resetZoom: function() {
                this.zoom = 100;
            },

            fitToScreen: function() {
                var canvas = document.querySelector('.vbp-canvas');
                var wrapper = document.querySelector('.vbp-canvas-wrapper');
                if (!canvas || !wrapper) return;

                var wrapperWidth = wrapper.clientWidth - 80; // padding
                var canvasWidth = canvas.offsetWidth;
                var ratio = (wrapperWidth / canvasWidth) * 100;

                this.zoom = Math.min(100, Math.floor(ratio / 5) * 5);
            },

            setPreset: function(value) {
                this.zoom = value;
            }
        };
    });

    /**
     * Indicador de estado de guardado
     */
    Alpine.store('vbpSaveStatus', {
        status: 'saved', // 'saved', 'saving', 'unsaved', 'error'
        lastSaved: null,
        message: '',

        setStatus: function(status, message) {
            this.status = status;
            this.message = message || '';
            if (status === 'saved') {
                this.lastSaved = new Date();
            }
        },

        getStatusText: function() {
            switch (this.status) {
                case 'saved':
                    if (this.lastSaved) {
                        return 'Guardado ' + this.formatTime(this.lastSaved);
                    }
                    return 'Guardado';
                case 'saving':
                    return 'Guardando...';
                case 'unsaved':
                    return 'Sin guardar';
                case 'error':
                    return this.message || 'Error al guardar';
                default:
                    return '';
            }
        },

        getStatusIcon: function() {
            switch (this.status) {
                case 'saved': return '✓';
                case 'saving': return '↻';
                case 'unsaved': return '●';
                case 'error': return '✕';
                default: return '';
            }
        },

        formatTime: function(date) {
            var now = new Date();
            var diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'hace un momento';
            if (diff < 3600) return 'hace ' + Math.floor(diff / 60) + ' min';
            if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
            return date.toLocaleDateString();
        }
    });

    // Observar cambios para actualizar estado
    document.addEventListener('alpine:initialized', function() {
        var store = Alpine.store('vbp');
        if (store) {
            // Marcar como sin guardar cuando hay cambios
            var originalMarkDirty = store.markDirty;
            if (originalMarkDirty) {
                store.markDirty = function() {
                    originalMarkDirty.call(store);
                    Alpine.store('vbpSaveStatus').setStatus('unsaved');
                };
            }
        }
    });
});

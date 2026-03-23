/**
 * Visual Builder Pro - Mini Mapa
 * Navegación rápida mejorada para documentos largos
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    // Store global del minimap
    Alpine.store('vbpMinimap', {
        isVisible: true,
        scale: 0.06,
        minScale: 0.03,
        maxScale: 0.12,

        toggle: function() {
            this.isVisible = !this.isVisible;
            localStorage.setItem('vbp_minimap_visible', this.isVisible);
        },

        setScale: function(newScale) {
            this.scale = Math.max(this.minScale, Math.min(this.maxScale, newScale));
            localStorage.setItem('vbp_minimap_scale', this.scale);
        },

        init: function() {
            var savedVisible = localStorage.getItem('vbp_minimap_visible');
            if (savedVisible !== null) {
                this.isVisible = savedVisible === 'true';
            }
            var savedScale = localStorage.getItem('vbp_minimap_scale');
            if (savedScale !== null) {
                this.scale = parseFloat(savedScale);
            }
        }
    });

    // Componente del minimap
    Alpine.data('vbpMinimap', function() {
        return {
            isDragging: false,
            elements: [],
            canvasHeight: 0,
            viewportTop: 0,
            viewportHeight: 0,
            scale: 0.06,
            tooltipElement: null,

            // Etiquetas para cada tipo de elemento
            elementLabels: {
                'text': 'Texto',
                'heading': 'Encabezado',
                'image': 'Imagen',
                'button': 'Botón',
                'hero': 'Hero',
                'features': 'Características',
                'testimonials': 'Testimonios',
                'pricing': 'Precios',
                'cta': 'Llamada a Acción',
                'contact': 'Contacto',
                'faq': 'FAQ',
                'team': 'Equipo',
                'stats': 'Estadísticas',
                'gallery': 'Galería',
                'container': 'Contenedor',
                'columns': 'Columnas',
                'row': 'Fila',
                'grid': 'Grid',
                'divider': 'Separador',
                'spacer': 'Espaciador',
                'icon': 'Icono',
                'form': 'Formulario',
                'newsletter': 'Newsletter',
                'countdown': 'Cuenta Regresiva',
                'accordion': 'Acordeón',
                'tabs': 'Pestañas',
                'video-embed': 'Video',
                'map': 'Mapa',
                'default': 'Elemento'
            },

            init: function() {
                var self = this;

                // Sincronizar scale con store
                this.scale = this.$store.vbpMinimap.scale;

                // Watch para cambios en elementos
                this.$nextTick(function() {
                    if (Alpine.store('vbp')) {
                        self.$watch('$store.vbp.elements', function() {
                            self.updateElements();
                        });
                    }
                });

                // Watch para cambios en scale del store
                this.$watch('$store.vbpMinimap.scale', function(newScale) {
                    self.scale = newScale;
                    self.updateElements();
                    self.updateViewport();
                });

                // Listener de scroll
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (canvasWrapper) {
                    canvasWrapper.addEventListener('scroll', function() {
                        self.updateViewport();
                    }, { passive: true });
                }

                // Inicializar
                this.$nextTick(function() {
                    self.updateElements();
                    self.updateViewport();
                });

                // Actualizar periódicamente
                setInterval(function() {
                    self.updateElements();
                    self.updateViewport();
                }, 500);
            },

            // Zoom controls
            zoomIn: function() {
                var newScale = this.scale + 0.02;
                this.$store.vbpMinimap.setScale(newScale);
            },

            zoomOut: function() {
                var newScale = this.scale - 0.02;
                this.$store.vbpMinimap.setScale(newScale);
            },

            updateElements: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var elements = canvas.querySelectorAll('.vbp-element');
                var canvasRect = canvas.getBoundingClientRect();
                var scale = this.scale;
                var store = Alpine.store('vbp');
                var selectedIds = store && store.selection ? store.selection.elementIds : [];

                this.canvasHeight = canvas.scrollHeight * scale;
                this.elements = [];

                var self = this;
                elements.forEach(function(el) {
                    var rect = el.getBoundingClientRect();
                    var top = (rect.top - canvasRect.top + canvas.scrollTop) * scale;
                    var height = Math.max(rect.height * scale, 3);
                    var elementId = el.dataset.elementId;
                    var elementType = el.dataset.elementType || 'element';

                    self.elements.push({
                        id: elementId,
                        top: top,
                        height: height,
                        selected: selectedIds.indexOf(elementId) !== -1,
                        type: elementType,
                        name: self.getElementLabel(elementType)
                    });
                });
            },

            updateViewport: function() {
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvasWrapper || !canvas) return;

                var scale = this.scale;

                this.viewportTop = canvasWrapper.scrollTop * scale;
                this.viewportHeight = Math.min(
                    canvasWrapper.clientHeight * scale,
                    this.canvasHeight - this.viewportTop
                );
            },

            getElementColor: function(type) {
                var colors = {
                    // Secciones principales
                    'hero': '#ec4899',
                    'features': '#10b981',
                    'testimonials': '#f59e0b',
                    'pricing': '#ef4444',
                    'cta': '#06b6d4',
                    'contact': '#6366f1',
                    'faq': '#8b5cf6',
                    'team': '#14b8a6',
                    'stats': '#f97316',
                    'gallery': '#a855f7',
                    // Elementos básicos
                    'text': '#64748b',
                    'heading': '#475569',
                    'image': '#8b5cf6',
                    'button': '#3b82f6',
                    'icon': '#06b6d4',
                    'divider': '#94a3b8',
                    'spacer': '#cbd5e1',
                    // Layout
                    'container': '#334155',
                    'columns': '#475569',
                    'row': '#475569',
                    'grid': '#475569',
                    // Formularios
                    'form': '#22c55e',
                    'newsletter': '#0ea5e9',
                    // Interactivos
                    'countdown': '#f43f5e',
                    'accordion': '#7c3aed',
                    'tabs': '#2563eb',
                    // Media
                    'video-embed': '#dc2626',
                    'map': '#059669',
                    // Default
                    'default': '#64748b'
                };
                return colors[type] || colors['default'];
            },

            getElementLabel: function(type) {
                return this.elementLabels[type] || this.elementLabels['default'];
            },

            scrollToElement: function(elementId) {
                var element = document.querySelector('[data-element-id="' + elementId + '"]');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Seleccionar el elemento
                    var store = Alpine.store('vbp');
                    if (store && store.setSelection) {
                        store.setSelection([elementId]);
                    }
                }
            },

            handleMinimapClick: function(event) {
                // No hacer nada si se hizo click en un elemento
                if (event.target.classList.contains('vbp-minimap-element')) return;

                var minimap = this.$refs.minimap;
                if (!minimap) return;

                var rect = minimap.getBoundingClientRect();
                var clickY = event.clientY - rect.top;
                var scale = this.scale;

                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (canvasWrapper) {
                    var scrollTop = clickY / scale - canvasWrapper.clientHeight / 2;
                    canvasWrapper.scrollTo({
                        top: Math.max(0, scrollTop),
                        behavior: 'smooth'
                    });
                }
            },

            startDrag: function(event) {
                // Solo iniciar drag con click izquierdo
                if (event.button !== 0) return;
                this.isDragging = true;
                this.handleDrag(event);
            },

            handleDrag: function(event) {
                if (!this.isDragging) return;

                var minimap = this.$refs.minimap;
                if (!minimap) return;

                var rect = minimap.getBoundingClientRect();
                var dragY = event.clientY - rect.top;
                var scale = this.scale;

                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (canvasWrapper) {
                    var scrollTop = dragY / scale - canvasWrapper.clientHeight / 2;
                    canvasWrapper.scrollTop = Math.max(0, scrollTop);
                }
            },

            endDrag: function() {
                this.isDragging = false;
            },

            // Tooltip functions
            showTooltip: function(event, element) {
                var tooltip = document.querySelector('.vbp-minimap-tooltip');
                if (!tooltip) return;

                var rect = event.target.getBoundingClientRect();

                // Posicionar a la izquierda del elemento
                tooltip.style.left = (rect.left - tooltip.offsetWidth - 8) + 'px';
                tooltip.style.top = rect.top + 'px';
                tooltip.textContent = element.name;
                tooltip.classList.add('visible');
            },

            hideTooltip: function() {
                var tooltip = document.querySelector('.vbp-minimap-tooltip');
                if (tooltip) {
                    tooltip.classList.remove('visible');
                }
            }
        };
    });
});

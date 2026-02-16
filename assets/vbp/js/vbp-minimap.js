/**
 * Visual Builder Pro - Mini Mapa
 * Navegación rápida para documentos largos
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.store('vbpMinimap', {
        isVisible: true,
        width: 120,
        scale: 0.1,
        viewportRect: { top: 0, height: 100 },

        toggle: function() {
            this.isVisible = !this.isVisible;
            localStorage.setItem('vbp_minimap_visible', this.isVisible);
        },

        init: function() {
            var saved = localStorage.getItem('vbp_minimap_visible');
            if (saved !== null) {
                this.isVisible = saved === 'true';
            }
        }
    });

    Alpine.data('vbpMinimap', function() {
        return {
            isDragging: false,
            elements: [],
            canvasHeight: 0,
            viewportTop: 0,
            viewportHeight: 0,

            init: function() {
                var self = this;

                // Actualizar mini mapa cuando cambian los elementos
                this.$watch('$store.vbp.elements', function() {
                    self.updateElements();
                });

                // Actualizar viewport en scroll
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (canvasWrapper) {
                    canvasWrapper.addEventListener('scroll', function() {
                        self.updateViewport();
                    });
                }

                // Inicializar
                this.$nextTick(function() {
                    self.updateElements();
                    self.updateViewport();
                });

                // Actualizar cada 500ms para capturar cambios
                setInterval(function() {
                    self.updateElements();
                    self.updateViewport();
                }, 500);
            },

            updateElements: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var elements = canvas.querySelectorAll('.vbp-element');
                var canvasRect = canvas.getBoundingClientRect();
                var scale = this.$store.vbpMinimap.scale;
                var store = Alpine.store('vbp');
                var selectedIds = store.selection ? store.selection.elementIds : [];

                this.canvasHeight = canvas.scrollHeight * scale;
                this.elements = [];

                var self = this;
                elements.forEach(function(el) {
                    var rect = el.getBoundingClientRect();
                    var top = (rect.top - canvasRect.top + canvas.scrollTop) * scale;
                    var height = Math.max(rect.height * scale, 2);
                    var elementId = el.dataset.elementId;

                    self.elements.push({
                        id: elementId,
                        top: top,
                        height: height,
                        selected: selectedIds.indexOf(elementId) !== -1,
                        type: el.dataset.elementType || 'element'
                    });
                });
            },

            updateViewport: function() {
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvasWrapper || !canvas) return;

                var scale = this.$store.vbpMinimap.scale;

                this.viewportTop = canvasWrapper.scrollTop * scale;
                this.viewportHeight = Math.min(
                    canvasWrapper.clientHeight * scale,
                    this.canvasHeight - this.viewportTop
                );
            },

            getElementColor: function(type) {
                var colors = {
                    'text': '#94a3b8',
                    'heading': '#64748b',
                    'image': '#8b5cf6',
                    'button': '#3b82f6',
                    'hero': '#ec4899',
                    'features': '#10b981',
                    'testimonials': '#f59e0b',
                    'pricing': '#ef4444',
                    'cta': '#06b6d4',
                    'contact': '#6366f1',
                    'container': '#d1d5db',
                    'columns': '#d1d5db',
                    'default': '#cbd5e1'
                };
                return colors[type] || colors['default'];
            },

            scrollToElement: function(elementId) {
                var element = document.querySelector('[data-element-id="' + elementId + '"]');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    Alpine.store('vbp').setSelection([elementId]);
                }
            },

            handleMinimapClick: function(event) {
                var minimap = this.$refs.minimap;
                if (!minimap) return;

                var rect = minimap.getBoundingClientRect();
                var clickY = event.clientY - rect.top;
                var scale = this.$store.vbpMinimap.scale;

                // Calcular posición en el canvas
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
                this.isDragging = true;
                this.handleDrag(event);
            },

            handleDrag: function(event) {
                if (!this.isDragging) return;

                var minimap = this.$refs.minimap;
                if (!minimap) return;

                var rect = minimap.getBoundingClientRect();
                var dragY = event.clientY - rect.top;
                var scale = this.$store.vbpMinimap.scale;

                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (canvasWrapper) {
                    var scrollTop = dragY / scale - canvasWrapper.clientHeight / 2;
                    canvasWrapper.scrollTop = Math.max(0, scrollTop);
                }
            },

            endDrag: function() {
                this.isDragging = false;
            }
        };
    });
});

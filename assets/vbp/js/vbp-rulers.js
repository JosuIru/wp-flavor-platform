/**
 * Visual Builder Pro - Rulers & Guides
 * Sistema de reglas y guías arrastrables
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.data('vbpRulers', function() {
        return {
            /**
             * Guías activas
             */
            guides: {
                horizontal: [],
                vertical: []
            },

            /**
             * Estado de arrastre
             */
            isDragging: false,
            dragType: null,
            dragGuideIndex: null,
            tempGuide: null,

            /**
             * Configuración
             */
            snapThreshold: 5,
            guideColor: '#3b82f6',

            /**
             * Inicialización
             */
            init: function() {
                var self = this;

                this.$nextTick(function() {
                    self.draw();
                    self.setupEventListeners();
                });

                // Redibujar en resize
                window.addEventListener('resize', function() {
                    self.draw();
                });

                // Observar cambios de zoom
                Alpine.effect(function() {
                    var zoom = Alpine.store('vbp').zoom;
                    self.draw();
                });
            },

            /**
             * Configurar eventos de ratón
             */
            setupEventListeners: function() {
                var self = this;
                var rulerH = document.getElementById('vbp-ruler-h');
                var rulerV = document.getElementById('vbp-ruler-v');

                if (rulerH) {
                    rulerH.addEventListener('mousedown', function(e) {
                        self.startDragNewGuide(e, 'horizontal');
                    });
                }

                if (rulerV) {
                    rulerV.addEventListener('mousedown', function(e) {
                        self.startDragNewGuide(e, 'vertical');
                    });
                }

                document.addEventListener('mousemove', function(e) {
                    if (self.isDragging) {
                        self.handleDragMove(e);
                    }
                });

                document.addEventListener('mouseup', function(e) {
                    if (self.isDragging) {
                        self.handleDragEnd(e);
                    }
                });
            },

            /**
             * Iniciar arrastre de nueva guía
             */
            startDragNewGuide: function(event, type) {
                this.isDragging = true;
                this.dragType = type;
                this.dragGuideIndex = -1;

                var position = this.getPositionFromEvent(event, type);
                this.tempGuide = { type: type, position: position };

                document.body.style.cursor = type === 'horizontal' ? 'row-resize' : 'col-resize';
                event.preventDefault();
            },

            /**
             * Manejar movimiento de arrastre
             */
            handleDragMove: function(event) {
                if (!this.tempGuide) return;

                var position = this.getPositionFromEvent(event, this.tempGuide.type);
                this.tempGuide.position = position;
                this.drawTempGuide();
            },

            /**
             * Finalizar arrastre
             */
            handleDragEnd: function(event) {
                if (this.tempGuide) {
                    var canvas = document.querySelector('.vbp-canvas');
                    var canvasRect = canvas ? canvas.getBoundingClientRect() : null;

                    if (canvasRect) {
                        var inCanvas = false;
                        if (this.tempGuide.type === 'horizontal') {
                            inCanvas = event.clientY >= canvasRect.top && event.clientY <= canvasRect.bottom;
                        } else {
                            inCanvas = event.clientX >= canvasRect.left && event.clientX <= canvasRect.right;
                        }

                        if (inCanvas && this.tempGuide.position > 0) {
                            this.addGuide(this.tempGuide.type, this.tempGuide.position);
                        }
                    }
                }

                this.isDragging = false;
                this.dragType = null;
                this.dragGuideIndex = null;
                this.tempGuide = null;
                document.body.style.cursor = '';
                this.clearTempGuide();
            },

            /**
             * Obtener posición desde evento
             */
            getPositionFromEvent: function(event, type) {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return 0;

                var rect = canvas.getBoundingClientRect();
                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                if (type === 'horizontal') {
                    return Math.round((event.clientY - rect.top) / zoom);
                } else {
                    return Math.round((event.clientX - rect.left) / zoom);
                }
            },

            /**
             * Añadir guía
             */
            addGuide: function(type, position) {
                this.guides[type].push({
                    id: 'guide_' + Date.now(),
                    position: position
                });
                this.drawGuides();
            },

            /**
             * Eliminar guía
             */
            removeGuide: function(type, index) {
                this.guides[type].splice(index, 1);
                this.drawGuides();
            },

            /**
             * Limpiar todas las guías
             */
            clearGuides: function() {
                this.guides.horizontal = [];
                this.guides.vertical = [];
                this.drawGuides();
            },

            /**
             * Dibujar reglas
             */
            draw: function() {
                var rulerH = document.getElementById('vbp-ruler-h');
                var rulerV = document.getElementById('vbp-ruler-v');

                if (rulerH) this.drawRuler(rulerH, 'horizontal');
                if (rulerV) this.drawRuler(rulerV, 'vertical');
            },

            /**
             * Dibujar una regla
             */
            drawRuler: function(canvas, direction) {
                var ctx = canvas.getContext('2d');
                var isHorizontal = direction === 'horizontal';
                var length = isHorizontal ? canvas.width : canvas.height;

                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                // Fondo
                ctx.fillStyle = '#1a1a1a';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Marcas
                var step = 10;
                var majorStep = 100;

                ctx.fillStyle = '#444';

                for (var i = 0; i < length; i += step) {
                    var realPos = i / zoom;
                    var isMajor = Math.round(realPos) % majorStep === 0;
                    var isMid = Math.round(realPos) % 50 === 0;

                    var tickHeight = isMajor ? 14 : (isMid ? 10 : 5);

                    if (isHorizontal) {
                        ctx.fillRect(i, 20 - tickHeight, 1, tickHeight);
                    } else {
                        ctx.fillRect(20 - tickHeight, i, tickHeight, 1);
                    }

                    // Números
                    if (isMajor && realPos > 0) {
                        ctx.fillStyle = '#888';
                        ctx.font = '9px system-ui, sans-serif';

                        var label = Math.round(realPos).toString();

                        if (isHorizontal) {
                            ctx.fillText(label, i + 2, 10);
                        } else {
                            ctx.save();
                            ctx.translate(10, i + 2);
                            ctx.rotate(-Math.PI / 2);
                            ctx.fillText(label, 0, 0);
                            ctx.restore();
                        }

                        ctx.fillStyle = '#444';
                    }
                }

                // Marcadores de guías en la regla
                this.drawGuideMarkers(canvas, direction);
            },

            /**
             * Dibujar marcadores de guías en la regla
             */
            drawGuideMarkers: function(canvas, direction) {
                var ctx = canvas.getContext('2d');
                var guides = direction === 'horizontal' ? this.guides.horizontal : this.guides.vertical;
                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                ctx.fillStyle = this.guideColor;

                for (var i = 0; i < guides.length; i++) {
                    var pos = guides[i].position * zoom;

                    if (direction === 'horizontal') {
                        ctx.beginPath();
                        ctx.moveTo(pos - 4, 20);
                        ctx.lineTo(pos + 4, 20);
                        ctx.lineTo(pos, 14);
                        ctx.closePath();
                        ctx.fill();
                    } else {
                        ctx.beginPath();
                        ctx.moveTo(20, pos - 4);
                        ctx.lineTo(20, pos + 4);
                        ctx.lineTo(14, pos);
                        ctx.closePath();
                        ctx.fill();
                    }
                }
            },

            /**
             * Dibujar guías en el canvas
             */
            drawGuides: function() {
                var container = document.querySelector('.vbp-guides-container');

                if (!container) {
                    container = document.createElement('div');
                    container.className = 'vbp-guides-container';
                    container.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 1000;';

                    var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                    if (canvasWrapper) {
                        canvasWrapper.appendChild(container);
                    }
                }

                container.innerHTML = '';
                var self = this;

                // Guías horizontales
                this.guides.horizontal.forEach(function(guide, index) {
                    var guideEl = self.createGuideElement('horizontal', guide, index);
                    container.appendChild(guideEl);
                });

                // Guías verticales
                this.guides.vertical.forEach(function(guide, index) {
                    var guideEl = self.createGuideElement('vertical', guide, index);
                    container.appendChild(guideEl);
                });
            },

            /**
             * Crear elemento de guía
             */
            createGuideElement: function(type, guide, index) {
                var el = document.createElement('div');
                el.className = 'vbp-guide vbp-guide-' + type;
                el.dataset.guideId = guide.id;

                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                if (type === 'horizontal') {
                    el.style.cssText = 'position: absolute; left: 0; right: 0; height: 1px; background: ' + this.guideColor + '; top: ' + (guide.position * zoom) + 'px; pointer-events: auto; cursor: row-resize;';
                } else {
                    el.style.cssText = 'position: absolute; top: 0; bottom: 0; width: 1px; background: ' + this.guideColor + '; left: ' + (guide.position * zoom) + 'px; pointer-events: auto; cursor: col-resize;';
                }

                var self = this;

                // Doble click para eliminar
                el.addEventListener('dblclick', function() {
                    self.removeGuide(type, index);
                });

                return el;
            },

            /**
             * Dibujar guía temporal durante arrastre
             */
            drawTempGuide: function() {
                var existing = document.getElementById('vbp-temp-guide');
                if (existing) existing.remove();

                if (!this.tempGuide) return;

                var el = document.createElement('div');
                el.id = 'vbp-temp-guide';

                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                if (this.tempGuide.type === 'horizontal') {
                    el.style.cssText = 'position: fixed; left: 0; right: 0; height: 1px; background: ' + this.guideColor + '; opacity: 0.5; top: ' + (this.tempGuide.position * zoom + 60) + 'px; z-index: 10000; pointer-events: none;';
                } else {
                    el.style.cssText = 'position: fixed; top: 0; bottom: 0; width: 1px; background: ' + this.guideColor + '; opacity: 0.5; left: ' + (this.tempGuide.position * zoom + 280) + 'px; z-index: 10000; pointer-events: none;';
                }

                document.body.appendChild(el);
            },

            /**
             * Limpiar guía temporal
             */
            clearTempGuide: function() {
                var existing = document.getElementById('vbp-temp-guide');
                if (existing) existing.remove();
            },

            /**
             * Snap a guías cercanas
             */
            snapToGuides: function(position, type) {
                var guides = this.guides[type];

                for (var i = 0; i < guides.length; i++) {
                    if (Math.abs(position - guides[i].position) < this.snapThreshold) {
                        return guides[i].position;
                    }
                }

                return position;
            },

            /**
             * Obtener todas las guías
             */
            getAllGuides: function() {
                return this.guides;
            },

            /**
             * Establecer guías desde datos guardados
             */
            setGuides: function(data) {
                if (data.horizontal) {
                    this.guides.horizontal = data.horizontal;
                }
                if (data.vertical) {
                    this.guides.vertical = data.vertical;
                }
                this.drawGuides();
            }
        };
    });
});

// Exponer globalmente para acceso
window.vbpRulers = {
    getGuides: function() {
        var component = document.querySelector('[x-data*="vbpRulers"]');
        if (component && component.__x) {
            return component.__x.$data.getAllGuides();
        }
        return { horizontal: [], vertical: [] };
    },

    clearGuides: function() {
        var component = document.querySelector('[x-data*="vbpRulers"]');
        if (component && component.__x) {
            component.__x.$data.clearGuides();
        }
    }
};

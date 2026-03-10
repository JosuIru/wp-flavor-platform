/**
 * Visual Builder Pro - Canvas
 * Gestión del canvas principal y drag & drop
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.data('vbpCanvas', function() {
        return {
            /**
             * Instancias de Sortable
             */
            canvasSortable: null,
            blocksSortable: null,

            /**
             * Estado del canvas
             */
            isDragging: false,
            draggedType: null,

            /**
             * Inicialización
             */
            init: function() {
                var self = this;
                this.$nextTick(function() {
                    self.initCanvasSortable();
                    self.initBlocksPanelDrag();
                    self.initDropZone();
                    self.initClickHandler();
                    self.initWheelZoom();
                });
            },

            /**
             * Zoom con rueda del ratón (Ctrl+Wheel)
             */
            initWheelZoom: function() {
                var canvas = document.querySelector('.vbp-canvas-wrapper');
                if (!canvas) return;

                var self = this;

                canvas.addEventListener('wheel', function(e) {
                    // Solo si se mantiene Ctrl o Cmd presionado
                    if (!e.ctrlKey && !e.metaKey) return;

                    e.preventDefault();
                    e.stopPropagation();

                    var store = Alpine.store('vbp');
                    var currentZoom = store.zoom || 100;
                    var delta = e.deltaY > 0 ? -10 : 10;
                    var newZoom = Math.min(200, Math.max(25, currentZoom + delta));

                    if (newZoom !== currentZoom) {
                        store.zoom = newZoom;
                        self.showZoomIndicator(newZoom);
                    }
                }, { passive: false });
            },

            /**
             * Mostrar indicador visual de zoom
             */
            showZoomIndicator: function(zoomLevel) {
                var existingIndicator = document.getElementById('vbp-zoom-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                var indicator = document.createElement('div');
                indicator.id = 'vbp-zoom-indicator';
                indicator.innerHTML = '🔍 ' + zoomLevel + '%';
                indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: rgba(30, 30, 46, 0.9); color: #cdd6f4; border-radius: 6px; font-size: 14px; font-weight: 500; z-index: 10000; pointer-events: none; transition: opacity 0.3s;';
                document.body.appendChild(indicator);

                setTimeout(function() {
                    indicator.style.opacity = '0';
                    setTimeout(function() {
                        indicator.remove();
                    }, 300);
                }, 1000);
            },

            /**
             * Inicializar Sortable en el canvas (reordenar elementos)
             */
            initCanvasSortable: function() {
                var self = this;
                var canvas = this.$refs.canvas || document.querySelector('.vbp-canvas');

                if (!canvas || typeof Sortable === 'undefined') {
                    console.warn('VBP Canvas: Sortable no disponible');
                    return;
                }

                this.canvasSortable = new Sortable(canvas, {
                    animation: 150,
                    handle: '.vbp-element-handle',
                    draggable: '.vbp-element',
                    ghostClass: 'vbp-element-ghost',
                    chosenClass: 'vbp-element-chosen',
                    dragClass: 'vbp-element-drag',
                    filter: '.vbp-element-locked',
                    forceFallback: true,

                    onStart: function(evt) {
                        self.isDragging = true;
                        document.body.classList.add('vbp-dragging');

                        // Iniciar guías inteligentes
                        var elementId = evt.item.dataset.elementId;
                        window.vbpCanvasUtils.smartGuides.startDrag(elementId);

                        // Alt+Drag para duplicar
                        if (evt.originalEvent && evt.originalEvent.altKey) {
                            document.body.classList.add('vbp-dragging-alt');
                            window.vbpCanvasUtils.altDragDuplicate.start(elementId, evt.originalEvent);
                        }
                    },

                    onMove: function(evt, originalEvent) {
                        // Aplicar snap a guías durante el arrastre
                        self.applySnapDuringDrag(originalEvent);

                        // Verificar guías inteligentes
                        if (evt.dragged) {
                            var rect = evt.dragged.getBoundingClientRect();
                            window.vbpCanvasUtils.smartGuides.checkSnap(rect);
                        }
                    },

                    onEnd: function(evt) {
                        self.isDragging = false;
                        document.body.classList.remove('vbp-dragging', 'vbp-dragging-alt');
                        window.vbpCanvasUtils.hideSnapLines();
                        window.vbpCanvasUtils.smartGuides.endDrag();
                        window.vbpCanvasUtils.altDragDuplicate.end();

                        if (evt.oldIndex !== evt.newIndex) {
                            Alpine.store('vbp').moveElement(evt.oldIndex, evt.newIndex);
                        }
                    }
                });
            },

            /**
             * Aplicar snap a guías durante el arrastre
             */
            applySnapDuringDrag: function(event) {
                if (!event) return;

                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var rect = canvas.getBoundingClientRect();
                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                // Calcular posición relativa al canvas
                var posX = (event.clientX - rect.left) / zoom;
                var posY = (event.clientY - rect.top) / zoom;

                // Verificar snap horizontal (posición Y)
                var snapH = window.vbpCanvasUtils.snapToGuides(posY, 'horizontal');
                if (snapH.snapped) {
                    window.vbpCanvasUtils.showSnapLine(snapH.position, 'horizontal');
                }

                // Verificar snap vertical (posición X)
                var snapV = window.vbpCanvasUtils.snapToGuides(posX, 'vertical');
                if (snapV.snapped) {
                    window.vbpCanvasUtils.showSnapLine(snapV.position, 'vertical');
                }

                // Si no hay snap, ocultar líneas
                if (!snapH.snapped && !snapV.snapped) {
                    window.vbpCanvasUtils.hideSnapLines();
                }
            },

            /**
             * Inicializar drag desde panel de bloques
             */
            initBlocksPanelDrag: function() {
                var self = this;
                var blocksPanel = document.querySelector('.vbp-blocks-list');

                if (!blocksPanel || typeof Sortable === 'undefined') return;

                // Crear sortable para el panel de bloques (solo para arrastrar)
                this.blocksSortable = new Sortable(blocksPanel, {
                    group: {
                        name: 'vbp-blocks',
                        pull: 'clone',
                        put: false
                    },
                    sort: false,
                    animation: 150,
                    draggable: '.vbp-block-item',
                    ghostClass: 'vbp-block-ghost',
                    chosenClass: 'vbp-block-chosen',
                    forceFallback: true,

                    onStart: function(evt) {
                        self.isDragging = true;
                        self.draggedType = evt.item.dataset.blockType;
                        document.body.classList.add('vbp-dragging', 'vbp-dragging-new');
                    },

                    onEnd: function(evt) {
                        self.isDragging = false;
                        self.draggedType = null;
                        document.body.classList.remove('vbp-dragging', 'vbp-dragging-new');

                        // Remover el clon del panel de bloques si quedó ahí
                        if (evt.item.closest('.vbp-blocks-list')) {
                            // El elemento fue cancelado, no hacer nada
                        }
                    }
                });
            },

            /**
             * Inicializar zona de drop en el canvas
             */
            initDropZone: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');

                if (!canvas) return;

                // Hacer el canvas una zona de drop válida para bloques nuevos
                if (typeof Sortable !== 'undefined') {
                    new Sortable(canvas, {
                        group: {
                            name: 'vbp-canvas',
                            put: ['vbp-blocks']
                        },
                        animation: 150,
                        draggable: '.vbp-element',
                        ghostClass: 'vbp-element-ghost',

                        onMove: function(evt, originalEvent) {
                            // Aplicar snap a guías durante el arrastre de nuevo bloque
                            self.applySnapDuringDrag(originalEvent);
                        },

                        onAdd: function(evt) {
                            // Ocultar líneas de snap
                            window.vbpCanvasUtils.hideSnapLines();

                            // Obtener el tipo de bloque
                            var blockType = evt.item.dataset.blockType;

                            // Remover el elemento clonado
                            evt.item.remove();

                            // Añadir el nuevo elemento al store
                            if (blockType) {
                                var store = Alpine.store('vbp');
                                var newElement = store.addElement(blockType, evt.newIndex);

                                // Hacer scroll al nuevo elemento
                                self.$nextTick(function() {
                                    var newElementDom = document.querySelector('[data-element-id="' + newElement.id + '"]');
                                    if (newElementDom) {
                                        newElementDom.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    }
                                });
                            }
                        }
                    });
                }

                // Fallback: drag & drop nativo para navegadores sin Sortable
                canvas.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';

                    // Resaltar contenedor si estamos sobre uno
                    var dropzone = e.target.closest('.vbp-container-dropzone, .vbp-column-dropzone');
                    canvas.querySelectorAll('.vbp-drop-highlight').forEach(function(el) {
                        el.classList.remove('vbp-drop-highlight');
                    });
                    if (dropzone) {
                        dropzone.classList.add('vbp-drop-highlight');
                    } else {
                        canvas.classList.add('vbp-drop-active');
                    }
                });

                canvas.addEventListener('dragleave', function(e) {
                    canvas.classList.remove('vbp-drop-active');
                    var dropzone = e.target.closest('.vbp-container-dropzone, .vbp-column-dropzone');
                    if (dropzone) {
                        dropzone.classList.remove('vbp-drop-highlight');
                    }
                });

                canvas.addEventListener('drop', function(e) {
                    e.preventDefault();
                    canvas.classList.remove('vbp-drop-active');
                    canvas.querySelectorAll('.vbp-drop-highlight').forEach(function(el) {
                        el.classList.remove('vbp-drop-highlight');
                    });

                    var blockType = e.dataTransfer.getData('text/vbp-block-type');
                    if (!blockType) return;

                    var store = Alpine.store('vbp');

                    // Verificar si se soltó en un contenedor
                    var columnDropzone = e.target.closest('.vbp-column-dropzone');
                    var containerDropzone = e.target.closest('.vbp-container-dropzone');

                    if (columnDropzone) {
                        // Soltar en una columna específica
                        var containerId = columnDropzone.dataset.containerId;
                        var columnIndex = parseInt(columnDropzone.dataset.columnIndex) || 0;
                        store.addElementToContainer(blockType, containerId, columnIndex);
                    } else if (containerDropzone) {
                        // Soltar en un contenedor (grid, container)
                        var containerId = containerDropzone.dataset.containerId;
                        store.addElementToContainer(blockType, containerId, 0);
                    } else {
                        // Soltar en el canvas principal
                        store.addElement(blockType);
                    }
                });

                // Inicializar drop en contenedores existentes
                self.initContainerDropZones();
            },

            /**
             * Inicializar zonas de drop para contenedores
             */
            initContainerDropZones: function() {
                var self = this;

                // Observer para detectar nuevos contenedores añadidos al DOM
                if (!self.containerObserver) {
                    self.containerObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) {
                                    var dropzones = node.querySelectorAll ? node.querySelectorAll('.vbp-container-dropzone, .vbp-column-dropzone') : [];
                                    dropzones.forEach(function(zone) {
                                        self.setupDropzoneEvents(zone);
                                    });
                                }
                            });
                        });
                    });

                    var canvas = document.querySelector('.vbp-canvas');
                    if (canvas) {
                        self.containerObserver.observe(canvas, { childList: true, subtree: true });
                    }
                }
            },

            setupDropzoneEvents: function(zone) {
                // Los eventos ya se manejan con delegación en el canvas
            },

            /**
             * Inicializar manejador de clicks
             */
            initClickHandler: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');

                if (!canvas) return;

                canvas.addEventListener('click', function(e) {
                    var element = e.target.closest('.vbp-element');
                    var store = Alpine.store('vbp');

                    if (element) {
                        var elementId = element.dataset.elementId;

                        // Alt+Click para duplicar rápido
                        if (e.altKey) {
                            e.preventDefault();
                            e.stopPropagation();
                            var newId = store.duplicateElement(elementId);
                            if (newId) {
                                store.setSelection([newId]);
                                window.vbpCanvasUtils.altDragDuplicate.showDuplicateIndicator();
                            }
                            return;
                        }

                        if (e.shiftKey || e.ctrlKey || e.metaKey) {
                            // Multi-selección
                            store.toggleSelection(elementId);
                        } else {
                            // Selección simple
                            store.setSelection([elementId]);
                        }

                        e.stopPropagation();
                    } else if (e.target.closest('.vbp-canvas') && !e.target.closest('.vbp-element')) {
                        // Click en canvas vacío - deseleccionar
                        store.clearSelection();
                    }
                });

                // Doble click para edición rápida
                canvas.addEventListener('dblclick', function(e) {
                    var editableContent = e.target.closest('[contenteditable]');
                    if (editableContent) {
                        // Ya es editable, seleccionar todo el texto
                        var selection = window.getSelection();
                        var range = document.createRange();
                        range.selectNodeContents(editableContent);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                });

                // Sincronizar contenido editable con el store al perder el foco
                canvas.addEventListener('blur', function(e) {
                    var editableContent = e.target;
                    if (!editableContent.hasAttribute('contenteditable')) return;

                    var field = editableContent.dataset.field;
                    if (!field) return;

                    var element = editableContent.closest('.vbp-element');
                    if (!element) return;

                    var elementId = element.dataset.elementId;
                    var store = Alpine.store('vbp');
                    var storeElement = store.getElement(elementId);

                    if (storeElement) {
                        var newContent = editableContent.innerHTML;
                        // Usar textContent para campos simples, innerHTML para rich text
                        if (field === 'text' || field === 'titulo' || field === 'subtitulo' ||
                            field === 'descripcion' || field === 'boton_texto') {
                            newContent = editableContent.textContent;
                        }

                        if (storeElement.data[field] !== newContent) {
                            var data = JSON.parse(JSON.stringify(storeElement.data || {}));
                            data[field] = newContent;
                            store.updateElement(elementId, { data: data });
                        }
                    }
                }, true); // Usar captura para detectar blur en elementos anidados

                // También sincronizar en input para feedback en tiempo real
                canvas.addEventListener('input', function(e) {
                    var editableContent = e.target;
                    if (!editableContent.hasAttribute('contenteditable')) return;

                    var field = editableContent.dataset.field;
                    if (!field) return;

                    var element = editableContent.closest('.vbp-element');
                    if (!element) return;

                    var elementId = element.dataset.elementId;
                    var store = Alpine.store('vbp');
                    var storeElement = store.getElement(elementId);

                    if (storeElement) {
                        var newContent = editableContent.innerHTML;
                        if (field === 'text' || field === 'titulo' || field === 'subtitulo' ||
                            field === 'descripcion' || field === 'boton_texto') {
                            newContent = editableContent.textContent;
                        }

                        // Debounce para evitar demasiadas actualizaciones
                        clearTimeout(editableContent._vbpInputTimeout);
                        editableContent._vbpInputTimeout = setTimeout(function() {
                            if (storeElement.data[field] !== newContent) {
                                var data = JSON.parse(JSON.stringify(storeElement.data || {}));
                                data[field] = newContent;
                                store.updateElement(elementId, { data: data });
                            }
                        }, 200);
                    }
                }, true);
            },

            /**
             * Obtener posición del cursor relativa al canvas
             */
            getCursorPosition: function(event) {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return { x: 0, y: 0 };

                var rect = canvas.getBoundingClientRect();
                var store = Alpine.store('vbp');
                var zoom = store.zoom / 100;

                return {
                    x: (event.clientX - rect.left) / zoom,
                    y: (event.clientY - rect.top) / zoom
                };
            },

            /**
             * Calcular índice de inserción basado en posición Y
             */
            getInsertIndex: function(y) {
                var store = Alpine.store('vbp');
                var elements = document.querySelectorAll('.vbp-canvas .vbp-element');
                var index = store.elements.length;

                for (var i = 0; i < elements.length; i++) {
                    var rect = elements[i].getBoundingClientRect();
                    var midY = rect.top + rect.height / 2;

                    if (y < midY) {
                        index = i;
                        break;
                    }
                }

                return index;
            },

            /**
             * Añadir elemento por tipo
             */
            addElement: function(type) {
                var store = Alpine.store('vbp');
                return store.addElement(type);
            },

            /**
             * Eliminar elemento seleccionado
             */
            removeSelected: function() {
                var store = Alpine.store('vbp');

                store.selection.elementIds.forEach(function(id) {
                    store.removeElement(id);
                });
            },

            /**
             * Duplicar elemento seleccionado
             */
            duplicateSelected: function() {
                var store = Alpine.store('vbp');

                store.selection.elementIds.forEach(function(id) {
                    store.duplicateElement(id);
                });
            },

            /**
             * Limpiar al destruir componente
             */
            destroy: function() {
                if (this.canvasSortable) {
                    this.canvasSortable.destroy();
                }
                if (this.blocksSortable) {
                    this.blocksSortable.destroy();
                }
            }
        };
    });
});

// Exponer funciones globales para drag & drop nativo
window.vbpCanvasUtils = {
    /**
     * Iniciar drag de bloque (para drag nativo)
     */
    startBlockDrag: function(event, blockType) {
        event.dataTransfer.setData('text/vbp-block-type', blockType);
        event.dataTransfer.effectAllowed = 'copy';
        document.body.classList.add('vbp-dragging', 'vbp-dragging-new');
    },

    /**
     * Finalizar drag de bloque
     */
    endBlockDrag: function() {
        document.body.classList.remove('vbp-dragging', 'vbp-dragging-new');
    },

    /**
     * Obtener instancia del componente de rulers
     */
    getRulersComponent: function() {
        var rulersEl = document.querySelector('[x-data*="vbpRulers"]');
        if (rulersEl && rulersEl.__x) {
            return rulersEl.__x.$data;
        }
        return null;
    },

    /**
     * Aplicar snap a guías
     * @param {number} position - Posición actual
     * @param {string} type - 'horizontal' o 'vertical'
     * @returns {object} - { snapped: boolean, position: number }
     */
    snapToGuides: function(position, type) {
        var rulers = this.getRulersComponent();
        if (!rulers) {
            return { snapped: false, position: position };
        }

        var snappedPosition = rulers.snapToGuides(position, type);
        return {
            snapped: snappedPosition !== position,
            position: snappedPosition
        };
    },

    /**
     * Mostrar línea de snap temporal
     */
    showSnapLine: function(position, type) {
        var existingLine = document.getElementById('vbp-snap-line');
        if (existingLine) existingLine.remove();

        var line = document.createElement('div');
        line.id = 'vbp-snap-line';
        line.className = 'vbp-snap-line vbp-snap-line--' + type;

        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        var rect = canvas.getBoundingClientRect();
        var store = Alpine.store('vbp');
        var zoom = store.zoom / 100;

        if (type === 'horizontal') {
            line.style.cssText = 'position: absolute; left: 0; right: 0; height: 2px; background: #10b981; top: ' + (position * zoom) + 'px; pointer-events: none; z-index: 1001; box-shadow: 0 0 4px rgba(16,185,129,0.5);';
        } else {
            line.style.cssText = 'position: absolute; top: 0; bottom: 0; width: 2px; background: #10b981; left: ' + (position * zoom) + 'px; pointer-events: none; z-index: 1001; box-shadow: 0 0 4px rgba(16,185,129,0.5);';
        }

        var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
        if (canvasWrapper) {
            canvasWrapper.appendChild(line);
        }
    },

    /**
     * Ocultar líneas de snap
     */
    hideSnapLines: function() {
        var lines = document.querySelectorAll('.vbp-snap-line');
        lines.forEach(function(line) { line.remove(); });
    },

    /**
     * Guías de alineación inteligentes - detecta bordes de otros elementos
     */
    smartGuides: {
        tolerance: 5, // Pixels de tolerancia para snap
        guides: [],   // Guías calculadas de otros elementos
        currentDragId: null,

        /**
         * Iniciar guías para un elemento siendo arrastrado
         */
        startDrag: function(elementId) {
            this.currentDragId = elementId;
            this.calculateGuides(elementId);
        },

        /**
         * Calcular guías basadas en todos los elementos excepto el arrastrado
         */
        calculateGuides: function(excludeId) {
            var self = this;
            this.guides = [];

            var elements = document.querySelectorAll('.vbp-canvas .vbp-element');
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            // Guías del canvas (bordes y centro)
            var canvasWidth = canvas.offsetWidth;
            var canvasHeight = canvas.offsetHeight;

            this.guides.push(
                { type: 'vertical', position: 0, source: 'canvas-left' },
                { type: 'vertical', position: canvasWidth / 2, source: 'canvas-center' },
                { type: 'vertical', position: canvasWidth, source: 'canvas-right' },
                { type: 'horizontal', position: 0, source: 'canvas-top' }
            );

            // Guías de otros elementos
            elements.forEach(function(el) {
                var elId = el.dataset.elementId;
                if (elId === excludeId) return;

                var rect = el.getBoundingClientRect();
                var relativeLeft = (rect.left - canvasRect.left) / zoom;
                var relativeTop = (rect.top - canvasRect.top) / zoom;
                var relativeRight = relativeLeft + rect.width / zoom;
                var relativeBottom = relativeTop + rect.height / zoom;
                var centerX = relativeLeft + (rect.width / zoom) / 2;
                var centerY = relativeTop + (rect.height / zoom) / 2;

                // Guías verticales (posición X)
                self.guides.push(
                    { type: 'vertical', position: relativeLeft, source: elId + '-left' },
                    { type: 'vertical', position: centerX, source: elId + '-center' },
                    { type: 'vertical', position: relativeRight, source: elId + '-right' }
                );

                // Guías horizontales (posición Y)
                self.guides.push(
                    { type: 'horizontal', position: relativeTop, source: elId + '-top' },
                    { type: 'horizontal', position: centerY, source: elId + '-middle' },
                    { type: 'horizontal', position: relativeBottom, source: elId + '-bottom' }
                );
            });
        },

        /**
         * Verificar snap y mostrar guías
         */
        checkSnap: function(dragRect) {
            var self = this;
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return { snapX: null, snapY: null };

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            // Posiciones del elemento arrastrado
            var left = (dragRect.left - canvasRect.left) / zoom;
            var top = (dragRect.top - canvasRect.top) / zoom;
            var right = left + dragRect.width / zoom;
            var bottom = top + dragRect.height / zoom;
            var centerX = left + (dragRect.width / zoom) / 2;
            var centerY = top + (dragRect.height / zoom) / 2;

            var snapX = null;
            var snapY = null;
            var matchedGuidesV = [];
            var matchedGuidesH = [];

            // Verificar guías verticales
            this.guides.filter(function(g) { return g.type === 'vertical'; }).forEach(function(guide) {
                // Verificar borde izquierdo
                if (Math.abs(left - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - left, position: guide.position };
                    matchedGuidesV.push(guide);
                }
                // Verificar centro
                else if (Math.abs(centerX - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - centerX, position: guide.position };
                    matchedGuidesV.push(guide);
                }
                // Verificar borde derecho
                else if (Math.abs(right - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - right, position: guide.position };
                    matchedGuidesV.push(guide);
                }
            });

            // Verificar guías horizontales
            this.guides.filter(function(g) { return g.type === 'horizontal'; }).forEach(function(guide) {
                // Verificar borde superior
                if (Math.abs(top - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - top, position: guide.position };
                    matchedGuidesH.push(guide);
                }
                // Verificar centro
                else if (Math.abs(centerY - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - centerY, position: guide.position };
                    matchedGuidesH.push(guide);
                }
                // Verificar borde inferior
                else if (Math.abs(bottom - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - bottom, position: guide.position };
                    matchedGuidesH.push(guide);
                }
            });

            // Mostrar guías visuales
            this.showSmartGuides(matchedGuidesV, matchedGuidesH);

            return { snapX: snapX, snapY: snapY };
        },

        /**
         * Mostrar guías visuales inteligentes
         */
        showSmartGuides: function(verticalGuides, horizontalGuides) {
            // Limpiar guías anteriores
            document.querySelectorAll('.vbp-smart-guide').forEach(function(g) { g.remove(); });

            var canvas = document.querySelector('.vbp-canvas-wrapper');
            if (!canvas) return;

            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            // Mostrar guías verticales
            verticalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--vertical';
                line.style.cssText = 'position: absolute; top: 0; bottom: 0; width: 1px; background: #ec4899; left: ' + (guide.position * zoom) + 'px; pointer-events: none; z-index: 1002;';

                // Agregar indicador de tipo
                if (guide.source.includes('center')) {
                    line.style.background = '#8b5cf6'; // Púrpura para centros
                }

                canvas.appendChild(line);
            });

            // Mostrar guías horizontales
            horizontalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--horizontal';
                line.style.cssText = 'position: absolute; left: 0; right: 0; height: 1px; background: #ec4899; top: ' + (guide.position * zoom) + 'px; pointer-events: none; z-index: 1002;';

                // Agregar indicador de tipo
                if (guide.source.includes('middle') || guide.source.includes('center')) {
                    line.style.background = '#8b5cf6'; // Púrpura para centros
                }

                canvas.appendChild(line);
            });
        },

        /**
         * Finalizar arrastre
         */
        endDrag: function() {
            this.currentDragId = null;
            this.guides = [];
            document.querySelectorAll('.vbp-smart-guide').forEach(function(g) { g.remove(); });
        }
    },

    /**
     * Alt+Drag para duplicar elementos
     */
    altDragDuplicate: {
        isAltDrag: false,
        originalId: null,
        duplicatedId: null,

        /**
         * Iniciar Alt+Drag
         */
        start: function(elementId, event) {
            if (event.altKey) {
                this.isAltDrag = true;
                this.originalId = elementId;

                // Duplicar el elemento
                var store = Alpine.store('vbp');
                var newId = store.duplicateElement(elementId);
                this.duplicatedId = newId;

                // Seleccionar el duplicado para arrastrarlo
                store.setSelection([newId]);

                // Mostrar feedback visual
                this.showDuplicateIndicator();

                return newId;
            }
            return null;
        },

        /**
         * Mostrar indicador de duplicación
         */
        showDuplicateIndicator: function() {
            var indicator = document.createElement('div');
            indicator.id = 'vbp-duplicate-indicator';
            indicator.innerHTML = '⧉ Duplicando...';
            indicator.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 8px 16px; background: #8b5cf6; color: white; border-radius: 6px; font-size: 13px; font-weight: 500; z-index: 10000; box-shadow: 0 4px 12px rgba(139,92,246,0.3);';
            document.body.appendChild(indicator);

            setTimeout(function() {
                var el = document.getElementById('vbp-duplicate-indicator');
                if (el) el.remove();
            }, 1500);
        },

        /**
         * Finalizar Alt+Drag
         */
        end: function() {
            this.isAltDrag = false;
            this.originalId = null;
            this.duplicatedId = null;
        }
    }
};

/**
 * Sistema de redimensionamiento de elementos
 */
window.vbpResize = {
    isResizing: false,
    currentElement: null,
    currentHandle: null,
    startX: 0,
    startY: 0,
    startWidth: 0,
    startHeight: 0,
    startLeft: 0,
    startTop: 0,
    aspectRatio: null,

    /**
     * Inicializar sistema de resize
     */
    init: function() {
        var self = this;

        // Delegate event para handles
        document.addEventListener('mousedown', function(e) {
            var handle = e.target.closest('.vbp-handle');
            if (handle) {
                e.preventDefault();
                e.stopPropagation();
                self.startResize(handle, e);
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (self.isResizing) {
                e.preventDefault();
                self.doResize(e);
            }
        });

        document.addEventListener('mouseup', function(e) {
            if (self.isResizing) {
                self.endResize();
            }
        });
    },

    /**
     * Iniciar redimensionamiento
     */
    startResize: function(handle, event) {
        var elementWrapper = handle.closest('.vbp-element');
        if (!elementWrapper) return;

        this.isResizing = true;
        this.currentElement = elementWrapper;
        this.currentHandle = this.getHandleType(handle);
        this.startX = event.clientX;
        this.startY = event.clientY;

        // Obtener dimensiones actuales
        var rect = elementWrapper.getBoundingClientRect();
        var content = elementWrapper.querySelector('.vbp-element-content');
        
        this.startWidth = rect.width;
        this.startHeight = rect.height;
        this.startLeft = rect.left;
        this.startTop = rect.top;

        // Guardar aspect ratio si se mantiene Shift
        this.aspectRatio = event.shiftKey ? (this.startWidth / this.startHeight) : null;

        // Clase visual
        document.body.classList.add('vbp-resizing');
        elementWrapper.classList.add('vbp-element-resizing');

        // Mostrar dimensiones
        this.showSizeIndicator(this.startWidth, this.startHeight);
    },

    /**
     * Obtener tipo de handle
     */
    getHandleType: function(handle) {
        var classes = handle.className;
        if (classes.includes('handle-nw')) return 'nw';
        if (classes.includes('handle-n')) return 'n';
        if (classes.includes('handle-ne')) return 'ne';
        if (classes.includes('handle-w')) return 'w';
        if (classes.includes('handle-e')) return 'e';
        if (classes.includes('handle-sw')) return 'sw';
        if (classes.includes('handle-s')) return 's';
        if (classes.includes('handle-se')) return 'se';
        return 'se';
    },

    /**
     * Realizar redimensionamiento
     */
    doResize: function(event) {
        if (!this.currentElement) return;

        var deltaX = event.clientX - this.startX;
        var deltaY = event.clientY - this.startY;

        // Aplicar snap to grid si está activo
        if (window.vbpKeyboard && window.vbpKeyboard.snapToGridEnabled) {
            var gridSize = window.vbpKeyboard.gridSize || 8;
            deltaX = Math.round(deltaX / gridSize) * gridSize;
            deltaY = Math.round(deltaY / gridSize) * gridSize;
        }

        var newWidth = this.startWidth;
        var newHeight = this.startHeight;
        var translateX = 0;
        var translateY = 0;

        // Calcular nuevas dimensiones según el handle
        switch (this.currentHandle) {
            case 'e':
                newWidth = this.startWidth + deltaX;
                break;
            case 'w':
                newWidth = this.startWidth - deltaX;
                translateX = deltaX;
                break;
            case 's':
                newHeight = this.startHeight + deltaY;
                break;
            case 'n':
                newHeight = this.startHeight - deltaY;
                translateY = deltaY;
                break;
            case 'se':
                newWidth = this.startWidth + deltaX;
                newHeight = this.startHeight + deltaY;
                break;
            case 'sw':
                newWidth = this.startWidth - deltaX;
                newHeight = this.startHeight + deltaY;
                translateX = deltaX;
                break;
            case 'ne':
                newWidth = this.startWidth + deltaX;
                newHeight = this.startHeight - deltaY;
                translateY = deltaY;
                break;
            case 'nw':
                newWidth = this.startWidth - deltaX;
                newHeight = this.startHeight - deltaY;
                translateX = deltaX;
                translateY = deltaY;
                break;
        }

        // Mantener aspect ratio con Shift
        if (event.shiftKey && this.aspectRatio) {
            if (this.currentHandle.includes('e') || this.currentHandle.includes('w')) {
                newHeight = newWidth / this.aspectRatio;
            } else {
                newWidth = newHeight * this.aspectRatio;
            }
        }

        // Mínimos
        newWidth = Math.max(50, newWidth);
        newHeight = Math.max(30, newHeight);

        // Aplicar estilos
        var content = this.currentElement.querySelector('.vbp-element-content');
        if (content) {
            content.style.width = newWidth + 'px';
            content.style.minHeight = newHeight + 'px';
        }

        // Actualizar indicador
        this.showSizeIndicator(Math.round(newWidth), Math.round(newHeight));
    },

    /**
     * Finalizar redimensionamiento
     */
    endResize: function() {
        if (!this.currentElement) return;

        var elementId = this.currentElement.dataset.elementId;
        var content = this.currentElement.querySelector('.vbp-element-content');

        if (elementId && content) {
            var newWidth = content.style.width;
            var newHeight = content.style.minHeight;

            // Guardar en el store
            var store = Alpine.store('vbp');
            if (store) {
                var element = store.getElement(elementId);
                if (element) {
                    store.saveToHistory();

                    // Hacer merge profundo de estilos para no perder otras propiedades
                    var currentStyles = element.styles ? JSON.parse(JSON.stringify(element.styles)) : store.getDefaultStyles();

                    // Asegurar que dimensions existe
                    if (!currentStyles.dimensions) {
                        currentStyles.dimensions = { width: '', height: '', minHeight: '', maxWidth: '' };
                    }

                    // Actualizar solo width y height
                    currentStyles.dimensions.width = newWidth;
                    currentStyles.dimensions.height = newHeight;

                    store.updateElement(elementId, { styles: currentStyles });
                    store.isDirty = true;
                }
            }
        }

        // Limpiar
        document.body.classList.remove('vbp-resizing');
        this.currentElement.classList.remove('vbp-element-resizing');
        this.hideSizeIndicator();

        this.isResizing = false;
        this.currentElement = null;
        this.currentHandle = null;
    },

    /**
     * Mostrar indicador de tamaño
     */
    showSizeIndicator: function(width, height) {
        var indicator = document.getElementById('vbp-size-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'vbp-size-indicator';
            indicator.style.cssText = 'position: fixed; bottom: 60px; right: 20px; padding: 8px 14px; background: rgba(30, 30, 46, 0.95); color: #cdd6f4; border-radius: 6px; font-size: 13px; font-family: monospace; z-index: 10001; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            document.body.appendChild(indicator);
        }
        indicator.innerHTML = '<span style="color: #89b4fa;">' + width + '</span> × <span style="color: #a6e3a1;">' + height + '</span> px';
    },

    /**
     * Ocultar indicador de tamaño
     */
    hideSizeIndicator: function() {
        var indicator = document.getElementById('vbp-size-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.vbpResize.init();
});

// También inicializar cuando Alpine esté listo (por si el DOM ya cargó)
document.addEventListener('alpine:initialized', function() {
    if (!window.vbpResize.isResizing) {
        window.vbpResize.init();
    }
});

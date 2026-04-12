/**
 * Visual Builder Pro - Canvas
 * Gestión del canvas principal y drag & drop
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

document.addEventListener('alpine:init', function() {
    Alpine.data('vbpCanvas', function() {
        return {
            /**
             * Instancias de Sortable
             */
            canvasSortable: null,
            blocksSortable: null,
            dropZoneSortable: null,

            /**
             * Estado del canvas
             */
            isDragging: false,
            draggedType: null,

            /**
             * Referencias para cleanup de event listeners y timers
             */
            _eventHandlers: {},
            _inputDebounceMap: null,
            _zoomIndicatorTimer: null,

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
                if (!canvas || canvas.dataset.vbpWheelZoomBound === 'true') return;

                var self = this;

                // Guardar referencia para cleanup
                this._eventHandlers.wheelZoom = function(e) {
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
                };

                canvas.addEventListener('wheel', this._eventHandlers.wheelZoom, { passive: false });
                canvas.dataset.vbpWheelZoomBound = 'true';
            },

            /**
             * Mostrar indicador visual de zoom
             */
            showZoomIndicator: function(zoomLevel) {
                var self = this;
                var existingIndicator = document.getElementById('vbp-zoom-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                // Cancelar timer anterior si existe
                if (this._zoomIndicatorTimer) {
                    clearTimeout(this._zoomIndicatorTimer);
                }

                var indicator = document.createElement('div');
                indicator.id = 'vbp-zoom-indicator';
                indicator.innerHTML = '🔍 ' + zoomLevel + '%';
                indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: rgba(30, 30, 46, 0.9); color: #cdd6f4; border-radius: 6px; font-size: 14px; font-weight: 500; z-index: 10000; pointer-events: none; transition: opacity 0.3s;';
                document.body.appendChild(indicator);

                this._zoomIndicatorTimer = setTimeout(function() {
                    indicator.style.opacity = '0';
                    self._zoomIndicatorTimer = setTimeout(function() {
                        if (indicator.parentNode) {
                            indicator.remove();
                        }
                        self._zoomIndicatorTimer = null;
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
                    vbpLog.warn('Canvas: Sortable no disponible');
                    return;
                }

                if (this.canvasSortable) {
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

                        // Iniciar guías inteligentes (con verificación de existencia)
                        var elementId = evt.item.dataset.elementId;
                        if (window.vbpCanvasUtils && window.vbpCanvasUtils.smartGuides) {
                            window.vbpCanvasUtils.smartGuides.startDrag(elementId);
                        }

                        // Alt+Drag para duplicar
                        if (evt.originalEvent && evt.originalEvent.altKey) {
                            document.body.classList.add('vbp-dragging-alt');
                            if (window.vbpCanvasUtils && window.vbpCanvasUtils.altDragDuplicate) {
                                window.vbpCanvasUtils.altDragDuplicate.start(elementId, evt.originalEvent);
                            }
                        }
                    },

                    onMove: function(evt, originalEvent) {
                        // Aplicar snap a guías durante el arrastre
                        self.applySnapDuringDrag(originalEvent);

                        // Verificar guías inteligentes (con verificación de existencia)
                        if (evt.dragged && window.vbpCanvasUtils && window.vbpCanvasUtils.smartGuides) {
                            var rect = evt.dragged.getBoundingClientRect();
                            window.vbpCanvasUtils.smartGuides.checkSnap(rect);
                        }
                    },

                    onEnd: function(evt) {
                        self.isDragging = false;
                        document.body.classList.remove('vbp-dragging', 'vbp-dragging-alt');

                        // Limpiar guías y estado de drag (con verificación de existencia)
                        if (window.vbpCanvasUtils) {
                            if (typeof window.vbpCanvasUtils.hideSnapLines === 'function') {
                                window.vbpCanvasUtils.hideSnapLines();
                            }
                            if (window.vbpCanvasUtils.smartGuides && typeof window.vbpCanvasUtils.smartGuides.endDrag === 'function') {
                                window.vbpCanvasUtils.smartGuides.endDrag();
                            }
                            if (window.vbpCanvasUtils.altDragDuplicate && typeof window.vbpCanvasUtils.altDragDuplicate.end === 'function') {
                                window.vbpCanvasUtils.altDragDuplicate.end();
                            }
                        }

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
                if (!window.vbpCanvasUtils) return;

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
                if (snapH.snapped && typeof window.vbpCanvasUtils.showSnapLine === 'function') {
                    window.vbpCanvasUtils.showSnapLine(snapH.position, 'horizontal');
                }

                // Verificar snap vertical (posición X)
                var snapV = window.vbpCanvasUtils.snapToGuides(posX, 'vertical');
                if (snapV.snapped && typeof window.vbpCanvasUtils.showSnapLine === 'function') {
                    window.vbpCanvasUtils.showSnapLine(snapV.position, 'vertical');
                }

                // Si no hay snap, ocultar líneas
                if (!snapH.snapped && !snapV.snapped && typeof window.vbpCanvasUtils.hideSnapLines === 'function') {
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

                if (this.blocksSortable) {
                    return;
                }

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

                if (this.dropZoneSortable || canvas.dataset.vbpDropZoneBound === 'true') {
                    return;
                }

                // Hacer el canvas una zona de drop válida para bloques nuevos
                if (typeof Sortable !== 'undefined') {
                    this.dropZoneSortable = new Sortable(canvas, {
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
                            // Ocultar líneas de snap (con verificación)
                            if (window.vbpCanvasUtils && typeof window.vbpCanvasUtils.hideSnapLines === 'function') {
                                window.vbpCanvasUtils.hideSnapLines();
                            }

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
                // Optimizado: cachear último dropzone para evitar querySelectorAll
                var lastHighlightedDropzone = null;
                var dragoverThrottled = false;

                canvas.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';

                    // Throttle: máximo 30fps para updates visuales
                    if (dragoverThrottled) return;
                    dragoverThrottled = true;
                    requestAnimationFrame(function() {
                        dragoverThrottled = false;
                    });

                    // Resaltar contenedor si estamos sobre uno
                    var dropzone = e.target.closest('.vbp-container-dropzone, .vbp-column-dropzone');

                    // Solo actualizar si cambió el dropzone (evita querySelectorAll repetido)
                    if (dropzone !== lastHighlightedDropzone) {
                        if (lastHighlightedDropzone) {
                            lastHighlightedDropzone.classList.remove('vbp-drop-highlight');
                        }
                        if (dropzone) {
                            dropzone.classList.add('vbp-drop-highlight');
                            canvas.classList.remove('vbp-drop-active');
                        } else {
                            canvas.classList.add('vbp-drop-active');
                        }
                        lastHighlightedDropzone = dropzone;
                    }
                });

                canvas.addEventListener('dragleave', function(e) {
                    // Solo limpiar si salimos del canvas completamente
                    if (!canvas.contains(e.relatedTarget)) {
                        canvas.classList.remove('vbp-drop-active');
                        if (lastHighlightedDropzone) {
                            lastHighlightedDropzone.classList.remove('vbp-drop-highlight');
                            lastHighlightedDropzone = null;
                        }
                    }
                });

                canvas.addEventListener('drop', function(e) {
                    e.preventDefault();
                    canvas.classList.remove('vbp-drop-active');
                    // Limpiar highlight cacheado
                    if (lastHighlightedDropzone) {
                        lastHighlightedDropzone.classList.remove('vbp-drop-highlight');
                        lastHighlightedDropzone = null;
                    }

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

                canvas.dataset.vbpDropZoneBound = 'true';

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

                if (!canvas || canvas.dataset.vbpClickHandlerBound === 'true') return;

                // Inicializar Map para debounce de inputs
                this._inputDebounceMap = new Map();

                // Crear handlers con referencias para cleanup
                this._eventHandlers.canvasClick = function(e) {
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
                                // Mostrar indicador de duplicación (con verificación)
                                if (window.vbpCanvasUtils && window.vbpCanvasUtils.altDragDuplicate && typeof window.vbpCanvasUtils.altDragDuplicate.showDuplicateIndicator === 'function') {
                                    window.vbpCanvasUtils.altDragDuplicate.showDuplicateIndicator();
                                }
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
                };

                canvas.addEventListener('click', this._eventHandlers.canvasClick);

                // Doble click para edición rápida
                this._eventHandlers.canvasDblClick = function(e) {
                    var editableContent = e.target.closest('[contenteditable]');
                    if (editableContent) {
                        // Ya es editable, seleccionar todo el texto
                        var selection = window.getSelection();
                        var range = document.createRange();
                        range.selectNodeContents(editableContent);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                };

                canvas.addEventListener('dblclick', this._eventHandlers.canvasDblClick);

                // Sincronizar contenido editable con el store al perder el foco
                this._eventHandlers.canvasBlur = function(e) {
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
                };

                canvas.addEventListener('blur', this._eventHandlers.canvasBlur, true); // Usar captura para detectar blur en elementos anidados

                // También sincronizar en input para feedback en tiempo real
                // Usar Map de la instancia para cleanup apropiado
                this._eventHandlers.canvasInput = function(e) {
                    var editableContent = e.target;
                    if (!editableContent.hasAttribute('contenteditable')) return;

                    var field = editableContent.dataset.field;
                    if (!field) return;

                    var element = editableContent.closest('.vbp-element');
                    if (!element) return;

                    var elementId = element.dataset.elementId;
                    var debounceKey = elementId + '_' + field;

                    // Cancelar timeout anterior si existe
                    if (self._inputDebounceMap.has(debounceKey)) {
                        clearTimeout(self._inputDebounceMap.get(debounceKey));
                    }

                    // Capturar valores actuales para el closure
                    var newContent = (field === 'text' || field === 'titulo' || field === 'subtitulo' ||
                        field === 'descripcion' || field === 'boton_texto')
                        ? editableContent.textContent
                        : editableContent.innerHTML;

                    // Debounce: actualizar store después de 200ms de inactividad
                    var timeoutId = setTimeout(function() {
                        self._inputDebounceMap.delete(debounceKey);
                        var store = Alpine.store('vbp');
                        var storeElement = store.getElement(elementId);

                        if (storeElement && storeElement.data && storeElement.data[field] !== newContent) {
                            // Actualizar directamente sin clonar todo el objeto
                            storeElement.data[field] = newContent;
                            store.updateElement(elementId, { data: storeElement.data });
                        }
                    }, 200);

                    self._inputDebounceMap.set(debounceKey, timeoutId);
                };

                canvas.addEventListener('input', this._eventHandlers.canvasInput, true);

                canvas.dataset.vbpClickHandlerBound = 'true';
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
                // Limpiar Sortables
                if (this.canvasSortable) {
                    this.canvasSortable.destroy();
                    this.canvasSortable = null;
                }
                if (this.blocksSortable) {
                    this.blocksSortable.destroy();
                    this.blocksSortable = null;
                }
                if (this.dropZoneSortable) {
                    this.dropZoneSortable.destroy();
                    this.dropZoneSortable = null;
                }
                if (this.containerObserver) {
                    this.containerObserver.disconnect();
                    this.containerObserver = null;
                }

                // Limpiar timers
                if (this._zoomIndicatorTimer) {
                    clearTimeout(this._zoomIndicatorTimer);
                    this._zoomIndicatorTimer = null;
                }

                // Limpiar debounce timers de input
                if (this._inputDebounceMap) {
                    this._inputDebounceMap.forEach(function(timerId) {
                        clearTimeout(timerId);
                    });
                    this._inputDebounceMap.clear();
                    this._inputDebounceMap = null;
                }

                // Limpiar event listeners
                var canvas = document.querySelector('.vbp-canvas');
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');

                if (canvas) {
                    if (this._eventHandlers.canvasClick) {
                        canvas.removeEventListener('click', this._eventHandlers.canvasClick);
                    }
                    if (this._eventHandlers.canvasDblClick) {
                        canvas.removeEventListener('dblclick', this._eventHandlers.canvasDblClick);
                    }
                    if (this._eventHandlers.canvasBlur) {
                        canvas.removeEventListener('blur', this._eventHandlers.canvasBlur, true);
                    }
                    if (this._eventHandlers.canvasInput) {
                        canvas.removeEventListener('input', this._eventHandlers.canvasInput, true);
                    }
                    canvas.dataset.vbpClickHandlerBound = 'false';
                }

                if (canvasWrapper && this._eventHandlers.wheelZoom) {
                    canvasWrapper.removeEventListener('wheel', this._eventHandlers.wheelZoom);
                    canvasWrapper.dataset.vbpWheelZoomBound = 'false';
                }

                this._eventHandlers = {};
            }
        };
    });
});

// Exponer funciones globales para drag & drop nativo

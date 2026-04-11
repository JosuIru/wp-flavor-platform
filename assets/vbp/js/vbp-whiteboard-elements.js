/**
 * Visual Builder Pro - Whiteboard Elements
 *
 * Componentes Alpine.js para renderizar elementos del whiteboard:
 * - Sticky notes
 * - Formas
 * - Texto
 * - Dibujos
 * - Conectores
 * - Stamps
 * - Frames
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.6.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Whiteboard Elements] Alpine.js no esta disponible');
        return;
    }

    document.addEventListener('alpine:init', function() {

        /**
         * Componente: Canvas del Whiteboard
         */
        Alpine.data('whiteboardCanvas', function() {
            return {
                // Referencias
                canvasElement: null,
                svgElement: null,

                // Estado local
                selectionRect: null,
                activeGuides: { horizontal: [], vertical: [] },

                // Herramientas
                snapGuides: null,
                selectionTool: null,
                resizeTool: null,

                init() {
                    const store = Alpine.store('vbpWhiteboard');

                    // Guardar referencias
                    this.$nextTick(() => {
                        this.canvasElement = this.$el;
                        this.svgElement = this.$el.querySelector('.vbp-whiteboard-svg');

                        store.canvasRef = this.canvasElement;
                        store.svgRef = this.svgElement;

                        store.updateCursor();
                    });

                    // Inicializar herramientas
                    this.snapGuides = new window.VBPSnapGuides(store.gridSize, 10);
                    this.selectionTool = new window.VBPSelectionTool();
                    this.resizeTool = new window.VBPResizeTool();

                    // Listeners de eventos
                    this.setupEventListeners();
                },

                setupEventListeners() {
                    // Wheel para zoom
                    this.$el.addEventListener('wheel', this.handleWheel.bind(this), { passive: false });
                },

                /**
                 * Manejar evento wheel (zoom y pan)
                 */
                handleWheel(event) {
                    const store = Alpine.store('vbpWhiteboard');
                    if (!store.enabled) return;

                    event.preventDefault();

                    if (event.ctrlKey || event.metaKey) {
                        // Zoom con Ctrl/Cmd + Wheel
                        const zoomDelta = event.deltaY > 0 ? -0.1 : 0.1;
                        const centerPoint = {
                            x: event.clientX,
                            y: event.clientY
                        };
                        store.setZoom(store.viewport.zoom + zoomDelta, centerPoint);
                    } else {
                        // Pan con scroll normal
                        store.panBy(-event.deltaX, -event.deltaY);
                    }
                },

                /**
                 * Manejar click en canvas
                 */
                handleCanvasClick(event) {
                    const store = Alpine.store('vbpWhiteboard');
                    if (!store.enabled) return;

                    // Convertir coordenadas
                    const point = store.screenToCanvas(event.clientX, event.clientY);

                    // Si click en canvas vacio, deseleccionar
                    if (event.target === this.canvasElement || event.target === this.svgElement) {
                        const elementUnderCursor = window.VBPHitTesting.findElementAtPoint(point, store.elements);

                        if (!elementUnderCursor) {
                            if (store.tool === 'select') {
                                store.deselectAll();
                            } else {
                                // Crear elemento segun herramienta
                                this.createElementAtPoint(point);
                            }
                        }
                    }
                },

                /**
                 * Crear elemento en punto segun herramienta activa
                 */
                createElementAtPoint(point) {
                    const store = Alpine.store('vbpWhiteboard');

                    switch (store.tool) {
                        case 'sticky':
                            store.createSticky(point);
                            store.setTool('select');
                            break;

                        case 'shape':
                            store.createShape(point);
                            store.setTool('select');
                            break;

                        case 'text':
                            store.createText(point, { text: '' });
                            store.setTool('select');
                            break;

                        case 'stamp':
                            store.createStamp(point);
                            break;

                        case 'frame':
                            store.createFrame(point);
                            store.setTool('select');
                            break;
                    }
                },

                /**
                 * Manejar mouse down
                 */
                handleMouseDown(event) {
                    const store = Alpine.store('vbpWhiteboard');
                    if (!store.enabled) return;

                    const point = store.screenToCanvas(event.clientX, event.clientY);

                    // Pan tool
                    if (store.tool === 'pan' || (event.button === 1) || (event.button === 0 && event.spaceKey)) {
                        store.interaction.isPanning = true;
                        store.interaction.dragStart = { x: event.clientX, y: event.clientY };
                        this.canvasElement.style.cursor = 'grabbing';
                        return;
                    }

                    // Draw tool
                    if (store.tool === 'draw') {
                        store.interaction.isDrawing = true;
                        store.interaction.drawPoints = [point];
                        return;
                    }

                    // Connector tool
                    if (store.tool === 'connector') {
                        const elementAtPoint = window.VBPHitTesting.findElementAtPoint(point, store.elements);

                        if (elementAtPoint && elementAtPoint.type !== 'connector') {
                            const anchor = window.VBPConnectorGenerator.findBestAnchor(point, elementAtPoint);
                            store.interaction.isConnecting = true;
                            store.interaction.connectFrom = {
                                elementId: elementAtPoint.id,
                                anchor: anchor,
                                point: point
                            };
                        } else {
                            store.interaction.isConnecting = true;
                            store.interaction.connectFrom = {
                                point: point
                            };
                        }
                        return;
                    }

                    // Select tool
                    if (store.tool === 'select') {
                        const elementAtPoint = window.VBPHitTesting.findElementAtPoint(point, store.elements);

                        if (elementAtPoint) {
                            // Verificar si estamos en un handle de resize
                            if (store.selectedIds.includes(elementAtPoint.id)) {
                                const handle = window.VBPHitTesting.getResizeHandle(point, elementAtPoint);
                                if (handle) {
                                    const bounds = {
                                        x: elementAtPoint.position.x,
                                        y: elementAtPoint.position.y,
                                        width: elementAtPoint.size.width,
                                        height: elementAtPoint.size.height
                                    };
                                    this.resizeTool.start(handle, bounds, point, event.shiftKey);
                                    store.interaction.isResizing = true;
                                    store.interaction.resizeHandle = handle;
                                    return;
                                }
                            }

                            // Seleccionar y preparar drag
                            if (event.shiftKey) {
                                store.select(elementAtPoint.id, true);
                            } else if (!store.selectedIds.includes(elementAtPoint.id)) {
                                store.select(elementAtPoint.id, false);
                            }

                            store.interaction.isDragging = true;
                            store.interaction.dragStart = point;
                            store.interaction.dragCurrent = point;
                        } else {
                            // Iniciar seleccion rectangular
                            this.selectionTool.start(point);
                        }
                    }

                    // Eraser tool
                    if (store.tool === 'eraser') {
                        const elementAtPoint = window.VBPHitTesting.findElementAtPoint(point, store.elements);
                        if (elementAtPoint) {
                            store.removeElement(elementAtPoint.id);
                        }
                    }
                },

                /**
                 * Manejar mouse move
                 */
                handleMouseMove(event) {
                    const store = Alpine.store('vbpWhiteboard');
                    if (!store.enabled) return;

                    const point = store.screenToCanvas(event.clientX, event.clientY);

                    // Update hover
                    const elementAtPoint = window.VBPHitTesting.findElementAtPoint(point, store.elements);
                    store.hoveredId = elementAtPoint?.id || null;

                    // Panning
                    if (store.interaction.isPanning) {
                        const dx = event.clientX - store.interaction.dragStart.x;
                        const dy = event.clientY - store.interaction.dragStart.y;
                        store.panBy(dx, dy);
                        store.interaction.dragStart = { x: event.clientX, y: event.clientY };
                        return;
                    }

                    // Drawing
                    if (store.interaction.isDrawing) {
                        store.interaction.drawPoints.push(point);
                        this.$el.dispatchEvent(new CustomEvent('whiteboard:drawing', {
                            detail: { points: store.interaction.drawPoints }
                        }));
                        return;
                    }

                    // Connecting
                    if (store.interaction.isConnecting) {
                        store.interaction.dragCurrent = point;
                        return;
                    }

                    // Resizing
                    if (store.interaction.isResizing) {
                        const newBounds = this.resizeTool.move(point);
                        if (newBounds && store.selectedIds.length === 1) {
                            const element = store.getElementById(store.selectedIds[0]);
                            if (element) {
                                store.updateElement(element.id, {
                                    position: { x: newBounds.x, y: newBounds.y },
                                    size: { width: newBounds.width, height: newBounds.height }
                                }, true);
                            }
                        }
                        return;
                    }

                    // Dragging elements
                    if (store.interaction.isDragging) {
                        const dx = point.x - store.interaction.dragCurrent.x;
                        const dy = point.y - store.interaction.dragCurrent.y;

                        store.getSelectedElements().forEach(el => {
                            if (el.position) {
                                let newPosition = {
                                    x: el.position.x + dx,
                                    y: el.position.y + dy
                                };

                                // Aplicar snap si esta activo
                                if (store.snapToGrid) {
                                    const snapResult = this.snapGuides.applySnap(
                                        newPosition,
                                        el,
                                        store.elements.filter(e => !store.selectedIds.includes(e.id))
                                    );
                                    newPosition = snapResult.position;
                                    this.activeGuides = snapResult.guides;
                                }

                                store.updateElement(el.id, { position: newPosition }, true);
                            }
                        });

                        store.interaction.dragCurrent = point;
                        return;
                    }

                    // Selection rectangle
                    if (this.selectionTool.isSelecting) {
                        this.selectionTool.move(point);
                        this.selectionRect = this.selectionTool.getRect();
                    }
                },

                /**
                 * Manejar mouse up
                 */
                handleMouseUp(event) {
                    const store = Alpine.store('vbpWhiteboard');
                    if (!store.enabled) return;

                    const point = store.screenToCanvas(event.clientX, event.clientY);

                    // Terminar panning
                    if (store.interaction.isPanning) {
                        store.interaction.isPanning = false;
                        store.updateCursor();
                    }

                    // Terminar drawing
                    if (store.interaction.isDrawing) {
                        const points = store.interaction.drawPoints;
                        if (points.length >= 2) {
                            // Simplificar puntos
                            const simplified = window.VBPLineSmoothing.simplify(points, 2);
                            store.createDrawing(simplified);
                        }
                        store.interaction.isDrawing = false;
                        store.interaction.drawPoints = [];
                    }

                    // Terminar connecting
                    if (store.interaction.isConnecting) {
                        const from = store.interaction.connectFrom;
                        const elementAtPoint = window.VBPHitTesting.findElementAtPoint(point, store.elements);

                        let to;
                        if (elementAtPoint && elementAtPoint.type !== 'connector') {
                            const anchor = window.VBPConnectorGenerator.findBestAnchor(point, elementAtPoint);
                            to = { elementId: elementAtPoint.id, anchor: anchor };
                        } else {
                            to = { point: point };
                        }

                        // Solo crear conector si hay destino diferente
                        if (from.elementId !== to.elementId || !from.elementId) {
                            store.createConnector(from, to);
                        }

                        store.interaction.isConnecting = false;
                        store.interaction.connectFrom = null;
                    }

                    // Terminar resizing
                    if (store.interaction.isResizing) {
                        this.resizeTool.end();
                        store.interaction.isResizing = false;
                        store.interaction.resizeHandle = null;
                    }

                    // Terminar dragging
                    if (store.interaction.isDragging) {
                        store.interaction.isDragging = false;
                        this.activeGuides = { horizontal: [], vertical: [] };
                    }

                    // Terminar seleccion rectangular
                    if (this.selectionTool.isSelecting) {
                        const rect = this.selectionTool.end();
                        if (rect && rect.width > 5 && rect.height > 5) {
                            store.selectInArea(rect);
                        }
                        this.selectionRect = null;
                    }
                },

                /**
                 * Obtener transformacion del viewport
                 */
                getViewportTransform() {
                    const store = Alpine.store('vbpWhiteboard');
                    return `translate(${store.viewport.x}px, ${store.viewport.y}px) scale(${store.viewport.zoom})`;
                },

                /**
                 * Generar pattern del grid
                 */
                getGridPattern() {
                    const store = Alpine.store('vbpWhiteboard');
                    const size = store.gridSize;
                    const color = document.documentElement.classList.contains('dark')
                        ? store.config.gridColorDark
                        : store.config.gridColor;

                    return `
                        <pattern id="wb-grid-small" width="${size}" height="${size}" patternUnits="userSpaceOnUse">
                            <path d="M ${size} 0 L 0 0 0 ${size}" fill="none" stroke="${color}" stroke-width="0.5" opacity="0.3"/>
                        </pattern>
                        <pattern id="wb-grid-large" width="${size * 5}" height="${size * 5}" patternUnits="userSpaceOnUse">
                            <rect width="${size * 5}" height="${size * 5}" fill="url(#wb-grid-small)"/>
                            <path d="M ${size * 5} 0 L 0 0 0 ${size * 5}" fill="none" stroke="${color}" stroke-width="1" opacity="0.5"/>
                        </pattern>
                    `;
                }
            };
        });

        /**
         * Componente: Sticky Note
         */
        Alpine.data('whiteboardSticky', function(elementId) {
            return {
                isEditing: false,
                textContent: '',

                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get colors() {
                    const colorKey = this.element?.color || 'yellow';
                    return window.VBPStickyColors[colorKey] || window.VBPStickyColors.yellow;
                },

                get style() {
                    const element = this.element;
                    if (!element) return {};

                    return {
                        left: element.position.x + 'px',
                        top: element.position.y + 'px',
                        width: element.size.width + 'px',
                        height: element.size.height + 'px',
                        backgroundColor: this.colors.bg,
                        borderColor: this.colors.border,
                        color: this.colors.text
                    };
                },

                init() {
                    this.textContent = this.element?.text || '';
                },

                startEditing() {
                    this.isEditing = true;
                    this.textContent = this.element?.text || '';
                    this.$nextTick(() => {
                        const textarea = this.$el.querySelector('textarea');
                        if (textarea) {
                            textarea.focus();
                            textarea.select();
                        }
                    });
                },

                stopEditing() {
                    this.isEditing = false;
                    if (this.element) {
                        Alpine.store('vbpWhiteboard').updateElement(elementId, {
                            text: this.textContent
                        });
                    }
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                },

                handleDoubleClick(event) {
                    event.stopPropagation();
                    this.startEditing();
                },

                vote() {
                    Alpine.store('vbpWhiteboard').voteForSticky(elementId);
                }
            };
        });

        /**
         * Componente: Forma
         */
        Alpine.data('whiteboardShape', function(elementId) {
            return {
                isEditing: false,
                textContent: '',

                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get path() {
                    const element = this.element;
                    if (!element) return '';

                    return window.VBPShapeGenerator.getPath(
                        element.shape,
                        0, 0,
                        element.size.width,
                        element.size.height,
                        { cornerRadius: element.cornerRadius }
                    );
                },

                get transform() {
                    const element = this.element;
                    if (!element) return '';

                    let transformStr = `translate(${element.position.x}, ${element.position.y})`;
                    if (element.rotation) {
                        const cx = element.size.width / 2;
                        const cy = element.size.height / 2;
                        transformStr += ` rotate(${element.rotation}, ${cx}, ${cy})`;
                    }
                    return transformStr;
                },

                init() {
                    this.textContent = this.element?.text || '';
                },

                startEditing() {
                    this.isEditing = true;
                    this.textContent = this.element?.text || '';
                },

                stopEditing() {
                    this.isEditing = false;
                    if (this.element) {
                        Alpine.store('vbpWhiteboard').updateElement(elementId, {
                            text: this.textContent
                        });
                    }
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                }
            };
        });

        /**
         * Componente: Texto
         */
        Alpine.data('whiteboardText', function(elementId) {
            return {
                isEditing: false,
                textContent: '',

                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get style() {
                    const element = this.element;
                    if (!element) return {};

                    return {
                        left: element.position.x + 'px',
                        top: element.position.y + 'px',
                        fontSize: (element.fontSize || 16) + 'px',
                        fontWeight: element.fontWeight || 'normal',
                        fontStyle: element.fontStyle || 'normal',
                        color: element.color || '#000000',
                        textAlign: element.align || 'left'
                    };
                },

                init() {
                    this.textContent = this.element?.text || '';
                },

                startEditing() {
                    this.isEditing = true;
                    this.textContent = this.element?.text || '';
                    this.$nextTick(() => {
                        const input = this.$el.querySelector('input, textarea');
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                },

                stopEditing() {
                    this.isEditing = false;
                    if (this.element) {
                        Alpine.store('vbpWhiteboard').updateElement(elementId, {
                            text: this.textContent
                        });
                    }
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                },

                handleDoubleClick(event) {
                    event.stopPropagation();
                    this.startEditing();
                }
            };
        });

        /**
         * Componente: Dibujo a mano alzada
         */
        Alpine.data('whiteboardDraw', function(elementId) {
            return {
                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get path() {
                    const element = this.element;
                    if (!element || !element.points) return '';

                    return window.VBPLineSmoothing.pointsToSVGPath(element.points, true);
                },

                get style() {
                    const element = this.element;
                    return {
                        stroke: element?.stroke || '#000000',
                        strokeWidth: element?.strokeWidth || 3,
                        fill: 'none',
                        strokeLinecap: 'round',
                        strokeLinejoin: 'round'
                    };
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                }
            };
        });

        /**
         * Componente: Conector
         */
        Alpine.data('whiteboardConnector', function(elementId) {
            return {
                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get fromPoint() {
                    const element = this.element;
                    if (!element) return { x: 0, y: 0 };

                    if (element.fromPoint) {
                        return element.fromPoint;
                    }

                    if (element.from?.elementId) {
                        const fromElement = Alpine.store('vbpWhiteboard').getElementById(element.from.elementId);
                        if (fromElement) {
                            const anchors = window.VBPConnectorGenerator.getAnchorPoints(fromElement);
                            return anchors[element.from.anchor] || anchors.center;
                        }
                    }

                    return { x: 0, y: 0 };
                },

                get toPoint() {
                    const element = this.element;
                    if (!element) return { x: 0, y: 0 };

                    if (element.toPoint) {
                        return element.toPoint;
                    }

                    if (element.to?.elementId) {
                        const toElement = Alpine.store('vbpWhiteboard').getElementById(element.to.elementId);
                        if (toElement) {
                            const anchors = window.VBPConnectorGenerator.getAnchorPoints(toElement);
                            return anchors[element.to.anchor] || anchors.center;
                        }
                    }

                    return { x: 0, y: 0 };
                },

                get path() {
                    const element = this.element;
                    if (!element) return '';

                    return window.VBPConnectorGenerator.getPath(
                        element.style || 'straight',
                        this.fromPoint,
                        this.toPoint,
                        element.from?.anchor,
                        element.to?.anchor
                    );
                },

                get style() {
                    const element = this.element;
                    return {
                        stroke: element?.stroke || '#000000',
                        strokeWidth: element?.strokeWidth || 2,
                        fill: 'none'
                    };
                },

                get markerId() {
                    return 'arrow-' + elementId;
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                }
            };
        });

        /**
         * Componente: Stamp/Sticker
         */
        Alpine.data('whiteboardStamp', function(elementId) {
            return {
                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get style() {
                    const element = this.element;
                    if (!element) return {};

                    return {
                        left: element.position.x + 'px',
                        top: element.position.y + 'px',
                        fontSize: (element.size || 48) + 'px'
                    };
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                }
            };
        });

        /**
         * Componente: Frame
         */
        Alpine.data('whiteboardFrame', function(elementId) {
            return {
                isEditingTitle: false,
                titleContent: '',

                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isSelected() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                get style() {
                    const element = this.element;
                    if (!element) return {};

                    return {
                        left: element.position.x + 'px',
                        top: element.position.y + 'px',
                        width: element.size.width + 'px',
                        height: element.size.height + 'px',
                        backgroundColor: element.color || '#f3f4f6'
                    };
                },

                init() {
                    this.titleContent = this.element?.title || '';
                },

                startEditingTitle() {
                    this.isEditingTitle = true;
                    this.titleContent = this.element?.title || '';
                    this.$nextTick(() => {
                        const input = this.$el.querySelector('.wb-frame-title-input');
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                },

                stopEditingTitle() {
                    this.isEditingTitle = false;
                    if (this.element) {
                        Alpine.store('vbpWhiteboard').updateElement(elementId, {
                            title: this.titleContent
                        });
                    }
                },

                handleClick(event) {
                    event.stopPropagation();
                    Alpine.store('vbpWhiteboard').select(elementId, event.shiftKey);
                },

                handleTitleDoubleClick(event) {
                    event.stopPropagation();
                    this.startEditingTitle();
                }
            };
        });

        /**
         * Componente: Handles de resize
         */
        Alpine.data('whiteboardResizeHandles', function(elementId) {
            return {
                handles: ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'],

                get element() {
                    return Alpine.store('vbpWhiteboard').getElementById(elementId);
                },

                get isVisible() {
                    return Alpine.store('vbpWhiteboard').selectedIds.includes(elementId);
                },

                getHandleStyle(handle) {
                    const element = this.element;
                    if (!element || !element.position || !element.size) return {};

                    const x = element.position.x;
                    const y = element.position.y;
                    const w = element.size.width;
                    const h = element.size.height;

                    const positions = {
                        'nw': { left: x, top: y },
                        'n': { left: x + w / 2, top: y },
                        'ne': { left: x + w, top: y },
                        'e': { left: x + w, top: y + h / 2 },
                        'se': { left: x + w, top: y + h },
                        's': { left: x + w / 2, top: y + h },
                        'sw': { left: x, top: y + h },
                        'w': { left: x, top: y + h / 2 }
                    };

                    const pos = positions[handle] || { left: 0, top: 0 };

                    return {
                        left: pos.left + 'px',
                        top: pos.top + 'px',
                        cursor: window.VBPResizeTool.getCursor(handle)
                    };
                }
            };
        });

    });

})();

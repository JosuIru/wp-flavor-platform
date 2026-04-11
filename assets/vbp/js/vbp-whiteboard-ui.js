/**
 * Visual Builder Pro - Whiteboard UI Components
 *
 * Componentes de interfaz de usuario para el whiteboard:
 * - Toolbar principal
 * - Panel de propiedades
 * - Panel de plantillas
 * - Minimap
 * - Controles de zoom
 * - Menu contextual
 * - Modales de exportacion
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.6.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Whiteboard UI] Alpine.js no esta disponible');
        return;
    }

    document.addEventListener('alpine:init', function() {

        /**
         * Componente: Toolbar Principal
         */
        Alpine.data('whiteboardToolbar', function() {
            return {
                showSubmenu: false,
                submenuType: null,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get currentTool() {
                    return this.store.tool;
                },

                get canUndo() {
                    return this.store.canUndo;
                },

                get canRedo() {
                    return this.store.canRedo;
                },

                get hasSelection() {
                    return this.store.selectedIds.length > 0;
                },

                tools: [
                    { id: 'select', icon: '🔍', label: 'Seleccionar', shortcut: 'V' },
                    { id: 'pan', icon: '✋', label: 'Mover canvas', shortcut: 'H' },
                    { id: 'divider' },
                    { id: 'sticky', icon: '📝', label: 'Sticky Note', shortcut: 'S', hasSubmenu: true, submenuType: 'sticky-colors' },
                    { id: 'shape', icon: '🔷', label: 'Forma', shortcut: 'R', hasSubmenu: true, submenuType: 'shapes' },
                    { id: 'draw', icon: '✏️', label: 'Dibujar', shortcut: 'P', hasSubmenu: true, submenuType: 'draw-options' },
                    { id: 'text', icon: '📃', label: 'Texto', shortcut: 'T' },
                    { id: 'connector', icon: '📎', label: 'Conector', shortcut: 'L', hasSubmenu: true, submenuType: 'connector-styles' },
                    { id: 'stamp', icon: '😀', label: 'Stamp', shortcut: 'E', hasSubmenu: true, submenuType: 'stamps' },
                    { id: 'frame', icon: '📋', label: 'Frame', shortcut: 'F' },
                    { id: 'divider' },
                    { id: 'eraser', icon: '🧹', label: 'Borrador', shortcut: 'X' }
                ],

                selectTool(toolId, hasSubmenu, submenuType) {
                    if (hasSubmenu) {
                        if (this.showSubmenu && this.submenuType === submenuType) {
                            this.showSubmenu = false;
                            this.submenuType = null;
                        } else {
                            this.showSubmenu = true;
                            this.submenuType = submenuType;
                        }
                    } else {
                        this.showSubmenu = false;
                        this.submenuType = null;
                    }

                    this.store.setTool(toolId);
                },

                closeSubmenu() {
                    this.showSubmenu = false;
                    this.submenuType = null;
                },

                undo() {
                    this.store.undo();
                },

                redo() {
                    this.store.redo();
                },

                deleteSelected() {
                    this.store.deleteSelected();
                },

                duplicate() {
                    this.store.duplicate();
                },

                // Sticky colors
                stickyColors: Object.keys(window.VBPStickyColors || {}),

                selectStickyColor(color) {
                    this.store.toolOptions.stickyColor = color;
                    this.closeSubmenu();
                },

                getStickyColorStyle(color) {
                    const colors = window.VBPStickyColors[color];
                    return colors ? { backgroundColor: colors.bg, borderColor: colors.border } : {};
                },

                // Shapes
                shapes: Object.keys(window.VBPShapes || {}),

                selectShape(shape) {
                    this.store.toolOptions.shape = shape;
                    this.closeSubmenu();
                },

                getShapeIcon(shape) {
                    return window.VBPShapes[shape]?.icon || '▭';
                },

                // Draw options
                drawColors: ['#000000', '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899'],
                drawWidths: [2, 4, 6, 8, 12],

                selectDrawColor(color) {
                    this.store.toolOptions.drawColor = color;
                },

                selectDrawWidth(width) {
                    this.store.toolOptions.drawWidth = width;
                    this.closeSubmenu();
                },

                // Connector styles
                connectorStyles: Object.keys(window.VBPConnectorStyles || {}),

                selectConnectorStyle(style) {
                    this.store.toolOptions.connectorStyle = style;
                    this.closeSubmenu();
                },

                getConnectorIcon(style) {
                    return window.VBPConnectorStyles[style]?.icon || '—';
                },

                // Stamps
                stampCategories: Object.keys(window.VBPStamps || {}),

                selectStampCategory(category) {
                    this.store.toolOptions.stampCategory = category;
                },

                getStampsInCategory(category) {
                    return window.VBPStamps[category] || [];
                },

                selectStamp(emoji) {
                    this.store.toolOptions.stamp = emoji;
                    this.closeSubmenu();
                }
            };
        });

        /**
         * Componente: Panel de Propiedades
         */
        Alpine.data('whiteboardProperties', function() {
            return {
                visible: false,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get selectedElement() {
                    const selected = this.store.getSelectedElements();
                    return selected.length === 1 ? selected[0] : null;
                },

                get elementType() {
                    return this.selectedElement?.type || null;
                },

                show() {
                    this.visible = true;
                },

                hide() {
                    this.visible = false;
                },

                toggle() {
                    this.visible = !this.visible;
                },

                updateProperty(property, value) {
                    if (!this.selectedElement) return;

                    const updates = {};

                    // Manejar propiedades anidadas
                    if (property.includes('.')) {
                        const [parent, child] = property.split('.');
                        updates[parent] = {
                            ...this.selectedElement[parent],
                            [child]: value
                        };
                    } else {
                        updates[property] = value;
                    }

                    this.store.updateElement(this.selectedElement.id, updates);
                },

                // Propiedades comunes
                get position() {
                    return this.selectedElement?.position || { x: 0, y: 0 };
                },

                get size() {
                    return this.selectedElement?.size || { width: 100, height: 100 };
                },

                // Propiedades de sticky
                get stickyColor() {
                    return this.selectedElement?.color || 'yellow';
                },

                get stickyText() {
                    return this.selectedElement?.text || '';
                },

                // Propiedades de forma
                get shapeFill() {
                    return this.selectedElement?.fill || '#ffffff';
                },

                get shapeStroke() {
                    return this.selectedElement?.stroke || '#000000';
                },

                get shapeStrokeWidth() {
                    return this.selectedElement?.strokeWidth || 2;
                },

                // Propiedades de texto
                get textFontSize() {
                    return this.selectedElement?.fontSize || 16;
                },

                get textFontWeight() {
                    return this.selectedElement?.fontWeight || 'normal';
                },

                get textColor() {
                    return this.selectedElement?.color || '#000000';
                },

                // Propiedades de conector
                get connectorStyle() {
                    return this.selectedElement?.style || 'straight';
                },

                get connectorStartArrow() {
                    return this.selectedElement?.startArrow || false;
                },

                get connectorEndArrow() {
                    return this.selectedElement?.endArrow || true;
                },

                // Propiedades de frame
                get frameTitle() {
                    return this.selectedElement?.title || '';
                },

                get frameColor() {
                    return this.selectedElement?.color || '#f3f4f6';
                }
            };
        });

        /**
         * Componente: Panel de Plantillas
         */
        Alpine.data('whiteboardTemplates', function() {
            return {
                visible: false,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get templates() {
                    return this.store.getTemplates();
                },

                show() {
                    this.visible = true;
                },

                hide() {
                    this.visible = false;
                },

                toggle() {
                    this.visible = !this.visible;
                },

                selectTemplate(templateId) {
                    this.store.loadTemplate(templateId);
                    this.hide();
                }
            };
        });

        /**
         * Componente: Minimap
         */
        Alpine.data('whiteboardMinimap', function() {
            return {
                visible: true,
                isDragging: false,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get viewport() {
                    return this.store.viewport;
                },

                get elements() {
                    return this.store.elements;
                },

                toggle() {
                    this.visible = !this.visible;
                },

                getViewportRect() {
                    const canvas = this.store.canvasRef;
                    if (!canvas) return { x: 0, y: 0, width: 50, height: 35 };

                    const bounds = this.store.getElementsBounds(this.elements);
                    const scale = 0.1; // Escala del minimap

                    // Calcular posicion del viewport en el minimap
                    const viewportWidth = canvas.clientWidth / this.viewport.zoom * scale;
                    const viewportHeight = canvas.clientHeight / this.viewport.zoom * scale;

                    const viewportX = (-this.viewport.x / this.viewport.zoom - bounds.x) * scale;
                    const viewportY = (-this.viewport.y / this.viewport.zoom - bounds.y) * scale;

                    return {
                        x: Math.max(0, viewportX),
                        y: Math.max(0, viewportY),
                        width: viewportWidth,
                        height: viewportHeight
                    };
                },

                handleMinimapClick(event) {
                    // Navegar al punto clickeado
                    const rect = event.currentTarget.getBoundingClientRect();
                    const clickX = event.clientX - rect.left;
                    const clickY = event.clientY - rect.top;

                    const bounds = this.store.getElementsBounds(this.elements);
                    const scale = 0.1;

                    const targetX = clickX / scale + bounds.x;
                    const targetY = clickY / scale + bounds.y;

                    const canvas = this.store.canvasRef;
                    if (canvas) {
                        this.store.viewport.x = -targetX * this.viewport.zoom + canvas.clientWidth / 2;
                        this.store.viewport.y = -targetY * this.viewport.zoom + canvas.clientHeight / 2;
                    }
                }
            };
        });

        /**
         * Componente: Controles de Zoom
         */
        Alpine.data('whiteboardZoom', function() {
            return {
                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get zoomLevel() {
                    return Math.round(this.store.viewport.zoom * 100);
                },

                zoomIn() {
                    this.store.zoomIn();
                },

                zoomOut() {
                    this.store.zoomOut();
                },

                zoomReset() {
                    this.store.zoomReset();
                },

                zoomToFit() {
                    this.store.zoomToFit();
                },

                zoomToSelection() {
                    this.store.zoomToSelection();
                }
            };
        });

        /**
         * Componente: Menu Contextual
         */
        Alpine.data('whiteboardContextMenu', function() {
            return {
                visible: false,
                position: { x: 0, y: 0 },
                targetElement: null,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get hasSelection() {
                    return this.store.selectedIds.length > 0;
                },

                get multipleSelected() {
                    return this.store.selectedIds.length > 1;
                },

                show(event, element = null) {
                    event.preventDefault();
                    this.visible = true;
                    this.position = { x: event.clientX, y: event.clientY };
                    this.targetElement = element;

                    if (element && !this.store.selectedIds.includes(element.id)) {
                        this.store.select(element.id);
                    }
                },

                hide() {
                    this.visible = false;
                    this.targetElement = null;
                },

                // Acciones
                copy() {
                    this.store.copy();
                    this.hide();
                },

                cut() {
                    this.store.cut();
                    this.hide();
                },

                paste() {
                    const canvasPoint = this.store.screenToCanvas(this.position.x, this.position.y);
                    this.store.paste({ x: canvasPoint.x, y: canvasPoint.y });
                    this.hide();
                },

                duplicate() {
                    this.store.duplicate();
                    this.hide();
                },

                delete() {
                    this.store.deleteSelected();
                    this.hide();
                },

                bringToFront() {
                    this.store.bringToFront();
                    this.hide();
                },

                sendToBack() {
                    this.store.sendToBack();
                    this.hide();
                },

                selectAll() {
                    this.store.selectAll();
                    this.hide();
                },

                // Alineacion
                alignLeft() {
                    this.store.alignSelection('left');
                    this.hide();
                },

                alignCenter() {
                    this.store.alignSelection('center');
                    this.hide();
                },

                alignRight() {
                    this.store.alignSelection('right');
                    this.hide();
                },

                alignTop() {
                    this.store.alignSelection('top');
                    this.hide();
                },

                alignMiddle() {
                    this.store.alignSelection('middle');
                    this.hide();
                },

                alignBottom() {
                    this.store.alignSelection('bottom');
                    this.hide();
                },

                distributeHorizontal() {
                    this.store.distributeSelection('horizontal');
                    this.hide();
                },

                distributeVertical() {
                    this.store.distributeSelection('vertical');
                    this.hide();
                }
            };
        });

        /**
         * Componente: Modal de Exportacion
         */
        Alpine.data('whiteboardExport', function() {
            return {
                visible: false,
                format: 'png',
                exporting: false,
                exportProgress: 0,

                formats: [
                    { id: 'png', label: 'PNG', icon: '🖼️' },
                    { id: 'svg', label: 'SVG', icon: '📐' },
                    { id: 'json', label: 'JSON', icon: '📄' }
                ],

                options: {
                    scale: 2,
                    background: '#ffffff',
                    padding: 50
                },

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                show() {
                    this.visible = true;
                },

                hide() {
                    this.visible = false;
                },

                selectFormat(format) {
                    this.format = format;
                },

                async export() {
                    if (this.exporting) return;

                    this.exporting = true;
                    this.exportProgress = 0;

                    try {
                        let blob;
                        let filename;

                        switch (this.format) {
                            case 'png':
                                blob = await this.store.exportAsImage('png', this.options);
                                filename = `whiteboard-${Date.now()}.png`;
                                break;

                            case 'svg':
                                blob = this.store.exportAsSVG();
                                filename = `whiteboard-${Date.now()}.svg`;
                                break;

                            case 'json':
                                const jsonData = this.store.exportJSON();
                                blob = new Blob([jsonData], { type: 'application/json' });
                                filename = `whiteboard-${Date.now()}.json`;
                                break;
                        }

                        if (blob) {
                            this.store.downloadFile(blob, filename);
                        }

                        this.hide();
                    } catch (error) {
                        console.error('[VBP Whiteboard] Error exporting:', error);
                        alert('Error al exportar. Intenta de nuevo.');
                    } finally {
                        this.exporting = false;
                    }
                }
            };
        });

        /**
         * Componente: Timer
         */
        Alpine.data('whiteboardTimer', function() {
            return {
                visible: false,
                presets: [60, 180, 300, 600], // 1min, 3min, 5min, 10min

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get timer() {
                    return this.store.timer;
                },

                get isRunning() {
                    return this.timer !== null;
                },

                get remaining() {
                    if (!this.timer) return 0;
                    return this.timer.remaining;
                },

                get formattedTime() {
                    const total = this.remaining;
                    const minutes = Math.floor(total / 60);
                    const seconds = total % 60;
                    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
                },

                get timerClass() {
                    if (!this.timer) return '';
                    if (this.remaining <= 10) return 'vbp-wb-timer--critical';
                    if (this.remaining <= 30) return 'vbp-wb-timer--warning';
                    return '';
                },

                show() {
                    this.visible = true;
                },

                hide() {
                    this.visible = false;
                },

                start(seconds) {
                    this.store.startTimer(seconds);
                },

                stop() {
                    this.store.stopTimer();
                },

                formatPreset(seconds) {
                    if (seconds < 60) return `${seconds}s`;
                    return `${Math.floor(seconds / 60)}m`;
                }
            };
        });

        /**
         * Componente: Panel de Configuracion
         */
        Alpine.data('whiteboardSettings', function() {
            return {
                visible: false,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get gridVisible() {
                    return this.store.gridVisible;
                },

                set gridVisible(value) {
                    this.store.gridVisible = value;
                    this.store.saveSettings();
                },

                get snapToGrid() {
                    return this.store.snapToGrid;
                },

                set snapToGrid(value) {
                    this.store.snapToGrid = value;
                    this.store.saveSettings();
                },

                get gridSize() {
                    return this.store.gridSize;
                },

                set gridSize(value) {
                    this.store.gridSize = parseInt(value, 10);
                    this.store.saveSettings();
                },

                show() {
                    this.visible = true;
                },

                hide() {
                    this.visible = false;
                },

                toggle() {
                    this.visible = !this.visible;
                }
            };
        });

        /**
         * Componente: Ayuda de Atajos
         */
        Alpine.data('whiteboardShortcuts', function() {
            return {
                visible: false,

                shortcuts: [
                    { key: 'V', action: 'Seleccionar' },
                    { key: 'H', action: 'Pan' },
                    { key: 'S', action: 'Sticky' },
                    { key: 'R', action: 'Forma' },
                    { key: 'P', action: 'Dibujar' },
                    { key: 'T', action: 'Texto' },
                    { key: 'L', action: 'Linea' },
                    { key: 'Space', action: 'Pan temp' },
                    { key: 'Del', action: 'Borrar' },
                    { key: 'Ctrl+Z', action: 'Deshacer' },
                    { key: 'Ctrl+C', action: 'Copiar' },
                    { key: 'Ctrl+V', action: 'Pegar' }
                ],

                toggle() {
                    this.visible = !this.visible;
                }
            };
        });

        /**
         * Componente: Votacion
         */
        Alpine.data('whiteboardVoting', function() {
            return {
                showSetup: false,
                duration: 60,
                maxVotes: 3,

                get store() {
                    return Alpine.store('vbpWhiteboard');
                },

                get votingSession() {
                    return this.store.votingSession;
                },

                get isVoting() {
                    return this.votingSession !== null;
                },

                get remainingTime() {
                    if (!this.votingSession) return 0;
                    const elapsed = Date.now() - this.votingSession.startedAt;
                    return Math.max(0, this.votingSession.duration - elapsed);
                },

                showSetupModal() {
                    this.showSetup = true;
                },

                hideSetupModal() {
                    this.showSetup = false;
                },

                startVoting() {
                    this.store.startVotingSession({
                        duration: this.duration * 1000,
                        maxVotes: this.maxVotes
                    });
                    this.hideSetupModal();
                },

                endVoting() {
                    this.store.endVotingSession();
                },

                getVoteResults() {
                    return this.store.elements
                        .filter(el => el.type === 'sticky' && el.votes > 0)
                        .sort((a, b) => b.votes - a.votes);
                }
            };
        });

    });

})();

/**
 * Visual Builder Pro - Whiteboard / FigJam Mode
 *
 * Sistema de pizarra colaborativa estilo FigJam con:
 * - Canvas infinito con pan/zoom
 * - Sticky notes, formas, dibujo libre
 * - Conectores y frames
 * - Votacion y reacciones
 * - Colaboracion en tiempo real
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.6.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Whiteboard] Alpine.js no esta disponible');
        return;
    }

    /**
     * Configuracion por defecto del whiteboard
     */
    const WHITEBOARD_CONFIG = {
        // Grid
        gridSize: 20,
        gridColor: '#e5e7eb',
        gridColorDark: '#374151',

        // Zoom
        minZoom: 0.1,
        maxZoom: 4,
        zoomStep: 0.1,

        // Canvas
        infiniteCanvas: true,
        canvasPadding: 2000,

        // Autosave
        autosaveInterval: 10000,

        // Colaboracion
        cursorUpdateInterval: 50,

        // Sticky defaults
        defaultStickySize: { width: 200, height: 200 },

        // Dibujo
        defaultStrokeWidth: 3,
        smoothingFactor: 0.5
    };

    /**
     * Colores de sticky notes
     */
    const STICKY_COLORS = {
        yellow: { bg: '#fef3c7', border: '#f59e0b', text: '#92400e' },
        purple: { bg: '#e9d5ff', border: '#a855f7', text: '#6b21a8' },
        blue: { bg: '#bfdbfe', border: '#3b82f6', text: '#1e40af' },
        green: { bg: '#bbf7d0', border: '#22c55e', text: '#166534' },
        orange: { bg: '#fed7aa', border: '#f97316', text: '#9a3412' },
        pink: { bg: '#fecdd3', border: '#f43f5e', text: '#be123c' },
        gray: { bg: '#e5e7eb', border: '#6b7280', text: '#374151' },
        teal: { bg: '#99f6e4', border: '#14b8a6', text: '#115e59' }
    };

    /**
     * Formas disponibles
     */
    const SHAPES = {
        rectangle: {
            name: 'Rectangulo',
            icon: '▭',
            defaultSize: { width: 120, height: 80 }
        },
        square: {
            name: 'Cuadrado',
            icon: '□',
            defaultSize: { width: 100, height: 100 }
        },
        ellipse: {
            name: 'Elipse',
            icon: '⬭',
            defaultSize: { width: 120, height: 80 }
        },
        circle: {
            name: 'Circulo',
            icon: '○',
            defaultSize: { width: 100, height: 100 }
        },
        diamond: {
            name: 'Rombo',
            icon: '◇',
            defaultSize: { width: 100, height: 100 }
        },
        triangle: {
            name: 'Triangulo',
            icon: '△',
            defaultSize: { width: 100, height: 87 }
        },
        star: {
            name: 'Estrella',
            icon: '☆',
            defaultSize: { width: 100, height: 100 }
        },
        arrow: {
            name: 'Flecha',
            icon: '➔',
            defaultSize: { width: 150, height: 60 }
        },
        hexagon: {
            name: 'Hexagono',
            icon: '⬡',
            defaultSize: { width: 100, height: 87 }
        },
        cloud: {
            name: 'Nube',
            icon: '☁',
            defaultSize: { width: 150, height: 100 }
        }
    };

    /**
     * Stamps/Stickers disponibles
     */
    const STAMPS = {
        reactions: ['👍', '👎', '❤️', '🎉', '🤔', '👀', '🔥', '⭐', '💡', '✨'],
        status: ['✅', '❌', '⏳', '🚧', '📌', '🎯', '⚡', '🚀', '💪', '🏆'],
        priority: ['🔴', '🟡', '🟢', '🔵', '⚪', '🟣', '🟠', '⬛'],
        emotions: ['😀', '😢', '😡', '😱', '🤯', '🥳', '😴', '🤷'],
        objects: ['📝', '📌', '📎', '🔗', '📋', '📦', '🎨', '💬']
    };

    /**
     * Estilos de conectores
     */
    const CONNECTOR_STYLES = {
        straight: { name: 'Recto', icon: '—' },
        curved: { name: 'Curvo', icon: '⌒' },
        elbow: { name: 'Codo', icon: '⌐' },
        step: { name: 'Escalon', icon: '⌊' }
    };

    /**
     * Plantillas de whiteboard predefinidas
     */
    const WHITEBOARD_TEMPLATES = {
        blank: {
            id: 'blank',
            name: 'Lienzo en blanco',
            description: 'Empieza desde cero',
            icon: '📄',
            elements: []
        },
        brainstorm: {
            id: 'brainstorm',
            name: 'Lluvia de ideas',
            description: 'Sesion de brainstorming con pregunta central',
            icon: '💡',
            elements: [
                {
                    type: 'sticky',
                    position: { x: 400, y: 300 },
                    size: { width: 300, height: 150 },
                    color: 'yellow',
                    text: '¿Cual es la pregunta principal?',
                    fontSize: 24
                },
                {
                    type: 'text',
                    position: { x: 350, y: 200 },
                    text: 'Agrega tus ideas en sticky notes alrededor',
                    fontSize: 16,
                    color: '#6b7280'
                }
            ]
        },
        kanban: {
            id: 'kanban',
            name: 'Kanban',
            description: 'Tablero con columnas To Do, In Progress, Done',
            icon: '📋',
            elements: [
                { type: 'frame', position: { x: 50, y: 100 }, size: { width: 300, height: 500 }, title: 'Por hacer', color: '#fef3c7' },
                { type: 'frame', position: { x: 370, y: 100 }, size: { width: 300, height: 500 }, title: 'En progreso', color: '#bfdbfe' },
                { type: 'frame', position: { x: 690, y: 100 }, size: { width: 300, height: 500 }, title: 'Completado', color: '#bbf7d0' }
            ]
        },
        userJourney: {
            id: 'user-journey',
            name: 'User Journey',
            description: 'Mapa de viaje del usuario con etapas',
            icon: '🗺️',
            elements: [
                { type: 'frame', position: { x: 50, y: 150 }, size: { width: 180, height: 400 }, title: 'Descubrimiento', color: '#e9d5ff' },
                { type: 'frame', position: { x: 250, y: 150 }, size: { width: 180, height: 400 }, title: 'Consideracion', color: '#bfdbfe' },
                { type: 'frame', position: { x: 450, y: 150 }, size: { width: 180, height: 400 }, title: 'Decision', color: '#bbf7d0' },
                { type: 'frame', position: { x: 650, y: 150 }, size: { width: 180, height: 400 }, title: 'Compra', color: '#fef3c7' },
                { type: 'frame', position: { x: 850, y: 150 }, size: { width: 180, height: 400 }, title: 'Fidelizacion', color: '#fecdd3' },
                { type: 'text', position: { x: 50, y: 100 }, text: 'User Journey Map', fontSize: 24, fontWeight: 'bold' }
            ]
        },
        swot: {
            id: 'swot',
            name: 'Analisis SWOT',
            description: 'Matriz de fortalezas, debilidades, oportunidades y amenazas',
            icon: '📊',
            elements: [
                { type: 'frame', position: { x: 50, y: 100 }, size: { width: 350, height: 300 }, title: 'Fortalezas', color: '#bbf7d0' },
                { type: 'frame', position: { x: 420, y: 100 }, size: { width: 350, height: 300 }, title: 'Debilidades', color: '#fecdd3' },
                { type: 'frame', position: { x: 50, y: 420 }, size: { width: 350, height: 300 }, title: 'Oportunidades', color: '#bfdbfe' },
                { type: 'frame', position: { x: 420, y: 420 }, size: { width: 350, height: 300 }, title: 'Amenazas', color: '#fed7aa' }
            ]
        },
        retrospective: {
            id: 'retrospective',
            name: 'Retrospectiva',
            description: 'Que salio bien, que mejorar, acciones',
            icon: '🔄',
            elements: [
                { type: 'frame', position: { x: 50, y: 100 }, size: { width: 280, height: 450 }, title: '¿Que salio bien?', color: '#bbf7d0' },
                { type: 'frame', position: { x: 350, y: 100 }, size: { width: 280, height: 450 }, title: '¿Que mejorar?', color: '#fecdd3' },
                { type: 'frame', position: { x: 650, y: 100 }, size: { width: 280, height: 450 }, title: 'Acciones', color: '#bfdbfe' }
            ]
        },
        mindMap: {
            id: 'mind-map',
            name: 'Mapa mental',
            description: 'Nodo central con ramas de ideas',
            icon: '🧠',
            elements: [
                { type: 'shape', shape: 'ellipse', position: { x: 400, y: 300 }, size: { width: 200, height: 100 }, fill: '#bfdbfe', text: 'Idea Central', textColor: '#1e40af' }
            ]
        },
        flowchart: {
            id: 'flowchart',
            name: 'Diagrama de flujo',
            description: 'Formas conectadas para procesos',
            icon: '🔀',
            elements: [
                { type: 'shape', shape: 'ellipse', position: { x: 400, y: 100 }, size: { width: 120, height: 60 }, fill: '#bbf7d0', text: 'Inicio' },
                { type: 'shape', shape: 'rectangle', position: { x: 370, y: 200 }, size: { width: 180, height: 80 }, fill: '#bfdbfe', text: 'Proceso' },
                { type: 'shape', shape: 'diamond', position: { x: 400, y: 350 }, size: { width: 120, height: 120 }, fill: '#fef3c7', text: '¿Decision?' },
                { type: 'connector', fromPoint: { x: 460, y: 160 }, toPoint: { x: 460, y: 200 }, endArrow: true },
                { type: 'connector', fromPoint: { x: 460, y: 280 }, toPoint: { x: 460, y: 350 }, endArrow: true }
            ]
        },
        empathyMap: {
            id: 'empathy-map',
            name: 'Mapa de empatia',
            description: 'Entiende a tu usuario: piensa, siente, dice, hace',
            icon: '💭',
            elements: [
                { type: 'shape', shape: 'circle', position: { x: 350, y: 280 }, size: { width: 120, height: 120 }, fill: '#e9d5ff', text: 'Usuario' },
                { type: 'frame', position: { x: 50, y: 50 }, size: { width: 250, height: 200 }, title: 'Piensa', color: '#fef3c7' },
                { type: 'frame', position: { x: 520, y: 50 }, size: { width: 250, height: 200 }, title: 'Siente', color: '#fecdd3' },
                { type: 'frame', position: { x: 50, y: 430 }, size: { width: 250, height: 200 }, title: 'Dice', color: '#bfdbfe' },
                { type: 'frame', position: { x: 520, y: 430 }, size: { width: 250, height: 200 }, title: 'Hace', color: '#bbf7d0' }
            ]
        }
    };

    /**
     * Generador de IDs unicos
     */
    function generateElementId(prefix = 'wb') {
        return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Store principal del Whiteboard
     */
    document.addEventListener('alpine:init', function() {

        Alpine.store('vbpWhiteboard', {
            // Estado principal
            enabled: false,
            initialized: false,

            // Canvas
            canvasRef: null,
            svgRef: null,

            // Viewport (pan y zoom)
            viewport: {
                x: 0,
                y: 0,
                zoom: 1
            },

            // Herramienta activa
            tool: 'select', // select, pan, sticky, shape, draw, text, connector, stamp, frame, eraser

            // Submenu de herramienta (ej: tipo de forma)
            toolOptions: {
                shape: 'rectangle',
                stickyColor: 'yellow',
                stampCategory: 'reactions',
                stamp: '👍',
                connectorStyle: 'straight',
                drawColor: '#000000',
                drawWidth: 3
            },

            // Elementos del whiteboard
            elements: [],

            // Seleccion
            selectedIds: [],
            hoveredId: null,

            // Estado de interaccion
            interaction: {
                isDragging: false,
                isPanning: false,
                isDrawing: false,
                isResizing: false,
                isConnecting: false,
                dragStart: null,
                dragCurrent: null,
                resizeHandle: null,
                drawPoints: [],
                connectFrom: null
            },

            // Grid
            gridVisible: true,
            snapToGrid: true,
            gridSize: WHITEBOARD_CONFIG.gridSize,

            // Historial
            history: {
                past: [],
                future: []
            },
            maxHistorySize: 50,

            // Colaboracion
            collaborators: {},
            remoteCursors: {},

            // Votacion
            votingSession: null,

            // Chat de sesion
            chatMessages: [],
            chatVisible: false,

            // Timer
            timer: null,

            // Clipboard
            clipboard: [],

            // Datos del documento
            documentId: null,
            documentTitle: 'Sin titulo',
            isDirty: false,
            lastSaved: null,

            // Configuracion
            config: { ...WHITEBOARD_CONFIG },

            // ============================================
            // INICIALIZACION
            // ============================================

            init() {
                if (this.initialized) return;

                // Cargar configuracion guardada
                this.loadSettings();

                // Inicializar listeners
                this.setupKeyboardShortcuts();

                this.initialized = true;

                console.log('[VBP Whiteboard] Inicializado');
            },

            /**
             * Activar/desactivar modo whiteboard
             */
            toggle() {
                this.enabled = !this.enabled;

                if (this.enabled) {
                    this.init();
                    document.body.classList.add('vbp-whiteboard-active');
                    this.dispatchEvent('whiteboard:enabled');
                } else {
                    document.body.classList.remove('vbp-whiteboard-active');
                    this.dispatchEvent('whiteboard:disabled');
                }

                return this.enabled;
            },

            /**
             * Cargar configuracion guardada
             */
            loadSettings() {
                try {
                    const savedSettings = localStorage.getItem('vbp_whiteboard_settings');
                    if (savedSettings) {
                        const settings = JSON.parse(savedSettings);
                        this.gridVisible = settings.gridVisible ?? true;
                        this.snapToGrid = settings.snapToGrid ?? true;
                        this.gridSize = settings.gridSize ?? WHITEBOARD_CONFIG.gridSize;
                    }
                } catch (error) {
                    console.warn('[VBP Whiteboard] Error cargando configuracion:', error);
                }
            },

            /**
             * Guardar configuracion
             */
            saveSettings() {
                try {
                    const settings = {
                        gridVisible: this.gridVisible,
                        snapToGrid: this.snapToGrid,
                        gridSize: this.gridSize
                    };
                    localStorage.setItem('vbp_whiteboard_settings', JSON.stringify(settings));
                } catch (error) {
                    console.warn('[VBP Whiteboard] Error guardando configuracion:', error);
                }
            },

            // ============================================
            // VIEWPORT (Pan y Zoom)
            // ============================================

            /**
             * Establecer zoom
             */
            setZoom(zoom, centerPoint = null) {
                const oldZoom = this.viewport.zoom;
                const newZoom = Math.max(
                    this.config.minZoom,
                    Math.min(this.config.maxZoom, zoom)
                );

                if (newZoom === oldZoom) return;

                // Si hay punto central, ajustar pan para mantenerlo centrado
                if (centerPoint) {
                    const zoomRatio = newZoom / oldZoom;
                    this.viewport.x = centerPoint.x - (centerPoint.x - this.viewport.x) * zoomRatio;
                    this.viewport.y = centerPoint.y - (centerPoint.y - this.viewport.y) * zoomRatio;
                }

                this.viewport.zoom = newZoom;
                this.dispatchEvent('whiteboard:zoom', { zoom: newZoom });
            },

            /**
             * Zoom in
             */
            zoomIn() {
                this.setZoom(this.viewport.zoom + this.config.zoomStep);
            },

            /**
             * Zoom out
             */
            zoomOut() {
                this.setZoom(this.viewport.zoom - this.config.zoomStep);
            },

            /**
             * Zoom al 100%
             */
            zoomReset() {
                this.setZoom(1);
                this.viewport.x = 0;
                this.viewport.y = 0;
            },

            /**
             * Ajustar zoom para ver todos los elementos
             */
            zoomToFit() {
                if (this.elements.length === 0) {
                    this.zoomReset();
                    return;
                }

                const bounds = this.getElementsBounds(this.elements);
                const canvasRect = this.canvasRef?.getBoundingClientRect();

                if (!canvasRect) return;

                const padding = 100;
                const scaleX = (canvasRect.width - padding * 2) / bounds.width;
                const scaleY = (canvasRect.height - padding * 2) / bounds.height;
                const newZoom = Math.min(scaleX, scaleY, 1);

                this.viewport.zoom = Math.max(this.config.minZoom, newZoom);
                this.viewport.x = (canvasRect.width / 2 - bounds.centerX * this.viewport.zoom);
                this.viewport.y = (canvasRect.height / 2 - bounds.centerY * this.viewport.zoom);
            },

            /**
             * Zoom a seleccion
             */
            zoomToSelection() {
                const selected = this.getSelectedElements();
                if (selected.length === 0) return;

                const bounds = this.getElementsBounds(selected);
                const canvasRect = this.canvasRef?.getBoundingClientRect();

                if (!canvasRect) return;

                const padding = 100;
                const scaleX = (canvasRect.width - padding * 2) / bounds.width;
                const scaleY = (canvasRect.height - padding * 2) / bounds.height;
                const newZoom = Math.min(scaleX, scaleY, 2);

                this.viewport.zoom = Math.max(this.config.minZoom, newZoom);
                this.viewport.x = (canvasRect.width / 2 - bounds.centerX * this.viewport.zoom);
                this.viewport.y = (canvasRect.height / 2 - bounds.centerY * this.viewport.zoom);
            },

            /**
             * Pan viewport
             */
            panBy(deltaX, deltaY) {
                this.viewport.x += deltaX;
                this.viewport.y += deltaY;
            },

            /**
             * Obtener bounds de elementos
             */
            getElementsBounds(elements) {
                if (elements.length === 0) {
                    return { x: 0, y: 0, width: 0, height: 0, centerX: 0, centerY: 0 };
                }

                let minX = Infinity;
                let minY = Infinity;
                let maxX = -Infinity;
                let maxY = -Infinity;

                elements.forEach(el => {
                    const position = el.position || { x: 0, y: 0 };
                    const size = el.size || { width: 100, height: 100 };

                    minX = Math.min(minX, position.x);
                    minY = Math.min(minY, position.y);
                    maxX = Math.max(maxX, position.x + size.width);
                    maxY = Math.max(maxY, position.y + size.height);
                });

                return {
                    x: minX,
                    y: minY,
                    width: maxX - minX,
                    height: maxY - minY,
                    centerX: (minX + maxX) / 2,
                    centerY: (minY + maxY) / 2
                };
            },

            // ============================================
            // HERRAMIENTAS
            // ============================================

            /**
             * Establecer herramienta activa
             */
            setTool(tool, options = {}) {
                this.tool = tool;

                // Cancelar interacciones pendientes
                this.cancelInteraction();

                // Aplicar opciones de herramienta
                if (options) {
                    Object.assign(this.toolOptions, options);
                }

                // Cambiar cursor segun herramienta
                this.updateCursor();

                this.dispatchEvent('whiteboard:tool-change', { tool, options });
            },

            /**
             * Actualizar cursor segun herramienta
             */
            updateCursor() {
                const cursors = {
                    select: 'default',
                    pan: 'grab',
                    sticky: 'crosshair',
                    shape: 'crosshair',
                    draw: 'crosshair',
                    text: 'text',
                    connector: 'crosshair',
                    stamp: 'copy',
                    frame: 'crosshair',
                    eraser: 'cell'
                };

                if (this.canvasRef) {
                    this.canvasRef.style.cursor = cursors[this.tool] || 'default';
                }
            },

            /**
             * Cancelar interaccion actual
             */
            cancelInteraction() {
                this.interaction = {
                    isDragging: false,
                    isPanning: false,
                    isDrawing: false,
                    isResizing: false,
                    isConnecting: false,
                    dragStart: null,
                    dragCurrent: null,
                    resizeHandle: null,
                    drawPoints: [],
                    connectFrom: null
                };
            },

            // ============================================
            // ELEMENTOS
            // ============================================

            /**
             * Agregar elemento
             */
            addElement(elementData) {
                const element = {
                    id: generateElementId(elementData.type),
                    createdAt: Date.now(),
                    createdBy: this.getCurrentUserId(),
                    ...elementData
                };

                // Snap to grid si esta activo
                if (this.snapToGrid && element.position) {
                    element.position = this.snapPosition(element.position);
                }

                this.pushToHistory('add', { element });
                this.elements.push(element);
                this.isDirty = true;

                this.dispatchEvent('whiteboard:element-add', { element });

                return element;
            },

            /**
             * Eliminar elemento
             */
            removeElement(elementId) {
                const index = this.elements.findIndex(el => el.id === elementId);
                if (index === -1) return false;

                const element = this.elements[index];
                this.pushToHistory('remove', { element, index });

                this.elements.splice(index, 1);
                this.selectedIds = this.selectedIds.filter(id => id !== elementId);
                this.isDirty = true;

                // Eliminar conectores relacionados
                this.removeConnectorsForElement(elementId);

                this.dispatchEvent('whiteboard:element-remove', { elementId, element });

                return true;
            },

            /**
             * Actualizar elemento
             */
            updateElement(elementId, updates, skipHistory = false) {
                const element = this.getElementById(elementId);
                if (!element) return false;

                const oldValues = {};
                Object.keys(updates).forEach(key => {
                    oldValues[key] = JSON.parse(JSON.stringify(element[key]));
                });

                if (!skipHistory) {
                    this.pushToHistory('update', { elementId, oldValues, newValues: updates });
                }

                Object.assign(element, updates);
                element.updatedAt = Date.now();
                element.updatedBy = this.getCurrentUserId();

                this.isDirty = true;

                this.dispatchEvent('whiteboard:element-update', { elementId, updates });

                return true;
            },

            /**
             * Obtener elemento por ID
             */
            getElementById(elementId) {
                return this.elements.find(el => el.id === elementId);
            },

            /**
             * Obtener elementos seleccionados
             */
            getSelectedElements() {
                return this.elements.filter(el => this.selectedIds.includes(el.id));
            },

            /**
             * Snap posicion a grid
             */
            snapPosition(position) {
                return {
                    x: Math.round(position.x / this.gridSize) * this.gridSize,
                    y: Math.round(position.y / this.gridSize) * this.gridSize
                };
            },

            /**
             * Eliminar conectores de un elemento
             */
            removeConnectorsForElement(elementId) {
                const connectorsToRemove = this.elements.filter(el =>
                    el.type === 'connector' &&
                    (el.from?.elementId === elementId || el.to?.elementId === elementId)
                );

                connectorsToRemove.forEach(connector => {
                    this.removeElement(connector.id);
                });
            },

            // ============================================
            // ELEMENTOS ESPECIFICOS
            // ============================================

            /**
             * Crear sticky note
             */
            createSticky(position, options = {}) {
                return this.addElement({
                    type: 'sticky',
                    position: position,
                    size: options.size || { ...WHITEBOARD_CONFIG.defaultStickySize },
                    color: options.color || this.toolOptions.stickyColor,
                    text: options.text || '',
                    fontSize: options.fontSize || 16,
                    votes: 0,
                    voters: [],
                    comments: []
                });
            },

            /**
             * Crear forma
             */
            createShape(position, options = {}) {
                const shapeType = options.shape || this.toolOptions.shape;
                const shapeConfig = SHAPES[shapeType] || SHAPES.rectangle;

                return this.addElement({
                    type: 'shape',
                    shape: shapeType,
                    position: position,
                    size: options.size || { ...shapeConfig.defaultSize },
                    fill: options.fill || '#ffffff',
                    stroke: options.stroke || '#000000',
                    strokeWidth: options.strokeWidth || 2,
                    text: options.text || '',
                    textColor: options.textColor || '#000000',
                    cornerRadius: options.cornerRadius || 0,
                    rotation: options.rotation || 0
                });
            },

            /**
             * Crear texto
             */
            createText(position, options = {}) {
                return this.addElement({
                    type: 'text',
                    position: position,
                    text: options.text || 'Texto',
                    fontSize: options.fontSize || 16,
                    fontWeight: options.fontWeight || 'normal',
                    fontStyle: options.fontStyle || 'normal',
                    color: options.color || '#000000',
                    align: options.align || 'left'
                });
            },

            /**
             * Crear dibujo libre
             */
            createDrawing(points, options = {}) {
                if (points.length < 2) return null;

                return this.addElement({
                    type: 'draw',
                    points: points,
                    stroke: options.stroke || this.toolOptions.drawColor,
                    strokeWidth: options.strokeWidth || this.toolOptions.drawWidth,
                    smoothing: options.smoothing || this.config.smoothingFactor
                });
            },

            /**
             * Crear conector
             */
            createConnector(from, to, options = {}) {
                return this.addElement({
                    type: 'connector',
                    from: from, // { elementId, anchor } o null
                    to: to,     // { elementId, anchor } o null
                    fromPoint: from.point || null, // Para puntos libres
                    toPoint: to.point || null,
                    style: options.style || this.toolOptions.connectorStyle,
                    stroke: options.stroke || '#000000',
                    strokeWidth: options.strokeWidth || 2,
                    startArrow: options.startArrow || false,
                    endArrow: options.endArrow || true,
                    label: options.label || ''
                });
            },

            /**
             * Crear stamp
             */
            createStamp(position, options = {}) {
                return this.addElement({
                    type: 'stamp',
                    position: position,
                    emoji: options.emoji || this.toolOptions.stamp,
                    size: options.size || 48
                });
            },

            /**
             * Crear frame
             */
            createFrame(position, options = {}) {
                return this.addElement({
                    type: 'frame',
                    position: position,
                    size: options.size || { width: 400, height: 300 },
                    title: options.title || 'Sin titulo',
                    color: options.color || '#f3f4f6',
                    children: []
                });
            },

            // ============================================
            // SELECCION
            // ============================================

            /**
             * Seleccionar elemento
             */
            select(elementId, addToSelection = false) {
                if (addToSelection) {
                    if (!this.selectedIds.includes(elementId)) {
                        this.selectedIds.push(elementId);
                    }
                } else {
                    this.selectedIds = elementId ? [elementId] : [];
                }

                this.dispatchEvent('whiteboard:selection-change', { selectedIds: this.selectedIds });
            },

            /**
             * Deseleccionar todo
             */
            deselectAll() {
                this.selectedIds = [];
                this.dispatchEvent('whiteboard:selection-change', { selectedIds: [] });
            },

            /**
             * Seleccionar todo
             */
            selectAll() {
                this.selectedIds = this.elements.map(el => el.id);
                this.dispatchEvent('whiteboard:selection-change', { selectedIds: this.selectedIds });
            },

            /**
             * Seleccionar elementos en area
             */
            selectInArea(rect) {
                const selected = this.elements.filter(el => {
                    if (el.type === 'connector' || el.type === 'draw') return false;

                    const elementRect = {
                        x: el.position.x,
                        y: el.position.y,
                        width: el.size?.width || 100,
                        height: el.size?.height || 100
                    };

                    return this.rectsIntersect(rect, elementRect);
                });

                this.selectedIds = selected.map(el => el.id);
                this.dispatchEvent('whiteboard:selection-change', { selectedIds: this.selectedIds });
            },

            /**
             * Verificar si dos rectangulos se intersectan
             */
            rectsIntersect(rectA, rectB) {
                return !(
                    rectA.x + rectA.width < rectB.x ||
                    rectB.x + rectB.width < rectA.x ||
                    rectA.y + rectA.height < rectB.y ||
                    rectB.y + rectB.height < rectA.y
                );
            },

            // ============================================
            // HISTORIAL (Undo/Redo)
            // ============================================

            /**
             * Agregar accion al historial
             */
            pushToHistory(action, data) {
                this.history.past.push({
                    action,
                    data,
                    timestamp: Date.now()
                });

                // Limpiar futuro
                this.history.future = [];

                // Limitar tamano
                if (this.history.past.length > this.maxHistorySize) {
                    this.history.past.shift();
                }
            },

            /**
             * Deshacer
             */
            undo() {
                if (this.history.past.length === 0) return false;

                const entry = this.history.past.pop();
                this.history.future.unshift(entry);

                // Aplicar inversa
                this.applyHistoryAction(entry, true);

                this.dispatchEvent('whiteboard:undo', { entry });
                return true;
            },

            /**
             * Rehacer
             */
            redo() {
                if (this.history.future.length === 0) return false;

                const entry = this.history.future.shift();
                this.history.past.push(entry);

                // Aplicar accion
                this.applyHistoryAction(entry, false);

                this.dispatchEvent('whiteboard:redo', { entry });
                return true;
            },

            /**
             * Aplicar accion de historial
             */
            applyHistoryAction(entry, isUndo) {
                const { action, data } = entry;

                switch (action) {
                    case 'add':
                        if (isUndo) {
                            const index = this.elements.findIndex(el => el.id === data.element.id);
                            if (index !== -1) this.elements.splice(index, 1);
                        } else {
                            this.elements.push(data.element);
                        }
                        break;

                    case 'remove':
                        if (isUndo) {
                            this.elements.splice(data.index, 0, data.element);
                        } else {
                            const index = this.elements.findIndex(el => el.id === data.element.id);
                            if (index !== -1) this.elements.splice(index, 1);
                        }
                        break;

                    case 'update':
                        const element = this.getElementById(data.elementId);
                        if (element) {
                            const valuesToApply = isUndo ? data.oldValues : data.newValues;
                            Object.assign(element, valuesToApply);
                        }
                        break;
                }
            },

            get canUndo() {
                return this.history.past.length > 0;
            },

            get canRedo() {
                return this.history.future.length > 0;
            },

            // ============================================
            // CLIPBOARD
            // ============================================

            /**
             * Copiar seleccion
             */
            copy() {
                const selected = this.getSelectedElements();
                if (selected.length === 0) return;

                this.clipboard = selected.map(el => ({
                    ...JSON.parse(JSON.stringify(el)),
                    originalId: el.id
                }));

                this.dispatchEvent('whiteboard:copy', { count: this.clipboard.length });
            },

            /**
             * Cortar seleccion
             */
            cut() {
                this.copy();
                this.deleteSelected();
                this.dispatchEvent('whiteboard:cut', { count: this.clipboard.length });
            },

            /**
             * Pegar
             */
            paste(offset = { x: 20, y: 20 }) {
                if (this.clipboard.length === 0) return;

                const pastedIds = [];
                const idMapping = {};

                this.clipboard.forEach(item => {
                    const newElement = {
                        ...item,
                        id: generateElementId(item.type),
                        position: item.position ? {
                            x: item.position.x + offset.x,
                            y: item.position.y + offset.y
                        } : null,
                        createdAt: Date.now()
                    };

                    delete newElement.originalId;

                    idMapping[item.originalId] = newElement.id;
                    this.elements.push(newElement);
                    pastedIds.push(newElement.id);
                });

                // Actualizar referencias de conectores
                this.elements.forEach(el => {
                    if (el.type === 'connector') {
                        if (el.from?.elementId && idMapping[el.from.elementId]) {
                            el.from.elementId = idMapping[el.from.elementId];
                        }
                        if (el.to?.elementId && idMapping[el.to.elementId]) {
                            el.to.elementId = idMapping[el.to.elementId];
                        }
                    }
                });

                this.selectedIds = pastedIds;
                this.isDirty = true;

                this.dispatchEvent('whiteboard:paste', { count: pastedIds.length, ids: pastedIds });
            },

            /**
             * Duplicar seleccion
             */
            duplicate() {
                this.copy();
                this.paste({ x: 30, y: 30 });
            },

            /**
             * Eliminar seleccionados
             */
            deleteSelected() {
                const toDelete = [...this.selectedIds];
                toDelete.forEach(id => this.removeElement(id));
                this.selectedIds = [];
            },

            // ============================================
            // ORDENAMIENTO (Z-Index)
            // ============================================

            /**
             * Traer al frente
             */
            bringToFront() {
                const selected = this.getSelectedElements();
                selected.forEach(el => {
                    const index = this.elements.findIndex(e => e.id === el.id);
                    if (index !== -1) {
                        this.elements.splice(index, 1);
                        this.elements.push(el);
                    }
                });
                this.isDirty = true;
            },

            /**
             * Enviar al fondo
             */
            sendToBack() {
                const selected = this.getSelectedElements();
                selected.reverse().forEach(el => {
                    const index = this.elements.findIndex(e => e.id === el.id);
                    if (index !== -1) {
                        this.elements.splice(index, 1);
                        this.elements.unshift(el);
                    }
                });
                this.isDirty = true;
            },

            /**
             * Subir una posicion
             */
            bringForward() {
                const selected = this.getSelectedElements();
                selected.forEach(el => {
                    const index = this.elements.findIndex(e => e.id === el.id);
                    if (index !== -1 && index < this.elements.length - 1) {
                        [this.elements[index], this.elements[index + 1]] =
                        [this.elements[index + 1], this.elements[index]];
                    }
                });
                this.isDirty = true;
            },

            /**
             * Bajar una posicion
             */
            sendBackward() {
                const selected = this.getSelectedElements();
                selected.forEach(el => {
                    const index = this.elements.findIndex(e => e.id === el.id);
                    if (index > 0) {
                        [this.elements[index], this.elements[index - 1]] =
                        [this.elements[index - 1], this.elements[index]];
                    }
                });
                this.isDirty = true;
            },

            // ============================================
            // ALINEAMIENTO
            // ============================================

            /**
             * Alinear seleccion
             */
            alignSelection(alignment) {
                const selected = this.getSelectedElements();
                if (selected.length < 2) return;

                const bounds = this.getElementsBounds(selected);

                selected.forEach(el => {
                    if (!el.position || !el.size) return;

                    switch (alignment) {
                        case 'left':
                            el.position.x = bounds.x;
                            break;
                        case 'center':
                            el.position.x = bounds.centerX - el.size.width / 2;
                            break;
                        case 'right':
                            el.position.x = bounds.x + bounds.width - el.size.width;
                            break;
                        case 'top':
                            el.position.y = bounds.y;
                            break;
                        case 'middle':
                            el.position.y = bounds.centerY - el.size.height / 2;
                            break;
                        case 'bottom':
                            el.position.y = bounds.y + bounds.height - el.size.height;
                            break;
                    }
                });

                this.isDirty = true;
            },

            /**
             * Distribuir seleccion
             */
            distributeSelection(direction) {
                const selected = this.getSelectedElements().filter(el => el.position && el.size);
                if (selected.length < 3) return;

                const isHorizontal = direction === 'horizontal';
                const posKey = isHorizontal ? 'x' : 'y';
                const sizeKey = isHorizontal ? 'width' : 'height';

                // Ordenar por posicion
                selected.sort((a, b) => a.position[posKey] - b.position[posKey]);

                const first = selected[0];
                const last = selected[selected.length - 1];

                const totalSpace = last.position[posKey] - first.position[posKey];
                const spacing = totalSpace / (selected.length - 1);

                selected.forEach((el, index) => {
                    if (index === 0 || index === selected.length - 1) return;
                    el.position[posKey] = first.position[posKey] + spacing * index;
                });

                this.isDirty = true;
            },

            // ============================================
            // VOTACION
            // ============================================

            /**
             * Votar por un sticky
             */
            voteForSticky(stickyId) {
                const sticky = this.getElementById(stickyId);
                if (!sticky || sticky.type !== 'sticky') return false;

                const userId = this.getCurrentUserId();
                const voters = sticky.voters || [];

                if (voters.includes(userId)) {
                    // Quitar voto
                    sticky.voters = voters.filter(id => id !== userId);
                    sticky.votes = (sticky.votes || 1) - 1;
                } else {
                    // Agregar voto
                    sticky.voters.push(userId);
                    sticky.votes = (sticky.votes || 0) + 1;
                }

                this.isDirty = true;
                this.dispatchEvent('whiteboard:vote', { stickyId, votes: sticky.votes });

                return true;
            },

            /**
             * Iniciar sesion de votacion
             */
            startVotingSession(options = {}) {
                this.votingSession = {
                    id: generateElementId('vote'),
                    startedAt: Date.now(),
                    duration: options.duration || 60000, // 1 minuto por defecto
                    maxVotesPerUser: options.maxVotes || 3,
                    voters: {}
                };

                // Resetear votos de stickies
                this.elements.filter(el => el.type === 'sticky').forEach(sticky => {
                    sticky.votes = 0;
                    sticky.voters = [];
                });

                this.dispatchEvent('whiteboard:voting-start', { session: this.votingSession });
            },

            /**
             * Terminar sesion de votacion
             */
            endVotingSession() {
                if (!this.votingSession) return;

                const results = this.elements
                    .filter(el => el.type === 'sticky' && el.votes > 0)
                    .sort((a, b) => b.votes - a.votes);

                this.dispatchEvent('whiteboard:voting-end', {
                    session: this.votingSession,
                    results
                });

                this.votingSession = null;
            },

            // ============================================
            // TIMER
            // ============================================

            /**
             * Iniciar timer
             */
            startTimer(seconds) {
                this.stopTimer();

                this.timer = {
                    total: seconds,
                    remaining: seconds,
                    startedAt: Date.now(),
                    interval: setInterval(() => {
                        this.timer.remaining--;

                        if (this.timer.remaining <= 0) {
                            this.stopTimer();
                            this.dispatchEvent('whiteboard:timer-end');
                        }
                    }, 1000)
                };

                this.dispatchEvent('whiteboard:timer-start', { seconds });
            },

            /**
             * Detener timer
             */
            stopTimer() {
                if (this.timer?.interval) {
                    clearInterval(this.timer.interval);
                }
                this.timer = null;
            },

            // ============================================
            // PLANTILLAS
            // ============================================

            /**
             * Cargar plantilla
             */
            loadTemplate(templateId) {
                const template = WHITEBOARD_TEMPLATES[templateId];
                if (!template) return false;

                // Limpiar canvas actual
                if (this.elements.length > 0) {
                    const confirmed = window.confirm('Esto reemplazara el contenido actual. ¿Continuar?');
                    if (!confirmed) return false;
                }

                this.elements = [];
                this.selectedIds = [];
                this.history = { past: [], future: [] };

                // Crear elementos de la plantilla
                template.elements.forEach(elementData => {
                    this.addElement({ ...elementData });
                });

                this.zoomToFit();
                this.isDirty = true;

                this.dispatchEvent('whiteboard:template-load', { templateId });

                return true;
            },

            /**
             * Obtener plantillas disponibles
             */
            getTemplates() {
                return Object.values(WHITEBOARD_TEMPLATES);
            },

            // ============================================
            // EXPORTACION
            // ============================================

            /**
             * Exportar como imagen
             */
            async exportAsImage(format = 'png', options = {}) {
                const svgElement = this.svgRef;
                if (!svgElement) {
                    console.error('[VBP Whiteboard] No se encontro el SVG');
                    return null;
                }

                const bounds = this.getElementsBounds(this.elements);
                const padding = options.padding || 50;

                // Crear canvas temporal
                const canvas = document.createElement('canvas');
                const scale = options.scale || 2;

                canvas.width = (bounds.width + padding * 2) * scale;
                canvas.height = (bounds.height + padding * 2) * scale;

                const ctx = canvas.getContext('2d');
                ctx.fillStyle = options.background || '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Clonar y serializar SVG
                const svgClone = svgElement.cloneNode(true);
                svgClone.setAttribute('viewBox', `${bounds.x - padding} ${bounds.y - padding} ${bounds.width + padding * 2} ${bounds.height + padding * 2}`);

                const svgData = new XMLSerializer().serializeToString(svgClone);
                const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                const url = URL.createObjectURL(svgBlob);

                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        URL.revokeObjectURL(url);

                        if (format === 'png') {
                            canvas.toBlob(blob => resolve(blob), 'image/png');
                        } else if (format === 'jpeg') {
                            canvas.toBlob(blob => resolve(blob), 'image/jpeg', options.quality || 0.9);
                        } else {
                            resolve(canvas.toDataURL(`image/${format}`));
                        }
                    };
                    img.onerror = reject;
                    img.src = url;
                });
            },

            /**
             * Exportar como SVG
             */
            exportAsSVG() {
                const svgElement = this.svgRef;
                if (!svgElement) return null;

                const bounds = this.getElementsBounds(this.elements);
                const padding = 50;

                const svgClone = svgElement.cloneNode(true);
                svgClone.setAttribute('viewBox', `${bounds.x - padding} ${bounds.y - padding} ${bounds.width + padding * 2} ${bounds.height + padding * 2}`);
                svgClone.setAttribute('width', bounds.width + padding * 2);
                svgClone.setAttribute('height', bounds.height + padding * 2);

                const svgData = new XMLSerializer().serializeToString(svgClone);
                return new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
            },

            /**
             * Descargar archivo
             */
            downloadFile(blob, filename) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            },

            /**
             * Exportar datos JSON
             */
            exportJSON() {
                return JSON.stringify({
                    version: '1.0',
                    title: this.documentTitle,
                    exportedAt: Date.now(),
                    viewport: this.viewport,
                    elements: this.elements,
                    settings: {
                        gridVisible: this.gridVisible,
                        snapToGrid: this.snapToGrid,
                        gridSize: this.gridSize
                    }
                }, null, 2);
            },

            /**
             * Importar datos JSON
             */
            importJSON(jsonString) {
                try {
                    const data = JSON.parse(jsonString);

                    if (!data.elements || !Array.isArray(data.elements)) {
                        throw new Error('Formato invalido');
                    }

                    this.elements = data.elements;
                    this.documentTitle = data.title || 'Importado';

                    if (data.viewport) {
                        this.viewport = data.viewport;
                    }

                    if (data.settings) {
                        this.gridVisible = data.settings.gridVisible ?? this.gridVisible;
                        this.snapToGrid = data.settings.snapToGrid ?? this.snapToGrid;
                        this.gridSize = data.settings.gridSize ?? this.gridSize;
                    }

                    this.selectedIds = [];
                    this.history = { past: [], future: [] };
                    this.isDirty = true;

                    this.dispatchEvent('whiteboard:import', { count: this.elements.length });

                    return true;
                } catch (error) {
                    console.error('[VBP Whiteboard] Error importando JSON:', error);
                    return false;
                }
            },

            // ============================================
            // TECLADO
            // ============================================

            /**
             * Configurar atajos de teclado
             */
            setupKeyboardShortcuts() {
                document.addEventListener('keydown', (e) => {
                    if (!this.enabled) return;

                    // Ignorar si estamos editando texto
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                        return;
                    }

                    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                    const modKey = isMac ? e.metaKey : e.ctrlKey;

                    // Undo: Ctrl/Cmd + Z
                    if (modKey && e.key === 'z' && !e.shiftKey) {
                        e.preventDefault();
                        this.undo();
                        return;
                    }

                    // Redo: Ctrl/Cmd + Shift + Z o Ctrl/Cmd + Y
                    if ((modKey && e.shiftKey && e.key === 'z') || (modKey && e.key === 'y')) {
                        e.preventDefault();
                        this.redo();
                        return;
                    }

                    // Copy: Ctrl/Cmd + C
                    if (modKey && e.key === 'c') {
                        e.preventDefault();
                        this.copy();
                        return;
                    }

                    // Cut: Ctrl/Cmd + X
                    if (modKey && e.key === 'x') {
                        e.preventDefault();
                        this.cut();
                        return;
                    }

                    // Paste: Ctrl/Cmd + V
                    if (modKey && e.key === 'v') {
                        e.preventDefault();
                        this.paste();
                        return;
                    }

                    // Duplicate: Ctrl/Cmd + D
                    if (modKey && e.key === 'd') {
                        e.preventDefault();
                        this.duplicate();
                        return;
                    }

                    // Select All: Ctrl/Cmd + A
                    if (modKey && e.key === 'a') {
                        e.preventDefault();
                        this.selectAll();
                        return;
                    }

                    // Delete: Delete o Backspace
                    if (e.key === 'Delete' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.deleteSelected();
                        return;
                    }

                    // Escape: Deseleccionar o cambiar a select
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        if (this.selectedIds.length > 0) {
                            this.deselectAll();
                        } else {
                            this.setTool('select');
                        }
                        return;
                    }

                    // Herramientas con teclas
                    const toolKeys = {
                        'v': 'select',
                        'h': 'pan',
                        's': 'sticky',
                        'r': 'shape',
                        'p': 'draw',
                        't': 'text',
                        'l': 'connector',
                        'e': 'stamp',
                        'f': 'frame',
                        'x': 'eraser'
                    };

                    const tool = toolKeys[e.key.toLowerCase()];
                    if (tool && !modKey && !e.shiftKey && !e.altKey) {
                        e.preventDefault();
                        this.setTool(tool);
                        return;
                    }

                    // Zoom
                    if (e.key === '+' || e.key === '=') {
                        e.preventDefault();
                        this.zoomIn();
                        return;
                    }

                    if (e.key === '-') {
                        e.preventDefault();
                        this.zoomOut();
                        return;
                    }

                    if (e.key === '0' && modKey) {
                        e.preventDefault();
                        this.zoomReset();
                        return;
                    }

                    if (e.key === '1' && modKey) {
                        e.preventDefault();
                        this.zoomToFit();
                        return;
                    }

                    // Space para pan temporal
                    if (e.key === ' ' && !e.repeat) {
                        e.preventDefault();
                        this._previousTool = this.tool;
                        this.setTool('pan');
                        return;
                    }

                    // Mover seleccion con flechas
                    const arrowMoves = {
                        'ArrowUp': { x: 0, y: -1 },
                        'ArrowDown': { x: 0, y: 1 },
                        'ArrowLeft': { x: -1, y: 0 },
                        'ArrowRight': { x: 1, y: 0 }
                    };

                    if (arrowMoves[e.key] && this.selectedIds.length > 0) {
                        e.preventDefault();
                        const delta = e.shiftKey ? 10 : 1;
                        const move = arrowMoves[e.key];

                        this.getSelectedElements().forEach(el => {
                            if (el.position) {
                                el.position.x += move.x * delta * this.gridSize;
                                el.position.y += move.y * delta * this.gridSize;
                            }
                        });

                        this.isDirty = true;
                        return;
                    }
                });

                // Soltar Space para volver a herramienta anterior
                document.addEventListener('keyup', (e) => {
                    if (!this.enabled) return;

                    if (e.key === ' ' && this._previousTool) {
                        this.setTool(this._previousTool);
                        this._previousTool = null;
                    }
                });
            },

            // ============================================
            // UTILIDADES
            // ============================================

            /**
             * Obtener ID del usuario actual
             */
            getCurrentUserId() {
                return window.vbpWhiteboardConfig?.userId || 'anonymous';
            },

            /**
             * Emitir evento
             */
            dispatchEvent(name, detail = {}) {
                document.dispatchEvent(new CustomEvent(name, { detail }));
            },

            /**
             * Convertir posicion de pantalla a canvas
             */
            screenToCanvas(screenX, screenY) {
                const rect = this.canvasRef?.getBoundingClientRect();
                if (!rect) return { x: screenX, y: screenY };

                return {
                    x: (screenX - rect.left - this.viewport.x) / this.viewport.zoom,
                    y: (screenY - rect.top - this.viewport.y) / this.viewport.zoom
                };
            },

            /**
             * Convertir posicion de canvas a pantalla
             */
            canvasToScreen(canvasX, canvasY) {
                const rect = this.canvasRef?.getBoundingClientRect();
                if (!rect) return { x: canvasX, y: canvasY };

                return {
                    x: canvasX * this.viewport.zoom + this.viewport.x + rect.left,
                    y: canvasY * this.viewport.zoom + this.viewport.y + rect.top
                };
            }
        });
    });

    // Exponer constantes globalmente
    window.VBPWhiteboardConfig = WHITEBOARD_CONFIG;
    window.VBPStickyColors = STICKY_COLORS;
    window.VBPShapes = SHAPES;
    window.VBPStamps = STAMPS;
    window.VBPConnectorStyles = CONNECTOR_STYLES;
    window.VBPWhiteboardTemplates = WHITEBOARD_TEMPLATES;

})();

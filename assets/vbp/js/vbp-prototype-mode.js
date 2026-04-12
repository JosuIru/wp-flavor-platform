/**
 * VBP Prototype Mode - Sistema de prototipado interactivo
 *
 * Permite definir interacciones entre elementos, crear flujos de navegacion
 * entre frames/paginas, y previsualizar prototipos interactivos.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Configuracion de tipos de trigger disponibles
     */
    var TRIGGER_TYPES = {
        'click': {
            id: 'click',
            label: 'Al hacer clic',
            icon: '👆',
            description: 'Se activa al hacer clic en el elemento',
            hasDelay: false
        },
        'hover': {
            id: 'hover',
            label: 'Al pasar el cursor',
            icon: '🖱️',
            description: 'Se activa al pasar el cursor sobre el elemento',
            hasDelay: true,
            defaultDelay: 0
        },
        'scroll': {
            id: 'scroll',
            label: 'Al hacer scroll',
            icon: '📜',
            description: 'Se activa cuando el elemento entra en el viewport',
            hasOffset: true,
            defaultOffset: 0
        },
        'load': {
            id: 'load',
            label: 'Al cargar',
            icon: '⚡',
            description: 'Se activa cuando el frame/pagina carga',
            hasDelay: true,
            defaultDelay: 0
        },
        'drag': {
            id: 'drag',
            label: 'Al arrastrar',
            icon: '✋',
            description: 'Se activa al arrastrar el elemento',
            hasDirection: true
        }
    };

    /**
     * Configuracion de tipos de accion disponibles
     */
    var ACTION_TYPES = {
        'navigate': {
            id: 'navigate',
            label: 'Navegar',
            icon: '➡️',
            description: 'Navegar a otro frame o pagina',
            requiresTarget: true,
            targetType: 'frame'
        },
        'overlay': {
            id: 'overlay',
            label: 'Mostrar overlay',
            icon: '📱',
            description: 'Mostrar un elemento como overlay/modal',
            requiresTarget: true,
            targetType: 'element'
        },
        'animate': {
            id: 'animate',
            label: 'Animar',
            icon: '🎬',
            description: 'Ejecutar una animacion en el elemento',
            requiresTarget: false,
            usesAnimationBuilder: true
        },
        'set_variable': {
            id: 'set_variable',
            label: 'Establecer variable',
            icon: '📝',
            description: 'Cambiar el valor de una variable de estado',
            requiresTarget: false,
            hasVariable: true
        },
        'open_url': {
            id: 'open_url',
            label: 'Abrir URL',
            icon: '🔗',
            description: 'Abrir una URL externa',
            requiresTarget: false,
            hasUrl: true
        },
        'close_overlay': {
            id: 'close_overlay',
            label: 'Cerrar overlay',
            icon: '✖️',
            description: 'Cerrar el overlay actualmente visible',
            requiresTarget: false
        },
        'back': {
            id: 'back',
            label: 'Volver atras',
            icon: '⬅️',
            description: 'Volver al frame anterior en el historial',
            requiresTarget: false
        }
    };

    /**
     * Configuracion de tipos de transicion/animacion
     */
    var TRANSITION_TYPES = {
        'instant': {
            id: 'instant',
            label: 'Instantaneo',
            icon: '⚡',
            defaultDuration: 0
        },
        'dissolve': {
            id: 'dissolve',
            label: 'Disolver',
            icon: '🌫️',
            defaultDuration: 300
        },
        'slide-left': {
            id: 'slide-left',
            label: 'Deslizar izquierda',
            icon: '⬅️',
            defaultDuration: 300
        },
        'slide-right': {
            id: 'slide-right',
            label: 'Deslizar derecha',
            icon: '➡️',
            defaultDuration: 300
        },
        'slide-up': {
            id: 'slide-up',
            label: 'Deslizar arriba',
            icon: '⬆️',
            defaultDuration: 300
        },
        'slide-down': {
            id: 'slide-down',
            label: 'Deslizar abajo',
            icon: '⬇️',
            defaultDuration: 300
        },
        'push-left': {
            id: 'push-left',
            label: 'Empujar izquierda',
            icon: '◀️',
            defaultDuration: 300
        },
        'push-right': {
            id: 'push-right',
            label: 'Empujar derecha',
            icon: '▶️',
            defaultDuration: 300
        },
        'flip': {
            id: 'flip',
            label: 'Voltear',
            icon: '🔄',
            defaultDuration: 500
        },
        'zoom-in': {
            id: 'zoom-in',
            label: 'Acercar',
            icon: '🔍',
            defaultDuration: 300
        },
        'zoom-out': {
            id: 'zoom-out',
            label: 'Alejar',
            icon: '🔎',
            defaultDuration: 300
        },
        'smart-animate': {
            id: 'smart-animate',
            label: 'Smart Animate',
            icon: '✨',
            defaultDuration: 300,
            isSmartAnimate: true
        }
    };

    /**
     * Genera un ID unico para interacciones
     */
    function generateInteractionId() {
        return 'int_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Genera un ID unico para frames
     */
    function generateFrameId() {
        return 'frame_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * VBP Prototype Mode - Objeto principal
     */
    window.VBPPrototypeMode = {
        /**
         * Estado del modo prototipo
         */
        enabled: false,
        previewMode: false,

        /**
         * Interacciones por elemento
         * Estructura: { elementId: [{ id, trigger, action, target, animation, duration, ... }] }
         */
        interactions: {},

        /**
         * Conexiones visuales entre elementos (para dibujar flechas)
         */
        connections: [],

        /**
         * Frames/paginas del prototipo
         */
        frames: [],
        currentFrameId: null,

        /**
         * Historial de navegacion para funcion "back"
         */
        navigationHistory: [],

        /**
         * Variables de estado del prototipo
         */
        variables: {},

        /**
         * Overlay actualmente visible
         */
        activeOverlay: null,

        /**
         * Elemento seleccionado para editar interacciones
         */
        selectedElementId: null,

        /**
         * Panel de prototipo abierto
         */
        panelOpen: false,

        /**
         * Tipos de trigger disponibles
         */
        triggerTypes: TRIGGER_TYPES,

        /**
         * Tipos de accion disponibles
         */
        actionTypes: ACTION_TYPES,

        /**
         * Tipos de transicion disponibles
         */
        transitionTypes: TRANSITION_TYPES,

        /**
         * Inicializa el Prototype Mode
         */
        init: function() {
            var self = this;

            // Registrar comando en la paleta
            if (window.VBPCommandPalette && typeof window.VBPCommandPalette.registerCommand === 'function') {
                window.VBPCommandPalette.registerCommand({
                    id: 'prototype-mode',
                    label: 'Activar Modo Prototipo',
                    category: 'tools',
                    icon: '🔗',
                    action: function() {
                        self.toggleMode();
                    }
                });

                window.VBPCommandPalette.registerCommand({
                    id: 'prototype-preview',
                    label: 'Previsualizar Prototipo',
                    category: 'tools',
                    icon: '▶️',
                    action: function() {
                        self.startPreview();
                    }
                });
            }

            // Escuchar eventos
            this.bindEvents();

            // Cargar datos del documento actual si existe
            this.loadFromDocument();

            // Registrar atajos de teclado
            this.registerKeyboardShortcuts();

            console.log('[VBP Prototype Mode] Initialized');
        },

        /**
         * Vincula eventos del DOM
         */
        bindEvents: function() {
            var self = this;

            // Escuchar cambios en la seleccion del store
            document.addEventListener('alpine:init', function() {
                if (window.Alpine && Alpine.effect) {
                    Alpine.effect(function() {
                        var store = Alpine.store('vbp');
                        if (store && store.selection && store.selection.elementIds) {
                            var selectedIds = store.selection.elementIds;
                            if (selectedIds.length === 1 && self.enabled) {
                                self.selectedElementId = selectedIds[0];
                                self.updateConnectionsDisplay();
                            }
                        }
                    });
                }
            });

            // Escuchar evento de apertura del panel
            document.addEventListener('vbp:open-prototype-panel', function(event) {
                self.openPanel();
                if (event.detail && event.detail.elementId) {
                    self.selectedElementId = event.detail.elementId;
                }
            });

            // Escuchar guardado del documento para persistir interacciones
            document.addEventListener('vbp:beforeSave', function(event) {
                self.saveToDocument();
            });

            // Escuchar carga del documento
            document.addEventListener('vbp:documentLoaded', function(event) {
                self.loadFromDocument();
            });
        },

        /**
         * Registra atajos de teclado
         */
        registerKeyboardShortcuts: function() {
            var self = this;

            // Intentar usar VBPKeyboardModular si existe
            if (window.VBPKeyboardModular && typeof window.VBPKeyboardModular.registerShortcut === 'function') {
                window.VBPKeyboardModular.registerShortcut({
                    key: 'p',
                    handler: function() {
                        self.toggleMode();
                        return true;
                    },
                    description: 'Activar/desactivar modo prototipo'
                });

                window.VBPKeyboardModular.registerShortcut({
                    key: 'Escape',
                    context: 'prototype-preview',
                    handler: function() {
                        if (self.previewMode) {
                            self.stopPreview();
                            return true;
                        }
                        return false;
                    },
                    description: 'Salir del modo preview'
                });
            } else {
                // Fallback: registrar atajos directamente en el documento
                this.setupFallbackKeyboardShortcuts();
            }
        },

        /**
         * Configura atajos de teclado con listener directo (fallback)
         */
        setupFallbackKeyboardShortcuts: function() {
            var self = this;

            document.addEventListener('keydown', function(keyboardEvent) {
                // No procesar si el foco esta en un input/textarea
                var activeElement = document.activeElement;
                var tagName = activeElement ? activeElement.tagName.toLowerCase() : '';
                var isInputFocused = tagName === 'input' || tagName === 'textarea' || activeElement.isContentEditable;

                if (isInputFocused) {
                    return;
                }

                // P: Toggle modo prototipo (sin modificadores)
                if (keyboardEvent.key === 'p' && !keyboardEvent.ctrlKey && !keyboardEvent.metaKey && !keyboardEvent.altKey) {
                    keyboardEvent.preventDefault();
                    self.toggleMode();
                    return;
                }

                // Shift+P: Abrir panel de prototipo
                if (keyboardEvent.key === 'P' && keyboardEvent.shiftKey && !keyboardEvent.ctrlKey && !keyboardEvent.metaKey) {
                    keyboardEvent.preventDefault();
                    if (window.VBPPrototypePanel) {
                        window.VBPPrototypePanel.show();
                    } else {
                        self.openPanel();
                    }
                    return;
                }

                // Ctrl+Shift+Enter: Iniciar preview
                if (keyboardEvent.key === 'Enter' && keyboardEvent.ctrlKey && keyboardEvent.shiftKey) {
                    keyboardEvent.preventDefault();
                    if (self.previewMode) {
                        self.stopPreview();
                    } else {
                        self.startPreview();
                    }
                    return;
                }

                // Escape: Salir del modo preview
                if (keyboardEvent.key === 'Escape' && self.previewMode) {
                    keyboardEvent.preventDefault();
                    self.stopPreview();
                    return;
                }
            });
        },

        /**
         * Activa/desactiva el modo prototipo
         */
        toggleMode: function() {
            this.enabled = !this.enabled;

            if (this.enabled) {
                this.onModeEnabled();
            } else {
                this.onModeDisabled();
            }

            // Notificar al store
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                Alpine.store('vbp').prototypeMode = this.enabled;
            }

            document.dispatchEvent(new CustomEvent('vbp:prototypeModeChanged', {
                detail: { enabled: this.enabled }
            }));
        },

        /**
         * Callback cuando se activa el modo prototipo
         */
        onModeEnabled: function() {
            // Agregar clase al canvas
            var canvasElement = document.querySelector('.vbp-canvas');
            if (canvasElement) {
                canvasElement.classList.add('vbp-prototype-mode');
            }

            // Mostrar conexiones
            this.renderConnections();

            // Mostrar hotspots
            this.showHotspots();

            if (window.VBPToast) {
                window.VBPToast.show('Modo Prototipo activado', 'info');
            }
        },

        /**
         * Callback cuando se desactiva el modo prototipo
         */
        onModeDisabled: function() {
            // Quitar clase del canvas
            var canvasElement = document.querySelector('.vbp-canvas');
            if (canvasElement) {
                canvasElement.classList.remove('vbp-prototype-mode');
            }

            // Ocultar conexiones
            this.clearConnectionsDisplay();

            // Ocultar hotspots
            this.hideHotspots();

            // Cerrar panel si esta abierto
            if (this.panelOpen) {
                this.closePanel();
            }
        },

        /**
         * Abre el panel de interacciones
         */
        openPanel: function() {
            this.panelOpen = true;
            if (!this.enabled) {
                this.toggleMode();
            }
        },

        /**
         * Cierra el panel de interacciones
         */
        closePanel: function() {
            this.panelOpen = false;
        },

        /**
         * Obtiene las interacciones de un elemento
         */
        getInteractions: function(elementId) {
            return this.interactions[elementId] || [];
        },

        /**
         * Agrega una nueva interaccion a un elemento
         */
        addInteraction: function(elementId, interactionData) {
            if (!elementId) {
                elementId = this.selectedElementId;
            }
            if (!elementId) return null;

            var newInteraction = {
                id: generateInteractionId(),
                trigger: interactionData.trigger || 'click',
                action: interactionData.action || 'navigate',
                target: interactionData.target || null,
                animation: interactionData.animation || 'dissolve',
                duration: interactionData.duration || 300,
                delay: interactionData.delay || 0,
                easing: interactionData.easing || 'ease-out',
                // Propiedades adicionales segun tipo
                url: interactionData.url || '',
                variable: interactionData.variable || '',
                variableValue: interactionData.variableValue || '',
                scrollOffset: interactionData.scrollOffset || 0,
                hoverDelay: interactionData.hoverDelay || 0,
                preserveScroll: interactionData.preserveScroll || false
            };

            if (!this.interactions[elementId]) {
                this.interactions[elementId] = [];
            }

            this.interactions[elementId].push(newInteraction);
            this.updateConnections();
            this.saveToDocument();

            return newInteraction;
        },

        /**
         * Actualiza una interaccion existente
         */
        updateInteraction: function(elementId, interactionId, changes) {
            var elementInteractions = this.interactions[elementId];
            if (!elementInteractions) return false;

            var interactionIndex = elementInteractions.findIndex(function(interactionItem) {
                return interactionItem.id === interactionId;
            });

            if (interactionIndex === -1) return false;

            Object.assign(elementInteractions[interactionIndex], changes);
            this.updateConnections();
            this.saveToDocument();

            return true;
        },

        /**
         * Elimina una interaccion
         */
        removeInteraction: function(elementId, interactionId) {
            var elementInteractions = this.interactions[elementId];
            if (!elementInteractions) return false;

            var interactionIndex = elementInteractions.findIndex(function(interactionItem) {
                return interactionItem.id === interactionId;
            });

            if (interactionIndex === -1) return false;

            elementInteractions.splice(interactionIndex, 1);

            // Limpiar array vacio
            if (elementInteractions.length === 0) {
                delete this.interactions[elementId];
            }

            this.updateConnections();
            this.saveToDocument();

            return true;
        },

        /**
         * Actualiza la lista de conexiones basada en las interacciones
         */
        updateConnections: function() {
            var self = this;
            this.connections = [];

            Object.keys(this.interactions).forEach(function(sourceElementId) {
                var elementInteractions = self.interactions[sourceElementId];
                elementInteractions.forEach(function(interaction) {
                    if (interaction.target && (interaction.action === 'navigate' || interaction.action === 'overlay')) {
                        self.connections.push({
                            sourceId: sourceElementId,
                            targetId: interaction.target,
                            interactionId: interaction.id,
                            action: interaction.action,
                            animation: interaction.animation
                        });
                    }
                });
            });

            if (this.enabled) {
                this.renderConnections();
            }
        },

        /**
         * Renderiza las flechas de conexion en el canvas
         */
        renderConnections: function() {
            this.clearConnectionsDisplay();

            var svgContainer = this.getOrCreateSvgContainer();
            var self = this;

            this.connections.forEach(function(connection) {
                self.drawConnectionArrow(svgContainer, connection);
            });
        },

        /**
         * Obtiene o crea el contenedor SVG para las flechas
         */
        getOrCreateSvgContainer: function() {
            var existingContainer = document.getElementById('vbp-prototype-connections');
            if (existingContainer) {
                return existingContainer;
            }

            var canvasElement = document.querySelector('.vbp-canvas');
            if (!canvasElement) return null;

            var svgNamespace = 'http://www.w3.org/2000/svg';
            var svgElement = document.createElementNS(svgNamespace, 'svg');
            svgElement.id = 'vbp-prototype-connections';
            svgElement.setAttribute('class', 'vbp-prototype-connections-svg');
            svgElement.style.position = 'absolute';
            svgElement.style.top = '0';
            svgElement.style.left = '0';
            svgElement.style.width = '100%';
            svgElement.style.height = '100%';
            svgElement.style.pointerEvents = 'none';
            svgElement.style.zIndex = '1000';
            svgElement.style.overflow = 'visible';

            // Definir marcadores para las flechas
            var defsElement = document.createElementNS(svgNamespace, 'defs');

            var markerElement = document.createElementNS(svgNamespace, 'marker');
            markerElement.setAttribute('id', 'vbp-arrow-marker');
            markerElement.setAttribute('markerWidth', '10');
            markerElement.setAttribute('markerHeight', '10');
            markerElement.setAttribute('refX', '9');
            markerElement.setAttribute('refY', '3');
            markerElement.setAttribute('orient', 'auto');
            markerElement.setAttribute('markerUnits', 'strokeWidth');

            var pathElement = document.createElementNS(svgNamespace, 'path');
            pathElement.setAttribute('d', 'M0,0 L0,6 L9,3 z');
            pathElement.setAttribute('fill', '#6366f1');

            markerElement.appendChild(pathElement);
            defsElement.appendChild(markerElement);
            svgElement.appendChild(defsElement);

            canvasElement.appendChild(svgElement);
            return svgElement;
        },

        /**
         * Dibuja una flecha de conexion entre dos elementos
         */
        drawConnectionArrow: function(svgContainer, connection) {
            if (!svgContainer) return;

            var sourceElement = document.querySelector('[data-vbp-id="' + connection.sourceId + '"]');
            var targetElement = document.querySelector('[data-vbp-id="' + connection.targetId + '"]');

            if (!sourceElement || !targetElement) return;

            var sourceRect = sourceElement.getBoundingClientRect();
            var targetRect = targetElement.getBoundingClientRect();
            var canvasRect = svgContainer.getBoundingClientRect();

            // Calcular puntos de inicio y fin
            var startX = sourceRect.right - canvasRect.left;
            var startY = sourceRect.top + sourceRect.height / 2 - canvasRect.top;
            var endX = targetRect.left - canvasRect.left;
            var endY = targetRect.top + targetRect.height / 2 - canvasRect.top;

            // Crear path curvo
            var svgNamespace = 'http://www.w3.org/2000/svg';
            var pathElement = document.createElementNS(svgNamespace, 'path');

            // Control points para curva bezier
            var controlOffsetX = Math.min(100, Math.abs(endX - startX) / 2);
            var controlPointOneX = startX + controlOffsetX;
            var controlPointTwoX = endX - controlOffsetX;

            var pathData = 'M ' + startX + ',' + startY +
                          ' C ' + controlPointOneX + ',' + startY +
                          ' ' + controlPointTwoX + ',' + endY +
                          ' ' + endX + ',' + endY;

            pathElement.setAttribute('d', pathData);
            pathElement.setAttribute('class', 'vbp-connection-path');
            pathElement.setAttribute('data-source', connection.sourceId);
            pathElement.setAttribute('data-target', connection.targetId);
            pathElement.setAttribute('data-interaction', connection.interactionId);
            pathElement.setAttribute('fill', 'none');
            pathElement.setAttribute('stroke', '#6366f1');
            pathElement.setAttribute('stroke-width', '2');
            pathElement.setAttribute('marker-end', 'url(#vbp-arrow-marker)');
            pathElement.style.pointerEvents = 'stroke';
            pathElement.style.cursor = 'pointer';

            // Agregar evento click para seleccionar la conexion
            var self = this;
            pathElement.addEventListener('click', function(clickEvent) {
                clickEvent.stopPropagation();
                self.selectConnection(connection);
            });

            svgContainer.appendChild(pathElement);
        },

        /**
         * Selecciona una conexion para editarla
         */
        selectConnection: function(connection) {
            this.selectedElementId = connection.sourceId;

            // Seleccionar el elemento en el store
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                Alpine.store('vbp').setSelection([connection.sourceId]);
            }

            this.openPanel();
        },

        /**
         * Limpia las flechas de conexion del display
         */
        clearConnectionsDisplay: function() {
            var svgContainer = document.getElementById('vbp-prototype-connections');
            if (svgContainer) {
                // Eliminar solo paths, mantener defs
                var paths = svgContainer.querySelectorAll('.vbp-connection-path');
                paths.forEach(function(pathElement) {
                    pathElement.remove();
                });
            }
        },

        /**
         * Actualiza el display de conexiones cuando cambia la seleccion
         */
        updateConnectionsDisplay: function() {
            if (this.enabled) {
                this.renderConnections();
            }
        },

        /**
         * Muestra indicadores de hotspot en elementos con interacciones
         */
        showHotspots: function() {
            var self = this;

            Object.keys(this.interactions).forEach(function(elementId) {
                var elementWithInteraction = document.querySelector('[data-vbp-id="' + elementId + '"]');
                if (elementWithInteraction) {
                    elementWithInteraction.classList.add('vbp-has-interaction');

                    // Agregar badge con numero de interacciones
                    var interactionCount = self.interactions[elementId].length;
                    var existingBadge = elementWithInteraction.querySelector('.vbp-interaction-badge');

                    if (!existingBadge) {
                        var badgeElement = document.createElement('span');
                        badgeElement.className = 'vbp-interaction-badge';
                        badgeElement.textContent = interactionCount.toString();
                        elementWithInteraction.appendChild(badgeElement);
                    } else {
                        existingBadge.textContent = interactionCount.toString();
                    }
                }
            });
        },

        /**
         * Oculta los indicadores de hotspot
         */
        hideHotspots: function() {
            var elementsWithInteraction = document.querySelectorAll('.vbp-has-interaction');
            elementsWithInteraction.forEach(function(element) {
                element.classList.remove('vbp-has-interaction');
                var badge = element.querySelector('.vbp-interaction-badge');
                if (badge) {
                    badge.remove();
                }
            });
        },

        // ============================================
        // Preview Mode
        // ============================================

        /**
         * Inicia el modo preview del prototipo
         */
        startPreview: function() {
            if (this.previewMode) return;

            this.previewMode = true;
            this.navigationHistory = [];
            this.activeOverlay = null;

            // Guardar frame inicial
            if (this.frames.length > 0) {
                this.currentFrameId = this.frames[0].id;
            }

            // Agregar clase al body para modo preview
            document.body.classList.add('vbp-prototype-preview-mode');

            // Crear overlay de preview
            this.createPreviewOverlay();

            // Configurar handlers de interaccion
            this.setupPreviewInteractionHandlers();

            document.dispatchEvent(new CustomEvent('vbp:prototypePreviewStarted'));

            if (window.VBPToast) {
                window.VBPToast.show('Modo Preview - Presiona ESC para salir', 'info');
            }
        },

        /**
         * Detiene el modo preview
         */
        stopPreview: function() {
            if (!this.previewMode) return;

            this.previewMode = false;
            this.navigationHistory = [];
            this.activeOverlay = null;

            // Quitar clase del body
            document.body.classList.remove('vbp-prototype-preview-mode');

            // Eliminar overlay de preview
            this.removePreviewOverlay();

            // Limpiar handlers
            this.cleanupPreviewInteractionHandlers();

            document.dispatchEvent(new CustomEvent('vbp:prototypePreviewStopped'));
        },

        /**
         * Crea el overlay para el modo preview
         */
        createPreviewOverlay: function() {
            var overlayElement = document.createElement('div');
            overlayElement.id = 'vbp-prototype-preview-overlay';
            overlayElement.className = 'vbp-prototype-preview-overlay';

            // Toolbar de preview
            overlayElement.innerHTML = [
                '<div class="vbp-preview-toolbar">',
                '  <div class="vbp-preview-toolbar__left">',
                '    <button class="vbp-preview-btn vbp-preview-back" title="Volver" disabled>',
                '      <span>⬅️</span>',
                '    </button>',
                '    <span class="vbp-preview-frame-name">Frame 1</span>',
                '  </div>',
                '  <div class="vbp-preview-toolbar__right">',
                '    <button class="vbp-preview-btn vbp-preview-restart" title="Reiniciar">',
                '      <span>🔄</span>',
                '    </button>',
                '    <button class="vbp-preview-btn vbp-preview-close" title="Cerrar (ESC)">',
                '      <span>✖️</span>',
                '    </button>',
                '  </div>',
                '</div>',
                '<div class="vbp-preview-viewport">',
                '  <div class="vbp-preview-frame-container"></div>',
                '</div>',
                '<div class="vbp-preview-overlay-container"></div>'
            ].join('');

            document.body.appendChild(overlayElement);

            // Configurar eventos de toolbar
            var self = this;

            overlayElement.querySelector('.vbp-preview-close').addEventListener('click', function() {
                self.stopPreview();
            });

            overlayElement.querySelector('.vbp-preview-restart').addEventListener('click', function() {
                self.restartPreview();
            });

            overlayElement.querySelector('.vbp-preview-back').addEventListener('click', function() {
                self.goBack();
            });

            // Clonar el contenido actual del canvas al viewport
            this.cloneCanvasToPreview();
        },

        /**
         * Clona el contenido del canvas al viewport de preview
         */
        cloneCanvasToPreview: function() {
            var canvasContent = document.querySelector('.vbp-canvas__content');
            var previewContainer = document.querySelector('.vbp-preview-frame-container');

            if (!canvasContent || !previewContainer) return;

            var clonedContent = canvasContent.cloneNode(true);
            clonedContent.className = 'vbp-preview-frame';

            // Eliminar elementos de edicion del clon
            var editOnlyElements = clonedContent.querySelectorAll('.vbp-selector, .vbp-resize-handle, .vbp-element-controls');
            editOnlyElements.forEach(function(element) {
                element.remove();
            });

            previewContainer.appendChild(clonedContent);
        },

        /**
         * Elimina el overlay de preview
         */
        removePreviewOverlay: function() {
            var overlayElement = document.getElementById('vbp-prototype-preview-overlay');
            if (overlayElement) {
                overlayElement.remove();
            }
        },

        /**
         * Configura los handlers de interaccion para el modo preview
         * @param {Element} containerOverride - Contenedor opcional (para overlays)
         */
        setupPreviewInteractionHandlers: function(containerOverride) {
            var self = this;

            // Buscar en el frame principal y también en overlays
            var containers = [];
            if (containerOverride) {
                containers.push(containerOverride);
            } else {
                var previewFrame = document.querySelector('.vbp-preview-frame');
                var overlayContent = document.querySelector('.vbp-preview-overlay-content');
                if (previewFrame) containers.push(previewFrame);
                if (overlayContent) containers.push(overlayContent);
            }

            if (containers.length === 0) return;

            // Para cada elemento con interacciones, agregar handlers
            Object.keys(this.interactions).forEach(function(elementId) {
                containers.forEach(function(container) {
                    var elementInPreview = container.querySelector('[data-vbp-id="' + elementId + '"]');
                    if (!elementInPreview) return;

                    var elementInteractions = self.interactions[elementId];

                    elementInteractions.forEach(function(interaction) {
                        self.bindInteractionHandler(elementInPreview, interaction);
                    });
                });
            });
        },

        /**
         * Vincula un handler de interacción a un elemento
         */
        bindInteractionHandler: function(element, interaction) {
            var self = this;

            switch (interaction.trigger) {
                case 'click':
                    element.addEventListener('click', function(clickEvent) {
                        clickEvent.preventDefault();
                        clickEvent.stopPropagation();
                        self.executeInteraction(interaction);
                    });
                    element.style.cursor = 'pointer';
                    break;

                case 'hover':
                    var hoverTimeoutId = null;
                    element.addEventListener('mouseenter', function() {
                        hoverTimeoutId = setTimeout(function() {
                            self.executeInteraction(interaction);
                        }, interaction.hoverDelay || 0);
                    });
                    element.addEventListener('mouseleave', function() {
                        if (hoverTimeoutId) {
                            clearTimeout(hoverTimeoutId);
                            hoverTimeoutId = null;
                        }
                    });
                    break;

                case 'load':
                    setTimeout(function() {
                        self.executeInteraction(interaction);
                    }, interaction.delay || 0);
                    break;

                case 'scroll':
                    self.setupScrollTrigger(element, interaction);
                    break;

                case 'drag':
                    self.setupDragTrigger(element, interaction);
                    break;
            }
        },

        /**
         * Configura trigger de drag para un elemento
         */
        setupDragTrigger: function(element, interaction) {
            var self = this;
            var isDragging = false;
            var startX = 0;
            var startY = 0;
            var dragThreshold = 10; // Pixeles minimos para considerar drag

            element.style.cursor = 'grab';
            element.style.userSelect = 'none';

            var onMouseDown = function(downEvent) {
                isDragging = true;
                startX = downEvent.clientX;
                startY = downEvent.clientY;
                element.style.cursor = 'grabbing';

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            };

            var onMouseMove = function(moveEvent) {
                if (!isDragging) return;

                var deltaX = moveEvent.clientX - startX;
                var deltaY = moveEvent.clientY - startY;
                var distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

                if (distance > dragThreshold) {
                    // Determinar direccion si se especifica
                    if (interaction.dragDirection) {
                        var angle = Math.atan2(deltaY, deltaX) * 180 / Math.PI;
                        var direction = self.getDragDirection(angle);

                        if (direction === interaction.dragDirection) {
                            self.executeInteraction(interaction);
                            isDragging = false;
                        }
                    } else {
                        // Cualquier direccion activa el trigger
                        self.executeInteraction(interaction);
                        isDragging = false;
                    }
                }
            };

            var onMouseUp = function() {
                isDragging = false;
                element.style.cursor = 'grab';
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            element.addEventListener('mousedown', onMouseDown);
        },

        /**
         * Determina la direccion del drag basado en el angulo
         */
        getDragDirection: function(angle) {
            if (angle > -45 && angle <= 45) return 'right';
            if (angle > 45 && angle <= 135) return 'down';
            if (angle > 135 || angle <= -135) return 'left';
            if (angle > -135 && angle <= -45) return 'up';
            return 'any';
        },

        /**
         * Configura trigger de scroll para un elemento
         */
        setupScrollTrigger: function(element, interaction) {
            var self = this;
            var viewport = document.querySelector('.vbp-preview-viewport');
            var hasTriggered = false;

            if (!viewport) return;

            var scrollHandler = function() {
                if (hasTriggered) return;

                var elementRect = element.getBoundingClientRect();
                var viewportRect = viewport.getBoundingClientRect();
                var scrollOffset = interaction.scrollOffset || 0;

                // Verificar si el elemento esta visible
                var isVisible = elementRect.top < viewportRect.bottom - scrollOffset &&
                               elementRect.bottom > viewportRect.top + scrollOffset;

                if (isVisible) {
                    hasTriggered = true;
                    self.executeInteraction(interaction);
                }
            };

            viewport.addEventListener('scroll', scrollHandler);
            // Verificar inmediatamente por si ya esta visible
            scrollHandler();
        },

        /**
         * Limpia los handlers de interaccion del modo preview
         */
        cleanupPreviewInteractionHandlers: function() {
            // Los handlers se limpian automaticamente cuando se elimina el overlay
            // ya que estan vinculados a elementos clonados
        },

        /**
         * Ejecuta una interaccion
         */
        executeInteraction: function(interaction) {
            var self = this;

            switch (interaction.action) {
                case 'navigate':
                    this.navigate(interaction.target, interaction.animation, interaction.duration);
                    break;

                case 'overlay':
                    this.showOverlay(interaction.target, interaction.animation, interaction.duration);
                    break;

                case 'animate':
                    this.animateElement(interaction.target || this.selectedElementId, interaction);
                    break;

                case 'set_variable':
                    this.setVariable(interaction.variable, interaction.variableValue);
                    break;

                case 'open_url':
                    window.open(interaction.url, '_blank');
                    break;

                case 'close_overlay':
                    this.closeOverlay();
                    break;

                case 'back':
                    this.goBack();
                    break;
            }
        },

        /**
         * Navega a otro frame/pagina
         */
        navigate: function(targetFrameId, animationType, duration) {
            var self = this;

            // Guardar frame actual en historial
            if (this.currentFrameId) {
                this.navigationHistory.push(this.currentFrameId);
                this.updateBackButton();
            }

            var frameContainer = document.querySelector('.vbp-preview-frame-container');
            if (!frameContainer) return;

            var currentFrame = frameContainer.querySelector('.vbp-preview-frame');
            var targetElement = document.querySelector('[data-vbp-id="' + targetFrameId + '"]');

            if (!targetElement) {
                console.warn('[VBP Prototype Mode] Target frame not found:', targetFrameId);
                return;
            }

            // Clonar el frame destino
            var newFrame = targetElement.cloneNode(true);
            newFrame.className = 'vbp-preview-frame vbp-preview-frame--entering';

            // Si es Smart Animate, detectar y animar propiedades
            if (animationType === 'smart-animate') {
                this.performSmartAnimate(currentFrame, newFrame, duration);
            } else {
                this.performTransition(frameContainer, currentFrame, newFrame, animationType, duration);
            }

            this.currentFrameId = targetFrameId;
            this.updateFrameName();

            // Configurar handlers para el nuevo frame
            setTimeout(function() {
                self.setupPreviewInteractionHandlers();
            }, duration || 300);
        },

        /**
         * Realiza una transicion entre frames
         */
        performTransition: function(container, currentFrame, newFrame, animationType, duration) {
            var transitionDuration = duration || 300;
            var transitionConfig = TRANSITION_TYPES[animationType] || TRANSITION_TYPES['dissolve'];

            currentFrame.style.transition = 'all ' + transitionDuration + 'ms ease-out';
            newFrame.style.transition = 'all ' + transitionDuration + 'ms ease-out';

            switch (animationType) {
                case 'instant':
                    currentFrame.remove();
                    newFrame.classList.remove('vbp-preview-frame--entering');
                    container.appendChild(newFrame);
                    break;

                case 'dissolve':
                    newFrame.style.opacity = '0';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.opacity = '0';
                        newFrame.style.opacity = '1';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'slide-left':
                    newFrame.style.transform = 'translateX(100%)';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'translateX(-100%)';
                        newFrame.style.transform = 'translateX(0)';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'slide-right':
                    newFrame.style.transform = 'translateX(-100%)';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'translateX(100%)';
                        newFrame.style.transform = 'translateX(0)';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'slide-up':
                    newFrame.style.transform = 'translateY(100%)';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'translateY(-100%)';
                        newFrame.style.transform = 'translateY(0)';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'slide-down':
                    newFrame.style.transform = 'translateY(-100%)';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'translateY(100%)';
                        newFrame.style.transform = 'translateY(0)';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'zoom-in':
                    newFrame.style.transform = 'scale(0.8)';
                    newFrame.style.opacity = '0';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'scale(1.2)';
                        currentFrame.style.opacity = '0';
                        newFrame.style.transform = 'scale(1)';
                        newFrame.style.opacity = '1';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                case 'zoom-out':
                    newFrame.style.transform = 'scale(1.2)';
                    newFrame.style.opacity = '0';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.transform = 'scale(0.8)';
                        currentFrame.style.opacity = '0';
                        newFrame.style.transform = 'scale(1)';
                        newFrame.style.opacity = '1';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
                    break;

                default:
                    // Fallback a dissolve
                    newFrame.style.opacity = '0';
                    container.appendChild(newFrame);
                    requestAnimationFrame(function() {
                        currentFrame.style.opacity = '0';
                        newFrame.style.opacity = '1';
                    });
                    setTimeout(function() {
                        currentFrame.remove();
                        newFrame.classList.remove('vbp-preview-frame--entering');
                    }, transitionDuration);
            }
        },

        /**
         * Realiza Smart Animate entre dos frames
         * Detecta elementos con el mismo ID y anima sus propiedades
         */
        performSmartAnimate: function(currentFrame, newFrame, duration) {
            var transitionDuration = duration || 300;
            var container = currentFrame.parentElement;

            // Posicionar nuevo frame encima
            newFrame.style.position = 'absolute';
            newFrame.style.top = '0';
            newFrame.style.left = '0';
            newFrame.style.width = '100%';
            newFrame.style.height = '100%';
            newFrame.style.opacity = '0';
            container.appendChild(newFrame);

            // Encontrar elementos con mismo data-vbp-id en ambos frames
            var currentElements = currentFrame.querySelectorAll('[data-vbp-id]');
            var matchedPairs = [];

            currentElements.forEach(function(currentElement) {
                var elementId = currentElement.getAttribute('data-vbp-id');
                var newElement = newFrame.querySelector('[data-vbp-id="' + elementId + '"]');

                if (newElement) {
                    matchedPairs.push({
                        current: currentElement,
                        next: newElement,
                        id: elementId
                    });
                }
            });

            // Animar cada par de elementos
            var self = this;
            matchedPairs.forEach(function(pair) {
                self.animateElementPair(pair.current, pair.next, transitionDuration);
            });

            // Fade in nuevo frame despues de un delay
            setTimeout(function() {
                newFrame.style.transition = 'opacity ' + (transitionDuration / 2) + 'ms ease-out';
                newFrame.style.opacity = '1';
                currentFrame.style.transition = 'opacity ' + (transitionDuration / 2) + 'ms ease-out';
                currentFrame.style.opacity = '0';
            }, transitionDuration / 2);

            // Limpiar
            setTimeout(function() {
                currentFrame.remove();
                newFrame.style.position = '';
                newFrame.classList.remove('vbp-preview-frame--entering');
            }, transitionDuration);
        },

        /**
         * Anima un par de elementos durante Smart Animate
         */
        animateElementPair: function(currentElement, nextElement, duration) {
            var currentRect = currentElement.getBoundingClientRect();
            var nextRect = nextElement.getBoundingClientRect();

            var currentStyle = window.getComputedStyle(currentElement);
            var nextStyle = window.getComputedStyle(nextElement);

            // Propiedades a animar
            var animatableProperties = ['transform', 'opacity', 'background-color', 'color', 'border-radius', 'width', 'height'];

            // Calcular diferencias
            var translateDeltaX = nextRect.left - currentRect.left;
            var translateDeltaY = nextRect.top - currentRect.top;
            var scaleDeltaX = nextRect.width / currentRect.width;
            var scaleDeltaY = nextRect.height / currentRect.height;

            // Crear elemento phantom para animacion
            var phantomElement = currentElement.cloneNode(true);
            phantomElement.style.position = 'fixed';
            phantomElement.style.top = currentRect.top + 'px';
            phantomElement.style.left = currentRect.left + 'px';
            phantomElement.style.width = currentRect.width + 'px';
            phantomElement.style.height = currentRect.height + 'px';
            phantomElement.style.zIndex = '10000';
            phantomElement.style.pointerEvents = 'none';
            phantomElement.style.transition = 'all ' + duration + 'ms cubic-bezier(0.4, 0, 0.2, 1)';

            document.body.appendChild(phantomElement);

            // Ocultar originales durante animacion
            currentElement.style.opacity = '0';
            nextElement.style.opacity = '0';

            // Animar phantom hacia posicion destino
            requestAnimationFrame(function() {
                phantomElement.style.top = nextRect.top + 'px';
                phantomElement.style.left = nextRect.left + 'px';
                phantomElement.style.width = nextRect.width + 'px';
                phantomElement.style.height = nextRect.height + 'px';
                phantomElement.style.backgroundColor = nextStyle.backgroundColor;
                phantomElement.style.color = nextStyle.color;
                phantomElement.style.borderRadius = nextStyle.borderRadius;
            });

            // Limpiar despues de animacion
            setTimeout(function() {
                phantomElement.remove();
                nextElement.style.opacity = '';
            }, duration);
        },

        /**
         * Muestra un overlay
         */
        showOverlay: function(targetId, animationType, duration) {
            var overlayContainer = document.querySelector('.vbp-preview-overlay-container');
            var targetElement = document.querySelector('[data-vbp-id="' + targetId + '"]');

            if (!overlayContainer || !targetElement) return;

            // Clonar elemento objetivo
            var overlayContent = targetElement.cloneNode(true);
            overlayContent.className = 'vbp-preview-overlay-content';
            overlayContent.style.position = 'relative';

            // Crear backdrop
            var backdropElement = document.createElement('div');
            backdropElement.className = 'vbp-preview-overlay-backdrop';

            var self = this;
            backdropElement.addEventListener('click', function() {
                self.closeOverlay();
            });

            overlayContainer.innerHTML = '';
            overlayContainer.appendChild(backdropElement);
            overlayContainer.appendChild(overlayContent);
            overlayContainer.classList.add('vbp-preview-overlay--visible');

            this.activeOverlay = targetId;

            // Animar entrada
            var transitionDuration = duration || 300;
            overlayContent.style.opacity = '0';
            overlayContent.style.transform = 'scale(0.9) translateY(20px)';
            overlayContent.style.transition = 'all ' + transitionDuration + 'ms cubic-bezier(0.4, 0, 0.2, 1)';

            requestAnimationFrame(function() {
                overlayContent.style.opacity = '1';
                overlayContent.style.transform = 'scale(1) translateY(0)';
            });

            // Configurar handlers del overlay (pasar contenedor específico)
            this.setupPreviewInteractionHandlers(overlayContent);
        },

        /**
         * Cierra el overlay activo
         */
        closeOverlay: function() {
            var overlayContainer = document.querySelector('.vbp-preview-overlay-container');
            if (!overlayContainer) return;

            var overlayContent = overlayContainer.querySelector('.vbp-preview-overlay-content');
            if (overlayContent) {
                overlayContent.style.opacity = '0';
                overlayContent.style.transform = 'scale(0.9) translateY(20px)';
            }

            setTimeout(function() {
                overlayContainer.classList.remove('vbp-preview-overlay--visible');
                overlayContainer.innerHTML = '';
            }, 200);

            this.activeOverlay = null;
        },

        /**
         * Vuelve al frame anterior en el historial
         */
        goBack: function() {
            if (this.navigationHistory.length === 0) return;

            var previousFrameId = this.navigationHistory.pop();
            this.updateBackButton();

            // Navegar sin agregar al historial
            var frameContainer = document.querySelector('.vbp-preview-frame-container');
            if (!frameContainer) return;

            var currentFrame = frameContainer.querySelector('.vbp-preview-frame');
            var targetElement = document.querySelector('[data-vbp-id="' + previousFrameId + '"]');

            if (!targetElement) return;

            var newFrame = targetElement.cloneNode(true);
            newFrame.className = 'vbp-preview-frame vbp-preview-frame--entering';

            // Usar transicion inversa (slide-right para simular volver)
            this.performTransition(frameContainer, currentFrame, newFrame, 'slide-right', 300);

            this.currentFrameId = previousFrameId;
            this.updateFrameName();

            var self = this;
            setTimeout(function() {
                self.setupPreviewInteractionHandlers();
            }, 300);
        },

        /**
         * Reinicia el preview al estado inicial
         */
        restartPreview: function() {
            this.navigationHistory = [];
            this.activeOverlay = null;
            this.updateBackButton();

            // Volver al primer frame
            if (this.frames.length > 0) {
                var firstFrameId = this.frames[0].id;
                var frameContainer = document.querySelector('.vbp-preview-frame-container');

                if (frameContainer) {
                    frameContainer.innerHTML = '';
                    this.cloneCanvasToPreview();
                    this.currentFrameId = firstFrameId;
                    this.updateFrameName();
                    this.setupPreviewInteractionHandlers();
                }
            }
        },

        /**
         * Actualiza el estado del boton "Volver"
         */
        updateBackButton: function() {
            var backButton = document.querySelector('.vbp-preview-back');
            if (backButton) {
                backButton.disabled = this.navigationHistory.length === 0;
            }
        },

        /**
         * Actualiza el nombre del frame mostrado
         */
        updateFrameName: function() {
            var frameNameElement = document.querySelector('.vbp-preview-frame-name');
            if (!frameNameElement) return;

            var currentFrameData = this.frames.find(function(frameItem) {
                return frameItem.id === this.currentFrameId;
            }, this);

            frameNameElement.textContent = currentFrameData ? currentFrameData.name : 'Frame';
        },

        /**
         * Anima un elemento usando Animation Builder
         */
        animateElement: function(elementId, interactionConfig) {
            if (!window.VBPAnimationBuilder) {
                console.warn('[VBP Prototype Mode] Animation Builder not available');
                return;
            }

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return;

            // Si hay una animacion especifica configurada
            if (interactionConfig.animationName) {
                var preset = window.VBPAnimationBuilder.presets[interactionConfig.animationName];
                if (preset) {
                    // Aplicar animacion usando Animation Builder
                    var animationCss = window.VBPAnimationBuilder.generateCSS({
                        name: interactionConfig.animationName,
                        keyframes: preset.keyframes,
                        duration: interactionConfig.duration || preset.duration,
                        easing: interactionConfig.easing || preset.easing
                    });

                    // Inyectar CSS y aplicar
                    var styleTag = document.createElement('style');
                    styleTag.textContent = animationCss;
                    document.head.appendChild(styleTag);

                    targetElement.style.animation = interactionConfig.animationName + ' ' +
                        (interactionConfig.duration || '0.5') + 's ' +
                        (interactionConfig.easing || 'ease-out');

                    // Limpiar despues
                    var cleanupDuration = parseInt(interactionConfig.duration) || 500;
                    setTimeout(function() {
                        targetElement.style.animation = '';
                        styleTag.remove();
                    }, cleanupDuration + 100);
                }
            }
        },

        /**
         * Establece el valor de una variable de estado
         */
        setVariable: function(variableName, value) {
            this.variables[variableName] = value;

            document.dispatchEvent(new CustomEvent('vbp:prototypeVariableChanged', {
                detail: { name: variableName, value: value }
            }));

            // Actualizar elementos que dependen de esta variable
            this.updateVariableDependentElements(variableName);
        },

        /**
         * Obtiene el valor de una variable
         */
        getVariable: function(variableName) {
            return this.variables[variableName];
        },

        /**
         * Actualiza elementos que dependen de una variable
         */
        updateVariableDependentElements: function(variableName) {
            // Buscar elementos con condiciones de visibilidad basadas en la variable
            // Este es un placeholder para funcionalidad avanzada
            console.log('[VBP Prototype Mode] Variable changed:', variableName, '=', this.variables[variableName]);
        },

        // ============================================
        // Frames Management
        // ============================================

        /**
         * Agrega un nuevo frame al prototipo
         */
        addFrame: function(name, elementId) {
            var newFrame = {
                id: generateFrameId(),
                name: name || 'Frame ' + (this.frames.length + 1),
                elementId: elementId || null,
                isStartFrame: this.frames.length === 0
            };

            this.frames.push(newFrame);
            this.saveToDocument();

            return newFrame;
        },

        /**
         * Elimina un frame
         */
        removeFrame: function(frameId) {
            var frameIndex = this.frames.findIndex(function(frameItem) {
                return frameItem.id === frameId;
            });

            if (frameIndex === -1) return false;

            this.frames.splice(frameIndex, 1);

            // Si era el frame inicial, marcar el siguiente
            if (this.frames.length > 0 && !this.frames.some(function(frameItem) { return frameItem.isStartFrame; })) {
                this.frames[0].isStartFrame = true;
            }

            this.saveToDocument();
            return true;
        },

        /**
         * Establece el frame inicial
         */
        setStartFrame: function(frameId) {
            this.frames.forEach(function(frameItem) {
                frameItem.isStartFrame = frameItem.id === frameId;
            });
            this.saveToDocument();
        },

        // ============================================
        // Persistence
        // ============================================

        /**
         * Guarda los datos del prototipo en el documento
         */
        saveToDocument: function() {
            if (!window.Alpine || !Alpine.store || !Alpine.store('vbp')) return;

            var store = Alpine.store('vbp');
            var prototypeData = {
                interactions: this.interactions,
                frames: this.frames,
                variables: this.variables
            };

            store.settings.prototypeData = prototypeData;
            store.markAsDirty();
        },

        /**
         * Carga los datos del prototipo desde el documento
         */
        loadFromDocument: function() {
            if (!window.Alpine || !Alpine.store || !Alpine.store('vbp')) return;

            var store = Alpine.store('vbp');
            var prototypeData = store.settings && store.settings.prototypeData;

            if (prototypeData) {
                this.interactions = prototypeData.interactions || {};
                this.frames = prototypeData.frames || [];
                this.variables = prototypeData.variables || {};
                this.updateConnections();
            }
        },

        // ============================================
        // Export
        // ============================================

        /**
         * Exporta el prototipo como HTML interactivo standalone
         */
        exportAsHTML: function() {
            var htmlContent = this.generateStandaloneHTML();

            var blob = new Blob([htmlContent], { type: 'text/html' });
            var downloadUrl = URL.createObjectURL(blob);

            var downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = 'prototype-' + Date.now() + '.html';
            downloadLink.click();

            URL.revokeObjectURL(downloadUrl);

            if (window.VBPToast) {
                window.VBPToast.show('Prototipo exportado como HTML', 'success');
            }
        },

        /**
         * Genera el HTML standalone del prototipo
         */
        generateStandaloneHTML: function() {
            var canvasContent = document.querySelector('.vbp-canvas__content');
            if (!canvasContent) return '';

            var contentClone = canvasContent.cloneNode(true);

            // Limpiar elementos de edicion
            var editOnlyElements = contentClone.querySelectorAll('.vbp-selector, .vbp-resize-handle, .vbp-element-controls');
            editOnlyElements.forEach(function(element) {
                element.remove();
            });

            var interactionsJson = JSON.stringify(this.interactions);
            var framesJson = JSON.stringify(this.frames);
            var variablesJson = JSON.stringify(this.variables);

            var htmlTemplate = [
                '<!DOCTYPE html>',
                '<html lang="es">',
                '<head>',
                '  <meta charset="UTF-8">',
                '  <meta name="viewport" content="width=device-width, initial-scale=1.0">',
                '  <title>Prototipo Interactivo</title>',
                '  <style>',
                this.generateStandaloneCSS(),
                '  </style>',
                '</head>',
                '<body>',
                '  <div id="prototype-container">',
                contentClone.outerHTML,
                '  </div>',
                '  <script>',
                '    var prototypeInteractions = ' + interactionsJson + ';',
                '    var prototypeFrames = ' + framesJson + ';',
                '    var prototypeVariables = ' + variablesJson + ';',
                this.generateStandaloneJS(),
                '  </script>',
                '</body>',
                '</html>'
            ].join('\n');

            return htmlTemplate;
        },

        /**
         * Genera el CSS para el HTML standalone
         */
        generateStandaloneCSS: function() {
            return [
                '* { box-sizing: border-box; margin: 0; padding: 0; }',
                'body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; }',
                '#prototype-container { max-width: 1200px; margin: 0 auto; background: #fff; min-height: 100vh; }',
                '[data-vbp-id] { transition: all 0.3s ease; }',
                '[data-vbp-id][data-clickable] { cursor: pointer; }',
                '.prototype-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }',
                '.prototype-overlay.visible { display: flex; }',
                '.prototype-overlay-content { background: #fff; border-radius: 8px; max-width: 90%; max-height: 90%; overflow: auto; }'
            ].join('\n');
        },

        /**
         * Genera el JavaScript para el HTML standalone
         */
        generateStandaloneJS: function() {
            return [
                '(function() {',
                '  var currentFrame = null;',
                '  var history = [];',
                '  var activeOverlay = null;',
                '',
                '  function init() {',
                '    Object.keys(prototypeInteractions).forEach(function(elementId) {',
                '      var element = document.querySelector("[data-vbp-id=\'" + elementId + "\']");',
                '      if (!element) return;',
                '      element.setAttribute("data-clickable", "true");',
                '      var interactions = prototypeInteractions[elementId];',
                '      interactions.forEach(function(interaction) {',
                '        if (interaction.trigger === "click") {',
                '          element.addEventListener("click", function(e) {',
                '            e.preventDefault();',
                '            executeAction(interaction);',
                '          });',
                '        }',
                '      });',
                '    });',
                '  }',
                '',
                '  function executeAction(interaction) {',
                '    switch(interaction.action) {',
                '      case "navigate":',
                '        var target = document.querySelector("[data-vbp-id=\'" + interaction.target + "\']");',
                '        if (target) {',
                '          target.scrollIntoView({ behavior: "smooth" });',
                '        }',
                '        break;',
                '      case "open_url":',
                '        window.open(interaction.url, "_blank");',
                '        break;',
                '      case "overlay":',
                '        showOverlay(interaction.target);',
                '        break;',
                '    }',
                '  }',
                '',
                '  function showOverlay(targetId) {',
                '    var target = document.querySelector("[data-vbp-id=\'" + targetId + "\']");',
                '    if (!target) return;',
                '    var overlay = document.createElement("div");',
                '    overlay.className = "prototype-overlay visible";',
                '    var content = document.createElement("div");',
                '    content.className = "prototype-overlay-content";',
                '    content.innerHTML = target.innerHTML;',
                '    overlay.appendChild(content);',
                '    overlay.addEventListener("click", function(e) {',
                '      if (e.target === overlay) overlay.remove();',
                '    });',
                '    document.body.appendChild(overlay);',
                '  }',
                '',
                '  document.addEventListener("DOMContentLoaded", init);',
                '})();'
            ].join('\n');
        },

        /**
         * Verifica si un elemento tiene interacciones
         */
        hasInteractions: function(elementId) {
            return this.interactions[elementId] && this.interactions[elementId].length > 0;
        },

        /**
         * Obtiene el numero de interacciones de un elemento
         */
        getInteractionCount: function(elementId) {
            return this.interactions[elementId] ? this.interactions[elementId].length : 0;
        }
    };

    // ============================================
    // Integracion con Alpine.js Store
    // ============================================

    document.addEventListener('alpine:init', function() {
        if (window.Alpine && Alpine.store) {
            // Extender el store VBP con propiedades de prototipo
            var existingStore = Alpine.store('vbp');
            if (existingStore) {
                existingStore.prototype = {
                    enabled: false,
                    previewMode: false,
                    panelOpen: false,

                    get interactions() {
                        return window.VBPPrototypeMode.interactions;
                    },

                    get connections() {
                        return window.VBPPrototypeMode.connections;
                    },

                    get frames() {
                        return window.VBPPrototypeMode.frames;
                    },

                    get variables() {
                        return window.VBPPrototypeMode.variables;
                    },

                    toggleMode: function() {
                        window.VBPPrototypeMode.toggleMode();
                        this.enabled = window.VBPPrototypeMode.enabled;
                    },

                    startPreview: function() {
                        window.VBPPrototypeMode.startPreview();
                        this.previewMode = true;
                    },

                    stopPreview: function() {
                        window.VBPPrototypeMode.stopPreview();
                        this.previewMode = false;
                    },

                    addInteraction: function(elementId, interaction) {
                        return window.VBPPrototypeMode.addInteraction(elementId, interaction);
                    },

                    removeInteraction: function(elementId, interactionId) {
                        return window.VBPPrototypeMode.removeInteraction(elementId, interactionId);
                    },

                    navigate: function(targetId, animation, duration) {
                        window.VBPPrototypeMode.navigate(targetId, animation, duration);
                    },

                    goBack: function() {
                        window.VBPPrototypeMode.goBack();
                    },

                    setVariable: function(name, value) {
                        window.VBPPrototypeMode.setVariable(name, value);
                    }
                };
            }
        }

        // Registrar componente Alpine
        if (window.Alpine && Alpine.data) {
            Alpine.data('vbpPrototypeMode', function() {
                return Object.assign({}, window.VBPPrototypeMode, {
                    init: function() {
                        window.VBPPrototypeMode.init.call(window.VBPPrototypeMode);
                    }
                });
            });
        }
    });

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPPrototypeMode.init();
        });
    } else {
        window.VBPPrototypeMode.init();
    }

})();

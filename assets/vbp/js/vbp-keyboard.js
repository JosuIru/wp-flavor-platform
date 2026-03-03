/**
 * Visual Builder Pro - Keyboard Shortcuts
 * Gestión de atajos de teclado
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.data('vbpKeyboard', function() {
        return {
            /**
             * Mapeo de atajos a acciones
             */
            shortcuts: {
                // Archivo
                'ctrl+s': 'save',
                'ctrl+shift+s': 'saveAs',
                'ctrl+p': 'preview',
                'ctrl+e': 'export',
                'ctrl+t': 'templates',

                // Edición
                'ctrl+z': 'undo',
                'ctrl+shift+z': 'redo',
                'ctrl+y': 'redo',
                'ctrl+c': 'copy',
                'ctrl+x': 'cut',
                'ctrl+v': 'paste',
                'ctrl+d': 'duplicate',
                'ctrl+shift+c': 'copyStyles',
                'ctrl+shift+v': 'pasteStyles',
                'ctrl+shift+r': 'resetStyles',

                // Agrupar
                'ctrl+g': 'group',
                'ctrl+shift+u': 'ungroup',

                // Selección y navegación
                'delete': 'delete',
                'backspace': 'delete',
                'escape': 'deselect',
                'ctrl+a': 'selectAll',
                'ctrl+shift+a': 'invertSelection',
                'ctrl+alt+a': 'selectSimilar',
                'enter': 'editInline',
                'f2': 'editInline',
                'tab': 'selectNext',
                'shift+tab': 'selectPrevious',

                // Navegación y posicionamiento
                'arrowup': 'nudgeUp',
                'arrowdown': 'nudgeDown',
                'arrowleft': 'nudgeLeft',
                'arrowright': 'nudgeRight',
                'shift+arrowup': 'nudgeUpLarge',
                'shift+arrowdown': 'nudgeDownLarge',
                'shift+arrowleft': 'nudgeLeftLarge',
                'shift+arrowright': 'nudgeRightLarge',
                'ctrl+arrowup': 'moveToTop',
                'ctrl+arrowdown': 'moveToBottom',

                // Navegación jerárquica
                'alt+arrowup': 'selectParent',
                'alt+arrowdown': 'selectFirstChild',
                'alt+enter': 'centerInViewport',

                // Duplicado avanzado
                'ctrl+shift+d': 'duplicateInPlace',

                // Colapsar/expandir
                'ctrl+.': 'toggleCollapse',

                // Spacing presets
                'alt+1': 'setSpacing8',
                'alt+2': 'setSpacing16',
                'alt+3': 'setSpacing24',
                'alt+4': 'setSpacing32',

                // Flip/transformaciones visuales
                'alt+shift+h': 'flipHorizontal',
                'alt+shift+v': 'flipVertical',

                // Reset
                'ctrl+shift+0': 'resetPosition',

                // Bookmarks de canvas
                'ctrl+shift+1': 'goToBookmark1',
                'ctrl+shift+2': 'goToBookmark2',
                'ctrl+shift+3': 'goToBookmark3',
                'ctrl+alt+1': 'setBookmark1',
                'ctrl+alt+2': 'setBookmark2',
                'ctrl+alt+3': 'setBookmark3',

                // Quick rename
                'ctrl+alt+r': 'quickRename',

                // Aspect ratio lock
                'ctrl+shift+p': 'toggleAspectRatioLock',

                // Smart guides
                'ctrl+alt+g': 'toggleSmartGuides',

                // Measure tool
                'm': 'toggleMeasureTool',

                // Favoritos/presets
                'ctrl+alt+k': 'saveAsFavorite',
                'ctrl+alt+shift+k': 'openFavorites',

                // CSS Variables
                'ctrl+alt+c': 'openCSSVariables',

                // Version compare
                'ctrl+alt+d': 'openVersionCompare',

                // Rotación
                'r': 'rotate15',
                'shift+r': 'rotateNeg15',
                'ctrl+r': 'rotate90',
                'ctrl+alt+0': 'resetRotation',

                // Snap to grid
                'ctrl+shift+.': 'toggleSnapToGrid',

                // Constraints
                'ctrl+alt+t': 'toggleConstraintTop',
                'ctrl+alt+b': 'toggleConstraintBottom',
                'ctrl+alt+l': 'toggleConstraintLeft',
                'ctrl+alt+right': 'toggleConstraintRight',

                // Efectos
                'ctrl+alt+shift+s': 'openShadowEditor',
                'ctrl+alt+shift+x': 'openGradientEditor',

                // Auto-layout
                'shift+a': 'toggleAutoLayout',
                'ctrl+shift+arrowup': 'decreaseGap',
                'ctrl+shift+arrowdown': 'increaseGap',

                // Zoom
                'ctrl++': 'zoomIn',
                'ctrl+=': 'zoomIn',
                'ctrl+-': 'zoomOut',
                'ctrl+0': 'zoomReset',
                'ctrl+1': 'zoom100',
                'ctrl+2': 'zoom200',
                'ctrl+5': 'zoom50',

                // Paneles
                'ctrl+\\': 'togglePanels',
                'ctrl+b': 'toggleBlocksPanel',
                'ctrl+i': 'toggleInspectorPanel',
                'ctrl+l': 'toggleLayersPanel',

                // Modales
                'ctrl+k': 'commandPalette',
                '?': 'help',
                'f1': 'help',
                'ctrl+,': 'settings',

                // Alineación (Alt + tecla)
                'alt+l': 'alignLeft',
                'alt+c': 'alignCenterH',
                'alt+r': 'alignRight',
                'alt+t': 'alignTop',
                'alt+m': 'alignCenterV',
                'alt+b': 'alignBottom',

                // Distribución
                'ctrl+alt+h': 'distributeH',
                'ctrl+alt+v': 'distributeV',

                // Stack (apilar)
                'ctrl+shift+arrowright': 'stackHorizontal',
                'ctrl+shift+arrowdown': 'stackVertical',

                // Transformaciones
                'ctrl+[': 'sendBackward',
                'ctrl+]': 'bringForward',
                'ctrl+shift+[': 'sendToBack',
                'ctrl+shift+]': 'bringToFront',
                'ctrl+m': 'matchSize',
                'ctrl+alt+s': 'swapElements',

                // Envolver/Desenvolver
                'ctrl+shift+w': 'wrapInContainer',
                'ctrl+shift+u': 'ungroup',

                // Bloqueo
                'ctrl+shift+l': 'toggleLock',

                // Grid y guías
                'ctrl+\'': 'toggleGrid',
                'ctrl+;': 'toggleGuides',

                // Dimensiones
                'ctrl+shift+f': 'fitContent',
                'ctrl+alt+f': 'fillParent',

                // Visibilidad
                'ctrl+shift+h': 'toggleVisibility',
                'ctrl+alt+h': 'hideOthers',

                // Búsqueda
                'ctrl+f': 'findElements',

                // Exportar/Importar
                'ctrl+shift+e': 'copyAsHTML',
                'ctrl+alt+e': 'copyAsJSON',
                'ctrl+shift+v': 'pasteStyles',
                'ctrl+alt+v': 'pasteFromJSON',

                // Extras
                'ctrl+u': 'unsplash',
                'ctrl+shift+g': 'saveAsGlobal',
                'ctrl+h': 'versionHistory'
            },

            /**
             * Clipboard para copy/paste de elementos
             */
            clipboard: null,

            /**
             * Clipboard para copy/paste de estilos
             */
            styleClipboard: null,

            /**
             * Inicialización
             */
            init: function() {
                var self = this;
                document.addEventListener('keydown', function(e) {
                    self.handleKeydown(e);
                });

                // Listener para ejecutar acciones desde command palette
                document.addEventListener('vbp:executeAction', function(e) {
                    if (e.detail && e.detail.action) {
                        self.executeAction(e.detail.action);
                    }
                });

                // Inicializar multi-select box
                this.initMultiSelectBox();

                // Cargar preferencias desde localStorage
                this.loadPreferences();

                // Cargar favoritos
                var storedFavorites = localStorage.getItem('vbp_favorites');
                if (storedFavorites) {
                    this.favorites = JSON.parse(storedFavorites);
                }

                // Exponer globalmente para acceso desde modales
                window.vbpKeyboard = this;
            },

            /**
             * Cargar preferencias guardadas
             */
            loadPreferences: function() {
                // Smart guides
                var smartGuides = localStorage.getItem('vbp_smart_guides');
                if (smartGuides === 'true') {
                    this.smartGuidesEnabled = true;
                    this.initSmartGuides();
                }

                // Snap to grid
                var snapToGrid = localStorage.getItem('vbp_snap_to_grid');
                if (snapToGrid === 'true') {
                    this.snapToGridEnabled = true;
                }

                // Grid size
                var gridSize = localStorage.getItem('vbp_grid_size');
                if (gridSize) {
                    this.gridSize = parseInt(gridSize, 10);
                }
            },

            /**
             * Manejador de keydown
             */
            handleKeydown: function(event) {
                // No interceptar si estamos en un campo editable
                if (event.target.closest('[contenteditable], input, textarea, select')) {
                    // Solo permitir Escape en campos editables
                    if (event.key === 'Escape') {
                        event.target.blur();
                        this.executeAction('deselect');
                    }
                    return;
                }

                // Construir la clave del atajo
                var key = '';
                if (event.ctrlKey || event.metaKey) key += 'ctrl+';
                if (event.shiftKey) key += 'shift+';
                if (event.altKey) key += 'alt+';
                key += event.key.toLowerCase();

                // Buscar acción
                var action = this.shortcuts[key];
                if (action) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.executeAction(action);
                }
            },

            /**
             * Ejecutar acción
             */
            executeAction: function(action) {
                var store = Alpine.store('vbp');
                var self = this;

                switch (action) {
                    // === ARCHIVO ===
                    case 'save':
                        this.saveDocument();
                        break;

                    case 'saveAs':
                        this.saveAsTemplate();
                        break;

                    case 'preview':
                        this.openPreview();
                        break;

                    // === EDICIÓN ===
                    case 'undo':
                        store.undo();
                        this.showNotification('Deshacer');
                        break;

                    case 'redo':
                        store.redo();
                        this.showNotification('Rehacer');
                        break;

                    case 'copy':
                        this.copySelection();
                        break;

                    case 'cut':
                        this.cutSelection();
                        break;

                    case 'paste':
                        this.pasteClipboard();
                        break;

                    case 'duplicate':
                        this.duplicateSelection();
                        break;

                    case 'copyStyles':
                        this.copyStyles();
                        break;

                    case 'pasteStyles':
                        this.pasteStyles();
                        break;

                    case 'resetStyles':
                        this.resetStyles();
                        break;

                    case 'group':
                        this.groupSelection();
                        break;

                    case 'ungroup':
                        this.ungroupSelection();
                        break;

                    case 'delete':
                        this.deleteSelection();
                        break;

                    // === SELECCIÓN ===
                    case 'deselect':
                        store.clearSelection();
                        break;

                    case 'selectAll':
                        store.setSelection(store.elements.map(function(el) {
                            return el.id;
                        }));
                        this.showNotification('Todos seleccionados');
                        break;

                    case 'invertSelection':
                        this.invertSelection();
                        break;

                    case 'selectSimilar':
                        this.selectSimilar();
                        break;

                    case 'selectNext':
                        this.selectAdjacentElement(1);
                        break;

                    case 'selectPrevious':
                        this.selectAdjacentElement(-1);
                        break;

                    case 'editInline':
                        this.startInlineEdit();
                        break;

                    // === NAVEGACIÓN ===
                    case 'nudgeUp':
                        this.nudgeSelection(0, -1);
                        break;

                    case 'nudgeDown':
                        this.nudgeSelection(0, 1);
                        break;

                    case 'nudgeLeft':
                        this.nudgeSelection(-1, 0);
                        break;

                    case 'nudgeRight':
                        this.nudgeSelection(1, 0);
                        break;

                    case 'nudgeUpLarge':
                        this.nudgeSelection(0, -10);
                        break;

                    case 'nudgeDownLarge':
                        this.nudgeSelection(0, 10);
                        break;

                    case 'nudgeLeftLarge':
                        this.nudgeSelection(-10, 0);
                        break;

                    case 'nudgeRightLarge':
                        this.nudgeSelection(10, 0);
                        break;

                    case 'moveToTop':
                        this.moveSelectionToEdge('top');
                        break;

                    case 'moveToBottom':
                        this.moveSelectionToEdge('bottom');
                        break;

                    // === NAVEGACIÓN JERÁRQUICA ===
                    case 'selectParent':
                        this.selectParent();
                        break;

                    case 'selectFirstChild':
                        this.selectFirstChild();
                        break;

                    case 'centerInViewport':
                        this.centerInViewport();
                        break;

                    // === DUPLICADO AVANZADO ===
                    case 'duplicateInPlace':
                        this.duplicateInPlace();
                        break;

                    // === COLAPSAR ===
                    case 'toggleCollapse':
                        this.toggleCollapse();
                        break;

                    // === SPACING PRESETS ===
                    case 'setSpacing8':
                        this.setSpacingPreset(8);
                        break;

                    case 'setSpacing16':
                        this.setSpacingPreset(16);
                        break;

                    case 'setSpacing24':
                        this.setSpacingPreset(24);
                        break;

                    case 'setSpacing32':
                        this.setSpacingPreset(32);
                        break;

                    // === FLIP/TRANSFORMACIONES ===
                    case 'flipHorizontal':
                        this.flipElement('horizontal');
                        break;

                    case 'flipVertical':
                        this.flipElement('vertical');
                        break;

                    // === RESET ===
                    case 'resetPosition':
                        this.resetPosition();
                        break;

                    // === BOOKMARKS ===
                    case 'setBookmark1':
                        this.setBookmark(1);
                        break;

                    case 'setBookmark2':
                        this.setBookmark(2);
                        break;

                    case 'setBookmark3':
                        this.setBookmark(3);
                        break;

                    case 'goToBookmark1':
                        this.goToBookmark(1);
                        break;

                    case 'goToBookmark2':
                        this.goToBookmark(2);
                        break;

                    case 'goToBookmark3':
                        this.goToBookmark(3);
                        break;

                    // === QUICK RENAME ===
                    case 'quickRename':
                        this.quickRename();
                        break;

                    // === ASPECT RATIO ===
                    case 'toggleAspectRatioLock':
                        this.toggleAspectRatioLock();
                        break;

                    // === SMART GUIDES ===
                    case 'toggleSmartGuides':
                        this.toggleSmartGuides();
                        break;

                    // === MEASURE TOOL ===
                    case 'toggleMeasureTool':
                        this.toggleMeasureTool();
                        break;

                    // === FAVORITOS ===
                    case 'saveAsFavorite':
                        this.saveAsFavorite();
                        break;

                    case 'openFavorites':
                        this.openFavoritesPanel();
                        break;

                    // === CSS VARIABLES ===
                    case 'openCSSVariables':
                        this.openCSSVariablesEditor();
                        break;

                    // === VERSION COMPARE ===
                    case 'openVersionCompare':
                        this.openVersionCompare();
                        break;

                    // === ROTACIÓN ===
                    case 'rotate15':
                        this.rotateSelection(15);
                        break;

                    case 'rotateNeg15':
                        this.rotateSelection(-15);
                        break;

                    case 'rotate90':
                        this.rotateSelection(90);
                        break;

                    case 'resetRotation':
                        this.resetRotation();
                        break;

                    // === SNAP TO GRID ===
                    case 'toggleSnapToGrid':
                        this.toggleSnapToGrid();
                        break;

                    // === CONSTRAINTS ===
                    case 'toggleConstraintTop':
                        this.toggleConstraint('top');
                        break;

                    case 'toggleConstraintBottom':
                        this.toggleConstraint('bottom');
                        break;

                    case 'toggleConstraintLeft':
                        this.toggleConstraint('left');
                        break;

                    case 'toggleConstraintRight':
                        this.toggleConstraint('right');
                        break;

                    // === EFECTOS ===
                    case 'openShadowEditor':
                        this.openShadowEditor();
                        break;

                    case 'openGradientEditor':
                        this.openGradientEditor();
                        break;

                    // === AUTO-LAYOUT ===
                    case 'toggleAutoLayout':
                        this.toggleAutoLayout();
                        break;

                    case 'decreaseGap':
                        this.adjustAutoLayoutGap(-4);
                        break;

                    case 'increaseGap':
                        this.adjustAutoLayoutGap(4);
                        break;

                    // === ZOOM ===
                    case 'zoomIn':
                        store.zoom = Math.min(200, store.zoom + 10);
                        this.showZoomFeedback(store.zoom);
                        break;

                    case 'zoomOut':
                        store.zoom = Math.max(25, store.zoom - 10);
                        this.showZoomFeedback(store.zoom);
                        break;

                    case 'zoomReset':
                        store.zoom = 100;
                        this.showZoomFeedback(100);
                        break;

                    case 'zoom100':
                        store.zoom = 100;
                        this.showZoomFeedback(100);
                        break;

                    case 'zoom50':
                        store.zoom = 50;
                        this.showZoomFeedback(50);
                        break;

                    case 'zoom200':
                        store.zoom = 200;
                        this.showZoomFeedback(200);
                        break;

                    // === PANELES ===
                    case 'togglePanels':
                        var allVisible = store.panels.blocks && store.panels.inspector && store.panels.layers;
                        store.panels.blocks = !allVisible;
                        store.panels.inspector = !allVisible;
                        store.panels.layers = !allVisible;
                        break;

                    case 'toggleBlocksPanel':
                        store.panels.blocks = !store.panels.blocks;
                        break;

                    case 'toggleInspectorPanel':
                        store.panels.inspector = !store.panels.inspector;
                        break;

                    case 'toggleLayersPanel':
                        store.panels.layers = !store.panels.layers;
                        break;

                    // === MODALES Y ACCIONES ESPECIALES ===
                    case 'export':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', {
                            detail: { modal: 'export' }
                        }));
                        this.showNotification('Exportar...');
                        break;

                    case 'templates':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', {
                            detail: { modal: 'templates' }
                        }));
                        this.showNotification('Templates...');
                        break;

                    case 'commandPalette':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', {
                            detail: { modal: 'commandPalette' }
                        }));
                        break;

                    case 'help':
                        this.showHelpModal();
                        break;

                    case 'settings':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', {
                            detail: { modal: 'settings' }
                        }));
                        this.showNotification('Configuración...');
                        break;

                    // === ALINEACIÓN ===
                    case 'alignLeft':
                        this.alignElements('left');
                        break;

                    case 'alignCenterH':
                        this.alignElements('centerH');
                        break;

                    case 'alignRight':
                        this.alignElements('right');
                        break;

                    case 'alignTop':
                        this.alignElements('top');
                        break;

                    case 'alignCenterV':
                        this.alignElements('centerV');
                        break;

                    case 'alignBottom':
                        this.alignElements('bottom');
                        break;

                    // === DISTRIBUCIÓN ===
                    case 'distributeH':
                        this.distributeElements('horizontal');
                        break;

                    case 'distributeV':
                        this.distributeElements('vertical');
                        break;

                    // === STACK ===
                    case 'stackHorizontal':
                        this.stackElements('horizontal');
                        break;

                    case 'stackVertical':
                        this.stackElements('vertical');
                        break;

                    // === ORDEN Z ===
                    case 'sendBackward':
                        this.changeZOrder('backward');
                        break;

                    case 'bringForward':
                        this.changeZOrder('forward');
                        break;

                    case 'sendToBack':
                        this.changeZOrder('back');
                        break;

                    case 'bringToFront':
                        this.changeZOrder('front');
                        break;

                    // === TRANSFORMACIONES ===
                    case 'matchSize':
                        this.matchSize();
                        break;

                    case 'swapElements':
                        this.swapElements();
                        break;

                    case 'wrapInContainer':
                        this.wrapInContainer();
                        break;

                    // === BLOQUEO ===
                    case 'toggleLock':
                        this.toggleLock();
                        break;

                    // === GRID Y GUÍAS ===
                    case 'toggleGrid':
                        this.toggleGrid();
                        break;

                    case 'toggleGuides':
                        this.toggleGuides();
                        break;

                    // === DIMENSIONES ===
                    case 'fitContent':
                        this.fitContent();
                        break;

                    case 'fillParent':
                        this.fillParent();
                        break;

                    // === VISIBILIDAD ===
                    case 'toggleVisibility':
                        this.toggleVisibility();
                        break;

                    case 'hideOthers':
                        this.hideOthers();
                        break;

                    // === BÚSQUEDA ===
                    case 'findElements':
                        this.openFindDialog();
                        break;

                    // === EXPORTAR/IMPORTAR ===
                    case 'copyAsHTML':
                        this.copyAsHTML();
                        break;

                    case 'copyAsJSON':
                        this.copyAsJSON();
                        break;

                    case 'pasteFromJSON':
                        this.pasteFromJSON();
                        break;

                    // === HERRAMIENTAS AVANZADAS ===
                    case 'toggleAspectRatioLock':
                        this.toggleAspectRatioLock();
                        break;

                    case 'toggleSmartGuides':
                        this.toggleSmartGuides();
                        break;

                    case 'toggleMeasureTool':
                        this.toggleMeasureTool();
                        break;

                    case 'saveAsFavorite':
                        this.saveAsFavorite();
                        break;

                    case 'openFavorites':
                        this.openFavorites();
                        break;

                    case 'openCSSVariables':
                        this.openCSSVariables();
                        break;

                    case 'openVersionCompare':
                        this.openVersionCompare();
                        break;

                    // === ROTACIÓN ===
                    case 'rotate15':
                        this.rotateSelection(15);
                        break;

                    case 'rotateNeg15':
                        this.rotateSelection(-15);
                        break;

                    case 'rotate90':
                        this.rotateSelection(90);
                        break;

                    case 'resetRotation':
                        this.resetRotation();
                        break;

                    // === SNAP Y CONSTRAINTS ===
                    case 'toggleSnapToGrid':
                        this.toggleSnapToGrid();
                        break;

                    case 'toggleConstraintTop':
                        this.toggleConstraint('top');
                        break;

                    case 'toggleConstraintBottom':
                        this.toggleConstraint('bottom');
                        break;

                    case 'toggleConstraintLeft':
                        this.toggleConstraint('left');
                        break;

                    case 'toggleConstraintRight':
                        this.toggleConstraint('right');
                        break;

                    // === EFECTOS ===
                    case 'openShadowEditor':
                        this.openShadowEditor();
                        break;

                    case 'openGradientEditor':
                        this.openGradientEditor();
                        break;

                    // === AUTO-LAYOUT ===
                    case 'toggleAutoLayout':
                        this.toggleAutoLayout();
                        break;

                    case 'decreaseGap':
                        this.adjustAutoLayoutGap(-4);
                        break;

                    case 'increaseGap':
                        this.adjustAutoLayoutGap(4);
                        break;
                }
            },

            /**
             * Guardar documento
             */
            saveDocument: function() {
                var store = Alpine.store('vbp');

                if (!store.postId) {
                    this.showNotification('No hay documento para guardar', 'error');
                    return;
                }

                if (!store.isDirty) {
                    this.showNotification('No hay cambios pendientes');
                    return;
                }

                this.showNotification('Guardando...');

                if (window.vbpApi && window.vbpApi.saveDocument) {
                    window.vbpApi.saveDocument(store.postId, store.elements, store.settings)
                        .then(function(result) {
                            if (result.success) {
                                Alpine.store('vbp').isDirty = false;
                            }
                        });
                } else {
                    // Fallback: dispatch evento para que otro manejador lo capture
                    document.dispatchEvent(new CustomEvent('vbp:requestSave', {
                        detail: {
                            postId: store.postId,
                            elements: store.elements,
                            settings: store.settings
                        }
                    }));
                }
            },

            /**
             * Guardar como template
             */
            saveAsTemplate: function() {
                var store = Alpine.store('vbp');
                var name = prompt('Nombre del template:', 'Mi Template');

                if (name && window.vbpApi) {
                    window.vbpApi.exportTemplate(store.elements, name)
                        .then(function(result) {
                            if (result.success) {
                                alert('Template guardado correctamente');
                            }
                        });
                }
            },

            /**
             * Abrir preview
             */
            openPreview: function() {
                var store = Alpine.store('vbp');
                if (store.postId) {
                    var previewUrl = VBP_Config.siteUrl + '?p=' + store.postId + '&preview=true';
                    window.open(previewUrl, '_blank');
                }
            },

            /**
             * Copiar selección
             */
            copySelection: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    return;
                }

                // Copiar elementos seleccionados
                this.clipboard = store.selection.elementIds.map(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        return JSON.parse(JSON.stringify(element));
                    }
                    return null;
                }).filter(function(el) { return el !== null; });

                this.showNotification('Copiado (' + this.clipboard.length + ')');
            },

            /**
             * Cortar selección
             */
            cutSelection: function() {
                this.copySelection();
                this.deleteSelection();
                this.showNotification('Cortado');
            },

            /**
             * Pegar desde clipboard
             */
            pasteClipboard: function() {
                var store = Alpine.store('vbp');
                var self = this;

                if (!this.clipboard || this.clipboard.length === 0) {
                    this.showNotification('Nada que pegar', 'warning');
                    return;
                }

                // Guardar en historial antes de pegar
                store.saveToHistory();

                var newIds = [];

                this.clipboard.forEach(function(elementData) {
                    // Generar nuevo ID
                    var newElement = JSON.parse(JSON.stringify(elementData));
                    newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
                    newElement.name = elementData.name + ' (copia)';

                    store.elements.push(newElement);
                    newIds.push(newElement.id);
                });

                store.isDirty = true;
                store.setSelection(newIds);

                this.showNotification('Pegado (' + newIds.length + ')');
            },

            /**
             * Duplicar selección
             */
            duplicateSelection: function() {
                var store = Alpine.store('vbp');
                var count = 0;

                store.selection.elementIds.forEach(function(id) {
                    if (store.duplicateElement(id)) {
                        count++;
                    }
                });

                if (count > 0) {
                    this.showNotification('Duplicado (' + count + ')');
                }
            },

            /**
             * Copiar estilos del elemento seleccionado
             */
            copyStyles: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un elemento para copiar estilos', 'warning');
                    return;
                }

                var element = store.getElement(store.selection.elementIds[0]);
                if (!element || !element.styles) {
                    this.showNotification('El elemento no tiene estilos', 'warning');
                    return;
                }

                // Copiar estilos excluyendo posición y tamaño
                var estilosParaCopiar = JSON.parse(JSON.stringify(element.styles));

                // Guardar el tipo de elemento para compatibilidad
                this.styleClipboard = {
                    type: element.type,
                    styles: estilosParaCopiar
                };

                this.showNotification('Estilos copiados');
            },

            /**
             * Pegar estilos al elemento seleccionado
             */
            pasteStyles: function() {
                var store = Alpine.store('vbp');
                var self = this;

                if (!this.styleClipboard) {
                    this.showNotification('No hay estilos para pegar', 'warning');
                    return;
                }

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para aplicar estilos', 'warning');
                    return;
                }

                // Guardar en historial antes de aplicar
                store.saveToHistory();

                var count = 0;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        // Fusionar estilos preservando posición y tamaño
                        var posicion = element.styles && element.styles.position ? element.styles.position : {};
                        var tamano = element.styles && element.styles.size ? element.styles.size : {};

                        var nuevosEstilos = JSON.parse(JSON.stringify(self.styleClipboard.styles));
                        nuevosEstilos.position = posicion;
                        nuevosEstilos.size = tamano;

                        store.updateElement(id, { styles: nuevosEstilos });
                        count++;
                    }
                });

                store.isDirty = true;
                this.showNotification('Estilos aplicados a ' + count + ' elemento(s)');
            },

            /**
             * Resetear estilos a valores por defecto
             */
            resetStyles: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para resetear estilos', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                var count = 0;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        // Obtener estilos por defecto según tipo
                        var estilosPorDefecto = {
                            typography: {},
                            colors: {},
                            spacing: {},
                            border: {},
                            shadow: {},
                            advanced: {}
                        };

                        // Preservar posición y tamaño
                        if (element.styles && element.styles.position) {
                            estilosPorDefecto.position = element.styles.position;
                        }
                        if (element.styles && element.styles.size) {
                            estilosPorDefecto.size = element.styles.size;
                        }

                        store.updateElement(id, { styles: estilosPorDefecto });
                        count++;
                    }
                });

                store.isDirty = true;
                this.showNotification('Estilos reseteados en ' + count + ' elemento(s)');
            },

            /**
             * Agrupar elementos seleccionados
             */
            groupSelection: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length < 2) {
                    this.showNotification('Selecciona al menos 2 elementos para agrupar', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                // Obtener elementos seleccionados
                var elementosAGrupar = [];
                var indicesMasAlto = 0;

                store.selection.elementIds.forEach(function(id) {
                    var elemento = store.getElement(id);
                    var indice = store.elements.findIndex(function(el) { return el.id === id; });
                    if (elemento) {
                        elementosAGrupar.push(JSON.parse(JSON.stringify(elemento)));
                        if (indice > indicesMasAlto) indicesMasAlto = indice;
                    }
                });

                // Eliminar elementos originales
                store.selection.elementIds.forEach(function(id) {
                    var indice = store.elements.findIndex(function(el) { return el.id === id; });
                    if (indice !== -1) {
                        store.elements.splice(indice, 1);
                    }
                });

                // Crear el grupo
                var grupoId = 'el_' + Math.random().toString(36).substr(2, 9);
                var grupo = {
                    id: grupoId,
                    type: 'group',
                    name: 'Grupo (' + elementosAGrupar.length + ' elementos)',
                    visible: true,
                    locked: false,
                    children: elementosAGrupar,
                    data: {},
                    styles: {}
                };

                // Insertar grupo en la posición del elemento más alto
                var posicionInsercion = Math.min(indicesMasAlto, store.elements.length);
                store.elements.splice(posicionInsercion, 0, grupo);

                store.isDirty = true;
                store.setSelection([grupoId]);

                this.showNotification('Grupo creado con ' + elementosAGrupar.length + ' elementos');
            },

            /**
             * Desagrupar elementos
             */
            ungroupSelection: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un grupo para desagrupar', 'warning');
                    return;
                }

                var grupoId = store.selection.elementIds[0];
                var grupo = store.getElement(grupoId);

                if (!grupo || grupo.type !== 'group' || !grupo.children) {
                    this.showNotification('El elemento seleccionado no es un grupo', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                // Encontrar índice del grupo
                var indiceGrupo = store.elements.findIndex(function(el) { return el.id === grupoId; });

                // Eliminar el grupo
                store.elements.splice(indiceGrupo, 1);

                // Insertar los hijos en la posición del grupo
                var nuevosIds = [];
                grupo.children.forEach(function(hijo, i) {
                    // Generar nuevos IDs para evitar conflictos
                    var nuevoHijo = JSON.parse(JSON.stringify(hijo));
                    nuevoHijo.id = 'el_' + Math.random().toString(36).substr(2, 9);
                    store.elements.splice(indiceGrupo + i, 0, nuevoHijo);
                    nuevosIds.push(nuevoHijo.id);
                });

                store.isDirty = true;
                store.setSelection(nuevosIds);

                this.showNotification('Grupo disuelto: ' + nuevosIds.length + ' elementos');
            },

            /**
             * Eliminar selección
             */
            deleteSelection: function() {
                var store = Alpine.store('vbp');
                var count = store.selection.elementIds.length;

                if (count === 0) return;

                // Guardar en historial
                store.saveToHistory();

                // Eliminar de atrás hacia adelante para evitar problemas de índices
                var ids = store.selection.elementIds.slice().reverse();
                ids.forEach(function(id) {
                    var index = store.elements.findIndex(function(el) {
                        return el.id === id;
                    });
                    if (index !== -1) {
                        store.elements.splice(index, 1);
                    }
                });

                store.isDirty = true;
                store.clearSelection();

                this.showNotification('Eliminado (' + count + ')');
            },

            /**
             * Iniciar edición inline del elemento seleccionado
             */
            startInlineEdit: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);

                if (!element) return;

                // Tipos de elementos que soportan edición inline
                var tiposEditables = ['heading', 'text', 'paragraph', 'button', 'link', 'list'];

                if (tiposEditables.indexOf(element.type) === -1) {
                    return;
                }

                // Buscar el elemento en el canvas y activar contenteditable
                var elementoCanvas = document.querySelector('[data-element-id="' + elementId + '"]');
                if (!elementoCanvas) {
                    elementoCanvas = document.querySelector('#' + elementId);
                }

                if (elementoCanvas) {
                    // Buscar el contenedor de texto editable
                    var textoEditable = elementoCanvas.querySelector('[contenteditable]');
                    if (!textoEditable) {
                        // Si no tiene contenteditable, buscamos el contenido principal
                        var contenidoPrincipal = elementoCanvas.querySelector('.vbp-element-content, .vbp-heading-text, .vbp-text-content');
                        if (contenidoPrincipal) {
                            textoEditable = contenidoPrincipal;
                            textoEditable.setAttribute('contenteditable', 'true');
                        }
                    }

                    if (textoEditable) {
                        textoEditable.focus();
                        // Seleccionar todo el texto
                        var seleccion = window.getSelection();
                        var rango = document.createRange();
                        rango.selectNodeContents(textoEditable);
                        seleccion.removeAllRanges();
                        seleccion.addRange(rango);
                    }
                }

                // También disparar evento para que el inspector sepa
                document.dispatchEvent(new CustomEvent('vbp:startInlineEdit', {
                    detail: { elementId: elementId, type: element.type }
                }));
            },

            /**
             * Invertir selección (seleccionar no seleccionados)
             */
            invertSelection: function() {
                var store = Alpine.store('vbp');

                if (store.elements.length === 0) return;

                var currentIds = store.selection.elementIds;
                var newIds = store.elements
                    .map(function(el) { return el.id; })
                    .filter(function(id) { return currentIds.indexOf(id) === -1; });

                store.setSelection(newIds);
                this.showNotification('Selección invertida (' + newIds.length + ')');
            },

            /**
             * Seleccionar elementos del mismo tipo
             */
            selectSimilar: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento primero', 'warning');
                    return;
                }

                // Obtener tipos de elementos seleccionados
                var tipos = [];
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && tipos.indexOf(element.type) === -1) {
                        tipos.push(element.type);
                    }
                });

                // Seleccionar todos los elementos de esos tipos
                var newIds = store.elements
                    .filter(function(el) { return tipos.indexOf(el.type) !== -1; })
                    .map(function(el) { return el.id; });

                store.setSelection(newIds);
                this.showNotification('Seleccionados similares (' + newIds.length + ')');
            },

            /**
             * Seleccionar elemento adyacente (navegación con Tab)
             * @param {number} direction - 1 para siguiente, -1 para anterior
             */
            selectAdjacentElement: function(direction) {
                var store = Alpine.store('vbp');

                if (store.elements.length === 0) return;

                var currentIndex = -1;

                // Si hay un elemento seleccionado, encontrar su índice
                if (store.selection.elementIds.length === 1) {
                    currentIndex = store.elements.findIndex(function(el) {
                        return el.id === store.selection.elementIds[0];
                    });
                }

                // Calcular nuevo índice
                var newIndex = currentIndex + direction;

                // Wrap around (circular)
                if (newIndex < 0) {
                    newIndex = store.elements.length - 1;
                } else if (newIndex >= store.elements.length) {
                    newIndex = 0;
                }

                // Seleccionar el elemento
                var element = store.elements[newIndex];
                if (element) {
                    store.setSelection([element.id]);

                    // Hacer scroll al elemento
                    var domElement = document.querySelector('[data-element-id="' + element.id + '"]');
                    if (domElement) {
                        domElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            },

            /**
             * Mover elementos seleccionados píxel a píxel (nudge)
             * @param {number} dx - Desplazamiento horizontal en px
             * @param {number} dy - Desplazamiento vertical en px
             */
            nudgeSelection: function(dx, dy) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) return;

                // Verificar si hay elementos bloqueados
                var hayBloqueados = false;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && element.locked) {
                        hayBloqueados = true;
                    }
                });

                if (hayBloqueados) {
                    this.showNotification('Hay elementos bloqueados en la selección', 'warning');
                    return;
                }

                // No guardar en historial para cada nudge pequeño (se guarda al final)
                var self = this;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.position) estilos.position = {};

                    // Obtener posición actual
                    var currentLeft = parseFloat(estilos.position.left) || 0;
                    var currentTop = parseFloat(estilos.position.top) || 0;

                    // Aplicar desplazamiento
                    estilos.position.left = (currentLeft + dx) + 'px';
                    estilos.position.top = (currentTop + dy) + 'px';

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
            },

            /**
             * Mover selección al borde
             */
            moveSelectionToEdge: function(edge) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) return;

                var id = store.selection.elementIds[0];
                var currentIndex = store.elements.findIndex(function(el) {
                    return el.id === id;
                });

                if (currentIndex === -1) return;

                var newIndex = edge === 'top' ? 0 : store.elements.length - 1;

                if (currentIndex !== newIndex) {
                    store.moveElement(currentIndex, newIndex);
                }
            },

            /**
             * Alinear elementos seleccionados
             * @param {string} alignment - left, centerH, right, top, centerV, bottom
             */
            alignElements: function(alignment) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length < 2) {
                    this.showNotification('Selecciona al menos 2 elementos para alinear', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                // Obtener bounds de todos los elementos seleccionados
                var bounds = this.getSelectionBounds(store);
                if (!bounds) return;

                var self = this;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || !element.styles) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles));
                    if (!estilos.position) estilos.position = {};

                    var elementBounds = self.getElementBounds(element);
                    if (!elementBounds) return;

                    switch (alignment) {
                        case 'left':
                            estilos.position.left = bounds.left + 'px';
                            break;
                        case 'centerH':
                            var centerX = bounds.left + (bounds.width / 2) - (elementBounds.width / 2);
                            estilos.position.left = centerX + 'px';
                            break;
                        case 'right':
                            var rightPos = bounds.left + bounds.width - elementBounds.width;
                            estilos.position.left = rightPos + 'px';
                            break;
                        case 'top':
                            estilos.position.top = bounds.top + 'px';
                            break;
                        case 'centerV':
                            var centerY = bounds.top + (bounds.height / 2) - (elementBounds.height / 2);
                            estilos.position.top = centerY + 'px';
                            break;
                        case 'bottom':
                            var bottomPos = bounds.top + bounds.height - elementBounds.height;
                            estilos.position.top = bottomPos + 'px';
                            break;
                    }

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                var labels = {
                    'left': 'Alineado a la izquierda',
                    'centerH': 'Centrado horizontalmente',
                    'right': 'Alineado a la derecha',
                    'top': 'Alineado arriba',
                    'centerV': 'Centrado verticalmente',
                    'bottom': 'Alineado abajo'
                };
                this.showNotification(labels[alignment] || 'Alineado');
            },

            /**
             * Cambiar orden Z de elementos
             */
            changeZOrder: function(direction) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                store.saveToHistory();

                var self = this;
                store.selection.elementIds.forEach(function(id) {
                    var currentIndex = store.elements.findIndex(function(el) {
                        return el.id === id;
                    });

                    if (currentIndex === -1) return;

                    var newIndex;
                    switch (direction) {
                        case 'backward':
                            newIndex = Math.max(0, currentIndex - 1);
                            break;
                        case 'forward':
                            newIndex = Math.min(store.elements.length - 1, currentIndex + 1);
                            break;
                        case 'back':
                            newIndex = 0;
                            break;
                        case 'front':
                            newIndex = store.elements.length - 1;
                            break;
                    }

                    if (newIndex !== currentIndex) {
                        store.moveElement(currentIndex, newIndex);
                    }
                });

                store.isDirty = true;

                var labels = {
                    'backward': '⬇ Un nivel atrás',
                    'forward': '⬆ Un nivel adelante',
                    'back': '⬇ Al fondo',
                    'front': '⬆ Al frente'
                };
                this.showNotification(labels[direction]);
            },

            /**
             * Igualar tamaño de elementos al primero seleccionado
             */
            matchSize: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length < 2) {
                    this.showNotification('Selecciona al menos 2 elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                // El primer elemento seleccionado es la referencia
                var referenceId = store.selection.elementIds[0];
                var refElement = store.getElement(referenceId);
                if (!refElement) return;

                var refBounds = this.getElementBounds(refElement);
                if (!refBounds) return;

                var count = 0;
                var self = this;

                store.selection.elementIds.slice(1).forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.size) estilos.size = {};

                    estilos.size.width = refBounds.width + 'px';
                    estilos.size.height = refBounds.height + 'px';

                    store.updateElement(id, { styles: estilos });
                    count++;
                });

                store.isDirty = true;
                this.showNotification('📐 ' + count + ' elemento(s) igualado(s)');
            },

            /**
             * Intercambiar posición de dos elementos
             */
            swapElements: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 2) {
                    this.showNotification('Selecciona exactamente 2 elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                var id1 = store.selection.elementIds[0];
                var id2 = store.selection.elementIds[1];

                var el1 = store.getElement(id1);
                var el2 = store.getElement(id2);

                if (!el1 || !el2) return;
                if (el1.locked || el2.locked) {
                    this.showNotification('No se pueden intercambiar elementos bloqueados', 'warning');
                    return;
                }

                var bounds1 = this.getElementBounds(el1);
                var bounds2 = this.getElementBounds(el2);

                if (!bounds1 || !bounds2) return;

                // Intercambiar posiciones
                var estilos1 = JSON.parse(JSON.stringify(el1.styles || {}));
                var estilos2 = JSON.parse(JSON.stringify(el2.styles || {}));

                if (!estilos1.position) estilos1.position = {};
                if (!estilos2.position) estilos2.position = {};

                // Guardar posiciones
                var temp = {
                    left: estilos1.position.left,
                    top: estilos1.position.top
                };

                estilos1.position.left = estilos2.position.left;
                estilos1.position.top = estilos2.position.top;
                estilos2.position.left = temp.left;
                estilos2.position.top = temp.top;

                store.updateElement(id1, { styles: estilos1 });
                store.updateElement(id2, { styles: estilos2 });

                store.isDirty = true;
                this.showNotification('🔄 Posiciones intercambiadas');
            },

            /**
             * Envolver elementos en un contenedor
             */
            wrapInContainer: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para envolver', 'warning');
                    return;
                }

                store.saveToHistory();

                // Obtener bounds combinados
                var bounds = this.getSelectionBounds(store);
                if (!bounds) return;

                // Copiar elementos seleccionados
                var elementosAEnvolver = [];
                var indicesMasAlto = 0;

                store.selection.elementIds.forEach(function(id) {
                    var elemento = store.getElement(id);
                    var indice = store.elements.findIndex(function(el) { return el.id === id; });
                    if (elemento) {
                        elementosAEnvolver.push(JSON.parse(JSON.stringify(elemento)));
                        if (indice > indicesMasAlto) indicesMasAlto = indice;
                    }
                });

                // Eliminar elementos originales
                store.selection.elementIds.forEach(function(id) {
                    var indice = store.elements.findIndex(function(el) { return el.id === id; });
                    if (indice !== -1) {
                        store.elements.splice(indice, 1);
                    }
                });

                // Crear contenedor
                var containerId = 'el_' + Math.random().toString(36).substr(2, 9);
                var container = {
                    id: containerId,
                    type: 'container',
                    name: 'Contenedor (' + elementosAEnvolver.length + ' elementos)',
                    visible: true,
                    locked: false,
                    children: elementosAEnvolver,
                    data: {},
                    styles: {
                        position: {
                            left: bounds.left + 'px',
                            top: bounds.top + 'px'
                        },
                        size: {
                            width: bounds.width + 'px',
                            height: bounds.height + 'px'
                        }
                    }
                };

                // Insertar contenedor
                var posicionInsercion = Math.min(indicesMasAlto, store.elements.length);
                store.elements.splice(posicionInsercion, 0, container);

                store.isDirty = true;
                store.setSelection([containerId]);

                this.showNotification('📦 Envueltos en contenedor');
            },

            /**
             * Apilar elementos seleccionados (horizontal o vertical)
             * @param {string} direction - horizontal, vertical
             */
            stackElements: function(direction) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length < 2) {
                    this.showNotification('Selecciona al menos 2 elementos para apilar', 'warning');
                    return;
                }

                store.saveToHistory();

                var self = this;
                var elementos = [];
                var gap = 16; // Espacio entre elementos

                // Obtener elementos con sus bounds actuales
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && !element.locked) {
                        var bounds = self.getElementBounds(element);
                        if (bounds) {
                            elementos.push({
                                id: id,
                                element: element,
                                bounds: bounds
                            });
                        }
                    }
                });

                if (elementos.length < 2) return;

                // Ordenar por posición actual
                if (direction === 'horizontal') {
                    elementos.sort(function(a, b) { return a.bounds.left - b.bounds.left; });
                } else {
                    elementos.sort(function(a, b) { return a.bounds.top - b.bounds.top; });
                }

                // Posición inicial del primer elemento
                var posActual = direction === 'horizontal'
                    ? elementos[0].bounds.left
                    : elementos[0].bounds.top;

                // Apilar elementos uno tras otro
                elementos.forEach(function(el) {
                    var estilos = JSON.parse(JSON.stringify(el.element.styles || {}));
                    if (!estilos.position) estilos.position = {};

                    if (direction === 'horizontal') {
                        estilos.position.left = posActual + 'px';
                        posActual += el.bounds.width + gap;
                    } else {
                        estilos.position.top = posActual + 'px';
                        posActual += el.bounds.height + gap;
                    }

                    store.updateElement(el.id, { styles: estilos });
                });

                store.isDirty = true;
                var label = direction === 'horizontal' ? 'horizontalmente' : 'verticalmente';
                this.showNotification('📚 Apilados ' + label + ' (' + elementos.length + ')');
            },

            /**
             * Distribuir elementos seleccionados
             * @param {string} direction - horizontal, vertical
             */
            distributeElements: function(direction) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length < 3) {
                    this.showNotification('Selecciona al menos 3 elementos para distribuir', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                var self = this;
                var elementos = [];

                // Obtener elementos con sus bounds
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        var bounds = self.getElementBounds(element);
                        if (bounds) {
                            elementos.push({
                                id: id,
                                element: element,
                                bounds: bounds
                            });
                        }
                    }
                });

                if (elementos.length < 3) return;

                // Ordenar por posición
                if (direction === 'horizontal') {
                    elementos.sort(function(a, b) { return a.bounds.left - b.bounds.left; });
                } else {
                    elementos.sort(function(a, b) { return a.bounds.top - b.bounds.top; });
                }

                // Calcular espaciado uniforme
                var primero = elementos[0];
                var ultimo = elementos[elementos.length - 1];

                if (direction === 'horizontal') {
                    var totalWidth = ultimo.bounds.left + ultimo.bounds.width - primero.bounds.left;
                    var elementosWidth = elementos.reduce(function(sum, el) { return sum + el.bounds.width; }, 0);
                    var espacioTotal = totalWidth - elementosWidth;
                    var espacioEntre = espacioTotal / (elementos.length - 1);

                    var posActual = primero.bounds.left;
                    elementos.forEach(function(el) {
                        var estilos = JSON.parse(JSON.stringify(el.element.styles || {}));
                        if (!estilos.position) estilos.position = {};
                        estilos.position.left = posActual + 'px';
                        store.updateElement(el.id, { styles: estilos });
                        posActual += el.bounds.width + espacioEntre;
                    });
                } else {
                    var totalHeight = ultimo.bounds.top + ultimo.bounds.height - primero.bounds.top;
                    var elementosHeight = elementos.reduce(function(sum, el) { return sum + el.bounds.height; }, 0);
                    var espacioTotalV = totalHeight - elementosHeight;
                    var espacioEntreV = espacioTotalV / (elementos.length - 1);

                    var posActualV = primero.bounds.top;
                    elementos.forEach(function(el) {
                        var estilos = JSON.parse(JSON.stringify(el.element.styles || {}));
                        if (!estilos.position) estilos.position = {};
                        estilos.position.top = posActualV + 'px';
                        store.updateElement(el.id, { styles: estilos });
                        posActualV += el.bounds.height + espacioEntreV;
                    });
                }

                store.isDirty = true;
                this.showNotification('Distribuido ' + (direction === 'horizontal' ? 'horizontalmente' : 'verticalmente'));
            },

            /**
             * Obtener bounds de un elemento
             */
            getElementBounds: function(element) {
                if (!element || !element.styles) return null;

                var pos = element.styles.position || {};
                var size = element.styles.size || {};

                return {
                    left: parseFloat(pos.left) || 0,
                    top: parseFloat(pos.top) || 0,
                    width: parseFloat(size.width) || 100,
                    height: parseFloat(size.height) || 100
                };
            },

            /**
             * Ajustar elemento al tamaño del contenido
             */
            fitContent: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                store.saveToHistory();

                var self = this;
                var count = 0;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    // Buscar el elemento en el DOM para obtener su contenido
                    var domEl = document.querySelector('[data-element-id="' + id + '"]');
                    if (!domEl) return;

                    // Calcular tamaño del contenido
                    var contenido = domEl.querySelector('.vbp-element-content');
                    if (contenido) {
                        var rect = contenido.getBoundingClientRect();
                        var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                        if (!estilos.size) estilos.size = {};

                        // Ajustar con un pequeño padding
                        estilos.size.width = Math.ceil(rect.width + 20) + 'px';
                        estilos.size.height = 'auto';

                        store.updateElement(id, { styles: estilos });
                        count++;
                    }
                });

                store.isDirty = true;
                this.showNotification('📐 ' + count + ' elemento(s) ajustado(s)');
            },

            /**
             * Expandir elemento para llenar el contenedor padre
             */
            fillParent: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                store.saveToHistory();

                var self = this;
                var count = 0;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.size) estilos.size = {};
                    if (!estilos.position) estilos.position = {};

                    // Establecer tamaño al 100%
                    estilos.size.width = '100%';
                    estilos.size.height = 'auto';
                    estilos.position.left = '0';
                    estilos.position.top = estilos.position.top || '0';

                    store.updateElement(id, { styles: estilos });
                    count++;
                });

                store.isDirty = true;
                this.showNotification('📏 ' + count + ' elemento(s) expandido(s)');
            },

            /**
             * Mostrar/ocultar cuadrícula
             */
            toggleGrid: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var isVisible = canvas.classList.toggle('vbp-show-grid');

                // Crear CSS de grid si no existe
                if (!document.getElementById('vbp-grid-styles')) {
                    var style = document.createElement('style');
                    style.id = 'vbp-grid-styles';
                    style.textContent = '.vbp-canvas.vbp-show-grid { background-image: linear-gradient(rgba(139, 180, 250, 0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(139, 180, 250, 0.1) 1px, transparent 1px); background-size: 20px 20px; }';
                    document.head.appendChild(style);
                }

                // Guardar preferencia
                localStorage.setItem('vbp_grid_visible', isVisible);

                this.showNotification(isVisible ? '⊞ Cuadrícula visible' : '⊞ Cuadrícula oculta');
            },

            /**
             * Toggle visibilidad de elementos seleccionados
             */
            toggleVisibility: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para ocultar/mostrar', 'warning');
                    return;
                }

                store.saveToHistory();

                var count = 0;
                var allHidden = true;

                // Verificar si todos están ocultos
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && element.visible !== false) {
                        allHidden = false;
                    }
                });

                // Toggle: si todos ocultos, mostrar; si no, ocultar
                var newVisibility = allHidden;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        store.updateElement(id, { visible: newVisibility });
                        count++;
                    }
                });

                store.isDirty = true;
                this.showNotification(newVisibility ? '👁 ' + count + ' visible(s)' : '👁‍🗨 ' + count + ' oculto(s)');
            },

            /**
             * Ocultar todos excepto los seleccionados
             */
            hideOthers: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para mantener visibles', 'warning');
                    return;
                }

                store.saveToHistory();

                var selectedIds = store.selection.elementIds;
                var hiddenCount = 0;

                store.elements.forEach(function(element) {
                    if (selectedIds.indexOf(element.id) === -1) {
                        store.updateElement(element.id, { visible: false });
                        hiddenCount++;
                    } else {
                        store.updateElement(element.id, { visible: true });
                    }
                });

                store.isDirty = true;
                this.showNotification('👁‍🗨 ' + hiddenCount + ' oculto(s), ' + selectedIds.length + ' visible(s)');
            },

            /**
             * Copiar elemento seleccionado como HTML
             */
            copyAsHTML: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento para copiar', 'warning');
                    return;
                }

                var self = this;
                var htmlParts = [];

                store.selection.elementIds.forEach(function(id) {
                    var domEl = document.querySelector('[data-element-id="' + id + '"]');
                    if (domEl) {
                        // Clonar y limpiar atributos de editor
                        var clone = domEl.cloneNode(true);
                        clone.removeAttribute('data-element-id');
                        clone.removeAttribute('x-data');
                        clone.removeAttribute('x-bind');
                        clone.classList.remove('vbp-element', 'vbp-selected', 'vbp-hover');

                        // Limpiar clases vbp-* del clon
                        clone.querySelectorAll('*').forEach(function(el) {
                            var classes = Array.from(el.classList);
                            classes.forEach(function(cls) {
                                if (cls.startsWith('vbp-')) {
                                    el.classList.remove(cls);
                                }
                            });
                        });

                        htmlParts.push(clone.outerHTML);
                    }
                });

                if (htmlParts.length > 0) {
                    var html = htmlParts.join('\n\n');

                    // Formatear HTML básico
                    html = html.replace(/></g, '>\n<');

                    navigator.clipboard.writeText(html).then(function() {
                        self.showNotification('📋 HTML copiado al portapapeles');
                    }).catch(function() {
                        self.showNotification('Error al copiar HTML', 'error');
                    });
                }
            },

            /**
             * Pegar elemento desde JSON del portapapeles
             */
            pasteFromJSON: function() {
                var store = Alpine.store('vbp');
                var self = this;

                navigator.clipboard.readText().then(function(text) {
                    try {
                        var data = JSON.parse(text);
                        var elements = Array.isArray(data) ? data : [data];

                        // Validar que sean elementos válidos
                        var validElements = elements.filter(function(el) {
                            return el && el.type;
                        });

                        if (validElements.length === 0) {
                            self.showNotification('El JSON no contiene elementos válidos', 'error');
                            return;
                        }

                        store.saveToHistory();

                        var newIds = [];
                        validElements.forEach(function(elementData) {
                            // Generar nuevo ID
                            var newElement = JSON.parse(JSON.stringify(elementData));
                            newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
                            newElement.name = newElement.name || newElement.type;

                            store.elements.push(newElement);
                            newIds.push(newElement.id);
                        });

                        store.isDirty = true;
                        store.setSelection(newIds);

                        self.showNotification('📥 ' + newIds.length + ' elemento(s) importado(s)');
                    } catch (e) {
                        self.showNotification('El contenido no es JSON válido', 'error');
                    }
                }).catch(function() {
                    self.showNotification('No se pudo acceder al portapapeles', 'error');
                });
            },

            /**
             * Copiar elemento seleccionado como JSON
             */
            copyAsJSON: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento para copiar', 'warning');
                    return;
                }

                var self = this;
                var elements = [];

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        // Copiar sin ID para que sea reutilizable
                        var cleanElement = JSON.parse(JSON.stringify(element));
                        delete cleanElement.id;
                        elements.push(cleanElement);
                    }
                });

                if (elements.length > 0) {
                    var json = JSON.stringify(elements.length === 1 ? elements[0] : elements, null, 2);

                    navigator.clipboard.writeText(json).then(function() {
                        self.showNotification('📋 JSON copiado al portapapeles');
                    }).catch(function() {
                        self.showNotification('Error al copiar JSON', 'error');
                    });
                }
            },

            /**
             * Abrir diálogo de búsqueda de elementos
             */
            openFindDialog: function() {
                var self = this;
                var store = Alpine.store('vbp');

                // Crear modal de búsqueda
                var modalId = 'vbp-find-modal';
                var existingModal = document.getElementById(modalId);
                if (existingModal) {
                    existingModal.remove();
                }

                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay" style="z-index: 10001;">';
                modalHtml += '<div class="vbp-modal vbp-find-modal" style="max-width: 400px;">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>🔍 Buscar Elementos</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body" style="padding: 16px;">';
                modalHtml += '<input type="text" id="vbp-find-input" class="vbp-find-input" placeholder="Buscar por nombre o tipo..." style="width: 100%; padding: 12px; border: 1px solid #313244; background: #11111b; color: #cdd6f4; border-radius: 6px; font-size: 14px; margin-bottom: 12px;">';
                modalHtml += '<div id="vbp-find-results" class="vbp-find-results" style="max-height: 300px; overflow-y: auto;"></div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var modal = document.getElementById(modalId);
                var input = document.getElementById('vbp-find-input');
                var results = document.getElementById('vbp-find-results');

                // Focus en el input
                input.focus();

                // Renderizar resultados
                function renderResults(query) {
                    var filtered = store.elements.filter(function(el) {
                        var name = (el.name || el.type).toLowerCase();
                        var type = el.type.toLowerCase();
                        var q = query.toLowerCase();
                        return name.indexOf(q) !== -1 || type.indexOf(q) !== -1;
                    });

                    if (filtered.length === 0) {
                        results.innerHTML = '<p style="color: #6c7086; padding: 12px; text-align: center;">No se encontraron elementos</p>';
                        return;
                    }

                    var html = '';
                    filtered.forEach(function(el) {
                        var isSelected = store.selection.elementIds.indexOf(el.id) !== -1;
                        html += '<div class="vbp-find-item" data-id="' + el.id + '" style="padding: 10px 12px; border-bottom: 1px solid #313244; cursor: pointer; display: flex; align-items: center; gap: 10px;' + (isSelected ? ' background: rgba(139, 180, 250, 0.1);' : '') + '">';
                        html += '<span style="opacity: 0.5;">' + el.type + '</span>';
                        html += '<span style="flex: 1;">' + (el.name || el.type) + '</span>';
                        if (el.locked) html += '<span style="opacity: 0.5;">🔒</span>';
                        if (el.visible === false) html += '<span style="opacity: 0.5;">👁‍🗨</span>';
                        html += '</div>';
                    });

                    results.innerHTML = html;

                    // Event listeners para cada resultado
                    results.querySelectorAll('.vbp-find-item').forEach(function(item) {
                        item.addEventListener('click', function() {
                            var id = this.dataset.id;
                            store.setSelection([id]);

                            // Scroll al elemento
                            var domEl = document.querySelector('[data-element-id="' + id + '"]');
                            if (domEl) {
                                domEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }

                            modal.remove();
                        });

                        item.addEventListener('mouseenter', function() {
                            this.style.background = 'rgba(139, 180, 250, 0.15)';
                        });

                        item.addEventListener('mouseleave', function() {
                            var id = this.dataset.id;
                            var isSelected = store.selection.elementIds.indexOf(id) !== -1;
                            this.style.background = isSelected ? 'rgba(139, 180, 250, 0.1)' : '';
                        });
                    });
                }

                // Mostrar todos inicialmente
                renderResults('');

                // Buscar al escribir
                input.addEventListener('input', function() {
                    renderResults(this.value);
                });

                // Cerrar con Escape
                var closeOnEscape = function(e) {
                    if (e.key === 'Escape') {
                        modal.remove();
                        document.removeEventListener('keydown', closeOnEscape);
                    }
                };
                document.addEventListener('keydown', closeOnEscape);

                // Cerrar al hacer clic fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
            },

            /**
             * Mostrar/ocultar guías
             */
            toggleGuides: function() {
                var guidesContainer = document.querySelector('.vbp-guides-container');
                var rulerH = document.getElementById('vbp-ruler-h');
                var rulerV = document.getElementById('vbp-ruler-v');

                var isVisible = true;

                if (guidesContainer) {
                    isVisible = guidesContainer.style.display !== 'none';
                    guidesContainer.style.display = isVisible ? 'none' : 'block';
                }

                if (rulerH) rulerH.style.opacity = isVisible ? '0.3' : '1';
                if (rulerV) rulerV.style.opacity = isVisible ? '0.3' : '1';

                localStorage.setItem('vbp_guides_visible', !isVisible);

                this.showNotification(!isVisible ? '📏 Guías visibles' : '📏 Guías ocultas');
            },

            /**
             * Bloquear/desbloquear elementos seleccionados
             */
            toggleLock: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para bloquear/desbloquear', 'warning');
                    return;
                }

                // Guardar en historial
                store.saveToHistory();

                var allLocked = true;
                var countLocked = 0;

                // Verificar si todos están bloqueados
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && !element.locked) {
                        allLocked = false;
                    }
                    if (element && element.locked) {
                        countLocked++;
                    }
                });

                // Toggle: si todos bloqueados, desbloquear; si no, bloquear
                var newLockState = !allLocked;
                var count = 0;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        store.updateElement(id, { locked: newLockState });
                        count++;
                    }
                });

                store.isDirty = true;

                if (newLockState) {
                    this.showNotification('🔒 ' + count + ' elemento(s) bloqueado(s)');
                } else {
                    this.showNotification('🔓 ' + count + ' elemento(s) desbloqueado(s)');
                }
            },

            /**
             * Seleccionar elemento padre
             */
            selectParent: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                var currentId = store.selection.elementIds[0];
                var current = store.getElement(currentId);

                if (!current) return;

                // Buscar el padre en todos los elementos
                var parentId = null;
                var self = this;

                function findParent(elements, targetId, currentParentId) {
                    for (var i = 0; i < elements.length; i++) {
                        var el = elements[i];
                        if (el.id === targetId) {
                            return currentParentId;
                        }
                        if (el.children && el.children.length > 0) {
                            var found = findParent(el.children, targetId, el.id);
                            if (found) return found;
                        }
                    }
                    return null;
                }

                parentId = findParent(store.elements, currentId, null);

                if (parentId) {
                    store.setSelection([parentId]);
                    this.showNotification('⬆️ Seleccionado padre');

                    // Scroll al elemento
                    var parentElement = document.querySelector('[data-element-id="' + parentId + '"]');
                    if (parentElement) {
                        parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    this.showNotification('Este elemento no tiene padre', 'info');
                }
            },

            /**
             * Seleccionar primer hijo
             */
            selectFirstChild: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                var currentId = store.selection.elementIds[0];
                var current = store.getElement(currentId);

                if (!current) return;

                if (current.children && current.children.length > 0) {
                    var firstChild = current.children[0];
                    store.setSelection([firstChild.id]);
                    this.showNotification('⬇️ Seleccionado primer hijo');

                    // Scroll al elemento
                    var childElement = document.querySelector('[data-element-id="' + firstChild.id + '"]');
                    if (childElement) {
                        childElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    this.showNotification('Este elemento no tiene hijos', 'info');
                }
            },

            /**
             * Centrar elemento seleccionado en el viewport
             */
            centerInViewport: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = document.querySelector('[data-element-id="' + elementId + '"]');

                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                    this.showNotification('📍 Centrado en viewport');
                }
            },

            /**
             * Duplicar en el mismo lugar (sin offset)
             */
            duplicateInPlace: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para duplicar', 'warning');
                    return;
                }

                store.saveToHistory();
                var newIds = [];
                var self = this;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element) return;

                    // Clonar el elemento (deep clone)
                    var clone = JSON.parse(JSON.stringify(element));
                    clone.id = 'el_' + Math.random().toString(36).substr(2, 9);
                    clone.name = (element.name || element.type) + ' (copia)';

                    // Mismo lugar exacto (sin offset)
                    store.elements.push(clone);
                    newIds.push(clone.id);
                });

                store.setSelection(newIds);
                store.isDirty = true;

                this.showNotification('📋 ' + newIds.length + ' elemento(s) duplicado(s) en el mismo lugar');
            },

            /**
             * Colapsar/expandir contenedor
             */
            toggleCollapse: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona un contenedor', 'warning');
                    return;
                }

                store.saveToHistory();
                var toggledCount = 0;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element) return;

                    // Solo colapsar elementos que pueden tener hijos
                    if (element.children && element.children.length > 0 ||
                        ['container', 'columns', 'row', 'group', 'section'].indexOf(element.type) !== -1) {

                        var isCollapsed = element.collapsed || false;
                        store.updateElement(id, { collapsed: !isCollapsed });
                        toggledCount++;
                    }
                });

                if (toggledCount > 0) {
                    store.isDirty = true;
                    this.showNotification(toggledCount > 0 ? '📁 Toggle colapso' : '📂 Toggle expansión');
                } else {
                    this.showNotification('Solo contenedores pueden colapsarse', 'info');
                }
            },

            /**
             * Aplicar preset de spacing
             */
            setSpacingPreset: function(spacing) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.spacing) estilos.spacing = {};

                    estilos.spacing.padding = spacing + 'px';
                    estilos.spacing.margin = spacing + 'px';

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                this.showNotification('📏 Spacing: ' + spacing + 'px');
            },

            /**
             * Voltear elemento horizontal o verticalmente
             */
            flipElement: function(direction) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para voltear', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.transform) estilos.transform = {};

                    if (direction === 'horizontal') {
                        var currentScaleX = estilos.transform.scaleX || 1;
                        estilos.transform.scaleX = currentScaleX === 1 ? -1 : 1;
                    } else {
                        var currentScaleY = estilos.transform.scaleY || 1;
                        estilos.transform.scaleY = currentScaleY === 1 ? -1 : 1;
                    }

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                var dirLabel = direction === 'horizontal' ? '↔️ Horizontal' : '↕️ Vertical';
                this.showNotification('Flip ' + dirLabel);
            },

            /**
             * Resetear posición de elementos
             */
            resetPosition: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.position) estilos.position = {};

                    estilos.position.top = '0';
                    estilos.position.left = '0';
                    estilos.position.right = 'auto';
                    estilos.position.bottom = 'auto';

                    // También resetear transformaciones de posición
                    if (estilos.transform) {
                        estilos.transform.translateX = 0;
                        estilos.transform.translateY = 0;
                    }

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                this.showNotification('🔄 Posición reseteada');
            },

            /**
             * Bookmarks de canvas
             */
            canvasBookmarks: {},

            /**
             * Guardar bookmark de posición del canvas
             */
            setBookmark: function(index) {
                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                if (!canvasWrapper) return;

                var store = Alpine.store('vbp');

                this.canvasBookmarks[index] = {
                    scrollTop: canvasWrapper.scrollTop,
                    scrollLeft: canvasWrapper.scrollLeft,
                    zoom: store.zoom || 100,
                    selection: store.selection.elementIds.slice()
                };

                // Guardar en localStorage
                localStorage.setItem('vbp_bookmarks', JSON.stringify(this.canvasBookmarks));

                this.showNotification('🔖 Marcador ' + index + ' guardado');
            },

            /**
             * Ir a un bookmark
             */
            goToBookmark: function(index) {
                // Cargar bookmarks si no están cargados
                if (Object.keys(this.canvasBookmarks).length === 0) {
                    var saved = localStorage.getItem('vbp_bookmarks');
                    if (saved) {
                        try {
                            this.canvasBookmarks = JSON.parse(saved);
                        } catch (e) {
                            this.canvasBookmarks = {};
                        }
                    }
                }

                var bookmark = this.canvasBookmarks[index];
                if (!bookmark) {
                    this.showNotification('Marcador ' + index + ' no existe', 'warning');
                    return;
                }

                var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
                var store = Alpine.store('vbp');

                if (canvasWrapper) {
                    canvasWrapper.scrollTo({
                        top: bookmark.scrollTop,
                        left: bookmark.scrollLeft,
                        behavior: 'smooth'
                    });
                }

                if (bookmark.zoom) {
                    store.zoom = bookmark.zoom;
                }

                if (bookmark.selection && bookmark.selection.length > 0) {
                    store.setSelection(bookmark.selection);
                }

                this.showNotification('🔖 Ir a marcador ' + index);
            },

            /**
             * Renombrar elemento rápidamente
             */
            quickRename: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);

                if (!element) return;

                var currentName = element.name || element.type || 'Elemento';
                var newName = prompt('Nuevo nombre:', currentName);

                if (newName && newName !== currentName) {
                    store.saveToHistory();
                    store.updateElement(elementId, { name: newName });
                    store.isDirty = true;
                    this.showNotification('✏️ Renombrado: ' + newName);
                }
            },

            /**
             * Toggle bloqueo de aspect ratio
             */
            toggleAspectRatioLock: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos', 'warning');
                    return;
                }

                store.saveToHistory();
                var count = 0;
                var allLocked = true;

                // Verificar si todos tienen aspect ratio bloqueado
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element && !element.aspectRatioLocked) {
                        allLocked = false;
                    }
                });

                var newLockState = !allLocked;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    // Calcular y guardar aspect ratio actual si se está bloqueando
                    if (newLockState) {
                        var bounds = document.querySelector('[data-element-id="' + id + '"]');
                        if (bounds) {
                            var rect = bounds.getBoundingClientRect();
                            var aspectRatio = rect.width / rect.height;
                            store.updateElement(id, {
                                aspectRatioLocked: true,
                                aspectRatio: aspectRatio
                            });
                        }
                    } else {
                        store.updateElement(id, { aspectRatioLocked: false });
                    }
                    count++;
                });

                store.isDirty = true;
                if (newLockState) {
                    this.showNotification('🔒 Proporción bloqueada (' + count + ')');
                } else {
                    this.showNotification('🔓 Proporción desbloqueada (' + count + ')');
                }
            },

            /**
             * Estado de smart guides
             */
            smartGuidesEnabled: true,

            /**
             * Toggle smart guides
             */
            toggleSmartGuides: function() {
                this.smartGuidesEnabled = !this.smartGuidesEnabled;
                localStorage.setItem('vbp_smart_guides', this.smartGuidesEnabled);

                // Actualizar UI
                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    if (this.smartGuidesEnabled) {
                        canvas.classList.add('vbp-smart-guides-enabled');
                    } else {
                        canvas.classList.remove('vbp-smart-guides-enabled');
                        // Limpiar guías existentes
                        this.clearSmartGuides();
                    }
                }

                this.showNotification(this.smartGuidesEnabled ? '📐 Smart Guides activadas' : '📐 Smart Guides desactivadas');
            },

            /**
             * Mostrar smart guides durante el arrastre
             */
            showSmartGuides: function(draggedElement, allElements) {
                if (!this.smartGuidesEnabled) return;

                this.clearSmartGuides();

                var draggedRect = draggedElement.getBoundingClientRect();
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var canvasRect = canvas.getBoundingClientRect();
                var guides = [];
                var snapThreshold = 5;

                allElements.forEach(function(el) {
                    if (el === draggedElement) return;

                    var rect = el.getBoundingClientRect();

                    // Comparar bordes
                    // Left edge
                    if (Math.abs(draggedRect.left - rect.left) < snapThreshold) {
                        guides.push({ type: 'vertical', position: rect.left - canvasRect.left });
                    }
                    if (Math.abs(draggedRect.left - rect.right) < snapThreshold) {
                        guides.push({ type: 'vertical', position: rect.right - canvasRect.left });
                    }

                    // Right edge
                    if (Math.abs(draggedRect.right - rect.left) < snapThreshold) {
                        guides.push({ type: 'vertical', position: rect.left - canvasRect.left });
                    }
                    if (Math.abs(draggedRect.right - rect.right) < snapThreshold) {
                        guides.push({ type: 'vertical', position: rect.right - canvasRect.left });
                    }

                    // Center horizontal
                    var draggedCenterX = draggedRect.left + draggedRect.width / 2;
                    var elCenterX = rect.left + rect.width / 2;
                    if (Math.abs(draggedCenterX - elCenterX) < snapThreshold) {
                        guides.push({ type: 'vertical', position: elCenterX - canvasRect.left });
                    }

                    // Top edge
                    if (Math.abs(draggedRect.top - rect.top) < snapThreshold) {
                        guides.push({ type: 'horizontal', position: rect.top - canvasRect.top });
                    }
                    if (Math.abs(draggedRect.top - rect.bottom) < snapThreshold) {
                        guides.push({ type: 'horizontal', position: rect.bottom - canvasRect.top });
                    }

                    // Bottom edge
                    if (Math.abs(draggedRect.bottom - rect.top) < snapThreshold) {
                        guides.push({ type: 'horizontal', position: rect.top - canvasRect.top });
                    }
                    if (Math.abs(draggedRect.bottom - rect.bottom) < snapThreshold) {
                        guides.push({ type: 'horizontal', position: rect.bottom - canvasRect.top });
                    }

                    // Center vertical
                    var draggedCenterY = draggedRect.top + draggedRect.height / 2;
                    var elCenterY = rect.top + rect.height / 2;
                    if (Math.abs(draggedCenterY - elCenterY) < snapThreshold) {
                        guides.push({ type: 'horizontal', position: elCenterY - canvasRect.top });
                    }
                });

                // Renderizar guías
                guides.forEach(function(guide) {
                    var line = document.createElement('div');
                    line.className = 'vbp-smart-guide vbp-smart-guide-' + guide.type;

                    if (guide.type === 'vertical') {
                        line.style.cssText = 'position: absolute; left: ' + guide.position + 'px; top: 0; width: 1px; height: 100%; background: #f43f5e; z-index: 9999; pointer-events: none;';
                    } else {
                        line.style.cssText = 'position: absolute; top: ' + guide.position + 'px; left: 0; height: 1px; width: 100%; background: #f43f5e; z-index: 9999; pointer-events: none;';
                    }

                    canvas.appendChild(line);
                });
            },

            /**
             * Limpiar smart guides
             */
            clearSmartGuides: function() {
                var guides = document.querySelectorAll('.vbp-smart-guide');
                guides.forEach(function(guide) {
                    guide.remove();
                });
            },

            /**
             * Estado de la herramienta de medición
             */
            measureToolActive: false,
            measureStartPoint: null,

            /**
             * Toggle herramienta de medición
             */
            toggleMeasureTool: function() {
                this.measureToolActive = !this.measureToolActive;
                var canvas = document.querySelector('.vbp-canvas');

                if (this.measureToolActive) {
                    canvas.classList.add('vbp-measure-mode');
                    canvas.style.cursor = 'crosshair';
                    this.initMeasureTool();
                    this.showNotification('📏 Herramienta de medición activada - Click para medir');
                } else {
                    canvas.classList.remove('vbp-measure-mode');
                    canvas.style.cursor = '';
                    this.removeMeasureTool();
                    this.showNotification('📏 Herramienta de medición desactivada');
                }
            },

            /**
             * Inicializar herramienta de medición
             */
            initMeasureTool: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                this.measureHandler = function(e) {
                    if (!self.measureToolActive) return;

                    var canvasRect = canvas.getBoundingClientRect();
                    var x = e.clientX - canvasRect.left;
                    var y = e.clientY - canvasRect.top;

                    if (!self.measureStartPoint) {
                        // Primer click - establecer punto inicial
                        self.measureStartPoint = { x: x, y: y };
                        self.showMeasurePoint(x, y, 'start');
                    } else {
                        // Segundo click - calcular y mostrar medida
                        var dx = x - self.measureStartPoint.x;
                        var dy = y - self.measureStartPoint.y;
                        var distance = Math.sqrt(dx * dx + dy * dy);

                        self.showMeasureLine(self.measureStartPoint.x, self.measureStartPoint.y, x, y, distance);
                        self.measureStartPoint = null;
                    }
                };

                canvas.addEventListener('click', this.measureHandler);
            },

            /**
             * Mostrar punto de medición
             */
            showMeasurePoint: function(x, y, type) {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var point = document.createElement('div');
                point.className = 'vbp-measure-point vbp-measure-' + type;
                point.style.cssText = 'position: absolute; left: ' + (x - 4) + 'px; top: ' + (y - 4) + 'px; width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; z-index: 9999; pointer-events: none;';
                canvas.appendChild(point);
            },

            /**
             * Mostrar línea de medición
             */
            showMeasureLine: function(x1, y1, x2, y2, distance) {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                // Limpiar mediciones anteriores
                this.clearMeasurements();

                // Crear línea
                var length = Math.sqrt((x2 - x1) * (x2 - x1) + (y2 - y1) * (y2 - y1));
                var angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;

                var line = document.createElement('div');
                line.className = 'vbp-measure-line';
                line.style.cssText = 'position: absolute; left: ' + x1 + 'px; top: ' + y1 + 'px; width: ' + length + 'px; height: 2px; background: #3b82f6; transform-origin: 0 0; transform: rotate(' + angle + 'deg); z-index: 9999; pointer-events: none;';
                canvas.appendChild(line);

                // Crear etiqueta con la distancia
                var midX = (x1 + x2) / 2;
                var midY = (y1 + y2) / 2;
                var label = document.createElement('div');
                label.className = 'vbp-measure-label';
                label.innerHTML = Math.round(distance) + 'px<br><small>Δx: ' + Math.round(Math.abs(x2 - x1)) + ' Δy: ' + Math.round(Math.abs(y2 - y1)) + '</small>';
                label.style.cssText = 'position: absolute; left: ' + midX + 'px; top: ' + (midY - 30) + 'px; background: rgba(59, 130, 246, 0.95); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; z-index: 9999; pointer-events: none; text-align: center; transform: translateX(-50%);';
                canvas.appendChild(label);

                // Puntos en los extremos
                this.showMeasurePoint(x1, y1, 'start');
                this.showMeasurePoint(x2, y2, 'end');

                // Auto-limpiar después de 5 segundos
                var self = this;
                setTimeout(function() {
                    self.clearMeasurements();
                }, 5000);
            },

            /**
             * Limpiar mediciones
             */
            clearMeasurements: function() {
                var elements = document.querySelectorAll('.vbp-measure-point, .vbp-measure-line, .vbp-measure-label');
                elements.forEach(function(el) {
                    el.remove();
                });
            },

            /**
             * Remover herramienta de medición
             */
            removeMeasureTool: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (canvas && this.measureHandler) {
                    canvas.removeEventListener('click', this.measureHandler);
                }
                this.clearMeasurements();
                this.measureStartPoint = null;
            },

            /**
             * Favoritos de elementos
             */
            favorites: [],

            /**
             * Guardar elemento como favorito
             */
            saveAsFavorite: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento para guardar', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);

                if (!element) return;

                var favoriteName = prompt('Nombre para el favorito:', element.name || element.type);
                if (!favoriteName) return;

                // Clonar elemento sin ID
                var favorite = JSON.parse(JSON.stringify(element));
                delete favorite.id;
                favorite.favoriteName = favoriteName;
                favorite.savedAt = new Date().toISOString();

                // Cargar favoritos existentes
                this.loadFavorites();

                // Añadir nuevo favorito
                this.favorites.push(favorite);

                // Guardar
                localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));

                this.showNotification('⭐ Guardado como favorito: ' + favoriteName);
            },

            /**
             * Cargar favoritos
             */
            loadFavorites: function() {
                var saved = localStorage.getItem('vbp_favorites');
                if (saved) {
                    try {
                        this.favorites = JSON.parse(saved);
                    } catch (e) {
                        this.favorites = [];
                    }
                }
            },

            /**
             * Abrir panel de favoritos
             */
            openFavoritesPanel: function() {
                this.loadFavorites();

                if (this.favorites.length === 0) {
                    this.showNotification('No hay favoritos guardados', 'info');
                    return;
                }

                var self = this;
                var modalId = 'vbp-favorites-modal';

                // Eliminar modal existente
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                // Crear modal
                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal vbp-favorites-modal">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>⭐ Favoritos</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body">';
                modalHtml += '<div class="vbp-favorites-grid">';

                this.favorites.forEach(function(fav, index) {
                    modalHtml += '<div class="vbp-favorite-item" data-index="' + index + '">';
                    modalHtml += '<div class="vbp-favorite-icon">' + (fav.type === 'text' ? 'T' : fav.type === 'image' ? '🖼' : '▢') + '</div>';
                    modalHtml += '<div class="vbp-favorite-name">' + (fav.favoriteName || fav.type) + '</div>';
                    modalHtml += '<div class="vbp-favorite-actions">';
                    modalHtml += '<button class="vbp-btn-insert" data-index="' + index + '">Insertar</button>';
                    modalHtml += '<button class="vbp-btn-delete" data-index="' + index + '">🗑</button>';
                    modalHtml += '</div>';
                    modalHtml += '</div>';
                });

                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Event handlers
                var modal = document.getElementById(modalId);

                modal.querySelectorAll('.vbp-btn-insert').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = parseInt(this.dataset.index);
                        self.insertFavorite(index);
                        modal.remove();
                    });
                });

                modal.querySelectorAll('.vbp-btn-delete').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = parseInt(this.dataset.index);
                        self.deleteFavorite(index);
                        this.closest('.vbp-favorite-item').remove();
                    });
                });

                // Cerrar con click fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.remove();
                });
            },

            /**
             * Insertar favorito en el canvas
             */
            insertFavorite: function(index) {
                var store = Alpine.store('vbp');
                var favorite = this.favorites[index];

                if (!favorite) return;

                store.saveToHistory();

                // Crear nuevo elemento con nuevo ID
                var newElement = JSON.parse(JSON.stringify(favorite));
                newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
                newElement.name = favorite.favoriteName || favorite.type;

                store.elements.push(newElement);
                store.setSelection([newElement.id]);
                store.isDirty = true;

                this.showNotification('⭐ Insertado: ' + newElement.name);
            },

            /**
             * Eliminar favorito
             */
            deleteFavorite: function(index) {
                this.favorites.splice(index, 1);
                localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));
                this.showNotification('🗑 Favorito eliminado');
            },

            /**
             * Abrir editor de variables CSS
             */
            openCSSVariablesEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');
                var modalId = 'vbp-css-vars-modal';

                // Obtener variables CSS actuales
                var cssVars = store.cssVariables || {
                    '--vbp-primary': '#3b82f6',
                    '--vbp-secondary': '#6366f1',
                    '--vbp-accent': '#f43f5e',
                    '--vbp-background': '#ffffff',
                    '--vbp-text': '#1f2937',
                    '--vbp-text-muted': '#6b7280',
                    '--vbp-border': '#e5e7eb',
                    '--vbp-spacing-xs': '4px',
                    '--vbp-spacing-sm': '8px',
                    '--vbp-spacing-md': '16px',
                    '--vbp-spacing-lg': '24px',
                    '--vbp-spacing-xl': '32px',
                    '--vbp-radius-sm': '4px',
                    '--vbp-radius-md': '8px',
                    '--vbp-radius-lg': '12px',
                    '--vbp-font-family': 'Inter, system-ui, sans-serif',
                    '--vbp-font-size-sm': '14px',
                    '--vbp-font-size-md': '16px',
                    '--vbp-font-size-lg': '20px',
                    '--vbp-font-size-xl': '24px'
                };

                // Eliminar modal existente
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                // Crear modal
                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal vbp-css-vars-modal" style="max-width: 600px;">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>🎨 Variables CSS</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body" style="max-height: 60vh; overflow-y: auto;">';

                // Colores
                modalHtml += '<div class="vbp-css-section"><h3>Colores</h3>';
                ['--vbp-primary', '--vbp-secondary', '--vbp-accent', '--vbp-background', '--vbp-text', '--vbp-text-muted', '--vbp-border'].forEach(function(varName) {
                    var label = varName.replace('--vbp-', '').replace(/-/g, ' ');
                    modalHtml += '<div class="vbp-css-var-row">';
                    modalHtml += '<label>' + label + '</label>';
                    modalHtml += '<input type="color" data-var="' + varName + '" value="' + (cssVars[varName] || '#000000') + '">';
                    modalHtml += '<input type="text" data-var="' + varName + '" value="' + (cssVars[varName] || '') + '" style="width: 100px;">';
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                // Espaciado
                modalHtml += '<div class="vbp-css-section"><h3>Espaciado</h3>';
                ['--vbp-spacing-xs', '--vbp-spacing-sm', '--vbp-spacing-md', '--vbp-spacing-lg', '--vbp-spacing-xl'].forEach(function(varName) {
                    var label = varName.replace('--vbp-spacing-', '');
                    modalHtml += '<div class="vbp-css-var-row">';
                    modalHtml += '<label>' + label.toUpperCase() + '</label>';
                    modalHtml += '<input type="text" data-var="' + varName + '" value="' + (cssVars[varName] || '') + '" placeholder="8px">';
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                // Border radius
                modalHtml += '<div class="vbp-css-section"><h3>Border Radius</h3>';
                ['--vbp-radius-sm', '--vbp-radius-md', '--vbp-radius-lg'].forEach(function(varName) {
                    var label = varName.replace('--vbp-radius-', '');
                    modalHtml += '<div class="vbp-css-var-row">';
                    modalHtml += '<label>' + label.toUpperCase() + '</label>';
                    modalHtml += '<input type="text" data-var="' + varName + '" value="' + (cssVars[varName] || '') + '" placeholder="8px">';
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                // Tipografía
                modalHtml += '<div class="vbp-css-section"><h3>Tipografía</h3>';
                modalHtml += '<div class="vbp-css-var-row">';
                modalHtml += '<label>Font Family</label>';
                modalHtml += '<input type="text" data-var="--vbp-font-family" value="' + (cssVars['--vbp-font-family'] || '') + '" style="width: 200px;">';
                modalHtml += '</div>';
                ['--vbp-font-size-sm', '--vbp-font-size-md', '--vbp-font-size-lg', '--vbp-font-size-xl'].forEach(function(varName) {
                    var label = varName.replace('--vbp-font-size-', '');
                    modalHtml += '<div class="vbp-css-var-row">';
                    modalHtml += '<label>Size ' + label.toUpperCase() + '</label>';
                    modalHtml += '<input type="text" data-var="' + varName + '" value="' + (cssVars[varName] || '') + '" placeholder="16px">';
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-footer">';
                modalHtml += '<button class="vbp-btn vbp-btn-secondary" onclick="document.getElementById(\'' + modalId + '\').remove()">Cancelar</button>';
                modalHtml += '<button class="vbp-btn vbp-btn-primary" id="vbp-save-css-vars">Aplicar Variables</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Sincronizar color picker con input text
                var modal = document.getElementById(modalId);
                modal.querySelectorAll('input[type="color"]').forEach(function(colorInput) {
                    var varName = colorInput.dataset.var;
                    var textInput = modal.querySelector('input[type="text"][data-var="' + varName + '"]');

                    colorInput.addEventListener('input', function() {
                        if (textInput) textInput.value = this.value;
                    });

                    if (textInput) {
                        textInput.addEventListener('input', function() {
                            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                                colorInput.value = this.value;
                            }
                        });
                    }
                });

                // Guardar variables
                document.getElementById('vbp-save-css-vars').addEventListener('click', function() {
                    var newVars = {};
                    modal.querySelectorAll('input[data-var]').forEach(function(input) {
                        if (input.type !== 'color' && input.value) {
                            newVars[input.dataset.var] = input.value;
                        } else if (input.type === 'color') {
                            var textInput = modal.querySelector('input[type="text"][data-var="' + input.dataset.var + '"]');
                            if (!textInput || !textInput.value) {
                                newVars[input.dataset.var] = input.value;
                            }
                        }
                    });

                    // Aplicar al store
                    store.cssVariables = newVars;
                    store.isDirty = true;

                    // Aplicar al documento
                    self.applyCSSVariables(newVars);

                    modal.remove();
                    self.showNotification('🎨 Variables CSS aplicadas');
                });

                // Cerrar con click fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.remove();
                });
            },

            /**
             * Aplicar variables CSS al documento
             */
            applyCSSVariables: function(vars) {
                var root = document.documentElement;
                Object.keys(vars).forEach(function(varName) {
                    root.style.setProperty(varName, vars[varName]);
                });
            },

            /**
             * Historial de versiones
             */
            versionHistory: [],

            /**
             * Abrir comparador de versiones
             */
            openVersionCompare: function() {
                var self = this;
                var store = Alpine.store('vbp');
                var modalId = 'vbp-version-modal';

                // Cargar historial
                this.loadVersionHistory();

                if (this.versionHistory.length === 0) {
                    this.showNotification('No hay versiones guardadas', 'info');
                    return;
                }

                // Eliminar modal existente
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                // Crear modal
                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal vbp-version-modal" style="max-width: 800px;">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>📜 Historial de Versiones</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body">';

                modalHtml += '<div class="vbp-version-list">';
                this.versionHistory.forEach(function(version, index) {
                    var date = new Date(version.timestamp);
                    var dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    var elemCount = version.elements ? version.elements.length : 0;

                    modalHtml += '<div class="vbp-version-item" data-index="' + index + '">';
                    modalHtml += '<div class="vbp-version-info">';
                    modalHtml += '<div class="vbp-version-date">' + dateStr + '</div>';
                    modalHtml += '<div class="vbp-version-meta">' + elemCount + ' elementos</div>';
                    if (version.name) {
                        modalHtml += '<div class="vbp-version-name">' + version.name + '</div>';
                    }
                    modalHtml += '</div>';
                    modalHtml += '<div class="vbp-version-actions">';
                    modalHtml += '<button class="vbp-btn vbp-btn-preview" data-index="' + index + '">Vista previa</button>';
                    modalHtml += '<button class="vbp-btn vbp-btn-restore" data-index="' + index + '">Restaurar</button>';
                    modalHtml += '</div>';
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-version-preview" id="vbp-version-preview-area" style="display: none;">';
                modalHtml += '<h4>Vista previa</h4>';
                modalHtml += '<div class="vbp-version-preview-content"></div>';
                modalHtml += '</div>';

                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-footer">';
                modalHtml += '<button class="vbp-btn vbp-btn-secondary" id="vbp-save-version">💾 Guardar versión actual</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var modal = document.getElementById(modalId);

                // Preview handlers
                modal.querySelectorAll('.vbp-btn-preview').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = parseInt(this.dataset.index);
                        self.previewVersion(index);
                    });
                });

                // Restore handlers
                modal.querySelectorAll('.vbp-btn-restore').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = parseInt(this.dataset.index);
                        if (confirm('¿Restaurar esta versión? Los cambios actuales se perderán.')) {
                            self.restoreVersion(index);
                            modal.remove();
                        }
                    });
                });

                // Save current version
                document.getElementById('vbp-save-version').addEventListener('click', function() {
                    var name = prompt('Nombre para esta versión (opcional):');
                    self.saveCurrentVersion(name);
                });

                // Cerrar con click fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.remove();
                });
            },

            /**
             * Cargar historial de versiones
             */
            loadVersionHistory: function() {
                var saved = localStorage.getItem('vbp_version_history');
                if (saved) {
                    try {
                        this.versionHistory = JSON.parse(saved);
                    } catch (e) {
                        this.versionHistory = [];
                    }
                }
            },

            /**
             * Guardar versión actual
             */
            saveCurrentVersion: function(name) {
                var store = Alpine.store('vbp');

                this.loadVersionHistory();

                var version = {
                    timestamp: new Date().toISOString(),
                    name: name || '',
                    elements: JSON.parse(JSON.stringify(store.elements)),
                    settings: JSON.parse(JSON.stringify(store.settings || {})),
                    cssVariables: JSON.parse(JSON.stringify(store.cssVariables || {}))
                };

                this.versionHistory.unshift(version);

                // Limitar a 20 versiones
                if (this.versionHistory.length > 20) {
                    this.versionHistory = this.versionHistory.slice(0, 20);
                }

                localStorage.setItem('vbp_version_history', JSON.stringify(this.versionHistory));

                this.showNotification('💾 Versión guardada' + (name ? ': ' + name : ''));
            },

            /**
             * Vista previa de versión
             */
            previewVersion: function(index) {
                var version = this.versionHistory[index];
                if (!version) return;

                var previewArea = document.getElementById('vbp-version-preview-area');
                var previewContent = previewArea.querySelector('.vbp-version-preview-content');

                previewArea.style.display = 'block';

                // Mostrar resumen de elementos
                var html = '<ul style="list-style: none; padding: 0; margin: 0;">';
                version.elements.forEach(function(el) {
                    html += '<li style="padding: 4px 0; border-bottom: 1px solid #e5e7eb;">';
                    html += '<strong>' + (el.name || el.type) + '</strong>';
                    html += ' <span style="color: #6b7280;">(' + el.type + ')</span>';
                    if (el.children && el.children.length > 0) {
                        html += ' <span style="color: #3b82f6;">[' + el.children.length + ' hijos]</span>';
                    }
                    html += '</li>';
                });
                html += '</ul>';

                previewContent.innerHTML = html;
            },

            /**
             * Restaurar versión
             */
            restoreVersion: function(index) {
                var version = this.versionHistory[index];
                if (!version) return;

                var store = Alpine.store('vbp');

                // Guardar versión actual antes de restaurar
                this.saveCurrentVersion('Auto-guardado antes de restaurar');

                // Restaurar
                store.elements = JSON.parse(JSON.stringify(version.elements));
                if (version.settings) {
                    store.settings = JSON.parse(JSON.stringify(version.settings));
                }
                if (version.cssVariables) {
                    store.cssVariables = JSON.parse(JSON.stringify(version.cssVariables));
                    this.applyCSSVariables(store.cssVariables);
                }

                store.isDirty = true;
                store.clearSelection();

                this.showNotification('✅ Versión restaurada');
            },

            /**
             * Rotar elementos seleccionados
             */
            rotateSelection: function(degrees) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para rotar', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.transform) estilos.transform = {};

                    var currentRotation = parseFloat(estilos.transform.rotate) || 0;
                    estilos.transform.rotate = ((currentRotation + degrees) % 360) + 'deg';

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                this.showNotification('🔄 Rotado ' + degrees + '°');
            },

            /**
             * Resetear rotación
             */
            resetRotation: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (estilos.transform) {
                        estilos.transform.rotate = '0deg';
                    }

                    store.updateElement(id, { styles: estilos });
                });

                store.isDirty = true;
                this.showNotification('🔄 Rotación reseteada');
            },

            /**
             * Estado de snap to grid
             */
            snapToGridEnabled: false,
            gridSize: 8,

            /**
             * Toggle snap to grid
             */
            toggleSnapToGrid: function() {
                this.snapToGridEnabled = !this.snapToGridEnabled;
                localStorage.setItem('vbp_snap_to_grid', this.snapToGridEnabled);

                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    if (this.snapToGridEnabled) {
                        canvas.classList.add('vbp-snap-grid-enabled');
                    } else {
                        canvas.classList.remove('vbp-snap-grid-enabled');
                    }
                }

                this.showNotification(this.snapToGridEnabled ? '🧲 Snap to grid activado' : '🧲 Snap to grid desactivado');
            },

            /**
             * Ajustar posición al grid
             */
            snapToGrid: function(value) {
                if (!this.snapToGridEnabled) return value;
                return Math.round(value / this.gridSize) * this.gridSize;
            },

            /**
             * Toggle constraint en elemento
             */
            toggleConstraint: function(side) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos', 'warning');
                    return;
                }

                store.saveToHistory();

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element || element.locked) return;

                    var constraints = JSON.parse(JSON.stringify(element.constraints || {
                        top: false,
                        bottom: false,
                        left: false,
                        right: false,
                        centerH: false,
                        centerV: false
                    }));

                    constraints[side] = !constraints[side];

                    store.updateElement(id, { constraints: constraints });
                });

                store.isDirty = true;

                var icons = { top: '⬆️', bottom: '⬇️', left: '⬅️', right: '➡️' };
                this.showNotification(icons[side] + ' Constraint ' + side + ' toggle');
            },

            /**
             * Abrir editor de sombras
             */
            openShadowEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentShadow = (element.styles && element.styles.effects && element.styles.effects.boxShadow) || {
                    enabled: false,
                    x: 0,
                    y: 4,
                    blur: 8,
                    spread: 0,
                    color: 'rgba(0,0,0,0.15)',
                    inset: false
                };

                var modalId = 'vbp-shadow-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal" style="max-width: 400px;">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>🌑 Editor de Sombras</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body">';

                modalHtml += '<div class="vbp-shadow-preview" id="shadow-preview" style="width: 100%; height: 100px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">';
                modalHtml += '<div id="shadow-preview-box" style="width: 60px; height: 60px; background: white; border-radius: 8px;"></div>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-shadow-controls" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';

                modalHtml += '<div class="vbp-control">';
                modalHtml += '<label>Offset X</label>';
                modalHtml += '<input type="range" id="shadow-x" min="-50" max="50" value="' + currentShadow.x + '">';
                modalHtml += '<span id="shadow-x-val">' + currentShadow.x + 'px</span>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control">';
                modalHtml += '<label>Offset Y</label>';
                modalHtml += '<input type="range" id="shadow-y" min="-50" max="50" value="' + currentShadow.y + '">';
                modalHtml += '<span id="shadow-y-val">' + currentShadow.y + 'px</span>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control">';
                modalHtml += '<label>Blur</label>';
                modalHtml += '<input type="range" id="shadow-blur" min="0" max="100" value="' + currentShadow.blur + '">';
                modalHtml += '<span id="shadow-blur-val">' + currentShadow.blur + 'px</span>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control">';
                modalHtml += '<label>Spread</label>';
                modalHtml += '<input type="range" id="shadow-spread" min="-50" max="50" value="' + currentShadow.spread + '">';
                modalHtml += '<span id="shadow-spread-val">' + currentShadow.spread + 'px</span>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control" style="grid-column: span 2;">';
                modalHtml += '<label>Color</label>';
                modalHtml += '<input type="text" id="shadow-color" value="' + currentShadow.color + '" style="width: 100%;">';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control">';
                modalHtml += '<label><input type="checkbox" id="shadow-inset" ' + (currentShadow.inset ? 'checked' : '') + '> Inset</label>';
                modalHtml += '</div>';

                modalHtml += '</div>';

                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-footer">';
                modalHtml += '<button class="vbp-btn vbp-btn-secondary" id="shadow-remove">Quitar sombra</button>';
                modalHtml += '<button class="vbp-btn vbp-btn-primary" id="shadow-apply">Aplicar</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var modal = document.getElementById(modalId);
                var previewBox = document.getElementById('shadow-preview-box');

                function updatePreview() {
                    var x = document.getElementById('shadow-x').value;
                    var y = document.getElementById('shadow-y').value;
                    var blur = document.getElementById('shadow-blur').value;
                    var spread = document.getElementById('shadow-spread').value;
                    var color = document.getElementById('shadow-color').value;
                    var inset = document.getElementById('shadow-inset').checked;

                    document.getElementById('shadow-x-val').textContent = x + 'px';
                    document.getElementById('shadow-y-val').textContent = y + 'px';
                    document.getElementById('shadow-blur-val').textContent = blur + 'px';
                    document.getElementById('shadow-spread-val').textContent = spread + 'px';

                    var shadowValue = (inset ? 'inset ' : '') + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
                    previewBox.style.boxShadow = shadowValue;
                }

                modal.querySelectorAll('input').forEach(function(input) {
                    input.addEventListener('input', updatePreview);
                });

                updatePreview();

                document.getElementById('shadow-apply').addEventListener('click', function() {
                    var shadowData = {
                        enabled: true,
                        x: parseInt(document.getElementById('shadow-x').value),
                        y: parseInt(document.getElementById('shadow-y').value),
                        blur: parseInt(document.getElementById('shadow-blur').value),
                        spread: parseInt(document.getElementById('shadow-spread').value),
                        color: document.getElementById('shadow-color').value,
                        inset: document.getElementById('shadow-inset').checked
                    };

                    store.saveToHistory();
                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.effects) estilos.effects = {};
                    estilos.effects.boxShadow = shadowData;
                    store.updateElement(elementId, { styles: estilos });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🌑 Sombra aplicada');
                });

                document.getElementById('shadow-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (estilos.effects) {
                        delete estilos.effects.boxShadow;
                    }
                    store.updateElement(elementId, { styles: estilos });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🌑 Sombra eliminada');
                });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.remove();
                });
            },

            /**
             * Abrir editor de gradientes
             */
            openGradientEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentGradient = (element.styles && element.styles.background && element.styles.background.gradient) || {
                    enabled: false,
                    type: 'linear',
                    angle: 135,
                    stops: [
                        { color: '#3b82f6', position: 0 },
                        { color: '#8b5cf6', position: 100 }
                    ]
                };

                var modalId = 'vbp-gradient-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal" style="max-width: 450px;">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>🌈 Editor de Gradientes</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body">';

                modalHtml += '<div id="gradient-preview" style="width: 100%; height: 80px; border-radius: 8px; margin-bottom: 16px;"></div>';

                modalHtml += '<div class="vbp-control" style="margin-bottom: 12px;">';
                modalHtml += '<label>Tipo</label>';
                modalHtml += '<select id="gradient-type" style="width: 100%; padding: 8px;">';
                modalHtml += '<option value="linear" ' + (currentGradient.type === 'linear' ? 'selected' : '') + '>Linear</option>';
                modalHtml += '<option value="radial" ' + (currentGradient.type === 'radial' ? 'selected' : '') + '>Radial</option>';
                modalHtml += '</select>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-control" id="angle-control" style="margin-bottom: 12px;">';
                modalHtml += '<label>Ángulo: <span id="angle-val">' + currentGradient.angle + '</span>°</label>';
                modalHtml += '<input type="range" id="gradient-angle" min="0" max="360" value="' + currentGradient.angle + '" style="width: 100%;">';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-gradient-stops" id="gradient-stops" style="margin-bottom: 12px;">';
                modalHtml += '<label>Color Stops</label>';
                currentGradient.stops.forEach(function(stop, i) {
                    modalHtml += '<div class="vbp-gradient-stop" style="display: flex; gap: 8px; margin-top: 8px;">';
                    modalHtml += '<input type="color" class="stop-color" value="' + stop.color + '" data-index="' + i + '">';
                    modalHtml += '<input type="range" class="stop-position" min="0" max="100" value="' + stop.position + '" data-index="' + i + '" style="flex: 1;">';
                    modalHtml += '<span class="stop-pos-val">' + stop.position + '%</span>';
                    if (i > 1) {
                        modalHtml += '<button class="vbp-btn-sm stop-remove" data-index="' + i + '">×</button>';
                    }
                    modalHtml += '</div>';
                });
                modalHtml += '</div>';

                modalHtml += '<button class="vbp-btn vbp-btn-secondary" id="add-stop" style="width: 100%; margin-bottom: 12px;">+ Añadir color stop</button>';

                modalHtml += '<div class="vbp-gradient-presets" style="margin-bottom: 12px;">';
                modalHtml += '<label>Presets</label>';
                modalHtml += '<div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; margin-top: 8px;">';
                var presets = [
                    ['#667eea', '#764ba2'],
                    ['#f093fb', '#f5576c'],
                    ['#4facfe', '#00f2fe'],
                    ['#43e97b', '#38f9d7'],
                    ['#fa709a', '#fee140'],
                    ['#a8edea', '#fed6e3'],
                    ['#ff9a9e', '#fecfef'],
                    ['#ffecd2', '#fcb69f'],
                    ['#667eea', '#764ba2'],
                    ['#11998e', '#38ef7d'],
                    ['#fc5c7d', '#6a82fb'],
                    ['#00c6ff', '#0072ff']
                ];
                presets.forEach(function(preset, i) {
                    modalHtml += '<div class="gradient-preset" data-colors="' + preset.join(',') + '" style="width: 100%; height: 24px; border-radius: 4px; cursor: pointer; background: linear-gradient(135deg, ' + preset[0] + ', ' + preset[1] + ');"></div>';
                });
                modalHtml += '</div>';
                modalHtml += '</div>';

                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-footer">';
                modalHtml += '<button class="vbp-btn vbp-btn-secondary" id="gradient-remove">Quitar gradiente</button>';
                modalHtml += '<button class="vbp-btn vbp-btn-primary" id="gradient-apply">Aplicar</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var modal = document.getElementById(modalId);
                var stops = JSON.parse(JSON.stringify(currentGradient.stops));

                function updatePreview() {
                    var type = document.getElementById('gradient-type').value;
                    var angle = document.getElementById('gradient-angle').value;
                    var preview = document.getElementById('gradient-preview');
                    var angleControl = document.getElementById('angle-control');

                    document.getElementById('angle-val').textContent = angle;

                    angleControl.style.display = type === 'linear' ? 'block' : 'none';

                    var stopStrings = stops.map(function(s) {
                        return s.color + ' ' + s.position + '%';
                    }).join(', ');

                    if (type === 'linear') {
                        preview.style.background = 'linear-gradient(' + angle + 'deg, ' + stopStrings + ')';
                    } else {
                        preview.style.background = 'radial-gradient(circle, ' + stopStrings + ')';
                    }
                }

                function rebuildStops() {
                    var container = document.getElementById('gradient-stops');
                    var html = '<label>Color Stops</label>';
                    stops.forEach(function(stop, i) {
                        html += '<div class="vbp-gradient-stop" style="display: flex; gap: 8px; margin-top: 8px; align-items: center;">';
                        html += '<input type="color" class="stop-color" value="' + stop.color + '" data-index="' + i + '">';
                        html += '<input type="range" class="stop-position" min="0" max="100" value="' + stop.position + '" data-index="' + i + '" style="flex: 1;">';
                        html += '<span class="stop-pos-val" style="width: 40px;">' + stop.position + '%</span>';
                        if (stops.length > 2) {
                            html += '<button class="vbp-btn-sm stop-remove" data-index="' + i + '" style="padding: 2px 6px;">×</button>';
                        }
                        html += '</div>';
                    });
                    container.innerHTML = html;
                    attachStopListeners();
                }

                function attachStopListeners() {
                    modal.querySelectorAll('.stop-color').forEach(function(input) {
                        input.addEventListener('input', function() {
                            var idx = parseInt(this.dataset.index);
                            stops[idx].color = this.value;
                            updatePreview();
                        });
                    });

                    modal.querySelectorAll('.stop-position').forEach(function(input) {
                        input.addEventListener('input', function() {
                            var idx = parseInt(this.dataset.index);
                            stops[idx].position = parseInt(this.value);
                            this.nextElementSibling.textContent = this.value + '%';
                            updatePreview();
                        });
                    });

                    modal.querySelectorAll('.stop-remove').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var idx = parseInt(this.dataset.index);
                            stops.splice(idx, 1);
                            rebuildStops();
                            updatePreview();
                        });
                    });
                }

                document.getElementById('gradient-type').addEventListener('change', updatePreview);
                document.getElementById('gradient-angle').addEventListener('input', updatePreview);

                document.getElementById('add-stop').addEventListener('click', function() {
                    stops.push({ color: '#888888', position: 50 });
                    stops.sort(function(a, b) { return a.position - b.position; });
                    rebuildStops();
                    updatePreview();
                });

                modal.querySelectorAll('.gradient-preset').forEach(function(preset) {
                    preset.addEventListener('click', function() {
                        var colors = this.dataset.colors.split(',');
                        stops = [
                            { color: colors[0], position: 0 },
                            { color: colors[1], position: 100 }
                        ];
                        rebuildStops();
                        updatePreview();
                    });
                });

                attachStopListeners();
                updatePreview();

                document.getElementById('gradient-apply').addEventListener('click', function() {
                    var gradientData = {
                        enabled: true,
                        type: document.getElementById('gradient-type').value,
                        angle: parseInt(document.getElementById('gradient-angle').value),
                        stops: stops
                    };

                    store.saveToHistory();
                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (!estilos.background) estilos.background = {};
                    estilos.background.gradient = gradientData;
                    store.updateElement(elementId, { styles: estilos });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🌈 Gradiente aplicado');
                });

                document.getElementById('gradient-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var estilos = JSON.parse(JSON.stringify(element.styles || {}));
                    if (estilos.background) {
                        delete estilos.background.gradient;
                    }
                    store.updateElement(elementId, { styles: estilos });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🌈 Gradiente eliminado');
                });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.remove();
                });
            },

            /**
             * Toggle auto-layout en contenedor
             */
            toggleAutoLayout: function() {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un contenedor', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);

                if (!element) return;

                // Solo para contenedores
                if (['container', 'columns', 'row', 'group', 'section'].indexOf(element.type) === -1) {
                    this.showNotification('Solo contenedores soportan auto-layout', 'warning');
                    return;
                }

                store.saveToHistory();

                var autoLayout = element.autoLayout || {
                    enabled: false,
                    direction: 'vertical',
                    gap: 16,
                    padding: 16,
                    alignItems: 'stretch',
                    justifyContent: 'flex-start'
                };

                autoLayout.enabled = !autoLayout.enabled;

                store.updateElement(elementId, { autoLayout: autoLayout });
                store.isDirty = true;

                if (autoLayout.enabled) {
                    this.showNotification('📐 Auto-layout activado');
                } else {
                    this.showNotification('📐 Auto-layout desactivado');
                }
            },

            /**
             * Ajustar gap del auto-layout
             */
            adjustAutoLayoutGap: function(delta) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);

                if (!element || !element.autoLayout || !element.autoLayout.enabled) {
                    this.showNotification('Activa auto-layout primero (Shift+A)', 'warning');
                    return;
                }

                store.saveToHistory();

                var autoLayout = JSON.parse(JSON.stringify(element.autoLayout));
                autoLayout.gap = Math.max(0, (autoLayout.gap || 16) + delta);

                store.updateElement(elementId, { autoLayout: autoLayout });
                store.isDirty = true;

                this.showNotification('📐 Gap: ' + autoLayout.gap + 'px');
            },

            /**
             * Inicializar multi-select con caja
             */
            initMultiSelectBox: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                var selectionBox = null;
                var startX, startY;
                var isSelecting = false;

                canvas.addEventListener('mousedown', function(e) {
                    // Solo si hacemos click en el canvas vacío
                    if (e.target !== canvas && !e.target.classList.contains('vbp-canvas-content')) return;
                    if (e.button !== 0) return; // Solo click izquierdo

                    isSelecting = true;
                    var rect = canvas.getBoundingClientRect();
                    startX = e.clientX - rect.left + canvas.scrollLeft;
                    startY = e.clientY - rect.top + canvas.scrollTop;

                    selectionBox = document.createElement('div');
                    selectionBox.className = 'vbp-selection-box';
                    selectionBox.style.cssText = 'position: absolute; border: 1px dashed #3b82f6; background: rgba(59, 130, 246, 0.1); pointer-events: none; z-index: 9999;';
                    canvas.appendChild(selectionBox);
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isSelecting || !selectionBox) return;

                    var rect = canvas.getBoundingClientRect();
                    var currentX = e.clientX - rect.left + canvas.scrollLeft;
                    var currentY = e.clientY - rect.top + canvas.scrollTop;

                    var left = Math.min(startX, currentX);
                    var top = Math.min(startY, currentY);
                    var width = Math.abs(currentX - startX);
                    var height = Math.abs(currentY - startY);

                    selectionBox.style.left = left + 'px';
                    selectionBox.style.top = top + 'px';
                    selectionBox.style.width = width + 'px';
                    selectionBox.style.height = height + 'px';
                });

                document.addEventListener('mouseup', function(e) {
                    if (!isSelecting || !selectionBox) return;

                    isSelecting = false;

                    // Obtener bounds de la caja de selección
                    var boxRect = selectionBox.getBoundingClientRect();

                    // Encontrar elementos dentro de la caja
                    var elementsInBox = [];
                    var elements = canvas.querySelectorAll('.vbp-element');

                    elements.forEach(function(el) {
                        var elRect = el.getBoundingClientRect();

                        // Verificar si el elemento está dentro de la caja
                        if (elRect.left < boxRect.right &&
                            elRect.right > boxRect.left &&
                            elRect.top < boxRect.bottom &&
                            elRect.bottom > boxRect.top) {
                            var elementId = el.dataset.elementId;
                            if (elementId) {
                                elementsInBox.push(elementId);
                            }
                        }
                    });

                    selectionBox.remove();
                    selectionBox = null;

                    // Seleccionar elementos encontrados
                    if (elementsInBox.length > 0) {
                        var store = Alpine.store('vbp');
                        store.setSelection(elementsInBox);
                        self.showNotification('Seleccionados ' + elementsInBox.length + ' elementos');
                    }
                });
            },

            /**
             * Obtener bounds combinados de la selección
             */
            getSelectionBounds: function(store) {
                var self = this;
                var minX = Infinity, minY = Infinity;
                var maxX = -Infinity, maxY = -Infinity;

                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (!element) return;

                    var bounds = self.getElementBounds(element);
                    if (!bounds) return;

                    minX = Math.min(minX, bounds.left);
                    minY = Math.min(minY, bounds.top);
                    maxX = Math.max(maxX, bounds.left + bounds.width);
                    maxY = Math.max(maxY, bounds.top + bounds.height);
                });

                if (minX === Infinity) return null;

                return {
                    left: minX,
                    top: minY,
                    width: maxX - minX,
                    height: maxY - minY
                };
            },

            // =========================================================
            // HERRAMIENTAS AVANZADAS
            // =========================================================

            /**
             * Toggle bloqueo de proporción (aspect ratio)
             */
            toggleAspectRatioLock: function() {
                var store = Alpine.store('vbp');
                if (store.selection.elementIds.length === 0) return;

                var self = this;
                store.selection.elementIds.forEach(function(id) {
                    var element = store.getElement(id);
                    if (element) {
                        var locked = !element.aspectRatioLock;
                        store.updateElement(id, { aspectRatioLock: locked });
                    }
                });

                var firstEl = store.getElement(store.selection.elementIds[0]);
                this.showNotification('Proporción ' + (firstEl.aspectRatioLock ? 'bloqueada 🔒' : 'desbloqueada 🔓'));
            },

            /**
             * Toggle smart guides
             */
            smartGuidesEnabled: false,

            toggleSmartGuides: function() {
                this.smartGuidesEnabled = !this.smartGuidesEnabled;
                localStorage.setItem('vbp_smart_guides', this.smartGuidesEnabled);

                if (this.smartGuidesEnabled) {
                    this.initSmartGuides();
                } else {
                    this.removeSmartGuides();
                }

                this.showNotification('Smart Guides ' + (this.smartGuidesEnabled ? 'ON 📐' : 'OFF'));
            },

            initSmartGuides: function() {
                // Las guías se muestran durante el drag
                document.body.classList.add('vbp-smart-guides-enabled');
            },

            removeSmartGuides: function() {
                document.body.classList.remove('vbp-smart-guides-enabled');
                var guides = document.querySelectorAll('.vbp-smart-guide');
                guides.forEach(function(g) { g.remove(); });
            },

            /**
             * Toggle herramienta de medición
             */
            measureToolActive: false,

            toggleMeasureTool: function() {
                this.measureToolActive = !this.measureToolActive;

                if (this.measureToolActive) {
                    this.initMeasureTool();
                    this.showNotification('Herramienta de medición ON 📏');
                } else {
                    this.removeMeasureTool();
                    this.showNotification('Herramienta de medición OFF');
                }
            },

            initMeasureTool: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                canvas.classList.add('vbp-measuring');

                this.measureHandler = function(e) {
                    var elements = document.querySelectorAll('.vbp-element');
                    var target = e.target.closest('.vbp-element');

                    if (target) {
                        self.showMeasurements(target);
                    }
                };

                canvas.addEventListener('mousemove', this.measureHandler);
            },

            removeMeasureTool: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    canvas.classList.remove('vbp-measuring');
                    if (this.measureHandler) {
                        canvas.removeEventListener('mousemove', this.measureHandler);
                    }
                }
                var tooltips = document.querySelectorAll('.vbp-measure-tooltip');
                tooltips.forEach(function(t) { t.remove(); });
            },

            showMeasurements: function(element) {
                var existing = document.querySelector('.vbp-measure-tooltip');
                if (existing) existing.remove();

                var rect = element.getBoundingClientRect();
                var tooltip = document.createElement('div');
                tooltip.className = 'vbp-measure-tooltip';
                tooltip.innerHTML = Math.round(rect.width) + ' × ' + Math.round(rect.height) + ' px';
                tooltip.style.cssText = 'position: fixed; left: ' + rect.left + 'px; top: ' + (rect.top - 24) + 'px; padding: 4px 8px; background: #89b4fa; color: #1e1e2e; font-size: 11px; font-weight: 600; border-radius: 4px; z-index: 10001; pointer-events: none;';
                document.body.appendChild(tooltip);
            },

            /**
             * Favoritos / Presets
             */
            favorites: [],

            saveAsFavorite: function() {
                var store = Alpine.store('vbp');
                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para guardar', 'warning');
                    return;
                }

                var name = prompt('Nombre del favorito:', 'Favorito ' + (this.favorites.length + 1));
                if (!name) return;

                var self = this;
                var elementsToSave = store.selection.elementIds.map(function(id) {
                    return JSON.parse(JSON.stringify(store.getElement(id)));
                });

                this.favorites.push({
                    id: Date.now(),
                    name: name,
                    elements: elementsToSave,
                    date: new Date().toISOString()
                });

                localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));
                this.showNotification('Guardado como favorito ⭐');
            },

            openFavorites: function() {
                var self = this;
                var stored = localStorage.getItem('vbp_favorites');
                if (stored) {
                    this.favorites = JSON.parse(stored);
                }

                if (this.favorites.length === 0) {
                    this.showNotification('No hay favoritos guardados', 'info');
                    return;
                }

                // Crear modal
                var modal = document.createElement('div');
                modal.id = 'vbp-favorites-modal';
                modal.className = 'vbp-modal-overlay';

                var html = '<div class="vbp-modal" style="max-width: 500px;">';
                html += '<div class="vbp-modal-header"><h2>⭐ Favoritos</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'vbp-favorites-modal\').remove()">&times;</button></div>';
                html += '<div class="vbp-modal-body" style="max-height: 400px; overflow-y: auto;">';
                html += '<ul class="vbp-favorites-list" style="list-style: none; padding: 0; margin: 0;">';

                this.favorites.forEach(function(fav, index) {
                    html += '<li style="display: flex; justify-content: space-between; align-items: center; padding: 12px; margin: 4px 0; background: var(--vbp-surface, #313244); border-radius: 6px;">';
                    html += '<span style="font-weight: 500;">' + fav.name + '</span>';
                    html += '<div>';
                    html += '<button onclick="window.vbpKeyboard.insertFavorite(' + index + ')" style="padding: 6px 12px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px;">Insertar</button>';
                    html += '<button onclick="window.vbpKeyboard.deleteFavorite(' + index + ')" style="padding: 6px 12px; background: #f38ba8; color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>';
                    html += '</div></li>';
                });

                html += '</ul></div></div>';
                modal.innerHTML = html;
                document.body.appendChild(modal);
            },

            insertFavorite: function(index) {
                var store = Alpine.store('vbp');
                var fav = this.favorites[index];
                if (!fav) return;

                var self = this;
                fav.elements.forEach(function(el) {
                    var newEl = JSON.parse(JSON.stringify(el));
                    newEl.id = 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    store.addElement(newEl);
                });

                document.getElementById('vbp-favorites-modal').remove();
                this.showNotification('Favorito insertado');
            },

            deleteFavorite: function(index) {
                this.favorites.splice(index, 1);
                localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));
                document.getElementById('vbp-favorites-modal').remove();
                this.openFavorites();
            },

            /**
             * Editor de variables CSS
             */
            openCSSVariables: function() {
                var store = Alpine.store('vbp');
                var cssVars = store.settings && store.settings.cssVariables ? store.settings.cssVariables : {};

                var modal = document.createElement('div');
                modal.id = 'vbp-css-vars-modal';
                modal.className = 'vbp-modal-overlay';

                var html = '<div class="vbp-modal" style="max-width: 600px;">';
                html += '<div class="vbp-modal-header"><h2>🎨 Variables CSS</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'vbp-css-vars-modal\').remove()">&times;</button></div>';
                html += '<div class="vbp-modal-body">';

                html += '<div style="margin-bottom: 16px;">';
                html += '<button onclick="window.vbpKeyboard.addCSSVariable()" style="padding: 8px 16px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer;">+ Añadir variable</button>';
                html += '</div>';

                html += '<div id="vbp-css-vars-list" style="max-height: 300px; overflow-y: auto;">';

                Object.keys(cssVars).forEach(function(key) {
                    var val = cssVars[key];
                    var isColor = val.startsWith('#') || val.startsWith('rgb');
                    html += '<div class="vbp-css-var-row" style="display: flex; gap: 8px; margin-bottom: 8px; align-items: center;">';
                    html += '<input type="text" value="' + key + '" style="flex: 1; padding: 8px; background: var(--vbp-input-bg, #11111b); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; color: var(--vbp-text, #cdd6f4);" data-var-name>';
                    if (isColor) {
                        html += '<input type="color" value="' + val + '" style="width: 40px; height: 36px; padding: 0; border: none; cursor: pointer;" data-var-value-color>';
                    }
                    html += '<input type="text" value="' + val + '" style="flex: 1; padding: 8px; background: var(--vbp-input-bg, #11111b); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; color: var(--vbp-text, #cdd6f4);" data-var-value>';
                    html += '<button onclick="this.parentElement.remove()" style="padding: 8px; background: #f38ba8; color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>';
                    html += '</div>';
                });

                html += '</div>';
                html += '<div style="margin-top: 16px; text-align: right;">';
                html += '<button onclick="window.vbpKeyboard.saveCSSVariables()" style="padding: 10px 20px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Guardar variables</button>';
                html += '</div>';
                html += '</div></div>';

                modal.innerHTML = html;
                document.body.appendChild(modal);
            },

            addCSSVariable: function() {
                var list = document.getElementById('vbp-css-vars-list');
                var row = document.createElement('div');
                row.className = 'vbp-css-var-row';
                row.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
                row.innerHTML = '<input type="text" placeholder="--mi-variable" style="flex: 1; padding: 8px; background: var(--vbp-input-bg, #11111b); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; color: var(--vbp-text, #cdd6f4);" data-var-name>' +
                    '<input type="text" placeholder="#ffffff o 16px" style="flex: 1; padding: 8px; background: var(--vbp-input-bg, #11111b); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; color: var(--vbp-text, #cdd6f4);" data-var-value>' +
                    '<button onclick="this.parentElement.remove()" style="padding: 8px; background: #f38ba8; color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>';
                list.appendChild(row);
            },

            saveCSSVariables: function() {
                var store = Alpine.store('vbp');
                var rows = document.querySelectorAll('#vbp-css-vars-list .vbp-css-var-row');
                var vars = {};

                rows.forEach(function(row) {
                    var nameInput = row.querySelector('[data-var-name]');
                    var valueInput = row.querySelector('[data-var-value]');
                    if (nameInput && valueInput && nameInput.value && valueInput.value) {
                        vars[nameInput.value] = valueInput.value;
                    }
                });

                if (!store.settings) store.settings = {};
                store.settings.cssVariables = vars;
                store.isDirty = true;

                document.getElementById('vbp-css-vars-modal').remove();
                this.showNotification('Variables CSS guardadas');
            },

            /**
             * Comparador de versiones
             */
            versions: [],

            openVersionCompare: function() {
                var store = Alpine.store('vbp');
                var self = this;

                // Guardar versión actual
                var currentVersion = {
                    id: Date.now(),
                    date: new Date().toLocaleString(),
                    elements: JSON.parse(JSON.stringify(store.elements))
                };

                var stored = localStorage.getItem('vbp_versions_' + store.postId);
                this.versions = stored ? JSON.parse(stored) : [];

                var modal = document.createElement('div');
                modal.id = 'vbp-versions-modal';
                modal.className = 'vbp-modal-overlay';

                var html = '<div class="vbp-modal" style="max-width: 600px;">';
                html += '<div class="vbp-modal-header"><h2>📜 Historial de Versiones</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'vbp-versions-modal\').remove()">&times;</button></div>';
                html += '<div class="vbp-modal-body">';

                html += '<div style="margin-bottom: 16px;">';
                html += '<button onclick="window.vbpKeyboard.saveVersion()" style="padding: 8px 16px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer;">💾 Guardar versión actual</button>';
                html += '</div>';

                html += '<div style="max-height: 300px; overflow-y: auto;">';
                if (this.versions.length === 0) {
                    html += '<p style="color: var(--vbp-text-muted, #6c7086); text-align: center;">No hay versiones guardadas</p>';
                } else {
                    this.versions.forEach(function(ver, index) {
                        html += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; margin: 4px 0; background: var(--vbp-surface, #313244); border-radius: 6px;">';
                        html += '<div><strong>Versión ' + (index + 1) + '</strong><br><small style="color: var(--vbp-text-muted, #6c7086);">' + ver.date + ' - ' + ver.elements.length + ' elementos</small></div>';
                        html += '<div>';
                        html += '<button onclick="window.vbpKeyboard.restoreVersion(' + index + ')" style="padding: 6px 12px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px;">Restaurar</button>';
                        html += '<button onclick="window.vbpKeyboard.deleteVersion(' + index + ')" style="padding: 6px 12px; background: #f38ba8; color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer;">🗑️</button>';
                        html += '</div></div>';
                    });
                }
                html += '</div></div></div>';

                modal.innerHTML = html;
                document.body.appendChild(modal);
            },

            saveVersion: function() {
                var store = Alpine.store('vbp');
                var version = {
                    id: Date.now(),
                    date: new Date().toLocaleString(),
                    elements: JSON.parse(JSON.stringify(store.elements))
                };

                this.versions.push(version);
                localStorage.setItem('vbp_versions_' + store.postId, JSON.stringify(this.versions));

                document.getElementById('vbp-versions-modal').remove();
                this.openVersionCompare();
                this.showNotification('Versión guardada');
            },

            restoreVersion: function(index) {
                var store = Alpine.store('vbp');
                var ver = this.versions[index];
                if (!ver) return;

                if (confirm('¿Restaurar esta versión? Los cambios actuales se perderán.')) {
                    store.elements = JSON.parse(JSON.stringify(ver.elements));
                    store.isDirty = true;
                    document.getElementById('vbp-versions-modal').remove();
                    this.showNotification('Versión restaurada');
                }
            },

            deleteVersion: function(index) {
                var store = Alpine.store('vbp');
                this.versions.splice(index, 1);
                localStorage.setItem('vbp_versions_' + store.postId, JSON.stringify(this.versions));
                document.getElementById('vbp-versions-modal').remove();
                this.openVersionCompare();
            },


            /**
             * Mostrar indicador visual de zoom
             */
            showZoomFeedback: function(zoomLevel) {
                var existingIndicator = document.getElementById('vbp-zoom-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                var indicator = document.createElement('div');
                indicator.id = 'vbp-zoom-indicator';
                indicator.innerHTML = '🔍 ' + zoomLevel + '%';
                indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background: rgba(30, 30, 46, 0.95); color: #cdd6f4; border-radius: 8px; font-size: 16px; font-weight: 600; z-index: 10000; pointer-events: none; transition: opacity 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                document.body.appendChild(indicator);

                setTimeout(function() {
                    indicator.style.opacity = '0';
                    setTimeout(function() {
                        if (indicator.parentNode) {
                            indicator.remove();
                        }
                    }, 300);
                }, 800);
            },

            /**
             * Mostrar notificación
             */
            showNotification: function(message, type) {
                type = type || 'info';

                // Dispatch evento para que la UI lo muestre
                document.dispatchEvent(new CustomEvent('vbp:notification', {
                    detail: {
                        message: message,
                        type: type
                    }
                }));

                // También mostrar en consola
                console.log('VBP:', message);
            },

            /**
             * Mostrar modal de ayuda con atajos de teclado
             */
            showHelpModal: function() {
                var shortcuts = window.vbpKeyboard.getShortcutsList();
                var modalId = 'vbp-help-modal';

                // Verificar si ya existe el modal
                var existingModal = document.getElementById(modalId);
                if (existingModal) {
                    existingModal.remove();
                }

                // Crear HTML del modal
                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                modalHtml += '<div class="vbp-modal vbp-help-modal">';
                modalHtml += '<div class="vbp-modal-header">';
                modalHtml += '<h2>⌨️ Atajos de Teclado</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-body">';
                modalHtml += '<div class="vbp-shortcuts-grid">';

                shortcuts.forEach(function(category) {
                    modalHtml += '<div class="vbp-shortcuts-category">';
                    modalHtml += '<h3>' + category.category + '</h3>';
                    modalHtml += '<ul class="vbp-shortcuts-list">';

                    category.shortcuts.forEach(function(shortcut) {
                        modalHtml += '<li>';
                        modalHtml += '<span class="vbp-shortcut-keys">' + shortcut.keys + '</span>';
                        modalHtml += '<span class="vbp-shortcut-action">' + shortcut.action + '</span>';
                        modalHtml += '</li>';
                    });

                    modalHtml += '</ul>';
                    modalHtml += '</div>';
                });

                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '<div class="vbp-modal-footer">';
                modalHtml += '<p class="vbp-help-tip">💡 Presiona <kbd>?</kbd> o <kbd>F1</kbd> en cualquier momento para ver esta ayuda</p>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                // Insertar modal en el DOM
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Cerrar con Escape
                var modal = document.getElementById(modalId);
                var closeOnEscape = function(e) {
                    if (e.key === 'Escape') {
                        modal.remove();
                        document.removeEventListener('keydown', closeOnEscape);
                    }
                };
                document.addEventListener('keydown', closeOnEscape);

                // Cerrar al hacer clic fuera
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
            }
        };
    });
});

// Exponer globalmente para acceso directo
window.vbpKeyboard = {
    /**
     * Obtener lista de atajos para mostrar en ayuda
     */
    getShortcutsList: function() {
        return [
            { category: 'Archivo', shortcuts: [
                { keys: 'Ctrl + S', action: 'Guardar' },
                { keys: 'Ctrl + Shift + S', action: 'Guardar como template' },
                { keys: 'Ctrl + P', action: 'Preview' }
            ]},
            { category: 'Edición', shortcuts: [
                { keys: 'Ctrl + Z', action: 'Deshacer' },
                { keys: 'Ctrl + Shift + Z', action: 'Rehacer' },
                { keys: 'Ctrl + C', action: 'Copiar elemento' },
                { keys: 'Ctrl + X', action: 'Cortar' },
                { keys: 'Ctrl + V', action: 'Pegar' },
                { keys: 'Ctrl + D', action: 'Duplicar (con offset)' },
                { keys: 'Ctrl + Shift + D', action: 'Duplicar en mismo lugar' },
                { keys: 'Delete', action: 'Eliminar' },
                { keys: 'Ctrl + Shift + C', action: 'Copiar estilos' },
                { keys: 'Ctrl + Shift + V', action: 'Pegar estilos' },
                { keys: 'Ctrl + Shift + R', action: 'Resetear estilos' }
            ]},
            { category: 'Texto enriquecido', shortcuts: [
                { keys: 'Ctrl + B', action: 'Negrita' },
                { keys: 'Ctrl + I', action: 'Cursiva' },
                { keys: 'Ctrl + U', action: 'Subrayado' },
                { keys: 'Ctrl + K', action: 'Insertar enlace' },
                { keys: '**texto**', action: 'Markdown negrita' },
                { keys: '*texto*', action: 'Markdown cursiva' },
                { keys: '@', action: 'Mencionar página/entrada' }
            ]},
            { category: 'Selección y Grupos', shortcuts: [
                { keys: 'Ctrl + A', action: 'Seleccionar todo' },
                { keys: 'Ctrl + Shift + A', action: 'Invertir selección' },
                { keys: 'Ctrl + Alt + A', action: 'Seleccionar similares' },
                { keys: 'Tab', action: 'Siguiente elemento' },
                { keys: 'Shift + Tab', action: 'Elemento anterior' },
                { keys: 'Escape', action: 'Deseleccionar' },
                { keys: 'Enter / F2', action: 'Editar texto inline' },
                { keys: 'Ctrl + G', action: 'Agrupar elementos' },
                { keys: 'Ctrl + Shift + U', action: 'Desagrupar' },
                { keys: 'Ctrl + Shift + L', action: 'Bloquear/Desbloquear' },
                { keys: 'Alt + Click', action: 'Duplicar elemento' },
                { keys: 'Shift + Click', action: 'Multi-selección' }
            ]},
            { category: 'Posicionamiento', shortcuts: [
                { keys: '↑ ↓ ← →', action: 'Mover 1px' },
                { keys: 'Shift + ↑ ↓ ← →', action: 'Mover 10px' },
                { keys: 'Ctrl + ↑', action: 'Mover al frente' },
                { keys: 'Ctrl + ↓', action: 'Mover al fondo' },
                { keys: 'Ctrl + Shift + F', action: 'Ajustar al contenido' },
                { keys: 'Ctrl + Alt + F', action: 'Llenar contenedor' },
                { keys: 'Alt + Enter', action: 'Centrar en viewport' }
            ]},
            { category: 'Navegación Jerárquica', shortcuts: [
                { keys: 'Alt + ↑', action: 'Seleccionar padre' },
                { keys: 'Alt + ↓', action: 'Seleccionar primer hijo' },
                { keys: 'Ctrl + .', action: 'Colapsar/expandir' }
            ]},
            { category: 'Spacing Rápido', shortcuts: [
                { keys: 'Alt + 1', action: 'Spacing 8px' },
                { keys: 'Alt + 2', action: 'Spacing 16px' },
                { keys: 'Alt + 3', action: 'Spacing 24px' },
                { keys: 'Alt + 4', action: 'Spacing 32px' }
            ]},
            { category: 'Transformaciones', shortcuts: [
                { keys: 'Alt + Shift + H', action: 'Flip horizontal' },
                { keys: 'Alt + Shift + V', action: 'Flip vertical' },
                { keys: 'Ctrl + Shift + 0', action: 'Resetear posición' },
                { keys: 'Ctrl + Alt + R', action: 'Renombrar rápido' }
            ]},
            { category: 'Marcadores', shortcuts: [
                { keys: 'Ctrl + Alt + 1/2/3', action: 'Guardar marcador' },
                { keys: 'Ctrl + Shift + 1/2/3', action: 'Ir a marcador' }
            ]},
            { category: 'Herramientas Avanzadas', shortcuts: [
                { keys: 'Ctrl + Shift + P', action: 'Bloquear proporción' },
                { keys: 'Ctrl + Alt + G', action: 'Smart Guides on/off' },
                { keys: 'M', action: 'Herramienta de medición' },
                { keys: 'Ctrl + Alt + K', action: 'Guardar como favorito' },
                { keys: 'Ctrl + Alt + Shift + K', action: 'Abrir favoritos' },
                { keys: 'Ctrl + Alt + C', action: 'Editor variables CSS' },
                { keys: 'Ctrl + Alt + D', action: 'Comparar versiones' }
            ]},
            { category: 'Rotación', shortcuts: [
                { keys: 'R', action: 'Rotar 15°' },
                { keys: 'Shift + R', action: 'Rotar -15°' },
                { keys: 'Ctrl + R', action: 'Rotar 90°' },
                { keys: 'Ctrl + Alt + 0', action: 'Resetear rotación' }
            ]},
            { category: 'Snap y Constraints', shortcuts: [
                { keys: 'Ctrl + Shift + .', action: 'Snap to grid on/off' },
                { keys: 'Ctrl + Alt + T', action: 'Constraint arriba' },
                { keys: 'Ctrl + Alt + B', action: 'Constraint abajo' },
                { keys: 'Ctrl + Alt + L', action: 'Constraint izquierda' },
                { keys: 'Ctrl + Alt + →', action: 'Constraint derecha' }
            ]},
            { category: 'Efectos', shortcuts: [
                { keys: 'Ctrl + Alt + Shift + S', action: 'Editor de sombras' },
                { keys: 'Ctrl + Alt + Shift + X', action: 'Editor de gradientes' }
            ]},
            { category: 'Auto-layout', shortcuts: [
                { keys: 'Shift + A', action: 'Toggle auto-layout' },
                { keys: 'Ctrl + Shift + ↑', action: 'Reducir gap' },
                { keys: 'Ctrl + Shift + ↓', action: 'Aumentar gap' }
            ]},
            { category: 'Zoom', shortcuts: [
                { keys: 'Ctrl + +', action: 'Acercar' },
                { keys: 'Ctrl + -', action: 'Alejar' },
                { keys: 'Ctrl + 0', action: 'Restablecer zoom' },
                { keys: 'Ctrl + 1', action: 'Zoom 100%' },
                { keys: 'Ctrl + 2', action: 'Zoom 200%' },
                { keys: 'Ctrl + 5', action: 'Zoom 50%' },
                { keys: 'Ctrl + Rueda', action: 'Zoom con ratón' }
            ]},
            { category: 'Paneles y Vista', shortcuts: [
                { keys: 'Ctrl + \\', action: 'Toggle todos los paneles' },
                { keys: 'Ctrl + B', action: 'Panel de bloques' },
                { keys: 'Ctrl + L', action: 'Capas' },
                { keys: 'Ctrl + \'', action: 'Mostrar/Ocultar cuadrícula' },
                { keys: 'Ctrl + ;', action: 'Mostrar/Ocultar guías' },
                { keys: 'Ctrl + F', action: 'Buscar elementos' },
                { keys: 'Ctrl + Shift + H', action: 'Ocultar/Mostrar selección' },
                { keys: 'Ctrl + Alt + H', action: 'Ocultar otros' }
            ]},
            { category: 'Alineación', shortcuts: [
                { keys: 'Alt + L', action: 'Alinear a la izquierda' },
                { keys: 'Alt + C', action: 'Centrar horizontalmente' },
                { keys: 'Alt + R', action: 'Alinear a la derecha' },
                { keys: 'Alt + T', action: 'Alinear arriba' },
                { keys: 'Alt + M', action: 'Centrar verticalmente' },
                { keys: 'Alt + B', action: 'Alinear abajo' },
                { keys: 'Ctrl + Alt + H', action: 'Distribuir horizontalmente' },
                { keys: 'Ctrl + Alt + V', action: 'Distribuir verticalmente' },
                { keys: 'Ctrl + Shift + →', action: 'Apilar horizontal' },
                { keys: 'Ctrl + Shift + ↓', action: 'Apilar vertical' }
            ]},
            { category: 'Orden y Transformación', shortcuts: [
                { keys: 'Ctrl + ]', action: 'Traer adelante' },
                { keys: 'Ctrl + [', action: 'Enviar atrás' },
                { keys: 'Ctrl + Shift + ]', action: 'Traer al frente' },
                { keys: 'Ctrl + Shift + [', action: 'Enviar al fondo' },
                { keys: 'Ctrl + M', action: 'Igualar tamaño' },
                { keys: 'Ctrl + Alt + S', action: 'Intercambiar posición' },
                { keys: 'Ctrl + Shift + W', action: 'Envolver en contenedor' }
            ]},
            { category: 'Productividad', shortcuts: [
                { keys: 'Ctrl + /', action: 'Paleta de comandos' },
                { keys: 'Ctrl + K', action: 'Paleta de comandos' },
                { keys: 'Ctrl + E', action: 'Exportar' },
                { keys: 'Ctrl + T', action: 'Templates' },
                { keys: 'Ctrl + ,', action: 'Configuración' },
                { keys: 'Ctrl + Shift + E', action: 'Copiar como HTML' },
                { keys: 'Ctrl + Alt + E', action: 'Copiar como JSON' },
                { keys: 'Ctrl + Alt + V', action: 'Pegar desde JSON' },
                { keys: '? / F1', action: 'Ayuda (esta ventana)' }
            ]}
        ];
    }
};

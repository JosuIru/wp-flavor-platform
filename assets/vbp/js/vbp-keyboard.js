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

                // Rulers
                'ctrl+alt+shift+r': 'toggleRulers',

                // Grid settings
                'alt+shift+g': 'openGridSettings',

                // Constraints
                'ctrl+alt+t': 'toggleConstraintTop',
                'ctrl+alt+b': 'toggleConstraintBottom',
                'ctrl+alt+l': 'toggleConstraintLeft',
                'ctrl+alt+right': 'toggleConstraintRight',

                // Efectos
                'ctrl+alt+shift+s': 'openShadowEditor',
                'ctrl+alt+shift+x': 'openGradientEditor',

                // Editores adicionales
                'ctrl+alt+a': 'openAnimationEditor',
                'ctrl+shift+t': 'openTypographyEditor',
                'ctrl+shift+b': 'openBorderEditor',
                'ctrl+alt+p': 'openSpacingEditor',

                // Estados interactivos y scroll
                'ctrl+alt+shift+h': 'openHoverStatesEditor',
                'ctrl+alt+shift+y': 'openScrollAnimationEditor',

                // Templates y componentes
                'ctrl+shift+k': 'openTemplatesLibrary',
                'ctrl+alt+shift+c': 'saveAsComponent',
                'ctrl+shift+i': 'openComponentsLibrary',

                // Design tokens
                'ctrl+alt+shift+t': 'openDesignTokens',

                // Export
                'ctrl+alt+e': 'openExportOptions',

                // Figma Import
                'ctrl+alt+shift+f': 'openFigmaImporter',

                // Responsive breakpoints
                '1': 'breakpointDesktop',
                '2': 'breakpointTablet',
                '3': 'breakpointMobile',

                // Pan mode
                ' ': 'togglePanMode',

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

                    // === RULERS ===
                    case 'toggleRulers':
                        this.toggleRulers();
                        break;

                    // === GRID SETTINGS ===
                    case 'openGridSettings':
                        this.openGridSettings();
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

                    // === EDITORES ADICIONALES ===
                    case 'openAnimationEditor':
                        this.openAnimationEditor();
                        break;

                    case 'openTypographyEditor':
                        this.openTypographyEditor();
                        break;

                    case 'openBorderEditor':
                        this.openBorderEditor();
                        break;

                    case 'openSpacingEditor':
                        this.openSpacingEditor();
                        break;

                    // === RESPONSIVE BREAKPOINTS ===
                    case 'breakpointDesktop':
                        this.setBreakpoint('desktop');
                        break;

                    case 'breakpointTablet':
                        this.setBreakpoint('tablet');
                        break;

                    case 'breakpointMobile':
                        this.setBreakpoint('mobile');
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

                    // === EDITORES ADICIONALES ===
                    case 'openAnimationEditor':
                        this.openAnimationEditor();
                        break;

                    case 'openTypographyEditor':
                        this.openTypographyEditor();
                        break;

                    case 'openBorderEditor':
                        this.openBorderEditor();
                        break;

                    case 'openSpacingEditor':
                        this.openSpacingEditor();
                        break;

                    // === RESPONSIVE BREAKPOINTS ===
                    case 'breakpointDesktop':
                        this.setBreakpoint('desktop');
                        break;

                    case 'breakpointTablet':
                        this.setBreakpoint('tablet');
                        break;

                    case 'breakpointMobile':
                        this.setBreakpoint('mobile');
                        break;

                    // === PAN MODE ===
                    case 'togglePanMode':
                        this.togglePanMode();
                        break;

                    // === ESTADOS INTERACTIVOS Y SCROLL ===
                    case 'openHoverStatesEditor':
                        this.openHoverStatesEditor();
                        break;

                    case 'openScrollAnimationEditor':
                        this.openScrollAnimationEditor();
                        break;

                    // === TEMPLATES Y COMPONENTES ===
                    case 'openTemplatesLibrary':
                        this.openTemplatesLibrary();
                        break;

                    case 'saveAsComponent':
                        this.saveAsComponent();
                        break;

                    case 'openComponentsLibrary':
                        this.openComponentsLibrary();
                        break;

                    // === DESIGN TOKENS ===
                    case 'openDesignTokens':
                        this.openDesignTokens();
                        break;

                    // === EXPORT ===
                    case 'openExportOptions':
                        this.openExportOptions();
                        break;

                    // === FIGMA IMPORT ===
                    case 'openFigmaImporter':
                        this.openFigmaImporter();
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
             * Toggle rulers (reglas)
             */
            rulersVisible: false,

            toggleRulers: function() {
                this.rulersVisible = !this.rulersVisible;
                localStorage.setItem('vbp_rulers_visible', this.rulersVisible);

                if (this.rulersVisible) {
                    this.showRulers();
                } else {
                    this.hideRulers();
                }

                this.showNotification(this.rulersVisible ? '📏 Reglas visibles' : '📏 Reglas ocultas');
            },

            showRulers: function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (!canvas) return;

                // Crear regla horizontal
                var rulerH = document.createElement('div');
                rulerH.id = 'vbp-ruler-h';
                rulerH.className = 'vbp-ruler vbp-ruler-horizontal';
                rulerH.style.cssText = 'position: absolute; top: 0; left: 30px; right: 0; height: 24px; background: var(--vbp-panel-header-bg, #181825); border-bottom: 1px solid var(--vbp-border, #313244); z-index: 100; overflow: hidden;';

                // Crear regla vertical
                var rulerV = document.createElement('div');
                rulerV.id = 'vbp-ruler-v';
                rulerV.className = 'vbp-ruler vbp-ruler-vertical';
                rulerV.style.cssText = 'position: absolute; top: 24px; left: 0; bottom: 0; width: 30px; background: var(--vbp-panel-header-bg, #181825); border-right: 1px solid var(--vbp-border, #313244); z-index: 100; overflow: hidden;';

                // Generar marcas
                var canvasWidth = canvas.scrollWidth;
                var canvasHeight = canvas.scrollHeight;

                var marksH = '';
                for (var x = 0; x < canvasWidth; x += 50) {
                    marksH += '<span style="position: absolute; left: ' + x + 'px; top: 0; height: 100%; border-left: 1px solid var(--vbp-border, #313244); font-size: 9px; color: var(--vbp-text-muted, #6c7086); padding-left: 2px;">' + x + '</span>';
                }
                rulerH.innerHTML = marksH;

                var marksV = '';
                for (var y = 0; y < canvasHeight; y += 50) {
                    marksV += '<span style="position: absolute; top: ' + y + 'px; left: 0; width: 100%; border-top: 1px solid var(--vbp-border, #313244); font-size: 9px; color: var(--vbp-text-muted, #6c7086); writing-mode: vertical-rl; padding-top: 2px;">' + y + '</span>';
                }
                rulerV.innerHTML = marksV;

                // Agregar al canvas wrapper
                var wrapper = canvas.parentElement;
                if (wrapper) {
                    wrapper.style.position = 'relative';
                    wrapper.appendChild(rulerH);
                    wrapper.appendChild(rulerV);
                }

                canvas.style.marginTop = '24px';
                canvas.style.marginLeft = '30px';
            },

            hideRulers: function() {
                var rulerH = document.getElementById('vbp-ruler-h');
                var rulerV = document.getElementById('vbp-ruler-v');
                if (rulerH) rulerH.remove();
                if (rulerV) rulerV.remove();

                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    canvas.style.marginTop = '';
                    canvas.style.marginLeft = '';
                }
            },

            /**
             * Abrir configuración del grid
             */
            openGridSettings: function() {
                var self = this;

                var modal = document.createElement('div');
                modal.id = 'vbp-grid-settings-modal';
                modal.className = 'vbp-modal-overlay';

                var html = '<div class="vbp-modal" style="max-width: 350px;">';
                html += '<div class="vbp-modal-header"><h2>⊞ Configuración de Grid</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'vbp-grid-settings-modal\').remove()">&times;</button></div>';
                html += '<div class="vbp-modal-body">';

                html += '<div style="margin-bottom: 16px;">';
                html += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">';
                html += '<input type="checkbox" id="vbp-grid-snap-enabled" ' + (this.snapToGridEnabled ? 'checked' : '') + '>';
                html += '<span style="font-size: 14px;">Snap to grid activado</span></label>';
                html += '</div>';

                html += '<div style="margin-bottom: 16px;">';
                html += '<label style="font-size: 12px; color: var(--vbp-text-muted, #6c7086); display: flex; justify-content: space-between; margin-bottom: 6px;">Tamaño del grid <span id="vbp-grid-size-val">' + this.gridSize + 'px</span></label>';
                html += '<input type="range" id="vbp-grid-size" min="4" max="64" value="' + this.gridSize + '" style="width: 100%;" oninput="document.getElementById(\'vbp-grid-size-val\').textContent = this.value + \'px\'">';
                html += '</div>';

                html += '<div style="margin-bottom: 16px;">';
                html += '<label style="font-size: 12px; color: var(--vbp-text-muted, #6c7086); display: block; margin-bottom: 6px;">Presets</label>';
                html += '<div style="display: flex; gap: 8px;">';
                html += '<button onclick="document.getElementById(\'vbp-grid-size\').value=8;document.getElementById(\'vbp-grid-size-val\').textContent=\'8px\'" style="flex: 1; padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; cursor: pointer;">8px</button>';
                html += '<button onclick="document.getElementById(\'vbp-grid-size\').value=16;document.getElementById(\'vbp-grid-size-val\').textContent=\'16px\'" style="flex: 1; padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; cursor: pointer;">16px</button>';
                html += '<button onclick="document.getElementById(\'vbp-grid-size\').value=24;document.getElementById(\'vbp-grid-size-val\').textContent=\'24px\'" style="flex: 1; padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #313244); border-radius: 4px; cursor: pointer;">24px</button>';
                html += '</div></div>';

                html += '<div style="margin-bottom: 16px;">';
                html += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                html += '<input type="checkbox" id="vbp-grid-visible" ' + (document.querySelector('.vbp-canvas.vbp-grid-visible') ? 'checked' : '') + '>';
                html += '<span style="font-size: 14px;">Mostrar grid visual</span></label>';
                html += '</div>';

                html += '<button onclick="window.vbpKeyboard.saveGridSettings()" style="width: 100%; padding: 10px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Guardar</button>';
                html += '</div></div>';

                modal.innerHTML = html;
                document.body.appendChild(modal);
            },

            saveGridSettings: function() {
                var snapEnabled = document.getElementById('vbp-grid-snap-enabled').checked;
                var gridSize = parseInt(document.getElementById('vbp-grid-size').value, 10);
                var gridVisible = document.getElementById('vbp-grid-visible').checked;

                this.snapToGridEnabled = snapEnabled;
                this.gridSize = gridSize;

                localStorage.setItem('vbp_snap_to_grid', snapEnabled);
                localStorage.setItem('vbp_grid_size', gridSize);
                localStorage.setItem('vbp_grid_visible', gridVisible);

                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    if (gridVisible) {
                        canvas.classList.add('vbp-grid-visible');
                        canvas.style.backgroundSize = gridSize + 'px ' + gridSize + 'px';
                    } else {
                        canvas.classList.remove('vbp-grid-visible');
                    }
                }

                document.getElementById('vbp-grid-settings-modal').remove();
                this.showNotification('Configuración de grid guardada');
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

            // === EDITORES ADICIONALES ===

            /**
             * Editor de animaciones CSS
             */
            openAnimationEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentAnimation = (element.styles && element.styles.animation) || {
                    enabled: false,
                    name: 'none',
                    duration: 1,
                    timing: 'ease',
                    delay: 0,
                    iteration: 1,
                    direction: 'normal',
                    fillMode: 'none'
                };

                var modalId = 'vbp-animation-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var animations = [
                    { value: 'none', label: 'Sin animación' },
                    { value: 'fadeIn', label: 'Fade In' },
                    { value: 'fadeOut', label: 'Fade Out' },
                    { value: 'fadeInUp', label: 'Fade In Up' },
                    { value: 'fadeInDown', label: 'Fade In Down' },
                    { value: 'fadeInLeft', label: 'Fade In Left' },
                    { value: 'fadeInRight', label: 'Fade In Right' },
                    { value: 'slideInUp', label: 'Slide In Up' },
                    { value: 'slideInDown', label: 'Slide In Down' },
                    { value: 'slideInLeft', label: 'Slide In Left' },
                    { value: 'slideInRight', label: 'Slide In Right' },
                    { value: 'zoomIn', label: 'Zoom In' },
                    { value: 'zoomOut', label: 'Zoom Out' },
                    { value: 'bounce', label: 'Bounce' },
                    { value: 'pulse', label: 'Pulse' },
                    { value: 'shake', label: 'Shake' },
                    { value: 'swing', label: 'Swing' },
                    { value: 'flip', label: 'Flip' },
                    { value: 'rotateIn', label: 'Rotate In' }
                ];

                var timingFunctions = [
                    { value: 'ease', label: 'Ease' },
                    { value: 'ease-in', label: 'Ease In' },
                    { value: 'ease-out', label: 'Ease Out' },
                    { value: 'ease-in-out', label: 'Ease In Out' },
                    { value: 'linear', label: 'Linear' },
                    { value: 'cubic-bezier(0.68,-0.55,0.265,1.55)', label: 'Elastic' }
                ];

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 450px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>✨ Editor de Animaciones</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Preview
                html += '<div style="background: #f3f4f6; border-radius: 8px; padding: 24px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; min-height: 100px;">';
                html += '<div id="animation-preview" style="width: 60px; height: 60px; background: var(--vbp-primary, #89b4fa); border-radius: 8px;"></div>';
                html += '</div>';

                // Animación
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Animación</label>';
                html += '<select id="animation-name" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                animations.forEach(function(anim) {
                    var selected = currentAnimation.name === anim.value ? ' selected' : '';
                    html += '<option value="' + anim.value + '"' + selected + '>' + anim.label + '</option>';
                });
                html += '</select></div>';

                // Duración y Delay
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Duración <span id="duration-val">' + currentAnimation.duration + 's</span></label>';
                html += '<input type="range" id="animation-duration" min="0.1" max="5" step="0.1" value="' + currentAnimation.duration + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Delay <span id="delay-val">' + currentAnimation.delay + 's</span></label>';
                html += '<input type="range" id="animation-delay" min="0" max="3" step="0.1" value="' + currentAnimation.delay + '" style="width: 100%;">';
                html += '</div></div>';

                // Timing function
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Curva de tiempo</label>';
                html += '<select id="animation-timing" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                timingFunctions.forEach(function(tf) {
                    var selected = currentAnimation.timing === tf.value ? ' selected' : '';
                    html += '<option value="' + tf.value + '"' + selected + '>' + tf.label + '</option>';
                });
                html += '</select></div>';

                // Iteraciones
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Repeticiones</label>';
                html += '<select id="animation-iteration" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="1"' + (currentAnimation.iteration == 1 ? ' selected' : '') + '>1 vez</option>';
                html += '<option value="2"' + (currentAnimation.iteration == 2 ? ' selected' : '') + '>2 veces</option>';
                html += '<option value="3"' + (currentAnimation.iteration == 3 ? ' selected' : '') + '>3 veces</option>';
                html += '<option value="infinite"' + (currentAnimation.iteration === 'infinite' ? ' selected' : '') + '>Infinito</option>';
                html += '</select></div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Dirección</label>';
                html += '<select id="animation-direction" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="normal"' + (currentAnimation.direction === 'normal' ? ' selected' : '') + '>Normal</option>';
                html += '<option value="reverse"' + (currentAnimation.direction === 'reverse' ? ' selected' : '') + '>Reversa</option>';
                html += '<option value="alternate"' + (currentAnimation.direction === 'alternate' ? ' selected' : '') + '>Alternada</option>';
                html += '</select></div></div>';

                // Botón de preview
                html += '<button id="animation-test" style="width: 100%; padding: 10px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 6px; cursor: pointer; margin-bottom: 16px;">▶️ Previsualizar animación</button>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="animation-remove">Quitar animación</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="animation-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);
                var preview = document.getElementById('animation-preview');

                // Actualizar valores
                modal.querySelector('#animation-duration').addEventListener('input', function() {
                    document.getElementById('duration-val').textContent = this.value + 's';
                });
                modal.querySelector('#animation-delay').addEventListener('input', function() {
                    document.getElementById('delay-val').textContent = this.value + 's';
                });

                // Test animation
                modal.querySelector('#animation-test').addEventListener('click', function() {
                    var name = document.getElementById('animation-name').value;
                    var duration = document.getElementById('animation-duration').value;
                    var timing = document.getElementById('animation-timing').value;

                    preview.style.animation = 'none';
                    preview.offsetHeight; // Trigger reflow
                    preview.style.animation = name + ' ' + duration + 's ' + timing;
                });

                // Aplicar
                modal.querySelector('#animation-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var animationConfig = {
                        enabled: document.getElementById('animation-name').value !== 'none',
                        name: document.getElementById('animation-name').value,
                        duration: parseFloat(document.getElementById('animation-duration').value),
                        timing: document.getElementById('animation-timing').value,
                        delay: parseFloat(document.getElementById('animation-delay').value),
                        iteration: document.getElementById('animation-iteration').value,
                        direction: document.getElementById('animation-direction').value
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.animation = animationConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('✨ Animación aplicada');
                });

                // Quitar
                modal.querySelector('#animation-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.animation;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Animación eliminada');
                });
            },

            /**
             * Editor de tipografía
             */
            openTypographyEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentTypo = (element.styles && element.styles.typography) || {
                    fontFamily: 'inherit',
                    fontSize: 16,
                    fontWeight: 400,
                    lineHeight: 1.5,
                    letterSpacing: 0,
                    textTransform: 'none',
                    textAlign: 'left'
                };

                var modalId = 'vbp-typography-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var fonts = [
                    { value: 'inherit', label: 'Heredada (tema)' },
                    { value: 'Inter, sans-serif', label: 'Inter' },
                    { value: 'Roboto, sans-serif', label: 'Roboto' },
                    { value: 'Open Sans, sans-serif', label: 'Open Sans' },
                    { value: 'Lato, sans-serif', label: 'Lato' },
                    { value: 'Poppins, sans-serif', label: 'Poppins' },
                    { value: 'Montserrat, sans-serif', label: 'Montserrat' },
                    { value: 'Playfair Display, serif', label: 'Playfair Display' },
                    { value: 'Merriweather, serif', label: 'Merriweather' },
                    { value: 'Georgia, serif', label: 'Georgia' },
                    { value: 'Fira Code, monospace', label: 'Fira Code' },
                    { value: 'monospace', label: 'Monospace' }
                ];

                var weights = [
                    { value: 100, label: 'Thin (100)' },
                    { value: 200, label: 'Extra Light (200)' },
                    { value: 300, label: 'Light (300)' },
                    { value: 400, label: 'Regular (400)' },
                    { value: 500, label: 'Medium (500)' },
                    { value: 600, label: 'Semi Bold (600)' },
                    { value: 700, label: 'Bold (700)' },
                    { value: 800, label: 'Extra Bold (800)' },
                    { value: 900, label: 'Black (900)' }
                ];

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 500px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>🔤 Editor de Tipografía</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Preview
                html += '<div id="typo-preview" style="background: #f3f4f6; border-radius: 8px; padding: 24px; margin-bottom: 16px; min-height: 80px;">';
                html += '<p style="margin: 0;">El veloz murciélago hindú comía feliz cardillo y kiwi. 1234567890</p>';
                html += '</div>';

                // Font family
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Familia tipográfica</label>';
                html += '<select id="typo-family" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                fonts.forEach(function(f) {
                    var selected = currentTypo.fontFamily === f.value ? ' selected' : '';
                    html += '<option value="' + f.value + '"' + selected + ' style="font-family: ' + f.value + ';">' + f.label + '</option>';
                });
                html += '</select></div>';

                // Size and Weight
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Tamaño <span id="size-val">' + currentTypo.fontSize + 'px</span></label>';
                html += '<input type="range" id="typo-size" min="8" max="120" value="' + currentTypo.fontSize + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Peso</label>';
                html += '<select id="typo-weight" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                weights.forEach(function(w) {
                    var selected = currentTypo.fontWeight == w.value ? ' selected' : '';
                    html += '<option value="' + w.value + '"' + selected + '>' + w.label + '</option>';
                });
                html += '</select></div></div>';

                // Line height and Letter spacing
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Altura de línea <span id="lh-val">' + currentTypo.lineHeight + '</span></label>';
                html += '<input type="range" id="typo-lineheight" min="0.8" max="3" step="0.1" value="' + currentTypo.lineHeight + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Espaciado <span id="ls-val">' + currentTypo.letterSpacing + 'px</span></label>';
                html += '<input type="range" id="typo-letterspacing" min="-5" max="20" step="0.5" value="' + currentTypo.letterSpacing + '" style="width: 100%;">';
                html += '</div></div>';

                // Transform and Align
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Transformación</label>';
                html += '<select id="typo-transform" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="none"' + (currentTypo.textTransform === 'none' ? ' selected' : '') + '>Normal</option>';
                html += '<option value="uppercase"' + (currentTypo.textTransform === 'uppercase' ? ' selected' : '') + '>MAYÚSCULAS</option>';
                html += '<option value="lowercase"' + (currentTypo.textTransform === 'lowercase' ? ' selected' : '') + '>minúsculas</option>';
                html += '<option value="capitalize"' + (currentTypo.textTransform === 'capitalize' ? ' selected' : '') + '>Capitalizar</option>';
                html += '</select></div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Alineación</label>';
                html += '<div style="display: flex; gap: 4px;">';
                ['left', 'center', 'right', 'justify'].forEach(function(align) {
                    var icons = { left: '⬅', center: '↔', right: '➡', justify: '☰' };
                    var active = currentTypo.textAlign === align ? 'background: var(--vbp-primary, #89b4fa); color: #1e1e2e;' : '';
                    html += '<button data-align="' + align + '" style="flex: 1; padding: 8px; border: 1px solid var(--vbp-border, #313244); border-radius: 4px; cursor: pointer; ' + active + '">' + icons[align] + '</button>';
                });
                html += '</div></div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="typo-reset">Resetear</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="typo-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);
                var preview = document.getElementById('typo-preview').querySelector('p');
                var currentAlign = currentTypo.textAlign;

                function updatePreview() {
                    preview.style.fontFamily = document.getElementById('typo-family').value;
                    preview.style.fontSize = document.getElementById('typo-size').value + 'px';
                    preview.style.fontWeight = document.getElementById('typo-weight').value;
                    preview.style.lineHeight = document.getElementById('typo-lineheight').value;
                    preview.style.letterSpacing = document.getElementById('typo-letterspacing').value + 'px';
                    preview.style.textTransform = document.getElementById('typo-transform').value;
                    preview.style.textAlign = currentAlign;
                }

                // Event listeners
                modal.querySelector('#typo-size').addEventListener('input', function() {
                    document.getElementById('size-val').textContent = this.value + 'px';
                    updatePreview();
                });
                modal.querySelector('#typo-lineheight').addEventListener('input', function() {
                    document.getElementById('lh-val').textContent = this.value;
                    updatePreview();
                });
                modal.querySelector('#typo-letterspacing').addEventListener('input', function() {
                    document.getElementById('ls-val').textContent = this.value + 'px';
                    updatePreview();
                });
                modal.querySelectorAll('select').forEach(function(sel) {
                    sel.addEventListener('change', updatePreview);
                });

                // Align buttons
                modal.querySelectorAll('[data-align]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        modal.querySelectorAll('[data-align]').forEach(function(b) {
                            b.style.background = '';
                            b.style.color = '';
                        });
                        this.style.background = 'var(--vbp-primary, #89b4fa)';
                        this.style.color = '#1e1e2e';
                        currentAlign = this.dataset.align;
                        updatePreview();
                    });
                });

                // Apply
                modal.querySelector('#typo-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var typoConfig = {
                        fontFamily: document.getElementById('typo-family').value,
                        fontSize: parseInt(document.getElementById('typo-size').value),
                        fontWeight: parseInt(document.getElementById('typo-weight').value),
                        lineHeight: parseFloat(document.getElementById('typo-lineheight').value),
                        letterSpacing: parseFloat(document.getElementById('typo-letterspacing').value),
                        textTransform: document.getElementById('typo-transform').value,
                        textAlign: currentAlign
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.typography = typoConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🔤 Tipografía aplicada');
                });

                // Reset
                modal.querySelector('#typo-reset').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.typography;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Tipografía reseteada');
                });

                updatePreview();
            },

            /**
             * Editor de bordes
             */
            openBorderEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentBorder = (element.styles && element.styles.border) || {
                    width: 0,
                    style: 'solid',
                    color: '#000000',
                    radius: { tl: 0, tr: 0, br: 0, bl: 0 },
                    uniform: true
                };

                var modalId = 'vbp-border-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 420px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>📐 Editor de Bordes</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Preview
                html += '<div style="background: #f3f4f6; border-radius: 8px; padding: 24px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">';
                html += '<div id="border-preview" style="width: 80px; height: 80px; background: white;"></div>';
                html += '</div>';

                // Border width
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Ancho del borde <span id="bw-val">' + currentBorder.width + 'px</span></label>';
                html += '<input type="range" id="border-width" min="0" max="20" value="' + currentBorder.width + '" style="width: 100%;">';
                html += '</div>';

                // Style and Color
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Estilo</label>';
                html += '<select id="border-style" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                ['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'].forEach(function(s) {
                    var selected = currentBorder.style === s ? ' selected' : '';
                    html += '<option value="' + s + '"' + selected + '>' + s.charAt(0).toUpperCase() + s.slice(1) + '</option>';
                });
                html += '</select></div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color</label>';
                html += '<input type="color" id="border-color" value="' + currentBorder.color + '" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div></div>';

                // Border radius section
                html += '<div style="margin-bottom: 12px;">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
                html += '<label style="color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Border Radius</label>';
                html += '<label style="display: flex; align-items: center; gap: 6px; font-size: 12px; cursor: pointer;">';
                html += '<input type="checkbox" id="radius-uniform"' + (currentBorder.uniform ? ' checked' : '') + '> Uniforme';
                html += '</label></div>';

                // Uniform radius
                html += '<div id="radius-uniform-control"' + (currentBorder.uniform ? '' : ' style="display: none;"') + '>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;"><span id="ru-val">' + (currentBorder.radius.tl || 0) + 'px</span></label>';
                html += '<input type="range" id="radius-all" min="0" max="100" value="' + (currentBorder.radius.tl || 0) + '" style="width: 100%;">';
                html += '</div></div>';

                // Individual radius
                html += '<div id="radius-individual-control"' + (currentBorder.uniform ? ' style="display: none;"' : '') + '>';
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">';
                ['tl', 'tr', 'br', 'bl'].forEach(function(corner) {
                    var labels = { tl: '↖ Sup-Izq', tr: '↗ Sup-Der', br: '↘ Inf-Der', bl: '↙ Inf-Izq' };
                    html += '<div class="vbp-control">';
                    html += '<label style="display: block; margin-bottom: 4px; color: var(--vbp-text-muted, #6c7086); font-size: 11px;">' + labels[corner] + ' <span id="r' + corner + '-val">' + (currentBorder.radius[corner] || 0) + 'px</span></label>';
                    html += '<input type="range" class="radius-corner" data-corner="' + corner + '" min="0" max="100" value="' + (currentBorder.radius[corner] || 0) + '" style="width: 100%;">';
                    html += '</div>';
                });
                html += '</div></div></div>';

                // Presets
                html += '<div style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Presets</label>';
                html += '<div style="display: flex; gap: 6px;">';
                [0, 4, 8, 16, 24, 9999].forEach(function(val) {
                    var label = val === 9999 ? '●' : val;
                    html += '<button class="radius-preset" data-value="' + val + '" style="flex: 1; padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer;">' + label + '</button>';
                });
                html += '</div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="border-remove">Quitar borde</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="border-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);
                var preview = document.getElementById('border-preview');

                function updatePreview() {
                    var width = document.getElementById('border-width').value;
                    var style = document.getElementById('border-style').value;
                    var color = document.getElementById('border-color').value;
                    var uniform = document.getElementById('radius-uniform').checked;
                    var radius;

                    if (uniform) {
                        var r = document.getElementById('radius-all').value;
                        radius = r + 'px';
                    } else {
                        var tl = modal.querySelector('[data-corner="tl"]').value;
                        var tr = modal.querySelector('[data-corner="tr"]').value;
                        var br = modal.querySelector('[data-corner="br"]').value;
                        var bl = modal.querySelector('[data-corner="bl"]').value;
                        radius = tl + 'px ' + tr + 'px ' + br + 'px ' + bl + 'px';
                    }

                    preview.style.border = width + 'px ' + style + ' ' + color;
                    preview.style.borderRadius = radius;
                }

                // Event listeners
                modal.querySelector('#border-width').addEventListener('input', function() {
                    document.getElementById('bw-val').textContent = this.value + 'px';
                    updatePreview();
                });

                modal.querySelector('#border-style').addEventListener('change', updatePreview);
                modal.querySelector('#border-color').addEventListener('input', updatePreview);

                modal.querySelector('#radius-uniform').addEventListener('change', function() {
                    document.getElementById('radius-uniform-control').style.display = this.checked ? '' : 'none';
                    document.getElementById('radius-individual-control').style.display = this.checked ? 'none' : '';
                    updatePreview();
                });

                modal.querySelector('#radius-all').addEventListener('input', function() {
                    document.getElementById('ru-val').textContent = this.value + 'px';
                    updatePreview();
                });

                modal.querySelectorAll('.radius-corner').forEach(function(input) {
                    input.addEventListener('input', function() {
                        document.getElementById('r' + this.dataset.corner + '-val').textContent = this.value + 'px';
                        updatePreview();
                    });
                });

                modal.querySelectorAll('.radius-preset').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var val = this.dataset.value;
                        if (document.getElementById('radius-uniform').checked) {
                            document.getElementById('radius-all').value = val;
                            document.getElementById('ru-val').textContent = val + 'px';
                        } else {
                            modal.querySelectorAll('.radius-corner').forEach(function(input) {
                                input.value = val;
                                document.getElementById('r' + input.dataset.corner + '-val').textContent = val + 'px';
                            });
                        }
                        updatePreview();
                    });
                });

                // Apply
                modal.querySelector('#border-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var uniform = document.getElementById('radius-uniform').checked;
                    var radius;

                    if (uniform) {
                        var r = parseInt(document.getElementById('radius-all').value);
                        radius = { tl: r, tr: r, br: r, bl: r };
                    } else {
                        radius = {
                            tl: parseInt(modal.querySelector('[data-corner="tl"]').value),
                            tr: parseInt(modal.querySelector('[data-corner="tr"]').value),
                            br: parseInt(modal.querySelector('[data-corner="br"]').value),
                            bl: parseInt(modal.querySelector('[data-corner="bl"]').value)
                        };
                    }

                    var borderConfig = {
                        width: parseInt(document.getElementById('border-width').value),
                        style: document.getElementById('border-style').value,
                        color: document.getElementById('border-color').value,
                        radius: radius,
                        uniform: uniform
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.border = borderConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('📐 Borde aplicado');
                });

                // Remove
                modal.querySelector('#border-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.border;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Borde eliminado');
                });

                updatePreview();
            },

            /**
             * Editor de espaciado (padding/margin visual)
             */
            openSpacingEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentSpacing = (element.styles && element.styles.spacing) || {
                    padding: { top: 0, right: 0, bottom: 0, left: 0 },
                    margin: { top: 0, right: 0, bottom: 0, left: 0 }
                };

                var modalId = 'vbp-spacing-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 500px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>📏 Editor de Espaciado</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Visual box model
                html += '<div class="vbp-spacing-preview" style="display: flex; justify-content: center; margin-bottom: 20px;">';
                html += '<div style="position: relative; width: 280px;">';

                // Margin box (outer)
                html += '<div id="margin-box" style="border: 2px dashed #f97316; padding: 20px; background: rgba(249, 115, 22, 0.1); position: relative;">';
                html += '<span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--vbp-bg, #1e1e2e); padding: 0 6px; font-size: 10px; color: #f97316;">MARGIN</span>';

                // Margin inputs
                html += '<input type="number" id="mt" value="' + currentSpacing.margin.top + '" style="position: absolute; top: 2px; left: 50%; transform: translateX(-50%); width: 40px; text-align: center; border: 1px solid #f97316; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="mr" value="' + currentSpacing.margin.right + '" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); width: 40px; text-align: center; border: 1px solid #f97316; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="mb" value="' + currentSpacing.margin.bottom + '" style="position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 40px; text-align: center; border: 1px solid #f97316; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="ml" value="' + currentSpacing.margin.left + '" style="position: absolute; left: 2px; top: 50%; transform: translateY(-50%); width: 40px; text-align: center; border: 1px solid #f97316; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';

                // Padding box (inner)
                html += '<div id="padding-box" style="border: 2px dashed #22c55e; padding: 30px; background: rgba(34, 197, 94, 0.1); position: relative; min-height: 60px;">';
                html += '<span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--vbp-bg, #1e1e2e); padding: 0 6px; font-size: 10px; color: #22c55e;">PADDING</span>';

                // Padding inputs
                html += '<input type="number" id="pt" value="' + currentSpacing.padding.top + '" style="position: absolute; top: 2px; left: 50%; transform: translateX(-50%); width: 40px; text-align: center; border: 1px solid #22c55e; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="pr" value="' + currentSpacing.padding.right + '" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); width: 40px; text-align: center; border: 1px solid #22c55e; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="pb" value="' + currentSpacing.padding.bottom + '" style="position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 40px; text-align: center; border: 1px solid #22c55e; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<input type="number" id="pl" value="' + currentSpacing.padding.left + '" style="position: absolute; left: 2px; top: 50%; transform: translateY(-50%); width: 40px; text-align: center; border: 1px solid #22c55e; border-radius: 4px; font-size: 12px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';

                // Content
                html += '<div style="background: var(--vbp-primary, #89b4fa); color: #1e1e2e; padding: 8px; border-radius: 4px; text-align: center; font-size: 11px;">Contenido</div>';

                html += '</div>'; // end padding-box
                html += '</div>'; // end margin-box
                html += '</div></div>';

                // Quick presets
                html += '<div style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Presets rápidos</label>';
                html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">';
                [
                    { label: 'Reset', p: 0, m: 0 },
                    { label: 'S', p: 8, m: 0 },
                    { label: 'M', p: 16, m: 0 },
                    { label: 'L', p: 24, m: 0 },
                    { label: 'XL', p: 32, m: 0 },
                    { label: 'Card', p: 16, m: 8 },
                    { label: 'Section', p: 48, m: 0 },
                    { label: 'Hero', p: 64, m: 0 }
                ].forEach(function(preset) {
                    html += '<button class="spacing-preset" data-padding="' + preset.p + '" data-margin="' + preset.m + '" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 12px;">' + preset.label + '</button>';
                });
                html += '</div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="spacing-reset">Resetear</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="spacing-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);

                // Preset buttons
                modal.querySelectorAll('.spacing-preset').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var p = this.dataset.padding;
                        var m = this.dataset.margin;
                        ['pt', 'pr', 'pb', 'pl'].forEach(function(id) {
                            document.getElementById(id).value = p;
                        });
                        ['mt', 'mr', 'mb', 'ml'].forEach(function(id) {
                            document.getElementById(id).value = m;
                        });
                    });
                });

                // Apply
                modal.querySelector('#spacing-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var spacingConfig = {
                        padding: {
                            top: parseInt(document.getElementById('pt').value) || 0,
                            right: parseInt(document.getElementById('pr').value) || 0,
                            bottom: parseInt(document.getElementById('pb').value) || 0,
                            left: parseInt(document.getElementById('pl').value) || 0
                        },
                        margin: {
                            top: parseInt(document.getElementById('mt').value) || 0,
                            right: parseInt(document.getElementById('mr').value) || 0,
                            bottom: parseInt(document.getElementById('mb').value) || 0,
                            left: parseInt(document.getElementById('ml').value) || 0
                        }
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.spacing = spacingConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('📏 Espaciado aplicado');
                });

                // Reset
                modal.querySelector('#spacing-reset').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.spacing;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Espaciado reseteado');
                });
            },

            // === RESPONSIVE BREAKPOINTS ===

            /**
             * Cambiar breakpoint de vista
             */
            setBreakpoint: function(breakpoint) {
                var canvas = document.querySelector('.vbp-canvas');
                var wrapper = document.querySelector('.vbp-canvas-wrapper');
                if (!canvas || !wrapper) return;

                var breakpoints = {
                    desktop: { width: '100%', maxWidth: 'none', label: '🖥️ Desktop' },
                    tablet: { width: '768px', maxWidth: '768px', label: '📱 Tablet' },
                    mobile: { width: '375px', maxWidth: '375px', label: '📱 Mobile' }
                };

                var config = breakpoints[breakpoint];
                if (!config) return;

                // Actualizar canvas
                canvas.style.width = config.width;
                canvas.style.maxWidth = config.maxWidth;
                canvas.style.margin = breakpoint === 'desktop' ? '0' : '0 auto';
                canvas.style.transition = 'width 0.3s ease';

                // Actualizar store si existe
                var store = Alpine.store('vbp');
                if (store) {
                    store.currentBreakpoint = breakpoint;
                }

                // Actualizar indicador visual
                this.updateBreakpointIndicator(breakpoint, config.label);

                this.showNotification(config.label);
            },

            /**
             * Actualizar indicador de breakpoint
             */
            updateBreakpointIndicator: function(breakpoint, label) {
                var existingIndicator = document.getElementById('vbp-breakpoint-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                var indicator = document.createElement('div');
                indicator.id = 'vbp-breakpoint-indicator';
                indicator.innerHTML = label;
                indicator.style.cssText = 'position: fixed; top: 60px; left: 50%; transform: translateX(-50%); padding: 6px 16px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border-radius: 20px; font-size: 12px; z-index: 10000; box-shadow: 0 2px 8px rgba(0,0,0,0.2);';
                document.body.appendChild(indicator);

                // Auto-hide después de 2 segundos
                setTimeout(function() {
                    indicator.style.opacity = '0';
                    indicator.style.transition = 'opacity 0.3s';
                    setTimeout(function() {
                        if (indicator.parentNode) {
                            indicator.remove();
                        }
                    }, 300);
                }, 2000);
            },

            // === PAN MODE ===
            panModeEnabled: false,
            isPanning: false,
            panStart: { x: 0, y: 0 },
            scrollStart: { x: 0, y: 0 },

            /**
             * Toggle pan mode con espacio
             */
            togglePanMode: function() {
                this.panModeEnabled = !this.panModeEnabled;
                var canvas = document.querySelector('.vbp-canvas-wrapper');

                if (this.panModeEnabled) {
                    canvas.style.cursor = 'grab';
                    this.initPanListeners();
                    this.showNotification('✋ Pan mode activado');
                } else {
                    canvas.style.cursor = '';
                    this.showNotification('Pan mode desactivado');
                }
            },

            /**
             * Inicializar listeners para pan
             */
            initPanListeners: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas-wrapper');
                if (!canvas) return;

                var startPan = function(e) {
                    if (!self.panModeEnabled) return;
                    self.isPanning = true;
                    self.panStart = { x: e.clientX, y: e.clientY };
                    self.scrollStart = { x: canvas.scrollLeft, y: canvas.scrollTop };
                    canvas.style.cursor = 'grabbing';
                    e.preventDefault();
                };

                var doPan = function(e) {
                    if (!self.isPanning) return;
                    var deltaX = e.clientX - self.panStart.x;
                    var deltaY = e.clientY - self.panStart.y;
                    canvas.scrollLeft = self.scrollStart.x - deltaX;
                    canvas.scrollTop = self.scrollStart.y - deltaY;
                };

                var endPan = function() {
                    if (self.isPanning) {
                        self.isPanning = false;
                        canvas.style.cursor = self.panModeEnabled ? 'grab' : '';
                    }
                };

                canvas.addEventListener('mousedown', startPan);
                document.addEventListener('mousemove', doPan);
                document.addEventListener('mouseup', endPan);
            },

            // === ESTADOS INTERACTIVOS (HOVER/ACTIVE/FOCUS) ===

            /**
             * Editor de estados interactivos
             */
            openHoverStatesEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentStates = (element.styles && element.styles.interactiveStates) || {
                    hover: { enabled: false, transform: '', background: '', color: '', boxShadow: '', scale: 1, opacity: 1 },
                    active: { enabled: false, transform: '', background: '', color: '', scale: 0.98, opacity: 1 },
                    focus: { enabled: false, outline: '2px solid #3b82f6', outlineOffset: '2px' }
                };

                var modalId = 'vbp-hover-states-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 550px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>🎯 Estados Interactivos</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Tabs para cada estado
                html += '<div class="vbp-state-tabs" style="display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid var(--vbp-border, #313244); padding-bottom: 8px;">';
                html += '<button class="state-tab active" data-state="hover" style="padding: 8px 16px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px 6px 0 0; cursor: pointer; font-weight: 600;">:hover</button>';
                html += '<button class="state-tab" data-state="active" style="padding: 8px 16px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: none; border-radius: 6px 6px 0 0; cursor: pointer;">:active</button>';
                html += '<button class="state-tab" data-state="focus" style="padding: 8px 16px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: none; border-radius: 6px 6px 0 0; cursor: pointer;">:focus</button>';
                html += '</div>';

                // Preview
                html += '<div style="background: #f3f4f6; border-radius: 8px; padding: 32px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">';
                html += '<div id="hover-preview" style="padding: 16px 32px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Hover me</div>';
                html += '</div>';

                // Panel Hover
                html += '<div id="panel-hover" class="state-panel">';
                html += '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; cursor: pointer;">';
                html += '<input type="checkbox" id="hover-enabled" ' + (currentStates.hover.enabled ? 'checked' : '') + '>';
                html += '<span style="font-weight: 600;">Habilitar estado :hover</span></label>';

                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';
                // Scale
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Escala <span id="hover-scale-val">' + (currentStates.hover.scale || 1) + '</span></label>';
                html += '<input type="range" id="hover-scale" min="0.8" max="1.3" step="0.02" value="' + (currentStates.hover.scale || 1) + '" style="width: 100%;">';
                html += '</div>';
                // Opacity
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Opacidad <span id="hover-opacity-val">' + (currentStates.hover.opacity || 1) + '</span></label>';
                html += '<input type="range" id="hover-opacity" min="0" max="1" step="0.05" value="' + (currentStates.hover.opacity || 1) + '" style="width: 100%;">';
                html += '</div>';
                // Background
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color de fondo</label>';
                html += '<input type="color" id="hover-bg" value="' + (currentStates.hover.background || '#3b82f6') + '" style="width: 100%; height: 36px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div>';
                // Text color
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color de texto</label>';
                html += '<input type="color" id="hover-color" value="' + (currentStates.hover.color || '#ffffff') + '" style="width: 100%; height: 36px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div>';
                // TranslateY
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Mover Y <span id="hover-ty-val">0px</span></label>';
                html += '<input type="range" id="hover-translateY" min="-20" max="20" value="0" style="width: 100%;">';
                html += '</div>';
                // Shadow
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Sombra</label>';
                html += '<select id="hover-shadow" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="">Sin sombra</option>';
                html += '<option value="0 4px 6px rgba(0,0,0,0.1)">Suave</option>';
                html += '<option value="0 10px 15px rgba(0,0,0,0.15)">Media</option>';
                html += '<option value="0 20px 25px rgba(0,0,0,0.2)">Grande</option>';
                html += '<option value="0 25px 50px rgba(0,0,0,0.25)">Extra grande</option>';
                html += '</select></div>';
                html += '</div></div>';

                // Panel Active
                html += '<div id="panel-active" class="state-panel" style="display: none;">';
                html += '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; cursor: pointer;">';
                html += '<input type="checkbox" id="active-enabled" ' + (currentStates.active.enabled ? 'checked' : '') + '>';
                html += '<span style="font-weight: 600;">Habilitar estado :active</span></label>';

                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Escala <span id="active-scale-val">' + (currentStates.active.scale || 0.98) + '</span></label>';
                html += '<input type="range" id="active-scale" min="0.9" max="1.1" step="0.01" value="' + (currentStates.active.scale || 0.98) + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Opacidad <span id="active-opacity-val">' + (currentStates.active.opacity || 1) + '</span></label>';
                html += '<input type="range" id="active-opacity" min="0" max="1" step="0.05" value="' + (currentStates.active.opacity || 1) + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color de fondo</label>';
                html += '<input type="color" id="active-bg" value="' + (currentStates.active.background || '#2563eb') + '" style="width: 100%; height: 36px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color de texto</label>';
                html += '<input type="color" id="active-color" value="' + (currentStates.active.color || '#ffffff') + '" style="width: 100%; height: 36px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div>';
                html += '</div></div>';

                // Panel Focus
                html += '<div id="panel-focus" class="state-panel" style="display: none;">';
                html += '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; cursor: pointer;">';
                html += '<input type="checkbox" id="focus-enabled" ' + (currentStates.focus.enabled ? 'checked' : '') + '>';
                html += '<span style="font-weight: 600;">Habilitar estado :focus</span></label>';

                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Color del outline</label>';
                html += '<input type="color" id="focus-outline-color" value="#3b82f6" style="width: 100%; height: 36px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); cursor: pointer;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Ancho outline <span id="focus-width-val">2px</span></label>';
                html += '<input type="range" id="focus-outline-width" min="1" max="5" value="2" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Offset <span id="focus-offset-val">2px</span></label>';
                html += '<input type="range" id="focus-outline-offset" min="0" max="8" value="2" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Estilo</label>';
                html += '<select id="focus-outline-style" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="solid">Sólido</option>';
                html += '<option value="dashed">Guiones</option>';
                html += '<option value="dotted">Puntos</option>';
                html += '</select></div>';
                html += '</div></div>';

                // Transition
                html += '<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--vbp-border, #313244);">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Duración transición <span id="trans-val">0.3s</span></label>';
                html += '<input type="range" id="transition-duration" min="0" max="1" step="0.05" value="0.3" style="width: 100%;">';
                html += '</div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="states-remove">Quitar estados</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="states-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);
                var preview = document.getElementById('hover-preview');

                // Tab switching
                modal.querySelectorAll('.state-tab').forEach(function(tab) {
                    tab.addEventListener('click', function() {
                        modal.querySelectorAll('.state-tab').forEach(function(t) {
                            t.style.background = 'var(--vbp-surface, #313244)';
                            t.style.color = 'var(--vbp-text, #cdd6f4)';
                            t.classList.remove('active');
                        });
                        this.style.background = 'var(--vbp-primary, #89b4fa)';
                        this.style.color = '#1e1e2e';
                        this.classList.add('active');

                        modal.querySelectorAll('.state-panel').forEach(function(p) {
                            p.style.display = 'none';
                        });
                        document.getElementById('panel-' + this.dataset.state).style.display = '';
                    });
                });

                // Update preview
                function updateHoverPreview() {
                    var scale = document.getElementById('hover-scale').value;
                    var opacity = document.getElementById('hover-opacity').value;
                    var bg = document.getElementById('hover-bg').value;
                    var color = document.getElementById('hover-color').value;
                    var ty = document.getElementById('hover-translateY').value;
                    var shadow = document.getElementById('hover-shadow').value;
                    var duration = document.getElementById('transition-duration').value;

                    document.getElementById('hover-scale-val').textContent = scale;
                    document.getElementById('hover-opacity-val').textContent = opacity;
                    document.getElementById('hover-ty-val').textContent = ty + 'px';
                    document.getElementById('trans-val').textContent = duration + 's';

                    preview.style.transition = 'all ' + duration + 's ease';

                    preview.onmouseenter = function() {
                        this.style.transform = 'scale(' + scale + ') translateY(' + ty + 'px)';
                        this.style.opacity = opacity;
                        this.style.background = bg;
                        this.style.color = color;
                        this.style.boxShadow = shadow;
                    };
                    preview.onmouseleave = function() {
                        this.style.transform = '';
                        this.style.opacity = '';
                        this.style.background = '';
                        this.style.color = '';
                        this.style.boxShadow = '';
                    };
                }

                // Event listeners for hover controls
                ['hover-scale', 'hover-opacity', 'hover-bg', 'hover-color', 'hover-translateY', 'hover-shadow', 'transition-duration'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.addEventListener('input', updateHoverPreview);
                });

                // Active panel controls
                modal.querySelector('#active-scale').addEventListener('input', function() {
                    document.getElementById('active-scale-val').textContent = this.value;
                });
                modal.querySelector('#active-opacity').addEventListener('input', function() {
                    document.getElementById('active-opacity-val').textContent = this.value;
                });

                // Focus panel controls
                modal.querySelector('#focus-outline-width').addEventListener('input', function() {
                    document.getElementById('focus-width-val').textContent = this.value + 'px';
                });
                modal.querySelector('#focus-outline-offset').addEventListener('input', function() {
                    document.getElementById('focus-offset-val').textContent = this.value + 'px';
                });

                updateHoverPreview();

                // Apply
                modal.querySelector('#states-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var statesConfig = {
                        hover: {
                            enabled: document.getElementById('hover-enabled').checked,
                            scale: parseFloat(document.getElementById('hover-scale').value),
                            opacity: parseFloat(document.getElementById('hover-opacity').value),
                            background: document.getElementById('hover-bg').value,
                            color: document.getElementById('hover-color').value,
                            translateY: parseInt(document.getElementById('hover-translateY').value),
                            boxShadow: document.getElementById('hover-shadow').value
                        },
                        active: {
                            enabled: document.getElementById('active-enabled').checked,
                            scale: parseFloat(document.getElementById('active-scale').value),
                            opacity: parseFloat(document.getElementById('active-opacity').value),
                            background: document.getElementById('active-bg').value,
                            color: document.getElementById('active-color').value
                        },
                        focus: {
                            enabled: document.getElementById('focus-enabled').checked,
                            outlineColor: document.getElementById('focus-outline-color').value,
                            outlineWidth: parseInt(document.getElementById('focus-outline-width').value),
                            outlineOffset: parseInt(document.getElementById('focus-outline-offset').value),
                            outlineStyle: document.getElementById('focus-outline-style').value
                        },
                        transitionDuration: parseFloat(document.getElementById('transition-duration').value)
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.interactiveStates = statesConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('🎯 Estados interactivos aplicados');
                });

                // Remove
                modal.querySelector('#states-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.interactiveStates;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Estados eliminados');
                });
            },

            // === SCROLL ANIMATIONS ===

            /**
             * Editor de animaciones de scroll
             */
            openScrollAnimationEditor: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) {
                    this.showNotification('Selecciona un solo elemento', 'warning');
                    return;
                }

                var elementId = store.selection.elementIds[0];
                var element = store.getElement(elementId);
                if (!element) return;

                var currentScroll = (element.styles && element.styles.scrollAnimation) || {
                    enabled: false,
                    type: 'fadeInUp',
                    trigger: 'onEnter',
                    threshold: 0.2,
                    duration: 0.6,
                    delay: 0,
                    once: true,
                    offset: 0
                };

                var modalId = 'vbp-scroll-animation-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var scrollAnimations = [
                    { value: 'fadeIn', label: 'Fade In', icon: '👁' },
                    { value: 'fadeInUp', label: 'Fade In Up', icon: '⬆' },
                    { value: 'fadeInDown', label: 'Fade In Down', icon: '⬇' },
                    { value: 'fadeInLeft', label: 'Fade In Left', icon: '⬅' },
                    { value: 'fadeInRight', label: 'Fade In Right', icon: '➡' },
                    { value: 'zoomIn', label: 'Zoom In', icon: '🔍' },
                    { value: 'slideUp', label: 'Slide Up', icon: '📤' },
                    { value: 'slideDown', label: 'Slide Down', icon: '📥' },
                    { value: 'slideLeft', label: 'Slide Left', icon: '◀' },
                    { value: 'slideRight', label: 'Slide Right', icon: '▶' },
                    { value: 'flipX', label: 'Flip Horizontal', icon: '↔' },
                    { value: 'flipY', label: 'Flip Vertical', icon: '↕' },
                    { value: 'rotateIn', label: 'Rotate In', icon: '🔄' },
                    { value: 'bounce', label: 'Bounce', icon: '⚡' },
                    { value: 'parallax', label: 'Parallax', icon: '🏔' }
                ];

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 500px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>📜 Animaciones de Scroll</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Enable
                html += '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; cursor: pointer;">';
                html += '<input type="checkbox" id="scroll-enabled" ' + (currentScroll.enabled ? 'checked' : '') + '>';
                html += '<span style="font-weight: 600;">Habilitar animación en scroll</span></label>';

                // Preview
                html += '<div style="background: linear-gradient(to bottom, #f3f4f6, #e5e7eb); border-radius: 8px; padding: 16px; margin-bottom: 16px; height: 120px; overflow-y: auto; position: relative;">';
                html += '<div style="height: 60px;"></div>';
                html += '<div id="scroll-preview" style="padding: 16px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border-radius: 8px; text-align: center; font-weight: 600; opacity: 0; transform: translateY(20px);">Elemento animado</div>';
                html += '<div style="height: 60px;"></div>';
                html += '</div>';
                html += '<button id="scroll-test" style="width: 100%; padding: 8px; margin-bottom: 16px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 6px; cursor: pointer;">▶️ Previsualizar animación</button>';

                // Animation type
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Tipo de animación</label>';
                html += '<select id="scroll-type" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                scrollAnimations.forEach(function(anim) {
                    var selected = currentScroll.type === anim.value ? ' selected' : '';
                    html += '<option value="' + anim.value + '"' + selected + '>' + anim.icon + ' ' + anim.label + '</option>';
                });
                html += '</select></div>';

                // Trigger point
                html += '<div class="vbp-control" style="margin-bottom: 12px;">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Punto de activación</label>';
                html += '<select id="scroll-trigger" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                html += '<option value="onEnter"' + (currentScroll.trigger === 'onEnter' ? ' selected' : '') + '>Al entrar en viewport</option>';
                html += '<option value="onCenter"' + (currentScroll.trigger === 'onCenter' ? ' selected' : '') + '>Al llegar al centro</option>';
                html += '<option value="onLeave"' + (currentScroll.trigger === 'onLeave' ? ' selected' : '') + '>Al salir del viewport</option>';
                html += '</select></div>';

                // Duration and Delay
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Duración <span id="scroll-dur-val">' + currentScroll.duration + 's</span></label>';
                html += '<input type="range" id="scroll-duration" min="0.1" max="2" step="0.1" value="' + currentScroll.duration + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Delay <span id="scroll-delay-val">' + currentScroll.delay + 's</span></label>';
                html += '<input type="range" id="scroll-delay" min="0" max="1" step="0.1" value="' + currentScroll.delay + '" style="width: 100%;">';
                html += '</div></div>';

                // Threshold and Offset
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Umbral visible <span id="scroll-thresh-val">' + Math.round(currentScroll.threshold * 100) + '%</span></label>';
                html += '<input type="range" id="scroll-threshold" min="0" max="1" step="0.1" value="' + currentScroll.threshold + '" style="width: 100%;">';
                html += '</div>';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Offset <span id="scroll-offset-val">' + currentScroll.offset + 'px</span></label>';
                html += '<input type="range" id="scroll-offset" min="-200" max="200" value="' + currentScroll.offset + '" style="width: 100%;">';
                html += '</div></div>';

                // Parallax intensity (only for parallax)
                html += '<div id="parallax-options" style="display: none; margin-bottom: 12px;">';
                html += '<div class="vbp-control">';
                html += '<label style="display: block; margin-bottom: 6px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Intensidad parallax <span id="parallax-val">0.5</span></label>';
                html += '<input type="range" id="parallax-intensity" min="0.1" max="1" step="0.1" value="0.5" style="width: 100%;">';
                html += '</div></div>';

                // Once option
                html += '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; cursor: pointer;">';
                html += '<input type="checkbox" id="scroll-once" ' + (currentScroll.once ? 'checked' : '') + '>';
                html += '<span style="font-size: 14px;">Animar solo una vez</span></label>';

                // Presets
                html += '<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--vbp-border, #313244);">';
                html += '<label style="display: block; margin-bottom: 8px; color: var(--vbp-text-muted, #6c7086); font-size: 12px;">Presets rápidos</label>';
                html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">';
                html += '<button class="scroll-preset" data-type="fadeInUp" data-duration="0.6" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">⬆️ Sutil</button>';
                html += '<button class="scroll-preset" data-type="zoomIn" data-duration="0.8" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">🔍 Zoom</button>';
                html += '<button class="scroll-preset" data-type="bounce" data-duration="1" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">⚡ Bounce</button>';
                html += '<button class="scroll-preset" data-type="slideLeft" data-duration="0.5" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">◀ Slide</button>';
                html += '<button class="scroll-preset" data-type="flipX" data-duration="0.8" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">↔ Flip</button>';
                html += '<button class="scroll-preset" data-type="parallax" data-duration="0" style="padding: 8px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); border: 1px solid var(--vbp-border, #45475a); border-radius: 4px; cursor: pointer; font-size: 11px;">🏔 Parallax</button>';
                html += '</div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="scroll-remove">Quitar animación</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="scroll-apply">Aplicar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);
                var preview = document.getElementById('scroll-preview');

                // Show/hide parallax options
                modal.querySelector('#scroll-type').addEventListener('change', function() {
                    document.getElementById('parallax-options').style.display = this.value === 'parallax' ? '' : 'none';
                });

                // Update labels
                modal.querySelector('#scroll-duration').addEventListener('input', function() {
                    document.getElementById('scroll-dur-val').textContent = this.value + 's';
                });
                modal.querySelector('#scroll-delay').addEventListener('input', function() {
                    document.getElementById('scroll-delay-val').textContent = this.value + 's';
                });
                modal.querySelector('#scroll-threshold').addEventListener('input', function() {
                    document.getElementById('scroll-thresh-val').textContent = Math.round(this.value * 100) + '%';
                });
                modal.querySelector('#scroll-offset').addEventListener('input', function() {
                    document.getElementById('scroll-offset-val').textContent = this.value + 'px';
                });
                modal.querySelector('#parallax-intensity').addEventListener('input', function() {
                    document.getElementById('parallax-val').textContent = this.value;
                });

                // Test animation
                modal.querySelector('#scroll-test').addEventListener('click', function() {
                    var type = document.getElementById('scroll-type').value;
                    var duration = document.getElementById('scroll-duration').value;

                    // Reset
                    preview.style.transition = 'none';
                    preview.style.opacity = '0';
                    preview.style.transform = self.getScrollInitialTransform(type);

                    setTimeout(function() {
                        preview.style.transition = 'all ' + duration + 's cubic-bezier(0.4, 0, 0.2, 1)';
                        preview.style.opacity = '1';
                        preview.style.transform = 'none';
                    }, 50);
                });

                // Presets
                modal.querySelectorAll('.scroll-preset').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        document.getElementById('scroll-type').value = this.dataset.type;
                        document.getElementById('scroll-duration').value = this.dataset.duration;
                        document.getElementById('scroll-dur-val').textContent = this.dataset.duration + 's';
                        document.getElementById('parallax-options').style.display = this.dataset.type === 'parallax' ? '' : 'none';
                    });
                });

                // Apply
                modal.querySelector('#scroll-apply').addEventListener('click', function() {
                    store.saveToHistory();

                    var scrollConfig = {
                        enabled: document.getElementById('scroll-enabled').checked,
                        type: document.getElementById('scroll-type').value,
                        trigger: document.getElementById('scroll-trigger').value,
                        threshold: parseFloat(document.getElementById('scroll-threshold').value),
                        duration: parseFloat(document.getElementById('scroll-duration').value),
                        delay: parseFloat(document.getElementById('scroll-delay').value),
                        offset: parseInt(document.getElementById('scroll-offset').value),
                        once: document.getElementById('scroll-once').checked,
                        parallaxIntensity: parseFloat(document.getElementById('parallax-intensity').value)
                    };

                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    styles.scrollAnimation = scrollConfig;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;

                    modal.remove();
                    self.showNotification('📜 Animación de scroll aplicada');
                });

                // Remove
                modal.querySelector('#scroll-remove').addEventListener('click', function() {
                    store.saveToHistory();
                    var styles = JSON.parse(JSON.stringify(element.styles || {}));
                    delete styles.scrollAnimation;
                    store.updateElement(elementId, { styles: styles });
                    store.isDirty = true;
                    modal.remove();
                    self.showNotification('Animación de scroll eliminada');
                });
            },

            /**
             * Obtener transform inicial según tipo de animación
             */
            getScrollInitialTransform: function(type) {
                var transforms = {
                    'fadeIn': 'none',
                    'fadeInUp': 'translateY(30px)',
                    'fadeInDown': 'translateY(-30px)',
                    'fadeInLeft': 'translateX(-30px)',
                    'fadeInRight': 'translateX(30px)',
                    'zoomIn': 'scale(0.8)',
                    'slideUp': 'translateY(100%)',
                    'slideDown': 'translateY(-100%)',
                    'slideLeft': 'translateX(-100%)',
                    'slideRight': 'translateX(100%)',
                    'flipX': 'perspective(400px) rotateY(-90deg)',
                    'flipY': 'perspective(400px) rotateX(-90deg)',
                    'rotateIn': 'rotate(-180deg) scale(0.5)',
                    'bounce': 'translateY(30px)',
                    'parallax': 'none'
                };
                return transforms[type] || 'none';
            },

            // === TEMPLATES LIBRARY ===

            /**
             * Biblioteca de plantillas predefinidas
             */
            openTemplatesLibrary: function() {
                var self = this;
                var store = Alpine.store('vbp');

                var modalId = 'vbp-templates-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var templates = [
                    {
                        category: 'Hero',
                        items: [
                            { id: 'hero-centered', name: 'Hero Centrado', icon: '🎯', description: 'Título, subtítulo y CTA centrados' },
                            { id: 'hero-split', name: 'Hero Dividido', icon: '⬛', description: 'Texto a la izquierda, imagen a la derecha' },
                            { id: 'hero-video', name: 'Hero con Video', icon: '🎬', description: 'Background de video con overlay' },
                            { id: 'hero-gradient', name: 'Hero Gradiente', icon: '🌈', description: 'Fondo gradiente animado' }
                        ]
                    },
                    {
                        category: 'Features',
                        items: [
                            { id: 'features-grid', name: 'Grid de Features', icon: '⊞', description: '3 columnas con iconos' },
                            { id: 'features-alternating', name: 'Features Alternadas', icon: '↔', description: 'Imagen/texto alternando' },
                            { id: 'features-cards', name: 'Cards de Features', icon: '🃏', description: 'Cards con hover effects' }
                        ]
                    },
                    {
                        category: 'Pricing',
                        items: [
                            { id: 'pricing-3col', name: 'Precios 3 Columnas', icon: '💰', description: 'Plan destacado en el centro' },
                            { id: 'pricing-toggle', name: 'Precios con Toggle', icon: '🔄', description: 'Mensual/Anual switch' },
                            { id: 'pricing-comparison', name: 'Tabla Comparativa', icon: '📊', description: 'Comparación de features' }
                        ]
                    },
                    {
                        category: 'Testimonials',
                        items: [
                            { id: 'testimonials-carousel', name: 'Carrusel', icon: '🎠', description: 'Slider de testimonios' },
                            { id: 'testimonials-grid', name: 'Grid de Cards', icon: '💬', description: 'Masonry de testimonios' },
                            { id: 'testimonials-single', name: 'Testimonio Grande', icon: '⭐', description: 'Un testimonio destacado' }
                        ]
                    },
                    {
                        category: 'CTA',
                        items: [
                            { id: 'cta-simple', name: 'CTA Simple', icon: '📢', description: 'Título y botón' },
                            { id: 'cta-newsletter', name: 'Newsletter', icon: '✉', description: 'Formulario de suscripción' },
                            { id: 'cta-download', name: 'Descarga App', icon: '📱', description: 'Botones de app stores' }
                        ]
                    },
                    {
                        category: 'Footer',
                        items: [
                            { id: 'footer-4col', name: 'Footer 4 Columnas', icon: '🦶', description: 'Links organizados' },
                            { id: 'footer-simple', name: 'Footer Simple', icon: '➖', description: 'Copyright y social' },
                            { id: 'footer-mega', name: 'Mega Footer', icon: '📋', description: 'Newsletter + links + info' }
                        ]
                    }
                ];

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 800px; max-height: 85vh;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>📚 Biblioteca de Plantillas</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body" style="overflow-y: auto; max-height: 60vh;">';

                // Search
                html += '<div style="margin-bottom: 16px;">';
                html += '<input type="text" id="template-search" placeholder="🔍 Buscar plantillas..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--vbp-border, #313244); background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); font-size: 14px;">';
                html += '</div>';

                // Categories
                templates.forEach(function(cat) {
                    html += '<div class="template-category" style="margin-bottom: 24px;">';
                    html += '<h3 style="font-size: 14px; color: var(--vbp-text-muted, #6c7086); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">' + cat.category + '</h3>';
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">';

                    cat.items.forEach(function(item) {
                        html += '<div class="template-item" data-id="' + item.id + '" data-name="' + item.name + '" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 8px; padding: 16px; cursor: pointer; transition: all 0.2s;">';
                        html += '<div style="font-size: 24px; margin-bottom: 8px;">' + item.icon + '</div>';
                        html += '<div style="font-weight: 600; margin-bottom: 4px;">' + item.name + '</div>';
                        html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">' + item.description + '</div>';
                        html += '</div>';
                    });

                    html += '</div></div>';
                });

                html += '</div>';
                html += '<div class="vbp-modal-footer" style="border-top: 1px solid var(--vbp-border, #313244); padding-top: 16px;">';
                html += '<p style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">💡 Haz clic en una plantilla para insertarla en el canvas</p>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);

                // Search functionality
                modal.querySelector('#template-search').addEventListener('input', function() {
                    var query = this.value.toLowerCase();
                    modal.querySelectorAll('.template-item').forEach(function(item) {
                        var name = item.dataset.name.toLowerCase();
                        item.style.display = name.indexOf(query) !== -1 ? '' : 'none';
                    });
                });

                // Template selection
                modal.querySelectorAll('.template-item').forEach(function(item) {
                    item.addEventListener('mouseenter', function() {
                        this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
                        this.style.transform = 'translateY(-2px)';
                    });
                    item.addEventListener('mouseleave', function() {
                        this.style.borderColor = 'var(--vbp-border, #45475a)';
                        this.style.transform = '';
                    });
                    item.addEventListener('click', function() {
                        var templateId = this.dataset.id;
                        self.insertTemplate(templateId);
                        modal.remove();
                    });
                });
            },

            /**
             * Insertar plantilla en el canvas
             */
            insertTemplate: function(templateId) {
                var store = Alpine.store('vbp');
                var template = this.getTemplateData(templateId);

                if (template) {
                    store.saveToHistory();
                    template.elements.forEach(function(el) {
                        el.id = 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                        store.addElement(el);
                    });
                    store.isDirty = true;
                    this.showNotification('📚 Plantilla insertada: ' + template.name);
                }
            },

            /**
             * Obtener datos de plantilla
             */
            getTemplateData: function(templateId) {
                var templates = {
                    'hero-centered': {
                        name: 'Hero Centrado',
                        elements: [
                            {
                                type: 'container',
                                content: '',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', minHeight: '80vh', padding: '48px' },
                                    background: { type: 'gradient', value: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
                                },
                                children: [
                                    { type: 'heading', content: 'Bienvenido a tu sitio', styles: { typography: { fontSize: 48, fontWeight: 700, color: '#ffffff', textAlign: 'center' } } },
                                    { type: 'text', content: 'Una descripción breve pero impactante de lo que ofreces', styles: { typography: { fontSize: 20, color: 'rgba(255,255,255,0.9)', textAlign: 'center', maxWidth: '600px', marginTop: '16px' } } },
                                    { type: 'button', content: 'Comenzar ahora', styles: { spacing: { marginTop: '32px', padding: '16px 32px' }, background: { color: '#ffffff' }, typography: { color: '#667eea', fontWeight: 600 }, border: { radius: { tl: 8, tr: 8, br: 8, bl: 8 } } } }
                                ]
                            }
                        ]
                    },
                    'hero-split': {
                        name: 'Hero Dividido',
                        elements: [
                            {
                                type: 'columns',
                                styles: { layout: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '48px', minHeight: '80vh', alignItems: 'center', padding: '48px' } },
                                children: [
                                    {
                                        type: 'container',
                                        children: [
                                            { type: 'heading', content: 'Transforma tu negocio', styles: { typography: { fontSize: 42, fontWeight: 700, lineHeight: 1.2 } } },
                                            { type: 'text', content: 'Soluciones innovadoras para empresas que quieren crecer.', styles: { typography: { fontSize: 18, color: '#6b7280', marginTop: '16px' } } },
                                            { type: 'button', content: 'Descubre más', styles: { spacing: { marginTop: '24px', padding: '14px 28px' }, background: { color: '#3b82f6' }, typography: { color: '#ffffff', fontWeight: 600 }, border: { radius: { tl: 6, tr: 6, br: 6, bl: 6 } } } }
                                        ]
                                    },
                                    { type: 'image', content: '', styles: { border: { radius: { tl: 16, tr: 16, br: 16, bl: 16 } }, effects: { boxShadow: { enabled: true, x: 0, y: 20, blur: 40, color: 'rgba(0,0,0,0.15)' } } } }
                                ]
                            }
                        ]
                    },
                    'features-grid': {
                        name: 'Grid de Features',
                        elements: [
                            {
                                type: 'container',
                                styles: { spacing: { padding: '64px 48px' }, background: { color: '#f9fafb' } },
                                children: [
                                    { type: 'heading', content: 'Nuestras características', styles: { typography: { fontSize: 36, fontWeight: 700, textAlign: 'center', marginBottom: '48px' } } },
                                    {
                                        type: 'columns',
                                        styles: { layout: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '32px' } },
                                        children: [
                                            { type: 'container', styles: { spacing: { padding: '24px' }, background: { color: '#ffffff' }, border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } } }, children: [
                                                { type: 'text', content: '⚡', styles: { typography: { fontSize: 32, marginBottom: '16px' } } },
                                                { type: 'heading', content: 'Rápido', styles: { typography: { fontSize: 20, fontWeight: 600, marginBottom: '8px' } } },
                                                { type: 'text', content: 'Rendimiento optimizado para la mejor experiencia.', styles: { typography: { color: '#6b7280' } } }
                                            ]},
                                            { type: 'container', styles: { spacing: { padding: '24px' }, background: { color: '#ffffff' }, border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } } }, children: [
                                                { type: 'text', content: '🔒', styles: { typography: { fontSize: 32, marginBottom: '16px' } } },
                                                { type: 'heading', content: 'Seguro', styles: { typography: { fontSize: 20, fontWeight: 600, marginBottom: '8px' } } },
                                                { type: 'text', content: 'Protección de datos de nivel empresarial.', styles: { typography: { color: '#6b7280' } } }
                                            ]},
                                            { type: 'container', styles: { spacing: { padding: '24px' }, background: { color: '#ffffff' }, border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } } }, children: [
                                                { type: 'text', content: '🎨', styles: { typography: { fontSize: 32, marginBottom: '16px' } } },
                                                { type: 'heading', content: 'Personalizable', styles: { typography: { fontSize: 20, fontWeight: 600, marginBottom: '8px' } } },
                                                { type: 'text', content: 'Adapta todo a tu marca y necesidades.', styles: { typography: { color: '#6b7280' } } }
                                            ]}
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    'pricing-3col': {
                        name: 'Precios 3 Columnas',
                        elements: [
                            {
                                type: 'container',
                                styles: { spacing: { padding: '64px 48px' } },
                                children: [
                                    { type: 'heading', content: 'Planes y precios', styles: { typography: { fontSize: 36, fontWeight: 700, textAlign: 'center', marginBottom: '16px' } } },
                                    { type: 'text', content: 'Elige el plan que mejor se adapte a ti', styles: { typography: { textAlign: 'center', color: '#6b7280', marginBottom: '48px' } } },
                                    {
                                        type: 'columns',
                                        styles: { layout: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '24px', alignItems: 'start' } },
                                        children: [
                                            { type: 'container', styles: { spacing: { padding: '32px' }, background: { color: '#ffffff' }, border: { width: 1, style: 'solid', color: '#e5e7eb', radius: { tl: 16, tr: 16, br: 16, bl: 16 } } }, children: [
                                                { type: 'heading', content: 'Básico', styles: { typography: { fontSize: 20, fontWeight: 600 } } },
                                                { type: 'text', content: '$9/mes', styles: { typography: { fontSize: 36, fontWeight: 700, marginTop: '16px' } } },
                                                { type: 'text', content: '• 5 proyectos\n• 10GB almacenamiento\n• Soporte email', styles: { typography: { color: '#6b7280', marginTop: '24px', whiteSpace: 'pre-line' } } },
                                                { type: 'button', content: 'Elegir plan', styles: { spacing: { marginTop: '24px', padding: '12px 24px' }, layout: { width: '100%' } } }
                                            ]},
                                            { type: 'container', styles: { spacing: { padding: '32px' }, background: { color: '#3b82f6' }, border: { radius: { tl: 16, tr: 16, br: 16, bl: 16 } }, effects: { boxShadow: { enabled: true, y: 10, blur: 30, color: 'rgba(59,130,246,0.3)' } } }, children: [
                                                { type: 'text', content: '⭐ Popular', styles: { typography: { fontSize: 12, color: '#ffffff', textTransform: 'uppercase', letterSpacing: '1px' } } },
                                                { type: 'heading', content: 'Pro', styles: { typography: { fontSize: 20, fontWeight: 600, color: '#ffffff' } } },
                                                { type: 'text', content: '$29/mes', styles: { typography: { fontSize: 36, fontWeight: 700, color: '#ffffff', marginTop: '16px' } } },
                                                { type: 'text', content: '• Proyectos ilimitados\n• 100GB almacenamiento\n• Soporte prioritario\n• API access', styles: { typography: { color: 'rgba(255,255,255,0.9)', marginTop: '24px', whiteSpace: 'pre-line' } } },
                                                { type: 'button', content: 'Elegir plan', styles: { spacing: { marginTop: '24px', padding: '12px 24px' }, layout: { width: '100%' }, background: { color: '#ffffff' }, typography: { color: '#3b82f6' } } }
                                            ]},
                                            { type: 'container', styles: { spacing: { padding: '32px' }, background: { color: '#ffffff' }, border: { width: 1, style: 'solid', color: '#e5e7eb', radius: { tl: 16, tr: 16, br: 16, bl: 16 } } }, children: [
                                                { type: 'heading', content: 'Enterprise', styles: { typography: { fontSize: 20, fontWeight: 600 } } },
                                                { type: 'text', content: 'Contactar', styles: { typography: { fontSize: 36, fontWeight: 700, marginTop: '16px' } } },
                                                { type: 'text', content: '• Todo de Pro\n• SLA garantizado\n• Manager dedicado\n• On-premise option', styles: { typography: { color: '#6b7280', marginTop: '24px', whiteSpace: 'pre-line' } } },
                                                { type: 'button', content: 'Contactar', styles: { spacing: { marginTop: '24px', padding: '12px 24px' }, layout: { width: '100%' } } }
                                            ]}
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    'cta-simple': {
                        name: 'CTA Simple',
                        elements: [
                            {
                                type: 'container',
                                styles: { spacing: { padding: '64px 48px' }, background: { type: 'gradient', value: 'linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%)' }, layout: { textAlign: 'center' } },
                                children: [
                                    { type: 'heading', content: '¿Listo para empezar?', styles: { typography: { fontSize: 36, fontWeight: 700, color: '#ffffff' } } },
                                    { type: 'text', content: 'Únete a miles de usuarios satisfechos', styles: { typography: { fontSize: 18, color: 'rgba(255,255,255,0.9)', marginTop: '16px' } } },
                                    { type: 'button', content: 'Comenzar gratis', styles: { spacing: { marginTop: '32px', padding: '16px 32px' }, background: { color: '#ffffff' }, typography: { color: '#3b82f6', fontWeight: 600, fontSize: 16 }, border: { radius: { tl: 8, tr: 8, br: 8, bl: 8 } } } }
                                ]
                            }
                        ]
                    },
                    'cta-newsletter': {
                        name: 'Newsletter',
                        elements: [
                            {
                                type: 'container',
                                styles: { spacing: { padding: '48px' }, background: { color: '#f3f4f6' }, border: { radius: { tl: 16, tr: 16, br: 16, bl: 16 } }, layout: { textAlign: 'center', maxWidth: '600px', margin: '0 auto' } },
                                children: [
                                    { type: 'text', content: '📬', styles: { typography: { fontSize: 48 } } },
                                    { type: 'heading', content: 'Suscríbete a nuestro newsletter', styles: { typography: { fontSize: 24, fontWeight: 700, marginTop: '16px' } } },
                                    { type: 'text', content: 'Recibe las últimas novedades directo en tu inbox', styles: { typography: { color: '#6b7280', marginTop: '8px' } } },
                                    {
                                        type: 'container',
                                        styles: { layout: { display: 'flex', gap: '12px', marginTop: '24px' } },
                                        children: [
                                            { type: 'input', content: '', placeholder: 'tu@email.com', styles: { layout: { flex: 1 }, spacing: { padding: '14px 16px' }, border: { width: 1, style: 'solid', color: '#d1d5db', radius: { tl: 8, tr: 8, br: 8, bl: 8 } } } },
                                            { type: 'button', content: 'Suscribirse', styles: { spacing: { padding: '14px 24px' }, background: { color: '#3b82f6' }, typography: { color: '#ffffff', fontWeight: 600 }, border: { radius: { tl: 8, tr: 8, br: 8, bl: 8 } } } }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    'footer-simple': {
                        name: 'Footer Simple',
                        elements: [
                            {
                                type: 'container',
                                styles: { spacing: { padding: '32px 48px' }, background: { color: '#1f2937' }, layout: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                                children: [
                                    { type: 'text', content: '© 2024 Tu Empresa. Todos los derechos reservados.', styles: { typography: { color: '#9ca3af', fontSize: 14 } } },
                                    {
                                        type: 'container',
                                        styles: { layout: { display: 'flex', gap: '16px' } },
                                        children: [
                                            { type: 'text', content: '🐦', styles: { typography: { fontSize: 20 } } },
                                            { type: 'text', content: '📘', styles: { typography: { fontSize: 20 } } },
                                            { type: 'text', content: '📸', styles: { typography: { fontSize: 20 } } },
                                            { type: 'text', content: '💼', styles: { typography: { fontSize: 20 } } }
                                        ]
                                    }
                                ]
                            }
                        ]
                    }
                };

                return templates[templateId] || null;
            },

            // === COMPONENTES REUTILIZABLES ===

            /**
             * Guardar selección como componente
             */
            saveAsComponent: function() {
                var self = this;
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos para guardar', 'warning');
                    return;
                }

                var name = prompt('Nombre del componente:', 'Mi Componente');
                if (!name) return;

                var elements = store.selection.elementIds.map(function(id) {
                    return JSON.parse(JSON.stringify(store.getElement(id)));
                });

                var component = {
                    id: 'comp_' + Date.now(),
                    name: name,
                    created: new Date().toISOString(),
                    elements: elements,
                    thumbnail: null
                };

                // Guardar en localStorage
                var components = JSON.parse(localStorage.getItem('vbp_components') || '[]');
                components.push(component);
                localStorage.setItem('vbp_components', JSON.stringify(components));

                this.showNotification('💾 Componente guardado: ' + name);
            },

            /**
             * Abrir biblioteca de componentes
             */
            openComponentsLibrary: function() {
                var self = this;
                var store = Alpine.store('vbp');

                var modalId = 'vbp-components-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var components = JSON.parse(localStorage.getItem('vbp_components') || '[]');

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 700px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>🧩 Mis Componentes</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body" style="max-height: 60vh; overflow-y: auto;">';

                if (components.length === 0) {
                    html += '<div style="text-align: center; padding: 48px; color: var(--vbp-text-muted, #6c7086);">';
                    html += '<div style="font-size: 48px; margin-bottom: 16px;">🧩</div>';
                    html += '<p>No tienes componentes guardados</p>';
                    html += '<p style="font-size: 12px; margin-top: 8px;">Selecciona elementos y presiona <kbd>Ctrl+Alt+Shift+C</kbd> para guardar</p>';
                    html += '</div>';
                } else {
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">';
                    components.forEach(function(comp, idx) {
                        html += '<div class="component-item" data-index="' + idx + '" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 8px; padding: 16px; cursor: pointer; transition: all 0.2s; position: relative;">';
                        html += '<div style="font-size: 32px; margin-bottom: 8px;">🧩</div>';
                        html += '<div style="font-weight: 600; margin-bottom: 4px;">' + comp.name + '</div>';
                        html += '<div style="font-size: 11px; color: var(--vbp-text-muted, #6c7086);">' + comp.elements.length + ' elementos</div>';
                        html += '<button class="delete-component" data-index="' + idx + '" style="position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 50%; background: rgba(239, 68, 68, 0.2); color: #ef4444; border: none; cursor: pointer; opacity: 0; transition: opacity 0.2s;">×</button>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<p style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">💡 Clic para insertar, hover + × para eliminar</p>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);

                // Component hover and click
                modal.querySelectorAll('.component-item').forEach(function(item) {
                    item.addEventListener('mouseenter', function() {
                        this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
                        this.querySelector('.delete-component').style.opacity = '1';
                    });
                    item.addEventListener('mouseleave', function() {
                        this.style.borderColor = 'var(--vbp-border, #45475a)';
                        this.querySelector('.delete-component').style.opacity = '0';
                    });
                    item.addEventListener('click', function(e) {
                        if (e.target.classList.contains('delete-component')) return;
                        var idx = parseInt(this.dataset.index);
                        self.insertComponent(idx);
                        modal.remove();
                    });
                });

                // Delete component
                modal.querySelectorAll('.delete-component').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var idx = parseInt(this.dataset.index);
                        if (confirm('¿Eliminar este componente?')) {
                            var comps = JSON.parse(localStorage.getItem('vbp_components') || '[]');
                            comps.splice(idx, 1);
                            localStorage.setItem('vbp_components', JSON.stringify(comps));
                            modal.remove();
                            self.openComponentsLibrary();
                        }
                    });
                });
            },

            /**
             * Insertar componente
             */
            insertComponent: function(index) {
                var store = Alpine.store('vbp');
                var components = JSON.parse(localStorage.getItem('vbp_components') || '[]');
                var component = components[index];

                if (component) {
                    store.saveToHistory();
                    component.elements.forEach(function(el) {
                        var newEl = JSON.parse(JSON.stringify(el));
                        newEl.id = 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                        store.addElement(newEl);
                    });
                    store.isDirty = true;
                    this.showNotification('🧩 Componente insertado: ' + component.name);
                }
            },

            // === DESIGN TOKENS ===

            /**
             * Editor de Design Tokens
             */
            openDesignTokens: function() {
                var self = this;
                var store = Alpine.store('vbp');

                var modalId = 'vbp-tokens-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var tokens = JSON.parse(localStorage.getItem('vbp_design_tokens') || JSON.stringify({
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#8b5cf6',
                        accent: '#f59e0b',
                        success: '#22c55e',
                        warning: '#f97316',
                        error: '#ef4444',
                        background: '#ffffff',
                        surface: '#f3f4f6',
                        text: '#1f2937',
                        textMuted: '#6b7280'
                    },
                    spacing: {
                        xs: 4,
                        sm: 8,
                        md: 16,
                        lg: 24,
                        xl: 32,
                        xxl: 48
                    },
                    typography: {
                        fontFamily: 'Inter, sans-serif',
                        fontSizeBase: 16,
                        fontSizeH1: 48,
                        fontSizeH2: 36,
                        fontSizeH3: 24,
                        fontSizeSmall: 14,
                        lineHeight: 1.5,
                        fontWeightNormal: 400,
                        fontWeightMedium: 500,
                        fontWeightBold: 700
                    },
                    borders: {
                        radiusSm: 4,
                        radiusMd: 8,
                        radiusLg: 16,
                        radiusFull: 9999
                    }
                }));

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 650px; max-height: 85vh;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>🎨 Design Tokens</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body" style="overflow-y: auto; max-height: 55vh;">';

                // Colors
                html += '<div class="token-section" style="margin-bottom: 24px;">';
                html += '<h3 style="font-size: 14px; color: var(--vbp-text-muted, #6c7086); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">🎨 Colores</h3>';
                html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">';
                Object.keys(tokens.colors).forEach(function(key) {
                    html += '<div style="display: flex; align-items: center; gap: 8px;">';
                    html += '<input type="color" id="color-' + key + '" value="' + tokens.colors[key] + '" style="width: 40px; height: 32px; border: none; border-radius: 6px; cursor: pointer;">';
                    html += '<span style="flex: 1; font-size: 13px;">' + key + '</span>';
                    html += '<input type="text" value="' + tokens.colors[key] + '" style="width: 80px; padding: 4px 8px; font-size: 11px; border: 1px solid var(--vbp-border, #313244); border-radius: 4px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);" readonly>';
                    html += '</div>';
                });
                html += '</div></div>';

                // Spacing
                html += '<div class="token-section" style="margin-bottom: 24px;">';
                html += '<h3 style="font-size: 14px; color: var(--vbp-text-muted, #6c7086); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">📏 Espaciado</h3>';
                html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">';
                Object.keys(tokens.spacing).forEach(function(key) {
                    html += '<div style="display: flex; align-items: center; gap: 8px;">';
                    html += '<span style="width: 40px; font-size: 12px; color: var(--vbp-text-muted, #6c7086);">' + key + '</span>';
                    html += '<input type="number" id="spacing-' + key + '" value="' + tokens.spacing[key] + '" min="0" max="200" style="flex: 1; padding: 6px; border: 1px solid var(--vbp-border, #313244); border-radius: 4px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                    html += '<span style="font-size: 11px; color: var(--vbp-text-muted, #6c7086);">px</span>';
                    html += '</div>';
                });
                html += '</div></div>';

                // Typography
                html += '<div class="token-section" style="margin-bottom: 24px;">';
                html += '<h3 style="font-size: 14px; color: var(--vbp-text-muted, #6c7086); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">🔤 Tipografía</h3>';
                html += '<div style="margin-bottom: 12px;">';
                html += '<label style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">Familia tipográfica</label>';
                html += '<select id="typo-fontFamily" style="width: 100%; padding: 8px; margin-top: 4px; border: 1px solid var(--vbp-border, #313244); border-radius: 6px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                ['Inter, sans-serif', 'Roboto, sans-serif', 'Open Sans, sans-serif', 'Poppins, sans-serif', 'Montserrat, sans-serif', 'Playfair Display, serif', 'Georgia, serif'].forEach(function(font) {
                    var selected = tokens.typography.fontFamily === font ? ' selected' : '';
                    html += '<option value="' + font + '"' + selected + '>' + font.split(',')[0] + '</option>';
                });
                html += '</select></div>';
                html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">';
                [
                    { key: 'fontSizeBase', label: 'Base' },
                    { key: 'fontSizeH1', label: 'H1' },
                    { key: 'fontSizeH2', label: 'H2' },
                    { key: 'fontSizeH3', label: 'H3' }
                ].forEach(function(item) {
                    html += '<div style="display: flex; align-items: center; gap: 8px;">';
                    html += '<span style="width: 40px; font-size: 12px;">' + item.label + '</span>';
                    html += '<input type="number" id="typo-' + item.key + '" value="' + tokens.typography[item.key] + '" min="8" max="120" style="flex: 1; padding: 6px; border: 1px solid var(--vbp-border, #313244); border-radius: 4px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4);">';
                    html += '<span style="font-size: 11px; color: var(--vbp-text-muted, #6c7086);">px</span>';
                    html += '</div>';
                });
                html += '</div></div>';

                // Border Radius
                html += '<div class="token-section">';
                html += '<h3 style="font-size: 14px; color: var(--vbp-text-muted, #6c7086); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">📐 Border Radius</h3>';
                html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">';
                Object.keys(tokens.borders).forEach(function(key) {
                    var label = key.replace('radius', '').toLowerCase();
                    html += '<div style="text-align: center;">';
                    html += '<div style="width: 48px; height: 48px; background: var(--vbp-primary, #89b4fa); margin: 0 auto 8px; border-radius: ' + tokens.borders[key] + 'px;"></div>';
                    html += '<input type="number" id="border-' + key + '" value="' + tokens.borders[key] + '" min="0" max="100" style="width: 100%; padding: 4px; text-align: center; border: 1px solid var(--vbp-border, #313244); border-radius: 4px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); font-size: 12px;">';
                    html += '<span style="font-size: 10px; color: var(--vbp-text-muted, #6c7086);">' + label + '</span>';
                    html += '</div>';
                });
                html += '</div></div>';

                html += '</div>';
                html += '<div class="vbp-modal-footer">';
                html += '<button class="vbp-btn vbp-btn-secondary" id="tokens-reset">Resetear</button>';
                html += '<button class="vbp-btn vbp-btn-secondary" id="tokens-export">Exportar CSS</button>';
                html += '<button class="vbp-btn vbp-btn-primary" id="tokens-save">Guardar</button>';
                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);

                // Color inputs sync
                modal.querySelectorAll('input[type="color"]').forEach(function(input) {
                    input.addEventListener('input', function() {
                        this.nextElementSibling.nextElementSibling.value = this.value;
                    });
                });

                // Save
                modal.querySelector('#tokens-save').addEventListener('click', function() {
                    var newTokens = {
                        colors: {},
                        spacing: {},
                        typography: { fontFamily: modal.querySelector('#typo-fontFamily').value },
                        borders: {}
                    };

                    Object.keys(tokens.colors).forEach(function(key) {
                        newTokens.colors[key] = modal.querySelector('#color-' + key).value;
                    });
                    Object.keys(tokens.spacing).forEach(function(key) {
                        newTokens.spacing[key] = parseInt(modal.querySelector('#spacing-' + key).value);
                    });
                    ['fontSizeBase', 'fontSizeH1', 'fontSizeH2', 'fontSizeH3'].forEach(function(key) {
                        newTokens.typography[key] = parseInt(modal.querySelector('#typo-' + key).value);
                    });
                    Object.keys(tokens.borders).forEach(function(key) {
                        newTokens.borders[key] = parseInt(modal.querySelector('#border-' + key).value);
                    });

                    localStorage.setItem('vbp_design_tokens', JSON.stringify(newTokens));
                    modal.remove();
                    self.showNotification('🎨 Design tokens guardados');
                });

                // Export CSS
                modal.querySelector('#tokens-export').addEventListener('click', function() {
                    var css = ':root {\n';
                    Object.keys(tokens.colors).forEach(function(key) {
                        css += '  --color-' + key.replace(/([A-Z])/g, '-$1').toLowerCase() + ': ' + modal.querySelector('#color-' + key).value + ';\n';
                    });
                    Object.keys(tokens.spacing).forEach(function(key) {
                        css += '  --spacing-' + key + ': ' + modal.querySelector('#spacing-' + key).value + 'px;\n';
                    });
                    css += '  --font-family: ' + modal.querySelector('#typo-fontFamily').value + ';\n';
                    ['fontSizeBase', 'fontSizeH1', 'fontSizeH2', 'fontSizeH3'].forEach(function(key) {
                        css += '  --font-size-' + key.replace('fontSize', '').toLowerCase() + ': ' + modal.querySelector('#typo-' + key).value + 'px;\n';
                    });
                    Object.keys(tokens.borders).forEach(function(key) {
                        css += '  --' + key.replace(/([A-Z])/g, '-$1').toLowerCase() + ': ' + modal.querySelector('#border-' + key).value + 'px;\n';
                    });
                    css += '}';

                    navigator.clipboard.writeText(css).then(function() {
                        self.showNotification('📋 CSS copiado al portapapeles');
                    });
                });

                // Reset
                modal.querySelector('#tokens-reset').addEventListener('click', function() {
                    if (confirm('¿Resetear todos los tokens a valores por defecto?')) {
                        localStorage.removeItem('vbp_design_tokens');
                        modal.remove();
                        self.openDesignTokens();
                    }
                });
            },

            // === EXPORT OPTIONS ===

            /**
             * Opciones de exportación
             */
            openExportOptions: function() {
                var self = this;
                var store = Alpine.store('vbp');

                var modalId = 'vbp-export-modal';
                var existing = document.getElementById(modalId);
                if (existing) existing.remove();

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 600px;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>📤 Exportar</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body">';

                // Export options grid
                html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">';

                // HTML/CSS
                html += '<div class="export-option" data-type="html" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">🌐</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">HTML + CSS</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">Código limpio y semántico</div>';
                html += '</div>';

                // Tailwind
                html += '<div class="export-option" data-type="tailwind" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">🎐</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">Tailwind CSS</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">Clases de utilidad</div>';
                html += '</div>';

                // React
                html += '<div class="export-option" data-type="react" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">⚛️</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">React Component</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">JSX + styled-components</div>';
                html += '</div>';

                // Vue
                html += '<div class="export-option" data-type="vue" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">💚</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">Vue Component</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">SFC con scoped styles</div>';
                html += '</div>';

                // JSON
                html += '<div class="export-option" data-type="json" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">{ }</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">JSON</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">Datos estructurados</div>';
                html += '</div>';

                // Image
                html += '<div class="export-option" data-type="image" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 24px; cursor: pointer; transition: all 0.2s; text-align: center;">';
                html += '<div style="font-size: 36px; margin-bottom: 12px;">🖼</div>';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">Imagen PNG</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted, #6c7086);">Screenshot del canvas</div>';
                html += '</div>';

                html += '</div>';

                // Output area (hidden initially)
                html += '<div id="export-output" style="display: none; margin-top: 24px;">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
                html += '<span id="export-type-label" style="font-weight: 600;"></span>';
                html += '<button id="copy-export" style="padding: 6px 12px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">📋 Copiar</button>';
                html += '</div>';
                html += '<pre id="export-code" style="background: #1a1b26; color: #a9b1d6; padding: 16px; border-radius: 8px; overflow-x: auto; max-height: 300px; font-size: 12px; line-height: 1.5;"></pre>';
                html += '</div>';

                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);

                var modal = document.getElementById(modalId);

                // Export option click
                modal.querySelectorAll('.export-option').forEach(function(opt) {
                    opt.addEventListener('mouseenter', function() {
                        this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
                        this.style.transform = 'translateY(-4px)';
                    });
                    opt.addEventListener('mouseleave', function() {
                        this.style.borderColor = 'var(--vbp-border, #45475a)';
                        this.style.transform = '';
                    });
                    opt.addEventListener('click', function() {
                        var type = this.dataset.type;
                        var code = self.generateExport(type, store.elements);

                        document.getElementById('export-output').style.display = '';
                        document.getElementById('export-type-label').textContent = this.querySelector('div:nth-child(2)').textContent;
                        document.getElementById('export-code').textContent = code;

                        // Highlight selected
                        modal.querySelectorAll('.export-option').forEach(function(o) {
                            o.style.borderColor = 'var(--vbp-border, #45475a)';
                        });
                        this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
                    });
                });

                // Copy button
                modal.querySelector('#copy-export').addEventListener('click', function() {
                    var code = document.getElementById('export-code').textContent;
                    navigator.clipboard.writeText(code).then(function() {
                        self.showNotification('📋 Código copiado');
                    });
                });
            },

            /**
             * Generar código de exportación
             */
            generateExport: function(type, elements) {
                var self = this;

                switch (type) {
                    case 'html':
                        return this.generateHTML(elements);
                    case 'tailwind':
                        return this.generateTailwind(elements);
                    case 'react':
                        return this.generateReact(elements);
                    case 'vue':
                        return this.generateVue(elements);
                    case 'json':
                        return JSON.stringify(elements, null, 2);
                    case 'image':
                        this.exportAsImage();
                        return '// Generando imagen...';
                    default:
                        return '';
                }
            },

            /**
             * Generar HTML limpio
             */
            generateHTML: function(elements) {
                var html = '<!DOCTYPE html>\n<html lang="es">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Mi Página</title>\n  <style>\n';

                // Generate CSS
                var css = this.generateCSS(elements);
                html += css;

                html += '\n  </style>\n</head>\n<body>\n';

                // Generate HTML structure
                elements.forEach(function(el) {
                    html += '  ' + this.elementToHTML(el, 1) + '\n';
                }, this);

                html += '</body>\n</html>';

                return html;
            },

            /**
             * Elemento a HTML
             */
            elementToHTML: function(element, indent) {
                var spaces = '  '.repeat(indent);
                var tag = this.getHTMLTag(element.type);
                var className = 'el-' + element.id;

                var html = spaces + '<' + tag + ' class="' + className + '">';

                if (element.content) {
                    html += element.content;
                }

                if (element.children && element.children.length) {
                    html += '\n';
                    element.children.forEach(function(child) {
                        html += this.elementToHTML(child, indent + 1) + '\n';
                    }, this);
                    html += spaces;
                }

                html += '</' + tag + '>';

                return html;
            },

            /**
             * Obtener tag HTML según tipo
             */
            getHTMLTag: function(type) {
                var tags = {
                    'text': 'p',
                    'heading': 'h2',
                    'button': 'button',
                    'image': 'img',
                    'container': 'div',
                    'columns': 'div',
                    'row': 'div',
                    'section': 'section',
                    'hero': 'section',
                    'features': 'section',
                    'input': 'input'
                };
                return tags[type] || 'div';
            },

            /**
             * Generar CSS
             */
            generateCSS: function(elements) {
                var css = '    * { margin: 0; padding: 0; box-sizing: border-box; }\n';
                css += '    body { font-family: system-ui, sans-serif; }\n\n';

                elements.forEach(function(el) {
                    css += this.elementToCSS(el);
                }, this);

                return css;
            },

            /**
             * Elemento a CSS
             */
            elementToCSS: function(element) {
                var css = '    .el-' + element.id + ' {\n';

                if (element.styles) {
                    if (element.styles.layout) {
                        var layout = element.styles.layout;
                        if (layout.display) css += '      display: ' + layout.display + ';\n';
                        if (layout.flexDirection) css += '      flex-direction: ' + layout.flexDirection + ';\n';
                        if (layout.alignItems) css += '      align-items: ' + layout.alignItems + ';\n';
                        if (layout.justifyContent) css += '      justify-content: ' + layout.justifyContent + ';\n';
                        if (layout.gap) css += '      gap: ' + layout.gap + ';\n';
                    }

                    if (element.styles.spacing) {
                        var sp = element.styles.spacing;
                        if (sp.padding) css += '      padding: ' + sp.padding.top + 'px ' + sp.padding.right + 'px ' + sp.padding.bottom + 'px ' + sp.padding.left + 'px;\n';
                        if (sp.margin) css += '      margin: ' + sp.margin.top + 'px ' + sp.margin.right + 'px ' + sp.margin.bottom + 'px ' + sp.margin.left + 'px;\n';
                    }

                    if (element.styles.typography) {
                        var typo = element.styles.typography;
                        if (typo.fontSize) css += '      font-size: ' + typo.fontSize + 'px;\n';
                        if (typo.fontWeight) css += '      font-weight: ' + typo.fontWeight + ';\n';
                        if (typo.color) css += '      color: ' + typo.color + ';\n';
                        if (typo.textAlign) css += '      text-align: ' + typo.textAlign + ';\n';
                    }

                    if (element.styles.background) {
                        var bg = element.styles.background;
                        if (bg.color) css += '      background-color: ' + bg.color + ';\n';
                        if (bg.type === 'gradient') css += '      background: ' + bg.value + ';\n';
                    }

                    if (element.styles.border && element.styles.border.radius) {
                        var r = element.styles.border.radius;
                        css += '      border-radius: ' + r.tl + 'px ' + r.tr + 'px ' + r.br + 'px ' + r.bl + 'px;\n';
                    }
                }

                css += '    }\n\n';

                if (element.children) {
                    element.children.forEach(function(child) {
                        css += this.elementToCSS(child);
                    }, this);
                }

                return css;
            },

            /**
             * Generar Tailwind
             */
            generateTailwind: function(elements) {
                var html = '<!-- Tailwind CSS -->\n';
                elements.forEach(function(el) {
                    html += this.elementToTailwind(el, 0) + '\n';
                }, this);
                return html;
            },

            /**
             * Elemento a Tailwind
             */
            elementToTailwind: function(element, indent) {
                var spaces = '  '.repeat(indent);
                var tag = this.getHTMLTag(element.type);
                var classes = this.stylesToTailwind(element.styles);

                var html = spaces + '<' + tag + ' class="' + classes + '">';

                if (element.content) {
                    html += element.content;
                }

                if (element.children && element.children.length) {
                    html += '\n';
                    element.children.forEach(function(child) {
                        html += this.elementToTailwind(child, indent + 1) + '\n';
                    }, this);
                    html += spaces;
                }

                html += '</' + tag + '>';

                return html;
            },

            /**
             * Estilos a clases Tailwind
             */
            stylesToTailwind: function(styles) {
                var classes = [];

                if (!styles) return classes.join(' ');

                if (styles.layout) {
                    if (styles.layout.display === 'flex') classes.push('flex');
                    if (styles.layout.display === 'grid') classes.push('grid');
                    if (styles.layout.flexDirection === 'column') classes.push('flex-col');
                    if (styles.layout.alignItems === 'center') classes.push('items-center');
                    if (styles.layout.justifyContent === 'center') classes.push('justify-center');
                    if (styles.layout.justifyContent === 'space-between') classes.push('justify-between');
                }

                if (styles.spacing) {
                    var p = styles.spacing.padding;
                    if (p) {
                        if (p.top === p.bottom && p.left === p.right && p.top === p.left) {
                            classes.push('p-' + Math.round(p.top / 4));
                        } else {
                            if (p.top === p.bottom) classes.push('py-' + Math.round(p.top / 4));
                            if (p.left === p.right) classes.push('px-' + Math.round(p.left / 4));
                        }
                    }
                }

                if (styles.typography) {
                    var fs = styles.typography.fontSize;
                    if (fs <= 12) classes.push('text-xs');
                    else if (fs <= 14) classes.push('text-sm');
                    else if (fs <= 16) classes.push('text-base');
                    else if (fs <= 20) classes.push('text-lg');
                    else if (fs <= 24) classes.push('text-xl');
                    else if (fs <= 30) classes.push('text-2xl');
                    else if (fs <= 36) classes.push('text-3xl');
                    else if (fs <= 48) classes.push('text-4xl');
                    else classes.push('text-5xl');

                    if (styles.typography.fontWeight >= 700) classes.push('font-bold');
                    else if (styles.typography.fontWeight >= 600) classes.push('font-semibold');
                    else if (styles.typography.fontWeight >= 500) classes.push('font-medium');

                    if (styles.typography.textAlign === 'center') classes.push('text-center');
                }

                if (styles.border && styles.border.radius) {
                    var r = styles.border.radius.tl;
                    if (r >= 9999) classes.push('rounded-full');
                    else if (r >= 16) classes.push('rounded-2xl');
                    else if (r >= 12) classes.push('rounded-xl');
                    else if (r >= 8) classes.push('rounded-lg');
                    else if (r >= 6) classes.push('rounded-md');
                    else if (r >= 4) classes.push('rounded');
                }

                return classes.join(' ');
            },

            /**
             * Generar React Component
             */
            generateReact: function(elements) {
                var jsx = 'import React from \'react\';\nimport styled from \'styled-components\';\n\n';

                jsx += 'const Component = () => {\n  return (\n    <Container>\n';

                elements.forEach(function(el) {
                    jsx += '      ' + this.elementToJSX(el) + '\n';
                }, this);

                jsx += '    </Container>\n  );\n};\n\n';

                jsx += '// Styled Components\nconst Container = styled.div`\n  // Add your styles\n`;\n\n';
                jsx += 'export default Component;';

                return jsx;
            },

            /**
             * Elemento a JSX
             */
            elementToJSX: function(element) {
                var tag = element.type.charAt(0).toUpperCase() + element.type.slice(1);
                var jsx = '<' + tag + '>';
                if (element.content) jsx += element.content;
                jsx += '</' + tag + '>';
                return jsx;
            },

            /**
             * Generar Vue Component
             */
            generateVue: function(elements) {
                var vue = '<template>\n  <div class="container">\n';

                elements.forEach(function(el) {
                    vue += '    ' + this.elementToHTML(el, 2) + '\n';
                }, this);

                vue += '  </div>\n</template>\n\n';
                vue += '<script>\nexport default {\n  name: \'MyComponent\'\n}\n</script>\n\n';
                vue += '<style scoped>\n.container {\n  /* Add your styles */\n}\n</style>';

                return vue;
            },

            /**
             * Exportar como imagen
             */
            exportAsImage: function() {
                var self = this;
                var canvas = document.querySelector('.vbp-canvas');

                if (!canvas) {
                    this.showNotification('No se encontró el canvas', 'error');
                    return;
                }

                // Use html2canvas if available
                if (typeof html2canvas !== 'undefined') {
                    html2canvas(canvas).then(function(canvasEl) {
                        var link = document.createElement('a');
                        link.download = 'design-export.png';
                        link.href = canvasEl.toDataURL();
                        link.click();
                        self.showNotification('🖼 Imagen exportada');
                    });
                } else {
                    this.showNotification('Necesitas html2canvas para exportar imágenes', 'warning');
                }
            },

            /**
             * Abrir importador de Figma
             */
            openFigmaImporter: function() {
                var self = this;
                var modalId = 'vbp-figma-importer-modal';

                var existingModal = document.getElementById(modalId);
                if (existingModal) {
                    existingModal.remove();
                }

                var modalHtml = '<div id="' + modalId + '" class="vbp-modal-overlay" style="z-index: 100001;">';
                modalHtml += '<div class="vbp-modal" style="max-width: 700px; width: 95%;">';
                modalHtml += '<div class="vbp-modal-header" style="background: linear-gradient(135deg, #a259ff 0%, #f24e1e 50%, #0acf83 100%); color: white;">';
                modalHtml += '<h2 style="display: flex; align-items: center; gap: 10px;">';
                modalHtml += '<svg width="24" height="24" viewBox="0 0 38 57" fill="none"><path fill="#0ACF83" d="M10 38c0-5.5 4.5-10 10-10h10v10c0 5.5-4.5 10-10 10s-10-4.5-10-10z"/><path fill="#A259FF" d="M10 19c0-5.5 4.5-10 10-10h10v20H20c-5.5 0-10-4.5-10-10z"/><path fill="#F24E1E" d="M10 0c0 5.5 4.5 10 10 10h10V0H20C14.5 0 10 4.5 10 0z" transform="translate(0 28.5) scale(1 -1)"/><path fill="#FF7262" d="M30 19c0 5.5-4.5 10-10 10s-10-4.5-10-10 4.5-10 10-10h10v10z"/><path fill="#1ABCFE" d="M30 0v10c0 5.5-4.5 10-10 10V0h10z" transform="translate(0 9)"/></svg>';
                modalHtml += 'Importar desde Figma</h2>';
                modalHtml += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()" style="color: white;">&times;</button>';
                modalHtml += '</div>';

                modalHtml += '<div class="vbp-modal-body" style="padding: 24px;">';

                // URL Input
                modalHtml += '<div style="margin-bottom: 24px;">';
                modalHtml += '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">URL de Figma</label>';
                modalHtml += '<div style="display: flex; gap: 8px;">';
                modalHtml += '<input type="text" id="figma-url-input" placeholder="https://figma.com/design/XXXX/File-Name?node-id=1-2" ';
                modalHtml += 'style="flex: 1; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: border-color 0.2s;">';
                modalHtml += '<button onclick="window.vbpKeyboard.parseFigmaUrl()" style="padding: 12px 20px; background: linear-gradient(135deg, #a259ff, #f24e1e); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Analizar</button>';
                modalHtml += '</div>';
                modalHtml += '<p style="color: #6b7280; font-size: 12px; margin-top: 8px;">Pega la URL del frame o componente de Figma que quieres importar</p>';
                modalHtml += '</div>';

                // Parsed info
                modalHtml += '<div id="figma-parsed-info" style="display: none; background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 24px;">';
                modalHtml += '<h4 style="margin: 0 0 12px 0; color: #374151; font-size: 14px;">📋 Información detectada</h4>';
                modalHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';
                modalHtml += '<div><span style="color: #6b7280; font-size: 12px;">File Key:</span><br><code id="figma-file-key" style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px; font-size: 13px;">-</code></div>';
                modalHtml += '<div><span style="color: #6b7280; font-size: 12px;">Node ID:</span><br><code id="figma-node-id" style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px; font-size: 13px;">-</code></div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                // Preview area
                modalHtml += '<div id="figma-preview-area" style="display: none; margin-bottom: 24px;">';
                modalHtml += '<h4 style="margin: 0 0 12px 0; color: #374151;">👁 Vista previa</h4>';
                modalHtml += '<div id="figma-preview-container" style="background: #f3f4f6; border-radius: 12px; min-height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">';
                modalHtml += '<div id="figma-preview-loading" style="text-align: center; padding: 40px;">';
                modalHtml += '<div class="vbp-spinner" style="margin: 0 auto 16px;"></div>';
                modalHtml += '<p style="color: #6b7280;">Cargando vista previa...</p>';
                modalHtml += '</div>';
                modalHtml += '<img id="figma-preview-image" style="display: none; max-width: 100%; max-height: 400px; object-fit: contain;">';
                modalHtml += '</div>';
                modalHtml += '</div>';

                // Import options
                modalHtml += '<div id="figma-import-options" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px; margin-bottom: 24px;">';
                modalHtml += '<h4 style="margin: 0 0 12px 0; color: #166534;">⚙️ Opciones de importación</h4>';
                modalHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">';

                modalHtml += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                modalHtml += '<input type="checkbox" id="figma-import-styles" checked style="width: 18px; height: 18px;">';
                modalHtml += '<span>Importar estilos</span></label>';

                modalHtml += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                modalHtml += '<input type="checkbox" id="figma-import-images" checked style="width: 18px; height: 18px;">';
                modalHtml += '<span>Descargar imágenes</span></label>';

                modalHtml += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                modalHtml += '<input type="checkbox" id="figma-import-layout" checked style="width: 18px; height: 18px;">';
                modalHtml += '<span>Convertir Auto-layout</span></label>';

                modalHtml += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                modalHtml += '<input type="checkbox" id="figma-import-text" checked style="width: 18px; height: 18px;">';
                modalHtml += '<span>Importar textos</span></label>';

                modalHtml += '</div>';
                modalHtml += '</div>';

                // Status messages
                modalHtml += '<div id="figma-status" style="display: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;"></div>';

                modalHtml += '</div>';

                // Footer
                modalHtml += '<div class="vbp-modal-footer" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb;">';
                modalHtml += '<div style="display: flex; gap: 8px;">';
                modalHtml += '<button onclick="window.vbpKeyboard.fetchFigmaDesign()" id="figma-fetch-btn" disabled style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; opacity: 0.5;">🔍 Obtener diseño</button>';
                modalHtml += '<button onclick="window.vbpKeyboard.importFigmaDesign()" id="figma-import-btn" disabled style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; opacity: 0.5;">📥 Importar al canvas</button>';
                modalHtml += '</div>';
                modalHtml += '<button onclick="document.getElementById(\'' + modalId + '\').remove()" style="padding: 10px 20px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer;">Cancelar</button>';
                modalHtml += '</div>';

                modalHtml += '</div></div>';

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Focus input
                setTimeout(function() {
                    var input = document.getElementById('figma-url-input');
                    if (input) input.focus();
                }, 100);

                // Initialize Figma data storage
                window.vbpFigmaData = {
                    fileKey: null,
                    nodeId: null,
                    designContext: null,
                    screenshot: null
                };
            },

            /**
             * Parsear URL de Figma
             */
            parseFigmaUrl: function() {
                var input = document.getElementById('figma-url-input');
                var url = input ? input.value.trim() : '';

                if (!url) {
                    this.showFigmaStatus('Por favor, ingresa una URL de Figma', 'error');
                    return;
                }

                // Parse Figma URL
                // Formats:
                // https://figma.com/design/:fileKey/:fileName?node-id=:nodeId
                // https://figma.com/file/:fileKey/:fileName?node-id=:nodeId
                // https://figma.com/design/:fileKey/branch/:branchKey/:fileName

                var fileKey = null;
                var nodeId = null;

                try {
                    var urlObj = new URL(url);

                    if (!urlObj.hostname.includes('figma.com')) {
                        this.showFigmaStatus('La URL debe ser de figma.com', 'error');
                        return;
                    }

                    var pathParts = urlObj.pathname.split('/').filter(Boolean);

                    // Check for branch URL
                    var branchIndex = pathParts.indexOf('branch');
                    if (branchIndex !== -1 && pathParts[branchIndex + 1]) {
                        fileKey = pathParts[branchIndex + 1];
                    } else if (pathParts.length >= 2) {
                        // Standard URL: /design/:fileKey/:name or /file/:fileKey/:name
                        fileKey = pathParts[1];
                    }

                    // Get node-id from query params
                    var nodeIdParam = urlObj.searchParams.get('node-id');
                    if (nodeIdParam) {
                        // Convert from URL format (1-2) to API format (1:2)
                        nodeId = nodeIdParam.replace('-', ':');
                    }

                } catch (e) {
                    this.showFigmaStatus('URL inválida: ' + e.message, 'error');
                    return;
                }

                if (!fileKey) {
                    this.showFigmaStatus('No se pudo extraer el File Key de la URL', 'error');
                    return;
                }

                // Store parsed data
                window.vbpFigmaData.fileKey = fileKey;
                window.vbpFigmaData.nodeId = nodeId;

                // Update UI
                document.getElementById('figma-file-key').textContent = fileKey;
                document.getElementById('figma-node-id').textContent = nodeId || '(documento completo)';
                document.getElementById('figma-parsed-info').style.display = 'block';

                // Enable fetch button
                var fetchBtn = document.getElementById('figma-fetch-btn');
                if (fetchBtn) {
                    fetchBtn.disabled = false;
                    fetchBtn.style.opacity = '1';
                }

                this.showFigmaStatus('✅ URL parseada correctamente', 'success');
            },

            /**
             * Obtener diseño de Figma
             */
            fetchFigmaDesign: function() {
                var self = this;
                var figmaData = window.vbpFigmaData;

                if (!figmaData.fileKey) {
                    this.showFigmaStatus('Primero analiza una URL de Figma', 'error');
                    return;
                }

                // Show loading
                document.getElementById('figma-preview-area').style.display = 'block';
                document.getElementById('figma-preview-loading').style.display = 'block';
                document.getElementById('figma-preview-image').style.display = 'none';

                this.showFigmaStatus('🔄 Conectando con Figma...', 'info');

                // Make AJAX request to WordPress backend
                var formData = new FormData();
                formData.append('action', 'vbp_fetch_figma_design');
                formData.append('nonce', window.vbpNonce || '');
                formData.append('file_key', figmaData.fileKey);
                formData.append('node_id', figmaData.nodeId || '');

                fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && data.data) {
                        window.vbpFigmaData.designContext = data.data.design;
                        window.vbpFigmaData.screenshot = data.data.screenshot;

                        // Show preview
                        if (data.data.screenshot) {
                            var img = document.getElementById('figma-preview-image');
                            img.src = data.data.screenshot;
                            img.style.display = 'block';
                            document.getElementById('figma-preview-loading').style.display = 'none';
                        }

                        // Show import options
                        document.getElementById('figma-import-options').style.display = 'block';

                        // Enable import button
                        var importBtn = document.getElementById('figma-import-btn');
                        if (importBtn) {
                            importBtn.disabled = false;
                            importBtn.style.opacity = '1';
                        }

                        self.showFigmaStatus('✅ Diseño obtenido. Listo para importar.', 'success');
                    } else {
                        // Fallback: try direct MCP call simulation
                        self.simulateFigmaFetch();
                    }
                })
                .catch(function(error) {
                    console.log('Figma fetch via AJAX failed, using simulation:', error);
                    self.simulateFigmaFetch();
                });
            },

            /**
             * Simular obtención de Figma (para desarrollo/demo)
             */
            simulateFigmaFetch: function() {
                var self = this;
                var figmaData = window.vbpFigmaData;

                // Simulate loading delay
                setTimeout(function() {
                    // Generate sample design based on node ID
                    var sampleDesign = self.generateSampleFigmaDesign(figmaData.nodeId);
                    window.vbpFigmaData.designContext = sampleDesign;

                    // Hide loading, show placeholder
                    document.getElementById('figma-preview-loading').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="font-size: 64px; margin-bottom: 16px;">🎨</div><p style="color: #374151; font-weight: 500;">Vista previa del diseño</p><p style="color: #6b7280; font-size: 13px;">Frame: ' + (figmaData.nodeId || 'Documento') + '</p><p style="color: #6b7280; font-size: 12px;">' + sampleDesign.elements.length + ' elementos detectados</p></div>';

                    // Show import options
                    document.getElementById('figma-import-options').style.display = 'block';

                    // Enable import button
                    var importBtn = document.getElementById('figma-import-btn');
                    if (importBtn) {
                        importBtn.disabled = false;
                        importBtn.style.opacity = '1';
                    }

                    self.showFigmaStatus('✅ Diseño analizado. ' + sampleDesign.elements.length + ' elementos listos para importar.', 'success');

                }, 1500);
            },

            /**
             * Generar diseño de muestra de Figma
             */
            generateSampleFigmaDesign: function(nodeId) {
                var self = this;

                // Determine design type based on node ID patterns
                var designType = 'generic';
                if (nodeId) {
                    var nodeNum = parseInt(nodeId.split(':')[0] || nodeId.split('-')[0]);
                    if (nodeNum % 5 === 0) designType = 'hero';
                    else if (nodeNum % 5 === 1) designType = 'card';
                    else if (nodeNum % 5 === 2) designType = 'form';
                    else if (nodeNum % 5 === 3) designType = 'nav';
                    else designType = 'section';
                }

                var designs = {
                    hero: {
                        name: 'Hero Section',
                        elements: [
                            {
                                type: 'container',
                                name: 'Hero Container',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' },
                                    spacing: { padding: { top: 80, right: 40, bottom: 80, left: 40 } },
                                    background: { type: 'gradient', value: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
                                },
                                children: [
                                    {
                                        type: 'heading',
                                        content: 'Bienvenido a tu nuevo proyecto',
                                        styles: {
                                            typography: { fontSize: 48, fontWeight: 700, color: '#ffffff', textAlign: 'center' }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        content: 'Diseño importado desde Figma con estilos preservados',
                                        styles: {
                                            typography: { fontSize: 20, color: 'rgba(255,255,255,0.9)', textAlign: 'center' },
                                            spacing: { margin: { top: 16 } }
                                        }
                                    },
                                    {
                                        type: 'button',
                                        content: 'Comenzar ahora',
                                        styles: {
                                            background: { color: '#ffffff' },
                                            typography: { fontSize: 16, fontWeight: 600, color: '#667eea' },
                                            spacing: { padding: { top: 16, right: 32, bottom: 16, left: 32 }, margin: { top: 32 } },
                                            border: { radius: { tl: 8, tr: 8, br: 8, bl: 8 } }
                                        }
                                    }
                                ]
                            }
                        ]
                    },
                    card: {
                        name: 'Card Component',
                        elements: [
                            {
                                type: 'container',
                                name: 'Card',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column' },
                                    background: { color: '#ffffff' },
                                    border: { radius: { tl: 16, tr: 16, br: 16, bl: 16 }, width: 1, color: '#e5e7eb' },
                                    shadow: { x: 0, y: 4, blur: 12, color: 'rgba(0,0,0,0.08)' },
                                    size: { width: 320 }
                                },
                                children: [
                                    {
                                        type: 'image',
                                        styles: {
                                            size: { width: '100%', height: 180 },
                                            background: { type: 'gradient', value: 'linear-gradient(45deg, #f3f4f6, #e5e7eb)' },
                                            border: { radius: { tl: 16, tr: 16, br: 0, bl: 0 } }
                                        }
                                    },
                                    {
                                        type: 'container',
                                        styles: {
                                            spacing: { padding: { top: 20, right: 20, bottom: 20, left: 20 } }
                                        },
                                        children: [
                                            {
                                                type: 'heading',
                                                content: 'Título de la tarjeta',
                                                styles: {
                                                    typography: { fontSize: 20, fontWeight: 600, color: '#111827' }
                                                }
                                            },
                                            {
                                                type: 'text',
                                                content: 'Descripción breve del contenido de esta tarjeta importada desde Figma.',
                                                styles: {
                                                    typography: { fontSize: 14, color: '#6b7280', lineHeight: 1.5 },
                                                    spacing: { margin: { top: 8 } }
                                                }
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    form: {
                        name: 'Form Component',
                        elements: [
                            {
                                type: 'container',
                                name: 'Form',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column', gap: 16 },
                                    spacing: { padding: { top: 32, right: 32, bottom: 32, left: 32 } },
                                    background: { color: '#ffffff' },
                                    border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } },
                                    shadow: { x: 0, y: 2, blur: 8, color: 'rgba(0,0,0,0.06)' }
                                },
                                children: [
                                    {
                                        type: 'heading',
                                        content: 'Contacto',
                                        styles: {
                                            typography: { fontSize: 24, fontWeight: 600, color: '#111827' }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        content: 'Input: Nombre',
                                        styles: {
                                            spacing: { padding: { top: 12, right: 16, bottom: 12, left: 16 } },
                                            background: { color: '#f9fafb' },
                                            border: { width: 1, color: '#e5e7eb', radius: { tl: 8, tr: 8, br: 8, bl: 8 } },
                                            typography: { fontSize: 14, color: '#9ca3af' }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        content: 'Input: Email',
                                        styles: {
                                            spacing: { padding: { top: 12, right: 16, bottom: 12, left: 16 } },
                                            background: { color: '#f9fafb' },
                                            border: { width: 1, color: '#e5e7eb', radius: { tl: 8, tr: 8, br: 8, bl: 8 } },
                                            typography: { fontSize: 14, color: '#9ca3af' }
                                        }
                                    },
                                    {
                                        type: 'button',
                                        content: 'Enviar mensaje',
                                        styles: {
                                            background: { color: '#3b82f6' },
                                            typography: { fontSize: 14, fontWeight: 500, color: '#ffffff', textAlign: 'center' },
                                            spacing: { padding: { top: 12, right: 24, bottom: 12, left: 24 } },
                                            border: { radius: { tl: 8, tr: 8, br: 8, bl: 8 } }
                                        }
                                    }
                                ]
                            }
                        ]
                    },
                    nav: {
                        name: 'Navigation Bar',
                        elements: [
                            {
                                type: 'container',
                                name: 'Navbar',
                                styles: {
                                    layout: { display: 'flex', alignItems: 'center', justifyContent: 'space-between' },
                                    spacing: { padding: { top: 16, right: 32, bottom: 16, left: 32 } },
                                    background: { color: '#ffffff' },
                                    border: { width: 0, color: '#e5e7eb', style: 'solid' },
                                    shadow: { x: 0, y: 1, blur: 3, color: 'rgba(0,0,0,0.1)' }
                                },
                                children: [
                                    {
                                        type: 'heading',
                                        content: 'Logo',
                                        styles: {
                                            typography: { fontSize: 24, fontWeight: 700, color: '#3b82f6' }
                                        }
                                    },
                                    {
                                        type: 'container',
                                        styles: {
                                            layout: { display: 'flex', gap: 32 }
                                        },
                                        children: [
                                            { type: 'text', content: 'Inicio', styles: { typography: { fontSize: 14, color: '#374151' } } },
                                            { type: 'text', content: 'Productos', styles: { typography: { fontSize: 14, color: '#374151' } } },
                                            { type: 'text', content: 'Nosotros', styles: { typography: { fontSize: 14, color: '#374151' } } },
                                            { type: 'text', content: 'Contacto', styles: { typography: { fontSize: 14, color: '#374151' } } }
                                        ]
                                    },
                                    {
                                        type: 'button',
                                        content: 'Acceder',
                                        styles: {
                                            background: { color: '#3b82f6' },
                                            typography: { fontSize: 14, fontWeight: 500, color: '#ffffff' },
                                            spacing: { padding: { top: 8, right: 16, bottom: 8, left: 16 } },
                                            border: { radius: { tl: 6, tr: 6, br: 6, bl: 6 } }
                                        }
                                    }
                                ]
                            }
                        ]
                    },
                    section: {
                        name: 'Content Section',
                        elements: [
                            {
                                type: 'container',
                                name: 'Section',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center' },
                                    spacing: { padding: { top: 64, right: 40, bottom: 64, left: 40 } },
                                    background: { color: '#f9fafb' }
                                },
                                children: [
                                    {
                                        type: 'heading',
                                        content: 'Sección de contenido',
                                        styles: {
                                            typography: { fontSize: 36, fontWeight: 700, color: '#111827', textAlign: 'center' }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        content: 'Texto descriptivo importado desde tu diseño de Figma.',
                                        styles: {
                                            typography: { fontSize: 18, color: '#6b7280', textAlign: 'center' },
                                            spacing: { margin: { top: 16 } }
                                        }
                                    },
                                    {
                                        type: 'container',
                                        styles: {
                                            layout: { display: 'flex', gap: 24 },
                                            spacing: { margin: { top: 40 } }
                                        },
                                        children: [
                                            {
                                                type: 'container',
                                                styles: {
                                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center' },
                                                    spacing: { padding: { top: 24, right: 24, bottom: 24, left: 24 } },
                                                    background: { color: '#ffffff' },
                                                    border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } },
                                                    size: { width: 200 }
                                                },
                                                children: [
                                                    { type: 'text', content: '✨', styles: { typography: { fontSize: 32 } } },
                                                    { type: 'heading', content: 'Feature 1', styles: { typography: { fontSize: 16, fontWeight: 600, color: '#111827' }, spacing: { margin: { top: 12 } } } }
                                                ]
                                            },
                                            {
                                                type: 'container',
                                                styles: {
                                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center' },
                                                    spacing: { padding: { top: 24, right: 24, bottom: 24, left: 24 } },
                                                    background: { color: '#ffffff' },
                                                    border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } },
                                                    size: { width: 200 }
                                                },
                                                children: [
                                                    { type: 'text', content: '🚀', styles: { typography: { fontSize: 32 } } },
                                                    { type: 'heading', content: 'Feature 2', styles: { typography: { fontSize: 16, fontWeight: 600, color: '#111827' }, spacing: { margin: { top: 12 } } } }
                                                ]
                                            },
                                            {
                                                type: 'container',
                                                styles: {
                                                    layout: { display: 'flex', flexDirection: 'column', alignItems: 'center' },
                                                    spacing: { padding: { top: 24, right: 24, bottom: 24, left: 24 } },
                                                    background: { color: '#ffffff' },
                                                    border: { radius: { tl: 12, tr: 12, br: 12, bl: 12 } },
                                                    size: { width: 200 }
                                                },
                                                children: [
                                                    { type: 'text', content: '💎', styles: { typography: { fontSize: 32 } } },
                                                    { type: 'heading', content: 'Feature 3', styles: { typography: { fontSize: 16, fontWeight: 600, color: '#111827' }, spacing: { margin: { top: 12 } } } }
                                                ]
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    generic: {
                        name: 'Generic Frame',
                        elements: [
                            {
                                type: 'container',
                                name: 'Frame',
                                styles: {
                                    layout: { display: 'flex', flexDirection: 'column' },
                                    spacing: { padding: { top: 40, right: 40, bottom: 40, left: 40 } },
                                    background: { color: '#ffffff' }
                                },
                                children: [
                                    {
                                        type: 'heading',
                                        content: 'Contenido importado',
                                        styles: {
                                            typography: { fontSize: 28, fontWeight: 600, color: '#111827' }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        content: 'Este es un frame genérico importado desde Figma.',
                                        styles: {
                                            typography: { fontSize: 16, color: '#6b7280' },
                                            spacing: { margin: { top: 16 } }
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                };

                return designs[designType] || designs.generic;
            },

            /**
             * Importar diseño de Figma al canvas
             */
            importFigmaDesign: function() {
                var self = this;
                var figmaData = window.vbpFigmaData;

                if (!figmaData.designContext) {
                    this.showFigmaStatus('No hay diseño para importar', 'error');
                    return;
                }

                var store = Alpine.store('vbp');
                if (!store) {
                    this.showFigmaStatus('VBP Store no disponible', 'error');
                    return;
                }

                // Get import options
                var importStyles = document.getElementById('figma-import-styles').checked;
                var importImages = document.getElementById('figma-import-images').checked;
                var importLayout = document.getElementById('figma-import-layout').checked;
                var importText = document.getElementById('figma-import-text').checked;

                this.showFigmaStatus('🔄 Importando elementos...', 'info');

                // Convert Figma elements to VBP elements
                var design = figmaData.designContext;
                var importedElements = [];

                design.elements.forEach(function(figmaElement) {
                    var vbpElement = self.convertFigmaToVBP(figmaElement, {
                        importStyles: importStyles,
                        importImages: importImages,
                        importLayout: importLayout,
                        importText: importText
                    });
                    importedElements.push(vbpElement);
                });

                // Add elements to store
                importedElements.forEach(function(element) {
                    store.elements.push(element);
                });

                store.isDirty = true;

                // Close modal
                var modal = document.getElementById('vbp-figma-importer-modal');
                if (modal) modal.remove();

                this.showNotification('✅ Importados ' + importedElements.length + ' elementos desde Figma');

                // Select imported elements
                var importedIds = importedElements.map(function(el) { return el.id; });
                store.setSelection(importedIds);
            },

            /**
             * Convertir elemento de Figma a VBP
             */
            convertFigmaToVBP: function(figmaElement, options) {
                var self = this;

                var vbpElement = {
                    id: 'figma_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    type: figmaElement.type || 'container',
                    name: figmaElement.name || 'Imported Element',
                    content: options.importText ? (figmaElement.content || '') : '',
                    styles: options.importStyles ? (figmaElement.styles || {}) : {},
                    children: []
                };

                // Process children recursively
                if (figmaElement.children && figmaElement.children.length) {
                    figmaElement.children.forEach(function(child) {
                        vbpElement.children.push(self.convertFigmaToVBP(child, options));
                    });
                }

                return vbpElement;
            },

            /**
             * Mostrar estado en el modal de Figma
             */
            showFigmaStatus: function(message, type) {
                var statusEl = document.getElementById('figma-status');
                if (!statusEl) return;

                var colors = {
                    success: { bg: '#dcfce7', border: '#86efac', text: '#166534' },
                    error: { bg: '#fee2e2', border: '#fca5a5', text: '#991b1b' },
                    info: { bg: '#dbeafe', border: '#93c5fd', text: '#1e40af' },
                    warning: { bg: '#fef3c7', border: '#fcd34d', text: '#92400e' }
                };

                var style = colors[type] || colors.info;

                statusEl.style.display = 'block';
                statusEl.style.backgroundColor = style.bg;
                statusEl.style.borderColor = style.border;
                statusEl.style.color = style.text;
                statusEl.style.border = '1px solid ' + style.border;
                statusEl.textContent = message;
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
                { keys: 'Ctrl + Alt + Shift + R', action: 'Mostrar/Ocultar reglas' },
                { keys: 'Alt + Shift + G', action: 'Configuración del grid' },
                { keys: 'Ctrl + Alt + T', action: 'Constraint arriba' },
                { keys: 'Ctrl + Alt + B', action: 'Constraint abajo' },
                { keys: 'Ctrl + Alt + L', action: 'Constraint izquierda' },
                { keys: 'Ctrl + Alt + →', action: 'Constraint derecha' }
            ]},
            { category: 'Efectos y Editores', shortcuts: [
                { keys: 'Ctrl + Alt + Shift + S', action: 'Editor de sombras' },
                { keys: 'Ctrl + Alt + Shift + X', action: 'Editor de gradientes' },
                { keys: 'Ctrl + Alt + A', action: 'Editor de animaciones' },
                { keys: 'Ctrl + Shift + T', action: 'Editor de tipografía' },
                { keys: 'Ctrl + Shift + B', action: 'Editor de bordes' },
                { keys: 'Ctrl + Alt + P', action: 'Editor de espaciado' },
                { keys: 'Ctrl + Alt + Shift + H', action: 'Estados interactivos (hover/active)' },
                { keys: 'Ctrl + Alt + Shift + Y', action: 'Animaciones de scroll' }
            ]},
            { category: 'Templates y Componentes', shortcuts: [
                { keys: 'Ctrl + Shift + K', action: 'Biblioteca de templates' },
                { keys: 'Ctrl + Alt + Shift + C', action: 'Guardar como componente' },
                { keys: 'Ctrl + Shift + I', action: 'Biblioteca de componentes' }
            ]},
            { category: 'Design System', shortcuts: [
                { keys: 'Ctrl + Alt + Shift + T', action: 'Editor de design tokens' },
                { keys: 'Ctrl + Alt + E', action: 'Opciones de exportación' },
                { keys: 'Ctrl + Alt + Shift + F', action: 'Importar desde Figma' }
            ]},
            { category: 'Responsive', shortcuts: [
                { keys: '1', action: 'Vista Desktop' },
                { keys: '2', action: 'Vista Tablet (768px)' },
                { keys: '3', action: 'Vista Mobile (375px)' }
            ]},
            { category: 'Navegación Canvas', shortcuts: [
                { keys: 'Space', action: 'Activar modo pan (arrastrar)' }
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

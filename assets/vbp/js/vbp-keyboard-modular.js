/**
 * Visual Builder Pro - Keyboard Shortcuts (Modular)
 * Gestión de atajos de teclado - Versión modularizada
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 *
 * Este archivo carga los módulos de forma diferida para optimizar
 * el tiempo de carga inicial del editor.
 *
 * Módulos disponibles:
 * - vbp-keyboard-clipboard.js: Copiar, pegar, duplicar, estilos
 * - vbp-keyboard-selection.js: Selección, grupos, visibilidad
 * - vbp-keyboard-transform.js: Alineación, rotación, distribución
 * - vbp-keyboard-export.js: Exportación HTML, CSS, React, Vue, Svelte
 * - vbp-keyboard-tools.js: Grid, guías, favoritos, bookmarks
 * - vbp-keyboard-editors.js: Editores de propiedades (carga diferida)
 * - vbp-keyboard-figma.js: Importación de Figma (carga diferida)
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
    Alpine.data('vbpKeyboard', function() {
        return {
            /**
             * Mapeo de atajos a acciones (cargado desde JSON)
             */
            shortcuts: null,

            /**
             * Módulos cargados
             */
            modulesLoaded: {
                clipboard: false,
                selection: false,
                transform: false,
                export: false,
                tools: false,
                editors: false,
                figma: false
            },

            /**
             * Inicialización
             */
            init: function() {
                var self = this;

                // Cargar mapeo de atajos desde JSON
                this.loadShortcuts();

                // Registrar handler de teclado
                document.addEventListener('keydown', function(e) {
                    self.handleKeydown(e);
                });

                // Listener para ejecutar acciones desde command palette
                document.addEventListener('vbp:executeAction', function(e) {
                    if (e.detail && e.detail.action) {
                        self.executeAction(e.detail.action);
                    }
                });

                // Cargar módulos esenciales de forma inmediata
                this.loadModule('clipboard');
                this.loadModule('selection');
                this.loadModule('transform');
                this.loadModule('tools');

                // Exponer globalmente para acceso desde modales
                window.vbpKeyboard = this;
            },

            /**
             * Cargar mapeo de atajos desde JSON
             */
            loadShortcuts: function() {
                var self = this;
                var basePath = typeof VBP_Config !== 'undefined' ? VBP_Config.assetsUrl : '/wp-content/plugins/flavor-chat-ia/assets/vbp/';

                fetch(basePath + 'js/vbp-keyboard-shortcuts.json')
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        self.shortcuts = self.flattenShortcuts(data.categories);
                    })
                    .catch(function(error) {
                        vbpLog.warn('Usando atajos por defecto', error);
                        self.shortcuts = self.getDefaultShortcuts();
                    });
            },

            /**
             * Aplanar categorías de atajos en un solo objeto
             */
            flattenShortcuts: function(categories) {
                var flat = {};
                for (var category in categories) {
                    var cat = categories[category];
                    if (cat.shortcuts) {
                        for (var shortcut in cat.shortcuts) {
                            flat[shortcut] = cat.shortcuts[shortcut];
                        }
                    }
                }
                return flat;
            },

            /**
             * Atajos por defecto (fallback)
             */
            getDefaultShortcuts: function() {
                return {
                    'ctrl+s': 'save', 'ctrl+z': 'undo', 'ctrl+y': 'redo',
                    'ctrl+c': 'copy', 'ctrl+x': 'cut', 'ctrl+v': 'paste',
                    'ctrl+d': 'duplicate', 'delete': 'delete', 'backspace': 'delete',
                    'escape': 'deselect', 'ctrl+a': 'selectAll',
                    'arrowup': 'nudgeUp', 'arrowdown': 'nudgeDown',
                    'arrowleft': 'nudgeLeft', 'arrowright': 'nudgeRight',
                    'ctrl+k': 'commandPalette', '?': 'help'
                };
            },

            /**
             * Cargar módulo dinámicamente
             */
            loadModule: function(moduleName) {
                if (this.modulesLoaded[moduleName]) return Promise.resolve();

                var self = this;
                var basePath = typeof VBP_Config !== 'undefined' ? VBP_Config.assetsUrl : '/wp-content/plugins/flavor-chat-ia/assets/vbp/';
                var moduleNames = {
                    clipboard: 'vbp-keyboard-clipboard.js',
                    selection: 'vbp-keyboard-selection.js',
                    transform: 'vbp-keyboard-transform.js',
                    export: 'vbp-keyboard-export.js',
                    tools: 'vbp-keyboard-tools.js',
                    editors: 'vbp-keyboard-editors.js',
                    figma: 'vbp-keyboard-figma.js'
                };

                return new Promise(function(resolve, reject) {
                    var script = document.createElement('script');
                    script.src = basePath + 'js/modules/' + moduleNames[moduleName];
                    script.onload = function() {
                        self.modulesLoaded[moduleName] = true;
                        resolve();
                    };
                    script.onerror = function() {
                        vbpLog.error('Error cargando módulo ' + moduleName);
                        reject();
                    };
                    document.head.appendChild(script);
                });
            },

            /**
             * Manejador de keydown
             */
            handleKeydown: function(event) {
                // No interceptar si estamos en un campo editable
                if (event.target.closest('[contenteditable], input, textarea, select')) {
                    if (event.key === 'Escape') {
                        event.target.blur();
                        this.executeAction('deselect');
                    }
                    return;
                }

                if (!this.shortcuts) return;

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

                // Acciones del core
                switch (action) {
                    // === ARCHIVO ===
                    case 'save':
                        this.saveDocument();
                        return;
                    case 'saveAs':
                        this.saveAsTemplate();
                        return;
                    case 'preview':
                        this.openPreview();
                        return;

                    // === UNDO/REDO ===
                    case 'undo':
                        store.undo();
                        this.showNotification('Deshacer');
                        return;
                    case 'redo':
                        store.redo();
                        this.showNotification('Rehacer');
                        return;

                    // === ZOOM ===
                    case 'zoomIn':
                        store.zoom = Math.min(200, store.zoom + 10);
                        this.showZoomFeedback(store.zoom);
                        return;
                    case 'zoomOut':
                        store.zoom = Math.max(25, store.zoom - 10);
                        this.showZoomFeedback(store.zoom);
                        return;
                    case 'zoomReset':
                    case 'zoom100':
                        store.zoom = 100;
                        this.showZoomFeedback(100);
                        return;
                    case 'zoom50':
                        store.zoom = 50;
                        this.showZoomFeedback(50);
                        return;
                    case 'zoom200':
                        store.zoom = 200;
                        this.showZoomFeedback(200);
                        return;

                    // === PANELES ===
                    case 'togglePanels':
                        var allVisible = store.panels.blocks && store.panels.inspector && store.panels.layers;
                        store.panels.blocks = !allVisible;
                        store.panels.inspector = !allVisible;
                        store.panels.layers = !allVisible;
                        return;
                    case 'toggleBlocksPanel':
                        store.panels.blocks = !store.panels.blocks;
                        return;
                    case 'toggleInspectorPanel':
                        store.panels.inspector = !store.panels.inspector;
                        return;
                    case 'toggleLayersPanel':
                        store.panels.layers = !store.panels.layers;
                        return;

                    // === MODALES ===
                    case 'commandPalette':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', { detail: { modal: 'commandPalette' } }));
                        return;
                    case 'help':
                        this.showHelpModal();
                        return;
                    case 'settings':
                        document.dispatchEvent(new CustomEvent('vbp:openModal', { detail: { modal: 'settings' } }));
                        return;

                    // === SELECCIÓN BÁSICA ===
                    case 'deselect':
                        store.clearSelection();
                        return;
                    case 'selectAll':
                        store.setSelection(store.elements.map(function(el) { return el.id; }));
                        this.showNotification('Todos seleccionados');
                        return;
                }

                // Delegar a módulos
                this.delegateToModule(action);
            },

            /**
             * Delegar acción a módulo correspondiente
             */
            delegateToModule: function(action) {
                var self = this;

                // Acciones del módulo Clipboard
                var clipboardActions = ['copy', 'cut', 'paste', 'duplicate', 'duplicateInPlace', 'copyStyles', 'pasteStyles', 'resetStyles', 'copyAsHTML', 'copyAsJSON', 'pasteFromJSON'];
                if (clipboardActions.indexOf(action) !== -1) {
                    this.loadModule('clipboard').then(function() {
                        if (window.VBPKeyboardClipboard && window.VBPKeyboardClipboard[action]) {
                            window.VBPKeyboardClipboard[action]();
                        }
                    });
                    return;
                }

                // Acciones del módulo Selection
                var selectionActions = ['invertSelection', 'selectSimilar', 'selectNext', 'selectPrevious', 'editInline', 'group', 'ungroup', 'delete', 'selectParent', 'selectFirstChild', 'toggleLock', 'toggleVisibility', 'hideOthers'];
                if (selectionActions.indexOf(action) !== -1) {
                    this.loadModule('selection').then(function() {
                        var methodMap = {
                            'selectNext': function() { window.VBPKeyboardSelection.selectAdjacentElement(1); },
                            'selectPrevious': function() { window.VBPKeyboardSelection.selectAdjacentElement(-1); },
                            'editInline': function() { window.VBPKeyboardSelection.startInlineEdit(); },
                            'group': function() { window.VBPKeyboardSelection.groupSelection(); },
                            'ungroup': function() { window.VBPKeyboardSelection.ungroupSelection(); },
                            'delete': function() { window.VBPKeyboardSelection.deleteSelection(); }
                        };
                        if (methodMap[action]) {
                            methodMap[action]();
                        } else if (window.VBPKeyboardSelection[action]) {
                            window.VBPKeyboardSelection[action]();
                        }
                    });
                    return;
                }

                // Acciones del módulo Transform
                var transformActions = ['nudgeUp', 'nudgeDown', 'nudgeLeft', 'nudgeRight', 'nudgeUpLarge', 'nudgeDownLarge', 'nudgeLeftLarge', 'nudgeRightLarge', 'moveToTop', 'moveToBottom', 'alignLeft', 'alignRight', 'alignTop', 'alignBottom', 'alignCenterH', 'alignCenterV', 'distributeH', 'distributeV', 'sendBackward', 'bringForward', 'sendToBack', 'bringToFront', 'matchSize', 'swapElements', 'wrapInContainer', 'stackHorizontal', 'stackVertical', 'rotate15', 'rotateNeg15', 'rotate90', 'resetRotation', 'flipHorizontal', 'flipVertical', 'resetPosition', 'fitContent', 'fillParent', 'centerInViewport', 'setSpacing8', 'setSpacing16', 'setSpacing24', 'setSpacing32'];
                if (transformActions.indexOf(action) !== -1) {
                    this.loadModule('transform').then(function() {
                        var methodMap = {
                            'nudgeUp': function() { window.VBPKeyboardTransform.nudgeSelection(0, -1); },
                            'nudgeDown': function() { window.VBPKeyboardTransform.nudgeSelection(0, 1); },
                            'nudgeLeft': function() { window.VBPKeyboardTransform.nudgeSelection(-1, 0); },
                            'nudgeRight': function() { window.VBPKeyboardTransform.nudgeSelection(1, 0); },
                            'nudgeUpLarge': function() { window.VBPKeyboardTransform.nudgeSelection(0, -10); },
                            'nudgeDownLarge': function() { window.VBPKeyboardTransform.nudgeSelection(0, 10); },
                            'nudgeLeftLarge': function() { window.VBPKeyboardTransform.nudgeSelection(-10, 0); },
                            'nudgeRightLarge': function() { window.VBPKeyboardTransform.nudgeSelection(10, 0); },
                            'moveToTop': function() { window.VBPKeyboardTransform.moveSelectionToEdge('top'); },
                            'moveToBottom': function() { window.VBPKeyboardTransform.moveSelectionToEdge('bottom'); },
                            'alignLeft': function() { window.VBPKeyboardTransform.alignElements('left'); },
                            'alignRight': function() { window.VBPKeyboardTransform.alignElements('right'); },
                            'alignTop': function() { window.VBPKeyboardTransform.alignElements('top'); },
                            'alignBottom': function() { window.VBPKeyboardTransform.alignElements('bottom'); },
                            'alignCenterH': function() { window.VBPKeyboardTransform.alignElements('centerH'); },
                            'alignCenterV': function() { window.VBPKeyboardTransform.alignElements('centerV'); },
                            'distributeH': function() { window.VBPKeyboardTransform.distributeElements('horizontal'); },
                            'distributeV': function() { window.VBPKeyboardTransform.distributeElements('vertical'); },
                            'sendBackward': function() { window.VBPKeyboardTransform.changeZOrder('backward'); },
                            'bringForward': function() { window.VBPKeyboardTransform.changeZOrder('forward'); },
                            'sendToBack': function() { window.VBPKeyboardTransform.changeZOrder('back'); },
                            'bringToFront': function() { window.VBPKeyboardTransform.changeZOrder('front'); },
                            'stackHorizontal': function() { window.VBPKeyboardTransform.stackElements('horizontal'); },
                            'stackVertical': function() { window.VBPKeyboardTransform.stackElements('vertical'); },
                            'rotate15': function() { window.VBPKeyboardTransform.rotateSelection(15); },
                            'rotateNeg15': function() { window.VBPKeyboardTransform.rotateSelection(-15); },
                            'rotate90': function() { window.VBPKeyboardTransform.rotateSelection(90); },
                            'flipHorizontal': function() { window.VBPKeyboardTransform.flipElement('horizontal'); },
                            'flipVertical': function() { window.VBPKeyboardTransform.flipElement('vertical'); },
                            'setSpacing8': function() { window.VBPKeyboardTransform.setSpacingPreset(8); },
                            'setSpacing16': function() { window.VBPKeyboardTransform.setSpacingPreset(16); },
                            'setSpacing24': function() { window.VBPKeyboardTransform.setSpacingPreset(24); },
                            'setSpacing32': function() { window.VBPKeyboardTransform.setSpacingPreset(32); }
                        };
                        if (methodMap[action]) {
                            methodMap[action]();
                        } else if (window.VBPKeyboardTransform[action]) {
                            window.VBPKeyboardTransform[action]();
                        }
                    });
                    return;
                }

                // Acciones del módulo Tools
                var toolsActions = ['toggleGrid', 'toggleGuides', 'toggleSnapToGrid', 'toggleRulers', 'openGridSettings', 'togglePanMode', 'setBookmark1', 'setBookmark2', 'setBookmark3', 'goToBookmark1', 'goToBookmark2', 'goToBookmark3', 'quickRename', 'toggleAspectRatioLock', 'toggleCollapse', 'toggleConstraintTop', 'toggleConstraintBottom', 'toggleConstraintLeft', 'toggleConstraintRight', 'saveAsFavorite', 'openFavorites', 'find', 'findElements', 'toggleSmartGuides', 'toggleMeasureTool', 'toggleAutoLayout', 'decreaseGap', 'increaseGap', 'breakpointDesktop', 'breakpointTablet', 'breakpointMobile'];
                if (toolsActions.indexOf(action) !== -1) {
                    this.loadModule('tools').then(function() {
                        var methodMap = {
                            'setBookmark1': function() { window.VBPKeyboardTools.setBookmark(1); },
                            'setBookmark2': function() { window.VBPKeyboardTools.setBookmark(2); },
                            'setBookmark3': function() { window.VBPKeyboardTools.setBookmark(3); },
                            'goToBookmark1': function() { window.VBPKeyboardTools.goToBookmark(1); },
                            'goToBookmark2': function() { window.VBPKeyboardTools.goToBookmark(2); },
                            'goToBookmark3': function() { window.VBPKeyboardTools.goToBookmark(3); },
                            'toggleConstraintTop': function() { window.VBPKeyboardTools.toggleConstraint('top'); },
                            'toggleConstraintBottom': function() { window.VBPKeyboardTools.toggleConstraint('bottom'); },
                            'toggleConstraintLeft': function() { window.VBPKeyboardTools.toggleConstraint('left'); },
                            'toggleConstraintRight': function() { window.VBPKeyboardTools.toggleConstraint('right'); },
                            'openFavorites': function() { window.VBPKeyboardTools.openFavoritesPanel(); },
                            'find': function() { window.VBPKeyboardTools.openFindDialog(); },
                            'findElements': function() { window.VBPKeyboardTools.openFindDialog(); },
                            'decreaseGap': function() { window.VBPKeyboardTools.adjustAutoLayoutGap(-4); },
                            'increaseGap': function() { window.VBPKeyboardTools.adjustAutoLayoutGap(4); },
                            'breakpointDesktop': function() { window.VBPKeyboardTools.setBreakpoint('desktop'); },
                            'breakpointTablet': function() { window.VBPKeyboardTools.setBreakpoint('tablet'); },
                            'breakpointMobile': function() { window.VBPKeyboardTools.setBreakpoint('mobile'); }
                        };
                        if (methodMap[action]) {
                            methodMap[action]();
                        } else if (window.VBPKeyboardTools[action]) {
                            window.VBPKeyboardTools[action]();
                        }
                    });
                    return;
                }

                // Acciones del módulo Export
                var exportActions = ['export', 'openExportOptions'];
                if (exportActions.indexOf(action) !== -1) {
                    this.loadModule('export').then(function() {
                        window.VBPKeyboardExport.openExportOptions();
                    });
                    return;
                }

                // Acciones de editores (carga diferida)
                var editorActions = ['openCSSVariables', 'openVersionCompare', 'openShadowEditor', 'openGradientEditor', 'openAnimationEditor', 'openTypographyEditor', 'openBorderEditor', 'openSpacingEditor', 'openHoverStatesEditor', 'openScrollAnimationEditor', 'openTemplatesLibrary', 'saveAsComponent', 'openComponentsLibrary', 'openDesignTokens'];
                if (editorActions.indexOf(action) !== -1) {
                    this.loadModule('editors').then(function() {
                        if (window.VBPKeyboardEditors && window.VBPKeyboardEditors[action]) {
                            window.VBPKeyboardEditors[action]();
                        }
                    });
                    return;
                }

                // Acciones de Figma (carga diferida)
                var figmaActions = ['openFigmaImporter'];
                if (figmaActions.indexOf(action) !== -1) {
                    this.loadModule('figma').then(function() {
                        if (window.VBPKeyboardFigma && window.VBPKeyboardFigma[action]) {
                            window.VBPKeyboardFigma[action]();
                        }
                    });
                    return;
                }

                vbpLog.warn('Acción no reconocida:', action);
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
                    var previewUrl = (typeof VBP_Config !== 'undefined' ? VBP_Config.siteUrl : '') + '?p=' + store.postId + '&preview=true';
                    window.open(previewUrl, '_blank');
                }
            },

            /**
             * Mostrar feedback de zoom
             */
            showZoomFeedback: function(zoomLevel) {
                var existente = document.querySelector('.vbp-zoom-feedback');
                if (existente) existente.remove();

                var feedback = document.createElement('div');
                feedback.className = 'vbp-zoom-feedback';
                feedback.textContent = zoomLevel + '%';
                feedback.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 16px 32px; border-radius: 8px; font-size: 24px; font-weight: bold; z-index: 100000; pointer-events: none;';

                document.body.appendChild(feedback);

                setTimeout(function() {
                    feedback.remove();
                }, 800);
            },

            /**
             * Mostrar notificación
             */
            showNotification: function(message, type) {
                type = type || 'info';

                var existente = document.querySelector('.vbp-notification');
                if (existente) existente.remove();

                var notif = document.createElement('div');
                notif.className = 'vbp-notification vbp-notification-' + type;
                notif.textContent = message;
                notif.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: var(--vbp-surface, #313244); color: var(--vbp-text, #cdd6f4); padding: 12px 20px; border-radius: 8px; font-size: 14px; z-index: 100000; box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: vbp-slide-in 0.3s ease;';

                document.body.appendChild(notif);

                setTimeout(function() {
                    notif.style.animation = 'vbp-slide-out 0.3s ease forwards';
                    setTimeout(function() { notif.remove(); }, 300);
                }, 2000);
            },

            /**
             * Mostrar modal de ayuda
             */
            showHelpModal: function() {
                var self = this;
                var modalId = 'vbp-help-modal';
                var existente = document.getElementById(modalId);
                if (existente) existente.remove();

                var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
                html += '<div class="vbp-modal" style="max-width: 700px; max-height: 80vh;">';
                html += '<div class="vbp-modal-header">';
                html += '<h2>⌨️ Atajos de Teclado</h2>';
                html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
                html += '</div>';
                html += '<div class="vbp-modal-body" style="overflow-y: auto; max-height: 60vh;">';

                var categorias = {
                    'Archivo': { 'Ctrl+S': 'Guardar', 'Ctrl+Shift+S': 'Guardar como', 'Ctrl+P': 'Vista previa' },
                    'Edición': { 'Ctrl+Z': 'Deshacer', 'Ctrl+Y': 'Rehacer', 'Ctrl+C': 'Copiar', 'Ctrl+X': 'Cortar', 'Ctrl+V': 'Pegar', 'Ctrl+D': 'Duplicar' },
                    'Selección': { 'Escape': 'Deseleccionar', 'Ctrl+A': 'Seleccionar todo', 'Delete': 'Eliminar', 'Tab': 'Siguiente', 'Shift+Tab': 'Anterior' },
                    'Navegación': { '↑↓←→': 'Mover 1px', 'Shift+Flechas': 'Mover 10px', 'Ctrl+↑↓': 'Mover a borde' },
                    'Alineación': { 'Alt+A/D/W/S': 'Alinear', 'Alt+H/V': 'Centrar', 'Ctrl+Alt+H/V': 'Distribuir' },
                    'Zoom': { 'Ctrl++/-': 'Zoom in/out', 'Ctrl+0': 'Reset zoom', 'Ctrl+1/2/5': '100/200/50%' },
                    'Paneles': { 'Ctrl+B': 'Panel bloques', 'Ctrl+I': 'Inspector', 'Ctrl+L': 'Capas' },
                    'Herramientas': { 'Ctrl+K': 'Paleta comandos', 'Ctrl+F': 'Buscar', 'M': 'Medir', 'Espacio': 'Pan' }
                };

                for (var cat in categorias) {
                    html += '<div style="margin-bottom: 20px;">';
                    html += '<h3 style="font-size: 14px; color: var(--vbp-primary); margin-bottom: 8px;">' + cat + '</h3>';
                    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">';
                    for (var atajo in categorias[cat]) {
                        html += '<div style="display: flex; justify-content: space-between; padding: 6px 12px; background: var(--vbp-surface); border-radius: 4px;">';
                        html += '<kbd style="font-family: monospace; font-size: 12px; background: var(--vbp-bg); padding: 2px 6px; border-radius: 3px;">' + atajo + '</kbd>';
                        html += '<span style="font-size: 12px; color: var(--vbp-text-muted);">' + categorias[cat][atajo] + '</span>';
                        html += '</div>';
                    }
                    html += '</div></div>';
                }

                html += '</div></div></div>';

                document.body.insertAdjacentHTML('beforeend', html);
            },

            /**
             * Obtener lista de atajos (para command palette)
             */
            getShortcutsList: function() {
                if (!this.shortcuts) return [];

                var lista = [];
                for (var atajo in this.shortcuts) {
                    lista.push({
                        shortcut: atajo,
                        action: this.shortcuts[atajo]
                    });
                }
                return lista;
            }
        };
    });
});

// CSS para animaciones de notificación
(function() {
    var style = document.createElement('style');
    style.textContent = '@keyframes vbp-slide-in { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes vbp-slide-out { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }';
    document.head.appendChild(style);
})();

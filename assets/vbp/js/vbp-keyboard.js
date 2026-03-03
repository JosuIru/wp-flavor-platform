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

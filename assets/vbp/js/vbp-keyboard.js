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

                // Bloqueo
                'ctrl+shift+l': 'toggleLock',

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

                    // === BLOQUEO ===
                    case 'toggleLock':
                        this.toggleLock();
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
                { keys: 'Ctrl + D', action: 'Duplicar' },
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
                { keys: 'Ctrl + ↓', action: 'Mover al fondo' }
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
            { category: 'Paneles', shortcuts: [
                { keys: 'Ctrl + \\', action: 'Toggle todos los paneles' },
                { keys: 'Ctrl + B', action: 'Panel de bloques' },
                { keys: 'Ctrl + L', action: 'Capas' }
            ]},
            { category: 'Alineación', shortcuts: [
                { keys: 'Alt + L', action: 'Alinear a la izquierda' },
                { keys: 'Alt + C', action: 'Centrar horizontalmente' },
                { keys: 'Alt + R', action: 'Alinear a la derecha' },
                { keys: 'Alt + T', action: 'Alinear arriba' },
                { keys: 'Alt + M', action: 'Centrar verticalmente' },
                { keys: 'Alt + B', action: 'Alinear abajo' },
                { keys: 'Ctrl + Alt + H', action: 'Distribuir horizontalmente' },
                { keys: 'Ctrl + Alt + V', action: 'Distribuir verticalmente' }
            ]},
            { category: 'Productividad', shortcuts: [
                { keys: 'Ctrl + /', action: 'Paleta de comandos' },
                { keys: 'Ctrl + K', action: 'Paleta de comandos' },
                { keys: 'Ctrl + E', action: 'Exportar' },
                { keys: 'Ctrl + T', action: 'Templates' },
                { keys: 'Ctrl + ,', action: 'Configuración' },
                { keys: '? / F1', action: 'Ayuda (esta ventana)' }
            ]}
        ];
    }
};

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

                // Selección
                'delete': 'delete',
                'backspace': 'delete',
                'escape': 'deselect',
                'ctrl+a': 'selectAll',

                // Navegación
                'arrowup': 'moveUp',
                'arrowdown': 'moveDown',
                'ctrl+arrowup': 'moveToTop',
                'ctrl+arrowdown': 'moveToBottom',

                // Zoom
                'ctrl++': 'zoomIn',
                'ctrl+=': 'zoomIn',
                'ctrl+-': 'zoomOut',
                'ctrl+0': 'zoomReset',
                'ctrl+1': 'zoom100',

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

                // Extras
                'ctrl+u': 'unsplash',
                'ctrl+shift+g': 'saveAsGlobal',
                'ctrl+h': 'versionHistory'
            },

            /**
             * Clipboard para copy/paste
             */
            clipboard: null,

            /**
             * Inicialización
             */
            init: function() {
                var self = this;
                document.addEventListener('keydown', function(e) {
                    self.handleKeydown(e);
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

                    // === NAVEGACIÓN ===
                    case 'moveUp':
                        this.moveSelection(-1);
                        break;

                    case 'moveDown':
                        this.moveSelection(1);
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
                        break;

                    case 'zoomOut':
                        store.zoom = Math.max(25, store.zoom - 10);
                        break;

                    case 'zoomReset':
                        store.zoom = 100;
                        break;

                    case 'zoom100':
                        store.zoom = 100;
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
             * Mover selección
             */
            moveSelection: function(direction) {
                var store = Alpine.store('vbp');

                if (store.selection.elementIds.length !== 1) return;

                var id = store.selection.elementIds[0];
                var currentIndex = store.elements.findIndex(function(el) {
                    return el.id === id;
                });

                if (currentIndex === -1) return;

                var newIndex = currentIndex + direction;

                if (newIndex < 0 || newIndex >= store.elements.length) return;

                store.moveElement(currentIndex, newIndex);
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
                { keys: 'Ctrl + Shift + V', action: 'Pegar estilos' }
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
            { category: 'Selección', shortcuts: [
                { keys: 'Ctrl + A', action: 'Seleccionar todo' },
                { keys: 'Escape', action: 'Deseleccionar' },
                { keys: 'Alt + Click', action: 'Duplicar elemento' },
                { keys: 'Shift + Click', action: 'Multi-selección' }
            ]},
            { category: 'Navegación', shortcuts: [
                { keys: '↑ / ↓', action: 'Mover elemento' },
                { keys: 'Ctrl + ↑', action: 'Mover al inicio' },
                { keys: 'Ctrl + ↓', action: 'Mover al final' }
            ]},
            { category: 'Zoom', shortcuts: [
                { keys: 'Ctrl + +', action: 'Acercar' },
                { keys: 'Ctrl + -', action: 'Alejar' },
                { keys: 'Ctrl + 0', action: 'Zoom 100%' }
            ]},
            { category: 'Paneles', shortcuts: [
                { keys: 'Ctrl + \\', action: 'Toggle todos los paneles' },
                { keys: 'Ctrl + B', action: 'Panel de bloques' },
                { keys: 'Ctrl + L', action: 'Capas' }
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

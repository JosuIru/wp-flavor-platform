/**
 * Visual Builder Pro - Keyboard Selection Module
 * Operaciones de selección y agrupación
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardSelection = {
    /**
     * Invertir selección
     */
    invertSelection: function() {
        var store = Alpine.store('vbp');

        if (store.elements.length === 0) return;

        var currentIds = store.selection.elementIds;
        var newIds = store.elements
            .map(function(el) { return el.id; })
            .filter(function(id) { return currentIds.indexOf(id) === -1; });

        store.setSelection(newIds);
        window.vbpKeyboard.showNotification('Selección invertida (' + newIds.length + ')');
    },

    /**
     * Seleccionar elementos del mismo tipo
     */
    selectSimilar: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }

        var tipos = [];
        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && tipos.indexOf(element.type) === -1) {
                tipos.push(element.type);
            }
        });

        var newIds = store.elements
            .filter(function(el) { return tipos.indexOf(el.type) !== -1; })
            .map(function(el) { return el.id; });

        store.setSelection(newIds);
        window.vbpKeyboard.showNotification('Seleccionados similares (' + newIds.length + ')');
    },

    /**
     * Seleccionar elemento adyacente
     */
    selectAdjacentElement: function(direction) {
        var store = Alpine.store('vbp');

        if (store.elements.length === 0) return;

        var currentIndex = -1;

        if (store.selection.elementIds.length > 0) {
            var lastSelected = store.selection.elementIds[store.selection.elementIds.length - 1];
            currentIndex = store.elements.findIndex(function(el) {
                return el.id === lastSelected;
            });
        }

        var newIndex = currentIndex + direction;

        if (newIndex < 0) {
            newIndex = store.elements.length - 1;
        } else if (newIndex >= store.elements.length) {
            newIndex = 0;
        }

        store.setSelection([store.elements[newIndex].id]);
    },

    /**
     * Seleccionar elemento padre
     */
    selectParent: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un solo elemento', 'warning');
            return;
        }

        var elementId = store.selection.elementIds[0];

        function encontrarPadre(elementos, targetId, padre) {
            for (var i = 0; i < elementos.length; i++) {
                var elemento = elementos[i];
                if (elemento.id === targetId) {
                    return padre;
                }
                if (elemento.children && elemento.children.length > 0) {
                    var resultado = encontrarPadre(elemento.children, targetId, elemento);
                    if (resultado) return resultado;
                }
            }
            return null;
        }

        var padre = encontrarPadre(store.elements, elementId, null);

        if (padre) {
            store.setSelection([padre.id]);
            window.vbpKeyboard.showNotification('Padre seleccionado: ' + (padre.name || padre.type));
        } else {
            window.vbpKeyboard.showNotification('El elemento está en el nivel raíz', 'info');
        }
    },

    /**
     * Seleccionar primer hijo
     */
    selectFirstChild: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un solo elemento', 'warning');
            return;
        }

        var element = store.getElement(store.selection.elementIds[0]);

        if (!element) return;

        if (element.children && element.children.length > 0) {
            store.setSelection([element.children[0].id]);
            window.vbpKeyboard.showNotification('Primer hijo seleccionado');
        } else {
            window.vbpKeyboard.showNotification('El elemento no tiene hijos', 'info');
        }
    },

    /**
     * Agrupar elementos seleccionados
     */
    groupSelection: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length < 2) {
            window.vbpKeyboard.showNotification('Selecciona al menos 2 elementos para agrupar', 'warning');
            return;
        }

        store.saveToHistory();

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

        store.selection.elementIds.forEach(function(id) {
            var indice = store.elements.findIndex(function(el) { return el.id === id; });
            if (indice !== -1) {
                store.elements.splice(indice, 1);
            }
        });

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

        var posicionInsercion = Math.min(indicesMasAlto, store.elements.length);
        store.elements.splice(posicionInsercion, 0, grupo);

        store.isDirty = true;
        store.setSelection([grupoId]);

        window.vbpKeyboard.showNotification('Grupo creado con ' + elementosAGrupar.length + ' elementos');
    },

    /**
     * Desagrupar elementos
     */
    ungroupSelection: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un grupo para desagrupar', 'warning');
            return;
        }

        var grupoId = store.selection.elementIds[0];
        var grupo = store.getElement(grupoId);

        if (!grupo || grupo.type !== 'group' || !grupo.children) {
            window.vbpKeyboard.showNotification('El elemento seleccionado no es un grupo', 'warning');
            return;
        }

        store.saveToHistory();

        var indiceGrupo = store.elements.findIndex(function(el) { return el.id === grupoId; });
        store.elements.splice(indiceGrupo, 1);

        var nuevosIds = [];
        grupo.children.forEach(function(hijo, i) {
            var nuevoHijo = JSON.parse(JSON.stringify(hijo));
            nuevoHijo.id = 'el_' + Math.random().toString(36).substr(2, 9);
            store.elements.splice(indiceGrupo + i, 0, nuevoHijo);
            nuevosIds.push(nuevoHijo.id);
        });

        store.isDirty = true;
        store.setSelection(nuevosIds);

        window.vbpKeyboard.showNotification('Grupo disuelto: ' + nuevosIds.length + ' elementos');
    },

    /**
     * Eliminar selección
     */
    deleteSelection: function() {
        var store = Alpine.store('vbp');
        var count = store.selection.elementIds.length;

        if (count === 0) return;

        store.saveToHistory();

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

        window.vbpKeyboard.showNotification('Eliminado (' + count + ')');
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

        var tiposEditables = ['heading', 'text', 'paragraph', 'button', 'link', 'list'];

        if (tiposEditables.indexOf(element.type) === -1) {
            return;
        }

        var elementoCanvas = document.querySelector('[data-element-id="' + elementId + '"]');
        if (!elementoCanvas) {
            elementoCanvas = document.querySelector('#' + elementId);
        }

        if (elementoCanvas) {
            var textoEditable = elementoCanvas.querySelector('[contenteditable]');
            if (!textoEditable) {
                var contenidoPrincipal = elementoCanvas.querySelector('.vbp-element-content, .vbp-heading-text, .vbp-text-content');
                if (contenidoPrincipal) {
                    textoEditable = contenidoPrincipal;
                    textoEditable.setAttribute('contenteditable', 'true');
                }
            }

            if (textoEditable) {
                textoEditable.focus();
                var seleccion = window.getSelection();
                var rango = document.createRange();
                rango.selectNodeContents(textoEditable);
                seleccion.removeAllRanges();
                seleccion.addRange(rango);
            }
        }

        document.dispatchEvent(new CustomEvent('vbp:startInlineEdit', {
            detail: { elementId: elementId, type: element.type }
        }));
    },

    /**
     * Toggle bloqueo
     */
    toggleLock: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para bloquear/desbloquear', 'warning');
            return;
        }

        store.saveToHistory();

        var primerElemento = store.getElement(store.selection.elementIds[0]);
        var nuevoEstado = !primerElemento.locked;
        var count = 0;

        store.selection.elementIds.forEach(function(id) {
            store.updateElement(id, { locked: nuevoEstado });
            count++;
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification((nuevoEstado ? '🔒 Bloqueado' : '🔓 Desbloqueado') + ' (' + count + ')');
    },

    /**
     * Toggle visibilidad
     */
    toggleVisibility: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos', 'warning');
            return;
        }

        store.saveToHistory();

        var primerElemento = store.getElement(store.selection.elementIds[0]);
        var nuevoEstado = primerElemento.visible === false;
        var count = 0;

        store.selection.elementIds.forEach(function(id) {
            store.updateElement(id, { visible: nuevoEstado });
            count++;
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification((nuevoEstado ? '👁 Visible' : '👁‍🗨 Oculto') + ' (' + count + ')');
    },

    /**
     * Ocultar otros elementos
     */
    hideOthers: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para mantener visibles', 'warning');
            return;
        }

        store.saveToHistory();

        var seleccionados = store.selection.elementIds;
        var count = 0;

        store.elements.forEach(function(elemento) {
            if (seleccionados.indexOf(elemento.id) === -1) {
                store.updateElement(elemento.id, { visible: false });
                count++;
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Ocultados ' + count + ' elementos');
    }
};

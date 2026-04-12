/**
 * Visual Builder Pro - Keyboard Clipboard Module
 * Operaciones de portapapeles: copiar, cortar, pegar, duplicar
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardClipboard = {
    clipboard: null,
    styleClipboard: null,

    /**
     * Copiar selección
     */
    copySelection: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            return;
        }

        if (store.selection.elementIds.length === 0) {
            return;
        }

        this.clipboard = store.selection.elementIds.map(function(id) {
            var element = store.getElement(id);
            if (element) {
                return JSON.parse(JSON.stringify(element));
            }
            return null;
        }).filter(function(el) { return el !== null; });

        window.vbpKeyboard.showNotification('Copiado (' + this.clipboard.length + ')');
    },

    /**
     * Cortar selección
     */
    cutSelection: function() {
        this.copySelection();
        window.VBPKeyboardSelection.deleteSelection();
        window.vbpKeyboard.showNotification('Cortado');
    },

    /**
     * Pegar desde clipboard
     */
    pasteClipboard: function() {
        var store = Alpine.store('vbp');

        if (!this.clipboard || this.clipboard.length === 0) {
            window.vbpKeyboard.showNotification('Nada que pegar', 'warning');
            return;
        }

        store.saveToHistory();

        var newIds = [];

        this.clipboard.forEach(function(elementData) {
            var newElement = JSON.parse(JSON.stringify(elementData));
            newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
            newElement.name = elementData.name + ' (copia)';

            store.elements.push(newElement);
            newIds.push(newElement.id);
        });

        store.isDirty = true;
        store.setSelection(newIds);

        window.vbpKeyboard.showNotification('Pegado (' + newIds.length + ')');
    },

    /**
     * Duplicar selección
     */
    duplicateSelection: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            return;
        }
        var count = 0;

        store.selection.elementIds.forEach(function(id) {
            if (store.duplicateElement(id)) {
                count++;
            }
        });

        if (count > 0) {
            window.vbpKeyboard.showNotification('Duplicado (' + count + ')');
        }
    },

    /**
     * Duplicar en el mismo lugar
     */
    duplicateInPlace: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona elementos para duplicar', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para duplicar', 'warning');
            return;
        }

        store.saveToHistory();

        var newIds = [];

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var newElement = JSON.parse(JSON.stringify(element));
                newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
                newElement.name = element.name + ' (copia)';

                var indice = store.elements.findIndex(function(el) { return el.id === id; });
                if (indice !== -1) {
                    store.elements.splice(indice + 1, 0, newElement);
                } else {
                    store.elements.push(newElement);
                }
                newIds.push(newElement.id);
            }
        });

        store.isDirty = true;
        store.setSelection(newIds);

        window.vbpKeyboard.showNotification('Duplicado en lugar (' + newIds.length + ')');
    },

    /**
     * Copiar estilos del elemento seleccionado
     */
    copyStyles: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para copiar estilos', 'warning');
            return;
        }

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para copiar estilos', 'warning');
            return;
        }

        var element = store.getElement(store.selection.elementIds[0]);
        if (!element || !element.styles) {
            window.vbpKeyboard.showNotification('El elemento no tiene estilos', 'warning');
            return;
        }

        var estilosParaCopiar = JSON.parse(JSON.stringify(element.styles));

        this.styleClipboard = {
            type: element.type,
            styles: estilosParaCopiar
        };

        window.vbpKeyboard.showNotification('Estilos copiados');

        // Emitir evento para otros módulos
        document.dispatchEvent(new CustomEvent('vbp:styles:copied', {
            detail: { styles: this.styleClipboard, sourceElementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Pegar estilos al elemento seleccionado
     */
    pasteStyles: function() {
        var store = Alpine.store('vbp');
        var self = this;

        if (!this.styleClipboard) {
            window.vbpKeyboard.showNotification('No hay estilos para pegar', 'warning');
            return;
        }

        if (!store || !store.selection || !store.selection.elementIds || store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para aplicar estilos', 'warning');
            return;
        }

        store.saveToHistory();

        var count = 0;
        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
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
        window.vbpKeyboard.showNotification('Estilos aplicados a ' + count + ' elemento(s)');

        // Emitir evento para otros módulos
        document.dispatchEvent(new CustomEvent('vbp:styles:pasted', {
            detail: {
                styles: this.styleClipboard,
                targetElementIds: store.selection.elementIds.slice(),
                count: count
            }
        }));
    },

    /**
     * Verificar si hay estilos en el clipboard
     */
    hasStylesInClipboard: function() {
        return this.styleClipboard && this.styleClipboard.styles && Object.keys(this.styleClipboard.styles).length > 0;
    },

    /**
     * Resetear estilos a valores por defecto
     */
    resetStyles: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona elementos para resetear estilos', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para resetear estilos', 'warning');
            return;
        }

        store.saveToHistory();

        var count = 0;
        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var estilosPorDefecto = {
                    typography: {},
                    colors: {},
                    spacing: {},
                    border: {},
                    shadow: {},
                    advanced: {}
                };

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
        window.vbpKeyboard.showNotification('Estilos reseteados en ' + count + ' elemento(s)');
    },

    /**
     * Copiar como HTML
     */
    copyAsHTML: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para copiar', 'warning');
            return;
        }

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        var html = window.VBPKeyboardExport.generateHTML(elementos);

        navigator.clipboard.writeText(html).then(function() {
            window.vbpKeyboard.showNotification('HTML copiado al portapapeles');
        });
    },

    /**
     * Copiar como JSON
     */
    copyAsJSON: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para copiar', 'warning');
            return;
        }

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        var json = JSON.stringify(elementos, null, 2);

        navigator.clipboard.writeText(json).then(function() {
            window.vbpKeyboard.showNotification('JSON copiado al portapapeles');
        });
    },

    /**
     * Pegar desde JSON
     */
    pasteFromJSON: function() {
        var store = Alpine.store('vbp');

        navigator.clipboard.readText().then(function(text) {
            try {
                var elementos = JSON.parse(text);

                if (!Array.isArray(elementos)) {
                    elementos = [elementos];
                }

                store.saveToHistory();

                var newIds = [];
                elementos.forEach(function(elementData) {
                    var newElement = JSON.parse(JSON.stringify(elementData));
                    newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);

                    store.elements.push(newElement);
                    newIds.push(newElement.id);
                });

                store.isDirty = true;
                store.setSelection(newIds);

                window.vbpKeyboard.showNotification('Pegado desde JSON (' + newIds.length + ')');
            } catch (e) {
                window.vbpKeyboard.showNotification('Error: JSON inválido', 'error');
            }
        }).catch(function() {
            window.vbpKeyboard.showNotification('No se pudo leer el portapapeles', 'error');
        });
    }
};

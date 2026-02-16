/**
 * Visual Builder Pro - Text Editor
 * Editor de texto enriquecido con toolbar flotante
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Exponer globalmente para el toolbar flotante (debe estar disponible antes de Alpine)
window.vbpTextEditor = {
    formatText: function(command) {
        document.execCommand(command, false, null);
    },
    formatHeading: function(level) {
        document.execCommand('formatBlock', false, level);
    },
    insertLink: function() {
        var url = prompt('Introduce la URL:', 'https://');
        if (url) {
            document.execCommand('createLink', false, url);
        }
    },
    removeLink: function() {
        document.execCommand('unlink', false, null);
    },
    clearFormatting: function() {
        document.execCommand('removeFormat', false, null);
    },
    isFormatActive: function(format) {
        return document.queryCommandState(format);
    }
};

document.addEventListener('alpine:init', function() {
    /**
     * Componente Editor de Texto
     */
    Alpine.data('vbpTextEditor', () => ({
        isEditing: false,
        currentHeading: 'p',
        formatStates: {
            bold: false,
            italic: false,
            underline: false,
            strikeThrough: false
        },

        init() {
            var self = this;

            // Escuchar selección de texto
            document.addEventListener('selectionchange', () => {
                this.handleSelectionChange();
            });

            // Escuchar clicks en elementos editables
            document.addEventListener('click', (e) => {
                if (e.target.matches('[contenteditable="true"]')) {
                    this.activateEditing(e.target);
                } else if (!e.target.closest('.vbp-floating-toolbar') &&
                           !e.target.closest('[contenteditable="true"]')) {
                    this.deactivateEditing();
                }
            });

            // ============ AUTO-GUARDADO EN BLUR/INPUT ============
            // Guardar automáticamente cuando el usuario sale del campo editable
            document.addEventListener('focusout', (e) => {
                if (e.target.matches && e.target.matches('[contenteditable="true"]')) {
                    self.saveContentFromElement(e.target);
                }
            });

            // Guardar automáticamente con debounce mientras escribe (cada 500ms)
            var inputDebounce = null;
            document.addEventListener('input', (e) => {
                if (e.target.matches && e.target.matches('[contenteditable="true"]')) {
                    clearTimeout(inputDebounce);
                    inputDebounce = setTimeout(function() {
                        self.saveContentFromElement(e.target);
                    }, 500);
                }
            });
        },

        /**
         * Guarda el contenido de un elemento específico
         */
        saveContentFromElement(editableElement) {
            if (!editableElement) return;

            // Buscar el elemento del builder
            var vbpElement = editableElement.closest('.vbp-element');
            if (!vbpElement) return;

            var elementId = vbpElement.dataset.elementId;
            if (!elementId) return;

            // Obtener el campo que se está editando
            var campo = editableElement.dataset.field || 'text';
            var contenido = editableElement.innerHTML;

            // Actualizar el elemento en el store
            var store = Alpine.store('vbp');
            var elemento = store.getElement(elementId);

            if (elemento) {
                var datosActualizados = Object.assign({}, elemento.data);
                datosActualizados[campo] = contenido;
                store.updateElement(elementId, { data: datosActualizados });
            }
        },

        /**
         * Activa el modo de edición
         */
        activateEditing(element) {
            this.isEditing = true;
            this.updateFormatStates();
        },

        /**
         * Desactiva el modo de edición
         */
        deactivateEditing() {
            this.isEditing = false;
            Alpine.store('vbp').showFloatingToolbar = false;
        },

        /**
         * Maneja cambios en la selección
         */
        handleSelectionChange() {
            const selection = window.getSelection();

            if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
                Alpine.store('vbp').showFloatingToolbar = false;
                return;
            }

            // Verificar si la selección está en un elemento editable
            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            const editableParent = container.nodeType === Node.TEXT_NODE
                ? container.parentElement?.closest('[contenteditable="true"]')
                : container.closest?.('[contenteditable="true"]');

            if (!editableParent) {
                Alpine.store('vbp').showFloatingToolbar = false;
                return;
            }

            // Mostrar toolbar flotante
            this.showToolbar(range);
            this.updateFormatStates();
        },

        /**
         * Muestra el toolbar en la posición correcta
         */
        showToolbar(range) {
            const rect = range.getBoundingClientRect();

            // Calcular posición centrada sobre la selección
            const toolbarWidth = 400; // Ancho aproximado del toolbar
            let x = rect.left + (rect.width / 2) - (toolbarWidth / 2);
            let y = rect.top - 50; // 50px arriba de la selección

            // Ajustar si se sale de la pantalla
            if (x < 10) x = 10;
            if (x + toolbarWidth > window.innerWidth - 10) {
                x = window.innerWidth - toolbarWidth - 10;
            }
            if (y < 10) y = rect.bottom + 10;

            Alpine.store('vbp').floatingToolbarPosition = { x, y };
            Alpine.store('vbp').showFloatingToolbar = true;
        },

        /**
         * Actualiza el estado de los formatos activos
         */
        updateFormatStates() {
            this.formatStates.bold = document.queryCommandState('bold');
            this.formatStates.italic = document.queryCommandState('italic');
            this.formatStates.underline = document.queryCommandState('underline');
            this.formatStates.strikeThrough = document.queryCommandState('strikeThrough');

            // Detectar heading actual
            const selection = window.getSelection();
            if (selection && selection.rangeCount > 0) {
                const parentElement = selection.getRangeAt(0).commonAncestorContainer;
                const heading = parentElement.nodeType === Node.TEXT_NODE
                    ? parentElement.parentElement
                    : parentElement;

                const tagName = heading?.closest('h1, h2, h3, h4, h5, h6, p')?.tagName?.toLowerCase();
                this.currentHeading = tagName || 'p';
            }
        },

        /**
         * Verifica si un formato está activo
         */
        isFormatActive(format) {
            return this.formatStates[format] || false;
        },

        /**
         * Aplica formato de texto
         */
        formatText(command) {
            document.execCommand(command, false, null);
            this.updateFormatStates();
            this.saveChanges();
        },

        /**
         * Formatea como heading
         */
        formatHeading(level) {
            document.execCommand('formatBlock', false, level);
            this.currentHeading = level;
            this.saveChanges();
        },

        /**
         * Inserta un enlace
         */
        insertLink() {
            const selection = window.getSelection();
            if (!selection || selection.isCollapsed) {
                alert('Selecciona texto para crear un enlace');
                return;
            }

            const url = prompt('Introduce la URL:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
                this.saveChanges();
            }
        },

        /**
         * Elimina un enlace
         */
        removeLink() {
            document.execCommand('unlink', false, null);
            this.saveChanges();
        },

        /**
         * Limpia el formato
         */
        clearFormatting() {
            document.execCommand('removeFormat', false, null);
            this.updateFormatStates();
            this.saveChanges();
        },

        /**
         * Guarda los cambios en el elemento
         */
        saveChanges() {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);
            const editableElement = range.commonAncestorContainer.nodeType === Node.TEXT_NODE
                ? range.commonAncestorContainer.parentElement?.closest('[contenteditable="true"]')
                : range.commonAncestorContainer.closest?.('[contenteditable="true"]');

            if (!editableElement) return;

            // Buscar el elemento del builder
            const vbpElement = editableElement.closest('.vbp-element');
            if (!vbpElement) return;

            const elementId = vbpElement.dataset.elementId;
            if (!elementId) return;

            // Obtener el campo que se está editando
            const campo = editableElement.dataset.field || 'text';
            const contenido = editableElement.innerHTML;

            // Actualizar el elemento en el store
            const store = Alpine.store('vbp');
            const elemento = store.getElement(elementId);

            if (elemento) {
                const datosActualizados = { ...elemento.data };
                datosActualizados[campo] = contenido;

                store.updateElement(elementId, { data: datosActualizados });
            }
        },

        /**
         * Maneja atajos de teclado de formato
         */
        handleKeydown(event) {
            const isCtrl = event.ctrlKey || event.metaKey;

            if (isCtrl) {
                switch (event.key.toLowerCase()) {
                    case 'b':
                        event.preventDefault();
                        this.formatText('bold');
                        break;
                    case 'i':
                        event.preventDefault();
                        this.formatText('italic');
                        break;
                    case 'u':
                        event.preventDefault();
                        this.formatText('underline');
                        break;
                    case 'k':
                        event.preventDefault();
                        this.insertLink();
                        break;
                }
            }
        }
    }));
});

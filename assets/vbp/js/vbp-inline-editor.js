/**
 * Visual Builder Pro - Inline Editor
 * Sistema WYSIWYG de edición directa en el canvas
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * Sistema de edición inline
     */
    window.vbpInlineEditor = {
        // Elemento actualmente en edición
        activeElement: null,
        activeElementId: null,
        originalContent: null,

        // Toolbar flotante
        toolbar: null,
        toolbarVisible: false,

        // Configuración
        config: {
            editableTypes: ['text', 'heading', 'paragraph', 'button', 'link', 'list-item'],
            doubleClickDelay: 300,
            saveDebounce: 500
        },

        // Timers
        saveTimer: null,
        clickTimer: null,
        clickCount: 0,

        /**
         * Inicializar sistema de edición inline
         */
        init: function() {
            var self = this;

            // Crear toolbar flotante
            this.createToolbar();

            // Escuchar doble clic en elementos editables
            document.addEventListener('dblclick', function(e) {
                self.handleDoubleClick(e);
            });

            // Escuchar clicks fuera para cerrar edición
            document.addEventListener('click', function(e) {
                if (self.activeElement && !self.isInsideEditor(e.target)) {
                    self.finishEditing();
                }
            });

            // Escuchar Escape para cancelar edición
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.activeElement) {
                    self.cancelEditing();
                }
                // Enter + Shift para finalizar
                if (e.key === 'Enter' && !e.shiftKey && self.activeElement) {
                    var type = self.getElementType(self.activeElementId);
                    if (type === 'heading' || type === 'button') {
                        e.preventDefault();
                        self.finishEditing();
                    }
                }
            });

            // Escuchar cambios de selección para toolbar
            document.addEventListener('selectionchange', function() {
                if (self.activeElement) {
                    self.updateToolbarPosition();
                }
            });

            console.log('[VBP InlineEditor] Sistema de edición inline inicializado');
        },

        /**
         * Crear toolbar flotante
         */
        createToolbar: function() {
            if (this.toolbar) return;

            this.toolbar = document.createElement('div');
            this.toolbar.className = 'vbp-inline-toolbar';
            this.toolbar.innerHTML = this.getToolbarHTML();
            this.toolbar.style.display = 'none';
            document.body.appendChild(this.toolbar);

            // Event listeners para botones de toolbar
            this.attachToolbarEvents();
        },

        /**
         * HTML de la toolbar
         */
        getToolbarHTML: function() {
            return '<div class="vbp-inline-toolbar__inner">' +
                '<button type="button" class="vbp-inline-toolbar__btn" data-command="bold" title="Negrita (Ctrl+B)">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>' +
                '</button>' +
                '<button type="button" class="vbp-inline-toolbar__btn" data-command="italic" title="Cursiva (Ctrl+I)">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>' +
                '</button>' +
                '<button type="button" class="vbp-inline-toolbar__btn" data-command="underline" title="Subrayado (Ctrl+U)">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>' +
                '</button>' +
                '<span class="vbp-inline-toolbar__divider"></span>' +
                '<button type="button" class="vbp-inline-toolbar__btn" data-command="link" title="Enlace (Ctrl+K)">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>' +
                '</button>' +
                '<button type="button" class="vbp-inline-toolbar__btn" data-command="removeFormat" title="Quitar formato">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="4" x2="20" y2="20"/><path d="M6 14l4-9h1"/><path d="M14 4l-1.5 3.5"/></svg>' +
                '</button>' +
                '<span class="vbp-inline-toolbar__divider"></span>' +
                '<button type="button" class="vbp-inline-toolbar__btn vbp-inline-toolbar__btn--done" data-command="done" title="Finalizar edición (Esc)">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' +
                '</button>' +
            '</div>';
        },

        /**
         * Adjuntar eventos a toolbar
         */
        attachToolbarEvents: function() {
            var self = this;

            this.toolbar.addEventListener('mousedown', function(e) {
                e.preventDefault(); // Evitar perder foco del editor
            });

            this.toolbar.querySelectorAll('.vbp-inline-toolbar__btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var command = btn.dataset.command;
                    self.executeCommand(command);
                });
            });
        },

        /**
         * Manejar doble clic
         */
        handleDoubleClick: function(e) {
            var elementWrapper = e.target.closest('.vbp-element');
            if (!elementWrapper) return;

            var elementId = elementWrapper.dataset.elementId;
            var elementType = this.getElementType(elementId);

            // Verificar si es un tipo editable
            if (!this.config.editableTypes.includes(elementType)) return;

            // Verificar que no está bloqueado
            if (elementWrapper.classList.contains('vbp-element-locked')) return;

            e.preventDefault();
            e.stopPropagation();

            this.startEditing(elementWrapper, elementId);
        },

        /**
         * Obtener tipo de elemento desde el store
         */
        getElementType: function(elementId) {
            if (typeof Alpine !== 'undefined' && Alpine.store) {
                var store = Alpine.store('vbp');
                if (store && typeof store.getElementById === 'function') {
                    var element = store.getElementById(elementId);
                    return element ? element.type : null;
                }
            }
            return null;
        },

        /**
         * Iniciar edición
         */
        startEditing: function(elementWrapper, elementId) {
            // Si ya estamos editando otro elemento, finalizar primero
            if (this.activeElement && this.activeElementId !== elementId) {
                this.finishEditing();
            }

            var contentArea = elementWrapper.querySelector('.vbp-element-content');
            if (!contentArea) return;

            // Encontrar el elemento de texto dentro
            var textElement = contentArea.querySelector('h1, h2, h3, h4, h5, h6, p, span, a, button, li') ||
                             contentArea;

            // Guardar estado original
            this.activeElement = textElement;
            this.activeElementId = elementId;
            this.originalContent = textElement.innerHTML;

            // Hacer editable
            textElement.contentEditable = 'true';
            textElement.classList.add('vbp-inline-editing');
            elementWrapper.classList.add('vbp-element--editing');

            // Enfocar y seleccionar todo
            textElement.focus();

            // Seleccionar contenido
            var range = document.createRange();
            range.selectNodeContents(textElement);
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            // Mostrar toolbar
            this.showToolbar();

            // Escuchar cambios
            var self = this;
            textElement.addEventListener('input', function() {
                self.handleInput();
            });

            // Notificar que se inició edición
            document.dispatchEvent(new CustomEvent('vbp:inline:start', {
                detail: { elementId: elementId }
            }));
        },

        /**
         * Finalizar edición y guardar
         */
        finishEditing: function() {
            if (!this.activeElement) return;

            var newContent = this.activeElement.innerHTML;
            var elementId = this.activeElementId;

            // Quitar editable
            this.activeElement.contentEditable = 'false';
            this.activeElement.classList.remove('vbp-inline-editing');

            var wrapper = this.activeElement.closest('.vbp-element');
            if (wrapper) {
                wrapper.classList.remove('vbp-element--editing');
            }

            // Guardar cambios en el store
            if (newContent !== this.originalContent) {
                this.saveContent(elementId, newContent);
            }

            // Ocultar toolbar
            this.hideToolbar();

            // Notificar que se finalizó edición
            document.dispatchEvent(new CustomEvent('vbp:inline:end', {
                detail: {
                    elementId: elementId,
                    saved: newContent !== this.originalContent
                }
            }));

            // Limpiar estado
            this.activeElement = null;
            this.activeElementId = null;
            this.originalContent = null;
        },

        /**
         * Cancelar edición
         */
        cancelEditing: function() {
            if (!this.activeElement) return;

            // Restaurar contenido original
            this.activeElement.innerHTML = this.originalContent;

            // Quitar editable
            this.activeElement.contentEditable = 'false';
            this.activeElement.classList.remove('vbp-inline-editing');

            var wrapper = this.activeElement.closest('.vbp-element');
            if (wrapper) {
                wrapper.classList.remove('vbp-element--editing');
            }

            // Ocultar toolbar
            this.hideToolbar();

            // Notificar
            document.dispatchEvent(new CustomEvent('vbp:inline:cancel', {
                detail: { elementId: this.activeElementId }
            }));

            // Limpiar estado
            this.activeElement = null;
            this.activeElementId = null;
            this.originalContent = null;
        },

        /**
         * Manejar input (debounced save)
         */
        handleInput: function() {
            var self = this;

            // Cancelar save pendiente
            if (this.saveTimer) {
                clearTimeout(this.saveTimer);
            }

            // Programar save con debounce
            this.saveTimer = setTimeout(function() {
                if (self.activeElement && self.activeElementId) {
                    self.saveContent(self.activeElementId, self.activeElement.innerHTML);
                }
            }, this.config.saveDebounce);
        },

        /**
         * Guardar contenido en el store
         */
        saveContent: function(elementId, content) {
            if (typeof Alpine !== 'undefined' && Alpine.store) {
                var store = Alpine.store('vbp');
                if (store && typeof store.updateElementData === 'function') {
                    store.updateElementData(elementId, { content: content });

                    // Mostrar indicador de guardado
                    this.showSaveIndicator();
                }
            }
        },

        /**
         * Mostrar indicador de guardado
         */
        showSaveIndicator: function() {
            var indicator = document.getElementById('vbp-save-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'vbp-save-indicator';
                indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: rgba(34, 197, 94, 0.9); color: #fff; border-radius: 6px; font-size: 12px; font-weight: 500; z-index: 10000; pointer-events: none; opacity: 0; transition: opacity 0.2s;';
                document.body.appendChild(indicator);
            }

            indicator.textContent = '✓ Guardado';
            indicator.style.opacity = '1';

            setTimeout(function() {
                indicator.style.opacity = '0';
            }, 1500);
        },

        /**
         * Mostrar toolbar flotante
         */
        showToolbar: function() {
            if (!this.toolbar) return;

            this.toolbar.style.display = 'block';
            this.toolbarVisible = true;
            this.updateToolbarPosition();

            // Animación de entrada
            requestAnimationFrame(function() {
                this.toolbar.classList.add('vbp-inline-toolbar--visible');
            }.bind(this));
        },

        /**
         * Ocultar toolbar
         */
        hideToolbar: function() {
            if (!this.toolbar) return;

            this.toolbar.classList.remove('vbp-inline-toolbar--visible');
            this.toolbarVisible = false;

            setTimeout(function() {
                this.toolbar.style.display = 'none';
            }.bind(this), 200);
        },

        /**
         * Actualizar posición de toolbar
         */
        updateToolbarPosition: function() {
            if (!this.toolbar || !this.activeElement) return;

            var selection = window.getSelection();
            if (!selection.rangeCount) {
                // Posicionar encima del elemento
                var rect = this.activeElement.getBoundingClientRect();
                this.toolbar.style.top = (rect.top - 50 + window.scrollY) + 'px';
                this.toolbar.style.left = (rect.left + (rect.width / 2)) + 'px';
                return;
            }

            var range = selection.getRangeAt(0);
            var rect = range.getBoundingClientRect();

            if (rect.width === 0) {
                // Sin selección, posicionar encima del elemento
                rect = this.activeElement.getBoundingClientRect();
            }

            var toolbarRect = this.toolbar.getBoundingClientRect();
            var left = rect.left + (rect.width / 2) - (toolbarRect.width / 2);
            var top = rect.top - toolbarRect.height - 10 + window.scrollY;

            // Asegurar que no se sale de la pantalla
            left = Math.max(10, Math.min(left, window.innerWidth - toolbarRect.width - 10));
            top = Math.max(10, top);

            this.toolbar.style.left = left + 'px';
            this.toolbar.style.top = top + 'px';
        },

        /**
         * Ejecutar comando de formato
         */
        executeCommand: function(command) {
            if (!this.activeElement) return;

            switch (command) {
                case 'bold':
                    document.execCommand('bold', false, null);
                    break;
                case 'italic':
                    document.execCommand('italic', false, null);
                    break;
                case 'underline':
                    document.execCommand('underline', false, null);
                    break;
                case 'link':
                    this.insertLink();
                    break;
                case 'removeFormat':
                    document.execCommand('removeFormat', false, null);
                    break;
                case 'done':
                    this.finishEditing();
                    return;
            }

            // Actualizar estado de botones
            this.updateToolbarState();

            // Trigger save
            this.handleInput();
        },

        /**
         * Insertar enlace
         */
        insertLink: function() {
            var selection = window.getSelection();
            if (!selection.rangeCount) return;

            var selectedText = selection.toString();
            var currentUrl = '';

            // Verificar si ya hay un enlace seleccionado
            var parentLink = selection.anchorNode.parentElement;
            if (parentLink && parentLink.tagName === 'A') {
                currentUrl = parentLink.href;
            }

            var url = prompt('URL del enlace:', currentUrl || 'https://');

            if (url !== null) {
                if (url === '') {
                    // Quitar enlace
                    document.execCommand('unlink', false, null);
                } else {
                    document.execCommand('createLink', false, url);
                }
                this.handleInput();
            }
        },

        /**
         * Actualizar estado de botones de toolbar
         */
        updateToolbarState: function() {
            var self = this;

            ['bold', 'italic', 'underline'].forEach(function(command) {
                var btn = self.toolbar.querySelector('[data-command="' + command + '"]');
                if (btn) {
                    if (document.queryCommandState(command)) {
                        btn.classList.add('vbp-inline-toolbar__btn--active');
                    } else {
                        btn.classList.remove('vbp-inline-toolbar__btn--active');
                    }
                }
            });
        },

        /**
         * Verificar si el target está dentro del editor o toolbar
         */
        isInsideEditor: function(target) {
            if (!this.activeElement) return false;

            // Verificar si está en el elemento activo
            if (this.activeElement.contains(target)) return true;

            // Verificar si está en el wrapper del elemento
            var wrapper = this.activeElement.closest('.vbp-element');
            if (wrapper && wrapper.contains(target)) return true;

            // Verificar si está en la toolbar
            if (this.toolbar && this.toolbar.contains(target)) return true;

            return false;
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.vbpInlineEditor.init();
        });
    } else {
        window.vbpInlineEditor.init();
    }

})();

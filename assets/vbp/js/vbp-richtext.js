/**
 * Visual Builder Pro - Editor de Texto Enriquecido
 *
 * Componentes Alpine para edición rich text
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Componente Alpine para editor con toolbar fija + flotante
 * Incluye atajos de teclado y Markdown inline
 */
function vbpToolbarEditor() {
    return {
        content: '',
        field: '',
        isFocused: false,
        showFloatingToolbar: false,
        floatingToolbarPosition: { top: 0, left: 0 },
        history: [],
        historyIndex: -1,
        maxHistory: 50,
        lastSavedContent: '',

        init: function() {
            var self = this;

            // Observar cambios de selección para toolbar flotante
            document.addEventListener('selectionchange', function() {
                if (self.isFocused) {
                    self.handleSelectionChange();
                }
            });
        },

        // ============================================
        // ATAJOS DE TECLADO
        // ============================================
        handleKeydown: function(event) {
            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            var modKey = isMac ? event.metaKey : event.ctrlKey;

            if (modKey) {
                switch (event.key.toLowerCase()) {
                    case 'b':
                        event.preventDefault();
                        this.toggleBold();
                        break;
                    case 'i':
                        event.preventDefault();
                        this.toggleItalic();
                        break;
                    case 'u':
                        event.preventDefault();
                        this.toggleUnderline();
                        break;
                    case 'k':
                        event.preventDefault();
                        this.insertLink();
                        break;
                    case 'z':
                        if (event.shiftKey) {
                            event.preventDefault();
                            this.redo();
                        } else {
                            event.preventDefault();
                            this.undo();
                        }
                        break;
                    case 'y':
                        event.preventDefault();
                        this.redo();
                        break;
                }
            }

            // Markdown shortcuts
            this.handleMarkdownShortcuts(event);
        },

        // ============================================
        // MARKDOWN INLINE
        // ============================================
        handleMarkdownShortcuts: function(event) {
            if (event.key !== ' ' && event.key !== 'Enter') return;

            var editor = this.$refs.editor;
            var selection = window.getSelection();
            if (!selection.rangeCount) return;

            var range = selection.getRangeAt(0);
            var textNode = range.startContainer;
            if (textNode.nodeType !== Node.TEXT_NODE) return;

            var text = textNode.textContent;
            var cursorPos = range.startOffset;

            // Detectar patrones Markdown
            var patterns = [
                { regex: /\*\*(.+?)\*\*$/, tag: 'strong' },
                { regex: /\*(.+?)\*$/, tag: 'em' },
                { regex: /__(.+?)__$/, tag: 'strong' },
                { regex: /_(.+?)_$/, tag: 'em' },
                { regex: /~~(.+?)~~$/, tag: 'del' },
                { regex: /`(.+?)`$/, tag: 'code' },
            ];

            var beforeCursor = text.substring(0, cursorPos);

            for (var i = 0; i < patterns.length; i++) {
                var pattern = patterns[i];
                var match = beforeCursor.match(pattern.regex);

                if (match) {
                    event.preventDefault();

                    // Crear el elemento formateado
                    var formattedText = match[1];
                    var fullMatch = match[0];
                    var startPos = cursorPos - fullMatch.length;

                    // Reemplazar el texto Markdown con HTML
                    var beforeMatch = text.substring(0, startPos);
                    var afterMatch = text.substring(cursorPos);

                    var newElement = document.createElement(pattern.tag);
                    newElement.textContent = formattedText;

                    // Reconstruir el contenido
                    textNode.textContent = beforeMatch;
                    textNode.parentNode.insertBefore(newElement, textNode.nextSibling);

                    if (afterMatch || event.key === ' ') {
                        var spaceNode = document.createTextNode(event.key === ' ' ? ' ' : '');
                        textNode.parentNode.insertBefore(spaceNode, newElement.nextSibling);

                        if (afterMatch) {
                            var afterNode = document.createTextNode(afterMatch);
                            textNode.parentNode.insertBefore(afterNode, spaceNode.nextSibling);
                        }

                        // Mover cursor después del espacio
                        var newRange = document.createRange();
                        newRange.setStartAfter(spaceNode);
                        newRange.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(newRange);
                    }

                    this.updateContent();
                    this.saveToHistory();
                    return;
                }
            }
        },

        // ============================================
        // TOOLBAR FLOTANTE
        // ============================================
        handleSelectionChange: function() {
            var selection = window.getSelection();

            if (!selection || selection.isCollapsed || !selection.toString().trim()) {
                this.showFloatingToolbar = false;
                return;
            }

            // Verificar que la selección está dentro de nuestro editor
            var editor = this.$refs.editor;
            if (!editor || !editor.contains(selection.anchorNode)) {
                this.showFloatingToolbar = false;
                return;
            }

            // Posicionar toolbar
            var range = selection.getRangeAt(0);
            var rect = range.getBoundingClientRect();
            var editorRect = editor.getBoundingClientRect();

            this.floatingToolbarPosition = {
                top: rect.top - editorRect.top - 45,
                left: rect.left - editorRect.left + (rect.width / 2)
            };

            this.showFloatingToolbar = true;
        },

        hideFloatingToolbar: function() {
            var self = this;
            setTimeout(function() {
                self.showFloatingToolbar = false;
            }, 150);
        },

        // ============================================
        // HISTORIAL (UNDO/REDO)
        // ============================================
        saveToHistory: function() {
            var currentContent = this.$refs.editor ? this.$refs.editor.innerHTML : '';

            // No guardar si es igual al último
            if (this.history.length > 0 && this.history[this.historyIndex] === currentContent) {
                return;
            }

            // Eliminar historia futura si estamos en medio
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            this.history.push(currentContent);
            this.historyIndex = this.history.length - 1;

            // Limitar tamaño del historial
            if (this.history.length > this.maxHistory) {
                this.history.shift();
                this.historyIndex--;
            }
        },

        undo: function() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.$refs.editor.innerHTML = this.history[this.historyIndex];
                this.content = this.history[this.historyIndex];
                this.$dispatch('richtext-change', { content: this.content, field: this.field });
            }
        },

        redo: function() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.$refs.editor.innerHTML = this.history[this.historyIndex];
                this.content = this.history[this.historyIndex];
                this.$dispatch('richtext-change', { content: this.content, field: this.field });
            }
        },

        // ============================================
        // COMANDOS DE FORMATO
        // ============================================
        execCommand: function(command, value) {
            this.$refs.editor.focus();
            document.execCommand(command, false, value || null);
            this.updateContent();
            this.saveToHistory();
        },

        toggleBold: function() {
            this.execCommand('bold');
        },

        toggleItalic: function() {
            this.execCommand('italic');
        },

        toggleUnderline: function() {
            this.execCommand('underline');
        },

        toggleStrike: function() {
            this.execCommand('strikeThrough');
        },

        insertLink: function() {
            var selection = window.getSelection();
            var selectedText = selection.toString();
            var currentUrl = '';

            // Detectar si ya hay un enlace seleccionado
            if (selection.anchorNode && selection.anchorNode.parentElement) {
                var parentLink = selection.anchorNode.parentElement.closest('a');
                if (parentLink) {
                    currentUrl = parentLink.href;
                }
            }

            var url = prompt('URL del enlace:', currentUrl || 'https://');

            if (url === null) return; // Cancelado

            if (url === '') {
                // Eliminar enlace
                this.execCommand('unlink');
            } else {
                this.execCommand('createLink', url);
            }
        },

        removeLink: function() {
            this.execCommand('unlink');
        },

        insertList: function(ordered) {
            this.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList');
        },

        clearFormat: function() {
            this.execCommand('removeFormat');
        },

        // Alineación
        alignLeft: function() {
            this.execCommand('justifyLeft');
        },

        alignCenter: function() {
            this.execCommand('justifyCenter');
        },

        alignRight: function() {
            this.execCommand('justifyRight');
        },

        // ============================================
        // GESTIÓN DE CONTENIDO
        // ============================================
        updateContent: function() {
            if (this.$refs.editor) {
                var newContent = this.$refs.editor.innerHTML;
                // Limpiar tags vacíos
                newContent = newContent.replace(/<br\s*\/?>\s*$/gi, '');
                newContent = newContent.replace(/^<br\s*\/?>$/gi, '');
                this.content = newContent;
                this.$dispatch('richtext-change', { content: this.content, field: this.field });
            }
        },

        setContent: function(html) {
            this.content = html || '';
            if (this.$refs.editor) {
                this.$refs.editor.innerHTML = this.content;
            }
            // Guardar estado inicial en historial
            if (this.history.length === 0) {
                this.saveToHistory();
            }
        },

        handlePaste: function(event) {
            event.preventDefault();

            // Intentar obtener HTML limpio, sino texto plano
            var html = event.clipboardData.getData('text/html');
            var text = event.clipboardData.getData('text/plain');

            if (html) {
                // Limpiar HTML peligroso
                var cleanHtml = this.sanitizeHtml(html);
                document.execCommand('insertHTML', false, cleanHtml);
            } else {
                document.execCommand('insertText', false, text);
            }

            this.updateContent();
            this.saveToHistory();
        },

        sanitizeHtml: function(html) {
            // Crear un elemento temporal para limpiar
            var temp = document.createElement('div');
            temp.innerHTML = html;

            // Eliminar scripts, styles, y atributos peligrosos
            var dangerous = temp.querySelectorAll('script, style, link, meta, iframe, object, embed');
            dangerous.forEach(function(el) { el.remove(); });

            // Eliminar atributos de estilo y eventos
            var allElements = temp.querySelectorAll('*');
            allElements.forEach(function(el) {
                // Mantener solo algunos atributos seguros
                var safeAttrs = ['href', 'src', 'alt', 'title'];
                var attrs = Array.from(el.attributes);
                attrs.forEach(function(attr) {
                    if (safeAttrs.indexOf(attr.name) === -1) {
                        el.removeAttribute(attr.name);
                    }
                });
            });

            return temp.innerHTML;
        },

        handleFocus: function() {
            this.isFocused = true;
            // Guardar estado inicial si es la primera vez
            if (this.history.length === 0 && this.$refs.editor) {
                this.saveToHistory();
            }
        },

        handleBlur: function() {
            var self = this;
            setTimeout(function() {
                self.isFocused = false;
                self.showFloatingToolbar = false;
                self.updateContent();
            }, 200);
        },

        handleInput: function() {
            // Debounce para guardar en historial
            var self = this;
            clearTimeout(this.inputTimeout);
            this.inputTimeout = setTimeout(function() {
                self.saveToHistory();
            }, 500);

            this.updateContent();
        },

        // ============================================
        // UTILIDADES
        // ============================================
        isFormatActive: function(command) {
            return document.queryCommandState(command);
        },

        getWordCount: function() {
            var text = this.$refs.editor ? this.$refs.editor.textContent : '';
            return text.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
        },

        getCharCount: function() {
            var text = this.$refs.editor ? this.$refs.editor.textContent : '';
            return text.length;
        }
    };
}

/**
 * Componente para modal de inserción de enlace mejorado
 */
function vbpLinkModal() {
    return {
        isOpen: false,
        url: '',
        text: '',
        openInNewTab: false,
        onInsert: null,

        open: function(selectedText, currentUrl, callback) {
            this.text = selectedText || '';
            this.url = currentUrl || 'https://';
            this.openInNewTab = false;
            this.onInsert = callback;
            this.isOpen = true;

            var self = this;
            this.$nextTick(function() {
                var urlInput = document.querySelector('.vbp-link-modal-url');
                if (urlInput) urlInput.focus();
            });
        },

        close: function() {
            this.isOpen = false;
            this.url = '';
            this.text = '';
            this.onInsert = null;
        },

        insert: function() {
            if (this.onInsert && this.url) {
                this.onInsert({
                    url: this.url,
                    text: this.text,
                    newTab: this.openInNewTab
                });
            }
            this.close();
        }
    };
}

/**
 * Componente para menciones @ (enlaces internos)
 */
function vbpMentionAutocomplete() {
    return {
        isOpen: false,
        isLoading: false,
        query: '',
        results: [],
        activeIndex: 0,
        position: { top: 0, left: 0 },
        triggerRange: null,
        debounceTimer: null,

        handleKeydown: function(event) {
            // Detectar @ para iniciar mención
            if (event.key === '@' && !this.isOpen) {
                var selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    var range = selection.getRangeAt(0);
                    var textBefore = range.startContainer.textContent.substring(0, range.startOffset);

                    // Solo activar al inicio o después de espacio
                    if (textBefore === '' || textBefore.endsWith(' ') || textBefore.endsWith('\n')) {
                        this.triggerRange = range.cloneRange();
                        var rect = range.getBoundingClientRect();
                        this.position = { top: rect.bottom + 5, left: rect.left };
                        this.open();
                        return false; // Permitir que se escriba el @
                    }
                }
            }

            if (!this.isOpen) return false;

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.activeIndex = Math.min(this.activeIndex + 1, this.results.length - 1);
                    return true;
                case 'ArrowUp':
                    event.preventDefault();
                    this.activeIndex = Math.max(this.activeIndex - 1, 0);
                    return true;
                case 'Enter':
                case 'Tab':
                    if (this.results.length > 0) {
                        event.preventDefault();
                        this.selectResult(this.results[this.activeIndex]);
                        return true;
                    }
                    break;
                case 'Escape':
                    this.close();
                    return true;
                case 'Backspace':
                    if (this.query === '') {
                        this.close();
                    }
                    break;
                case ' ':
                    this.close();
                    break;
            }

            return false;
        },

        handleInput: function(event) {
            if (!this.isOpen) return;

            // Extraer query desde el @
            var self = this;
            this.$nextTick(function() {
                var selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    var range = selection.getRangeAt(0);
                    var text = range.startContainer.textContent || '';
                    var cursor = range.startOffset;

                    // Buscar el @ más cercano hacia atrás
                    var atIndex = text.lastIndexOf('@', cursor - 1);
                    if (atIndex !== -1) {
                        self.query = text.substring(atIndex + 1, cursor);
                        self.searchPosts();
                    } else {
                        self.close();
                    }
                }
            });
        },

        open: function() {
            this.isOpen = true;
            this.query = '';
            this.results = [];
            this.activeIndex = 0;
            this.searchPosts(); // Mostrar resultados iniciales
        },

        close: function() {
            this.isOpen = false;
            this.query = '';
            this.results = [];
        },

        searchPosts: function() {
            var self = this;

            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            this.debounceTimer = setTimeout(function() {
                self.isLoading = true;

                var searchParam = self.query ? '?search=' + encodeURIComponent(self.query) : '';

                fetch(VBP_Config.restUrl + 'search-posts' + searchParam, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': VBP_Config.restNonce
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    self.results = (data.results || []).slice(0, 8);
                    self.activeIndex = 0;
                    self.isLoading = false;
                })
                .catch(function() {
                    self.results = [];
                    self.isLoading = false;
                });
            }, 150);
        },

        selectResult: function(result) {
            var selection = window.getSelection();
            if (selection.rangeCount === 0) return;

            var range = selection.getRangeAt(0);
            var text = range.startContainer.textContent || '';
            var cursor = range.startOffset;

            // Encontrar el @ y eliminar la mención escrita
            var atIndex = text.lastIndexOf('@', cursor - 1);
            if (atIndex !== -1) {
                // Crear el enlace
                var link = document.createElement('a');
                link.href = result.url;
                link.textContent = result.title;
                link.setAttribute('data-internal-link', result.id);

                // Reemplazar el texto @query con el enlace
                var before = text.substring(0, atIndex);
                var after = text.substring(cursor);

                range.startContainer.textContent = before;

                var afterNode = document.createTextNode(' ' + after);
                range.startContainer.parentNode.insertBefore(link, range.startContainer.nextSibling);
                range.startContainer.parentNode.insertBefore(afterNode, link.nextSibling);

                // Mover cursor después del enlace
                var newRange = document.createRange();
                newRange.setStartAfter(afterNode);
                newRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(newRange);
            }

            this.close();
            this.$dispatch('mention-inserted', { result: result });
        },

        getTypeIcon: function(type) {
            var icons = {
                'post': '📝',
                'page': '📄',
                'flavor_landing': '🎯',
                'category': '📁'
            };
            return icons[type] || '📄';
        }
    };
}

/**
 * Componente para comando slash (/)
 */
function vbpSlashCommand() {
    return {
        isOpen: false,
        query: '',
        commands: [
            { id: 'h1', label: 'Encabezado 1', icon: 'H1', action: 'heading', value: 'h1' },
            { id: 'h2', label: 'Encabezado 2', icon: 'H2', action: 'heading', value: 'h2' },
            { id: 'h3', label: 'Encabezado 3', icon: 'H3', action: 'heading', value: 'h3' },
            { id: 'ul', label: 'Lista con viñetas', icon: '•', action: 'list', value: false },
            { id: 'ol', label: 'Lista numerada', icon: '1.', action: 'list', value: true },
            { id: 'quote', label: 'Cita', icon: '"', action: 'quote' },
            { id: 'code', label: 'Código', icon: '</>', action: 'code' },
            { id: 'hr', label: 'Separador', icon: '—', action: 'hr' },
            { id: 'link', label: 'Enlace', icon: '🔗', action: 'link' },
        ],
        activeIndex: 0,
        position: { top: 0, left: 0 },

        get filteredCommands() {
            if (!this.query) return this.commands;
            var q = this.query.toLowerCase();
            return this.commands.filter(function(cmd) {
                return cmd.label.toLowerCase().indexOf(q) !== -1 || cmd.id.indexOf(q) !== -1;
            });
        },

        handleKeydown: function(event) {
            if (event.key === '/' && !this.isOpen) {
                // Detectar si estamos al inicio de una línea o después de un espacio
                var selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    var range = selection.getRangeAt(0);
                    var textBefore = range.startContainer.textContent.substring(0, range.startOffset);

                    if (textBefore === '' || textBefore.endsWith(' ') || textBefore.endsWith('\n')) {
                        this.open();
                        return true;
                    }
                }
            }

            if (!this.isOpen) return false;

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.activeIndex = Math.min(this.activeIndex + 1, this.filteredCommands.length - 1);
                    return true;
                case 'ArrowUp':
                    event.preventDefault();
                    this.activeIndex = Math.max(this.activeIndex - 1, 0);
                    return true;
                case 'Enter':
                    event.preventDefault();
                    this.executeCommand(this.filteredCommands[this.activeIndex]);
                    return true;
                case 'Escape':
                    this.close();
                    return true;
                case 'Backspace':
                    if (this.query === '') {
                        this.close();
                        return true;
                    }
                    break;
            }

            return false;
        },

        open: function() {
            this.isOpen = true;
            this.query = '';
            this.activeIndex = 0;
            // Posicionar cerca del cursor
            var selection = window.getSelection();
            if (selection.rangeCount > 0) {
                var rect = selection.getRangeAt(0).getBoundingClientRect();
                this.position = { top: rect.bottom + 5, left: rect.left };
            }
        },

        close: function() {
            this.isOpen = false;
            this.query = '';
        },

        executeCommand: function(cmd) {
            this.close();
            // Eliminar el "/" del texto
            document.execCommand('delete', false);

            // Ejecutar la acción
            this.$dispatch('slash-command', { command: cmd });
        }
    };
}

// Registrar componentes globalmente
window.vbpToolbarEditor = vbpToolbarEditor;
window.vbpLinkModal = vbpLinkModal;
window.vbpSlashCommand = vbpSlashCommand;
window.vbpMentionAutocomplete = vbpMentionAutocomplete;

/**
 * Registrar componentes en Alpine
 */
function registerRichtextComponents() {
    if (typeof Alpine === 'undefined') return false;
    Alpine.data('vbpToolbarEditor', vbpToolbarEditor);
    Alpine.data('vbpLinkModal', vbpLinkModal);
    Alpine.data('vbpSlashCommand', vbpSlashCommand);
    Alpine.data('vbpMentionAutocomplete', vbpMentionAutocomplete);
    return true;
}

// Registrar inmediatamente si Alpine ya existe
if (typeof Alpine !== 'undefined') {
    registerRichtextComponents();
}

// También escuchar el evento por si Alpine se carga después
document.addEventListener('alpine:init', function() {
    registerRichtextComponents();
});

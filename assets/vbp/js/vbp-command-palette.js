/**
 * Visual Builder Pro - Paleta de Comandos
 *
 * Búsqueda rápida de acciones, bloques y configuraciones
 * Acceso: Ctrl+/ o Ctrl+K
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.store('vbpCommandPalette', {
        isOpen: false,
        query: '',
        activeIndex: 0,
        recentCommands: [],
        maxRecent: 5,

        // Categorías de comandos
        commands: [
            // Bloques básicos
            { id: 'add-text', label: 'Añadir Texto', category: 'bloques', icon: 'T', action: 'addBlock', value: 'text' },
            { id: 'add-heading', label: 'Añadir Encabezado', category: 'bloques', icon: 'H', action: 'addBlock', value: 'heading' },
            { id: 'add-image', label: 'Añadir Imagen', category: 'bloques', icon: '🖼', action: 'addBlock', value: 'image' },
            { id: 'add-button', label: 'Añadir Botón', category: 'bloques', icon: '▢', action: 'addBlock', value: 'button' },
            { id: 'add-divider', label: 'Añadir Divisor', category: 'bloques', icon: '—', action: 'addBlock', value: 'divider' },
            { id: 'add-spacer', label: 'Añadir Espaciador', category: 'bloques', icon: '↕', action: 'addBlock', value: 'spacer' },

            // Secciones
            { id: 'add-hero', label: 'Añadir Hero', category: 'secciones', icon: '🎯', action: 'addBlock', value: 'hero' },
            { id: 'add-features', label: 'Añadir Características', category: 'secciones', icon: '⚡', action: 'addBlock', value: 'features' },
            { id: 'add-testimonials', label: 'Añadir Testimonios', category: 'secciones', icon: '💬', action: 'addBlock', value: 'testimonials' },
            { id: 'add-pricing', label: 'Añadir Precios', category: 'secciones', icon: '💰', action: 'addBlock', value: 'pricing' },
            { id: 'add-cta', label: 'Añadir CTA', category: 'secciones', icon: '📢', action: 'addBlock', value: 'cta' },
            { id: 'add-faq', label: 'Añadir FAQ', category: 'secciones', icon: '❓', action: 'addBlock', value: 'faq' },
            { id: 'add-contact', label: 'Añadir Contacto', category: 'secciones', icon: '✉', action: 'addBlock', value: 'contact' },
            { id: 'add-gallery', label: 'Añadir Galería', category: 'secciones', icon: '🖼', action: 'addBlock', value: 'gallery' },

            // Layout
            { id: 'add-container', label: 'Añadir Contenedor', category: 'layout', icon: '☐', action: 'addBlock', value: 'container' },
            { id: 'add-columns', label: 'Añadir Columnas', category: 'layout', icon: '▥', action: 'addBlock', value: 'columns' },
            { id: 'add-row', label: 'Añadir Fila', category: 'layout', icon: '▤', action: 'addBlock', value: 'row' },

            // Acciones
            { id: 'save', label: 'Guardar', category: 'acciones', icon: '💾', action: 'save', shortcut: 'Ctrl+S' },
            { id: 'preview', label: 'Vista previa', category: 'acciones', icon: '👁', action: 'preview', shortcut: 'Ctrl+P' },
            { id: 'undo', label: 'Deshacer', category: 'acciones', icon: '↩', action: 'undo', shortcut: 'Ctrl+Z' },
            { id: 'redo', label: 'Rehacer', category: 'acciones', icon: '↪', action: 'redo', shortcut: 'Ctrl+Y' },
            { id: 'duplicate', label: 'Duplicar elemento', category: 'acciones', icon: '⧉', action: 'duplicate', shortcut: 'Ctrl+D' },
            { id: 'delete', label: 'Eliminar elemento', category: 'acciones', icon: '🗑', action: 'delete', shortcut: 'Del' },
            { id: 'copy-styles', label: 'Copiar estilos', category: 'acciones', icon: '📋', action: 'copyStyles', shortcut: 'Ctrl+Shift+C' },
            { id: 'paste-styles', label: 'Pegar estilos', category: 'acciones', icon: '📥', action: 'pasteStyles', shortcut: 'Ctrl+Shift+V' },
            { id: 'reset-styles', label: 'Resetear estilos', category: 'acciones', icon: '🔄', action: 'resetStyles', shortcut: 'Ctrl+Shift+R' },
            { id: 'group', label: 'Agrupar elementos', category: 'acciones', icon: '📦', action: 'group', shortcut: 'Ctrl+G' },
            { id: 'ungroup', label: 'Desagrupar elementos', category: 'acciones', icon: '📤', action: 'ungroup', shortcut: 'Ctrl+Shift+U' },
            { id: 'edit-inline', label: 'Editar texto', category: 'acciones', icon: '✏️', action: 'editInline', shortcut: 'Enter' },
            { id: 'toggle-lock', label: 'Bloquear/Desbloquear elemento', category: 'acciones', icon: '🔒', action: 'toggleLock', shortcut: 'Ctrl+Shift+L' },

            // Alineación y distribución
            { id: 'align-left', label: 'Alinear a la izquierda', category: 'alineacion', icon: '⬅', action: 'alignElements', value: 'left', shortcut: 'Alt+L' },
            { id: 'align-center-h', label: 'Centrar horizontalmente', category: 'alineacion', icon: '↔', action: 'alignElements', value: 'centerH', shortcut: 'Alt+C' },
            { id: 'align-right', label: 'Alinear a la derecha', category: 'alineacion', icon: '➡', action: 'alignElements', value: 'right', shortcut: 'Alt+R' },
            { id: 'align-top', label: 'Alinear arriba', category: 'alineacion', icon: '⬆', action: 'alignElements', value: 'top', shortcut: 'Alt+T' },
            { id: 'align-center-v', label: 'Centrar verticalmente', category: 'alineacion', icon: '↕', action: 'alignElements', value: 'centerV', shortcut: 'Alt+M' },
            { id: 'align-bottom', label: 'Alinear abajo', category: 'alineacion', icon: '⬇', action: 'alignElements', value: 'bottom', shortcut: 'Alt+B' },
            { id: 'distribute-h', label: 'Distribuir horizontalmente', category: 'alineacion', icon: '⇔', action: 'distributeElements', value: 'horizontal', shortcut: 'Ctrl+Alt+H' },
            { id: 'distribute-v', label: 'Distribuir verticalmente', category: 'alineacion', icon: '⇕', action: 'distributeElements', value: 'vertical', shortcut: 'Ctrl+Alt+V' },

            // Vista
            { id: 'toggle-grid', label: 'Mostrar/Ocultar Cuadrícula', category: 'vista', icon: '⊞', action: 'toggleGrid', shortcut: 'Ctrl+\'' },
            { id: 'toggle-guides', label: 'Mostrar/Ocultar Guías', category: 'vista', icon: '📏', action: 'toggleGuides', shortcut: 'Ctrl+;' },
            { id: 'toggle-layers', label: 'Mostrar/Ocultar Capas', category: 'vista', icon: '📑', action: 'togglePanel', value: 'layers' },
            { id: 'toggle-inspector', label: 'Mostrar/Ocultar Inspector', category: 'vista', icon: '⚙', action: 'togglePanel', value: 'inspector' },
            { id: 'toggle-blocks', label: 'Mostrar/Ocultar Bloques', category: 'vista', icon: '🧱', action: 'togglePanel', value: 'blocks' },
            { id: 'zoom-in', label: 'Acercar', category: 'vista', icon: '🔍+', action: 'zoom', value: 'in', shortcut: 'Ctrl++' },
            { id: 'zoom-out', label: 'Alejar', category: 'vista', icon: '🔍-', action: 'zoom', value: 'out', shortcut: 'Ctrl+-' },
            { id: 'zoom-fit', label: 'Ajustar a pantalla', category: 'vista', icon: '⬜', action: 'zoom', value: 'fit', shortcut: 'Ctrl+0' },
            { id: 'zoom-100', label: 'Zoom 100%', category: 'vista', icon: '1️⃣', action: 'zoom', value: '100', shortcut: 'Ctrl+1' },
            { id: 'zoom-50', label: 'Zoom 50%', category: 'vista', icon: '5️⃣', action: 'zoom', value: '50', shortcut: 'Ctrl+5' },
            { id: 'zoom-200', label: 'Zoom 200%', category: 'vista', icon: '2️⃣', action: 'zoom', value: '200', shortcut: 'Ctrl+2' },

            // Responsive
            { id: 'device-desktop', label: 'Vista Escritorio', category: 'responsive', icon: '🖥', action: 'device', value: 'desktop' },
            { id: 'device-tablet', label: 'Vista Tablet', category: 'responsive', icon: '📱', action: 'device', value: 'tablet' },
            { id: 'device-mobile', label: 'Vista Móvil', category: 'responsive', icon: '📱', action: 'device', value: 'mobile' },
        ],

        get filteredCommands() {
            var self = this;
            if (!this.query) {
                // Mostrar recientes primero, luego los más usados
                var recent = this.recentCommands.map(function(id) {
                    return self.commands.find(function(cmd) { return cmd.id === id; });
                }).filter(Boolean);

                var rest = this.commands.filter(function(cmd) {
                    return self.recentCommands.indexOf(cmd.id) === -1;
                }).slice(0, 10);

                return recent.concat(rest);
            }

            var query = this.query.toLowerCase();
            return this.commands.filter(function(cmd) {
                return cmd.label.toLowerCase().indexOf(query) !== -1 ||
                       cmd.category.toLowerCase().indexOf(query) !== -1 ||
                       (cmd.shortcut && cmd.shortcut.toLowerCase().indexOf(query) !== -1);
            });
        },

        open: function() {
            this.isOpen = true;
            this.query = '';
            this.activeIndex = 0;
            this.loadRecent();

            // Focus en el input después de abrir
            var self = this;
            setTimeout(function() {
                var input = document.querySelector('.vbp-command-input');
                if (input) input.focus();
            }, 50);
        },

        close: function() {
            this.isOpen = false;
            this.query = '';
        },

        toggle: function() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        handleKeydown: function(event) {
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.activeIndex = Math.min(this.activeIndex + 1, this.filteredCommands.length - 1);
                    this.scrollToActive();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.activeIndex = Math.max(this.activeIndex - 1, 0);
                    this.scrollToActive();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.filteredCommands[this.activeIndex]) {
                        this.executeCommand(this.filteredCommands[this.activeIndex]);
                    }
                    break;
                case 'Escape':
                    this.close();
                    break;
            }
        },

        scrollToActive: function() {
            var container = document.querySelector('.vbp-command-results');
            var activeItem = container ? container.querySelector('.active') : null;
            if (activeItem && container) {
                activeItem.scrollIntoView({ block: 'nearest' });
            }
        },

        executeCommand: function(cmd) {
            this.addToRecent(cmd.id);
            this.close();

            var store = Alpine.store('vbp');

            switch (cmd.action) {
                case 'addBlock':
                    if (typeof store.addElement === 'function') {
                        store.addElement(cmd.value);
                    }
                    break;

                case 'save':
                    if (typeof store.save === 'function') {
                        store.save();
                    }
                    break;

                case 'preview':
                    Alpine.store('vbpPreview').toggle();
                    break;

                case 'undo':
                    if (typeof store.undo === 'function') {
                        store.undo();
                    }
                    break;

                case 'redo':
                    if (typeof store.redo === 'function') {
                        store.redo();
                    }
                    break;

                case 'duplicate':
                    if (store.selection.elementIds.length === 1) {
                        store.duplicateElement(store.selection.elementIds[0]);
                    }
                    break;

                case 'delete':
                    if (store.selection.elementIds.length > 0) {
                        store.deleteElements(store.selection.elementIds);
                    }
                    break;

                case 'copyStyles':
                    Alpine.store('vbpClipboard').copyStyles();
                    break;

                case 'pasteStyles':
                    Alpine.store('vbpClipboard').pasteStyles();
                    break;

                case 'resetStyles':
                    // Disparar evento para que vbpKeyboard lo maneje
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'r',
                        ctrlKey: true,
                        shiftKey: true,
                        bubbles: true
                    }));
                    break;

                case 'group':
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'g',
                        ctrlKey: true,
                        bubbles: true
                    }));
                    break;

                case 'ungroup':
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'u',
                        ctrlKey: true,
                        shiftKey: true,
                        bubbles: true
                    }));
                    break;

                case 'editInline':
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'Enter',
                        bubbles: true
                    }));
                    break;

                case 'toggleLock':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleLock' }
                    }));
                    break;

                case 'toggleGrid':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleGrid' }
                    }));
                    break;

                case 'toggleGuides':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleGuides' }
                    }));
                    break;

                case 'alignElements':
                    // Disparar el evento de alineación con el valor correcto
                    var alignActions = {
                        'left': 'alignLeft',
                        'centerH': 'alignCenterH',
                        'right': 'alignRight',
                        'top': 'alignTop',
                        'centerV': 'alignCenterV',
                        'bottom': 'alignBottom'
                    };
                    if (alignActions[cmd.value]) {
                        document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                            detail: { action: alignActions[cmd.value] }
                        }));
                    }
                    break;

                case 'distributeElements':
                    var distributeActions = {
                        'horizontal': 'distributeH',
                        'vertical': 'distributeV'
                    };
                    if (distributeActions[cmd.value]) {
                        document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                            detail: { action: distributeActions[cmd.value] }
                        }));
                    }
                    break;

                case 'togglePanel':
                    this.togglePanel(cmd.value);
                    break;

                case 'zoom':
                    this.handleZoom(cmd.value);
                    break;

                case 'device':
                    if (typeof store.setDevice === 'function') {
                        store.setDevice(cmd.value);
                    }
                    break;
            }
        },

        togglePanel: function(panel) {
            var store = Alpine.store('vbp');
            if (store.panels && typeof store.panels[panel] !== 'undefined') {
                store.panels[panel] = !store.panels[panel];
            }
        },

        handleZoom: function(direction) {
            var store = Alpine.store('vbp');
            var currentZoom = store.zoom || 100;

            switch (direction) {
                case 'in':
                    store.zoom = Math.min(currentZoom + 10, 200);
                    break;
                case 'out':
                    store.zoom = Math.max(currentZoom - 10, 25);
                    break;
                case 'fit':
                    store.zoom = 100;
                    break;
                case '100':
                    store.zoom = 100;
                    break;
                case '50':
                    store.zoom = 50;
                    break;
                case '200':
                    store.zoom = 200;
                    break;
            }

            // Mostrar feedback de zoom
            this.showZoomFeedback(store.zoom);
        },

        /**
         * Mostrar indicador de zoom
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

        addToRecent: function(commandId) {
            // Eliminar si ya existe
            var index = this.recentCommands.indexOf(commandId);
            if (index !== -1) {
                this.recentCommands.splice(index, 1);
            }

            // Agregar al inicio
            this.recentCommands.unshift(commandId);

            // Limitar cantidad
            if (this.recentCommands.length > this.maxRecent) {
                this.recentCommands = this.recentCommands.slice(0, this.maxRecent);
            }

            this.saveRecent();
        },

        loadRecent: function() {
            var saved = localStorage.getItem('vbp_recent_commands');
            if (saved) {
                try {
                    this.recentCommands = JSON.parse(saved);
                } catch (e) {
                    this.recentCommands = [];
                }
            }
        },

        saveRecent: function() {
            localStorage.setItem('vbp_recent_commands', JSON.stringify(this.recentCommands));
        },

        getCategoryLabel: function(category) {
            var labels = {
                'bloques': 'Bloques',
                'secciones': 'Secciones',
                'layout': 'Layout',
                'acciones': 'Acciones',
                'alineacion': 'Alineación',
                'vista': 'Vista',
                'responsive': 'Responsive'
            };
            return labels[category] || category;
        }
    });

    /**
     * Store para modo preview
     */
    Alpine.store('vbpPreview', {
        isActive: false,

        toggle: function() {
            this.isActive = !this.isActive;

            var editor = document.querySelector('.vbp-editor');
            if (editor) {
                if (this.isActive) {
                    editor.classList.add('preview-mode');
                    this.showExitButton();
                } else {
                    editor.classList.remove('preview-mode');
                    this.hideExitButton();
                }
            }
        },

        exit: function() {
            this.isActive = false;
            var editor = document.querySelector('.vbp-editor');
            if (editor) {
                editor.classList.remove('preview-mode');
            }
            this.hideExitButton();
        },

        showExitButton: function() {
            var btn = document.createElement('button');
            btn.className = 'vbp-preview-exit-btn';
            btn.innerHTML = '✕ Salir de vista previa <small>(Esc)</small>';
            btn.onclick = this.exit.bind(this);
            btn.id = 'vbp-preview-exit';
            document.body.appendChild(btn);
        },

        hideExitButton: function() {
            var btn = document.getElementById('vbp-preview-exit');
            if (btn) btn.remove();
        }
    });
});

/**
 * Atajos de teclado globales
 */
document.addEventListener('keydown', function(event) {
    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var modKey = isMac ? event.metaKey : event.ctrlKey;

    // No capturar si estamos en un input (excepto para Escape)
    var isInput = event.target.tagName === 'INPUT' ||
                  event.target.tagName === 'TEXTAREA' ||
                  event.target.isContentEditable;

    // Escape para salir de preview
    if (event.key === 'Escape') {
        var preview = Alpine.store('vbpPreview');
        if (preview && preview.isActive) {
            preview.exit();
            return;
        }

        var palette = Alpine.store('vbpCommandPalette');
        if (palette && palette.isOpen) {
            palette.close();
            return;
        }
    }

    if (isInput) return;

    // Ctrl+/ o Ctrl+K: Abrir paleta de comandos
    if (modKey && (event.key === '/' || event.key === 'k')) {
        event.preventDefault();
        Alpine.store('vbpCommandPalette').toggle();
        return;
    }

    // Ctrl+P: Preview
    if (modKey && event.key.toLowerCase() === 'p') {
        event.preventDefault();
        Alpine.store('vbpPreview').toggle();
        return;
    }
});

/**
 * Componente Alpine para el modal de la paleta
 */
function vbpCommandPaletteModal() {
    return {
        get store() {
            return Alpine.store('vbpCommandPalette');
        },

        get isOpen() {
            return this.store.isOpen;
        },

        get query() {
            return this.store.query;
        },

        set query(value) {
            this.store.query = value;
            this.store.activeIndex = 0;
        },

        get filteredCommands() {
            return this.store.filteredCommands;
        },

        get activeIndex() {
            return this.store.activeIndex;
        },

        handleKeydown: function(event) {
            this.store.handleKeydown(event);
        },

        executeCommand: function(cmd) {
            this.store.executeCommand(cmd);
        },

        close: function() {
            this.store.close();
        },

        setActive: function(index) {
            this.store.activeIndex = index;
        }
    };
}

window.vbpCommandPaletteModal = vbpCommandPaletteModal;

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

            // Vista
            { id: 'toggle-layers', label: 'Mostrar/Ocultar Capas', category: 'vista', icon: '📑', action: 'togglePanel', value: 'layers' },
            { id: 'toggle-inspector', label: 'Mostrar/Ocultar Inspector', category: 'vista', icon: '⚙', action: 'togglePanel', value: 'inspector' },
            { id: 'toggle-blocks', label: 'Mostrar/Ocultar Bloques', category: 'vista', icon: '🧱', action: 'togglePanel', value: 'blocks' },
            { id: 'zoom-in', label: 'Acercar', category: 'vista', icon: '🔍+', action: 'zoom', value: 'in' },
            { id: 'zoom-out', label: 'Alejar', category: 'vista', icon: '🔍-', action: 'zoom', value: 'out' },
            { id: 'zoom-fit', label: 'Ajustar a pantalla', category: 'vista', icon: '⬜', action: 'zoom', value: 'fit' },

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
            if (!store.canvas) return;

            var currentZoom = store.canvas.zoom || 100;

            switch (direction) {
                case 'in':
                    store.canvas.zoom = Math.min(currentZoom + 10, 200);
                    break;
                case 'out':
                    store.canvas.zoom = Math.max(currentZoom - 10, 25);
                    break;
                case 'fit':
                    store.canvas.zoom = 100;
                    break;
            }
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

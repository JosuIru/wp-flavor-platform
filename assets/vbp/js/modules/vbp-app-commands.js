/**
 * Visual Builder Pro - App Module: Command Palette
 * Paleta de comandos rápidos (Ctrl+K)
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppCommands = {
    // Estado
    showCommandPalette: false,
    commandSearch: '',
    commandIndex: 0,
    filteredCommands: [],

    // Lista de comandos disponibles
    commands: [
        { id: 'save', name: 'Guardar', icon: '💾', action: 'save', shortcut: 'Ctrl+S' },
        { id: 'undo', name: 'Deshacer', icon: '↩️', action: 'undo', shortcut: 'Ctrl+Z' },
        { id: 'redo', name: 'Rehacer', icon: '↪️', action: 'redo', shortcut: 'Ctrl+Shift+Z' },
        { id: 'copy', name: 'Copiar', icon: '📋', action: 'copy', shortcut: 'Ctrl+C' },
        { id: 'paste', name: 'Pegar', icon: '📄', action: 'paste', shortcut: 'Ctrl+V' },
        { id: 'duplicate', name: 'Duplicar', icon: '📑', action: 'duplicate', shortcut: 'Ctrl+D' },
        { id: 'copyStyles', name: 'Copiar estilos', icon: '🎨', action: 'copyStyles', shortcut: 'Ctrl+Alt+C' },
        { id: 'pasteStyles', name: 'Pegar estilos', icon: '🖌️', action: 'pasteStyles', shortcut: 'Ctrl+Alt+V' },
        { id: 'eyedropper', name: 'Cuentagotas de color', icon: '🧪', action: 'eyedropper', shortcut: 'I' },
        { id: 'delete', name: 'Eliminar', icon: '🗑️', action: 'delete', shortcut: 'Delete' },
        { id: 'saveAsGlobal', name: 'Guardar como widget global', icon: '🌍', action: 'saveAsGlobal', shortcut: 'Ctrl+Shift+G' },
        { id: 'selectAll', name: 'Seleccionar todo', icon: '☑️', action: 'selectAll', shortcut: 'Ctrl+A' },
        { id: 'deselect', name: 'Deseleccionar', icon: '⬜', action: 'deselect', shortcut: 'Esc' },
        { id: 'zoomIn', name: 'Acercar', icon: '🔍', action: 'zoomIn', shortcut: 'Ctrl++' },
        { id: 'zoomOut', name: 'Alejar', icon: '🔍', action: 'zoomOut', shortcut: 'Ctrl+-' },
        { id: 'zoomReset', name: 'Zoom 100%', icon: '🔍', action: 'zoomReset', shortcut: 'Ctrl+0' },
        { id: 'preview', name: 'Previsualizar', icon: '👁️', action: 'preview', shortcut: 'Ctrl+P' },
        { id: 'help', name: 'Mostrar ayuda', icon: '❓', action: 'help', shortcut: '?' },
        { id: 'togglePanels', name: 'Toggle paneles', icon: '📐', action: 'togglePanels', shortcut: 'Ctrl+\\' },
        { id: 'addHero', name: 'Añadir Hero', icon: '🦸', action: 'addHero' },
        { id: 'addText', name: 'Añadir Texto', icon: '📝', action: 'addText' },
        { id: 'addImage', name: 'Añadir Imagen', icon: '🖼️', action: 'addImage' },
        { id: 'addButton', name: 'Añadir Botón', icon: '🔘', action: 'addButton' },
        { id: 'templates', name: 'Templates', icon: '📋', action: 'templates', shortcut: 'Ctrl+T' },
        { id: 'export', name: 'Exportar diseño', icon: '📤', action: 'export', shortcut: 'Ctrl+E' },
        { id: 'unsplash', name: 'Buscar en Unsplash', icon: '📷', action: 'unsplash', shortcut: 'Ctrl+U' },
        { id: 'versionHistory', name: 'Historial de versiones', icon: '📜', action: 'versionHistory', shortcut: 'Ctrl+H' }
    ],

    /**
     * Abrir paleta de comandos
     */
    openCommandPalette: function() {
        this.showCommandPalette = true;
        this.commandSearch = '';
        this.commandIndex = 0;
        this.filteredCommands = this.commands.slice();
        var self = this;
        this.$nextTick(function() {
            if (self.$refs.commandInput) {
                self.$refs.commandInput.focus();
            }
        });
    },

    /**
     * Cerrar paleta de comandos
     */
    closeCommandPalette: function() {
        this.showCommandPalette = false;
        this.commandSearch = '';
    },

    /**
     * Filtrar comandos por búsqueda
     */
    filterCommands: function() {
        var search = this.commandSearch.toLowerCase();
        if (!search) {
            this.filteredCommands = this.commands.slice();
        } else {
            this.filteredCommands = this.commands.filter(function(cmd) {
                return cmd.name.toLowerCase().includes(search) || cmd.id.toLowerCase().includes(search);
            });
        }
        this.commandIndex = 0;
    },

    /**
     * Navegar por comandos con teclado
     */
    navigateCommands: function(direction) {
        if (direction === 'up') {
            this.commandIndex = Math.max(0, this.commandIndex - 1);
        } else if (direction === 'down') {
            this.commandIndex = Math.min(this.filteredCommands.length - 1, this.commandIndex + 1);
        }
    },

    /**
     * Ejecutar comando seleccionado
     */
    executeSelectedCommand: function() {
        var cmd = this.filteredCommands[this.commandIndex];
        if (cmd) {
            this.executeCommand(cmd);
        }
    },

    /**
     * Ejecutar un comando
     */
    executeCommand: function(cmd) {
        if (!cmd) return;
        this.showCommandPalette = false;
        var self = this;
        var store = Alpine.store('vbp');

        switch (cmd.action) {
            case 'save':
                this.saveDocument();
                break;
            case 'undo':
                var undoDescription = store.undo();
                if (undoDescription) {
                    this.showNotification('Deshacer: ' + undoDescription, 'info');
                }
                break;
            case 'redo':
                var redoDescription = store.redo();
                if (redoDescription) {
                    this.showNotification('Rehacer: ' + redoDescription, 'info');
                }
                break;
            case 'copy':
                this.copyElement();
                break;
            case 'paste':
                this.pasteElement();
                break;
            case 'copyStyles':
                this.copyStyles();
                break;
            case 'pasteStyles':
                this.pasteStyles();
                break;
            case 'eyedropper':
                this.activateEyedropper();
                break;
            case 'duplicate':
                store.selection.elementIds.forEach(function(id) { store.duplicateElement(id); });
                break;
            case 'delete':
                store.selection.elementIds.forEach(function(id) { store.removeElement(id); });
                break;
            case 'saveAsGlobal':
                this.saveAsGlobalWidget();
                break;
            case 'selectAll':
                store.setSelection(store.elements.map(function(el) { return el.id; }));
                break;
            case 'deselect':
                store.clearSelection();
                break;
            case 'zoomIn':
                this.zoomIn();
                break;
            case 'zoomOut':
                this.zoomOut();
                break;
            case 'zoomReset':
                this.zoom = 100;
                store.zoom = 100;
                break;
            case 'preview':
                if (VBP_Config.previewUrl) {
                    window.open(VBP_Config.previewUrl, '_blank');
                }
                break;
            case 'help':
                this.openHelpModal();
                break;
            case 'togglePanels':
                var allVisible = this.panels.blocks && this.panels.inspector && this.panels.layers;
                this.panels.blocks = !allVisible;
                this.panels.inspector = !allVisible;
                this.panels.layers = !allVisible;
                break;
            case 'addHero':
                store.addElement('hero');
                break;
            case 'addText':
                store.addElement('text');
                break;
            case 'addImage':
                store.addElement('image');
                break;
            case 'addButton':
                store.addElement('button');
                break;
            case 'templates':
                this.openTemplatesModal();
                break;
            case 'export':
                this.showExportModal = true;
                break;
            case 'unsplash':
                this.openUnsplash();
                break;
            case 'versionHistory':
                this.openVersionHistory();
                break;
        }
    },

    /**
     * Manejar teclas en la paleta de comandos
     */
    handleCommandKeydown: function(event) {
        switch (event.key) {
            case 'ArrowUp':
                event.preventDefault();
                this.navigateCommands('up');
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.navigateCommands('down');
                break;
            case 'Enter':
                event.preventDefault();
                this.executeSelectedCommand();
                break;
            case 'Escape':
                this.closeCommandPalette();
                break;
        }
    }
};

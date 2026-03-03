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
            { id: 'invert-selection', label: 'Invertir selección', category: 'acciones', icon: '🔄', action: 'invertSelection', shortcut: 'Ctrl+Shift+A' },
            { id: 'select-similar', label: 'Seleccionar similares', category: 'acciones', icon: '👥', action: 'selectSimilar', shortcut: 'Ctrl+Alt+A' },
            { id: 'fit-content', label: 'Ajustar al contenido', category: 'acciones', icon: '📐', action: 'fitContent', shortcut: 'Ctrl+Shift+F' },
            { id: 'fill-parent', label: 'Llenar contenedor', category: 'acciones', icon: '📏', action: 'fillParent', shortcut: 'Ctrl+Alt+F' },

            // Alineación y distribución
            { id: 'align-left', label: 'Alinear a la izquierda', category: 'alineacion', icon: '⬅', action: 'alignElements', value: 'left', shortcut: 'Alt+L' },
            { id: 'align-center-h', label: 'Centrar horizontalmente', category: 'alineacion', icon: '↔', action: 'alignElements', value: 'centerH', shortcut: 'Alt+C' },
            { id: 'align-right', label: 'Alinear a la derecha', category: 'alineacion', icon: '➡', action: 'alignElements', value: 'right', shortcut: 'Alt+R' },
            { id: 'align-top', label: 'Alinear arriba', category: 'alineacion', icon: '⬆', action: 'alignElements', value: 'top', shortcut: 'Alt+T' },
            { id: 'align-center-v', label: 'Centrar verticalmente', category: 'alineacion', icon: '↕', action: 'alignElements', value: 'centerV', shortcut: 'Alt+M' },
            { id: 'align-bottom', label: 'Alinear abajo', category: 'alineacion', icon: '⬇', action: 'alignElements', value: 'bottom', shortcut: 'Alt+B' },
            { id: 'distribute-h', label: 'Distribuir horizontalmente', category: 'alineacion', icon: '⇔', action: 'distributeElements', value: 'horizontal', shortcut: 'Ctrl+Alt+H' },
            { id: 'distribute-v', label: 'Distribuir verticalmente', category: 'alineacion', icon: '⇕', action: 'distributeElements', value: 'vertical', shortcut: 'Ctrl+Alt+V' },
            { id: 'stack-h', label: 'Apilar horizontalmente', category: 'alineacion', icon: '📚', action: 'stackElements', value: 'horizontal', shortcut: 'Ctrl+Shift+→' },
            { id: 'stack-v', label: 'Apilar verticalmente', category: 'alineacion', icon: '📚', action: 'stackElements', value: 'vertical', shortcut: 'Ctrl+Shift+↓' },

            // Vista
            { id: 'toggle-grid', label: 'Mostrar/Ocultar Cuadrícula', category: 'vista', icon: '⊞', action: 'toggleGrid', shortcut: 'Ctrl+\'' },
            { id: 'toggle-guides', label: 'Mostrar/Ocultar Guías', category: 'vista', icon: '📏', action: 'toggleGuides', shortcut: 'Ctrl+;' },
            { id: 'find-elements', label: 'Buscar elementos', category: 'vista', icon: '🔍', action: 'findElements', shortcut: 'Ctrl+F' },
            { id: 'toggle-visibility', label: 'Ocultar/Mostrar selección', category: 'vista', icon: '👁', action: 'toggleVisibility', shortcut: 'Ctrl+Shift+H' },
            { id: 'hide-others', label: 'Ocultar otros elementos', category: 'vista', icon: '👁‍🗨', action: 'hideOthers', shortcut: 'Ctrl+Alt+H' },
            { id: 'copy-html', label: 'Copiar como HTML', category: 'exportar', icon: '📄', action: 'copyAsHTML', shortcut: 'Ctrl+Shift+E' },
            { id: 'copy-json', label: 'Copiar como JSON', category: 'exportar', icon: '{ }', action: 'copyAsJSON', shortcut: 'Ctrl+Alt+E' },
            { id: 'paste-json', label: 'Pegar desde JSON', category: 'exportar', icon: '📥', action: 'pasteFromJSON', shortcut: 'Ctrl+Alt+V' },
            { id: 'toggle-layers', label: 'Mostrar/Ocultar Capas', category: 'vista', icon: '📑', action: 'togglePanel', value: 'layers' },
            { id: 'toggle-inspector', label: 'Mostrar/Ocultar Inspector', category: 'vista', icon: '⚙', action: 'togglePanel', value: 'inspector' },
            { id: 'toggle-blocks', label: 'Mostrar/Ocultar Bloques', category: 'vista', icon: '🧱', action: 'togglePanel', value: 'blocks' },
            { id: 'zoom-in', label: 'Acercar', category: 'vista', icon: '🔍+', action: 'zoom', value: 'in', shortcut: 'Ctrl++' },
            { id: 'zoom-out', label: 'Alejar', category: 'vista', icon: '🔍-', action: 'zoom', value: 'out', shortcut: 'Ctrl+-' },
            { id: 'zoom-fit', label: 'Ajustar a pantalla', category: 'vista', icon: '⬜', action: 'zoom', value: 'fit', shortcut: 'Ctrl+0' },
            { id: 'zoom-100', label: 'Zoom 100%', category: 'vista', icon: '1️⃣', action: 'zoom', value: '100', shortcut: 'Ctrl+1' },
            { id: 'zoom-50', label: 'Zoom 50%', category: 'vista', icon: '5️⃣', action: 'zoom', value: '50', shortcut: 'Ctrl+5' },
            { id: 'zoom-200', label: 'Zoom 200%', category: 'vista', icon: '2️⃣', action: 'zoom', value: '200', shortcut: 'Ctrl+2' },

            // Orden y transformación
            { id: 'bring-forward', label: 'Traer adelante', category: 'orden', icon: '⬆', action: 'bringForward', shortcut: 'Ctrl+]' },
            { id: 'send-backward', label: 'Enviar atrás', category: 'orden', icon: '⬇', action: 'sendBackward', shortcut: 'Ctrl+[' },
            { id: 'bring-to-front', label: 'Traer al frente', category: 'orden', icon: '⏫', action: 'bringToFront', shortcut: 'Ctrl+Shift+]' },
            { id: 'send-to-back', label: 'Enviar al fondo', category: 'orden', icon: '⏬', action: 'sendToBack', shortcut: 'Ctrl+Shift+[' },
            { id: 'match-size', label: 'Igualar tamaño', category: 'orden', icon: '📐', action: 'matchSize', shortcut: 'Ctrl+M' },
            { id: 'swap-elements', label: 'Intercambiar posición', category: 'orden', icon: '🔀', action: 'swapElements', shortcut: 'Ctrl+Alt+S' },
            { id: 'wrap-container', label: 'Envolver en contenedor', category: 'orden', icon: '📦', action: 'wrapInContainer', shortcut: 'Ctrl+Shift+W' },

            // Navegación jerárquica
            { id: 'select-parent', label: 'Seleccionar padre', category: 'navegacion', icon: '⬆', action: 'selectParent', shortcut: 'Alt+↑' },
            { id: 'select-child', label: 'Seleccionar primer hijo', category: 'navegacion', icon: '⬇', action: 'selectFirstChild', shortcut: 'Alt+↓' },
            { id: 'center-viewport', label: 'Centrar en viewport', category: 'navegacion', icon: '📍', action: 'centerInViewport', shortcut: 'Alt+Enter' },
            { id: 'toggle-collapse', label: 'Colapsar/Expandir', category: 'navegacion', icon: '📁', action: 'toggleCollapse', shortcut: 'Ctrl+.' },
            { id: 'duplicate-in-place', label: 'Duplicar en mismo lugar', category: 'acciones', icon: '📋', action: 'duplicateInPlace', shortcut: 'Ctrl+Shift+D' },

            // Spacing presets
            { id: 'spacing-8', label: 'Spacing 8px', category: 'spacing', icon: '📏', action: 'setSpacing', value: '8' },
            { id: 'spacing-16', label: 'Spacing 16px', category: 'spacing', icon: '📏', action: 'setSpacing', value: '16' },
            { id: 'spacing-24', label: 'Spacing 24px', category: 'spacing', icon: '📏', action: 'setSpacing', value: '24' },
            { id: 'spacing-32', label: 'Spacing 32px', category: 'spacing', icon: '📏', action: 'setSpacing', value: '32' },

            // Transformaciones visuales
            { id: 'flip-h', label: 'Flip horizontal', category: 'transformaciones', icon: '↔️', action: 'flipHorizontal', shortcut: 'Alt+Shift+H' },
            { id: 'flip-v', label: 'Flip vertical', category: 'transformaciones', icon: '↕️', action: 'flipVertical', shortcut: 'Alt+Shift+V' },
            { id: 'reset-pos', label: 'Resetear posición', category: 'transformaciones', icon: '🔄', action: 'resetPosition', shortcut: 'Ctrl+Shift+0' },
            { id: 'quick-rename', label: 'Renombrar elemento', category: 'acciones', icon: '✏️', action: 'quickRename', shortcut: 'Ctrl+Alt+R' },

            // Marcadores
            { id: 'set-bookmark-1', label: 'Guardar marcador 1', category: 'marcadores', icon: '🔖', action: 'setBookmark', value: '1', shortcut: 'Ctrl+Alt+1' },
            { id: 'set-bookmark-2', label: 'Guardar marcador 2', category: 'marcadores', icon: '🔖', action: 'setBookmark', value: '2', shortcut: 'Ctrl+Alt+2' },
            { id: 'set-bookmark-3', label: 'Guardar marcador 3', category: 'marcadores', icon: '🔖', action: 'setBookmark', value: '3', shortcut: 'Ctrl+Alt+3' },
            { id: 'goto-bookmark-1', label: 'Ir a marcador 1', category: 'marcadores', icon: '📍', action: 'goToBookmark', value: '1', shortcut: 'Ctrl+Shift+1' },
            { id: 'goto-bookmark-2', label: 'Ir a marcador 2', category: 'marcadores', icon: '📍', action: 'goToBookmark', value: '2', shortcut: 'Ctrl+Shift+2' },
            { id: 'goto-bookmark-3', label: 'Ir a marcador 3', category: 'marcadores', icon: '📍', action: 'goToBookmark', value: '3', shortcut: 'Ctrl+Shift+3' },

            // Herramientas avanzadas
            { id: 'aspect-ratio-lock', label: 'Bloquear proporción', category: 'herramientas', icon: '🔒', action: 'toggleAspectRatioLock', shortcut: 'Ctrl+Shift+P' },
            { id: 'smart-guides', label: 'Smart Guides on/off', category: 'herramientas', icon: '📐', action: 'toggleSmartGuides', shortcut: 'Ctrl+Alt+G' },
            { id: 'measure-tool', label: 'Herramienta de medición', category: 'herramientas', icon: '📏', action: 'toggleMeasureTool', shortcut: 'M' },
            { id: 'save-favorite', label: 'Guardar como favorito', category: 'herramientas', icon: '⭐', action: 'saveAsFavorite', shortcut: 'Ctrl+Alt+K' },
            { id: 'open-favorites', label: 'Abrir favoritos', category: 'herramientas', icon: '⭐', action: 'openFavorites', shortcut: 'Ctrl+Alt+Shift+K' },
            { id: 'css-variables', label: 'Editor de variables CSS', category: 'herramientas', icon: '🎨', action: 'openCSSVariables', shortcut: 'Ctrl+Alt+C' },
            { id: 'version-compare', label: 'Comparar versiones', category: 'herramientas', icon: '📜', action: 'openVersionCompare', shortcut: 'Ctrl+Alt+D' },

            // Rotación
            { id: 'rotate-15', label: 'Rotar 15°', category: 'rotacion', icon: '↻', action: 'rotate15', shortcut: 'R' },
            { id: 'rotate-neg-15', label: 'Rotar -15°', category: 'rotacion', icon: '↺', action: 'rotateNeg15', shortcut: 'Shift+R' },
            { id: 'rotate-90', label: 'Rotar 90°', category: 'rotacion', icon: '⟳', action: 'rotate90', shortcut: 'Ctrl+R' },
            { id: 'reset-rotation', label: 'Resetear rotación', category: 'rotacion', icon: '🔄', action: 'resetRotation', shortcut: 'Ctrl+Alt+0' },

            // Snap y Constraints
            { id: 'snap-to-grid', label: 'Snap to grid on/off', category: 'snap', icon: '⊞', action: 'toggleSnapToGrid', shortcut: 'Ctrl+Shift+.' },
            { id: 'toggle-rulers', label: 'Mostrar/Ocultar reglas', category: 'snap', icon: '📏', action: 'toggleRulers', shortcut: 'Ctrl+Alt+Shift+R' },
            { id: 'grid-settings', label: 'Configuración del grid', category: 'snap', icon: '⚙', action: 'openGridSettings', shortcut: 'Alt+Shift+G' },
            { id: 'constraint-top', label: 'Constraint arriba', category: 'snap', icon: '⬆', action: 'toggleConstraintTop', shortcut: 'Ctrl+Alt+T' },
            { id: 'constraint-bottom', label: 'Constraint abajo', category: 'snap', icon: '⬇', action: 'toggleConstraintBottom', shortcut: 'Ctrl+Alt+B' },
            { id: 'constraint-left', label: 'Constraint izquierda', category: 'snap', icon: '⬅', action: 'toggleConstraintLeft', shortcut: 'Ctrl+Alt+L' },
            { id: 'constraint-right', label: 'Constraint derecha', category: 'snap', icon: '➡', action: 'toggleConstraintRight', shortcut: 'Ctrl+Alt+→' },

            // Efectos
            { id: 'shadow-editor', label: 'Editor de sombras', category: 'efectos', icon: '🌑', action: 'openShadowEditor', shortcut: 'Ctrl+Alt+Shift+S' },
            { id: 'gradient-editor', label: 'Editor de gradientes', category: 'efectos', icon: '🌈', action: 'openGradientEditor', shortcut: 'Ctrl+Alt+Shift+X' },
            { id: 'animation-editor', label: 'Editor de animaciones', category: 'efectos', icon: '✨', action: 'openAnimationEditor', shortcut: 'Ctrl+Alt+A' },
            { id: 'typography-editor', label: 'Editor de tipografía', category: 'efectos', icon: '🔤', action: 'openTypographyEditor', shortcut: 'Ctrl+Shift+T' },
            { id: 'border-editor', label: 'Editor de bordes', category: 'efectos', icon: '📐', action: 'openBorderEditor', shortcut: 'Ctrl+Shift+B' },
            { id: 'spacing-editor', label: 'Editor de espaciado', category: 'efectos', icon: '📏', action: 'openSpacingEditor', shortcut: 'Ctrl+Alt+P' },
            { id: 'hover-states', label: 'Estados interactivos (hover/active/focus)', category: 'efectos', icon: '🎯', action: 'openHoverStatesEditor', shortcut: 'Ctrl+Alt+Shift+H' },
            { id: 'scroll-animation', label: 'Animaciones de scroll', category: 'efectos', icon: '📜', action: 'openScrollAnimationEditor', shortcut: 'Ctrl+Alt+Shift+Y' },

            // Navegación canvas
            { id: 'pan-mode', label: 'Modo pan (arrastrar)', category: 'navegacion', icon: '✋', action: 'togglePanMode', shortcut: 'Space' },

            // Auto-layout
            { id: 'auto-layout', label: 'Toggle auto-layout', category: 'autolayout', icon: '📐', action: 'toggleAutoLayout', shortcut: 'Shift+A' },
            { id: 'decrease-gap', label: 'Reducir gap', category: 'autolayout', icon: '⬇', action: 'decreaseGap', shortcut: 'Ctrl+Shift+↑' },
            { id: 'increase-gap', label: 'Aumentar gap', category: 'autolayout', icon: '⬆', action: 'increaseGap', shortcut: 'Ctrl+Shift+↓' },

            // Responsive
            { id: 'device-desktop', label: 'Vista Escritorio', category: 'responsive', icon: '🖥', action: 'breakpointDesktop', shortcut: '1' },
            { id: 'device-tablet', label: 'Vista Tablet (768px)', category: 'responsive', icon: '📱', action: 'breakpointTablet', shortcut: '2' },
            { id: 'device-mobile', label: 'Vista Móvil (375px)', category: 'responsive', icon: '📱', action: 'breakpointMobile', shortcut: '3' },

            // Templates y Componentes
            { id: 'templates-library', label: 'Biblioteca de templates', category: 'templates', icon: '📐', action: 'openTemplatesLibrary', shortcut: 'Ctrl+Shift+K' },
            { id: 'save-component', label: 'Guardar como componente', category: 'templates', icon: '💾', action: 'saveAsComponent', shortcut: 'Ctrl+Alt+Shift+C' },
            { id: 'components-library', label: 'Biblioteca de componentes', category: 'templates', icon: '🧩', action: 'openComponentsLibrary', shortcut: 'Ctrl+Shift+I' },

            // Design System
            { id: 'design-tokens', label: 'Editor de design tokens', category: 'design', icon: '🎨', action: 'openDesignTokens', shortcut: 'Ctrl+Alt+Shift+T' },
            { id: 'export-options', label: 'Opciones de exportación', category: 'design', icon: '📤', action: 'openExportOptions', shortcut: 'Ctrl+Alt+E' },
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

                case 'invertSelection':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'invertSelection' }
                    }));
                    break;

                case 'selectSimilar':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'selectSimilar' }
                    }));
                    break;

                case 'fitContent':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'fitContent' }
                    }));
                    break;

                case 'fillParent':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'fillParent' }
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

                case 'findElements':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'findElements' }
                    }));
                    break;

                case 'toggleVisibility':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleVisibility' }
                    }));
                    break;

                case 'hideOthers':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'hideOthers' }
                    }));
                    break;

                case 'copyAsHTML':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'copyAsHTML' }
                    }));
                    break;

                case 'copyAsJSON':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'copyAsJSON' }
                    }));
                    break;

                case 'pasteFromJSON':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'pasteFromJSON' }
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

                case 'stackElements':
                    var stackActions = {
                        'horizontal': 'stackHorizontal',
                        'vertical': 'stackVertical'
                    };
                    if (stackActions[cmd.value]) {
                        document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                            detail: { action: stackActions[cmd.value] }
                        }));
                    }
                    break;

                case 'bringForward':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'bringForward' }
                    }));
                    break;

                case 'sendBackward':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'sendBackward' }
                    }));
                    break;

                case 'bringToFront':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'bringToFront' }
                    }));
                    break;

                case 'sendToBack':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'sendToBack' }
                    }));
                    break;

                case 'matchSize':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'matchSize' }
                    }));
                    break;

                case 'swapElements':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'swapElements' }
                    }));
                    break;

                case 'wrapInContainer':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'wrapInContainer' }
                    }));
                    break;

                case 'selectParent':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'selectParent' }
                    }));
                    break;

                case 'selectFirstChild':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'selectFirstChild' }
                    }));
                    break;

                case 'centerInViewport':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'centerInViewport' }
                    }));
                    break;

                case 'toggleCollapse':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleCollapse' }
                    }));
                    break;

                case 'duplicateInPlace':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'duplicateInPlace' }
                    }));
                    break;

                case 'setSpacing':
                    var spacingValue = parseInt(cmd.value, 10);
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'setSpacing' + spacingValue }
                    }));
                    break;

                case 'flipHorizontal':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'flipHorizontal' }
                    }));
                    break;

                case 'flipVertical':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'flipVertical' }
                    }));
                    break;

                case 'resetPosition':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'resetPosition' }
                    }));
                    break;

                case 'quickRename':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'quickRename' }
                    }));
                    break;

                case 'setBookmark':
                    var bookmarkIndex = parseInt(cmd.value, 10);
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'setBookmark' + bookmarkIndex }
                    }));
                    break;

                case 'goToBookmark':
                    var gotoIndex = parseInt(cmd.value, 10);
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'goToBookmark' + gotoIndex }
                    }));
                    break;

                case 'toggleAspectRatioLock':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleAspectRatioLock' }
                    }));
                    break;

                case 'toggleSmartGuides':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleSmartGuides' }
                    }));
                    break;

                case 'toggleMeasureTool':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleMeasureTool' }
                    }));
                    break;

                case 'saveAsFavorite':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'saveAsFavorite' }
                    }));
                    break;

                case 'openFavorites':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openFavorites' }
                    }));
                    break;

                case 'openCSSVariables':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openCSSVariables' }
                    }));
                    break;

                case 'openVersionCompare':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openVersionCompare' }
                    }));
                    break;

                // Rotación
                case 'rotate15':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'rotate15' }
                    }));
                    break;

                case 'rotateNeg15':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'rotateNeg15' }
                    }));
                    break;

                case 'rotate90':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'rotate90' }
                    }));
                    break;

                case 'resetRotation':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'resetRotation' }
                    }));
                    break;

                // Snap y Constraints
                case 'toggleSnapToGrid':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleSnapToGrid' }
                    }));
                    break;

                case 'toggleRulers':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleRulers' }
                    }));
                    break;

                case 'openGridSettings':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openGridSettings' }
                    }));
                    break;

                case 'toggleConstraintTop':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleConstraintTop' }
                    }));
                    break;

                case 'toggleConstraintBottom':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleConstraintBottom' }
                    }));
                    break;

                case 'toggleConstraintLeft':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleConstraintLeft' }
                    }));
                    break;

                case 'toggleConstraintRight':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleConstraintRight' }
                    }));
                    break;

                // Efectos
                case 'openShadowEditor':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openShadowEditor' }
                    }));
                    break;

                case 'openGradientEditor':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'openGradientEditor' }
                    }));
                    break;

                // Auto-layout
                case 'toggleAutoLayout':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'toggleAutoLayout' }
                    }));
                    break;

                case 'decreaseGap':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'decreaseGap' }
                    }));
                    break;

                case 'increaseGap':
                    document.dispatchEvent(new CustomEvent('vbp:executeAction', {
                        detail: { action: 'increaseGap' }
                    }));
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
                'orden': 'Orden y Transformación',
                'navegacion': 'Navegación Jerárquica',
                'spacing': 'Spacing Rápido',
                'transformaciones': 'Transformaciones',
                'marcadores': 'Marcadores',
                'herramientas': 'Herramientas Avanzadas',
                'vista': 'Vista',
                'exportar': 'Exportar',
                'responsive': 'Responsive',
                'rotacion': 'Rotación',
                'snap': 'Snap y Constraints',
                'efectos': 'Efectos',
                'autolayout': 'Auto-layout'
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

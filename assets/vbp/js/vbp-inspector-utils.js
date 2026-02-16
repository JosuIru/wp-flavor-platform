/**
 * Visual Builder Pro - Utilidades del Inspector
 *
 * Funcionalidades adicionales: copiar/pegar estilos, secciones colapsables
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Store para el portapapeles de estilos
 */
document.addEventListener('alpine:init', function() {
    Alpine.store('vbpClipboard', {
        copiedStyles: null,
        copiedData: null,

        /**
         * Copiar estilos del elemento seleccionado
         */
        copyStyles: function() {
            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            this.copiedStyles = JSON.parse(JSON.stringify(element.styles || {}));
            this.showNotification('Estilos copiados', 'success');
        },

        /**
         * Pegar estilos al elemento seleccionado
         */
        pasteStyles: function() {
            if (!this.copiedStyles) {
                this.showNotification('No hay estilos copiados', 'warning');
                return;
            }

            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            // Aplicar estilos copiados
            element.styles = Object.assign({}, element.styles || {}, this.copiedStyles);
            store.markDirty();
            this.showNotification('Estilos pegados', 'success');
        },

        /**
         * Copiar todo el elemento (estilos + data)
         */
        copyElement: function() {
            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            this.copiedData = JSON.parse(JSON.stringify({
                type: element.type,
                data: element.data || {},
                styles: element.styles || {}
            }));
            this.showNotification('Elemento copiado', 'success');
        },

        /**
         * Pegar configuración al elemento seleccionado
         */
        pasteElement: function() {
            if (!this.copiedData) {
                this.showNotification('No hay elemento copiado', 'warning');
                return;
            }

            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            // Solo pegar si son del mismo tipo
            if (element.type !== this.copiedData.type) {
                this.showNotification('Los elementos deben ser del mismo tipo', 'warning');
                return;
            }

            element.data = Object.assign({}, element.data || {}, this.copiedData.data);
            element.styles = Object.assign({}, element.styles || {}, this.copiedData.styles);
            store.markDirty();
            this.showNotification('Configuración pegada', 'success');
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            type = type || 'info';
            // Crear notificación temporal
            var notification = document.createElement('div');
            notification.className = 'vbp-notification vbp-notification-' + type;
            notification.textContent = message;
            notification.style.cssText = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 10px 20px; background: ' + (type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6') + '; color: white; border-radius: 6px; font-size: 13px; font-weight: 500; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: vbp-slide-up 0.3s ease;';

            document.body.appendChild(notification);

            setTimeout(function() {
                notification.style.animation = 'vbp-slide-down 0.3s ease forwards';
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 2000);
        }
    });

    /**
     * Store para modales de selectores (iconos, emojis)
     */
    Alpine.store('vbpModals', {
        // Selector de iconos
        iconSelector: {
            open: false,
            field: '',
            callback: null
        },

        // Selector de emojis (usa emoji-picker-element)
        emojiPicker: {
            open: false,
            field: '',
            position: { x: 0, y: 0 },
            callback: null
        },

        // Métodos para selector de iconos
        openIconSelector: function(field, callback) {
            this.iconSelector.open = true;
            this.iconSelector.field = field;
            this.iconSelector.callback = callback;
        },

        closeIconSelector: function() {
            this.iconSelector.open = false;
            this.iconSelector.field = '';
            this.iconSelector.callback = null;
        },

        selectIcon: function(type, iconName) {
            if (this.iconSelector.callback && typeof this.iconSelector.callback === 'function') {
                this.iconSelector.callback(type, iconName);
            }
            this.closeIconSelector();
        },

        // Métodos para selector de emojis
        openEmojiPicker: function(field, position, callback) {
            this.emojiPicker.open = true;
            this.emojiPicker.field = field;
            this.emojiPicker.position = position || { x: 0, y: 0 };
            this.emojiPicker.callback = callback;
        },

        closeEmojiPicker: function() {
            this.emojiPicker.open = false;
            this.emojiPicker.field = '';
            this.emojiPicker.callback = null;
        },

        applyEmojiSelection: function(emoji) {
            if (this.emojiPicker.callback) {
                this.emojiPicker.callback(emoji);
            }
            this.closeEmojiPicker();
        }
    });

    /**
     * Store para secciones colapsables
     */
    Alpine.store('vbpSections', {
        collapsed: {},

        init: function() {
            // Cargar estado guardado
            var saved = localStorage.getItem('vbp_collapsed_sections');
            if (saved) {
                try {
                    this.collapsed = JSON.parse(saved);
                } catch (e) {
                    this.collapsed = {};
                }
            }
        },

        isCollapsed: function(sectionId) {
            return !!this.collapsed[sectionId];
        },

        toggle: function(sectionId) {
            this.collapsed[sectionId] = !this.collapsed[sectionId];
            this.save();
        },

        expand: function(sectionId) {
            this.collapsed[sectionId] = false;
            this.save();
        },

        collapse: function(sectionId) {
            this.collapsed[sectionId] = true;
            this.save();
        },

        expandAll: function() {
            this.collapsed = {};
            this.save();
        },

        collapseAll: function() {
            // Colapsar todas las secciones conocidas
            var sections = document.querySelectorAll('[data-section-id]');
            var self = this;
            sections.forEach(function(section) {
                var id = section.getAttribute('data-section-id');
                self.collapsed[id] = true;
            });
            this.save();
        },

        save: function() {
            localStorage.setItem('vbp_collapsed_sections', JSON.stringify(this.collapsed));
        }
    });
});

/**
 * Componente Alpine para sección colapsable
 */
function vbpCollapsibleSection() {
    return {
        sectionId: '',

        init: function() {
            // Generar ID único si no existe
            if (!this.sectionId) {
                this.sectionId = 'section_' + Math.random().toString(36).substr(2, 9);
            }
        },

        get isCollapsed() {
            return Alpine.store('vbpSections').isCollapsed(this.sectionId);
        },

        toggle: function() {
            Alpine.store('vbpSections').toggle(this.sectionId);
        }
    };
}

/**
 * Atajos de teclado globales para el inspector
 */
document.addEventListener('keydown', function(event) {
    // Solo funciona si no estamos en un input
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.isContentEditable) {
        return;
    }

    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var modKey = isMac ? event.metaKey : event.ctrlKey;

    // Ctrl/Cmd + C: Copiar estilos
    if (modKey && event.shiftKey && event.key.toLowerCase() === 'c') {
        event.preventDefault();
        Alpine.store('vbpClipboard').copyStyles();
    }

    // Ctrl/Cmd + V: Pegar estilos
    if (modKey && event.shiftKey && event.key.toLowerCase() === 'v') {
        event.preventDefault();
        Alpine.store('vbpClipboard').pasteStyles();
    }

    // D: Duplicar elemento
    if (event.key.toLowerCase() === 'd' && modKey) {
        event.preventDefault();
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 1) {
            store.duplicateElement(store.selection.elementIds[0]);
        }
    }
});

// Agregar animaciones CSS
(function() {
    var style = document.createElement('style');
    style.textContent = '\
        @keyframes vbp-slide-up {\
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }\
            to { opacity: 1; transform: translateX(-50%) translateY(0); }\
        }\
        @keyframes vbp-slide-down {\
            from { opacity: 1; transform: translateX(-50%) translateY(0); }\
            to { opacity: 0; transform: translateX(-50%) translateY(20px); }\
        }\
    ';
    document.head.appendChild(style);
})();

/**
 * Componente Alpine para selector de iconos
 */
function vbpIconSelector() {
    return {
        activeTab: 'material',
        searchQuery: '',
        selectedIcon: '',
        selectedType: '',
        customSvgUrl: '',
        iconCategories: {
            'navigation': ['home', 'menu_book', 'search', 'dashboard'],
            'communication': ['chat_bubble', 'phone', 'email', 'forum', 'announcement'],
            'social': ['people', 'person', 'group_work', 'volunteer_activism'],
            'commerce': ['shopping_cart', 'store', 'local_offer', 'attach_money', 'receipt'],
            'food': ['restaurant', 'local_cafe', 'local_bar', 'local_pizza'],
            'travel': ['directions_car', 'flight', 'hotel', 'map', 'location_on'],
            'health': ['spa', 'fitness_center', 'local_pharmacy', 'local_hospital'],
            'business': ['work', 'account_balance', 'analytics', 'trending_up', 'inventory']
        },

        filterIcons: function() {
            // El filtrado es reactivo via isIconVisible(), este método es para compatibilidad
        },

        isIconVisible: function(iconName) {
            if (!this.searchQuery) return true;
            var query = this.searchQuery.toLowerCase();
            return iconName.toLowerCase().indexOf(query) !== -1;
        },

        selectIcon: function(type, iconName) {
            // Solo previsualizar, no confirmar todavía
            this.selectedIcon = iconName;
            this.selectedType = type;
            // Limpiar SVG personalizado si selecciona Material Icon
            if (type === 'material') {
                this.customSvgUrl = '';
            }
        },

        closeModal: function() {
            Alpine.store('vbpModals').closeIconSelector();
        },

        clearCustomSvg: function() {
            this.customSvgUrl = '';
            this.selectedIcon = '';
            this.selectedType = '';
        },

        confirmSelection: function() {
            var store = Alpine.store('vbpModals');
            if (this.selectedType === 'svg' && this.customSvgUrl) {
                // Llamar callback si existe
                if (store.iconSelector.callback && typeof store.iconSelector.callback === 'function') {
                    store.iconSelector.callback('svg', this.customSvgUrl);
                }
            } else if (this.selectedIcon) {
                // Llamar callback si existe
                if (store.iconSelector.callback && typeof store.iconSelector.callback === 'function') {
                    store.iconSelector.callback(this.selectedType, this.selectedIcon);
                }
            }
            // Cerrar modal
            store.closeIconSelector();
        },

        openMediaLibrarySvg: function() {
            if (wp && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar SVG',
                    button: { text: 'Usar este SVG' },
                    multiple: false,
                    library: { type: 'image/svg+xml' }
                });

                var self = this;
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    self.customSvgUrl = attachment.url;
                    self.selectedType = 'svg';
                    self.selectedIcon = '';
                });

                frame.open();
            }
        }
    };
}

/**
 * Componente Alpine para selector de emojis
 */
function vbpEmojiSelector() {
    return {
        searchQuery: '',
        activeCategory: 'smileys',
        categories: {
            'smileys': {
                label: 'Caritas',
                emojis: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥']
            },
            'gestures': {
                label: 'Gestos',
                emojis: ['👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏']
            },
            'objects': {
                label: 'Objetos',
                emojis: ['💡', '🔧', '🔨', '⚙️', '🛠️', '📱', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '💾', '💿', '📷', '🎥', '📞', '☎️', '📺', '📻', '⏰', '⌚', '💰', '💳', '💎', '🔑', '🗝️', '🔒', '🔓', '📦', '📫']
            },
            'symbols': {
                label: 'Símbolos',
                emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '⭐', '🌟', '✨', '💫', '🔥', '✅', '❌', '⚡', '💯', '🎯', '🏆', '🎁']
            },
            'nature': {
                label: 'Naturaleza',
                emojis: ['🌸', '🌺', '🌻', '🌼', '🌷', '🌹', '🥀', '🌵', '🌴', '🌲', '🌳', '🌱', '🌿', '☘️', '🍀', '🍁', '🍂', '🍃', '🌍', '🌎', '🌏', '🌙', '⭐', '☀️', '🌤️', '⛅', '🌈', '💧', '🌊', '🔥']
            },
            'food': {
                label: 'Comida',
                emojis: ['🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥗', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🍤', '🍙', '🍚', '🍘', '🍥', '🥮', '🍡', '🥧', '🍰', '🎂', '🧁', '🍮', '🍦', '🍧', '🍨']
            }
        },
        position: { top: 0, left: 0 },

        get filteredEmojis() {
            if (!this.searchQuery) {
                return this.categories[this.activeCategory].emojis;
            }
            var all = [];
            var self = this;
            Object.keys(this.categories).forEach(function(cat) {
                all = all.concat(self.categories[cat].emojis);
            });
            // Búsqueda básica (sería mejor con un mapa nombre->emoji)
            return all;
        },

        selectEmoji: function(emoji) {
            Alpine.store('vbpModals').applyEmojiSelection(emoji);
        },

        closeModal: function() {
            Alpine.store('vbpModals').closeEmojiPicker();
        }
    };
}

/**
 * Componente Alpine para color picker con paleta del sitio
 */
function vbpColorPicker() {
    return {
        showPalette: true,
        colors: [],

        init: function() {
            // Obtener colores del sitio desde VBP_Config
            var settings = typeof VBP_Config !== 'undefined' ? VBP_Config.designSettings : {};

            this.colors = [
                { name: 'Pri', color: settings.primary_color || '#3b82f6', label: 'Primario' },
                { name: 'Sec', color: settings.secondary_color || '#8b5cf6', label: 'Secundario' },
                { name: 'Acc', color: settings.accent_color || '#f59e0b', label: 'Acento' },
                { name: 'Txt', color: settings.text_color || '#1f2937', label: 'Texto' },
                { name: 'Mut', color: settings.text_muted_color || '#6b7280', label: 'Muted' },
                { name: 'Bg', color: settings.background_color || '#ffffff', label: 'Fondo' },
                { name: 'Suc', color: settings.success_color || '#10b981', label: 'Éxito' },
                { name: 'Err', color: settings.error_color || '#ef4444', label: 'Error' }
            ];
        },

        applyColor: function(color, field) {
            // Emitir evento para actualizar el modelo
            this.$dispatch('color-selected', { color: color, field: field });
        },

        isActive: function(color, currentValue) {
            return color.toLowerCase() === (currentValue || '').toLowerCase();
        }
    };
}

// Registrar componentes globalmente (para compatibilidad)
window.vbpCollapsibleSection = vbpCollapsibleSection;
window.vbpIconSelector = vbpIconSelector;
window.vbpEmojiSelector = vbpEmojiSelector;
window.vbpColorPicker = vbpColorPicker;

// Registrar con Alpine.data() para mejor timing
document.addEventListener('alpine:init', function() {
    Alpine.data('vbpIconSelector', vbpIconSelector);
    Alpine.data('vbpEmojiSelector', vbpEmojiSelector);
    Alpine.data('vbpColorPicker', vbpColorPicker);
    Alpine.data('vbpCollapsibleSection', vbpCollapsibleSection);
});

/**
 * Visual Builder Pro - Inspector Modals & Pickers
 *
 * Centraliza los selectores visuales del inspector sobre el store
 * canónico de vbpModals definido en vbp-store.js.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

function vbpIconSelector() {
    return {
        activeTab: 'material',
        searchQuery: '',
        selectedIcon: '',
        selectedType: '',
        customSvgUrl: '',
        iconCategories: {
            navigation: ['home', 'menu_book', 'search', 'dashboard'],
            communication: ['chat_bubble', 'phone', 'email', 'forum', 'announcement'],
            social: ['people', 'person', 'group_work', 'volunteer_activism'],
            commerce: ['shopping_cart', 'store', 'local_offer', 'attach_money', 'receipt'],
            food: ['restaurant', 'local_cafe', 'local_bar', 'local_pizza'],
            travel: ['directions_car', 'flight', 'hotel', 'map', 'location_on'],
            health: ['spa', 'fitness_center', 'local_pharmacy', 'local_hospital'],
            business: ['work', 'account_balance', 'analytics', 'trending_up', 'inventory']
        },

        init: function() {
            var self = this;
            this.$watch('$store.vbpModals.iconSelector.open', function(isOpen) {
                if (!isOpen) return;

                self.resetState();

                var currentValue = Alpine.store('vbpModals').iconSelector.currentValue;
                if (!currentValue) return;

                if (currentValue.startsWith('http') || currentValue.startsWith('/')) {
                    self.customSvgUrl = currentValue;
                    self.selectedType = 'svg';
                    self.activeTab = 'svg';
                } else if (currentValue.length > 4 || /^[a-z_]+$/.test(currentValue)) {
                    self.selectedIcon = currentValue;
                    self.selectedType = 'material';
                } else {
                    self.selectedIcon = currentValue;
                    self.selectedType = 'emoji';
                    self.activeTab = 'emoji';
                }
            });
        },

        resetState: function() {
            this.searchQuery = '';
            this.selectedIcon = '';
            this.selectedType = '';
            this.customSvgUrl = '';
            this.activeTab = 'material';
        },

        filterIcons: function() {
            // Compatibilidad con llamadas desde plantilla; el filtrado es reactivo.
        },

        isIconVisible: function(iconName) {
            if (!this.searchQuery) return true;
            return iconName.toLowerCase().indexOf(this.searchQuery.toLowerCase()) !== -1;
        },

        selectIcon: function(type, iconName) {
            this.selectedIcon = iconName;
            this.selectedType = type;
            if (type === 'material') {
                this.customSvgUrl = '';
            }
        },

        closeModal: function() {
            Alpine.store('vbpModals').closeIconSelector();
            this.resetState();
        },

        clearCustomSvg: function() {
            this.customSvgUrl = '';
            this.selectedIcon = '';
            this.selectedType = '';
        },

        confirmSelection: function() {
            if (this.selectedType === 'svg' && this.customSvgUrl) {
                Alpine.store('vbpModals').applyIconSelection('svg', this.customSvgUrl);
            } else if (this.selectedIcon) {
                Alpine.store('vbpModals').applyIconSelection(this.selectedType, this.selectedIcon);
            }

            this.closeModal();
        },

        openMediaLibrarySvg: function() {
            var self = this;
            var isValidSvgUrl = function(url) {
                if (!url || typeof url !== 'string') return false;

                var normalized = url.trim().toLowerCase();
                if (!(normalized.startsWith('/') || normalized.startsWith('http://') || normalized.startsWith('https://'))) {
                    return false;
                }

                return /\.svg(\?.*)?(#.*)?$/.test(normalized);
            };

            if (typeof wp === 'undefined' || !wp.media) {
                var url = prompt('La biblioteca de medios no está disponible. Introduce la URL del SVG:');
                if (isValidSvgUrl(url)) {
                    self.customSvgUrl = url;
                    self.selectedType = 'svg';
                    self.selectedIcon = '';
                    self.activeTab = 'svg';
                }
                return;
            }

            var frame = wp.media({
                title: 'Seleccionar SVG',
                button: { text: 'Usar este SVG' },
                multiple: false,
                library: { type: 'image/svg+xml' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                self.customSvgUrl = attachment.url;
                self.selectedType = 'svg';
                self.selectedIcon = '';
                self.activeTab = 'svg';
            });

            frame.open();
        }
    };
}

function vbpEmojiSelector() {
    return {
        searchQuery: '',
        activeCategory: 'smileys',
        categories: {
            smileys: {
                label: 'Caritas',
                emojis: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥']
            },
            gestures: {
                label: 'Gestos',
                emojis: ['👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏']
            },
            objects: {
                label: 'Objetos',
                emojis: ['💡', '🔧', '🔨', '⚙️', '🛠️', '📱', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '💾', '💿', '📷', '🎥', '📞', '☎️', '📺', '📻', '⏰', '⌚', '💰', '💳', '💎', '🔑', '🗝️', '🔒', '🔓', '📦', '📫']
            },
            symbols: {
                label: 'Símbolos',
                emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '⭐', '🌟', '✨', '💫', '🔥', '✅', '❌', '⚡', '💯', '🎯', '🏆', '🎁']
            },
            nature: {
                label: 'Naturaleza',
                emojis: ['🌸', '🌺', '🌻', '🌼', '🌷', '🌹', '🥀', '🌵', '🌴', '🌲', '🌳', '🌱', '🌿', '☘️', '🍀', '🍁', '🍂', '🍃', '🌍', '🌎', '🌏', '🌙', '⭐', '☀️', '🌤️', '⛅', '🌈', '💧', '🌊', '🔥']
            },
            food: {
                label: 'Comida',
                emojis: ['🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥗', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🍤', '🍙', '🍚', '🍘', '🍥', '🥮', '🍡', '🥧', '🍰', '🎂', '🧁', '🍮', '🍦', '🍧', '🍨']
            }
        },

        get filteredEmojis() {
            if (!this.searchQuery) {
                return this.categories[this.activeCategory].emojis;
            }

            var all = [];
            var self = this;
            Object.keys(this.categories).forEach(function(category) {
                all = all.concat(self.categories[category].emojis);
            });
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

function vbpColorPicker() {
    return {
        currentColor: '#ffffff',
        isOpen: false,
        presetColors: [],
        siteColors: [],

        init: function() {
            var settings = typeof VBP_Config !== 'undefined' ? VBP_Config.designSettings : {};

            this.siteColors = [
                { color: settings.primary_color || '#3b82f6', label: 'Primario' },
                { color: settings.secondary_color || '#8b5cf6', label: 'Secundario' },
                { color: settings.accent_color || '#f59e0b', label: 'Acento' },
                { color: settings.success_color || '#10b981', label: 'Éxito' },
                { color: settings.error_color || '#ef4444', label: 'Error' }
            ];

            this.presetColors = [
                '#000000', '#ffffff', '#f3f4f6', '#e5e7eb',
                '#d1d5db', '#9ca3af', '#6b7280', '#4b5563',
                '#374151', '#1f2937', '#111827', '#0f172a',
                '#ef4444', '#f97316', '#f59e0b', '#eab308',
                '#84cc16', '#22c55e', '#10b981', '#14b8a6',
                '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1',
                '#8b5cf6', '#a855f7', '#d946ef', '#ec4899'
            ];
        },

        initColor: function(color) {
            this.currentColor = color || '#ffffff';
        },

        togglePicker: function() {
            this.isOpen = !this.isOpen;
        },

        selectColor: function(color) {
            this.currentColor = color;
            this.isOpen = false;
        },

        updateColor: function(color) {
            this.currentColor = color;
        },

        normalizeForInput: function(color) {
            if (!color) return '#ffffff';
            if (color.startsWith('rgba') || color.startsWith('rgb')) {
                return this.rgbToHex(color);
            }
            if (color.length === 4) {
                return '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
            }
            return color;
        },

        rgbToHex: function(rgb) {
            var match = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (!match) return '#ffffff';
            var r = parseInt(match[1], 10).toString(16).padStart(2, '0');
            var g = parseInt(match[2], 10).toString(16).padStart(2, '0');
            var b = parseInt(match[3], 10).toString(16).padStart(2, '0');
            return '#' + r + g + b;
        },

        applyColor: function(color, field) {
            this.$dispatch('color-selected', { color: color, field: field });
        },

        isActive: function(color) {
            if (!this.currentColor || !color) return false;
            return color.toLowerCase() === this.currentColor.toLowerCase();
        }
    };
}

window.vbpIconSelector = vbpIconSelector;
window.vbpEmojiSelector = vbpEmojiSelector;
window.vbpColorPicker = vbpColorPicker;

/**
 * Registrar componentes Alpine - tanto inmediatamente si Alpine existe,
 * como en el evento alpine:init por si se carga después
 */
function registerInspectorModalComponents() {
    if (typeof Alpine === 'undefined') return false;
    Alpine.data('vbpIconSelector', vbpIconSelector);
    Alpine.data('vbpEmojiSelector', vbpEmojiSelector);
    Alpine.data('vbpColorPicker', vbpColorPicker);
    return true;
}

// Registrar inmediatamente si Alpine ya existe
if (typeof Alpine !== 'undefined') {
    registerInspectorModalComponents();
}

// También escuchar el evento por si Alpine se carga después
document.addEventListener('alpine:init', function() {
    registerInspectorModalComponents();
});

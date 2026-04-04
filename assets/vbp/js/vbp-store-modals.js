/**
 * Visual Builder Pro - Store Modals
 *
 * Separa del store principal el estado de modales y selectores
 * para reducir acoplamiento del núcleo del documento.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('vbpModals', {
        iconSelector: {
            open: false,
            callback: null,
            currentValue: '',
            field: '',
            itemIndex: null
        },
        emojiPicker: {
            open: false,
            callback: null,
            position: { x: 0, y: 0 },
            field: '',
            itemIndex: null
        },
        linkSearch: {
            open: false,
            callback: null
        },

        openEmojiPicker: function(callback, position, field, itemIndex) {
            this.emojiPicker.callback = callback;
            this.emojiPicker.position = position || { x: 0, y: 0 };
            this.emojiPicker.field = field || '';
            this.emojiPicker.itemIndex = itemIndex !== undefined ? itemIndex : null;
            this.emojiPicker.open = true;
        },

        closeEmojiPicker: function() {
            this.emojiPicker.open = false;
            this.emojiPicker.callback = null;
        },

        applyEmojiSelection: function(emoji) {
            if (this.emojiPicker.callback) {
                this.emojiPicker.callback(emoji);
            }
            this.closeEmojiPicker();
        },

        openIconSelector: function(callback, currentValue, field, itemIndex) {
            this.iconSelector.callback = callback;
            this.iconSelector.currentValue = currentValue || '';
            this.iconSelector.field = field || 'icono';
            this.iconSelector.itemIndex = itemIndex !== undefined ? itemIndex : null;
            this.iconSelector.open = true;
        },

        closeIconSelector: function() {
            this.iconSelector.open = false;
            this.iconSelector.callback = null;
        },

        applyIconSelection: function(type, value) {
            if (this.iconSelector.callback) {
                this.iconSelector.callback(type, value);
            }
            this.closeIconSelector();
        }
    });
});

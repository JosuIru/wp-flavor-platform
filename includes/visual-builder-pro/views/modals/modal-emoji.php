<?php
/**
 * Visual Builder Pro - Modal Selector de Emojis
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="vbp-emoji-picker"
     class="vbp-emoji-picker-wrapper"
     x-data="vbpEmojiPicker()"
     x-show="$store.vbpModals.emojiPicker.open"
     x-cloak
     :style="{ left: $store.vbpModals.emojiPicker.position.x + 'px', top: $store.vbpModals.emojiPicker.position.y + 'px' }"
     @click.outside="closeEmojiPicker()"
     @keydown.escape.window="closeEmojiPicker()">
    <emoji-picker x-ref="picker"></emoji-picker>
</div>

<script>
// Componente Alpine para el selector de emojis
function vbpEmojiPicker() {
    return {
        init: function() {
            var self = this;

            // Esperar a que el emoji-picker esté listo
            this.$nextTick(function() {
                var picker = self.$refs.picker;
                if (picker) {
                    picker.addEventListener('emoji-click', function(event) {
                        Alpine.store('vbpModals').applyEmojiSelection(event.detail.unicode);
                    });
                }
            });
        },

        closeEmojiPicker: function() {
            Alpine.store('vbpModals').closeEmojiPicker();
        }
    };
}
</script>

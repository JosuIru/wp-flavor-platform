<?php
/**
 * Visual Builder Pro - Panel Mini Mapa
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Mini Mapa de Navegación -->
<div x-data="vbpMinimap()"
     x-show="$store.vbpMinimap.isVisible"
     x-cloak
     class="vbp-minimap"
     :class="{ 'hidden': !$store.vbpMinimap.isVisible }">

    <div class="vbp-minimap-header">
        <span class="vbp-minimap-title"><?php esc_html_e( 'Mini Mapa', 'flavor-chat-ia' ); ?></span>
        <button type="button"
                class="vbp-minimap-toggle"
                @click="$store.vbpMinimap.toggle()"
                title="<?php esc_attr_e( 'Ocultar mini mapa', 'flavor-chat-ia' ); ?>">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="vbp-minimap-content"
         x-ref="minimap"
         :style="{ height: canvasHeight + 'px' }"
         :class="{ 'dragging': isDragging }"
         @click="handleMinimapClick($event)"
         @mousedown="startDrag($event)"
         @mousemove="handleDrag($event)"
         @mouseup="endDrag()"
         @mouseleave="endDrag()">

        <!-- Elementos del canvas -->
        <template x-for="element in elements" :key="element.id">
            <div class="vbp-minimap-element"
                 :class="{ 'selected': element.selected }"
                 :style="{
                     top: element.top + 'px',
                     height: element.height + 'px',
                     backgroundColor: getElementColor(element.type)
                 }"
                 @click.stop="scrollToElement(element.id)">
            </div>
        </template>

        <!-- Indicador de viewport -->
        <div class="vbp-minimap-viewport"
             :style="{
                 top: viewportTop + 'px',
                 height: viewportHeight + 'px'
             }">
        </div>
    </div>
</div>

<!-- Botón para mostrar mini mapa cuando está oculto -->
<button x-data
        x-show="!$store.vbpMinimap.isVisible"
        x-cloak
        type="button"
        class="vbp-minimap-show-btn"
        @click="$store.vbpMinimap.toggle()"
        title="<?php esc_attr_e( 'Mostrar mini mapa', 'flavor-chat-ia' ); ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="M9 3v18M3 9h6M3 15h6"/>
    </svg>
</button>

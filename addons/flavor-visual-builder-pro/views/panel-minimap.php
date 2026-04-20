<?php
/**
 * Visual Builder Pro - Panel Mini Mapa
 * Navegación visual mejorada para documentos largos
 *
 * @package Flavor_Platform
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

    <!-- Header con título e iconos -->
    <div class="vbp-minimap-header">
        <span class="vbp-minimap-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 3v18M3 9h6M3 15h6"/>
            </svg>
            <?php esc_html_e( 'Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
        </span>
        <div class="vbp-minimap-actions">
            <!-- Zoom controls -->
            <div class="vbp-minimap-zoom">
                <button type="button"
                        class="vbp-minimap-zoom-btn"
                        @click="zoomOut()"
                        title="<?php esc_attr_e( 'Reducir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                    </svg>
                </button>
                <span class="vbp-minimap-zoom-value" x-text="Math.round(scale * 100) + '%'"></span>
                <button type="button"
                        class="vbp-minimap-zoom-btn"
                        @click="zoomIn()"
                        title="<?php esc_attr_e( 'Ampliar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            </div>
            <!-- Cerrar -->
            <button type="button"
                    class="vbp-minimap-btn"
                    @click="$store.vbpMinimap.toggle()"
                    title="<?php esc_attr_e( 'Ocultar mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Contenido del mini mapa -->
    <div class="vbp-minimap-content"
         x-ref="minimap"
         :style="{ minHeight: Math.max(canvasHeight, 100) + 'px' }"
         :class="{ 'dragging': isDragging, 'has-scroll': canvasHeight > 250 }"
         @click="handleMinimapClick($event)"
         @mousedown="startDrag($event)"
         @mousemove="handleDrag($event)"
         @mouseup="endDrag()"
         @mouseleave="endDrag()">

        <!-- Estado vacío -->
        <template x-if="elements.length === 0">
            <div class="vbp-minimap-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <span class="vbp-minimap-empty-text"><?php esc_html_e( 'Sin elementos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            </div>
        </template>

        <!-- Elementos del canvas -->
        <template x-for="element in elements" :key="element.id">
            <div class="vbp-minimap-element"
                 :class="{ 'selected': element.selected }"
                 :style="{
                     top: element.top + 'px',
                     height: Math.max(element.height, 3) + 'px',
                     backgroundColor: getElementColor(element.type),
                     opacity: element.selected ? 1 : 0.85
                 }"
                 :title="getElementLabel(element.type)"
                 @click.stop="scrollToElement(element.id)"
                 @mouseenter="showTooltip($event, element)"
                 @mouseleave="hideTooltip()">
            </div>
        </template>

        <!-- Indicador de viewport (área visible) -->
        <div class="vbp-minimap-viewport"
             :style="{
                 top: viewportTop + 'px',
                 height: Math.max(viewportHeight, 20) + 'px'
             }">
        </div>
    </div>

    <!-- Footer con estadísticas -->
    <div class="vbp-minimap-footer">
        <div class="vbp-minimap-legend">
            <span class="vbp-minimap-legend-item">
                <span class="vbp-minimap-legend-color" style="background: #ec4899;"></span>
                Hero
            </span>
            <span class="vbp-minimap-legend-item">
                <span class="vbp-minimap-legend-color" style="background: #10b981;"></span>
                Features
            </span>
            <span class="vbp-minimap-legend-item">
                <span class="vbp-minimap-legend-color" style="background: #3b82f6;"></span>
                CTA
            </span>
            <span class="vbp-minimap-legend-item" x-show="elements.length > 0">
                <span x-text="elements.length"></span> <?php esc_html_e( 'bloques', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </span>
        </div>
    </div>
</div>

<!-- Tooltip flotante -->
<div x-data="{ show: false, text: '', x: 0, y: 0 }"
     x-ref="minimapTooltip"
     class="vbp-minimap-tooltip"
     :class="{ 'visible': show }"
     :style="{ left: x + 'px', top: y + 'px' }"
     x-text="text">
</div>

<!-- Botón flotante para mostrar mini mapa cuando está oculto -->
<button x-data
        x-show="!$store.vbpMinimap.isVisible"
        x-cloak
        type="button"
        class="vbp-minimap-show-btn"
        @click="$store.vbpMinimap.toggle()"
        title="<?php esc_attr_e( 'Mostrar mapa de navegación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="M9 3v18"/>
        <path d="M3 9h6"/>
        <path d="M3 15h6"/>
        <circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/>
    </svg>
</button>

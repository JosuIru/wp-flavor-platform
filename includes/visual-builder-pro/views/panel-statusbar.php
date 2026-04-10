<?php
/**
 * Visual Builder Pro - Barra de Estado
 * Breadcrumbs, Zoom, Estado de guardado
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Barra de Estado -->
<div class="vbp-statusbar">
    <!-- Breadcrumbs -->
    <div class="vbp-breadcrumbs" x-data="vbpBreadcrumbs()">
        <template x-for="(item, index) in path" :key="index">
            <div class="vbp-breadcrumb-wrapper" style="display: flex; align-items: center;">
                <button type="button"
                        class="vbp-breadcrumb-item"
                        :class="{ 'active': isLast(index) }"
                        @click="selectElement(item)">
                    <span class="vbp-breadcrumb-icon" x-text="item.icon"></span>
                    <span x-text="item.name"></span>
                </button>
                <span class="vbp-breadcrumb-separator" x-show="!isLast(index)">›</span>
            </div>
        </template>
    </div>

    <!-- Control de Zoom -->
    <div class="vbp-zoom-control" x-data="vbpZoomControl()">
        <button type="button"
                class="vbp-zoom-btn"
                @click="zoomOut()"
                title="<?php esc_attr_e( 'Alejar (Ctrl+-)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35M8 11h6"/>
            </svg>
        </button>

        <div class="vbp-zoom-wrapper" style="position: relative;">
            <button type="button"
                    class="vbp-zoom-value"
                    @click="$refs.zoomDropdown.classList.toggle('hidden')"
                    x-text="zoom + '%'">
            </button>

            <div x-ref="zoomDropdown"
                 class="vbp-zoom-dropdown hidden"
                 @click.outside="$refs.zoomDropdown.classList.add('hidden')">
                <template x-for="preset in presets" :key="preset">
                    <button type="button"
                            class="vbp-zoom-preset"
                            :class="{ 'active': zoom === preset }"
                            @click="setPreset(preset); $refs.zoomDropdown.classList.add('hidden')"
                            x-text="preset + '%'">
                    </button>
                </template>
                <hr style="margin: 4px 0; border: none; border-top: 1px solid var(--vbp-border-color, #e2e8f0);">
                <button type="button"
                        class="vbp-zoom-preset"
                        @click="fitToScreen(); $refs.zoomDropdown.classList.add('hidden')">
                    <?php esc_html_e( 'Ajustar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>

        <button type="button"
                class="vbp-zoom-btn"
                @click="zoomIn()"
                title="<?php esc_attr_e( 'Acercar (Ctrl++)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/>
            </svg>
        </button>

        <button type="button"
                class="vbp-zoom-btn"
                @click="resetZoom()"
                title="<?php esc_attr_e( 'Zoom 100% (Ctrl+0)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                x-show="zoom !== 100">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 21l-4.35-4.35"/>
                <circle cx="11" cy="11" r="8"/>
                <path d="M11 8v6M8 11h6" transform="rotate(45 11 11)"/>
            </svg>
        </button>
    </div>

    <!-- Estado de Guardado -->
    <div class="vbp-save-status"
         :class="'vbp-save-status--' + $store.vbpSaveStatus.status"
         x-data
         @click="$store.vbpSaveStatus.status === 'unsaved' && $dispatch('vbp:requestSave')">
        <span class="vbp-save-status-icon" x-text="$store.vbpSaveStatus.getStatusIcon()"></span>
        <span class="vbp-save-status-text" x-text="$store.vbpSaveStatus.getStatusText()"></span>
    </div>
</div>

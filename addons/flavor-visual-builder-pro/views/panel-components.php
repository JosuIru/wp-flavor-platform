<?php
/**
 * Visual Builder Pro - Panel de Biblioteca de Componentes
 *
 * Panel del sidebar izquierdo para gestionar componentes reutilizables.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.22
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-component-library-panel">
    <!-- Header del panel -->
    <div class="vbp-panel-header" style="padding: 12px 16px; border-bottom: 1px solid var(--vbp-border-color, #e5e7eb);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <h3 style="margin: 0; font-size: 13px; font-weight: 600; color: var(--vbp-text-primary, #111);">
                <?php esc_html_e( 'Biblioteca de Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </h3>
            <div style="display: flex; gap: 4px;">
                <button type="button" id="vbp-import-component" class="vbp-btn-icon-sm" title="<?php esc_attr_e( 'Importar componente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="padding: 6px; border: none; background: transparent; cursor: pointer; border-radius: 4px; color: var(--vbp-text-secondary, #6b7280);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17,8 12,3 7,8"/><path d="M12 3v12"/></svg>
                </button>
                <button type="button" onclick="VBPComponentLibrary.guardarSeleccionComoComponente()" class="vbp-btn-icon-sm" title="<?php esc_attr_e( 'Guardar selección como componente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="padding: 6px; border: none; background: transparent; cursor: pointer; border-radius: 4px; color: var(--vbp-text-secondary, #6b7280);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Búsqueda -->
    <div class="vbp-component-search" style="padding: 12px 16px; border-bottom: 1px solid var(--vbp-border-color, #e5e7eb);">
        <div style="position: relative;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--vbp-text-secondary, #9ca3af);"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="vbp-component-search" placeholder="<?php esc_attr_e( 'Buscar componentes...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 6px; font-size: 13px; background: var(--vbp-bg-secondary, #f9fafb); box-sizing: border-box; color: var(--vbp-text-primary, #111);">
        </div>
    </div>

    <!-- Contenedor con scroll -->
    <div class="vbp-component-content" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
        <!-- Categorías -->
        <div class="vbp-component-categories-wrapper" style="padding: 12px 16px; border-bottom: 1px solid var(--vbp-border-color, #e5e7eb);">
            <h4 style="margin: 0 0 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vbp-text-secondary, #6b7280);">
                <?php esc_html_e( 'Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </h4>
            <div id="vbp-component-categories" style="display: flex; flex-direction: column; gap: 4px;">
                <!-- Las categorías se cargarán dinámicamente -->
                <div style="text-align: center; padding: 12px; color: var(--vbp-text-secondary, #9ca3af); font-size: 12px;">
                    <?php esc_html_e( 'Cargando categorías...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </div>
            </div>
        </div>

        <!-- Lista de componentes -->
        <div class="vbp-components-list-wrapper" style="flex: 1; overflow-y: auto; padding: 12px 16px;">
            <div id="vbp-components-list">
                <!-- Los componentes se cargarán dinámicamente -->
                <div style="text-align: center; padding: 40px 20px;">
                    <div class="vbp-spinner" style="width: 32px; height: 32px; border: 3px solid var(--vbp-border-color, #e5e7eb); border-top-color: var(--vbp-accent-color, #6366f1); border-radius: 50%; margin: 0 auto 12px; animation: vbp-spin 0.8s linear infinite;"></div>
                    <p style="margin: 0; color: var(--vbp-text-secondary, #6b7280); font-size: 13px;">
                        <?php esc_html_e( 'Cargando componentes...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer con ayuda -->
    <div class="vbp-component-footer" style="padding: 12px 16px; border-top: 1px solid var(--vbp-border-color, #e5e7eb); background: var(--vbp-bg-secondary, #f9fafb);">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--vbp-text-secondary, #6b7280);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
            <span><?php esc_html_e( 'Clic para insertar, arrastrar para posicionar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </div>
    </div>
</div>

<style>
/* Estilos específicos del panel de componentes */
.vbp-component-library-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--vbp-bg-primary, #fff);
}

.vbp-component-library-panel .vbp-btn-icon-sm:hover {
    background: var(--vbp-bg-secondary, #f3f4f6);
    color: var(--vbp-accent-color, #6366f1);
}

.vbp-component-library-panel input:focus {
    outline: none;
    border-color: var(--vbp-accent-color, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.vbp-components-list-wrapper {
    scrollbar-width: thin;
    scrollbar-color: var(--vbp-border-color, #e5e7eb) transparent;
}

.vbp-components-list-wrapper::-webkit-scrollbar {
    width: 6px;
}

.vbp-components-list-wrapper::-webkit-scrollbar-track {
    background: transparent;
}

.vbp-components-list-wrapper::-webkit-scrollbar-thumb {
    background-color: var(--vbp-border-color, #d1d5db);
    border-radius: 3px;
}

.vbp-components-list-wrapper::-webkit-scrollbar-thumb:hover {
    background-color: var(--vbp-text-secondary, #9ca3af);
}

/* Animación de spin para el loader */
@keyframes vbp-spin {
    to { transform: rotate(360deg); }
}

/* Estilos para las tarjetas de componentes */
.vbp-component-card {
    position: relative;
    transition: all 0.2s ease;
}

.vbp-component-card:hover {
    border-color: var(--vbp-accent-color, #6366f1) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Botones de categoría */
.vbp-category-btn {
    transition: all 0.15s ease;
}

.vbp-category-btn:hover:not(.active) {
    background: var(--vbp-bg-secondary, #f3f4f6) !important;
}
</style>

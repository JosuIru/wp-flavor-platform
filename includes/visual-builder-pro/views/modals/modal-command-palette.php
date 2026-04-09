<?php
/**
 * Visual Builder Pro - Modal Paleta de Comandos
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Modal Paleta de Comandos -->
<div x-data="vbpCommandPaletteModal()"
     x-show="$store.vbpCommandPalette.isOpen"
     x-cloak
     class="vbp-command-overlay"
     @click.self="close()"
     @keydown.escape.window="close()">

    <div class="vbp-command-modal" @click.stop>
        <!-- Input de búsqueda -->
        <div class="vbp-command-header">
            <svg class="vbp-command-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text"
                   class="vbp-command-input"
                   x-model="query"
                   @keydown="handleKeydown($event)"
                   placeholder="<?php esc_attr_e( 'Buscar comandos, bloques, acciones...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                   autofocus>
            <div class="vbp-command-shortcut">
                <kbd>Esc</kbd>
            </div>
        </div>

        <!-- Resultados -->
        <div class="vbp-command-results">
            <template x-if="filteredCommands.length === 0">
                <div class="vbp-command-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <p><?php esc_html_e( 'No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                </div>
            </template>

            <template x-for="(cmd, index) in filteredCommands" :key="cmd.id">
                <button type="button"
                        class="vbp-command-item"
                        :class="{ 'active': activeIndex === index }"
                        @click="executeCommand(cmd)"
                        @mouseenter="setActive(index)">
                    <span class="vbp-command-icon" x-text="cmd.icon"></span>
                    <span class="vbp-command-info">
                        <span class="vbp-command-label" x-text="cmd.label"></span>
                        <span class="vbp-command-category" x-text="$store.vbpCommandPalette.getCategoryLabel(cmd.category)"></span>
                    </span>
                    <span class="vbp-command-shortcut-hint" x-show="cmd.shortcut" x-text="cmd.shortcut"></span>
                </button>
            </template>
        </div>

        <!-- Footer con ayuda -->
        <div class="vbp-command-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> <?php esc_html_e( 'navegar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <span><kbd>↵</kbd> <?php esc_html_e( 'seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
            <span><kbd>Esc</kbd> <?php esc_html_e( 'cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </div>
    </div>
</div>

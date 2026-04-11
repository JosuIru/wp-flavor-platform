<?php
/**
 * Visual Builder Pro - Panel de Símbolos
 *
 * Panel para gestionar símbolos reutilizables (componentes maestros con instancias vinculadas).
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-symbols-container" x-data="vbpSymbolsPanel()" role="region" aria-label="<?php esc_attr_e( 'Gestión de símbolos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
    <!-- Cabecera del panel -->
    <div class="vbp-symbols-header">
        <div class="vbp-symbols-header-title">
            <svg class="vbp-symbols-header-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polygon points="12,2 2,7 12,12 22,7"/>
                <polyline points="2,17 12,22 22,17"/>
                <polyline points="2,12 12,17 22,12"/>
            </svg>
            <span><?php esc_html_e( 'Símbolos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </div>
        <button
            type="button"
            @click="createFromSelection()"
            class="vbp-btn-icon vbp-symbols-create-btn"
            title="<?php esc_attr_e( 'Crear símbolo desde selección (Ctrl+Shift+K)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            aria-label="<?php esc_attr_e( 'Crear símbolo desde selección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            :disabled="!canCreateFromSelection"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </button>
    </div>

    <!-- Búsqueda -->
    <div class="vbp-symbols-search">
        <div class="vbp-symbols-search-input-wrapper">
            <svg class="vbp-symbols-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input
                type="text"
                x-model="searchQuery"
                @input="filterSymbols()"
                class="vbp-symbols-search-input"
                placeholder="<?php esc_attr_e( 'Buscar símbolos...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                aria-label="<?php esc_attr_e( 'Buscar símbolos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            >
            <button
                type="button"
                x-show="searchQuery"
                @click="clearSearch()"
                class="vbp-symbols-search-clear"
                title="<?php esc_attr_e( 'Limpiar búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Filtro de categorías -->
    <div class="vbp-symbols-categories" role="tablist" aria-label="<?php esc_attr_e( 'Filtrar por categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
        <button
            type="button"
            @click="selectCategory('')"
            class="vbp-symbols-category-btn"
            :class="{ 'active': selectedCategory === '' }"
            role="tab"
            :aria-selected="selectedCategory === ''"
        >
            <?php esc_html_e( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
        </button>
        <template x-for="category in categories" :key="category.id">
            <button
                type="button"
                @click="selectCategory(category.id)"
                class="vbp-symbols-category-btn"
                :class="{ 'active': selectedCategory === category.id }"
                role="tab"
                :aria-selected="selectedCategory === category.id"
                x-text="category.name"
            ></button>
        </template>
    </div>

    <!-- Estado de carga -->
    <div x-show="loading" class="vbp-symbols-loading">
        <div class="vbp-symbols-spinner"></div>
        <span><?php esc_html_e( 'Cargando símbolos...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
    </div>

    <!-- Lista de símbolos -->
    <div
        x-show="!loading"
        class="vbp-symbols-list"
        role="listbox"
        aria-label="<?php esc_attr_e( 'Lista de símbolos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
    >
        <template x-for="symbol in filteredSymbols" :key="symbol.id">
            <div
                class="vbp-symbol-item"
                :class="{ 'selected': selectedSymbolId === symbol.id }"
                @click="selectSymbol(symbol)"
                @dblclick="insertSymbol(symbol.id)"
                draggable="true"
                @dragstart="handleDragStart($event, symbol.id)"
                @dragend="handleDragEnd($event)"
                role="option"
                :aria-selected="selectedSymbolId === symbol.id"
                :aria-label="symbol.name + ' - ' + getSymbolUsageCount(symbol.id) + ' instancias'"
                tabindex="0"
                @keydown.enter="insertSymbol(symbol.id)"
                @keydown.space.prevent="selectSymbol(symbol)"
            >
                <!-- Thumbnail/Preview -->
                <div class="vbp-symbol-thumbnail" :style="getThumbnailStyle(symbol)">
                    <template x-if="!symbol.thumbnail">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <polygon points="12,2 2,7 12,12 22,7"/>
                            <polyline points="2,17 12,22 22,17"/>
                            <polyline points="2,12 12,17 22,12"/>
                        </svg>
                    </template>
                </div>

                <!-- Información del símbolo -->
                <div class="vbp-symbol-info">
                    <span class="vbp-symbol-name" x-text="symbol.name"></span>
                    <span class="vbp-symbol-meta">
                        <span class="vbp-symbol-category" x-text="getCategoryName(symbol.category)"></span>
                    </span>
                </div>

                <!-- Badge de instancias -->
                <div
                    class="vbp-symbol-badge"
                    x-show="getSymbolUsageCount(symbol.id) > 0"
                    :title="getSymbolUsageCount(symbol.id) + ' instancias en esta página'"
                >
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M20 8v6M23 11h-6"/>
                    </svg>
                    <span x-text="getSymbolUsageCount(symbol.id)"></span>
                </div>

                <!-- Acciones del símbolo -->
                <div class="vbp-symbol-actions" @click.stop>
                    <button
                        type="button"
                        @click="insertSymbol(symbol.id)"
                        class="vbp-symbol-action-btn vbp-symbol-action-insert"
                        title="<?php esc_attr_e( 'Insertar instancia', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        aria-label="<?php esc_attr_e( 'Insertar instancia del símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="editSymbol(symbol.id)"
                        class="vbp-symbol-action-btn vbp-symbol-action-edit"
                        title="<?php esc_attr_e( 'Editar símbolo maestro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        aria-label="<?php esc_attr_e( 'Editar símbolo maestro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="showSymbolMenu(symbol, $event)"
                        class="vbp-symbol-action-btn vbp-symbol-action-more"
                        title="<?php esc_attr_e( 'Más opciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        aria-label="<?php esc_attr_e( 'Más opciones del símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        aria-haspopup="true"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="1"/>
                            <circle cx="19" cy="12" r="1"/>
                            <circle cx="5" cy="12" r="1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Empty state - sin símbolos -->
        <template x-if="!loading && symbols.length === 0">
            <div class="vbp-symbols-empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                    <polygon points="12,2 2,7 12,12 22,7"/>
                    <polyline points="2,17 12,22 22,17"/>
                    <polyline points="2,12 12,17 22,12"/>
                </svg>
                <p class="vbp-symbols-empty-title"><?php esc_html_e( 'Sin símbolos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                <p class="vbp-symbols-empty-text"><?php esc_html_e( 'Selecciona elementos y presiona Ctrl+Shift+K para crear un símbolo reutilizable.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                <button
                    type="button"
                    @click="createFromSelection()"
                    class="vbp-btn vbp-btn-sm vbp-btn-primary"
                    :disabled="!canCreateFromSelection"
                >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php esc_html_e( 'Crear símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </template>

        <!-- Empty state - sin resultados de búsqueda -->
        <template x-if="!loading && symbols.length > 0 && filteredSymbols.length === 0">
            <div class="vbp-symbols-empty vbp-symbols-no-results">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                    <line x1="8" y1="8" x2="14" y2="14"/>
                    <line x1="14" y1="8" x2="8" y2="14"/>
                </svg>
                <p class="vbp-symbols-empty-title"><?php esc_html_e( 'Sin resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                <p class="vbp-symbols-empty-text"><?php esc_html_e( 'No se encontraron símbolos que coincidan con tu búsqueda.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                <button
                    type="button"
                    @click="clearFilters()"
                    class="vbp-btn vbp-btn-sm vbp-btn-secondary"
                >
                    <?php esc_html_e( 'Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </template>
    </div>

    <!-- Menú contextual -->
    <div
        x-show="contextMenu.visible"
        x-transition:enter="vbp-dropdown-enter"
        x-transition:leave="vbp-dropdown-leave"
        @click.outside="closeContextMenu()"
        class="vbp-symbols-context-menu"
        :style="contextMenuStyle"
        role="menu"
        aria-label="<?php esc_attr_e( 'Opciones del símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
    >
        <button
            type="button"
            @click="duplicateSymbol(contextMenu.symbolId)"
            class="vbp-symbols-context-item"
            role="menuitem"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2"/>
                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
            </svg>
            <span><?php esc_html_e( 'Duplicar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </button>
        <button
            type="button"
            @click="renameSymbol(contextMenu.symbolId)"
            class="vbp-symbols-context-item"
            role="menuitem"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
            <span><?php esc_html_e( 'Renombrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </button>
        <button
            type="button"
            @click="unlinkAllInstances(contextMenu.symbolId)"
            class="vbp-symbols-context-item"
            role="menuitem"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M18.84 12.25l1.72-1.71a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M5.17 11.75l-1.71 1.71a5 5 0 007.07 7.07l1.71-1.71"/>
                <line x1="8" y1="2" x2="8" y2="5"/>
                <line x1="2" y1="8" x2="5" y2="8"/>
                <line x1="16" y1="19" x2="16" y2="22"/>
                <line x1="19" y1="16" x2="22" y2="16"/>
            </svg>
            <span><?php esc_html_e( 'Desvincular instancias', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </button>
        <div class="vbp-symbols-context-divider"></div>
        <button
            type="button"
            @click="deleteSymbol(contextMenu.symbolId)"
            class="vbp-symbols-context-item vbp-symbols-context-item--danger"
            role="menuitem"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="3,6 5,6 21,6"/>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                <line x1="10" y1="11" x2="10" y2="17"/>
                <line x1="14" y1="11" x2="14" y2="17"/>
            </svg>
            <span><?php esc_html_e( 'Eliminar símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        </button>
    </div>

    <!-- Keyboard shortcut hint -->
    <div class="vbp-symbols-shortcut-hint">
        <kbd class="vbp-kbd">Ctrl</kbd>
        <span>+</span>
        <kbd class="vbp-kbd">Shift</kbd>
        <span>+</span>
        <kbd class="vbp-kbd">K</kbd>
        <span><?php esc_html_e( 'para crear símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
    </div>
</div>

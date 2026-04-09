<?php
/**
 * Visual Builder Pro - Panel de Bloques Mejorado
 * Con vista grid/lista, favoritos, filtros rápidos y descripciones
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-blocks-container" x-data="vbpBlocksPanel()" role="region" aria-label="<?php esc_attr_e( 'Panel de bloques disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
    <div class="vbp-basic-panel-hint" x-show="$store.vbp.inspectorMode === 'basic'">
        <span class="vbp-basic-panel-hint__eyebrow"><?php esc_html_e( 'Modo básico', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
        <p><?php esc_html_e( 'Empieza arrastrando bloques de contenido. Las capas, componentes y herramientas avanzadas aparecen al cambiar de modo.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
    </div>

    <!-- Buscador -->
    <div class="vbp-blocks-search" role="search">
        <input
            type="search"
            x-model.debounce.200ms="searchQuery"
            placeholder="<?php esc_attr_e( 'Buscar bloques...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            class="vbp-search-input"
            aria-label="<?php esc_attr_e( 'Buscar bloques', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            aria-describedby="vbp-search-results"
            @keydown.escape="searchQuery = ''"
        >
        <svg class="vbp-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
        </svg>
        <button type="button" class="vbp-search-clear" x-show="searchQuery" @click="searchQuery = ''" aria-label="<?php esc_attr_e( 'Limpiar búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="vbp-search-results" class="vbp-sr-only" aria-live="polite" x-text="searchQuery ? (totalFilteredBlocks + ' bloques encontrados') : ''"></div>

    <!-- Barra de herramientas -->
    <div class="vbp-blocks-toolbar">
        <!-- Modos de vista -->
        <div class="vbp-blocks-toolbar-group">
            <button type="button"
                class="vbp-view-mode-btn"
                :class="{ 'active': viewMode === 'list' }"
                @click="setViewMode('list')"
                data-tooltip="<?php esc_attr_e( 'Vista lista', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                aria-label="<?php esc_attr_e( 'Vista lista', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
            </button>
            <button type="button"
                class="vbp-view-mode-btn"
                :class="{ 'active': viewMode === 'grid' }"
                @click="setViewMode('grid')"
                data-tooltip="<?php esc_attr_e( 'Vista cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                aria-label="<?php esc_attr_e( 'Vista cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                </svg>
            </button>
            <button type="button"
                class="vbp-view-mode-btn"
                :class="{ 'active': viewMode === 'compact' }"
                @click="setViewMode('compact')"
                data-tooltip="<?php esc_attr_e( 'Vista compacta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                aria-label="<?php esc_attr_e( 'Vista compacta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="vbp-blocks-toolbar-separator"></div>

        <!-- Expandir/Colapsar -->
        <div class="vbp-blocks-toolbar-group">
            <button type="button" class="vbp-toggle-all-btn" @click="expandAllCategories()" data-tooltip="<?php esc_attr_e( 'Expandir todo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"/></svg>
            </button>
            <button type="button" class="vbp-toggle-all-btn" @click="collapseAllCategories()" data-tooltip="<?php esc_attr_e( 'Colapsar todo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18,15 12,9 6,15"/></svg>
            </button>
        </div>
    </div>

    <!-- Filtros rápidos -->
    <div class="vbp-blocks-filters" x-show="!searchQuery">
        <button type="button"
            class="vbp-filter-chip"
            :class="{ 'active': activeFilter === 'all' }"
            @click="setFilter('all')"
        >
            <?php esc_html_e( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            <span class="vbp-filter-chip-count" x-text="totalBlocks"></span>
        </button>
        <button type="button"
            class="vbp-filter-chip"
            :class="{ 'active': activeFilter === 'favorites' }"
            @click="setFilter('favorites')"
            x-show="favoriteBlocks.length > 0"
        >
            <span class="vbp-filter-chip-icon">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </span>
            <?php esc_html_e( 'Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            <span class="vbp-filter-chip-count" x-text="favoriteBlocks.length"></span>
        </button>
        <template x-for="cat in mainFilterCategories" :key="cat.id">
            <button type="button"
                class="vbp-filter-chip"
                :class="{ 'active': activeFilter === cat.id }"
                @click="setFilter(cat.id)"
            >
                <span class="vbp-filter-chip-icon" x-html="cat.icon"></span>
                <span x-text="cat.name"></span>
                <span class="vbp-filter-chip-count" x-text="cat.blocks.length"></span>
            </button>
        </template>
    </div>

    <!-- Estado vacío si no hay categorías -->
    <template x-if="categories.length === 0">
        <div class="vbp-blocks-empty" style="padding: 20px; text-align: center; color: var(--vbp-text-muted);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin: 0 auto 12px; display: block; opacity: 0.5;">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v8M8 12h8"/>
            </svg>
            <p style="margin: 0;"><?php esc_html_e( 'No hay bloques disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
            <small style="opacity: 0.7;"><?php esc_html_e( 'Verifica la consola para más información', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
        </div>
    </template>

    <!-- Estado vacío de búsqueda -->
    <div class="vbp-blocks-empty-search" x-show="searchQuery && totalFilteredBlocks === 0">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <div class="vbp-blocks-empty-search-title"><?php esc_html_e( 'Sin resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></div>
        <div class="vbp-blocks-empty-search-hint">
            <?php esc_html_e( 'No se encontraron bloques para', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> "<span x-text="searchQuery"></span>"
        </div>
    </div>

    <!-- Lista de categorías y bloques -->
    <div class="vbp-blocks-list" :class="'view-' + viewMode" role="tree" aria-label="<?php esc_attr_e( 'Categorías de bloques', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" x-show="categories.length > 0 && (totalFilteredBlocks > 0 || !searchQuery)">

        <!-- Bloques Favoritos -->
        <div class="vbp-block-category vbp-favorites-category" role="treeitem" :aria-expanded="openCategories.includes('favorites')" x-show="activeFilter === 'all' && favoriteBlocks.length > 0 && !searchQuery">
            <button
                type="button"
                @click="toggleCategory('favorites')"
                class="vbp-category-header vbp-category-header--favorites"
                :class="{ 'open': openCategories.includes('favorites') }"
                :aria-expanded="openCategories.includes('favorites')"
            >
                <span class="vbp-category-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </span>
                <span class="vbp-category-name"><?php esc_html_e( 'Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <span class="vbp-category-count" x-text="favoriteBlocks.length"></span>
                <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <div class="vbp-category-blocks" x-show="openCategories.includes('favorites')" x-collapse>
                <template x-for="block in favoriteBlocks" :key="'fav-' + block.type">
                    <div
                        class="vbp-block-item"
                        :data-block-type="block.type"
                        draggable="true"
                        @dragstart="handleDragStart($event, block)"
                        @dragend="handleDragEnd($event)"
                        @click="addBlock(block)"
                        @mouseenter="showBlockPreview($event, block)"
                        @mouseleave="hideBlockPreview()"
                        role="treeitem"
                        tabindex="0"
                        @keydown.enter="addBlock(block)"
                        @keydown.space.prevent="addBlock(block)"
                        style="position: relative;"
                    >
                        <button type="button" class="vbp-favorite-btn is-favorite" @click.stop="toggleFavorite(block)" aria-label="<?php esc_attr_e( 'Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </button>
                        <span class="vbp-block-icon" x-html="block.icon" aria-hidden="true"></span>
                        <span class="vbp-block-name" x-text="block.name"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Bloques Recientes -->
        <div class="vbp-block-category vbp-recent-blocks-category" role="treeitem" :aria-expanded="openCategories.includes('recent')" x-show="activeFilter === 'all' && recentBlocks.length > 0 && !searchQuery">
            <button
                type="button"
                @click="toggleCategory('recent')"
                class="vbp-category-header vbp-category-header--recent"
                :class="{ 'open': openCategories.includes('recent') }"
                :aria-expanded="openCategories.includes('recent')"
            >
                <span class="vbp-category-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </span>
                <span class="vbp-category-name"><?php esc_html_e( 'Usados recientemente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <span class="vbp-category-count" x-text="recentBlocks.length"></span>
                <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <div class="vbp-category-blocks" x-show="openCategories.includes('recent')" x-collapse>
                <template x-for="block in recentBlocks" :key="'recent-' + block.type">
                    <div
                        class="vbp-block-item"
                        :data-block-type="block.type"
                        draggable="true"
                        @dragstart="handleDragStart($event, block)"
                        @dragend="handleDragEnd($event)"
                        @click="addBlock(block)"
                        @mouseenter="showBlockPreview($event, block)"
                        @mouseleave="hideBlockPreview()"
                        role="treeitem"
                        tabindex="0"
                        @keydown.enter="addBlock(block)"
                        @keydown.space.prevent="addBlock(block)"
                        style="position: relative;"
                    >
                        <button type="button" class="vbp-favorite-btn" :class="{ 'is-favorite': isFavorite(block.type) }" @click.stop="toggleFavorite(block)" :aria-label="isFavorite(block.type) ? '<?php esc_attr_e( 'Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                            <svg width="12" height="12" viewBox="0 0 24 24" :fill="isFavorite(block.type) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </button>
                        <span class="vbp-block-icon" x-html="block.icon" aria-hidden="true"></span>
                        <span class="vbp-block-name" x-text="block.name"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Categorías normales -->
        <template x-for="category in displayedCategories" :key="category.id">
            <div class="vbp-block-category" :data-category="category.id" role="treeitem" :aria-expanded="openCategories.includes(category.id)" x-show="category.blocks.length > 0">
                <button
                    type="button"
                    @click="toggleCategory(category.id)"
                    class="vbp-category-header"
                    :class="{ 'open': openCategories.includes(category.id) }"
                    :aria-expanded="openCategories.includes(category.id)"
                    :aria-controls="'vbp-cat-' + category.id"
                >
                    <span class="vbp-category-icon" x-html="category.icon" aria-hidden="true"></span>
                    <span class="vbp-category-name" x-text="category.name"></span>
                    <span class="vbp-category-count" x-text="category.blocks.length"></span>
                    <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>

                <div class="vbp-category-blocks" x-show="openCategories.includes(category.id)" x-collapse :id="'vbp-cat-' + category.id" role="group" :aria-label="category.name">
                    <template x-for="block in category.blocks" :key="block.type">
                        <div
                            class="vbp-block-item"
                            :data-block-type="block.type"
                            :style="'--block-category-color: ' + getCategoryColor(category.id)"
                            draggable="true"
                            @dragstart="handleDragStart($event, block)"
                            @dragend="handleDragEnd($event)"
                            @click="addBlock(block)"
                            @mouseenter="showBlockPreview($event, block)"
                            @mouseleave="hideBlockPreview()"
                            role="treeitem"
                            tabindex="0"
                            @keydown.enter="addBlock(block)"
                            @keydown.space.prevent="addBlock(block)"
                            :aria-label="block.name + ' - ' + '<?php esc_attr_e( 'Clic o arrastra para añadir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"
                            style="position: relative;"
                        >
                            <button type="button" class="vbp-favorite-btn" :class="{ 'is-favorite': isFavorite(block.type) }" @click.stop="toggleFavorite(block)" :aria-label="isFavorite(block.type) ? '<?php esc_attr_e( 'Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'">
                                <svg width="12" height="12" viewBox="0 0 24 24" :fill="isFavorite(block.type) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </button>
                            <span class="vbp-block-icon" x-html="block.icon" aria-hidden="true"></span>
                            <span class="vbp-block-name" x-text="block.name"></span>
                            <template x-if="block.module">
                                <span class="vbp-block-module-badge" x-text="block.module"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Widgets Globales -->
        <div class="vbp-block-category vbp-global-widgets-category" role="treeitem" :aria-expanded="openCategories.includes('global-widgets')" x-show="(activeFilter === 'all' || activeFilter === 'global-widgets') && (globalWidgets.length > 0 || !globalWidgetsLoaded)">
            <button
                type="button"
                @click="toggleCategory('global-widgets')"
                class="vbp-category-header vbp-category-header--global"
                :class="{ 'open': openCategories.includes('global-widgets') }"
            >
                <span class="vbp-category-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v8M8 12h8"/>
                    </svg>
                </span>
                <span class="vbp-category-name"><?php esc_html_e( 'Widgets Globales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <span class="vbp-category-count" x-text="globalWidgets.length" x-show="globalWidgets.length > 0"></span>
                <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <div class="vbp-category-blocks" x-show="openCategories.includes('global-widgets')" x-collapse>
                <!-- Loading state -->
                <div x-show="!globalWidgetsLoaded" class="vbp-global-widgets-loading">
                    <span class="vbp-loading-spinner"></span>
                    <span><?php esc_html_e( 'Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                </div>

                <!-- Empty state -->
                <div x-show="globalWidgetsLoaded && globalWidgets.length === 0" class="vbp-global-widgets-empty">
                    <p><?php esc_html_e( 'No hay widgets globales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                    <small><?php esc_html_e( 'Selecciona un elemento y usa Ctrl+Shift+G para guardarlo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></small>
                </div>

                <!-- Lista de widgets globales -->
                <template x-for="widget in filteredGlobalWidgets" :key="widget.id">
                    <div
                        class="vbp-block-item vbp-global-widget-item"
                        :data-global-widget-id="widget.id"
                        draggable="true"
                        @dragstart="handleGlobalWidgetDragStart($event, widget)"
                        @dragend="handleDragEnd($event)"
                        @click="insertGlobalWidget(widget)"
                        role="treeitem"
                        tabindex="0"
                        @keydown.enter="insertGlobalWidget(widget)"
                    >
                        <span class="vbp-block-icon vbp-global-widget-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 12h8M12 8v8"/>
                            </svg>
                        </span>
                        <span class="vbp-block-name" x-text="widget.title"></span>
                        <span class="vbp-global-widget-type" x-text="widget.type"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Contador de bloques -->
    <div class="vbp-blocks-count" x-show="!searchQuery">
        <strong x-text="totalBlocks"></strong> <?php esc_html_e( 'bloques en', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
        <strong x-text="categories.length"></strong> <?php esc_html_e( 'categorías', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
    </div>
</div>

<!-- Tooltip de Preview -->
<div id="vbp-block-preview-tooltip" class="vbp-block-preview-tooltip" x-data="{ visible: false }" x-show="visible" style="display: none;">
    <div class="vbp-block-preview-tooltip-header">
        <div class="vbp-block-preview-tooltip-icon" id="preview-icon"></div>
        <div>
            <div class="vbp-block-preview-tooltip-name" id="preview-name"></div>
            <div class="vbp-block-preview-tooltip-type" id="preview-type"></div>
        </div>
    </div>
    <div class="vbp-block-preview-tooltip-description" id="preview-description"></div>
    <div class="vbp-block-preview-tooltip-hint">
        <kbd>Clic</kbd> <?php esc_html_e( 'para añadir', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> · <kbd>Arrastra</kbd> <?php esc_html_e( 'al canvas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    // Debug: verificar VBP_Config
    if (typeof VBP_Config !== 'undefined') {
        console.log('[VBP] VBP_Config.blocks:', VBP_Config.blocks);
        console.log('[VBP] Total categorías:', VBP_Config.blocks ? VBP_Config.blocks.length : 0);
    } else {
        console.warn('[VBP] VBP_Config no está definido');
    }

    Alpine.data('vbpBlocksPanel', () => ({
        searchQuery: '',
        viewMode: 'list', // 'list', 'grid', 'compact'
        activeFilter: 'all',
        openCategories: ['favorites', 'recent', 'sections', 'basic'],
        globalWidgets: [],
        globalWidgetsLoaded: false,
        recentBlocks: [],
        favoriteBlocks: [],
        maxRecentBlocks: 8,
        previewTimeout: null,

        // Colores por categoría
        categoryColors: {
            'sections': '#6366f1',
            'layout': '#8b5cf6',
            'basic': '#3b82f6',
            'media': '#10b981',
            'forms': '#f59e0b',
            'modules': '#ef4444',
            'interactive': '#ec4899',
            'advanced': '#64748b',
            'default': '#6366f1'
        },

        // Descripciones de bloques
        blockDescriptions: {
            'hero': '<?php echo esc_js( __( 'Sección principal con imagen de fondo, título y llamada a la acción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'features': '<?php echo esc_js( __( 'Grid de características o servicios con iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'cta': '<?php echo esc_js( __( 'Llamada a la acción con botón destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'heading': '<?php echo esc_js( __( 'Encabezado de texto H1-H6', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'text': '<?php echo esc_js( __( 'Bloque de párrafo con texto enriquecido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'image': '<?php echo esc_js( __( 'Imagen con opciones de tamaño y alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'button': '<?php echo esc_js( __( 'Botón personalizable con enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'video': '<?php echo esc_js( __( 'Reproductor de vídeo embebido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'gallery': '<?php echo esc_js( __( 'Galería de imágenes con lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'columns': '<?php echo esc_js( __( 'Layout de columnas flexible', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'container': '<?php echo esc_js( __( 'Contenedor con ancho máximo centrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'spacer': '<?php echo esc_js( __( 'Espaciador vertical ajustable', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'divider': '<?php echo esc_js( __( 'Línea divisoria horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'accordion': '<?php echo esc_js( __( 'Panel colapsable tipo FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'tabs': '<?php echo esc_js( __( 'Contenido en pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'form': '<?php echo esc_js( __( 'Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'map': '<?php echo esc_js( __( 'Mapa de Google embebido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'testimonials': '<?php echo esc_js( __( 'Carrusel de testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'pricing': '<?php echo esc_js( __( 'Tabla de precios', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'team': '<?php echo esc_js( __( 'Grid de miembros del equipo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'countdown': '<?php echo esc_js( __( 'Contador regresivo animado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'icon': '<?php echo esc_js( __( 'Icono decorativo o funcional', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'social': '<?php echo esc_js( __( 'Enlaces a redes sociales', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'slider': '<?php echo esc_js( __( 'Carrusel de imágenes o contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
            'module_shortcode': '<?php echo esc_js( __( 'Shortcode de módulo Flavor', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'
        },

        // Cargar categorías desde VBP_Config si están disponibles, con fallback
        categories: (typeof VBP_Config !== 'undefined' && VBP_Config.blocks && VBP_Config.blocks.length > 0)
            ? VBP_Config.blocks.map(function(cat) {
                return {
                    id: cat.id,
                    name: cat.name,
                    icon: cat.icon || '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                    blocks: (cat.blocks || []).map(function(block) {
                        return {
                            type: block.id,
                            name: block.name,
                            icon: block.icon || '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                            shortcode: block.shortcode || '',
                            module: block.module || '',
                            category: cat.id
                        };
                    })
                };
            })
            : [
                // Fallback a categorías básicas
                {
                    id: 'sections',
                    name: '<?php echo esc_js( __( 'Secciones', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
                    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>',
                    blocks: [
                        { type: 'hero', name: 'Hero', category: 'sections', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>' },
                        { type: 'features', name: 'Features', category: 'sections', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>' },
                        { type: 'cta', name: 'CTA', category: 'sections', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>' }
                    ]
                },
                {
                    id: 'basic',
                    name: '<?php echo esc_js( __( 'Básicos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
                    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
                    blocks: [
                        { type: 'heading', name: 'Encabezado', category: 'basic', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>' },
                        { type: 'text', name: 'Texto', category: 'basic', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4,7 4,4 20,4 20,7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>' },
                        { type: 'image', name: 'Imagen', category: 'basic', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>' },
                        { type: 'button', name: 'Botón', category: 'basic', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>' }
                    ]
                }
            ],

        // Filtros principales (primeras 4 categorías)
        get mainFilterCategories() {
            return this.categories.slice(0, 4);
        },

        // Total de bloques
        get totalBlocks() {
            return this.categories.reduce((sum, cat) => sum + cat.blocks.length, 0);
        },

        // Total de bloques filtrados
        get totalFilteredBlocks() {
            return this.displayedCategories.reduce((sum, cat) => sum + cat.blocks.length, 0);
        },

        // Categorías a mostrar según filtros
        get displayedCategories() {
            let cats = this.categories;

            // Filtrar por búsqueda
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                cats = cats.map(cat => ({
                    ...cat,
                    blocks: cat.blocks.filter(block =>
                        block.name.toLowerCase().includes(query) ||
                        block.type.toLowerCase().includes(query) ||
                        (this.blockDescriptions[block.type] || '').toLowerCase().includes(query)
                    )
                })).filter(cat => cat.blocks.length > 0);
                return cats;
            }

            // Filtrar por categoría
            if (this.activeFilter !== 'all' && this.activeFilter !== 'favorites') {
                cats = cats.filter(cat => cat.id === this.activeFilter);
            }

            return cats;
        },

        get filteredCategories() {
            return this.displayedCategories;
        },

        // Setters
        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('vbp_blocks_view_mode', mode);
        },

        setFilter(filter) {
            this.activeFilter = filter;
            // Auto-expandir categoría seleccionada
            if (filter !== 'all' && filter !== 'favorites' && !this.openCategories.includes(filter)) {
                this.openCategories.push(filter);
            }
            if (filter === 'global-widgets') {
                this.ensureGlobalWidgetsLoaded();
            }
        },

        expandAllCategories() {
            this.openCategories = ['favorites', 'recent', 'global-widgets', ...this.categories.map(c => c.id)];
            this.ensureGlobalWidgetsLoaded();
        },

        collapseAllCategories() {
            this.openCategories = [];
        },

        toggleCategory(categoryId) {
            const index = this.openCategories.indexOf(categoryId);
            if (index > -1) {
                this.openCategories.splice(index, 1);
            } else {
                this.openCategories.push(categoryId);
                if (categoryId === 'global-widgets') {
                    this.ensureGlobalWidgetsLoaded();
                }
            }
        },

        getCategoryColor(categoryId) {
            return this.categoryColors[categoryId] || this.categoryColors.default;
        },

        // Favoritos
        isFavorite(blockType) {
            return this.favoriteBlocks.some(b => b.type === blockType);
        },

        toggleFavorite(block) {
            const index = this.favoriteBlocks.findIndex(b => b.type === block.type);
            if (index > -1) {
                this.favoriteBlocks.splice(index, 1);
            } else {
                this.favoriteBlocks.push({
                    type: block.type,
                    name: block.name,
                    icon: block.icon,
                    category: block.category
                });
            }
            this.saveFavorites();
        },

        loadFavorites() {
            try {
                const stored = localStorage.getItem('vbp_favorite_blocks');
                if (stored) {
                    this.favoriteBlocks = JSON.parse(stored);
                }
            } catch (e) {
                console.warn('Error cargando favoritos:', e);
            }
        },

        saveFavorites() {
            try {
                localStorage.setItem('vbp_favorite_blocks', JSON.stringify(this.favoriteBlocks));
            } catch (e) {
                console.warn('Error guardando favoritos:', e);
            }
        },

        // Preview tooltip
        showBlockPreview(event, block) {
            const self = this;
            clearTimeout(this.previewTimeout);

            this.previewTimeout = setTimeout(function() {
                const tooltip = document.getElementById('vbp-block-preview-tooltip');
                if (!tooltip) return;

                // Actualizar contenido
                const iconEl = tooltip.querySelector('#preview-icon');
                const nameEl = tooltip.querySelector('#preview-name');
                const typeEl = tooltip.querySelector('#preview-type');
                const descEl = tooltip.querySelector('#preview-description');

                if (iconEl) iconEl.innerHTML = block.icon;
                if (nameEl) nameEl.textContent = block.name;
                if (typeEl) typeEl.textContent = block.type;
                if (descEl) descEl.textContent = self.blockDescriptions[block.type] || '<?php echo esc_js( __( 'Bloque de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>';

                // Posicionar
                const rect = event.target.closest('.vbp-block-item').getBoundingClientRect();
                tooltip.style.left = (rect.right + 10) + 'px';
                tooltip.style.top = rect.top + 'px';

                // Si se sale por la derecha, mostrar a la izquierda
                if (rect.right + 300 > window.innerWidth) {
                    tooltip.style.left = (rect.left - 290) + 'px';
                }

                tooltip.style.display = 'block';
                setTimeout(function() {
                    tooltip.classList.add('visible');
                }, 10);
            }, 400);
        },

        hideBlockPreview() {
            clearTimeout(this.previewTimeout);
            const tooltip = document.getElementById('vbp-block-preview-tooltip');
            if (tooltip) {
                tooltip.classList.remove('visible');
                setTimeout(function() {
                    tooltip.style.display = 'none';
                }, 200);
            }
        },

        // Drag & Drop
        handleDragStart(event, block) {
            event.dataTransfer.setData('text/vbp-block-type', block.type);
            event.dataTransfer.setData('application/json', JSON.stringify(block));
            event.dataTransfer.effectAllowed = 'copy';
            event.target.classList.add('dragging');
            document.body.classList.add('vbp-dragging', 'vbp-dragging-new');
        },

        handleDragEnd(event) {
            event.target.classList.remove('dragging');
            document.body.classList.remove('vbp-dragging', 'vbp-dragging-new');
        },

        addBlock(block) {
            Alpine.store('vbp').addElement(block.type);
            this.addToRecent(block);
        },

        addToRecent(block) {
            var existingIndex = this.recentBlocks.findIndex(b => b.type === block.type);
            if (existingIndex > -1) {
                this.recentBlocks.splice(existingIndex, 1);
            }
            this.recentBlocks.unshift({
                type: block.type,
                name: block.name,
                icon: block.icon,
                category: block.category
            });
            if (this.recentBlocks.length > this.maxRecentBlocks) {
                this.recentBlocks = this.recentBlocks.slice(0, this.maxRecentBlocks);
            }
            this.saveRecentBlocks();
        },

        loadRecentBlocks() {
            try {
                var stored = localStorage.getItem('vbp_recent_blocks');
                if (stored) {
                    this.recentBlocks = JSON.parse(stored);
                }
            } catch (e) {
                console.warn('Error cargando bloques recientes:', e);
            }
        },

        saveRecentBlocks() {
            try {
                localStorage.setItem('vbp_recent_blocks', JSON.stringify(this.recentBlocks));
            } catch (e) {
                console.warn('Error guardando bloques recientes:', e);
            }
        },

        // Inicialización
        init() {
            this.loadRecentBlocks();
            this.loadFavorites();

            // Cargar preferencias de vista
            const savedViewMode = localStorage.getItem('vbp_blocks_view_mode');
            if (savedViewMode) {
                this.viewMode = savedViewMode;
            }
        },

        get filteredGlobalWidgets() {
            if (!this.searchQuery) {
                return this.globalWidgets;
            }
            const query = this.searchQuery.toLowerCase();
            return this.globalWidgets.filter(widget =>
                widget.title.toLowerCase().includes(query) ||
                widget.type.toLowerCase().includes(query)
            );
        },

        ensureGlobalWidgetsLoaded(force = false) {
            if (force) {
                this.globalWidgetsLoaded = false;
            }

            if (this.globalWidgetsLoaded) {
                return Promise.resolve(this.globalWidgets);
            }

            return this.loadGlobalWidgets();
        },

        loadGlobalWidgets() {
            var self = this;
            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                self.globalWidgetsLoaded = true;
                return Promise.resolve([]);
            }

            return fetch(VBP_Config.restUrl + 'global-widgets', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (!response.ok || !contentType || !contentType.includes('application/json')) {
                    throw new Error('Error al cargar widgets globales');
                }
                return response.json();
            })
            .then(function(data) {
                self.globalWidgets = Array.isArray(data) ? data : [];
                self.globalWidgetsLoaded = true;
                return self.globalWidgets;
            })
            .catch(function(error) {
                console.warn('Error cargando widgets globales:', error.message);
                self.globalWidgets = [];
                self.globalWidgetsLoaded = true;
                return self.globalWidgets;
            });
        },

        handleGlobalWidgetDragStart(event, widget) {
            event.dataTransfer.setData('text/vbp-global-widget-id', widget.id);
            event.dataTransfer.setData('application/json', JSON.stringify({
                type: 'global_widget',
                globalWidgetId: widget.id,
                title: widget.title
            }));
            event.dataTransfer.effectAllowed = 'copy';
            event.target.classList.add('dragging');
            document.body.classList.add('vbp-dragging', 'vbp-dragging-new');
        },

        insertGlobalWidget(widget) {
            var store = Alpine.store('vbp');

            var newElement = {
                id: 'el_' + Date.now(),
                type: 'global_widget',
                name: widget.title,
                data: {
                    globalWidgetId: widget.id,
                    originalType: widget.type
                },
                styles: {},
                visible: true,
                locked: false
            };

            store.addElement(newElement);

            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('<?php echo esc_js( __( 'Widget global insertado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>', 'success');
            }
        }
    }));
});
</script>

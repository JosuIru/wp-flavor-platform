<?php
/**
 * Visual Builder Pro - Panel de Bloques
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-blocks-container" x-data="vbpBlocksPanel()" role="region" aria-label="<?php esc_attr_e( 'Panel de bloques disponibles', 'flavor-chat-ia' ); ?>">
    <!-- Buscador -->
    <div class="vbp-blocks-search" role="search">
        <input
            type="search"
            x-model.debounce.200ms="searchQuery"
            placeholder="<?php esc_attr_e( 'Buscar bloques...', 'flavor-chat-ia' ); ?>"
            class="vbp-search-input"
            aria-label="<?php esc_attr_e( 'Buscar bloques', 'flavor-chat-ia' ); ?>"
            aria-describedby="vbp-search-results"
        >
        <svg class="vbp-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
        </svg>
    </div>
    <div id="vbp-search-results" class="vbp-sr-only" aria-live="polite" x-text="searchQuery ? (filteredCategories.reduce((acc, cat) => acc + cat.blocks.length, 0) + ' bloques encontrados') : ''"></div>

    <!-- Estado vacío si no hay categorías -->
    <template x-if="categories.length === 0">
        <div class="vbp-blocks-empty" style="padding: 20px; text-align: center; color: var(--vbp-text-muted);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin: 0 auto 12px; display: block; opacity: 0.5;">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v8M8 12h8"/>
            </svg>
            <p style="margin: 0;"><?php esc_html_e( 'No hay bloques disponibles', 'flavor-chat-ia' ); ?></p>
            <small style="opacity: 0.7;"><?php esc_html_e( 'Verifica la consola para más información', 'flavor-chat-ia' ); ?></small>
        </div>
    </template>

    <!-- Lista de categorías y bloques -->
    <div class="vbp-blocks-list" role="tree" aria-label="<?php esc_attr_e( 'Categorías de bloques', 'flavor-chat-ia' ); ?>" x-show="categories.length > 0">

        <!-- Bloques Recientes -->
        <div class="vbp-block-category vbp-recent-blocks-category" role="treeitem" :aria-expanded="openCategories.includes('recent')" x-show="recentBlocks.length > 0 && !searchQuery">
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
                <span class="vbp-category-name"><?php esc_html_e( 'Recientes', 'flavor-chat-ia' ); ?></span>
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
                        role="treeitem"
                        tabindex="0"
                        @keydown.enter="addBlock(block)"
                        @keydown.space.prevent="addBlock(block)"
                    >
                        <span class="vbp-block-icon" x-html="block.icon" aria-hidden="true"></span>
                        <span class="vbp-block-name" x-text="block.name"></span>
                    </div>
                </template>
            </div>
        </div>

        <template x-for="category in filteredCategories" :key="category.id">
            <div class="vbp-block-category" role="treeitem" :aria-expanded="openCategories.includes(category.id)">
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
                    <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>

                <div class="vbp-category-blocks" x-show="openCategories.includes(category.id)" x-collapse :id="'vbp-cat-' + category.id" role="group" :aria-label="category.name">
                    <template x-for="block in category.blocks" :key="block.type">
                        <div
                            class="vbp-block-item"
                            :data-block-type="block.type"
                            draggable="true"
                            @dragstart="handleDragStart($event, block)"
                            @dragend="handleDragEnd($event)"
                            @click="addBlock(block)"
                            role="treeitem"
                            tabindex="0"
                            @keydown.enter="addBlock(block)"
                            @keydown.space.prevent="addBlock(block)"
                            :aria-label="block.name + ' - ' + '<?php esc_attr_e( 'Clic o arrastra para añadir', 'flavor-chat-ia' ); ?>'"
                        >
                            <span class="vbp-block-icon" x-html="block.icon" aria-hidden="true"></span>
                            <span class="vbp-block-name" x-text="block.name"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Widgets Globales -->
        <div class="vbp-block-category vbp-global-widgets-category" role="treeitem" :aria-expanded="openCategories.includes('global-widgets')" x-show="globalWidgets.length > 0 || !globalWidgetsLoaded">
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
                <span class="vbp-category-name"><?php esc_html_e( 'Widgets Globales', 'flavor-chat-ia' ); ?></span>
                <span class="vbp-category-count" x-text="globalWidgets.length" x-show="globalWidgets.length > 0"></span>
                <svg class="vbp-category-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <div class="vbp-category-blocks" x-show="openCategories.includes('global-widgets')" x-collapse>
                <!-- Loading state -->
                <div x-show="!globalWidgetsLoaded" class="vbp-global-widgets-loading">
                    <span class="vbp-loading-spinner"></span>
                    <span><?php esc_html_e( 'Cargando...', 'flavor-chat-ia' ); ?></span>
                </div>

                <!-- Empty state -->
                <div x-show="globalWidgetsLoaded && globalWidgets.length === 0" class="vbp-global-widgets-empty">
                    <p><?php esc_html_e( 'No hay widgets globales', 'flavor-chat-ia' ); ?></p>
                    <small><?php esc_html_e( 'Selecciona un elemento y usa Ctrl+Shift+G para guardarlo', 'flavor-chat-ia' ); ?></small>
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
        openCategories: ['recent', 'sections', 'basic'],
        globalWidgets: [],
        globalWidgetsLoaded: false,
        recentBlocks: [],
        maxRecentBlocks: 8,

        // Cargar categorías desde VBP_Config si están disponibles, con fallback
        categories: (typeof VBP_Config !== 'undefined' && VBP_Config.blocks && VBP_Config.blocks.length > 0)
            ? VBP_Config.blocks.map(function(cat) {
                // Formatear los bloques para compatibilidad con el panel
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
                            module: block.module || ''
                        };
                    })
                };
            })
            : [
                // Fallback a categorías básicas si VBP_Config no está disponible
                {
                    id: 'sections',
                    name: '<?php echo esc_js( __( 'Secciones', 'flavor-chat-ia' ) ); ?>',
                    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>',
                    blocks: [
                        { type: 'hero', name: 'Hero', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>' },
                        { type: 'features', name: 'Features', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>' },
                        { type: 'cta', name: 'CTA', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>' }
                    ]
                },
                {
                    id: 'basic',
                    name: '<?php echo esc_js( __( 'Básicos', 'flavor-chat-ia' ) ); ?>',
                    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
                    blocks: [
                        { type: 'heading', name: 'Encabezado', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>' },
                        { type: 'text', name: 'Texto', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4,7 4,4 20,4 20,7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>' },
                        { type: 'image', name: 'Imagen', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>' },
                        { type: 'button', name: 'Botón', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>' }
                    ]
                }
            ],

        get filteredCategories() {
            if (!this.searchQuery) {
                return this.categories;
            }

            const query = this.searchQuery.toLowerCase();
            return this.categories.map(cat => ({
                ...cat,
                blocks: cat.blocks.filter(block => 
                    block.name.toLowerCase().includes(query) ||
                    block.type.toLowerCase().includes(query)
                )
            })).filter(cat => cat.blocks.length > 0);
        },

        toggleCategory(categoryId) {
            const index = this.openCategories.indexOf(categoryId);
            if (index > -1) {
                this.openCategories.splice(index, 1);
            } else {
                this.openCategories.push(categoryId);
            }
        },

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
            // Evitar duplicados
            var existingIndex = this.recentBlocks.findIndex(b => b.type === block.type);
            if (existingIndex > -1) {
                this.recentBlocks.splice(existingIndex, 1);
            }

            // Agregar al inicio
            this.recentBlocks.unshift({
                type: block.type,
                name: block.name,
                icon: block.icon
            });

            // Limitar cantidad
            if (this.recentBlocks.length > this.maxRecentBlocks) {
                this.recentBlocks = this.recentBlocks.slice(0, this.maxRecentBlocks);
            }

            // Guardar en localStorage
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
            this.loadGlobalWidgets();
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

        loadGlobalWidgets() {
            var self = this;
            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                self.globalWidgetsLoaded = true;
                return;
            }

            fetch(VBP_Config.restUrl + 'global-widgets', {
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
            })
            .catch(function(error) {
                console.warn('Error cargando widgets globales:', error.message);
                self.globalWidgets = [];
                self.globalWidgetsLoaded = true;
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
            var self = this;
            var store = Alpine.store('vbp');

            // Crear elemento de tipo global_widget
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

            // Notificar si hay acceso al sistema de notificaciones
            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('<?php echo esc_js( __( 'Widget global insertado', 'flavor-chat-ia' ) ); ?>', 'success');
            }
        }
    }));
});
</script>

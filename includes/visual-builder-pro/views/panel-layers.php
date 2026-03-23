<?php
/**
 * Visual Builder Pro - Panel de Capas
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="vbp-layers-container" x-data="vbpLayersComponent()" role="region" aria-label="<?php esc_attr_e( 'Gestión de capas', 'flavor-chat-ia' ); ?>">
    <!-- Búsqueda y filtros -->
    <div class="vbp-layers-search">
        <div class="vbp-layers-search-input-wrapper">
            <svg class="vbp-layers-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input
                type="text"
                x-model="searchQuery"
                class="vbp-layers-search-input"
                placeholder="<?php esc_attr_e( 'Buscar capas...', 'flavor-chat-ia' ); ?>"
                aria-label="<?php esc_attr_e( 'Buscar capas', 'flavor-chat-ia' ); ?>"
            >
            <button
                type="button"
                x-show="searchQuery"
                @click="searchQuery = ''"
                class="vbp-layers-search-clear"
                title="<?php esc_attr_e( 'Limpiar búsqueda', 'flavor-chat-ia' ); ?>"
            >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <button
            type="button"
            @click="toggleFilters()"
            class="vbp-btn-icon vbp-layers-filter-btn"
            :class="{ 'active': showFilters || filterType }"
            title="<?php esc_attr_e( 'Filtros', 'flavor-chat-ia' ); ?>"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/>
            </svg>
        </button>
    </div>

    <!-- Panel de filtros colapsable -->
    <div x-show="showFilters" x-collapse class="vbp-layers-filters">
        <label class="vbp-layers-filter-label"><?php esc_html_e( 'Tipo', 'flavor-chat-ia' ); ?></label>
        <select x-model="filterType" class="vbp-layers-filter-select">
            <option value=""><?php esc_html_e( 'Todos', 'flavor-chat-ia' ); ?></option>
            <template x-for="tipo in availableTypes" :key="tipo">
                <option :value="tipo" x-text="tipo"></option>
            </template>
        </select>
        <button
            type="button"
            x-show="hasActiveFilters"
            @click="clearFilters()"
            class="vbp-btn vbp-btn-sm vbp-btn-secondary"
        >
            <?php esc_html_e( 'Limpiar', 'flavor-chat-ia' ); ?>
        </button>
    </div>

    <!-- Contador de resultados -->
    <div x-show="hasActiveFilters" class="vbp-layers-filter-count">
        <span x-text="filterCount"></span> <?php esc_html_e( 'de', 'flavor-chat-ia' ); ?> <span x-text="elements.length"></span>
    </div>

    <!-- Toolbar de capas -->
    <div class="vbp-layers-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Acciones de capas', 'flavor-chat-ia' ); ?>">
        <button type="button" @click="selectAll()" class="vbp-btn-icon" title="<?php esc_attr_e( 'Seleccionar todo', 'flavor-chat-ia' ); ?>" aria-label="<?php esc_attr_e( 'Seleccionar todas las capas', 'flavor-chat-ia' ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 9h6v6H9z"/>
            </svg>
        </button>
        <button type="button" @click="duplicateSelected()" :disabled="selectedElements.length === 0" class="vbp-btn-icon" title="<?php esc_attr_e( 'Duplicar selección', 'flavor-chat-ia' ); ?>" aria-label="<?php esc_attr_e( 'Duplicar capas seleccionadas', 'flavor-chat-ia' ); ?>" :aria-disabled="selectedElements.length === 0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2"/>
                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
            </svg>
        </button>
        <button type="button" @click="deleteSelected()" :disabled="selectedElements.length === 0" class="vbp-btn-icon vbp-btn-danger" title="<?php esc_attr_e( 'Eliminar selección', 'flavor-chat-ia' ); ?>" aria-label="<?php esc_attr_e( 'Eliminar capas seleccionadas', 'flavor-chat-ia' ); ?>" :aria-disabled="selectedElements.length === 0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="3,6 5,6 21,6"/>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
            </svg>
        </button>
    </div>

    <!-- Lista de capas -->
    <div class="vbp-layers-list" x-ref="layersList" role="listbox" aria-label="<?php esc_attr_e( 'Lista de capas', 'flavor-chat-ia' ); ?>" aria-multiselectable="true">
        <template x-for="(element, index) in filteredElements" :key="element.id">
            <div
                class="vbp-layer-item"
                :class="{
                    'selected': isSelected(element.id),
                    'hidden': element.visible === false,
                    'locked': element.locked,
                    'drag-over': dragOverId === element.id
                }"
                @click="selectElement(element, $event)"
                @dblclick="renameElement(element)"
                :draggable="!element.locked"
                @dragstart="handleDragStart($event, element, index)"
                @dragover="handleDragOver($event, element, index)"
                @dragleave="handleDragLeave($event)"
                @drop="handleDrop($event, element, index)"
                @dragend="handleDragEnd($event)"
                role="option"
                :aria-selected="isSelected(element.id)"
                :aria-label="(element.name || element.type) + (element.visible === false ? ' - oculto' : '') + (element.locked ? ' - bloqueado' : '')"
                tabindex="0"
                @keydown.enter="selectElement(element, $event)"
                @keydown.space.prevent="selectElement(element, $event)"
            >
                <!-- Icono de tipo -->
                <span class="vbp-layer-icon" x-html="getTypeIcon(element.type)"></span>

                <!-- Nombre (editable) -->
                <template x-if="editingId !== element.id">
                    <span class="vbp-layer-name" x-html="highlightMatch(element.name || element.type)"></span>
                </template>
                <template x-if="editingId === element.id">
                    <input
                        type="text"
                        x-model="editingName"
                        @blur="saveElementName(element)"
                        @keydown.enter="saveElementName(element)"
                        @keydown.escape="cancelEdit()"
                        class="vbp-layer-name-input"
                        x-ref="layerNameInput"
                    >
                </template>

                <!-- Controles -->
                <div class="vbp-layer-controls" role="group" aria-label="<?php esc_attr_e( 'Controles de capa', 'flavor-chat-ia' ); ?>">
                    <button
                        type="button"
                        @click.stop="toggleVisibility(element)"
                        class="vbp-layer-control"
                        :title="element.visible !== false ? '<?php esc_attr_e( 'Ocultar', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Mostrar', 'flavor-chat-ia' ); ?>'"
                        :aria-label="element.visible !== false ? '<?php esc_attr_e( 'Ocultar capa', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Mostrar capa', 'flavor-chat-ia' ); ?>'"
                        :aria-pressed="element.visible !== false"
                    >
                        <template x-if="element.visible !== false">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </template>
                        <template x-if="element.visible === false">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </template>
                    </button>
                    <button
                        type="button"
                        @click.stop="toggleLock(element)"
                        class="vbp-layer-control"
                        :title="element.locked ? '<?php esc_attr_e( 'Desbloquear', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Bloquear', 'flavor-chat-ia' ); ?>'"
                        :aria-label="element.locked ? '<?php esc_attr_e( 'Desbloquear capa', 'flavor-chat-ia' ); ?>' : '<?php esc_attr_e( 'Bloquear capa', 'flavor-chat-ia' ); ?>'"
                        :aria-pressed="element.locked"
                    >
                        <template x-if="!element.locked">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </template>
                        <template x-if="element.locked">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 019.9-1"/>
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </template>

        <!-- Empty state - sin elementos -->
        <template x-if="elements.length === 0">
            <div class="vbp-layers-empty">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <polygon points="12,2 2,7 12,12 22,7"/>
                    <polyline points="2,17 12,22 22,17"/>
                    <polyline points="2,12 12,17 22,12"/>
                </svg>
                <p><?php esc_html_e( 'Sin elementos', 'flavor-chat-ia' ); ?></p>
            </div>
        </template>

        <!-- Empty state - sin resultados de búsqueda -->
        <template x-if="elements.length > 0 && filteredElements.length === 0">
            <div class="vbp-layers-empty vbp-layers-no-results">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                    <line x1="8" y1="8" x2="14" y2="14"/>
                    <line x1="14" y1="8" x2="8" y2="14"/>
                </svg>
                <p><?php esc_html_e( 'Sin resultados', 'flavor-chat-ia' ); ?></p>
                <button type="button" @click="clearFilters()" class="vbp-btn vbp-btn-sm vbp-btn-secondary">
                    <?php esc_html_e( 'Limpiar filtros', 'flavor-chat-ia' ); ?>
                </button>
            </div>
        </template>
    </div>
</div>

<?php
/**
 * Componente: Action Bar
 *
 * Barra de acciones para bulk actions, filtros y búsqueda.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $bulk_actions   Acciones masivas: [['id' => 'delete', 'label' => 'Eliminar', 'icon' => '🗑', 'confirm' => true, 'danger' => true]]
 * @param array  $filters        Filtros: [['id' => 'status', 'label' => 'Estado', 'options' => [...]]]
 * @param bool   $show_search    Mostrar buscador
 * @param string $search_placeholder Placeholder del buscador
 * @param array  $view_modes     Modos de vista: [['id' => 'grid', 'icon' => '⊞'], ['id' => 'list', 'icon' => '☰']]
 * @param string $active_view    Vista activa
 * @param array  $actions        Acciones del lado derecho
 * @param string $color          Color del tema
 * @param int    $selected_count Cantidad de items seleccionados
 * @param string $on_bulk_action Callback JS: fn(action, selectedIds)
 * @param string $on_filter      Callback JS: fn(filters)
 * @param string $on_search      Callback JS: fn(query)
 * @param string $on_view_change Callback JS: fn(viewMode)
 */

if (!defined('ABSPATH')) {
    exit;
}

$bulk_actions = $bulk_actions ?? [];
$filters = $filters ?? [];
$show_search = $show_search ?? true;
$search_placeholder = $search_placeholder ?? __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN);
$view_modes = $view_modes ?? [];
$active_view = $active_view ?? 'grid';
$actions = $actions ?? [];
$color = $color ?? 'blue';
$selected_count = $selected_count ?? 0;
$on_bulk_action = $on_bulk_action ?? '';
$on_filter = $on_filter ?? '';
$on_search = $on_search ?? '';
$on_view_change = $on_view_change ?? '';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

$bar_id = 'action-bar-' . wp_rand(1000, 9999);
?>

<div id="<?php echo esc_attr($bar_id); ?>"
     class="flavor-action-bar bg-white rounded-2xl border border-gray-100 shadow-sm p-4"
     data-selected-count="<?php echo esc_attr($selected_count); ?>">

    <div class="flex flex-wrap items-center gap-4">
        <!-- Lado izquierdo: Selección y acciones masivas -->
        <div class="flex items-center gap-3">
            <?php if (!empty($bulk_actions)): ?>
                <!-- Checkbox de selección global -->
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox"
                           class="select-all-checkbox w-5 h-5 rounded border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500"
                           onchange="flavorActionBar.toggleSelectAll('<?php echo esc_js($bar_id); ?>', this.checked)">
                </label>

                <!-- Contador de seleccionados -->
                <span class="selected-count text-sm text-gray-500 <?php echo $selected_count === 0 ? 'hidden' : ''; ?>">
                    <?php printf(
                        esc_html(_n('%d seleccionado', '%d seleccionados', $selected_count, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $selected_count
                    ); ?>
                </span>

                <!-- Dropdown de acciones masivas -->
                <div class="relative bulk-actions-dropdown <?php echo $selected_count === 0 ? 'hidden' : ''; ?>">
                    <button type="button"
                            onclick="this.nextElementSibling.classList.toggle('hidden')"
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        <span><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-xs">▼</span>
                    </button>
                    <div class="hidden absolute left-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-20">
                        <?php foreach ($bulk_actions as $action):
                            $is_danger = $action['danger'] ?? false;
                            $needs_confirm = $action['confirm'] ?? false;
                        ?>
                            <button type="button"
                                    data-action="<?php echo esc_attr($action['id'] ?? ''); ?>"
                                    data-confirm="<?php echo $needs_confirm ? 'true' : 'false'; ?>"
                                    onclick="flavorActionBar.executeBulkAction('<?php echo esc_js($bar_id); ?>', '<?php echo esc_js($action['id'] ?? ''); ?>', <?php echo $needs_confirm ? 'true' : 'false'; ?>)"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-left transition-colors
                                        <?php echo $is_danger ? 'text-red-600 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php if (!empty($action['icon'])): ?>
                                    <span><?php echo esc_html($action['icon']); ?></span>
                                <?php endif; ?>
                                <?php echo esc_html($action['label'] ?? ''); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Centro: Filtros -->
        <?php if (!empty($filters)): ?>
            <div class="flex items-center gap-2 flex-wrap">
                <?php foreach ($filters as $filter): ?>
                    <div class="relative filter-dropdown">
                        <button type="button"
                                data-filter="<?php echo esc_attr($filter['id'] ?? ''); ?>"
                                onclick="this.nextElementSibling.classList.toggle('hidden')"
                                class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <?php if (!empty($filter['icon'])): ?>
                                <span><?php echo esc_html($filter['icon']); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html($filter['label'] ?? ''); ?></span>
                            <span class="filter-value text-<?php echo esc_attr($color); ?>-600 font-semibold hidden"></span>
                            <span class="text-xs text-gray-400">▼</span>
                        </button>
                        <div class="hidden absolute left-0 top-full mt-1 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-20">
                            <?php if (!empty($filter['options'])): ?>
                                <?php foreach ($filter['options'] as $option): ?>
                                    <button type="button"
                                            data-value="<?php echo esc_attr($option['value'] ?? ''); ?>"
                                            onclick="flavorActionBar.setFilter('<?php echo esc_js($bar_id); ?>', '<?php echo esc_js($filter['id'] ?? ''); ?>', '<?php echo esc_js($option['value'] ?? ''); ?>', '<?php echo esc_js($option['label'] ?? ''); ?>')"
                                            class="w-full flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">
                                        <span><?php echo esc_html($option['label'] ?? ''); ?></span>
                                        <?php if (!empty($option['count'])): ?>
                                            <span class="text-xs text-gray-400"><?php echo esc_html($option['count']); ?></span>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Limpiar filtro -->
                            <div class="border-t border-gray-100 mt-1 pt-1">
                                <button type="button"
                                        onclick="flavorActionBar.clearFilter('<?php echo esc_js($bar_id); ?>', '<?php echo esc_js($filter['id'] ?? ''); ?>')"
                                        class="w-full px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 text-left">
                                    <?php esc_html_e('Limpiar filtro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Limpiar todos los filtros -->
                <button type="button"
                        class="clear-all-filters hidden px-3 py-2 text-sm text-<?php echo esc_attr($color); ?>-600 hover:bg-<?php echo esc_attr($color); ?>-50 rounded-xl transition-colors"
                        onclick="flavorActionBar.clearAllFilters('<?php echo esc_js($bar_id); ?>')">
                    ✕ <?php esc_html_e('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Espaciador -->
        <div class="flex-1"></div>

        <!-- Lado derecho: Búsqueda, vistas y acciones -->
        <div class="flex items-center gap-3">
            <?php if ($show_search): ?>
                <!-- Buscador -->
                <div class="relative">
                    <input type="search"
                           class="search-input w-64 pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20 focus:border-<?php echo esc_attr($color); ?>-500"
                           placeholder="<?php echo esc_attr($search_placeholder); ?>"
                           oninput="flavorActionBar.search('<?php echo esc_js($bar_id); ?>', this.value)">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($view_modes)): ?>
                <!-- Selector de vista -->
                <div class="flex items-center bg-gray-100 rounded-xl p-1">
                    <?php foreach ($view_modes as $mode): ?>
                        <button type="button"
                                data-view="<?php echo esc_attr($mode['id'] ?? ''); ?>"
                                onclick="flavorActionBar.setView('<?php echo esc_js($bar_id); ?>', '<?php echo esc_js($mode['id'] ?? ''); ?>')"
                                class="view-mode-btn p-2 rounded-lg transition-colors
                                    <?php echo ($mode['id'] ?? '') === $active_view
                                        ? 'bg-white shadow text-gray-900'
                                        : 'text-gray-500 hover:text-gray-700'; ?>"
                                title="<?php echo esc_attr($mode['label'] ?? ucfirst($mode['id'] ?? '')); ?>">
                            <span class="text-lg"><?php echo esc_html($mode['icon'] ?? ''); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($actions)): ?>
                <!-- Acciones adicionales -->
                <?php foreach ($actions as $action): ?>
                    <?php if (!empty($action['action'])): ?>
                        <button type="button"
                                onclick="<?php echo esc_attr($action['action']); ?>"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-colors
                                    <?php echo ($action['primary'] ?? false)
                                        ? esc_attr($color_classes['bg_solid']) . ' text-white hover:opacity-90'
                                        : esc_attr($color_classes['bg']) . ' ' . esc_attr($color_classes['text']) . ' hover:opacity-80'; ?>">
                            <?php if (!empty($action['icon'])): ?>
                                <span><?php echo esc_html($action['icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                           class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-colors
                               <?php echo ($action['primary'] ?? false)
                                   ? esc_attr($color_classes['bg_solid']) . ' text-white hover:opacity-90'
                                   : esc_attr($color_classes['bg']) . ' ' . esc_attr($color_classes['text']) . ' hover:opacity-80'; ?>">
                            <?php if (!empty($action['icon'])): ?>
                                <span><?php echo esc_html($action['icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.flavorActionBar = window.flavorActionBar || {
    selectedIds: {},
    filters: {},
    searchTimeout: null,

    init: function(barId) {
        this.selectedIds[barId] = [];
        this.filters[barId] = {};
    },

    toggleSelectAll: function(barId, checked) {
        // Dispatch event for parent to handle
        document.dispatchEvent(new CustomEvent('actionbar-select-all', {
            detail: { barId, checked }
        }));
    },

    updateSelection: function(barId, ids) {
        this.selectedIds[barId] = ids;
        const bar = document.getElementById(barId);
        if (!bar) return;

        const count = ids.length;
        bar.dataset.selectedCount = count;

        // Update counter
        const counter = bar.querySelector('.selected-count');
        if (counter) {
            counter.textContent = count + ' seleccionado' + (count !== 1 ? 's' : '');
            counter.classList.toggle('hidden', count === 0);
        }

        // Show/hide bulk actions
        const bulkDropdown = bar.querySelector('.bulk-actions-dropdown');
        if (bulkDropdown) {
            bulkDropdown.classList.toggle('hidden', count === 0);
        }

        // Update select all checkbox
        const selectAll = bar.querySelector('.select-all-checkbox');
        if (selectAll) {
            selectAll.checked = count > 0;
            selectAll.indeterminate = count > 0 && !this.isAllSelected;
        }
    },

    executeBulkAction: function(barId, actionId, needsConfirm) {
        const ids = this.selectedIds[barId] || [];
        if (ids.length === 0) return;

        if (needsConfirm) {
            if (!confirm('<?php echo esc_js(__('¿Estás seguro de realizar esta acción en los elementos seleccionados?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                return;
            }
        }

        // Close dropdown
        const bar = document.getElementById(barId);
        bar.querySelectorAll('.bulk-actions-dropdown > div').forEach(d => d.classList.add('hidden'));

        // Dispatch event
        document.dispatchEvent(new CustomEvent('actionbar-bulk-action', {
            detail: { barId, actionId, ids }
        }));

        <?php if ($on_bulk_action): ?>
        <?php echo $on_bulk_action; ?>(actionId, ids);
        <?php endif; ?>
    },

    setFilter: function(barId, filterId, value, label) {
        this.filters[barId] = this.filters[barId] || {};
        this.filters[barId][filterId] = value;

        const bar = document.getElementById(barId);
        if (!bar) return;

        // Update button label
        const filterBtn = bar.querySelector(`[data-filter="${filterId}"]`);
        if (filterBtn) {
            const valueSpan = filterBtn.querySelector('.filter-value');
            if (valueSpan) {
                valueSpan.textContent = label;
                valueSpan.classList.remove('hidden');
            }
        }

        // Close dropdown
        bar.querySelectorAll('.filter-dropdown > div').forEach(d => d.classList.add('hidden'));

        // Show clear all button
        bar.querySelector('.clear-all-filters')?.classList.remove('hidden');

        // Dispatch event
        document.dispatchEvent(new CustomEvent('actionbar-filter', {
            detail: { barId, filters: this.filters[barId] }
        }));

        <?php if ($on_filter): ?>
        <?php echo $on_filter; ?>(this.filters[barId]);
        <?php endif; ?>
    },

    clearFilter: function(barId, filterId) {
        if (this.filters[barId]) {
            delete this.filters[barId][filterId];
        }

        const bar = document.getElementById(barId);
        if (!bar) return;

        // Reset button
        const filterBtn = bar.querySelector(`[data-filter="${filterId}"]`);
        if (filterBtn) {
            const valueSpan = filterBtn.querySelector('.filter-value');
            if (valueSpan) {
                valueSpan.classList.add('hidden');
            }
        }

        // Close dropdown
        bar.querySelectorAll('.filter-dropdown > div').forEach(d => d.classList.add('hidden'));

        // Hide clear all if no filters
        if (Object.keys(this.filters[barId] || {}).length === 0) {
            bar.querySelector('.clear-all-filters')?.classList.add('hidden');
        }

        // Dispatch event
        document.dispatchEvent(new CustomEvent('actionbar-filter', {
            detail: { barId, filters: this.filters[barId] }
        }));

        <?php if ($on_filter): ?>
        <?php echo $on_filter; ?>(this.filters[barId]);
        <?php endif; ?>
    },

    clearAllFilters: function(barId) {
        this.filters[barId] = {};

        const bar = document.getElementById(barId);
        if (!bar) return;

        // Reset all buttons
        bar.querySelectorAll('.filter-value').forEach(span => span.classList.add('hidden'));
        bar.querySelector('.clear-all-filters')?.classList.add('hidden');

        // Dispatch event
        document.dispatchEvent(new CustomEvent('actionbar-filter', {
            detail: { barId, filters: {} }
        }));

        <?php if ($on_filter): ?>
        <?php echo $on_filter; ?>({});
        <?php endif; ?>
    },

    search: function(barId, query) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            document.dispatchEvent(new CustomEvent('actionbar-search', {
                detail: { barId, query }
            }));

            <?php if ($on_search): ?>
            <?php echo $on_search; ?>(query);
            <?php endif; ?>
        }, 300);
    },

    setView: function(barId, viewMode) {
        const bar = document.getElementById(barId);
        if (!bar) return;

        // Update buttons
        bar.querySelectorAll('.view-mode-btn').forEach(btn => {
            const isActive = btn.dataset.view === viewMode;
            btn.classList.toggle('bg-white', isActive);
            btn.classList.toggle('shadow', isActive);
            btn.classList.toggle('text-gray-900', isActive);
            btn.classList.toggle('text-gray-500', !isActive);
        });

        // Dispatch event
        document.dispatchEvent(new CustomEvent('actionbar-view-change', {
            detail: { barId, viewMode }
        }));

        <?php if ($on_view_change): ?>
        <?php echo $on_view_change; ?>(viewMode);
        <?php endif; ?>
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    flavorActionBar.init('<?php echo esc_js($bar_id); ?>');
});
</script>

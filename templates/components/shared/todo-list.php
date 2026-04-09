<?php
/**
 * Componente: Todo List
 *
 * Lista de tareas pendientes para dashboards.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $items      Tareas: [['id' => 1, 'title' => 'Tarea', 'completed' => false, 'priority' => 'high', 'due_date' => '']]
 * @param string $title      Título de la lista
 * @param string $color      Color del tema
 * @param bool   $interactive Permitir marcar como completado
 * @param string $ajax_action Acción AJAX para actualizar estado
 * @param array  $filters    Filtros: [['id' => 'all', 'label' => 'Todas'], ...]
 * @param string $empty_text Texto cuando está vacía
 * @param array  $add_action Acción para añadir: ['label' => 'Nueva tarea', 'action' => 'fn()']
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$title = $title ?? __('Tareas Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color = $color ?? 'blue';
$interactive = $interactive ?? true;
$ajax_action = $ajax_action ?? '';
$filters = $filters ?? [];
$empty_text = $empty_text ?? __('No hay tareas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN);
$add_action = $add_action ?? [];

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Colores de prioridad
$priority_colors = [
    'high'   => ['dot' => 'bg-red-500', 'text' => 'text-red-600', 'bg' => 'bg-red-50'],
    'medium' => ['dot' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
    'low'    => ['dot' => 'bg-green-500', 'text' => 'text-green-600', 'bg' => 'bg-green-50'],
    'normal' => ['dot' => 'bg-gray-400', 'text' => 'text-gray-600', 'bg' => 'bg-gray-50'],
];

// Contar completadas
$total = count($items);
$completed = count(array_filter($items, fn($item) => !empty($item['completed'])));
$pending = $total - $completed;

$todo_id = 'todo-' . wp_rand(1000, 9999);
?>

<div id="<?php echo esc_attr($todo_id); ?>"
     class="flavor-todo-list bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="p-4 border-b border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
            <?php if ($total > 0): ?>
                <span class="text-sm text-gray-500">
                    <?php printf(
                        esc_html__('%d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $completed,
                        $total
                    ); ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($total > 0): ?>
            <!-- Progress bar -->
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-full transition-all duration-500"
                     style="width: <?php echo esc_attr($total > 0 ? round(($completed / $total) * 100) : 0); ?>%"></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($filters)): ?>
            <div class="flex gap-1 mt-3">
                <?php foreach ($filters as $index => $filter): ?>
                    <button type="button"
                            data-filter="<?php echo esc_attr($filter['id'] ?? ''); ?>"
                            onclick="flavorTodo.filter('<?php echo esc_js($todo_id); ?>', '<?php echo esc_js($filter['id'] ?? ''); ?>')"
                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors
                                <?php echo $index === 0 ? esc_attr($color_classes['bg']) . ' ' . esc_attr($color_classes['text']) : 'text-gray-500 hover:bg-gray-100'; ?>">
                        <?php echo esc_html($filter['label'] ?? ''); ?>
                        <?php if (isset($filter['count'])): ?>
                            <span class="ml-1 opacity-75">(<?php echo esc_html($filter['count']); ?>)</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tasks -->
    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
        <?php if (empty($items)): ?>
            <div class="p-6 text-center">
                <span class="text-3xl block mb-2">✅</span>
                <p class="text-gray-500"><?php echo esc_html($empty_text); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item):
                $item_id = $item['id'] ?? wp_rand(1000, 9999);
                $item_title = $item['title'] ?? '';
                $item_completed = !empty($item['completed']);
                $item_priority = $item['priority'] ?? 'normal';
                $item_due_date = $item['due_date'] ?? '';
                $item_assignee = $item['assignee'] ?? '';
                $item_tags = $item['tags'] ?? [];

                $priority_style = $priority_colors[$item_priority] ?? $priority_colors['normal'];

                // Calcular si está vencida
                $is_overdue = false;
                if ($item_due_date && !$item_completed) {
                    $due_timestamp = strtotime($item_due_date);
                    $is_overdue = $due_timestamp < current_time('timestamp');
                }
            ?>
                <div class="todo-item flex items-start gap-3 p-3 hover:bg-gray-50 transition-colors <?php echo $item_completed ? 'opacity-60' : ''; ?>"
                     data-id="<?php echo esc_attr($item_id); ?>"
                     data-status="<?php echo $item_completed ? 'completed' : 'pending'; ?>"
                     data-priority="<?php echo esc_attr($item_priority); ?>">

                    <?php if ($interactive): ?>
                        <label class="flex-shrink-0 cursor-pointer">
                            <input type="checkbox"
                                   class="sr-only"
                                   <?php checked($item_completed); ?>
                                   onchange="flavorTodo.toggle('<?php echo esc_js($todo_id); ?>', '<?php echo esc_js($item_id); ?>', this.checked)">
                            <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors
                                <?php echo $item_completed
                                    ? esc_attr($color_classes['bg_solid']) . ' border-transparent text-white'
                                    : 'border-gray-300 hover:border-gray-400'; ?>">
                                <?php if ($item_completed): ?>
                                    <span class="text-xs">✓</span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php else: ?>
                        <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0 <?php echo esc_attr($priority_style['dot']); ?>"></span>
                    <?php endif; ?>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm <?php echo $item_completed ? 'line-through text-gray-400' : 'text-gray-700'; ?>">
                            <?php echo esc_html($item_title); ?>
                        </p>

                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <?php if ($item_due_date): ?>
                                <span class="text-xs <?php echo $is_overdue ? 'text-red-600' : 'text-gray-400'; ?>">
                                    📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item_due_date))); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($item_assignee): ?>
                                <span class="text-xs text-gray-400">
                                    👤 <?php echo esc_html($item_assignee); ?>
                                </span>
                            <?php endif; ?>

                            <?php foreach ($item_tags as $tag): ?>
                                <span class="px-1.5 py-0.5 text-xs rounded <?php echo esc_attr($priority_style['bg']); ?> <?php echo esc_attr($priority_style['text']); ?>">
                                    <?php echo esc_html($tag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!$item_completed && $item_priority !== 'normal'): ?>
                        <span class="w-2 h-2 rounded-full flex-shrink-0 <?php echo esc_attr($priority_style['dot']); ?>"
                              title="<?php echo esc_attr(ucfirst($item_priority)); ?>"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Task -->
    <?php if (!empty($add_action)): ?>
        <div class="p-3 border-t border-gray-100">
            <button onclick="<?php echo esc_attr($add_action['action'] ?? ''); ?>"
                    class="w-full py-2 px-4 text-sm font-medium <?php echo esc_attr($color_classes['text']); ?> hover:<?php echo esc_attr($color_classes['bg']); ?> rounded-lg transition-colors flex items-center justify-center gap-2">
                <span>+</span>
                <?php echo esc_html($add_action['label'] ?? __('Añadir tarea', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
window.flavorTodo = window.flavorTodo || {
    toggle: function(containerId, itemId, completed) {
        const container = document.getElementById(containerId);
        const item = container.querySelector('[data-id="' + itemId + '"]');

        if (item) {
            item.dataset.status = completed ? 'completed' : 'pending';
            item.classList.toggle('opacity-60', completed);

            const title = item.querySelector('p');
            if (title) {
                title.classList.toggle('line-through', completed);
                title.classList.toggle('text-gray-400', completed);
                title.classList.toggle('text-gray-700', !completed);
            }

            // Update checkbox visual
            const checkSpan = item.querySelector('label span');
            if (checkSpan && completed) {
                checkSpan.innerHTML = '<span class="text-xs">✓</span>';
            } else if (checkSpan) {
                checkSpan.innerHTML = '';
            }
        }

        // Dispatch event
        document.dispatchEvent(new CustomEvent('todo-toggle', {
            detail: { containerId, itemId, completed }
        }));
    },

    filter: function(containerId, filter) {
        const container = document.getElementById(containerId);

        // Update buttons
        container.querySelectorAll('[data-filter]').forEach(btn => {
            const isActive = btn.dataset.filter === filter;
            btn.classList.toggle('<?php echo esc_attr($color_classes['bg']); ?>', isActive);
            btn.classList.toggle('<?php echo esc_attr($color_classes['text']); ?>', isActive);
            btn.classList.toggle('text-gray-500', !isActive);
        });

        // Filter items
        container.querySelectorAll('.todo-item').forEach(item => {
            let show = true;
            if (filter === 'completed') {
                show = item.dataset.status === 'completed';
            } else if (filter === 'pending') {
                show = item.dataset.status === 'pending';
            } else if (filter === 'high' || filter === 'medium' || filter === 'low') {
                show = item.dataset.priority === filter;
            }
            item.style.display = show ? '' : 'none';
        });
    }
};
</script>

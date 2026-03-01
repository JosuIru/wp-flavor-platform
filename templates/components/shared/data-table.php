<?php
/**
 * Componente: Data Table
 *
 * Tabla de datos reutilizable para admin y dashboards.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $columns     Columnas: [['key' => 'name', 'label' => 'Nombre', 'sortable' => true, 'width' => '200px']]
 * @param array  $rows        Filas de datos
 * @param string $color       Color del tema
 * @param bool   $striped     Filas alternas
 * @param bool   $hoverable   Hover en filas
 * @param bool   $selectable  Checkboxes para selección
 * @param array  $row_actions Acciones por fila: [['label' => 'Editar', 'icon' => '✏️', 'action' => 'edit({id})']]
 * @param string $empty_text  Texto cuando no hay datos
 * @param string $id          ID de la tabla
 */

if (!defined('ABSPATH')) {
    exit;
}

$columns = $columns ?? [];
$rows = $rows ?? [];
$color = $color ?? 'blue';
$striped = $striped ?? true;
$hoverable = $hoverable ?? true;
$selectable = $selectable ?? false;
$row_actions = $row_actions ?? [];
$empty_text = $empty_text ?? __('No hay datos disponibles', 'flavor-chat-ia');
$table_id = $id ?? 'table-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}
?>

<div class="overflow-x-auto">
    <table id="<?php echo esc_attr($table_id); ?>" class="w-full">
        <thead>
            <tr class="border-b border-gray-200">
                <?php if ($selectable): ?>
                    <th class="p-4 text-left w-12">
                        <input type="checkbox"
                               class="rounded border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500"
                               onclick="document.querySelectorAll('#<?php echo esc_attr($table_id); ?> tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked)">
                    </th>
                <?php endif; ?>

                <?php foreach ($columns as $column): ?>
                    <th class="p-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        <?php if (!empty($column['width'])): ?>style="width: <?php echo esc_attr($column['width']); ?>"<?php endif; ?>>
                        <div class="flex items-center gap-2">
                            <?php echo esc_html($column['label'] ?? $column['key'] ?? ''); ?>
                            <?php if (!empty($column['sortable'])): ?>
                                <button class="text-gray-400 hover:text-gray-600" onclick="/* sort logic */">↕</button>
                            <?php endif; ?>
                        </div>
                    </th>
                <?php endforeach; ?>

                <?php if (!empty($row_actions)): ?>
                    <th class="p-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">
                        <?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?>
                    </th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?php echo count($columns) + ($selectable ? 1 : 0) + (!empty($row_actions) ? 1 : 0); ?>"
                        class="p-8 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <span class="text-4xl">📭</span>
                            <p><?php echo esc_html($empty_text); ?></p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="<?php echo $striped && $index % 2 === 1 ? 'bg-gray-50' : 'bg-white'; ?> <?php echo $hoverable ? 'hover:bg-gray-100 transition-colors' : ''; ?>">
                        <?php if ($selectable): ?>
                            <td class="p-4">
                                <input type="checkbox"
                                       value="<?php echo esc_attr($row['id'] ?? $index); ?>"
                                       class="rounded border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500">
                            </td>
                        <?php endif; ?>

                        <?php foreach ($columns as $column):
                            $key = $column['key'] ?? '';
                            $value = $row[$key] ?? '';
                            $type = $column['type'] ?? 'text';
                            $format = $column['format'] ?? null;
                        ?>
                            <td class="p-4 text-sm text-gray-700">
                                <?php
                                switch ($type) {
                                    case 'badge':
                                        $badge_color = $column['colors'][$value] ?? 'gray';
                                        $badge_classes = function_exists('flavor_get_color_classes')
                                            ? flavor_get_color_classes($badge_color)
                                            : ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
                                        echo '<span class="px-2 py-1 rounded-full text-xs font-medium ' . esc_attr($badge_classes['bg']) . ' ' . esc_attr($badge_classes['text']) . '">' . esc_html($column['labels'][$value] ?? $value) . '</span>';
                                        break;

                                    case 'avatar':
                                        echo '<img src="' . esc_url($value) . '" class="w-8 h-8 rounded-full object-cover" alt="">';
                                        break;

                                    case 'user':
                                        $user_avatar = $row[$column['avatar_key'] ?? 'avatar'] ?? '';
                                        $user_name = $value;
                                        echo '<div class="flex items-center gap-3">';
                                        if ($user_avatar) {
                                            echo '<img src="' . esc_url($user_avatar) . '" class="w-8 h-8 rounded-full object-cover" alt="">';
                                        }
                                        echo '<span class="font-medium">' . esc_html($user_name) . '</span>';
                                        echo '</div>';
                                        break;

                                    case 'date':
                                        echo '<span class="text-gray-500">' . esc_html($value) . '</span>';
                                        break;

                                    case 'currency':
                                        echo '<span class="font-medium">' . esc_html($value) . '</span>';
                                        break;

                                    case 'link':
                                        $link_url = $row[$column['url_key'] ?? 'url'] ?? '#';
                                        echo '<a href="' . esc_url($link_url) . '" class="' . esc_attr($color_classes['text']) . ' hover:underline">' . esc_html($value) . '</a>';
                                        break;

                                    case 'progress':
                                        $progress_value = (float) $value;
                                        echo '<div class="flex items-center gap-2">';
                                        echo '<div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">';
                                        echo '<div class="h-full ' . esc_attr($color_classes['bg_solid']) . ' rounded-full" style="width: ' . esc_attr($progress_value) . '%"></div>';
                                        echo '</div>';
                                        echo '<span class="text-xs text-gray-500 w-10">' . esc_html($progress_value) . '%</span>';
                                        echo '</div>';
                                        break;

                                    default:
                                        echo esc_html($value);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>

                        <?php if (!empty($row_actions)): ?>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <?php foreach ($row_actions as $action):
                                        // Reemplazar {field} con valores del row
                                        $action_onclick = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($row) {
                                            return $row[$matches[1]] ?? '';
                                        }, $action['action'] ?? '');
                                        $action_url = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($row) {
                                            return $row[$matches[1]] ?? '';
                                        }, $action['url'] ?? '');
                                    ?>
                                        <?php if ($action_onclick): ?>
                                            <button onclick="<?php echo esc_attr($action_onclick); ?>"
                                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                                    title="<?php echo esc_attr($action['label'] ?? ''); ?>">
                                                <?php echo esc_html($action['icon'] ?? '⋮'); ?>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($action_url ?: '#'); ?>"
                                               class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                               title="<?php echo esc_attr($action['label'] ?? ''); ?>">
                                                <?php echo esc_html($action['icon'] ?? '⋮'); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

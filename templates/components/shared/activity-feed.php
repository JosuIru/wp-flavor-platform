<?php
/**
 * Componente: Activity Feed
 *
 * Feed de actividad reciente para dashboards.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $items      Actividades: [['type' => 'comment', 'user' => [], 'action' => 'comentó en', 'target' => 'Publicación X', 'time' => '2 min', 'url' => '#']]
 * @param string $title      Título del feed
 * @param string $color      Color del tema
 * @param int    $limit      Máximo de items a mostrar
 * @param string $empty_text Texto cuando no hay actividad
 * @param array  $actions    Acciones del header
 * @param bool   $compact    Vista compacta
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$title = $title ?? __('Actividad Reciente', 'flavor-chat-ia');
$color = $color ?? 'blue';
$limit = $limit ?? 10;
$empty_text = $empty_text ?? __('No hay actividad reciente', 'flavor-chat-ia');
$actions = $actions ?? [];
$compact = $compact ?? false;

// Limitar items
$items = array_slice($items, 0, $limit);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Iconos por tipo de actividad
$type_icons = [
    'comment'   => '💬',
    'like'      => '❤️',
    'follow'    => '👤',
    'post'      => '📝',
    'share'     => '🔄',
    'purchase'  => '🛒',
    'register'  => '✨',
    'update'    => '📋',
    'delete'    => '🗑️',
    'upload'    => '📤',
    'download'  => '📥',
    'payment'   => '💳',
    'event'     => '📅',
    'message'   => '✉️',
    'alert'     => '🔔',
    'success'   => '✅',
    'error'     => '❌',
    'default'   => '📌',
];
?>

<div class="flavor-activity-feed bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
        <?php if (!empty($actions)): ?>
            <div class="flex items-center gap-2">
                <?php foreach ($actions as $action): ?>
                    <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                       class="text-sm <?php echo esc_attr($color_classes['text']); ?> hover:underline">
                        <?php echo esc_html($action['label'] ?? ''); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Feed -->
    <div class="<?php echo $compact ? '' : 'divide-y divide-gray-50'; ?>">
        <?php if (empty($items)): ?>
            <div class="p-8 text-center">
                <span class="text-4xl block mb-2">📭</span>
                <p class="text-gray-500"><?php echo esc_html($empty_text); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item):
                $type = $item['type'] ?? 'default';
                $icon = $item['icon'] ?? ($type_icons[$type] ?? $type_icons['default']);
                $user = $item['user'] ?? [];
                $user_name = $user['name'] ?? $user['display_name'] ?? '';
                $user_avatar = $user['avatar'] ?? '';
                $action_text = $item['action'] ?? '';
                $target = $item['target'] ?? '';
                $target_url = $item['target_url'] ?? $item['url'] ?? '';
                $time = $item['time'] ?? '';
                $meta = $item['meta'] ?? '';
            ?>
                <div class="flex items-start gap-3 p-<?php echo $compact ? '3' : '4'; ?> hover:bg-gray-50 transition-colors">
                    <!-- Avatar o Icono -->
                    <?php if ($user_avatar): ?>
                        <img src="<?php echo esc_url($user_avatar); ?>"
                             alt="<?php echo esc_attr($user_name); ?>"
                             class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center flex-shrink-0">
                            <span class="text-lg"><?php echo esc_html($icon); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Contenido -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">
                            <?php if ($user_name): ?>
                                <span class="font-medium text-gray-900"><?php echo esc_html($user_name); ?></span>
                            <?php endif; ?>

                            <?php if ($action_text): ?>
                                <span><?php echo esc_html($action_text); ?></span>
                            <?php endif; ?>

                            <?php if ($target): ?>
                                <?php if ($target_url): ?>
                                    <a href="<?php echo esc_url($target_url); ?>"
                                       class="font-medium <?php echo esc_attr($color_classes['text']); ?> hover:underline">
                                        <?php echo esc_html($target); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="font-medium text-gray-900"><?php echo esc_html($target); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>

                        <?php if ($meta): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($meta); ?></p>
                        <?php endif; ?>

                        <?php if ($time): ?>
                            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($time); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Icono tipo (si hay avatar) -->
                    <?php if ($user_avatar): ?>
                        <span class="text-sm text-gray-400"><?php echo esc_html($icon); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Load More -->
    <?php if (count($items) >= $limit): ?>
        <div class="p-3 border-t border-gray-100 text-center">
            <button type="button"
                    class="text-sm <?php echo esc_attr($color_classes['text']); ?> hover:underline"
                    onclick="this.dispatchEvent(new CustomEvent('load-more-activity', {bubbles: true}))">
                <?php esc_html_e('Ver más actividad', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

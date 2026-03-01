<?php
/**
 * Componente: Timeline
 *
 * Línea de tiempo/historial de actividad.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $items       Items: [['title' => '', 'description' => '', 'date' => '', 'icon' => '', 'color' => '', 'user' => []]]
 * @param string $color       Color del tema por defecto
 * @param string $layout      Layout: 'left', 'right', 'alternate'
 * @param bool   $show_line   Mostrar línea conectora
 * @param bool   $compact     Vista compacta
 * @param string $id          ID único
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$color = $color ?? 'blue';
$layout = $layout ?? 'left';
$show_line = $show_line ?? true;
$compact = $compact ?? false;
$timeline_id = $id ?? 'timeline-' . wp_rand(1000, 9999);

// Clases de color por defecto
if (function_exists('flavor_get_color_classes')) {
    $default_color_classes = flavor_get_color_classes($color);
} else {
    $default_color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}
?>

<div id="<?php echo esc_attr($timeline_id); ?>"
     class="flavor-timeline relative <?php echo $show_line ? 'pl-8' : ''; ?>">

    <?php if ($show_line): ?>
        <!-- Línea vertical -->
        <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-gray-200"></div>
    <?php endif; ?>

    <div class="space-y-<?php echo $compact ? '4' : '6'; ?>">
        <?php foreach ($items as $index => $item):
            $item_title = $item['title'] ?? '';
            $item_description = $item['description'] ?? '';
            $item_date = $item['date'] ?? '';
            $item_time = $item['time'] ?? '';
            $item_icon = $item['icon'] ?? '📌';
            $item_color = $item['color'] ?? $color;
            $item_user = $item['user'] ?? [];
            $item_actions = $item['actions'] ?? [];
            $item_status = $item['status'] ?? '';
            $item_url = $item['url'] ?? '';

            // Clases de color del item
            if (function_exists('flavor_get_color_classes')) {
                $item_color_classes = flavor_get_color_classes($item_color);
            } else {
                $item_color_classes = $default_color_classes;
            }
        ?>
            <div class="relative flex gap-4 <?php echo $layout === 'alternate' && $index % 2 === 1 ? 'flex-row-reverse text-right' : ''; ?>">

                <?php if ($show_line): ?>
                    <!-- Dot/Icon -->
                    <div class="absolute left-0 -translate-x-1/2 w-6 h-6 rounded-full <?php echo esc_attr($item_color_classes['bg_solid']); ?> flex items-center justify-center ring-4 ring-white z-10"
                         style="left: -1.5rem;">
                        <span class="text-xs text-white"><?php echo esc_html($item_icon); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="flex-1 <?php echo $compact ? '' : 'bg-white rounded-xl border border-gray-100 p-4'; ?>">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2 <?php echo $layout === 'alternate' && $index % 2 === 1 ? 'flex-row-reverse' : ''; ?>">
                        <div class="flex-1">
                            <?php if ($item_url): ?>
                                <a href="<?php echo esc_url($item_url); ?>" class="font-semibold text-gray-900 hover:<?php echo esc_attr($item_color_classes['text']); ?> transition-colors">
                                    <?php echo esc_html($item_title); ?>
                                </a>
                            <?php else: ?>
                                <h4 class="font-semibold text-gray-900"><?php echo esc_html($item_title); ?></h4>
                            <?php endif; ?>

                            <?php if ($item_status): ?>
                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo esc_attr($item_color_classes['bg']); ?> <?php echo esc_attr($item_color_classes['text']); ?>">
                                    <?php echo esc_html($item_status); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-sm text-gray-500 flex-shrink-0">
                            <?php if ($item_date): ?>
                                <span><?php echo esc_html($item_date); ?></span>
                            <?php endif; ?>
                            <?php if ($item_time): ?>
                                <span class="text-gray-400"> · <?php echo esc_html($item_time); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if ($item_description): ?>
                        <p class="text-sm text-gray-600 mt-2"><?php echo wp_kses_post($item_description); ?></p>
                    <?php endif; ?>

                    <!-- User -->
                    <?php if (!empty($item_user)): ?>
                        <div class="flex items-center gap-2 mt-3 <?php echo $layout === 'alternate' && $index % 2 === 1 ? 'flex-row-reverse' : ''; ?>">
                            <?php if (!empty($item_user['avatar'])): ?>
                                <img src="<?php echo esc_url($item_user['avatar']); ?>"
                                     alt=""
                                     class="w-6 h-6 rounded-full">
                            <?php endif; ?>
                            <span class="text-sm text-gray-500">
                                <?php if (!empty($item_user['url'])): ?>
                                    <a href="<?php echo esc_url($item_user['url']); ?>" class="hover:<?php echo esc_attr($item_color_classes['text']); ?>">
                                        <?php echo esc_html($item_user['name'] ?? ''); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($item_user['name'] ?? ''); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <?php if (!empty($item_actions)): ?>
                        <div class="flex items-center gap-2 mt-3 <?php echo $layout === 'alternate' && $index % 2 === 1 ? 'flex-row-reverse' : ''; ?>">
                            <?php foreach ($item_actions as $action): ?>
                                <?php if (!empty($action['action'])): ?>
                                    <button onclick="<?php echo esc_attr($action['action']); ?>"
                                            class="text-sm <?php echo esc_attr($item_color_classes['text']); ?> hover:underline">
                                        <?php echo esc_html($action['label'] ?? ''); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                                       class="text-sm <?php echo esc_attr($item_color_classes['text']); ?> hover:underline">
                                        <?php echo esc_html($action['label'] ?? ''); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

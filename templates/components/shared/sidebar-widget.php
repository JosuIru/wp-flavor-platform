<?php
/**
 * Componente: Sidebar Widget
 *
 * Widget de sidebar genérico y reutilizable.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $title      Título del widget
 * @param string $icon       Icono emoji
 * @param string $color      Color del tema
 * @param string $content    Contenido HTML
 * @param array  $items      Lista de items: [['icon' => '📌', 'label' => 'Item', 'value' => '10', 'url' => '#']]
 * @param array  $actions    Acciones en footer: [['label' => 'Ver más', 'url' => '#']]
 * @param string $type       Tipo de widget: 'default', 'stats', 'list', 'cta', 'info'
 * @param array  $cta        Call to action: ['label' => 'Crear nuevo', 'icon' => '➕', 'url' => '#', 'action' => '']
 * @param bool   $loading    Mostrar skeleton loader
 * @param string $id         ID único
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$items = $items ?? [];
$actions = $actions ?? [];
$type = $type ?? 'default';
$cta = $cta ?? [];
$loading = $loading ?? false;
$widget_id = $id ?? 'widget-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}
?>

<div id="<?php echo esc_attr($widget_id); ?>"
     class="flavor-sidebar-widget bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <!-- Header -->
    <?php if ($title): ?>
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <?php if ($icon): ?>
                    <span class="text-lg"><?php echo esc_html($icon); ?></span>
                <?php endif; ?>
                <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
            </div>

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
    <?php endif; ?>

    <!-- Content -->
    <div class="p-4">
        <?php if ($loading): ?>
            <!-- Skeleton Loader -->
            <div class="space-y-3 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>

        <?php elseif ($type === 'stats' && !empty($items)): ?>
            <!-- Stats Type -->
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($items as $item): ?>
                    <div class="p-3 rounded-xl bg-gray-50 text-center">
                        <?php if (!empty($item['icon'])): ?>
                            <span class="text-xl"><?php echo esc_html($item['icon']); ?></span>
                        <?php endif; ?>
                        <p class="text-xl font-bold text-gray-900"><?php echo esc_html($item['value'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($item['label'] ?? ''); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($type === 'list' && !empty($items)): ?>
            <!-- List Type -->
            <ul class="space-y-2">
                <?php foreach ($items as $item):
                    $item_url = $item['url'] ?? '';
                    $item_label = $item['label'] ?? '';
                    $item_value = $item['value'] ?? '';
                    $item_icon = $item['icon'] ?? '';
                    $item_badge = $item['badge'] ?? '';
                ?>
                    <li>
                        <?php if ($item_url): ?>
                            <a href="<?php echo esc_url($item_url); ?>"
                               class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition-colors group">
                        <?php else: ?>
                            <div class="flex items-center justify-between p-2">
                        <?php endif; ?>

                            <div class="flex items-center gap-2">
                                <?php if ($item_icon): ?>
                                    <span><?php echo esc_html($item_icon); ?></span>
                                <?php endif; ?>
                                <span class="text-sm text-gray-700 <?php echo $item_url ? 'group-hover:' . esc_attr($color_classes['text']) : ''; ?>">
                                    <?php echo esc_html($item_label); ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if ($item_badge): ?>
                                    <span class="px-2 py-0.5 text-xs rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>">
                                        <?php echo esc_html($item_badge); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($item_value): ?>
                                    <span class="text-sm font-medium text-gray-500"><?php echo esc_html($item_value); ?></span>
                                <?php endif; ?>
                                <?php if ($item_url): ?>
                                    <span class="text-gray-400 group-hover:<?php echo esc_attr($color_classes['text']); ?>">→</span>
                                <?php endif; ?>
                            </div>

                        <?php echo $item_url ? '</a>' : '</div>'; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($type === 'cta'): ?>
            <!-- CTA Type -->
            <div class="text-center py-4">
                <?php if (!empty($cta['icon'])): ?>
                    <span class="text-4xl block mb-3"><?php echo esc_html($cta['icon']); ?></span>
                <?php endif; ?>
                <?php if (!empty($cta['title'])): ?>
                    <h4 class="font-semibold text-gray-900 mb-2"><?php echo esc_html($cta['title']); ?></h4>
                <?php endif; ?>
                <?php if (!empty($cta['description'])): ?>
                    <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($cta['description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($cta['label'])): ?>
                    <?php if (!empty($cta['action'])): ?>
                        <button onclick="<?php echo esc_attr($cta['action']); ?>"
                                class="w-full py-3 px-4 rounded-xl text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 transition-all flex items-center justify-center gap-2">
                            <?php if (!empty($cta['btn_icon'])): ?>
                                <span><?php echo esc_html($cta['btn_icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($cta['label']); ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo esc_url($cta['url'] ?? '#'); ?>"
                           class="w-full py-3 px-4 rounded-xl text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 transition-all flex items-center justify-center gap-2">
                            <?php if (!empty($cta['btn_icon'])): ?>
                                <span><?php echo esc_html($cta['btn_icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($type === 'info'): ?>
            <!-- Info Type -->
            <div class="space-y-3">
                <?php foreach ($items as $item): ?>
                    <div class="flex items-start gap-3">
                        <?php if (!empty($item['icon'])): ?>
                            <span class="text-gray-400 mt-0.5"><?php echo esc_html($item['icon']); ?></span>
                        <?php endif; ?>
                        <div>
                            <?php if (!empty($item['label'])): ?>
                                <p class="text-xs text-gray-500"><?php echo esc_html($item['label']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['value'])): ?>
                                <p class="text-sm font-medium text-gray-900"><?php echo esc_html($item['value']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Default: HTML Content -->
            <?php
            if (!empty($content)) {
                echo wp_kses_post($content);
            } elseif (!empty($content_callback) && is_callable($content_callback)) {
                call_user_func($content_callback);
            }
            ?>
        <?php endif; ?>
    </div>

    <!-- CTA Button (if not CTA type) -->
    <?php if ($type !== 'cta' && !empty($cta)): ?>
        <div class="p-4 pt-0">
            <?php if (!empty($cta['action'])): ?>
                <button onclick="<?php echo esc_attr($cta['action']); ?>"
                        class="w-full py-2.5 px-4 rounded-xl text-sm font-medium <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?> hover:opacity-80 transition-all flex items-center justify-center gap-2">
                    <?php if (!empty($cta['icon'])): ?>
                        <span><?php echo esc_html($cta['icon']); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($cta['label'] ?? ''); ?>
                </button>
            <?php else: ?>
                <a href="<?php echo esc_url($cta['url'] ?? '#'); ?>"
                   class="w-full py-2.5 px-4 rounded-xl text-sm font-medium <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?> hover:opacity-80 transition-all flex items-center justify-center gap-2">
                    <?php if (!empty($cta['icon'])): ?>
                        <span><?php echo esc_html($cta['icon']); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($cta['label'] ?? ''); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

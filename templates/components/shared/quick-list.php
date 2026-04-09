<?php
/**
 * Componente: Quick List
 *
 * Lista rápida de items para dashboards y sidebars.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $items      Items: [['title' => '', 'subtitle' => '', 'icon' => '', 'badge' => '', 'url' => '', 'status' => '']]
 * @param string $title      Título de la lista
 * @param string $color      Color del tema
 * @param string $empty_text Texto cuando está vacía
 * @param array  $actions    Acciones del header
 * @param bool   $numbered   Mostrar números
 * @param bool   $bordered   Con bordes entre items
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$title = $title ?? '';
$color = $color ?? 'blue';
$empty_text = $empty_text ?? __('No hay elementos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$actions = $actions ?? [];
$numbered = $numbered ?? false;
$bordered = $bordered ?? true;

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Colores de estado
$status_colors = [
    'active'    => 'bg-green-100 text-green-700',
    'pending'   => 'bg-yellow-100 text-yellow-700',
    'inactive'  => 'bg-gray-100 text-gray-600',
    'error'     => 'bg-red-100 text-red-700',
    'new'       => 'bg-blue-100 text-blue-700',
    'completed' => 'bg-green-100 text-green-700',
];
?>

<div class="flavor-quick-list bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Header -->
    <?php if ($title || !empty($actions)): ?>
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <?php if ($title): ?>
                <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php if (!empty($actions)): ?>
                <div class="flex items-center gap-2">
                    <?php foreach ($actions as $action): ?>
                        <?php if (!empty($action['action'])): ?>
                            <button onclick="<?php echo esc_attr($action['action']); ?>"
                                    class="text-sm <?php echo esc_attr($color_classes['text']); ?> hover:underline">
                                <?php echo esc_html($action['label'] ?? ''); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                               class="text-sm <?php echo esc_attr($color_classes['text']); ?> hover:underline">
                                <?php echo esc_html($action['label'] ?? ''); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- List -->
    <div class="<?php echo $bordered ? 'divide-y divide-gray-50' : ''; ?>">
        <?php if (empty($items)): ?>
            <div class="p-6 text-center text-gray-500">
                <p><?php echo esc_html($empty_text); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $index => $item):
                $item_title = $item['title'] ?? '';
                $item_subtitle = $item['subtitle'] ?? '';
                $item_icon = $item['icon'] ?? '';
                $item_image = $item['image'] ?? '';
                $item_badge = $item['badge'] ?? '';
                $item_status = $item['status'] ?? '';
                $item_url = $item['url'] ?? '';
                $item_value = $item['value'] ?? '';
                $item_meta = $item['meta'] ?? '';
                $item_actions = $item['actions'] ?? [];

                $tag = $item_url ? 'a' : 'div';
                $href = $item_url ? 'href="' . esc_url($item_url) . '"' : '';
            ?>
                <<?php echo $tag; ?> <?php echo $href; ?>
                    class="flex items-center gap-3 p-3 <?php echo $item_url ? 'hover:bg-gray-50 cursor-pointer' : ''; ?> transition-colors group">

                    <!-- Número o Icono/Imagen -->
                    <?php if ($numbered): ?>
                        <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-500 text-xs font-medium flex items-center justify-center flex-shrink-0">
                            <?php echo esc_html($index + 1); ?>
                        </span>
                    <?php elseif ($item_image): ?>
                        <img src="<?php echo esc_url($item_image); ?>"
                             alt=""
                             class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                    <?php elseif ($item_icon): ?>
                        <span class="text-xl flex-shrink-0"><?php echo esc_html($item_icon); ?></span>
                    <?php endif; ?>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate <?php echo $item_url ? 'group-hover:' . esc_attr($color_classes['text']) : ''; ?>">
                            <?php echo esc_html($item_title); ?>
                        </p>
                        <?php if ($item_subtitle): ?>
                            <p class="text-xs text-gray-500 truncate"><?php echo esc_html($item_subtitle); ?></p>
                        <?php endif; ?>
                        <?php if ($item_meta): ?>
                            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($item_meta); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if ($item_value): ?>
                            <span class="text-sm font-semibold text-gray-900"><?php echo esc_html($item_value); ?></span>
                        <?php endif; ?>

                        <?php if ($item_status): ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo esc_attr($status_colors[$item_status] ?? 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo esc_html(ucfirst($item_status)); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($item_badge): ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>">
                                <?php echo esc_html($item_badge); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($item_actions)): ?>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php foreach ($item_actions as $action): ?>
                                    <button onclick="event.preventDefault(); event.stopPropagation(); <?php echo esc_attr($action['action'] ?? ''); ?>"
                                            class="p-1 text-gray-400 hover:text-gray-600 rounded"
                                            title="<?php echo esc_attr($action['label'] ?? ''); ?>">
                                        <?php echo esc_html($action['icon'] ?? '⋮'); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($item_url): ?>
                            <span class="text-gray-300 group-hover:text-gray-400">→</span>
                        <?php endif; ?>
                    </div>
                </<?php echo $tag; ?>>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

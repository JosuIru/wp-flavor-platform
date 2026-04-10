<?php
/**
 * Componente: Breadcrumb
 *
 * Navegación de migas de pan reutilizable.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $items      Array de items: [['label' => 'Inicio', 'url' => '/'], ['label' => 'Actual']]
 * @param string $color      Color del tema (para hover)
 * @param string $separator  Separador entre items
 * @param bool   $show_home  Mostrar link a inicio automáticamente
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$color = $color ?? 'blue';
$separator = $separator ?? '›';
$show_home = $show_home ?? true;

// Obtener clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
    $hover_color = str_replace('text-', 'hover:text-', $color_classes['text']);
} else {
    $hover_color = 'hover:text-blue-600';
}

// Añadir inicio si está habilitado
if ($show_home && (empty($items) || $items[0]['label'] !== __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN))) {
    array_unshift($items, [
        'label' => __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'url'   => home_url('/'),
    ]);
}
?>

<nav class="flex items-center flex-wrap gap-2 text-sm text-gray-500 mb-6"
     role="navigation"
     aria-label="<?php esc_attr_e('Migas de pan', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
    <?php foreach ($items as $index => $item):
        $is_last = $index === count($items) - 1;
        $label = $item['label'] ?? '';
        $url = $item['url'] ?? '';
        $icon = $item['icon'] ?? '';
    ?>
        <?php if ($index > 0): ?>
            <span class="text-gray-400" aria-hidden="true"><?php echo esc_html($separator); ?></span>
        <?php endif; ?>

        <?php if ($is_last): ?>
            <span class="text-gray-700 font-medium" aria-current="page">
                <?php if ($icon): ?>
                    <span class="mr-1"><?php echo esc_html($icon); ?></span>
                <?php endif; ?>
                <?php echo esc_html($label); ?>
            </span>
        <?php else: ?>
            <a href="<?php echo esc_url($url); ?>"
               class="<?php echo esc_attr($hover_color); ?> transition-colors">
                <?php if ($icon): ?>
                    <span class="mr-1"><?php echo esc_html($icon); ?></span>
                <?php endif; ?>
                <?php echo esc_html($label); ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

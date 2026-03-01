<?php
/**
 * Componente: Action Cards Grid
 *
 * Renderiza un grid de tarjetas de acción rápida.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $actions  Array de acciones [{title, icon, url, color, description, badge}]
 * @param int    $columns  Número de columnas (2, 3, 4)
 * @param string $size     Tamaño de las cards: 'sm', 'md', 'lg'
 * @param string $layout   Layout de cada card: 'vertical', 'horizontal'
 */

if (!defined('ABSPATH')) {
    exit;
}

$actions = $actions ?? [];
$columns = $columns ?? 4;
$size = $size ?? 'md';
$layout = $layout ?? 'vertical';

if (empty($actions)) {
    return;
}

// Clases de columnas
$columns_classes = [
    2 => 'grid-cols-1 sm:grid-cols-2',
    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-2 md:grid-cols-4',
];
$grid_class = $columns_classes[$columns] ?? $columns_classes[4];
?>

<div class="grid <?php echo esc_attr($grid_class); ?> gap-4">
    <?php foreach ($actions as $action): ?>
        <?php
        flavor_render_component('action-card', array_merge($action, [
            'size'   => $size,
            'layout' => $layout,
        ]));
        ?>
    <?php endforeach; ?>
</div>

<?php
/**
 * Componente: KPI Grid
 *
 * Grid de indicadores KPI para dashboards.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $kpis     Array de KPIs: [['value' => '123', 'label' => 'Total', 'icon' => '📊', 'color' => 'blue', 'trend' => 'up', 'trend_value' => '+5%']]
 * @param int    $columns  Columnas: 2, 3, 4
 * @param string $size     Tamaño de cada KPI: 'sm', 'md', 'lg'
 * @param bool   $compact  Vista compacta
 */

if (!defined('ABSPATH')) {
    exit;
}

$kpis = $kpis ?? [];
$columns = $columns ?? 4;
$size = $size ?? 'md';
$compact = $compact ?? false;

if (empty($kpis)) {
    return;
}

// Clases de grid
$grid_classes = [
    2 => 'grid-cols-1 sm:grid-cols-2',
    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-2 lg:grid-cols-4',
    5 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
    6 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
];
$grid_class = $grid_classes[$columns] ?? $grid_classes[4];
?>

<div class="flavor-kpi-grid grid <?php echo esc_attr($grid_class); ?> gap-4">
    <?php foreach ($kpis as $kpi):
        $kpi['size'] = $kpi['size'] ?? $size;
        $kpi['compact'] = $kpi['compact'] ?? $compact;

        // Renderizar KPI card
        include __DIR__ . '/kpi-card.php';

        // Limpiar variables para el siguiente
        $value = $label = $icon = $color = $trend = $trend_value = $subtitle = $url = null;
    endforeach; ?>
</div>

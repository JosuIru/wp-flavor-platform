<?php
/**
 * Componente: Chart Container
 *
 * Contenedor para gráficos con header y controles.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $title      Título del gráfico
 * @param string $subtitle   Subtítulo opcional
 * @param string $chart_id   ID del canvas para el gráfico
 * @param array  $data       Datos del gráfico (para Chart.js)
 * @param string $type       Tipo: 'line', 'bar', 'doughnut', 'pie', 'area'
 * @param string $color      Color principal
 * @param array  $filters    Filtros de período: [['id' => '7d', 'label' => '7 días'], ...]
 * @param string $height     Altura del gráfico
 * @param bool   $loading    Mostrar skeleton
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? '';
$subtitle = $subtitle ?? '';
$chart_id = $id ?? 'chart-' . wp_rand(1000, 9999);
$data = $data ?? [];
$type = $type ?? 'line';
$color = $color ?? 'blue';
$filters = $filters ?? [];
$height = $height ?? '300px';
$loading = $loading ?? false;

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Colores para gráficos
$chart_colors = [
    'blue'   => ['primary' => '#3b82f6', 'light' => 'rgba(59, 130, 246, 0.1)'],
    'green'  => ['primary' => '#10b981', 'light' => 'rgba(16, 185, 129, 0.1)'],
    'red'    => ['primary' => '#ef4444', 'light' => 'rgba(239, 68, 68, 0.1)'],
    'yellow' => ['primary' => '#f59e0b', 'light' => 'rgba(245, 158, 11, 0.1)'],
    'purple' => ['primary' => '#8b5cf6', 'light' => 'rgba(139, 92, 246, 0.1)'],
];
$chart_color = $chart_colors[$color] ?? $chart_colors['blue'];
?>

<div class="flavor-chart-container bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <?php if ($title): ?>
                <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php if ($subtitle): ?>
                <p class="text-sm text-gray-500"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($filters)): ?>
            <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
                <?php foreach ($filters as $index => $filter): ?>
                    <button type="button"
                            data-period="<?php echo esc_attr($filter['id'] ?? ''); ?>"
                            onclick="flavorChart.updatePeriod('<?php echo esc_js($chart_id); ?>', '<?php echo esc_js($filter['id'] ?? ''); ?>')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors <?php echo $index === 0 ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <?php echo esc_html($filter['label'] ?? ''); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chart Area -->
    <div class="p-4">
        <?php if ($loading): ?>
            <div class="animate-pulse" style="height: <?php echo esc_attr($height); ?>;">
                <div class="h-full bg-gray-100 rounded-lg"></div>
            </div>
        <?php else: ?>
            <div style="height: <?php echo esc_attr($height); ?>;">
                <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$loading && !empty($data)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded');
        return;
    }

    const ctx = document.getElementById('<?php echo esc_js($chart_id); ?>');
    if (!ctx) return;

    const chartData = <?php echo wp_json_encode($data); ?>;
    const chartType = '<?php echo esc_js($type); ?>';
    const primaryColor = '<?php echo esc_js($chart_color['primary']); ?>';
    const lightColor = '<?php echo esc_js($chart_color['light']); ?>';

    // Configuración base según tipo
    const config = {
        type: chartType === 'area' ? 'line' : chartType,
        data: {
            labels: chartData.labels || [],
            datasets: (chartData.datasets || []).map((dataset, index) => ({
                ...dataset,
                borderColor: dataset.borderColor || primaryColor,
                backgroundColor: chartType === 'area' || chartType === 'line'
                    ? (dataset.backgroundColor || lightColor)
                    : (dataset.backgroundColor || primaryColor),
                fill: chartType === 'area',
                tension: 0.4,
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: chartData.datasets && chartData.datasets.length > 1,
                    position: 'bottom'
                }
            },
            scales: chartType === 'doughnut' || chartType === 'pie' ? {} : {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    };

    window['chart_<?php echo esc_js($chart_id); ?>'] = new Chart(ctx, config);
});

window.flavorChart = window.flavorChart || {
    updatePeriod: function(chartId, period) {
        // Actualizar botones
        const container = document.getElementById(chartId).closest('.flavor-chart-container');
        container.querySelectorAll('[data-period]').forEach(btn => {
            if (btn.dataset.period === period) {
                btn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                btn.classList.remove('text-gray-500');
            } else {
                btn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                btn.classList.add('text-gray-500');
            }
        });

        // Dispatch event para actualizar datos
        document.dispatchEvent(new CustomEvent('flavor-chart-period', {
            detail: { chartId, period }
        }));
    }
};
</script>
<?php endif; ?>

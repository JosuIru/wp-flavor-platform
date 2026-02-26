<?php
/**
 * Componente: Stats Grid (KPIs)
 *
 * Grid de estadísticas/KPIs reutilizable con iconos y colores.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $stats    Array de estadísticas, cada una con: value, label, icon, color
 * @param int    $columns  Número de columnas (2 o 4, default: 4)
 * @param string $layout   Tipo de layout: 'horizontal' (icono a la izquierda) o 'vertical' (centrado)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$stats = $stats ?? [];
$columns = $columns ?? 4;
$layout = $layout ?? 'horizontal';

// No renderizar si no hay stats
if (empty($stats)) {
    return;
}

// Clases del grid según columnas
$grid_class = $columns === 2
    ? 'grid-cols-2 gap-4'
    : 'grid-cols-2 md:grid-cols-4 gap-4';
?>

<div class="grid <?php echo esc_attr($grid_class); ?> mb-8">
    <?php foreach ($stats as $stat):
        $value = $stat['value'] ?? $stat['valor'] ?? 0;
        $label = $stat['label'] ?? '';
        $icon = $stat['icon'] ?? '';
        $color = $stat['color'] ?? 'blue';
        $url = $stat['url'] ?? '';

        $color_classes = flavor_get_color_classes($color);
    ?>

    <?php if ($layout === 'vertical'): ?>
        <!-- Layout Vertical (centrado, con emoji grande) -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <?php if ($icon): ?>
            <div class="text-3xl mb-2"><?php echo esc_html($icon); ?></div>
            <?php endif; ?>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($value); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html($label); ?></p>
        </div>
    <?php else: ?>
        <!-- Layout Horizontal (icono a la izquierda) -->
        <?php if ($url): ?>
        <a href="<?php echo esc_url($url); ?>" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <?php else: ?>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <?php endif; ?>
            <div class="flex items-center gap-3">
                <?php if ($icon): ?>
                <div class="w-10 h-10 <?php echo esc_attr($color_classes['bg']); ?> rounded-lg flex items-center justify-center">
                    <span class="<?php echo esc_attr($color_classes['text']); ?>"><?php echo esc_html($icon); ?></span>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($value); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html($label); ?></p>
                </div>
            </div>
        <?php if ($url): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php endforeach; ?>
</div>

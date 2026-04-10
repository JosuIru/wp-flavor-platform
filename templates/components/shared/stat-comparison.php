<?php
/**
 * Componente: Stat Comparison
 *
 * Comparación de estadísticas entre periodos.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $current      Datos actuales: ['value' => 100, 'label' => 'Este mes']
 * @param array  $previous     Datos anteriores: ['value' => 80, 'label' => 'Mes pasado']
 * @param string $title        Título de la comparación
 * @param string $format       Formato: 'number', 'currency', 'percent'
 * @param string $currency     Símbolo de moneda
 * @param string $color        Color del tema
 * @param string $layout       Layout: 'horizontal', 'vertical', 'card'
 * @param bool   $show_chart   Mostrar mini chart de barras
 * @param string $icon         Icono
 */

if (!defined('ABSPATH')) {
    exit;
}

$current = $current ?? ['value' => 0, 'label' => __('Actual', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$previous = $previous ?? ['value' => 0, 'label' => __('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$title = $title ?? '';
$format = $format ?? 'number';
$currency = $currency ?? '€';
$color = $color ?? 'blue';
$layout = $layout ?? 'horizontal';
$show_chart = $show_chart ?? true;
$icon = $icon ?? '';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Calcular diferencia
$current_val = floatval($current['value'] ?? 0);
$previous_val = floatval($previous['value'] ?? 0);
$difference = $current_val - $previous_val;
$percentage_change = $previous_val > 0 ? (($current_val - $previous_val) / $previous_val) * 100 : ($current_val > 0 ? 100 : 0);
$is_positive = $difference >= 0;

// Formatear valores
function format_stat_value($value, $format, $currency = '€') {
    switch ($format) {
        case 'currency':
            return number_format($value, 2, ',', '.') . ' ' . $currency;
        case 'percent':
            return number_format($value, 1, ',', '.') . '%';
        default:
            return number_format($value, 0, ',', '.');
    }
}

$formatted_current = format_stat_value($current_val, $format, $currency);
$formatted_previous = format_stat_value($previous_val, $format, $currency);
$formatted_difference = ($is_positive ? '+' : '') . format_stat_value($difference, $format, $currency);
$formatted_percent = ($is_positive ? '+' : '') . number_format($percentage_change, 1, ',', '.') . '%';

// Para mini chart de barras
$max_val = max($current_val, $previous_val);
$current_percent = $max_val > 0 ? ($current_val / $max_val) * 100 : 0;
$previous_percent = $max_val > 0 ? ($previous_val / $max_val) * 100 : 0;
?>

<?php if ($layout === 'card'): ?>
    <!-- Layout Card -->
    <div class="flavor-stat-comparison bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <?php if ($icon): ?>
                    <span class="text-2xl"><?php echo esc_html($icon); ?></span>
                <?php endif; ?>
                <h3 class="font-semibold text-gray-900"><?php echo esc_html($title); ?></h3>
            </div>
            <span class="px-2 py-1 text-sm font-medium rounded-full
                <?php echo $is_positive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo esc_html($formatted_percent); ?>
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Valor actual -->
            <div class="text-center p-4 rounded-xl <?php echo esc_attr($color_classes['bg']); ?>">
                <p class="text-2xl font-bold <?php echo esc_attr($color_classes['text']); ?>">
                    <?php echo esc_html($formatted_current); ?>
                </p>
                <p class="text-sm text-gray-600"><?php echo esc_html($current['label'] ?? ''); ?></p>
            </div>

            <!-- Valor anterior -->
            <div class="text-center p-4 rounded-xl bg-gray-100">
                <p class="text-2xl font-bold text-gray-700">
                    <?php echo esc_html($formatted_previous); ?>
                </p>
                <p class="text-sm text-gray-500"><?php echo esc_html($previous['label'] ?? ''); ?></p>
            </div>
        </div>

        <?php if ($show_chart): ?>
            <!-- Mini chart de barras -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-20"><?php echo esc_html($current['label'] ?? ''); ?></span>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-full transition-all duration-500"
                             style="width: <?php echo esc_attr($current_percent); ?>%"></div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-20"><?php echo esc_html($previous['label'] ?? ''); ?></span>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gray-400 rounded-full transition-all duration-500"
                             style="width: <?php echo esc_attr($previous_percent); ?>%"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Diferencia -->
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
            <span class="text-sm text-gray-500"><?php esc_html_e('Diferencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span class="font-semibold <?php echo $is_positive ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $is_positive ? '↑' : '↓'; ?> <?php echo esc_html($formatted_difference); ?>
            </span>
        </div>
    </div>

<?php elseif ($layout === 'vertical'): ?>
    <!-- Layout Vertical -->
    <div class="flavor-stat-comparison text-center">
        <?php if ($title): ?>
            <h4 class="text-sm font-medium text-gray-500 mb-2"><?php echo esc_html($title); ?></h4>
        <?php endif; ?>

        <div class="text-3xl font-bold text-gray-900 mb-1">
            <?php echo esc_html($formatted_current); ?>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm">
            <span class="<?php echo $is_positive ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $is_positive ? '↑' : '↓'; ?> <?php echo esc_html($formatted_percent); ?>
            </span>
            <span class="text-gray-400">vs <?php echo esc_html($formatted_previous); ?></span>
        </div>
    </div>

<?php else: ?>
    <!-- Layout Horizontal (default) -->
    <div class="flavor-stat-comparison flex items-center gap-6">
        <?php if ($icon): ?>
            <div class="w-12 h-12 rounded-xl <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center flex-shrink-0">
                <span class="text-xl"><?php echo esc_html($icon); ?></span>
            </div>
        <?php endif; ?>

        <div class="flex-1">
            <?php if ($title): ?>
                <h4 class="text-sm font-medium text-gray-500"><?php echo esc_html($title); ?></h4>
            <?php endif; ?>

            <div class="flex items-baseline gap-3">
                <span class="text-2xl font-bold text-gray-900"><?php echo esc_html($formatted_current); ?></span>
                <span class="text-sm text-gray-400">
                    <?php esc_html_e('vs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($formatted_previous); ?>
                </span>
            </div>
        </div>

        <div class="flex-shrink-0 text-right">
            <span class="inline-flex items-center gap-1 px-2 py-1 text-sm font-medium rounded-full
                <?php echo $is_positive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $is_positive ? '↑' : '↓'; ?>
                <?php echo esc_html($formatted_percent); ?>
            </span>
            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($formatted_difference); ?></p>
        </div>
    </div>
<?php endif; ?>

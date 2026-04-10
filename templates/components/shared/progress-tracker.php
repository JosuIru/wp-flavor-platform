<?php
/**
 * Componente: Progress Tracker
 *
 * Tracker de progreso con pasos o barras.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $type       Tipo: 'bar', 'steps', 'circular'
 * @param int    $value      Valor actual (0-100 para bar/circular)
 * @param int    $max        Valor máximo
 * @param array  $steps      Para type=steps: [['label' => 'Paso 1', 'status' => 'completed'], ...]
 * @param string $label      Etiqueta
 * @param string $color      Color del tema
 * @param string $size       Tamaño: 'sm', 'md', 'lg'
 * @param bool   $show_value Mostrar valor numérico
 * @param string $format     Formato del valor: 'percent', 'fraction', 'value'
 */

if (!defined('ABSPATH')) {
    exit;
}

$type = $type ?? 'bar';
$value = $value ?? 0;
$max = $max ?? 100;
$steps = $steps ?? [];
$label = $label ?? '';
$color = $color ?? 'blue';
$size = $size ?? 'md';
$show_value = $show_value ?? true;
$format = $format ?? 'percent';

// Calcular porcentaje
$percentage = $max > 0 ? round(($value / $max) * 100) : 0;
$percentage = min(100, max(0, $percentage));

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Tamaños
$size_config = [
    'sm' => ['bar' => 'h-1', 'text' => 'text-xs', 'circle' => 60, 'stroke' => 4],
    'md' => ['bar' => 'h-2', 'text' => 'text-sm', 'circle' => 80, 'stroke' => 6],
    'lg' => ['bar' => 'h-3', 'text' => 'text-base', 'circle' => 120, 'stroke' => 8],
];
$sizes = $size_config[$size] ?? $size_config['md'];

// Formatear valor
$display_value = '';
switch ($format) {
    case 'percent':
        $display_value = $percentage . '%';
        break;
    case 'fraction':
        $display_value = $value . '/' . $max;
        break;
    case 'value':
        $display_value = $value;
        break;
}
?>

<div class="flavor-progress-tracker">
    <?php if ($type === 'bar'): ?>
        <!-- Barra de progreso -->
        <div class="space-y-2">
            <?php if ($label || $show_value): ?>
                <div class="flex items-center justify-between">
                    <?php if ($label): ?>
                        <span class="<?php echo esc_attr($sizes['text']); ?> font-medium text-gray-700">
                            <?php echo esc_html($label); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($show_value): ?>
                        <span class="<?php echo esc_attr($sizes['text']); ?> font-medium <?php echo esc_attr($color_classes['text']); ?>">
                            <?php echo esc_html($display_value); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="w-full bg-gray-100 rounded-full overflow-hidden <?php echo esc_attr($sizes['bar']); ?>">
                <div class="<?php echo esc_attr($color_classes['bg_solid']); ?> <?php echo esc_attr($sizes['bar']); ?> rounded-full transition-all duration-500"
                     style="width: <?php echo esc_attr($percentage); ?>%"></div>
            </div>
        </div>

    <?php elseif ($type === 'circular'): ?>
        <!-- Progreso circular -->
        <?php
        $circle_size = $sizes['circle'];
        $stroke_width = $sizes['stroke'];
        $radius = ($circle_size - $stroke_width) / 2;
        $circumference = 2 * M_PI * $radius;
        $offset = $circumference - ($percentage / 100) * $circumference;
        ?>
        <div class="flex flex-col items-center">
            <div class="relative" style="width: <?php echo esc_attr($circle_size); ?>px; height: <?php echo esc_attr($circle_size); ?>px;">
                <svg class="transform -rotate-90" width="<?php echo esc_attr($circle_size); ?>" height="<?php echo esc_attr($circle_size); ?>">
                    <!-- Background circle -->
                    <circle cx="<?php echo esc_attr($circle_size / 2); ?>"
                            cy="<?php echo esc_attr($circle_size / 2); ?>"
                            r="<?php echo esc_attr($radius); ?>"
                            stroke="currentColor"
                            stroke-width="<?php echo esc_attr($stroke_width); ?>"
                            fill="none"
                            class="text-gray-100" />
                    <!-- Progress circle -->
                    <circle cx="<?php echo esc_attr($circle_size / 2); ?>"
                            cy="<?php echo esc_attr($circle_size / 2); ?>"
                            r="<?php echo esc_attr($radius); ?>"
                            stroke="currentColor"
                            stroke-width="<?php echo esc_attr($stroke_width); ?>"
                            fill="none"
                            stroke-linecap="round"
                            class="<?php echo esc_attr($color_classes['text']); ?> transition-all duration-500"
                            style="stroke-dasharray: <?php echo esc_attr($circumference); ?>; stroke-dashoffset: <?php echo esc_attr($offset); ?>;" />
                </svg>
                <?php if ($show_value): ?>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="<?php echo esc_attr($sizes['text']); ?> font-bold text-gray-900">
                            <?php echo esc_html($display_value); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($label): ?>
                <span class="mt-2 <?php echo esc_attr($sizes['text']); ?> text-gray-600">
                    <?php echo esc_html($label); ?>
                </span>
            <?php endif; ?>
        </div>

    <?php elseif ($type === 'steps'): ?>
        <!-- Pasos de progreso -->
        <div class="flex items-center">
            <?php foreach ($steps as $index => $step):
                $step_status = $step['status'] ?? 'pending'; // completed, current, pending
                $step_label = $step['label'] ?? '';
                $step_icon = $step['icon'] ?? '';

                $is_completed = $step_status === 'completed';
                $is_current = $step_status === 'current';
                $is_last = $index === count($steps) - 1;
            ?>
                <!-- Step -->
                <div class="flex items-center <?php echo $is_last ? '' : 'flex-1'; ?>">
                    <div class="flex flex-col items-center">
                        <!-- Circle -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-colors
                            <?php if ($is_completed): ?>
                                <?php echo esc_attr($color_classes['bg_solid']); ?> border-transparent text-white
                            <?php elseif ($is_current): ?>
                                border-<?php echo esc_attr($color); ?>-500 <?php echo esc_attr($color_classes['text']); ?> bg-white
                            <?php else: ?>
                                border-gray-300 text-gray-400 bg-white
                            <?php endif; ?>">
                            <?php if ($is_completed): ?>
                                <span>✓</span>
                            <?php elseif ($step_icon): ?>
                                <span class="text-sm"><?php echo esc_html($step_icon); ?></span>
                            <?php else: ?>
                                <span class="text-sm font-medium"><?php echo esc_html($index + 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Label -->
                        <?php if ($step_label): ?>
                            <span class="mt-2 text-xs font-medium text-center max-w-[80px]
                                <?php echo $is_completed || $is_current ? 'text-gray-900' : 'text-gray-400'; ?>">
                                <?php echo esc_html($step_label); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Connector -->
                    <?php if (!$is_last): ?>
                        <div class="flex-1 h-0.5 mx-2
                            <?php echo $is_completed ? esc_attr($color_classes['bg_solid']) : 'bg-gray-200'; ?>">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

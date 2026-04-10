<?php
/**
 * Componente: Progress Bar
 *
 * Barra de progreso con múltiples variantes y estados.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param int    $value       Valor actual (0-100 o absoluto si $max se proporciona)
 * @param int    $max         Valor máximo (default 100)
 * @param string $label       Etiqueta
 * @param bool   $show_value  Mostrar valor numérico
 * @param string $value_format Formato: percent, fraction, custom
 * @param string $custom_text Texto personalizado para value_format=custom
 * @param string $color       Color: blue, green, red, yellow, purple, gradient
 * @param string $size        Tamaño: xs, sm, md, lg
 * @param string $variant     Variante: default, striped, animated, circular
 * @param bool   $indeterminate Progreso indeterminado (animación continua)
 */

if (!defined('ABSPATH')) {
    exit;
}

$value = floatval($value ?? 0);
$max = floatval($max ?? 100);
$label = $label ?? '';
$show_value = $show_value ?? true;
$value_format = $value_format ?? 'percent';
$custom_text = $custom_text ?? '';
$color = $color ?? 'blue';
$size = $size ?? 'md';
$variant = $variant ?? 'default';
$indeterminate = $indeterminate ?? false;

// Calcular porcentaje
$percent = $max > 0 ? min(100, ($value / $max) * 100) : 0;

// Colores
$color_classes = [
    'blue'     => 'bg-blue-600',
    'green'    => 'bg-green-600',
    'red'      => 'bg-red-600',
    'yellow'   => 'bg-yellow-500',
    'purple'   => 'bg-purple-600',
    'orange'   => 'bg-orange-500',
    'gray'     => 'bg-gray-600',
    'gradient' => 'bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500',
];
$bar_color = $color_classes[$color] ?? $color_classes['blue'];

// Tamaños
$size_classes = [
    'xs' => 'h-1',
    'sm' => 'h-2',
    'md' => 'h-3',
    'lg' => 'h-4',
    'xl' => 'h-6',
];
$bar_height = $size_classes[$size] ?? $size_classes['md'];

// Formato del valor
if ($value_format === 'percent') {
    $display_value = round($percent) . '%';
} elseif ($value_format === 'fraction') {
    $display_value = number_format_i18n($value) . ' / ' . number_format_i18n($max);
} else {
    $display_value = $custom_text ?: number_format_i18n($value);
}

// Clases adicionales para variantes
$variant_classes = '';
if ($variant === 'striped' || $variant === 'animated') {
    $variant_classes = 'bg-stripes';
}
if ($variant === 'animated' || $indeterminate) {
    $variant_classes .= ' animate-progress-stripes';
}
?>

<?php if ($variant === 'circular'): ?>
    <!-- Variante Circular -->
    <?php
    $circle_size = ['xs' => 40, 'sm' => 60, 'md' => 80, 'lg' => 100, 'xl' => 120][$size] ?? 80;
    $stroke_width = ['xs' => 4, 'sm' => 5, 'md' => 6, 'lg' => 8, 'xl' => 10][$size] ?? 6;
    $radius = ($circle_size - $stroke_width) / 2;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($percent / 100) * $circumference;
    $text_size = ['xs' => 'text-xs', 'sm' => 'text-sm', 'md' => 'text-lg', 'lg' => 'text-xl', 'xl' => 'text-2xl'][$size] ?? 'text-lg';
    ?>
    <div class="flavor-progress-circular inline-flex flex-col items-center gap-2">
        <div class="relative" style="width: <?php echo $circle_size; ?>px; height: <?php echo $circle_size; ?>px;">
            <svg class="transform -rotate-90" width="<?php echo $circle_size; ?>" height="<?php echo $circle_size; ?>">
                <!-- Background circle -->
                <circle
                    cx="<?php echo $circle_size / 2; ?>"
                    cy="<?php echo $circle_size / 2; ?>"
                    r="<?php echo $radius; ?>"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="<?php echo $stroke_width; ?>"
                    class="text-gray-200"
                />
                <!-- Progress circle -->
                <circle
                    cx="<?php echo $circle_size / 2; ?>"
                    cy="<?php echo $circle_size / 2; ?>"
                    r="<?php echo $radius; ?>"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="<?php echo $stroke_width; ?>"
                    stroke-linecap="round"
                    class="<?php echo str_replace('bg-', 'text-', $bar_color); ?> transition-all duration-500"
                    style="stroke-dasharray: <?php echo $circumference; ?>; stroke-dashoffset: <?php echo $offset; ?>;"
                    <?php if ($indeterminate): ?>
                    style="animation: progress-circular-spin 1.5s linear infinite;"
                    <?php endif; ?>
                />
            </svg>
            <?php if ($show_value): ?>
                <span class="absolute inset-0 flex items-center justify-center font-bold <?php echo esc_attr($text_size); ?> text-gray-700">
                    <?php echo esc_html($display_value); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($label): ?>
            <span class="text-sm text-gray-600"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Variante Default/Striped/Animated -->
    <div class="flavor-progress-bar w-full">
        <?php if ($label || $show_value): ?>
            <div class="flex items-center justify-between mb-1">
                <?php if ($label): ?>
                    <span class="text-sm font-medium text-gray-700"><?php echo esc_html($label); ?></span>
                <?php endif; ?>
                <?php if ($show_value): ?>
                    <span class="text-sm font-medium text-gray-500"><?php echo esc_html($display_value); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="w-full bg-gray-200 rounded-full overflow-hidden <?php echo esc_attr($bar_height); ?>">
            <?php if ($indeterminate): ?>
                <div class="h-full <?php echo esc_attr($bar_color); ?> rounded-full animate-progress-indeterminate" style="width: 30%;"></div>
            <?php else: ?>
                <div class="h-full <?php echo esc_attr($bar_color); ?> <?php echo esc_attr($variant_classes); ?> rounded-full transition-all duration-500"
                     style="width: <?php echo $percent; ?>%;"
                     role="progressbar"
                     aria-valuenow="<?php echo $value; ?>"
                     aria-valuemin="0"
                     aria-valuemax="<?php echo $max; ?>">
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
/* Rayas para variante striped */
.bg-stripes {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 1rem 1rem;
}

/* Animación de rayas */
@keyframes progress-stripes {
    from { background-position: 1rem 0; }
    to { background-position: 0 0; }
}
.animate-progress-stripes {
    animation: progress-stripes 1s linear infinite;
}

/* Animación indeterminada */
@keyframes progress-indeterminate {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
.animate-progress-indeterminate {
    animation: progress-indeterminate 1.5s ease-in-out infinite;
}

/* Animación circular */
@keyframes progress-circular-spin {
    0% { stroke-dashoffset: 0; }
    50% { stroke-dashoffset: 100; }
    100% { stroke-dashoffset: 0; }
}
</style>

<?php
/**
 * Componente: KPI Card
 *
 * Tarjeta de indicador clave de rendimiento.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $value      Valor principal (ej: "1,234", "85%")
 * @param string $label      Etiqueta del KPI
 * @param string $icon       Icono emoji
 * @param string $color      Color del tema
 * @param string $trend      Tendencia: 'up', 'down', 'neutral'
 * @param string $trend_value Valor de tendencia (ej: "+12%", "-5")
 * @param string $subtitle   Subtítulo opcional
 * @param string $url        URL al hacer clic
 * @param string $size       Tamaño: 'sm', 'md', 'lg'
 * @param bool   $compact    Vista compacta
 */

if (!defined('ABSPATH')) {
    exit;
}

$value = $value ?? '0';
$label = $label ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$trend = $trend ?? '';
$trend_value = $trend_value ?? '';
$subtitle = $subtitle ?? '';
$url = $url ?? '';
$size = $size ?? 'md';
$compact = $compact ?? false;

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Configuración de tendencia
$trend_config = [
    'up'      => ['icon' => '↑', 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
    'down'    => ['icon' => '↓', 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
    'neutral' => ['icon' => '→', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50'],
];
$trend_info = $trend_config[$trend] ?? null;

// Clases de tamaño
$size_config = [
    'sm' => ['padding' => 'p-3', 'value' => 'text-xl', 'label' => 'text-xs', 'icon' => 'text-lg'],
    'md' => ['padding' => 'p-4', 'value' => 'text-2xl', 'label' => 'text-sm', 'icon' => 'text-xl'],
    'lg' => ['padding' => 'p-6', 'value' => 'text-4xl', 'label' => 'text-base', 'icon' => 'text-3xl'],
];
$sizes = $size_config[$size] ?? $size_config['md'];

$tag = $url ? 'a' : 'div';
$href = $url ? 'href="' . esc_url($url) . '"' : '';
$hover_class = $url ? 'hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all' : '';
?>

<<?php echo $tag; ?> <?php echo $href; ?>
    class="flavor-kpi-card bg-white rounded-2xl border border-gray-100 shadow-sm <?php echo esc_attr($sizes['padding']); ?> <?php echo esc_attr($hover_class); ?>">

    <?php if ($compact): ?>
        <!-- Layout compacto horizontal -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <?php if ($icon): ?>
                    <div class="w-10 h-10 rounded-xl <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
                        <span class="<?php echo esc_attr($sizes['icon']); ?>"><?php echo esc_html($icon); ?></span>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="<?php echo esc_attr($sizes['label']); ?> text-gray-500"><?php echo esc_html($label); ?></p>
                    <p class="<?php echo esc_attr($sizes['value']); ?> font-bold text-gray-900"><?php echo esc_html($value); ?></p>
                </div>
            </div>
            <?php if ($trend_info && $trend_value): ?>
                <div class="px-2 py-1 rounded-lg <?php echo esc_attr($trend_info['bg']); ?>">
                    <span class="<?php echo esc_attr($trend_info['color']); ?> text-sm font-medium">
                        <?php echo esc_html($trend_info['icon']); ?> <?php echo esc_html($trend_value); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Layout vertical estándar -->
        <div class="flex items-start justify-between mb-3">
            <?php if ($icon): ?>
                <div class="w-12 h-12 rounded-xl <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
                    <span class="<?php echo esc_attr($sizes['icon']); ?>"><?php echo esc_html($icon); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($trend_info && $trend_value): ?>
                <div class="px-2 py-1 rounded-lg <?php echo esc_attr($trend_info['bg']); ?>">
                    <span class="<?php echo esc_attr($trend_info['color']); ?> text-sm font-medium">
                        <?php echo esc_html($trend_info['icon']); ?> <?php echo esc_html($trend_value); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <p class="<?php echo esc_attr($sizes['value']); ?> font-bold text-gray-900 mb-1">
                <?php echo esc_html($value); ?>
            </p>
            <p class="<?php echo esc_attr($sizes['label']); ?> text-gray-500">
                <?php echo esc_html($label); ?>
            </p>
            <?php if ($subtitle): ?>
                <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</<?php echo $tag; ?>>

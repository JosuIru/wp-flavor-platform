<?php
/**
 * Componente: Dashboard Header
 *
 * Header reutilizable para dashboards de módulos con título, subtítulo,
 * icono, y acciones.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $title    Título principal
 * @param string $subtitle Subtítulo opcional
 * @param string $icon     Emoji o icono
 * @param string $color    Color del tema (red, green, blue, etc.)
 * @param array  $actions  Acciones del header [{label, url, icon, primary}]
 * @param string $badge    Badge opcional (ej: "123 registros")
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$actions = $actions ?? [];
$badge = $badge ?? '';

// Clases de color
$gradient_classes = function_exists('flavor_get_gradient_classes')
    ? flavor_get_gradient_classes($color)
    : ['from' => 'from-blue-500', 'to' => 'to-indigo-600'];

$color_classes = function_exists('flavor_get_color_classes')
    ? flavor_get_color_classes($color)
    : ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
?>

<div class="flavor-dashboard-header mb-6">
    <div class="bg-gradient-to-r <?php echo esc_attr($gradient_classes['from']); ?> <?php echo esc_attr($gradient_classes['to']); ?> rounded-2xl p-6 text-white shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Título y subtítulo -->
            <div class="flex items-center gap-4">
                <?php if ($icon): ?>
                    <span class="text-4xl"><?php echo esc_html($icon); ?></span>
                <?php endif; ?>

                <div>
                    <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2">
                        <?php echo esc_html($title); ?>
                        <?php if ($badge): ?>
                            <span class="text-sm font-normal bg-white/20 px-3 py-1 rounded-full">
                                <?php echo esc_html($badge); ?>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <?php if ($subtitle): ?>
                        <p class="text-white/80 mt-1"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones -->
            <?php if (!empty($actions)): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($actions as $action): ?>
                        <?php
                        $is_primary = $action['primary'] ?? false;
                        $btn_class = $is_primary
                            ? 'bg-white text-gray-900 hover:bg-gray-100'
                            : 'bg-white/20 text-white hover:bg-white/30';
                        ?>
                        <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                           <?php if (!empty($action['onclick'])): ?>
                           onclick="<?php echo esc_attr($action['onclick']); ?>"
                           <?php endif; ?>
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition-colors <?php echo esc_attr($btn_class); ?>">
                            <?php if (!empty($action['icon'])): ?>
                                <span><?php echo esc_html($action['icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

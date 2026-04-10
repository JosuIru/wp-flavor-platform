<?php
/**
 * Componente: Cómo Funciona (Genérico)
 *
 * Sección explicativa del proceso de un módulo.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $steps Pasos del proceso [{icon, title, text}]
 * @param string $title Título de la sección (opcional)
 * @param string $color Color de fondo (tailwind color name, default: lime)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

$steps = $steps ?? [];
$title = $title ?? __('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color = $color ?? 'lime';

// No renderizar si no hay pasos
if (empty($steps)) {
    return;
}

// Obtener clases de color
$color_classes = flavor_get_color_classes($color);
?>

<div class="bg-<?php echo esc_attr($color); ?>-50 rounded-2xl p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">💡 <?php echo esc_html($title); ?></h2>

    <div class="grid grid-cols-1 md:grid-cols-<?php echo count($steps) > 4 ? '4' : count($steps); ?> gap-6">
        <?php foreach ($steps as $step): ?>
        <div class="text-center">
            <div class="w-16 h-16 <?php echo esc_attr($color_classes['bg_solid']); ?> text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
                <?php echo esc_html($step['icon']); ?>
            </div>
            <h3 class="font-semibold text-gray-800 mb-1">
                <?php echo esc_html($step['title']); ?>
            </h3>
            <p class="text-sm text-gray-600">
                <?php echo esc_html($step['text']); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
/**
 * Componente: Cómo Funciona (Marketplace)
 *
 * Sección explicativa del proceso del marketplace.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array $steps Pasos personalizados (opcional)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pasos por defecto
$default_steps = [
    [
        'icon'  => '📸',
        'title' => __('Publica', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'text'  => __('Sube fotos y describe tu producto para que otros lo encuentren', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    [
        'icon'  => '💬',
        'title' => __('Contacta', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'text'  => __('Habla directamente con compradores o vendedores cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
    [
        'icon'  => '🎉',
        'title' => __('Intercambia', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'text'  => __('Acuerda el precio y recoge el producto en tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
];

$steps = $steps ?? $default_steps;
?>

<div class="bg-lime-50 rounded-2xl p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <?php echo esc_html__('💡 ¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($steps as $step): ?>
        <div class="text-center">
            <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
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

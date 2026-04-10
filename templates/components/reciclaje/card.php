<?php
/**
 * Componente: Card de Punto de Reciclaje
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$punto = $item ?? $card_item ?? [];
if (empty($punto)) return;

$id = $punto['id'] ?? 0;
$nombre = $punto['nombre'] ?? __('Punto de reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN);
$direccion = $punto['direccion'] ?? '';
$url = $punto['url'] ?? '#';
$distancia = $punto['distancia'] ?? '';
$contenedores = $punto['contenedores'] ?? [];
$horario = $punto['horario'] ?? '';
$tipos = $punto['tipos'] ?? [];
?>

<article class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow"
         data-tipo="<?php echo esc_attr(implode(',', array_column($tipos, 'slug'))); ?>">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
            ♻️
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($nombre); ?></h3>

            <?php if ($direccion): ?>
            <p class="text-sm text-gray-500 mb-2">📍 <?php echo esc_html($direccion); ?></p>
            <?php endif; ?>

            <!-- Contenedores disponibles -->
            <?php if (!empty($contenedores)): ?>
            <div class="flex gap-2 mb-2">
                <?php foreach ($contenedores as $contenedor): ?>
                    <span class="w-6 h-6 rounded <?php echo esc_attr($contenedor['color'] ?? 'bg-gray-400'); ?>"
                          title="<?php echo esc_attr($contenedor['nombre'] ?? ''); ?>"></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($distancia): ?>
            <span class="text-sm text-emerald-600 font-medium">📏 <?php echo esc_html($distancia); ?></span>
            <?php endif; ?>
        </div>

        <a href="<?php echo esc_url($url); ?>"
           class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-medium text-sm hover:bg-emerald-200 transition-colors flex-shrink-0">
            <?php echo esc_html__('Cómo llegar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</article>

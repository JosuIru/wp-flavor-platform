<?php
/**
 * Componente: Card de Viaje Carpooling
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$viaje = $item ?? $card_item ?? [];
if (empty($viaje)) return;

$id = $viaje['id'] ?? 0;
$url = $viaje['url'] ?? '#';
$origen = $viaje['origen'] ?? '';
$destino = $viaje['destino'] ?? '';
$fecha = $viaje['fecha'] ?? '';
$hora = $viaje['hora'] ?? '';
$plazas_libres = $viaje['plazas_libres'] ?? 0;
$precio = $viaje['precio'] ?? 0;
$conductor_nombre = $viaje['conductor_nombre'] ?? __('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN);
$conductor_inicial = mb_substr($conductor_nombre, 0, 1);
$valoracion = $viaje['valoracion'] ?? '4.8';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-destino="<?php echo esc_attr(sanitize_title($destino)); ?>">
    <div class="p-5">
        <!-- Ruta visual -->
        <div class="flex items-center gap-3 mb-4">
            <div class="flex flex-col items-center">
                <div class="w-3 h-3 rounded-full bg-lime-500"></div>
                <div class="w-0.5 h-8 bg-lime-300"></div>
                <div class="w-3 h-3 rounded-full bg-green-600"></div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($origen); ?></p>
                <div class="h-4"></div>
                <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($destino); ?></p>
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-green-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($origen); ?> → <?php echo esc_html($destino); ?>
            </a>
        </h3>

        <div class="flex flex-wrap gap-2 mb-3">
            <?php if ($fecha): ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                📅 <?php echo esc_html($fecha); ?>
            </span>
            <?php endif; ?>
            <?php if ($hora): ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                🕐 <?php echo esc_html($hora); ?>
            </span>
            <?php endif; ?>
            <span class="bg-lime-100 text-lime-700 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                💺 <?php echo esc_html($plazas_libres); ?> <?php echo esc_html__('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                    <?php echo esc_html($conductor_inicial); ?>
                </div>
                <div>
                    <span class="text-sm text-gray-600"><?php echo esc_html($conductor_nombre); ?></span>
                    <span class="text-xs text-gray-400 ml-1">⭐ <?php echo esc_html($valoracion); ?></span>
                </div>
            </div>
            <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm">
                <?php echo esc_html($precio); ?> €
            </span>
        </div>
    </div>
</article>

<?php
/**
 * Componente: Card de Huerto Urbano
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$huerto = $item ?? $card_item ?? [];
if (empty($huerto)) return;

$id = $huerto['id'] ?? 0;
$nombre = $huerto['nombre'] ?? $huerto['title'] ?? '';
$descripcion = $huerto['descripcion'] ?? '';
$url = $huerto['url'] ?? '#';
$imagen = $huerto['imagen'] ?? 'https://picsum.photos/seed/huerto' . $id . '/600/400';
$ubicacion = $huerto['ubicacion'] ?? __('Sin ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN);
$parcelas_libres = $huerto['parcelas_libres'] ?? 0;
$tamano_parcela = $huerto['tamano_parcela'] ?? '25';
$precio = $huerto['precio'] ?? '20€';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-estado="<?php echo $parcelas_libres > 0 ? 'disponible' : 'completo'; ?>">
    <div class="relative aspect-[16/10] overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

        <!-- Badge parcelas -->
        <?php if ($parcelas_libres > 0): ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                <?php echo esc_html($parcelas_libres); ?> <?php echo esc_html__('libres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php else: ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white">
                <?php echo esc_html__('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php endif; ?>

        <!-- Ubicación -->
        <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
            📍 <?php echo esc_html($ubicacion); ?>
        </div>
    </div>

    <div class="p-5">
        <h2 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors mb-2">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($nombre); ?>
            </a>
        </h2>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <!-- Info -->
        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
            <span class="flex items-center gap-1">
                📐 <?php echo esc_html($tamano_parcela); ?>m²/parcela
            </span>
            <span class="flex items-center gap-1">
                💰 <?php echo esc_html($precio); ?>/mes
            </span>
        </div>

        <a href="<?php echo esc_url($url); ?>"
           class="block w-full py-2.5 rounded-xl text-center font-semibold text-white transition-all hover:scale-105 bg-gradient-to-r from-green-500 to-emerald-600">
            <?php echo esc_html__('Ver Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</article>

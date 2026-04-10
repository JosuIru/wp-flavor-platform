<?php
/**
 * Componente: Card de Bar/Restaurante
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$local = $item ?? $card_item ?? [];
if (empty($local)) return;

$id = $local['id'] ?? 0;
$nombre = $local['nombre'] ?? $local['title'] ?? '';
$url = $local['url'] ?? '#';
$imagen = $local['imagen'] ?? '';
$tipo_cocina = $local['tipo_cocina'] ?? 'Variada';
$rango_precio = $local['rango_precio'] ?? 2;
$valoracion = floatval($local['valoracion'] ?? 0);
$total_resenas = $local['total_resenas'] ?? 0;
$abierto = !empty($local['abierto']);
$distancia = $local['distancia'] ?? '';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-categoria="<?php echo esc_attr(sanitize_title($tipo_cocina)); ?>">
    <div class="aspect-video bg-gray-100 relative overflow-hidden">
        <?php if ($imagen): ?>
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-gray-400">
            <span class="text-5xl">🍽️</span>
        </div>
        <?php endif; ?>
        <span class="absolute top-3 left-3 <?php echo $abierto ? 'bg-green-500' : 'bg-red-500'; ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
            <?php echo $abierto ? esc_html__('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
    </div>

    <div class="p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="bg-amber-100 text-amber-700 text-xs font-medium px-3 py-1 rounded-full">
                <?php echo esc_html($tipo_cocina); ?>
            </span>
            <span class="text-amber-600 font-bold text-sm">
                <?php echo esc_html(str_repeat('€', $rango_precio)); ?>
            </span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-orange-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($nombre); ?>
            </a>
        </h3>

        <div class="flex items-center gap-1 mb-2">
            <div class="flex text-yellow-400 text-sm">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <span><?php echo $i <= $valoracion ? '★' : '☆'; ?></span>
                <?php endfor; ?>
            </div>
            <span class="text-sm text-gray-500">(<?php echo esc_html($total_resenas); ?>)</span>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-sm text-gray-500">
            <?php if ($distancia): ?>
            <span>📍 <?php echo esc_html($distancia); ?></span>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <a href="<?php echo esc_url($url); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                <?php echo esc_html__('Ver mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
            </a>
        </div>
    </div>
</article>

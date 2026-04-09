<?php
/**
 * Componente: Card de Grupo de Consumo
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$grupo = $item ?? $card_item ?? [];
if (empty($grupo)) return;

$id = $grupo['id'] ?? 0;
$nombre = $grupo['nombre'] ?? $grupo['title'] ?? '';
$url = $grupo['url'] ?? '#';
$descripcion = $grupo['descripcion'] ?? '';
$imagen = $grupo['imagen'] ?? '';
$num_miembros = $grupo['num_miembros'] ?? 0;
$zona = $grupo['zona'] ?? '';
$categorias = $grupo['categorias'] ?? [];
$abierto = $grupo['abierto_inscripciones'] ?? true;
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-zona="<?php echo esc_attr(sanitize_title($zona)); ?>">
    <div class="relative h-40 bg-gradient-to-br from-lime-400 to-green-500">
        <?php if ($imagen): ?>
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover">
        <?php else: ?>
        <div class="absolute inset-0 flex items-center justify-center text-white text-6xl opacity-30">🥕</div>
        <?php endif; ?>
        <?php if ($abierto): ?>
        <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
            <?php echo esc_html__('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-lime-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($nombre); ?>
            </a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($categorias)): ?>
        <div class="flex flex-wrap gap-2 mb-4">
            <?php foreach (array_slice($categorias, 0, 3) as $cat): ?>
            <span class="bg-lime-100 text-lime-700 text-xs px-2 py-1 rounded-full">
                <?php echo esc_html($cat); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
            <span class="flex items-center gap-1">
                👥 <?php echo esc_html($num_miembros); ?> <?php echo esc_html__('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <?php if ($zona): ?>
            <span class="flex items-center gap-1">
                📍 <?php echo esc_html($zona); ?>
            </span>
            <?php endif; ?>
        </div>

        <a href="<?php echo esc_url($url); ?>"
           class="block mt-4 text-center bg-lime-500 hover:bg-lime-600 text-white py-2 px-4 rounded-xl font-medium transition-colors">
            <?php echo esc_html__('Ver grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</article>

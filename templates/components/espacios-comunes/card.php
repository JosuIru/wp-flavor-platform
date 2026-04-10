<?php
/**
 * Componente: Card de Espacio Comun
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$espacio = $item ?? $card_item ?? [];
if (empty($espacio)) return;

$id = $espacio['id'] ?? 0;
$nombre = $espacio['nombre'] ?? $espacio['title'] ?? '';
$url = $espacio['url'] ?? '#';
$descripcion = $espacio['descripcion'] ?? '';
$imagen = $espacio['imagen'] ?? 'https://picsum.photos/seed/esp' . ($id ?: rand(1, 100)) . '/600/400';
$capacidad = $espacio['capacidad'] ?? '?';
$precio = $espacio['precio'] ?? '0€';
$disponible = !empty($espacio['disponible']);
$equipamiento = $espacio['equipamiento'] ?? [];
$tipo = $espacio['tipo'] ?? '';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-tipo="<?php echo esc_attr(sanitize_title($tipo)); ?>">
    <div class="relative aspect-[16/10] overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

        <?php if ($disponible): ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                <?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php else: ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white">
                <?php echo esc_html__('Ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php endif; ?>

        <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
            <span>👥 <?php echo esc_html($capacidad); ?> personas</span>
        </div>
    </div>

    <div class="p-5">
        <div class="flex items-start justify-between gap-2 mb-2">
            <h2 class="text-lg font-bold text-gray-900 group-hover:text-rose-600 transition-colors">
                <a href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($nombre); ?>
                </a>
            </h2>
            <span class="text-lg font-bold text-rose-600 whitespace-nowrap">
                <?php echo esc_html($precio); ?>/h
            </span>
        </div>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($equipamiento)): ?>
        <div class="flex flex-wrap gap-1 mb-4">
            <?php foreach (array_slice($equipamiento, 0, 3) as $equipo): ?>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-700">
                    <?php echo esc_html($equipo); ?>
                </span>
            <?php endforeach; ?>
            <?php if (count($equipamiento) > 3): ?>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                    +<?php echo count($equipamiento) - 3; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <a href="<?php echo esc_url($url); ?>"
           class="block w-full py-2.5 rounded-xl text-center font-semibold text-white bg-gradient-to-r from-rose-500 to-pink-600 transition-all hover:scale-105">
            <?php echo esc_html__('Ver Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</article>

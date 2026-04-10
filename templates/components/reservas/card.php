<?php
/**
 * Componente: Card de Recurso Reservable
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$recurso = $item ?? $card_item ?? [];
if (empty($recurso)) return;

// Soportar tanto arrays como objetos
$id = is_object($recurso) ? ($recurso->id ?? 0) : ($recurso['id'] ?? 0);
$nombre = is_object($recurso) ? ($recurso->nombre ?? '') : ($recurso['nombre'] ?? $recurso['title'] ?? '');
$descripcion = is_object($recurso) ? ($recurso->descripcion ?? '') : ($recurso['descripcion'] ?? '');
$tipo = is_object($recurso) ? ($recurso->tipo ?? '') : ($recurso['tipo'] ?? __('General', FLAVOR_PLATFORM_TEXT_DOMAIN));
$imagen = is_object($recurso) ? ($recurso->imagen ?? '') : ($recurso['imagen'] ?? '');
$ubicacion = is_object($recurso) ? ($recurso->ubicacion ?? '') : ($recurso['ubicacion'] ?? '');
$capacidad = is_object($recurso) ? ($recurso->capacidad ?? 0) : ($recurso['capacidad'] ?? 0);
$url = is_array($recurso) ? ($recurso['url'] ?? home_url('/reservas/' . $id . '/')) : home_url('/reservas/' . $id . '/');
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-tipo="<?php echo esc_attr(sanitize_title($tipo)); ?>">

    <?php if ($imagen): ?>
    <div class="aspect-video relative overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
             loading="lazy">
        <span class="absolute top-3 left-3 bg-blue-500 text-white text-xs font-medium px-3 py-1 rounded-full">
            <?php echo esc_html(ucfirst($tipo)); ?>
        </span>
    </div>
    <?php else: ?>
    <div class="aspect-video bg-gray-100 flex items-center justify-center relative">
        <span class="text-4xl">📅</span>
        <span class="absolute top-3 left-3 bg-blue-500 text-white text-xs font-medium px-3 py-1 rounded-full">
            <?php echo esc_html(ucfirst($tipo)); ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($nombre); ?>
            </a>
        </h3>

        <?php if ($ubicacion): ?>
        <p class="text-sm text-gray-500 mb-2 flex items-center gap-1">
            📍 <?php echo esc_html($ubicacion); ?>
        </p>
        <?php endif; ?>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            <?php echo esc_html(wp_trim_words($descripcion, 20)); ?>
        </p>
        <?php endif; ?>

        <?php if ($capacidad): ?>
        <div class="text-sm text-gray-500 mb-4">
            👥 <?php printf(esc_html__('%d personas', FLAVOR_PLATFORM_TEXT_DOMAIN), $capacidad); ?>
        </div>
        <?php endif; ?>

        <div class="flex gap-2">
            <a href="<?php echo esc_url($url); ?>"
               class="flex-1 py-2 rounded-lg text-center text-blue-600 font-medium text-sm bg-blue-50 hover:bg-blue-100 transition-colors">
                <?php echo esc_html__('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/reservas/nueva/?recurso_id=' . $id)); ?>"
               class="flex-1 py-2 rounded-lg text-center text-white font-medium text-sm bg-blue-500 hover:bg-blue-600 transition-colors">
                <?php echo esc_html__('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</article>

<?php
/**
 * Componente: Card de Comunidad
 *
 * Card específica para mostrar comunidades con tipo,
 * miembros, ubicación y estado.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array $item  Datos de la comunidad (o usar $card_item si viene de items-grid)
 * @param int   $index Índice del item en el array
 */

if (!defined('ABSPATH')) {
    exit;
}

// Soportar ambas variables (directa o desde items-grid)
$comunidad = $item ?? $card_item ?? [];
$index = $index ?? $card_index ?? 0;

// No renderizar si no hay datos
if (empty($comunidad)) {
    return;
}

// Extraer campos
$id = $comunidad['id'] ?? 0;
$nombre = $comunidad['nombre'] ?? $comunidad['titulo'] ?? $comunidad['title'] ?? '';
$descripcion = $comunidad['descripcion'] ?? $comunidad['excerpt'] ?? '';
$imagen = $comunidad['imagen'] ?? $comunidad['image'] ?? '';
$url = $comunidad['url'] ?? $comunidad['permalink'] ?? '#';
$tipo = $comunidad['tipo'] ?? __('Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN);
$miembros = $comunidad['miembros'] ?? 0;
$ubicacion = $comunidad['ubicacion'] ?? __('Local', FLAVOR_PLATFORM_TEXT_DOMAIN);
$verificada = !empty($comunidad['verificada']);
$activa = !empty($comunidad['activa']);
$emoji = $comunidad['emoji'] ?? '🏘️';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-tipo="<?php echo esc_attr(sanitize_title($tipo)); ?>"
         data-id="<?php echo esc_attr($id); ?>">

    <!-- Imagen de cabecera -->
    <?php if ($imagen): ?>
    <div class="h-40 overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($nombre); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
             loading="lazy">
    </div>
    <?php else: ?>
    <div class="h-40 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
        <span class="text-6xl"><?php echo esc_html($emoji); ?></span>
    </div>
    <?php endif; ?>

    <!-- Contenido -->
    <div class="p-6">
        <!-- Tipo y verificación -->
        <div class="flex items-center justify-between mb-3">
            <span class="bg-rose-100 text-rose-700 text-xs font-medium px-3 py-1 rounded-full">
                <?php echo esc_html($tipo); ?>
            </span>
            <?php if ($verificada): ?>
            <span class="text-green-500" title="<?php echo esc_attr__('Comunidad verificada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">✓</span>
            <?php endif; ?>
        </div>

        <!-- Nombre -->
        <h3 class="text-lg font-bold text-gray-800 mb-2">
            <a href="<?php echo esc_url($url); ?>" class="hover:text-rose-600 transition-colors">
                <?php echo esc_html($nombre); ?>
            </a>
        </h3>

        <!-- Descripción -->
        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <!-- Meta info -->
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-4 text-gray-500">
                <span>👥 <?php echo esc_html($miembros); ?></span>
                <span>📍 <?php echo esc_html($ubicacion); ?></span>
            </div>
            <?php if ($activa): ?>
            <span class="w-2 h-2 bg-green-500 rounded-full" title="<?php echo esc_attr__('Comunidad activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
            <?php endif; ?>
        </div>
    </div>
</article>

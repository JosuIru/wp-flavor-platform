<?php
/**
 * Componente: Card de Producto Marketplace
 *
 * Card específica para mostrar productos del marketplace
 * con precio, vendedor y condición.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array $item  Datos del producto (o usar $card_item si viene de items-grid)
 * @param int   $index Índice del item en el array
 */

if (!defined('ABSPATH')) {
    exit;
}

// Soportar ambas variables (directa o desde items-grid)
$producto = $item ?? $card_item ?? [];
$index = $index ?? $card_index ?? 0;

// No renderizar si no hay datos
if (empty($producto)) {
    return;
}

// Extraer campos
$id = $producto['id'] ?? 0;
$titulo = $producto['titulo'] ?? $producto['title'] ?? '';
$descripcion = $producto['descripcion'] ?? $producto['excerpt'] ?? '';
$precio = $producto['precio'] ?? $producto['price'] ?? '0';
$imagen = $producto['imagen'] ?? $producto['image'] ?? '';
$url = $producto['url'] ?? $producto['permalink'] ?? '#';
$condicion = $producto['condicion'] ?? $producto['condition'] ?? __('Usado', FLAVOR_PLATFORM_TEXT_DOMAIN);
$ubicacion = $producto['ubicacion'] ?? $producto['location'] ?? __('Cerca', FLAVOR_PLATFORM_TEXT_DOMAIN);
$vendedor_nombre = $producto['vendedor_nombre'] ?? $producto['author'] ?? __('Vendedor', FLAVOR_PLATFORM_TEXT_DOMAIN);
$vendedor_id = $producto['vendedor_id'] ?? 0;
$categoria = $producto['categoria'] ?? $producto['category'] ?? '';

// Primera letra del vendedor para avatar
$vendedor_inicial = mb_substr($vendedor_nombre, 0, 1);
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>"
         data-id="<?php echo esc_attr($id); ?>">

    <!-- Imagen con precio -->
    <div class="aspect-video bg-gray-100 relative overflow-hidden">
        <?php if ($imagen): ?>
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
             loading="lazy">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-gray-400">
            <span class="text-5xl">📷</span>
        </div>
        <?php endif; ?>

        <!-- Badge de precio -->
        <span class="absolute top-3 right-3 bg-green-500 text-white font-bold px-3 py-1 rounded-full text-sm shadow">
            <?php
            if (is_numeric($precio)) {
                echo esc_html(number_format((float)$precio, 2, ',', '.') . ' €');
            } else {
                echo esc_html($precio);
            }
            ?>
        </span>
    </div>

    <!-- Contenido -->
    <div class="p-5">
        <!-- Condición y ubicación -->
        <div class="flex items-center justify-between mb-2">
            <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                <?php echo esc_html($condicion); ?>
            </span>
            <span class="text-xs text-gray-500 flex items-center gap-1">
                📍 <?php echo esc_html($ubicacion); ?>
            </span>
        </div>

        <!-- Título -->
        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-green-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <!-- Descripción -->
        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <!-- Footer con vendedor -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                    <?php echo esc_html($vendedor_inicial); ?>
                </div>
                <span class="text-sm text-gray-600"><?php echo esc_html($vendedor_nombre); ?></span>
            </div>
            <a href="<?php echo esc_url($url); ?>"
               class="text-green-600 hover:text-green-700 font-medium text-sm">
                <?php echo esc_html__('Ver más →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</article>

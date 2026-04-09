<?php
/**
 * Componente: Item Card (Genérica)
 *
 * Card genérica que se adapta según los datos proporcionados.
 * Para casos específicos, crear cards personalizadas por módulo.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array $item  Datos del item (o usar $card_item si viene de items-grid)
 * @param int   $index Índice del item en el array
 *
 * Campos soportados del item:
 * - id:          ID del item
 * - titulo:      Título principal
 * - descripcion: Descripción corta
 * - imagen:      URL de la imagen
 * - url:         URL de detalle
 * - estado:      Estado con badge (pendiente, activo, etc.)
 * - fecha:       Fecha formateada
 * - autor:       Nombre del autor
 * - ubicacion:   Ubicación
 * - categoria:   Categoría
 * - precio:      Precio (si aplica)
 * - badge:       Badge extra
 * - icon:        Icono/emoji por defecto si no hay imagen
 * - color:       Color del badge/estado
 * - meta:        Array de meta items [{icon, text}]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Soportar ambas variables (directa o desde items-grid)
$item = $item ?? $card_item ?? [];
$index = $index ?? $card_index ?? 0;

// No renderizar si no hay item
if (empty($item)) {
    return;
}

// Extraer campos con valores por defecto
$item_id = $item['id'] ?? 0;
$titulo = $item['titulo'] ?? $item['title'] ?? '';
$descripcion = $item['descripcion'] ?? $item['description'] ?? $item['excerpt'] ?? '';
$imagen = $item['imagen'] ?? $item['image'] ?? '';
$url = $item['url'] ?? $item['permalink'] ?? '#';
$estado = $item['estado'] ?? $item['status'] ?? '';
$fecha = $item['fecha'] ?? $item['date'] ?? '';
$autor = $item['autor'] ?? $item['author'] ?? '';
$ubicacion = $item['ubicacion'] ?? $item['location'] ?? '';
$categoria = $item['categoria'] ?? $item['category'] ?? '';
$precio = $item['precio'] ?? $item['price'] ?? '';
$badge = $item['badge'] ?? '';
$icon = $item['icon'] ?? '📄';
$color = $item['color'] ?? 'blue';
$meta = $item['meta'] ?? [];

$color_classes = flavor_get_color_classes($color);

// Configuración de estados comunes
$estado_config = [
    'pendiente'  => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🔴'],
    'en_proceso' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => '🟡'],
    'activo'     => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢'],
    'resuelto'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢'],
    'resuelta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '🟢'],
    'cerrado'    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => '⚪'],
    'borrador'   => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'icon' => '📝'],
];
$estado_styles = $estado_config[$estado] ?? ['bg' => $color_classes['bg'], 'text' => $color_classes['text'], 'icon' => ''];
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden border border-gray-100 group flex flex-col">
    <!-- Imagen o placeholder -->
    <?php if ($imagen): ?>
    <div class="aspect-video bg-gray-100 relative overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
             loading="lazy">

        <?php if ($precio): ?>
        <span class="absolute top-3 right-3 bg-green-500 text-white font-bold px-3 py-1 rounded-full text-sm shadow">
            <?php echo esc_html($precio); ?>
        </span>
        <?php endif; ?>
    </div>
    <?php elseif ($icon): ?>
    <div class="aspect-video bg-gray-50 flex items-center justify-center text-5xl">
        <?php echo esc_html($icon); ?>
    </div>
    <?php endif; ?>

    <!-- Contenido -->
    <div class="p-5 flex-1 flex flex-col">
        <!-- Estado y categoría -->
        <div class="flex items-center justify-between gap-2 mb-2">
            <?php if ($estado): ?>
            <span class="inline-flex items-center gap-1 <?php echo esc_attr($estado_styles['bg']); ?> <?php echo esc_attr($estado_styles['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                <?php if ($estado_styles['icon']): ?><?php echo esc_html($estado_styles['icon']); ?><?php endif; ?>
                <?php echo esc_html(ucfirst(str_replace('_', ' ', $estado))); ?>
            </span>
            <?php endif; ?>

            <?php if ($categoria): ?>
            <span class="text-xs text-gray-500">
                🏷️ <?php echo esc_html($categoria); ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Título -->
        <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-<?php echo esc_attr($color); ?>-600 transition-colors">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <!-- Descripción -->
        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2 flex-1">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <!-- Meta info -->
        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-auto">
            <?php if ($ubicacion): ?>
            <span class="flex items-center gap-1">📍 <?php echo esc_html($ubicacion); ?></span>
            <?php endif; ?>

            <?php if ($fecha): ?>
            <span class="flex items-center gap-1">📅 <?php echo esc_html($fecha); ?></span>
            <?php endif; ?>

            <?php if ($autor): ?>
            <span class="flex items-center gap-1">👤 <?php echo esc_html($autor); ?></span>
            <?php endif; ?>

            <?php foreach ($meta as $meta_item): ?>
            <span class="flex items-center gap-1">
                <?php echo esc_html($meta_item['icon'] ?? ''); ?>
                <?php echo esc_html($meta_item['text'] ?? ''); ?>
            </span>
            <?php endforeach; ?>
        </div>

        <?php if ($badge): ?>
        <div class="mt-3 pt-3 border-t border-gray-100">
            <span class="<?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?> text-xs font-medium px-2 py-1 rounded">
                <?php echo esc_html($badge); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Enlace flotante para accesibilidad -->
    <a href="<?php echo esc_url($url); ?>"
       class="absolute inset-0 z-10"
       aria-label="<?php echo esc_attr(sprintf(__('Ver %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo)); ?>">
    </a>
</article>

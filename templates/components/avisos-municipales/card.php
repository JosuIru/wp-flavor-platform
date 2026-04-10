<?php
/**
 * Componente: Card de Aviso Municipal
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$aviso = $item ?? $card_item ?? [];
if (empty($aviso)) return;

$id = $aviso['id'] ?? 0;
$titulo = $aviso['titulo'] ?? $aviso['title'] ?? '';
$url = $aviso['url'] ?? '#';
$resumen = $aviso['resumen'] ?? $aviso['excerpt'] ?? '';
$fecha = $aviso['fecha'] ?? '';
$categoria = $aviso['categoria'] ?? 'servicios';
$urgencia = $aviso['urgencia'] ?? 'informativo';
$zona_afectada = $aviso['zona_afectada'] ?? '';

$colores_urgencia = [
    'informativo' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'borde' => 'border-l-sky-500'],
    'importante'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'borde' => 'border-l-amber-500'],
    'urgente'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'borde' => 'border-l-red-500'],
];
$color = $colores_urgencia[$urgencia] ?? $colores_urgencia['informativo'];

$iconos_categoria = [
    'obras'           => '🏗️',
    'servicios'       => '⚙️',
    'trafico'         => '🚗',
    'medio-ambiente'  => '🌿',
    'cultural'        => '🎭',
];
$icono_categoria = $iconos_categoria[strtolower(str_replace(' ', '-', $categoria))] ?? '📋';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col border-l-4 <?php echo esc_attr($color['borde']); ?>"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-5 flex-1">
        <div class="flex items-center justify-between mb-3">
            <span class="<?php echo esc_attr($color['bg']); ?> <?php echo esc_attr($color['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                <?php echo esc_html(ucfirst($urgencia)); ?>
            </span>
            <span class="text-2xl"><?php echo esc_html($icono_categoria); ?></span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            <a href="<?php echo esc_url($url); ?>" class="hover:text-sky-600 transition-colors">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>
        <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($resumen); ?></p>

        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
            <?php if ($fecha): ?>
            <span class="flex items-center gap-1">
                📅 <?php echo esc_html($fecha); ?>
            </span>
            <?php endif; ?>
            <?php if ($zona_afectada): ?>
            <span class="flex items-center gap-1">
                📍 <?php echo esc_html($zona_afectada); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <span class="text-xs text-gray-500"><?php echo esc_html($categoria); ?></span>
        <a href="<?php echo esc_url($url); ?>" class="text-sky-600 text-sm font-medium hover:text-sky-700 transition-colors">
            <?php echo esc_html__('Leer mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
        </a>
    </div>
</article>

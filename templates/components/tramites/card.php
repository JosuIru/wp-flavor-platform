<?php
/**
 * Componente: Card de Tramite Online
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$tramite = $item ?? $card_item ?? [];
if (empty($tramite)) return;

$id = $tramite['id'] ?? 0;
$titulo = $tramite['titulo'] ?? $tramite['title'] ?? '';
$url = $tramite['url'] ?? '#';
$descripcion = $tramite['descripcion'] ?? '';
$modalidad = $tramite['modalidad'] ?? 'online';
$tiempo_estimado = $tramite['tiempo_estimado'] ?? '';
$requisitos_count = $tramite['requisitos_count'] ?? 0;
$categoria = $tramite['categoria'] ?? '';

$colores_modalidad = [
    'online'     => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'presencial' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'ambos'      => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
];
$color = $colores_modalidad[$modalidad] ?? $colores_modalidad['online'];
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-5 flex-1">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-2xl">
                📋
            </div>
            <span class="<?php echo esc_attr($color['bg']); ?> <?php echo esc_attr($color['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                <?php echo esc_html(ucfirst($modalidad)); ?>
            </span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            <a href="<?php echo esc_url($url); ?>" class="hover:text-orange-600 transition-colors">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
            <?php if ($tiempo_estimado): ?>
            <span class="flex items-center gap-1">
                🕐 <?php echo esc_html($tiempo_estimado); ?>
            </span>
            <?php endif; ?>
            <?php if ($requisitos_count > 0): ?>
            <span class="flex items-center gap-1">
                📝 <?php echo esc_html($requisitos_count); ?> <?php echo esc_html__('requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
        <a href="<?php echo esc_url($url); ?>"
           class="w-full inline-flex items-center justify-center gap-2 bg-orange-500 text-white py-2 px-4 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm">
            <?php echo esc_html__('Iniciar tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
        </a>
    </div>
</article>

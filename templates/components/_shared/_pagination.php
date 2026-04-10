<?php
/**
 * Componente Compartido: Paginación
 *
 * @package FlavorPlatform
 * @var int $pagina_actual
 * @var int $total_paginas
 * @var string $base_url
 * @var string $color_primario
 */
if (!defined('ABSPATH')) exit;

$pagina_actual = $pagina_actual ?? 1;
$total_paginas = $total_paginas ?? 1;
$base_url = $base_url ?? '';
$color_primario = $color_primario ?? 'violet';

if ($total_paginas <= 1) return;

$rango_visible = 2;
$inicio_rango = max(1, $pagina_actual - $rango_visible);
$fin_rango = min($total_paginas, $pagina_actual + $rango_visible);
?>

<nav class="flex items-center justify-center gap-2 mt-8" aria-label="<?php echo esc_attr__('Paginación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
    <?php if ($pagina_actual > 1): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1, $base_url)); ?>"
           class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
           aria-label="<?php echo esc_attr__('Página anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('← Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    <?php else: ?>
        <span class="px-4 py-2 rounded-lg bg-gray-50 text-gray-300 cursor-not-allowed"><?php echo esc_html__('← Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    <?php endif; ?>

    <?php if ($inicio_rango > 1): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', 1, $base_url)); ?>"
           class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
            1
        </a>
        <?php if ($inicio_rango > 2): ?>
            <span class="px-2 text-gray-400">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($pagina_iterada = $inicio_rango; $pagina_iterada <= $fin_rango; $pagina_iterada++): ?>
        <?php if ($pagina_iterada === $pagina_actual): ?>
            <span class="w-10 h-10 flex items-center justify-center rounded-lg bg-<?php echo esc_attr($color_primario); ?>-500 text-white font-semibold"
                  aria-current="page">
                <?php echo esc_html($pagina_iterada); ?>
            </span>
        <?php else: ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $pagina_iterada, $base_url)); ?>"
               class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                <?php echo esc_html($pagina_iterada); ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($fin_rango < $total_paginas): ?>
        <?php if ($fin_rango < $total_paginas - 1): ?>
            <span class="px-2 text-gray-400">...</span>
        <?php endif; ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $total_paginas, $base_url)); ?>"
           class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
            <?php echo esc_html($total_paginas); ?>
        </a>
    <?php endif; ?>

    <?php if ($pagina_actual < $total_paginas): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1, $base_url)); ?>"
           class="px-4 py-2 rounded-lg bg-<?php echo esc_attr($color_primario); ?>-500 text-white hover:bg-<?php echo esc_attr($color_primario); ?>-600 transition-colors"
           aria-label="<?php echo esc_attr__('Página siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php echo esc_html__('Siguiente →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    <?php else: ?>
        <span class="px-4 py-2 rounded-lg bg-gray-50 text-gray-300 cursor-not-allowed"><?php echo esc_html__('Siguiente →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    <?php endif; ?>
</nav>

<p class="text-center text-sm text-gray-500 mt-3">
    Página <?php echo esc_html($pagina_actual); ?> de <?php echo esc_html($total_paginas); ?>
</p>

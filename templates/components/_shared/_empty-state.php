<?php
/**
 * Componente Compartido: Estado Vacío
 *
 * @package FlavorPlatform
 * @var string $icono Emoji o SVG
 * @var string $titulo
 * @var string $descripcion
 * @var string $texto_boton
 * @var string $url_boton
 * @var string $accion_onclick
 * @var string $color_primario
 */
if (!defined('ABSPATH')) exit;

$icono = $icono ?? '📭';
$titulo = $titulo ?? 'No hay elementos';
$descripcion = $descripcion ?? 'No se encontraron resultados.';
$texto_boton = $texto_boton ?? '';
$url_boton = $url_boton ?? '#';
$accion_onclick = $accion_onclick ?? '';
$color_primario = $color_primario ?? 'violet';
?>

<div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
    <div class="text-6xl mb-4"><?php echo esc_html($icono); ?></div>

    <h3 class="text-xl font-semibold text-gray-700 mb-2">
        <?php echo esc_html($titulo); ?>
    </h3>

    <p class="text-gray-500 mb-6 max-w-md mx-auto">
        <?php echo esc_html($descripcion); ?>
    </p>

    <?php if (!empty($texto_boton)): ?>
        <?php if (!empty($accion_onclick)): ?>
            <button class="bg-<?php echo esc_attr($color_primario); ?>-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-<?php echo esc_attr($color_primario); ?>-600 transition-colors shadow-md"
                    onclick="<?php echo esc_attr($accion_onclick); ?>">
                <?php echo esc_html($texto_boton); ?>
            </button>
        <?php else: ?>
            <a href="<?php echo esc_url($url_boton); ?>"
               class="inline-block bg-<?php echo esc_attr($color_primario); ?>-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-<?php echo esc_attr($color_primario); ?>-600 transition-colors shadow-md">
                <?php echo esc_html($texto_boton); ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>

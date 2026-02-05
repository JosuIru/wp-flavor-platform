<?php
/**
 * Template dispatcher: Grid Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'cards_imagen';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$columnas = $columnas ?? 3;
$items = $items ?? [];
$limite = $limite ?? 6;
$mostrar_imagen = $mostrar_imagen ?? true;
$mostrar_descripcion = $mostrar_descripcion ?? true;

$variantes_permitidas = ['cards_imagen', 'cards_icono', 'lista_compacta', 'masonry'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'cards_imagen';
}

$ruta_parcial = __DIR__ . '/_partials/grid-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/grid-cards-imagen.php';
}

include $ruta_parcial;

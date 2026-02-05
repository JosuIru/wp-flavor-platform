<?php
/**
 * Template dispatcher: Galería Unificada
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'grid_masonry';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];
$columnas = $columnas ?? 3;

$variantes_permitidas = ['grid_masonry', 'carrusel', 'lightbox'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'grid_masonry';
}

$ruta_parcial = __DIR__ . '/_partials/galeria-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/galeria-grid-masonry.php';
}

include $ruta_parcial;

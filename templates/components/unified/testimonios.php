<?php
/**
 * Template dispatcher: Testimonios Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'carrusel';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];

$variantes_permitidas = ['carrusel', 'grid', 'quotes'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'carrusel';
}

$ruta_parcial = __DIR__ . '/_partials/testimonios-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/testimonios-carrusel.php';
}

include $ruta_parcial;

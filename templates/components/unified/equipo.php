<?php
/**
 * Template dispatcher: Equipo Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'grid_tarjetas';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];

$variantes_permitidas = ['grid_tarjetas', 'carrusel', 'lista'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'grid_tarjetas';
}

$ruta_parcial = __DIR__ . '/_partials/equipo-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/equipo-grid-tarjetas.php';
}

include $ruta_parcial;

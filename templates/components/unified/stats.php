<?php
/**
 * Template dispatcher: Stats Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'counters_horizontal';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$color_fondo = $color_fondo ?? '';
$items = $items ?? [];

$variantes_permitidas = ['counters_horizontal', 'counters_grid', 'con_iconos'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'counters_horizontal';
}

$ruta_parcial = __DIR__ . '/_partials/stats-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/stats-counters-horizontal.php';
}

include $ruta_parcial;

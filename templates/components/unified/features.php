<?php
/**
 * Template dispatcher: Features Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'grid_iconos';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$columnas = $columnas ?? 3;
$items = $items ?? [];

$variantes_permitidas = ['grid_iconos', 'lista_alternada', 'tabs', 'acordeon'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'grid_iconos';
}

$ruta_parcial = __DIR__ . '/_partials/features-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/features-grid-iconos.php';
}

include $ruta_parcial;

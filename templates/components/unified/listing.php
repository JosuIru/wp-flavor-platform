<?php
/**
 * Template dispatcher: Listing Unificado (con filtros)
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'grid_filtrable';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$columnas = $columnas ?? 3;
$items = $items ?? [];
$limite = $limite ?? 6;
$mostrar_filtros = $mostrar_filtros ?? true;
$filtros = $filtros ?? [];
$mostrar_buscador = $mostrar_buscador ?? false;

$variantes_permitidas = ['grid_filtrable', 'tabla', 'mapa_y_lista'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'grid_filtrable';
}

$ruta_parcial = __DIR__ . '/_partials/listing-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/listing-grid-filtrable.php';
}

include $ruta_parcial;

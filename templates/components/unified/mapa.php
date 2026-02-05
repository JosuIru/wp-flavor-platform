<?php
/**
 * Template dispatcher: Mapa Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'embed_simple';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$direccion = $direccion ?? '';
$latitud = $latitud ?? '';
$longitud = $longitud ?? '';
$zoom = $zoom ?? 14;
$marcadores = $marcadores ?? [];
$altura = $altura ?? '400px';

$variantes_permitidas = ['embed_simple', 'con_marcadores', 'con_sidebar'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'embed_simple';
}

$ruta_parcial = __DIR__ . '/_partials/mapa-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/mapa-embed-simple.php';
}

include $ruta_parcial;

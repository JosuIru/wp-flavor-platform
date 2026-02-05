<?php
/**
 * Template dispatcher: Proceso/Cómo funciona Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'pasos_horizontal';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];

$variantes_permitidas = ['pasos_horizontal', 'pasos_vertical', 'timeline'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'pasos_horizontal';
}

$ruta_parcial = __DIR__ . '/_partials/proceso-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/proceso-pasos-horizontal.php';
}

include $ruta_parcial;

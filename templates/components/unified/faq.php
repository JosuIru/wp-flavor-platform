<?php
/**
 * Template dispatcher: FAQ Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'acordeon';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];
$mostrar_buscador = $mostrar_buscador ?? false;

$variantes_permitidas = ['acordeon', 'dos_columnas', 'con_buscador'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'acordeon';
}

$ruta_parcial = __DIR__ . '/_partials/faq-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/faq-acordeon.php';
}

include $ruta_parcial;

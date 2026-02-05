<?php
/**
 * Template dispatcher: Footer Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'simple';
$texto_copyright = $texto_copyright ?? '';
$color_fondo = $color_fondo ?? '#1f2937';
$color_texto = $color_texto ?? '#ffffff';
$columnas = $columnas ?? [];
$redes_sociales = $redes_sociales ?? [];
$logo = $logo ?? '';

$variantes_permitidas = ['simple', 'multi_columna'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'simple';
}

$ruta_parcial = __DIR__ . '/_partials/footer-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/footer-simple.php';
}

include $ruta_parcial;

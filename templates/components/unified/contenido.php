<?php
/**
 * Template dispatcher: Contenido Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'texto_simple';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$contenido = $contenido ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$imagen = $imagen ?? '';
$posicion_imagen = $posicion_imagen ?? 'derecha';
$url_video = $url_video ?? '';

$imagen_url = '';
if (!empty($imagen) && is_numeric($imagen)) {
    $imagen_url = wp_get_attachment_image_url($imagen, 'large');
} elseif (!empty($imagen)) {
    $imagen_url = $imagen;
}

$variantes_permitidas = ['texto_simple', 'texto_con_imagen', 'dos_columnas', 'video'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'texto_simple';
}

$ruta_parcial = __DIR__ . '/_partials/contenido-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/contenido-texto-simple.php';
}

include $ruta_parcial;

<?php
/**
 * Template dispatcher: CTA Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'banner_horizontal';
$titulo = $titulo ?? '';
$descripcion = $descripcion ?? '';
$texto_boton = $texto_boton ?? '';
$url_boton = $url_boton ?? '#';
$color_primario = $color_primario ?? '#3b82f6';
$color_fondo = $color_fondo ?? '';
$imagen = $imagen ?? '';
$icono = $icono ?? '';
$texto_boton_secundario = $texto_boton_secundario ?? '';
$url_boton_secundario = $url_boton_secundario ?? '#';
$posicion = $posicion ?? 'bottom-right';

$imagen_url = '';
if (!empty($imagen) && is_numeric($imagen)) {
    $imagen_url = wp_get_attachment_image_url($imagen, 'large');
} elseif (!empty($imagen)) {
    $imagen_url = $imagen;
}

$variantes_permitidas = ['banner_horizontal', 'banner_centrado', 'card_con_imagen', 'flotante', 'minimalista'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'banner_horizontal';
}

$ruta_parcial = __DIR__ . '/_partials/cta-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/cta-banner-horizontal.php';
}

include $ruta_parcial;

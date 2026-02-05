<?php
/**
 * Template dispatcher: Hero Unificado
 * Selecciona la variante correcta y la renderiza
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'centrado';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$imagen_fondo = $imagen_fondo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$texto_boton = $texto_boton ?? '';
$url_boton = $url_boton ?? '#';
$mostrar_buscador = $mostrar_buscador ?? false;
$placeholder_buscador = $placeholder_buscador ?? __('Buscar...', 'flavor-chat-ia');
$mostrar_estadisticas = $mostrar_estadisticas ?? false;
$estadisticas = $estadisticas ?? [];
$imagen_lateral = $imagen_lateral ?? '';
$texto_boton_secundario = $texto_boton_secundario ?? '';
$url_boton_secundario = $url_boton_secundario ?? '#';
$url_video = $url_video ?? '';
$tarjetas = $tarjetas ?? [];
$subtexto_inferior = $subtexto_inferior ?? '';
$overlay_oscuro = $overlay_oscuro ?? true;

// Resolver imagen de fondo
$imagen_fondo_url = '';
if (!empty($imagen_fondo)) {
    if (is_numeric($imagen_fondo)) {
        $imagen_fondo_url = wp_get_attachment_image_url($imagen_fondo, 'full');
    } else {
        $imagen_fondo_url = $imagen_fondo;
    }
}

// Resolver imagen lateral
$imagen_lateral_url = '';
if (!empty($imagen_lateral)) {
    if (is_numeric($imagen_lateral)) {
        $imagen_lateral_url = wp_get_attachment_image_url($imagen_lateral, 'large');
    } else {
        $imagen_lateral_url = $imagen_lateral;
    }
}

$variantes_permitidas = [
    'centrado', 'split_izquierda', 'split_derecha', 'con_buscador',
    'con_estadisticas', 'minimalista', 'con_video', 'con_tarjetas'
];

if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'centrado';
}

$ruta_parcial = __DIR__ . '/_partials/hero-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/hero-centrado.php';
}

include $ruta_parcial;

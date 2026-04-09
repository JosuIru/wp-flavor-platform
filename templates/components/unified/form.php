<?php
/**
 * Template dispatcher: Formulario Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'simple';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$campos = $campos ?? [];
$texto_boton = $texto_boton ?? __('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN);
$accion = $accion ?? '';

$variantes_permitidas = ['simple', 'multi_paso'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'simple';
}

$ruta_parcial = __DIR__ . '/_partials/form-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/form-simple.php';
}

include $ruta_parcial;

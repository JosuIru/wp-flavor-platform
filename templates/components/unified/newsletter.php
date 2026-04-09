<?php
/**
 * Template dispatcher: Newsletter Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'inline';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$texto_boton = $texto_boton ?? __('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN);
$placeholder = $placeholder ?? __('Tu email', FLAVOR_PLATFORM_TEXT_DOMAIN);
$beneficios = $beneficios ?? [];

$variantes_permitidas = ['inline', 'card_centrada', 'con_beneficios'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'inline';
}

$ruta_parcial = __DIR__ . '/_partials/newsletter-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/newsletter-inline.php';
}

include $ruta_parcial;

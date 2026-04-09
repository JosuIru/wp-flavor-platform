<?php
/**
 * Template dispatcher: Pricing Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'columnas';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];
$mostrar_toggle = $mostrar_toggle ?? false;
$texto_mensual = $texto_mensual ?? __('Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN);
$texto_anual = $texto_anual ?? __('Anual', FLAVOR_PLATFORM_TEXT_DOMAIN);

$variantes_permitidas = ['columnas', 'toggle_plan', 'comparativa'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'columnas';
}

$ruta_parcial = __DIR__ . '/_partials/pricing-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/pricing-columnas.php';
}

include $ruta_parcial;

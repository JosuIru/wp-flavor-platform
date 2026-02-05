<?php
/**
 * Template dispatcher: Navegación Unificada
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'tabs_horizontal';
$titulo = $titulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$items = $items ?? [];

$variantes_permitidas = ['tabs_horizontal', 'pills', 'sidebar_filtros'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'tabs_horizontal';
}

$ruta_parcial = __DIR__ . '/_partials/navegacion-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/navegacion-tabs-horizontal.php';
}

include $ruta_parcial;

<?php
/**
 * Template dispatcher: Calendario Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'mensual';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$eventos = $eventos ?? [];

$variantes_permitidas = ['mensual', 'lista_eventos', 'agenda'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'mensual';
}

$ruta_parcial = __DIR__ . '/_partials/calendario-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/calendario-mensual.php';
}

include $ruta_parcial;

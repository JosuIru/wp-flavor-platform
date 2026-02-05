<?php
/**
 * Template dispatcher: Contacto Unificado
 */
if (!defined('ABSPATH')) exit;

$variante = $variante ?? 'formulario_simple';
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$email_destino = $email_destino ?? '';
$mostrar_telefono = $mostrar_telefono ?? false;
$telefono = $telefono ?? '';
$mostrar_direccion = $mostrar_direccion ?? false;
$direccion = $direccion ?? '';
$mostrar_mapa = $mostrar_mapa ?? false;

$variantes_permitidas = ['formulario_simple', 'split_con_mapa', 'con_info'];
if (!in_array($variante, $variantes_permitidas)) {
    $variante = 'formulario_simple';
}

$ruta_parcial = __DIR__ . '/_partials/contacto-' . str_replace('_', '-', sanitize_file_name($variante)) . '.php';
if (!file_exists($ruta_parcial)) {
    $ruta_parcial = __DIR__ . '/_partials/contacto-formulario-simple.php';
}

include $ruta_parcial;

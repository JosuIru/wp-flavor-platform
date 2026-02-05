<?php
/**
 * Vista $(basename $file .php) - Módulo Participación
 */
if (!defined('ABSPATH')) exit;

$titulos = [
    'dashboard' => 'Dashboard de Participación Ciudadana',
    'propuestas' => 'Gestión de Propuestas',
    'votaciones' => 'Procesos de Votación',
    'resultados' => 'Resultados y Analytics',
    'debates' => 'Moderación de Debates'
];

$iconos = [
    'dashboard' => 'dashicons-groups',
    'propuestas' => 'dashicons-lightbulb',
    'votaciones' => 'dashicons-thumbs-up',
    'resultados' => 'dashicons-chart-bar',
    'debates' => 'dashicons-format-chat'
];

$nombre_vista = basename(__FILE__, '.php');

echo '<div class="wrap">';
echo '<h1><span class="dashicons ' . $iconos[$nombre_vista] . '"></span> ' . $titulos[$nombre_vista] . '</h1>';
echo '<hr class="wp-header-end">';
echo '<div class="postbox" style="margin-top: 20px;"><div class="inside">';
echo '<p>Vista de ' . strtolower($titulos[$nombre_vista]) . '.</p>';
echo '</div></div>';
echo '</div>';

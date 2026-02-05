<?php
/**
 * Vista $(basename $file .php) - Módulo Presupuestos Participativos
 */
if (!defined('ABSPATH')) exit;

$titulos = [
    'dashboard' => 'Dashboard de Presupuestos Participativos',
    'proyectos' => 'Gestión de Proyectos',
    'votos' => 'Seguimiento de Votos',
    'presupuesto' => 'Asignación de Presupuesto',
    'resultados' => 'Resultados Finales'
];

$iconos = [
    'dashboard' => 'dashicons-money-alt',
    'proyectos' => 'dashicons-portfolio',
    'votos' => 'dashicons-yes',
    'presupuesto' => 'dashicons-calculator',
    'resultados' => 'dashicons-awards'
];

$nombre_vista = basename(__FILE__, '.php');

echo '<div class="wrap">';
echo '<h1><span class="dashicons ' . $iconos[$nombre_vista] . '"></span> ' . $titulos[$nombre_vista] . '</h1>';
echo '<hr class="wp-header-end">';
echo '<div class="postbox" style="margin-top: 20px;"><div class="inside">';
echo '<p>Vista de ' . strtolower($titulos[$nombre_vista]) . '.</p>';
echo '</div></div>';
echo '</div>';

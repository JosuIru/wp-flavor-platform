<?php
/**
 * Frontend: Archive de Incidencias
 *
 * Versión refactorizada usando componentes shared y Archive Renderer.
 *
 * @package FlavorChatIA
 * @since 5.0.0 Refactorizado con componentes reutilizables
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar el Archive Renderer
if (!class_exists('Flavor_Archive_Renderer')) {
    require_once FLAVOR_PLUGIN_PATH . 'includes/class-archive-renderer.php';
}

// Variables esperadas del controlador
$incidencias = $incidencias ?? [];
$total_incidencias = $total_incidencias ?? count($incidencias);
$estadisticas = $estadisticas ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 10;

// Construir stats desde las estadísticas del módulo
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        [
            'value' => $estadisticas['pendientes'] ?? 0,
            'label' => __('Pendientes', 'flavor-chat-ia'),
            'icon'  => '🔴',
            'color' => 'red',
        ],
        [
            'value' => $estadisticas['en_proceso'] ?? $estadisticas['en_curso'] ?? 0,
            'label' => __('En proceso', 'flavor-chat-ia'),
            'icon'  => '🟡',
            'color' => 'yellow',
        ],
        [
            'value' => $estadisticas['resueltas'] ?? 0,
            'label' => __('Resueltas', 'flavor-chat-ia'),
            'icon'  => '🟢',
            'color' => 'green',
        ],
        [
            'value' => $estadisticas['tiempo_medio'] ?? '—',
            'label' => __('Días promedio', 'flavor-chat-ia'),
            'icon'  => '📊',
            'color' => 'blue',
        ],
    ];
}

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'incidencias',
    'title'        => __('Incidencias del Barrio', 'flavor-chat-ia'),
    'subtitle'     => __('Reporta y consulta problemas en espacios públicos', 'flavor-chat-ia'),
    'icon'         => '⚠️',
    'color'        => 'red',
    'items'        => $incidencias,
    'total'        => $total_incidencias,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'columns'      => 1,
    'layout'       => 'list',
    'filters'      => [
        ['id' => 'todos', 'label' => __('Todas', 'flavor-chat-ia'), 'active' => true],
        ['id' => 'pendiente', 'label' => __('Pendientes', 'flavor-chat-ia'), 'icon' => '🔴'],
        ['id' => 'en_proceso', 'label' => __('En proceso', 'flavor-chat-ia'), 'icon' => '🟡'],
        ['id' => 'resuelto', 'label' => __('Resueltas', 'flavor-chat-ia'), 'icon' => '🟢'],
    ],
    'filter_data_attr' => 'estado',
    'cta_text'     => __('Reportar incidencia', 'flavor-chat-ia'),
    'cta_icon'     => '📝',
    'cta_action'   => 'flavorIncidencias.nuevaIncidencia()',
    'card_template' => 'incidencias/card',
    'empty_state'  => [
        'icon'       => '✅',
        'title'      => __('No hay incidencias', 'flavor-chat-ia'),
        'text'       => __('El barrio está en perfecto estado', 'flavor-chat-ia'),
        'cta_text'   => __('Reportar nueva incidencia', 'flavor-chat-ia'),
        'cta_icon'   => '📝',
        'cta_action' => 'flavorIncidencias.nuevaIncidencia()',
    ],
]);

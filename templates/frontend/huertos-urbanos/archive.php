<?php
/**
 * Frontend: Archive de Huertos Urbanos
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

// Cargar funciones helper
if (!function_exists('flavor_render_component')) {
    require_once FLAVOR_PLUGIN_PATH . 'templates/components/shared/_functions.php';
}

// Variables esperadas
$huertos = $huertos ?? [];
$total_huertos = $total_huertos ?? count($huertos);
$estadisticas = $estadisticas ?? [];
$current_page = $pagina_actual ?? 1;
$per_page = $por_pagina ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['huertos_activos'] ?? 0, 'label' => __('Huertos activos', 'flavor-chat-ia'), 'icon' => '🌱'],
        ['value' => $estadisticas['parcelas_libres'] ?? 0, 'label' => __('Parcelas libres', 'flavor-chat-ia'), 'icon' => '📐'],
        ['value' => $estadisticas['hortelanos'] ?? 0, 'label' => __('Hortelanos', 'flavor-chat-ia'), 'icon' => '👨‍🌾'],
        ['value' => $estadisticas['kg_producidos'] ?? '0', 'label' => __('Kg producidos', 'flavor-chat-ia'), 'icon' => '🥬'],
    ];
}

// Filtros
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'disponible', 'label' => __('Con parcelas', 'flavor-chat-ia'), 'icon' => '✅'],
    ['id' => 'completo', 'label' => __('Completos', 'flavor-chat-ia'), 'icon' => '🚫'],
    ['id' => 'ecologico', 'label' => __('Ecológicos', 'flavor-chat-ia'), 'icon' => '🌿'],
];

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🔍', 'title' => __('Busca', 'flavor-chat-ia'), 'text' => __('Encuentra un huerto con parcelas disponibles cerca de ti', 'flavor-chat-ia')],
    ['icon' => '📝', 'title' => __('Solicita', 'flavor-chat-ia'), 'text' => __('Reserva tu parcela y espera la confirmación', 'flavor-chat-ia')],
    ['icon' => '🌱', 'title' => __('Cultiva', 'flavor-chat-ia'), 'text' => __('Disfruta cultivando tus propios alimentos', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'huertos-urbanos',
    'title'        => __('Huertos Urbanos', 'flavor-chat-ia'),
    'subtitle'     => __('Cultiva tus propios alimentos en la comunidad', 'flavor-chat-ia'),
    'icon'         => '🌱',
    'color'        => 'green',
    'items'        => $huertos,
    'total'        => $total_huertos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 2,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'estado',
    'cta_text'     => __('Solicitar Parcela', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorHuertos.solicitarParcela()',
    'card_template' => 'huertos-urbanos/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'green',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🌱',
        'title'      => __('No hay huertos disponibles', 'flavor-chat-ia'),
        'text'       => __('Prueba a modificar los filtros de búsqueda', 'flavor-chat-ia'),
    ],
]);

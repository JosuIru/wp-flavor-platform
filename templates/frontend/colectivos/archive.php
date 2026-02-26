<?php
/**
 * Frontend: Archive de Colectivos y Asociaciones
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
$colectivos = $colectivos ?? [];
$total_colectivos = $total_colectivos ?? count($colectivos);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $pagina_actual ?? 1;
$per_page = $por_pagina ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['colectivos'] ?? 48, 'label' => __('Colectivos registrados', 'flavor-chat-ia'), 'icon' => '🏛️'],
        ['value' => $estadisticas['miembros'] ?? '1.5k', 'label' => __('Miembros', 'flavor-chat-ia'), 'icon' => '👥'],
        ['value' => $estadisticas['actividades'] ?? 210, 'label' => __('Actividades', 'flavor-chat-ia'), 'icon' => '📋'],
        ['value' => $estadisticas['eventos'] ?? 36, 'label' => __('Eventos', 'flavor-chat-ia'), 'icon' => '🎉'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'cultural', 'label' => __('Cultural', 'flavor-chat-ia'), 'icon' => '🎭'],
    ['id' => 'deportivo', 'label' => __('Deportivo', 'flavor-chat-ia'), 'icon' => '⚽'],
    ['id' => 'social', 'label' => __('Social', 'flavor-chat-ia'), 'icon' => '🤝'],
    ['id' => 'medioambiental', 'label' => __('Medioambiental', 'flavor-chat-ia'), 'icon' => '🌿'],
    ['id' => 'vecinal', 'label' => __('Vecinal', 'flavor-chat-ia'), 'icon' => '🏘️'],
];

// Añadir categorías dinámicas
foreach ($categorias as $cat) {
    $filters[] = [
        'id'    => $cat['slug'] ?? sanitize_title($cat['nombre'] ?? ''),
        'label' => $cat['nombre'] ?? '',
        'icon'  => $cat['icono'] ?? '',
    ];
}

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🔍', 'title' => __('Descubre', 'flavor-chat-ia'), 'text' => __('Explora colectivos por categoría e intereses', 'flavor-chat-ia')],
    ['icon' => '✋', 'title' => __('Únete', 'flavor-chat-ia'), 'text' => __('Solicita unirte a los grupos que te interesen', 'flavor-chat-ia')],
    ['icon' => '🤝', 'title' => __('Participa', 'flavor-chat-ia'), 'text' => __('Colabora en actividades y eventos del colectivo', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'colectivos',
    'title'        => __('Colectivos y Asociaciones', 'flavor-chat-ia'),
    'subtitle'     => __('Encuentra y únete a grupos de tu comunidad', 'flavor-chat-ia'),
    'icon'         => '🏛️',
    'color'        => 'rose',
    'items'        => $colectivos,
    'total'        => $total_colectivos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Registrar Colectivo', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorColectivos.registrarColectivo()',
    'card_template' => 'colectivos/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'rose',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🏛️',
        'title'      => __('No hay colectivos registrados', 'flavor-chat-ia'),
        'text'       => __('Registra tu colectivo y empieza a conectar', 'flavor-chat-ia'),
        'cta_text'   => __('Registrar Colectivo', 'flavor-chat-ia'),
        'cta_action' => 'flavorColectivos.registrarColectivo()',
    ],
]);

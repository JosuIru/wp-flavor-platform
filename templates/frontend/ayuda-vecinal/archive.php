<?php
/**
 * Frontend: Archive de Ayuda Vecinal
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
$solicitudes = $solicitudes ?? [];
$total_solicitudes = $total_solicitudes ?? count($solicitudes);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $pagina_actual ?? 1;
$per_page = $por_pagina ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['activas'] ?? 0, 'label' => __('Activas', 'flavor-chat-ia'), 'icon' => '🆘'],
        ['value' => $estadisticas['resueltas'] ?? 0, 'label' => __('Resueltas', 'flavor-chat-ia'), 'icon' => '✅'],
        ['value' => $estadisticas['voluntarios'] ?? 0, 'label' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => '🤝'],
        ['value' => $estadisticas['ayudas_mes'] ?? 0, 'label' => __('Ayudas este mes', 'flavor-chat-ia'), 'icon' => '📅'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'necesito', 'label' => __('Necesito ayuda', 'flavor-chat-ia'), 'icon' => '🆘'],
    ['id' => 'ofrezco', 'label' => __('Ofrezco ayuda', 'flavor-chat-ia'), 'icon' => '🤝'],
    ['id' => 'urgente', 'label' => __('Urgentes', 'flavor-chat-ia'), 'icon' => '🔴'],
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
    ['icon' => '📝', 'title' => __('Publica', 'flavor-chat-ia'), 'text' => __('Describe qué tipo de ayuda necesitas o puedes ofrecer', 'flavor-chat-ia')],
    ['icon' => '🔔', 'title' => __('Conecta', 'flavor-chat-ia'), 'text' => __('Recibe respuestas de vecinos dispuestos a ayudar', 'flavor-chat-ia')],
    ['icon' => '🤝', 'title' => __('Colabora', 'flavor-chat-ia'), 'text' => __('Coordínate y fortalece los lazos vecinales', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'ayuda-vecinal',
    'title'        => __('Ayuda Vecinal', 'flavor-chat-ia'),
    'subtitle'     => __('Conecta con vecinos que necesitan o ofrecen ayuda', 'flavor-chat-ia'),
    'icon'         => '🤝',
    'color'        => 'orange',
    'items'        => $solicitudes,
    'total'        => $total_solicitudes,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 1, // Layout de lista para solicitudes
    'layout'       => 'list',
    'filters'      => $filters,
    'filter_data_attr' => 'tipo',
    'cta_text'     => __('Crear solicitud', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorAyudaVecinal.crearSolicitud()',
    'card_template' => 'ayuda-vecinal/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'orange',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🤝',
        'title'      => __('No hay solicitudes activas', 'flavor-chat-ia'),
        'text'       => __('Sé el primero en pedir o ofrecer ayuda', 'flavor-chat-ia'),
        'cta_text'   => __('Crear solicitud', 'flavor-chat-ia'),
        'cta_action' => 'flavorAyudaVecinal.crearSolicitud()',
    ],
]);

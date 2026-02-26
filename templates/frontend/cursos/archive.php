<?php
/**
 * Frontend: Archive de Cursos
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
$cursos = $cursos ?? [];
$total_cursos = $total_cursos ?? count($cursos);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $pagina_actual ?? 1;
$per_page = $por_pagina ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['cursos_disponibles'] ?? 0, 'label' => __('Cursos disponibles', 'flavor-chat-ia'), 'icon' => '📚'],
        ['value' => $estadisticas['alumnos'] ?? 0, 'label' => __('Alumnos inscritos', 'flavor-chat-ia'), 'icon' => '👨‍🎓'],
        ['value' => $estadisticas['instructores'] ?? 0, 'label' => __('Instructores', 'flavor-chat-ia'), 'icon' => '👨‍🏫'],
        ['value' => $estadisticas['valoracion_media'] ?? '4.8', 'label' => __('Valoración media', 'flavor-chat-ia'), 'icon' => '⭐'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'proximos', 'label' => __('Próximos', 'flavor-chat-ia'), 'icon' => '📅'],
    ['id' => 'en_curso', 'label' => __('En curso', 'flavor-chat-ia'), 'icon' => '▶️'],
    ['id' => 'online', 'label' => __('Online', 'flavor-chat-ia'), 'icon' => '💻'],
    ['id' => 'gratuitos', 'label' => __('Gratuitos', 'flavor-chat-ia'), 'icon' => '🆓'],
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
    ['icon' => '🔍', 'title' => __('Explora', 'flavor-chat-ia'), 'text' => __('Encuentra cursos por categoría, nivel y horario', 'flavor-chat-ia')],
    ['icon' => '📝', 'title' => __('Inscríbete', 'flavor-chat-ia'), 'text' => __('Reserva tu plaza fácilmente online', 'flavor-chat-ia')],
    ['icon' => '🎓', 'title' => __('Aprende', 'flavor-chat-ia'), 'text' => __('Desarrolla nuevas habilidades con expertos', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'cursos',
    'title'        => __('Cursos y Talleres', 'flavor-chat-ia'),
    'subtitle'     => __('Aprende nuevas habilidades con tus vecinos', 'flavor-chat-ia'),
    'icon'         => '🎓',
    'color'        => 'purple',
    'items'        => $cursos,
    'total'        => $total_cursos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 2,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Crear Curso', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorCursos.crearCurso()',
    'card_template' => 'cursos/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'purple',
        ]);
    },
    'empty_state'  => [
        'icon'       => '📚',
        'title'      => __('No hay cursos disponibles', 'flavor-chat-ia'),
        'text'       => __('Prueba a modificar los filtros', 'flavor-chat-ia'),
        'cta_text'   => __('Crear Curso', 'flavor-chat-ia'),
        'cta_action' => 'flavorCursos.crearCurso()',
    ],
]);

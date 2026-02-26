<?php
/**
 * Frontend: Archive de Eventos
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
$eventos = $eventos ?? [];
$total_eventos = $total_eventos ?? count($eventos);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;
$vista_activa = $vista_activa ?? 'lista';

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['eventos_activos'] ?? 0, 'label' => __('Eventos activos', 'flavor-chat-ia'), 'icon' => '📅'],
        ['value' => $estadisticas['asistentes'] ?? 0, 'label' => __('Asistentes', 'flavor-chat-ia'), 'icon' => '👥'],
        ['value' => $estadisticas['organizadores'] ?? 0, 'label' => __('Organizadores', 'flavor-chat-ia'), 'icon' => '🎤'],
        ['value' => $estadisticas['este_mes'] ?? 0, 'label' => __('Este mes', 'flavor-chat-ia'), 'icon' => '🗓️'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'musica', 'label' => __('Música', 'flavor-chat-ia'), 'icon' => '🎵'],
    ['id' => 'deporte', 'label' => __('Deporte', 'flavor-chat-ia'), 'icon' => '⚽'],
    ['id' => 'cultura', 'label' => __('Cultura', 'flavor-chat-ia'), 'icon' => '🎭'],
    ['id' => 'infantil', 'label' => __('Infantil', 'flavor-chat-ia'), 'icon' => '👶'],
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
    ['icon' => '🔍', 'title' => __('Descubre', 'flavor-chat-ia'), 'text' => __('Encuentra eventos cerca de ti por categoría y fecha', 'flavor-chat-ia')],
    ['icon' => '✅', 'title' => __('Inscríbete', 'flavor-chat-ia'), 'text' => __('Reserva tu plaza fácilmente con un solo clic', 'flavor-chat-ia')],
    ['icon' => '🎊', 'title' => __('Participa', 'flavor-chat-ia'), 'text' => __('Disfruta del evento y comparte tu experiencia', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'eventos',
    'title'        => __('Eventos', 'flavor-chat-ia'),
    'subtitle'     => __('Descubre y participa en los eventos de tu comunidad', 'flavor-chat-ia'),
    'icon'         => '🎉',
    'color'        => 'pink',
    'items'        => $eventos,
    'total'        => $total_eventos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Crear Evento', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorEventos.crearEvento()',
    'card_template' => 'eventos/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'pink',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🎉',
        'title'      => __('No hay eventos programados', 'flavor-chat-ia'),
        'text'       => __('¡Crea el primer evento de la comunidad!', 'flavor-chat-ia'),
        'cta_text'   => __('Crear Evento', 'flavor-chat-ia'),
        'cta_action' => 'flavorEventos.crearEvento()',
    ],
]);

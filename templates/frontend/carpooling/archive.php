<?php
/**
 * Frontend: Archive de Carpooling
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
$viajes = $viajes ?? [];
$total_viajes = $total_viajes ?? count($viajes);
$estadisticas = $estadisticas ?? [];
$destinos_populares = $destinos_populares ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['viajes_disponibles'] ?? 0, 'label' => __('Viajes disponibles', 'flavor-chat-ia'), 'icon' => '🚗'],
        ['value' => $estadisticas['plazas_libres'] ?? 0, 'label' => __('Plazas libres', 'flavor-chat-ia'), 'icon' => '💺'],
        ['value' => $estadisticas['co2_ahorrado'] ?? '0 kg', 'label' => __('CO2 ahorrado', 'flavor-chat-ia'), 'icon' => '🌿'],
        ['value' => $estadisticas['km_compartidos'] ?? 0, 'label' => __('Km compartidos', 'flavor-chat-ia'), 'icon' => '📏'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
];

// Añadir destinos populares como filtros
foreach ($destinos_populares as $destino) {
    $filters[] = [
        'id'    => $destino['slug'] ?? sanitize_title($destino['nombre'] ?? ''),
        'label' => $destino['nombre'] ?? '',
        'icon'  => '📍',
    ];
}

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🔍', 'title' => __('Busca', 'flavor-chat-ia'), 'text' => __('Encuentra viajes disponibles con tu mismo destino y horario', 'flavor-chat-ia')],
    ['icon' => '📩', 'title' => __('Reserva', 'flavor-chat-ia'), 'text' => __('Solicita tu plaza y espera la confirmación del conductor', 'flavor-chat-ia')],
    ['icon' => '🚗', 'title' => __('Viaja', 'flavor-chat-ia'), 'text' => __('Comparte el viaje, ahorra dinero y reduce tu huella de carbono', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'carpooling',
    'title'        => __('Carpooling', 'flavor-chat-ia'),
    'subtitle'     => __('Comparte viajes, reduce costes y cuida el medio ambiente', 'flavor-chat-ia'),
    'icon'         => '🚗',
    'color'        => 'lime',
    'items'        => $viajes,
    'total'        => $total_viajes,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'destino',
    'cta_text'     => __('Ofrecer Viaje', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorCarpooling.ofrecerViaje()',
    'card_template' => 'carpooling/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'lime',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🚗',
        'title'      => __('No hay viajes disponibles', 'flavor-chat-ia'),
        'text'       => __('¡Ofrece el primer viaje compartido!', 'flavor-chat-ia'),
        'cta_text'   => __('Ofrecer Viaje', 'flavor-chat-ia'),
        'cta_action' => 'flavorCarpooling.ofrecerViaje()',
    ],
]);

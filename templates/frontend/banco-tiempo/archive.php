<?php
/**
 * Frontend: Archive de Banco de Tiempo
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
$servicios = $servicios ?? [];
$total_servicios = $total_servicios ?? count($servicios);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['miembros'] ?? 0, 'label' => __('Miembros activos', 'flavor-chat-ia'), 'icon' => '👥'],
        ['value' => $estadisticas['intercambios'] ?? 0, 'label' => __('Intercambios realizados', 'flavor-chat-ia'), 'icon' => '🔄'],
        ['value' => ($estadisticas['horas_intercambiadas'] ?? 0) . 'h', 'label' => __('Horas intercambiadas', 'flavor-chat-ia'), 'icon' => '⏱️'],
        ['value' => $estadisticas['valoracion_media'] ?? '4.8', 'label' => __('Valoración media', 'flavor-chat-ia'), 'icon' => '⭐'],
    ];
}

// Construir filtros desde categorías
$filters = [['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true]];
foreach ($categorias as $cat) {
    $filters[] = [
        'id'    => $cat['slug'] ?? sanitize_title($cat['nombre'] ?? ''),
        'label' => $cat['nombre'] ?? '',
        'icon'  => $cat['icono'] ?? '',
    ];
}

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🎁', 'title' => __('Ofrece', 'flavor-chat-ia'), 'text' => __('Comparte tus habilidades y talentos con la comunidad', 'flavor-chat-ia')],
    ['icon' => '🤝', 'title' => __('Intercambia', 'flavor-chat-ia'), 'text' => __('1 hora de tu tiempo = 1 hora de cualquier servicio', 'flavor-chat-ia')],
    ['icon' => '✨', 'title' => __('Recibe', 'flavor-chat-ia'), 'text' => __('Utiliza tus horas ganadas para recibir servicios', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'banco-tiempo',
    'title'        => __('Banco de Tiempo', 'flavor-chat-ia'),
    'subtitle'     => __('Intercambia servicios con tus vecinos: 1 hora = 1 hora', 'flavor-chat-ia'),
    'icon'         => '⏰',
    'color'        => 'purple',
    'items'        => $servicios,
    'total'        => $total_servicios,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Ofrecer servicio', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorBancoTiempo.ofrecerServicio()',
    'card_template' => 'banco-tiempo/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'purple',
        ]);
    },
    'empty_state'  => [
        'icon'       => '⏰',
        'title'      => __('No hay servicios disponibles', 'flavor-chat-ia'),
        'text'       => __('¡Sé el primero en ofrecer un servicio!', 'flavor-chat-ia'),
        'cta_text'   => __('Ofrecer servicio', 'flavor-chat-ia'),
        'cta_action' => 'flavorBancoTiempo.ofrecerServicio()',
    ],
]);

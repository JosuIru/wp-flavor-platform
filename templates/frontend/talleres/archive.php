<?php
/**
 * Frontend: Archive de Talleres
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
$talleres = $talleres ?? [];
$total_talleres = $total_talleres ?? count($talleres);
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['talleres_disponibles'] ?? 0, 'label' => __('Talleres disponibles', 'flavor-chat-ia'), 'icon' => '📚'],
        ['value' => $estadisticas['plazas'] ?? 0, 'label' => __('Plazas', 'flavor-chat-ia'), 'icon' => '💺'],
        ['value' => $estadisticas['instructores'] ?? 0, 'label' => __('Instructores', 'flavor-chat-ia'), 'icon' => '👨‍🏫'],
        ['value' => $estadisticas['valoracion_media'] ?? '4.8', 'label' => __('Valoración media', 'flavor-chat-ia'), 'icon' => '⭐'],
    ];
}

// Filtros base
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'arte', 'label' => __('Arte', 'flavor-chat-ia'), 'icon' => '🎨'],
    ['id' => 'cocina', 'label' => __('Cocina', 'flavor-chat-ia'), 'icon' => '🍳'],
    ['id' => 'tecnologia', 'label' => __('Tecnología', 'flavor-chat-ia'), 'icon' => '💻'],
    ['id' => 'manualidades', 'label' => __('Manualidades', 'flavor-chat-ia'), 'icon' => '✂️'],
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
    ['icon' => '🔍', 'title' => __('Explora', 'flavor-chat-ia'), 'text' => __('Busca talleres por categoría, nivel y horario', 'flavor-chat-ia')],
    ['icon' => '📝', 'title' => __('Reserva', 'flavor-chat-ia'), 'text' => __('Inscríbete fácilmente y asegura tu plaza', 'flavor-chat-ia')],
    ['icon' => '🎓', 'title' => __('Aprende', 'flavor-chat-ia'), 'text' => __('Disfruta aprendiendo y valora la experiencia', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'talleres',
    'title'        => __('Talleres', 'flavor-chat-ia'),
    'subtitle'     => __('Aprende nuevas habilidades con talleres de tu comunidad', 'flavor-chat-ia'),
    'icon'         => '🎨',
    'color'        => 'purple',
    'items'        => $talleres,
    'total'        => $total_talleres,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Crear Taller', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorTalleres.crearTaller()',
    'card_template' => 'talleres/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'purple',
        ]);
    },
    'empty_state'  => [
        'icon'       => '🎨',
        'title'      => __('No hay talleres disponibles', 'flavor-chat-ia'),
        'text'       => __('¿Tienes algo que enseñar? ¡Crea un taller!', 'flavor-chat-ia'),
        'cta_text'   => __('Crear Taller', 'flavor-chat-ia'),
        'cta_action' => 'flavorTalleres.crearTaller()',
    ],
]);

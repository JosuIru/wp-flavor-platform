<?php
/**
 * Frontend: Archive de Reservas
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

// Verificar que el módulo esté activo
$modulo_activo = class_exists('Flavor_Chat_Module_Loader')
    && Flavor_Chat_Module_Loader::get_instance()->is_module_active('reservas');

if (!$modulo_activo) {
    echo '<div class="flavor-notice flavor-notice-warning p-4 bg-yellow-50 border border-yellow-200 rounded-lg">';
    echo '<p>' . esc_html__('El módulo de reservas no está activo.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Variables - pueden venir de la query o ser pasadas directamente
$recursos = $recursos ?? [];
$total_recursos = $total_recursos ?? count($recursos);
$estadisticas = $estadisticas ?? [];
$tipos_disponibles = $tipos_disponibles ?? [];
$current_page = $pagina_actual ?? 1;
$per_page = $items_por_pagina ?? 12;

// Si no hay recursos pasados, intentar obtenerlos de la base de datos
if (empty($recursos)) {
    global $wpdb;
    $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_recursos)) {
        $filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $offset = ($current_page - 1) * $per_page;

        $where_condiciones = ["estado = 'activo'"];
        $params_query = [];

        if (!empty($filtro_tipo)) {
            $where_condiciones[] = "tipo = %s";
            $params_query[] = $filtro_tipo;
        }

        if (!empty($filtro_busqueda)) {
            $where_condiciones[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $like_pattern = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
            $params_query[] = $like_pattern;
            $params_query[] = $like_pattern;
        }

        $clausula_where = implode(' AND ', $where_condiciones);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_recursos WHERE $clausula_where";
        $total_recursos = !empty($params_query)
            ? $wpdb->get_var($wpdb->prepare($sql_count, ...$params_query))
            : $wpdb->get_var($sql_count);

        // Obtener recursos
        $sql_recursos = "SELECT * FROM $tabla_recursos WHERE $clausula_where ORDER BY nombre ASC LIMIT %d OFFSET %d";
        $params_query[] = $per_page;
        $params_query[] = $offset;
        $recursos = $wpdb->get_results($wpdb->prepare($sql_recursos, ...$params_query), ARRAY_A);

        // Obtener tipos únicos para filtros
        $tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_recursos WHERE estado = 'activo' ORDER BY tipo ASC");
    }
}

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['recursos_disponibles'] ?? 0, 'label' => __('Recursos disponibles', 'flavor-chat-ia'), 'icon' => '📅'],
        ['value' => $estadisticas['reservas_hoy'] ?? 0, 'label' => __('Reservas hoy', 'flavor-chat-ia'), 'icon' => '📆'],
        ['value' => $estadisticas['usuarios_activos'] ?? 0, 'label' => __('Usuarios activos', 'flavor-chat-ia'), 'icon' => '👥'],
        ['value' => $estadisticas['disponibilidad'] ?? '85%', 'label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => '✅'],
    ];
}

// Construir filtros desde tipos disponibles
$filters = [['id' => 'todos', 'label' => __('Todos los tipos', 'flavor-chat-ia'), 'active' => true]];
foreach ($tipos_disponibles as $tipo) {
    $filters[] = [
        'id'    => sanitize_title($tipo),
        'label' => ucfirst($tipo),
    ];
}

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🔍', 'title' => __('Busca', 'flavor-chat-ia'), 'text' => __('Explora los recursos disponibles para reservar', 'flavor-chat-ia')],
    ['icon' => '📅', 'title' => __('Reserva', 'flavor-chat-ia'), 'text' => __('Selecciona fecha, hora y confirma tu reserva', 'flavor-chat-ia')],
    ['icon' => '✅', 'title' => __('Disfruta', 'flavor-chat-ia'), 'text' => __('Acude a tu reserva y disfruta del recurso', 'flavor-chat-ia')],
];

// Enqueue styles específicos si existen
if (function_exists('wp_enqueue_style')) {
    wp_enqueue_style('flavor-reservas');
    wp_enqueue_script('flavor-reservas');
}

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'reservas',
    'title'        => __('Recursos Disponibles', 'flavor-chat-ia'),
    'subtitle'     => __('Explora nuestros recursos y realiza tu reserva', 'flavor-chat-ia'),
    'icon'         => '📅',
    'color'        => 'blue',
    'items'        => $recursos,
    'total'        => $total_recursos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'tipo',
    'cta_text'     => __('Mis Reservas', 'flavor-chat-ia'),
    'cta_icon'     => '📋',
    'cta_url'      => home_url('/mi-portal/reservas/'),
    'card_template' => 'reservas/card',
    'extra_content' => function() use ($como_funciona_steps) {
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'blue',
        ]);
    },
    'empty_state'  => [
        'icon'       => '📅',
        'title'      => __('No hay recursos disponibles', 'flavor-chat-ia'),
        'text'       => __('No se encontraron recursos con los filtros seleccionados.', 'flavor-chat-ia'),
    ],
]);

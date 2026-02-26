<?php
/**
 * Frontend: Archive de Marketplace Local
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

// Si no se pasan las variables, consultarlas directamente
if (!isset($productos) || empty($productos)) {
    $query_args = [
        'post_type'      => 'marketplace_item',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($query_args);
    $productos = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $autor_id = get_post_field('post_author', $post_id);
            $autor = get_userdata($autor_id);

            $productos[] = [
                'id'              => $post_id,
                'titulo'          => get_the_title(),
                'descripcion'     => wp_trim_words(get_the_excerpt(), 20),
                'precio'          => get_post_meta($post_id, '_precio', true) ?: get_post_meta($post_id, 'precio', true) ?: '0',
                'imagen'          => get_the_post_thumbnail_url($post_id, 'medium') ?: '',
                'url'             => get_permalink($post_id),
                'condicion'       => get_post_meta($post_id, '_condicion', true) ?: get_post_meta($post_id, 'condicion', true) ?: 'Usado',
                'ubicacion'       => get_post_meta($post_id, '_ubicacion', true) ?: get_post_meta($post_id, 'ubicacion', true) ?: 'Local',
                'vendedor_nombre' => $autor ? $autor->display_name : 'Vendedor',
                'vendedor_id'     => $autor_id,
            ];
        }
        wp_reset_postdata();
    }

    $total_productos = $query->found_posts;
}

// Si no hay estadísticas, calcularlas
if (!isset($estadisticas) || empty($estadisticas)) {
    $total_items = wp_count_posts('marketplace_item');
    $vendedores_query = new WP_Query([
        'post_type'      => 'marketplace_item',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    $vendedores_ids = [];
    if ($vendedores_query->have_posts()) {
        foreach ($vendedores_query->posts as $post_id) {
            $vendedores_ids[] = get_post_field('post_author', $post_id);
        }
    }

    $estadisticas = [
        'productos_activos' => isset($total_items->publish) ? $total_items->publish : 0,
        'vendedores'        => count(array_unique($vendedores_ids)),
        'transacciones'     => 0,
        'valoracion_media'  => '4.8',
    ];
}

// Si no hay categorías, obtenerlas de la taxonomía
if (!isset($categorias) || empty($categorias)) {
    $terms = get_terms([
        'taxonomy'   => 'marketplace_categoria',
        'hide_empty' => false,
    ]);

    $categorias = [];
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $icono = get_term_meta($term->term_id, 'icono', true);
            $categorias[] = [
                'id'     => $term->slug,
                'label'  => $term->name,
                'icon'   => $icono ?: '🏷️',
                'count'  => $term->count,
            ];
        }
    }
}

$total_productos = $total_productos ?? count($productos);
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;

// Construir stats
$stats = [
    [
        'value' => $estadisticas['productos_activos'] ?? 0,
        'label' => __('Productos activos', 'flavor-chat-ia'),
        'icon'  => '📦',
    ],
    [
        'value' => $estadisticas['vendedores'] ?? 0,
        'label' => __('Vendedores', 'flavor-chat-ia'),
        'icon'  => '👤',
    ],
    [
        'value' => $estadisticas['transacciones'] ?? 0,
        'label' => __('Transacciones', 'flavor-chat-ia'),
        'icon'  => '🤝',
    ],
    [
        'value' => $estadisticas['valoracion_media'] ?? '4.7',
        'label' => __('Valoración media', 'flavor-chat-ia'),
        'icon'  => '⭐',
    ],
];

// Construir filtros desde categorías
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
];
foreach ($categorias as $cat) {
    $filters[] = [
        'id'    => $cat['id'],
        'label' => $cat['label'],
        'icon'  => $cat['icon'],
        'count' => $cat['count'] ?? null,
    ];
}

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'marketplace',
    'title'        => __('Marketplace Local', 'flavor-chat-ia'),
    'subtitle'     => __('Compra, vende e intercambia productos en tu comunidad', 'flavor-chat-ia'),
    'icon'         => '🛒',
    'color'        => 'green',
    'items'        => $productos,
    'total'        => $total_productos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'categoria',
    'cta_text'     => __('Publicar Anuncio', 'flavor-chat-ia'),
    'cta_icon'     => '📢',
    'cta_action'   => 'flavorMarketplace.publicarAnuncio()',
    'card_template' => 'marketplace/card',
    'extra_content' => function() {
        // Incluir el componente "Cómo funciona"
        include FLAVOR_PLUGIN_PATH . 'templates/components/marketplace/como-funciona.php';
    },
    'empty_state'  => [
        'icon'       => '🛒',
        'title'      => __('No hay productos disponibles', 'flavor-chat-ia'),
        'text'       => __('¡Sé el primero en publicar un anuncio!', 'flavor-chat-ia'),
        'cta_text'   => __('Publicar Anuncio', 'flavor-chat-ia'),
        'cta_action' => 'flavorMarketplace.publicarAnuncio()',
    ],
]);

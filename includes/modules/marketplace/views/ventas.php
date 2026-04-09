<?php
/**
 * Vista Ventas - Marketplace
 *
 * Gestión completa de ventas del marketplace con estadísticas,
 * filtros avanzados y paginación.
 *
 * @package FlavorChatIA
 * @subpackage Marketplace
 */

if (!defined('ABSPATH')) exit;

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// =============================================================================
// FUNCIONES AUXILIARES
// =============================================================================

/**
 * Obtener badge de estado de conservación
 */
function obtener_badge_estado_conservacion($estado) {
    $estados = [
        'nuevo' => ['clase' => 'success', 'texto' => __('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'star-filled'],
        'como_nuevo' => ['clase' => 'info', 'texto' => __('Como nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'yes-alt'],
        'buen_estado' => ['clase' => 'primary', 'texto' => __('Buen estado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'thumbs-up'],
        'usado' => ['clase' => 'warning', 'texto' => __('Usado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'admin-tools'],
        'para_reparar' => ['clase' => 'danger', 'texto' => __('Para reparar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'warning'],
    ];

    $estado_key = str_replace([' ', '-'], '_', strtolower($estado));

    if (isset($estados[$estado_key])) {
        return $estados[$estado_key];
    }

    return ['clase' => 'secondary', 'texto' => ucfirst(str_replace('_', ' ', $estado)), 'icono' => 'tag'];
}

/**
 * Obtener badge de rango de precio
 */
function obtener_rango_precio($precio) {
    if ($precio == 0) {
        return ['clase' => 'success', 'texto' => __('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    } elseif ($precio <= 10) {
        return ['clase' => 'info', 'texto' => __('Económico', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    } elseif ($precio <= 50) {
        return ['clase' => 'primary', 'texto' => __('Medio', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    } elseif ($precio <= 200) {
        return ['clase' => 'warning', 'texto' => __('Alto', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    } else {
        return ['clase' => 'danger', 'texto' => __('Premium', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }
}

// =============================================================================
// CONFIGURACIÓN Y PARÁMETROS
// =============================================================================

$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Filtros
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado_conservacion']) ? sanitize_text_field($_GET['estado_conservacion']) : '';
$filtro_precio_min = isset($_GET['precio_min']) ? floatval($_GET['precio_min']) : '';
$filtro_precio_max = isset($_GET['precio_max']) ? floatval($_GET['precio_max']) : '';
$filtro_orden = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$filtro_orden_dir = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// =============================================================================
// ESTADÍSTICAS GENERALES
// =============================================================================

// Consulta para estadísticas (sin límite)
$args_stats = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'tax_query' => [
        [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => 'venta'
        ]
    ],
    'fields' => 'ids'
];

$stats_query = new WP_Query($args_stats);
$ids_ventas = $stats_query->posts;

$total_ventas = count($ids_ventas);
$valor_total = 0;
$precios = [];
$estados_conservacion = [];
$vendedores_activos = [];

if (!empty($ids_ventas)) {
    foreach ($ids_ventas as $venta_id) {
        $precio = floatval(get_post_meta($venta_id, '_marketplace_precio', true));
        $precios[] = $precio;
        $valor_total += $precio;

        $estado = get_post_meta($venta_id, '_marketplace_estado', true);
        if ($estado) {
            $estados_conservacion[$estado] = ($estados_conservacion[$estado] ?? 0) + 1;
        }

        $autor_id = get_post_field('post_author', $venta_id);
        $vendedores_activos[$autor_id] = true;
    }
}

$precio_promedio = $total_ventas > 0 ? $valor_total / $total_ventas : 0;
$ventas_nuevas_mes = 0;
$fecha_hace_30_dias = date('Y-m-d H:i:s', strtotime('-30 days'));

foreach ($ids_ventas as $venta_id) {
    $fecha_publicacion = get_post_field('post_date', $venta_id);
    if ($fecha_publicacion >= $fecha_hace_30_dias) {
        $ventas_nuevas_mes++;
    }
}

wp_reset_postdata();

// =============================================================================
// CONSULTA PRINCIPAL CON FILTROS
// =============================================================================

$args = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => $elementos_por_pagina,
    'offset' => $offset,
    'tax_query' => [
        [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => 'venta'
        ]
    ]
];

// Búsqueda
if (!empty($filtro_busqueda)) {
    $args['s'] = $filtro_busqueda;
}

// Ordenamiento
switch ($filtro_orden) {
    case 'price':
        $args['meta_key'] = '_marketplace_precio';
        $args['orderby'] = 'meta_value_num';
        break;
    case 'title':
        $args['orderby'] = 'title';
        break;
    default:
        $args['orderby'] = 'date';
}
$args['order'] = $filtro_orden_dir;

// Filtro por precio con meta_query
$meta_query = [];

if (!empty($filtro_estado)) {
    $meta_query[] = [
        'key' => '_marketplace_estado',
        'value' => $filtro_estado,
        'compare' => '='
    ];
}

if ($filtro_precio_min !== '') {
    $meta_query[] = [
        'key' => '_marketplace_precio',
        'value' => $filtro_precio_min,
        'compare' => '>=',
        'type' => 'NUMERIC'
    ];
}

if ($filtro_precio_max !== '') {
    $meta_query[] = [
        'key' => '_marketplace_precio',
        'value' => $filtro_precio_max,
        'compare' => '<=',
        'type' => 'NUMERIC'
    ];
}

if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}

$ventas_query = new WP_Query($args);
$total_filtrado = $ventas_query->found_posts;
$total_paginas = ceil($total_filtrado / $elementos_por_pagina);

// =============================================================================
// TOP PRODUCTOS MÁS RECIENTES
// =============================================================================

$args_top = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'tax_query' => [
        [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => 'venta'
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC'
];
$top_recientes = new WP_Query($args_top);

// Productos más caros
$args_caros = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'tax_query' => [
        [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => 'venta'
        ]
    ],
    'meta_key' => '_marketplace_precio',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
];
$top_caros = new WP_Query($args_caros);
?>

<style>
.flavor-ventas-wrapper {
    padding: 20px 0;
}

/* Estadísticas */
.flavor-ventas-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-ventas-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-ventas-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-ventas-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-ventas-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-ventas-stat-icon.cart { background: linear-gradient(135deg, #667eea, #764ba2); }
.flavor-ventas-stat-icon.money { background: linear-gradient(135deg, #11998e, #38ef7d); }
.flavor-ventas-stat-icon.avg { background: linear-gradient(135deg, #f093fb, #f5576c); }
.flavor-ventas-stat-icon.calendar { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.flavor-ventas-stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.flavor-ventas-stat-content span {
    color: #64748b;
    font-size: 13px;
}

/* Layout principal */
.flavor-ventas-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

@media (max-width: 1200px) {
    .flavor-ventas-layout {
        grid-template-columns: 1fr;
    }
}

.flavor-ventas-main {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Filtros */
.flavor-ventas-filters {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-ventas-filters-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}

.flavor-ventas-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-ventas-filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.flavor-ventas-filter-group input,
.flavor-ventas-filter-group select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    min-width: 140px;
}

.flavor-ventas-filter-group input[type="number"] {
    width: 100px;
}

.flavor-ventas-filters-actions {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

/* Tabla */
.flavor-ventas-table {
    width: 100%;
    border-collapse: collapse;
}

.flavor-ventas-table th {
    background: #f8fafc;
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    font-size: 13px;
    border-bottom: 2px solid #e2e8f0;
}

.flavor-ventas-table td {
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.flavor-ventas-table tr:hover {
    background: #f8fafc;
}

/* Producto info */
.flavor-ventas-producto {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-ventas-producto-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #f1f5f9;
}

.flavor-ventas-producto-img-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: linear-gradient(135deg, #e2e8f0, #f1f5f9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-ventas-producto-img-placeholder .dashicons {
    color: #94a3b8;
    font-size: 24px;
}

.flavor-ventas-producto-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
}

.flavor-ventas-producto-info h4 a {
    color: #1e293b;
    text-decoration: none;
}

.flavor-ventas-producto-info h4 a:hover {
    color: #3b82f6;
}

.flavor-ventas-producto-meta {
    font-size: 12px;
    color: #64748b;
}

/* Vendedor */
.flavor-ventas-vendedor {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-ventas-vendedor img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.flavor-ventas-vendedor-info span {
    display: block;
    font-size: 13px;
    color: #1e293b;
}

.flavor-ventas-vendedor-info small {
    color: #64748b;
    font-size: 11px;
}

/* Precio */
.flavor-ventas-precio {
    font-size: 16px;
    font-weight: 700;
    color: #059669;
}

.flavor-ventas-precio.gratis {
    color: #10b981;
}

/* Badges */
.flavor-ventas-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-ventas-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-ventas-badge.success { background: #dcfce7; color: #166534; }
.flavor-ventas-badge.info { background: #dbeafe; color: #1e40af; }
.flavor-ventas-badge.primary { background: #e0e7ff; color: #3730a3; }
.flavor-ventas-badge.warning { background: #fef3c7; color: #92400e; }
.flavor-ventas-badge.danger { background: #fee2e2; color: #991b1b; }
.flavor-ventas-badge.secondary { background: #f1f5f9; color: #475569; }

/* Acciones */
.flavor-ventas-acciones {
    display: flex;
    gap: 8px;
}

.flavor-ventas-acciones .button {
    padding: 6px 12px !important;
    font-size: 12px !important;
}

/* Paginación */
.flavor-ventas-pagination {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-ventas-pagination-info {
    font-size: 13px;
    color: #64748b;
}

.flavor-ventas-pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    margin: 0 2px;
    border-radius: 6px;
    font-size: 14px;
    text-decoration: none;
    color: #475569;
    background: #fff;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.flavor-ventas-pagination .page-numbers:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.flavor-ventas-pagination .page-numbers.current {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

/* Sidebar */
.flavor-ventas-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.flavor-ventas-sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.flavor-ventas-sidebar-card h3 {
    margin: 0;
    padding: 16px 20px;
    font-size: 14px;
    font-weight: 600;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-ventas-sidebar-card h3 .dashicons {
    color: #64748b;
}

.flavor-ventas-top-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-ventas-top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-ventas-top-item:last-child {
    border-bottom: none;
}

.flavor-ventas-top-rank {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
}

.flavor-ventas-top-rank.gold { background: #fef3c7; color: #92400e; }
.flavor-ventas-top-rank.silver { background: #f1f5f9; color: #475569; }
.flavor-ventas-top-rank.bronze { background: #fed7aa; color: #9a3412; }

.flavor-ventas-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-ventas-top-info h4 {
    margin: 0 0 2px 0;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-ventas-top-info h4 a {
    color: #1e293b;
    text-decoration: none;
}

.flavor-ventas-top-info span {
    font-size: 12px;
    color: #64748b;
}

.flavor-ventas-top-price {
    font-size: 14px;
    font-weight: 700;
    color: #059669;
}

/* Chart */
.flavor-ventas-chart-container {
    padding: 20px;
}

/* Vacío */
.flavor-ventas-empty {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.flavor-ventas-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    color: #cbd5e1;
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-cart" style="margin-right: 8px;"></span>
        <?php echo esc_html__('Ventas del Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="flavor-ventas-wrapper">
        <!-- Estadísticas -->
        <div class="flavor-ventas-stats">
            <div class="flavor-ventas-stat-card">
                <div class="flavor-ventas-stat-icon cart">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="flavor-ventas-stat-content">
                    <h3><?php echo number_format($total_ventas); ?></h3>
                    <span><?php echo esc_html__('Total Productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="flavor-ventas-stat-card">
                <div class="flavor-ventas-stat-icon money">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="flavor-ventas-stat-content">
                    <h3><?php echo number_format($valor_total, 2); ?> €</h3>
                    <span><?php echo esc_html__('Valor Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="flavor-ventas-stat-card">
                <div class="flavor-ventas-stat-icon avg">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="flavor-ventas-stat-content">
                    <h3><?php echo number_format($precio_promedio, 2); ?> €</h3>
                    <span><?php echo esc_html__('Precio Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="flavor-ventas-stat-card">
                <div class="flavor-ventas-stat-icon calendar">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="flavor-ventas-stat-content">
                    <h3><?php echo number_format($ventas_nuevas_mes); ?></h3>
                    <span><?php echo esc_html__('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <!-- Layout principal -->
        <div class="flavor-ventas-layout">
            <!-- Contenido principal -->
            <div class="flavor-ventas-main">
                <!-- Filtros -->
                <form method="get" class="flavor-ventas-filters">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
                    <?php if (isset($_GET['tab'])): ?>
                        <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>">
                    <?php endif; ?>

                    <div class="flavor-ventas-filters-grid">
                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre del producto...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>

                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="estado_conservacion">
                                <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="nuevo" <?php selected($filtro_estado, 'nuevo'); ?>><?php echo esc_html__('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="como_nuevo" <?php selected($filtro_estado, 'como_nuevo'); ?>><?php echo esc_html__('Como nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="buen_estado" <?php selected($filtro_estado, 'buen_estado'); ?>><?php echo esc_html__('Buen estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="usado" <?php selected($filtro_estado, 'usado'); ?>><?php echo esc_html__('Usado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="para_reparar" <?php selected($filtro_estado, 'para_reparar'); ?>><?php echo esc_html__('Para reparar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Precio mín.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" name="precio_min" value="<?php echo esc_attr($filtro_precio_min); ?>" min="0" step="0.01" placeholder="0">
                        </div>

                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Precio máx.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" name="precio_max" value="<?php echo esc_attr($filtro_precio_max); ?>" min="0" step="0.01" placeholder="∞">
                        </div>

                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="orderby">
                                <option value="date" <?php selected($filtro_orden, 'date'); ?>><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="title" <?php selected($filtro_orden, 'title'); ?>><?php echo esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="price" <?php selected($filtro_orden, 'price'); ?>><?php echo esc_html__('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-ventas-filter-group">
                            <label><?php echo esc_html__('Orden', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="order">
                                <option value="DESC" <?php selected($filtro_orden_dir, 'DESC'); ?>><?php echo esc_html__('Descendente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="ASC" <?php selected($filtro_orden_dir, 'ASC'); ?>><?php echo esc_html__('Ascendente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-ventas-filters-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                                <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <a href="<?php echo esc_url(remove_query_arg(['s', 'estado_conservacion', 'precio_min', 'precio_max', 'orderby', 'order', 'paged'])); ?>" class="button">
                                <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Tabla -->
                <?php if ($ventas_query->have_posts()): ?>
                    <table class="flavor-ventas-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Producto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Vendedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ventas_query->have_posts()): $ventas_query->the_post(); ?>
                                <?php
                                $precio = floatval(get_post_meta(get_the_ID(), '_marketplace_precio', true));
                                $estado_conservacion = get_post_meta(get_the_ID(), '_marketplace_estado', true);
                                $ubicacion = get_post_meta(get_the_ID(), '_marketplace_ubicacion', true);
                                $autor = get_the_author();
                                $autor_id = get_the_author_meta('ID');
                                $autor_email = get_the_author_meta('user_email');
                                $badge_estado = obtener_badge_estado_conservacion($estado_conservacion);
                                ?>
                                <tr>
                                    <td>
                                        <div class="flavor-ventas-producto">
                                            <?php if (has_post_thumbnail()): ?>
                                                <?php the_post_thumbnail([60, 60], ['class' => 'flavor-ventas-producto-img']); ?>
                                            <?php else: ?>
                                                <div class="flavor-ventas-producto-img-placeholder">
                                                    <span class="dashicons dashicons-format-image"></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flavor-ventas-producto-info">
                                                <h4><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></h4>
                                                <span class="flavor-ventas-producto-meta">ID: <?php the_ID(); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flavor-ventas-vendedor">
                                            <?php echo get_avatar($autor_id, 32); ?>
                                            <div class="flavor-ventas-vendedor-info">
                                                <span><?php echo esc_html($autor); ?></span>
                                                <small><?php echo esc_html($autor_email); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($precio > 0): ?>
                                            <span class="flavor-ventas-precio"><?php echo number_format($precio, 2); ?> €</span>
                                        <?php else: ?>
                                            <span class="flavor-ventas-precio gratis"><?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="flavor-ventas-badge <?php echo esc_attr($badge_estado['clase']); ?>">
                                            <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                            <?php echo esc_html($badge_estado['texto']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ubicacion): ?>
                                            <span class="dashicons dashicons-location" style="color: #64748b; font-size: 14px;"></span>
                                            <?php echo esc_html($ubicacion); ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo get_the_date('d/m/Y'); ?>
                                        <br><small style="color: #64748b;"><?php echo get_the_date('H:i'); ?></small>
                                    </td>
                                    <td>
                                        <div class="flavor-ventas-acciones">
                                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small" title="<?php echo esc_attr__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-edit" style="font-size: 14px; line-height: 1.4;"></span>
                                            </a>
                                            <a href="<?php the_permalink(); ?>" class="button button-small" target="_blank" title="<?php echo esc_attr__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-external" style="font-size: 14px; line-height: 1.4;"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="flavor-ventas-pagination">
                            <span class="flavor-ventas-pagination-info">
                                <?php printf(
                                    esc_html__('Mostrando %d-%d de %d productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    $offset + 1,
                                    min($offset + $elementos_por_pagina, $total_filtrado),
                                    $total_filtrado
                                ); ?>
                            </span>
                            <div class="flavor-ventas-pagination-links">
                                <?php
                                $pagination_args = [
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'total' => $total_paginas,
                                    'current' => $pagina_actual,
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                ];
                                echo paginate_links($pagination_args);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="flavor-ventas-empty">
                        <span class="dashicons dashicons-cart"></span>
                        <p><?php echo esc_html__('No se encontraron productos con los filtros aplicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo esc_url(remove_query_arg(['s', 'estado_conservacion', 'precio_min', 'precio_max', 'orderby', 'order', 'paged'])); ?>" class="button">
                            <?php echo esc_html__('Ver todos los productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="flavor-ventas-sidebar">
                <!-- Top Productos Recientes -->
                <div class="flavor-ventas-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html__('Más Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <?php if ($top_recientes->have_posts()): ?>
                        <ul class="flavor-ventas-top-list">
                            <?php $posicion = 1; ?>
                            <?php while ($top_recientes->have_posts()): $top_recientes->the_post(); ?>
                                <?php
                                $precio_top = floatval(get_post_meta(get_the_ID(), '_marketplace_precio', true));
                                $rank_class = $posicion === 1 ? 'gold' : ($posicion === 2 ? 'silver' : ($posicion === 3 ? 'bronze' : ''));
                                ?>
                                <li class="flavor-ventas-top-item">
                                    <span class="flavor-ventas-top-rank <?php echo esc_attr($rank_class); ?>"><?php echo $posicion; ?></span>
                                    <div class="flavor-ventas-top-info">
                                        <h4><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></h4>
                                        <span><?php echo get_the_date('d/m/Y'); ?></span>
                                    </div>
                                    <span class="flavor-ventas-top-price">
                                        <?php echo $precio_top > 0 ? number_format($precio_top, 2) . ' €' : __('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </li>
                                <?php $posicion++; ?>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </ul>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #64748b;">
                            <?php echo esc_html__('Sin productos recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Productos Más Caros -->
                <div class="flavor-ventas-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php echo esc_html__('Mayor Valor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <?php if ($top_caros->have_posts()): ?>
                        <ul class="flavor-ventas-top-list">
                            <?php $posicion = 1; ?>
                            <?php while ($top_caros->have_posts()): $top_caros->the_post(); ?>
                                <?php
                                $precio_top = floatval(get_post_meta(get_the_ID(), '_marketplace_precio', true));
                                $rank_class = $posicion === 1 ? 'gold' : ($posicion === 2 ? 'silver' : ($posicion === 3 ? 'bronze' : ''));
                                ?>
                                <li class="flavor-ventas-top-item">
                                    <span class="flavor-ventas-top-rank <?php echo esc_attr($rank_class); ?>"><?php echo $posicion; ?></span>
                                    <div class="flavor-ventas-top-info">
                                        <h4><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></h4>
                                        <span><?php the_author(); ?></span>
                                    </div>
                                    <span class="flavor-ventas-top-price">
                                        <?php echo $precio_top > 0 ? number_format($precio_top, 2) . ' €' : __('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </li>
                                <?php $posicion++; ?>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </ul>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #64748b;">
                            <?php echo esc_html__('Sin productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Distribución por Estado -->
                <?php if (!empty($estados_conservacion)): ?>
                <div class="flavor-ventas-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php echo esc_html__('Por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="flavor-ventas-chart-container">
                        <canvas id="flavor-ventas-estados-chart" height="200"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($estados_conservacion)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('flavor-ventas-estados-chart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($e) {
                    return ucfirst(str_replace('_', ' ', $e));
                }, array_keys($estados_conservacion))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($estados_conservacion)); ?>,
                    backgroundColor: ['#10b981', '#3b82f6', '#6366f1', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

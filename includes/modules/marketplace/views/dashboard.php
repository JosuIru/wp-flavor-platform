<?php
/**
 * Dashboard Profesional - Marketplace Comunitario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

require_once dirname(__DIR__, 3) . '/dashboard/class-dashboard-components.php';
$DC = 'Flavor_Dashboard_Components';

wp_enqueue_style('flavor-dashboard-enhanced', plugins_url('assets/css/dashboard-components-enhanced.css', dirname(__DIR__, 3)), [], '3.3.0');
wp_enqueue_script('flavor-dashboard-components', plugins_url('assets/js/dashboard-components.js', dirname(__DIR__, 3)), ['jquery'], '3.3.0', true);

global $wpdb;

// ============================================
// FILTROS
// ============================================
$filtro_categoria = isset($_GET['filtro_categoria']) ? sanitize_text_field($_GET['filtro_categoria']) : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? sanitize_text_field($_GET['filtro_tipo']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? sanitize_text_field($_GET['filtro_estado']) : '';
$filtro_precio_min = isset($_GET['filtro_precio_min']) ? absint($_GET['filtro_precio_min']) : 0;
$filtro_precio_max = isset($_GET['filtro_precio_max']) ? absint($_GET['filtro_precio_max']) : 0;
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? sanitize_text_field($_GET['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? sanitize_text_field($_GET['filtro_fecha_hasta']) : '';

// ============================================
// DATOS CON CACHÉ (OPTIMIZADO)
// ============================================
$stats = flavor_get_dashboard_stats('marketplace', function() use ($wpdb) {
    $inicio_mes = gmdate('Y-m-01');
    $mes_anterior = gmdate('Y-m-01', strtotime('-1 month'));
    $ultimo_dia_mes_anterior = gmdate('Y-m-t 23:59:59', strtotime('-1 month'));
    $hace_7_dias = gmdate('Y-m-d', strtotime('-7 days'));

    // Query combinada para estadísticas de posts
    $post_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COUNT(*) as total,
            SUM(post_status = 'publish') as publicados,
            SUM(post_status = 'pending') as pendientes,
            SUM(post_status = 'draft') as borradores,
            SUM(post_status = 'publish' AND post_date >= %s) as mes_actual,
            SUM(post_status = 'publish' AND post_date BETWEEN %s AND %s) as mes_anterior
         FROM {$wpdb->posts}
         WHERE post_type = 'marketplace_item'
         AND post_status IN ('publish', 'pending', 'draft')",
        $inicio_mes,
        $mes_anterior,
        $ultimo_dia_mes_anterior
    ), ARRAY_A);

    // Query combinada para estadísticas de postmeta
    $meta_stats = $wpdb->get_row(
        "SELECT
            COALESCE(SUM(CASE WHEN meta_key = '_marketplace_views' THEN CAST(meta_value AS UNSIGNED) ELSE 0 END), 0) as vistas_totales,
            COUNT(DISTINCT CASE WHEN meta_key = '_marketplace_precio' AND meta_value > 0 THEN post_id END) as con_precio,
            COALESCE(AVG(CASE WHEN meta_key = '_marketplace_precio' AND meta_value > 0 THEN CAST(meta_value AS DECIMAL(10,2)) END), 0) as precio_promedio,
            COUNT(DISTINCT CASE WHEN meta_key = '_marketplace_contacts' AND CAST(meta_value AS UNSIGNED) > 0 THEN post_id END) as con_contactos
         FROM {$wpdb->postmeta}
         WHERE meta_key IN ('_marketplace_views', '_marketplace_precio', '_marketplace_contacts')",
        ARRAY_A
    );

    // Vistas del mes (con JOIN)
    $vistas_mes_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT
            SUM(pm.meta_value >= %s) as vistas_mes,
            SUM(pm.meta_value BETWEEN %s AND %s) as vistas_mes_anterior
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_marketplace_last_view'",
        $inicio_mes,
        $mes_anterior,
        $ultimo_dia_mes_anterior
    ), ARRAY_A);

    // Evolución mensual (12 meses) - Una sola query con GROUP BY
    $evolucion_raw = $wpdb->get_results(
        "SELECT DATE_FORMAT(post_date, '%Y-%m') as mes, COUNT(*) as total
         FROM {$wpdb->posts}
         WHERE post_type = 'marketplace_item'
         AND post_status = 'publish'
         AND post_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(post_date, '%Y-%m')
         ORDER BY mes ASC",
        ARRAY_A
    );
    $evolucion_map = [];
    foreach ($evolucion_raw as $row) {
        $evolucion_map[$row['mes']] = (int) $row['total'];
    }
    $labels_meses = [];
    $data_anuncios_mes = [];
    for ($i = 11; $i >= 0; $i--) {
        $mes_key = gmdate('Y-m', strtotime("-{$i} months"));
        $labels_meses[] = date_i18n('M Y', strtotime($mes_key . '-01'));
        $data_anuncios_mes[] = $evolucion_map[$mes_key] ?? 0;
    }

    // Actividad 7 días - Una sola query con GROUP BY
    $actividad_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(post_date) as dia, COUNT(*) as total
         FROM {$wpdb->posts}
         WHERE post_type = 'marketplace_item'
         AND post_status = 'publish'
         AND post_date >= %s
         GROUP BY DATE(post_date)",
        $hace_7_dias
    ), ARRAY_A);
    $actividad_map = [];
    foreach ($actividad_raw as $row) {
        $actividad_map[$row['dia']] = (int) $row['total'];
    }
    $actividad_7_dias = [];
    for ($i = 6; $i >= 0; $i--) {
        $dia = gmdate('Y-m-d', strtotime("-{$i} days"));
        $actividad_7_dias[] = $actividad_map[$dia] ?? 0;
    }

    // Top 10 productos más vistos
    $top_productos = $wpdb->get_results(
        "SELECT p.ID, p.post_title, COALESCE(CAST(pm.meta_value AS UNSIGNED), 0) as vistas
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_marketplace_views'
         WHERE p.post_type = 'marketplace_item'
         AND p.post_status = 'publish'
         ORDER BY vistas DESC
         LIMIT 10",
        ARRAY_A
    );

    // Top 5 vendedores
    $top_vendedores = $wpdb->get_results(
        "SELECT p.post_author, u.display_name, COUNT(*) as total_anuncios,
                COALESCE(AVG(CAST(pm_rating.meta_value AS DECIMAL(3,2))), 0) as rating_promedio
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
         LEFT JOIN {$wpdb->postmeta} pm_rating ON p.ID = pm_rating.post_id AND pm_rating.meta_key = '_marketplace_seller_rating'
         WHERE p.post_type = 'marketplace_item'
         AND p.post_status = 'publish'
         GROUP BY p.post_author
         ORDER BY total_anuncios DESC, rating_promedio DESC
         LIMIT 5",
        ARRAY_A
    );

    // Trending 7 días
    $trending = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, COALESCE(CAST(pm.meta_value AS UNSIGNED), 0) as vistas_recientes
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'marketplace_item'
         AND p.post_status = 'publish'
         AND pm.meta_key = '_marketplace_last_view'
         AND pm.meta_value >= %s
         ORDER BY vistas_recientes DESC
         LIMIT 5",
        $hace_7_dias
    ), ARRAY_A);

    // Precios por categoría - Una sola query
    $precios_cat_raw = $wpdb->get_results(
        "SELECT t.name as categoria, t.slug,
                COALESCE(AVG(CAST(pm.meta_value AS DECIMAL(10,2))), 0) as precio_promedio,
                COUNT(DISTINCT pm.post_id) as total
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->term_relationships} tr ON pm.post_id = tr.object_id
         INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         WHERE pm.meta_key = '_marketplace_precio'
         AND pm.meta_value > 0
         AND tt.taxonomy = 'marketplace_categoria'
         GROUP BY t.term_id
         HAVING precio_promedio > 0
         ORDER BY precio_promedio DESC",
        ARRAY_A
    );

    return [
        'post_stats' => $post_stats,
        'meta_stats' => $meta_stats,
        'vistas_mes_stats' => $vistas_mes_stats,
        'labels_meses' => $labels_meses,
        'data_anuncios_mes' => $data_anuncios_mes,
        'actividad_7_dias' => $actividad_7_dias,
        'top_productos' => $top_productos,
        'top_vendedores' => $top_vendedores,
        'trending' => $trending,
        'precios_por_categoria' => $precios_cat_raw,
    ];
}, 300);

// Extraer datos del caché
$post_stats = $stats['post_stats'] ?? [];
$meta_stats = $stats['meta_stats'] ?? [];
$vistas_mes_stats = $stats['vistas_mes_stats'] ?? [];

$total_anuncios = (int) ($post_stats['total'] ?? 0);
$anuncios_publicados = (int) ($post_stats['publicados'] ?? 0);
$anuncios_pendientes = (int) ($post_stats['pendientes'] ?? 0);
$anuncios_borrador = (int) ($post_stats['borradores'] ?? 0);
$anuncios_mes = (int) ($post_stats['mes_actual'] ?? 0);
$anuncios_mes_anterior = (int) ($post_stats['mes_anterior'] ?? 0);

$vistas_totales = (int) ($meta_stats['vistas_totales'] ?? 0);
$anuncios_con_precio = (int) ($meta_stats['con_precio'] ?? 0);
$precio_promedio = (float) ($meta_stats['precio_promedio'] ?? 0);
$anuncios_con_contactos = (int) ($meta_stats['con_contactos'] ?? 0);

$vistas_mes = (int) ($vistas_mes_stats['vistas_mes'] ?? 0);
$vistas_mes_anterior = (int) ($vistas_mes_stats['vistas_mes_anterior'] ?? 0);

// Calcular tendencias
$trend_anuncios = null;
$trend_value_anuncios = '';
if ($anuncios_mes_anterior > 0) {
    $diff = (($anuncios_mes - $anuncios_mes_anterior) / $anuncios_mes_anterior) * 100;
    $trend_anuncios = $diff >= 0 ? 'up' : 'down';
    $trend_value_anuncios = ($diff >= 0 ? '+' : '') . round($diff, 1) . '%';
}

$trend_vistas = null;
$trend_value_vistas = '';
if ($vistas_mes_anterior > 0) {
    $diff_vistas = (($vistas_mes - $vistas_mes_anterior) / $vistas_mes_anterior) * 100;
    $trend_vistas = $diff_vistas >= 0 ? 'up' : 'down';
    $trend_value_vistas = ($diff_vistas >= 0 ? '+' : '') . round($diff_vistas, 1) . '%';
}

$tasa_conversion = $anuncios_publicados > 0 ? ($anuncios_con_contactos / $anuncios_publicados) * 100 : 0;

// Datos de gráficos desde caché
$labels_meses = $stats['labels_meses'] ?? [];
$data_anuncios_mes = $stats['data_anuncios_mes'] ?? [];
$actividad_7_dias = $stats['actividad_7_dias'] ?? [];

// Rankings desde caché (convertir a objetos para compatibilidad)
$top_productos_vistos = array_map(fn($p) => (object) $p, $stats['top_productos'] ?? []);
$top_vendedores = array_map(fn($v) => (object) $v, $stats['top_vendedores'] ?? []);
$trending_7_dias = array_map(fn($t) => (object) $t, $stats['trending'] ?? []);
$precios_por_categoria = $stats['precios_por_categoria'] ?? [];

// ============================================
// TAXONOMÍAS (WordPress tiene caché interno)
// ============================================
$categorias = get_terms([
    'taxonomy' => 'marketplace_categoria',
    'hide_empty' => false,
]);
$categorias_stats = [];
if (!is_wp_error($categorias)) {
    foreach ($categorias as $cat) {
        $categorias_stats[] = [
            'nombre' => $cat->name,
            'total' => (int) $cat->count,
            'slug' => $cat->slug,
        ];
    }
}
usort($categorias_stats, fn($a, $b) => $b['total'] <=> $a['total']);
$categorias_activas = count(array_filter($categorias_stats, fn($cat) => $cat['total'] > 0));

$tipos = get_terms([
    'taxonomy' => 'marketplace_tipo',
    'hide_empty' => false,
]);
$tipos_stats = [];
if (!is_wp_error($tipos)) {
    foreach ($tipos as $tipo) {
        $tipos_stats[] = [
            'nombre' => $tipo->name,
            'total' => (int) $tipo->count,
            'slug' => $tipo->slug,
        ];
    }
}
usort($tipos_stats, fn($a, $b) => $b['total'] <=> $a['total']);

// ============================================
// DATOS EN TIEMPO REAL (SIN CACHÉ)
// ============================================
$anuncios_recientes = get_posts([
    'post_type' => 'marketplace_item',
    'post_status' => ['publish', 'pending'],
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
]);

?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="wrap flavor-dashboard-wrap marketplace-dashboard">

    <div class="dm-dashboard-header">
        <h1 class="dm-dashboard-title">
            <span class="dashicons dashicons-megaphone"></span>
            <?php _e('Marketplace Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h1>
        <p class="dm-dashboard-subtitle">
            <?php _e('Compra, vende, regala e intercambia en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- ============================================ -->
    <!-- PANEL DE FILTROS -->
    <!-- ============================================ -->
    <div class="dm-section dm-filters-panel">
        <div class="dm-section__header">
            <h3 class="dm-section__title">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filtros Avanzados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-section__content">
            <form method="GET" action="" class="dm-filters-form">
                <input type="hidden" name="page" value="flavor-marketplace">

                <div class="dm-filters-grid">
                    <div class="dm-filter-group">
                        <label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="filtro_categoria">
                            <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($categorias_stats as $cat): ?>
                                <option value="<?php echo esc_attr($cat['slug']); ?>" <?php selected($filtro_categoria, $cat['slug']); ?>>
                                    <?php echo esc_html($cat['nombre']) . ' (' . $cat['total'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="filtro_tipo">
                            <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($tipos_stats as $tipo): ?>
                                <option value="<?php echo esc_attr($tipo['slug']); ?>" <?php selected($filtro_tipo, $tipo['slug']); ?>>
                                    <?php echo esc_html($tipo['nombre']) . ' (' . $tipo['total'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="filtro_estado">
                            <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="publish" <?php selected($filtro_estado, 'publish'); ?>><?php _e('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="pending" <?php selected($filtro_estado, 'pending'); ?>><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="draft" <?php selected($filtro_estado, 'draft'); ?>><?php _e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Precio Mínimo (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" name="filtro_precio_min" value="<?php echo esc_attr($filtro_precio_min); ?>" min="0" step="1">
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Precio Máximo (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" name="filtro_precio_max" value="<?php echo esc_attr($filtro_precio_max); ?>" min="0" step="1">
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="filtro_fecha_desde" value="<?php echo esc_attr($filtro_fecha_desde); ?>">
                    </div>

                    <div class="dm-filter-group">
                        <label><?php _e('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="filtro_fecha_hasta" value="<?php echo esc_attr($filtro_fecha_hasta); ?>">
                    </div>

                    <div class="dm-filter-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=flavor-marketplace'); ?>" class="button button-secondary">
                            <?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- BOTONES DE EXPORTACIÓN -->
    <!-- ============================================ -->
    <div class="dm-export-buttons">
        <button class="button button-secondary" onclick="alert('Exportar CSV - En desarrollo')">
            <span class="dashicons dashicons-media-spreadsheet"></span>
            <?php _e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <button class="button button-secondary" onclick="alert('Exportar PDF - En desarrollo')">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Exportar PDF', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>

    <!-- ============================================ -->
    <!-- ESTADÍSTICAS PRINCIPALES (KPIs) -->
    <!-- ============================================ -->
    <?php
    echo $DC::stats_grid([
        [
            'value' => number_format_i18n($total_anuncios),
            'label' => __('Total Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-megaphone',
            'color' => 'primary',
            'meta' => __('Histórico completo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => number_format_i18n($anuncios_publicados),
            'label' => __('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-yes-alt',
            'color' => 'success',
            'meta' => __('Visibles actualmente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'highlight' => true,
        ],
        [
            'value' => number_format_i18n($anuncios_mes),
            'label' => __('Anuncios del Mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-chart-line',
            'color' => 'eco',
            'trend' => $trend_anuncios,
            'trend_value' => $trend_value_anuncios,
            'meta' => __('vs mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => number_format_i18n($vistas_totales),
            'label' => __('Vistas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-visibility',
            'color' => 'info',
            'meta' => __('Acumulado histórico', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => number_format_i18n($vistas_mes),
            'label' => __('Vistas del Mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-visibility',
            'color' => 'info',
            'trend' => $trend_vistas,
            'trend_value' => $trend_value_vistas,
            'meta' => __('vs mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => round($tasa_conversion, 1) . '%',
            'label' => __('Tasa de Conversión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-thumbs-up',
            'color' => 'success',
            'meta' => __('Anuncios con contactos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => number_format_i18n($precio_promedio, 2) . ' €',
            'label' => __('Precio Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-money-alt',
            'color' => 'warning',
            'meta' => __('De productos en venta', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'value' => number_format_i18n($categorias_activas),
            'label' => __('Categorías Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-category',
            'color' => 'eco',
            'meta' => sprintf(__('de %d totales', FLAVOR_PLATFORM_TEXT_DOMAIN), count($categorias_stats)),
        ],
        [
            'value' => number_format_i18n($anuncios_pendientes),
            'label' => __('Pendientes Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-clock',
            'color' => 'warning',
            'meta' => __('Requieren revisión', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
    ], 3);
    ?>

    <!-- ============================================ -->
    <!-- GRÁFICOS CHART.JS -->
    <!-- ============================================ -->
    <div class="dm-section">
        <div class="dm-section__header">
            <h3 class="dm-section__title">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('Análisis Visual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-section__content">
            <div class="dm-charts-grid">

                <!-- Gráfico 1: Evolución Anuncios 12 Meses -->
                <div class="dm-chart-container">
                    <h4><?php _e('Evolución de Anuncios Publicados (12 meses)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div class="dm-chart-wrapper">
                        <canvas id="chartAnunciosMes"></canvas>
                    </div>
                </div>

                <!-- Gráfico 2: Distribución por Categoría -->
                <div class="dm-chart-container">
                    <h4><?php _e('Distribución por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div class="dm-chart-wrapper">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>

                <!-- Gráfico 3: Tipos de Anuncio -->
                <div class="dm-chart-container">
                    <h4><?php _e('Tipos de Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div class="dm-chart-wrapper">
                        <canvas id="chartTipos"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="dm-grid-2">

        <!-- ============================================ -->
        <!-- COLUMNA IZQUIERDA -->
        <!-- ============================================ -->
        <div>

            <!-- Top 10 Productos Más Vistos -->
            <?php if (!empty($top_productos_vistos)): ?>
            <div class="dm-section">
                <div class="dm-section__header">
                    <h3 class="dm-section__title">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Top 10 Productos Más Vistos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                </div>
                <div class="dm-section__content">
                    <div class="dm-ranking-list">
                        <?php
                        $posicion = 1;
                        foreach ($top_productos_vistos as $producto):
                            $medallas = ['🥇', '🥈', '🥉'];
                            $medalla = isset($medallas[$posicion - 1]) ? $medallas[$posicion - 1] : '📍';
                        ?>
                        <div class="dm-ranking-item">
                            <span class="dm-ranking-pos"><?php echo $medalla; ?></span>
                            <div class="dm-ranking-info">
                                <strong><?php echo esc_html($producto->post_title); ?></strong>
                                <span class="dm-ranking-meta"><?php echo number_format_i18n($producto->vistas); ?> <?php _e('vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <span class="dm-ranking-badge">#<?php echo $posicion; ?></span>
                        </div>
                        <?php
                        $posicion++;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Anuncios Recientes -->
            <?php
            $anuncios_table = [];
            foreach ($anuncios_recientes as $anuncio) {
                $tipo_term = get_the_terms($anuncio->ID, 'marketplace_tipo');
                $tipo = $tipo_term && !is_wp_error($tipo_term) ? $tipo_term[0]->name : '—';

                $categoria_term = get_the_terms($anuncio->ID, 'marketplace_categoria');
                $categoria = $categoria_term && !is_wp_error($categoria_term) ? $categoria_term[0]->name : '—';

                $estado_colors = [
                    'publish' => 'success',
                    'pending' => 'warning',
                    'draft' => 'secondary',
                ];
                $estado_labels = [
                    'publish' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'pending' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'draft' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];

                $anuncios_table[] = [
                    'titulo' => '<strong>' . esc_html($anuncio->post_title) . '</strong>',
                    'tipo' => esc_html($tipo),
                    'categoria' => esc_html($categoria),
                    'fecha' => date_i18n('d/m/Y', strtotime($anuncio->post_date)),
                    'estado' => $DC::badge(
                        $estado_labels[$anuncio->post_status] ?? $anuncio->post_status,
                        $estado_colors[$anuncio->post_status] ?? 'secondary'
                    ),
                ];
            }

            echo $DC::data_table([
                'title' => __('Anuncios Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-list-view',
                'columns' => [
                    'titulo' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'tipo' => __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'categoria' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'fecha' => __('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'estado' => __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'data' => $anuncios_table,
                'empty_message' => __('No hay anuncios aún', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'striped' => true,
                'hoverable' => true,
            ]);
            ?>

            <!-- Análisis de Precios por Categoría -->
            <?php if (!empty($precios_por_categoria)): ?>
            <div class="dm-section">
                <div class="dm-section__header">
                    <h3 class="dm-section__title">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php _e('Análisis de Precios por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                </div>
                <div class="dm-section__content">
                    <div class="dm-precio-analisis">
                        <?php foreach (array_slice($precios_por_categoria, 0, 8) as $cat_precio): ?>
                        <div class="dm-precio-item">
                            <div class="dm-precio-header">
                                <strong><?php echo esc_html($cat_precio['categoria']); ?></strong>
                                <span class="dm-precio-valor"><?php echo number_format_i18n($cat_precio['precio_promedio'], 2); ?> €</span>
                            </div>
                            <div class="dm-precio-meta">
                                <?php echo number_format_i18n($cat_precio['total']); ?> <?php _e('anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ============================================ -->
        <!-- COLUMNA DERECHA -->
        <!-- ============================================ -->
        <div>

            <!-- Top 5 Vendedores -->
            <?php if (!empty($top_vendedores)): ?>
            <div class="dm-section">
                <div class="dm-section__header">
                    <h3 class="dm-section__title">
                        <span class="dashicons dashicons-businessman"></span>
                        <?php _e('Top 5 Vendedores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                </div>
                <div class="dm-section__content">
                    <div class="dm-vendedores-list">
                        <?php
                        $posicion = 1;
                        foreach ($top_vendedores as $vendedor):
                            $medallas = ['🥇', '🥈', '🥉'];
                            $medalla = isset($medallas[$posicion - 1]) ? $medallas[$posicion - 1] : '⭐';
                        ?>
                        <div class="dm-vendedor-card">
                            <span class="dm-vendedor-medalla"><?php echo $medalla; ?></span>
                            <div class="dm-vendedor-info">
                                <strong><?php echo esc_html($vendedor->display_name); ?></strong>
                                <div class="dm-vendedor-stats">
                                    <span><?php echo number_format_i18n($vendedor->total_anuncios); ?> <?php _e('anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php if ($vendedor->rating_promedio > 0): ?>
                                    <span>⭐ <?php echo number_format_i18n($vendedor->rating_promedio, 1); ?>/5</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $posicion++;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Productos Trending (7 días) -->
            <?php if (!empty($trending_7_dias)): ?>
            <div class="dm-section">
                <div class="dm-section__header">
                    <h3 class="dm-section__title">
                        <span class="dashicons dashicons-thumbs-up"></span>
                        <?php _e('Productos Trending (7 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                </div>
                <div class="dm-section__content">
                    <div class="dm-trending-list">
                        <?php foreach ($trending_7_dias as $trending): ?>
                        <div class="dm-trending-item">
                            <span class="dm-trending-icon">🔥</span>
                            <div class="dm-trending-info">
                                <strong><?php echo esc_html($trending->post_title); ?></strong>
                                <span class="dm-trending-meta"><?php echo number_format_i18n($trending->vistas_recientes); ?> <?php _e('vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actividad Reciente (Mini Chart) -->
            <?php
            $chart_html = '<div class="dm-mb-2">';
            $chart_html .= '<p style="font-size: 13px; color: var(--dm-text-secondary); margin-bottom: 12px;">';
            $chart_html .= __('Anuncios publicados en los últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $chart_html .= '</p>';
            $chart_html .= $DC::mini_chart($actividad_7_dias, 'primary');
            $chart_html .= '</div>';

            echo $DC::section(
                __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $chart_html,
                [
                    'icon' => 'dashicons-chart-line',
                    'collapsible' => true,
                ]
            );
            ?>

            <!-- Distribución por Estado -->
            <?php
            $total_items = $anuncios_publicados + $anuncios_pendientes + $anuncios_borrador;
            if ($total_items > 0) {
                $dist_html = '<div style="display: grid; gap: 12px;">';
                $dist_html .= $DC::progress_bar($anuncios_publicados, $total_items, __('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'success');
                $dist_html .= $DC::progress_bar($anuncios_pendientes, $total_items, __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'warning');
                $dist_html .= $DC::progress_bar($anuncios_borrador, $total_items, __('Borradores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'secondary');
                $dist_html .= '</div>';

                echo $DC::section(
                    __('Distribución por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $dist_html,
                    [
                        'icon' => 'dashicons-chart-pie',
                        'collapsible' => true,
                    ]
                );
            }
            ?>

            <!-- Categorías Más Activas -->
            <?php
            if (!empty($categorias_stats)) {
                $cat_html = '<div style="display: grid; gap: 12px;">';
                foreach (array_slice($categorias_stats, 0, 8) as $cat) {
                    if ($cat['total'] > 0) {
                        $cat_html .= '<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--dm-bg-secondary); border-radius: 8px;">';
                        $cat_html .= '<span style="font-weight: 500;">' . esc_html($cat['nombre']) . '</span>';
                        $cat_html .= '<span style="color: var(--dm-primary); font-weight: 600;">' . number_format_i18n($cat['total']) . '</span>';
                        $cat_html .= '</div>';
                    }
                }
                $cat_html .= '</div>';

                echo $DC::section(
                    __('Categorías Más Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $cat_html,
                    [
                        'icon' => 'dashicons-category',
                        'collapsible' => true,
                        'collapsed' => true,
                    ]
                );
            }
            ?>

            <!-- Tipos de Anuncio -->
            <?php
            if (!empty($tipos_stats)) {
                $tipos_html = '<div style="display: grid; gap: 8px;">';
                foreach ($tipos_stats as $tipo) {
                    if ($tipo['total'] > 0) {
                        $tipo_colors = [
                            'Venta' => 'success',
                            'Regalo' => 'eco',
                            'Intercambio' => 'info',
                            'Alquiler' => 'warning',
                        ];
                        $color = $tipo_colors[$tipo['nombre']] ?? 'primary';
                        $tipos_html .= '<div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--dm-bg-secondary); border-radius: 6px;">';
                        $tipos_html .= '<span>' . esc_html($tipo['nombre']) . '</span>';
                        $tipos_html .= $DC::badge(number_format_i18n($tipo['total']), $color);
                        $tipos_html .= '</div>';
                    }
                }
                $tipos_html .= '</div>';

                echo $DC::section(
                    __('Tipos de Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $tipos_html,
                    [
                        'icon' => 'dashicons-tag',
                        'collapsible' => true,
                        'collapsed' => true,
                    ]
                );
            }
            ?>

            <!-- Acciones Rápidas -->
            <?php
            $actions_html = '
            <div style="display: grid; gap: 8px;">
                <a href="' . admin_url('edit.php?post_type=marketplace_item') . '" class="button button-primary">
                    <span class="dashicons dashicons-list-view"></span>
                    ' . __('Ver Todos los Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '
                </a>
                <a href="' . admin_url('post-new.php?post_type=marketplace_item') . '" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    ' . __('Crear Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN) . '
                </a>
                <a href="' . admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item') . '" class="button button-secondary">
                    <span class="dashicons dashicons-category"></span>
                    ' . __('Gestionar Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN) . '
                </a>
            </div>
            ';

            echo $DC::section(
                __('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $actions_html,
                [
                    'icon' => 'dashicons-admin-links',
                    'collapsible' => true,
                    'collapsed' => true,
                ]
            );
            ?>

        </div>

    </div>

    <!-- ============================================ -->
    <!-- MÓDULOS RELACIONADOS -->
    <!-- ============================================ -->
    <?php
    $active_modules = get_option('flavor_active_modules', []);
    $modulos_relacionados = [];

    // Grupos de Consumo
    if (in_array('grupos-consumo', $active_modules)) {
        $tabla_gc_productos = $wpdb->prefix . 'flavor_gc_productos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_gc_productos'") === $tabla_gc_productos) {
            $productos_gc = $wpdb->get_results(
                "SELECT id, nombre, precio FROM $tabla_gc_productos
                 WHERE activo = 1
                 ORDER BY id DESC
                 LIMIT 3"
            );

            if (!empty($productos_gc)) {
                $datos_html = '<div class="dm-widget-data-list">';
                foreach ($productos_gc as $prod) {
                    $datos_html .= sprintf(
                        '<div class="dm-widget-item">
                            <strong>%s</strong>
                            <span class="dm-widget-meta">%s €/ud</span>
                        </div>',
                        esc_html($prod->nombre),
                        number_format_i18n($prod->precio, 2)
                    );
                }
                $datos_html .= '</div>';

                $modulos_relacionados['grupos-consumo'] = [
                    'titulo' => sprintf(__('Productos en Grupos de Consumo (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($productos_gc)),
                    'descripcion' => __('Productos también disponibles en pedidos colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'dashicons-cart',
                    'url' => admin_url('admin.php?page=flavor-grupos-consumo'),
                    'datos' => $datos_html,
                ];
            }
        }
    }

    // Eventos
    if (in_array('eventos', $active_modules)) {
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
            $eventos_comercio = $wpdb->get_results(
                "SELECT id, titulo, fecha_inicio FROM $tabla_eventos
                 WHERE (titulo LIKE '%feria%' OR titulo LIKE '%mercado%' OR categoria = 'comercio')
                 AND fecha_inicio >= NOW()
                 ORDER BY fecha_inicio ASC
                 LIMIT 3"
            );

            if (!empty($eventos_comercio)) {
                $datos_html = '<div class="dm-widget-data-list">';
                foreach ($eventos_comercio as $evento) {
                    $datos_html .= sprintf(
                        '<div class="dm-widget-item">
                            <strong>%s</strong>
                            <span class="dm-widget-meta">📅 %s</span>
                        </div>',
                        esc_html($evento->titulo),
                        date_i18n('d/m/Y', strtotime($evento->fecha_inicio))
                    );
                }
                $datos_html .= '</div>';

                $modulos_relacionados['eventos'] = [
                    'titulo' => sprintf(__('Próximas Ferias y Mercados (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($eventos_comercio)),
                    'descripcion' => __('Eventos donde promocionar tus productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'dashicons-calendar',
                    'url' => admin_url('admin.php?page=flavor-eventos'),
                    'datos' => $datos_html,
                ];
            }
        }
    }

    // Socios
    if (in_array('socios', $active_modules)) {
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") === $tabla_socios) {
            $vendedores_socios = $wpdb->get_results(
                "SELECT DISTINCT u.display_name, COUNT(p.ID) as total_anuncios
                 FROM {$wpdb->posts} p
                 INNER JOIN $tabla_socios s ON p.post_author = s.usuario_id
                 INNER JOIN {$wpdb->users} u ON s.usuario_id = u.ID
                 WHERE p.post_type = 'marketplace_item'
                 AND p.post_status = 'publish'
                 AND s.estado = 'activo'
                 GROUP BY s.usuario_id
                 ORDER BY total_anuncios DESC
                 LIMIT 3"
            );

            if (!empty($vendedores_socios)) {
                $datos_html = '<div class="dm-widget-data-list">';
                foreach ($vendedores_socios as $vendedor) {
                    $datos_html .= sprintf(
                        '<div class="dm-widget-item">
                            <strong>%s</strong>
                            <span class="dm-widget-meta">⭐ %d anuncios</span>
                        </div>',
                        esc_html($vendedor->display_name),
                        (int)$vendedor->total_anuncios
                    );
                }
                $datos_html .= '</div>';

                $modulos_relacionados['socios'] = [
                    'titulo' => sprintf(__('Vendedores Socios (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($vendedores_socios)),
                    'descripcion' => __('Top vendedores que son socios de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'dashicons-groups',
                    'url' => admin_url('admin.php?page=flavor-socios'),
                    'datos' => $datos_html,
                ];
            }
        }
    }

    // Comunidades
    if (in_array('comunidades', $active_modules)) {
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_comunidades'") === $tabla_comunidades) {
            $comunidades_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_comunidades WHERE activo = 1"
            );

            if ($comunidades_activas > 0) {
                $datos_html = '<p style="font-size: 14px; color: var(--dm-text-secondary);">';
                $datos_html .= sprintf(__('%d comunidades donde se publican anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidades_activas);
                $datos_html .= '</p>';

                $modulos_relacionados['comunidades'] = [
                    'titulo' => __('Anuncios en Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'descripcion' => __('Alcance multi-comunidad del marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'dashicons-groups',
                    'url' => admin_url('admin.php?page=flavor-comunidades'),
                    'datos' => $datos_html,
                ];
            }
        }
    }

    if (!empty($modulos_relacionados)):
    ?>
    <div class="dm-section" style="margin-top: 32px;">
        <div class="dm-section__header">
            <h3 class="dm-section__title">
                <span class="dashicons dashicons-networking"></span>
                <?php _e('Módulos Relacionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                <?php foreach ($modulos_relacionados as $modulo): ?>
                <div class="dm-widget-relacionado">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>" style="color: var(--dm-primary); font-size: 24px;"></span>
                        <strong style="font-size: 15px;"><?php echo esc_html($modulo['titulo']); ?></strong>
                    </div>
                    <p style="color: var(--dm-text-secondary); font-size: 13px; margin: 0 0 12px 0;"><?php echo esc_html($modulo['descripcion']); ?></p>

                    <?php if (isset($modulo['datos'])): ?>
                        <div class="dm-widget-datos-vivo">
                            <?php echo $modulo['datos']; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($modulo['url']); ?>" class="button button-small" style="margin-top: 12px;">
                        <?php _e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ============================================ -->
<!-- JAVASCRIPT CHART.JS -->
<!-- ============================================ -->
<script>
jQuery(document).ready(function($) {

    // Gráfico 1: Evolución Anuncios 12 Meses
    const ctx1 = document.getElementById('chartAnunciosMes');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_meses); ?>,
                datasets: [{
                    label: '<?php _e('Anuncios Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                    data: <?php echo json_encode($data_anuncios_mes); ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // Gráfico 2: Distribución por Categoría
    const ctx2 = document.getElementById('chartCategorias');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column(array_slice($categorias_stats, 0, 8), 'nombre')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column(array_slice($categorias_stats, 0, 8), 'total')); ?>,
                    backgroundColor: [
                        '#f59e0b', '#3b82f6', '#10b981', '#ef4444',
                        '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, padding: 10 }
                    }
                }
            }
        });
    }

    // Gráfico 3: Tipos de Anuncio
    const ctx3 = document.getElementById('chartTipos');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($tipos_stats, 'nombre')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($tipos_stats, 'total')); ?>,
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 10 }
                    }
                }
            }
        });
    }

});
</script>

<!-- ============================================ -->
<!-- ESTILOS CSS -->
<!-- ============================================ -->
<style>
.marketplace-dashboard {
    max-width: 1400px;
}

.dm-dashboard-header {
    margin-bottom: 24px;
}

.dm-dashboard-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    margin: 0 0 8px;
}

.dm-dashboard-title .dashicons {
    color: #f59e0b;
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.dm-dashboard-subtitle {
    font-size: 15px;
    color: var(--dm-text-secondary);
    margin: 0;
}

/* Panel de Filtros */
.dm-filters-panel {
    margin-bottom: 20px;
}

.dm-filters-form {
    padding: 0;
}

.dm-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.dm-filter-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 13px;
    color: var(--dm-text);
}

.dm-filter-group select,
.dm-filter-group input[type="number"],
.dm-filter-group input[type="date"] {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.dm-filter-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Botones de Exportación */
.dm-export-buttons {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    justify-content: flex-end;
}

.dm-export-buttons .button {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Gráficos Chart.js */
.dm-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
}

.dm-chart-container {
    background: var(--dm-bg);
    border: 1px solid var(--dm-border);
    border-radius: 12px;
    padding: 20px;
}

.dm-chart-container h4 {
    margin: 0 0 16px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--dm-text);
}

.dm-chart-wrapper {
    position: relative;
    height: 300px;
}

/* Rankings y Listas */
.dm-ranking-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dm-ranking-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--dm-bg-secondary);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.dm-ranking-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.dm-ranking-pos {
    font-size: 24px;
    flex-shrink: 0;
}

.dm-ranking-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dm-ranking-info strong {
    font-size: 14px;
    color: var(--dm-text);
}

.dm-ranking-meta {
    font-size: 12px;
    color: var(--dm-text-secondary);
}

.dm-ranking-badge {
    background: var(--dm-primary);
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Vendedores */
.dm-vendedores-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dm-vendedor-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--dm-bg-secondary);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.dm-vendedor-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.dm-vendedor-medalla {
    font-size: 28px;
    flex-shrink: 0;
}

.dm-vendedor-info {
    flex: 1;
}

.dm-vendedor-info strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}

.dm-vendedor-stats {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: var(--dm-text-secondary);
}

/* Trending */
.dm-trending-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dm-trending-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--dm-bg-secondary);
    border-radius: 8px;
}

.dm-trending-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.dm-trending-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dm-trending-info strong {
    font-size: 13px;
}

.dm-trending-meta {
    font-size: 12px;
    color: var(--dm-text-secondary);
}

/* Análisis de Precios */
.dm-precio-analisis {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dm-precio-item {
    padding: 12px;
    background: var(--dm-bg-secondary);
    border-radius: 8px;
}

.dm-precio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.dm-precio-header strong {
    font-size: 14px;
    color: var(--dm-text);
}

.dm-precio-valor {
    font-size: 15px;
    font-weight: 600;
    color: var(--dm-success);
}

.dm-precio-meta {
    font-size: 12px;
    color: var(--dm-text-secondary);
}

/* Widgets Módulos Relacionados */
.dm-widget-relacionado {
    padding: 20px;
    background: var(--dm-bg-secondary);
    border-radius: 12px;
    border-left: 4px solid var(--dm-primary);
    transition: all 0.3s ease;
}

.dm-widget-relacionado:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.dm-widget-datos-vivo {
    margin: 12px 0;
    padding: 12px;
    background: var(--dm-bg);
    border-radius: 8px;
}

.dm-widget-data-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dm-widget-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    background: var(--dm-bg-secondary);
    border-radius: 6px;
    font-size: 13px;
    transition: background 0.2s ease;
}

.dm-widget-item:hover {
    background: var(--dm-bg-hover, rgba(0, 0, 0, 0.05));
}

.dm-widget-item strong {
    flex: 1;
    color: var(--dm-text);
}

.dm-widget-meta {
    color: var(--dm-text-secondary);
    font-size: 12px;
    white-space: nowrap;
    margin-left: 8px;
}

/* Responsive */
@media (max-width: 1200px) {
    .dm-charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 782px) {
    .dm-filters-grid {
        grid-template-columns: 1fr;
    }

    .dm-export-buttons {
        justify-content: flex-start;
    }
}
</style>

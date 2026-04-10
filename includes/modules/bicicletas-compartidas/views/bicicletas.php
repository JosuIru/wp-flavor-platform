<?php
/**
 * Vista de Gestión de Bicicletas - Dashboard Mejorado
 *
 * @package FlavorPlatform
 * @subpackage BicicletasCompartidas
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN));

global $wpdb;
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';
$tabla_bicicletas_alt = $wpdb->prefix . 'flavor_bicicletas';
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
$tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtener badge de estado de bicicleta
 */
function obtener_badge_estado_bicicleta($estado) {
    $estados = [
        'disponible' => ['clase' => 'success', 'texto' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'yes-alt'],
        'en_uso' => ['clase' => 'info', 'texto' => __('En Uso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'businessman'],
        'mantenimiento' => ['clase' => 'warning', 'texto' => __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'admin-tools'],
        'fuera_servicio' => ['clase' => 'danger', 'texto' => __('Fuera de Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dismiss'],
        'reservada' => ['clase' => 'secondary', 'texto' => __('Reservada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'clock']
    ];

    $estado_key = strtolower($estado);
    return $estados[$estado_key] ?? ['clase' => 'secondary', 'texto' => ucfirst(str_replace('_', ' ', $estado)), 'icono' => 'marker'];
}

/**
 * Calcular estado de mantenimiento
 */
function calcular_estado_mantenimiento($fecha_ultimo_mantenimiento, $km_totales) {
    if (!$fecha_ultimo_mantenimiento) {
        return ['nivel' => 'urgente', 'texto' => __('Sin registro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ef4444'];
    }

    $dias_desde_mantenimiento = floor((time() - strtotime($fecha_ultimo_mantenimiento)) / 86400);

    if ($dias_desde_mantenimiento > 90 || $km_totales > 1000) {
        return ['nivel' => 'urgente', 'texto' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ef4444'];
    } elseif ($dias_desde_mantenimiento > 60 || $km_totales > 500) {
        return ['nivel' => 'proximo', 'texto' => __('Próximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b'];
    } elseif ($dias_desde_mantenimiento > 30) {
        return ['nivel' => 'normal', 'texto' => __('Normal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#3b82f6'];
    }
    return ['nivel' => 'optimo', 'texto' => __('Óptimo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#10b981'];
}

/**
 * Obtener tipo de bicicleta
 */
function obtener_tipo_bicicleta($tipo) {
    $tipos = [
        'urbana' => ['icono' => '🚲', 'texto' => __('Urbana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#3b82f6'],
        'electrica' => ['icono' => '⚡', 'texto' => __('Eléctrica', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#10b981'],
        'montana' => ['icono' => '🏔️', 'texto' => __('Montaña', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#8b5cf6'],
        'plegable' => ['icono' => '📦', 'texto' => __('Plegable', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b'],
        'cargo' => ['icono' => '📦', 'texto' => __('Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#6b7280']
    ];

    $tipo_key = strtolower($tipo ?? 'urbana');
    return $tipos[$tipo_key] ?? ['icono' => '🚲', 'texto' => ucfirst($tipo ?? 'Urbana'), 'color' => '#3b82f6'];
}

// =====================================================
// VERIFICAR EXISTENCIA DE TABLAS
// =====================================================

$tabla_bicicletas_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_bicicletas
)) > 0;

$tabla_bicicletas_alt_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_bicicletas_alt
)) > 0;

if (!$tabla_bicicletas_existe && $tabla_bicicletas_alt_existe) {
    $tabla_bicicletas = $tabla_bicicletas_alt;
    $tabla_bicicletas_existe = true;
}

$tabla_estaciones_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_estaciones
)) > 0;

$tablas_bicicletas_disponibles = $tabla_bicicletas_existe;
$col_km = 'kilometros_totales';
$col_revision = 'fecha_ultimo_mantenimiento';

if ($tablas_bicicletas_disponibles) {
    $col_km = (int) $wpdb->get_var("SHOW COLUMNS FROM $tabla_bicicletas LIKE 'kilometros_totales'")
        ? 'kilometros_totales'
        : 'kilometros_acumulados';
    $col_revision = (int) $wpdb->get_var("SHOW COLUMNS FROM $tabla_bicicletas LIKE 'fecha_ultimo_mantenimiento'")
        ? 'fecha_ultimo_mantenimiento'
        : 'ultima_revision';
}

// =====================================================
// PARÁMETROS DE FILTRADO Y PAGINACIÓN
// =====================================================

$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_estacion = isset($_GET['estacion']) ? intval($_GET['estacion']) : 0;
$filtro_mantenimiento = isset($_GET['mantenimiento']) ? sanitize_text_field($_GET['mantenimiento']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'codigo_asc';
$pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// =====================================================
// OBTENER DATOS
// =====================================================

if ($tablas_bicicletas_disponibles) {
    // Construir consulta real
    $where_conditions = ["1=1"];
    $params = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(b.codigo LIKE %s OR b.modelo LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
    }

    if (!empty($filtro_estado)) {
        $where_conditions[] = "b.estado = %s";
        $params[] = $filtro_estado;
    }

    if (!empty($filtro_tipo)) {
        $where_conditions[] = "b.tipo = %s";
        $params[] = $filtro_tipo;
    }

    if ($filtro_estacion > 0) {
        $where_conditions[] = "b.estacion_actual_id = %d";
        $params[] = $filtro_estacion;
    }

    $where_sql = implode(' AND ', $where_conditions);

    switch ($orden) {
        case 'codigo_desc': $order_sql = 'b.codigo DESC'; break;
        case 'km_desc': $order_sql = 'b.kilometros_totales DESC'; break;
        case 'km_asc': $order_sql = 'b.kilometros_totales ASC'; break;
        case 'prestamos_desc': $order_sql = 'total_prestamos DESC'; break;
        case 'modelo_asc': $order_sql = 'b.modelo ASC'; break;
        default: $order_sql = 'b.codigo ASC';
    }

    // Query base
    $query_base = "
        SELECT b.*,
               COALESCE(b.{$col_km}, 0) as kilometros_totales,
               b.{$col_revision} as fecha_ultimo_mantenimiento,
               e.nombre as nombre_estacion,
               (SELECT COUNT(*) FROM $tabla_prestamos WHERE bicicleta_id = b.id) as total_prestamos
        FROM $tabla_bicicletas b
        LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
        WHERE $where_sql
    ";

    // Contar total para paginación
    $count_query = "SELECT COUNT(*) FROM ($query_base) as subquery";
    if (!empty($params)) {
        $total_bicicletas_filtradas = $wpdb->get_var($wpdb->prepare($count_query, $params));
    } else {
        $total_bicicletas_filtradas = $wpdb->get_var($count_query);
    }

    // Obtener bicicletas paginadas
    $query_paginada = "$query_base ORDER BY $order_sql LIMIT %d OFFSET %d";
    $params_paginados = array_merge($params, [$items_por_pagina, $offset]);
    $bicicletas = $wpdb->get_results($wpdb->prepare($query_paginada, $params_paginados));

    // Estadísticas generales
    $stats = $wpdb->get_row("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
            SUM(CASE WHEN estado = 'en_uso' THEN 1 ELSE 0 END) as en_uso,
            SUM(CASE WHEN estado IN ('mantenimiento', 'fuera_servicio') THEN 1 ELSE 0 END) as mantenimiento,
            SUM(COALESCE({$col_km}, 0)) as km_totales
        FROM $tabla_bicicletas
    ");

    $total_bicicletas = $stats->total ?? 0;
    $disponibles = $stats->disponibles ?? 0;
    $en_uso = $stats->en_uso ?? 0;
    $mantenimiento_count = $stats->mantenimiento ?? 0;
    $km_totales_flota = $stats->km_totales ?? 0;

    // Conteo por tipo
    $tipos_result = $wpdb->get_results("
        SELECT tipo, COUNT(*) as cantidad
        FROM $tabla_bicicletas
        GROUP BY tipo
    ");

    $por_tipo = ['urbana' => 0, 'electrica' => 0, 'montana' => 0, 'plegable' => 0];
    foreach ($tipos_result as $t) {
        $por_tipo[$t->tipo] = $t->cantidad;
    }

    // Estaciones para filtro
    $estaciones = $tabla_estaciones_existe
        ? $wpdb->get_results("SELECT id, nombre FROM $tabla_estaciones ORDER BY nombre ASC")
        : [];
} else {
    $total_bicicletas_filtradas = 0;
    $bicicletas = [];
    $total_bicicletas = 0;
    $disponibles = 0;
    $en_uso = 0;
    $mantenimiento_count = 0;
    $km_totales_flota = 0;
    $por_tipo = ['urbana' => 0, 'electrica' => 0, 'montana' => 0, 'plegable' => 0];
    $estaciones = [];
}

$total_paginas = ceil($total_bicicletas_filtradas / $items_por_pagina);

// Top bicicletas por uso (para sidebar)
if ($tablas_bicicletas_disponibles) {
    $top_bicicletas = $wpdb->get_results("
        SELECT b.*, e.nombre as nombre_estacion,
               COALESCE(b.{$col_km}, 0) as kilometros_totales,
               b.{$col_revision} as fecha_ultimo_mantenimiento,
               (SELECT COUNT(*) FROM $tabla_prestamos WHERE bicicleta_id = b.id) as total_prestamos
        FROM $tabla_bicicletas b
        LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
        ORDER BY total_prestamos DESC
        LIMIT 5
    ");
} else {
    $top_bicicletas = [];
}
?>

<div class="wrap flavor-bicicletas-dashboard">

    <!-- Encabezado -->
    <div class="flavor-dashboard-header">
        <div class="flavor-header-content">
            <h1>
                <span class="dashicons dashicons-location-alt"></span>
                <?php echo esc_html__('Gestión de Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="flavor-header-descripcion">
                <?php echo esc_html__('Administra la flota de bicicletas compartidas del sistema.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="flavor-header-acciones">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=nueva')); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php echo esc_html__('Nueva Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <?php if (!$tablas_bicicletas_disponibles): ?>
    <div class="notice notice-info" style="margin: 20px 0;">
        <p>
            <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
            <strong><?php echo esc_html__('Sin datos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <?php echo esc_html__('No hay tablas de bicicletas disponibles todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card" style="--gradient-start: #667eea; --gradient-end: #764ba2;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-location-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($total_bicicletas); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Flota', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #11998e; --gradient-end: #38ef7d;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($disponibles); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #4776e6; --gradient-end: #8e54e9;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($en_uso); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('En Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="--gradient-start: #fc4a1a; --gradient-end: #f7b733;">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-numero"><?php echo number_format($mantenimiento_count); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout Principal -->
    <div class="flavor-main-layout">

        <!-- Contenido Principal -->
        <div class="flavor-content-area">

            <!-- Filtros -->
            <div class="flavor-filtros-card">
                <form method="get" class="flavor-filtros-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'flavor-bicicletas-bicicletas'); ?>">

                    <div class="flavor-filtro-grupo">
                        <label for="busqueda">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="text" id="busqueda" name="busqueda"
                               value="<?php echo esc_attr($busqueda); ?>"
                               placeholder="<?php echo esc_attr__('Código o modelo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="estado">
                            <span class="dashicons dashicons-flag"></span>
                            <?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="estado" name="estado">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="disponible" <?php selected($filtro_estado, 'disponible'); ?>><?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="en_uso" <?php selected($filtro_estado, 'en_uso'); ?>><?php echo esc_html__('En Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="mantenimiento" <?php selected($filtro_estado, 'mantenimiento'); ?>><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="fuera_servicio" <?php selected($filtro_estado, 'fuera_servicio'); ?>><?php echo esc_html__('Fuera Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="reservada" <?php selected($filtro_estado, 'reservada'); ?>><?php echo esc_html__('Reservada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="tipo">
                            <span class="dashicons dashicons-category"></span>
                            <?php echo esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="tipo" name="tipo">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="urbana" <?php selected($filtro_tipo, 'urbana'); ?>><?php echo esc_html__('Urbana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="electrica" <?php selected($filtro_tipo, 'electrica'); ?>><?php echo esc_html__('Eléctrica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="montana" <?php selected($filtro_tipo, 'montana'); ?>><?php echo esc_html__('Montaña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="plegable" <?php selected($filtro_tipo, 'plegable'); ?>><?php echo esc_html__('Plegable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="estacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html__('Estación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="estacion" name="estacion">
                            <option value=""><?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($estaciones as $est): ?>
                                <option value="<?php echo esc_attr($est->id); ?>" <?php selected($filtro_estacion, $est->id); ?>>
                                    <?php echo esc_html($est->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="orden">
                            <span class="dashicons dashicons-sort"></span>
                            <?php echo esc_html__('Ordenar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="orden" name="orden">
                            <option value="codigo_asc" <?php selected($orden, 'codigo_asc'); ?>><?php echo esc_html__('Código A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="codigo_desc" <?php selected($orden, 'codigo_desc'); ?>><?php echo esc_html__('Código Z-A', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="km_desc" <?php selected($orden, 'km_desc'); ?>><?php echo esc_html__('Más km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="km_asc" <?php selected($orden, 'km_asc'); ?>><?php echo esc_html__('Menos km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="prestamos_desc" <?php selected($orden, 'prestamos_desc'); ?>><?php echo esc_html__('Más usadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="modelo_asc" <?php selected($orden, 'modelo_asc'); ?>><?php echo esc_html__('Modelo A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-acciones">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-filter"></span>
                            <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'flavor-bicicletas-bicicletas'))); ?>" class="button">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Información de Resultados -->
            <div class="flavor-resultados-info">
                <span class="flavor-resultados-contador">
                    <?php
                    printf(
                        esc_html__('Mostrando %1$d-%2$d de %3$d bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        min($offset + 1, $total_bicicletas_filtradas),
                        min($offset + $items_por_pagina, $total_bicicletas_filtradas),
                        $total_bicicletas_filtradas
                    );
                    ?>
                </span>
                <span class="flavor-km-totales">
                    <span class="dashicons dashicons-performance"></span>
                    <?php printf(esc_html__('%s km recorridos', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($km_totales_flota, 1)); ?>
                </span>
            </div>

            <!-- Grid de Bicicletas -->
            <?php if (empty($bicicletas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-location-alt"></span>
                    <h3><?php echo esc_html__('No hay bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php echo esc_html__('No se encontraron bicicletas con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=nueva')); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php echo esc_html__('Añadir Primera Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-bicicletas-grid">
                    <?php foreach ($bicicletas as $bicicleta):
                        $estado_badge = obtener_badge_estado_bicicleta($bicicleta->estado);
                        $tipo_info = obtener_tipo_bicicleta($bicicleta->tipo ?? 'urbana');
                        $mantenimiento_info = calcular_estado_mantenimiento($bicicleta->fecha_ultimo_mantenimiento ?? null, $bicicleta->kilometros_totales);
                    ?>
                        <div class="flavor-bicicleta-card">
                            <!-- Header con código y tipo -->
                            <div class="flavor-bicicleta-header" style="--tipo-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                                <div class="flavor-bicicleta-codigo">
                                    <span class="flavor-bicicleta-icono"><?php echo $tipo_info['icono']; ?></span>
                                    <strong><?php echo esc_html($bicicleta->codigo); ?></strong>
                                </div>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_badge['clase']); ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($estado_badge['icono']); ?>"></span>
                                    <?php echo esc_html($estado_badge['texto']); ?>
                                </span>
                            </div>

                            <!-- Contenido -->
                            <div class="flavor-bicicleta-content">
                                <h3 class="flavor-bicicleta-modelo">
                                    <?php echo esc_html($bicicleta->modelo ?? __('Sin modelo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </h3>

                                <div class="flavor-bicicleta-tipo">
                                    <span class="flavor-tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>15; color: <?php echo esc_attr($tipo_info['color']); ?>;">
                                        <?php echo esc_html($tipo_info['texto']); ?>
                                    </span>
                                </div>

                                <!-- Ubicación -->
                                <div class="flavor-bicicleta-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php if ($bicicleta->nombre_estacion): ?>
                                        <span><?php echo esc_html($bicicleta->nombre_estacion); ?></span>
                                    <?php else: ?>
                                        <span class="flavor-sin-estacion"><?php echo esc_html__('En circulación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Estadísticas -->
                                <div class="flavor-bicicleta-stats">
                                    <div class="flavor-stat-item">
                                        <span class="dashicons dashicons-performance"></span>
                                        <span class="valor"><?php echo number_format($bicicleta->kilometros_totales ?? 0, 1); ?> km</span>
                                    </div>
                                    <div class="flavor-stat-item">
                                        <span class="dashicons dashicons-groups"></span>
                                        <span class="valor"><?php echo number_format($bicicleta->total_prestamos ?? 0); ?> usos</span>
                                    </div>
                                    <?php if (isset($bicicleta->bateria) && $bicicleta->bateria !== null): ?>
                                        <div class="flavor-stat-item flavor-bateria">
                                            <span class="dashicons dashicons-lightbulb"></span>
                                            <span class="valor"><?php echo intval($bicicleta->bateria); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Estado de mantenimiento -->
                                <div class="flavor-mantenimiento-estado">
                                    <span class="flavor-mantenimiento-label"><?php echo esc_html__('Mantenimiento:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="flavor-mantenimiento-valor" style="color: <?php echo esc_attr($mantenimiento_info['color']); ?>;">
                                        <?php echo esc_html($mantenimiento_info['texto']); ?>
                                    </span>
                                </div>

                                <!-- Fecha último mantenimiento -->
                                <div class="flavor-bicicleta-meta">
                                    <?php if ($bicicleta->fecha_ultimo_mantenimiento): ?>
                                        <span>
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo date_i18n('d/m/Y', strtotime($bicicleta->fecha_ultimo_mantenimiento)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="flavor-sin-mantenimiento">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php echo esc_html__('Sin registro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Acciones -->
                                <div class="flavor-bicicleta-acciones">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=ver&bicicleta_id=' . $bicicleta->id)); ?>" class="button" title="<?php echo esc_attr__('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                    <?php if ($bicicleta->estado !== 'en_uso'): ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento&bicicleta_id=' . $bicicleta->id)); ?>" class="button button-primary" title="<?php echo esc_attr__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-admin-tools"></span>
                                            <?php echo esc_html__('Mant.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-paginacion">
                        <?php
                        $paginacion_args = [
                            'base' => add_query_arg('pag', '%#%'),
                            'format' => '',
                            'current' => $pagina_actual,
                            'total' => $total_paginas,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'next_text' => __('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'type' => 'list',
                            'end_size' => 1,
                            'mid_size' => 2,
                        ];

                        // Preservar filtros en paginación
                        if (!empty($busqueda)) $paginacion_args['add_args']['busqueda'] = $busqueda;
                        if (!empty($filtro_estado)) $paginacion_args['add_args']['estado'] = $filtro_estado;
                        if (!empty($filtro_tipo)) $paginacion_args['add_args']['tipo'] = $filtro_tipo;
                        if ($filtro_estacion > 0) $paginacion_args['add_args']['estacion'] = $filtro_estacion;
                        if (!empty($orden) && $orden !== 'codigo_asc') $paginacion_args['add_args']['orden'] = $orden;

                        echo paginate_links($paginacion_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="flavor-sidebar">

            <!-- Top Bicicletas -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php echo esc_html__('Más Utilizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-top-lista">
                    <?php
                    $posicion = 0;
                    foreach ($top_bicicletas as $top_bici):
                        $posicion++;
                        $tipo_top = obtener_tipo_bicicleta($top_bici->tipo ?? 'urbana');
                    ?>
                        <div class="flavor-top-item">
                            <span class="flavor-top-posicion <?php echo $posicion <= 3 ? 'top-' . $posicion : ''; ?>">
                                <?php echo $posicion; ?>
                            </span>
                            <div class="flavor-top-info">
                                <span class="flavor-top-nombre">
                                    <?php echo $tipo_top['icono']; ?>
                                    <?php echo esc_html($top_bici->codigo); ?>
                                </span>
                                <span class="flavor-top-stats">
                                    <?php echo esc_html($top_bici->modelo ?? ''); ?>
                                </span>
                            </div>
                            <span class="flavor-top-items">
                                <?php echo number_format($top_bici->total_prestamos ?? 0); ?>
                                <small><?php echo esc_html__('usos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gráfico por Tipo -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-chart-container">
                    <canvas id="chartTipoBicicleta" width="200" height="200"></canvas>
                </div>
                <div class="flavor-chart-leyenda">
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #3b82f6;"></span>
                        <span class="flavor-leyenda-texto">🚲 <?php echo esc_html__('Urbana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($por_tipo['urbana']); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #10b981;"></span>
                        <span class="flavor-leyenda-texto">⚡ <?php echo esc_html__('Eléctrica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($por_tipo['electrica']); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #8b5cf6;"></span>
                        <span class="flavor-leyenda-texto">🏔️ <?php echo esc_html__('Montaña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($por_tipo['montana']); ?></span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-color" style="background: #f59e0b;"></span>
                        <span class="flavor-leyenda-texto">📦 <?php echo esc_html__('Plegable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-leyenda-valor"><?php echo number_format($por_tipo['plegable']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Leyenda de Estados -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php echo esc_html__('Estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-estados-lista">
                    <div class="flavor-estado-item">
                        <span class="flavor-badge flavor-badge-success"><span class="dashicons dashicons-yes-alt"></span></span>
                        <span><?php echo esc_html__('Disponible - Lista para usar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-estado-item">
                        <span class="flavor-badge flavor-badge-info"><span class="dashicons dashicons-businessman"></span></span>
                        <span><?php echo esc_html__('En uso - Actualmente prestada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-estado-item">
                        <span class="flavor-badge flavor-badge-warning"><span class="dashicons dashicons-admin-tools"></span></span>
                        <span><?php echo esc_html__('Mantenimiento - En revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-estado-item">
                        <span class="flavor-badge flavor-badge-danger"><span class="dashicons dashicons-dismiss"></span></span>
                        <span><?php echo esc_html__('Fuera servicio - No operativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="flavor-sidebar-card">
                <h3 class="flavor-sidebar-titulo">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html__('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-acciones-rapidas">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=nueva')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php echo esc_html__('Añadir Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html__('Ver Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-prestamos')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php echo esc_html__('Ver Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento')); ?>" class="flavor-accion-btn">
                        <span class="dashicons dashicons-hammer"></span>
                        <?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de tipo de bicicleta
    const ctxTipo = document.getElementById('chartTipoBicicleta');
    if (ctxTipo) {
        new Chart(ctxTipo.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Urbana', 'Eléctrica', 'Montaña', 'Plegable'],
                datasets: [{
                    data: [
                        <?php echo intval($por_tipo['urbana']); ?>,
                        <?php echo intval($por_tipo['electrica']); ?>,
                        <?php echo intval($por_tipo['montana']); ?>,
                        <?php echo intval($por_tipo['plegable']); ?>
                    ],
                    backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const porcentaje = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.raw + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
/* =====================================================
   ESTILOS GENERALES
   ===================================================== */
.flavor-bicicletas-dashboard {
    max-width: 1800px;
    margin: 0 auto;
}

/* Header */
.flavor-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 20px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: #fff;
}

.flavor-dashboard-header h1 {
    color: #fff;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
}

.flavor-dashboard-header h1 .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-header-descripcion {
    margin: 8px 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.flavor-header-acciones .button-hero {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.5);
    color: #fff;
    padding: 10px 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.flavor-header-acciones .button-hero:hover {
    background: rgba(255,255,255,0.3);
    border-color: #fff;
    color: #fff;
}

/* =====================================================
   TARJETAS DE ESTADÍSTICAS
   ===================================================== */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.flavor-stat-card {
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.flavor-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.flavor-stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-stat-content {
    display: flex;
    flex-direction: column;
}

.flavor-stat-numero {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}

.flavor-stat-label {
    font-size: 13px;
    opacity: 0.9;
}

/* =====================================================
   LAYOUT PRINCIPAL
   ===================================================== */
.flavor-main-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
}

@media (max-width: 1200px) {
    .flavor-main-layout {
        grid-template-columns: 1fr;
    }

    .flavor-sidebar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
}

/* =====================================================
   FILTROS
   ===================================================== */
.flavor-filtros-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.flavor-filtros-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.flavor-filtro-grupo {
    flex: 1;
    min-width: 140px;
}

.flavor-filtro-grupo label {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 12px;
    color: #374151;
    text-transform: uppercase;
}

.flavor-filtro-grupo label .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #6b7280;
}

.flavor-filtro-grupo input,
.flavor-filtro-grupo select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-filtro-grupo input:focus,
.flavor-filtro-grupo select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.flavor-filtro-acciones {
    display: flex;
    gap: 10px;
}

.flavor-filtro-acciones .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 16px;
}

/* =====================================================
   RESULTADOS INFO
   ===================================================== */
.flavor-resultados-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 15px;
    background: #f8fafc;
    border-radius: 8px;
    font-size: 13px;
}

.flavor-resultados-contador {
    color: #6b7280;
}

.flavor-km-totales {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #667eea;
    font-weight: 500;
}

/* =====================================================
   GRID DE BICICLETAS
   ===================================================== */
.flavor-bicicletas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.flavor-bicicleta-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.flavor-bicicleta-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

/* Header */
.flavor-bicicleta-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: linear-gradient(135deg, var(--tipo-color, #667eea), color-mix(in srgb, var(--tipo-color, #667eea), black 20%));
    color: #fff;
}

.flavor-bicicleta-codigo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.flavor-bicicleta-icono {
    font-size: 20px;
}

/* Contenido */
.flavor-bicicleta-content {
    padding: 15px;
}

.flavor-bicicleta-modelo {
    margin: 0 0 8px;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.flavor-bicicleta-tipo {
    margin-bottom: 10px;
}

.flavor-tipo-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-bicicleta-ubicacion {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 12px;
    font-size: 13px;
    color: #6b7280;
}

.flavor-bicicleta-ubicacion .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #ef4444;
}

.flavor-sin-estacion {
    font-style: italic;
    color: #9ca3af;
}

/* Stats */
.flavor-bicicleta-stats {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.flavor-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6b7280;
}

.flavor-stat-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-stat-item .valor {
    font-weight: 500;
}

.flavor-bateria .valor {
    color: #10b981;
}

/* Mantenimiento */
.flavor-mantenimiento-estado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-top: 1px solid #f0f0f1;
    font-size: 12px;
}

.flavor-mantenimiento-label {
    color: #9ca3af;
}

.flavor-mantenimiento-valor {
    font-weight: 600;
}

/* Meta */
.flavor-bicicleta-meta {
    padding: 8px 0;
    font-size: 12px;
    color: #9ca3af;
}

.flavor-bicicleta-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-sin-mantenimiento {
    color: #ef4444;
}

/* Acciones */
.flavor-bicicleta-acciones {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f1;
}

.flavor-bicicleta-acciones .button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 8px 12px;
    font-size: 12px;
    text-decoration: none;
}

/* =====================================================
   BADGES
   ===================================================== */
.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

.flavor-badge-info {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
}

.flavor-badge-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
}

.flavor-badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.flavor-badge-secondary {
    background: rgba(107, 114, 128, 0.15);
    color: #4b5563;
}

/* =====================================================
   EMPTY STATE
   ===================================================== */
.flavor-empty-state {
    text-align: center;
    padding: 60px 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.flavor-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #d1d5db;
    margin-bottom: 15px;
}

.flavor-empty-state h3 {
    margin: 0 0 10px;
    color: #374151;
}

.flavor-empty-state p {
    color: #6b7280;
    margin: 0 0 20px;
}

/* =====================================================
   PAGINACIÓN
   ===================================================== */
.flavor-paginacion {
    margin-top: 25px;
    display: flex;
    justify-content: center;
}

.flavor-paginacion ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.flavor-paginacion li {
    margin: 0;
}

.flavor-paginacion a,
.flavor-paginacion span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s ease;
}

.flavor-paginacion a:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

.flavor-paginacion .current {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

/* =====================================================
   SIDEBAR
   ===================================================== */
.flavor-sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.flavor-sidebar-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.flavor-sidebar-titulo .dashicons {
    color: #667eea;
}

/* Top Lista */
.flavor-top-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
}

.flavor-top-posicion {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
    border-radius: 50%;
    font-weight: 700;
    font-size: 12px;
    color: #6b7280;
}

.flavor-top-posicion.top-1 {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #fff;
}

.flavor-top-posicion.top-2 {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    color: #fff;
}

.flavor-top-posicion.top-3 {
    background: linear-gradient(135deg, #cd7f32, #a0522d);
    color: #fff;
}

.flavor-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-top-nombre {
    display: block;
    font-weight: 500;
    font-size: 13px;
    color: #1f2937;
}

.flavor-top-stats {
    font-size: 11px;
    color: #9ca3af;
}

.flavor-top-items {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-weight: 700;
    font-size: 14px;
    color: #667eea;
}

.flavor-top-items small {
    font-weight: 400;
    font-size: 10px;
    color: #9ca3af;
}

/* Chart */
.flavor-chart-container {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.flavor-chart-leyenda {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
}

.flavor-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

.flavor-leyenda-texto {
    flex: 1;
    color: #6b7280;
}

.flavor-leyenda-valor {
    font-weight: 600;
    color: #1f2937;
}

/* Estados Lista */
.flavor-estados-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-estado-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    color: #6b7280;
}

.flavor-estado-item .flavor-badge {
    padding: 4px 6px;
}

/* Acciones Rápidas */
.flavor-acciones-rapidas {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-accion-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.flavor-accion-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

.flavor-accion-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* =====================================================
   RESPONSIVE
   ===================================================== */
@media (max-width: 782px) {
    .flavor-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .flavor-filtros-form {
        flex-direction: column;
    }

    .flavor-filtro-grupo {
        width: 100%;
    }

    .flavor-bicicletas-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
/**
 * Vista de Gestión de Estaciones - Bicicletas Compartidas
 *
 * Dashboard mejorado con estadísticas, filtros avanzados, paginación
 * y visualización de datos.
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN));

global $wpdb;

// =====================================================
// CONFIGURACIÓN Y VERIFICACIÓN DE TABLAS
// =====================================================

$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';
$tabla_bicicletas_alt = $wpdb->prefix . 'flavor_bicicletas';
$tabla_reservas = $wpdb->prefix . 'flavor_bicicletas_reservas';

// Verificar existencia de tablas
$tabla_estaciones_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_estaciones
    )
);

$tabla_bicicletas_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_bicicletas
    )
);

$tabla_bicicletas_alt_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_bicicletas_alt
    )
);

if (!$tabla_bicicletas_existe && $tabla_bicicletas_alt_existe) {
    $tabla_bicicletas = $tabla_bicicletas_alt;
    $tabla_bicicletas_existe = true;
}

$tablas_estaciones_disponibles = (bool) $tabla_estaciones_existe;
$col_zona = null;

// =====================================================
// FUNCIONES HELPER
// =====================================================

/**
 * Obtener badge de estado de estación
 */
function obtener_badge_estado_estacion($estado) {
    $estados = [
        'activa' => [
            'clase' => 'success',
            'texto' => __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'yes-alt'
        ],
        'mantenimiento' => [
            'clase' => 'warning',
            'texto' => __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'admin-tools'
        ],
        'inactiva' => [
            'clase' => 'secondary',
            'texto' => __('Inactiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'marker'
        ],
        'fuera_servicio' => [
            'clase' => 'danger',
            'texto' => __('Fuera de servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'warning'
        ]
    ];

    $estado_key = strtolower($estado);
    return $estados[$estado_key] ?? ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Calcular nivel de ocupación
 */
function calcular_nivel_ocupacion($bicicletas, $capacidad) {
    if ($capacidad <= 0) return ['nivel' => 'vacia', 'texto' => __('Sin capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#6b7280'];

    $porcentaje = ($bicicletas / $capacidad) * 100;

    if ($porcentaje >= 80) {
        return ['nivel' => 'llena', 'texto' => __('Casi llena', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ef4444'];
    } elseif ($porcentaje >= 50) {
        return ['nivel' => 'media', 'texto' => __('Disponibilidad media', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b'];
    } elseif ($porcentaje >= 20) {
        return ['nivel' => 'buena', 'texto' => __('Buena disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#10b981'];
    } elseif ($porcentaje > 0) {
        return ['nivel' => 'baja', 'texto' => __('Pocas bicis', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f59e0b'];
    }
    return ['nivel' => 'vacia', 'texto' => __('Sin bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ef4444'];
}

/**
 * Calcular tipo de estación según capacidad
 */
function obtener_tipo_estacion($capacidad) {
    if ($capacidad >= 30) {
        return ['tipo' => 'grande', 'texto' => __('Grande', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'building'];
    } elseif ($capacidad >= 15) {
        return ['tipo' => 'mediana', 'texto' => __('Mediana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'store'];
    }
    return ['tipo' => 'pequena', 'texto' => __('Pequeña', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location'];
}

// =====================================================
// PARÁMETROS DE FILTRADO Y PAGINACIÓN
// =====================================================

$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_por_pagina = 12;
$offset = ($pagina_actual - 1) * $items_por_pagina;

$filtro_busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_zona = isset($_GET['zona']) ? sanitize_text_field($_GET['zona']) : '';
$filtro_disponibilidad = isset($_GET['disponibilidad']) ? sanitize_text_field($_GET['disponibilidad']) : '';
$filtro_orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'nombre_asc';

// =====================================================
// CONSULTA DE DATOS REALES O FILTRADO DEMO
// =====================================================

if ($tablas_estaciones_disponibles) {
    $col_capacidad = (int) $wpdb->get_var("SHOW COLUMNS FROM $tabla_estaciones LIKE 'capacidad_maxima'")
        ? 'capacidad_maxima'
        : 'capacidad_total';
    $col_zona = (int) $wpdb->get_var("SHOW COLUMNS FROM $tabla_estaciones LIKE 'zona'")
        ? 'zona'
        : null;

    // Construir query con filtros
    $where_clauses = ["1=1"];
    $params = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(e.nombre LIKE %s OR e.direccion LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $params[] = $busqueda_like;
        $params[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "e.estado = %s";
        $params[] = $filtro_estado;
    }

    if (!empty($filtro_zona) && $col_zona) {
        $where_clauses[] = "e.zona = %s";
        $params[] = $filtro_zona;
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Ordenamiento
    $order_sql = "e.nombre ASC";
    switch ($filtro_orden) {
        case 'nombre_desc':
            $order_sql = "e.nombre DESC";
            break;
        case 'capacidad_desc':
            $order_sql = "e.capacidad_maxima DESC";
            break;
        case 'bicicletas_desc':
            $order_sql = "bicicletas_actuales DESC";
            break;
        case 'reservas_desc':
            $order_sql = "reservas_mes DESC";
            break;
    }

    // Query de estadísticas
    $estadisticas = [
        'total_estaciones' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_estaciones"),
        'estaciones_activas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_estaciones WHERE estado = 'activa'"),
        'total_bicicletas' => $tabla_bicicletas_existe ? $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible'") : 0,
        'capacidad_total' => $wpdb->get_var("SELECT COALESCE(SUM($col_capacidad), 0) FROM $tabla_estaciones"),
        'ocupacion_promedio' => 0,
        'reservas_mes' => 0
    ];

    if ($estadisticas['capacidad_total'] > 0) {
        $estadisticas['ocupacion_promedio'] = round(($estadisticas['total_bicicletas'] / $estadisticas['capacidad_total']) * 100, 1);
    }

    // Query principal
    $query = "
        SELECT e.*,
               e.$col_capacidad as capacidad_maxima,
               " . ($col_zona ? "e.$col_zona" : "''") . " as zona,
               COUNT(b.id) as bicicletas_actuales,
               e.$col_capacidad - COUNT(b.id) as espacios_libres
        FROM $tabla_estaciones e
        LEFT JOIN $tabla_bicicletas b ON e.id = b.estacion_actual_id AND b.estado = 'disponible'
        WHERE $where_sql
        GROUP BY e.id
    ";

    // Filtro por disponibilidad (post-GROUP BY)
    $having_clauses = [];
    if (!empty($filtro_disponibilidad)) {
        switch ($filtro_disponibilidad) {
            case 'con_bicis':
                $having_clauses[] = "bicicletas_actuales > 0";
                break;
            case 'sin_bicis':
                $having_clauses[] = "bicicletas_actuales = 0";
                break;
            case 'llena':
                $having_clauses[] = "bicicletas_actuales >= e.$col_capacidad * 0.8";
                break;
        }
    }

    if (!empty($having_clauses)) {
        $query .= " HAVING " . implode(' AND ', $having_clauses);
    }

    // Conteo total
    $count_query = "SELECT COUNT(*) FROM ($query) as subquery";
    $total_items = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);

    // Query con orden y paginación
    $query .= " ORDER BY $order_sql LIMIT %d OFFSET %d";
    $params[] = $items_por_pagina;
    $params[] = $offset;

    $estaciones = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

    // Obtener zonas únicas para filtro
    $zonas = $col_zona
        ? $wpdb->get_col("SELECT DISTINCT $col_zona FROM $tabla_estaciones WHERE $col_zona IS NOT NULL AND $col_zona != '' ORDER BY $col_zona")
        : [];
} else {
    $estadisticas = [
        'total_estaciones' => 0,
        'estaciones_activas' => 0,
        'total_bicicletas' => 0,
        'capacidad_total' => 0,
        'ocupacion_promedio' => 0,
        'reservas_mes' => 0,
    ];
    $total_items = 0;
    $estaciones = [];
    $zonas = [];
}

$total_paginas = ceil($total_items / $items_por_pagina);

// Top estaciones para sidebar
$top_estaciones = $tablas_estaciones_disponibles
    ? $wpdb->get_results("
        SELECT e.*, COUNT(b.id) as bicicletas_actuales
        FROM $tabla_estaciones e
        LEFT JOIN $tabla_bicicletas b ON e.id = b.estacion_actual_id
        WHERE e.estado = 'activa'
        GROUP BY e.id
        ORDER BY bicicletas_actuales DESC
        LIMIT 5
    ")
    : [];

// Distribución por zona para gráfico
$distribucion_zona = [];
if ($tablas_estaciones_disponibles && $col_zona) {
    $resultados = $wpdb->get_results("SELECT $col_zona as zona, COUNT(*) as total FROM $tabla_estaciones GROUP BY $col_zona");
    foreach ($resultados as $r) {
        $distribucion_zona[$r->zona] = $r->total;
    }
}
?>

<div class="wrap flavor-bicicletas-estaciones">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-location"></span>
        <?php esc_html_e('Gestión de Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=nueva')); ?>" class="page-title-action">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Nueva Estación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <?php if (!$tablas_estaciones_disponibles): ?>
    <div class="notice notice-info inline" style="margin: 15px 0;">
        <p>
            <span class="dashicons dashicons-info"></span>
            <?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Bicicletas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-location"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_estaciones']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Total Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['estaciones_activas']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-screenoptions"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['total_bicicletas']); ?></span>
                <span class="flavor-stat-label"><?php echo esc_html__('Bicicletas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($estadisticas['ocupacion_promedio'], 1); ?>%</span>
                <span class="flavor-stat-label"><?php echo esc_html__('Ocupación Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout principal con sidebar -->
    <div class="flavor-main-layout">
        <div class="flavor-main-content">
            <!-- Filtros -->
            <div class="flavor-filtros-card">
                <form method="get" class="flavor-filtros-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'flavor-bicicletas-estaciones'); ?>">

                    <div class="flavor-filtro-grupo">
                        <label for="buscar"><?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="buscar" name="buscar" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre, dirección...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="estado"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="estado" name="estado">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="activa" <?php selected($filtro_estado, 'activa'); ?>><?php echo esc_html__('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="mantenimiento" <?php selected($filtro_estado, 'mantenimiento'); ?>><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="inactiva" <?php selected($filtro_estado, 'inactiva'); ?>><?php echo esc_html__('Inactiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="zona"><?php echo esc_html__('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="zona" name="zona">
                            <option value=""><?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?php echo esc_attr($zona); ?>" <?php selected($filtro_zona, $zona); ?>>
                                    <?php echo esc_html($zona); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="disponibilidad"><?php echo esc_html__('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="disponibilidad" name="disponibilidad">
                            <option value=""><?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="con_bicis" <?php selected($filtro_disponibilidad, 'con_bicis'); ?>><?php echo esc_html__('Con bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="sin_bicis" <?php selected($filtro_disponibilidad, 'sin_bicis'); ?>><?php echo esc_html__('Sin bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="llena" <?php selected($filtro_disponibilidad, 'llena'); ?>><?php echo esc_html__('Casi llenas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-grupo">
                        <label for="orden"><?php echo esc_html__('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="orden" name="orden">
                            <option value="nombre_asc" <?php selected($filtro_orden, 'nombre_asc'); ?>><?php echo esc_html__('Nombre A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="nombre_desc" <?php selected($filtro_orden, 'nombre_desc'); ?>><?php echo esc_html__('Nombre Z-A', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="bicicletas_desc" <?php selected($filtro_orden, 'bicicletas_desc'); ?>><?php echo esc_html__('Más bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="capacidad_desc" <?php selected($filtro_orden, 'capacidad_desc'); ?>><?php echo esc_html__('Mayor capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filtro-acciones">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_GET['page'] ?? 'flavor-bicicletas-estaciones'))); ?>" class="button">
                            <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Mapa placeholder -->
            <div class="flavor-mapa-card">
                <h3>
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php echo esc_html__('Mapa de Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-mapa-placeholder">
                    <span class="dashicons dashicons-location-alt"></span>
                    <p><?php echo esc_html__('Integración de Google Maps disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <!-- Contador de resultados -->
            <div class="flavor-resultados-info">
                <span>
                    <?php
                    printf(
                        esc_html__('Mostrando %d-%d de %d estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        min($offset + 1, $total_items),
                        min($offset + $items_por_pagina, $total_items),
                        $total_items
                    );
                    ?>
                </span>
            </div>

            <!-- Grid de estaciones -->
            <?php if (empty($estaciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-location"></span>
                    <h3><?php echo esc_html__('No se encontraron estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php echo esc_html__('Intenta ajustar los filtros o añade una nueva estación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-estaciones-grid">
                    <?php foreach ($estaciones as $estacion):
                        $badge_estado = obtener_badge_estado_estacion($estacion->estado);
                        $ocupacion = $estacion->capacidad_maxima > 0
                            ? round(($estacion->bicicletas_actuales / $estacion->capacidad_maxima) * 100)
                            : 0;
                        $nivel_ocupacion = calcular_nivel_ocupacion($estacion->bicicletas_actuales, $estacion->capacidad_maxima);
                        $tipo_estacion = obtener_tipo_estacion($estacion->capacidad_maxima);
                    ?>
                        <div class="flavor-estacion-card <?php echo $estacion->estado !== 'activa' ? 'flavor-estacion-inactiva' : ''; ?>" data-id="<?php echo esc_attr($estacion->id); ?>">
                            <div class="flavor-estacion-header">
                                <div class="flavor-estacion-icon" style="background-color: <?php echo esc_attr($nivel_ocupacion['color']); ?>;">
                                    <span class="dashicons dashicons-location"></span>
                                </div>
                                <div class="flavor-estacion-badges">
                                    <span class="flavor-badge flavor-badge-estado-<?php echo esc_attr($badge_estado['clase']); ?>">
                                        <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                        <?php echo esc_html($badge_estado['texto']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="flavor-estacion-body">
                                <h3 class="flavor-estacion-nombre"><?php echo esc_html($estacion->nombre); ?></h3>

                                <div class="flavor-estacion-direccion">
                                    <span class="dashicons dashicons-location-alt"></span>
                                    <?php echo esc_html($estacion->direccion); ?>
                                </div>

                                <?php if (!empty($estacion->zona)): ?>
                                    <span class="flavor-estacion-zona">
                                        <span class="dashicons dashicons-tag"></span>
                                        <?php echo esc_html($estacion->zona); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Stats de bicicletas -->
                                <div class="flavor-estacion-stats">
                                    <div class="flavor-stat-bicis">
                                        <div class="flavor-bicis-numero">
                                            <span class="flavor-bicis-actual"><?php echo intval($estacion->bicicletas_actuales); ?></span>
                                            <span class="flavor-bicis-separador">/</span>
                                            <span class="flavor-bicis-capacidad"><?php echo intval($estacion->capacidad_maxima); ?></span>
                                        </div>
                                        <span class="flavor-bicis-label"><?php echo esc_html__('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    </div>
                                    <div class="flavor-stat-espacios">
                                        <span class="flavor-espacios-numero"><?php echo intval($estacion->espacios_libres); ?></span>
                                        <span class="flavor-espacios-label"><?php echo esc_html__('Espacios libres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    </div>
                                </div>

                                <!-- Barra de ocupación -->
                                <div class="flavor-ocupacion">
                                    <div class="flavor-ocupacion-header">
                                        <span><?php echo esc_html__('Ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <strong><?php echo $ocupacion; ?>%</strong>
                                    </div>
                                    <div class="flavor-progress-bar">
                                        <div class="flavor-progress-fill" style="width: <?php echo $ocupacion; ?>%; background-color: <?php echo esc_attr($nivel_ocupacion['color']); ?>;"></div>
                                    </div>
                                    <span class="flavor-ocupacion-nivel" style="color: <?php echo esc_attr($nivel_ocupacion['color']); ?>;">
                                        <?php echo esc_html($nivel_ocupacion['texto']); ?>
                                    </span>
                                </div>

                                <!-- Info adicional -->
                                <?php if (!empty($estacion->horario)): ?>
                                <div class="flavor-estacion-horario">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($estacion->horario); ?>
                                </div>
                                <?php endif; ?>

                                <!-- Coordenadas -->
                                <?php if (!empty($estacion->coordenadas_lat) && !empty($estacion->coordenadas_lng)): ?>
                                <a href="https://www.google.com/maps?q=<?php echo esc_attr($estacion->coordenadas_lat); ?>,<?php echo esc_attr($estacion->coordenadas_lng); ?>" target="_blank" class="flavor-ver-mapa">
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <?php echo esc_html__('Ver en Google Maps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-estacion-footer">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=ver&estacion_id=' . $estacion->id)); ?>" class="button button-small">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=editar&estacion_id=' . $estacion->id)); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php echo esc_html__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-paginacion">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $pagina_actual,
                            'total' => $total_paginas,
                            'prev_text' => '&laquo; ' . __('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'next_text' => __('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' &raquo;',
                            'type' => 'list'
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="flavor-sidebar">
            <!-- Top Estaciones -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php echo esc_html__('Estaciones con más bicis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <?php if (!empty($top_estaciones)): ?>
                    <ul class="flavor-top-list">
                        <?php foreach ($top_estaciones as $index => $top): ?>
                            <li>
                                <span class="flavor-top-posicion"><?php echo $index + 1; ?></span>
                                <div class="flavor-top-info">
                                    <strong><?php echo esc_html($top->nombre); ?></strong>
                                    <span><?php echo intval($top->bicicletas_actuales); ?> / <?php echo intval($top->capacidad_maxima); ?> bicis</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="flavor-no-data"><?php echo esc_html__('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>

            <!-- Gráfico por Zona -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Por Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <canvas id="graficoZonas" height="200"></canvas>
            </div>

            <!-- Leyenda de estados -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php echo esc_html__('Estados de Estación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <ul class="flavor-leyenda-estados">
                    <li>
                        <span class="flavor-badge flavor-badge-estado-success"><span class="dashicons dashicons-yes-alt"></span> Activa</span>
                        <small><?php echo esc_html__('En funcionamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-estado-warning"><span class="dashicons dashicons-admin-tools"></span> Mantenimiento</span>
                        <small><?php echo esc_html__('En reparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </li>
                    <li>
                        <span class="flavor-badge flavor-badge-estado-secondary"><span class="dashicons dashicons-marker"></span> Inactiva</span>
                        <small><?php echo esc_html__('Temporalmente cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </li>
                </ul>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-sidebar-card">
                <h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html__('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=flavor-bicicletas-bicicletas'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-screenoptions"></span>
                        <?php echo esc_html__('Ver Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-bicicletas-reservas'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html__('Ver Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Variables */
:root {
    --flavor-primary: #2271b1;
    --flavor-success: #00a32a;
    --flavor-warning: #dba617;
    --flavor-danger: #d63638;
    --flavor-info: #72aee6;
    --flavor-secondary: #787c82;
}

.flavor-bicicletas-estaciones {
    max-width: 1600px;
}

.flavor-bicicletas-estaciones .page-title-action {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Stats Grid */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.flavor-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.flavor-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-stat-content {
    display: flex;
    flex-direction: column;
}

.flavor-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1.2;
}

.flavor-stat-label {
    font-size: 13px;
    color: #646970;
}

/* Main Layout */
.flavor-main-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
    margin-top: 20px;
}

@media (max-width: 1200px) {
    .flavor-main-layout {
        grid-template-columns: 1fr;
    }
}

/* Filtros */
.flavor-filtros-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-filtros-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.flavor-filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 140px;
}

.flavor-filtro-grupo label {
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

.flavor-filtro-grupo input,
.flavor-filtro-grupo select {
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    font-size: 13px;
}

.flavor-filtro-acciones {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

.flavor-filtro-acciones .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Mapa placeholder */
.flavor-mapa-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-mapa-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px 0;
    font-size: 15px;
}

.flavor-mapa-placeholder {
    height: 200px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6366f1;
}

.flavor-mapa-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 10px;
}

/* Resultados info */
.flavor-resultados-info {
    padding: 10px 0;
    color: #646970;
    font-size: 13px;
}

/* Grid de estaciones */
.flavor-estaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.flavor-estacion-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-estacion-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.flavor-estacion-inactiva {
    opacity: 0.7;
}

.flavor-estacion-header {
    padding: 20px;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.flavor-estacion-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-estacion-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #fff;
}

.flavor-estacion-badges {
    display: flex;
    gap: 8px;
}

.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-badge-estado-success { background: rgba(0,163,42,0.15); color: #00a32a; }
.flavor-badge-estado-warning { background: rgba(219,166,23,0.15); color: #996800; }
.flavor-badge-estado-secondary { background: rgba(120,124,130,0.15); color: #787c82; }
.flavor-badge-estado-danger { background: rgba(214,54,56,0.15); color: #d63638; }

.flavor-estacion-body {
    padding: 20px;
}

.flavor-estacion-nombre {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #1d2327;
}

.flavor-estacion-direccion {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 13px;
    color: #646970;
    margin-bottom: 8px;
}

.flavor-estacion-direccion .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin-top: 2px;
}

.flavor-estacion-zona {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--flavor-primary);
    background: rgba(34,113,177,0.1);
    padding: 4px 10px;
    border-radius: 12px;
    margin-bottom: 15px;
}

.flavor-estacion-stats {
    display: flex;
    gap: 20px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 15px;
}

.flavor-stat-bicis,
.flavor-stat-espacios {
    text-align: center;
}

.flavor-bicis-numero {
    font-size: 24px;
    font-weight: 700;
    color: #1d2327;
}

.flavor-bicis-actual {
    color: var(--flavor-primary);
}

.flavor-bicis-separador {
    color: #dcdcde;
    margin: 0 2px;
}

.flavor-bicis-capacidad {
    color: #646970;
}

.flavor-bicis-label,
.flavor-espacios-label {
    display: block;
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    margin-top: 4px;
}

.flavor-espacios-numero {
    font-size: 24px;
    font-weight: 700;
    color: var(--flavor-success);
}

/* Barra de ocupación */
.flavor-ocupacion {
    margin-bottom: 15px;
}

.flavor-ocupacion-header {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 6px;
}

.flavor-ocupacion-header span {
    color: #646970;
}

.flavor-ocupacion-header strong {
    color: #1d2327;
}

.flavor-progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.flavor-ocupacion-nivel {
    display: block;
    font-size: 11px;
    margin-top: 6px;
    font-weight: 600;
}

.flavor-estacion-horario {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #646970;
    margin-bottom: 10px;
}

.flavor-ver-mapa {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--flavor-primary);
    text-decoration: none;
}

.flavor-ver-mapa:hover {
    text-decoration: underline;
}

.flavor-estacion-footer {
    padding: 15px 20px;
    background: #f9fafb;
    border-top: 1px solid #f0f0f1;
    display: flex;
    gap: 10px;
}

.flavor-estacion-footer .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Empty state */
.flavor-empty-state {
    background: #fff;
    border-radius: 12px;
    padding: 60px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #dcdcde;
}

.flavor-empty-state h3 {
    margin: 20px 0 10px;
    color: #1d2327;
}

.flavor-empty-state p {
    color: #646970;
}

/* Sidebar */
.flavor-sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-sidebar-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px 0;
    font-size: 15px;
    color: #1d2327;
}

.flavor-sidebar-card h3 .dashicons {
    color: var(--flavor-primary);
}

.flavor-top-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-top-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.flavor-top-list li:last-child {
    border-bottom: none;
}

.flavor-top-posicion {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    flex-shrink: 0;
}

.flavor-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-top-info strong {
    display: block;
    font-size: 13px;
    color: #1d2327;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-top-info span {
    font-size: 11px;
    color: #646970;
}

.flavor-leyenda-estados {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-leyenda-estados li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.flavor-leyenda-estados li:last-child {
    border-bottom: none;
}

.flavor-leyenda-estados small {
    font-size: 11px;
    color: #646970;
}

.flavor-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-quick-actions .button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.flavor-no-data {
    color: #646970;
    font-size: 13px;
    text-align: center;
    padding: 20px;
}

/* Paginación */
.flavor-paginacion {
    margin-top: 25px;
    display: flex;
    justify-content: center;
}

.flavor-paginacion .page-numbers {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.flavor-paginacion .page-numbers li a,
.flavor-paginacion .page-numbers li span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    color: #1d2327;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.flavor-paginacion .page-numbers li a:hover {
    background: var(--flavor-primary);
    border-color: var(--flavor-primary);
    color: #fff;
}

.flavor-paginacion .page-numbers li span.current {
    background: var(--flavor-primary);
    border-color: var(--flavor-primary);
    color: #fff;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico por zona
    const ctx = document.getElementById('graficoZonas');
    if (ctx) {
        const distribucion = <?php echo json_encode($distribucion_zona); ?>;
        const labels = Object.keys(distribucion);
        const data = Object.values(distribucion);

        const colores = [
            '#667eea', '#11998e', '#f093fb', '#fa709a', '#fee140',
            '#00c6ff', '#f857a6', '#4facfe', '#43e97b'
        ];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colores.slice(0, labels.length),
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
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>

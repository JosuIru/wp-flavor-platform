<?php
/**
 * Vista de Gestión de Propietarios - Parkings
 *
 * Gestión completa de propietarios de plazas de parking con estadísticas,
 * filtros avanzados, paginación y acciones de administración.
 *
 * @package FlavorChatIA
 * @subpackage Parkings
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// =============================================================================
// FUNCIONES AUXILIARES
// =============================================================================

/**
 * Obtener badge de estado del propietario
 */
function obtener_badge_estado_propietario($estado) {
    $estados = [
        'activo' => ['clase' => 'success', 'texto' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'yes-alt'],
        'inactivo' => ['clase' => 'secondary', 'texto' => __('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dismiss'],
        'pendiente' => ['clase' => 'warning', 'texto' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'clock'],
        'suspendido' => ['clase' => 'danger', 'texto' => __('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'warning'],
    ];

    if (isset($estados[$estado])) {
        return $estados[$estado];
    }

    return ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Calcular nivel de propietario según plazas y ocupación
 */
function calcular_nivel_propietario($total_plazas, $plazas_ocupadas) {
    if ($total_plazas >= 10) {
        return ['nivel' => __('Premium', FLAVOR_PLATFORM_TEXT_DOMAIN), 'clase' => 'premium'];
    } elseif ($total_plazas >= 5) {
        return ['nivel' => __('Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'clase' => 'avanzado'];
    } elseif ($total_plazas >= 2) {
        return ['nivel' => __('Estándar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'clase' => 'estandar'];
    } else {
        return ['nivel' => __('Básico', FLAVOR_PLATFORM_TEXT_DOMAIN), 'clase' => 'basico'];
    }
}

// =============================================================================
// CONFIGURACIÓN Y BASE DE DATOS
// =============================================================================

global $wpdb;
$tabla_propietarios = $wpdb->prefix . 'flavor_parkings_propietarios';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

// Verificar existencia de tablas
$tabla_propietarios_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_propietarios
    )
);

$tabla_plazas_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_plazas
    )
);

$tablas_parkings_disponibles = $tabla_propietarios_existe && $tabla_plazas_existe;

// =============================================================================
// PARÁMETROS DE PAGINACIÓN Y FILTROS
// =============================================================================

$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Filtros
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_plazas_min = isset($_GET['plazas_min']) ? intval($_GET['plazas_min']) : '';
$filtro_orden = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'total_plazas';
$filtro_orden_dir = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// =============================================================================
// DATOS DEMO O REALES
// =============================================================================

if ($tablas_parkings_disponibles) {
    // Datos reales de la base de datos

    // Estadísticas generales
    $total_propietarios = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_propietarios}");

    $propietarios_activos = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_propietarios} WHERE estado = 'activo'"
    );

    $total_plazas_sistema = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas}") ?: 1;

    $plazas_ocupadas_sistema = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'ocupada'"
    );

    $tasa_ocupacion = $total_plazas_sistema > 0 ? round(($plazas_ocupadas_sistema / $total_plazas_sistema) * 100) : 0;

    $fecha_inicio_mes = date('Y-m-01 00:00:00');
    $nuevos_este_mes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_propietarios} WHERE fecha_registro >= %s",
            $fecha_inicio_mes
        )
    );

    // Construir consulta con filtros
    $where_clauses = ["1=1"];
    $where_values = [];
    $having_clauses = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR p.telefono LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "p.estado = %s";
        $where_values[] = $filtro_estado;
    }

    if ($filtro_plazas_min !== '') {
        $having_clauses[] = "total_plazas >= %d";
        $where_values[] = $filtro_plazas_min;
    }

    $where_sql = implode(' AND ', $where_clauses);
    $having_sql = !empty($having_clauses) ? 'HAVING ' . implode(' AND ', $having_clauses) : '';

    // Ordenamiento seguro
    $columnas_permitidas = ['total_plazas', 'display_name', 'fecha_registro', 'plazas_ocupadas'];
    $orden_columna = in_array($filtro_orden, $columnas_permitidas) ? $filtro_orden : 'total_plazas';
    if ($orden_columna === 'display_name') $orden_columna = 'u.display_name';
    if ($orden_columna === 'fecha_registro') $orden_columna = 'p.fecha_registro';

    $orden_direccion = strtoupper($filtro_orden_dir) === 'ASC' ? 'ASC' : 'DESC';

    // Total filtrado
    $query_count = "SELECT COUNT(*) FROM (
        SELECT p.id
        FROM {$tabla_propietarios} p
        INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
        WHERE {$where_sql}
        GROUP BY p.id
        {$having_sql}
    ) as subquery";

    if (!empty($where_values)) {
        $total_filtrado = $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_filtrado = $wpdb->get_var($query_count);
    }

    $total_paginas = ceil($total_filtrado / $elementos_por_pagina);

    // Consulta principal
    $query = "SELECT
            p.*,
            u.display_name,
            u.user_email,
            COUNT(pl.id) as total_plazas,
            SUM(CASE WHEN pl.estado = 'ocupada' THEN 1 ELSE 0 END) as plazas_ocupadas,
            SUM(CASE WHEN pl.estado = 'disponible' THEN 1 ELSE 0 END) as plazas_disponibles
        FROM {$tabla_propietarios} p
        INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
        WHERE {$where_sql}
        GROUP BY p.id
        {$having_sql}
        ORDER BY {$orden_columna} {$orden_direccion}
        LIMIT %d OFFSET %d";

    $query_values = array_merge($where_values, [$elementos_por_pagina, $offset]);
    $propietarios = $wpdb->get_results($wpdb->prepare($query, $query_values));

    // Top propietarios por plazas
    $top_propietarios = $wpdb->get_results(
        "SELECT
            p.id,
            u.display_name,
            COUNT(pl.id) as total_plazas,
            SUM(CASE WHEN pl.estado = 'ocupada' THEN 1 ELSE 0 END) as plazas_ocupadas
        FROM {$tabla_propietarios} p
        INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
        WHERE p.estado = 'activo'
        GROUP BY p.id
        ORDER BY total_plazas DESC
        LIMIT 5"
    );

    // Distribución por estado
    $distribucion_raw = $wpdb->get_results(
        "SELECT estado, COUNT(*) as total FROM {$tabla_propietarios} GROUP BY estado",
        OBJECT_K
    );
    $distribucion_estados = [];
    foreach ($distribucion_raw as $estado => $row) {
        $distribucion_estados[$estado] = $row->total;
    }
} else {
    $total_propietarios = 0;
    $propietarios_activos = 0;
    $total_plazas_sistema = 0;
    $plazas_ocupadas_sistema = 0;
    $tasa_ocupacion = 0;
    $nuevos_este_mes = 0;
    $total_filtrado = 0;
    $total_paginas = 0;
    $propietarios = [];
    $top_propietarios = [];
    $distribucion_estados = [];
}
?>

<style>
.flavor-propietarios-wrapper {
    padding: 20px 0;
}

/* Aviso de modo demo */
.flavor-propietarios-demo-notice {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-left: 4px solid #f59e0b;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 0 8px 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-propietarios-demo-notice .dashicons {
    color: #d97706;
}

/* Estadísticas */
.flavor-propietarios-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-propietarios-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-propietarios-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-propietarios-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-propietarios-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-propietarios-stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
.flavor-propietarios-stat-icon.activos { background: linear-gradient(135deg, #11998e, #38ef7d); }
.flavor-propietarios-stat-icon.plazas { background: linear-gradient(135deg, #f093fb, #f5576c); }
.flavor-propietarios-stat-icon.ocupacion { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.flavor-propietarios-stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.flavor-propietarios-stat-content span {
    color: #64748b;
    font-size: 13px;
}

.flavor-propietarios-stat-content small {
    display: block;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 2px;
}

/* Layout principal */
.flavor-propietarios-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

@media (max-width: 1200px) {
    .flavor-propietarios-layout {
        grid-template-columns: 1fr;
    }
}

.flavor-propietarios-main {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Filtros */
.flavor-propietarios-filters {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-propietarios-filters-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}

.flavor-propietarios-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-propietarios-filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.flavor-propietarios-filter-group input,
.flavor-propietarios-filter-group select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    min-width: 140px;
}

.flavor-propietarios-filter-group input[type="number"] {
    width: 80px;
}

.flavor-propietarios-filters-actions {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

/* Tabla */
.flavor-propietarios-table {
    width: 100%;
    border-collapse: collapse;
}

.flavor-propietarios-table th {
    background: #f8fafc;
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    font-size: 13px;
    border-bottom: 2px solid #e2e8f0;
}

.flavor-propietarios-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.flavor-propietarios-table tr:hover {
    background: #f8fafc;
}

/* Propietario info */
.flavor-propietarios-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-propietarios-user img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
}

.flavor-propietarios-user-info h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-propietarios-user-info span {
    font-size: 12px;
    color: #64748b;
}

/* Contacto */
.flavor-propietarios-contacto {
    font-size: 13px;
}

.flavor-propietarios-contacto .email {
    color: #3b82f6;
}

.flavor-propietarios-contacto .telefono {
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}

.flavor-propietarios-contacto .telefono .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Plazas */
.flavor-propietarios-plazas {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-propietarios-plazas-total {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.flavor-propietarios-plazas-detalle {
    font-size: 11px;
    color: #64748b;
}

.flavor-propietarios-plazas-detalle .ocupadas {
    color: #059669;
}

.flavor-propietarios-plazas-detalle .disponibles {
    color: #f59e0b;
}

/* Barra de ocupación */
.flavor-propietarios-ocupacion-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
}

.flavor-propietarios-ocupacion-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 4px;
    transition: width 0.3s;
}

/* Badges */
.flavor-propietarios-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-propietarios-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-propietarios-badge.success { background: #dcfce7; color: #166534; }
.flavor-propietarios-badge.info { background: #dbeafe; color: #1e40af; }
.flavor-propietarios-badge.primary { background: #e0e7ff; color: #3730a3; }
.flavor-propietarios-badge.warning { background: #fef3c7; color: #92400e; }
.flavor-propietarios-badge.danger { background: #fee2e2; color: #991b1b; }
.flavor-propietarios-badge.secondary { background: #f1f5f9; color: #475569; }

/* Nivel badges */
.flavor-propietarios-nivel {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.flavor-propietarios-nivel.premium { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.flavor-propietarios-nivel.avanzado { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
.flavor-propietarios-nivel.estandar { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534; }
.flavor-propietarios-nivel.basico { background: #f1f5f9; color: #475569; }

/* Acciones */
.flavor-propietarios-acciones {
    display: flex;
    gap: 6px;
}

.flavor-propietarios-acciones .button {
    padding: 4px 10px !important;
    font-size: 12px !important;
}

/* Paginación */
.flavor-propietarios-pagination {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-propietarios-pagination-info {
    font-size: 13px;
    color: #64748b;
}

.flavor-propietarios-pagination .page-numbers {
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

.flavor-propietarios-pagination .page-numbers:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.flavor-propietarios-pagination .page-numbers.current {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

/* Sidebar */
.flavor-propietarios-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.flavor-propietarios-sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.flavor-propietarios-sidebar-card h3 {
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

.flavor-propietarios-sidebar-card h3 .dashicons {
    color: #64748b;
}

/* Top list */
.flavor-propietarios-top-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-propietarios-top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-propietarios-top-item:last-child {
    border-bottom: none;
}

.flavor-propietarios-top-rank {
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

.flavor-propietarios-top-rank.gold { background: #fef3c7; color: #92400e; }
.flavor-propietarios-top-rank.silver { background: #f1f5f9; color: #475569; }
.flavor-propietarios-top-rank.bronze { background: #fed7aa; color: #9a3412; }

.flavor-propietarios-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-propietarios-top-info h4 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-propietarios-top-info span {
    font-size: 11px;
    color: #64748b;
}

.flavor-propietarios-top-count {
    font-size: 16px;
    font-weight: 700;
    color: #3b82f6;
}

/* Chart */
.flavor-propietarios-chart-container {
    padding: 20px;
}

/* Vacío */
.flavor-propietarios-empty {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.flavor-propietarios-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    color: #cbd5e1;
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-businessman" style="margin-right: 8px;"></span>
        <?php echo esc_html__('Gestión de Propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="flavor-propietarios-wrapper">
        <?php if (!$tablas_parkings_disponibles): ?>
            <div class="flavor-propietarios-demo-notice">
                <span class="dashicons dashicons-info"></span>
                <span><?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Parkings.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="flavor-propietarios-stats">
            <div class="flavor-propietarios-stat-card">
                <div class="flavor-propietarios-stat-icon total">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="flavor-propietarios-stat-content">
                    <h3><?php echo number_format($total_propietarios); ?></h3>
                    <span><?php echo esc_html__('Total Propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <small><?php printf(esc_html__('%d nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), $nuevos_este_mes); ?></small>
                </div>
            </div>

            <div class="flavor-propietarios-stat-card">
                <div class="flavor-propietarios-stat-icon activos">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="flavor-propietarios-stat-content">
                    <h3><?php echo number_format($propietarios_activos); ?></h3>
                    <span><?php echo esc_html__('Propietarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <small><?php printf(esc_html__('%d%% del total', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_propietarios > 0 ? round(($propietarios_activos / $total_propietarios) * 100) : 0); ?></small>
                </div>
            </div>

            <div class="flavor-propietarios-stat-card">
                <div class="flavor-propietarios-stat-icon plazas">
                    <span class="dashicons dashicons-car"></span>
                </div>
                <div class="flavor-propietarios-stat-content">
                    <h3><?php echo number_format($total_plazas_sistema); ?></h3>
                    <span><?php echo esc_html__('Plazas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <small><?php printf(esc_html__('%d ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN), $plazas_ocupadas_sistema); ?></small>
                </div>
            </div>

            <div class="flavor-propietarios-stat-card">
                <div class="flavor-propietarios-stat-icon ocupacion">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <div class="flavor-propietarios-stat-content">
                    <h3><?php echo $tasa_ocupacion; ?>%</h3>
                    <span><?php echo esc_html__('Tasa de Ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <!-- Layout principal -->
        <div class="flavor-propietarios-layout">
            <!-- Contenido principal -->
            <div class="flavor-propietarios-main">
                <!-- Filtros -->
                <form method="get" class="flavor-propietarios-filters">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
                    <?php if (isset($_GET['tab'])): ?>
                        <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>">
                    <?php endif; ?>

                    <div class="flavor-propietarios-filters-grid">
                        <div class="flavor-propietarios-filter-group">
                            <label><?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre, email, teléfono...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>

                        <div class="flavor-propietarios-filter-group">
                            <label><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="estado">
                                <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php echo esc_html__('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="inactivo" <?php selected($filtro_estado, 'inactivo'); ?>><?php echo esc_html__('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>><?php echo esc_html__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="suspendido" <?php selected($filtro_estado, 'suspendido'); ?>><?php echo esc_html__('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-propietarios-filter-group">
                            <label><?php echo esc_html__('Mín. plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" name="plazas_min" value="<?php echo esc_attr($filtro_plazas_min); ?>" min="0" placeholder="0">
                        </div>

                        <div class="flavor-propietarios-filter-group">
                            <label><?php echo esc_html__('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="orderby">
                                <option value="total_plazas" <?php selected($filtro_orden, 'total_plazas'); ?>><?php echo esc_html__('Nº Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="display_name" <?php selected($filtro_orden, 'display_name'); ?>><?php echo esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="fecha_registro" <?php selected($filtro_orden, 'fecha_registro'); ?>><?php echo esc_html__('Fecha registro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="plazas_ocupadas" <?php selected($filtro_orden, 'plazas_ocupadas'); ?>><?php echo esc_html__('Ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-propietarios-filter-group">
                            <label><?php echo esc_html__('Orden', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="order">
                                <option value="DESC" <?php selected($filtro_orden_dir, 'DESC'); ?>><?php echo esc_html__('Descendente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="ASC" <?php selected($filtro_orden_dir, 'ASC'); ?>><?php echo esc_html__('Ascendente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-propietarios-filters-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                                <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <a href="<?php echo esc_url(remove_query_arg(['s', 'estado', 'plazas_min', 'orderby', 'order', 'paged'])); ?>" class="button">
                                <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Tabla -->
                <?php if (!empty($propietarios)): ?>
                    <table class="flavor-propietarios-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Propietario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propietarios as $propietario): ?>
                                <?php
                                $badge_estado = obtener_badge_estado_propietario($propietario->estado);
                                $nivel = calcular_nivel_propietario($propietario->total_plazas, $propietario->plazas_ocupadas ?? 0);
                                $ocupacion_pct = $propietario->total_plazas > 0 ? round(($propietario->plazas_ocupadas / $propietario->total_plazas) * 100) : 0;
                                ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($propietario->id); ?></strong></td>
                                    <td>
                                        <div class="flavor-propietarios-user">
                                            <?php echo get_avatar($propietario->usuario_id ?? 0, 44); ?>
                                            <div class="flavor-propietarios-user-info">
                                                <h4><?php echo esc_html($propietario->display_name); ?></h4>
                                                <span><?php echo esc_html__('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('M Y', strtotime($propietario->fecha_registro ?? 'now')); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flavor-propietarios-contacto">
                                            <span class="email"><?php echo esc_html($propietario->user_email); ?></span>
                                            <?php if (!empty($propietario->telefono)): ?>
                                                <span class="telefono">
                                                    <span class="dashicons dashicons-phone"></span>
                                                    <?php echo esc_html($propietario->telefono); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flavor-propietarios-plazas">
                                            <span class="flavor-propietarios-plazas-total"><?php echo esc_html($propietario->total_plazas); ?></span>
                                            <span class="flavor-propietarios-plazas-detalle">
                                                <span class="ocupadas"><?php echo esc_html($propietario->plazas_ocupadas ?? 0); ?> <?php echo esc_html__('ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                                /
                                                <span class="disponibles"><?php echo esc_html($propietario->plazas_disponibles ?? 0); ?> <?php echo esc_html__('libres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                            </span>
                                            <div class="flavor-propietarios-ocupacion-bar">
                                                <div class="flavor-propietarios-ocupacion-fill" style="width: <?php echo $ocupacion_pct; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="flavor-propietarios-nivel <?php echo esc_attr($nivel['clase']); ?>">
                                            <?php echo esc_html($nivel['nivel']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="flavor-propietarios-badge <?php echo esc_attr($badge_estado['clase']); ?>">
                                            <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                            <?php echo esc_html($badge_estado['texto']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flavor-propietarios-acciones">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios&action=ver&propietario_id=' . $propietario->id)); ?>" class="button button-small" title="<?php echo esc_attr__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-visibility" style="font-size: 14px; line-height: 1.4;"></span>
                                            </a>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&propietario_id=' . $propietario->id)); ?>" class="button button-small" title="<?php echo esc_attr__('Ver plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-car" style="font-size: 14px; line-height: 1.4;"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="flavor-propietarios-pagination">
                            <span class="flavor-propietarios-pagination-info">
                                <?php printf(
                                    esc_html__('Mostrando %d-%d de %d propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    $offset + 1,
                                    min($offset + $elementos_por_pagina, $total_filtrado),
                                    $total_filtrado
                                ); ?>
                            </span>
                            <div class="flavor-propietarios-pagination-links">
                                <?php
                                echo paginate_links([
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'total' => $total_paginas,
                                    'current' => $pagina_actual,
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                ]);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="flavor-propietarios-empty">
                        <span class="dashicons dashicons-businessman"></span>
                        <p><?php echo esc_html__('No se encontraron propietarios con los filtros aplicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo esc_url(remove_query_arg(['s', 'estado', 'plazas_min', 'orderby', 'order', 'paged'])); ?>" class="button">
                            <?php echo esc_html__('Ver todos los propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="flavor-propietarios-sidebar">
                <!-- Top Propietarios -->
                <div class="flavor-propietarios-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php echo esc_html__('Top Propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <?php if (!empty($top_propietarios)): ?>
                        <ul class="flavor-propietarios-top-list">
                            <?php $posicion = 1; ?>
                            <?php foreach ($top_propietarios as $top): ?>
                                <?php
                                $rank_class = $posicion === 1 ? 'gold' : ($posicion === 2 ? 'silver' : ($posicion === 3 ? 'bronze' : ''));
                                $ocupacion = isset($top->plazas_ocupadas) && $top->total_plazas > 0 ? round(($top->plazas_ocupadas / $top->total_plazas) * 100) : 0;
                                ?>
                                <li class="flavor-propietarios-top-item">
                                    <span class="flavor-propietarios-top-rank <?php echo esc_attr($rank_class); ?>"><?php echo $posicion; ?></span>
                                    <div class="flavor-propietarios-top-info">
                                        <h4><?php echo esc_html($top->display_name); ?></h4>
                                        <span><?php printf(esc_html__('%d%% ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN), $ocupacion); ?></span>
                                    </div>
                                    <span class="flavor-propietarios-top-count"><?php echo esc_html($top->total_plazas); ?></span>
                                </li>
                                <?php $posicion++; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #64748b;">
                            <?php echo esc_html__('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Distribución por Estado -->
                <?php if (!empty($distribucion_estados)): ?>
                <div class="flavor-propietarios-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php echo esc_html__('Por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="flavor-propietarios-chart-container">
                        <canvas id="flavor-propietarios-estados-chart" height="200"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($distribucion_estados)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('flavor-propietarios-estados-chart');
    if (ctx) {
        var labels = <?php echo json_encode(array_map(function($e) {
            $nombres = [
                'activo' => 'Activo',
                'inactivo' => 'Inactivo',
                'pendiente' => 'Pendiente',
                'suspendido' => 'Suspendido'
            ];
            return $nombres[$e] ?? ucfirst($e);
        }, array_keys($distribucion_estados))); ?>;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: <?php echo json_encode(array_values($distribucion_estados)); ?>,
                    backgroundColor: ['#10b981', '#94a3b8', '#f59e0b', '#ef4444'],
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
                            padding: 10,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

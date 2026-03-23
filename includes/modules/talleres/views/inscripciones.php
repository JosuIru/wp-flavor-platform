<?php
/**
 * Vista Gestión de Inscripciones
 *
 * Gestión completa de inscripciones a talleres con estadísticas,
 * filtros avanzados, paginación y acciones masivas.
 *
 * @package FlavorChatIA
 * @subpackage Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

// =============================================================================
// FUNCIONES AUXILIARES
// =============================================================================

/**
 * Obtener badge de estado de inscripción
 */
function obtener_badge_estado_inscripcion($estado) {
    $estados = [
        'confirmada' => ['clase' => 'success', 'texto' => __('Confirmada', 'flavor-chat-ia'), 'icono' => 'yes-alt'],
        'pendiente' => ['clase' => 'warning', 'texto' => __('Pendiente', 'flavor-chat-ia'), 'icono' => 'clock'],
        'lista_espera' => ['clase' => 'info', 'texto' => __('Lista de espera', 'flavor-chat-ia'), 'icono' => 'hourglass'],
        'cancelada' => ['clase' => 'danger', 'texto' => __('Cancelada', 'flavor-chat-ia'), 'icono' => 'dismiss'],
        'asistio' => ['clase' => 'primary', 'texto' => __('Asistió', 'flavor-chat-ia'), 'icono' => 'awards'],
        'no_asistio' => ['clase' => 'secondary', 'texto' => __('No asistió', 'flavor-chat-ia'), 'icono' => 'no'],
    ];

    if (isset($estados[$estado])) {
        return $estados[$estado];
    }

    return ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Obtener badge de estado de pago
 */
function obtener_badge_estado_pago($estado_pago, $precio) {
    if ($precio == 0) {
        return ['clase' => 'info', 'texto' => __('Gratis', 'flavor-chat-ia'), 'icono' => 'heart'];
    }

    $estados = [
        'pagado' => ['clase' => 'success', 'texto' => __('Pagado', 'flavor-chat-ia'), 'icono' => 'yes-alt'],
        'pendiente' => ['clase' => 'warning', 'texto' => __('Pendiente', 'flavor-chat-ia'), 'icono' => 'clock'],
        'parcial' => ['clase' => 'info', 'texto' => __('Parcial', 'flavor-chat-ia'), 'icono' => 'chart-pie'],
        'reembolsado' => ['clase' => 'secondary', 'texto' => __('Reembolsado', 'flavor-chat-ia'), 'icono' => 'undo'],
    ];

    if (isset($estados[$estado_pago])) {
        return $estados[$estado_pago];
    }

    return ['clase' => 'warning', 'texto' => __('Pendiente', 'flavor-chat-ia'), 'icono' => 'clock'];
}

// =============================================================================
// CONFIGURACIÓN Y BASE DE DATOS
// =============================================================================

global $wpdb;
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';

// Verificar existencia de tablas
$tabla_inscripciones_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_inscripciones
    )
);

$tabla_talleres_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_talleres
    )
);

$tablas_talleres_disponibles = $tabla_inscripciones_existe && $tabla_talleres_existe;

// =============================================================================
// PARÁMETROS DE PAGINACIÓN Y FILTROS
// =============================================================================

$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Filtros
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_taller = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_pago = isset($_GET['estado_pago']) ? sanitize_text_field($_GET['estado_pago']) : '';
$filtro_orden = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'fecha_inscripcion';
$filtro_orden_dir = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// =============================================================================
// DATOS DEMO O REALES
// =============================================================================

if ($tablas_talleres_disponibles) {
    // Datos reales de la base de datos

    // Estadísticas generales
    $total_inscripciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_inscripciones}");

    $inscripciones_confirmadas = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'confirmada'"
    );

    $inscripciones_pendientes = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'pendiente'"
    );

    $ingresos_totales = $wpdb->get_var(
        "SELECT COALESCE(SUM(precio_pagado), 0) FROM {$tabla_inscripciones} WHERE estado_pago = 'pagado'"
    );

    $ingresos_pendientes = $wpdb->get_var(
        "SELECT COALESCE(SUM(precio_pagado), 0) FROM {$tabla_inscripciones} WHERE estado_pago = 'pendiente'"
    );

    $total_asistieron = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'asistio'"
    );
    $total_finalizadas = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado IN ('asistio', 'no_asistio')"
    );
    $tasa_asistencia = $total_finalizadas > 0 ? round(($total_asistieron / $total_finalizadas) * 100) : 0;

    $fecha_inicio_mes = date('Y-m-01 00:00:00');
    $inscripciones_mes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE fecha_inscripcion >= %s",
            $fecha_inicio_mes
        )
    );

    // Talleres disponibles para filtro
    $talleres_disponibles = $wpdb->get_results("SELECT id, titulo FROM {$tabla_talleres} ORDER BY titulo");

    // Construir consulta con filtros
    $where_clauses = ["1=1"];
    $where_values = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR t.titulo LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if ($filtro_taller > 0) {
        $where_clauses[] = "i.taller_id = %d";
        $where_values[] = $filtro_taller;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "i.estado = %s";
        $where_values[] = $filtro_estado;
    }

    if (!empty($filtro_pago)) {
        $where_clauses[] = "i.estado_pago = %s";
        $where_values[] = $filtro_pago;
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Ordenamiento seguro
    $columnas_permitidas = ['fecha_inscripcion', 'participante_nombre', 'taller_titulo', 'precio_pagado', 'estado'];
    $orden_columna = in_array($filtro_orden, $columnas_permitidas) ? $filtro_orden : 'fecha_inscripcion';
    if ($orden_columna === 'participante_nombre') $orden_columna = 'u.display_name';
    if ($orden_columna === 'taller_titulo') $orden_columna = 't.titulo';

    $orden_direccion = strtoupper($filtro_orden_dir) === 'ASC' ? 'ASC' : 'DESC';

    // Total filtrado
    $query_count = "SELECT COUNT(*)
        FROM {$tabla_inscripciones} i
        INNER JOIN {$tabla_talleres} t ON i.taller_id = t.id
        INNER JOIN {$wpdb->users} u ON i.participante_id = u.ID
        WHERE {$where_sql}";

    if (!empty($where_values)) {
        $total_filtrado = $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_filtrado = $wpdb->get_var($query_count);
    }

    $total_paginas = ceil($total_filtrado / $elementos_por_pagina);

    // Consulta principal
    $query = "SELECT i.*,
            t.titulo as taller_titulo,
            u.display_name as participante_nombre,
            u.user_email as participante_email
        FROM {$tabla_inscripciones} i
        INNER JOIN {$tabla_talleres} t ON i.taller_id = t.id
        INNER JOIN {$wpdb->users} u ON i.participante_id = u.ID
        WHERE {$where_sql}
        ORDER BY {$orden_columna} {$orden_direccion}
        LIMIT %d OFFSET %d";

    $query_values = array_merge($where_values, [$elementos_por_pagina, $offset]);
    $inscripciones = $wpdb->get_results($wpdb->prepare($query, $query_values));

    // Top talleres por inscripciones
    $top_talleres = $wpdb->get_results(
        "SELECT t.titulo, COUNT(i.id) as total_inscripciones
         FROM {$tabla_talleres} t
         LEFT JOIN {$tabla_inscripciones} i ON t.id = i.taller_id
         GROUP BY t.id
         ORDER BY total_inscripciones DESC
         LIMIT 5"
    );

    // Distribución por estado
    $distribucion_raw = $wpdb->get_results(
        "SELECT estado, COUNT(*) as total FROM {$tabla_inscripciones} GROUP BY estado",
        OBJECT_K
    );
    $distribucion_estados = [];
    foreach ($distribucion_raw as $estado => $row) {
        $distribucion_estados[$estado] = $row->total;
    }
} else {
    $total_inscripciones = 0;
    $inscripciones_confirmadas = 0;
    $inscripciones_pendientes = 0;
    $ingresos_totales = 0;
    $ingresos_pendientes = 0;
    $tasa_asistencia = 0;
    $inscripciones_mes = 0;
    $talleres_disponibles = [];
    $total_filtrado = 0;
    $total_paginas = 0;
    $inscripciones = [];
    $top_talleres = [];
    $distribucion_estados = [];
}
?>

<style>
.flavor-inscripciones-wrapper {
    padding: 20px 0;
}

/* Aviso de modo demo */
.flavor-inscripciones-demo-notice {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-left: 4px solid #f59e0b;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 0 8px 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-inscripciones-demo-notice .dashicons {
    color: #d97706;
}

/* Estadísticas */
.flavor-inscripciones-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-inscripciones-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-inscripciones-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-inscripciones-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-inscripciones-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-inscripciones-stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
.flavor-inscripciones-stat-icon.confirmadas { background: linear-gradient(135deg, #11998e, #38ef7d); }
.flavor-inscripciones-stat-icon.ingresos { background: linear-gradient(135deg, #f093fb, #f5576c); }
.flavor-inscripciones-stat-icon.asistencia { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.flavor-inscripciones-stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.flavor-inscripciones-stat-content span {
    color: #64748b;
    font-size: 13px;
}

.flavor-inscripciones-stat-content small {
    display: block;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 2px;
}

/* Layout principal */
.flavor-inscripciones-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

@media (max-width: 1200px) {
    .flavor-inscripciones-layout {
        grid-template-columns: 1fr;
    }
}

.flavor-inscripciones-main {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Filtros */
.flavor-inscripciones-filters {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-inscripciones-filters-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}

.flavor-inscripciones-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-inscripciones-filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.flavor-inscripciones-filter-group input,
.flavor-inscripciones-filter-group select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    min-width: 140px;
}

.flavor-inscripciones-filters-actions {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

/* Tabla */
.flavor-inscripciones-table {
    width: 100%;
    border-collapse: collapse;
}

.flavor-inscripciones-table th {
    background: #f8fafc;
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    font-size: 13px;
    border-bottom: 2px solid #e2e8f0;
}

.flavor-inscripciones-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.flavor-inscripciones-table tr:hover {
    background: #f8fafc;
}

/* Participante */
.flavor-inscripciones-participante {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-inscripciones-participante img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.flavor-inscripciones-participante-info h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-inscripciones-participante-info span {
    font-size: 12px;
    color: #64748b;
}

/* Taller */
.flavor-inscripciones-taller {
    font-weight: 600;
    color: #3b82f6;
}

/* Precio */
.flavor-inscripciones-precio {
    font-weight: 700;
}

.flavor-inscripciones-precio.pagado {
    color: #059669;
}

.flavor-inscripciones-precio.pendiente {
    color: #d97706;
}

.flavor-inscripciones-precio.gratis {
    color: #64748b;
}

/* Badges */
.flavor-inscripciones-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-inscripciones-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-inscripciones-badge.success { background: #dcfce7; color: #166534; }
.flavor-inscripciones-badge.info { background: #dbeafe; color: #1e40af; }
.flavor-inscripciones-badge.primary { background: #e0e7ff; color: #3730a3; }
.flavor-inscripciones-badge.warning { background: #fef3c7; color: #92400e; }
.flavor-inscripciones-badge.danger { background: #fee2e2; color: #991b1b; }
.flavor-inscripciones-badge.secondary { background: #f1f5f9; color: #475569; }

/* Acciones */
.flavor-inscripciones-acciones {
    display: flex;
    gap: 6px;
}

.flavor-inscripciones-acciones .button {
    padding: 4px 10px !important;
    font-size: 12px !important;
}

/* Paginación */
.flavor-inscripciones-pagination {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-inscripciones-pagination-info {
    font-size: 13px;
    color: #64748b;
}

.flavor-inscripciones-pagination .page-numbers {
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

.flavor-inscripciones-pagination .page-numbers:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.flavor-inscripciones-pagination .page-numbers.current {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

/* Sidebar */
.flavor-inscripciones-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.flavor-inscripciones-sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.flavor-inscripciones-sidebar-card h3 {
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

.flavor-inscripciones-sidebar-card h3 .dashicons {
    color: #64748b;
}

/* Top talleres */
.flavor-inscripciones-top-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-inscripciones-top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-inscripciones-top-item:last-child {
    border-bottom: none;
}

.flavor-inscripciones-top-rank {
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

.flavor-inscripciones-top-rank.gold { background: #fef3c7; color: #92400e; }
.flavor-inscripciones-top-rank.silver { background: #f1f5f9; color: #475569; }
.flavor-inscripciones-top-rank.bronze { background: #fed7aa; color: #9a3412; }

.flavor-inscripciones-top-info {
    flex: 1;
    min-width: 0;
}

.flavor-inscripciones-top-info h4 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-inscripciones-top-count {
    font-size: 14px;
    font-weight: 700;
    color: #3b82f6;
}

/* Chart */
.flavor-inscripciones-chart-container {
    padding: 20px;
}

/* Vacío */
.flavor-inscripciones-empty {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.flavor-inscripciones-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    color: #cbd5e1;
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-welcome-learn-more" style="margin-right: 8px;"></span>
        <?php echo esc_html__('Gestión de Inscripciones', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="flavor-inscripciones-wrapper">
        <?php if (!$tablas_talleres_disponibles): ?>
            <div class="flavor-inscripciones-demo-notice">
                <span class="dashicons dashicons-info"></span>
                <span><?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Talleres.', 'flavor-chat-ia'); ?></span>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="flavor-inscripciones-stats">
            <div class="flavor-inscripciones-stat-card">
                <div class="flavor-inscripciones-stat-icon total">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="flavor-inscripciones-stat-content">
                    <h3><?php echo number_format($total_inscripciones); ?></h3>
                    <span><?php echo esc_html__('Total Inscripciones', 'flavor-chat-ia'); ?></span>
                    <small><?php printf(esc_html__('%d este mes', 'flavor-chat-ia'), $inscripciones_mes); ?></small>
                </div>
            </div>

            <div class="flavor-inscripciones-stat-card">
                <div class="flavor-inscripciones-stat-icon confirmadas">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="flavor-inscripciones-stat-content">
                    <h3><?php echo number_format($inscripciones_confirmadas); ?></h3>
                    <span><?php echo esc_html__('Confirmadas', 'flavor-chat-ia'); ?></span>
                    <small><?php printf(esc_html__('%d pendientes', 'flavor-chat-ia'), $inscripciones_pendientes); ?></small>
                </div>
            </div>

            <div class="flavor-inscripciones-stat-card">
                <div class="flavor-inscripciones-stat-icon ingresos">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="flavor-inscripciones-stat-content">
                    <h3><?php echo number_format($ingresos_totales, 2); ?> €</h3>
                    <span><?php echo esc_html__('Ingresos Recaudados', 'flavor-chat-ia'); ?></span>
                    <small><?php printf(esc_html__('%s € pendientes', 'flavor-chat-ia'), number_format($ingresos_pendientes, 2)); ?></small>
                </div>
            </div>

            <div class="flavor-inscripciones-stat-card">
                <div class="flavor-inscripciones-stat-icon asistencia">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="flavor-inscripciones-stat-content">
                    <h3><?php echo $tasa_asistencia; ?>%</h3>
                    <span><?php echo esc_html__('Tasa de Asistencia', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>

        <!-- Layout principal -->
        <div class="flavor-inscripciones-layout">
            <!-- Contenido principal -->
            <div class="flavor-inscripciones-main">
                <!-- Filtros -->
                <form method="get" class="flavor-inscripciones-filters">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
                    <?php if (isset($_GET['tab'])): ?>
                        <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>">
                    <?php endif; ?>

                    <div class="flavor-inscripciones-filters-grid">
                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Nombre o email...', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></label>
                            <select name="taller_id">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($talleres_disponibles as $taller): ?>
                                    <option value="<?php echo esc_attr($taller->id); ?>" <?php selected($filtro_taller, $taller->id); ?>>
                                        <?php echo esc_html($taller->titulo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                            <select name="estado">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="confirmada" <?php selected($filtro_estado, 'confirmada'); ?>><?php echo esc_html__('Confirmada', 'flavor-chat-ia'); ?></option>
                                <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>><?php echo esc_html__('Pendiente', 'flavor-chat-ia'); ?></option>
                                <option value="lista_espera" <?php selected($filtro_estado, 'lista_espera'); ?>><?php echo esc_html__('Lista de espera', 'flavor-chat-ia'); ?></option>
                                <option value="asistio" <?php selected($filtro_estado, 'asistio'); ?>><?php echo esc_html__('Asistió', 'flavor-chat-ia'); ?></option>
                                <option value="cancelada" <?php selected($filtro_estado, 'cancelada'); ?>><?php echo esc_html__('Cancelada', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Pago', 'flavor-chat-ia'); ?></label>
                            <select name="estado_pago">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="pagado" <?php selected($filtro_pago, 'pagado'); ?>><?php echo esc_html__('Pagado', 'flavor-chat-ia'); ?></option>
                                <option value="pendiente" <?php selected($filtro_pago, 'pendiente'); ?>><?php echo esc_html__('Pendiente', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                            <select name="orderby">
                                <option value="fecha_inscripcion" <?php selected($filtro_orden, 'fecha_inscripcion'); ?>><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></option>
                                <option value="participante_nombre" <?php selected($filtro_orden, 'participante_nombre'); ?>><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></option>
                                <option value="taller_titulo" <?php selected($filtro_orden, 'taller_titulo'); ?>><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></option>
                                <option value="precio_pagado" <?php selected($filtro_orden, 'precio_pagado'); ?>><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="flavor-inscripciones-filter-group">
                            <label><?php echo esc_html__('Orden', 'flavor-chat-ia'); ?></label>
                            <select name="order">
                                <option value="DESC" <?php selected($filtro_orden_dir, 'DESC'); ?>><?php echo esc_html__('Descendente', 'flavor-chat-ia'); ?></option>
                                <option value="ASC" <?php selected($filtro_orden_dir, 'ASC'); ?>><?php echo esc_html__('Ascendente', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="flavor-inscripciones-filters-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                                <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                            </button>
                            <a href="<?php echo esc_url(remove_query_arg(['s', 'taller_id', 'estado', 'estado_pago', 'orderby', 'order', 'paged'])); ?>" class="button">
                                <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Tabla -->
                <?php if (!empty($inscripciones)): ?>
                    <table class="flavor-inscripciones-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Participante', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Pago', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscripciones as $inscripcion): ?>
                                <?php
                                $badge_estado = obtener_badge_estado_inscripcion($inscripcion->estado);
                                $badge_pago = obtener_badge_estado_pago($inscripcion->estado_pago ?? 'pendiente', $inscripcion->precio_pagado ?? 0);
                                $precio_clase = ($inscripcion->precio_pagado ?? 0) == 0 ? 'gratis' : ($inscripcion->estado_pago === 'pagado' ? 'pagado' : 'pendiente');
                                ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($inscripcion->id); ?></strong></td>
                                    <td>
                                        <div class="flavor-inscripciones-participante">
                                            <?php echo get_avatar($inscripcion->participante_id ?? 0, 40); ?>
                                            <div class="flavor-inscripciones-participante-info">
                                                <h4><?php echo esc_html($inscripcion->participante_nombre); ?></h4>
                                                <span><?php echo esc_html($inscripcion->participante_email); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="flavor-inscripciones-taller"><?php echo esc_html($inscripcion->taller_titulo); ?></span>
                                    </td>
                                    <td>
                                        <?php echo date_i18n('d/m/Y', strtotime($inscripcion->fecha_inscripcion)); ?>
                                        <br><small style="color: #64748b;"><?php echo date_i18n('H:i', strtotime($inscripcion->fecha_inscripcion)); ?></small>
                                    </td>
                                    <td>
                                        <span class="flavor-inscripciones-precio <?php echo esc_attr($precio_clase); ?>">
                                            <?php echo ($inscripcion->precio_pagado ?? 0) > 0 ? number_format($inscripcion->precio_pagado, 2) . ' €' : __('Gratis', 'flavor-chat-ia'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="flavor-inscripciones-badge <?php echo esc_attr($badge_pago['clase']); ?>">
                                            <span class="dashicons dashicons-<?php echo esc_attr($badge_pago['icono']); ?>"></span>
                                            <?php echo esc_html($badge_pago['texto']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="flavor-inscripciones-badge <?php echo esc_attr($badge_estado['clase']); ?>">
                                            <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                            <?php echo esc_html($badge_estado['texto']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flavor-inscripciones-acciones">
                                            <button type="button" class="button button-small" title="<?php echo esc_attr__('Ver detalles', 'flavor-chat-ia'); ?>">
                                                <span class="dashicons dashicons-visibility" style="font-size: 14px; line-height: 1.4;"></span>
                                            </button>
                                            <?php if ($inscripcion->estado === 'pendiente'): ?>
                                                <button type="button" class="button button-small button-primary" title="<?php echo esc_attr__('Confirmar', 'flavor-chat-ia'); ?>">
                                                    <span class="dashicons dashicons-yes" style="font-size: 14px; line-height: 1.4;"></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="flavor-inscripciones-pagination">
                            <span class="flavor-inscripciones-pagination-info">
                                <?php printf(
                                    esc_html__('Mostrando %d-%d de %d inscripciones', 'flavor-chat-ia'),
                                    $offset + 1,
                                    min($offset + $elementos_por_pagina, $total_filtrado),
                                    $total_filtrado
                                ); ?>
                            </span>
                            <div class="flavor-inscripciones-pagination-links">
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
                    <div class="flavor-inscripciones-empty">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <p><?php echo esc_html__('No se encontraron inscripciones con los filtros aplicados.', 'flavor-chat-ia'); ?></p>
                        <a href="<?php echo esc_url(remove_query_arg(['s', 'taller_id', 'estado', 'estado_pago', 'orderby', 'order', 'paged'])); ?>" class="button">
                            <?php echo esc_html__('Ver todas las inscripciones', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="flavor-inscripciones-sidebar">
                <!-- Top Talleres -->
                <div class="flavor-inscripciones-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php echo esc_html__('Talleres Más Populares', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($top_talleres)): ?>
                        <ul class="flavor-inscripciones-top-list">
                            <?php $posicion = 1; ?>
                            <?php foreach ($top_talleres as $taller): ?>
                                <?php
                                $rank_class = $posicion === 1 ? 'gold' : ($posicion === 2 ? 'silver' : ($posicion === 3 ? 'bronze' : ''));
                                ?>
                                <li class="flavor-inscripciones-top-item">
                                    <span class="flavor-inscripciones-top-rank <?php echo esc_attr($rank_class); ?>"><?php echo $posicion; ?></span>
                                    <div class="flavor-inscripciones-top-info">
                                        <h4><?php echo esc_html($taller->titulo); ?></h4>
                                    </div>
                                    <span class="flavor-inscripciones-top-count"><?php echo esc_html($taller->total_inscripciones); ?></span>
                                </li>
                                <?php $posicion++; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #64748b;">
                            <?php echo esc_html__('Sin datos disponibles', 'flavor-chat-ia'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Distribución por Estado -->
                <?php if (!empty($distribucion_estados)): ?>
                <div class="flavor-inscripciones-sidebar-card">
                    <h3>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php echo esc_html__('Distribución por Estado', 'flavor-chat-ia'); ?>
                    </h3>
                    <div class="flavor-inscripciones-chart-container">
                        <canvas id="flavor-inscripciones-estados-chart" height="200"></canvas>
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
    var ctx = document.getElementById('flavor-inscripciones-estados-chart');
    if (ctx) {
        var labels = <?php echo json_encode(array_map(function($e) {
            $nombres = [
                'confirmada' => 'Confirmada',
                'pendiente' => 'Pendiente',
                'lista_espera' => 'Lista espera',
                'asistio' => 'Asistió',
                'cancelada' => 'Cancelada',
                'no_asistio' => 'No asistió'
            ];
            return $nombres[$e] ?? ucfirst($e);
        }, array_keys($distribucion_estados))); ?>;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: <?php echo json_encode(array_values($distribucion_estados)); ?>,
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#6366f1', '#ef4444', '#94a3b8'],
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

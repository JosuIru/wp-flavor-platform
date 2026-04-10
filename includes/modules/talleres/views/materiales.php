<?php
/**
 * Vista Gestión de Materiales
 *
 * Gestión completa de materiales necesarios para talleres con estadísticas,
 * filtros avanzados y seguimiento de inventario.
 *
 * @package FlavorPlatform
 * @subpackage Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-platform'));
}

// =============================================================================
// FUNCIONES AUXILIARES
// =============================================================================

/**
 * Obtener badge de estado del taller
 */
function obtener_badge_estado_taller_materiales($estado) {
    $estados = [
        'publicado' => ['clase' => 'info', 'texto' => __('Publicado', 'flavor-platform'), 'icono' => 'visibility'],
        'confirmado' => ['clase' => 'success', 'texto' => __('Confirmado', 'flavor-platform'), 'icono' => 'yes-alt'],
        'en_curso' => ['clase' => 'warning', 'texto' => __('En curso', 'flavor-platform'), 'icono' => 'controls-play'],
        'finalizado' => ['clase' => 'secondary', 'texto' => __('Finalizado', 'flavor-platform'), 'icono' => 'flag'],
        'cancelado' => ['clase' => 'danger', 'texto' => __('Cancelado', 'flavor-platform'), 'icono' => 'dismiss'],
    ];

    if (isset($estados[$estado])) {
        return $estados[$estado];
    }

    return ['clase' => 'secondary', 'texto' => ucfirst($estado), 'icono' => 'marker'];
}

/**
 * Calcular urgencia de preparación de materiales
 */
function calcular_urgencia_materiales($fecha_inicio, $estado) {
    if ($estado === 'finalizado' || $estado === 'cancelado') {
        return ['clase' => 'completado', 'texto' => __('N/A', 'flavor-platform'), 'icono' => 'minus'];
    }

    if (empty($fecha_inicio)) {
        return ['clase' => 'sin-fecha', 'texto' => __('Sin fecha', 'flavor-platform'), 'icono' => 'calendar'];
    }

    $dias_restantes = floor((strtotime($fecha_inicio) - time()) / 86400);

    if ($dias_restantes < 0) {
        return ['clase' => 'pasado', 'texto' => __('Ya iniciado', 'flavor-platform'), 'icono' => 'warning'];
    } elseif ($dias_restantes == 0) {
        return ['clase' => 'hoy', 'texto' => __('Hoy', 'flavor-platform'), 'icono' => 'clock'];
    } elseif ($dias_restantes <= 3) {
        return ['clase' => 'urgente', 'texto' => sprintf(__('%d días', 'flavor-platform'), $dias_restantes), 'icono' => 'warning'];
    } elseif ($dias_restantes <= 7) {
        return ['clase' => 'pronto', 'texto' => sprintf(__('%d días', 'flavor-platform'), $dias_restantes), 'icono' => 'clock'];
    } else {
        return ['clase' => 'normal', 'texto' => sprintf(__('%d días', 'flavor-platform'), $dias_restantes), 'icono' => 'calendar-alt'];
    }
}

// =============================================================================
// CONFIGURACIÓN Y BASE DE DATOS
// =============================================================================

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';

// Verificar existencia de tabla
$tabla_existe = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $tabla_talleres
    )
);

$tablas_talleres_disponibles = (bool) $tabla_existe;

// =============================================================================
// PARÁMETROS DE FILTROS
// =============================================================================

$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_materiales_incluidos = isset($_GET['materiales_incluidos']) ? $_GET['materiales_incluidos'] : '';
$filtro_orden = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'fecha_inicio';
$filtro_orden_dir = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';

// =============================================================================
// DATOS DEMO O REALES
// =============================================================================

if ($tablas_talleres_disponibles) {
    // Datos reales de la base de datos

    // Estadísticas generales
    $total_talleres_materiales = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_talleres}
         WHERE materiales_necesarios IS NOT NULL
         AND materiales_necesarios != ''
         AND estado IN ('publicado', 'confirmado', 'en_curso')"
    );

    $talleres_materiales_incluidos = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_talleres}
         WHERE materiales_incluidos = 1
         AND materiales_necesarios IS NOT NULL AND materiales_necesarios != ''
         AND estado IN ('publicado', 'confirmado', 'en_curso')"
    );

    $talleres_material_propio = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_talleres}
         WHERE materiales_incluidos = 0
         AND materiales_necesarios IS NOT NULL AND materiales_necesarios != ''
         AND estado IN ('publicado', 'confirmado', 'en_curso')"
    );

    $fecha_limite_urgente = date('Y-m-d H:i:s', strtotime('+7 days'));
    $talleres_urgentes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_talleres}
             WHERE materiales_necesarios IS NOT NULL AND materiales_necesarios != ''
             AND estado IN ('publicado', 'confirmado')
             AND fecha_inicio <= %s AND fecha_inicio >= NOW()",
            $fecha_limite_urgente
        )
    );

    $participantes_totales = $wpdb->get_var(
        "SELECT COALESCE(SUM(inscritos_actuales), 0) FROM {$tabla_talleres}
         WHERE materiales_necesarios IS NOT NULL AND materiales_necesarios != ''
         AND estado IN ('publicado', 'confirmado', 'en_curso')"
    );

    // Construir consulta con filtros
    $where_clauses = [
        "t.materiales_necesarios IS NOT NULL",
        "t.materiales_necesarios != ''",
        "t.estado IN ('publicado', 'confirmado', 'en_curso')"
    ];
    $where_values = [];

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(t.titulo LIKE %s OR u.display_name LIKE %s OR t.materiales_necesarios LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "t.estado = %s";
        $where_values[] = $filtro_estado;
    }

    if ($filtro_materiales_incluidos !== '') {
        $where_clauses[] = "t.materiales_incluidos = %d";
        $where_values[] = intval($filtro_materiales_incluidos);
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Ordenamiento seguro
    $columnas_permitidas = ['fecha_inicio', 'titulo', 'inscritos_actuales'];
    $orden_columna = in_array($filtro_orden, $columnas_permitidas) ? 't.' . $filtro_orden : 't.fecha_inicio';
    $orden_direccion = strtoupper($filtro_orden_dir) === 'DESC' ? 'DESC' : 'ASC';

    // Consulta principal
    $query = "SELECT t.*, u.display_name as organizador
         FROM {$tabla_talleres} t
         LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
         WHERE {$where_sql}
         ORDER BY {$orden_columna} {$orden_direccion}";

    if (!empty($where_values)) {
        $talleres_con_materiales = $wpdb->get_results($wpdb->prepare($query, $where_values));
    } else {
        $talleres_con_materiales = $wpdb->get_results($query);
    }

    // Materiales frecuentes - placeholder
    $materiales_frecuentes = [];
} else {
    $total_talleres_materiales = 0;
    $talleres_materiales_incluidos = 0;
    $talleres_material_propio = 0;
    $talleres_urgentes = 0;
    $participantes_totales = 0;
    $talleres_con_materiales = [];
    $materiales_frecuentes = [];
}
?>

<style>
.flavor-materiales-wrapper {
    padding: 20px 0;
}

/* Aviso de modo demo */
.flavor-materiales-demo-notice {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-left: 4px solid #f59e0b;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 0 8px 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-materiales-demo-notice .dashicons {
    color: #d97706;
}

/* Estadísticas */
.flavor-materiales-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-materiales-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-materiales-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-materiales-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-materiales-stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #fff;
}

.flavor-materiales-stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
.flavor-materiales-stat-icon.incluidos { background: linear-gradient(135deg, #11998e, #38ef7d); }
.flavor-materiales-stat-icon.propios { background: linear-gradient(135deg, #f093fb, #f5576c); }
.flavor-materiales-stat-icon.urgentes { background: linear-gradient(135deg, #ff6b6b, #ee5a24); }
.flavor-materiales-stat-icon.participantes { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.flavor-materiales-stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.flavor-materiales-stat-content span {
    color: #64748b;
    font-size: 13px;
}

/* Filtros */
.flavor-materiales-filters {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-materiales-filters-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}

.flavor-materiales-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-materiales-filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.flavor-materiales-filter-group input,
.flavor-materiales-filter-group select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    min-width: 140px;
}

.flavor-materiales-filters-actions {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

/* Grid de talleres */
.flavor-materiales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
}

/* Card de taller */
.flavor-materiales-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-materiales-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.flavor-materiales-card-header {
    padding: 20px;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-materiales-card-title {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.flavor-materiales-card-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-materiales-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 13px;
    color: #64748b;
}

.flavor-materiales-card-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.flavor-materiales-card-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Badges */
.flavor-materiales-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.flavor-materiales-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-materiales-badge.success { background: #dcfce7; color: #166534; }
.flavor-materiales-badge.info { background: #dbeafe; color: #1e40af; }
.flavor-materiales-badge.warning { background: #fef3c7; color: #92400e; }
.flavor-materiales-badge.danger { background: #fee2e2; color: #991b1b; }
.flavor-materiales-badge.secondary { background: #f1f5f9; color: #475569; }

/* Urgencia badges */
.flavor-materiales-urgencia {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-materiales-urgencia.urgente { background: #fee2e2; color: #991b1b; }
.flavor-materiales-urgencia.hoy { background: #fef3c7; color: #92400e; }
.flavor-materiales-urgencia.pronto { background: #dbeafe; color: #1e40af; }
.flavor-materiales-urgencia.normal { background: #f1f5f9; color: #475569; }
.flavor-materiales-urgencia.pasado { background: #f1f5f9; color: #94a3b8; }
.flavor-materiales-urgencia.completado { background: #f1f5f9; color: #94a3b8; }

/* Tipo de materiales */
.flavor-materiales-tipo {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-materiales-tipo.incluidos {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
}

.flavor-materiales-tipo.propios {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

/* Lista de materiales */
.flavor-materiales-card-body {
    padding: 20px;
    background: #f8fafc;
}

.flavor-materiales-lista {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e2e8f0;
}

.flavor-materiales-lista h4 {
    margin: 0 0 12px 0;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 6px;
}

.flavor-materiales-lista h4 .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #64748b;
}

.flavor-materiales-lista-items {
    font-size: 13px;
    color: #1e293b;
    line-height: 1.8;
    white-space: pre-line;
    max-height: 150px;
    overflow-y: auto;
}

/* Footer del card */
.flavor-materiales-card-footer {
    padding: 16px 20px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.flavor-materiales-participantes {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-materiales-participantes-bar {
    width: 100px;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-materiales-participantes-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    border-radius: 4px;
}

.flavor-materiales-participantes-text {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

/* Vacío */
.flavor-materiales-empty {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-materiales-empty .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.flavor-materiales-empty h3 {
    margin: 0 0 8px 0;
    color: #475569;
}

.flavor-materiales-empty p {
    color: #64748b;
    margin: 0 0 20px 0;
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-archive" style="margin-right: 8px;"></span>
        <?php echo esc_html__('Gestión de Materiales', 'flavor-platform'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="flavor-materiales-wrapper">
        <?php if (!$tablas_talleres_disponibles): ?>
            <div class="flavor-materiales-demo-notice">
                <span class="dashicons dashicons-info"></span>
                <span><?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Talleres.', 'flavor-platform'); ?></span>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="flavor-materiales-stats">
            <div class="flavor-materiales-stat-card">
                <div class="flavor-materiales-stat-icon total">
                    <span class="dashicons dashicons-archive"></span>
                </div>
                <div class="flavor-materiales-stat-content">
                    <h3><?php echo number_format($total_talleres_materiales); ?></h3>
                    <span><?php echo esc_html__('Talleres con Materiales', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="flavor-materiales-stat-card">
                <div class="flavor-materiales-stat-icon incluidos">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="flavor-materiales-stat-content">
                    <h3><?php echo number_format($talleres_materiales_incluidos); ?></h3>
                    <span><?php echo esc_html__('Materiales Incluidos', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="flavor-materiales-stat-card">
                <div class="flavor-materiales-stat-icon propios">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="flavor-materiales-stat-content">
                    <h3><?php echo number_format($talleres_material_propio); ?></h3>
                    <span><?php echo esc_html__('Material Propio', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="flavor-materiales-stat-card">
                <div class="flavor-materiales-stat-icon urgentes">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="flavor-materiales-stat-content">
                    <h3><?php echo number_format($talleres_urgentes); ?></h3>
                    <span><?php echo esc_html__('Urgentes (7 días)', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="flavor-materiales-stat-card">
                <div class="flavor-materiales-stat-icon participantes">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="flavor-materiales-stat-content">
                    <h3><?php echo number_format($participantes_totales); ?></h3>
                    <span><?php echo esc_html__('Participantes', 'flavor-platform'); ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="get" class="flavor-materiales-filters">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <?php if (isset($_GET['tab'])): ?>
                <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>">
            <?php endif; ?>

            <div class="flavor-materiales-filters-grid">
                <div class="flavor-materiales-filter-group">
                    <label><?php echo esc_html__('Buscar', 'flavor-platform'); ?></label>
                    <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php echo esc_attr__('Taller, organizador, material...', 'flavor-platform'); ?>">
                </div>

                <div class="flavor-materiales-filter-group">
                    <label><?php echo esc_html__('Estado', 'flavor-platform'); ?></label>
                    <select name="estado">
                        <option value=""><?php echo esc_html__('Todos', 'flavor-platform'); ?></option>
                        <option value="publicado" <?php selected($filtro_estado, 'publicado'); ?>><?php echo esc_html__('Publicado', 'flavor-platform'); ?></option>
                        <option value="confirmado" <?php selected($filtro_estado, 'confirmado'); ?>><?php echo esc_html__('Confirmado', 'flavor-platform'); ?></option>
                        <option value="en_curso" <?php selected($filtro_estado, 'en_curso'); ?>><?php echo esc_html__('En curso', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-materiales-filter-group">
                    <label><?php echo esc_html__('Materiales', 'flavor-platform'); ?></label>
                    <select name="materiales_incluidos">
                        <option value=""><?php echo esc_html__('Todos', 'flavor-platform'); ?></option>
                        <option value="1" <?php selected($filtro_materiales_incluidos, '1'); ?>><?php echo esc_html__('Incluidos', 'flavor-platform'); ?></option>
                        <option value="0" <?php selected($filtro_materiales_incluidos, '0'); ?>><?php echo esc_html__('Propios', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-materiales-filter-group">
                    <label><?php echo esc_html__('Ordenar por', 'flavor-platform'); ?></label>
                    <select name="orderby">
                        <option value="fecha_inicio" <?php selected($filtro_orden, 'fecha_inicio'); ?>><?php echo esc_html__('Fecha inicio', 'flavor-platform'); ?></option>
                        <option value="titulo" <?php selected($filtro_orden, 'titulo'); ?>><?php echo esc_html__('Título', 'flavor-platform'); ?></option>
                        <option value="inscritos_actuales" <?php selected($filtro_orden, 'inscritos_actuales'); ?>><?php echo esc_html__('Participantes', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-materiales-filters-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                        <?php echo esc_html__('Filtrar', 'flavor-platform'); ?>
                    </button>
                    <a href="<?php echo esc_url(remove_query_arg(['s', 'estado', 'materiales_incluidos', 'orderby', 'order'])); ?>" class="button">
                        <?php echo esc_html__('Limpiar', 'flavor-platform'); ?>
                    </a>
                </div>
            </div>
        </form>

        <!-- Grid de talleres -->
        <?php if (!empty($talleres_con_materiales)): ?>
            <div class="flavor-materiales-grid">
                <?php foreach ($talleres_con_materiales as $taller): ?>
                    <?php
                    $badge_estado = obtener_badge_estado_taller_materiales($taller->estado);
                    $urgencia = calcular_urgencia_materiales($taller->fecha_inicio ?? null, $taller->estado);
                    $ocupacion_pct = $taller->max_participantes > 0 ? round(($taller->inscritos_actuales / $taller->max_participantes) * 100) : 0;
                    ?>
                    <div class="flavor-materiales-card">
                        <div class="flavor-materiales-card-header">
                            <div class="flavor-materiales-card-title">
                                <h3><?php echo esc_html($taller->titulo); ?></h3>
                                <span class="flavor-materiales-badge <?php echo esc_attr($badge_estado['clase']); ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($badge_estado['icono']); ?>"></span>
                                    <?php echo esc_html($badge_estado['texto']); ?>
                                </span>
                            </div>
                            <div class="flavor-materiales-card-meta">
                                <span class="flavor-materiales-card-meta-item">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($taller->organizador ?? __('Sin asignar', 'flavor-platform')); ?>
                                </span>
                                <?php if (!empty($taller->fecha_inicio)): ?>
                                    <span class="flavor-materiales-card-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <?php echo date_i18n('d M Y', strtotime($taller->fecha_inicio)); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="flavor-materiales-urgencia <?php echo esc_attr($urgencia['clase']); ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($urgencia['icono']); ?>"></span>
                                    <?php echo esc_html($urgencia['texto']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flavor-materiales-card-body">
                            <div class="flavor-materiales-lista">
                                <h4>
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php echo esc_html__('Materiales necesarios', 'flavor-platform'); ?>
                                    <?php if ($taller->materiales_incluidos): ?>
                                        <span class="flavor-materiales-tipo incluidos">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php echo esc_html__('Incluidos', 'flavor-platform'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="flavor-materiales-tipo propios">
                                            <span class="dashicons dashicons-admin-users"></span>
                                            <?php echo esc_html__('Propios', 'flavor-platform'); ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <div class="flavor-materiales-lista-items">
                                    <?php echo esc_html($taller->materiales_necesarios); ?>
                                </div>
                            </div>
                        </div>

                        <div class="flavor-materiales-card-footer">
                            <div class="flavor-materiales-participantes">
                                <div class="flavor-materiales-participantes-bar">
                                    <div class="flavor-materiales-participantes-fill" style="width: <?php echo $ocupacion_pct; ?>%;"></div>
                                </div>
                                <span class="flavor-materiales-participantes-text">
                                    <?php echo esc_html($taller->inscritos_actuales); ?>/<?php echo esc_html($taller->max_participantes); ?>
                                </span>
                            </div>
                            <button class="button button-small">
                                <span class="dashicons dashicons-edit" style="font-size: 14px; line-height: 1.4;"></span>
                                <?php echo esc_html__('Editar', 'flavor-platform'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="flavor-materiales-empty">
                <span class="dashicons dashicons-archive"></span>
                <h3><?php echo esc_html__('No hay talleres con materiales', 'flavor-platform'); ?></h3>
                <p><?php echo esc_html__('No se encontraron talleres con materiales especificados.', 'flavor-platform'); ?></p>
                <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || $filtro_materiales_incluidos !== ''): ?>
                    <a href="<?php echo esc_url(remove_query_arg(['s', 'estado', 'materiales_incluidos', 'orderby', 'order'])); ?>" class="button">
                        <?php echo esc_html__('Ver todos los talleres', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

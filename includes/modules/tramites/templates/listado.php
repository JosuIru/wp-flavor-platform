<?php
/**
 * Template: Listado de Tramites
 *
 * Muestra un listado de todos los expedientes/solicitudes de tramites
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
$tabla_estados = $wpdb->prefix . 'flavor_estados_tramite';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_expedientes)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? intval($_GET['tipo']) : 0;
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';

// Construir query
$where = ["1=1"];
$params = [];

if ($estado_filtro) {
    $where[] = "e.estado_actual = %s";
    $params[] = $estado_filtro;
}

if ($tipo_filtro) {
    $where[] = "e.tipo_tramite_id = %d";
    $params[] = $tipo_filtro;
}

if ($buscar) {
    $where[] = "(e.numero_expediente LIKE %s OR e.observaciones LIKE %s)";
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

if ($fecha_desde) {
    $where[] = "DATE(e.fecha_solicitud) >= %s";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $where[] = "DATE(e.fecha_solicitud) <= %s";
    $params[] = $fecha_hasta;
}

$limite = isset($limit) ? intval($limit) : 50;
$where_sql = implode(' AND ', $where);

$query = "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color, u.display_name as solicitante_nombre
          FROM $tabla_expedientes e
          LEFT JOIN $tabla_tipos_tramite t ON e.tipo_tramite_id = t.id
          LEFT JOIN {$wpdb->users} u ON e.solicitante_id = u.ID
          WHERE $where_sql
          ORDER BY e.fecha_solicitud DESC
          LIMIT %d";
$params[] = $limite;

$expedientes = $wpdb->get_results($wpdb->prepare($query, $params));

// Obtener estados disponibles
$estados_disponibles = ['pendiente', 'en_proceso', 'en_revision', 'requiere_documentacion', 'aprobado', 'rechazado', 'resuelto', 'cancelado'];

// Obtener tipos de tramite para filtro
$tipos_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_tipos_tramite WHERE estado = 'activo' ORDER BY nombre ASC");

// Labels y colores para estados
$estados_labels = [
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'en_proceso' => __('En proceso', 'flavor-chat-ia'),
    'en_revision' => __('En revision', 'flavor-chat-ia'),
    'requiere_documentacion' => __('Requiere documentacion', 'flavor-chat-ia'),
    'aprobado' => __('Aprobado', 'flavor-chat-ia'),
    'rechazado' => __('Rechazado', 'flavor-chat-ia'),
    'resuelto' => __('Resuelto', 'flavor-chat-ia'),
    'cancelado' => __('Cancelado', 'flavor-chat-ia'),
];

$estados_colores = [
    'pendiente' => '#f59e0b',
    'en_proceso' => '#3b82f6',
    'en_revision' => '#8b5cf6',
    'requiere_documentacion' => '#f97316',
    'aprobado' => '#10b981',
    'rechazado' => '#ef4444',
    'resuelto' => '#059669',
    'cancelado' => '#6b7280',
];

// Estadisticas
$total_expedientes = count($expedientes);
$pendientes_count = 0;
$en_proceso_count = 0;
$resueltos_count = 0;

foreach ($expedientes as $exp) {
    if ($exp->estado_actual === 'pendiente') $pendientes_count++;
    elseif (in_array($exp->estado_actual, ['en_proceso', 'en_revision'])) $en_proceso_count++;
    elseif (in_array($exp->estado_actual, ['aprobado', 'resuelto'])) $resueltos_count++;
}

// URL base
$tramites_base_url = home_url('/mi-portal/tramites/');
?>

<div class="tramites-listado-wrapper">
    <div class="tramites-header">
        <h2><?php esc_html_e('Listado de Tramites', 'flavor-chat-ia'); ?></h2>
        <div class="tramites-header-actions">
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url($tramites_base_url . 'nuevo/'); ?>" class="btn btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nuevo tramite', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estadisticas rapidas -->
    <div class="tramites-stats-grid">
        <div class="stat-card">
            <span class="stat-valor"><?php echo esc_html($total_expedientes); ?></span>
            <span class="stat-label"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card warning">
            <span class="stat-valor"><?php echo esc_html($pendientes_count); ?></span>
            <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card info">
            <span class="stat-valor"><?php echo esc_html($en_proceso_count); ?></span>
            <span class="stat-label"><?php esc_html_e('En proceso', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card success">
            <span class="stat-valor"><?php echo esc_html($resueltos_count); ?></span>
            <span class="stat-label"><?php esc_html_e('Resueltos', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Filtros -->
    <?php $mostrar_filtros = isset($mostrar_filtros) ? $mostrar_filtros : true; ?>
    <?php if ($mostrar_filtros): ?>
    <form class="tramites-filtros" method="get">
        <div class="filtro-grupo filtro-buscar">
            <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>"
                   placeholder="<?php esc_attr_e('Buscar por numero de expediente...', 'flavor-chat-ia'); ?>">
        </div>
        <div class="filtro-grupo">
            <select name="estado">
                <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                <?php foreach ($estados_disponibles as $estado): ?>
                    <option value="<?php echo esc_attr($estado); ?>" <?php selected($estado_filtro, $estado); ?>>
                        <?php echo esc_html($estados_labels[$estado] ?? ucfirst($estado)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($tipos_disponibles): ?>
            <div class="filtro-grupo">
                <select name="tipo">
                    <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_disponibles as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo->id); ?>" <?php selected($tipo_filtro, $tipo->id); ?>>
                            <?php echo esc_html($tipo->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="filtro-grupo">
            <input type="date" name="fecha_desde" value="<?php echo esc_attr($fecha_desde); ?>"
                   placeholder="<?php esc_attr_e('Desde', 'flavor-chat-ia'); ?>">
        </div>
        <div class="filtro-grupo">
            <input type="date" name="fecha_hasta" value="<?php echo esc_attr($fecha_hasta); ?>"
                   placeholder="<?php esc_attr_e('Hasta', 'flavor-chat-ia'); ?>">
        </div>
        <button type="submit" class="btn btn-outline"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
        <?php if ($estado_filtro || $tipo_filtro || $buscar || $fecha_desde || $fecha_hasta): ?>
            <a href="<?php echo esc_url(remove_query_arg(['estado', 'tipo', 'buscar', 'fecha_desde', 'fecha_hasta'])); ?>" class="btn btn-text">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <!-- Listado -->
    <?php if ($expedientes): ?>
        <div class="tramites-tabla-wrapper">
            <table class="tramites-tabla">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Expediente', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Tipo de tramite', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Solicitante', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Prioridad', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expedientes as $expediente): ?>
                        <tr>
                            <td>
                                <strong class="expediente-numero"><?php echo esc_html($expediente->numero_expediente); ?></strong>
                            </td>
                            <td>
                                <div class="tipo-tramite-cell">
                                    <span class="tipo-icono" style="background: <?php echo esc_attr($expediente->tipo_color ?: '#6b7280'); ?>">
                                        <span class="dashicons <?php echo esc_attr($expediente->tipo_icono ?: 'dashicons-clipboard'); ?>"></span>
                                    </span>
                                    <span><?php echo esc_html($expediente->tipo_nombre ?: __('Sin tipo', 'flavor-chat-ia')); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($expediente->solicitante_nombre ?: __('Anonimo', 'flavor-chat-ia')); ?>
                            </td>
                            <td>
                                <span class="fecha-solicitud">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="estado-badge" style="background: <?php echo esc_attr($estados_colores[$expediente->estado_actual] ?? '#6b7280'); ?>">
                                    <?php echo esc_html($estados_labels[$expediente->estado_actual] ?? ucfirst($expediente->estado_actual)); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $prioridad_colores = [
                                    'baja' => '#10b981',
                                    'media' => '#3b82f6',
                                    'alta' => '#f59e0b',
                                    'urgente' => '#ef4444',
                                ];
                                $prioridad_color = $prioridad_colores[$expediente->prioridad] ?? '#6b7280';
                                ?>
                                <span class="prioridad-badge" style="color: <?php echo esc_attr($prioridad_color); ?>">
                                    <?php echo esc_html(ucfirst($expediente->prioridad)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $expediente->id); ?>" class="btn btn-sm btn-outline">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="tramites-empty">
            <span class="dashicons dashicons-clipboard"></span>
            <h3><?php esc_html_e('No hay tramites', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('No se encontraron tramites con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
            <?php if ($estado_filtro || $tipo_filtro || $buscar): ?>
                <a href="<?php echo esc_url(remove_query_arg(['estado', 'tipo', 'buscar', 'fecha_desde', 'fecha_hasta'])); ?>" class="btn btn-outline">
                    <?php esc_html_e('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.tramites-listado-wrapper { max-width: 1200px; margin: 0 auto; }
.tramites-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.tramites-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.tramites-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { background: white; border-radius: 10px; padding: 1.25rem; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.stat-card .stat-valor { display: block; font-size: 1.75rem; font-weight: 700; color: #1f2937; }
.stat-card .stat-label { font-size: 0.85rem; color: #6b7280; }
.stat-card.warning { border-top: 3px solid #f59e0b; }
.stat-card.info { border-top: 3px solid #3b82f6; }
.stat-card.success { border-top: 3px solid #10b981; }
.tramites-filtros { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.filtro-grupo select, .filtro-grupo input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
.filtro-buscar { flex: 1; min-width: 200px; }
.filtro-buscar input { width: 100%; }
.tramites-tabla-wrapper { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.tramites-tabla { width: 100%; border-collapse: collapse; }
.tramites-tabla thead { background: #f9fafb; }
.tramites-tabla th { padding: 1rem; text-align: left; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.tramites-tabla td { padding: 1rem; border-top: 1px solid #f3f4f6; font-size: 0.9rem; color: #374151; }
.tramites-tabla tbody tr:hover { background: #f9fafb; }
.expediente-numero { color: #3b82f6; font-family: monospace; font-size: 0.95rem; }
.tipo-tramite-cell { display: flex; align-items: center; gap: 0.75rem; }
.tipo-icono { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.tipo-icono .dashicons { color: white; font-size: 16px; width: 16px; height: 16px; }
.fecha-solicitud { color: #6b7280; font-size: 0.85rem; }
.estado-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; color: white; font-size: 0.75rem; font-weight: 500; }
.prioridad-badge { font-weight: 600; font-size: 0.85rem; }
.tramites-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.tramites-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.tramites-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-text { background: none; border: none; color: #6b7280; padding: 0.5rem; }
.btn-text:hover { color: #374151; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8rem; }
@media (max-width: 768px) {
    .tramites-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .tramites-tabla-wrapper { overflow-x: auto; }
    .tramites-tabla { min-width: 700px; }
}
</style>

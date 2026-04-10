<?php
/**
 * Template: Tramites Pendientes
 *
 * Muestra los tramites pendientes del usuario que requieren atencion
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    echo '<div class="tramites-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesion para ver tus tramites pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';

$usuario_id = get_current_user_id();

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_expedientes)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Estados considerados "pendientes" o que requieren atencion
$estados_pendientes = ['pendiente', 'en_proceso', 'en_revision', 'requiere_documentacion'];
$estados_placeholder = implode(',', array_fill(0, count($estados_pendientes), '%s'));

// Filtro por tipo de pendiente
$tipo_pendiente = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Obtener tramites pendientes del usuario
$params = array_merge([$usuario_id], $estados_pendientes);

$where_extra = "";
if ($tipo_pendiente === 'documentacion') {
    $where_extra = " AND e.estado_actual = 'requiere_documentacion'";
} elseif ($tipo_pendiente === 'revision') {
    $where_extra = " AND e.estado_actual IN ('en_proceso', 'en_revision')";
} elseif ($tipo_pendiente === 'inicio') {
    $where_extra = " AND e.estado_actual = 'pendiente'";
}

$tramites_pendientes = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color, t.plazo_resolucion_dias
     FROM $tabla_expedientes e
     LEFT JOIN $tabla_tipos_tramite t ON e.tipo_tramite_id = t.id
     WHERE e.solicitante_id = %d AND e.estado_actual IN ($estados_placeholder) $where_extra
     ORDER BY
        CASE e.prioridad
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            ELSE 4
        END,
        CASE e.estado_actual
            WHEN 'requiere_documentacion' THEN 1
            ELSE 2
        END,
        e.fecha_solicitud ASC",
    $params
));

// Contadores por tipo
$count_total = count($tramites_pendientes);
$count_documentacion = 0;
$count_revision = 0;
$count_inicio = 0;

foreach ($tramites_pendientes as $tramite) {
    if ($tramite->estado_actual === 'requiere_documentacion') $count_documentacion++;
    elseif (in_array($tramite->estado_actual, ['en_proceso', 'en_revision'])) $count_revision++;
    elseif ($tramite->estado_actual === 'pendiente') $count_inicio++;
}

// Labels y colores para estados
$estados_labels = [
    'pendiente' => __('Pendiente de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_revision' => __('En revision', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'requiere_documentacion' => __('Requiere documentacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estados_colores = [
    'pendiente' => '#f59e0b',
    'en_proceso' => '#3b82f6',
    'en_revision' => '#8b5cf6',
    'requiere_documentacion' => '#f97316',
];

$estados_iconos = [
    'pendiente' => 'dashicons-clock',
    'en_proceso' => 'dashicons-update',
    'en_revision' => 'dashicons-visibility',
    'requiere_documentacion' => 'dashicons-media-document',
];

$prioridad_labels = [
    'urgente' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'alta' => __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'media' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'baja' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$prioridad_colores = [
    'urgente' => '#ef4444',
    'alta' => '#f59e0b',
    'media' => '#3b82f6',
    'baja' => '#10b981',
];

$tramites_base_url = Flavor_Platform_Helpers::get_action_url('tramites', '');
$current_url = $tramites_base_url . 'pendientes/';
?>

<div class="tramites-pendientes-wrapper">
    <div class="pendientes-header">
        <nav class="tramites-breadcrumb">
            <a href="<?php echo esc_url($tramites_base_url . 'mis-tramites/'); ?>"><?php esc_html_e('Mis tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <span class="separator">&rsaquo;</span>
            <span><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </nav>
        <h2><?php esc_html_e('Tramites Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="header-intro"><?php esc_html_e('Tramites que requieren tu atencion o estan en proceso.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>

    <!-- Filtros por tipo de pendiente -->
    <div class="pendientes-filtros-tabs">
        <a href="<?php echo esc_url($current_url); ?>" class="filtro-tab <?php echo empty($tipo_pendiente) ? 'activo' : ''; ?>">
            <span class="filtro-count"><?php echo intval($count_total); ?></span>
            <span class="filtro-label"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tipo', 'documentacion', $current_url)); ?>" class="filtro-tab warning <?php echo $tipo_pendiente === 'documentacion' ? 'activo' : ''; ?>">
            <span class="filtro-count"><?php echo intval($count_documentacion); ?></span>
            <span class="filtro-label"><?php esc_html_e('Requieren documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tipo', 'revision', $current_url)); ?>" class="filtro-tab info <?php echo $tipo_pendiente === 'revision' ? 'activo' : ''; ?>">
            <span class="filtro-count"><?php echo intval($count_revision); ?></span>
            <span class="filtro-label"><?php esc_html_e('En revision', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tipo', 'inicio', $current_url)); ?>" class="filtro-tab <?php echo $tipo_pendiente === 'inicio' ? 'activo' : ''; ?>">
            <span class="filtro-count"><?php echo intval($count_inicio); ?></span>
            <span class="filtro-label"><?php esc_html_e('Pendiente inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- Alerta para tramites que requieren documentacion -->
    <?php if ($count_documentacion > 0 && empty($tipo_pendiente)): ?>
    <div class="pendientes-alerta-global">
        <span class="dashicons dashicons-warning"></span>
        <div class="alerta-content">
            <strong><?php echo sprintf(esc_html__('%d tramite(s) requieren documentacion', FLAVOR_PLATFORM_TEXT_DOMAIN), $count_documentacion); ?></strong>
            <p><?php esc_html_e('Completa la documentacion solicitada para que puedan continuar siendo procesados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <a href="<?php echo esc_url(add_query_arg('tipo', 'documentacion', $current_url)); ?>" class="btn btn-sm btn-outline">
            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Listado de tramites pendientes -->
    <?php if ($tramites_pendientes): ?>
        <div class="pendientes-lista">
            <?php foreach ($tramites_pendientes as $tramite):
                $estado_color = $estados_colores[$tramite->estado_actual] ?? '#6b7280';
                $estado_icono = $estados_iconos[$tramite->estado_actual] ?? 'dashicons-clipboard';
                $prioridad_color = $prioridad_colores[$tramite->prioridad] ?? '#6b7280';

                // Calcular dias transcurridos y restantes
                $dias_transcurridos = floor((time() - strtotime($tramite->fecha_solicitud)) / DAY_IN_SECONDS);
                $dias_restantes = null;
                $vencido = false;
                if ($tramite->fecha_limite) {
                    $dias_restantes = floor((strtotime($tramite->fecha_limite) - time()) / DAY_IN_SECONDS);
                    $vencido = $dias_restantes < 0;
                }
            ?>
                <div class="pendiente-card <?php echo $tramite->estado_actual === 'requiere_documentacion' ? 'requiere-accion' : ''; ?> <?php echo $vencido ? 'vencido' : ''; ?>">
                    <div class="pendiente-estado-bar" style="background: <?php echo esc_attr($estado_color); ?>"></div>

                    <div class="pendiente-content">
                        <div class="pendiente-header">
                            <div class="pendiente-tipo">
                                <span class="tipo-icono" style="background: <?php echo esc_attr($tramite->tipo_color ?: '#6b7280'); ?>">
                                    <span class="dashicons <?php echo esc_attr($tramite->tipo_icono ?: 'dashicons-clipboard'); ?>"></span>
                                </span>
                                <div class="tipo-info">
                                    <h4><?php echo esc_html($tramite->tipo_nombre ?: __('Tramite', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h4>
                                    <span class="expediente-numero"><?php echo esc_html($tramite->numero_expediente); ?></span>
                                </div>
                            </div>
                            <div class="pendiente-badges">
                                <span class="estado-badge" style="background: <?php echo esc_attr($estado_color); ?>">
                                    <span class="dashicons <?php echo esc_attr($estado_icono); ?>"></span>
                                    <?php echo esc_html($estados_labels[$tramite->estado_actual] ?? ucfirst($tramite->estado_actual)); ?>
                                </span>
                                <?php if ($tramite->prioridad !== 'media'): ?>
                                    <span class="prioridad-badge" style="color: <?php echo esc_attr($prioridad_color); ?>; border-color: <?php echo esc_attr($prioridad_color); ?>">
                                        <?php echo esc_html($prioridad_labels[$tramite->prioridad] ?? ucfirst($tramite->prioridad)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pendiente-timeline">
                            <span class="timeline-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo sprintf(esc_html__('Iniciado hace %d dias', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_transcurridos); ?>
                            </span>
                            <?php if ($dias_restantes !== null): ?>
                                <span class="timeline-item <?php echo $vencido ? 'vencido' : ($dias_restantes <= 3 ? 'urgente' : ''); ?>">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php if ($vencido): ?>
                                        <?php echo sprintf(esc_html__('Vencido hace %d dias', FLAVOR_PLATFORM_TEXT_DOMAIN), abs($dias_restantes)); ?>
                                    <?php else: ?>
                                        <?php echo sprintf(esc_html__('%d dias restantes', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($tramite->estado_actual === 'requiere_documentacion'): ?>
                            <div class="pendiente-accion-requerida">
                                <span class="dashicons dashicons-warning"></span>
                                <span><?php esc_html_e('Se requiere que subas documentacion adicional para continuar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pendiente-actions">
                        <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $tramite->id); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e('Ver detalle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <?php if ($tramite->estado_actual === 'requiere_documentacion'): ?>
                            <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $tramite->id . '#documentos'); ?>" class="btn btn-outline btn-sm">
                                <span class="dashicons dashicons-upload"></span>
                                <?php esc_html_e('Subir docs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="pendientes-empty">
            <span class="dashicons dashicons-yes-alt"></span>
            <h3><?php esc_html_e('No tienes tramites pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Todos tus tramites estan al dia. Puedes iniciar un nuevo tramite cuando lo necesites.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="empty-actions">
                <a href="<?php echo esc_url($tramites_base_url . 'mis-tramites/'); ?>" class="btn btn-outline">
                    <?php esc_html_e('Ver mis tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url($tramites_base_url . 'nuevo/'); ?>" class="btn btn-primary">
                    <?php esc_html_e('Nuevo tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.tramites-pendientes-wrapper { max-width: 900px; margin: 0 auto; }
.tramites-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-login-required .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.tramites-breadcrumb { margin-bottom: 1rem; font-size: 0.9rem; color: #6b7280; }
.tramites-breadcrumb a { color: #3b82f6; text-decoration: none; }
.tramites-breadcrumb .separator { margin: 0 0.5rem; }
.pendientes-header h2 { margin: 0 0 0.35rem; font-size: 1.5rem; color: #1f2937; }
.header-intro { margin: 0 0 1.5rem; color: #6b7280; }
.pendientes-filtros-tabs { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.filtro-tab { display: flex; flex-direction: column; align-items: center; padding: 1rem 1.5rem; background: white; border-radius: 10px; text-decoration: none; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: all 0.2s; flex: 1; min-width: 120px; }
.filtro-tab:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.filtro-tab.activo { box-shadow: 0 0 0 2px #3b82f6; }
.filtro-tab.warning .filtro-count { color: #f59e0b; }
.filtro-tab.info .filtro-count { color: #3b82f6; }
.filtro-count { font-size: 1.5rem; font-weight: 700; color: #1f2937; }
.filtro-label { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
.pendientes-alerta-global { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 1.5rem; }
.pendientes-alerta-global .dashicons { font-size: 28px; width: 28px; height: 28px; color: #f59e0b; flex-shrink: 0; }
.pendientes-alerta-global .alerta-content { flex: 1; }
.pendientes-alerta-global strong { color: #92400e; }
.pendientes-alerta-global p { margin: 0.25rem 0 0; font-size: 0.9rem; color: #92400e; }
.pendientes-lista { display: flex; flex-direction: column; gap: 1rem; }
.pendiente-card { display: flex; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; transition: all 0.2s; }
.pendiente-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.pendiente-card.requiere-accion { border: 2px solid #fde68a; }
.pendiente-card.vencido { border: 2px solid #fecaca; }
.pendiente-estado-bar { width: 6px; flex-shrink: 0; }
.pendiente-content { flex: 1; padding: 1.25rem; }
.pendiente-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; gap: 1rem; flex-wrap: wrap; }
.pendiente-tipo { display: flex; gap: 0.75rem; align-items: center; }
.tipo-icono { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.tipo-icono .dashicons { color: white; font-size: 22px; width: 22px; height: 22px; }
.tipo-info h4 { margin: 0; font-size: 1rem; color: #1f2937; }
.expediente-numero { font-family: monospace; font-size: 0.85rem; color: #6b7280; }
.pendiente-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.estado-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 5px 12px; border-radius: 6px; color: white; font-size: 0.8rem; font-weight: 500; }
.estado-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
.prioridad-badge { padding: 4px 10px; border: 1px solid; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background: transparent; }
.pendiente-timeline { display: flex; gap: 1.5rem; margin-bottom: 0.75rem; font-size: 0.85rem; color: #6b7280; }
.timeline-item { display: flex; align-items: center; gap: 0.35rem; }
.timeline-item .dashicons { font-size: 14px; width: 14px; height: 14px; }
.timeline-item.urgente { color: #f59e0b; font-weight: 500; }
.timeline-item.vencido { color: #ef4444; font-weight: 500; }
.pendiente-accion-requerida { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #fef3c7; border-radius: 6px; font-size: 0.85rem; color: #92400e; }
.pendiente-accion-requerida .dashicons { color: #f59e0b; font-size: 18px; width: 18px; height: 18px; }
.pendiente-actions { display: flex; flex-direction: column; gap: 0.5rem; padding: 1.25rem; justify-content: center; }
.pendientes-empty { text-align: center; padding: 3rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; }
.pendientes-empty .dashicons { font-size: 56px; width: 56px; height: 56px; color: #10b981; margin-bottom: 1rem; }
.pendientes-empty h3 { margin: 0 0 0.5rem; color: #065f46; }
.pendientes-empty p { margin: 0 0 1.5rem; color: #047857; }
.empty-actions { display: flex; gap: 0.75rem; justify-content: center; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.95rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
.btn .dashicons { font-size: 16px; width: 16px; height: 16px; }
@media (max-width: 640px) {
    .pendientes-filtros-tabs { flex-direction: column; }
    .filtro-tab { flex-direction: row; justify-content: center; gap: 0.75rem; }
    .pendiente-card { flex-direction: column; }
    .pendiente-estado-bar { width: 100%; height: 4px; }
    .pendiente-actions { flex-direction: row; padding: 1rem 1.25rem; border-top: 1px solid #f3f4f6; }
}
</style>

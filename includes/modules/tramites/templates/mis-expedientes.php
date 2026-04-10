<?php
/**
 * Template: Mis Expedientes
 *
 * Muestra los expedientes del usuario actual con seguimiento detallado
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
    echo '<h3>' . esc_html__('Inicia sesion para ver tus expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
$tabla_historial = $wpdb->prefix . 'flavor_historial_estados_expediente';
$tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';

$usuario_id = get_current_user_id();

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_expedientes)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

// Obtener expedientes del usuario
$where = "e.solicitante_id = %d";
$params = [$usuario_id];

if ($estado_filtro) {
    $where .= " AND e.estado_actual = %s";
    $params[] = $estado_filtro;
}

$expedientes = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color
     FROM $tabla_expedientes e
     LEFT JOIN $tabla_tipos_tramite t ON e.tipo_tramite_id = t.id
     WHERE $where
     ORDER BY e.fecha_solicitud DESC",
    $params
));

// Estadisticas del usuario
$stats = [
    'total' => 0,
    'en_tramite' => 0,
    'resueltos' => 0,
    'pendiente_doc' => 0,
];

foreach ($expedientes as $exp) {
    $stats['total']++;
    if (in_array($exp->estado_actual, ['pendiente', 'en_proceso', 'en_revision'])) {
        $stats['en_tramite']++;
    } elseif (in_array($exp->estado_actual, ['aprobado', 'resuelto'])) {
        $stats['resueltos']++;
    } elseif ($exp->estado_actual === 'requiere_documentacion') {
        $stats['pendiente_doc']++;
    }
}

// Labels y colores para estados
$estados_labels = [
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_revision' => __('En revision', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'requiere_documentacion' => __('Requiere documentacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'aprobado' => __('Aprobado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'rechazado' => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'resuelto' => __('Resuelto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelado' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

$estados_iconos = [
    'pendiente' => 'dashicons-clock',
    'en_proceso' => 'dashicons-update',
    'en_revision' => 'dashicons-visibility',
    'requiere_documentacion' => 'dashicons-media-document',
    'aprobado' => 'dashicons-yes-alt',
    'rechazado' => 'dashicons-dismiss',
    'resuelto' => 'dashicons-yes',
    'cancelado' => 'dashicons-no',
];

$tramites_base_url = Flavor_Platform_Helpers::get_action_url('tramites', '');
?>

<div class="mis-expedientes-wrapper">
    <div class="expedientes-header">
        <h2><?php esc_html_e('Mis Expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <a href="<?php echo esc_url($tramites_base_url . 'nuevo/'); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nuevo tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <!-- Resumen estadisticas -->
    <div class="expedientes-stats">
        <a href="<?php echo esc_url($tramites_base_url . 'mis-expedientes/'); ?>" class="stat-card <?php echo empty($estado_filtro) ? 'activo' : ''; ?>">
            <span class="stat-valor"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label"><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('estado', 'en_proceso', $tramites_base_url . 'mis-expedientes/')); ?>" class="stat-card info <?php echo $estado_filtro === 'en_proceso' ? 'activo' : ''; ?>">
            <span class="stat-valor"><?php echo esc_html($stats['en_tramite']); ?></span>
            <span class="stat-label"><?php esc_html_e('En tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('estado', 'requiere_documentacion', $tramites_base_url . 'mis-expedientes/')); ?>" class="stat-card warning <?php echo $estado_filtro === 'requiere_documentacion' ? 'activo' : ''; ?>">
            <span class="stat-valor"><?php echo esc_html($stats['pendiente_doc']); ?></span>
            <span class="stat-label"><?php esc_html_e('Pendiente doc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('estado', 'resuelto', $tramites_base_url . 'mis-expedientes/')); ?>" class="stat-card success <?php echo $estado_filtro === 'resuelto' ? 'activo' : ''; ?>">
            <span class="stat-valor"><?php echo esc_html($stats['resueltos']); ?></span>
            <span class="stat-label"><?php esc_html_e('Resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <?php if ($estado_filtro): ?>
    <div class="filtro-activo">
        <span><?php esc_html_e('Filtrando por:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <strong><?php echo esc_html($estados_labels[$estado_filtro] ?? $estado_filtro); ?></strong></span>
        <a href="<?php echo esc_url($tramites_base_url . 'mis-expedientes/'); ?>" class="limpiar-filtro">
            <span class="dashicons dashicons-no-alt"></span>
        </a>
    </div>
    <?php endif; ?>

    <!-- Listado de expedientes -->
    <?php if ($expedientes): ?>
        <div class="expedientes-lista">
            <?php foreach ($expedientes as $expediente):
                // Obtener ultimo movimiento
                $ultimo_movimiento = null;
                if (Flavor_Platform_Helpers::tabla_existe($tabla_historial)) {
                    $ultimo_movimiento = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $tabla_historial WHERE expediente_id = %d ORDER BY fecha_cambio DESC LIMIT 1",
                        $expediente->id
                    ));
                }

                // Contar documentos
                $num_documentos = 0;
                if (Flavor_Platform_Helpers::tabla_existe($tabla_documentos)) {
                    $num_documentos = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $tabla_documentos WHERE expediente_id = %d",
                        $expediente->id
                    ));
                }

                $estado_color = $estados_colores[$expediente->estado_actual] ?? '#6b7280';
                $estado_icono = $estados_iconos[$expediente->estado_actual] ?? 'dashicons-clipboard';
            ?>
                <div class="expediente-card">
                    <div class="expediente-estado-indicator" style="background: <?php echo esc_attr($estado_color); ?>"></div>

                    <div class="expediente-content">
                        <div class="expediente-header">
                            <div class="expediente-tipo">
                                <span class="tipo-icono" style="background: <?php echo esc_attr($expediente->tipo_color ?: '#6b7280'); ?>">
                                    <span class="dashicons <?php echo esc_attr($expediente->tipo_icono ?: 'dashicons-clipboard'); ?>"></span>
                                </span>
                                <div class="tipo-info">
                                    <h4><?php echo esc_html($expediente->tipo_nombre ?: __('Tramite', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h4>
                                    <span class="expediente-numero"><?php echo esc_html($expediente->numero_expediente); ?></span>
                                </div>
                            </div>
                            <span class="estado-badge" style="background: <?php echo esc_attr($estado_color); ?>">
                                <span class="dashicons <?php echo esc_attr($estado_icono); ?>"></span>
                                <?php echo esc_html($estados_labels[$expediente->estado_actual] ?? ucfirst($expediente->estado_actual)); ?>
                            </span>
                        </div>

                        <div class="expediente-meta">
                            <span class="meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo sprintf(
                                    esc_html__('Solicitado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))
                                ); ?>
                            </span>
                            <?php if ($expediente->fecha_limite): ?>
                                <span class="meta-item <?php echo strtotime($expediente->fecha_limite) < time() ? 'vencido' : ''; ?>">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo sprintf(
                                        esc_html__('Plazo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        date_i18n(get_option('date_format'), strtotime($expediente->fecha_limite))
                                    ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($num_documentos > 0): ?>
                                <span class="meta-item">
                                    <span class="dashicons dashicons-media-default"></span>
                                    <?php echo sprintf(esc_html__('%d documentos', FLAVOR_PLATFORM_TEXT_DOMAIN), $num_documentos); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($ultimo_movimiento && !empty($ultimo_movimiento->comentario)): ?>
                            <div class="expediente-ultimo-movimiento">
                                <span class="movimiento-fecha">
                                    <?php echo esc_html(date_i18n('d M Y H:i', strtotime($ultimo_movimiento->fecha_cambio))); ?>
                                </span>
                                <p class="movimiento-comentario"><?php echo esc_html(wp_trim_words($ultimo_movimiento->comentario, 20)); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($expediente->estado_actual === 'requiere_documentacion'): ?>
                            <div class="expediente-alerta">
                                <span class="dashicons dashicons-warning"></span>
                                <span><?php esc_html_e('Se requiere documentacion adicional para continuar con el tramite.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="expediente-actions">
                        <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $expediente->id); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e('Ver detalle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <?php if ($expediente->estado_actual === 'requiere_documentacion'): ?>
                            <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $expediente->id . '#documentos'); ?>" class="btn btn-outline btn-sm">
                                <?php esc_html_e('Subir docs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="expedientes-empty">
            <span class="dashicons dashicons-portfolio"></span>
            <h3><?php esc_html_e('No tienes expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Cuando inicies un tramite, podras seguir su estado desde aqui.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url($tramites_base_url . 'catalogo/'); ?>" class="btn btn-primary">
                <?php esc_html_e('Ver catalogo de tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.mis-expedientes-wrapper { max-width: 900px; margin: 0 auto; }
.tramites-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-login-required .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.expedientes-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.expedientes-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.expedientes-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { display: flex; flex-direction: column; align-items: center; background: white; border-radius: 10px; padding: 1.25rem; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.05); text-decoration: none; cursor: pointer; transition: all 0.2s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.stat-card.activo { box-shadow: 0 0 0 2px #3b82f6; }
.stat-card .stat-valor { font-size: 1.75rem; font-weight: 700; color: #1f2937; }
.stat-card .stat-label { font-size: 0.8rem; color: #6b7280; }
.stat-card.warning { border-top: 3px solid #f59e0b; }
.stat-card.info { border-top: 3px solid #3b82f6; }
.stat-card.success { border-top: 3px solid #10b981; }
.filtro-activo { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #eff6ff; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; color: #1e40af; }
.limpiar-filtro { color: #3b82f6; display: flex; align-items: center; }
.limpiar-filtro:hover { color: #1d4ed8; }
.expedientes-lista { display: flex; flex-direction: column; gap: 1rem; }
.expediente-card { display: flex; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; transition: all 0.2s; }
.expediente-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.expediente-estado-indicator { width: 6px; flex-shrink: 0; }
.expediente-content { flex: 1; padding: 1.25rem; }
.expediente-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; gap: 1rem; flex-wrap: wrap; }
.expediente-tipo { display: flex; gap: 0.75rem; align-items: center; }
.tipo-icono { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.tipo-icono .dashicons { color: white; font-size: 22px; width: 22px; height: 22px; }
.tipo-info h4 { margin: 0; font-size: 1rem; color: #1f2937; }
.expediente-numero { font-family: monospace; font-size: 0.85rem; color: #6b7280; }
.estado-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 5px 12px; border-radius: 6px; color: white; font-size: 0.8rem; font-weight: 500; }
.estado-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
.expediente-meta { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem; font-size: 0.85rem; color: #6b7280; }
.meta-item { display: flex; align-items: center; gap: 0.35rem; }
.meta-item .dashicons { font-size: 14px; width: 14px; height: 14px; }
.meta-item.vencido { color: #ef4444; }
.expediente-ultimo-movimiento { background: #f9fafb; border-radius: 6px; padding: 0.75rem; margin-bottom: 0.75rem; }
.movimiento-fecha { font-size: 0.75rem; color: #9ca3af; }
.movimiento-comentario { margin: 0.35rem 0 0; font-size: 0.85rem; color: #6b7280; }
.expediente-alerta { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #fef3c7; border-radius: 6px; font-size: 0.85rem; color: #92400e; }
.expediente-alerta .dashicons { color: #f59e0b; }
.expediente-actions { display: flex; flex-direction: column; gap: 0.5rem; padding: 1.25rem; justify-content: center; }
.expedientes-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.expedientes-empty .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; margin-bottom: 1rem; }
.expedientes-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.expedientes-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
@media (max-width: 640px) {
    .expedientes-stats { grid-template-columns: repeat(2, 1fr); }
    .expediente-card { flex-direction: column; }
    .expediente-estado-indicator { width: 100%; height: 4px; }
    .expediente-actions { flex-direction: row; padding: 1rem 1.25rem; border-top: 1px solid #f3f4f6; }
}
</style>

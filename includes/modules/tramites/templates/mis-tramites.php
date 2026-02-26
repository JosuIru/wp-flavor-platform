<?php
/**
 * Template: Mis Tramites
 *
 * Panel principal del usuario para ver y gestionar sus tramites
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    echo '<div class="tramites-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesion para ver tus tramites', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Accede a tu cuenta para gestionar y dar seguimiento a tus tramites.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesion', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';

$usuario_id = get_current_user_id();
$usuario = wp_get_current_user();

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_expedientes)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Obtener tramites del usuario (ultimos 10)
$tramites_recientes = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color
     FROM $tabla_expedientes e
     LEFT JOIN $tabla_tipos_tramite t ON e.tipo_tramite_id = t.id
     WHERE e.solicitante_id = %d
     ORDER BY e.fecha_solicitud DESC
     LIMIT 10",
    $usuario_id
));

// Estadisticas del usuario
$stats = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado_actual IN ('pendiente', 'en_proceso', 'en_revision') THEN 1 ELSE 0 END) as en_tramite,
        SUM(CASE WHEN estado_actual = 'requiere_documentacion' THEN 1 ELSE 0 END) as pendiente_doc,
        SUM(CASE WHEN estado_actual IN ('aprobado', 'resuelto') THEN 1 ELSE 0 END) as resueltos,
        SUM(CASE WHEN estado_actual = 'rechazado' THEN 1 ELSE 0 END) as rechazados
     FROM $tabla_expedientes
     WHERE solicitante_id = %d",
    $usuario_id
), ARRAY_A) ?: ['total' => 0, 'en_tramite' => 0, 'pendiente_doc' => 0, 'resueltos' => 0, 'rechazados' => 0];

// Tramites que requieren accion
$tramites_accion = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color
     FROM $tabla_expedientes e
     LEFT JOIN $tabla_tipos_tramite t ON e.tipo_tramite_id = t.id
     WHERE e.solicitante_id = %d AND e.estado_actual = 'requiere_documentacion'
     ORDER BY e.fecha_solicitud DESC
     LIMIT 5",
    $usuario_id
));

// Tramites populares para sugerir
$tramites_populares = $wpdb->get_results(
    "SELECT * FROM $tabla_tipos_tramite
     WHERE estado = 'activo' AND permite_online = 1
     ORDER BY RAND()
     LIMIT 4"
);

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

$tramites_base_url = home_url('/mi-portal/tramites/');
?>

<div class="mis-tramites-wrapper">
    <!-- Cabecera con bienvenida -->
    <div class="tramites-welcome">
        <div class="welcome-content">
            <h2><?php echo sprintf(esc_html__('Hola, %s', 'flavor-chat-ia'), esc_html($usuario->display_name)); ?></h2>
            <p><?php esc_html_e('Gestiona y da seguimiento a todos tus tramites desde aqui.', 'flavor-chat-ia'); ?></p>
        </div>
        <a href="<?php echo esc_url($tramites_base_url . 'catalogo/'); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nuevo tramite', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Estadisticas -->
    <div class="tramites-kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icono">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="kpi-info">
                <span class="kpi-valor"><?php echo intval($stats['total']); ?></span>
                <span class="kpi-label"><?php esc_html_e('Tramites totales', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="kpi-card info">
            <div class="kpi-icono">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div class="kpi-info">
                <span class="kpi-valor"><?php echo intval($stats['en_tramite']); ?></span>
                <span class="kpi-label"><?php esc_html_e('En tramite', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="kpi-card warning">
            <div class="kpi-icono">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="kpi-info">
                <span class="kpi-valor"><?php echo intval($stats['pendiente_doc']); ?></span>
                <span class="kpi-label"><?php esc_html_e('Requieren accion', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="kpi-card success">
            <div class="kpi-icono">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="kpi-info">
                <span class="kpi-valor"><?php echo intval($stats['resueltos']); ?></span>
                <span class="kpi-label"><?php esc_html_e('Resueltos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Alertas de tramites que requieren accion -->
    <?php if ($tramites_accion): ?>
    <div class="tramites-alertas">
        <h3>
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Tramites que requieren tu atencion', 'flavor-chat-ia'); ?>
        </h3>
        <div class="alertas-lista">
            <?php foreach ($tramites_accion as $tramite): ?>
                <div class="alerta-item">
                    <div class="alerta-icono" style="background: <?php echo esc_attr($tramite->tipo_color ?: '#f97316'); ?>">
                        <span class="dashicons <?php echo esc_attr($tramite->tipo_icono ?: 'dashicons-media-document'); ?>"></span>
                    </div>
                    <div class="alerta-content">
                        <h4><?php echo esc_html($tramite->tipo_nombre); ?></h4>
                        <p><?php esc_html_e('Se requiere documentacion adicional para continuar.', 'flavor-chat-ia'); ?></p>
                        <span class="alerta-expediente"><?php echo esc_html($tramite->numero_expediente); ?></span>
                    </div>
                    <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $tramite->id); ?>" class="btn btn-sm btn-primary">
                        <?php esc_html_e('Completar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tramites-content-grid">
        <!-- Tramites recientes -->
        <div class="tramites-recientes">
            <div class="panel-header">
                <h3><?php esc_html_e('Tramites recientes', 'flavor-chat-ia'); ?></h3>
                <a href="<?php echo esc_url($tramites_base_url . 'mis-expedientes/'); ?>" class="ver-todos">
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </a>
            </div>

            <?php if ($tramites_recientes): ?>
                <div class="tramites-lista">
                    <?php foreach ($tramites_recientes as $tramite):
                        $estado_color = $estados_colores[$tramite->estado_actual] ?? '#6b7280';
                    ?>
                        <a href="<?php echo esc_url($tramites_base_url . 'expediente/?id=' . $tramite->id); ?>" class="tramite-item">
                            <div class="tramite-estado-dot" style="background: <?php echo esc_attr($estado_color); ?>"></div>
                            <div class="tramite-info">
                                <span class="tramite-tipo"><?php echo esc_html($tramite->tipo_nombre ?: __('Tramite', 'flavor-chat-ia')); ?></span>
                                <span class="tramite-expediente"><?php echo esc_html($tramite->numero_expediente); ?></span>
                            </div>
                            <div class="tramite-meta-right">
                                <span class="tramite-estado" style="color: <?php echo esc_attr($estado_color); ?>">
                                    <?php echo esc_html($estados_labels[$tramite->estado_actual] ?? ucfirst($tramite->estado_actual)); ?>
                                </span>
                                <span class="tramite-fecha">
                                    <?php echo esc_html(date_i18n('d M', strtotime($tramite->fecha_solicitud))); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="panel-empty">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('Aun no has realizado ningun tramite.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tramites sugeridos -->
        <div class="tramites-sugeridos">
            <div class="panel-header">
                <h3><?php esc_html_e('Tramites populares', 'flavor-chat-ia'); ?></h3>
            </div>

            <?php if ($tramites_populares): ?>
                <div class="sugeridos-lista">
                    <?php foreach ($tramites_populares as $tipo): ?>
                        <a href="<?php echo esc_url($tramites_base_url . 'iniciar/?tipo=' . $tipo->id); ?>" class="sugerido-item">
                            <span class="sugerido-icono" style="background: <?php echo esc_attr($tipo->color ?: '#6b7280'); ?>">
                                <span class="dashicons <?php echo esc_attr($tipo->icono ?: 'dashicons-clipboard'); ?>"></span>
                            </span>
                            <span class="sugerido-nombre"><?php echo esc_html($tipo->nombre); ?></span>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?php echo esc_url($tramites_base_url . 'catalogo/'); ?>" class="btn btn-outline btn-block">
                <?php esc_html_e('Ver todos los tramites', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Accesos rapidos -->
    <div class="tramites-accesos-rapidos">
        <h3><?php esc_html_e('Accesos rapidos', 'flavor-chat-ia'); ?></h3>
        <div class="accesos-grid">
            <a href="<?php echo esc_url($tramites_base_url . 'mis-expedientes/'); ?>" class="acceso-card">
                <span class="dashicons dashicons-portfolio"></span>
                <span><?php esc_html_e('Mis expedientes', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url($tramites_base_url . 'pendientes/'); ?>" class="acceso-card">
                <span class="dashicons dashicons-clock"></span>
                <span><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url($tramites_base_url . 'catalogo/'); ?>" class="acceso-card">
                <span class="dashicons dashicons-category"></span>
                <span><?php esc_html_e('Catalogo', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url($tramites_base_url . 'nuevo/'); ?>" class="acceso-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php esc_html_e('Nuevo tramite', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>
</div>

<style>
.mis-tramites-wrapper { max-width: 1100px; margin: 0 auto; }
.tramites-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-login-required .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.tramites-welcome { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; color: white; }
.welcome-content h2 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.welcome-content p { margin: 0; opacity: 0.9; }
.tramites-welcome .btn { background: white; color: #3b82f6; }
.tramites-welcome .btn:hover { background: #f3f4f6; }
.tramites-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
.kpi-card { display: flex; align-items: center; gap: 1rem; background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.kpi-card.info { border-left: 4px solid #3b82f6; }
.kpi-card.warning { border-left: 4px solid #f59e0b; }
.kpi-card.success { border-left: 4px solid #10b981; }
.kpi-icono { width: 48px; height: 48px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.kpi-icono .dashicons { font-size: 24px; width: 24px; height: 24px; color: #6b7280; }
.kpi-card.info .kpi-icono { background: #dbeafe; }
.kpi-card.info .kpi-icono .dashicons { color: #3b82f6; }
.kpi-card.warning .kpi-icono { background: #fef3c7; }
.kpi-card.warning .kpi-icono .dashicons { color: #f59e0b; }
.kpi-card.success .kpi-icono { background: #d1fae5; }
.kpi-card.success .kpi-icono .dashicons { color: #10b981; }
.kpi-info { display: flex; flex-direction: column; }
.kpi-valor { font-size: 1.5rem; font-weight: 700; color: #1f2937; line-height: 1.2; }
.kpi-label { font-size: 0.85rem; color: #6b7280; }
.tramites-alertas { background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
.tramites-alertas h3 { display: flex; align-items: center; gap: 0.5rem; margin: 0 0 1rem; font-size: 1rem; color: #92400e; }
.tramites-alertas h3 .dashicons { color: #f59e0b; }
.alertas-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.alerta-item { display: flex; align-items: center; gap: 1rem; background: white; border-radius: 8px; padding: 1rem; }
.alerta-icono { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.alerta-icono .dashicons { color: white; font-size: 20px; width: 20px; height: 20px; }
.alerta-content { flex: 1; }
.alerta-content h4 { margin: 0 0 0.25rem; font-size: 0.95rem; color: #1f2937; }
.alerta-content p { margin: 0; font-size: 0.85rem; color: #6b7280; }
.alerta-expediente { font-size: 0.8rem; color: #9ca3af; font-family: monospace; }
.tramites-content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
.tramites-recientes, .tramites-sugeridos { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.panel-header h3 { margin: 0; font-size: 1.1rem; color: #1f2937; }
.ver-todos { display: flex; align-items: center; gap: 0.35rem; font-size: 0.9rem; color: #3b82f6; text-decoration: none; }
.ver-todos:hover { color: #2563eb; }
.ver-todos .dashicons { font-size: 16px; width: 16px; height: 16px; }
.tramites-lista { display: flex; flex-direction: column; }
.tramite-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; transition: all 0.2s; }
.tramite-item:last-child { border-bottom: none; }
.tramite-item:hover { background: #f9fafb; margin: 0 -1rem; padding-left: 1rem; padding-right: 1rem; border-radius: 8px; }
.tramite-estado-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.tramite-info { flex: 1; }
.tramite-tipo { display: block; font-size: 0.95rem; color: #1f2937; font-weight: 500; }
.tramite-expediente { font-size: 0.8rem; color: #9ca3af; font-family: monospace; }
.tramite-meta-right { text-align: right; }
.tramite-estado { display: block; font-size: 0.8rem; font-weight: 500; }
.tramite-fecha { font-size: 0.8rem; color: #9ca3af; }
.panel-empty { text-align: center; padding: 2rem; color: #6b7280; }
.panel-empty .dashicons { font-size: 40px; width: 40px; height: 40px; color: #d1d5db; margin-bottom: 0.5rem; }
.panel-empty p { margin: 0; }
.sugeridos-lista { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
.sugerido-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 8px; text-decoration: none; transition: all 0.2s; }
.sugerido-item:hover { background: #f9fafb; }
.sugerido-icono { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.sugerido-icono .dashicons { color: white; font-size: 18px; width: 18px; height: 18px; }
.sugerido-nombre { flex: 1; font-size: 0.9rem; color: #374151; }
.sugerido-item .dashicons-arrow-right-alt2 { color: #9ca3af; font-size: 18px; }
.tramites-accesos-rapidos { margin-top: 2rem; }
.tramites-accesos-rapidos h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }
.accesos-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.acceso-card { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.5rem; background: white; border-radius: 12px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.acceso-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.acceso-card .dashicons { font-size: 28px; width: 28px; height: 28px; color: #3b82f6; }
.acceso-card span:last-child { font-size: 0.9rem; color: #374151; font-weight: 500; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.95rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-block { width: 100%; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
@media (max-width: 768px) {
    .tramites-welcome { flex-direction: column; text-align: center; gap: 1rem; }
    .tramites-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .tramites-content-grid { grid-template-columns: 1fr; }
    .accesos-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

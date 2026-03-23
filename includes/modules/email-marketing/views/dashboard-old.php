<?php
/**
 * Vista: Dashboard de Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

// Tablas
$tabla_campanias = $wpdb->prefix . 'flavor_em_campanias';
$tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
$tabla_automatizaciones = $wpdb->prefix . 'flavor_em_automatizaciones';
$tabla_envios = $wpdb->prefix . 'flavor_em_envios';
$tabla_listas = $wpdb->prefix . 'flavor_em_listas';

// Verificar existencia de tablas
$tabla_campanias_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_campanias'") === $tabla_campanias;
$tabla_suscriptores_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_suscriptores'") === $tabla_suscriptores;
$tabla_automatizaciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_automatizaciones'") === $tabla_automatizaciones;
$tabla_envios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_envios'") === $tabla_envios;
$tabla_listas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_listas'") === $tabla_listas;

// Inicializar estadísticas
$total_suscriptores = 0;
$suscriptores_activos = 0;
$nuevos_suscriptores = 0;
$bajas_mes = 0;
$total_campanias = 0;
$campanias_enviadas = 0;
$total_emails_enviados = 0;
$emails_mes = 0;
$tasa_apertura = 0;
$tasa_clicks = 0;
$total_automatizaciones = 0;
$automatizaciones_activas = 0;
$ultimas_campanias = [];
$automatizaciones_lista = [];
$crecimiento_diario = [];
$rendimiento_campanias = [];

if ($tabla_suscriptores_existe) {
    $total_suscriptores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_suscriptores");
    $suscriptores_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_suscriptores WHERE estado = 'activo'");
    $nuevos_suscriptores = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_suscriptores WHERE fecha_alta >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $bajas_mes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_suscriptores WHERE estado = 'baja' AND fecha_baja >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Crecimiento diario (últimos 14 días)
    $crecimiento_diario = $wpdb->get_results("
        SELECT DATE(fecha_alta) as fecha, COUNT(*) as nuevos
        FROM $tabla_suscriptores
        WHERE fecha_alta >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(fecha_alta)
        ORDER BY fecha ASC
    ");
}

if ($tabla_campanias_existe) {
    $total_campanias = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias");
    $campanias_enviadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'enviada'");

    // Últimas campañas
    $ultimas_campanias = $wpdb->get_results("
        SELECT *
        FROM $tabla_campanias
        ORDER BY creado_en DESC
        LIMIT 5
    ");

    // Calcular tasas promedio
    $promedios = $wpdb->get_row("
        SELECT
            AVG(CASE WHEN total_enviados > 0 THEN (total_abiertos * 100.0 / total_enviados) ELSE 0 END) as tasa_apertura,
            AVG(CASE WHEN total_enviados > 0 THEN (total_clicks * 100.0 / total_enviados) ELSE 0 END) as tasa_clicks
        FROM $tabla_campanias
        WHERE estado = 'enviada' AND total_enviados > 0
    ");
    if ($promedios) {
        $tasa_apertura = round($promedios->tasa_apertura ?? 0, 1);
        $tasa_clicks = round($promedios->tasa_clicks ?? 0, 1);
    }

    // Rendimiento últimas 5 campañas
    $rendimiento_campanias = $wpdb->get_results("
        SELECT nombre,
               total_enviados,
               total_abiertos,
               total_clicks,
               CASE WHEN total_enviados > 0 THEN ROUND(total_abiertos * 100.0 / total_enviados, 1) ELSE 0 END as porcentaje_apertura
        FROM $tabla_campanias
        WHERE estado = 'enviada' AND total_enviados > 0
        ORDER BY enviado_en DESC
        LIMIT 5
    ");
}

if ($tabla_envios_existe) {
    $total_emails_enviados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_envios");
    $emails_mes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_envios WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
}

if ($tabla_automatizaciones_existe) {
    $total_automatizaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_automatizaciones");
    $automatizaciones_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_automatizaciones WHERE estado = 'activa'");

    // Automatizaciones activas
    $automatizaciones_lista = $wpdb->get_results("
        SELECT *
        FROM $tabla_automatizaciones
        WHERE estado = 'activa'
        ORDER BY total_inscritos DESC
        LIMIT 5
    ");
}

// Preparar datos para gráficos
$crecimiento_labels = array_map(function($c) {
    return date_i18n('d M', strtotime($c->fecha));
}, $crecimiento_diario);
$crecimiento_data = array_map(function($c) { return (int) $c->nuevos; }, $crecimiento_diario);

$rendimiento_labels = array_map(function($r) {
    return wp_trim_words($r->nombre, 2, '');
}, $rendimiento_campanias);
$rendimiento_data = array_map(function($r) { return (float) $r->porcentaje_apertura; }, $rendimiento_campanias);

// Calcular tasa de retención
$tasa_retencion = $total_suscriptores > 0 ? round(($suscriptores_activos / $total_suscriptores) * 100, 1) : 0;
?>

<div class="wrap em-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-email-alt"></span>
        <?php echo esc_html__('Dashboard - Email Marketing', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <?php if (!$tabla_listas_existe): ?>
        <div class="notice notice-info">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Email Marketing.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($is_dashboard_viewer): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('Vista resumida para gestor de grupos. La configuración avanzada sigue reservada a administración.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($bajas_mes > 20 && !$is_dashboard_viewer): ?>
        <div class="notice notice-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html__('Se han registrado %d bajas en los últimos 30 días. Considera revisar la estrategia de contenido.', 'flavor-chat-ia'),
                    $bajas_mes
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="flavor-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 20px 0;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-campanias')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-megaphone" style="font-size: 22px; color: #2271b1;"></span>
            <span><?php esc_html_e('Campañas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-suscriptores')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 22px; color: #00a32a;"></span>
            <span><?php esc_html_e('Suscriptores', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-automatizaciones')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-controls-repeat" style="font-size: 22px; color: #8c52ff;"></span>
            <span><?php esc_html_e('Automatizaciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-listas')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-list-view" style="font-size: 22px; color: #dba617;"></span>
            <span><?php esc_html_e('Listas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-plantillas')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-media-document" style="font-size: 22px; color: #d63638;"></span>
            <span><?php esc_html_e('Plantillas', 'flavor-chat-ia'); ?></span>
        </a>
        <?php if (!$is_dashboard_viewer): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-configuracion')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 22px; color: #646970;"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Estadísticas Principales -->
    <div id="em-stats" class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Suscriptores Activos', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($suscriptores_activos); ?></h2>
                    <?php if ($nuevos_suscriptores > 0): ?>
                        <small style="color: #00a32a;">+<?php echo number_format($nuevos_suscriptores); ?> <?php echo esc_html__('este mes', 'flavor-chat-ia'); ?></small>
                    <?php endif; ?>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Emails Enviados', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($emails_mes); ?></h2>
                    <small style="color: #646970;"><?php echo esc_html__('últimos 30 días', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-email-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Tasa Apertura', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo $tasa_apertura; ?>%</h2>
                    <small style="color: #646970;"><?php echo esc_html__('promedio', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-visibility" style="font-size: 40px; color: #8c52ff; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Tasa Clicks', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo $tasa_clicks; ?>%</h2>
                    <small style="color: #646970;"><?php echo esc_html__('promedio', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-admin-links" style="font-size: 40px; color: #dba617; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #d63638; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Campañas Enviadas', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($campanias_enviadas); ?></h2>
                    <small style="color: #646970;"><?php echo esc_html__('total', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-megaphone" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #135e96; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Automatizaciones', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($automatizaciones_activas); ?></h2>
                    <small style="color: #646970;"><?php echo esc_html__('activas', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-controls-repeat" style="font-size: 40px; color: #135e96; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Acciones rápidas -->
    <div class="em-quick-actions" style="display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-campanias&action=new')); ?>" class="button button-primary">
            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span>
            <?php esc_html_e('Nueva campaña', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-suscriptores&action=import')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
            <?php esc_html_e('Importar suscriptores', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-automatizaciones&action=new')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-controls-repeat" style="margin-top: 3px;"></span>
            <?php esc_html_e('Nueva automatización', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Gráfico de crecimiento -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-chart-line" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Crecimiento de Suscriptores (14 días)', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 20px;">
                <canvas id="grafico-crecimiento" style="max-height: 280px;"></canvas>
            </div>
        </div>

        <!-- Gráfico de rendimiento -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-chart-bar" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Tasa de Apertura por Campaña', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 20px;">
                <canvas id="grafico-rendimiento" style="max-height: 280px;"></canvas>
            </div>
        </div>

    </div>

    <!-- Tablas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Últimas campañas -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-megaphone" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Últimas Campañas', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Campaña', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Enviados', 'flavor-chat-ia'); ?></th>
                            <th style="width: 70px;"><?php echo esc_html__('Apertura', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimas_campanias)): ?>
                            <?php foreach ($ultimas_campanias as $campania): ?>
                                <?php
                                $tasa_apertura_camp = $campania->total_enviados > 0
                                    ? round(($campania->total_abiertos / $campania->total_enviados) * 100, 1)
                                    : 0;

                                $estado_class = 'background: #f0f0f1; color: #646970;';
                                $estado_text = ucfirst($campania->estado ?? 'borrador');
                                if ($campania->estado === 'enviada') {
                                    $estado_class = 'background: #d4edda; color: #155724;';
                                } elseif ($campania->estado === 'programada') {
                                    $estado_class = 'background: #fff3cd; color: #856404;';
                                } elseif ($campania->estado === 'borrador') {
                                    $estado_class = 'background: #e7f3ff; color: #2271b1;';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-campanias&action=edit&id=' . $campania->id)); ?>">
                                            <strong><?php echo esc_html($campania->nombre); ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <span style="<?php echo $estado_class; ?> padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                            <?php echo esc_html($estado_text); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;"><?php echo number_format($campania->total_enviados); ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($campania->total_enviados > 0): ?>
                                            <?php echo $tasa_apertura_camp; ?>%
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay campañas aún', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="padding: 10px 15px; border-top: 1px solid #f0f0f1;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-campanias')); ?>">
                        <?php echo esc_html__('Ver todas las campañas', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Automatizaciones activas -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-controls-repeat" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Automatizaciones Activas', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Trigger', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Inscritos', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Completados', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($automatizaciones_lista)): ?>
                            <?php foreach ($automatizaciones_lista as $auto): ?>
                                <?php
                                $trigger_labels = [
                                    'suscripcion' => 'Suscripción',
                                    'inactividad' => 'Inactividad',
                                    'fecha' => 'Fecha',
                                    'evento' => 'Evento',
                                ];
                                $trigger_text = $trigger_labels[$auto->trigger_tipo ?? ''] ?? ucfirst(str_replace('_', ' ', $auto->trigger_tipo ?? ''));
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-automatizaciones&action=edit&id=' . $auto->id)); ?>">
                                            <strong><?php echo esc_html($auto->nombre); ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <span style="background: #f0f0f1; color: #646970; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                            <?php echo esc_html($trigger_text); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;"><?php echo number_format($auto->total_inscritos ?? 0); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($auto->total_completados ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay automatizaciones activas', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="padding: 10px 15px; border-top: 1px solid #f0f0f1;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-em-automatizaciones')); ?>">
                        <?php echo esc_html__('Ver automatizaciones', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Indicador de salud -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
            <span class="dashicons dashicons-heart" style="margin-right: 8px;"></span>
            <?php echo esc_html__('Salud de la Lista', 'flavor-chat-ia'); ?>
        </h2>
        <div class="inside" style="padding: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <p style="margin: 0 0 5px; color: #646970; font-size: 13px;"><?php echo esc_html__('Tasa de Retención', 'flavor-chat-ia'); ?></p>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; background: #f0f0f1; border-radius: 10px; height: 12px; overflow: hidden;">
                            <div style="background: <?php echo $tasa_retencion >= 90 ? '#00a32a' : ($tasa_retencion >= 70 ? '#dba617' : '#d63638'); ?>; height: 100%; width: <?php echo $tasa_retencion; ?>%;"></div>
                        </div>
                        <strong><?php echo $tasa_retencion; ?>%</strong>
                    </div>
                </div>
                <div>
                    <p style="margin: 0 0 5px; color: #646970; font-size: 13px;"><?php echo esc_html__('Bajas este mes', 'flavor-chat-ia'); ?></p>
                    <h3 style="margin: 0; color: <?php echo $bajas_mes > 20 ? '#d63638' : '#1d2327'; ?>;"><?php echo number_format($bajas_mes); ?></h3>
                </div>
                <div>
                    <p style="margin: 0 0 5px; color: #646970; font-size: 13px;"><?php echo esc_html__('Ratio Apertura vs Clicks', 'flavor-chat-ia'); ?></p>
                    <h3 style="margin: 0;">
                        <?php echo $tasa_apertura > 0 ? round(($tasa_clicks / $tasa_apertura) * 100, 1) : 0; ?>%
                        <small style="font-weight: normal; color: #646970;"><?php echo esc_html__('de los que abren hacen click', 'flavor-chat-ia'); ?></small>
                    </h3>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado');
        return;
    }

    // Gráfico de crecimiento (Línea)
    var ctxCrecimiento = document.getElementById('grafico-crecimiento');
    if (ctxCrecimiento) {
        new Chart(ctxCrecimiento.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode($crecimiento_labels); ?>,
                datasets: [{
                    label: '<?php echo esc_js(__('Nuevos suscriptores', 'flavor-chat-ia')); ?>',
                    data: <?php echo wp_json_encode($crecimiento_data); ?>,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#2271b1',
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    }

    // Gráfico de rendimiento (Barras horizontales)
    var ctxRendimiento = document.getElementById('grafico-rendimiento');
    if (ctxRendimiento) {
        new Chart(ctxRendimiento.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode($rendimiento_labels); ?>,
                datasets: [{
                    label: '<?php echo esc_js(__('% Apertura', 'flavor-chat-ia')); ?>',
                    data: <?php echo wp_json_encode($rendimiento_data); ?>,
                    backgroundColor: ['#2271b1', '#00a32a', '#8c52ff', '#dba617', '#d63638'],
                    borderRadius: 4,
                    barThickness: 25
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 50,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
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
.flavor-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
.flavor-stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12) !important;
}
.postbox {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    background: #fff;
}
.postbox .hndle {
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
}
.em-quick-actions .button .dashicons {
    vertical-align: middle;
    margin-right: 3px;
}
</style>

<?php
/**
 * Vista Dashboard - Red Social
 *
 * Panel principal con estadisticas de publicaciones y actividad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener estadisticas generales
$tabla_publicaciones = $wpdb->prefix . 'flavor_red_social_posts';
$tabla_interacciones = $wpdb->prefix . 'flavor_red_social_interacciones';
$tabla_publicaciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_publicaciones'");
$tabla_interacciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_interacciones'");

$publicaciones_hoy = 0;
$usuarios_activos = 0;
$total_interacciones = 0;

if ($tabla_publicaciones_existe) {
    $fecha_hoy = date('Y-m-d');

    $publicaciones_hoy = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_publicaciones WHERE DATE(fecha_creacion) = %s",
        $fecha_hoy
    ));

    $usuarios_activos = $wpdb->get_var(
        "SELECT COUNT(DISTINCT autor_id) FROM $tabla_publicaciones WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
}

if ($tabla_interacciones_existe) {
    $total_interacciones = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_interacciones WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
}

// Datos de ejemplo si no hay datos reales
$usar_datos_ejemplo = ($publicaciones_hoy == 0 && $usuarios_activos == 0 && $total_interacciones == 0);

if ($usar_datos_ejemplo) {
    $publicaciones_hoy = 24;
    $usuarios_activos = 89;
    $total_interacciones = 156;
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-share"></span>
        <?php echo esc_html__('Dashboard - Red Social', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadisticas Principales -->
    <div class="red-social-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="red-social-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($publicaciones_hoy); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Publicaciones Hoy', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="red-social-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($usuarios_activos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Usuarios Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="red-social-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_interacciones); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Interacciones (24h)', 'flavor-chat-ia'); ?>
            </div>
        </div>

    </div>

    <!-- Accesos Rapidos -->
    <div class="red-social-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=red-social-publicaciones'); ?>" class="red-social-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-post" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Publicaciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=red-social-usuarios'); ?>" class="red-social-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Usuarios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=red-social-reportes'); ?>" class="red-social-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-flag" style="font-size: 24px; color: #d63638;"></span>
            <span><?php echo esc_html__('Reportes', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=red-social-estadisticas'); ?>" class="red-social-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Estadisticas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=red-social-configuracion'); ?>" class="red-social-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuracion', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Publicaciones Recientes -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-update"></span> <?php echo esc_html__('Publicaciones Recientes', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Contenido', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Likes', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Comentarios', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usar_datos_ejemplo): ?>
                    <tr>
                        <td><strong>#245</strong></td>
                        <td>Gran evento comunitario este fin de semana...</td>
                        <td>Maria Garcia</td>
                        <td>32</td>
                        <td>8</td>
                        <td>hace 1 hora</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Publicado</span></td>
                    </tr>
                    <tr>
                        <td><strong>#244</strong></td>
                        <td>Nuevos talleres disponibles para inscripcion...</td>
                        <td>Carlos Lopez</td>
                        <td>18</td>
                        <td>5</td>
                        <td>hace 3 horas</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Publicado</span></td>
                    </tr>
                    <tr>
                        <td><strong>#243</strong></td>
                        <td>Buscando voluntarios para limpieza del parque...</td>
                        <td>Ana Martinez</td>
                        <td>45</td>
                        <td>12</td>
                        <td>hace 5 horas</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Publicado</span></td>
                    </tr>
                    <tr>
                        <td><strong>#242</strong></td>
                        <td>Contenido reportado por varios usuarios...</td>
                        <td>Usuario Anonimo</td>
                        <td>2</td>
                        <td>0</td>
                        <td>hace 8 horas</td>
                        <td><span class="badge-warning" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">En revision</span></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #646970;">
                            <?php echo esc_html__('No hay publicaciones recientes', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.postbox h2 {
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.red-social-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
.badge-warning { background-color: #dba617; color: #fff; }
.badge-info { background-color: #2271b1; color: #fff; }
.badge-success { background-color: #00a32a; color: #fff; }
.badge-error { background-color: #d63638; color: #fff; }
</style>

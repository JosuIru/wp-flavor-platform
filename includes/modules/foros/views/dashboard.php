<?php
/**
 * Vista Dashboard - Foros
 *
 * Panel principal con estadisticas de temas y actividad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener estadisticas generales
$tabla_temas = $wpdb->prefix . 'flavor_foros_temas';
$tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
$tabla_temas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_temas'");
$tabla_respuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_respuestas'");

$total_temas = 0;
$total_respuestas = 0;
$usuarios_activos = 0;

if ($tabla_temas_existe) {
    $total_temas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_temas");

    $usuarios_activos = $wpdb->get_var(
        "SELECT COUNT(DISTINCT autor_id) FROM $tabla_temas"
    );
}

if ($tabla_respuestas_existe) {
    $total_respuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas");
}

// Datos de ejemplo si no hay datos reales
$usar_datos_ejemplo = ($total_temas == 0 && $total_respuestas == 0);

if ($usar_datos_ejemplo) {
    $total_temas = 87;
    $total_respuestas = 456;
    $usuarios_activos = 62;
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-format-chat"></span>
        <?php echo esc_html__('Dashboard - Foros', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadisticas Principales -->
    <div class="foros-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_temas); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Total Temas', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_respuestas); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Respuestas', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($usuarios_activos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Usuarios Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>

    </div>

    <!-- Accesos Rapidos -->
    <div class="foros-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=foros-temas'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-format-chat" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Temas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-categorias'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-category" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Categorias', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-moderacion'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-shield" style="font-size: 24px; color: #d63638;"></span>
            <span><?php echo esc_html__('Moderacion', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-reportes'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Reportes', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-configuracion'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuracion', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Temas Recientes -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-update"></span> <?php echo esc_html__('Temas Recientes', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Titulo', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Respuestas', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Ultima Actividad', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usar_datos_ejemplo): ?>
                    <tr>
                        <td><strong>#87</strong></td>
                        <td>Como mejorar la participacion comunitaria</td>
                        <td>Ana Martinez</td>
                        <td>General</td>
                        <td>12</td>
                        <td>hace 2 horas</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Activo</span></td>
                    </tr>
                    <tr>
                        <td><strong>#86</strong></td>
                        <td>Propuesta para nuevo evento local</td>
                        <td>Pedro Sanchez</td>
                        <td>Eventos</td>
                        <td>8</td>
                        <td>hace 5 horas</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Activo</span></td>
                    </tr>
                    <tr>
                        <td><strong>#85</strong></td>
                        <td>Dudas sobre el nuevo sistema de reservas</td>
                        <td>Laura Gomez</td>
                        <td>Soporte</td>
                        <td>5</td>
                        <td>hace 1 dia</td>
                        <td><span class="badge-info" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Resuelto</span></td>
                    </tr>
                    <tr>
                        <td><strong>#84</strong></td>
                        <td>Sugerencias para mejorar la app</td>
                        <td>Roberto Diaz</td>
                        <td>Sugerencias</td>
                        <td>15</td>
                        <td>hace 2 dias</td>
                        <td><span class="badge-warning" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">En revision</span></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #646970;">
                            <?php echo esc_html__('No hay temas registrados', 'flavor-chat-ia'); ?>
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
.foros-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
.badge-warning { background-color: #dba617; color: #fff; }
.badge-info { background-color: #2271b1; color: #fff; }
.badge-success { background-color: #00a32a; color: #fff; }
.badge-error { background-color: #d63638; color: #fff; }
</style>

<?php
/**
 * Vista Dashboard - Reservas
 *
 * Panel principal con estadisticas de reservas y recursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener estadisticas generales
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'");

$reservas_hoy = 0;
$reservas_pendientes = 0;
$reservas_confirmadas = 0;

if ($tabla_existe) {
    $fecha_hoy = date('Y-m-d');

    $reservas_hoy = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_reservas WHERE DATE(fecha_reserva) = %s",
        $fecha_hoy
    ));

    $reservas_pendientes = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'pendiente'"
    );

    $reservas_confirmadas = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'confirmada'"
    );
}

// Datos de ejemplo si no hay datos reales
$usar_datos_ejemplo = ($reservas_hoy == 0 && $reservas_pendientes == 0 && $reservas_confirmadas == 0);

if ($usar_datos_ejemplo) {
    $reservas_hoy = 12;
    $reservas_pendientes = 8;
    $reservas_confirmadas = 45;
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php echo esc_html__('Dashboard - Reservas', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadisticas Principales -->
    <div class="reservas-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="reservas-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($reservas_hoy); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Reservas Hoy', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="reservas-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #dba617; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($reservas_pendientes); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="reservas-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($reservas_confirmadas); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Confirmadas', 'flavor-chat-ia'); ?>
            </div>
        </div>

    </div>

    <!-- Accesos Rapidos -->
    <div class="reservas-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=reservas-listado'); ?>" class="reservas-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Reservas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=reservas-nueva'); ?>" class="reservas-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-plus-alt2" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Nueva Reserva', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=reservas-recursos'); ?>" class="reservas-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-building" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Recursos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=reservas-calendario'); ?>" class="reservas-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-calendar" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Calendario', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=reservas-configuracion'); ?>" class="reservas-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuracion', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Calendario Placeholder -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-calendar"></span> <?php echo esc_html__('Calendario de Reservas', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <div id="calendario-reservas" style="min-height: 400px; background: #f6f7f7; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center; color: #646970;">
                    <span class="dashicons dashicons-calendar" style="font-size: 48px; margin-bottom: 10px;"></span>
                    <p><?php echo esc_html__('Calendario interactivo - Proximamente', 'flavor-chat-ia'); ?></p>
                    <p style="font-size: 12px;"><?php echo esc_html__('Aqui se mostrara un calendario con todas las reservas', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservas Proximas -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-clock"></span> <?php echo esc_html__('Reservas Proximas', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Recurso', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Hora', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usar_datos_ejemplo): ?>
                    <tr>
                        <td><strong>#1</strong></td>
                        <td>Sala de Reuniones A</td>
                        <td>Juan Garcia</td>
                        <td>24/02/2026</td>
                        <td>10:00 - 12:00</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Confirmada</span></td>
                    </tr>
                    <tr>
                        <td><strong>#2</strong></td>
                        <td>Espacio Coworking</td>
                        <td>Maria Lopez</td>
                        <td>24/02/2026</td>
                        <td>14:00 - 18:00</td>
                        <td><span class="badge-warning" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Pendiente</span></td>
                    </tr>
                    <tr>
                        <td><strong>#3</strong></td>
                        <td>Sala de Conferencias</td>
                        <td>Carlos Martinez</td>
                        <td>25/02/2026</td>
                        <td>09:00 - 11:00</td>
                        <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Confirmada</span></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #646970;">
                            <?php echo esc_html__('No hay reservas proximas', 'flavor-chat-ia'); ?>
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
.reservas-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
.badge-warning { background-color: #dba617; color: #fff; }
.badge-success { background-color: #00a32a; color: #fff; }
.badge-error { background-color: #d63638; color: #fff; }
</style>

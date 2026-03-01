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
$tabla_foros = $wpdb->prefix . 'flavor_foros';
$tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
$tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

$tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'");
$tabla_hilos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_hilos'");
$tabla_respuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_respuestas'");

$total_foros = 0;
$total_hilos = 0;
$total_respuestas = 0;
$usuarios_activos = 0;

if ($tabla_foros_existe) {
    $total_foros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_foros WHERE estado = 'activo'");
}

if ($tabla_hilos_existe) {
    $total_hilos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos WHERE estado != 'eliminado'");

    $usuarios_activos = $wpdb->get_var(
        "SELECT COUNT(DISTINCT autor_id) FROM $tabla_hilos WHERE estado != 'eliminado'"
    );
}

if ($tabla_respuestas_existe) {
    $total_respuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'visible'");
}

// Datos de ejemplo si no hay datos reales
$usar_datos_ejemplo = ($total_hilos == 0 && $total_respuestas == 0);

if ($usar_datos_ejemplo) {
    $total_foros = 5;
    $total_hilos = 87;
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
    <div class="foros-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_foros); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Foros Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_hilos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Hilos/Temas', 'flavor-chat-ia'); ?>
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

        <div class="foros-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #dba617; font-size: 32px; margin-bottom: 10px;">
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
        <a href="<?php echo admin_url('admin.php?page=foros-hilos'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-format-chat" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Hilos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-listado'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-category" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Categorias', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-moderacion'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-shield" style="font-size: 24px; color: #d63638;"></span>
            <span><?php echo esc_html__('Moderacion', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-app-composer&module=foros'); ?>" class="foros-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Hilos Recientes -->
    <?php
    // Obtener hilos recientes de la base de datos
    $hilos_recientes = [];
    if ($tabla_hilos_existe && $tabla_foros_existe) {
        $hilos_recientes = $wpdb->get_results(
            "SELECT h.*, f.nombre AS nombre_foro, u.display_name AS nombre_autor
             FROM $tabla_hilos h
             LEFT JOIN $tabla_foros f ON f.id = h.foro_id
             LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
             WHERE h.estado != 'eliminado'
             ORDER BY h.ultima_actividad DESC
             LIMIT 10"
        );
    }
    ?>
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-update"></span> <?php echo esc_html__('Hilos Recientes', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Titulo', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Foro', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Respuestas', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Ultima Act.', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hilos_recientes)): ?>
                        <?php foreach ($hilos_recientes as $hilo): ?>
                            <?php
                            $clases_estado = [
                                'abierto' => 'badge-success',
                                'cerrado' => 'badge-error',
                                'fijado' => 'badge-info',
                            ];
                            $clase_estado = $clases_estado[$hilo->estado] ?? 'badge-warning';
                            ?>
                            <tr>
                                <td><strong>#<?php echo intval($hilo->id); ?></strong></td>
                                <td>
                                    <?php if ($hilo->es_fijado): ?>
                                        <span class="dashicons dashicons-admin-post" style="color: #2271b1;" title="<?php echo esc_attr__('Fijado', 'flavor-chat-ia'); ?>"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($hilo->titulo); ?>
                                </td>
                                <td><?php echo esc_html($hilo->nombre_autor ?: __('Anonimo', 'flavor-chat-ia')); ?></td>
                                <td><?php echo esc_html($hilo->nombre_foro ?: '-'); ?></td>
                                <td style="text-align: center;"><?php echo intval($hilo->respuestas_count); ?></td>
                                <td>
                                    <?php
                                    if ($hilo->ultima_actividad) {
                                        $diferencia = human_time_diff(strtotime($hilo->ultima_actividad), current_time('timestamp'));
                                        echo sprintf(__('hace %s', 'flavor-chat-ia'), $diferencia);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="<?php echo $clase_estado; ?>" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                        <?php echo esc_html(ucfirst($hilo->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif ($usar_datos_ejemplo): ?>
                        <tr>
                            <td><strong>#87</strong></td>
                            <td>Como mejorar la participacion comunitaria</td>
                            <td>Ana Martinez</td>
                            <td>General</td>
                            <td style="text-align: center;">12</td>
                            <td>hace 2 horas</td>
                            <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Abierto</span></td>
                        </tr>
                        <tr>
                            <td><strong>#86</strong></td>
                            <td>Propuesta para nuevo evento local</td>
                            <td>Pedro Sanchez</td>
                            <td>Eventos</td>
                            <td style="text-align: center;">8</td>
                            <td>hace 5 horas</td>
                            <td><span class="badge-success" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Abierto</span></td>
                        </tr>
                        <tr>
                            <td><strong>#85</strong></td>
                            <td>Dudas sobre el nuevo sistema de reservas</td>
                            <td>Laura Gomez</td>
                            <td>Soporte</td>
                            <td style="text-align: center;">5</td>
                            <td>hace 1 dia</td>
                            <td><span class="badge-info" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Fijado</span></td>
                        </tr>
                        <tr>
                            <td><strong>#84</strong></td>
                            <td>Sugerencias para mejorar la app</td>
                            <td>Roberto Diaz</td>
                            <td>Sugerencias</td>
                            <td style="text-align: center;">15</td>
                            <td>hace 2 dias</td>
                            <td><span class="badge-warning" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">Cerrado</span></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #646970;">
                                <?php echo esc_html__('No hay hilos registrados', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($hilos_recientes) || $usar_datos_ejemplo): ?>
            <p style="text-align: right; margin-top: 10px;">
                <a href="<?php echo admin_url('admin.php?page=foros-hilos'); ?>" class="button">
                    <?php echo esc_html__('Ver todos los hilos', 'flavor-chat-ia'); ?> &rarr;
                </a>
            </p>
            <?php endif; ?>
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

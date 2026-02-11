<?php
/**
 * Vista Usuarios - Banco de Tiempo
 *
 * Dashboard de usuarios con créditos, servicios y rankings
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

// Obtener todos los usuarios con actividad en banco de tiempo
$usuarios_activos = $wpdb->get_results("
    SELECT DISTINCT u.ID, u.display_name, u.user_email
    FROM {$wpdb->users} u
    WHERE u.ID IN (
        SELECT usuario_id FROM $tabla_servicios
        UNION
        SELECT usuario_solicitante_id FROM $tabla_transacciones
        UNION
        SELECT usuario_receptor_id FROM $tabla_transacciones
    )
    ORDER BY u.display_name ASC
");

// Calcular estadísticas para cada usuario
$datos_usuarios = [];

foreach ($usuarios_activos as $usuario) {
    $user_id = $usuario->ID;

    // Horas ganadas
    $horas_ganadas = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones
         WHERE usuario_receptor_id = %d AND estado = 'completado'",
        $user_id
    ));

    // Horas gastadas
    $horas_gastadas = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones
         WHERE usuario_solicitante_id = %d AND estado = 'completado'",
        $user_id
    ));

    // Servicios ofrecidos
    $servicios_ofrecidos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_servicios WHERE usuario_id = %d",
        $user_id
    ));

    // Servicios activos
    $servicios_activos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_servicios WHERE usuario_id = %d AND estado = 'activo'",
        $user_id
    ));

    // Intercambios realizados
    $intercambios_completados = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones
         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
         AND estado = 'completado'",
        $user_id,
        $user_id
    ));

    // Intercambios pendientes
    $intercambios_pendientes = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones
         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
         AND estado IN ('pendiente', 'aceptado')",
        $user_id,
        $user_id
    ));

    $saldo_actual = $horas_ganadas - $horas_gastadas;

    $datos_usuarios[] = [
        'id' => $user_id,
        'nombre' => $usuario->display_name,
        'email' => $usuario->user_email,
        'horas_ganadas' => $horas_ganadas,
        'horas_gastadas' => $horas_gastadas,
        'saldo' => $saldo_actual,
        'servicios_ofrecidos' => $servicios_ofrecidos,
        'servicios_activos' => $servicios_activos,
        'intercambios_completados' => $intercambios_completados,
        'intercambios_pendientes' => $intercambios_pendientes,
    ];
}

// Ordenar por saldo (descendente por defecto)
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'saldo_desc';

usort($datos_usuarios, function($a, $b) use ($orden) {
    switch ($orden) {
        case 'saldo_desc':
            return $b['saldo'] <=> $a['saldo'];
        case 'saldo_asc':
            return $a['saldo'] <=> $b['saldo'];
        case 'nombre_asc':
            return strcmp($a['nombre'], $b['nombre']);
        case 'nombre_desc':
            return strcmp($b['nombre'], $a['nombre']);
        case 'intercambios_desc':
            return $b['intercambios_completados'] <=> $a['intercambios_completados'];
        default:
            return $b['saldo'] <=> $a['saldo'];
    }
});

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php echo esc_html__('Usuarios del Banco de Tiempo', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas Generales -->
    <div class="usuarios-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0;">
        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #2271b1;">
                <?php echo number_format(count($datos_usuarios)); ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Usuarios Activos', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #00a32a;">
                <?php
                $total_horas_circulando = array_sum(array_column($datos_usuarios, 'horas_ganadas'));
                echo number_format($total_horas_circulando, 1);
                ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Total Horas Circulando', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #8c52ff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #8c52ff;">
                <?php
                $promedio_saldo = count($datos_usuarios) > 0 ?
                    array_sum(array_column($datos_usuarios, 'saldo')) / count($datos_usuarios) : 0;
                echo number_format($promedio_saldo, 1);
                ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Saldo Promedio', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Herramientas -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 8px; align-items: center;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

                <label for="orden"><?php echo esc_html__('Ordenar por:', 'flavor-chat-ia'); ?></label>
                <select name="orden" id="orden" onchange="this.form.submit()">
                    <option value="<?php echo esc_attr__('saldo_desc', 'flavor-chat-ia'); ?>" <?php selected($orden, 'saldo_desc'); ?>><?php echo esc_html__('Saldo (mayor a menor)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('saldo_asc', 'flavor-chat-ia'); ?>" <?php selected($orden, 'saldo_asc'); ?>><?php echo esc_html__('Saldo (menor a mayor)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('nombre_asc', 'flavor-chat-ia'); ?>" <?php selected($orden, 'nombre_asc'); ?>><?php echo esc_html__('Nombre (A-Z)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('nombre_desc', 'flavor-chat-ia'); ?>" <?php selected($orden, 'nombre_desc'); ?>><?php echo esc_html__('Nombre (Z-A)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('intercambios_desc', 'flavor-chat-ia'); ?>" <?php selected($orden, 'intercambios_desc'); ?>><?php echo esc_html__('Más intercambios', 'flavor-chat-ia'); ?></option>
                </select>
            </form>
        </div>

        <div class="alignright actions">
            <button type="button" class="button" id="btn-exportar-usuarios">
                <span class="dashicons dashicons-download"></span> <?php echo esc_html__('Exportar CSV', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                <th style="text-align: center;"><?php echo esc_html__('Servicios', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Ofrecidos', 'flavor-chat-ia'); ?></th>
                <th style="text-align: center;"><?php echo esc_html__('Servicios', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Activos', 'flavor-chat-ia'); ?></th>
                <th style="text-align: right;"><?php echo esc_html__('Horas', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Ganadas', 'flavor-chat-ia'); ?></th>
                <th style="text-align: right;"><?php echo esc_html__('Horas', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Gastadas', 'flavor-chat-ia'); ?></th>
                <th style="text-align: right;"><?php echo esc_html__('Saldo', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Actual', 'flavor-chat-ia'); ?></th>
                <th style="text-align: center;"><?php echo esc_html__('Intercambios', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Completados', 'flavor-chat-ia'); ?></th>
                <th style="text-align: center;"><?php echo esc_html__('Intercambios', 'flavor-chat-ia'); ?><br><?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $posicion = 1;
            foreach ($datos_usuarios as $datos):
                // Color del saldo
                $color_saldo = '#1d2327';
                if ($datos['saldo'] > 0) {
                    $color_saldo = '#00a32a';
                } elseif ($datos['saldo'] < 0) {
                    $color_saldo = '#d63638';
                }
            ?>
            <tr>
                <td><strong><?php echo $posicion++; ?></strong></td>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $datos['id']); ?>">
                            <?php echo esc_html($datos['nombre']); ?>
                        </a>
                    </strong>
                    <br>
                    <small style="color: #646970;"><?php echo esc_html($datos['email']); ?></small>
                </td>
                <td style="text-align: center;"><?php echo $datos['servicios_ofrecidos']; ?></td>
                <td style="text-align: center;">
                    <strong style="color: #2271b1;"><?php echo $datos['servicios_activos']; ?></strong>
                </td>
                <td style="text-align: right;">
                    <span style="color: #00a32a; font-weight: 600;">
                        +<?php echo number_format($datos['horas_ganadas'], 1); ?> h
                    </span>
                </td>
                <td style="text-align: right;">
                    <span style="color: #d63638; font-weight: 600;">
                        -<?php echo number_format($datos['horas_gastadas'], 1); ?> h
                    </span>
                </td>
                <td style="text-align: right;">
                    <strong style="color: <?php echo $color_saldo; ?>; font-size: 16px;">
                        <?php echo number_format($datos['saldo'], 1); ?> h
                    </strong>
                </td>
                <td style="text-align: center;">
                    <span class="badge-completados" style="padding: 4px 8px; background: #00a32a; color: #fff; border-radius: 3px; font-weight: 600;">
                        <?php echo $datos['intercambios_completados']; ?>
                    </span>
                </td>
                <td style="text-align: center;">
                    <?php if ($datos['intercambios_pendientes'] > 0): ?>
                        <span class="badge-pendientes" style="padding: 4px 8px; background: #dba617; color: #fff; border-radius: 3px; font-weight: 600;">
                            <?php echo $datos['intercambios_pendientes']; ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #646970;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="#" class="button button-small ver-historial"
                       data-usuario-id="<?php echo $datos['id']; ?>">
                        <?php echo esc_html__('Historial', 'flavor-chat-ia'); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($datos_usuarios)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: #646970;">
                        <span class="dashicons dashicons-info" style="font-size: 48px;"></span>
                        <p><?php echo esc_html__('No hay usuarios con actividad en el banco de tiempo.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Modal Historial Usuario -->
<div id="modal-historial" style="display:none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="modal-content" style="position: relative; max-width: 900px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 4px; max-height: 80vh; overflow-y: auto;">
            <h2><?php echo esc_html__('Historial de Usuario', 'flavor-chat-ia'); ?></h2>
            <div id="contenido-historial">
                <p style="text-align: center; padding: 40px;">
                    <span class="spinner is-active"></span>
                </p>
            </div>
            <p>
                <button type="button" class="button" id="btn-cerrar-historial"><?php echo esc_html__('Cerrar', 'flavor-chat-ia'); ?></button>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // Ver historial
    $('.ver-historial').click(function(e) {
        e.preventDefault();
        var usuarioId = $(this).data('usuario-id');

        $('#modal-historial').fadeIn();

        // Aquí se cargaría el historial vía AJAX
        $('#contenido-historial').html('<p>Cargando historial del usuario #' + usuarioId + '...</p>');
    });

    $('#btn-cerrar-historial, .modal-overlay').click(function(e) {
        if (e.target === this) {
            $('#modal-historial').fadeOut();
        }
    });

    // Exportar CSV
    $('#btn-exportar-usuarios').click(function() {
        var csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Usuario,Email,Servicios Ofrecidos,Servicios Activos,Horas Ganadas,Horas Gastadas,Saldo,Intercambios Completados,Intercambios Pendientes\n";

        <?php foreach ($datos_usuarios as $datos): ?>
        csvContent += "<?php echo esc_js($datos['nombre']); ?>,";
        csvContent += "<?php echo esc_js($datos['email']); ?>,";
        csvContent += "<?php echo $datos['servicios_ofrecidos']; ?>,";
        csvContent += "<?php echo $datos['servicios_activos']; ?>,";
        csvContent += "<?php echo $datos['horas_ganadas']; ?>,";
        csvContent += "<?php echo $datos['horas_gastadas']; ?>,";
        csvContent += "<?php echo $datos['saldo']; ?>,";
        csvContent += "<?php echo $datos['intercambios_completados']; ?>,";
        csvContent += "<?php echo $datos['intercambios_pendientes']; ?>\n";
        <?php endforeach; ?>

        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "banco_tiempo_usuarios_" + new Date().toISOString().slice(0,10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<?php
/**
 * Vista Admin: Solicitudes de Alta de Socios
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_solicitudes = $wpdb->prefix . 'flavor_socios_solicitudes';
$tabla_socios = $wpdb->prefix . 'flavor_socios';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_solicitudes}'") === $tabla_solicitudes;

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'pendiente';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estados
$estados = ['pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'aprobada' => __('Aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'rechazada' => __('Rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$colores_estado = ['pendiente' => '#dba617', 'aprobada' => '#00a32a', 'rechazada' => '#d63638'];

// Obtener solicitudes
$solicitudes = [];
$total_items = 0;

if ($tabla_existe) {
    $where = $estado_filtro ? "estado = %s" : "1=1";
    $params = $estado_filtro ? [$estado_filtro] : [];

    $sql_count = "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE {$where}";
    $sql_items = "SELECT s.*, u.display_name, u.user_email
                  FROM {$tabla_solicitudes} s
                  LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                  WHERE {$where}
                  ORDER BY s.fecha_solicitud DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $solicitudes = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $solicitudes = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas
$stats = ['pendientes' => 0, 'aprobadas_mes' => 0, 'rechazadas_mes' => 0];
if ($tabla_existe) {
    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'pendiente'");
    $stats['aprobadas_mes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'aprobada' AND MONTH(fecha_resolucion) = MONTH(CURDATE())");
    $stats['rechazadas_mes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'rechazada' AND MONTH(fecha_resolucion) = MONTH(CURDATE())");
}

// Procesar acciones
if (isset($_POST['accion']) && isset($_POST['solicitud_id']) && wp_verify_nonce($_POST['_wpnonce'], 'socios_solicitud_action')) {
    $solicitud_id = absint($_POST['solicitud_id']);
    $accion = sanitize_text_field($_POST['accion']);

    if ($accion === 'aprobar') {
        $wpdb->update($tabla_solicitudes, ['estado' => 'aprobada', 'fecha_resolucion' => current_time('mysql')], ['id' => $solicitud_id]);
        // Crear socio
        $solicitud = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_solicitudes} WHERE id = %d", $solicitud_id));
        if ($solicitud) {
            $numero_socio = $wpdb->get_var("SELECT MAX(CAST(numero_socio AS UNSIGNED)) FROM {$tabla_socios}") + 1;
            $wpdb->insert($tabla_socios, [
                'user_id' => $solicitud->user_id,
                'numero_socio' => str_pad($numero_socio, 4, '0', STR_PAD_LEFT),
                'tipo_socio' => 'ordinario',
                'estado' => 'activo',
                'fecha_alta' => current_time('mysql'),
            ]);
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Solicitud aprobada. Miembro creado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    } elseif ($accion === 'rechazar') {
        $wpdb->update($tabla_solicitudes, ['estado' => 'rechazada', 'fecha_resolucion' => current_time('mysql')], ['id' => $solicitud_id]);
        echo '<div class="notice notice-warning is-dismissible"><p>' . __('Solicitud rechazada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }

    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'pendiente'");
}
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=socios-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-id-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Solicitudes de Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-users"></span>
        <?php _e('Solicitudes de Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['aprobadas_mes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Aprobadas (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['rechazadas_mes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Rechazadas (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Pestañas -->
    <div style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=socios-solicitudes&estado=pendiente'); ?>" class="button <?php echo $estado_filtro === 'pendiente' ? 'button-primary' : ''; ?>">
            <?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($stats['pendientes'] > 0): ?>
            <span style="background: #d63638; color: #fff; padding: 0 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?php echo $stats['pendientes']; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=socios-solicitudes&estado=aprobada'); ?>" class="button <?php echo $estado_filtro === 'aprobada' ? 'button-primary' : ''; ?>"><?php _e('Aprobadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        <a href="<?php echo admin_url('admin.php?page=socios-solicitudes&estado=rechazada'); ?>" class="button <?php echo $estado_filtro === 'rechazada' ? 'button-primary' : ''; ?>"><?php _e('Rechazadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    </div>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s solicitud', '%s solicitudes', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php _e('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 140px;"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 150px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($solicitudes)): ?>
            <tr><td colspan="5" style="text-align: center; padding: 40px;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #00a32a;"></span>
                <p style="color: #646970;"><?php _e('No hay solicitudes en este estado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </td></tr>
            <?php else: ?>
                <?php foreach ($solicitudes as $sol): ?>
                <tr>
                    <td><code><?php echo esc_html($sol->id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php echo get_avatar($sol->user_id, 36); ?>
                            <div>
                                <strong><?php echo esc_html($sol->display_name ?: $sol->nombre ?? __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($sol->user_email ?: $sol->email ?? ''); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($colores_estado[$sol->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$sol->estado] ?? ucfirst($sol->estado)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($sol->fecha_solicitud))); ?></td>
                    <td>
                        <?php if ($sol->estado === 'pendiente'): ?>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('socios_solicitud_action'); ?>
                            <input type="hidden" name="solicitud_id" value="<?php echo esc_attr($sol->id); ?>">
                            <button type="submit" name="accion" value="aprobar" class="button button-small button-primary">
                                <span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
                            </button>
                            <button type="submit" name="accion" value="rechazar" class="button button-small" style="color: #d63638;">
                                <span class="dashicons dashicons-no" style="margin-top: 3px;"></span>
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color: #646970;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_paginas > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'total' => $total_paginas, 'current' => $pagina_actual]); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

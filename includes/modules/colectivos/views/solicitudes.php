<?php
/**
 * Vista Admin: Solicitudes de Unión a Colectivos
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
$tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

// Verificar si las tablas existen
$tabla_miembros_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_miembros}'") === $tabla_miembros;

// Filtros
$colectivo_filtro = isset($_GET['colectivo']) ? absint($_GET['colectivo']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'pendiente';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener colectivos para el filtro
$colectivos = $wpdb->get_results("SELECT id, nombre FROM {$tabla_colectivos} WHERE estado = 'activo' ORDER BY nombre ASC");

// Estados de solicitud
$estados = [
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'aprobada'  => __('Aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'rechazada' => __('Rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'pendiente' => '#dba617',
    'aprobada'  => '#00a32a',
    'rechazada' => '#d63638',
];

// Obtener solicitudes
$solicitudes = [];
$total_items = 0;

if ($tabla_miembros_existe) {
    $where = ["m.estado = %s"];
    $params = [$estado_filtro === 'pendiente' ? 'pendiente' : $estado_filtro];

    if ($colectivo_filtro) {
        $where[] = 'm.colectivo_id = %d';
        $params[] = $colectivo_filtro;
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_miembros} m WHERE {$where_sql}";
    $sql_items = "
        SELECT m.*, u.display_name, u.user_email, c.nombre as colectivo_nombre
        FROM {$tabla_miembros} m
        LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
        LEFT JOIN {$tabla_colectivos} c ON m.colectivo_id = c.id
        WHERE {$where_sql}
        ORDER BY m.created_at DESC
        LIMIT %d OFFSET %d
    ";

    $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
    $solicitudes = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas
$stats = [
    'pendientes' => 0,
    'aprobadas_hoy' => 0,
    'rechazadas_hoy' => 0,
];

if ($tabla_miembros_existe) {
    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE estado = 'pendiente'");
    $stats['aprobadas_hoy'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE estado = 'activo' AND DATE(updated_at) = CURDATE()");
    $stats['rechazadas_hoy'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE estado = 'rechazada' AND DATE(updated_at) = CURDATE()");
}

// Procesar acciones
if (isset($_POST['accion']) && isset($_POST['solicitud_id']) && wp_verify_nonce($_POST['_wpnonce'], 'colectivos_solicitud_action')) {
    $solicitud_id = absint($_POST['solicitud_id']);
    $accion = sanitize_text_field($_POST['accion']);

    if ($accion === 'aprobar') {
        $wpdb->update($tabla_miembros, ['estado' => 'activo', 'updated_at' => current_time('mysql')], ['id' => $solicitud_id]);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Solicitud aprobada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    } elseif ($accion === 'rechazar') {
        $wpdb->update($tabla_miembros, ['estado' => 'rechazada', 'updated_at' => current_time('mysql')], ['id' => $solicitud_id]);
        echo '<div class="notice notice-warning is-dismissible"><p>' . __('Solicitud rechazada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }

    // Recargar datos
    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE estado = 'pendiente'");
}
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-colectivos-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-groups" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Solicitudes de Unión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-businesswoman"></span>
        <?php _e('Solicitudes de Unión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['aprobadas_hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Aprobadas hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['rechazadas_hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Rechazadas hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Pestañas de estado -->
    <div style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=colectivos-solicitudes&estado=pendiente'); ?>"
           class="button <?php echo $estado_filtro === 'pendiente' ? 'button-primary' : ''; ?>">
            <?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($stats['pendientes'] > 0): ?>
            <span style="background: #d63638; color: #fff; padding: 0 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                <?php echo number_format($stats['pendientes']); ?>
            </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=colectivos-solicitudes&estado=activo'); ?>"
           class="button <?php echo $estado_filtro === 'activo' ? 'button-primary' : ''; ?>">
            <?php _e('Aprobadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=colectivos-solicitudes&estado=rechazada'); ?>"
           class="button <?php echo $estado_filtro === 'rechazada' ? 'button-primary' : ''; ?>">
            <?php _e('Rechazadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="colectivos-solicitudes">
            <input type="hidden" name="estado" value="<?php echo esc_attr($estado_filtro); ?>">

            <div class="alignleft actions" style="display: flex; gap: 8px;">
                <select name="colectivo">
                    <option value=""><?php _e('Todos los colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($colectivos as $col): ?>
                    <option value="<?php echo esc_attr($col->id); ?>" <?php selected($colectivo_filtro, $col->id); ?>>
                        <?php echo esc_html($col->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s solicitud', '%s solicitudes', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 140px;"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($solicitudes)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #00a32a;"></span>
                    <p style="color: #646970;"><?php _e('No hay solicitudes en este estado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($solicitudes as $sol): ?>
                <tr>
                    <td><code><?php echo esc_html($sol->id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php echo get_avatar($sol->user_id, 36); ?>
                            <div>
                                <strong><?php echo esc_html($sol->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($sol->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=colectivos-editar&id=' . $sol->colectivo_id); ?>">
                            <?php echo esc_html($sol->colectivo_nombre ?: __('Colectivo eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </a>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_estado[$sol->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$sol->estado] ?? ucfirst($sol->estado)); ?>
                        </span>
                    </td>
                    <td>
                        <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sol->created_at))); ?>">
                            <?php echo esc_html(human_time_diff(strtotime($sol->created_at), current_time('timestamp'))); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($sol->estado === 'pendiente'): ?>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('colectivos_solicitud_action'); ?>
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

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_paginas,
                'current' => $pagina_actual,
            ]);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

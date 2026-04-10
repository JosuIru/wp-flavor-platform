<?php
/**
 * Vista Admin: Listado de Socios
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_socios = $wpdb->prefix . 'flavor_socios';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_socios}'") === $tabla_socios;

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estados y tipos
$estados = ['activo' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'baja' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'suspendido' => __('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$tipos = ['ordinario' => __('Ordinario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'fundador' => __('Fundador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'honorario' => __('Honorario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'juvenil' => __('Juvenil', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$colores_estado = ['activo' => '#00a32a', 'pendiente' => '#dba617', 'baja' => '#646970', 'suspendido' => '#d63638'];

// Obtener socios
$socios = [];
$total_items = 0;

if ($tabla_existe) {
    $where = ['1=1'];
    $params = [];

    if ($estado_filtro) {
        $where[] = 's.estado = %s';
        $params[] = $estado_filtro;
    }

    if ($tipo_filtro) {
        $where[] = 's.tipo_socio = %s';
        $params[] = $tipo_filtro;
    }

    if ($buscar) {
        $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR s.numero_socio LIKE %s)';
        $params[] = '%' . $wpdb->esc_like($buscar) . '%';
        $params[] = '%' . $wpdb->esc_like($buscar) . '%';
        $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_socios} s LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID WHERE {$where_sql}";
    $sql_items = "SELECT s.*, u.display_name, u.user_email
                  FROM {$tabla_socios} s
                  LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                  WHERE {$where_sql}
                  ORDER BY s.fecha_alta DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $socios = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $socios = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas
$stats = ['total' => 0, 'activos' => 0, 'pendientes' => 0];
if ($tabla_existe) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios}");
    $stats['activos'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'");
    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'pendiente'");
}
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=socios-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-id-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Listado de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=socios-nuevo'); ?>" class="page-title-action"><?php _e('Añadir Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['total']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['activos']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="socios-listado">
            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tipo">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($tipo_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php if ($estado_filtro || $tipo_filtro || $buscar): ?>
                <a href="<?php echo admin_url('admin.php?page=socios-listado'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </div>
        </form>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s miembro', '%s miembros', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 80px;"><?php _e('Nº Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php _e('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($socios)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 40px;">
                <span class="dashicons dashicons-id-alt" style="font-size: 48px; color: #c3c4c7;"></span>
                <p style="color: #646970;"><?php _e('No se encontraron miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </td></tr>
            <?php else: ?>
                <?php foreach ($socios as $s): ?>
                <tr>
                    <td><code><?php echo esc_html($s->numero_socio ?? $s->id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php echo get_avatar($s->user_id, 32); ?>
                            <div>
                                <strong><?php echo esc_html($s->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($s->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html($tipos[$s->tipo_socio] ?? ucfirst($s->tipo_socio ?? 'ordinario')); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($colores_estado[$s->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$s->estado] ?? ucfirst($s->estado)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($s->fecha_alta))); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=socios-editar&id=' . $s->id); ?>" class="button button-small">
                            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span>
                        </a>
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

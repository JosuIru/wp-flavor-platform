<?php
/**
 * Vista Admin: Pagos de Socios
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_pagos = $wpdb->prefix . 'flavor_socios_pagos';
$tabla_socios = $wpdb->prefix . 'flavor_socios';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_pagos}'") === $tabla_pagos;

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$periodo_filtro = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '';
$metodo_filtro = isset($_GET['metodo']) ? sanitize_text_field($_GET['metodo']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estados y métodos
$estados = ['pendiente' => __('Pendiente', 'flavor-chat-ia'), 'pagado' => __('Pagado', 'flavor-chat-ia'), 'fallido' => __('Fallido', 'flavor-chat-ia'), 'reembolsado' => __('Reembolsado', 'flavor-chat-ia')];
$metodos = ['transferencia' => __('Transferencia', 'flavor-chat-ia'), 'tarjeta' => __('Tarjeta', 'flavor-chat-ia'), 'efectivo' => __('Efectivo', 'flavor-chat-ia'), 'domiciliacion' => __('Domiciliación', 'flavor-chat-ia')];
$colores_estado = ['pendiente' => '#dba617', 'pagado' => '#00a32a', 'fallido' => '#d63638', 'reembolsado' => '#646970'];

// Obtener pagos
$pagos = [];
$total_items = 0;

if ($tabla_existe) {
    $where = ['1=1'];
    $params = [];

    if ($estado_filtro) {
        $where[] = 'p.estado = %s';
        $params[] = $estado_filtro;
    }

    if ($metodo_filtro) {
        $where[] = 'p.metodo_pago = %s';
        $params[] = $metodo_filtro;
    }

    if ($periodo_filtro === 'mes') {
        $where[] = 'MONTH(p.fecha_pago) = MONTH(CURDATE()) AND YEAR(p.fecha_pago) = YEAR(CURDATE())';
    } elseif ($periodo_filtro === 'anio') {
        $where[] = 'YEAR(p.fecha_pago) = YEAR(CURDATE())';
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_pagos} p WHERE {$where_sql}";
    $sql_items = "SELECT p.*, s.numero_socio, u.display_name
                  FROM {$tabla_pagos} p
                  LEFT JOIN {$tabla_socios} s ON p.socio_id = s.id
                  LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                  WHERE {$where_sql}
                  ORDER BY p.fecha_pago DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $pagos = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $pagos = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas
$stats = ['total_recaudado' => 0, 'pendiente' => 0, 'pagos_mes' => 0];
if ($tabla_existe) {
    $stats['total_recaudado'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(importe), 0) FROM {$tabla_pagos} WHERE estado = 'pagado'");
    $stats['pendiente'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(importe), 0) FROM {$tabla_pagos} WHERE estado = 'pendiente'");
    $stats['pagos_mes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_pagos} WHERE estado = 'pagado' AND MONTH(fecha_pago) = MONTH(CURDATE())");
}
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=socios-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-id-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Socios', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Pagos', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money-alt"></span>
        <?php _e('Historial de Pagos', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['total_recaudado'], 2); ?> &euro;</div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Total recaudado', 'flavor-chat-ia'); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['pendiente'], 2); ?> &euro;</div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendiente cobro', 'flavor-chat-ia'); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['pagos_mes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pagos este mes', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="socios-pagos">
            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="metodo">
                    <option value=""><?php _e('Todos los métodos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($metodos as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($metodo_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="periodo">
                    <option value=""><?php _e('Todo el tiempo', 'flavor-chat-ia'); ?></option>
                    <option value="mes" <?php selected($periodo_filtro, 'mes'); ?>><?php _e('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="anio" <?php selected($periodo_filtro, 'anio'); ?>><?php _e('Este año', 'flavor-chat-ia'); ?></option>
                </select>
                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
                <?php if ($estado_filtro || $metodo_filtro || $periodo_filtro): ?>
                <a href="<?php echo admin_url('admin.php?page=socios-pagos'); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>
            </div>
        </form>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s pago', '%s pagos', $total_items, 'flavor-chat-ia'), number_format($total_items)); ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php _e('ID', 'flavor-chat-ia'); ?></th>
                <th><?php _e('Socio', 'flavor-chat-ia'); ?></th>
                <th style="width: 100px;"><?php _e('Importe', 'flavor-chat-ia'); ?></th>
                <th style="width: 120px;"><?php _e('Método', 'flavor-chat-ia'); ?></th>
                <th style="width: 100px;"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                <th style="width: 120px;"><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pagos)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 40px;">
                <span class="dashicons dashicons-money-alt" style="font-size: 48px; color: #c3c4c7;"></span>
                <p style="color: #646970;"><?php _e('No se encontraron pagos.', 'flavor-chat-ia'); ?></p>
            </td></tr>
            <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><code><?php echo esc_html($p->id); ?></code></td>
                    <td>
                        <strong><?php echo esc_html($p->display_name ?: __('Socio', 'flavor-chat-ia')); ?></strong>
                        <?php if ($p->numero_socio): ?>
                        <br><small style="color: #646970;">#<?php echo esc_html($p->numero_socio); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo number_format($p->importe, 2); ?> &euro;</strong></td>
                    <td><?php echo esc_html($metodos[$p->metodo_pago] ?? ucfirst($p->metodo_pago ?? '-')); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($colores_estado[$p->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$p->estado] ?? ucfirst($p->estado)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($p->fecha_pago ? date_i18n('d/m/Y', strtotime($p->fecha_pago)) : '-'); ?></td>
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

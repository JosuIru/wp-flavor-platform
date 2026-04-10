<?php
/**
 * Vista Solicitudes - Módulo Trámites
 *
 * Gestión de solicitudes de trámites con workflow completo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Construir query
$where = ['1=1'];
$prepare_values = [];

if ($estado_filtro) {
    $where[] = 'estado = %s';
    $prepare_values[] = $estado_filtro;
}
if ($tipo_filtro) {
    $where[] = 'tipo_tramite = %s';
    $prepare_values[] = $tipo_filtro;
}
if ($buscar) {
    $where[] = '(numero_solicitud LIKE %s OR nombre_solicitante LIKE %s)';
    $busqueda_like = '%' . $wpdb->esc_like($buscar) . '%';
    $prepare_values[] = $busqueda_like;
    $prepare_values[] = $busqueda_like;
}

$where_sql = implode(' AND ', $where);

// Paginación
$items_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Total items
if (!empty($prepare_values)) {
    $total_items = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_solicitudes WHERE $where_sql",
        ...$prepare_values
    ));
} else {
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE $where_sql");
}

$total_paginas = ceil($total_items / $items_por_pagina);

// Obtener solicitudes
$prepare_values[] = $offset;
$prepare_values[] = $items_por_pagina;

$solicitudes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_solicitudes WHERE $where_sql ORDER BY fecha_solicitud DESC LIMIT %d, %d",
    ...$prepare_values
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-page"></span>
        <?php echo esc_html__('Gestión de Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="#" class="page-title-action" onclick="document.getElementById('filtros').style.display='block'; return false;">
        <span class="dashicons dashicons-filter"></span> <?php echo esc_html__('Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div id="filtros" class="postbox" style="margin: 20px 0; display: none;">
        <div class="inside">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-tramites-solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><strong><?php echo esc_html__('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
                        <select name="estado" class="regular-text">
                            <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($estado_filtro, 'pendiente'); ?>><?php echo esc_html__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('en_revision', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($estado_filtro, 'en_revision'); ?>><?php echo esc_html__('En Revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($estado_filtro, 'aprobada'); ?>><?php echo esc_html__('Aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($estado_filtro, 'rechazada'); ?>><?php echo esc_html__('Rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div>
                        <label><strong><?php echo esc_html__('Buscar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
                        <input type="text" name="s" value="<?php echo esc_attr($buscar); ?>" class="regular-text" placeholder="<?php echo esc_attr__('Número o solicitante...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span> <?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=flavor-tramites-solicitudes'); ?>" class="button"><?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </form>
        </div>
    </div>

    <!-- Listado -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="inside" style="margin: 0; padding: 0;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;"><?php echo esc_html__('Número', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Tipo Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Fecha Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($solicitudes)): ?>
                        <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td><strong><?php echo esc_html($solicitud->numero_solicitud); ?></strong></td>
                                <td><?php echo esc_html($solicitud->nombre_solicitante); ?></td>
                                <td><?php echo esc_html($solicitud->tipo_tramite); ?></td>
                                <td>
                                    <?php
                                    $colores = [
                                        'pendiente' => '#f0b849',
                                        'en_revision' => '#2271b1',
                                        'aprobada' => '#00a32a',
                                        'rechazada' => '#d63638'
                                    ];
                                    $color = $colores[$solicitud->estado] ?? '#646970';
                                    ?>
                                    <span style="background: <?php echo $color; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                        <?php echo esc_html(ucfirst($solicitud->estado)); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($solicitud->fecha_solicitud)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=flavor-tramites-solicitudes&id=' . $solicitud->id); ?>" class="button button-small"><?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 0; color: #646970;">
                                <?php echo esc_html__('No se encontraron solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total_items); ?> elementos</span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $pagina_actual,
                    'total' => $total_paginas,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;'
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>

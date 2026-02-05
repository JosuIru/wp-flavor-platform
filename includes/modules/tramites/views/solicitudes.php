<?php
/**
 * Vista Solicitudes - Módulo Trámites
 *
 * Gestión de solicitudes de trámites con workflow completo
 *
 * @package FlavorChatIA
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
        Gestión de Solicitudes
    </h1>

    <a href="#" class="page-title-action" onclick="document.getElementById('filtros').style.display='block'; return false;">
        <span class="dashicons dashicons-filter"></span> Filtros
    </a>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div id="filtros" class="postbox" style="margin: 20px 0; display: none;">
        <div class="inside">
            <form method="get" action="">
                <input type="hidden" name="page" value="flavor-tramites-solicitudes">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><strong>Estado:</strong></label>
                        <select name="estado" class="regular-text">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php selected($estado_filtro, 'pendiente'); ?>>Pendiente</option>
                            <option value="en_revision" <?php selected($estado_filtro, 'en_revision'); ?>>En Revisión</option>
                            <option value="aprobada" <?php selected($estado_filtro, 'aprobada'); ?>>Aprobada</option>
                            <option value="rechazada" <?php selected($estado_filtro, 'rechazada'); ?>>Rechazada</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>Buscar:</strong></label>
                        <input type="text" name="s" value="<?php echo esc_attr($buscar); ?>" class="regular-text" placeholder="Número o solicitante...">
                    </div>
                </div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span> Filtrar
                </button>
                <a href="<?php echo admin_url('admin.php?page=flavor-tramites-solicitudes'); ?>" class="button">Limpiar</a>
            </form>
        </div>
    </div>

    <!-- Listado -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="inside" style="margin: 0; padding: 0;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;">Número</th>
                        <th>Solicitante</th>
                        <th>Tipo Trámite</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 120px;">Fecha Solicitud</th>
                        <th style="width: 100px;">Acciones</th>
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
                                    <a href="<?php echo admin_url('admin.php?page=flavor-tramites-solicitudes&id=' . $solicitud->id); ?>" class="button button-small">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 0; color: #646970;">
                                No se encontraron solicitudes
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

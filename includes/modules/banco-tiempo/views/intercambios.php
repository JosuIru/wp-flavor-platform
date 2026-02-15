<?php
/**
 * Vista Intercambios - Banco de Tiempo
 *
 * Gestión de intercambios y transacciones entre usuarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

// Procesar acciones
if (isset($_POST['accion']) && check_admin_referer('banco_tiempo_intercambios')) {
    $accion = sanitize_text_field($_POST['accion']);
    $intercambio_id = absint($_POST['intercambio_id']);

    switch ($accion) {
        case 'aprobar':
            $wpdb->update(
                $tabla_transacciones,
                ['estado' => 'completado', 'fecha_completado' => current_time('mysql')],
                ['id' => $intercambio_id]
            );
            break;

        case 'rechazar':
            $wpdb->update(
                $tabla_transacciones,
                ['estado' => 'cancelado'],
                ['id' => $intercambio_id]
            );
            break;
    }
}

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';
$paginacion_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$items_por_pagina = 25;
$offset = ($paginacion_actual - 1) * $items_por_pagina;

// Construir query
$where = ['1=1'];
$preparar = [];

if ($filtro_estado) {
    $where[] = "t.estado = %s";
    $preparar[] = $filtro_estado;
}

if ($filtro_fecha_desde) {
    $where[] = "DATE(t.fecha_solicitud) >= %s";
    $preparar[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $where[] = "DATE(t.fecha_solicitud) <= %s";
    $preparar[] = $filtro_fecha_hasta;
}

$where_sql = implode(' AND ', $where);

// Obtener total
if (!empty($preparar)) {
    $total_intercambios = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones t WHERE $where_sql",
        ...$preparar
    ));
} else {
    $total_intercambios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones t WHERE $where_sql");
}

// Obtener intercambios
$preparar_paginado = $preparar;
$preparar_paginado[] = $items_por_pagina;
$preparar_paginado[] = $offset;

if (count($preparar_paginado) > 2) {
    $intercambios = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, s.titulo as servicio_titulo, s.categoria
         FROM $tabla_transacciones t
         LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
         WHERE $where_sql
         ORDER BY t.fecha_solicitud DESC
         LIMIT %d OFFSET %d",
        ...$preparar_paginado
    ));
} else {
    $intercambios = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, s.titulo as servicio_titulo, s.categoria
         FROM $tabla_transacciones t
         LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
         WHERE $where_sql
         ORDER BY t.fecha_solicitud DESC
         LIMIT %d OFFSET %d",
        $items_por_pagina,
        $offset
    ));
}

$total_paginas = ceil($total_intercambios / $items_por_pagina);

// Estadísticas rápidas
$stats = [
    'pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'pendiente'"),
    'aceptados' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'aceptado'"),
    'completados' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'completado'"),
    'cancelados' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'cancelado'"),
];

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-randomize"></span>
        <?php echo esc_html__('Gestión de Intercambios', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas Rápidas -->
    <div class="intercambios-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 20px 0;">
        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #dba617; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #dba617;">
                <?php echo number_format($stats['pendientes']); ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #2271b1;">
                <?php echo number_format($stats['aceptados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Aceptados', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #00a32a;">
                <?php echo number_format($stats['completados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Completados', 'flavor-chat-ia'); ?></div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #d63638;">
                <?php echo number_format($stats['cancelados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;"><?php echo esc_html__('Cancelados', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">

                <select name="estado">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'pendiente'); ?>><?php echo esc_html__('Pendiente', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('aceptado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'aceptado'); ?>><?php echo esc_html__('Aceptado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('completado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'completado'); ?>><?php echo esc_html__('Completado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('cancelado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'cancelado'); ?>><?php echo esc_html__('Cancelado', 'flavor-chat-ia'); ?></option>
                </select>

                <input type="date" name="fecha_desde" value="<?php echo esc_attr($filtro_fecha_desde); ?>"
                       placeholder="<?php echo esc_attr__('Desde', 'flavor-chat-ia'); ?>">

                <input type="date" name="fecha_hasta" value="<?php echo esc_attr($filtro_fecha_hasta); ?>"
                       placeholder="<?php echo esc_attr__('Hasta', 'flavor-chat-ia'); ?>">

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if ($filtro_estado || $filtro_fecha_desde || $filtro_fecha_hasta): ?>
                    <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>" class="button"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>

                <button type="button" class="button" id="btn-exportar-csv">
                    <span class="dashicons dashicons-download"></span> <?php echo esc_html__('Exportar CSV', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Tabla de Intercambios -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Servicio', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Solicitante', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Proveedor', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Horas', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                <th style="width: 180px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($intercambios)): ?>
                <?php foreach ($intercambios as $intercambio):
                    $solicitante = get_userdata($intercambio->usuario_solicitante_id);
                    $receptor = get_userdata($intercambio->usuario_receptor_id);

                    $estado_config = [
                        'pendiente' => ['class' => 'warning', 'texto' => 'Pendiente', 'color' => '#dba617'],
                        'aceptado' => ['class' => 'info', 'texto' => 'Aceptado', 'color' => '#2271b1'],
                        'completado' => ['class' => 'success', 'texto' => 'Completado', 'color' => '#00a32a'],
                        'cancelado' => ['class' => 'error', 'texto' => 'Cancelado', 'color' => '#d63638']
                    ];

                    $estado = $estado_config[$intercambio->estado] ?? ['class' => 'default', 'texto' => $intercambio->estado, 'color' => '#646970'];
                ?>
                <tr>
                    <td><strong>#<?php echo $intercambio->id; ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($intercambio->servicio_titulo ?: 'Sin título'); ?></strong>
                        <?php if ($intercambio->categoria): ?>
                            <br><small style="color: #646970;">
                                <?php echo esc_html(ucfirst($intercambio->categoria)); ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($solicitante): ?>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $intercambio->usuario_solicitante_id); ?>">
                                <?php echo esc_html($solicitante->display_name); ?>
                            </a>
                        <?php else: ?>
                            <em><?php echo esc_html__('Usuario desconocido', 'flavor-chat-ia'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($receptor): ?>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $intercambio->usuario_receptor_id); ?>">
                                <?php echo esc_html($receptor->display_name); ?>
                            </a>
                        <?php else: ?>
                            <em><?php echo esc_html__('Usuario desconocido', 'flavor-chat-ia'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo number_format($intercambio->horas, 1); ?> h</strong></td>
                    <td>
                        <span class="estado-badge"
                              style="padding: 4px 10px; border-radius: 3px; background: <?php echo $estado['color']; ?>; color: #fff; font-size: 11px; font-weight: 600;">
                            <?php echo $estado['texto']; ?>
                        </span>
                    </td>
                    <td>
                        <?php echo date_i18n('d/m/Y H:i', strtotime($intercambio->fecha_creacion)); ?>
                        <?php if ($intercambio->fecha_completado): ?>
                            <br><small style="color: #00a32a;">
                                Completado: <?php echo date_i18n('d/m/Y', strtotime($intercambio->fecha_completado)); ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="#" class="button button-small ver-detalle"
                           data-id="<?php echo $intercambio->id; ?>">
                            <?php echo esc_html__('Ver Detalles', 'flavor-chat-ia'); ?>
                        </a>

                        <?php if ($intercambio->estado === 'pendiente'): ?>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('banco_tiempo_intercambios'); ?>
                                <input type="hidden" name="accion" value="<?php echo esc_attr__('aprobar', 'flavor-chat-ia'); ?>">
                                <input type="hidden" name="intercambio_id" value="<?php echo $intercambio->id; ?>">
                                <button type="submit" class="button button-small button-primary"
                                        onclick="return confirm('¿Aprobar este intercambio?');">
                                    <?php echo esc_html__('Aprobar', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #646970;">
                        <span class="dashicons dashicons-info" style="font-size: 48px;"></span>
                        <p><?php echo esc_html__('No se encontraron intercambios.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php echo number_format($total_intercambios); ?> intercambios
                </span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_paginas,
                    'current' => $paginacion_actual
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Modal Detalles -->
<div id="modal-detalle-intercambio" style="display:none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="modal-content" style="position: relative; max-width: 700px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 4px;">
            <h2><?php echo esc_html__('Detalles del Intercambio', 'flavor-chat-ia'); ?></h2>
            <div id="contenido-detalle-intercambio"></div>
            <p>
                <button type="button" class="button" id="btn-cerrar-detalle"><?php echo esc_html__('Cerrar', 'flavor-chat-ia'); ?></button>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // Ver detalle
    $('.ver-detalle').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        // Aquí cargar datos vía AJAX
        $('#modal-detalle-intercambio').fadeIn();
    });

    $('#btn-cerrar-detalle, .modal-overlay').click(function(e) {
        if (e.target === this) {
            $('#modal-detalle-intercambio').fadeOut();
        }
    });

    // Exportar CSV
    $('#btn-exportar-csv').click(function() {
        var params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = '?' + params.toString();
    });
});
</script>

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
    $where[] = "DATE(t.fecha_creacion) >= %s";
    $preparar[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $where[] = "DATE(t.fecha_creacion) <= %s";
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
         ORDER BY t.fecha_creacion DESC
         LIMIT %d OFFSET %d",
        ...$preparar_paginado
    ));
} else {
    $intercambios = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, s.titulo as servicio_titulo, s.categoria
         FROM $tabla_transacciones t
         LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
         WHERE $where_sql
         ORDER BY t.fecha_creacion DESC
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
        Gestión de Intercambios
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas Rápidas -->
    <div class="intercambios-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 20px 0;">
        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #dba617; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #dba617;">
                <?php echo number_format($stats['pendientes']); ?>
            </div>
            <div class="stat-label" style="color: #646970;">Pendientes</div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #2271b1;">
                <?php echo number_format($stats['aceptados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;">Aceptados</div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #00a32a;">
                <?php echo number_format($stats['completados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;">Completados</div>
        </div>

        <div class="stat-box" style="background: #fff; padding: 16px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-value" style="font-size: 28px; font-weight: bold; color: #d63638;">
                <?php echo number_format($stats['cancelados']); ?>
            </div>
            <div class="stat-label" style="color: #646970;">Cancelados</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>>Pendiente</option>
                    <option value="aceptado" <?php selected($filtro_estado, 'aceptado'); ?>>Aceptado</option>
                    <option value="completado" <?php selected($filtro_estado, 'completado'); ?>>Completado</option>
                    <option value="cancelado" <?php selected($filtro_estado, 'cancelado'); ?>>Cancelado</option>
                </select>

                <input type="date" name="fecha_desde" value="<?php echo esc_attr($filtro_fecha_desde); ?>"
                       placeholder="Desde">

                <input type="date" name="fecha_hasta" value="<?php echo esc_attr($filtro_fecha_hasta); ?>"
                       placeholder="Hasta">

                <button type="submit" class="button">Filtrar</button>

                <?php if ($filtro_estado || $filtro_fecha_desde || $filtro_fecha_hasta): ?>
                    <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">Limpiar</a>
                <?php endif; ?>

                <button type="button" class="button" id="btn-exportar-csv">
                    <span class="dashicons dashicons-download"></span> Exportar CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Tabla de Intercambios -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Servicio</th>
                <th>Solicitante</th>
                <th>Proveedor</th>
                <th>Horas</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th style="width: 180px;">Acciones</th>
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
                            <em>Usuario desconocido</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($receptor): ?>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $intercambio->usuario_receptor_id); ?>">
                                <?php echo esc_html($receptor->display_name); ?>
                            </a>
                        <?php else: ?>
                            <em>Usuario desconocido</em>
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
                            Ver Detalles
                        </a>

                        <?php if ($intercambio->estado === 'pendiente'): ?>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('banco_tiempo_intercambios'); ?>
                                <input type="hidden" name="accion" value="aprobar">
                                <input type="hidden" name="intercambio_id" value="<?php echo $intercambio->id; ?>">
                                <button type="submit" class="button button-small button-primary"
                                        onclick="return confirm('¿Aprobar este intercambio?');">
                                    Aprobar
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
                        <p>No se encontraron intercambios.</p>
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
            <h2>Detalles del Intercambio</h2>
            <div id="contenido-detalle-intercambio"></div>
            <p>
                <button type="button" class="button" id="btn-cerrar-detalle">Cerrar</button>
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

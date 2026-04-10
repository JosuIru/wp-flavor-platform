<?php
/**
 * Vista Admin: Consolidado de Pedidos por Productor
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener ciclo seleccionado
$ciclo_id = absint($_GET['ciclo'] ?? 0);

// Obtener ciclos disponibles
$ciclos = get_posts([
    'post_type' => 'gc_ciclo',
    'posts_per_page' => 20,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Si no hay ciclo seleccionado, tomar el más reciente
if (!$ciclo_id && !empty($ciclos)) {
    $ciclo_id = $ciclos[0]->ID;
}

// Obtener consolidado del ciclo
$consolidado = [];
$totales_por_productor = [];
$total_general = 0;
$kpi_total_pedidos = 0;
$kpi_total_consumidores = 0;
$kpi_total_productos = 0;
$kpi_total_productores = 0;
$kpi_ticket_medio = 0;

if ($ciclo_id) {
    $consolidado_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, p.post_title as producto_nombre, pr.post_title as productor_nombre,
                pm_unidad.meta_value as unidad, pm_precio.meta_value as precio_base
         FROM {$wpdb->prefix}flavor_gc_consolidado c
         LEFT JOIN {$wpdb->posts} p ON c.producto_id = p.ID
         LEFT JOIN {$wpdb->posts} pr ON c.productor_id = pr.ID
         LEFT JOIN {$wpdb->postmeta} pm_unidad ON p.ID = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
         LEFT JOIN {$wpdb->postmeta} pm_precio ON p.ID = pm_precio.post_id AND pm_precio.meta_key = '_gc_precio'
         WHERE c.ciclo_id = %d
         ORDER BY pr.post_title, p.post_title",
        $ciclo_id
    ));

    // Agrupar por productor
    foreach ($consolidado_raw as $item) {
        $productor_id = $item->productor_id ?: 0;
        if (!isset($consolidado[$productor_id])) {
            $consolidado[$productor_id] = [
                'nombre' => $item->productor_nombre ?: 'Sin productor asignado',
                'email' => $productor_id ? get_post_meta($productor_id, '_gc_email', true) : '',
                'telefono' => $productor_id ? get_post_meta($productor_id, '_gc_telefono', true) : '',
                'productos' => [],
                'total' => 0,
            ];
        }
        $consolidado[$productor_id]['productos'][] = $item;
        $consolidado[$productor_id]['total'] += $item->total;
        $total_general += $item->total;
    }

    $totales_por_productor = array_map(function($p) {
        return ['nombre' => $p['nombre'], 'total' => $p['total']];
    }, $consolidado);

    // KPIs del ciclo
    $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
    if (Flavor_Platform_Helpers::tabla_existe($tabla_pedidos)) {
        $kpi_total_pedidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
            $ciclo_id
        ));
        $kpi_total_consumidores = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
            $ciclo_id
        ));
        $kpi_total_productos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT producto_id) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
            $ciclo_id
        ));
    }
    $kpi_total_productores = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT productor_id) FROM {$wpdb->prefix}flavor_gc_consolidado WHERE ciclo_id = %d",
        $ciclo_id
    ));
    if ($kpi_total_pedidos > 0) {
        $kpi_ticket_medio = $total_general / $kpi_total_pedidos;
    }
}

// Info del ciclo
$ciclo_info = $ciclo_id ? get_post($ciclo_id) : null;
$fecha_cierre = $ciclo_id ? get_post_meta($ciclo_id, '_gc_fecha_cierre', true) : '';
$fecha_entrega = $ciclo_id ? get_post_meta($ciclo_id, '_gc_fecha_entrega', true) : '';
$estado_ciclo = $ciclo_id ? get_post_meta($ciclo_id, '_gc_estado', true) : '';

// Marcar consolidado como visto
if ($ciclo_id && isset($_GET['marcar_visto']) && wp_verify_nonce($_GET['_wpnonce'], 'gc_marcar_visto')) {
    update_post_meta($ciclo_id, '_gc_consolidado_visto_' . get_current_user_id(), current_time('mysql'));
    echo '<div class="notice notice-success is-dismissible"><p>Consolidado marcado como visto.</p></div>';
}
?>

<div class="wrap gc-admin-consolidado">
    <h1><?php _e('Consolidado de Pedidos', 'flavor-platform'); ?></h1>

    <!-- Selector de Ciclo -->
    <div class="gc-ciclo-selector">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr__('gc-consolidado', 'flavor-platform'); ?>">
            <label for="ciclo"><?php _e('Seleccionar Ciclo:', 'flavor-platform'); ?></label>
            <select name="ciclo" id="ciclo" onchange="this.form.submit()">
                <?php foreach ($ciclos as $ciclo): ?>
                    <option value="<?php echo $ciclo->ID; ?>" <?php selected($ciclo_id, $ciclo->ID); ?>>
                        <?php echo esc_html($ciclo->post_title); ?>
                        <?php
                        $estado = get_post_meta($ciclo->ID, '_gc_estado', true);
                        echo ' (' . esc_html($estado ?: 'sin estado') . ')';
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($ciclo_id && $ciclo_info): ?>
        <!-- Info del Ciclo -->
        <div class="gc-ciclo-info">
            <div class="gc-info-card">
                <h3><?php echo esc_html($ciclo_info->post_title); ?></h3>
                <div class="gc-info-grid">
                    <div class="gc-info-item">
                        <span class="gc-info-label"><?php _e('Estado:', 'flavor-platform'); ?></span>
                        <span class="gc-estado gc-estado-<?php echo esc_attr($estado_ciclo); ?>">
                            <?php echo esc_html(ucfirst($estado_ciclo)); ?>
                        </span>
                    </div>
                    <div class="gc-info-item">
                        <span class="gc-info-label"><?php _e('Cierre:', 'flavor-platform'); ?></span>
                        <span><?php echo $fecha_cierre ? date_i18n('d M Y H:i', strtotime($fecha_cierre)) : 'N/A'; ?></span>
                    </div>
                    <div class="gc-info-item">
                        <span class="gc-info-label"><?php _e('Entrega:', 'flavor-platform'); ?></span>
                        <span><?php echo $fecha_entrega ? date_i18n('d M Y', strtotime($fecha_entrega)) : 'N/A'; ?></span>
                    </div>
                    <div class="gc-info-item gc-info-total">
                        <span class="gc-info-label"><?php _e('Total General:', 'flavor-platform'); ?></span>
                        <span class="gc-total-amount"><?php echo number_format($total_general, 2, ',', '.'); ?>€</span>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="gc-acciones">
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gc_exportar_consolidado&ciclo_id=' . $ciclo_id . '&formato=excel'), 'gc_exportar_consolidado'); ?>"
                   class="button button-primary">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Exportar Excel', 'flavor-platform'); ?>
                </a>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gc_exportar_consolidado&ciclo_id=' . $ciclo_id . '&formato=pdf'), 'gc_exportar_consolidado'); ?>"
                   class="button">
                    <span class="dashicons dashicons-pdf"></span>
                    <?php _e('Exportar PDF', 'flavor-platform'); ?>
                </a>
                <button type="button" class="button gc-btn-enviar-productores" data-ciclo="<?php echo $ciclo_id; ?>">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Enviar a Productores', 'flavor-platform'); ?>
                </button>
                <button type="button" class="button gc-btn-imprimir">
                    <span class="dashicons dashicons-printer"></span>
                    <?php _e('Imprimir', 'flavor-platform'); ?>
                </button>
            </div>
        </div>

        <!-- KPIs del ciclo -->
        <div class="gc-kpis-grid">
            <div class="gc-kpi-card">
                <div class="gc-kpi-icon"><span class="dashicons dashicons-cart"></span></div>
                <div class="gc-kpi-content">
                    <span class="gc-kpi-value"><?php echo number_format($kpi_total_pedidos); ?></span>
                    <span class="gc-kpi-label"><?php _e('Pedidos', 'flavor-platform'); ?></span>
                </div>
            </div>
            <div class="gc-kpi-card">
                <div class="gc-kpi-icon"><span class="dashicons dashicons-groups"></span></div>
                <div class="gc-kpi-content">
                    <span class="gc-kpi-value"><?php echo number_format($kpi_total_consumidores); ?></span>
                    <span class="gc-kpi-label"><?php _e('Consumidores', 'flavor-platform'); ?></span>
                </div>
            </div>
            <div class="gc-kpi-card">
                <div class="gc-kpi-icon"><span class="dashicons dashicons-products"></span></div>
                <div class="gc-kpi-content">
                    <span class="gc-kpi-value"><?php echo number_format($kpi_total_productos); ?></span>
                    <span class="gc-kpi-label"><?php _e('Productos', 'flavor-platform'); ?></span>
                </div>
            </div>
            <div class="gc-kpi-card">
                <div class="gc-kpi-icon"><span class="dashicons dashicons-store"></span></div>
                <div class="gc-kpi-content">
                    <span class="gc-kpi-value"><?php echo number_format($kpi_total_productores); ?></span>
                    <span class="gc-kpi-label"><?php _e('Productores', 'flavor-platform'); ?></span>
                </div>
            </div>
            <div class="gc-kpi-card gc-kpi-highlight">
                <div class="gc-kpi-icon"><span class="dashicons dashicons-chart-line"></span></div>
                <div class="gc-kpi-content">
                    <span class="gc-kpi-value"><?php echo number_format($kpi_ticket_medio, 2, ',', '.'); ?>€</span>
                    <span class="gc-kpi-label"><?php _e('Ticket medio', 'flavor-platform'); ?></span>
                </div>
            </div>
        </div>

        <!-- Resumen por Productor (gráfico) -->
        <?php if (!empty($totales_por_productor)): ?>
            <div class="gc-resumen-chart">
                <h2><?php _e('Distribución por Productor', 'flavor-platform'); ?></h2>
                <div class="gc-chart-container">
                    <canvas id="gc-chart-productores" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>

        <!-- Consolidado por Productor -->
        <?php if (!empty($consolidado)): ?>
            <div class="gc-tabla-card">
                <h2><?php _e('KPIs por productor', 'flavor-platform'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Productor', 'flavor-platform'); ?></th>
                            <th class="text-right"><?php _e('Total', 'flavor-platform'); ?></th>
                            <th class="text-right"><?php _e('% del ciclo', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consolidado as $productor_id => $productor_data): ?>
                            <tr>
                                <td><?php echo esc_html($productor_data['nombre']); ?></td>
                                <td class="text-right"><?php echo number_format($productor_data['total'], 2, ',', '.'); ?>€</td>
                                <td class="text-right">
                                    <?php
                                    $porcentaje = $total_general > 0 ? ($productor_data['total'] / $total_general) * 100 : 0;
                                    echo number_format($porcentaje, 1, ',', '.') . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="gc-consolidado-lista">
                <?php foreach ($consolidado as $productor_id => $productor_data): ?>
                    <div class="gc-productor-card" data-productor="<?php echo $productor_id; ?>">
                        <div class="gc-productor-header">
                            <h2>
                                <?php echo esc_html($productor_data['nombre']); ?>
                                <span class="gc-productor-total"><?php echo number_format($productor_data['total'], 2, ',', '.'); ?>€</span>
                            </h2>
                            <?php if ($productor_data['email']): ?>
                                <span class="gc-productor-contacto">
                                    <a href="mailto:<?php echo esc_attr($productor_data['email']); ?>">
                                        <?php echo esc_html($productor_data['email']); ?>
                                    </a>
                                    <?php if ($productor_data['telefono']): ?>
                                        | <?php echo esc_html($productor_data['telefono']); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <button type="button" class="gc-toggle-detalle" aria-expanded="true">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                        </div>

                        <div class="gc-productor-detalle">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th class="column-producto"><?php _e('Producto', 'flavor-platform'); ?></th>
                                        <th class="column-cantidad"><?php _e('Cantidad', 'flavor-platform'); ?></th>
                                        <th class="column-unidad"><?php _e('Unidad', 'flavor-platform'); ?></th>
                                        <th class="column-precio"><?php _e('Precio Unit.', 'flavor-platform'); ?></th>
                                        <th class="column-total"><?php _e('Total', 'flavor-platform'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productor_data['productos'] as $producto): ?>
                                        <tr>
                                            <td class="column-producto">
                                                <strong><?php echo esc_html($producto->producto_nombre); ?></strong>
                                            </td>
                                            <td class="column-cantidad">
                                                <?php echo number_format($producto->cantidad_total, 2, ',', '.'); ?>
                                            </td>
                                            <td class="column-unidad">
                                                <?php echo esc_html($producto->unidad ?: 'ud'); ?>
                                            </td>
                                            <td class="column-precio">
                                                <?php
                                                $precio_unit = $producto->cantidad_total > 0
                                                    ? $producto->total / $producto->cantidad_total
                                                    : 0;
                                                echo number_format($precio_unit, 2, ',', '.') . '€';
                                                ?>
                                            </td>
                                            <td class="column-total">
                                                <strong><?php echo number_format($producto->total, 2, ',', '.'); ?>€</strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="gc-subtotal-row">
                                        <td colspan="4" class="text-right">
                                            <strong><?php _e('Subtotal:', 'flavor-platform'); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($productor_data['total'], 2, ',', '.'); ?>€</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total General -->
                <div class="gc-total-general-card">
                    <div class="gc-total-general">
                        <span class="gc-total-label"><?php _e('TOTAL GENERAL:', 'flavor-platform'); ?></span>
                        <span class="gc-total-value"><?php echo number_format($total_general, 2, ',', '.'); ?>€</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="gc-no-data">
                <span class="dashicons dashicons-info"></span>
                <p><?php _e('No hay datos de consolidado para este ciclo.', 'flavor-platform'); ?></p>
                <p class="description"><?php _e('El consolidado se genera automáticamente cuando se cierran los pedidos del ciclo.', 'flavor-platform'); ?></p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="gc-no-data">
            <span class="dashicons dashicons-info"></span>
            <p><?php _e('No hay ciclos disponibles.', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.gc-admin-consolidado {
    max-width: 1200px;
}

.gc-ciclo-selector {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-ciclo-selector select {
    min-width: 300px;
    padding: 8px;
}

.gc-ciclo-info {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-info-card h3 {
    margin: 0 0 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #2c5530;
}

.gc-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.gc-info-item {
    display: flex;
    flex-direction: column;
}

.gc-info-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.gc-info-total .gc-total-amount {
    font-size: 24px;
    font-weight: bold;
    color: #2c5530;
}

.gc-estado {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.gc-estado-abierto { background: #e8f5e9; color: #2e7d32; }
.gc-estado-cerrado { background: #fff3e0; color: #ef6c00; }
.gc-estado-entregado { background: #e3f2fd; color: #1565c0; }

.gc-acciones {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gc-acciones .button .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

.gc-resumen-chart {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-chart-container {
    max-width: 600px;
    margin: 0 auto;
}

.gc-consolidado-lista {
    margin: 20px 0;
}

.gc-productor-card {
    background: #fff;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.gc-productor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.gc-productor-header h2 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.gc-productor-total {
    background: #2c5530;
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
}

.gc-productor-contacto {
    font-size: 13px;
    color: #666;
}

.gc-productor-contacto a {
    text-decoration: none;
}

.gc-toggle-detalle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.gc-toggle-detalle .dashicons {
    width: 24px;
    height: 24px;
    font-size: 24px;
}

.gc-productor-detalle {
    padding: 0;
}

.gc-productor-detalle table {
    margin: 0;
    border: none;
}

.gc-productor-detalle .column-cantidad,
.gc-productor-detalle .column-precio,
.gc-productor-detalle .column-total {
    text-align: right;
    width: 120px;
}

.gc-productor-detalle .column-unidad {
    width: 80px;
}

.gc-subtotal-row {
    background: #f8f9fa !important;
}

.gc-subtotal-row td {
    padding: 12px 10px;
}

.text-right {
    text-align: right;
}

.gc-total-general-card {
    background: #2c5530;
    padding: 20px 30px;
    border-radius: 8px;
    margin-top: 20px;
}

.gc-total-general {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
}

.gc-total-label {
    font-size: 18px;
    font-weight: 500;
}

.gc-total-value {
    font-size: 28px;
    font-weight: bold;
}

.gc-no-data {
    background: #fff;
    padding: 40px;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-no-data .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.gc-no-data p {
    margin: 10px 0;
    color: #666;
}

/* Impresión */
@media print {
    .gc-ciclo-selector,
    .gc-acciones,
    .gc-toggle-detalle,
    .gc-resumen-chart {
        display: none !important;
    }

    .gc-productor-detalle {
        display: block !important;
    }

    .gc-productor-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    function gcConsolidadoAviso(mensaje, tipo) {
        tipo = tipo || 'error';
        $('.gc-inline-notice').remove();
        $('<div class="gc-inline-notice gc-inline-notice-' + tipo + '"><p>' + mensaje + '</p></div>').insertAfter('.wrap h1.wp-heading-inline').hide().fadeIn(150);
    }

    function gcConsolidadoConfirmar(mensaje, onConfirm) {
        $('.gc-inline-confirm').remove();
        var $confirm = $('<div class="gc-inline-confirm"><p></p><div class="gc-inline-confirm-actions"><button type="button" class="button button-primary gc-inline-confirm-ok"><?php echo esc_js(__('Confirmar', 'flavor-platform')); ?></button><button type="button" class="button gc-inline-confirm-cancel"><?php echo esc_js(__('Cancelar', 'flavor-platform')); ?></button></div></div>');
        $confirm.find('p').text(mensaje);
        $confirm.insertAfter('.wrap h1.wp-heading-inline').hide().fadeIn(150);

        $confirm.on('click', '.gc-inline-confirm-ok', function() {
            $confirm.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        $confirm.on('click', '.gc-inline-confirm-cancel', function() {
            $confirm.remove();
        });
    }

    // Toggle detalle productor
    $('.gc-toggle-detalle').on('click', function() {
        var $card = $(this).closest('.gc-productor-card');
        var $detalle = $card.find('.gc-productor-detalle');
        var $icon = $(this).find('.dashicons');

        $detalle.slideToggle(200);
        $icon.toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
        $(this).attr('aria-expanded', $detalle.is(':visible'));
    });

    // Imprimir
    $('.gc-btn-imprimir').on('click', function() {
        window.print();
    });

    // Enviar a productores
    $('.gc-btn-enviar-productores').on('click', function() {
        var cicloId = $(this).data('ciclo');
        gcConsolidadoConfirmar('<?php echo esc_js(__('¿Enviar el consolidado a todos los productores?', 'flavor-platform')); ?>', function() {
            $.post(ajaxurl, {
                action: 'gc_enviar_consolidado_productores',
                ciclo_id: cicloId,
                nonce: '<?php echo wp_create_nonce('gc_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    gcConsolidadoAviso('<?php echo esc_js(__('Consolidado enviado correctamente', 'flavor-platform')); ?>', 'success');
                } else {
                    gcConsolidadoAviso(response.data.message || '<?php echo esc_js(__('Error al enviar', 'flavor-platform')); ?>', 'error');
                }
            });
        });
    });

    // Gráfico de productores
    <?php if (!empty($totales_por_productor)): ?>
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('gc-chart-productores');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo wp_json_encode(array_column($totales_por_productor, 'nombre')); ?>,
                    datasets: [{
                        data: <?php echo wp_json_encode(array_column($totales_por_productor, 'total')); ?>,
                        backgroundColor: [
                            '#2c5530', '#4a7c59', '#6b9b7a', '#8dba9b', '#afd9bc',
                            '#3d5c6b', '#5b8aa0', '#79b8d5', '#97c6e0', '#b5d4eb'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.raw.toFixed(2).replace('.', ',');
                                    return context.label + ': ' + value + '€';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    <?php endif; ?>
});
</script>
<style>
.gc-inline-notice{margin:16px 0;padding:12px 14px;border-left:4px solid #d63638;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.gc-inline-notice-success{border-left-color:#00a32a}
.gc-inline-notice-error{border-left-color:#d63638}
.gc-inline-confirm{margin:16px 0;padding:12px 14px;border-left:4px solid #dba617;background:#fff8e1;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.gc-inline-confirm p{margin:0 0 10px}
.gc-inline-confirm-actions{display:flex;gap:8px;flex-wrap:wrap}
</style>

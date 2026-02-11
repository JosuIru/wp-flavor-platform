<?php
/**
 * Vista Dashboard - Grupos de Consumo
 *
 * Panel principal con estadísticas de pedidos y productos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

// Obtener estadísticas generales
$total_pedidos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos");
$pedidos_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos WHERE estado = 'pendiente'");
$pedidos_completados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos WHERE estado = 'completado'");

// Productos totales
$total_productos = wp_count_posts('gc_producto')->publish;
$total_productores = wp_count_posts('gc_productor')->publish;

// Ciclos
$args_ciclos = [
    'post_type' => 'gc_ciclo',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => '_gc_estado',
            'value' => 'abierto'
        ]
    ]
];
$ciclo_actual = new WP_Query($args_ciclos);
$hay_ciclo_abierto = $ciclo_actual->have_posts();

// Ventas totales del mes actual
$primer_dia_mes = date('Y-m-01 00:00:00');
$ventas_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT IFNULL(SUM(cantidad * precio_unitario), 0)
     FROM $tabla_pedidos
     WHERE estado = 'completado'
     AND fecha_pedido >= %s",
    $primer_dia_mes
));

// Productos más pedidos
$productos_top = $wpdb->get_results(
    "SELECT producto_id, SUM(cantidad) as total_cantidad, SUM(cantidad * precio_unitario) as total_ventas
     FROM $tabla_pedidos
     WHERE estado = 'completado'
     GROUP BY producto_id
     ORDER BY total_cantidad DESC
     LIMIT 10"
);

// Pedidos recientes
$pedidos_recientes = $wpdb->get_results(
    "SELECT p.*, c.post_title as ciclo_titulo
     FROM $tabla_pedidos p
     LEFT JOIN {$wpdb->posts} c ON p.ciclo_id = c.ID
     ORDER BY p.fecha_pedido DESC
     LIMIT 10"
);

// Actividad por ciclo
$actividad_ciclos = $wpdb->get_results(
    "SELECT c.post_title as ciclo, COUNT(p.id) as total_pedidos, SUM(p.cantidad * p.precio_unitario) as total_ventas
     FROM {$wpdb->posts} c
     LEFT JOIN $tabla_pedidos p ON c.ID = p.ciclo_id
     WHERE c.post_type = 'gc_ciclo' AND c.post_status = 'publish'
     GROUP BY c.ID
     ORDER BY c.post_date DESC
     LIMIT 6"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-carrot"></span>
        <?php echo esc_html__('Dashboard - Grupos de Consumo', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas Principales -->
    <div class="gc-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_pedidos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Total Pedidos', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #dba617; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($pedidos_pendientes); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Pedidos Pendientes', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($pedidos_completados); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Pedidos Completados', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($ventas_mes, 2); ?> €
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Ventas Este Mes', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #00a0d2; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a0d2; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-products"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_productos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Productos Disponibles', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="gc-stat-card" style="background: #fff; border-left: 4px solid #d63638; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #d63638; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_productores); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Productores Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Estado del Ciclo Actual -->
    <?php if ($hay_ciclo_abierto): ?>
        <?php $ciclo_actual->the_post(); ?>
        <div class="notice notice-info" style="border-left-color: #2271b1; padding: 20px;">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-calendar-alt"></span>
                Ciclo Actual: <?php the_title(); ?>
            </h3>
            <p>
                <strong><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html__('Abierto |', 'flavor-chat-ia'); ?>
                <strong><?php echo esc_html__('Fecha Cierre:', 'flavor-chat-ia'); ?></strong> <?php echo get_post_meta(get_the_ID(), '_gc_fecha_cierre', true); ?>
            </p>
            <a href="<?php echo admin_url('post.php?post=' . get_the_ID() . '&action=edit'); ?>" class="button button-primary">
                <?php echo esc_html__('Gestionar Ciclo', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php echo esc_html__('No hay ningún ciclo abierto actualmente.', 'flavor-chat-ia'); ?></strong>
                <a href="<?php echo admin_url('post-new.php?post_type=gc_ciclo'); ?>" class="button">
                    <?php echo esc_html__('Crear Nuevo Ciclo', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Productos Más Pedidos -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html__('Productos Más Pedidos', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <canvas id="grafico-productos-top" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <!-- Ventas por Ciclo -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-line"></span> <?php echo esc_html__('Actividad por Ciclo', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <canvas id="grafico-ciclos" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Pedidos Recientes -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-update"></span> <?php echo esc_html__('Pedidos Recientes', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Producto', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Ciclo', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Precio Unit.', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos_recientes as $pedido):
                        $usuario = get_userdata($pedido->usuario_id);
                        $producto = get_post($pedido->producto_id);
                        $total_pedido = $pedido->cantidad * $pedido->precio_unitario;

                        $estado_config = [
                            'pendiente' => ['class' => 'warning', 'texto' => 'Pendiente'],
                            'confirmado' => ['class' => 'info', 'texto' => 'Confirmado'],
                            'completado' => ['class' => 'success', 'texto' => 'Completado'],
                            'cancelado' => ['class' => 'error', 'texto' => 'Cancelado']
                        ];
                        $estado = $estado_config[$pedido->estado] ?? ['class' => 'default', 'texto' => $pedido->estado];
                    ?>
                    <tr>
                        <td><strong>#<?php echo $pedido->id; ?></strong></td>
                        <td><?php echo $producto ? esc_html($producto->post_title) : 'Producto desconocido'; ?></td>
                        <td><?php echo $usuario ? esc_html($usuario->display_name) : 'Usuario desconocido'; ?></td>
                        <td><?php echo esc_html($pedido->ciclo_titulo ?: 'N/A'); ?></td>
                        <td><?php echo number_format($pedido->cantidad, 2); ?></td>
                        <td><?php echo number_format($pedido->precio_unitario, 2); ?> €</td>
                        <td><strong><?php echo number_format($total_pedido, 2); ?> €</strong></td>
                        <td>
                            <span class="badge-<?php echo $estado['class']; ?>"
                                  style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                <?php echo $estado['texto']; ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($pedido->fecha_pedido)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pedidos_recientes)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px; color: #646970;">
                            <?php echo esc_html__('No hay pedidos registrados', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.postbox h2 {
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.badge-warning { background-color: #dba617; color: #fff; }
.badge-info { background-color: #2271b1; color: #fff; }
.badge-success { background-color: #00a32a; color: #fff; }
.badge-error { background-color: #d63638; color: #fff; }
.badge-default { background-color: #646970; color: #fff; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(document).ready(function($) {

    // Gráfico Productos Top
    const ctxProductos = document.getElementById('grafico-productos-top').getContext('2d');
    new Chart(ctxProductos, {
        type: 'bar',
        data: {
            labels: [
                <?php
                foreach ($productos_top as $prod) {
                    $producto_post = get_post($prod->producto_id);
                    echo "'" . esc_js($producto_post ? $producto_post->post_title : 'Producto #' . $prod->producto_id) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Cantidad Vendida',
                data: [
                    <?php
                    foreach ($productos_top as $prod) {
                        echo $prod->total_cantidad . ',';
                    }
                    ?>
                ],
                backgroundColor: '#2271b1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico Ciclos
    const ctxCiclos = document.getElementById('grafico-ciclos').getContext('2d');
    new Chart(ctxCiclos, {
        type: 'line',
        data: {
            labels: [
                <?php
                foreach ($actividad_ciclos as $ciclo) {
                    echo "'" . esc_js($ciclo->ciclo) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Pedidos',
                data: [
                    <?php
                    foreach ($actividad_ciclos as $ciclo) {
                        echo $ciclo->total_pedidos . ',';
                    }
                    ?>
                ],
                borderColor: '#2271b1',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Ventas (€)',
                data: [
                    <?php
                    foreach ($actividad_ciclos as $ciclo) {
                        echo ($ciclo->total_ventas ?: 0) . ',';
                    }
                    ?>
                ],
                borderColor: '#00a32a',
                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

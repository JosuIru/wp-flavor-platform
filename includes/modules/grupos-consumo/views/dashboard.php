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

// Verificar si la tabla existe
$tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_pedidos)) === $tabla_pedidos;
$tablas_disponibles = $tabla_existe;

if ($tabla_existe) {
    $total_pedidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos");
    $pedidos_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos WHERE estado = 'pendiente'");
    $pedidos_completados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos WHERE estado = 'completado'");

    $primer_dia_mes = gmdate('Y-m-01 00:00:00');
    $ventas_mes = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(cantidad * precio_unitario), 0)
         FROM $tabla_pedidos
         WHERE estado = 'completado'
         AND fecha_pedido >= %s",
        $primer_dia_mes
    ));

    $productos_top = $wpdb->get_results(
        "SELECT producto_id, SUM(cantidad) as total_cantidad, SUM(cantidad * precio_unitario) as total_ventas
         FROM $tabla_pedidos
         WHERE estado = 'completado'
         GROUP BY producto_id
         ORDER BY total_cantidad DESC
         LIMIT 6"
    );

    $pedidos_recientes = $wpdb->get_results(
        "SELECT p.*, c.post_title as ciclo_titulo
         FROM $tabla_pedidos p
         LEFT JOIN {$wpdb->posts} c ON p.ciclo_id = c.ID
         ORDER BY p.fecha_pedido DESC
         LIMIT 6"
    );

    $actividad_ciclos = $wpdb->get_results(
        "SELECT c.post_title as ciclo, COUNT(p.id) as total_pedidos, SUM(p.cantidad * p.precio_unitario) as total_ventas
         FROM {$wpdb->posts} c
         LEFT JOIN $tabla_pedidos p ON c.ID = p.ciclo_id
         WHERE c.post_type = 'gc_ciclo' AND c.post_status = 'publish'
         GROUP BY c.ID
         ORDER BY c.post_date DESC
         LIMIT 6"
    );
} else {
    $total_pedidos = 0;
    $pedidos_pendientes = 0;
    $pedidos_completados = 0;
    $ventas_mes = 0;
    $productos_top = [];
    $pedidos_recientes = [];
    $actividad_ciclos = [];
}

// Productos y productores
$total_productos = (int) wp_count_posts('gc_producto')->publish;
$total_productores = (int) wp_count_posts('gc_productor')->publish;

// Ciclo actual
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

$estado_badges = [
    'pendiente' => 'dm-badge--warning',
    'confirmado' => 'dm-badge--info',
    'completado' => 'dm-badge--success',
    'cancelado' => 'dm-badge--error',
];

$estado_labels = [
    'pendiente' => __('Pendiente', 'flavor-platform'),
    'confirmado' => __('Confirmado', 'flavor-platform'),
    'completado' => __('Completado', 'flavor-platform'),
    'cancelado' => __('Cancelado', 'flavor-platform'),
];
?>

<div class="wrap dm-dashboard">
    <?php
    // Sección de ayuda colapsable
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('grupos_consumo');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', 'flavor-platform'); ?></strong>
        <?php esc_html_e('Falta la tabla del módulo Grupos de Consumo o aún no hay pedidos registrados.', 'flavor-platform'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-carrot"></span>
                <?php esc_html_e('Grupos de Consumo', 'flavor-platform'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Gestión de pedidos, productos y productores del grupo de consumo', 'flavor-platform'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <?php if (!$hay_ciclo_abierto): ?>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=gc_ciclo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo Ciclo', 'flavor-platform'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerta de ciclo -->
    <?php if ($hay_ciclo_abierto): ?>
        <?php $ciclo_actual->the_post(); ?>
        <div class="dm-alert dm-alert--success">
            <span class="dashicons dashicons-calendar-alt"></span>
            <div>
                <strong><?php esc_html_e('Ciclo activo:', 'flavor-platform'); ?> <?php the_title(); ?></strong>
                <div style="margin-top: 4px;">
                    <?php esc_html_e('Fecha cierre:', 'flavor-platform'); ?> <?php echo esc_html(get_post_meta(get_the_ID(), '_gc_fecha_cierre', true)); ?>
                    <a href="<?php echo esc_url(admin_url('post.php?post=' . get_the_ID() . '&action=edit')); ?>" class="dm-btn dm-btn--sm dm-btn--secondary" style="margin-left: 12px;">
                        <?php esc_html_e('Gestionar', 'flavor-platform'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <div class="dm-alert dm-alert--warning">
            <span class="dashicons dashicons-warning"></span>
            <strong><?php esc_html_e('No hay ningún ciclo abierto actualmente.', 'flavor-platform'); ?></strong>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=gc_ciclo')); ?>" class="dm-btn dm-btn--sm dm-btn--primary" style="margin-left: 12px;">
                <?php esc_html_e('Crear Ciclo', 'flavor-platform'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_pedidos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Pedidos', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($pedidos_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($pedidos_completados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Completados', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($ventas_mes, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Ventas del mes', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-carrot"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_productos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Productos', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-store"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_productores); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Productores', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <h2 class="dm-card__title">
            <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Accesos Rápidos', 'flavor-platform'); ?>
        </h2>
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=gc-pedidos')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-clipboard dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Pedidos', 'flavor-platform'); ?></span>
                <?php if ($pedidos_pendientes > 0): ?>
                    <span class="dm-badge dm-badge--warning"><?php echo $pedidos_pendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gc-consumidores')); ?>" class="dm-action-card dm-action-card--success">
                <span class="dashicons dashicons-admin-users dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Consumidores', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_ciclo')); ?>" class="dm-action-card dm-action-card--warning">
                <span class="dashicons dashicons-calendar-alt dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Ciclos', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_producto')); ?>" class="dm-action-card dm-action-card--pink">
                <span class="dashicons dashicons-carrot dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Productos', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_productor')); ?>" class="dm-action-card dm-action-card--purple">
                <span class="dashicons dashicons-store dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Productores', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gc-consolidado')); ?>" class="dm-action-card dm-action-card--info">
                <span class="dashicons dashicons-list-view dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Consolidado', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gc-solicitudes')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-businessperson dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Solicitudes', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gc-configuracion')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-admin-settings dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Configuración', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/')); ?>" class="dm-action-card" target="_blank">
                <span class="dashicons dashicons-external dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Portal público', 'flavor-platform'); ?></span>
            </a>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Productos Más Pedidos', 'flavor-platform'); ?>
            </h3>
            <div class="dm-chart-container">
                <canvas id="grafico-productos-top"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Actividad por Ciclo', 'flavor-platform'); ?>
            </h3>
            <div class="dm-chart-container">
                <canvas id="grafico-ciclos"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Productos top -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Productos Destacados', 'flavor-platform'); ?>
            </h3>
            <?php if (!empty($productos_top)): ?>
                <ol class="dm-ranking">
                    <?php foreach ($productos_top as $prod):
                        $nombre_producto = isset($prod->nombre) ? $prod->nombre : (($p = get_post($prod->producto_id)) ? $p->post_title : 'Producto #' . $prod->producto_id);
                    ?>
                        <li>
                            <span><?php echo esc_html($nombre_producto); ?></span>
                            <strong><?php echo number_format_i18n($prod->total_cantidad); ?> uds</strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin datos de productos.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Pedidos recientes -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-update"></span> <?php esc_html_e('Pedidos Recientes', 'flavor-platform'); ?>
            </h3>
            <?php if (!empty($pedidos_recientes)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Producto', 'flavor-platform'); ?></th>
                            <th><?php esc_html_e('Cantidad', 'flavor-platform'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pedidos_recientes, 0, 5) as $pedido):
                            $producto = get_post($pedido->producto_id);
                            $total_pedido = $pedido->cantidad * $pedido->precio_unitario;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($producto ? wp_trim_words($producto->post_title, 4) : 'Producto'); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo number_format_i18n($total_pedido, 2); ?> €</div>
                                </td>
                                <td><?php echo number_format_i18n($pedido->cantidad, 1); ?></td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_badges[$pedido->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html($estado_labels[$pedido->estado] ?? $pedido->estado); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-cart dm-empty__icon"></span>
                    <p><?php esc_html_e('No hay pedidos registrados.', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=gc-pedidos')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todos los pedidos', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="dm-card">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('Resumen del Grupo', 'flavor-platform'); ?>
        </h3>
        <div class="dm-focus-list">
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($total_pedidos); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('pedidos totales', 'flavor-platform'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($ventas_mes, 0); ?>€</span>
                <span class="dm-focus-item__label"><?php esc_html_e('facturado este mes', 'flavor-platform'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($total_productos); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('productos en catálogo', 'flavor-platform'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($total_productores); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('productores activos', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colores del tema
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--dm-primary').trim() || '#3b82f6';
    const successColor = getComputedStyle(document.documentElement).getPropertyValue('--dm-success').trim() || '#22c55e';

    // Gráfico Productos Top
    const ctxProductos = document.getElementById('grafico-productos-top');
    if (ctxProductos) {
        new Chart(ctxProductos, {
            type: 'bar',
            data: {
                labels: [<?php
                    foreach ($productos_top as $prod) {
                        $nombre = isset($prod->nombre) ? $prod->nombre : (($p = get_post($prod->producto_id)) ? $p->post_title : 'Producto');
                        echo "'" . esc_js(wp_trim_words($nombre, 3)) . "',";
                    }
                ?>],
                datasets: [{
                    label: '<?php esc_attr_e('Cantidad', 'flavor-platform'); ?>',
                    data: [<?php foreach ($productos_top as $prod) echo (int) $prod->total_cantidad . ','; ?>],
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Gráfico Ciclos
    const ctxCiclos = document.getElementById('grafico-ciclos');
    if (ctxCiclos) {
        new Chart(ctxCiclos, {
            type: 'line',
            data: {
                labels: [<?php foreach ($actividad_ciclos as $ciclo) echo "'" . esc_js($ciclo->ciclo) . "',"; ?>],
                datasets: [{
                    label: '<?php esc_attr_e('Pedidos', 'flavor-platform'); ?>',
                    data: [<?php foreach ($actividad_ciclos as $ciclo) echo (int) $ciclo->total_pedidos . ','; ?>],
                    borderColor: primaryColor,
                    backgroundColor: primaryColor + '20',
                    tension: 0.4,
                    fill: true
                }, {
                    label: '<?php esc_attr_e('Ventas (€)', 'flavor-platform'); ?>',
                    data: [<?php foreach ($actividad_ciclos as $ciclo) echo (float) ($ciclo->total_ventas ?: 0) . ','; ?>],
                    borderColor: successColor,
                    backgroundColor: successColor + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>

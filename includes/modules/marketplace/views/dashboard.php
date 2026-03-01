<?php
/**
 * Vista Dashboard - Marketplace
 *
 * Panel principal con estadísticas de ventas y anuncios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadísticas generales
$total_anuncios = wp_count_posts('marketplace_item');
$anuncios_activos = $total_anuncios->publish;
$anuncios_borrador = $total_anuncios->draft;
$anuncios_pendientes = $total_anuncios->pending;

// Estadísticas por tipo de transacción
$tipos_stats = [];
$tipos = get_terms(['taxonomy' => 'marketplace_tipo', 'hide_empty' => false]);

if (!is_wp_error($tipos)) {
    foreach ($tipos as $tipo) {
        $tipos_stats[] = [
            'nombre' => $tipo->name,
            'total' => $tipo->count
        ];
    }
}

// Anuncios por categoría
$categorias = get_terms(['taxonomy' => 'marketplace_categoria', 'hide_empty' => false]);
$categorias_stats = [];

if (!is_wp_error($categorias)) {
    foreach ($categorias as $cat) {
        if ($cat->count > 0) {
            $categorias_stats[] = [
                'nombre' => $cat->name,
                'total' => $cat->count
            ];
        }
    }
}

// Anuncios recientes
$args_recientes = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
];
$anuncios_recientes = new WP_Query($args_recientes);

// Usuarios más activos
global $wpdb;
$usuarios_activos = $wpdb->get_results(
    "SELECT post_author, COUNT(*) as total_anuncios
     FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item' AND post_status = 'publish'
     GROUP BY post_author
     ORDER BY total_anuncios DESC
     LIMIT 10"
);

// Anuncios por mes (últimos 6 meses)
$anuncios_mensuales = $wpdb->get_results(
    "SELECT DATE_FORMAT(post_date, '%Y-%m') as mes, COUNT(*) as total
     FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
     AND post_status = 'publish'
     AND post_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes
     ORDER BY mes ASC"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-store"></span>
        <?php echo esc_html__('Dashboard - Marketplace', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Accesos Rapidos -->
    <div class="marketplace-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('edit.php?post_type=marketplace_item'); ?>" class="marketplace-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-archive" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Productos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=marketplace-ventas'); ?>" class="marketplace-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-line" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Ventas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=marketplace-vendedores'); ?>" class="marketplace-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Vendedores', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item'); ?>" class="marketplace-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-category" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Categorias', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-app-composer&module=marketplace'); ?>" class="marketplace-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div class="marketplace-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="marketplace-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($anuncios_activos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Anuncios Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="marketplace-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #dba617; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($anuncios_pendientes); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Anuncios Pendientes', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="marketplace-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-products"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format(count($categorias_stats)); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Categorías Activas', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="marketplace-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format(count($usuarios_activos)); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                <?php echo esc_html__('Vendedores Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Anuncios por Tipo -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-tag"></span> <?php echo esc_html__('Anuncios por Tipo de Transacción', 'flavor-chat-ia'); ?></h2>
        <div class="inside">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; padding: 16px;">
                <?php foreach ($tipos_stats as $tipo): ?>
                    <div style="text-align: center; padding: 16px; background: #f6f7f7; border-radius: 4px;">
                        <div style="font-size: 28px; font-weight: bold; color: #2271b1;">
                            <?php echo $tipo['total']; ?>
                        </div>
                        <div style="color: #646970; font-size: 14px;">
                            <?php echo esc_html($tipo['nombre']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Anuncios por Categoría -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-pie"></span> <?php echo esc_html__('Anuncios por Categoría', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <canvas id="grafico-categorias" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <!-- Actividad Mensual -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-line"></span> <?php echo esc_html__('Anuncios por Mes', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <canvas id="grafico-mensual" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Anuncios Recientes -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-update"></span> <?php echo esc_html__('Anuncios Recientes', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Anuncio', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($anuncios_recientes->have_posts()): ?>
                            <?php while ($anuncios_recientes->have_posts()): $anuncios_recientes->the_post(); ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </td>
                                <td><?php the_author(); ?></td>
                                <td><?php echo get_the_date('d/m/Y'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px; color: #646970;">
                                    <?php echo esc_html__('No hay anuncios recientes', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Usuarios Más Activos -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__('Vendedores Más Activos', 'flavor-chat-ia'); ?></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="text-align: right;"><?php echo esc_html__('Anuncios', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $posicion = 1;
                        foreach ($usuarios_activos as $usuario):
                            $user_data = get_userdata($usuario->post_author);
                            if (!$user_data) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo $posicion++; ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $usuario->post_author); ?>">
                                    <?php echo esc_html($user_data->display_name); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo $usuario->total_anuncios; ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios_activos)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px; color: #646970;">
                                <?php echo esc_html__('No hay datos disponibles', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
.marketplace-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(document).ready(function($) {

    // Gráfico de Categorías
    const ctxCategorias = document.getElementById('grafico-categorias').getContext('2d');
    new Chart(ctxCategorias, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($categorias_stats as $cat): ?>
                    '<?php echo esc_js($cat['nombre']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($categorias_stats as $cat): ?>
                        <?php echo $cat['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#2271b1', '#00a32a', '#dba617', '#d63638', '#8c52ff',
                    '#00a0d2', '#b4a000', '#826eb4', '#c84851'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Gráfico Mensual
    const ctxMensual = document.getElementById('grafico-mensual').getContext('2d');
    new Chart(ctxMensual, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($anuncios_mensuales as $mes):
                    $fecha = DateTime::createFromFormat('Y-m', $mes->mes);
                    echo "'" . $fecha->format('M Y') . "',";
                endforeach; ?>
            ],
            datasets: [{
                label: 'Anuncios Publicados',
                data: [
                    <?php foreach ($anuncios_mensuales as $mes): ?>
                        <?php echo $mes->total; ?>,
                    <?php endforeach; ?>
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
});
</script>

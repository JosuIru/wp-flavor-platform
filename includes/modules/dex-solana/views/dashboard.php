<?php
/**
 * Dashboard de DEX Solana
 *
 * Panel administrativo para gestión del exchange descentralizado
 * con swaps, pools de liquidez y yield farming.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_swaps = $wpdb->prefix . 'flavor_dex_swaps';
$tabla_pools = $wpdb->prefix . 'flavor_dex_pools';
$tabla_farming = $wpdb->prefix . 'flavor_dex_farming';
$tabla_balances = $wpdb->prefix . 'flavor_dex_balances';

$tabla_swaps_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_swaps'") === $tabla_swaps;
$tabla_pools_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_pools'") === $tabla_pools;

// Configuración
$settings = get_option('flavor_module_dex_solana_settings', [
    'modo_activo' => 'paper',
]);
$modo_activo = $settings['modo_activo'] ?? 'paper';
$es_paper_mode = $modo_activo === 'paper';

// Estadísticas de swaps
$total_swaps = 0;
$swaps_24h = 0;
$volumen_24h = 0;
$usuarios_unicos = 0;

if ($tabla_swaps_existe) {
    $total_swaps = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_swaps");
    $swaps_24h = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_swaps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $volumen_24h = (float) $wpdb->get_var(
        "SELECT COALESCE(SUM(amount_in_usd), 0) FROM $tabla_swaps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $usuarios_unicos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $tabla_swaps");
}

// Estadísticas de pools
$total_pools = 0;
$tvl_total = 0;

if ($tabla_pools_existe) {
    $total_pools = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pools WHERE estado = 'activo'");
    $tvl_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(tvl), 0) FROM $tabla_pools WHERE estado = 'activo'");
}

// Top pares por volumen
$top_pares = [];
if ($tabla_swaps_existe) {
    $top_pares = $wpdb->get_results(
        "SELECT CONCAT(token_in, '/', token_out) as par, COUNT(*) as operaciones, SUM(amount_in_usd) as volumen
         FROM $tabla_swaps
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY token_in, token_out
         ORDER BY volumen DESC
         LIMIT 5"
    );
}

// Últimos swaps
$ultimos_swaps = [];
if ($tabla_swaps_existe) {
    $ultimos_swaps = $wpdb->get_results(
        "SELECT s.id, s.token_in, s.token_out, s.amount_in, s.amount_out, s.amount_in_usd, s.created_at, u.display_name
         FROM $tabla_swaps s
         LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
         ORDER BY s.created_at DESC
         LIMIT 8"
    );
}

// Evolución volumen semanal
$evolucion_volumen = [];
if ($tabla_swaps_existe) {
    $evolucion_volumen = $wpdb->get_results(
        "SELECT DATE(created_at) as dia, COUNT(*) as operaciones, COALESCE(SUM(amount_in_usd), 0) as volumen
         FROM $tabla_swaps
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY DATE(created_at)
         ORDER BY dia ASC"
    );
}
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('dex_solana'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-chart-line" style="color: #14f195;"></span>
            <h1><?php esc_html_e('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <?php if ($es_paper_mode): ?>
            <span class="dm-badge dm-badge--warning" style="margin-left: 10px;">
                <?php esc_html_e('Modo Paper', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <?php else: ?>
            <span class="dm-badge dm-badge--success" style="margin-left: 10px;">
                <?php esc_html_e('Modo Real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-config')); ?>" class="button">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-swap')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-randomize"></span>
                <span><?php esc_html_e('Swap', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-pools')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-chart-pie"></span>
                <span><?php esc_html_e('Pools', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-farming')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-carrot"></span>
                <span><?php esc_html_e('Farming', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-historial')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-backup"></span>
                <span><?php esc_html_e('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary" style="border-left-color: #14f195;">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-randomize"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($swaps_24h); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Swaps (24h)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta"><?php echo esc_html($total_swaps); ?> <?php esc_html_e('total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value">$<?php echo esc_html(number_format($volumen_24h, 2)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Volumen (24h)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value">$<?php echo esc_html(number_format($tvl_total, 2)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('TVL Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta"><?php echo esc_html($total_pools); ?> <?php esc_html_e('pools', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($usuarios_unicos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Traders', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <?php if ($es_paper_mode): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info-outline"></span>
        <div class="dm-alert__content">
            <strong><?php esc_html_e('Modo Paper Trading Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <p><?php esc_html_e('Las operaciones se realizan con balance simulado. Ideal para aprender sin riesgo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <!-- Top Pares -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Top Pares (7 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($top_pares)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Par', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Ops', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Volumen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_pares as $par): ?>
                        <tr>
                            <td><strong><?php echo esc_html($par->par); ?></strong></td>
                            <td><?php echo esc_html($par->operaciones); ?></td>
                            <td style="text-align: right; color: #14f195;">$<?php echo esc_html(number_format($par->volumen, 2)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin operaciones aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimos Swaps -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Últimos Swaps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=dex-solana-historial')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimos_swaps)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Swap', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('USD', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_swaps as $swap): ?>
                        <tr>
                            <td>
                                <span style="color: #ef4444;"><?php echo esc_html(number_format($swap->amount_in, 4)); ?> <?php echo esc_html($swap->token_in); ?></span>
                                <span class="dashicons dashicons-arrow-right-alt" style="font-size: 12px;"></span>
                                <span style="color: #10b981;"><?php echo esc_html(number_format($swap->amount_out, 4)); ?> <?php echo esc_html($swap->token_out); ?></span>
                            </td>
                            <td style="text-align: right;">$<?php echo esc_html(number_format($swap->amount_in_usd, 2)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin swaps recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico de Volumen -->
    <?php if (!empty($evolucion_volumen)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Volumen Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <canvas id="chart-dex-volumen" height="100"></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = initDexChart;
            document.head.appendChild(script);
        } else {
            initDexChart();
        }

        function initDexChart() {
            var ctx = document.getElementById('chart-dex-volumen');
            if (!ctx) return;

            var datos = <?php echo wp_json_encode(array_map(function($row) {
                return [
                    'dia' => date_i18n('D', strtotime($row->dia)),
                    'volumen' => (float) $row->volumen,
                    'operaciones' => (int) $row->operaciones
                ];
            }, $evolucion_volumen)); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.map(function(d) { return d.dia; }),
                    datasets: [{
                        label: '<?php esc_html_e("Volumen USD", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                        data: datos.map(function(d) { return d.volumen; }),
                        backgroundColor: 'rgba(20, 241, 149, 0.8)',
                        borderColor: '#14f195',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function(v) { return '$' + v.toLocaleString(); } }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <!-- Info Solana -->
    <div class="dm-card" style="background: linear-gradient(135deg, #9945FF 0%, #14f195 100%); color: white;">
        <div class="dm-card__body" style="text-align: center;">
            <span style="font-size: 24px; font-weight: bold;">◎ Solana</span>
            <p style="margin: 10px 0 0; opacity: 0.9;">
                <?php esc_html_e('Exchange descentralizado con swaps via Jupiter, pools AMM y yield farming.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>
</div>

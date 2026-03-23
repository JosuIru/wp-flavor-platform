<?php
/**
 * Vista del Dashboard de Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';
$tabla_bicicletas_alt = $wpdb->prefix . 'flavor_bicicletas';
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
$tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

// Verificar si las tablas existen
$tabla_bicicletas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_bicicletas)) === $tabla_bicicletas;
$tabla_bicicletas_alt_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_bicicletas_alt)) === $tabla_bicicletas_alt;

if (!$tabla_bicicletas_existe && $tabla_bicicletas_alt_existe) {
    $tabla_bicicletas = $tabla_bicicletas_alt;
    $tabla_bicicletas_existe = true;
}

if ($tabla_bicicletas_existe) {
    $fecha_inicio_mes = date('Y-m-01 00:00:00');

    $total_bicicletas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas}");
    $bicicletas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'disponible'");
    $bicicletas_en_uso = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'en_uso'");
    $bicicletas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'mantenimiento'");

    $tabla_estaciones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_estaciones)) === $tabla_estaciones;
    $total_estaciones = $tabla_estaciones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estaciones}") : 0;
    $estaciones_activas = $tabla_estaciones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estaciones} WHERE estado = 'activa'") : 0;

    $tabla_prestamos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_prestamos)) === $tabla_prestamos;
    $usos_mes = $tabla_prestamos_existe ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE fecha_inicio >= %s",
        $fecha_inicio_mes
    )) : 0;
    $usos_activos = $tabla_prestamos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'activo'") : 0;

    $stats_distancia = $tabla_prestamos_existe ? $wpdb->get_row($wpdb->prepare(
        "SELECT COALESCE(SUM(kilometros_recorridos), 0) as distancia_total, COALESCE(AVG(duracion_minutos), 0) as duracion_promedio
        FROM {$tabla_prestamos} WHERE estado = 'completado' AND fecha_inicio >= %s",
        $fecha_inicio_mes
    )) : null;

    $distancia_total_mes = $stats_distancia->distancia_total ?? 0;
    $duracion_promedio = $stats_distancia->duracion_promedio ?? 0;
    $co2_ahorrado_kg = $distancia_total_mes * 0.12;

    $top_estaciones = $tabla_estaciones_existe && $tabla_prestamos_existe ? $wpdb->get_results(
        "SELECT e.id, e.nombre, e.direccion, COUNT(p.id) as total_usos,
            (SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estacion_actual_id = e.id) as bicicletas_actuales
        FROM {$tabla_estaciones} e
        LEFT JOIN {$tabla_prestamos} p ON (e.id = p.estacion_salida_id OR e.id = p.estacion_llegada_id)
        WHERE e.estado = 'activa'
        GROUP BY e.id ORDER BY total_usos DESC LIMIT 5"
    ) : [];

    $datos_grafica = $tabla_prestamos_existe ? $wpdb->get_results(
        "SELECT DATE(fecha_inicio) as fecha, COUNT(*) as usos, SUM(kilometros_recorridos) as distancia
        FROM {$tabla_prestamos}
        WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_inicio) ORDER BY fecha ASC"
    ) : [];

} else {
    $total_bicicletas = 0;
    $bicicletas_disponibles = 0;
    $bicicletas_en_uso = 0;
    $bicicletas_mantenimiento = 0;
    $total_estaciones = 0;
    $estaciones_activas = 0;
    $usos_mes = 0;
    $usos_activos = 0;
    $duracion_promedio = 0;
    $distancia_total_mes = 0;
    $co2_ahorrado_kg = 0;
    $top_estaciones = [];
    $datos_grafica = [];
}

$fechas_grafica = [];
$usos_grafica = [];
$distancia_grafica = [];

foreach ($datos_grafica as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $usos_grafica[] = (int) $dato->usos;
    $distancia_grafica[] = (float) ($dato->distancia ?? 0);
}

$porcentaje_disponibles = $total_bicicletas > 0 ? round(($bicicletas_disponibles / $total_bicicletas) * 100, 1) : 0;
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('bicicletas_compartidas');
    }
    ?>

    <?php if (!$tabla_bicicletas_existe): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('No hay tablas de bicicletas disponibles todavía.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-location-alt"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Bicicletas Compartidas', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Gestiona la flota de bicicletas y estaciones', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-nueva')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva Bicicleta', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-quick-links">
        <h2 class="dm-quick-links__title">
            <span class="dashicons dashicons-admin-links"></span>
            <?php esc_html_e('Accesos Rápidos', 'flavor-chat-ia'); ?>
        </h2>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-flota')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-location-alt"></span>
                <span><?php esc_html_e('Flota', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-location"></span>
                <span><?php esc_html_e('Estaciones', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-prestamos')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-update"></span>
                <span><?php esc_html_e('Préstamos', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-configuracion')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/bicicletas-compartidas/')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Portal', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--6">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-location-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_bicicletas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Bicicletas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($bicicletas_disponibles); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php echo $porcentaje_disponibles; ?>%</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <span class="dashicons dashicons-update dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($bicicletas_en_uso); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('En Uso', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Ahora mismo', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <span class="dashicons dashicons-location dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($estaciones_activas); ?>/<?php echo number_format_i18n($total_estaciones); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Estaciones', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Activas / Total', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--eco">
            <span class="dashicons dashicons-palmtree dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($co2_ahorrado_kg, 1); ?> kg</div>
                <div class="dm-stat-card__label"><?php esc_html_e('CO₂ Ahorrado', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-chart-bar dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($usos_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Usos del Mes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php echo number_format_i18n($duracion_promedio, 0); ?> min prom.</div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="dm-grid dm-grid--2-1">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Actividad - Últimos 30 días', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="flavor-bicicletas-chart"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Estado de la Flota', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="flavor-bicicletas-pie-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Estaciones -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-location"></span>
                <?php esc_html_e('Top 5 Estaciones por Uso', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Estación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Usos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Bicicletas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_estaciones)): ?>
                    <?php foreach ($top_estaciones as $estacion): ?>
                    <tr>
                        <td><strong><?php echo esc_html($estacion->nombre); ?></strong></td>
                        <td class="dm-text-muted dm-text-sm"><?php echo esc_html($estacion->direccion); ?></td>
                        <td>
                            <span class="dm-badge dm-badge--sm dm-badge--primary">
                                <?php echo number_format_i18n($estacion->total_usos); ?>
                            </span>
                        </td>
                        <td>
                            <span class="dm-badge dm-badge--sm dm-badge--success">
                                <?php echo number_format_i18n($estacion->bicicletas_actuales); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=ver&estacion_id=' . $estacion->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="dm-table__empty">
                            <span class="dashicons dashicons-location"></span>
                            <?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Acciones rápidas -->
    <div class="dm-action-grid dm-action-grid--4">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="dm-action-card dm-action-card--primary">
            <span class="dashicons dashicons-location"></span>
            <span><?php esc_html_e('Gestionar Estaciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-location-alt"></span>
            <span><?php esc_html_e('Gestionar Bicicletas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-uso')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-chart-bar"></span>
            <span><?php esc_html_e('Ver Estadísticas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento')); ?>" class="dm-action-card dm-action-card--warning">
            <span class="dashicons dashicons-admin-tools"></span>
            <span><?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?></span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';

    var fechas = <?php echo wp_json_encode($fechas_grafica); ?>;
    var usos = <?php echo wp_json_encode($usos_grafica); ?>;
    var distancia = <?php echo wp_json_encode($distancia_grafica); ?>;

    var ctx1 = document.getElementById('flavor-bicicletas-chart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php echo esc_js(__('Usos', 'flavor-chat-ia')); ?>',
                        data: usos,
                        borderColor: primaryColor,
                        backgroundColor: primaryColor + '1A',
                        yAxisID: 'y',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '<?php echo esc_js(__('Distancia (km)', 'flavor-chat-ia')); ?>',
                        data: distancia,
                        borderColor: successColor,
                        backgroundColor: successColor + '1A',
                        yAxisID: 'y1',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', beginAtZero: true },
                    y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    var ctx2 = document.getElementById('flavor-bicicletas-pie-chart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['<?php echo esc_js(__('Disponibles', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('En Uso', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Mantenimiento', 'flavor-chat-ia')); ?>'],
                datasets: [{
                    data: [<?php echo (int) $bicicletas_disponibles; ?>, <?php echo (int) $bicicletas_en_uso; ?>, <?php echo (int) $bicicletas_mantenimiento; ?>],
                    backgroundColor: [successColor, primaryColor, warningColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>

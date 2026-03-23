<?php
/**
 * Vista del Dashboard de Carpooling
 *
 * @package FlavorChatIA
 * @subpackage Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
$tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
$tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';

// Verificar si las tablas existen
$tabla_viajes_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_viajes)) === $tabla_viajes;
$tabla_reservas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reservas)) === $tabla_reservas;

$viajes_activos = 0;
$viajes_completados_mes = 0;
$reservas_pendientes = 0;
$reservas_confirmadas_mes = 0;
$conductores_activos = 0;
$conductores_pendientes_verificacion = 0;
$usuarios_participantes = 0;
$plazas_compartidas = 0;
$co2_ahorrado_kg = 0.0;
$rutas_populares = [];
$top_conductores = [];
$datos_grafica_viajes = [];
$tablas_disponibles = $tabla_viajes_existe;

if ($tabla_viajes_existe) {
    $fecha_inicio_mes = date('Y-m-01 00:00:00');
    $fecha_actual = current_time('mysql');

    $viajes_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_viajes} WHERE estado = 'activo' AND fecha_salida >= NOW()");
    $viajes_completados_mes = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_viajes} WHERE estado = 'finalizado' AND fecha_salida >= %s AND fecha_salida <= %s",
        $fecha_inicio_mes,
        $fecha_actual
    ));

    $reservas_pendientes = $tabla_reservas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'") : 0;
    $reservas_confirmadas_mes = $tabla_reservas_existe ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'confirmada' AND fecha_reserva >= %s",
        $fecha_inicio_mes
    )) : 0;

    $conductores_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT conductor_id) FROM {$tabla_viajes} WHERE estado = 'activo'");
    $conductores_pendientes_verificacion = 0;

    $usuarios_participantes = $tabla_reservas_existe ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT r.pasajero_id) FROM {$tabla_reservas} r WHERE r.fecha_reserva >= %s",
        $fecha_inicio_mes
    )) : 0;

    $plazas_compartidas = $tabla_reservas_existe ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(r.numero_plazas), 0)
        FROM {$tabla_viajes} v
        INNER JOIN {$tabla_reservas} r ON v.id = r.viaje_id
        WHERE v.estado = 'finalizado' AND r.estado = 'completada' AND v.fecha_salida >= %s AND v.fecha_salida <= %s",
        $fecha_inicio_mes,
        $fecha_actual
    )) : 0;

    $co2_ahorrado_kg = ($plazas_compartidas * 20 * 0.12);

    $rutas_populares = $wpdb->get_results(
        "SELECT origen, destino, COUNT(*) as total_viajes, SUM(plazas_disponibles) as total_plazas
        FROM {$tabla_viajes}
        WHERE fecha_salida >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY origen, destino
        ORDER BY total_viajes DESC
        LIMIT 5"
    ) ?: [];

    $top_conductores = $wpdb->get_results(
        "SELECT v.conductor_id as id, u.display_name,
            COUNT(v.id) as total_viajes,
            SUM(CASE WHEN v.estado = 'finalizado' THEN 1 ELSE 0 END) as viajes_completados
        FROM {$tabla_viajes} v
        INNER JOIN {$wpdb->users} u ON v.conductor_id = u.ID
        GROUP BY v.conductor_id
        ORDER BY viajes_completados DESC
        LIMIT 5"
    ) ?: [];

    $datos_grafica_viajes = $wpdb->get_results(
        "SELECT DATE(fecha_salida) as fecha, COUNT(*) as total_viajes,
            SUM(plazas_disponibles) as plazas_ofertadas,
            SUM(plazas_disponibles - plazas_ocupadas) as plazas_disponibles
        FROM {$tabla_viajes}
        WHERE fecha_salida >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_salida)
        ORDER BY fecha ASC"
    ) ?: [];
}

$fechas_grafica = [];
$viajes_grafica = [];
$plazas_grafica = [];

foreach ($datos_grafica_viajes as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $viajes_grafica[] = (int) $dato->total_viajes;
    $plazas_grafica[] = (int) $dato->plazas_disponibles;
}
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('carpooling');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Carpooling o aún no hay viajes registrados.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-car"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Carpooling', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Comparte coche y reduce emisiones', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(home_url('/mi-portal/carpooling/publicar/')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Publicar Viaje', 'flavor-chat-ia'); ?>
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=carpooling-viajes')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-car"></span>
                <span><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=carpooling-reservas')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-tickets-alt"></span>
                <span><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=carpooling-config')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/carpooling/')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Portal', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--6">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-car dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($viajes_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Viajes Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($viajes_completados_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas Pendientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <span class="dashicons dashicons-id dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($conductores_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Conductores', 'flavor-chat-ia'); ?></div>
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

        <div class="dm-stat-card dm-stat-card--info">
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($usuarios_participantes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráfica y Rutas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Actividad de Viajes - Últimos 30 días', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="flavor-carpooling-chart"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php esc_html_e('Rutas Más Populares', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ruta', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Plazas', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rutas_populares)): ?>
                        <?php foreach ($rutas_populares as $ruta): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($ruta->origen); ?></strong>
                                <span class="dm-text-muted"> → </span>
                                <strong><?php echo esc_html($ruta->destino); ?></strong>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--sm dm-badge--primary">
                                    <?php echo number_format_i18n($ruta->total_viajes); ?>
                                </span>
                            </td>
                            <td class="dm-text-muted">
                                <?php echo number_format_i18n($ruta->total_plazas); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="dm-table__empty">
                                <span class="dashicons dashicons-location-alt"></span>
                                <?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Conductores -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Top 5 Conductores', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Conductor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Viajes', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_conductores)): ?>
                    <?php foreach ($top_conductores as $index => $conductor): ?>
                    <tr>
                        <td>
                            <span class="dm-badge dm-badge--sm <?php echo $index === 0 ? 'dm-badge--warning' : ($index === 1 ? 'dm-badge--secondary' : ($index === 2 ? 'dm-badge--info' : '')); ?>">
                                #<?php echo $index + 1; ?>
                            </span>
                            <strong><?php echo esc_html($conductor->display_name); ?></strong>
                        </td>
                        <td><?php echo number_format_i18n($conductor->total_viajes); ?></td>
                        <td>
                            <span class="dm-badge dm-badge--sm dm-badge--success">
                                <?php echo number_format_i18n($conductor->viajes_completados); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&conductor_id=' . $conductor->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                                <?php esc_html_e('Ver Perfil', 'flavor-chat-ia'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="dm-table__empty">
                            <span class="dashicons dashicons-id"></span>
                            <?php esc_html_e('No hay conductores disponibles', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Acciones rápidas -->
    <div class="dm-action-grid dm-action-grid--3">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes')); ?>" class="dm-action-card dm-action-card--primary">
            <span class="dashicons dashicons-car"></span>
            <span><?php esc_html_e('Gestionar Viajes', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-tickets-alt"></span>
            <span><?php esc_html_e('Ver Reservas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Gestionar Conductores', 'flavor-chat-ia'); ?></span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';

    var fechas = <?php echo wp_json_encode($fechas_grafica); ?>;
    var viajes = <?php echo wp_json_encode($viajes_grafica); ?>;
    var plazas = <?php echo wp_json_encode($plazas_grafica); ?>;

    var ctx = document.getElementById('flavor-carpooling-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php echo esc_js(__('Viajes Publicados', 'flavor-chat-ia')); ?>',
                        data: viajes,
                        borderColor: primaryColor,
                        backgroundColor: primaryColor + '1A',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '<?php echo esc_js(__('Plazas Disponibles', 'flavor-chat-ia')); ?>',
                        data: plazas,
                        borderColor: successColor,
                        backgroundColor: successColor + '1A',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>

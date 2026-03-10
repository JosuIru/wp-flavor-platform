<?php
/**
 * Vista del Dashboard de Parkings
 *
 * @package FlavorChatIA
 * @subpackage Parkings
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// El dashboard primario puede abrirse por administradores o por el rol gestor habilitado.
if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

// Obtener estadísticas
global $wpdb;
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_propietarios = $wpdb->prefix . 'flavor_parkings_propietarios';

// Verificar si las tablas existen
$tabla_plazas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_plazas)) === $tabla_plazas;
$tabla_reservas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reservas)) === $tabla_reservas;
$tabla_propietarios_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_propietarios)) === $tabla_propietarios;

$fecha_inicio_mes = date('Y-m-01 00:00:00');
$fecha_actual = current_time('mysql');

// Valores por defecto
$total_plazas = 0;
$plazas_disponibles = 0;
$plazas_ocupadas = 0;
$plazas_mantenimiento = 0;
$reservas_activas = 0;
$reservas_mes = 0;
$ingresos_mes = 0;
$propietarios_activos = 0;
$tasa_ocupacion = 0;
$plazas_por_zona = [];
$top_propietarios = [];
$datos_grafica = [];
$usando_demo = false;

if ($tabla_plazas_existe && $tabla_reservas_existe && $tabla_propietarios_existe) {
    // Plazas
    $total_plazas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas}");
    $plazas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'disponible'");
    $plazas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'ocupada'");
    $plazas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'mantenimiento'");

    // Reservas
    $reservas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'activa' AND fecha_fin >= NOW()");
    $reservas_mes = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_inicio >= %s",
        $fecha_inicio_mes
    ));

    // Ingresos
    $ingresos_mes = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(precio_total) FROM {$tabla_reservas} WHERE estado IN ('activa', 'completada') AND fecha_inicio >= %s",
        $fecha_inicio_mes
    )) ?? 0;

    // Propietarios
    $propietarios_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_propietarios} WHERE estado = 'activo'");

    // Tasa de ocupación
    $tasa_ocupacion = $total_plazas > 0 ? ($plazas_ocupadas / $total_plazas) * 100 : 0;

    // Plazas por zona
    $plazas_por_zona = $wpdb->get_results(
        "SELECT
            zona,
            COUNT(*) as total_plazas,
            SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
            SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas
        FROM {$tabla_plazas}
        GROUP BY zona
        ORDER BY total_plazas DESC
        LIMIT 10"
    );

    // Top propietarios
    $top_propietarios = $wpdb->get_results(
        "SELECT
            p.id,
            u.display_name,
            COUNT(pl.id) as total_plazas,
            SUM(CASE WHEN pl.estado = 'ocupada' THEN 1 ELSE 0 END) as plazas_ocupadas,
            COUNT(r.id) as total_reservas,
            COALESCE(SUM(r.precio_total), 0) as ingresos_totales
        FROM {$tabla_propietarios} p
        INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
        LEFT JOIN {$tabla_reservas} r ON pl.id = r.plaza_id AND r.fecha_inicio >= '{$fecha_inicio_mes}'
        WHERE p.estado = 'activo'
        GROUP BY p.id
        ORDER BY ingresos_totales DESC
        LIMIT 5"
    );

    // Datos para gráfica (últimos 30 días)
    $datos_grafica = $wpdb->get_results(
        "SELECT
            DATE(fecha_inicio) as fecha,
            COUNT(*) as reservas,
            SUM(precio_total) as ingresos
        FROM {$tabla_reservas}
        WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_inicio)
        ORDER BY fecha ASC"
    );
}

// Usar datos demo si no hay datos reales
if ($total_plazas == 0) {
    $usando_demo = true;
    $total_plazas = 48;
    $plazas_disponibles = 18;
    $plazas_ocupadas = 26;
    $plazas_mantenimiento = 4;
    $reservas_activas = 23;
    $reservas_mes = 87;
    $ingresos_mes = 2340.50;
    $propietarios_activos = 12;
    $tasa_ocupacion = 54.2;

    $plazas_por_zona = [
        (object) ['zona' => 'Zona A - Centro', 'total_plazas' => 16, 'disponibles' => 4, 'ocupadas' => 10],
        (object) ['zona' => 'Zona B - Norte', 'total_plazas' => 12, 'disponibles' => 5, 'ocupadas' => 6],
        (object) ['zona' => 'Zona C - Sur', 'total_plazas' => 10, 'disponibles' => 4, 'ocupadas' => 5],
        (object) ['zona' => 'Zona D - Este', 'total_plazas' => 10, 'disponibles' => 5, 'ocupadas' => 5],
    ];

    $top_propietarios = [
        (object) ['id' => 1, 'display_name' => 'María García', 'total_plazas' => 8, 'plazas_ocupadas' => 6, 'total_reservas' => 24, 'ingresos_totales' => 720.00],
        (object) ['id' => 2, 'display_name' => 'Carlos López', 'total_plazas' => 6, 'plazas_ocupadas' => 4, 'total_reservas' => 18, 'ingresos_totales' => 540.00],
        (object) ['id' => 3, 'display_name' => 'Ana Martínez', 'total_plazas' => 5, 'plazas_ocupadas' => 4, 'total_reservas' => 15, 'ingresos_totales' => 450.00],
        (object) ['id' => 4, 'display_name' => 'Pedro Sánchez', 'total_plazas' => 4, 'plazas_ocupadas' => 3, 'total_reservas' => 12, 'ingresos_totales' => 360.00],
        (object) ['id' => 5, 'display_name' => 'Laura Fernández', 'total_plazas' => 3, 'plazas_ocupadas' => 2, 'total_reservas' => 9, 'ingresos_totales' => 270.00],
    ];

    // Demo: datos de últimos 14 días
    $datos_grafica = [];
    for ($i = 13; $i >= 0; $i--) {
        $datos_grafica[] = (object) [
            'fecha' => date('Y-m-d', strtotime("-{$i} days")),
            'reservas' => rand(4, 12),
            'ingresos' => rand(80, 200)
        ];
    }
}

$fechas_grafica = [];
$reservas_grafica = [];
$ingresos_grafica = [];

foreach ($datos_grafica as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $reservas_grafica[] = (int) $dato->reservas;
    $ingresos_grafica[] = (float) $dato->ingresos;
}
?>

<div class="dm-dashboard">
    <!-- Header -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-car" style="font-size: 28px;"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Parkings', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Gestión de plazas de aparcamiento compartido', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&action=nueva')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Plaza', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <?php if ($usando_demo) : ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('Mostrando datos de demostración. Los datos reales aparecerán cuando se registren plazas y reservas.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="dm-quick-links">
        <h3 class="dm-quick-links__title"><?php esc_html_e('Acceso Rápido', 'flavor-chat-ia'); ?></h3>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas')); ?>" class="dm-quick-links__item dm-quick-links__item--primary">
                <span class="dashicons dashicons-location"></span>
                <?php esc_html_e('Gestionar Plazas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-reservas')); ?>" class="dm-quick-links__item dm-quick-links__item--info">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Ver Reservas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Propietarios', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-calendario')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-calendar"></span>
                <?php esc_html_e('Calendario', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/parkings/')); ?>" class="dm-quick-links__item" target="_blank">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Portal público', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--6">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-location"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_plazas)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Plazas', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('En el sistema', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($plazas_disponibles)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php echo esc_html(number_format_i18n(($total_plazas > 0 ? ($plazas_disponibles / $total_plazas) * 100 : 0), 1)); ?>% del total</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($tasa_ocupacion, 1)); ?>%</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Tasa de Ocupación', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php echo esc_html($plazas_ocupadas); ?> / <?php echo esc_html($total_plazas); ?> <?php esc_html_e('ocupadas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($reservas_activas)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas Activas', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('En curso ahora', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value">€<?php echo esc_html(number_format_i18n($ingresos_mes, 2)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Ingresos del Mes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php echo esc_html($reservas_mes); ?> <?php esc_html_e('reservas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($propietarios_activos)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Propietarios', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="dm-grid dm-grid--2-1">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Actividad - Últimos 30 días', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="flavor-parkings-chart"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Estado de Plazas', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="flavor-parkings-pie-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Distribución por Zona -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Distribución por Zona', 'flavor-chat-ia'); ?></h3>
        </div>
        <?php if (!empty($plazas_por_zona)) : ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Zona', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('% Ocupación', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plazas_por_zona as $zona) :
                    $ocupacion_zona = $zona->total_plazas > 0 ? ($zona->ocupadas / $zona->total_plazas) * 100 : 0;
                    $progress_class = $ocupacion_zona > 80 ? 'dm-progress--error' : ($ocupacion_zona > 50 ? 'dm-progress--warning' : 'dm-progress--success');
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($zona->zona); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($zona->total_plazas)); ?></td>
                        <td class="dm-text-success"><strong><?php echo esc_html(number_format_i18n($zona->disponibles)); ?></strong></td>
                        <td class="dm-text-error"><strong><?php echo esc_html(number_format_i18n($zona->ocupadas)); ?></strong></td>
                        <td>
                            <div class="dm-progress-inline">
                                <div class="dm-progress dm-progress--sm <?php echo esc_attr($progress_class); ?>">
                                    <div class="dm-progress__fill" style="width: <?php echo esc_attr($ocupacion_zona); ?>%;"></div>
                                </div>
                                <span class="dm-progress-inline__value"><?php echo esc_html(number_format_i18n($ocupacion_zona, 1)); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-location"></span>
            <p><?php esc_html_e('No hay datos de zonas disponibles.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Propietarios -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Top 5 Propietarios por Ingresos', 'flavor-chat-ia'); ?></h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios')); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php if (!empty($top_propietarios)) : ?>
        <table class="dm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php esc_html_e('Propietario', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Plazas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_propietarios as $index => $propietario) : ?>
                    <tr>
                        <td>
                            <span class="dm-badge dm-badge--<?php echo $index === 0 ? 'warning' : 'secondary'; ?>">
                                <?php echo esc_html($index + 1); ?>
                            </span>
                        </td>
                        <td><strong><?php echo esc_html($propietario->display_name); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($propietario->total_plazas)); ?></td>
                        <td><?php echo esc_html(number_format_i18n($propietario->plazas_ocupadas)); ?></td>
                        <td><?php echo esc_html(number_format_i18n($propietario->total_reservas)); ?></td>
                        <td class="dm-text-success"><strong>€<?php echo esc_html(number_format_i18n($propietario->ingresos_totales, 2)); ?></strong></td>
                        <td>
                            <?php if (!$usando_demo) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios&propietario_id=' . $propietario->id)); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                                <?php esc_html_e('Ver Perfil', 'flavor-chat-ia'); ?>
                            </a>
                            <?php else : ?>
                            <span class="dm-text-muted"><?php esc_html_e('Demo', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-businessman"></span>
            <p><?php esc_html_e('No hay propietarios registrados.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    var errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';

    // Gráfica de líneas - Actividad
    var fechas = <?php echo wp_json_encode($fechas_grafica); ?>;
    var reservas = <?php echo wp_json_encode($reservas_grafica); ?>;
    var ingresos = <?php echo wp_json_encode($ingresos_grafica); ?>;

    var ctx1 = document.getElementById('flavor-parkings-chart');
    if (ctx1 && typeof Chart !== 'undefined') {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php echo esc_js(__('Reservas', 'flavor-chat-ia')); ?>',
                        data: reservas,
                        borderColor: primaryColor,
                        backgroundColor: primaryColor + '1a',
                        yAxisID: 'y',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '<?php echo esc_js(__('Ingresos (€)', 'flavor-chat-ia')); ?>',
                        data: ingresos,
                        borderColor: successColor,
                        backgroundColor: successColor + '1a',
                        yAxisID: 'y1',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Gráfica de pastel - Estado de Plazas
    var ctx2 = document.getElementById('flavor-parkings-pie-chart');
    if (ctx2 && typeof Chart !== 'undefined') {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [
                    '<?php echo esc_js(__('Disponibles', 'flavor-chat-ia')); ?>',
                    '<?php echo esc_js(__('Ocupadas', 'flavor-chat-ia')); ?>',
                    '<?php echo esc_js(__('Mantenimiento', 'flavor-chat-ia')); ?>'
                ],
                datasets: [{
                    data: [
                        <?php echo esc_js($plazas_disponibles); ?>,
                        <?php echo esc_js($plazas_ocupadas); ?>,
                        <?php echo esc_js($plazas_mantenimiento); ?>
                    ],
                    backgroundColor: [successColor, errorColor, warningColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>

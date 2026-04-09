<?php
/**
 * Vista Dashboard - Clientes (CRM)
 *
 * Dashboard administrativo operativo para gestión de clientes.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_clientes = $wpdb->prefix . 'flavor_clientes';
$tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

$tabla_clientes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_clientes)) === $tabla_clientes;
$tabla_notas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_notas)) === $tabla_notas;

if (!$tabla_clientes_existe) {
    echo '<div class="dm-alert dm-alert--warning">' . esc_html__('La tabla principal de clientes no está disponible. Activa el módulo para crearla.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    return;
}

// Estadísticas generales
$total_clientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_clientes}");
$clientes_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_clientes} WHERE estado = 'activo'");
$clientes_potenciales = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_clientes} WHERE estado = 'potencial'");
$clientes_inactivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_clientes} WHERE estado = 'inactivo'");
$clientes_perdidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_clientes} WHERE estado = 'perdido'");

// Nuevos este mes
$inicio_mes = gmdate('Y-m-01 00:00:00', current_time('timestamp', true));
$nuevos_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_clientes} WHERE fecha_alta >= %s",
    $inicio_mes
));

// Por tipo
$por_tipo = $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM {$tabla_clientes}
     WHERE estado = 'activo'
     GROUP BY tipo
     ORDER BY total DESC"
);

// Por origen
$por_origen = $wpdb->get_results(
    "SELECT origen, COUNT(*) as total
     FROM {$tabla_clientes}
     GROUP BY origen
     ORDER BY total DESC
     LIMIT 6"
);

// Clientes recientes
$clientes_recientes = $wpdb->get_results(
    "SELECT c.id, c.nombre, c.empresa, c.tipo, c.estado, c.fecha_alta, u.display_name as creado_por_nombre
     FROM {$tabla_clientes} c
     LEFT JOIN {$wpdb->users} u ON u.ID = c.creado_por
     ORDER BY c.fecha_alta DESC
     LIMIT 8"
);

// Clientes potenciales prioritarios
$potenciales_prioritarios = $wpdb->get_results(
    "SELECT id, nombre, empresa, email, estado, fecha_alta
     FROM {$tabla_clientes}
     WHERE estado = 'potencial'
     ORDER BY fecha_alta DESC
     LIMIT 6"
);

// Actividad de notas recientes
$notas_recientes = [];
if ($tabla_notas_existe) {
    $notas_recientes = $wpdb->get_results(
        "SELECT n.*, c.nombre as cliente_nombre, c.empresa as cliente_empresa, u.display_name as autor_nombre
         FROM {$tabla_notas} n
         LEFT JOIN {$tabla_clientes} c ON c.id = n.cliente_id
         LEFT JOIN {$wpdb->users} u ON u.ID = n.usuario_id
         ORDER BY n.fecha DESC
         LIMIT 8"
    );
}

// Evolución mensual
$mensual_clientes = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_alta, '%Y-%m') as periodo, COUNT(*) as total
     FROM {$tabla_clientes}
     WHERE fecha_alta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha_alta, '%Y-%m')
     ORDER BY periodo ASC"
);

$meses_labels = [];
$meses_data = [];
foreach ($mensual_clientes as $mes) {
    $fecha = DateTime::createFromFormat('Y-m', $mes->periodo);
    $meses_labels[] = $fecha ? $fecha->format('M Y') : $mes->periodo;
    $meses_data[] = (int) $mes->total;
}

// Helpers
$estados_colores = [
    'activo' => 'green',
    'potencial' => 'blue',
    'inactivo' => 'gray',
    'perdido' => 'red',
];

$tipos_iconos = [
    'empresa' => 'dashicons-building',
    'particular' => 'dashicons-admin-users',
    'autonomo' => 'dashicons-businessperson',
    'administracion' => 'dashicons-bank',
];
?>

<div class="dm-dashboard dm-dashboard--clientes">
    <!-- Header -->
    <div class="dm-dashboard__header">
        <div class="dm-dashboard__title">
            <span class="dashicons dashicons-businessman"></span>
            <h1><?php esc_html_e('Gestión de Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-dashboard__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=clientes-listado&action=nuevo')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=clientes-listado')); ?>" class="button">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Ver Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo number_format_i18n($total_clientes); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Total Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo number_format_i18n($clientes_activos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo number_format_i18n($clientes_potenciales); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Potenciales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-plus-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo number_format_i18n($nuevos_mes); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dm-grid dm-grid--2">
        <!-- Clientes Recientes -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Clientes Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=clientes-listado')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body">
                <?php if (empty($clientes_recientes)) : ?>
                    <div class="dm-empty-state dm-empty-state--small">
                        <span class="dashicons dashicons-admin-users"></span>
                        <p><?php esc_html_e('No hay clientes registrados aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else : ?>
                    <ul class="dm-list dm-list--clientes">
                        <?php foreach ($clientes_recientes as $cliente) :
                            $tipo_icono = $tipos_iconos[$cliente->tipo] ?? 'dashicons-admin-users';
                            $estado_color = $estados_colores[$cliente->estado] ?? 'gray';
                        ?>
                            <li class="dm-list__item">
                                <div class="dm-list__icon">
                                    <span class="dashicons <?php echo esc_attr($tipo_icono); ?>"></span>
                                </div>
                                <div class="dm-list__content">
                                    <strong><?php echo esc_html($cliente->nombre); ?></strong>
                                    <?php if (!empty($cliente->empresa)) : ?>
                                        <span class="dm-list__meta"><?php echo esc_html($cliente->empresa); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="dm-list__badge dm-badge dm-badge--<?php echo esc_attr($estado_color); ?>">
                                    <?php echo esc_html(ucfirst($cliente->estado)); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Potenciales Prioritarios -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Clientes Potenciales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <span class="dm-card__badge"><?php echo number_format_i18n($clientes_potenciales); ?></span>
            </div>
            <div class="dm-card__body">
                <?php if (empty($potenciales_prioritarios)) : ?>
                    <div class="dm-empty-state dm-empty-state--small">
                        <span class="dashicons dashicons-star-empty"></span>
                        <p><?php esc_html_e('No hay clientes potenciales actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else : ?>
                    <ul class="dm-list">
                        <?php foreach ($potenciales_prioritarios as $potencial) : ?>
                            <li class="dm-list__item">
                                <div class="dm-list__content">
                                    <strong><?php echo esc_html($potencial->nombre); ?></strong>
                                    <?php if (!empty($potencial->empresa)) : ?>
                                        <span class="dm-list__meta"><?php echo esc_html($potencial->empresa); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($potencial->email)) : ?>
                                        <span class="dm-list__meta"><?php echo esc_html($potencial->email); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="dm-list__actions">
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Secondary Grid -->
    <div class="dm-grid dm-grid--3">
        <!-- Por Tipo -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-category"></span>
                    <?php esc_html_e('Por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__body">
                <?php if (empty($por_tipo)) : ?>
                    <p class="dm-text-muted"><?php esc_html_e('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <ul class="dm-breakdown-list">
                        <?php foreach ($por_tipo as $tipo) :
                            $icono = $tipos_iconos[$tipo->tipo] ?? 'dashicons-admin-users';
                        ?>
                            <li class="dm-breakdown-list__item">
                                <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                                <span class="dm-breakdown-list__label"><?php echo esc_html(ucfirst($tipo->tipo)); ?></span>
                                <span class="dm-breakdown-list__value"><?php echo number_format_i18n($tipo->total); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Por Origen -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Por Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__body">
                <?php if (empty($por_origen)) : ?>
                    <p class="dm-text-muted"><?php esc_html_e('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <ul class="dm-breakdown-list">
                        <?php foreach ($por_origen as $origen) : ?>
                            <li class="dm-breakdown-list__item">
                                <span class="dm-breakdown-list__label"><?php echo esc_html(ucfirst($origen->origen ?: 'Sin especificar')); ?></span>
                                <span class="dm-breakdown-list__value"><?php echo number_format_i18n($origen->total); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__body">
                <ul class="dm-breakdown-list">
                    <li class="dm-breakdown-list__item">
                        <span class="dm-dot dm-dot--green"></span>
                        <span class="dm-breakdown-list__label"><?php esc_html_e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="dm-breakdown-list__value"><?php echo number_format_i18n($clientes_activos); ?></span>
                    </li>
                    <li class="dm-breakdown-list__item">
                        <span class="dm-dot dm-dot--blue"></span>
                        <span class="dm-breakdown-list__label"><?php esc_html_e('Potenciales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="dm-breakdown-list__value"><?php echo number_format_i18n($clientes_potenciales); ?></span>
                    </li>
                    <li class="dm-breakdown-list__item">
                        <span class="dm-dot dm-dot--gray"></span>
                        <span class="dm-breakdown-list__label"><?php esc_html_e('Inactivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="dm-breakdown-list__value"><?php echo number_format_i18n($clientes_inactivos); ?></span>
                    </li>
                    <li class="dm-breakdown-list__item">
                        <span class="dm-dot dm-dot--red"></span>
                        <span class="dm-breakdown-list__label"><?php esc_html_e('Perdidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="dm-breakdown-list__value"><?php echo number_format_i18n($clientes_perdidos); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <?php if (!empty($notas_recientes)) : ?>
    <div class="dm-card dm-card--full">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <ul class="dm-activity-list">
                <?php foreach ($notas_recientes as $nota) : ?>
                    <li class="dm-activity-list__item">
                        <div class="dm-activity-list__icon">
                            <span class="dashicons dashicons-format-aside"></span>
                        </div>
                        <div class="dm-activity-list__content">
                            <strong><?php echo esc_html($nota->autor_nombre ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                            <?php esc_html_e('añadió una nota en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <strong><?php echo esc_html($nota->cliente_nombre); ?></strong>
                            <?php if (!empty($nota->cliente_empresa)) : ?>
                                (<?php echo esc_html($nota->cliente_empresa); ?>)
                            <?php endif; ?>
                            <span class="dm-activity-list__time">
                                <?php echo esc_html(human_time_diff(strtotime($nota->fecha), current_time('timestamp'))); ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Chart: Evolución Mensual -->
    <?php if (!empty($meses_data)) : ?>
    <div class="dm-card dm-card--full">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e('Evolución Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <canvas id="chart-clientes-mensual" height="200"></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') return;

        const ctx = document.getElementById('chart-clientes-mensual');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode($meses_labels); ?>,
                datasets: [{
                    label: '<?php echo esc_js(__('Nuevos clientes', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                    data: <?php echo wp_json_encode($meses_data); ?>,
                    backgroundColor: 'rgba(34, 113, 177, 0.7)',
                    borderColor: 'rgba(34, 113, 177, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>
</div>

<?php
/**
 * Dashboard de Espacios Comunes
 * Vista general de uso y estado de espacios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

// Obtener estadísticas
global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
$tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

// Verificar si las tablas existen
$tabla_espacios_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_espacios)) === $tabla_espacios;
$tabla_reservas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reservas)) === $tabla_reservas;

$fecha_inicio_mes = date('Y-m-01 00:00:00');
$fecha_actual = current_time('mysql');

// Valores por defecto
$total_espacios = 0;
$reservas_activas = 0;
$usuarios_activos = 0;
$tasa_ocupacion = 0;
$ranking_espacios = [];
$proximas_reservas = [];
$estado_espacios = [];
$usando_demo = false;

if ($tabla_espacios_existe && $tabla_reservas_existe) {
    $total_espacios = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_espacios} WHERE estado = 'activo'");

    $reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'confirmada' AND fecha_fin >= %s",
        $fecha_actual
    ));

    $usuarios_activos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_reservas} WHERE fecha_inicio >= %s",
        $fecha_inicio_mes
    ));

    // Tasa de ocupación aproximada
    $horas_reservadas = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin)) FROM {$tabla_reservas}
         WHERE estado = 'confirmada' AND fecha_inicio >= %s",
        $fecha_inicio_mes
    )) ?? 0;

    $horas_disponibles = $total_espacios * 30 * 12; // 30 días * 12 horas/día
    $tasa_ocupacion = $horas_disponibles > 0 ? round(($horas_reservadas / $horas_disponibles) * 100, 1) : 0;

    // Ranking de espacios más usados
    $ranking_espacios = $wpdb->get_results($wpdb->prepare(
        "SELECT e.nombre, COUNT(r.id) as total_reservas
         FROM {$tabla_espacios} e
         LEFT JOIN {$tabla_reservas} r ON e.id = r.espacio_id AND r.fecha_inicio >= %s
         WHERE e.estado = 'activo'
         GROUP BY e.id
         ORDER BY total_reservas DESC
         LIMIT 5",
        $fecha_inicio_mes
    ));

    // Próximas reservas
    $proximas_reservas = $wpdb->get_results($wpdb->prepare(
        "SELECT e.nombre as espacio, r.fecha_inicio, r.fecha_fin, u.display_name
         FROM {$tabla_reservas} r
         INNER JOIN {$tabla_espacios} e ON r.espacio_id = e.id
         INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
         WHERE r.estado = 'confirmada' AND r.fecha_inicio >= %s
         ORDER BY r.fecha_inicio ASC
         LIMIT 5",
        $fecha_actual
    ));

    // Estado actual de espacios
    $estado_espacios = $wpdb->get_results(
        "SELECT e.id, e.nombre,
            CASE WHEN EXISTS (
                SELECT 1 FROM {$tabla_reservas} r
                WHERE r.espacio_id = e.id
                AND r.estado = 'confirmada'
                AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin
            ) THEN 0 ELSE 1 END as disponible
         FROM {$tabla_espacios} e
         WHERE e.estado = 'activo'
         ORDER BY e.nombre"
    );
}

// Usar datos demo si no hay datos reales
if ($total_espacios == 0) {
    $usando_demo = true;
    $total_espacios = 6;
    $reservas_activas = 4;
    $usuarios_activos = 28;
    $tasa_ocupacion = 42.5;

    $ranking_espacios = [
        (object) ['nombre' => 'Sala de Reuniones A', 'total_reservas' => 45],
        (object) ['nombre' => 'Salón Multiusos', 'total_reservas' => 38],
        (object) ['nombre' => 'Aula Formación', 'total_reservas' => 32],
        (object) ['nombre' => 'Sala de Reuniones B', 'total_reservas' => 24],
        (object) ['nombre' => 'Terraza Comunitaria', 'total_reservas' => 18],
    ];

    $proximas_reservas = [
        (object) ['espacio' => 'Sala de Reuniones A', 'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+2 hours')), 'fecha_fin' => date('Y-m-d H:i:s', strtotime('+4 hours')), 'display_name' => 'María García'],
        (object) ['espacio' => 'Salón Multiusos', 'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+1 day 10:00')), 'fecha_fin' => date('Y-m-d H:i:s', strtotime('+1 day 14:00')), 'display_name' => 'Asociación Vecinal'],
        (object) ['espacio' => 'Aula Formación', 'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+2 days 16:00')), 'fecha_fin' => date('Y-m-d H:i:s', strtotime('+2 days 19:00')), 'display_name' => 'Carlos López'],
    ];

    $estado_espacios = [
        (object) ['id' => 1, 'nombre' => 'Sala de Reuniones A', 'disponible' => 1],
        (object) ['id' => 2, 'nombre' => 'Sala de Reuniones B', 'disponible' => 0],
        (object) ['id' => 3, 'nombre' => 'Salón Multiusos', 'disponible' => 1],
        (object) ['id' => 4, 'nombre' => 'Aula Formación', 'disponible' => 1],
        (object) ['id' => 5, 'nombre' => 'Terraza Comunitaria', 'disponible' => 1],
        (object) ['id' => 6, 'nombre' => 'Cocina Comunitaria', 'disponible' => 0],
    ];
}
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('espacios_comunes');
    }
    ?>

    <!-- Header -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-building" style="font-size: 28px;"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Espacios Comunes', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Gestión y reservas de espacios compartidos', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Espacio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <?php if ($usando_demo) : ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('Mostrando datos de demostración. Los datos reales aparecerán cuando se registren espacios y reservas.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="dm-quick-links">
        <h3 class="dm-quick-links__title"><?php esc_html_e('Acceso Rápido', 'flavor-chat-ia'); ?></h3>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-listado')); ?>" class="dm-quick-links__item dm-quick-links__item--primary">
                <span class="dashicons dashicons-building"></span>
                <?php esc_html_e('Espacios', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-reservas')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Reservas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-calendario')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-calendar"></span>
                <?php esc_html_e('Calendario', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-normas')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e('Normas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/espacios-comunes/')); ?>" class="dm-quick-links__item" target="_blank">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Portal público', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_espacios)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Espacios', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('disponibles', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($reservas_activas)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas Activas', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('en curso o próximas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($usuarios_activos)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('este mes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($tasa_ocupacion, 1)); ?>%</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Tasa de Ocupación', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('promedio mensual', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos y listas -->
    <div class="dm-grid dm-grid--2">
        <!-- Espacios más populares -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Espacios Más Populares', 'flavor-chat-ia'); ?></h3>
            </div>
            <?php if (!empty($ranking_espacios)) : ?>
            <ol class="dm-ranking">
                <?php foreach ($ranking_espacios as $espacio) : ?>
                <li>
                    <span><?php echo esc_html($espacio->nombre); ?></span>
                    <strong><?php echo esc_html(number_format_i18n($espacio->total_reservas)); ?> <?php esc_html_e('reservas', 'flavor-chat-ia'); ?></strong>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-building"></span>
                <p><?php esc_html_e('No hay datos de uso disponibles.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Próximas reservas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Próximas Reservas', 'flavor-chat-ia'); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=espacios-reservas')); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                    <?php esc_html_e('Ver todas', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php if (!empty($proximas_reservas)) : ?>
            <div class="dm-item-list">
                <?php foreach ($proximas_reservas as $reserva) :
                    $fecha = date_i18n('d M', strtotime($reserva->fecha_inicio));
                    $hora_inicio = date_i18n('H:i', strtotime($reserva->fecha_inicio));
                    $hora_fin = date_i18n('H:i', strtotime($reserva->fecha_fin));
                ?>
                <div class="dm-item-list__item">
                    <div class="dm-item-list__content">
                        <strong><?php echo esc_html($reserva->espacio); ?></strong>
                        <span class="dm-item-list__muted"><?php echo esc_html($reserva->display_name); ?></span>
                    </div>
                    <div class="dm-item-list__meta">
                        <span class="dm-badge dm-badge--info"><?php echo esc_html($fecha); ?></span>
                        <span class="dm-text-muted dm-text-sm"><?php echo esc_html($hora_inicio); ?> - <?php echo esc_html($hora_fin); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-calendar"></span>
                <p><?php esc_html_e('No hay reservas próximas.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estado actual de espacios -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Estado Actual de Espacios', 'flavor-chat-ia'); ?></h3>
            <span class="dm-live-indicator">
                <span class="dm-live-indicator__dot"></span>
                <?php esc_html_e('En vivo', 'flavor-chat-ia'); ?>
            </span>
        </div>
        <?php if (!empty($estado_espacios)) : ?>
        <div class="dm-spaces-grid">
            <?php foreach ($estado_espacios as $espacio) :
                $disponible = (bool) $espacio->disponible;
            ?>
            <div class="dm-space-status dm-space-status--<?php echo $disponible ? 'available' : 'occupied'; ?>">
                <h4><?php echo esc_html($espacio->nombre); ?></h4>
                <span class="dm-badge dm-badge--<?php echo $disponible ? 'success' : 'error'; ?>">
                    <?php echo $disponible ? esc_html__('Disponible', 'flavor-chat-ia') : esc_html__('Ocupado', 'flavor-chat-ia'); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-building"></span>
            <p><?php esc_html_e('No hay espacios registrados.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Uso por Espacio', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-uso-espacios"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Reservas por Día de la Semana', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-dias-semana"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';

    // Datos para gráficos
    var espaciosNombres = <?php echo wp_json_encode(array_map(function($e) { return $e->nombre; }, $ranking_espacios)); ?>;
    var espaciosReservas = <?php echo wp_json_encode(array_map(function($e) { return (int) $e->total_reservas; }, $ranking_espacios)); ?>;

    // Datos demo para días de la semana
    var diasSemana = ['<?php echo esc_js(__('Lun', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Mar', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Mié', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Jue', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Vie', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Sáb', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Dom', 'flavor-chat-ia')); ?>'];
    var reservasDias = [12, 18, 15, 22, 28, 8, 4];

    // Gráfico de barras - Uso por espacio
    var ctx1 = document.getElementById('grafico-uso-espacios');
    if (ctx1 && typeof Chart !== 'undefined') {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: espaciosNombres,
                datasets: [{
                    label: '<?php echo esc_js(__('Reservas', 'flavor-chat-ia')); ?>',
                    data: espaciosReservas,
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
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

    // Gráfico de líneas - Reservas por día
    var ctx2 = document.getElementById('grafico-dias-semana');
    if (ctx2 && typeof Chart !== 'undefined') {
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: diasSemana,
                datasets: [{
                    label: '<?php echo esc_js(__('Reservas', 'flavor-chat-ia')); ?>',
                    data: reservasDias,
                    borderColor: successColor,
                    backgroundColor: successColor + '1a',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
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
});
</script>

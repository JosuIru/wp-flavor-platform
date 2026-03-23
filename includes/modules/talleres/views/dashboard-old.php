<?php
/**
 * Vista Dashboard - Talleres
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
$tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';

// Verificar existencia de tablas
$tabla_talleres_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") === $tabla_talleres;
$tabla_inscripciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_inscripciones'") === $tabla_inscripciones;
$tabla_sesiones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_sesiones'") === $tabla_sesiones;

// Inicializar variables
$total_talleres = 0;
$talleres_activos = 0;
$proximos_talleres = 0;
$total_participantes = 0;
$inscripciones_mes = 0;
$ingresos_mes = 0;
$talleres_populares = [];
$proximos = [];
$inscripciones_por_dia = [];
$tablas_disponibles = ($tabla_talleres_existe && $tabla_inscripciones_existe);

if ($tabla_talleres_existe && $tabla_inscripciones_existe) {
    // Estadísticas
    $total_talleres = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado != 'borrador'");
    $talleres_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado IN ('publicado', 'confirmado', 'en_curso')");
    $proximos_talleres = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado = 'confirmado'");
    $total_participantes = (int) $wpdb->get_var("SELECT COALESCE(SUM(inscritos_actuales), 0) FROM $tabla_talleres");
    $inscripciones_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE MONTH(fecha_inscripcion) = MONTH(CURDATE())");
    $ingresos_mes = (float) $wpdb->get_var("SELECT COALESCE(SUM(precio_pagado), 0) FROM $tabla_inscripciones WHERE MONTH(fecha_inscripcion) = MONTH(CURDATE())");

    // Talleres populares
    $talleres_populares = $wpdb->get_results(
        "SELECT t.*, u.display_name as organizador
         FROM $tabla_talleres t
         LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
         WHERE t.estado IN ('publicado', 'confirmado', 'en_curso')
         ORDER BY t.inscritos_actuales DESC
         LIMIT 5"
    );

    // Próximos talleres
    if ($tabla_sesiones_existe) {
        $proximos = $wpdb->get_results(
            "SELECT t.*, s.fecha_hora, u.display_name as organizador
             FROM $tabla_talleres t
             INNER JOIN $tabla_sesiones s ON t.id = s.taller_id
             LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
             WHERE t.estado = 'confirmado'
             AND s.fecha_hora >= NOW()
             ORDER BY s.fecha_hora ASC
             LIMIT 5"
        );
    }

    // Inscripciones por día
    $inscripciones_por_dia = $wpdb->get_results(
        "SELECT DATE(fecha_inscripcion) as fecha, COUNT(*) as total
         FROM $tabla_inscripciones
         WHERE fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(fecha_inscripcion)
         ORDER BY fecha ASC"
    );
}

// Mapeo de estados a badges
$estado_badge_classes = [
    'en_curso' => 'dm-badge--success',
    'confirmado' => 'dm-badge--info',
    'publicado' => 'dm-badge--primary',
    'finalizado' => 'dm-badge--secondary',
    'cancelado' => 'dm-badge--error',
    'borrador' => 'dm-badge--warning',
];

/**
 * Obtener clase de capacidad según porcentaje
 */
function get_capacity_class($current, $max) {
    if ($max <= 0) return 'dm-capacity--low';
    $percentage = ($current / $max) * 100;
    if ($percentage >= 90) return 'dm-capacity--high';
    if ($percentage >= 60) return 'dm-capacity--medium';
    return 'dm-capacity--low';
}
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('talleres');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Talleres o aún no hay inscripciones registradas.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-hammer"></span>
            <h1><?php esc_html_e('Dashboard - Talleres Prácticos', 'flavor-chat-ia'); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=talleres-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-hammer"></span>
            <span><?php esc_html_e('Talleres', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=talleres-inscripciones')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-clipboard"></span>
            <span><?php esc_html_e('Inscripciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=talleres-materiales')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-page"></span>
            <span><?php esc_html_e('Materiales', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=talleres-configuracion')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('talleres', '')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_talleres); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Talleres', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-welcome-learn-more"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($talleres_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Talleres Activos', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-yes"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($proximos_talleres); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Próximos Talleres', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-calendar"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_participantes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Participantes', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-groups"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($inscripciones_mes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Inscripciones (mes)', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-tickets"></span></div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($ingresos_mes, 2); ?>€</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Ingresos (mes)', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-cart"></span></div>
        </div>
    </div>

    <!-- Gráficos y tablas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Inscripciones - Últimos 30 días', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chartInscripciones"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Talleres Más Populares', 'flavor-chat-ia'); ?></h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Taller', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Inscritos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($talleres_populares)): ?>
                        <?php foreach ($talleres_populares as $taller): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words($taller->titulo, 4)); ?></strong>
                                    <span class="dm-table__subtitle"><?php echo esc_html($taller->organizador); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $capacity_class = get_capacity_class($taller->inscritos_actuales, $taller->max_participantes);
                                    $percentage = $taller->max_participantes > 0 ? ($taller->inscritos_actuales / $taller->max_participantes) * 100 : 0;
                                    ?>
                                    <div class="dm-capacity <?php echo esc_attr($capacity_class); ?>">
                                        <div class="dm-capacity__bar">
                                            <div class="dm-capacity__fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                        </div>
                                        <span class="dm-capacity__text"><?php echo esc_html($taller->inscritos_actuales); ?>/<?php echo esc_html($taller->max_participantes); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">
                                <div class="dm-empty">
                                    <span class="dashicons dashicons-hammer"></span>
                                    <p><?php esc_html_e('No hay talleres disponibles', 'flavor-chat-ia'); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Próximos talleres -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Próximos Talleres', 'flavor-chat-ia'); ?></h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Taller', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Organizador', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($proximos)): ?>
                    <?php foreach ($proximos as $taller): ?>
                        <tr>
                            <td><strong><?php echo esc_html($taller->titulo); ?></strong></td>
                            <td><?php echo esc_html($taller->organizador); ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($taller->fecha_hora))); ?></td>
                            <td>
                                <?php
                                $capacity_class = get_capacity_class($taller->inscritos_actuales, $taller->max_participantes);
                                $percentage = $taller->max_participantes > 0 ? ($taller->inscritos_actuales / $taller->max_participantes) * 100 : 0;
                                ?>
                                <div class="dm-capacity <?php echo esc_attr($capacity_class); ?>">
                                    <div class="dm-capacity__bar">
                                        <div class="dm-capacity__fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                    </div>
                                    <span class="dm-capacity__text"><?php echo esc_html($taller->inscritos_actuales); ?>/<?php echo esc_html($taller->max_participantes); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$taller->estado] ?? 'dm-badge--secondary'); ?>">
                                    <?php echo esc_html(ucfirst($taller->estado)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="dm-empty">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <p><?php esc_html_e('No hay talleres próximos', 'flavor-chat-ia'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartInscripciones');
    if (ctx && typeof Chart !== 'undefined') {
        const data = <?php echo wp_json_encode($inscripciones_por_dia); ?>;
        const rootStyles = getComputedStyle(document.documentElement);
        const primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.getDate() + '/' + (fecha.getMonth() + 1);
                }),
                datasets: [{
                    label: '<?php esc_html_e('Inscripciones', 'flavor-chat-ia'); ?>',
                    data: data.map(d => parseInt(d.total)),
                    borderColor: primaryColor,
                    backgroundColor: primaryColor + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }
});
</script>

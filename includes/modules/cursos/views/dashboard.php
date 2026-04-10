<?php
/**
 * Vista Dashboard - Cursos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
$tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';

// Verificar existencia de tablas
$tabla_cursos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") === $tabla_cursos;
$tabla_inscripciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_inscripciones'") === $tabla_inscripciones;
$tabla_certificados_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_certificados'") === $tabla_certificados;

// Inicializar variables
$total_cursos = 0;
$cursos_activos = 0;
$total_alumnos = 0;
$total_inscripciones = 0;
$certificados_emitidos = 0;
$ingresos_mes = 0;
$cursos_populares = [];
$inscripciones_recientes = [];
$inscripciones_por_dia = [];
$tablas_disponibles = ($tabla_cursos_existe && $tabla_inscripciones_existe);

if ($tabla_cursos_existe && $tabla_inscripciones_existe) {
    // Obtener estadísticas
    $total_cursos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cursos WHERE estado != 'borrador'");
    $cursos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cursos WHERE estado = 'en_curso'");
    $total_alumnos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_inscripciones");
    $total_inscripciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado = 'activo'");

    if ($tabla_certificados_existe) {
        $certificados_emitidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_certificados WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE())");
    }

    $ingresos_mes = (float) $wpdb->get_var("SELECT COALESCE(SUM(precio_pagado), 0) FROM $tabla_inscripciones WHERE MONTH(created_at) = MONTH(CURRENT_DATE())");

    // Cursos más populares
    $cursos_populares = $wpdb->get_results(
        "SELECT c.id, c.titulo, c.alumnos_inscritos, c.valoracion_media, c.estado
         FROM $tabla_cursos c
         WHERE c.estado IN ('publicado', 'en_curso')
         ORDER BY c.alumnos_inscritos DESC
         LIMIT 5"
    );

    // Inscripciones recientes
    $inscripciones_recientes = $wpdb->get_results(
        "SELECT i.id, i.created_at as fecha_inscripcion, c.titulo as curso, u.display_name as alumno
         FROM $tabla_inscripciones i
         INNER JOIN $tabla_cursos c ON i.curso_id = c.id
         INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
         ORDER BY i.created_at DESC
         LIMIT 10"
    );

    // Datos para gráficos (últimos 30 días)
    $inscripciones_por_dia = $wpdb->get_results(
        "SELECT DATE(created_at) as fecha, COUNT(*) as total
         FROM $tabla_inscripciones
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY fecha ASC"
    );
}

// Mapeo de estados a badges
$estado_badge_classes = [
    'en_curso' => 'dm-badge--success',
    'publicado' => 'dm-badge--info',
    'finalizado' => 'dm-badge--secondary',
    'borrador' => 'dm-badge--warning',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('cursos');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Cursos o aún no hay inscripciones registradas.', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <h1><?php esc_html_e('Dashboard - Cursos y Formación', 'flavor-platform'); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=cursos-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <span><?php esc_html_e('Cursos', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cursos-alumnos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Alumnos', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cursos-instructores')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-businessperson"></span>
            <span><?php esc_html_e('Instructores', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cursos-matriculas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-clipboard"></span>
            <span><?php esc_html_e('Matrículas', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/cursos/')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-platform'); ?></span>
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_cursos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Cursos Publicados', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-book"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($cursos_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Cursos Activos', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-welcome-learn-more"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_alumnos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Alumnos Totales', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-groups"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_inscripciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Inscripciones Activas', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-welcome-write-blog"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($certificados_emitidos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Certificados (mes)', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-awards"></span></div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($ingresos_mes, 2); ?>€</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Ingresos (mes)', 'flavor-platform'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-cart"></span></div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Inscripciones - Últimos 30 días', 'flavor-platform'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chartInscripciones"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Cursos Populares', 'flavor-platform'); ?></h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Curso', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Alumnos', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Rating', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cursos_populares)): ?>
                        <?php foreach ($cursos_populares as $curso): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($curso->titulo); ?></strong>
                                    <br>
                                    <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$curso->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $curso->estado))); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format_i18n($curso->alumnos_inscritos); ?></td>
                                <td>
                                    <span class="dm-rating">
                                        <?php echo number_format_i18n($curso->valoracion_media, 1); ?> ★
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                <div class="dm-empty">
                                    <span class="dashicons dashicons-book"></span>
                                    <p><?php esc_html_e('No hay cursos disponibles', 'flavor-platform'); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inscripciones recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Inscripciones Recientes', 'flavor-platform'); ?></h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Alumno', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Curso', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inscripciones_recientes)): ?>
                    <?php foreach ($inscripciones_recientes as $inscripcion): ?>
                        <tr>
                            <td><span class="dm-text-muted">#<?php echo esc_html($inscripcion->id); ?></span></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($inscripcion->fecha_inscripcion))); ?></td>
                            <td><?php echo esc_html($inscripcion->alumno); ?></td>
                            <td><?php echo esc_html($inscripcion->curso); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="dm-empty">
                                <span class="dashicons dashicons-clipboard"></span>
                                <p><?php esc_html_e('No hay inscripciones recientes', 'flavor-platform'); ?></p>
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
                    label: '<?php esc_html_e('Inscripciones', 'flavor-platform'); ?>',
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

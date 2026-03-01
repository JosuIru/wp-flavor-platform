<?php
/**
 * Vista Dashboard - Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
$tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';

// Obtener estadísticas
$total_cursos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cursos WHERE estado != 'borrador'");
$cursos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cursos WHERE estado = 'en_curso'");
$total_alumnos = $wpdb->get_var("SELECT COUNT(DISTINCT alumno_id) FROM $tabla_inscripciones");
$total_inscripciones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado = 'activa'");
$certificados_emitidos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_certificados WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE())");
$ingresos_mes = $wpdb->get_var("SELECT SUM(precio_pagado) FROM $tabla_inscripciones WHERE MONTH(fecha_inscripcion) = MONTH(CURRENT_DATE())");

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
    "SELECT i.id, i.fecha_inscripcion, c.titulo as curso, u.display_name as alumno
     FROM $tabla_inscripciones i
     INNER JOIN $tabla_cursos c ON i.curso_id = c.id
     INNER JOIN {$wpdb->users} u ON i.alumno_id = u.ID
     ORDER BY i.fecha_inscripcion DESC
     LIMIT 10"
);

// Datos para gráficos (últimos 30 días)
$inscripciones_por_dia = $wpdb->get_results(
    "SELECT DATE(fecha_inscripcion) as fecha, COUNT(*) as total
     FROM $tabla_inscripciones
     WHERE fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(fecha_inscripcion)
     ORDER BY fecha ASC"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Dashboard - Cursos y Formación', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rápidos -->
    <div class="cursos-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=cursos-listado'); ?>" class="cursos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-welcome-learn-more" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Cursos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=cursos-alumnos'); ?>" class="cursos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Alumnos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=cursos-instructores'); ?>" class="cursos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-businessperson" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Instructores', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=cursos-matriculas'); ?>" class="cursos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-clipboard" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Matrículas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-app-composer&module=cursos'); ?>" class="cursos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_cursos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Cursos Publicados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($cursos_activos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Cursos Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_alumnos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Alumnos Totales', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #f59e0b;">
                <span class="dashicons dashicons-welcome-write-blog"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_inscripciones); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Inscripciones Activas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #06b6d4;">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($certificados_emitidos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Certificados (mes)', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #14b8a6;">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($ingresos_mes, 2); ?>€</div>
                <div class="flavor-stat-label"><?php echo esc_html__('Ingresos (mes)', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="flavor-dashboard-row">
        <div class="flavor-dashboard-col-8">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Inscripciones - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <canvas id="chartInscripciones" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-col-4">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Cursos Populares', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Curso', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Alumnos', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Rating', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cursos_populares)): ?>
                                <?php foreach ($cursos_populares as $curso): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($curso->titulo); ?></strong>
                                            <br><small class="flavor-badge flavor-badge-<?php echo $curso->estado === 'en_curso' ? 'success' : 'info'; ?>">
                                                <?php echo ucfirst($curso->estado); ?>
                                            </small>
                                        </td>
                                        <td><?php echo number_format($curso->alumnos_inscritos); ?></td>
                                        <td>
                                            <span class="flavor-rating">
                                                <?php echo number_format($curso->valoracion_media, 1); ?> ★
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="flavor-no-data"><?php echo esc_html__('No hay datos disponibles', 'flavor-chat-ia'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Inscripciones recientes -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php echo esc_html__('Inscripciones Recientes', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Alumno', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Curso', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripciones_recientes)): ?>
                        <?php foreach ($inscripciones_recientes as $inscripcion): ?>
                            <tr>
                                <td><?php echo $inscripcion->id; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($inscripcion->fecha_inscripcion)); ?></td>
                                <td><?php echo esc_html($inscripcion->alumno); ?></td>
                                <td><?php echo esc_html($inscripcion->curso); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="flavor-no-data"><?php echo esc_html__('No hay inscripciones recientes', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.flavor-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.flavor-stat-content {
    flex: 1;
}

.flavor-stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #1e293b;
}

.flavor-stat-label {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

.flavor-dashboard-row {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.flavor-dashboard-col-8 {
    grid-column: span 8;
}

.flavor-dashboard-col-4 {
    grid-column: span 4;
}

.flavor-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

.flavor-card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.flavor-card-body {
    padding: 20px;
}

.flavor-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge-success {
    background: #d1fae5;
    color: #065f46;
}

.flavor-badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.flavor-rating {
    color: #f59e0b;
    font-weight: 600;
}

.flavor-no-data {
    text-align: center;
    color: #94a3b8;
    padding: 20px !important;
}

@media (max-width: 782px) {
    .flavor-dashboard-col-8,
    .flavor-dashboard-col-4 {
        grid-column: span 12;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Gráfico de inscripciones
    const ctx = document.getElementById('chartInscripciones');
    if (ctx) {
        const data = <?php echo json_encode($inscripciones_por_dia); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.getDate() + '/' + (fecha.getMonth() + 1);
                }),
                datasets: [{
                    label: 'Inscripciones',
                    data: data.map(d => parseInt(d.total)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>

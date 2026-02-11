<?php
/**
 * Vista Dashboard - Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
$tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

// Estadísticas
$total_libros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros");
$libros_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE disponibilidad = 'disponible'");
$libros_prestados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE disponibilidad = 'prestado'");
$prestamos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'");
$prestamos_retrasados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'retrasado'");
$reservas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'pendiente'");
$total_lectores = $wpdb->get_var("SELECT COUNT(DISTINCT propietario_id) FROM $tabla_libros");
$prestamos_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE MONTH(fecha_prestamo) = MONTH(CURDATE())");

// Libros más prestados
$libros_populares = $wpdb->get_results(
    "SELECT l.*, COUNT(p.id) as num_prestamos
     FROM $tabla_libros l
     LEFT JOIN $tabla_prestamos p ON l.id = p.libro_id
     WHERE l.veces_prestado > 0
     GROUP BY l.id
     ORDER BY l.veces_prestado DESC
     LIMIT 5"
);

// Préstamos recientes
$prestamos_recientes = $wpdb->get_results(
    "SELECT p.*,
            l.titulo as libro_titulo,
            u1.display_name as prestamista,
            u2.display_name as prestatario
     FROM $tabla_prestamos p
     INNER JOIN $tabla_libros l ON p.libro_id = l.id
     INNER JOIN {$wpdb->users} u1 ON p.prestamista_id = u1.ID
     INNER JOIN {$wpdb->users} u2 ON p.prestatario_id = u2.ID
     ORDER BY p.fecha_prestamo DESC
     LIMIT 10"
);

// Préstamos por día (últimos 30 días)
$prestamos_por_dia = $wpdb->get_results(
    "SELECT DATE(fecha_prestamo) as fecha, COUNT(*) as total
     FROM $tabla_prestamos
     WHERE fecha_prestamo >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(fecha_prestamo)
     ORDER BY fecha ASC"
);

// Géneros más populares
$generos_populares = $wpdb->get_results(
    "SELECT l.genero, COUNT(p.id) as num_prestamos
     FROM $tabla_libros l
     LEFT JOIN $tabla_prestamos p ON l.id = p.libro_id
     WHERE l.genero IS NOT NULL
     GROUP BY l.genero
     ORDER BY num_prestamos DESC
     LIMIT 5"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Dashboard - Biblioteca Comunitaria', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Tarjetas de estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_libros); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Total Libros', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($libros_disponibles); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Disponibles', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #f59e0b;">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($libros_prestados); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Prestados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_lectores); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Lectores Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #ef4444;">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($prestamos_retrasados); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Préstamos Retrasados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #06b6d4;">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($reservas_pendientes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Reservas Pendientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="flavor-dashboard-row">
        <div class="flavor-dashboard-col-8">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Préstamos - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <canvas id="chartPrestamos" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-col-4">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Géneros Populares', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <canvas id="chartGeneros" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablas informativas -->
    <div class="flavor-dashboard-row">
        <div class="flavor-dashboard-col-6">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Libros Más Prestados', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Libro', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Préstamos', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($libros_populares)): ?>
                                <?php foreach ($libros_populares as $libro): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($libro->titulo); ?></strong>
                                        </td>
                                        <td><?php echo esc_html($libro->autor); ?></td>
                                        <td>
                                            <span class="flavor-badge flavor-badge-primary">
                                                <?php echo number_format($libro->veces_prestado); ?>
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

        <div class="flavor-dashboard-col-6">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Préstamos Recientes', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Libro', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Prestatario', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($prestamos_recientes)): ?>
                                <?php foreach ($prestamos_recientes as $prestamo): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(wp_trim_words($prestamo->libro_titulo, 5)); ?></strong>
                                            <br>
                                            <small class="flavor-text-muted">
                                                <?php echo date('d/m/Y', strtotime($prestamo->fecha_prestamo)); ?>
                                            </small>
                                        </td>
                                        <td><?php echo esc_html($prestamo->prestatario); ?></td>
                                        <td>
                                            <span class="flavor-badge flavor-badge-<?php
                                                echo $prestamo->estado === 'activo' ? 'success' :
                                                    ($prestamo->estado === 'retrasado' ? 'danger' : 'info');
                                            ?>">
                                                <?php echo ucfirst($prestamo->estado); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="flavor-no-data"><?php echo esc_html__('No hay préstamos recientes', 'flavor-chat-ia'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>

<script>
jQuery(document).ready(function($) {
    // Gráfico de préstamos
    const ctxPrestamos = document.getElementById('chartPrestamos');
    if (ctxPrestamos) {
        const dataPrestamos = <?php echo json_encode($prestamos_por_dia); ?>;

        new Chart(ctxPrestamos, {
            type: 'line',
            data: {
                labels: dataPrestamos.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.getDate() + '/' + (fecha.getMonth() + 1);
                }),
                datasets: [{
                    label: 'Préstamos',
                    data: dataPrestamos.map(d => parseInt(d.total)),
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

    // Gráfico de géneros
    const ctxGeneros = document.getElementById('chartGeneros');
    if (ctxGeneros) {
        const dataGeneros = <?php echo json_encode($generos_populares); ?>;

        new Chart(ctxGeneros, {
            type: 'doughnut',
            data: {
                labels: dataGeneros.map(g => g.genero),
                datasets: [{
                    data: dataGeneros.map(g => parseInt(g.num_prestamos)),
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

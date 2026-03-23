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

// Verificar tablas
$tabla_libros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_libros'");
$tabla_prestamos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_prestamos'");
$tabla_reservas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'");
$tablas_disponibles = ($tabla_libros_existe || $tabla_prestamos_existe || $tabla_reservas_existe);

// Inicializar estadísticas
$total_libros = 0;
$libros_disponibles = 0;
$libros_prestados = 0;
$prestamos_activos = 0;
$prestamos_retrasados = 0;
$reservas_pendientes = 0;
$total_lectores = 0;
$libros_populares = [];
$prestamos_recientes = [];
$prestamos_por_dia = [];
$generos_populares = [];

if ($tabla_libros_existe) {
    $total_libros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros");
    $libros_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE disponibilidad = 'disponible'");
    $libros_prestados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE disponibilidad = 'prestado'");
    $total_lectores = $wpdb->get_var("SELECT COUNT(DISTINCT propietario_id) FROM $tabla_libros");

    $libros_populares = $wpdb->get_results(
        "SELECT l.*, COUNT(p.id) as num_prestamos
         FROM $tabla_libros l
         LEFT JOIN $tabla_prestamos p ON l.id = p.libro_id
         WHERE l.veces_prestado > 0
         GROUP BY l.id
         ORDER BY l.veces_prestado DESC
         LIMIT 5"
    ) ?: [];

    $generos_populares = $wpdb->get_results(
        "SELECT l.genero, COUNT(p.id) as num_prestamos
         FROM $tabla_libros l
         LEFT JOIN $tabla_prestamos p ON l.id = p.libro_id
         WHERE l.genero IS NOT NULL
         GROUP BY l.genero
         ORDER BY num_prestamos DESC
         LIMIT 5"
    ) ?: [];
}

if ($tabla_prestamos_existe) {
    $prestamos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'");
    $prestamos_retrasados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'retrasado'");

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
    ) ?: [];

    $prestamos_por_dia = $wpdb->get_results(
        "SELECT DATE(fecha_prestamo) as fecha, COUNT(*) as total
         FROM $tabla_prestamos
         WHERE fecha_prestamo >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(fecha_prestamo)
         ORDER BY fecha ASC"
    ) ?: [];
}

if ($tabla_reservas_existe) {
    $reservas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'pendiente'");
}

// Mapeo de estados
$estado_badge_classes = [
    'activo' => 'dm-badge--success',
    'devuelto' => 'dm-badge--info',
    'retrasado' => 'dm-badge--error',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('biblioteca');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <p><?php esc_html_e('Faltan tablas del módulo Biblioteca o aún no hay actividad registrada.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-book"></span>
            <h1><?php esc_html_e('Biblioteca Comunitaria', 'flavor-chat-ia'); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=biblioteca-catalogo')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-book"></span>
            <span><?php esc_html_e('Catálogo', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=biblioteca-prestamos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-book-alt"></span>
            <span><?php esc_html_e('Préstamos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=biblioteca-usuarios')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=biblioteca-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biblioteca', '')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_libros); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Libros', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($libros_disponibles); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($libros_prestados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Prestados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_lectores); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Lectores Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($prestamos_retrasados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Préstamos Retrasados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas Pendientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Préstamos - Últimos 30 días', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="chartPrestamos"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Géneros Populares', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="chartGeneros"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Libros Más Prestados', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <?php if (!empty($libros_populares)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Libro', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php esc_html_e('Préstamos', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libros_populares as $libro) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($libro->titulo); ?></strong></td>
                            <td><?php echo esc_html($libro->autor); ?></td>
                            <td>
                                <span class="dm-badge dm-badge--primary">
                                    <?php echo number_format_i18n($libro->veces_prestado); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-book"></span>
                    <p><?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Préstamos Recientes', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <?php if (!empty($prestamos_recientes)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Libro', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Prestatario', 'flavor-chat-ia'); ?></th>
                            <th style="width: 90px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestamos_recientes as $prestamo) :
                            $badge_class = $estado_badge_classes[$prestamo->estado] ?? 'dm-badge--secondary';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(wp_trim_words($prestamo->libro_titulo, 5)); ?></strong>
                                <span class="dm-table__subtitle">
                                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($prestamo->fecha_prestamo))); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($prestamo->prestatario); ?></td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                    <?php echo esc_html(ucfirst($prestamo->estado)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-book-alt"></span>
                    <p><?php esc_html_e('No hay préstamos recientes', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxPrestamos = document.getElementById('chartPrestamos');
    const ctxGeneros = document.getElementById('chartGeneros');

    const dataPrestamos = <?php echo json_encode($prestamos_por_dia); ?>;

    const dataGeneros = <?php echo json_encode($generos_populares); ?>;

    if (ctxPrestamos) {
        new Chart(ctxPrestamos, {
            type: 'line',
            data: {
                labels: dataPrestamos.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.getDate() + '/' + (fecha.getMonth() + 1);
                }),
                datasets: [{
                    label: '<?php esc_attr_e('Préstamos', 'flavor-chat-ia'); ?>',
                    data: dataPrestamos.map(d => parseInt(d.total)),
                    borderColor: 'var(--dm-primary, #3b82f6)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    if (ctxGeneros) {
        new Chart(ctxGeneros, {
            type: 'doughnut',
            data: {
                labels: dataGeneros.map(g => g.genero),
                datasets: [{
                    data: dataGeneros.map(g => parseInt(g.num_prestamos)),
                    backgroundColor: [
                        'var(--dm-primary, #3b82f6)',
                        'var(--dm-success, #22c55e)',
                        'var(--dm-warning, #f59e0b)',
                        '#8b5cf6',
                        'var(--dm-error, #ef4444)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>

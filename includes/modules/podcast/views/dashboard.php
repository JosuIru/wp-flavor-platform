<?php
/**
 * Vista Dashboard del módulo Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
$tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
$tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

// Obtener estadísticas generales
$total_podcasts = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_podcasts WHERE estado = 'publicado'");
$total_episodios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_episodios WHERE estado = 'publicado'");
$total_suscriptores = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_suscripciones");
$total_reproducciones = $wpdb->get_var("SELECT SUM(reproducciones) FROM $tabla_episodios");

// Episodios más populares
$episodios_populares = $wpdb->get_results("
    SELECT e.*, p.titulo as podcast_titulo
    FROM $tabla_episodios e
    INNER JOIN $tabla_podcasts p ON e.podcast_id = p.id
    WHERE e.estado = 'publicado'
    ORDER BY e.reproducciones DESC
    LIMIT 10
");

// Datos para gráfico de crecimiento (últimos 30 días)
$datos_crecimiento = $wpdb->get_results("
    SELECT DATE(fecha_suscripcion) as fecha, COUNT(*) as total
    FROM $tabla_suscripciones
    WHERE fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_suscripcion)
    ORDER BY fecha
");

// Estadísticas por plataforma (simulado)
$stats_plataforma = [
    'Spotify' => rand(30, 50),
    'Apple Podcasts' => rand(20, 35),
    'Google Podcasts' => rand(15, 25),
    'RSS Directo' => rand(10, 20),
    'Otros' => rand(5, 15)
];
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-microphone"></span>
        Dashboard de Podcasts
    </h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Total Podcasts</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_podcasts); ?></h2>
                </div>
                <span class="dashicons dashicons-microphone" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Total Episodios</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($total_episodios); ?></h2>
                </div>
                <span class="dashicons dashicons-playlist-audio" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Total Suscriptores</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #8c49d8;"><?php echo number_format($total_suscriptores); ?></h2>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 48px; color: #8c49d8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Reproducciones</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #d63638;"><?php echo number_format($total_reproducciones); ?></h2>
                </div>
                <span class="dashicons dashicons-controls-play" style="font-size: 48px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- Gráfico de crecimiento -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-chart-line"></span>
                Crecimiento de Suscriptores (30 días)
            </h3>
            <canvas id="grafico-crecimiento" style="max-height: 300px;"></canvas>
        </div>

        <!-- Distribución por plataforma -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-share"></span>
                Plataformas de Escucha
            </h3>
            <canvas id="grafico-plataformas" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Episodios más populares -->
    <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-star-filled"></span>
            Episodios Más Populares
        </h3>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">Pos.</th>
                    <th>Episodio</th>
                    <th>Podcast</th>
                    <th style="width: 120px;">Reproducciones</th>
                    <th style="width: 100px;">Me Gusta</th>
                    <th style="width: 150px;">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($episodios_populares)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-admin-media" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;">No hay episodios publicados todavía</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($episodios_populares as $indice => $episodio): ?>
                        <tr>
                            <td style="text-align: center;">
                                <strong style="color: #2271b1; font-size: 16px;"><?php echo ($indice + 1); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html($episodio->titulo); ?></strong>
                                <div style="color: #666; font-size: 12px;">
                                    Episodio #<?php echo $episodio->numero_episodio; ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($episodio->podcast_titulo); ?></td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-controls-play" style="color: #00a32a;"></span>
                                <?php echo number_format($episodio->reproducciones); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-heart" style="color: #d63638;"></span>
                                <?php echo number_format($episodio->me_gusta); ?>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($episodio->fecha_publicacion)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {

    // Gráfico de crecimiento de suscriptores
    var ctxCrecimiento = document.getElementById('grafico-crecimiento').getContext('2d');
    new Chart(ctxCrecimiento, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($item) {
                return date_i18n('d/m', strtotime($item->fecha));
            }, $datos_crecimiento)); ?>,
            datasets: [{
                label: 'Nuevos Suscriptores',
                data: <?php echo json_encode(array_map(function($item) {
                    return $item->total;
                }, $datos_crecimiento)); ?>,
                borderColor: '#8c49d8',
                backgroundColor: 'rgba(140, 73, 216, 0.1)',
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
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Gráfico de plataformas
    var ctxPlataformas = document.getElementById('grafico-plataformas').getContext('2d');
    new Chart(ctxPlataformas, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats_plataforma)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats_plataforma)); ?>,
                backgroundColor: [
                    '#2271b1',
                    '#00a32a',
                    '#8c49d8',
                    '#d63638',
                    '#dba617'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

});
</script>

<style>
.flavor-stats-grid,
.flavor-chart-card,
.flavor-table-card {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>

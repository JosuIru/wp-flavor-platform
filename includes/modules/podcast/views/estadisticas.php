<?php
/**
 * Vista de Estadísticas Detalladas de Podcast
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
$tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
$tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

// Obtener podcasts para selector
$podcasts = $wpdb->get_results("SELECT id, titulo FROM $tabla_podcasts ORDER BY titulo");
$podcast_seleccionado = isset($_GET['podcast_id']) ? intval($_GET['podcast_id']) : 0;

// Si hay podcast seleccionado, obtener estadísticas detalladas
$estadisticas_detalladas = null;
$episodios_stats = [];
$datos_temporales = [];

if ($podcast_seleccionado > 0) {
    $estadisticas_detalladas = $wpdb->get_row($wpdb->prepare("
        SELECT p.*,
               COUNT(DISTINCT e.id) as total_episodios,
               SUM(e.reproducciones) as total_reproducciones,
               SUM(e.me_gusta) as total_me_gusta,
               AVG(e.duracion_segundos) as duracion_promedio
        FROM $tabla_podcasts p
        LEFT JOIN $tabla_episodios e ON p.id = e.podcast_id AND e.estado = 'publicado'
        WHERE p.id = %d
        GROUP BY p.id
    ", $podcast_seleccionado));

    // Estadísticas por episodio
    $episodios_stats = $wpdb->get_results($wpdb->prepare("
        SELECT *,
               (reproducciones / GREATEST(DATEDIFF(NOW(), fecha_publicacion), 1)) as reproducciones_por_dia
        FROM $tabla_episodios
        WHERE podcast_id = %d AND estado = 'publicado'
        ORDER BY fecha_publicacion DESC
    ", $podcast_seleccionado));

    // Datos temporales de crecimiento (últimos 90 días)
    $datos_temporales = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(fecha_suscripcion) as fecha, COUNT(*) as nuevos_suscriptores
        FROM $tabla_suscripciones
        WHERE podcast_id = %d AND fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        GROUP BY DATE(fecha_suscripcion)
        ORDER BY fecha
    ", $podcast_seleccionado));
}

// Función para formatear duración
function formatear_duracion_stats($segundos) {
    if (empty($segundos)) return '0:00';
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    if ($horas > 0) {
        return sprintf('%d:%02d h', $horas, $minutos);
    }
    return sprintf('%d min', $minutos);
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-chart-bar"></span>
        <?php echo esc_html__('Estadísticas Detalladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <!-- Selector de podcast -->
    <div class="flavor-selector" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1;">
                <label for="podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Selecciona un Podcast:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="podcast_id" id="podcast_id" class="regular-text" style="width: 100%;">
                    <option value="0"><?php echo esc_html__('Seleccionar podcast...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($podcasts as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>" <?php selected($podcast_seleccionado, $podcast->id); ?>>
                            <?php echo esc_html($podcast->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-search"></span> <?php echo esc_html__('Ver Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </form>
    </div>

    <?php if ($podcast_seleccionado > 0 && $estadisticas_detalladas): ?>

        <!-- Información del podcast -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; gap: 20px; align-items: center;">
                <?php if (!empty($estadisticas_detalladas->imagen_url)): ?>
                    <img src="<?php echo esc_url($estadisticas_detalladas->imagen_url); ?>" alt="<?php echo esc_attr($estadisticas_detalladas->titulo); ?>" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover;">
                <?php endif; ?>
                <div style="flex: 1;">
                    <h2 style="margin: 0 0 10px 0;"><?php echo esc_html($estadisticas_detalladas->titulo); ?></h2>
                    <p style="color: #666; margin: 0;"><?php echo esc_html($estadisticas_detalladas->descripcion); ?></p>
                </div>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($estadisticas_detalladas->total_episodios); ?></h2>
            </div>

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Reproducciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($estadisticas_detalladas->total_reproducciones ?? 0); ?></h2>
            </div>

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #8c49d8;"><?php echo number_format($estadisticas_detalladas->suscriptores); ?></h2>
            </div>

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Me Gusta Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #d63638;"><?php echo number_format($estadisticas_detalladas->total_me_gusta ?? 0); ?></h2>
            </div>

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Duración Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #dba617;"><?php echo formatear_duracion_stats($estadisticas_detalladas->duracion_promedio); ?></h2>
            </div>

            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Promedio por Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;">
                    <?php
                    $promedio_reproducciones = $estadisticas_detalladas->total_episodios > 0
                        ? $estadisticas_detalladas->total_reproducciones / $estadisticas_detalladas->total_episodios
                        : 0;
                    echo number_format($promedio_reproducciones, 0);
                    ?>
                </h2>
            </div>

        </div>

        <!-- Gráfico de crecimiento -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-chart-line"></span>
                <?php echo esc_html__('Crecimiento de Suscriptores (90 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <canvas id="grafico-crecimiento" style="max-height: 300px;"></canvas>
        </div>

        <!-- Tabla de rendimiento por episodio -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('Rendimiento por Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th><?php echo esc_html__('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Reproducciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Me Gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Reprod. / Día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Engagement', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($episodios_stats)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-analytics" style="font-size: 48px; color: #ddd;"></span>
                                <p style="color: #666;"><?php echo esc_html__('No hay episodios publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($episodios_stats as $episodio): ?>
                            <?php
                            $engagement_rate = $episodio->reproducciones > 0
                                ? ($episodio->me_gusta / $episodio->reproducciones) * 100
                                : 0;
                            ?>
                            <tr>
                                <td style="text-align: center;"><strong><?php echo $episodio->numero_episodio; ?></strong></td>
                                <td><strong><?php echo esc_html($episodio->titulo); ?></strong></td>
                                <td style="text-align: center;">
                                    <strong style="color: #00a32a;"><?php echo number_format($episodio->reproducciones); ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <span class="dashicons dashicons-heart" style="color: #d63638;"></span>
                                    <?php echo number_format($episodio->me_gusta); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php echo number_format($episodio->reproducciones_por_dia, 1); ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="width: 100%; background: #f0f0f1; border-radius: 10px; height: 8px; overflow: hidden;">
                                        <div style="width: <?php echo min($engagement_rate * 10, 100); ?>%; height: 100%; background: <?php echo $engagement_rate > 5 ? '#00a32a' : ($engagement_rate > 2 ? '#dba617' : '#d63638'); ?>;"></div>
                                    </div>
                                    <small style="color: #666;"><?php echo number_format($engagement_rate, 1); ?>%</small>
                                </td>
                                <td><?php echo date_i18n('d/m/Y', strtotime($episodio->fecha_publicacion)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gráfico de crecimiento
            var ctxCrecimiento = document.getElementById('grafico-crecimiento').getContext('2d');
            new Chart(ctxCrecimiento, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($item) {
                        return date_i18n('d/m', strtotime($item->fecha));
                    }, $datos_temporales)); ?>,
                    datasets: [{
                        label: 'Nuevos Suscriptores',
                        data: <?php echo json_encode(array_map(function($item) {
                            return $item->nuevos_suscriptores;
                        }, $datos_temporales)); ?>,
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
        });
        </script>

    <?php elseif ($podcast_seleccionado > 0): ?>

        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; margin: 20px 0;">
            <span class="dashicons dashicons-warning" style="font-size: 64px; color: #dba617;"></span>
            <h3 style="color: #666;"><?php echo esc_html__('No se encontró el podcast seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>

    <?php else: ?>

        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; margin: 20px 0;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 64px; color: #ddd;"></span>
            <h3 style="color: #666;"><?php echo esc_html__('Selecciona un podcast para ver sus estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>

    <?php endif; ?>

</div>

<style>
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>

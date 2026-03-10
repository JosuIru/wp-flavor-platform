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
$tabla_series = $wpdb->prefix . 'flavor_podcast_series';
$tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
$tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

// Verificar existencia de tablas
$tabla_series_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_series)) === $tabla_series;
$tabla_episodios_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_episodios)) === $tabla_episodios;
$tabla_suscripciones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_suscripciones)) === $tabla_suscripciones;

// Inicializar variables
$total_podcasts = 0;
$total_episodios = 0;
$total_suscriptores = 0;
$total_reproducciones = 0;
$episodios_populares = [];
$datos_crecimiento = [];
$usando_demo = false;

if ($tabla_series_existe && $tabla_episodios_existe && $tabla_suscripciones_existe) {
    // Obtener estadisticas generales
    $total_podcasts = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_series WHERE estado = 'publicado'");
    $total_episodios = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_episodios WHERE estado = 'publicado'");
    $total_suscriptores = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_suscripciones");
    $total_reproducciones = (int) $wpdb->get_var("SELECT COALESCE(SUM(reproducciones), 0) FROM $tabla_episodios");

    // Episodios mas populares
    $episodios_populares = $wpdb->get_results("
        SELECT e.*, s.titulo as podcast_titulo
        FROM $tabla_episodios e
        INNER JOIN $tabla_series s ON e.serie_id = s.id
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
}

// Datos de ejemplo si no hay datos reales
if ($total_podcasts == 0 && $total_episodios == 0) {
    $usando_demo = true;
    $total_podcasts = 5;
    $total_episodios = 48;
    $total_suscriptores = 320;
    $total_reproducciones = 8750;

    $episodios_populares = [
        (object) ['titulo' => 'Entrevista: El Futuro de la Comunidad', 'numero_episodio' => 12, 'podcast_titulo' => 'Voces del Barrio', 'reproducciones' => 1250, 'me_gusta' => 89, 'fecha_publicacion' => date('Y-m-d', strtotime('-3 days'))],
        (object) ['titulo' => 'Historia Local: Los Orígenes', 'numero_episodio' => 8, 'podcast_titulo' => 'Memoria Colectiva', 'reproducciones' => 980, 'me_gusta' => 72, 'fecha_publicacion' => date('Y-m-d', strtotime('-1 week'))],
        (object) ['titulo' => 'Debate: Sostenibilidad Urbana', 'numero_episodio' => 5, 'podcast_titulo' => 'Eco Charlas', 'reproducciones' => 756, 'me_gusta' => 54, 'fecha_publicacion' => date('Y-m-d', strtotime('-2 weeks'))],
    ];

    // Datos de crecimiento de ejemplo
    for ($i = 29; $i >= 0; $i--) {
        $datos_crecimiento[] = (object) [
            'fecha' => date('Y-m-d', strtotime("-$i days")),
            'total' => rand(3, 15)
        ];
    }
}

// Estadísticas por plataforma (simulado)
$stats_plataforma = [
    'Spotify' => rand(30, 50),
    'Apple Podcasts' => rand(20, 35),
    'Google Podcasts' => rand(15, 25),
    'RSS Directo' => rand(10, 20),
    'Otros' => rand(5, 15)
];
?>

<div class="dm-dashboard">
    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-microphone"></span>
            <?php esc_html_e('Dashboard de Podcasts', 'flavor-chat-ia'); ?>
        </h1>
        <?php if ($usando_demo): ?>
            <span class="dm-badge dm-badge--warning"><?php esc_html_e('Datos de ejemplo', 'flavor-chat-ia'); ?></span>
        <?php endif; ?>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-action-grid dm-action-grid--3">
        <a href="<?php echo esc_url(admin_url('admin.php?page=podcast-series')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-playlist-audio dm-text-primary"></span>
            <span><?php esc_html_e('Series', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=podcast-episodios')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-microphone dm-text-success"></span>
            <span><?php esc_html_e('Episodios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=podcast-suscriptores')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-groups dm-text-purple"></span>
            <span><?php esc_html_e('Suscriptores', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/podcast/')); ?>" class="dm-action-card" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_podcasts); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Podcasts', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-microphone dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_episodios); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Episodios', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-playlist-audio dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_suscriptores); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Suscriptores', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_reproducciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Reproducciones', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-controls-play dm-stat-card__icon"></span>
        </div>
    </div>

    <div class="dm-grid dm-grid--2-1">
        <!-- Gráfico de crecimiento -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Crecimiento de Suscriptores (30 días)', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-crecimiento"></canvas>
            </div>
        </div>

        <!-- Distribución por plataforma -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Plataformas de Escucha', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-plataformas"></canvas>
            </div>
        </div>
    </div>

    <!-- Episodios más populares -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Episodios Más Populares', 'flavor-chat-ia'); ?>
            </h3>
        </div>

        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Pos.', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Episodio', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Podcast', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Reproducciones', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Me Gusta', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($episodios_populares)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="dm-empty">
                                <span class="dashicons dashicons-admin-media"></span>
                                <p><?php esc_html_e('No hay episodios publicados todavía', 'flavor-chat-ia'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($episodios_populares as $indice => $episodio): ?>
                        <tr>
                            <td>
                                <strong class="dm-text-primary"><?php echo esc_html($indice + 1); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html($episodio->titulo); ?></strong>
                                <div class="dm-table__subtitle">
                                    <?php echo esc_html(sprintf(__('Episodio #%d', 'flavor-chat-ia'), $episodio->numero_episodio)); ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($episodio->podcast_titulo); ?></td>
                            <td>
                                <span class="dashicons dashicons-controls-play dm-text-success"></span>
                                <?php echo number_format_i18n($episodio->reproducciones); ?>
                            </td>
                            <td>
                                <span class="dashicons dashicons-heart dm-text-error"></span>
                                <?php echo number_format_i18n($episodio->me_gusta ?? 0); ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($episodio->fecha_publicacion))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Obtener colores del tema
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#22c55e';
    var purpleColor = '#8b5cf6';
    var errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';

    // Gráfico de crecimiento de suscriptores
    var ctxCrecimiento = document.getElementById('grafico-crecimiento');
    if (ctxCrecimiento) {
        new Chart(ctxCrecimiento.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($item) {
                    return date_i18n('d/m', strtotime($item->fecha));
                }, $datos_crecimiento)); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Nuevos Suscriptores', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode(array_map(function($item) {
                        return $item->total;
                    }, $datos_crecimiento)); ?>,
                    borderColor: purpleColor,
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
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
                        },
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

    // Gráfico de plataformas
    var ctxPlataformas = document.getElementById('grafico-plataformas');
    if (ctxPlataformas) {
        new Chart(ctxPlataformas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode(array_keys($stats_plataforma)); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode(array_values($stats_plataforma)); ?>,
                    backgroundColor: [
                        primaryColor,
                        successColor,
                        purpleColor,
                        errorColor,
                        warningColor
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php
/**
 * Vista Dashboard - Módulo Presupuestos Participativos
 *
 * Panel de control con estadísticas de presupuestos participativos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_propuestas';
$tabla_votos_pp = $wpdb->prefix . 'flavor_pp_votos';
$tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';

// Verificar existencia de tablas
$tabla_proyectos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_proyectos)) === $tabla_proyectos;
$tabla_votos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_votos_pp)) === $tabla_votos_pp;
$tabla_categorias_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_categorias)) === $tabla_categorias;

// Inicializar variables
$presupuesto_total = 500000;
$presupuesto_asignado = 0;
$total_proyectos = 0;
$proyectos_votacion = 0;
$proyectos_aprobados = 0;
$total_votos_pp = 0;
$votantes_unicos_pp = 0;
$stats_proyectos_estado = [];
$proyectos_mas_votados = [];
$distribucion_categoria = [];
$tendencia_votos = [];
$tablas_disponibles = ($tabla_proyectos_existe && $tabla_votos_existe);

if ($tabla_proyectos_existe && $tabla_votos_existe) {
    $presupuesto_asignado = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto_estimado), 0) FROM $tabla_proyectos WHERE estado = 'aprobada'");
    $total_proyectos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
    $proyectos_votacion = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'en_votacion'");
    $proyectos_aprobados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'aprobada'");
    $total_votos_pp = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos_pp");
    $votantes_unicos_pp = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos_pp");

    $stats_proyectos_estado = $wpdb->get_results("
        SELECT estado, COUNT(*) as total
        FROM $tabla_proyectos
        GROUP BY estado
    ");

    $proyectos_mas_votados = $wpdb->get_results("
        SELECT p.*, COUNT(v.id) as total_votos
        FROM $tabla_proyectos p
        LEFT JOIN $tabla_votos_pp v ON p.id = v.propuesta_id
        WHERE p.estado = 'en_votacion'
        GROUP BY p.id
        ORDER BY total_votos DESC
        LIMIT 5
    ");

    if ($tabla_categorias_existe) {
        $distribucion_categoria = $wpdb->get_results("
            SELECT COALESCE(c.nombre, 'Sin categoría') as categoria, SUM(p.presupuesto_estimado) as total_presupuesto
            FROM $tabla_proyectos p
            LEFT JOIN $tabla_categorias c ON p.categoria_id = c.id
            WHERE p.estado IN ('en_votacion', 'aprobada')
            GROUP BY p.categoria_id, c.nombre
            ORDER BY total_presupuesto DESC
            LIMIT 6
        ");
    }

    $tendencia_votos = $wpdb->get_results("
        SELECT DATE(fecha_voto) as fecha, COUNT(*) as total
        FROM $tabla_votos_pp
        WHERE fecha_voto >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(fecha_voto)
        ORDER BY fecha ASC
    ");
}

$presupuesto_disponible = $presupuesto_total - $presupuesto_asignado;
$porcentaje_asignado = $presupuesto_total > 0 ? round(($presupuesto_asignado / $presupuesto_total) * 100, 1) : 0;

// Preparar datos para gráficos
$tendencia_labels = array_map(function($t) {
    return date_i18n('d/m', strtotime($t->fecha));
}, $tendencia_votos);
$tendencia_data = array_map(function($t) { return (int) $t->total; }, $tendencia_votos);

$estado_labels = array_map(function($e) {
    $nombres = ['borrador' => 'Borrador', 'en_votacion' => 'En votación', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'];
    return $nombres[$e->estado] ?? ucfirst(str_replace('_', ' ', $e->estado));
}, $stats_proyectos_estado);
$estado_data = array_map(function($e) { return (int) $e->total; }, $stats_proyectos_estado);

$categoria_labels = array_column($distribucion_categoria, 'categoria');
$categoria_data = array_column($distribucion_categoria, 'total_presupuesto');
?>

<div class="dm-dashboard">
    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Presupuestos Participativos o aún no hay proyectos/votos.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-money-alt"></span>
            <?php esc_html_e('Dashboard de Presupuestos Participativos', 'flavor-chat-ia'); ?>
        </h1>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-action-grid dm-action-grid--4">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pp-proyectos')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-portfolio dm-text-primary"></span>
            <span><?php esc_html_e('Proyectos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pp-presupuesto')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-money-alt dm-text-success"></span>
            <span><?php esc_html_e('Presupuesto', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pp-votos')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-chart-pie dm-text-purple"></span>
            <span><?php esc_html_e('Votos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pp-resultados')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-chart-bar dm-text-warning"></span>
            <span><?php esc_html_e('Resultados', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/presupuestos-participativos/')); ?>" class="dm-action-card" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Métricas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($presupuesto_total); ?> €</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Presupuesto Total', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('para el ejercicio actual', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-money-alt dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($presupuesto_asignado); ?> €</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Presupuesto Asignado', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php echo esc_html($porcentaje_asignado); ?>% del total</div>
            <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($proyectos_votacion); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Proyectos en Votación', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('de %s totales', 'flavor-chat-ia'), number_format_i18n($total_proyectos)); ?></div>
            <span class="dashicons dashicons-portfolio dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($votantes_unicos_pp); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participación', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('ciudadanos han votado', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        </div>
    </div>

    <!-- Indicador de presupuesto -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-money-alt"></span>
                <?php esc_html_e('Estado del Presupuesto', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-budget-indicator">
            <div class="dm-budget-indicator__values">
                <div class="dm-budget-indicator__item">
                    <span class="dm-budget-indicator__label"><?php esc_html_e('Total Disponible', 'flavor-chat-ia'); ?></span>
                    <span class="dm-budget-indicator__value dm-text-success"><?php echo number_format_i18n($presupuesto_total); ?> €</span>
                </div>
                <div class="dm-budget-indicator__item">
                    <span class="dm-budget-indicator__label"><?php esc_html_e('Asignado', 'flavor-chat-ia'); ?></span>
                    <span class="dm-budget-indicator__value dm-text-primary"><?php echo number_format_i18n($presupuesto_asignado); ?> €</span>
                </div>
                <div class="dm-budget-indicator__item">
                    <span class="dm-budget-indicator__label"><?php esc_html_e('Disponible', 'flavor-chat-ia'); ?></span>
                    <span class="dm-budget-indicator__value dm-text-warning"><?php echo number_format_i18n($presupuesto_disponible); ?> €</span>
                </div>
            </div>
            <div class="dm-progress dm-progress--lg">
                <div class="dm-progress__fill dm-progress__fill--gradient" style="width: <?php echo esc_attr($porcentaje_asignado); ?>%;">
                    <span class="dm-progress__label"><?php echo esc_html($porcentaje_asignado); ?>% Asignado</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Tendencia de Votación (14 días)', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-tendencia-votos"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Proyectos por Estado', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-proyectos-estado"></canvas>
            </div>
        </div>
    </div>

    <!-- Distribución y proyectos más votados -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Distribución Presupuestaria por Categoría', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-distribucion-categoria"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Proyectos Más Votados', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <?php if (!empty($proyectos_mas_votados)): ?>
                <div class="dm-ranking">
                    <?php foreach ($proyectos_mas_votados as $indice => $proyecto): ?>
                        <div class="dm-ranking__item">
                            <span class="dm-ranking__number"><?php echo $indice + 1; ?></span>
                            <div class="dm-ranking__content">
                                <span class="dm-ranking__label"><?php echo esc_html($proyecto->titulo); ?></span>
                                <span class="dm-ranking__value dm-text-success"><?php echo number_format_i18n($proyecto->presupuesto_estimado); ?> €</span>
                            </div>
                            <div class="dm-ranking__votes">
                                <span class="dm-text-primary"><?php echo number_format_i18n($proyecto->total_votos); ?></span>
                                <small class="dm-text-muted"><?php esc_html_e('votos', 'flavor-chat-ia'); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-portfolio"></span>
                    <p><?php esc_html_e('No hay proyectos en votación', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado');
        return;
    }

    // Obtener colores del tema
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#22c55e';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    var errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';

    // Gráfico de tendencia de votos
    var ctxTendencia = document.getElementById('chart-tendencia-votos');
    if (ctxTendencia) {
        new Chart(ctxTendencia.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode($tendencia_labels); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Votos', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode($tendencia_data); ?>,
                    borderColor: successColor,
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Gráfico de proyectos por estado
    var ctxEstado = document.getElementById('chart-proyectos-estado');
    if (ctxEstado) {
        new Chart(ctxEstado.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode($estado_labels); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode($estado_data); ?>,
                    backgroundColor: [warningColor, primaryColor, successColor, errorColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15 }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Gráfico de distribución por categoría
    var ctxDistribucion = document.getElementById('chart-distribucion-categoria');
    if (ctxDistribucion) {
        new Chart(ctxDistribucion.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode($categoria_labels); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Presupuesto (€)', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode($categoria_data); ?>,
                    backgroundColor: successColor,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('es-ES', {
                                    style: 'currency',
                                    currency: 'EUR',
                                    minimumFractionDigits: 0
                                }).format(context.parsed.x);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

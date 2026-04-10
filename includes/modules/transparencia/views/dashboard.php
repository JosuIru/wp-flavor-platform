<?php
/**
 * Vista Dashboard - Modulo Transparencia
 *
 * Panel de control con estadísticas de datos públicos y solicitudes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

$tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';
$tabla_solicitudes = $wpdb->prefix . 'flavor_transparencia_solicitudes';
$tabla_categorias = $wpdb->prefix . 'flavor_transparencia_categorias';

// Verificar existencia de tablas
$tabla_datos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_datos)) === $tabla_datos;
$tabla_solicitudes_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_solicitudes)) === $tabla_solicitudes;
$tablas_disponibles = ($tabla_datos_existe || $tabla_solicitudes_existe);

// Inicializar estadísticas
$total_datos_publicados = 0;
$total_solicitudes = 0;
$solicitudes_pendientes = 0;
$solicitudes_resueltas_mes = 0;
$tiempo_promedio_resolucion = 0;
$tasa_resolucion = 0;
$descargas_documentos = 0;
$estadisticas_estado_solicitudes = [];
$tendencia_publicaciones_semana = [];
$solicitudes_recientes = [];
$datos_recientes = [];

if ($tabla_datos_existe) {
    $total_datos_publicados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_datos WHERE estado = 'publicado'");

    // Tendencia últimos 7 días
    $tendencia_publicaciones_semana = $wpdb->get_results("
        SELECT DATE(fecha_publicacion) as fecha, COUNT(*) as total
        FROM $tabla_datos
        WHERE fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND estado = 'publicado'
        GROUP BY DATE(fecha_publicacion)
        ORDER BY fecha ASC
    ");

    // Datos recientes
    $datos_recientes = $wpdb->get_results("
        SELECT d.*, u.display_name as publicado_por
        FROM $tabla_datos d
        LEFT JOIN {$wpdb->users} u ON d.usuario_id = u.ID
        WHERE d.estado = 'publicado'
        ORDER BY d.fecha_publicacion DESC
        LIMIT 5
    ");

    // Descargas de documentos
    $descargas_documentos = (int) $wpdb->get_var("SELECT COALESCE(SUM(descargas), 0) FROM $tabla_datos");
}

if ($tabla_solicitudes_existe) {
    $total_solicitudes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
    $solicitudes_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'en_tramite')");
    $solicitudes_resueltas_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE())");

    // Tiempo promedio de resolución
    $tiempo_promedio_resolucion = (float) $wpdb->get_var("
        SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud))
        FROM $tabla_solicitudes
        WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL
    ");

    // Por estado
    $estadisticas_estado_solicitudes = $wpdb->get_results("
        SELECT estado, COUNT(*) as total
        FROM $tabla_solicitudes
        GROUP BY estado
    ");

    // Tasa de resolución
    $total_tramitadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('resuelta', 'denegada')");
    $total_resueltas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'");
    $tasa_resolucion = $total_tramitadas > 0 ? round(($total_resueltas / $total_tramitadas) * 100, 1) : 0;

    // Solicitudes recientes
    $solicitudes_recientes = $wpdb->get_results("
        SELECT s.*, u.display_name as solicitante_nombre
        FROM $tabla_solicitudes s
        LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
        ORDER BY s.fecha_solicitud DESC
        LIMIT 5
    ");
}

// Preparar datos para gráficos
$tendencia_labels = array_map(function($t) {
    return date_i18n('D', strtotime($t->fecha));
}, $tendencia_publicaciones_semana);
$tendencia_data = array_map(function($t) { return (int) $t->total; }, $tendencia_publicaciones_semana);

$estado_labels = array_map(function($e) {
    $nombres = ['recibida' => 'Recibida', 'en_tramite' => 'En trámite', 'resuelta' => 'Resuelta', 'denegada' => 'Denegada'];
    return $nombres[$e->estado] ?? ucfirst(str_replace('_', ' ', $e->estado));
}, $estadisticas_estado_solicitudes);
$estado_data = array_map(function($e) { return (int) $e->total; }, $estadisticas_estado_solicitudes);

// Mapeo de estados a clases de badge
$estado_badge_classes = [
    'recibida' => 'dm-badge--warning',
    'en_tramite' => 'dm-badge--info',
    'resuelta' => 'dm-badge--success',
    'denegada' => 'dm-badge--error',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('transparencia');
    }
    ?>

    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-visibility"></span>
            <?php esc_html_e('Dashboard de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h1>
    </div>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Transparencia o aún no hay datos/solicitudes registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($is_dashboard_viewer): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Vista resumida para gestor de grupos. La gestión de datos y solicitudes sigue reservada a administración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($solicitudes_pendientes > 10 && !$is_dashboard_viewer): ?>
        <div class="dm-alert dm-alert--warning">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <?php printf(
                    esc_html__('Hay %d solicitudes pendientes de tramitar. Se recomienda revisarlas pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $solicitudes_pendientes
                ); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitudes')); ?>" class="dm-btn dm-btn--sm dm-btn--warning" style="margin-left: 10px;">
                    <?php esc_html_e('Ver solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="dm-action-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-datos')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-media-spreadsheet dm-text-primary"></span>
            <span><?php esc_html_e('Datos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitudes')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-clipboard dm-text-warning"></span>
            <span><?php esc_html_e('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-publicar')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-upload dm-text-success"></span>
            <span><?php esc_html_e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-external dm-text-purple"></span>
            <span><?php esc_html_e('Ver Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-informes')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-chart-bar dm-text-error"></span>
            <span><?php esc_html_e('Informes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <?php if (!$is_dashboard_viewer): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-configuracion')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-admin-settings dm-text-muted"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Métricas principales -->
    <div class="dm-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_datos_publicados); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Datos Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-media-spreadsheet dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_pendientes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Solicitudes Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('de %s totales', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_solicitudes)); ?></div>
            <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_resueltas_mes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Resueltas (Mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta dm-text-success"><?php printf(esc_html__('Tasa: %s%%', FLAVOR_PLATFORM_TEXT_DOMAIN), $tasa_resolucion); ?></div>
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n(round($tiempo_promedio_resolucion, 1)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Tiempo Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('días de resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-calendar-alt dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($descargas_documentos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-download dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_solicitudes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('históricas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-clipboard dm-stat-card__icon"></span>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <!-- Gráfico de tendencia -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Publicaciones Últimos 7 Días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-tendencia"></canvas>
            </div>
        </div>

        <!-- Gráfico de estados -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Solicitudes por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-estados"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Solicitudes recientes -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Solicitudes Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($solicitudes_recientes)): ?>
                        <?php foreach ($solicitudes_recientes as $solicitud): ?>
                            <?php $badge_class = $estado_badge_classes[$solicitud->estado] ?? 'dm-badge--secondary'; ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo esc_html($solicitud->id); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html(wp_trim_words($solicitud->titulo ?? '', 6)); ?></div>
                                </td>
                                <td class="dm-table__muted"><?php echo esc_html($solicitud->solicitante_nombre ?? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $solicitud->estado))); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                <div class="dm-empty">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <p><?php esc_html_e('No hay solicitudes recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Datos recientes -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php esc_html_e('Últimas Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($datos_recientes)): ?>
                        <?php foreach ($datos_recientes as $dato): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words($dato->titulo ?? '', 5)); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html(human_time_diff(strtotime($dato->fecha_publicacion), current_time('timestamp'))); ?></div>
                                </td>
                                <td>
                                    <span class="dm-badge dm-badge--info">
                                        <?php echo esc_html(ucfirst($dato->categoria ?? 'Otros')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-download dm-text-muted"></span>
                                    <?php echo number_format_i18n($dato->descargas ?? 0); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                <div class="dm-empty">
                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                    <p><?php esc_html_e('No hay datos publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Verificar que Chart.js esté disponible
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

    // Gráfico de tendencia (Línea)
    var ctxTendencia = document.getElementById('chart-tendencia');
    if (ctxTendencia) {
        new Chart(ctxTendencia.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode($tendencia_labels); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                    data: <?php echo wp_json_encode($tendencia_data); ?>,
                    borderColor: primaryColor,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: primaryColor,
                    pointRadius: 4,
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
                            stepSize: 1
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

    // Gráfico de estados (Doughnut)
    var ctxEstados = document.getElementById('chart-estados');
    if (ctxEstados) {
        new Chart(ctxEstados.getContext('2d'), {
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
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>

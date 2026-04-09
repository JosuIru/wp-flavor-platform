<?php
/**
 * Dashboard de Ayuda Vecinal
 * Vista general de solicitudes y voluntarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

global $wpdb;
$tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
$tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_voluntarios';

// Verificar si las tablas existen
$tabla_solicitudes_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_solicitudes)) === $tabla_solicitudes;
$tabla_voluntarios_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_voluntarios)) === $tabla_voluntarios;
$tablas_disponibles = ($tabla_solicitudes_existe && $tabla_voluntarios_existe);

$fecha_inicio_mes = date('Y-m-01 00:00:00');

// Valores por defecto
$solicitudes_activas = 0;
$voluntarios_activos = 0;
$ayudas_completadas = 0;
$horas_voluntariado = 0;
$solicitudes_urgentes = [];
$voluntarios_destacados = [];
$actividad_reciente = [];
$categorias_data = [];
$tendencia_labels = [];
$tendencia_values = [];

if ($tabla_solicitudes_existe && $tabla_voluntarios_existe) {
    $solicitudes_activas = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'pendiente'"
    );

    $voluntarios_activos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_voluntarios} WHERE estado = 'activo' AND ultima_actividad >= %s",
        $fecha_inicio_mes
    ));

    $ayudas_completadas = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'completada' AND fecha_completada >= %s",
        $fecha_inicio_mes
    ));

    $horas_voluntariado = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(horas_estimadas), 0) FROM {$tabla_solicitudes}
         WHERE estado = 'completada' AND fecha_completada >= %s",
        $fecha_inicio_mes
    ));

    // Solicitudes urgentes
    $solicitudes_urgentes = $wpdb->get_results(
        "SELECT id, titulo, categoria, fecha_creacion, usuario_id
         FROM {$tabla_solicitudes}
         WHERE estado = 'pendiente' AND urgente = 1
         ORDER BY fecha_creacion DESC
         LIMIT 5"
    );

    // Voluntarios destacados
    $voluntarios_destacados = $wpdb->get_results(
        "SELECT v.id, v.usuario_id, v.ayudas_completadas, v.valoracion_promedio
         FROM {$tabla_voluntarios} v
         WHERE v.estado = 'activo'
         ORDER BY v.ayudas_completadas DESC
         LIMIT 5"
    );

    // Categorías
    $categorias_data = $wpdb->get_results(
        "SELECT categoria, COUNT(*) as total
         FROM {$tabla_solicitudes}
         WHERE estado IN ('pendiente', 'en_proceso', 'completada')
         GROUP BY categoria
         ORDER BY total DESC"
    );

    // Tendencia de solicitudes de los últimos 7 días
    $tendencia_sql = $wpdb->get_results(
        "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
         FROM {$tabla_solicitudes}
         WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(fecha_creacion)
         ORDER BY fecha ASC"
    );
    $tendencia_map = [];
    foreach ($tendencia_sql as $row) {
        $tendencia_map[$row->fecha] = (int) $row->total;
    }
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-{$i} days"));
        $tendencia_labels[] = date_i18n('D', strtotime($fecha));
        $tendencia_values[] = $tendencia_map[$fecha] ?? 0;
    }
}

// Preparar datos para gráficos
$categorias_labels = array_map(function($c) { return $c->categoria; }, $categorias_data);
$categorias_values = array_map(function($c) { return (int) $c->total; }, $categorias_data);
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('ayuda_vecinal');
    }
    ?>

    <!-- Header -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-heart" style="font-size: 28px; color: #ef4444;"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
                <p><?php esc_html_e('Conectando vecinos que necesitan ayuda con quienes pueden ofrecerla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-nueva-solicitud')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <?php if (!$tablas_disponibles) : ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('Faltan tablas del módulo Ayuda Vecinal o aún no hay actividad registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="dm-quick-links">
        <h3 class="dm-quick-links__title"><?php esc_html_e('Acceso Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-solicitudes')); ?>" class="dm-quick-links__item dm-quick-links__item--error">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-voluntarios')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-matches')); ?>" class="dm-quick-links__item dm-quick-links__item--primary">
                <span class="dashicons dashicons-randomize"></span>
                <?php esc_html_e('Matches', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-estadisticas')); ?>" class="dm-quick-links__item dm-quick-links__item--info">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', '')); ?>" class="dm-quick-links__item" target="_blank">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-sos"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($solicitudes_activas)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Solicitudes Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('pendientes de asignación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($voluntarios_activos)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Voluntarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('disponibles este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($ayudas_completadas)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Ayudas Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($horas_voluntariado)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Horas de Voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('impacto social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Solicitudes por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-categorias"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Tendencia Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-tendencia"></canvas>
            </div>
        </div>
    </div>

    <!-- Solicitudes urgentes y voluntarios destacados -->
    <div class="dm-grid dm-grid--2">
        <!-- Solicitudes urgentes -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-warning" style="color: #ef4444;"></span>
                    <?php esc_html_e('Solicitudes Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ayuda-solicitudes&urgente=1')); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                    <?php esc_html_e('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php if (!empty($solicitudes_urgentes)) : ?>
            <div class="dm-urgent-list">
                <?php foreach ($solicitudes_urgentes as $solicitud) : ?>
                <div class="dm-urgent-item">
                    <div class="dm-urgent-item__content">
                        <strong><?php echo esc_html($solicitud->titulo); ?></strong>
                        <span class="dm-badge dm-badge--secondary"><?php echo esc_html($solicitud->categoria); ?></span>
                    </div>
                    <div class="dm-urgent-item__time">
                        <?php echo esc_html(human_time_diff(strtotime($solicitud->fecha_creacion), current_time('timestamp'))); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-yes-alt" style="color: var(--dm-success);"></span>
                <p><?php esc_html_e('No hay solicitudes urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Voluntarios destacados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-star-filled" style="color: #f59e0b;"></span>
                    <?php esc_html_e('Voluntarios Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <?php if (!empty($voluntarios_destacados)) : ?>
            <ol class="dm-ranking">
                <?php foreach ($voluntarios_destacados as $voluntario) :
                    $usuario = get_userdata($voluntario->usuario_id);
                    $nombre = $usuario ? $usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $ayudas = $voluntario->ayudas_completadas;
                    $valoracion = $voluntario->valoracion_promedio ?? 0;
                ?>
                <li>
                    <span><?php echo esc_html($nombre); ?></span>
                    <div>
                        <strong><?php echo esc_html($ayudas); ?> <?php esc_html_e('ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span class="dm-rating dm-text-warning"><?php echo esc_html(number_format_i18n($valoracion, 1)); ?> ★</span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-groups"></span>
                <p><?php esc_html_e('No hay voluntarios registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actividad reciente -->
    <?php if (!empty($actividad_reciente)) : ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-timeline">
            <?php foreach ($actividad_reciente as $actividad) :
                $icon_class = $actividad->tipo === 'completada' ? 'success' : ($actividad->tipo === 'asignada' ? 'warning' : 'info');
                $icon = $actividad->tipo === 'completada' ? 'yes' : ($actividad->tipo === 'asignada' ? 'admin-users' : 'plus');
            ?>
            <div class="dm-timeline__item">
                <div class="dm-timeline__icon dm-timeline__icon--<?php echo esc_attr($icon_class); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
                </div>
                <div class="dm-timeline__content">
                    <div class="dm-timeline__title"><?php echo esc_html($actividad->titulo); ?></div>
                    <div class="dm-timeline__meta"><?php echo esc_html($actividad->descripcion); ?> · <?php echo esc_html($actividad->tiempo); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    var errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';
    var purpleColor = '#8b5cf6';
    var pinkColor = '#ec4899';

    // Gráfico de categorías (donut)
    var categoriasLabels = <?php echo wp_json_encode($categorias_labels); ?>;
    var categoriasValues = <?php echo wp_json_encode($categorias_values); ?>;

    var ctx1 = document.getElementById('grafico-categorias');
    if (ctx1 && typeof Chart !== 'undefined') {
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: categoriasLabels,
                datasets: [{
                    data: categoriasValues,
                    backgroundColor: [primaryColor, successColor, warningColor, errorColor, purpleColor, pinkColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Gráfico de tendencia
    var tendenciaLabels = <?php echo wp_json_encode($tendencia_labels); ?>;
    var tendenciaValues = <?php echo wp_json_encode($tendencia_values); ?>;

    var ctx2 = document.getElementById('grafico-tendencia');
    if (ctx2 && typeof Chart !== 'undefined') {
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: tendenciaLabels,
                datasets: [{
                    label: '<?php echo esc_js(__('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                    data: tendenciaValues,
                    borderColor: primaryColor,
                    backgroundColor: primaryColor + '1a',
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
});
</script>

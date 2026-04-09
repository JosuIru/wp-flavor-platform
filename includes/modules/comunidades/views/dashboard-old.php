<?php
/**
 * Dashboard de Comunidades - Vista Admin
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas del módulo
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
$tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_publicaciones';
$tabla_eventos = $wpdb->prefix . 'flavor_comunidades_eventos';

// Verificar existencia de tablas
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_comunidades)) === $tabla_comunidades;
$tabla_miembros_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_miembros)) === $tabla_miembros;
$tabla_publicaciones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_publicaciones)) === $tabla_publicaciones;
$tabla_eventos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_eventos)) === $tabla_eventos;

// Inicializar variables
$total_comunidades = 0;
$activas = 0;
$miembros_activos = 0;
$nuevos_miembros_semana = 0;
$publicaciones_hoy = 0;
$total_publicaciones = 0;
$eventos_programados = 0;
$sin_actividad = 0;
$por_categoria = [];
$comunidades_activas = [];
$actividad_reciente = [];
$crecimiento_miembros = [];
$tablas_disponibles = $tabla_existe;

if ($tabla_existe) {
    $total_comunidades = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comunidades}");
    $activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comunidades} WHERE estado = 'activa'");

    if ($tabla_miembros_existe) {
        $miembros_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$tabla_miembros} WHERE estado = 'activo'");
        $nuevos_miembros_semana = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE fecha_union >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    }

    if ($tabla_publicaciones_existe) {
        $publicaciones_hoy = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE DATE(created_at) = CURDATE()");
        $total_publicaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_publicaciones}");
    }

    if ($tabla_eventos_existe) {
        $eventos_programados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos} WHERE fecha >= CURDATE() AND estado != 'cancelado'");
    }

    $por_categoria = $wpdb->get_results("
        SELECT categoria, COUNT(*) as total
        FROM {$tabla_comunidades}
        GROUP BY categoria
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    $comunidades_activas = $tabla_miembros_existe ? $wpdb->get_results("
        SELECT c.id, c.nombre, c.categoria, c.privacidad, COUNT(m.id) as num_miembros
        FROM {$tabla_comunidades} c
        LEFT JOIN {$tabla_miembros} m ON c.id = m.comunidad_id AND m.estado = 'activo'
        WHERE c.estado = 'activa'
        GROUP BY c.id
        ORDER BY num_miembros DESC
        LIMIT 5
    ", ARRAY_A) : [];

    $actividad_reciente = $tabla_publicaciones_existe ? $wpdb->get_results("
        SELECT p.id, p.contenido, p.created_at, c.nombre as comunidad_nombre,
               u.display_name as autor_nombre
        FROM {$tabla_publicaciones} p
        LEFT JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        ORDER BY p.created_at DESC
        LIMIT 5
    ", ARRAY_A) : [];

    $crecimiento_miembros = $tabla_miembros_existe ? $wpdb->get_results("
        SELECT DATE_FORMAT(fecha_union, '%Y-%m') as mes, COUNT(*) as nuevos
        FROM {$tabla_miembros}
        WHERE fecha_union >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes ASC
    ", ARRAY_A) : [];

    if ($tabla_publicaciones_existe) {
        $sin_actividad = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM {$tabla_comunidades} c
            WHERE c.estado = 'activa'
            AND NOT EXISTS (
                SELECT 1 FROM {$tabla_publicaciones} p
                WHERE p.comunidad_id = c.id
                AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
        ");
    }
}

// Labels y mapeos
$categorias_labels = [
    'vecinal' => 'Vecinal', 'deportiva' => 'Deportiva', 'cultural' => 'Cultural',
    'educativa' => 'Educativa', 'social' => 'Social', 'profesional' => 'Profesional', 'otra' => 'Otra',
];

$categoria_badge_classes = [
    'vecinal' => 'dm-badge--primary', 'deportiva' => 'dm-badge--success', 'cultural' => 'dm-badge--purple',
    'educativa' => 'dm-badge--warning', 'social' => 'dm-badge--pink', 'profesional' => 'dm-badge--info', 'otra' => 'dm-badge--secondary',
];

$privacidad_labels = ['publica' => 'Pública', 'privada' => 'Privada', 'secreta' => 'Secreta'];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('comunidades');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Comunidades o aún no hay actividad registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </div>
    <?php endif; ?>

    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-admin-multisite"></span>
            <?php esc_html_e('Dashboard de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-listado&accion=nueva')); ?>" class="dm-btn dm-btn--primary">
            <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <?php if ($sin_actividad > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <?php printf(
                esc_html__('Hay %d comunidad(es) sin actividad en los últimos 30 días.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $sin_actividad
            ); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-listado&filtro=inactivas')); ?>" class="dm-btn dm-btn--sm dm-btn--warning" style="margin-left: 10px;">
                <?php esc_html_e('Revisar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="dm-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_comunidades); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-admin-multisite dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($miembros_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Miembros Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($publicaciones_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Publicaciones Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-format-chat dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($eventos_programados); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Eventos Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-calendar-alt dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($nuevos_miembros_semana); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Nuevos (7 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-plus-alt dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_publicaciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <span class="dashicons dashicons-text-page dm-stat-card__icon"></span>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-action-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
        <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-listado')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-admin-multisite dm-text-primary"></span>
            <span><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-miembros')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-groups dm-text-purple"></span>
            <span><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-publicaciones')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-format-chat dm-text-warning"></span>
            <span><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('comunidades', '')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-external dm-text-pink"></span>
            <span><?php esc_html_e('Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-config')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-admin-settings dm-text-muted"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Distribución por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-categorias"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Crecimiento de Miembros (6 meses)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-crecimiento"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <?php esc_html_e('Comunidades Más Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($comunidades_activas)): ?>
                        <?php foreach ($comunidades_activas as $comunidad): ?>
                        <?php $badge_class = $categoria_badge_classes[$comunidad['categoria']] ?? 'dm-badge--secondary'; ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=comunidades-editar&id=' . $comunidad['id'])); ?>">
                                    <strong><?php echo esc_html($comunidad['nombre']); ?></strong>
                                </a>
                                <div class="dm-table__subtitle">
                                    <?php echo esc_html($privacidad_labels[$comunidad['privacidad']] ?? $comunidad['privacidad']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                    <?php echo esc_html($categorias_labels[$comunidad['categoria']] ?? $comunidad['categoria']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format_i18n($comunidad['num_miembros']); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                <div class="dm-empty">
                                    <span class="dashicons dashicons-admin-multisite"></span>
                                    <p><?php esc_html_e('No hay comunidades registradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <?php if (!empty($actividad_reciente)): ?>
            <div class="dm-item-list">
                <?php foreach ($actividad_reciente as $actividad): ?>
                <div class="dm-item-list__item">
                    <div class="dm-item-list__content">
                        <strong><?php echo esc_html($actividad['autor_nombre']); ?></strong>
                        <p><?php echo esc_html(wp_trim_words($actividad['contenido'], 12)); ?></p>
                        <span class="dm-item-list__muted">
                            <?php esc_html_e('en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <strong><?php echo esc_html($actividad['comunidad_nombre']); ?></strong>
                        </span>
                    </div>
                    <div class="dm-item-list__meta">
                        <?php echo esc_html(human_time_diff(strtotime($actividad['created_at']), current_time('timestamp'))); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-format-chat"></span>
                <p><?php esc_html_e('No hay actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
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

    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#22c55e';
    var purpleColor = '#8b5cf6';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    var pinkColor = '#ec4899';

    var categoriasData = <?php echo wp_json_encode(array_map(function($c) use ($categorias_labels) {
        return [
            'label' => $categorias_labels[$c['categoria']] ?? $c['categoria'],
            'value' => (int) $c['total']
        ];
    }, $por_categoria)); ?>;

    var crecimientoData = <?php echo wp_json_encode(array_map(function($m) {
        return [
            'mes' => date_i18n('M Y', strtotime($m['mes'] . '-01')),
            'value' => (int) $m['nuevos']
        ];
    }, $crecimiento_miembros)); ?>;

    // Gráfico por categoría
    var ctxCat = document.getElementById('chart-categorias');
    if (ctxCat) {
        new Chart(ctxCat.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: categoriasData.map(function(c) { return c.label; }),
                datasets: [{
                    data: categoriasData.map(function(c) { return c.value; }),
                    backgroundColor: [primaryColor, successColor, purpleColor, warningColor, pinkColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Gráfico de crecimiento
    var ctxCrec = document.getElementById('chart-crecimiento');
    if (ctxCrec) {
        new Chart(ctxCrec.getContext('2d'), {
            type: 'line',
            data: {
                labels: crecimientoData.map(function(c) { return c.mes; }),
                datasets: [{
                    label: '<?php esc_attr_e('Nuevos miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                    data: crecimientoData.map(function(c) { return c.value; }),
                    borderColor: purpleColor,
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.3,
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
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

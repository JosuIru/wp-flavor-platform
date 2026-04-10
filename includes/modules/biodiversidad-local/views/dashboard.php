<?php
/**
 * Vista Dashboard - Biodiversidad Local
 *
 * Dashboard administrativo para gestión de especies, avistamientos y proyectos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_especies = $wpdb->prefix . 'flavor_biodiversidad_especies';
$tabla_avistamientos = $wpdb->prefix . 'flavor_biodiversidad_avistamientos';
$tabla_proyectos = $wpdb->prefix . 'flavor_biodiversidad_proyectos';
$tabla_participantes = $wpdb->prefix . 'flavor_biodiversidad_participantes';

// Verificar si las tablas existen
$tabla_especies_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_especies)) === $tabla_especies;

if (!$tabla_especies_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Biodiversidad Local aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_especies = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_especies}");
$especies_verificadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_especies} WHERE verificada = 1");

$tabla_avistamientos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_avistamientos)) === $tabla_avistamientos;
$total_avistamientos = $tabla_avistamientos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_avistamientos}") : 0;
$avistamientos_pendientes = $tabla_avistamientos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_avistamientos} WHERE estado = 'pendiente'") : 0;
$avistamientos_verificados = $tabla_avistamientos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_avistamientos} WHERE estado = 'verificado'") : 0;
$avistamientos_mes = $tabla_avistamientos_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_avistamientos} WHERE fecha_avistamiento >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

$tabla_proyectos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_proyectos)) === $tabla_proyectos;
$total_proyectos = $tabla_proyectos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos}") : 0;
$proyectos_activos = $tabla_proyectos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado = 'activo'") : 0;

$tabla_participantes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_participantes)) === $tabla_participantes;
$total_observadores = $tabla_avistamientos_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_avistamientos}") : 0;

// Actividad mensual (últimos 6 meses)
$actividad_mensual = $tabla_avistamientos_existe ? $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_avistamiento, '%Y-%m') as mes, COUNT(*) as total
     FROM {$tabla_avistamientos}
     WHERE fecha_avistamiento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes
     ORDER BY mes ASC"
) : [];

// Especies más avistadas
$especies_populares = $tabla_avistamientos_existe ? $wpdb->get_results(
    "SELECT e.id, e.nombre_comun, e.nombre_cientifico, e.categoria,
            COUNT(a.id) as total_avistamientos
     FROM {$tabla_especies} e
     INNER JOIN {$tabla_avistamientos} a ON e.id = a.especie_id
     WHERE a.estado = 'verificado'
     GROUP BY e.id
     ORDER BY total_avistamientos DESC
     LIMIT 5"
) : [];

// Avistamientos recientes
$avistamientos_recientes = $tabla_avistamientos_existe ? $wpdb->get_results(
    "SELECT a.*, e.nombre_comun, u.display_name
     FROM {$tabla_avistamientos} a
     LEFT JOIN {$tabla_especies} e ON e.id = a.especie_id
     LEFT JOIN {$wpdb->users} u ON u.ID = a.usuario_id
     ORDER BY a.fecha_creacion DESC
     LIMIT 5"
) : [];

// Distribución por categoría de especies
$por_categoria = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM {$tabla_especies}
     GROUP BY categoria
     ORDER BY total DESC"
);

// Colores por categoría
$colores_categoria = [
    'aves' => '#3b82f6',
    'mamiferos' => '#8b5cf6',
    'reptiles' => '#22c55e',
    'anfibios' => '#06b6d4',
    'peces' => '#0ea5e9',
    'insectos' => '#f59e0b',
    'plantas' => '#10b981',
    'hongos' => '#ec4899',
    'otros' => '#6b7280'
];
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--eco dm-stat-card--horizontal">
        <span class="dashicons dashicons-pets dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_especies); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Especies Catalogadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-visibility dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_avistamientos)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_observadores); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Observadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-portfolio dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($proyectos_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Proyectos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>
</div>

<?php if ($avistamientos_pendientes > 0): ?>
<div class="dm-alert dm-alert--warning">
    <span class="dashicons dashicons-warning"></span>
    <div>
        <strong><?php printf(esc_html__('%s avistamientos pendientes de verificación', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($avistamientos_pendientes)); ?></strong>
        <span><?php esc_html_e('Requieren revisión por parte de expertos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Actividad de Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($avistamientos_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_mensual)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay avistamientos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_avistamientos = max(array_column($actividad_mensual, 'total'));
                $meses = [
                    '01' => __('Ene', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '02' => __('Feb', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '03' => __('Mar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '04' => __('Abr', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '05' => __('May', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '06' => __('Jun', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '07' => __('Jul', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '08' => __('Ago', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '09' => __('Sep', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '10' => __('Oct', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '11' => __('Nov', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '12' => __('Dic', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_mensual as $mes): ?>
                        <?php
                        $altura = $max_avistamientos > 0 ? ($mes->total / $max_avistamientos) * 100 : 5;
                        $mes_num = substr($mes->mes, 5, 2);
                        $mes_nombre = $meses[$mes_num] ?? $mes_num;
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($mes->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--success" style="height: <?php echo max(4, $altura); ?>px;"></div>
                            <span class="dm-chart-bars__label"><?php echo esc_html($mes_nombre); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-category"></span>
                <?php esc_html_e('Especies por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_categoria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-pets"></span>
                    <p><?php esc_html_e('No hay especies catalogadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_categoria as $cat): ?>
                        <?php $color = $colores_categoria[$cat->categoria] ?? '#6b7280'; ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr($color); ?>; margin-right: 8px;"></span>
                                <?php echo esc_html(ucfirst($cat->categoria)); ?>
                            </span>
                            <span class="dm-data-list__value"><?php echo esc_html($cat->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Especies Más Avistadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($especies_populares)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-pets"></span>
                    <p><?php esc_html_e('No hay avistamientos verificados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($especies_populares as $index => $especie): ?>
                        <?php $color = $colores_categoria[$especie->categoria] ?? '#6b7280'; ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: <?php echo esc_attr($color); ?>;">
                                <?php echo mb_substr($especie->nombre_comun, 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($especie->nombre_comun); ?></strong>
                                <span class="dm-ranking__meta">
                                    <em><?php echo esc_html($especie->nombre_cientifico); ?></em>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--success">
                                <?php echo esc_html(number_format_i18n($especie->total_avistamientos)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Avistamientos Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($avistamientos_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-visibility"></span>
                    <p><?php esc_html_e('No hay avistamientos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($avistamientos_recientes as $avistamiento): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($avistamiento->nombre_comun ?: __('Especie desconocida', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html($avistamiento->display_name ?: __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($avistamiento->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php
                                echo $avistamiento->estado === 'verificado' ? 'success' :
                                    ($avistamiento->estado === 'pendiente' ? 'warning' : 'error');
                            ?>">
                                <?php echo esc_html(ucfirst($avistamiento->estado)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--3">
    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($avistamientos_verificados); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Avistamientos Verificados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-star-filled dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($especies_verificadas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Especies Verificadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-portfolio dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_proyectos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>

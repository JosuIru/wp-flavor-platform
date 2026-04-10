<?php
/**
 * Vista Dashboard - Recetas
 *
 * Dashboard administrativo para recetario comunitario.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_recetas = $wpdb->prefix . 'flavor_recetas';
$tabla_ingredientes = $wpdb->prefix . 'flavor_recetas_ingredientes';
$tabla_valoraciones = $wpdb->prefix . 'flavor_recetas_valoraciones';
$tabla_favoritos = $wpdb->prefix . 'flavor_recetas_favoritos';
$tabla_comentarios = $wpdb->prefix . 'flavor_recetas_comentarios';

// Verificar si las tablas existen
$tabla_recetas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_recetas)) === $tabla_recetas;

if (!$tabla_recetas_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Recetas aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_recetas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recetas}");
$recetas_publicadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recetas} WHERE estado = 'publicada'");
$recetas_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_recetas} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Autores únicos
$autores_unicos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT autor_id) FROM {$tabla_recetas}");

// Valoraciones
$tabla_valoraciones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_valoraciones)) === $tabla_valoraciones;
$total_valoraciones = $tabla_valoraciones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_valoraciones}") : 0;
$promedio_general = $tabla_valoraciones_existe ? (float) $wpdb->get_var("SELECT COALESCE(AVG(puntuacion), 0) FROM {$tabla_valoraciones}") : 0;

// Favoritos
$tabla_favoritos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_favoritos)) === $tabla_favoritos;
$total_favoritos = $tabla_favoritos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_favoritos}") : 0;

// Comentarios
$tabla_comentarios_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_comentarios)) === $tabla_comentarios;
$total_comentarios = $tabla_comentarios_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comentarios}") : 0;
$comentarios_hoy = $tabla_comentarios_existe ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_comentarios} WHERE DATE(fecha_creacion) = %s",
    current_time('Y-m-d')
)) : 0;

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_recetas}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
);

// Por categoría
$por_categoria = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM {$tabla_recetas}
     WHERE estado = 'publicada'
     GROUP BY categoria
     ORDER BY total DESC
     LIMIT 6"
);

// Recetas más valoradas
$recetas_top = $tabla_valoraciones_existe ? $wpdb->get_results(
    "SELECT r.id, r.titulo, r.categoria, r.tiempo_preparacion,
            COUNT(v.id) as num_valoraciones,
            AVG(v.puntuacion) as promedio
     FROM {$tabla_recetas} r
     INNER JOIN {$tabla_valoraciones} v ON r.id = v.receta_id
     WHERE r.estado = 'publicada'
     GROUP BY r.id
     HAVING num_valoraciones >= 1
     ORDER BY promedio DESC, num_valoraciones DESC
     LIMIT 5"
) : [];

// Cocineros más activos
$cocineros_top = $wpdb->get_results(
    "SELECT r.autor_id, u.display_name, COUNT(*) as total_recetas
     FROM {$tabla_recetas} r
     LEFT JOIN {$wpdb->users} u ON u.ID = r.autor_id
     WHERE r.estado = 'publicada'
     GROUP BY r.autor_id
     ORDER BY total_recetas DESC
     LIMIT 5"
);

// Recetas recientes
$recetas_recientes = $wpdb->get_results(
    "SELECT r.*,
            (SELECT AVG(puntuacion) FROM {$tabla_valoraciones} v WHERE v.receta_id = r.id) as promedio
     FROM {$tabla_recetas} r
     ORDER BY r.fecha_creacion DESC
     LIMIT 5"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--warning dm-stat-card--horizontal">
        <span class="dashicons dashicons-carrot dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($recetas_publicadas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Recetas Publicadas', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($autores_unicos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Cocineros', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-star-filled dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($promedio_general, 1)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Valoración Media', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--pink dm-stat-card--horizontal">
        <span class="dashicons dashicons-heart dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_favoritos)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Favoritos', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Recetas Esta Semana', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', 'flavor-platform'), number_format_i18n($recetas_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay recetas esta semana.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_recetas = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'), __('Lun', 'flavor-platform'), __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'), __('Jue', 'flavor-platform'), __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_recetas > 0 ? ($dia->total / $max_recetas) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar" style="height: <?php echo max(4, $altura); ?>px; background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);"></div>
                            <span class="dm-chart-bars__label"><?php echo esc_html($dia_nombre); ?></span>
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
                <?php esc_html_e('Por Categoría', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_categoria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay recetas registradas.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_categoria as $cat): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $cat->categoria))); ?></span>
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
                <?php esc_html_e('Recetas Mejor Valoradas', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($recetas_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No hay valoraciones todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($recetas_top as $index => $receta): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($receta->titulo); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html(ucfirst($receta->categoria)); ?>
                                    <?php if ($receta->tiempo_preparacion): ?>
                                        &bull; <?php echo esc_html($receta->tiempo_preparacion); ?> min
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--warning">
                                ★ <?php echo esc_html(number_format_i18n($receta->promedio, 1)); ?>
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
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Cocineros Más Activos', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($cocineros_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay cocineros todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($cocineros_top as $index => $cocinero): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: #f97316;">
                                <?php echo mb_substr($cocinero->display_name ?: __('C', 'flavor-platform'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($cocinero->display_name ?: __('Cocinero', 'flavor-platform')); ?></strong>
                            </div>
                            <span class="dm-badge dm-badge--success">
                                <?php echo esc_html($cocinero->total_recetas); ?> <?php esc_html_e('recetas', 'flavor-platform'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-card">
    <div class="dm-card__header">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Recetas Recientes', 'flavor-platform'); ?>
        </h3>
    </div>
    <div class="dm-card__body">
        <?php if (empty($recetas_recientes)): ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-carrot"></span>
                <p><?php esc_html_e('No hay recetas registradas.', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <ul class="dm-list">
                <?php foreach ($recetas_recientes as $receta): ?>
                    <li class="dm-list__item">
                        <div class="dm-list__content">
                            <strong class="dm-list__title"><?php echo esc_html($receta->titulo); ?></strong>
                            <span class="dm-list__meta">
                                <?php echo esc_html(ucfirst($receta->categoria)); ?>
                                &bull;
                                <?php echo esc_html(human_time_diff(strtotime($receta->fecha_creacion), current_time('timestamp'))); ?>
                                <?php if ($receta->promedio): ?>
                                    &bull; ★ <?php echo esc_html(number_format_i18n($receta->promedio, 1)); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="dm-badge dm-badge--<?php echo $receta->estado === 'publicada' ? 'success' : 'secondary'; ?>">
                            <?php echo esc_html(ucfirst($receta->estado)); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--3">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-clipboard dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_recetas)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Recetas', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-star-empty dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_valoraciones)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Valoraciones', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-admin-comments dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_comentarios)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Comentarios', 'flavor-platform'); ?></div>
    </div>
</div>

<?php
/**
 * Vista Dashboard - Economía de Suficiencia
 *
 * Dashboard administrativo para modelo económico basado en suficiencia.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_recursos = $wpdb->prefix . 'flavor_suficiencia_recursos';
$tabla_compromisos = $wpdb->prefix . 'flavor_suficiencia_compromisos';
$tabla_intercambios = $wpdb->prefix . 'flavor_suficiencia_intercambios';
$tabla_indicadores = $wpdb->prefix . 'flavor_suficiencia_indicadores';
$tabla_participantes = $wpdb->prefix . 'flavor_suficiencia_participantes';

// Verificar si las tablas existen
$tabla_recursos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_recursos)) === $tabla_recursos;

if (!$tabla_recursos_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Economía de Suficiencia aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_recursos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recursos}");
$recursos_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recursos} WHERE estado = 'disponible'");
$recursos_compartidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recursos} WHERE estado = 'compartido'");

// Participantes
$tabla_participantes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_participantes)) === $tabla_participantes;
$total_participantes = $tabla_participantes_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_participantes}") : 0;
$participantes_activos = $tabla_participantes_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_participantes} WHERE activo = 1") : 0;

// Compromisos
$tabla_compromisos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_compromisos)) === $tabla_compromisos;
$total_compromisos = $tabla_compromisos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_compromisos}") : 0;
$compromisos_cumplidos = $tabla_compromisos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_compromisos} WHERE cumplido = 1") : 0;
$tasa_cumplimiento = $total_compromisos > 0 ? round(($compromisos_cumplidos / $total_compromisos) * 100) : 0;

// Intercambios
$tabla_intercambios_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_intercambios)) === $tabla_intercambios;
$total_intercambios = $tabla_intercambios_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_intercambios}") : 0;
$intercambios_mes = $tabla_intercambios_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_intercambios} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

// Indicadores de bienestar
$tabla_indicadores_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_indicadores)) === $tabla_indicadores;
$promedio_bienestar = $tabla_indicadores_existe ? (float) $wpdb->get_var(
    "SELECT COALESCE(AVG(valor), 0) FROM {$tabla_indicadores} WHERE tipo = 'bienestar' AND fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;
$promedio_satisfaccion = $tabla_indicadores_existe ? (float) $wpdb->get_var(
    "SELECT COALESCE(AVG(valor), 0) FROM {$tabla_indicadores} WHERE tipo = 'satisfaccion' AND fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

// Actividad semanal
$actividad_semanal = $tabla_intercambios_existe ? $wpdb->get_results(
    "SELECT DATE(fecha) as fecha, COUNT(*) as total
     FROM {$tabla_intercambios}
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha)
     ORDER BY fecha ASC"
) : [];

// Por tipo de recurso
$por_tipo = $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM {$tabla_recursos}
     GROUP BY tipo
     ORDER BY total DESC
     LIMIT 6"
);

// Participantes más activos
$participantes_top = $tabla_intercambios_existe ? $wpdb->get_results(
    "SELECT i.usuario_id, u.display_name, COUNT(*) as total_intercambios
     FROM {$tabla_intercambios} i
     LEFT JOIN {$wpdb->users} u ON u.ID = i.usuario_id
     WHERE i.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY i.usuario_id
     ORDER BY total_intercambios DESC
     LIMIT 5"
) : [];

// Recursos recientes
$recursos_recientes = $wpdb->get_results(
    "SELECT r.*, u.display_name as autor_nombre
     FROM {$tabla_recursos} r
     LEFT JOIN {$wpdb->users} u ON u.ID = r.usuario_id
     ORDER BY r.fecha_creacion DESC
     LIMIT 5"
);
?>

<!-- Acciones Rápidas -->
<div class="dm-quick-actions">
    <a href="<?php echo esc_url(admin_url('admin.php?page=suficiencia-recursos')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Nuevo Recurso', 'flavor-platform'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=suficiencia-intercambios')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-randomize"></span>
        <?php esc_html_e('Registrar Intercambio', 'flavor-platform'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=suficiencia-compromisos')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('Ver Compromisos', 'flavor-platform'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=suficiencia-participantes')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-groups"></span>
        <?php esc_html_e('Participantes', 'flavor-platform'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=suficiencia-indicadores')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Indicadores', 'flavor-platform'); ?>
    </a>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--eco dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-site-alt3 dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($recursos_disponibles); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Recursos Disponibles', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($participantes_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participantes Activos', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-update dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_intercambios)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Intercambios', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-smiley dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($promedio_bienestar, 1)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Índice Bienestar', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<?php if ($recursos_compartidos > 0): ?>
<div class="dm-alert dm-alert--success">
    <span class="dashicons dashicons-share"></span>
    <div>
        <strong><?php printf(esc_html__('%s recursos compartidos activamente', 'flavor-platform'), number_format_i18n($recursos_compartidos)); ?></strong>
        <span><?php esc_html_e('La comunidad practica la economía de suficiencia.', 'flavor-platform'); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Intercambios Esta Semana', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', 'flavor-platform'), number_format_i18n($intercambios_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay intercambios esta semana.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_intercambios = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'), __('Lun', 'flavor-platform'), __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'), __('Jue', 'flavor-platform'), __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_intercambios > 0 ? ($dia->total / $max_intercambios) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--eco" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <?php esc_html_e('Tipos de Recursos', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_tipo)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay recursos registrados.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_tipo as $tipo): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $tipo->tipo))); ?></span>
                            <span class="dm-data-list__value"><?php echo esc_html($tipo->total); ?></span>
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
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Participantes Más Activos', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', 'flavor-platform'); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($participantes_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay actividad todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($participantes_top as $index => $participante): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: var(--dm-success);">
                                <?php echo mb_substr($participante->display_name ?: __('P', 'flavor-platform'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($participante->display_name ?: __('Participante', 'flavor-platform')); ?></strong>
                            </div>
                            <span class="dm-badge dm-badge--eco">
                                <?php echo esc_html($participante->total_intercambios); ?> <?php esc_html_e('int.', 'flavor-platform'); ?>
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
                <?php esc_html_e('Recursos Recientes', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($recursos_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-products"></span>
                    <p><?php esc_html_e('No hay recursos registrados.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($recursos_recientes as $recurso): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($recurso->nombre); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html(ucfirst($recurso->tipo)); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($recurso->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php
                                echo $recurso->estado === 'disponible' ? 'success' :
                                    ($recurso->estado === 'compartido' ? 'info' : 'secondary');
                            ?>">
                                <?php echo esc_html(ucfirst($recurso->estado)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-products dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_recursos)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Recursos', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-networking dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_participantes); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Participantes', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-thumbs-up dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($promedio_satisfaccion, 1)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Satisfacción', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tasa_cumplimiento); ?>%</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Compromisos', 'flavor-platform'); ?></div>
    </div>
</div>

<!-- Acceso Frontend -->
<div class="dm-card">
    <div class="dm-card__header">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e('Páginas Públicas del Módulo', 'flavor-platform'); ?>
        </h3>
        <span class="dm-card__subtitle"><?php esc_html_e('Vistas disponibles para los usuarios', 'flavor-platform'); ?></span>
    </div>
    <div class="dm-card__body">
        <div class="dm-link-grid">
            <a href="<?php echo esc_url(home_url('/suficiencia/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <div>
                    <strong><?php esc_html_e('Introducción', 'flavor-platform'); ?></strong>
                    <span><?php esc_html_e('Qué es la economía de suficiencia', 'flavor-platform'); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/suficiencia/mi-camino/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-chart-line"></span>
                <div>
                    <strong><?php esc_html_e('Mi Camino', 'flavor-platform'); ?></strong>
                    <span><?php esc_html_e('Progreso personal del usuario', 'flavor-platform'); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/suficiencia/compromisos/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-yes-alt"></span>
                <div>
                    <strong><?php esc_html_e('Compromisos', 'flavor-platform'); ?></strong>
                    <span><?php esc_html_e('Compromisos de la comunidad', 'flavor-platform'); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/suficiencia/evaluacion/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-forms"></span>
                <div>
                    <strong><?php esc_html_e('Evaluación', 'flavor-platform'); ?></strong>
                    <span><?php esc_html_e('Test de nivel de suficiencia', 'flavor-platform'); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/suficiencia/biblioteca/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-book"></span>
                <div>
                    <strong><?php esc_html_e('Biblioteca', 'flavor-platform'); ?></strong>
                    <span><?php esc_html_e('Recursos y materiales', 'flavor-platform'); ?></span>
                </div>
            </a>
        </div>
    </div>
</div>

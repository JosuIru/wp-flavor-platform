<?php
/**
 * Vista Dashboard - Justicia Restaurativa
 *
 * Dashboard administrativo para mediación y resolución de conflictos.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_casos = $wpdb->prefix . 'flavor_justicia_casos';
$tabla_sesiones = $wpdb->prefix . 'flavor_justicia_sesiones';
$tabla_participantes = $wpdb->prefix . 'flavor_justicia_participantes';
$tabla_acuerdos = $wpdb->prefix . 'flavor_justicia_acuerdos';
$tabla_mediadores = $wpdb->prefix . 'flavor_justicia_mediadores';

// Verificar si las tablas existen
$tabla_casos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_casos)) === $tabla_casos;

if (!$tabla_casos_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-chat-ia'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Justicia Restaurativa aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_casos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_casos}");
$casos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_casos} WHERE estado = 'en_proceso'");
$casos_resueltos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_casos} WHERE estado = 'resuelto'");
$casos_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_casos} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Sesiones
$tabla_sesiones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_sesiones)) === $tabla_sesiones;
$total_sesiones = $tabla_sesiones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_sesiones}") : 0;
$sesiones_programadas = $tabla_sesiones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_sesiones} WHERE estado = 'programada'") : 0;

// Participantes únicos
$tabla_participantes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_participantes)) === $tabla_participantes;
$participantes_unicos = $tabla_participantes_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_participantes}") : 0;

// Acuerdos
$tabla_acuerdos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_acuerdos)) === $tabla_acuerdos;
$total_acuerdos = $tabla_acuerdos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_acuerdos}") : 0;
$acuerdos_cumplidos = $tabla_acuerdos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_acuerdos} WHERE cumplido = 1") : 0;

// Mediadores
$tabla_mediadores_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_mediadores)) === $tabla_mediadores;
$total_mediadores = $tabla_mediadores_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_mediadores}") : 0;
$mediadores_activos = $tabla_mediadores_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_mediadores} WHERE activo = 1") : 0;

// Tasa de resolución
$tasa_resolucion = $total_casos > 0 ? round(($casos_resueltos / $total_casos) * 100) : 0;

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_casos}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
);

// Por tipo de conflicto
$por_tipo = $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM {$tabla_casos}
     GROUP BY tipo
     ORDER BY total DESC
     LIMIT 5"
);

// Mediadores destacados
$mediadores_top = $tabla_mediadores_existe && $tabla_casos_existe ? $wpdb->get_results(
    "SELECT m.id, m.nombre,
            COUNT(c.id) as casos_atendidos,
            SUM(CASE WHEN c.estado = 'resuelto' THEN 1 ELSE 0 END) as casos_resueltos
     FROM {$tabla_mediadores} m
     LEFT JOIN {$tabla_casos} c ON m.id = c.mediador_id
     WHERE m.activo = 1
     GROUP BY m.id
     ORDER BY casos_atendidos DESC
     LIMIT 5"
) : [];

// Casos recientes
$casos_recientes = $wpdb->get_results(
    "SELECT c.*
     FROM {$tabla_casos} c
     ORDER BY c.fecha_creacion DESC
     LIMIT 5"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-shield dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($casos_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Casos Activos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($casos_resueltos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Casos Resueltos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($participantes_unicos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-businessman dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($mediadores_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Mediadores', 'flavor-chat-ia'); ?></div>
        </div>
    </div>
</div>

<?php if ($sesiones_programadas > 0): ?>
<div class="dm-alert dm-alert--info">
    <span class="dashicons dashicons-calendar-alt"></span>
    <div>
        <strong><?php printf(esc_html__('%s sesiones programadas', 'flavor-chat-ia'), number_format_i18n($sesiones_programadas)); ?></strong>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Casos Esta Semana', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', 'flavor-chat-ia'), number_format_i18n($casos_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay casos esta semana.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_casos = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-chat-ia'), __('Lun', 'flavor-chat-ia'), __('Mar', 'flavor-chat-ia'),
                    __('Mié', 'flavor-chat-ia'), __('Jue', 'flavor-chat-ia'), __('Vie', 'flavor-chat-ia'),
                    __('Sáb', 'flavor-chat-ia')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_casos > 0 ? ($dia->total / $max_casos) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--purple" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <?php esc_html_e('Tipos de Conflicto', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_tipo)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay casos registrados.', 'flavor-chat-ia'); ?></p>
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
                <?php esc_html_e('Mediadores Destacados', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($mediadores_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-businessman"></span>
                    <p><?php esc_html_e('No hay mediadores registrados.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($mediadores_top as $index => $mediador): ?>
                        <?php $tasa = $mediador->casos_atendidos > 0 ? round(($mediador->casos_resueltos / $mediador->casos_atendidos) * 100) : 0; ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: #7c3aed;">
                                <?php echo mb_substr($mediador->nombre ?: __('M', 'flavor-chat-ia'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($mediador->nombre ?: __('Mediador', 'flavor-chat-ia')); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($tasa); ?>% <?php esc_html_e('resueltos', 'flavor-chat-ia'); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--purple">
                                <?php echo esc_html($mediador->casos_atendidos); ?>
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
                <?php esc_html_e('Casos Recientes', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($casos_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-shield"></span>
                    <p><?php esc_html_e('No hay casos registrados.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($casos_recientes as $caso): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($caso->titulo ?: sprintf(__('Caso #%d', 'flavor-chat-ia'), $caso->id)); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html(ucfirst($caso->tipo)); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($caso->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php
                                echo $caso->estado === 'resuelto' ? 'success' :
                                    ($caso->estado === 'en_proceso' ? 'warning' : 'secondary');
                            ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $caso->estado))); ?>
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
        <span class="dashicons dashicons-portfolio dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_casos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Casos', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-calendar-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_sesiones); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Sesiones', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($acuerdos_cumplidos); ?>/<?php echo esc_html($total_acuerdos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Acuerdos', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tasa_resolucion); ?>%</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Tasa Resolución', 'flavor-chat-ia'); ?></div>
    </div>
</div>

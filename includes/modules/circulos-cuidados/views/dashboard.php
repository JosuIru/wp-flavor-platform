<?php
/**
 * Vista Dashboard - Círculos de Cuidados
 *
 * Dashboard administrativo para redes de apoyo mutuo.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_circulos = $wpdb->prefix . 'flavor_circulos';
$tabla_miembros = $wpdb->prefix . 'flavor_circulos_miembros';
$tabla_necesidades = $wpdb->prefix . 'flavor_circulos_necesidades';
$tabla_respuestas = $wpdb->prefix . 'flavor_circulos_respuestas';
$tabla_horas = $wpdb->prefix . 'flavor_circulos_horas';

// Verificar si las tablas existen
$tabla_circulos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_circulos)) === $tabla_circulos;

if (!$tabla_circulos_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-chat-ia'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Círculos de Cuidados aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_circulos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_circulos}");
$circulos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_circulos} WHERE estado = 'activo'");

$tabla_miembros_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_miembros)) === $tabla_miembros;
$total_miembros = $tabla_miembros_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros}") : 0;
$miembros_unicos = $tabla_miembros_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_miembros}") : 0;

$tabla_necesidades_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_necesidades)) === $tabla_necesidades;
$total_necesidades = $tabla_necesidades_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_necesidades}") : 0;
$necesidades_pendientes = $tabla_necesidades_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_necesidades} WHERE estado = 'pendiente'") : 0;
$necesidades_atendidas = $tabla_necesidades_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_necesidades} WHERE estado = 'atendida'") : 0;
$necesidades_mes = $tabla_necesidades_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_necesidades} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

$tabla_respuestas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_respuestas)) === $tabla_respuestas;
$total_respuestas = $tabla_respuestas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_respuestas}") : 0;

$tabla_horas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_horas)) === $tabla_horas;
$total_horas = $tabla_horas_existe ? (float) $wpdb->get_var("SELECT COALESCE(SUM(horas), 0) FROM {$tabla_horas}") : 0;
$horas_mes = $tabla_horas_existe ? (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(horas), 0) FROM {$tabla_horas} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

// Tasa de atención
$tasa_atencion = $total_necesidades > 0 ? round(($necesidades_atendidas / $total_necesidades) * 100) : 0;

// Actividad semanal
$actividad_semanal = $tabla_necesidades_existe ? $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_necesidades}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
) : [];

// Círculos más activos
$circulos_activos_top = $tabla_miembros_existe ? $wpdb->get_results(
    "SELECT c.id, c.nombre, c.tipo,
            COUNT(DISTINCT m.usuario_id) as num_miembros,
            (SELECT COUNT(*) FROM {$tabla_necesidades} n WHERE n.circulo_id = c.id) as num_necesidades
     FROM {$tabla_circulos} c
     LEFT JOIN {$tabla_miembros} m ON c.id = m.circulo_id
     WHERE c.estado = 'activo'
     GROUP BY c.id
     ORDER BY num_miembros DESC
     LIMIT 5"
) : [];

// Tipos de necesidades
$tipos_necesidades = $tabla_necesidades_existe ? $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM {$tabla_necesidades}
     GROUP BY tipo
     ORDER BY total DESC
     LIMIT 5"
) : [];

// Cuidadores más activos
$cuidadores_top = $tabla_respuestas_existe ? $wpdb->get_results(
    "SELECT r.cuidador_id, u.display_name, COUNT(*) as total_ayudas
     FROM {$tabla_respuestas} r
     LEFT JOIN {$wpdb->users} u ON u.ID = r.cuidador_id
     WHERE r.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY r.cuidador_id
     ORDER BY total_ayudas DESC
     LIMIT 5"
) : [];
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--pink dm-stat-card--horizontal">
        <span class="dashicons dashicons-heart dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($circulos_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Círculos Activos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($miembros_unicos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-sos dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_necesidades); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Necesidades', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_horas, 1)); ?>h</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Horas de Cuidado', 'flavor-chat-ia'); ?></div>
        </div>
    </div>
</div>

<?php if ($necesidades_pendientes > 0): ?>
<div class="dm-alert dm-alert--warning">
    <span class="dashicons dashicons-warning"></span>
    <div>
        <strong><?php printf(esc_html__('%s necesidades pendientes de atención', 'flavor-chat-ia'), number_format_i18n($necesidades_pendientes)); ?></strong>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Necesidades Esta Semana', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', 'flavor-chat-ia'), number_format_i18n($necesidades_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay necesidades registradas esta semana.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_necesidades = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-chat-ia'), __('Lun', 'flavor-chat-ia'), __('Mar', 'flavor-chat-ia'),
                    __('Mié', 'flavor-chat-ia'), __('Jue', 'flavor-chat-ia'), __('Vie', 'flavor-chat-ia'),
                    __('Sáb', 'flavor-chat-ia')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_necesidades > 0 ? ($dia->total / $max_necesidades) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--pink" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <?php esc_html_e('Tipos de Necesidades', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($tipos_necesidades)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay necesidades registradas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($tipos_necesidades as $tipo): ?>
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
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Círculos Más Activos', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($circulos_activos_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay círculos activos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($circulos_activos_top as $index => $circulo): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($circulo->nombre); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($circulo->num_miembros); ?> <?php esc_html_e('miembros', 'flavor-chat-ia'); ?>
                                    &bull;
                                    <?php echo esc_html($circulo->num_necesidades); ?> <?php esc_html_e('necesidades', 'flavor-chat-ia'); ?>
                                </span>
                            </div>
                            <?php if ($circulo->tipo): ?>
                            <span class="dm-badge dm-badge--pink"><?php echo esc_html(ucfirst($circulo->tipo)); ?></span>
                            <?php endif; ?>
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
                <?php esc_html_e('Cuidadores Más Activos', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($cuidadores_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay datos de actividad todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($cuidadores_top as $index => $cuidador): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar">
                                <?php echo mb_substr($cuidador->display_name ?: __('C', 'flavor-chat-ia'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($cuidador->display_name ?: __('Cuidador', 'flavor-chat-ia')); ?></strong>
                            </div>
                            <span class="dm-badge dm-badge--success">
                                <?php echo esc_html($cuidador->total_ayudas); ?> <?php esc_html_e('ayudas', 'flavor-chat-ia'); ?>
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
        <span class="dashicons dashicons-networking dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_circulos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Círculos', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($necesidades_atendidas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Atendidas', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-thumbs-up dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_respuestas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Respuestas', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tasa_atencion); ?>%</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Tasa Atención', 'flavor-chat-ia'); ?></div>
    </div>
</div>

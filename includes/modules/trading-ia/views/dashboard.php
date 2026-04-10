<?php
/**
 * Vista Dashboard - Trading IA
 *
 * Dashboard administrativo para herramientas de trading con IA.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_operaciones = $wpdb->prefix . 'flavor_trading_operaciones';
$tabla_estrategias = $wpdb->prefix . 'flavor_trading_estrategias';
$tabla_alertas = $wpdb->prefix . 'flavor_trading_alertas';
$tabla_analisis = $wpdb->prefix . 'flavor_trading_analisis';
$tabla_carteras = $wpdb->prefix . 'flavor_trading_carteras';

// Verificar si las tablas existen
$tabla_operaciones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_operaciones)) === $tabla_operaciones;

if (!$tabla_operaciones_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Trading IA aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_operaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_operaciones}");
$operaciones_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_operaciones} WHERE DATE(fecha) = %s",
    current_time('Y-m-d')
));
$operaciones_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_operaciones} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Operaciones ganadoras/perdedoras
$operaciones_ganadoras = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_operaciones} WHERE resultado > 0");
$operaciones_perdedoras = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_operaciones} WHERE resultado < 0");
$tasa_exito = $total_operaciones > 0 ? round(($operaciones_ganadoras / $total_operaciones) * 100, 1) : 0;

// Beneficios
$beneficio_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(resultado), 0) FROM {$tabla_operaciones}");
$beneficio_mes = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(resultado), 0) FROM {$tabla_operaciones} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Estrategias
$tabla_estrategias_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_estrategias)) === $tabla_estrategias;
$total_estrategias = $tabla_estrategias_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estrategias}") : 0;
$estrategias_activas = $tabla_estrategias_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estrategias} WHERE activa = 1") : 0;

// Alertas
$tabla_alertas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_alertas)) === $tabla_alertas;
$alertas_pendientes = $tabla_alertas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_alertas} WHERE estado = 'pendiente'") : 0;

// Usuarios únicos
$usuarios_trading = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_operaciones}");

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha) as fecha,
            COUNT(*) as total,
            SUM(CASE WHEN resultado > 0 THEN 1 ELSE 0 END) as ganadoras,
            SUM(resultado) as beneficio
     FROM {$tabla_operaciones}
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha)
     ORDER BY fecha ASC"
);

// Por tipo de activo
$por_activo = $wpdb->get_results(
    "SELECT activo, COUNT(*) as total, SUM(resultado) as beneficio
     FROM {$tabla_operaciones}
     GROUP BY activo
     ORDER BY total DESC
     LIMIT 6"
);

// Traders más activos
$traders_top = $wpdb->get_results(
    "SELECT o.usuario_id, u.display_name,
            COUNT(*) as total_operaciones,
            SUM(o.resultado) as beneficio_total,
            SUM(CASE WHEN o.resultado > 0 THEN 1 ELSE 0 END) as ganadoras
     FROM {$tabla_operaciones} o
     LEFT JOIN {$wpdb->users} u ON u.ID = o.usuario_id
     WHERE o.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY o.usuario_id
     ORDER BY beneficio_total DESC
     LIMIT 5"
);

// Operaciones recientes
$operaciones_recientes = $wpdb->get_results(
    "SELECT o.*, u.display_name
     FROM {$tabla_operaciones} o
     LEFT JOIN {$wpdb->users} u ON u.ID = o.usuario_id
     ORDER BY o.fecha DESC
     LIMIT 8"
);

// Mejores estrategias
$estrategias_top = $tabla_estrategias_existe ? $wpdb->get_results(
    "SELECT e.id, e.nombre, e.tipo,
            COUNT(o.id) as operaciones,
            SUM(o.resultado) as beneficio,
            AVG(CASE WHEN o.resultado > 0 THEN 1 ELSE 0 END) * 100 as tasa_exito
     FROM {$tabla_estrategias} e
     LEFT JOIN {$tabla_operaciones} o ON e.id = o.estrategia_id
     WHERE e.activa = 1
     GROUP BY e.id
     ORDER BY beneficio DESC
     LIMIT 5"
) : [];
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-chart-area dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($operaciones_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Operaciones Hoy', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--<?php echo $beneficio_mes >= 0 ? 'success' : 'error'; ?> dm-stat-card--horizontal">
        <span class="dashicons dashicons-<?php echo $beneficio_mes >= 0 ? 'arrow-up-alt' : 'arrow-down-alt'; ?> dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(($beneficio_mes >= 0 ? '+' : '') . number_format_i18n($beneficio_mes, 2)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Resultado Mes', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($tasa_exito); ?>%</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Tasa Éxito', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($usuarios_trading); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Traders', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<?php if ($alertas_pendientes > 0): ?>
<div class="dm-alert dm-alert--warning">
    <span class="dashicons dashicons-bell"></span>
    <div>
        <strong><?php printf(esc_html__('%s alertas pendientes', 'flavor-platform'), number_format_i18n($alertas_pendientes)); ?></strong>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Rendimiento Semanal', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s operaciones este mes', 'flavor-platform'), number_format_i18n($operaciones_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay operaciones esta semana.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_ops = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'), __('Lun', 'flavor-platform'), __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'), __('Jue', 'flavor-platform'), __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_ops > 0 ? ($dia->total / $max_ops) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        $color = $dia->beneficio >= 0 ? 'success' : 'error';
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--<?php echo $color; ?>" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Por Activo', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_activo)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay operaciones registradas.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_activo as $activo): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(strtoupper($activo->activo)); ?></span>
                            <span class="dm-data-list__value" style="color: <?php echo $activo->beneficio >= 0 ? 'var(--dm-success)' : 'var(--dm-error)'; ?>">
                                <?php echo esc_html(($activo->beneficio >= 0 ? '+' : '') . number_format_i18n($activo->beneficio, 2)); ?>
                            </span>
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
                <?php esc_html_e('Top Traders', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', 'flavor-platform'); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($traders_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay datos de trading.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($traders_top as $index => $trader): ?>
                        <?php $tasa = $trader->total_operaciones > 0 ? round(($trader->ganadoras / $trader->total_operaciones) * 100) : 0; ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: #059669;">
                                <?php echo mb_substr($trader->display_name ?: __('T', 'flavor-platform'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($trader->display_name ?: __('Trader', 'flavor-platform')); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($trader->total_operaciones); ?> ops &bull; <?php echo esc_html($tasa); ?>% win
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $trader->beneficio_total >= 0 ? 'success' : 'error'; ?>">
                                <?php echo esc_html(($trader->beneficio_total >= 0 ? '+' : '') . number_format_i18n($trader->beneficio_total, 2)); ?>
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
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e('Estrategias IA', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($estrategias_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <p><?php esc_html_e('No hay estrategias activas.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($estrategias_top as $index => $estrategia): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($estrategia->nombre); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html(ucfirst($estrategia->tipo)); ?>
                                    &bull;
                                    <?php echo esc_html(round($estrategia->tasa_exito)); ?>% win
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $estrategia->beneficio >= 0 ? 'success' : 'error'; ?>">
                                <?php echo esc_html(($estrategia->beneficio >= 0 ? '+' : '') . number_format_i18n($estrategia->beneficio, 2)); ?>
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
            <?php esc_html_e('Operaciones Recientes', 'flavor-platform'); ?>
        </h3>
    </div>
    <div class="dm-card__body">
        <?php if (empty($operaciones_recientes)): ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-chart-area"></span>
                <p><?php esc_html_e('No hay operaciones registradas.', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <ul class="dm-list">
                <?php foreach ($operaciones_recientes as $op): ?>
                    <li class="dm-list__item">
                        <div class="dm-list__content">
                            <strong class="dm-list__title"><?php echo esc_html(strtoupper($op->activo)); ?> - <?php echo esc_html(ucfirst($op->tipo)); ?></strong>
                            <span class="dm-list__meta">
                                <?php echo esc_html($op->display_name ?: __('Trader', 'flavor-platform')); ?>
                                &bull;
                                <?php
                                $fecha_op = new DateTime($op->fecha);
                                echo esc_html($fecha_op->format('d/m H:i'));
                                ?>
                            </span>
                        </div>
                        <span class="dm-badge dm-badge--<?php echo $op->resultado >= 0 ? 'success' : 'error'; ?>">
                            <?php echo esc_html(($op->resultado >= 0 ? '+' : '') . number_format_i18n($op->resultado, 2)); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-database dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_operaciones)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Operaciones', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($operaciones_ganadoras)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Ganadoras', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--error">
        <span class="dashicons dashicons-no dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($operaciones_perdedoras)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Perdedoras', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-admin-generic dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($estrategias_activas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Estrategias IA', 'flavor-platform'); ?></div>
    </div>
</div>

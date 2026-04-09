<?php
/**
 * Vista Dashboard - Huella Ecológica
 *
 * Dashboard administrativo para seguimiento de huella de carbono.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_registros = $wpdb->prefix . 'flavor_huella_registros';
$tabla_objetivos = $wpdb->prefix . 'flavor_huella_objetivos';
$tabla_compensaciones = $wpdb->prefix . 'flavor_huella_compensaciones';

// Verificar si las tablas existen
$tabla_registros_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_registros)) === $tabla_registros;

if (!$tabla_registros_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Huella Ecológica aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_registros = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_registros}");
$registros_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_registros} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Usuarios participantes
$usuarios_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_registros}");

// CO2 total emitido (en kg)
$co2_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(co2_kg), 0) FROM {$tabla_registros}");
$co2_mes = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(co2_kg), 0) FROM {$tabla_registros} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);
$co2_promedio_usuario = $usuarios_activos > 0 ? round($co2_total / $usuarios_activos, 2) : 0;

// Objetivos
$tabla_objetivos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_objetivos)) === $tabla_objetivos;
$total_objetivos = $tabla_objetivos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_objetivos}") : 0;
$objetivos_cumplidos = $tabla_objetivos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_objetivos} WHERE cumplido = 1") : 0;
$tasa_cumplimiento = $total_objetivos > 0 ? round(($objetivos_cumplidos / $total_objetivos) * 100) : 0;

// Compensaciones
$tabla_compensaciones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_compensaciones)) === $tabla_compensaciones;
$co2_compensado = $tabla_compensaciones_existe ? (float) $wpdb->get_var("SELECT COALESCE(SUM(co2_compensado), 0) FROM {$tabla_compensaciones}") : 0;
$balance_neto = $co2_total - $co2_compensado;

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha) as fecha, SUM(co2_kg) as total_co2, COUNT(*) as num_registros
     FROM {$tabla_registros}
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha)
     ORDER BY fecha ASC"
);

// Por categoría
$por_categoria = $wpdb->get_results(
    "SELECT categoria, SUM(co2_kg) as total_co2, COUNT(*) as registros
     FROM {$tabla_registros}
     GROUP BY categoria
     ORDER BY total_co2 DESC
     LIMIT 6"
);

// Usuarios con menor huella
$usuarios_eco = $wpdb->get_results(
    "SELECT r.usuario_id, u.display_name,
            SUM(r.co2_kg) as total_co2,
            COUNT(*) as registros
     FROM {$tabla_registros} r
     LEFT JOIN {$wpdb->users} u ON u.ID = r.usuario_id
     WHERE r.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY r.usuario_id
     ORDER BY total_co2 ASC
     LIMIT 5"
);

// Tendencia mensual
$tendencia = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, SUM(co2_kg) as total_co2
     FROM {$tabla_registros}
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha, '%Y-%m')
     ORDER BY mes DESC
     LIMIT 6"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--eco dm-stat-card--horizontal">
        <span class="dashicons dashicons-cloud dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($co2_total, 1)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('kg CO₂ Total', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-palmtree dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($co2_compensado, 1)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('kg Compensados', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($usuarios_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participantes', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--<?php echo $balance_neto <= 0 ? 'success' : 'warning'; ?> dm-stat-card--horizontal">
        <span class="dashicons dashicons-<?php echo $balance_neto <= 0 ? 'yes-alt' : 'warning'; ?> dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n(abs($balance_neto), 1)); ?></div>
            <div class="dm-stat-card__label"><?php echo $balance_neto <= 0 ? esc_html__('Carbono Neutro', 'flavor-platform') : esc_html__('Balance Neto', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<?php if ($balance_neto > 100): ?>
<div class="dm-alert dm-alert--info">
    <span class="dashicons dashicons-lightbulb"></span>
    <div>
        <strong><?php esc_html_e('Oportunidad de mejora', 'flavor-platform'); ?></strong>
        <span><?php printf(esc_html__('Aún quedan %.1f kg de CO₂ por compensar.', 'flavor-platform'), $balance_neto); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Emisiones Esta Semana', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%.1f kg CO₂ este mes', 'flavor-platform'), $co2_mes); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay registros esta semana.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_co2 = max(array_column($actividad_semanal, 'total_co2'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'), __('Lun', 'flavor-platform'), __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'), __('Jue', 'flavor-platform'), __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_co2 > 0 ? ($dia->total_co2 / $max_co2) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html(round($dia->total_co2, 1)); ?></span>
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
                <?php esc_html_e('Por Categoría', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_categoria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay registros todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_categoria as $cat): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $cat->categoria))); ?></span>
                            <span class="dm-data-list__value"><?php echo esc_html(number_format_i18n($cat->total_co2, 1)); ?> kg</span>
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
                <?php esc_html_e('Usuarios Más Sostenibles', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Menor huella mensual', 'flavor-platform'); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($usuarios_eco)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay datos suficientes.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($usuarios_eco as $index => $usuario): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: var(--dm-success);">
                                <?php echo mb_substr($usuario->display_name ?: __('U', 'flavor-platform'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($usuario->display_name ?: __('Usuario', 'flavor-platform')); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($usuario->registros); ?> <?php esc_html_e('registros', 'flavor-platform'); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--eco">
                                <?php echo esc_html(number_format_i18n($usuario->total_co2, 1)); ?> kg
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
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e('Tendencia Mensual', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($tendencia)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-area"></span>
                    <p><?php esc_html_e('No hay datos históricos.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php
                    $meses = [
                        '01' => __('Enero', 'flavor-platform'), '02' => __('Febrero', 'flavor-platform'),
                        '03' => __('Marzo', 'flavor-platform'), '04' => __('Abril', 'flavor-platform'),
                        '05' => __('Mayo', 'flavor-platform'), '06' => __('Junio', 'flavor-platform'),
                        '07' => __('Julio', 'flavor-platform'), '08' => __('Agosto', 'flavor-platform'),
                        '09' => __('Septiembre', 'flavor-platform'), '10' => __('Octubre', 'flavor-platform'),
                        '11' => __('Noviembre', 'flavor-platform'), '12' => __('Diciembre', 'flavor-platform')
                    ];
                    foreach ($tendencia as $mes_data):
                        $partes = explode('-', $mes_data->mes);
                        $nombre_mes = isset($meses[$partes[1]]) ? $meses[$partes[1]] . ' ' . $partes[0] : $mes_data->mes;
                    ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html($nombre_mes); ?></span>
                            <span class="dm-data-list__value"><?php echo esc_html(number_format_i18n($mes_data->total_co2, 1)); ?> kg</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-clipboard dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_registros)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Registros', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-businessman dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($co2_promedio_usuario, 1)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('kg/Usuario', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-flag dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($objetivos_cumplidos); ?>/<?php echo esc_html($total_objetivos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Objetivos', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tasa_cumplimiento); ?>%</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Cumplimiento', 'flavor-platform'); ?></div>
    </div>
</div>

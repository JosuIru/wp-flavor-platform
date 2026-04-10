<?php
/**
 * Vista Dashboard - Trabajo Digno
 *
 * Dashboard administrativo para bolsa de empleo ético.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_ofertas = $wpdb->prefix . 'flavor_trabajo_ofertas';
$tabla_candidaturas = $wpdb->prefix . 'flavor_trabajo_candidaturas';
$tabla_empresas = $wpdb->prefix . 'flavor_trabajo_empresas';

// Verificar si las tablas existen
$tabla_ofertas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_ofertas)) === $tabla_ofertas;

if (!$tabla_ofertas_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Trabajo Digno aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_ofertas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_ofertas}");
$ofertas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_ofertas} WHERE estado = 'activa'");
$ofertas_cerradas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_ofertas} WHERE estado = 'cerrada'");
$ofertas_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_ofertas} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

$tabla_candidaturas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_candidaturas)) === $tabla_candidaturas;
$total_candidaturas = $tabla_candidaturas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_candidaturas}") : 0;
$candidaturas_pendientes = $tabla_candidaturas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_candidaturas} WHERE estado = 'pendiente'") : 0;
$candidaturas_mes = $tabla_candidaturas_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_candidaturas} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
) : 0;

$tabla_empresas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_empresas)) === $tabla_empresas;
$total_empresas = $tabla_empresas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_empresas}") : 0;
$empresas_verificadas = $tabla_empresas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_empresas} WHERE verificada = 1") : 0;

// Candidatos únicos
$candidatos_unicos = $tabla_candidaturas_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_candidaturas}") : 0;

// Actividad semanal
$actividad_semanal = $tabla_candidaturas_existe ? $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_candidaturas}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
) : [];

// Ofertas más solicitadas
$ofertas_populares = $tabla_candidaturas_existe ? $wpdb->get_results(
    "SELECT o.id, o.titulo, o.empresa, o.tipo_contrato, o.ubicacion,
            COUNT(c.id) as total_candidaturas
     FROM {$tabla_ofertas} o
     INNER JOIN {$tabla_candidaturas} c ON o.id = c.oferta_id
     WHERE o.estado = 'activa'
     GROUP BY o.id
     ORDER BY total_candidaturas DESC
     LIMIT 5"
) : [];

// Ofertas recientes
$ofertas_recientes = $wpdb->get_results(
    "SELECT o.*,
            (SELECT COUNT(*) FROM {$tabla_candidaturas} c WHERE c.oferta_id = o.id) as num_candidaturas
     FROM {$tabla_ofertas} o
     ORDER BY o.fecha_creacion DESC
     LIMIT 5"
);

// Por tipo de contrato
$por_tipo = $wpdb->get_results(
    "SELECT tipo_contrato, COUNT(*) as total
     FROM {$tabla_ofertas}
     WHERE estado = 'activa'
     GROUP BY tipo_contrato
     ORDER BY total DESC"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-megaphone dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($ofertas_activas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Ofertas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_candidaturas)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($candidatos_unicos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Candidatos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-building dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_empresas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Empresas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>
</div>

<?php if ($candidaturas_pendientes > 0): ?>
<div class="dm-alert dm-alert--warning">
    <span class="dashicons dashicons-clipboard"></span>
    <div>
        <strong><?php printf(esc_html__('%s candidaturas pendientes de revisión', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($candidaturas_pendientes)); ?></strong>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Candidaturas Esta Semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($candidaturas_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay candidaturas esta semana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_candidaturas = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Lun', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Mié', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Jue', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Vie', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Sáb', FLAVOR_PLATFORM_TEXT_DOMAIN)
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_candidaturas > 0 ? ($dia->total / $max_candidaturas) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--primary" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e('Por Tipo de Contrato', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_tipo)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-businessman"></span>
                    <p><?php esc_html_e('No hay ofertas activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_tipo as $tipo): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $tipo->tipo_contrato))); ?></span>
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
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Ofertas Más Solicitadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($ofertas_populares)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay candidaturas todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($ofertas_populares as $index => $oferta): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($oferta->titulo); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($oferta->empresa); ?>
                                    <?php if ($oferta->ubicacion): ?>
                                        &bull; <?php echo esc_html($oferta->ubicacion); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--success">
                                <?php echo esc_html($oferta->total_candidaturas); ?>
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
                <?php esc_html_e('Ofertas Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($ofertas_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay ofertas publicadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($ofertas_recientes as $oferta): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($oferta->titulo); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html($oferta->num_candidaturas); ?> <?php esc_html_e('candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($oferta->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $oferta->estado === 'activa' ? 'success' : 'secondary'; ?>">
                                <?php echo esc_html(ucfirst($oferta->estado)); ?>
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
        <span class="dashicons dashicons-archive dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($total_ofertas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--warning">
        <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($ofertas_mes); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Nuevas Este Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--error">
        <span class="dashicons dashicons-no dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($ofertas_cerradas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Ofertas Cerradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($empresas_verificadas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Empresas Verificadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>

<?php
/**
 * Vista: Resumen mensual de horas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$meses = [
    1 => __('Enero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    2 => __('Febrero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    3 => __('Marzo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    4 => __('Abril', FLAVOR_PLATFORM_TEXT_DOMAIN),
    5 => __('Mayo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    6 => __('Junio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    7 => __('Julio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    8 => __('Agosto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    9 => __('Septiembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    10 => __('Octubre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    11 => __('Noviembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    12 => __('Diciembre', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="fichaje-resumen">
    <div class="fichaje-resumen-header">
        <h3><?php esc_html_e('Resumen de Horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <div class="fichaje-periodo-selector">
            <select id="fichaje-mes" class="fichaje-select">
                <?php foreach ($meses as $num => $nombre): ?>
                <option value="<?php echo esc_attr($num); ?>" <?php selected($resumen['mes'], $num); ?>>
                    <?php echo esc_html($nombre); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select id="fichaje-anio" class="fichaje-select">
                <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                <option value="<?php echo esc_attr($a); ?>" <?php selected($resumen['anio'], $a); ?>>
                    <?php echo esc_html($a); ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- Cards de estadísticas -->
    <div class="fichaje-resumen-stats">
        <div class="fichaje-stat-card">
            <div class="stat-valor"><?php echo esc_html(number_format($resumen['total_horas'], 1)); ?></div>
            <div class="stat-label"><?php esc_html_e('Horas totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>

        <div class="fichaje-stat-card">
            <div class="stat-valor"><?php echo esc_html($resumen['dias_trabajados']); ?></div>
            <div class="stat-label"><?php esc_html_e('Días trabajados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>

        <div class="fichaje-stat-card">
            <div class="stat-valor"><?php echo esc_html(number_format($resumen['promedio_horas_diarias'], 1)); ?></div>
            <div class="stat-label"><?php esc_html_e('Promedio diario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <?php if (!empty($resumen['detalle_dias'])): ?>
    <!-- Gráfico de barras simple -->
    <div class="fichaje-grafico">
        <h4><?php esc_html_e('Horas por día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="fichaje-grafico-barras">
            <?php
            $max_horas = max(array_column($resumen['detalle_dias'], 'horas'));
            $max_horas = max($max_horas, 8); // Mínimo 8 horas de escala
            ?>
            <?php foreach ($resumen['detalle_dias'] as $dia): ?>
            <div class="fichaje-barra-container" title="<?php echo esc_attr(date_i18n('l, j F', strtotime($dia['fecha'])) . ': ' . $dia['horas'] . 'h'); ?>">
                <div class="fichaje-barra" style="height: <?php echo esc_attr(($dia['horas'] / $max_horas) * 100); ?>%;">
                    <span class="barra-valor"><?php echo esc_html(number_format($dia['horas'], 1)); ?></span>
                </div>
                <span class="barra-label"><?php echo esc_html(date('d', strtotime($dia['fecha']))); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Detalle por día -->
    <div class="fichaje-detalle-dias">
        <h4><?php esc_html_e('Detalle por día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="fichaje-tabla fichaje-tabla-detalle">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fichajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumen['detalle_dias'] as $dia): ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('l, j F', strtotime($dia['fecha']))); ?></td>
                    <td>
                        <span class="horas-valor <?php echo $dia['horas'] >= 8 ? 'horas-completas' : 'horas-parciales'; ?>">
                            <?php echo esc_html(number_format($dia['horas'], 2)); ?>h
                        </span>
                    </td>
                    <td><?php echo esc_html($dia['fichajes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="fichaje-resumen-vacio">
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e('No hay registros para este periodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <div class="fichaje-resumen-acciones">
        <a href="<?php echo esc_url(home_url('/fichaje-empleados/')); ?>" class="fichaje-btn fichaje-btn-secundario">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e('Volver al panel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>

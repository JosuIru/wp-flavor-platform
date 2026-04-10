<?php
/**
 * Vista: Historial de fichajes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$periodo_labels = [
    'hoy' => __('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'semana' => __('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'mes' => __('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$tipo_labels = [
    'entrada' => __('Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'salida' => __('Salida', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pausa_inicio' => __('Inicio pausa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pausa_fin' => __('Fin pausa', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="fichaje-historial">
    <div class="fichaje-historial-header">
        <h3><?php esc_html_e('Mis Fichajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <div class="fichaje-filtros">
            <select id="fichaje-filtro-periodo" class="fichaje-select">
                <?php foreach ($periodo_labels as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($atts['periodo'], $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($fichajes)): ?>
    <div class="fichaje-historial-vacio">
        <span class="dashicons dashicons-clock"></span>
        <p><?php esc_html_e('No hay fichajes en este periodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>

    <!-- Vista móvil: Cards -->
    <div class="fichaje-historial-cards">
        <?php
        $fichajes_agrupados = [];
        foreach ($fichajes as $fichaje) {
            $fecha = $fichaje['fecha'];
            if (!isset($fichajes_agrupados[$fecha])) {
                $fichajes_agrupados[$fecha] = [];
            }
            $fichajes_agrupados[$fecha][] = $fichaje;
        }
        ?>

        <?php foreach ($fichajes_agrupados as $fecha => $fichajes_dia): ?>
        <div class="fichaje-dia-card">
            <div class="fichaje-dia-header">
                <span class="fichaje-dia-fecha">
                    <?php echo esc_html(date_i18n('l, j F', strtotime($fecha))); ?>
                </span>
            </div>

            <div class="fichaje-dia-registros">
                <?php foreach ($fichajes_dia as $fichaje): ?>
                <div class="fichaje-registro tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                    <span class="registro-hora"><?php echo esc_html($fichaje['hora']); ?></span>
                    <span class="registro-tipo">
                        <?php echo esc_html($tipo_labels[$fichaje['tipo']] ?? $fichaje['tipo']); ?>
                    </span>
                    <?php if (!$fichaje['validado']): ?>
                    <span class="registro-pendiente" title="<?php esc_attr_e('Pendiente de validación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-warning"></span>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($fichaje['notas'])): ?>
                    <span class="registro-notas"><?php echo esc_html($fichaje['notas']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Vista desktop: Tabla -->
    <div class="fichaje-historial-tabla">
        <table class="fichaje-tabla">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Notas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fichajes as $fichaje): ?>
                <tr class="tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                    <td data-label="<?php esc_attr_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($fichaje['fecha']))); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <?php echo esc_html($fichaje['hora']); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="tipo-badge tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                            <?php echo esc_html($tipo_labels[$fichaje['tipo']] ?? $fichaje['tipo']); ?>
                        </span>
                    </td>
                    <td data-label="<?php esc_attr_e('Notas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <?php echo esc_html($fichaje['notas'] ?: '-'); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <?php if ($fichaje['validado']): ?>
                        <span class="estado-validado" title="<?php esc_attr_e('Validado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </span>
                        <?php else: ?>
                        <span class="estado-pendiente" title="<?php esc_attr_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-clock"></span>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

    <div class="fichaje-historial-acciones">
        <a href="<?php echo esc_url(home_url('/fichaje-empleados/')); ?>" class="fichaje-btn fichaje-btn-secundario">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e('Volver al panel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>

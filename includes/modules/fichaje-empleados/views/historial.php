<?php
/**
 * Vista: Historial de fichajes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$periodo_labels = [
    'hoy' => __('Hoy', 'flavor-chat-ia'),
    'semana' => __('Esta semana', 'flavor-chat-ia'),
    'mes' => __('Este mes', 'flavor-chat-ia'),
];

$tipo_labels = [
    'entrada' => __('Entrada', 'flavor-chat-ia'),
    'salida' => __('Salida', 'flavor-chat-ia'),
    'pausa_inicio' => __('Inicio pausa', 'flavor-chat-ia'),
    'pausa_fin' => __('Fin pausa', 'flavor-chat-ia'),
];
?>

<div class="fichaje-historial">
    <div class="fichaje-historial-header">
        <h3><?php esc_html_e('Mis Fichajes', 'flavor-chat-ia'); ?></h3>

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
        <p><?php esc_html_e('No hay fichajes en este periodo.', 'flavor-chat-ia'); ?></p>
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
                    <span class="registro-pendiente" title="<?php esc_attr_e('Pendiente de validación', 'flavor-chat-ia'); ?>">
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
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Hora', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Notas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fichajes as $fichaje): ?>
                <tr class="tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                    <td data-label="<?php esc_attr_e('Fecha', 'flavor-chat-ia'); ?>">
                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($fichaje['fecha']))); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Hora', 'flavor-chat-ia'); ?>">
                        <?php echo esc_html($fichaje['hora']); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Tipo', 'flavor-chat-ia'); ?>">
                        <span class="tipo-badge tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                            <?php echo esc_html($tipo_labels[$fichaje['tipo']] ?? $fichaje['tipo']); ?>
                        </span>
                    </td>
                    <td data-label="<?php esc_attr_e('Notas', 'flavor-chat-ia'); ?>">
                        <?php echo esc_html($fichaje['notas'] ?: '-'); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Estado', 'flavor-chat-ia'); ?>">
                        <?php if ($fichaje['validado']): ?>
                        <span class="estado-validado" title="<?php esc_attr_e('Validado', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </span>
                        <?php else: ?>
                        <span class="estado-pendiente" title="<?php esc_attr_e('Pendiente', 'flavor-chat-ia'); ?>">
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
            <?php esc_html_e('Volver al panel', 'flavor-chat-ia'); ?>
        </a>
    </div>
</div>

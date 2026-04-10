<?php
/**
 * Vista: Panel principal de fichaje
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$estado = $this->obtener_estado_actual($usuario_id);
$fichajes_hoy = $this->obtener_fichajes_hoy($usuario_id);
$mostrar_historial = filter_var($atts['mostrar_historial'], FILTER_VALIDATE_BOOLEAN);
$mostrar_resumen = filter_var($atts['mostrar_resumen'], FILTER_VALIDATE_BOOLEAN);
?>

<div class="fichaje-panel">
    <!-- Reloj y Estado -->
    <div class="fichaje-panel-header">
        <div class="fichaje-reloj" id="fichaje-reloj">
            <span class="reloj-hora"><?php echo esc_html(current_time('H:i')); ?></span>
            <span class="reloj-fecha"><?php echo esc_html(date_i18n('l, j \d\e F')); ?></span>
        </div>

        <div class="fichaje-estado-actual estado-<?php echo esc_attr($estado['estado']); ?>">
            <span class="estado-indicador"></span>
            <span class="estado-label"><?php echo esc_html($this->get_label_estado($estado['estado'])); ?></span>
        </div>
    </div>

    <!-- Botones de Fichaje -->
    <div class="fichaje-acciones">
        <?php if ($estado['estado'] === 'fuera' || $estado['estado'] === 'sin_fichar'): ?>
            <button type="button" class="fichaje-btn fichaje-btn-entrada fichaje-btn-principal" data-action="entrada">
                <span class="btn-icono dashicons dashicons-yes-alt"></span>
                <span class="btn-texto"><?php esc_html_e('Fichar Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>
        <?php elseif ($estado['estado'] === 'trabajando'): ?>
            <div class="fichaje-btns-row">
                <button type="button" class="fichaje-btn fichaje-btn-pausa" data-action="pausa">
                    <span class="btn-icono dashicons dashicons-coffee"></span>
                    <span class="btn-texto"><?php esc_html_e('Pausa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>
                <button type="button" class="fichaje-btn fichaje-btn-salida fichaje-btn-principal" data-action="salida">
                    <span class="btn-icono dashicons dashicons-migrate"></span>
                    <span class="btn-texto"><?php esc_html_e('Fichar Salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>
            </div>
        <?php elseif ($estado['estado'] === 'en_pausa'): ?>
            <button type="button" class="fichaje-btn fichaje-btn-reanudar fichaje-btn-principal" data-action="reanudar">
                <span class="btn-icono dashicons dashicons-controls-play"></span>
                <span class="btn-texto"><?php esc_html_e('Reanudar Jornada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Modal para notas -->
    <div class="fichaje-modal" id="fichaje-modal-notas" style="display: none;">
        <div class="fichaje-modal-content">
            <h3 class="fichaje-modal-titulo"></h3>
            <textarea id="fichaje-notas" rows="3" placeholder="<?php esc_attr_e('Notas opcionales...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>

            <div id="fichaje-pausa-tipos" style="display: none;">
                <label><?php esc_html_e('Tipo de pausa:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="fichaje-tipo-pausa">
                    <option value="comida"><?php esc_html_e('Comida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="descanso"><?php esc_html_e('Descanso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="reunion"><?php esc_html_e('Reunión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="otros"><?php esc_html_e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="fichaje-modal-acciones">
                <button type="button" class="fichaje-btn fichaje-btn-cancelar" id="fichaje-modal-cancelar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="fichaje-btn fichaje-btn-confirmar" id="fichaje-modal-confirmar">
                    <?php esc_html_e('Confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Mensaje de respuesta -->
    <div class="fichaje-mensaje" id="fichaje-mensaje"></div>

    <!-- Resumen del día -->
    <?php if (!empty($fichajes_hoy['fichajes'])): ?>
    <div class="fichaje-resumen-dia">
        <h3><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div class="fichaje-stats">
            <div class="fichaje-stat">
                <span class="stat-valor"><?php echo esc_html(count($fichajes_hoy['fichajes'])); ?></span>
                <span class="stat-label"><?php esc_html_e('Fichajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="fichaje-stat">
                <span class="stat-valor"><?php echo esc_html(number_format($fichajes_hoy['horas_trabajadas'], 1)); ?>h</span>
                <span class="stat-label"><?php esc_html_e('Trabajadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="fichaje-timeline">
            <?php foreach ($fichajes_hoy['fichajes'] as $fichaje): ?>
            <div class="fichaje-timeline-item tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                <span class="timeline-hora"><?php echo esc_html($fichaje['hora']); ?></span>
                <span class="timeline-tipo"><?php echo esc_html(ucfirst(str_replace('_', ' ', $fichaje['tipo']))); ?></span>
                <?php if (!empty($fichaje['notas'])): ?>
                <span class="timeline-notas"><?php echo esc_html($fichaje['notas']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Link a historial completo -->
    <?php if ($mostrar_historial): ?>
    <div class="fichaje-panel-links">
        <a href="<?php echo esc_url(home_url('/fichaje-empleados/mis-fichajes/')); ?>" class="fichaje-link">
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Ver historial completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php if ($mostrar_resumen): ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'resumen', home_url('/fichaje-empleados/mis-fichajes/'))); ?>" class="fichaje-link">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Resumen mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

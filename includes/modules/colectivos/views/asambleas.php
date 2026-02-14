<?php
/**
 * Vista: Asambleas de colectivo
 *
 * @package FlavorChatIA
 * @var array  $asambleas             Lista de asambleas
 * @var int    $colectivo_id          ID del colectivo
 * @var int    $identificador_usuario ID del usuario actual
 * @var string $rol_usuario           Rol del usuario
 * @var bool   $puede_convocar        Si puede convocar asambleas
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-col-asambleas" data-colectivo="<?php echo esc_attr($colectivo_id); ?>">
    <div class="flavor-col-asambleas-header">
        <h2><?php esc_html_e('Asambleas', 'flavor-chat-ia'); ?></h2>

        <div class="flavor-col-filtros-inline">
            <select id="filtro-estado-asamblea" class="flavor-col-select-sm">
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                <option value="convocada"><?php esc_html_e('Convocadas', 'flavor-chat-ia'); ?></option>
                <option value="finalizada"><?php esc_html_e('Finalizadas', 'flavor-chat-ia'); ?></option>
            </select>

            <?php if ($puede_convocar): ?>
                <button type="button" class="flavor-col-btn flavor-col-btn-primary flavor-col-btn-sm" id="col-convocar-asamblea">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Convocar asamblea', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($asambleas)): ?>
        <div class="flavor-col-vacio">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h3><?php esc_html_e('No hay asambleas', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('No hay asambleas programadas para este colectivo.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-col-asambleas-timeline">
            <?php
            $asambleas_futuras = array_filter($asambleas, function($asamblea) {
                return strtotime($asamblea['fecha']) >= time() && $asamblea['estado'] === 'convocada';
            });

            $asambleas_pasadas = array_filter($asambleas, function($asamblea) {
                return strtotime($asamblea['fecha']) < time() || $asamblea['estado'] !== 'convocada';
            });
            ?>

            <?php if (!empty($asambleas_futuras)): ?>
                <div class="flavor-col-asambleas-seccion">
                    <h3 class="flavor-col-seccion-titulo"><?php esc_html_e('Próximas asambleas', 'flavor-chat-ia'); ?></h3>

                    <?php foreach ($asambleas_futuras as $asamblea): ?>
                        <article class="flavor-col-asamblea-card flavor-col-asamblea-proxima">
                            <div class="flavor-col-asamblea-fecha-grande">
                                <span class="dia"><?php echo esc_html(date_i18n('d', strtotime($asamblea['fecha']))); ?></span>
                                <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($asamblea['fecha']))); ?></span>
                                <span class="anio"><?php echo esc_html(date_i18n('Y', strtotime($asamblea['fecha']))); ?></span>
                            </div>

                            <div class="flavor-col-asamblea-contenido">
                                <div class="flavor-col-asamblea-header">
                                    <h4><?php echo esc_html($asamblea['titulo']); ?></h4>
                                    <span class="flavor-col-tipo-badge"><?php echo esc_html($asamblea['tipo_label']); ?></span>
                                </div>

                                <div class="flavor-col-asamblea-info">
                                    <span class="flavor-col-info-item">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html(date_i18n('H:i', strtotime($asamblea['fecha']))); ?>
                                    </span>
                                    <?php if (!empty($asamblea['lugar'])): ?>
                                        <span class="flavor-col-info-item">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($asamblea['lugar']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="flavor-col-info-item">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php printf(
                                            esc_html__('%d confirmados', 'flavor-chat-ia'),
                                            $asamblea['num_asistentes']
                                        ); ?>
                                    </span>
                                </div>

                                <?php if (!empty($asamblea['orden_del_dia'])): ?>
                                    <details class="flavor-col-orden-dia">
                                        <summary><?php esc_html_e('Ver orden del día', 'flavor-chat-ia'); ?></summary>
                                        <div class="flavor-col-orden-contenido">
                                            <?php echo nl2br(esc_html($asamblea['orden_del_dia'])); ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-col-asamblea-acciones">
                                <?php if ($identificador_usuario && $rol_usuario): ?>
                                    <button type="button" class="flavor-col-btn flavor-col-btn-primary flavor-col-btn-confirmar-asistencia"
                                            data-asamblea="<?php echo esc_attr($asamblea['id']); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e('Confirmar asistencia', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($asambleas_pasadas)): ?>
                <div class="flavor-col-asambleas-seccion">
                    <h3 class="flavor-col-seccion-titulo"><?php esc_html_e('Asambleas anteriores', 'flavor-chat-ia'); ?></h3>

                    <?php foreach ($asambleas_pasadas as $asamblea): ?>
                        <article class="flavor-col-asamblea-card flavor-col-asamblea-pasada">
                            <div class="flavor-col-asamblea-fecha-compacta">
                                <span><?php echo esc_html(date_i18n('j M Y', strtotime($asamblea['fecha']))); ?></span>
                            </div>

                            <div class="flavor-col-asamblea-contenido">
                                <h4><?php echo esc_html($asamblea['titulo']); ?></h4>
                                <div class="flavor-col-asamblea-meta-compacta">
                                    <span class="flavor-col-tipo-sm"><?php echo esc_html($asamblea['tipo_label']); ?></span>
                                    <span class="flavor-col-asistentes-sm">
                                        <?php printf(
                                            esc_html__('%d asistentes', 'flavor-chat-ia'),
                                            $asamblea['num_asistentes']
                                        ); ?>
                                    </span>
                                    <span class="flavor-col-estado flavor-col-estado-<?php echo esc_attr($asamblea['estado']); ?>">
                                        <?php echo esc_html($asamblea['estado_label']); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($asamblea['tiene_acta']): ?>
                                <div class="flavor-col-asamblea-acciones">
                                    <button type="button" class="flavor-col-btn flavor-col-btn-sm flavor-col-btn-outline">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <?php esc_html_e('Ver acta', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

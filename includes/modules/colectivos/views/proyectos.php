<?php
/**
 * Vista: Proyectos de colectivo
 *
 * @package FlavorChatIA
 * @var array $proyectos             Lista de proyectos
 * @var int   $colectivo_id          ID del colectivo
 * @var int   $identificador_usuario ID del usuario actual
 * @var bool  $es_miembro            Si el usuario es miembro
 */

if (!defined('ABSPATH')) {
    exit;
}

$colectivo_id = isset($colectivo_id) ? (int) $colectivo_id : 0;
$es_miembro = !empty($es_miembro);
$proyectos = is_array($proyectos ?? null) ? $proyectos : [];
?>

<div class="flavor-col-proyectos" data-colectivo="<?php echo esc_attr($colectivo_id); ?>">
    <div class="flavor-col-proyectos-header">
        <h2><?php esc_html_e('Proyectos', 'flavor-chat-ia'); ?></h2>

        <div class="flavor-col-filtros-inline">
            <select id="filtro-estado-proyecto" class="flavor-col-select-sm">
                <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                <option value="planificado"><?php esc_html_e('Planificados', 'flavor-chat-ia'); ?></option>
                <option value="en_curso"><?php esc_html_e('En curso', 'flavor-chat-ia'); ?></option>
                <option value="completado"><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></option>
            </select>

            <?php if ($es_miembro): ?>
                <button type="button" class="flavor-col-btn flavor-col-btn-primary flavor-col-btn-sm" id="col-crear-proyecto">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Nuevo proyecto', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-col-vacio">
            <span class="dashicons dashicons-portfolio"></span>
            <h3><?php esc_html_e('No hay proyectos', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Este colectivo aún no tiene proyectos registrados.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-col-proyectos-grid">
            <?php foreach ($proyectos as $proyecto): ?>
                <article class="flavor-col-proyecto-card" data-id="<?php echo esc_attr($proyecto['id']); ?>">
                    <div class="flavor-col-proyecto-estado">
                        <span class="flavor-col-badge flavor-col-badge-<?php echo esc_attr($proyecto['estado']); ?>">
                            <?php echo esc_html($proyecto['estado_label']); ?>
                        </span>
                    </div>

                    <h3 class="flavor-col-proyecto-titulo"><?php echo esc_html($proyecto['titulo']); ?></h3>

                    <?php if (!empty($proyecto['descripcion'])): ?>
                        <p class="flavor-col-proyecto-descripcion">
                            <?php echo esc_html(wp_trim_words($proyecto['descripcion'], 20)); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flavor-col-proyecto-detalles">
                        <?php if ($proyecto['presupuesto'] > 0): ?>
                            <div class="flavor-col-proyecto-detalle">
                                <span class="dashicons dashicons-money-alt"></span>
                                <span><?php echo esc_html($proyecto['presupuesto_fmt']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($proyecto['fecha_inicio'])): ?>
                            <div class="flavor-col-proyecto-detalle">
                                <span class="dashicons dashicons-calendar"></span>
                                <span><?php echo esc_html(date_i18n('j M Y', strtotime($proyecto['fecha_inicio']))); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($proyecto['responsable']): ?>
                            <div class="flavor-col-proyecto-detalle">
                                <img src="<?php echo esc_url($proyecto['responsable']['avatar']); ?>"
                                     alt="" class="flavor-col-avatar-xs">
                                <span><?php echo esc_html($proyecto['responsable']['nombre']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($proyecto['estado'] === 'en_curso'): ?>
                        <div class="flavor-col-proyecto-progreso">
                            <div class="flavor-col-progreso-header">
                                <span><?php esc_html_e('Progreso', 'flavor-chat-ia'); ?></span>
                                <span class="valor"><?php echo intval($proyecto['progreso']); ?>%</span>
                            </div>
                            <div class="flavor-col-progreso-barra">
                                <div class="flavor-col-progreso-fill" style="width: <?php echo intval($proyecto['progreso']); ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($es_miembro && $proyecto['estado'] === 'en_curso'): ?>
                        <div class="flavor-col-proyecto-acciones">
                            <button type="button" class="flavor-col-btn flavor-col-btn-sm flavor-col-btn-outline flavor-col-actualizar-progreso"
                                    data-proyecto="<?php echo esc_attr($proyecto['id']); ?>"
                                    data-progreso="<?php echo esc_attr($proyecto['progreso']); ?>">
                                <?php esc_html_e('Actualizar progreso', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

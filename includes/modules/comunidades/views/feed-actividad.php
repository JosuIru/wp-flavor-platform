<?php
/**
 * Vista: Feed de actividad de comunidad
 *
 * @package FlavorChatIA
 * @var array $actividades    Lista de actividades
 * @var int   $comunidad_id   ID de la comunidad
 * @var array $atributos      Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$iconos_tipo = [
    'publicacion' => 'dashicons-format-status',
    'nuevo_miembro' => 'dashicons-admin-users',
    'evento' => 'dashicons-calendar-alt',
    'actualizacion' => 'dashicons-update',
];
?>

<div class="flavor-com-feed-actividad" data-comunidad="<?php echo esc_attr($comunidad_id); ?>">
    <?php if (empty($actividades)): ?>
        <div class="flavor-com-sin-actividad">
            <span class="dashicons dashicons-format-chat"></span>
            <p><?php esc_html_e('No hay actividad reciente en esta comunidad.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-com-actividades">
            <?php foreach ($actividades as $actividad):
                $icono = $iconos_tipo[$actividad['tipo']] ?? 'dashicons-admin-post';
            ?>
                <article class="flavor-com-actividad flavor-com-actividad-<?php echo esc_attr($actividad['tipo']); ?>">
                    <div class="flavor-com-actividad-icono">
                        <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                    </div>

                    <div class="flavor-com-actividad-content">
                        <div class="flavor-com-actividad-header">
                            <img src="<?php echo esc_url($actividad['usuario_avatar'] ?? get_avatar_url(0)); ?>"
                                 alt="" class="flavor-com-avatar-sm">
                            <span class="flavor-com-actividad-autor">
                                <?php echo esc_html($actividad['usuario_nombre'] ?? __('Usuario', 'flavor-chat-ia')); ?>
                            </span>
                            <span class="flavor-com-actividad-tiempo">
                                <?php echo esc_html(human_time_diff(strtotime($actividad['fecha']), current_time('timestamp'))); ?>
                            </span>
                        </div>

                        <?php if ($actividad['tipo'] === 'publicacion'): ?>
                            <div class="flavor-com-actividad-texto">
                                <?php echo nl2br(esc_html($actividad['contenido'])); ?>
                            </div>
                        <?php elseif ($actividad['tipo'] === 'nuevo_miembro'): ?>
                            <div class="flavor-com-actividad-sistema">
                                <?php printf(
                                    esc_html__('%s se ha unido a la comunidad', 'flavor-chat-ia'),
                                    '<strong>' . esc_html($actividad['usuario_nombre']) . '</strong>'
                                ); ?>
                            </div>
                        <?php elseif ($actividad['tipo'] === 'evento'): ?>
                            <div class="flavor-com-actividad-evento">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html($actividad['contenido']); ?>
                            </div>
                        <?php else: ?>
                            <div class="flavor-com-actividad-texto">
                                <?php echo esc_html($actividad['contenido']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($actividad['tipo'] === 'publicacion'): ?>
                        <div class="flavor-com-actividad-acciones">
                            <button type="button" class="flavor-com-btn-like" data-id="<?php echo esc_attr($actividad['id']); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <span class="count"><?php echo intval($actividad['likes'] ?? 0); ?></span>
                            </button>
                            <button type="button" class="flavor-com-btn-comentar" data-id="<?php echo esc_attr($actividad['id']); ?>">
                                <span class="dashicons dashicons-admin-comments"></span>
                                <span class="count"><?php echo intval($actividad['comentarios'] ?? 0); ?></span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-com-cargar-mas-actividad" style="display: none;">
            <button type="button" class="flavor-com-btn flavor-com-btn-secondary" id="com-mas-actividad">
                <?php esc_html_e('Cargar más', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

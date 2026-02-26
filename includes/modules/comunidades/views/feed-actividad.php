<?php
/**
 * Vista: Feed de Actividad de Comunidad
 *
 * Variables disponibles:
 * - $actividades: array de actividades
 * - $comunidad_id: int ID de la comunidad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-com-feed-contenedor" data-comunidad-id="<?php echo esc_attr($comunidad_id); ?>">
    <?php if (empty($actividades)): ?>
        <div class="flavor-com-feed-vacio">
            <span class="dashicons dashicons-admin-comments"></span>
            <p><?php esc_html_e('Aun no hay actividad en esta comunidad.', 'flavor-chat-ia'); ?></p>
            <p><?php esc_html_e('Se el primero en publicar algo!', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($actividades as $actividad): ?>
        <article class="flavor-com-actividad" data-id="<?php echo esc_attr($actividad->id ?? 0); ?>">
            <div class="flavor-com-actividad-avatar">
                <?php echo get_avatar($actividad->usuario_id ?? 0, 40); ?>
            </div>
            <div class="flavor-com-actividad-contenido">
                <div class="flavor-com-actividad-header">
                    <span class="flavor-com-actividad-autor">
                        <?php echo esc_html($actividad->autor_nombre ?? __('Usuario', 'flavor-chat-ia')); ?>
                    </span>
                    <span class="flavor-com-actividad-fecha">
                        <?php
                        $fecha = strtotime($actividad->fecha ?? 'now');
                        $diferencia = time() - $fecha;

                        if ($diferencia < 60) {
                            esc_html_e('Hace un momento', 'flavor-chat-ia');
                        } elseif ($diferencia < 3600) {
                            printf(esc_html__('Hace %d minutos', 'flavor-chat-ia'), floor($diferencia / 60));
                        } elseif ($diferencia < 86400) {
                            printf(esc_html__('Hace %d horas', 'flavor-chat-ia'), floor($diferencia / 3600));
                        } else {
                            echo esc_html(date_i18n(get_option('date_format'), $fecha));
                        }
                        ?>
                    </span>
                </div>

                <div class="flavor-com-actividad-texto">
                    <?php echo wp_kses_post(nl2br($actividad->contenido ?? '')); ?>
                </div>

                <?php if (!empty($actividad->imagen)): ?>
                <div class="flavor-com-actividad-imagen">
                    <img src="<?php echo esc_url($actividad->imagen); ?>" alt="" loading="lazy">
                </div>
                <?php endif; ?>

                <div class="flavor-com-actividad-acciones">
                    <?php
                    $liked_class = !empty($actividad->liked) ? ' liked' : '';
                    $likes_count = isset($actividad->likes) ? intval($actividad->likes) : 0;
                    ?>
                    <button type="button" class="flavor-com-actividad-accion flavor-com-btn-like<?php echo esc_attr($liked_class); ?>" data-actividad-id="<?php echo esc_attr($actividad->id ?? 0); ?>" data-liked="<?php echo $actividad->liked ? '1' : '0'; ?>">
                        <span class="dashicons dashicons-heart"></span>
                        <span class="count"><?php echo esc_html($likes_count); ?></span>
                    </button>
                    <button type="button" class="flavor-com-actividad-accion flavor-com-btn-comentar">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span class="count"><?php echo esc_html($actividad->comentarios ?? 0); ?></span>
                    </button>
                </div>
            </div>
        </article>
        <?php endforeach; ?>

        <div class="flavor-com-feed-cargar-mas" style="display: none;">
            <button type="button" class="flavor-com-boton flavor-com-boton-secundario">
                <?php esc_html_e('Cargar mas', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

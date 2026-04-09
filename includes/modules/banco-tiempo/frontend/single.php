<?php
/**
 * Template: Detalle de Servicio - Banco de Tiempo
 *
 * Variables disponibles:
 * - $servicio: objeto con datos del servicio
 * - $usuario: objeto WP_User del oferente
 * - $categorias: array de categorias
 * - $categoria: categoria normalizada del servicio
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_actual_id = get_current_user_id();
$es_propio = $servicio->usuario_id == $usuario_actual_id;
$estado = $servicio->estado ?? 'activo';
?>

<div class="bt-servicio-detalle">
    <div class="bt-servicio-header">
        <div class="bt-servicio-info">
            <h1 class="bt-servicio-titulo"><?php echo esc_html($servicio->titulo); ?></h1>

            <div class="bt-servicio-meta">
                <span class="bt-badge bt-badge-categoria"><?php echo esc_html($categorias[$categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                <span class="bt-servicio-horas">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html(number_format((float)($servicio->horas_estimadas ?? 1), 1)); ?>h
                </span>
                <?php if ($estado !== 'activo'): ?>
                <span class="bt-badge bt-badge-pausado"><?php _e('Pausado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bt-servicio-body">
        <div class="bt-servicio-contenido">
            <h2><?php _e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="bt-servicio-descripcion">
                <?php echo wp_kses_post(nl2br($servicio->descripcion)); ?>
            </div>

            <?php if (!empty($servicio->disponibilidad)): ?>
            <h3><?php _e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php echo esc_html($servicio->disponibilidad); ?></p>
            <?php endif; ?>

            <?php if (!empty($servicio->zona)): ?>
            <h3><?php _e('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php echo esc_html($servicio->zona); ?></p>
            <?php endif; ?>
        </div>

        <div class="bt-servicio-sidebar">
            <div class="bt-card bt-card-oferente">
                <h3><?php _e('Ofrecido por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="bt-oferente-info">
                    <?php echo get_avatar($usuario->ID ?? 0, 64); ?>
                    <div class="bt-oferente-datos">
                        <strong><?php echo esc_html($usuario->display_name ?? __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                        <?php if (!empty($servicio->fecha_publicacion)): ?>
                        <small><?php printf(__('Publicado el %s', FLAVOR_PLATFORM_TEXT_DOMAIN), date_i18n('d/m/Y', strtotime($servicio->fecha_publicacion))); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!$es_propio && $estado === 'activo' && is_user_logged_in()): ?>
            <div class="bt-card bt-card-solicitar">
                <h3><?php _e('Solicitar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php printf(__('Este servicio tiene un valor de %s horas.', FLAVOR_PLATFORM_TEXT_DOMAIN), '<strong>' . number_format((float)($servicio->horas_estimadas ?? 1), 1) . '</strong>'); ?></p>
                <form method="post" class="bt-solicitar-form">
                    <?php wp_nonce_field('bt_solicitar_' . $servicio->id); ?>
                    <input type="hidden" name="servicio_id" value="<?php echo esc_attr($servicio->id); ?>">

                    <div class="bt-form-group">
                        <label for="mensaje_solicitud"><?php _e('Mensaje (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="mensaje_solicitud" name="mensaje" rows="3" placeholder="<?php esc_attr_e('Indica cuando te vendria bien, detalles adicionales...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>

                    <button type="submit" name="bt_solicitar_servicio" class="bt-btn bt-btn-primary bt-btn-block">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Solicitar intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </form>
            </div>
            <?php elseif (!is_user_logged_in()): ?>
            <div class="bt-card">
                <p><?php _e('Inicia sesion para solicitar este servicio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="bt-btn bt-btn-primary bt-btn-block">
                    <?php _e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php elseif ($es_propio): ?>
            <div class="bt-card">
                <p><?php _e('Este es tu propio servicio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(add_query_arg(['accion' => 'editar', 'id' => $servicio->id], home_url('/banco-tiempo/ofrecer/'))); ?>" class="bt-btn bt-btn-secondary bt-btn-block">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Editar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bt-servicio-nav">
        <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="bt-btn bt-btn-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php _e('Volver al catalogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>

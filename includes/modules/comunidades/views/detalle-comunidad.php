<?php
/**
 * Vista: Detalle de Comunidad
 *
 * Variables disponibles:
 * - $comunidad: objeto con datos de la comunidad
 * - $miembros: array de miembros
 * - $identificador_usuario: int ID del usuario actual
 * - $es_miembro: bool si el usuario es miembro
 * - $rol_usuario: string rol del usuario en la comunidad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$es_admin = ($rol_usuario === 'admin' || $rol_usuario === 'fundador');
?>

<div class="flavor-com-detalle-contenedor">
    <header class="flavor-com-detalle-header">
        <?php if (!empty($comunidad->imagen_portada)): ?>
        <div class="flavor-com-portada">
            <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="<?php echo esc_attr($comunidad->nombre); ?>">
        </div>
        <?php else: ?>
        <div class="flavor-com-portada flavor-com-portada-placeholder">
            <span class="dashicons dashicons-groups"></span>
        </div>
        <?php endif; ?>

        <div class="flavor-com-detalle-info">
            <div class="flavor-com-detalle-titulo-wrapper">
                <h1 class="flavor-com-detalle-titulo"><?php echo esc_html($comunidad->nombre); ?></h1>
                <?php if ($comunidad->tipo === 'privada'): ?>
                <span class="flavor-com-badge-privada">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('Privada', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <p class="flavor-com-detalle-descripcion"><?php echo esc_html($comunidad->descripcion); ?></p>

            <div class="flavor-com-detalle-stats">
                <span class="flavor-com-stat">
                    <span class="dashicons dashicons-admin-users"></span>
                    <strong><?php echo esc_html(count($miembros)); ?></strong> <?php esc_html_e('miembros', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-com-stat">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php printf(
                        esc_html__('Creada el %s', 'flavor-chat-ia'),
                        date_i18n(get_option('date_format'), strtotime($comunidad->fecha_creacion))
                    ); ?>
                </span>
            </div>

            <div class="flavor-com-detalle-acciones">
                <?php if (!$identificador_usuario): ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-com-boton flavor-com-boton-primario">
                        <?php esc_html_e('Inicia sesion para unirte', 'flavor-chat-ia'); ?>
                    </a>
                <?php elseif ($es_miembro): ?>
                    <?php if ($es_admin): ?>
                        <a href="<?php echo esc_url(add_query_arg(['comunidad' => $comunidad->id, 'gestionar' => 1], home_url('/comunidad/'))); ?>" class="flavor-com-boton flavor-com-boton-secundario">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Gestionar', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="flavor-com-boton flavor-com-boton-peligro-outline flavor-com-btn-salir" data-comunidad-id="<?php echo esc_attr($comunidad->id); ?>">
                        <?php esc_html_e('Abandonar comunidad', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="flavor-com-boton flavor-com-boton-primario flavor-com-btn-unirse" data-comunidad-id="<?php echo esc_attr($comunidad->id); ?>">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Unirse a la comunidad', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="flavor-com-detalle-contenido">
        <div class="flavor-com-tabs">
            <button type="button" class="flavor-com-tab active" data-tab="actividad">
                <span class="dashicons dashicons-admin-comments"></span>
                <?php esc_html_e('Actividad', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-com-tab" data-tab="miembros">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Miembros', 'flavor-chat-ia'); ?>
            </button>
            <?php if ($es_miembro && !empty($chat_grupos_activo)): ?>
            <button type="button" class="flavor-com-tab" data-tab="chat">
                <span class="dashicons dashicons-format-chat"></span>
                <?php esc_html_e('Chat', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
            <?php if (!empty($comunidad->reglas)): ?>
            <button type="button" class="flavor-com-tab" data-tab="reglas">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e('Reglas', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <div class="flavor-com-tab-content active" id="tab-actividad">
            <?php if ($es_miembro): ?>
            <div class="flavor-com-publicar">
                <form id="flavor-com-form-publicar" class="flavor-com-form-publicar">
                    <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad->id); ?>">
                    <textarea name="contenido" class="flavor-com-textarea" rows="3"
                              placeholder="<?php esc_attr_e('Comparte algo con la comunidad...', 'flavor-chat-ia'); ?>"></textarea>
                    <div class="flavor-com-publicar-acciones">
                        <button type="submit" class="flavor-com-boton flavor-com-boton-primario">
                            <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="flavor-com-feed" id="flavor-com-feed">
                <div class="flavor-com-cargando">
                    <span class="flavor-com-spinner"></span>
                    <?php esc_html_e('Cargando actividad...', 'flavor-chat-ia'); ?>
                </div>
            </div>
        </div>

        <div class="flavor-com-tab-content" id="tab-miembros">
            <div class="flavor-com-miembros-lista">
                <?php foreach ($miembros as $miembro): ?>
                <div class="flavor-com-miembro">
                    <div class="flavor-com-miembro-avatar">
                        <?php echo get_avatar($miembro['user_id'], 48); ?>
                    </div>
                    <div class="flavor-com-miembro-info">
                        <span class="flavor-com-miembro-nombre"><?php echo esc_html($miembro['display_name']); ?></span>
                        <span class="flavor-com-miembro-rol flavor-com-rol-<?php echo esc_attr($miembro['rol']); ?>">
                            <?php
                            $roles_nombres = [
                                'fundador' => __('Fundador', 'flavor-chat-ia'),
                                'admin' => __('Administrador', 'flavor-chat-ia'),
                                'moderador' => __('Moderador', 'flavor-chat-ia'),
                                'miembro' => __('Miembro', 'flavor-chat-ia'),
                            ];
                            echo esc_html($roles_nombres[$miembro['rol']] ?? ucfirst($miembro['rol']));
                            ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($comunidad->reglas)): ?>
        <div class="flavor-com-tab-content" id="tab-reglas">
            <div class="flavor-com-reglas">
                <h3><?php esc_html_e('Reglas de la comunidad', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-com-reglas-contenido">
                    <?php echo nl2br(esc_html($comunidad->reglas)); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($es_miembro && !empty($chat_grupos_activo)): ?>
        <div class="flavor-com-tab-content" id="tab-chat">
            <div class="flavor-com-chat-container">
                <?php
                // Incluir el chat del grupo de la comunidad
                echo do_shortcode('[flavor_chat_grupo id="' . esc_attr($grupo_chat_id) . '" embebido="1"]');
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

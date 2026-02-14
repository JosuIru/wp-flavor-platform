<?php
/**
 * Vista: Mis Comunidades
 *
 * Variables disponibles:
 * - $comunidades: array de comunidades del usuario
 * - $categorias: array de categorias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-com-mis-comunidades-contenedor">
    <div class="flavor-com-mis-comunidades-header">
        <h2><?php esc_html_e('Mis Comunidades', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(home_url('/crear-comunidad/')); ?>" class="flavor-com-boton flavor-com-boton-primario">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($comunidades)): ?>
        <div class="flavor-com-vacio">
            <span class="dashicons dashicons-groups"></span>
            <p><?php esc_html_e('Aun no perteneces a ninguna comunidad.', 'flavor-chat-ia'); ?></p>
            <p><?php esc_html_e('Explora las comunidades disponibles y unete a las que te interesen.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="flavor-com-boton flavor-com-boton-primario">
                <?php esc_html_e('Explorar comunidades', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="flavor-com-comunidades-lista">
            <?php foreach ($comunidades as $comunidad):
                $categoria_nombre = $categorias[$comunidad->categoria] ?? ucfirst($comunidad->categoria ?? 'otros');
                $roles_nombres = [
                    'fundador' => __('Fundador', 'flavor-chat-ia'),
                    'admin' => __('Administrador', 'flavor-chat-ia'),
                    'moderador' => __('Moderador', 'flavor-chat-ia'),
                    'miembro' => __('Miembro', 'flavor-chat-ia'),
                ];
                $rol_nombre = $roles_nombres[$comunidad->rol ?? 'miembro'] ?? __('Miembro', 'flavor-chat-ia');
            ?>
            <article class="flavor-com-mi-comunidad" data-id="<?php echo esc_attr($comunidad->id); ?>">
                <div class="flavor-com-mi-comunidad-imagen">
                    <?php if (!empty($comunidad->imagen_portada)): ?>
                        <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="<?php echo esc_attr($comunidad->nombre); ?>">
                    <?php else: ?>
                        <span class="dashicons dashicons-groups"></span>
                    <?php endif; ?>
                </div>

                <div class="flavor-com-mi-comunidad-info">
                    <div class="flavor-com-mi-comunidad-titulo-wrapper">
                        <h3 class="flavor-com-mi-comunidad-titulo">
                            <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad->id, home_url('/comunidad/'))); ?>">
                                <?php echo esc_html($comunidad->nombre); ?>
                            </a>
                        </h3>
                        <span class="flavor-com-mi-comunidad-rol flavor-com-rol-<?php echo esc_attr($comunidad->rol ?? 'miembro'); ?>">
                            <?php echo esc_html($rol_nombre); ?>
                        </span>
                    </div>

                    <div class="flavor-com-mi-comunidad-meta">
                        <span class="flavor-com-categoria flavor-com-categoria-<?php echo esc_attr($comunidad->categoria ?? 'otros'); ?>">
                            <?php echo esc_html($categoria_nombre); ?>
                        </span>
                        <span class="flavor-com-meta-item">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($comunidad->total_miembros ?? 0); ?>
                        </span>
                    </div>

                    <?php if (!empty($comunidad->actividad_reciente)): ?>
                    <p class="flavor-com-mi-comunidad-actividad">
                        <span class="dashicons dashicons-clock"></span>
                        <?php printf(
                            esc_html__('Ultima actividad: %s', 'flavor-chat-ia'),
                            human_time_diff(strtotime($comunidad->actividad_reciente), current_time('timestamp'))
                        ); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="flavor-com-mi-comunidad-acciones">
                    <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad->id, home_url('/comunidad/'))); ?>" class="flavor-com-boton flavor-com-boton-primario">
                        <?php esc_html_e('Entrar', 'flavor-chat-ia'); ?>
                    </a>

                    <?php if (in_array($comunidad->rol ?? '', ['fundador', 'admin'])): ?>
                    <a href="<?php echo esc_url(add_query_arg(['comunidad' => $comunidad->id, 'gestionar' => 1], home_url('/comunidad/'))); ?>" class="flavor-com-boton flavor-com-boton-secundario">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

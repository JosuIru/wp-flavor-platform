<?php
/**
 * Vista: Mis comunidades
 *
 * @package FlavorChatIA
 * @var array $comunidades Lista de comunidades del usuario
 * @var array $categorias  Categorías disponibles
 */

if (!defined('ABSPATH')) {
    exit;
}

$etiquetas_rol = [
    'admin' => __('Administrador', 'flavor-chat-ia'),
    'moderador' => __('Moderador', 'flavor-chat-ia'),
    'miembro' => __('Miembro', 'flavor-chat-ia'),
];
?>

<div class="flavor-com-mis-comunidades">
    <div class="flavor-com-header">
        <h2><?php esc_html_e('Mis comunidades', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(home_url('/comunidades/crear/')); ?>" class="flavor-com-btn flavor-com-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($comunidades)): ?>
        <div class="flavor-com-vacio">
            <span class="dashicons dashicons-groups"></span>
            <h3><?php esc_html_e('Aún no perteneces a ninguna comunidad', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Explora las comunidades disponibles y únete a las que te interesen.', 'flavor-chat-ia'); ?></p>
            <div class="flavor-com-acciones">
                <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="flavor-com-btn flavor-com-btn-secondary">
                    <?php esc_html_e('Explorar comunidades', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/comunidades/crear/')); ?>" class="flavor-com-btn flavor-com-btn-primary">
                    <?php esc_html_e('Crear mi comunidad', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="flavor-com-lista-vertical">
            <?php foreach ($comunidades as $comunidad):
                $etiqueta_categoria = $categorias[$comunidad['categoria']] ?? ucfirst($comunidad['categoria']);
                $etiqueta_rol = $etiquetas_rol[$comunidad['rol']] ?? ucfirst($comunidad['rol']);
            ?>
                <article class="flavor-com-item" data-id="<?php echo esc_attr($comunidad['id']); ?>">
                    <?php if (!empty($comunidad['imagen'])): ?>
                        <div class="flavor-com-item-imagen">
                            <img src="<?php echo esc_url($comunidad['imagen']); ?>" alt="">
                        </div>
                    <?php else: ?>
                        <div class="flavor-com-item-imagen flavor-com-placeholder">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-com-item-content">
                        <div class="flavor-com-item-header">
                            <h3 class="flavor-com-item-nombre">
                                <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad['id'], home_url('/comunidades/'))); ?>">
                                    <?php echo esc_html($comunidad['nombre']); ?>
                                </a>
                            </h3>
                            <span class="flavor-com-rol flavor-com-rol-<?php echo esc_attr($comunidad['rol']); ?>">
                                <?php echo esc_html($etiqueta_rol); ?>
                            </span>
                        </div>

                        <div class="flavor-com-item-meta">
                            <span class="flavor-com-categoria">
                                <?php echo esc_html($etiqueta_categoria); ?>
                            </span>
                            <span class="flavor-com-miembros">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo intval($comunidad['miembros_count']); ?>
                            </span>
                            <?php if ($comunidad['membresia_estado'] === 'pendiente'): ?>
                                <span class="flavor-com-estado-pendiente">
                                    <?php esc_html_e('Solicitud pendiente', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-com-item-acciones">
                        <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad['id'], home_url('/comunidades/'))); ?>"
                           class="flavor-com-btn flavor-com-btn-ver">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if ($comunidad['rol'] === 'admin'): ?>
                            <button type="button" class="flavor-com-btn flavor-com-btn-link">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

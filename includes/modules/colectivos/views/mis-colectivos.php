<?php
/**
 * Vista: Mis colectivos
 *
 * @package FlavorChatIA
 * @var array $colectivos     Lista de colectivos del usuario
 * @var array $etiquetas_rol  Etiquetas de roles
 * @var array $atributos      Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-col-mis-colectivos">
    <div class="flavor-col-header">
        <h2><?php esc_html_e('Mis colectivos', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(home_url('/mi-portal/colectivos/crear/')); ?>" class="flavor-col-btn flavor-col-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear colectivo', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($colectivos)): ?>
        <div class="flavor-col-vacio">
            <span class="dashicons dashicons-networking"></span>
            <h3><?php esc_html_e('Aún no perteneces a ningún colectivo', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Explora los colectivos disponibles y únete a los que te interesen.', 'flavor-chat-ia'); ?></p>
            <div class="flavor-col-vacio-acciones">
                <a href="<?php echo esc_url(home_url('/mi-portal/colectivos/')); ?>" class="flavor-col-btn flavor-col-btn-secondary">
                    <?php esc_html_e('Explorar colectivos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/colectivos/crear/')); ?>" class="flavor-col-btn flavor-col-btn-primary">
                    <?php esc_html_e('Crear mi colectivo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="flavor-col-lista-vertical">
            <?php foreach ($colectivos as $colectivo): ?>
                <article class="flavor-col-item" data-id="<?php echo esc_attr($colectivo['id']); ?>">
                    <?php if (!empty($colectivo['imagen'])): ?>
                        <div class="flavor-col-item-imagen">
                            <img src="<?php echo esc_url($colectivo['imagen']); ?>" alt="">
                        </div>
                    <?php else: ?>
                        <div class="flavor-col-item-imagen flavor-col-placeholder">
                            <span class="dashicons dashicons-networking"></span>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-col-item-content">
                        <div class="flavor-col-item-header">
                            <h3 class="flavor-col-item-nombre">
                                <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], home_url('/mi-portal/colectivos/'))); ?>">
                                    <?php echo esc_html($colectivo['nombre']); ?>
                                </a>
                            </h3>
                            <span class="flavor-col-rol flavor-col-rol-<?php echo esc_attr($colectivo['rol']); ?>">
                                <?php echo esc_html($colectivo['rol_label']); ?>
                            </span>
                        </div>

                        <div class="flavor-col-item-meta">
                            <span class="flavor-col-tipo">
                                <?php echo esc_html($colectivo['tipo_label']); ?>
                            </span>
                            <span class="flavor-col-miembros">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo intval($colectivo['miembros_count']); ?>
                            </span>
                            <span class="flavor-col-proyectos">
                                <span class="dashicons dashicons-portfolio"></span>
                                <?php echo intval($colectivo['proyectos_count']); ?>
                            </span>
                            <?php if ($colectivo['membresia_estado'] === 'pendiente'): ?>
                                <span class="flavor-col-estado-pendiente">
                                    <?php esc_html_e('Solicitud pendiente', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-col-item-acciones">
                        <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], home_url('/mi-portal/colectivos/'))); ?>"
                           class="flavor-col-btn flavor-col-btn-ver">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if (in_array($colectivo['rol'], ['presidente', 'secretario'], true)): ?>
                            <button type="button" class="flavor-col-btn flavor-col-btn-link">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

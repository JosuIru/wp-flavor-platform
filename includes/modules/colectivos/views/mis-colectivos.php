<?php
/**
 * Vista: Mis colectivos
 *
 * @package FlavorPlatform
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
        <h2><?php esc_html_e('Mis colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('colectivos', 'crear')); ?>" class="flavor-col-btn flavor-col-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <?php if (empty($colectivos)): ?>
        <div class="flavor-col-vacio">
            <span class="dashicons dashicons-networking"></span>
            <h3><?php esc_html_e('Aún no perteneces a ningún colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Explora los colectivos disponibles y únete a los que te interesen.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-col-vacio-acciones">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('colectivos', '')); ?>" class="flavor-col-btn flavor-col-btn-secondary">
                    <?php esc_html_e('Explorar colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('colectivos', 'crear')); ?>" class="flavor-col-btn flavor-col-btn-primary">
                    <?php esc_html_e('Crear mi colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                                <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], Flavor_Platform_Helpers::get_action_url('colectivos', ''))); ?>">
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
                                    <?php esc_html_e('Solicitud pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-col-item-acciones">
                        <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], Flavor_Platform_Helpers::get_action_url('colectivos', ''))); ?>"
                           class="flavor-col-btn flavor-col-btn-ver">
                            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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

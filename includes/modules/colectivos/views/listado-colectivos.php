<?php
/**
 * Vista: Listado de colectivos
 *
 * @package FlavorChatIA
 * @var array $colectivos  Lista de colectivos
 * @var array $categorias  Etiquetas de tipo
 * @var array $atributos   Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = absint($atributos['columnas'] ?? 3);
?>

<div class="flavor-col-listado" data-columnas="<?php echo esc_attr($columnas); ?>">
    <div class="flavor-col-filtros">
        <div class="flavor-col-busqueda">
            <input type="text" id="col-buscar" class="flavor-col-input"
                   placeholder="<?php esc_attr_e('Buscar colectivos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <span class="dashicons dashicons-search"></span>
        </div>

        <div class="flavor-col-filtros-selectores">
            <select id="col-filtro-tipo" class="flavor-col-select">
                <option value=""><?php esc_html_e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($categorias as $clave => $etiqueta): ?>
                    <option value="<?php echo esc_attr($clave); ?>"><?php echo esc_html($etiqueta); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($colectivos)): ?>
        <div class="flavor-col-vacio">
            <span class="dashicons dashicons-networking"></span>
            <h3><?php esc_html_e('No hay colectivos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Sé el primero en crear un colectivo o asociación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'crear')); ?>" class="flavor-col-btn flavor-col-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Crear colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-col-grid flavor-col-grid-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($colectivos as $colectivo): ?>
                <article class="flavor-col-card" data-id="<?php echo esc_attr($colectivo['id']); ?>">
                    <div class="flavor-col-card-imagen">
                        <?php if (!empty($colectivo['imagen'])): ?>
                            <img src="<?php echo esc_url($colectivo['imagen']); ?>" alt="">
                        <?php else: ?>
                            <div class="flavor-col-placeholder">
                                <span class="dashicons dashicons-networking"></span>
                            </div>
                        <?php endif; ?>
                        <span class="flavor-col-tipo-badge flavor-col-tipo-<?php echo esc_attr($colectivo['tipo']); ?>">
                            <?php echo esc_html($colectivo['tipo_label']); ?>
                        </span>
                    </div>

                    <div class="flavor-col-card-body">
                        <h3 class="flavor-col-card-titulo">
                            <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], Flavor_Chat_Helpers::get_action_url('colectivos', ''))); ?>">
                                <?php echo esc_html($colectivo['nombre']); ?>
                            </a>
                        </h3>

                        <?php if (!empty($colectivo['sector'])): ?>
                            <span class="flavor-col-sector"><?php echo esc_html($colectivo['sector']); ?></span>
                        <?php endif; ?>

                        <div class="flavor-col-card-stats">
                            <span class="flavor-col-stat">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo intval($colectivo['miembros_count']); ?>
                                <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="flavor-col-stat">
                                <span class="dashicons dashicons-portfolio"></span>
                                <?php echo intval($colectivo['proyectos_count']); ?>
                                <?php esc_html_e('proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>

                    <div class="flavor-col-card-footer">
                        <a href="<?php echo esc_url(add_query_arg('colectivo', $colectivo['id'], Flavor_Chat_Helpers::get_action_url('colectivos', ''))); ?>"
                           class="flavor-col-btn flavor-col-btn-secondary">
                            <?php esc_html_e('Ver colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

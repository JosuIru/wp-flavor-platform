<?php
/**
 * Template: Emprendimientos Locales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$sectores = Flavor_Chat_Trabajo_Digno_Module::SECTORES;

$emprendimientos = get_posts([
    'post_type' => 'td_emprendimiento',
    'post_status' => 'publish',
    'posts_per_page' => 30,
    'orderby' => 'title',
    'order' => 'ASC',
]);
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Emprendimientos Locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Directorio de proyectos y empresas de nuestra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Tabs -->
    <div class="td-tabs">
        <button class="td-tab activo" data-tab="tab-directorio">
            <span class="dashicons dashicons-store"></span>
            <?php esc_html_e('Directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php if (is_user_logged_in()) : ?>
        <button class="td-tab" data-tab="tab-registrar">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Registrar mi emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- Tab: Directorio -->
    <div id="tab-directorio" class="td-tab-contenido">
        <?php if ($emprendimientos) : ?>
        <div class="td-emprendimientos-grid">
            <?php foreach ($emprendimientos as $emprendimiento) :
                $terms = wp_get_post_terms($emprendimiento->ID, 'td_sector');
                $sector = !empty($terms) ? $terms[0] : null;
                $sector_data = $sector ? ($sectores[$sector->slug] ?? ['nombre' => $sector->name, 'icono' => 'dashicons-store']) : ['nombre' => '', 'icono' => 'dashicons-store'];

                $tipo_org = get_post_meta($emprendimiento->ID, '_td_tipo_organizacion', true);
                $web = get_post_meta($emprendimiento->ID, '_td_web', true);
                $contacto = get_post_meta($emprendimiento->ID, '_td_contacto', true);
            ?>
            <article class="td-emprendimiento-card">
                <div class="td-emprendimiento-card__header">
                    <div class="td-emprendimiento-card__logo">
                        <?php if (has_post_thumbnail($emprendimiento->ID)) : ?>
                            <?php echo get_the_post_thumbnail($emprendimiento->ID, 'thumbnail'); ?>
                        <?php else : ?>
                            <span class="dashicons <?php echo esc_attr($sector_data['icono']); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="td-emprendimiento-card__nombre"><?php echo esc_html($emprendimiento->post_title); ?></h3>
                        <?php if ($tipo_org) : ?>
                        <span class="td-emprendimiento-card__tipo"><?php echo esc_html($tipo_org); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="td-emprendimiento-card__descripcion">
                    <?php echo esc_html(wp_trim_words($emprendimiento->post_content, 30)); ?>
                </p>

                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if ($sector) : ?>
                    <span class="td-criterio-badge">
                        <span class="dashicons <?php echo esc_attr($sector_data['icono']); ?>"></span>
                        <?php echo esc_html($sector_data['nombre']); ?>
                    </span>
                    <?php endif; ?>

                    <?php if ($web) : ?>
                    <a href="<?php echo esc_url($web); ?>" target="_blank" class="td-btn td-btn--secondary td-btn--small">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <?php esc_html_e('Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($contacto) : ?>
                    <a href="mailto:<?php echo esc_attr($contacto); ?>" class="td-btn td-btn--primary td-btn--small">
                        <span class="dashicons dashicons-email"></span>
                        <?php esc_html_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="td-empty-state">
            <span class="dashicons dashicons-store"></span>
            <p><?php esc_html_e('No hay emprendimientos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Registrar -->
    <?php if (is_user_logged_in()) : ?>
    <div id="tab-registrar" class="td-tab-contenido" style="display: none;">
        <form class="td-form td-form-emprendimiento">
            <div class="td-form-grupo">
                <label for="td-emp-nombre"><?php esc_html_e('Nombre del emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" name="nombre" id="td-emp-nombre" required
                       placeholder="<?php esc_attr_e('Nombre de tu proyecto o empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="td-form-row">
                <div class="td-form-grupo">
                    <label for="td-emp-sector"><?php esc_html_e('Sector', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="sector" id="td-emp-sector">
                        <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($sectores as $sector_id => $sector_data) : ?>
                        <option value="<?php echo esc_attr($sector_id); ?>"><?php echo esc_html($sector_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="td-form-grupo">
                    <label for="td-emp-tipo"><?php esc_html_e('Tipo de organización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo_organizacion" id="td-emp-tipo">
                        <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="cooperativa"><?php esc_html_e('Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="autonomo"><?php esc_html_e('Autónomo/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="empresa_social"><?php esc_html_e('Empresa Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="asociacion"><?php esc_html_e('Asociación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="pyme"><?php esc_html_e('PYME', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="otro"><?php esc_html_e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </div>

            <div class="td-form-row">
                <div class="td-form-grupo">
                    <label for="td-emp-web"><?php esc_html_e('Web / Redes sociales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="url" name="web" id="td-emp-web"
                           placeholder="https://...">
                </div>
                <div class="td-form-grupo">
                    <label for="td-emp-contacto"><?php esc_html_e('Email de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="email" name="contacto" id="td-emp-contacto"
                           placeholder="<?php esc_attr_e('contacto@ejemplo.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="td-form-grupo">
                <label for="td-emp-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <textarea name="descripcion" id="td-emp-descripcion" rows="6" required
                          placeholder="<?php esc_attr_e('Describe tu emprendimiento: qué haces, tu propuesta de valor, impacto social...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="td-btn td-btn--primary">
                    <span class="dashicons dashicons-store"></span>
                    <?php esc_html_e('Registrar Emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

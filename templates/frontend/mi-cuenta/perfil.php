<?php
/**
 * Template del tab "Mi Perfil"
 *
 * Variables disponibles:
 *   $usuario        - WP_User objeto del usuario actual
 *   $nombre         - Nombre (first_name) del usuario
 *   $apellido       - Apellido (last_name) del usuario
 *   $email          - Email del usuario
 *   $telefono       - Telefono del usuario (billing_phone)
 *   $avatar_url     - URL del avatar del usuario (128px)
 *   $nombre_mostrar - display_name del usuario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-dashboard-perfil">

    <h2 class="flavor-dashboard-section-title"><?php esc_html_e('Mi Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <!-- Formulario de datos personales -->
    <form id="flavor-form-perfil" class="flavor-dashboard-form" novalidate>

        <div class="flavor-dashboard-form-avatar">
            <img src="<?php echo esc_url($avatar_url); ?>"
                 alt="<?php echo esc_attr($nombre_mostrar); ?>"
                 class="flavor-dashboard-avatar-grande"
                 width="96" height="96" />
            <p class="flavor-dashboard-avatar-nota">
                <?php esc_html_e('El avatar se gestiona a traves de Gravatar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <a href="https://gravatar.com" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Cambiar avatar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </p>
        </div>

        <div class="flavor-dashboard-form-grid">
            <div class="flavor-dashboard-form-group">
                <label for="flavor-perfil-nombre"><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text"
                       id="flavor-perfil-nombre"
                       name="nombre"
                       value="<?php echo esc_attr($nombre); ?>"
                       placeholder="<?php esc_attr_e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-dashboard-input" />
            </div>

            <div class="flavor-dashboard-form-group">
                <label for="flavor-perfil-apellido"><?php esc_html_e('Apellido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text"
                       id="flavor-perfil-apellido"
                       name="apellido"
                       value="<?php echo esc_attr($apellido); ?>"
                       placeholder="<?php esc_attr_e('Tu apellido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-dashboard-input" />
            </div>

            <div class="flavor-dashboard-form-group">
                <label for="flavor-perfil-email"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="email"
                       id="flavor-perfil-email"
                       name="email"
                       value="<?php echo esc_attr($email); ?>"
                       placeholder="<?php esc_attr_e('tu@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-dashboard-input"
                       required />
            </div>

            <div class="flavor-dashboard-form-group">
                <label for="flavor-perfil-telefono"><?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="tel"
                       id="flavor-perfil-telefono"
                       name="telefono"
                       value="<?php echo esc_attr($telefono); ?>"
                       placeholder="<?php esc_attr_e('+34 600 000 000', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-dashboard-input" />
            </div>
        </div>

        <div class="flavor-dashboard-form-actions">
            <button type="submit" class="flavor-dashboard-btn flavor-dashboard-btn--primary" id="flavor-btn-guardar-perfil">
                <?php esc_html_e('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span class="flavor-dashboard-form-status" id="flavor-perfil-status"></span>
        </div>
    </form>

    <!-- Seccion cambio de contrasena -->
    <div class="flavor-dashboard-seccion-separador"></div>

    <h3 class="flavor-dashboard-section-subtitle"><?php esc_html_e('Cambiar contrasena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

    <form id="flavor-form-password" class="flavor-dashboard-form" novalidate>
        <div class="flavor-dashboard-form-grid">
            <div class="flavor-dashboard-form-group flavor-dashboard-form-group--full">
                <label for="flavor-password-actual"><?php esc_html_e('Contrasena actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="password"
                       id="flavor-password-actual"
                       name="password_actual"
                       class="flavor-dashboard-input"
                       autocomplete="current-password"
                       required />
            </div>

            <div class="flavor-dashboard-form-group">
                <label for="flavor-password-nueva"><?php esc_html_e('Nueva contrasena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="password"
                       id="flavor-password-nueva"
                       name="password_nueva"
                       class="flavor-dashboard-input"
                       autocomplete="new-password"
                       minlength="8"
                       required />
            </div>

            <div class="flavor-dashboard-form-group">
                <label for="flavor-password-confirmar"><?php esc_html_e('Confirmar nueva contrasena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="password"
                       id="flavor-password-confirmar"
                       name="password_confirmar"
                       class="flavor-dashboard-input"
                       autocomplete="new-password"
                       minlength="8"
                       required />
            </div>
        </div>

        <div class="flavor-dashboard-form-actions">
            <button type="submit" class="flavor-dashboard-btn flavor-dashboard-btn--secondary" id="flavor-btn-cambiar-password">
                <?php esc_html_e('Cambiar contrasena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span class="flavor-dashboard-form-status" id="flavor-password-status"></span>
        </div>
    </form>

</div>

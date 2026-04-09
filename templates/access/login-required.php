<?php
/**
 * Template: Login requerido
 *
 * Se muestra cuando un usuario no logueado intenta acceder a contenido restringido.
 *
 * Variables disponibles:
 * - $redirect_url: URL a la que redirigir tras el login
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Asegurar que tenemos la URL de redirección
$redirect_url = isset($redirect_url) ? $redirect_url : home_url();
?>

<div class="flavor-access-container flavor-login-required">
    <div class="flavor-access-card">
        <div class="flavor-access-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10 17 15 12 10 7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
        </div>

        <h2 class="flavor-access-title">
            <?php esc_html_e('Inicia sesion para continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>

        <p class="flavor-access-description">
            <?php esc_html_e('Este contenido requiere que inicies sesion con tu cuenta. Si aun no tienes una, puedes registrarte de forma gratuita.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <div class="flavor-login-form-container">
            <?php
            wp_login_form([
                'redirect'       => $redirect_url,
                'form_id'        => 'flavor-login-form',
                'label_username' => __('Usuario o correo electronico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'label_password' => __('Contrasena', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'label_remember' => __('Mantener sesion iniciada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'label_log_in'   => __('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'remember'       => true,
            ]);
            ?>
        </div>

        <div class="flavor-access-links">
            <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>" class="flavor-link-password">
                <?php esc_html_e('Olvidaste tu contrasena?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>

            <?php if (get_option('users_can_register')) : ?>
                <span class="flavor-link-separator">|</span>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-link-register">
                    <?php esc_html_e('Crear una cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php
        // Hook para añadir contenido adicional
        do_action('flavor_after_login_form', $redirect_url);
        ?>
    </div>
</div>

<style>
    .flavor-access-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
        padding: 2rem;
    }

    .flavor-access-card {
        max-width: 440px;
        width: 100%;
        padding: 2.5rem;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .flavor-access-icon {
        margin-bottom: 1.5rem;
        color: #0d6efd;
    }

    .flavor-access-icon svg {
        display: inline-block;
    }

    .flavor-access-title {
        margin: 0 0 1rem;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.3;
    }

    .flavor-access-description {
        margin: 0 0 2rem;
        color: #6c757d;
        font-size: 1rem;
        line-height: 1.6;
    }

    .flavor-login-form-container {
        text-align: left;
        margin-bottom: 1.5rem;
    }

    .flavor-login-form-container form p {
        margin-bottom: 1rem;
    }

    .flavor-login-form-container label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        color: #495057;
    }

    .flavor-login-form-container input[type="text"],
    .flavor-login-form-container input[type="password"] {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #f8f9fa;
    }

    .flavor-login-form-container input[type="text"]:focus,
    .flavor-login-form-container input[type="password"]:focus {
        outline: none;
        border-color: #0d6efd;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .flavor-login-form-container .login-remember {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .flavor-login-form-container .login-remember input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #0d6efd;
    }

    .flavor-login-form-container .login-remember label {
        margin: 0;
        font-weight: 400;
        cursor: pointer;
    }

    .flavor-login-form-container input[type="submit"] {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .flavor-login-form-container input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(13, 110, 253, 0.35);
    }

    .flavor-login-form-container input[type="submit"]:active {
        transform: translateY(0);
    }

    .flavor-access-links {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .flavor-access-links a {
        color: #0d6efd;
        text-decoration: none;
        transition: color 0.2s;
    }

    .flavor-access-links a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    .flavor-link-separator {
        margin: 0 0.75rem;
        color: #dee2e6;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .flavor-access-card {
            padding: 1.5rem;
            margin: 1rem;
        }

        .flavor-access-title {
            font-size: 1.5rem;
        }
    }
</style>

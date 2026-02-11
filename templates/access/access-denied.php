<?php
/**
 * Template: Acceso denegado
 *
 * Se muestra cuando un usuario no tiene los permisos necesarios para acceder a contenido privado.
 *
 * Variables disponibles:
 * - $module_slug: Slug del módulo al que se intenta acceder
 * - $visibilidad: Tipo de visibilidad del módulo
 * - $capacidad_requerida: Capacidad de WordPress requerida
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// URLs personalizables
$url_contacto = apply_filters('flavor_contact_url', home_url('/contacto/'));
$url_soporte = apply_filters('flavor_support_url', home_url('/soporte/'));
$url_inicio = home_url('/');

// Usuario actual
$usuario_actual = wp_get_current_user();
$esta_logueado = is_user_logged_in();

// Email de soporte
$email_soporte = apply_filters('flavor_support_email', get_option('admin_email'));
?>

<div class="flavor-access-container flavor-access-denied">
    <div class="flavor-access-card">
        <div class="flavor-access-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <h2 class="flavor-access-title">
            <?php esc_html_e('Acceso restringido', 'flavor-chat-ia'); ?>
        </h2>

        <p class="flavor-access-description">
            <?php if ($esta_logueado) : ?>
                <?php
                printf(
                    /* translators: %s: nombre de usuario */
                    esc_html__('Lo sentimos, %s. Tu cuenta no tiene los permisos necesarios para acceder a este contenido. Este area esta reservada para usuarios con autorizacion especifica.', 'flavor-chat-ia'),
                    '<strong>' . esc_html($usuario_actual->display_name) . '</strong>'
                );
                ?>
            <?php else : ?>
                <?php esc_html_e('Este contenido esta restringido y requiere permisos especiales. Por favor, inicia sesion con una cuenta autorizada o contacta con el administrador.', 'flavor-chat-ia'); ?>
            <?php endif; ?>
        </p>

        <?php if ($esta_logueado) : ?>
            <div class="flavor-access-info-box">
                <div class="flavor-info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div class="flavor-info-content">
                    <p class="flavor-info-title"><?php esc_html_e('Necesitas acceso?', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-info-text">
                        <?php
                        printf(
                            /* translators: %s: email de soporte */
                            esc_html__('Si crees que deberias tener acceso a este contenido, puedes contactar con el administrador en %s o usar el formulario de contacto.', 'flavor-chat-ia'),
                            '<a href="mailto:' . esc_attr($email_soporte) . '">' . esc_html($email_soporte) . '</a>'
                        );
                        ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="flavor-access-actions">
            <?php if (!$esta_logueado) : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <?php esc_html_e('Iniciar sesion', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>

            <a href="<?php echo esc_url($url_contacto); ?>" class="flavor-btn <?php echo $esta_logueado ? 'flavor-btn-primary' : 'flavor-btn-secondary'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <?php esc_html_e('Contactar administrador', 'flavor-chat-ia'); ?>
            </a>

            <a href="<?php echo esc_url($url_inicio); ?>" class="flavor-btn flavor-btn-text">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <?php esc_html_e('Volver al inicio', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <?php if ($esta_logueado) : ?>
            <p class="flavor-access-user-info">
                <?php
                printf(
                    /* translators: %s: nombre de usuario con enlace a perfil */
                    esc_html__('Has iniciado sesion como %s', 'flavor-chat-ia'),
                    '<a href="' . esc_url(get_edit_profile_url()) . '">' . esc_html($usuario_actual->display_name) . '</a>'
                );
                ?>
                &bull;
                <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">
                    <?php esc_html_e('Cerrar sesion', 'flavor-chat-ia'); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php
        // Hook para contenido adicional
        do_action('flavor_after_access_denied_message', $module_slug ?? '', $esta_logueado);
        ?>
    </div>
</div>

<style>
    .flavor-access-denied {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 500px;
        padding: 2rem;
        background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    }

    .flavor-access-denied .flavor-access-card {
        max-width: 520px;
        width: 100%;
        padding: 2.5rem;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(220, 53, 69, 0.1);
        text-align: center;
    }

    .flavor-access-denied .flavor-access-icon {
        margin-bottom: 1.5rem;
        color: #dc3545;
    }

    .flavor-access-denied .flavor-access-title {
        margin: 0 0 1rem;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .flavor-access-denied .flavor-access-description {
        margin: 0 0 1.5rem;
        color: #6c757d;
        font-size: 1rem;
        line-height: 1.7;
    }

    .flavor-access-info-box {
        display: flex;
        gap: 1rem;
        padding: 1.25rem;
        background: #f8f9fa;
        border-radius: 12px;
        border-left: 4px solid #0d6efd;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .flavor-info-icon {
        flex-shrink: 0;
        color: #0d6efd;
    }

    .flavor-info-content {
        flex: 1;
    }

    .flavor-info-title {
        margin: 0 0 0.25rem;
        font-weight: 600;
        color: #212529;
        font-size: 0.95rem;
    }

    .flavor-info-text {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .flavor-info-text a {
        color: #0d6efd;
        text-decoration: none;
    }

    .flavor-info-text a:hover {
        text-decoration: underline;
    }

    .flavor-access-denied .flavor-access-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .flavor-access-denied .flavor-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
    }

    .flavor-access-denied .flavor-btn-primary {
        background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    }

    .flavor-access-denied .flavor-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(13, 110, 253, 0.4);
        color: #ffffff;
    }

    .flavor-access-denied .flavor-btn-secondary {
        background: #f8f9fa;
        color: #495057;
        border: 2px solid #e9ecef;
    }

    .flavor-access-denied .flavor-btn-secondary:hover {
        background: #e9ecef;
        color: #212529;
    }

    .flavor-access-denied .flavor-btn-text {
        background: transparent;
        color: #6c757d;
        padding: 0.75rem 1rem;
    }

    .flavor-access-denied .flavor-btn-text:hover {
        color: #212529;
        background: #f8f9fa;
    }

    .flavor-access-user-info {
        margin: 0;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .flavor-access-user-info a {
        color: #0d6efd;
        text-decoration: none;
    }

    .flavor-access-user-info a:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .flavor-access-denied .flavor-access-card {
            padding: 1.5rem;
            margin: 1rem;
        }

        .flavor-access-denied .flavor-access-actions {
            flex-direction: column;
        }

        .flavor-access-denied .flavor-btn {
            width: 100%;
            justify-content: center;
        }

        .flavor-access-info-box {
            flex-direction: column;
            gap: 0.75rem;
        }
    }
</style>

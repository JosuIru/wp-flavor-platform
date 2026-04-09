<?php
/**
 * Template: Solo para miembros
 *
 * Se muestra cuando un usuario intenta acceder a contenido exclusivo para socios/miembros.
 *
 * Variables disponibles:
 * - $module_slug: Slug del módulo al que se intenta acceder
 * - $visibilidad: Tipo de visibilidad del módulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// URLs personalizables via filtros
$url_membresia = apply_filters('flavor_membership_url', home_url('/hazte-socio/'));
$url_beneficios = apply_filters('flavor_membership_benefits_url', home_url('/beneficios-socios/'));
$url_contacto = apply_filters('flavor_contact_url', home_url('/contacto/'));

// Nombre de la organizacion
$nombre_organizacion = apply_filters('flavor_organization_name', get_bloginfo('name'));

// Usuario actual
$usuario_actual = wp_get_current_user();
$esta_logueado = is_user_logged_in();
?>

<div class="flavor-access-container flavor-members-only">
    <div class="flavor-access-card">
        <div class="flavor-access-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            <?php esc_html_e('Contenido exclusivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>

        <div class="flavor-access-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>

        <h2 class="flavor-access-title">
            <?php esc_html_e('Area exclusiva para miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>

        <p class="flavor-access-description">
            <?php if ($esta_logueado) : ?>
                <?php
                printf(
                    /* translators: %s: nombre de usuario */
                    esc_html__('Hola %s, este contenido esta reservado para miembros activos de nuestra comunidad. Si crees que deberias tener acceso, contacta con nosotros.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($usuario_actual->display_name)
                );
                ?>
            <?php else : ?>
                <?php
                printf(
                    /* translators: %s: nombre de la organizacion */
                    esc_html__('Este contenido esta disponible exclusivamente para los miembros de %s. Unete a nuestra comunidad para disfrutar de todos los beneficios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($nombre_organizacion)
                );
                ?>
            <?php endif; ?>
        </p>

        <?php if (!$esta_logueado) : ?>
            <div class="flavor-access-benefits">
                <h3><?php esc_html_e('Beneficios de ser miembro:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php esc_html_e('Acceso a contenido exclusivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php esc_html_e('Participacion en actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php esc_html_e('Descuentos y promociones especiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php esc_html_e('Comunidad y networking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="flavor-access-actions">
            <?php if (!$esta_logueado) : ?>
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="flavor-btn flavor-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <?php esc_html_e('Ya soy miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url($url_membresia); ?>" class="flavor-btn flavor-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <?php esc_html_e('Hacerme miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url($url_membresia); ?>" class="flavor-btn flavor-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <?php esc_html_e('Activar mi membresia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url($url_contacto); ?>" class="flavor-btn flavor-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    <?php esc_html_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$esta_logueado) : ?>
            <p class="flavor-access-note">
                <a href="<?php echo esc_url($url_beneficios); ?>">
                    <?php esc_html_e('Ver todos los beneficios de ser miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </p>
        <?php endif; ?>

        <?php
        // Hook para contenido adicional
        do_action('flavor_after_members_only_message', $module_slug ?? '', $esta_logueado);
        ?>
    </div>
</div>

<style>
    .flavor-members-only {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 500px;
        padding: 2rem;
        background: linear-gradient(135deg, #f8f9ff 0%, #e8f4ff 100%);
    }

    .flavor-members-only .flavor-access-card {
        max-width: 520px;
        width: 100%;
        padding: 2.5rem;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(13, 110, 253, 0.12);
        text-align: center;
        position: relative;
    }

    .flavor-access-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #ffd700 0%, #ffb700 100%);
        color: #1a1a2e;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-radius: 50px;
        margin-bottom: 1.5rem;
    }

    .flavor-access-badge svg {
        fill: currentColor;
        stroke: none;
    }

    .flavor-members-only .flavor-access-icon {
        margin-bottom: 1.5rem;
        color: #0d6efd;
    }

    .flavor-members-only .flavor-access-title {
        margin: 0 0 1rem;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .flavor-members-only .flavor-access-description {
        margin: 0 0 1.5rem;
        color: #6c757d;
        font-size: 1rem;
        line-height: 1.7;
    }

    .flavor-access-benefits {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .flavor-access-benefits h3 {
        margin: 0 0 0.75rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: #495057;
    }

    .flavor-access-benefits ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .flavor-access-benefits li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0;
        color: #495057;
        font-size: 0.9rem;
    }

    .flavor-access-benefits li svg {
        color: #28a745;
        flex-shrink: 0;
    }

    .flavor-access-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .flavor-members-only .flavor-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.875rem 1.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
    }

    .flavor-members-only .flavor-btn-primary {
        background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    }

    .flavor-members-only .flavor-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(13, 110, 253, 0.4);
        color: #ffffff;
    }

    .flavor-members-only .flavor-btn-secondary {
        background: #f8f9fa;
        color: #495057;
        border: 2px solid #e9ecef;
    }

    .flavor-members-only .flavor-btn-secondary:hover {
        background: #e9ecef;
        color: #212529;
    }

    .flavor-access-note {
        margin: 0;
        font-size: 0.85rem;
    }

    .flavor-access-note a {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        color: #0d6efd;
        text-decoration: none;
    }

    .flavor-access-note a:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .flavor-members-only .flavor-access-card {
            padding: 1.5rem;
            margin: 1rem;
        }

        .flavor-access-actions {
            flex-direction: column;
        }

        .flavor-members-only .flavor-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

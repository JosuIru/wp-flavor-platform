<?php
/**
 * Login Required - Mi Red Social
 *
 * Muestra mensaje cuando el usuario no está autenticado.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="mi-red-login-required">
    <div class="mi-red-login-required__card">
        <div class="mi-red-login-required__icon">🔐</div>
        <h1 class="mi-red-login-required__title"><?php esc_html_e('Inicia sesión para continuar', 'flavor-chat-ia'); ?></h1>
        <p class="mi-red-login-required__text">
            <?php esc_html_e('Necesitas iniciar sesión para acceder a Mi Red Social y conectar con la comunidad.', 'flavor-chat-ia'); ?>
        </p>
        <div class="mi-red-login-required__actions">
            <a href="<?php echo esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('mi_red', ''))); ?>" class="mi-red-btn mi-red-btn--primary">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
            <?php if (get_option('users_can_register')) : ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="mi-red-btn mi-red-btn--outline">
                    <?php esc_html_e('Crear cuenta', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.mi-red-login-required {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.mi-red-login-required__card {
    background: white;
    border-radius: 24px;
    padding: 48px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.mi-red-login-required__icon {
    font-size: 64px;
    margin-bottom: 24px;
}

.mi-red-login-required__title {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px;
}

.mi-red-login-required__text {
    color: #6b7280;
    margin: 0 0 32px;
    line-height: 1.6;
}

.mi-red-login-required__actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mi-red-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.mi-red-btn--primary {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
}

.mi-red-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    color: white;
}

.mi-red-btn--outline {
    background: transparent;
    border: 2px solid #e5e7eb;
    color: #374151;
}

.mi-red-btn--outline:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}
</style>

<?php get_footer(); ?>

<?php
/**
 * Sistema de Mensajes de Usuario
 *
 * Proporciona mensajes consistentes y bien diseñados para el usuario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_User_Messages {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Hook para personalizar mensajes de error de WordPress
        add_filter('wp_die_handler', [$this, 'custom_wp_die_handler'], 10, 1);
    }

    /**
     * Handler personalizado para wp_die
     */
    public function custom_wp_die_handler($handler) {
        // Solo usar handler personalizado para errores de permisos
        if (isset($_GET['flavor_permission_error']) ||
            (isset($GLOBALS['wp_die_args']) && isset($GLOBALS['wp_die_args']['response']) && $GLOBALS['wp_die_args']['response'] === 403)) {
            return [$this, 'custom_die_page'];
        }
        return $handler;
    }

    /**
     * Página personalizada de error
     */
    public function custom_die_page($message, $title = '', $args = []) {
        $defaults = [
            'response' => 403,
            'back_link' => true,
            'text_direction' => 'ltr',
            'charset' => 'utf-8',
        ];

        $args = wp_parse_args($args, $defaults);

        if (function_exists('is_wp_error') && is_wp_error($message)) {
            $title = $message->get_error_message();
        }

        if (empty($title)) {
            $title = __('Acceso Denegado', 'flavor-chat-ia');
        }

        // Extraer mensaje limpio
        $clean_message = strip_tags($message, '<p><a><strong><em>');

        $this->render_error_page($title, $clean_message, $args);
        die();
    }

    /**
     * Renderiza página de error con diseño mejorado
     */
    public static function render_error_page($title, $message, $args = []) {
        $defaults = [
            'icon' => '🔒',
            'back_link' => true,
            'home_link' => true,
            'support_link' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        status_header($args['response'] ?? 403);
        nocache_headers();

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_site_icon(); ?>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    max-width: 600px;
                    width: 100%;
                    background: white;
                    border-radius: 20px;
                    padding: 48px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                }
                .error-icon {
                    font-size: 80px;
                    margin-bottom: 24px;
                    display: block;
                    animation: bounce 2s infinite;
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                .error-title {
                    font-size: 32px;
                    font-weight: 700;
                    color: #111827;
                    margin-bottom: 16px;
                }
                .error-message {
                    font-size: 16px;
                    color: #6b7280;
                    line-height: 1.6;
                    margin-bottom: 32px;
                }
                .error-message p {
                    margin-bottom: 12px;
                }
                .error-message a {
                    color: #3b82f6;
                    text-decoration: none;
                    font-weight: 600;
                }
                .error-message a:hover {
                    text-decoration: underline;
                }
                .error-actions {
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                .error-button {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 14px 28px;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    text-decoration: none;
                    transition: all 0.2s ease;
                    border: none;
                    cursor: pointer;
                }
                .error-button--primary {
                    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
                    color: white;
                    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
                }
                .error-button--primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 15px rgba(59, 130, 246, 0.4);
                }
                .error-button--secondary {
                    background: white;
                    color: #3b82f6;
                    border: 2px solid #3b82f6;
                }
                .error-button--secondary:hover {
                    background: #eff6ff;
                }
                @media (max-width: 640px) {
                    .error-container {
                        padding: 32px 24px;
                    }
                    .error-icon {
                        font-size: 64px;
                    }
                    .error-title {
                        font-size: 24px;
                    }
                    .error-actions {
                        flex-direction: column;
                    }
                    .error-button {
                        width: 100%;
                        justify-content: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <span class="error-icon"><?php echo $args['icon']; ?></span>
                <h1 class="error-title"><?php echo esc_html($title); ?></h1>
                <div class="error-message">
                    <?php echo wp_kses_post($message); ?>
                </div>
                <div class="error-actions">
                    <?php if ($args['home_link']) : ?>
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('', '')); ?>" class="error-button error-button--primary">
                            🏠 <?php _e('Ir a Mi Portal', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($args['back_link']) : ?>
                        <a href="javascript:history.back()" class="error-button error-button--secondary">
                            ← <?php _e('Volver Atrás', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($args['support_link']) : ?>
                        <a href="<?php echo esc_url(home_url('/contacto/')); ?>" class="error-button error-button--secondary">
                            💬 <?php _e('Contactar Soporte', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Muestra mensaje de acceso denegado personalizado
     */
    public static function access_denied($module_name = '', $reason = '') {
        if (empty($module_name)) {
            $module_name = __('este módulo', 'flavor-chat-ia');
        }

        $title = __('Acceso Denegado', 'flavor-chat-ia');

        $message = '<p>' . sprintf(
            __('No tienes permisos para acceder a %s.', 'flavor-chat-ia'),
            '<strong>' . esc_html($module_name) . '</strong>'
        ) . '</p>';

        if (!empty($reason)) {
            $message .= '<p>' . esc_html($reason) . '</p>';
        }

        if (!is_user_logged_in()) {
            $message .= '<p>' . __('Necesitas iniciar sesión para acceder a esta página.', 'flavor-chat-ia') . '</p>';
        } else {
            $message .= '<p>' . __('Si crees que esto es un error, contacta con el administrador.', 'flavor-chat-ia') . '</p>';
        }

        self::render_error_page($title, $message, [
            'icon' => '🔒',
            'response' => 403,
            'back_link' => true,
            'home_link' => true,
            'support_link' => true,
        ]);

        exit;
    }

    /**
     * Muestra mensaje de página no encontrada personalizado
     */
    public static function not_found($item_type = '') {
        $title = __('No Encontrado', 'flavor-chat-ia');

        if (!empty($item_type)) {
            $message = '<p>' . sprintf(
                __('El %s que buscas no existe o ha sido eliminado.', 'flavor-chat-ia'),
                esc_html($item_type)
            ) . '</p>';
        } else {
            $message = '<p>' . __('La página que buscas no existe o ha sido eliminada.', 'flavor-chat-ia') . '</p>';
        }

        $message .= '<p>' . __('Verifica la URL o navega desde el menú principal.', 'flavor-chat-ia') . '</p>';

        self::render_error_page($title, $message, [
            'icon' => '🔍',
            'response' => 404,
            'back_link' => true,
            'home_link' => true,
        ]);

        exit;
    }

    /**
     * Muestra mensaje de éxito
     */
    public static function success($title, $message, $button_text = '', $button_url = '') {
        ?>
        <div class="flavor-message flavor-message--success">
            <div class="flavor-message__icon">✅</div>
            <h3 class="flavor-message__title"><?php echo esc_html($title); ?></h3>
            <div class="flavor-message__content">
                <?php echo wp_kses_post($message); ?>
            </div>
            <?php if (!empty($button_text) && !empty($button_url)) : ?>
                <div class="flavor-message__actions">
                    <a href="<?php echo esc_url($button_url); ?>" class="flavor-button flavor-button--primary">
                        <?php echo esc_html($button_text); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-message {
            max-width: 600px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .flavor-message--success {
            border-color: #10b981;
            background: linear-gradient(to bottom, #d1fae5 0%, white 100%);
        }
        .flavor-message__icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .flavor-message__title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 16px;
        }
        .flavor-message__content {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .flavor-message__actions {
            margin-top: 24px;
        }
        </style>
        <?php
    }

    /**
     * Muestra mensaje de info/aviso
     */
    public static function info($title, $message, $type = 'info') {
        $icons = [
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
        ];

        $icon = $icons[$type] ?? $icons['info'];

        ?>
        <div class="flavor-message flavor-message--<?php echo esc_attr($type); ?>">
            <div class="flavor-message__icon"><?php echo $icon; ?></div>
            <h3 class="flavor-message__title"><?php echo esc_html($title); ?></h3>
            <div class="flavor-message__content">
                <?php echo wp_kses_post($message); ?>
            </div>
        </div>
        <?php
    }
}

// Inicializar
Flavor_User_Messages::get_instance();

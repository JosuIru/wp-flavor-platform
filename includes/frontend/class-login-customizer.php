<?php
/**
 * Personalización de la página de Login de WordPress
 *
 * Aplica los estilos del plugin Flavor a la página de login de WP
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para personalizar el login de WordPress
 */
class Flavor_Login_Customizer {

    /**
     * Instancia singleton
     *
     * @var Flavor_Login_Customizer|null
     */
    private static $instance = null;

    /**
     * Configuración de diseño
     *
     * @var array
     */
    private $design_settings = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Login_Customizer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->design_settings = get_option('flavor_design_settings', []);

        // Solo aplicar si el plugin está activo y hay configuración
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_styles']);
        add_action('login_head', [$this, 'output_custom_css']);
        add_filter('login_headerurl', [$this, 'custom_login_url']);
        add_filter('login_headertext', [$this, 'custom_login_title']);
        add_action('login_footer', [$this, 'add_login_footer']);
    }

    /**
     * Obtiene un color de la configuración con fallback
     *
     * @param string $key     Clave del color
     * @param string $default Valor por defecto
     * @return string
     */
    private function get_color($key, $default = '') {
        return $this->design_settings[$key] ?? $default;
    }

    /**
     * Obtiene el logo configurado
     *
     * @return string URL del logo o vacío
     */
    private function get_logo_url() {
        return get_option('flavor_logo_url', '');
    }

    /**
     * Encola estilos para la página de login
     */
    public function enqueue_login_styles() {
        // Google Fonts si está configurado
        $font_headings = $this->design_settings['font_family_headings'] ?? 'Inter';
        $font_body = $this->design_settings['font_family_body'] ?? 'Inter';

        $fonts_to_load = array_unique([$font_headings, $font_body]);
        $fonts_string = implode('|', array_map(function($font) {
            return str_replace(' ', '+', $font) . ':wght@400;500;600;700';
        }, $fonts_to_load));

        if (!empty($fonts_string)) {
            wp_enqueue_style(
                'flavor-login-fonts',
                'https://fonts.googleapis.com/css2?family=' . $fonts_string . '&display=swap',
                [],
                FLAVOR_PLATFORM_VERSION
            );
        }
    }

    /**
     * Genera y muestra el CSS personalizado
     */
    public function output_custom_css() {
        // Colores
        $color_primario = $this->get_color('primary_color', '#3b82f6');
        $color_secundario = $this->get_color('secondary_color', '#8b5cf6');
        $color_fondo = $this->get_color('background_color', '#f8fafc');
        $color_texto = $this->get_color('text_color', '#1f2937');
        $color_texto_muted = $this->get_color('text_muted_color', '#6b7280');
        $color_exito = $this->get_color('success_color', '#10b981');
        $color_error = $this->get_color('error_color', '#ef4444');

        // Tipografía
        $font_headings = $this->design_settings['font_family_headings'] ?? 'Inter';
        $font_body = $this->design_settings['font_family_body'] ?? 'Inter';

        // Border radius
        $border_radius = $this->design_settings['border_radius'] ?? '12';

        // Logo
        $logo_url = $this->get_logo_url();
        ?>
        <style type="text/css">
            :root {
                --flavor-primary: <?php echo esc_attr($color_primario); ?>;
                --flavor-secondary: <?php echo esc_attr($color_secundario); ?>;
                --flavor-bg: <?php echo esc_attr($color_fondo); ?>;
                --flavor-text: <?php echo esc_attr($color_texto); ?>;
                --flavor-text-muted: <?php echo esc_attr($color_texto_muted); ?>;
                --flavor-success: <?php echo esc_attr($color_exito); ?>;
                --flavor-error: <?php echo esc_attr($color_error); ?>;
                --flavor-radius: <?php echo esc_attr($border_radius); ?>px;
            }

            /* Fondo de la página */
            body.login {
                background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);
                font-family: '<?php echo esc_attr($font_body); ?>', system-ui, -apple-system, sans-serif;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            /* Contenedor del formulario */
            #login {
                width: 100%;
                max-width: 420px;
                padding: 20px;
            }

            /* Logo/Header */
            #login h1 a,
            .login h1 a {
                <?php if ($logo_url) : ?>
                background-image: url('<?php echo esc_url($logo_url); ?>');
                background-size: contain;
                background-position: center;
                background-repeat: no-repeat;
                width: 200px;
                height: 80px;
                <?php else : ?>
                background-image: none;
                text-indent: 0;
                width: auto;
                height: auto;
                font-size: 28px;
                font-weight: 700;
                font-family: '<?php echo esc_attr($font_headings); ?>', system-ui, sans-serif;
                color: #ffffff;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                <?php endif; ?>
                margin: 0 auto 30px;
                display: block;
            }

            /* Formulario */
            .login form {
                background: #ffffff;
                border: none;
                border-radius: var(--flavor-radius);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                padding: 40px;
                margin-top: 0;
            }

            /* Labels */
            .login form label {
                color: var(--flavor-text);
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 8px;
                display: block;
            }

            /* Inputs */
            .login form input[type="text"],
            .login form input[type="password"],
            .login form input[type="email"] {
                background: #f8fafc;
                border: 2px solid #e2e8f0;
                border-radius: calc(var(--flavor-radius) - 4px);
                padding: 12px 16px;
                font-size: 15px;
                width: 100%;
                transition: all 0.2s ease;
                color: var(--flavor-text);
                box-sizing: border-box;
                margin-top: 6px;
            }

            .login form input[type="text"]:focus,
            .login form input[type="password"]:focus,
            .login form input[type="email"]:focus {
                background: #ffffff;
                border-color: var(--flavor-primary);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                outline: none;
            }

            /* Botón de submit */
            .login form .submit input[type="submit"],
            .wp-core-ui .button-primary {
                background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);
                border: none;
                border-radius: calc(var(--flavor-radius) - 4px);
                padding: 14px 24px;
                font-size: 15px;
                font-weight: 600;
                text-transform: none;
                letter-spacing: 0;
                width: 100%;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);
                margin-top: 10px;
            }

            .login form .submit input[type="submit"]:hover,
            .wp-core-ui .button-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.5);
            }

            .login form .submit input[type="submit"]:active {
                transform: translateY(0);
            }

            /* Checkbox "Recuérdame" */
            .login form .forgetmenot {
                margin-top: 15px;
            }

            .login form .forgetmenot label {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                color: var(--flavor-text-muted);
            }

            .login form input[type="checkbox"] {
                width: 18px;
                height: 18px;
                accent-color: var(--flavor-primary);
                border-radius: 4px;
            }

            /* Enlaces */
            .login #nav,
            .login #backtoblog {
                text-align: center;
                margin-top: 20px;
            }

            .login #nav a,
            .login #backtoblog a {
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                font-size: 14px;
                transition: color 0.2s ease;
            }

            .login #nav a:hover,
            .login #backtoblog a:hover {
                color: #ffffff;
                text-decoration: underline;
            }

            /* Mensajes de error */
            .login #login_error,
            .login .message,
            .login .success {
                border-radius: calc(var(--flavor-radius) - 4px);
                margin-bottom: 20px;
                padding: 16px 20px;
                border-left-width: 4px;
            }

            .login #login_error {
                background: #fef2f2;
                border-color: var(--flavor-error);
                color: #991b1b;
            }

            .login .message {
                background: #eff6ff;
                border-color: var(--flavor-primary);
                color: #1e40af;
            }

            .login .success {
                background: #ecfdf5;
                border-color: var(--flavor-success);
                color: #065f46;
            }

            /* Ocultar elementos innecesarios */
            .login .privacy-policy-page-link {
                display: none;
            }

            /* Footer personalizado */
            .flavor-login-footer {
                margin-top: 30px;
                text-align: center;
                color: rgba(255, 255, 255, 0.7);
                font-size: 13px;
            }

            .flavor-login-footer a {
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
            }

            .flavor-login-footer a:hover {
                color: #ffffff;
            }

            /* Responsive */
            @media screen and (max-width: 480px) {
                #login {
                    padding: 15px;
                }

                .login form {
                    padding: 30px 25px;
                }
            }

            /* Animación de entrada */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            #login {
                animation: fadeInUp 0.5s ease-out;
            }

            /* Ocultar el borde superior azul predeterminado */
            .login h1 {
                margin-bottom: 0;
            }

            /* Password toggle icon */
            .wp-hide-pw,
            .wp-show-pw {
                color: var(--flavor-text-muted);
            }

            .wp-hide-pw:hover,
            .wp-show-pw:hover {
                color: var(--flavor-primary);
            }

            /* Interstitial (2FA, etc) */
            .login .admin-email__actions-primary .button-primary {
                width: auto;
                padding: 12px 24px;
            }
        </style>
        <?php
    }

    /**
     * URL personalizada del logo
     *
     * @return string
     */
    public function custom_login_url() {
        return home_url('/');
    }

    /**
     * Título personalizado del logo
     *
     * @return string
     */
    public function custom_login_title() {
        return get_bloginfo('name');
    }

    /**
     * Añade footer personalizado
     */
    public function add_login_footer() {
        $site_name = get_bloginfo('name');
        ?>
        <div class="flavor-login-footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($site_name); ?></a></p>
        </div>
        <?php
    }
}

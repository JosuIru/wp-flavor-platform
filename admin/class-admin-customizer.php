<?php
/**
 * Personalización del Admin de WordPress
 *
 * Aplica los estilos de diseño del plugin al panel de administración de WP
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para personalizar el admin de WordPress
 */
class Flavor_Admin_Customizer {

    /**
     * Instancia singleton
     *
     * @var Flavor_Admin_Customizer|null
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
     * @return Flavor_Admin_Customizer
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

        // Solo aplicar si está habilitado
        if ($this->is_admin_customization_enabled()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
            add_action('admin_head', [$this, 'output_custom_css']);
            add_action('admin_bar_menu', [$this, 'customize_admin_bar'], 999);
        }
    }

    /**
     * Verifica si la personalización del admin está habilitada
     *
     * @return bool
     */
    private function is_admin_customization_enabled() {
        return !empty($this->design_settings['customize_admin']) || true; // Por defecto habilitado
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
     * Genera colores derivados (más claros/oscuros)
     *
     * @param string $hex    Color en hexadecimal
     * @param int    $steps  Pasos de ajuste (-255 a 255)
     * @return string
     */
    private function adjust_brightness($hex, $steps) {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convierte hex a rgba
     *
     * @param string $hex   Color hexadecimal
     * @param float  $alpha Transparencia (0-1)
     * @return string
     */
    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    /**
     * Encola estilos adicionales si es necesario
     */
    public function enqueue_admin_styles() {
        // Google Fonts si está configurado
        $font_headings = $this->design_settings['font_family_headings'] ?? '';
        $font_body = $this->design_settings['font_family_body'] ?? '';

        if ($font_headings || $font_body) {
            $fonts_to_load = array_filter(array_unique([$font_headings, $font_body]));
            if (!empty($fonts_to_load)) {
                $fonts_string = implode('|', array_map(function($font) {
                    return str_replace(' ', '+', $font) . ':wght@400;500;600;700';
                }, $fonts_to_load));

                wp_enqueue_style(
                    'flavor-admin-fonts',
                    'https://fonts.googleapis.com/css2?family=' . $fonts_string . '&display=swap',
                    [],
                    FLAVOR_CHAT_IA_VERSION
                );
            }
        }
    }

    /**
     * Genera y muestra el CSS personalizado para el admin
     */
    public function output_custom_css() {
        // Colores base
        $color_primario = $this->get_color('primary_color', '#3b82f6');
        $color_secundario = $this->get_color('secondary_color', '#8b5cf6');
        $color_acento = $this->get_color('accent_color', '#f59e0b');
        $color_exito = $this->get_color('success_color', '#10b981');
        $color_advertencia = $this->get_color('warning_color', '#f59e0b');
        $color_error = $this->get_color('error_color', '#ef4444');

        // Colores derivados
        $color_primario_light = $this->adjust_brightness($color_primario, 40);
        $color_primario_dark = $this->adjust_brightness($color_primario, -30);
        $color_primario_10 = $this->hex_to_rgba($color_primario, 0.1);
        $color_primario_20 = $this->hex_to_rgba($color_primario, 0.2);

        // Tipografía
        $font_headings = $this->design_settings['font_family_headings'] ?? 'system-ui';
        $font_body = $this->design_settings['font_family_body'] ?? 'system-ui';

        // Border radius
        $border_radius = $this->design_settings['border_radius'] ?? '8';
        ?>
        <style id="flavor-admin-customizer">
            :root {
                --flavor-admin-primary: <?php echo esc_attr($color_primario); ?>;
                --flavor-admin-primary-light: <?php echo esc_attr($color_primario_light); ?>;
                --flavor-admin-primary-dark: <?php echo esc_attr($color_primario_dark); ?>;
                --flavor-admin-primary-10: <?php echo esc_attr($color_primario_10); ?>;
                --flavor-admin-primary-20: <?php echo esc_attr($color_primario_20); ?>;
                --flavor-admin-secondary: <?php echo esc_attr($color_secundario); ?>;
                --flavor-admin-accent: <?php echo esc_attr($color_acento); ?>;
                --flavor-admin-success: <?php echo esc_attr($color_exito); ?>;
                --flavor-admin-warning: <?php echo esc_attr($color_advertencia); ?>;
                --flavor-admin-error: <?php echo esc_attr($color_error); ?>;
                --flavor-admin-radius: <?php echo esc_attr($border_radius); ?>px;
                --flavor-admin-font-headings: '<?php echo esc_attr($font_headings); ?>', system-ui, -apple-system, sans-serif;
                --flavor-admin-font-body: '<?php echo esc_attr($font_body); ?>', system-ui, -apple-system, sans-serif;
            }

            /* =============================================
               Admin Bar
               ============================================= */
            #wpadminbar {
                background: linear-gradient(135deg, var(--flavor-admin-primary) 0%, var(--flavor-admin-secondary) 100%);
            }

            #wpadminbar .ab-top-menu > li.hover > .ab-item,
            #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus,
            #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
            #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
                background: var(--flavor-admin-primary-20);
            }

            #wpadminbar .ab-submenu,
            #wpadminbar .quicklinks .ab-sub-wrapper {
                background: #1e1e1e;
            }

            #wpadminbar .quicklinks .ab-sub-wrapper .ab-item:hover,
            #wpadminbar .quicklinks .ab-sub-wrapper .ab-item:focus {
                background: var(--flavor-admin-primary);
                color: #fff;
            }

            /* =============================================
               Sidebar Menu
               ============================================= */
            #adminmenu,
            #adminmenuback,
            #adminmenuwrap {
                background: linear-gradient(180deg, #1e1e2d 0%, #12121a 100%);
            }

            #adminmenu a {
                color: rgba(255, 255, 255, 0.7);
                font-family: var(--flavor-admin-font-body);
            }

            #adminmenu li.menu-top:hover,
            #adminmenu li.opensub > a.menu-top,
            #adminmenu li > a.menu-top:focus {
                background: var(--flavor-admin-primary-20);
            }

            #adminmenu li.menu-top > a:hover,
            #adminmenu li.menu-top:hover > a {
                color: #fff;
            }

            /* Current menu item */
            #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head,
            #adminmenu .wp-menu-arrow,
            #adminmenu .wp-menu-arrow div,
            #adminmenu li.current a.menu-top,
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
            #adminmenu li.wp-has-current-submenu .wp-submenu-head,
            .folded #adminmenu li.current.menu-top,
            .folded #adminmenu li.wp-has-current-submenu {
                background: var(--flavor-admin-primary);
                color: #fff;
            }

            #adminmenu .wp-submenu a:focus,
            #adminmenu .wp-submenu a:hover,
            #adminmenu a:hover,
            #adminmenu li.menu-top > a:focus {
                color: var(--flavor-admin-primary-light);
            }

            #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
            #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
            #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
            #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover,
            #adminmenu .wp-submenu a:focus,
            #adminmenu .wp-submenu a:hover,
            #adminmenu a:hover,
            #adminmenu li.menu-top > a:focus {
                color: var(--flavor-admin-primary-light);
            }

            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu .wp-menu-image:before,
            #adminmenu li.current .wp-menu-image:before {
                color: #fff;
            }

            /* Submenu */
            #adminmenu .wp-submenu {
                background: #252536;
            }

            #adminmenu .wp-submenu li.current a {
                color: var(--flavor-admin-primary-light);
            }

            /* Collapse button */
            #collapse-button {
                color: rgba(255, 255, 255, 0.6);
            }

            #collapse-button:hover,
            #collapse-button:focus {
                color: var(--flavor-admin-primary-light);
            }

            /* =============================================
               Buttons
               ============================================= */
            .wp-core-ui .button-primary {
                background: var(--flavor-admin-primary);
                border-color: var(--flavor-admin-primary-dark);
                color: #fff;
                border-radius: var(--flavor-admin-radius);
                font-family: var(--flavor-admin-font-body);
                font-weight: 500;
                text-shadow: none;
                box-shadow: 0 2px 4px var(--flavor-admin-primary-20);
            }

            .wp-core-ui .button-primary:hover,
            .wp-core-ui .button-primary:focus {
                background: var(--flavor-admin-primary-dark);
                border-color: var(--flavor-admin-primary-dark);
                color: #fff;
                box-shadow: 0 4px 8px var(--flavor-admin-primary-20);
            }

            .wp-core-ui .button-secondary,
            .wp-core-ui .button {
                border-radius: var(--flavor-admin-radius);
                font-family: var(--flavor-admin-font-body);
            }

            /* =============================================
               Form Elements
               ============================================= */
            input[type="text"]:focus,
            input[type="password"]:focus,
            input[type="email"]:focus,
            input[type="number"]:focus,
            input[type="search"]:focus,
            input[type="url"]:focus,
            select:focus,
            textarea:focus {
                border-color: var(--flavor-admin-primary);
                box-shadow: 0 0 0 1px var(--flavor-admin-primary);
            }

            input[type="checkbox"]:checked::before {
                content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='<?php echo urlencode($color_primario); ?>' d='M14.83 4.89l1.34.94-7.37 10.5-5.02-4.3 1.3-1.52 3.43 2.93 6.32-8.55z'/%3E%3C/svg%3E");
            }

            input[type="radio"]:checked::before {
                background: var(--flavor-admin-primary);
            }

            /* =============================================
               Links
               ============================================= */
            a {
                color: var(--flavor-admin-primary);
            }

            a:hover,
            a:focus {
                color: var(--flavor-admin-primary-dark);
            }

            /* =============================================
               Headers & Typography
               ============================================= */
            .wrap h1,
            .wrap h2 {
                font-family: var(--flavor-admin-font-headings);
                font-weight: 600;
            }

            /* =============================================
               Notices
               ============================================= */
            .notice-success,
            .updated {
                border-left-color: var(--flavor-admin-success);
            }

            .notice-warning {
                border-left-color: var(--flavor-admin-warning);
            }

            .notice-error,
            .error {
                border-left-color: var(--flavor-admin-error);
            }

            .notice-info {
                border-left-color: var(--flavor-admin-primary);
            }

            /* =============================================
               Dashboard Widgets
               ============================================= */
            .postbox {
                border-radius: var(--flavor-admin-radius);
                border: 1px solid #e2e8f0;
            }

            .postbox .hndle {
                border-radius: var(--flavor-admin-radius) var(--flavor-admin-radius) 0 0;
            }

            /* =============================================
               Tables
               ============================================= */
            .wp-list-table th {
                font-family: var(--flavor-admin-font-headings);
                font-weight: 600;
            }

            .wp-list-table .check-column input[type="checkbox"]:checked {
                background: var(--flavor-admin-primary);
                border-color: var(--flavor-admin-primary);
            }

            /* Row actions */
            .wp-list-table .row-actions a:hover {
                color: var(--flavor-admin-primary-dark);
            }

            /* =============================================
               Pagination
               ============================================= */
            .tablenav-pages .current-page:focus,
            .tablenav-pages .current-page:hover {
                border-color: var(--flavor-admin-primary);
            }

            /* =============================================
               Tabs
               ============================================= */
            .nav-tab-active,
            .nav-tab-active:focus,
            .nav-tab-active:focus:active,
            .nav-tab-active:hover {
                border-bottom-color: #fff;
                background: #fff;
                color: var(--flavor-admin-primary);
            }

            .nav-tab:focus,
            .nav-tab:hover {
                background-color: #fff;
                color: var(--flavor-admin-primary);
            }

            /* =============================================
               Media Library
               ============================================= */
            .media-frame-toolbar .media-toolbar-primary .button-primary {
                background: var(--flavor-admin-primary);
                border-color: var(--flavor-admin-primary-dark);
            }

            .attachments-browser .attachments .attachment.selected {
                box-shadow: inset 0 0 0 3px var(--flavor-admin-primary);
            }

            /* =============================================
               Editor (Gutenberg)
               ============================================= */
            .editor-styles-wrapper {
                font-family: var(--flavor-admin-font-body);
            }

            .edit-post-header {
                border-bottom-color: var(--flavor-admin-primary-10);
            }

            /* =============================================
               Plugin-specific pages
               ============================================= */
            .flavor-admin-page .button-primary,
            .flavor-admin-page .btn-primary {
                background: linear-gradient(135deg, var(--flavor-admin-primary) 0%, var(--flavor-admin-secondary) 100%);
                border: none;
                box-shadow: 0 4px 14px var(--flavor-admin-primary-20);
            }

            .flavor-admin-page .button-primary:hover,
            .flavor-admin-page .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px var(--flavor-admin-primary-20);
            }

            /* =============================================
               Update indicator
               ============================================= */
            #adminmenu .update-plugins,
            #adminmenu .awaiting-mod {
                background: var(--flavor-admin-accent);
            }

            /* =============================================
               Screen Options / Help
               ============================================= */
            #screen-meta-links .show-settings:hover,
            #screen-meta-links .show-settings:focus {
                color: var(--flavor-admin-primary);
            }

            /* =============================================
               Welcome Panel
               ============================================= */
            .welcome-panel {
                border-radius: var(--flavor-admin-radius);
                background: linear-gradient(135deg, var(--flavor-admin-primary-10) 0%, var(--flavor-admin-primary-20) 100%);
                border-color: var(--flavor-admin-primary-20);
            }

            .welcome-panel h2 {
                color: var(--flavor-admin-primary-dark);
            }

            .welcome-panel .button-primary {
                background: var(--flavor-admin-primary);
                border-color: var(--flavor-admin-primary);
            }
        </style>
        <?php
    }

    /**
     * Personaliza la barra de admin
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function customize_admin_bar($wp_admin_bar) {
        // Podemos añadir items personalizados o modificar existentes
        $logo_url = get_option('flavor_logo_url', '');

        if ($logo_url) {
            // Reemplazar el logo de WordPress con el del sitio
            $wp_admin_bar->remove_node('wp-logo');
            $wp_admin_bar->add_node([
                'id'    => 'site-logo',
                'title' => sprintf(
                    '<img src="%s" alt="%s" style="height: 20px; width: auto; vertical-align: middle; margin-top: -2px;">',
                    esc_url($logo_url),
                    esc_attr(get_bloginfo('name'))
                ),
                'href'  => home_url('/'),
                'meta'  => [
                    'title' => get_bloginfo('name'),
                ],
            ]);
        }
    }
}

// Inicializar
Flavor_Admin_Customizer::get_instance();

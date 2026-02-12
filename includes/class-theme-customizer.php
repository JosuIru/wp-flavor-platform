<?php
/**
 * Theme Customizer - Dark Mode & Color Customization
 *
 * Sistema de personalización de temas con dark mode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Theme_Customizer {

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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_theme_styles'], 999);
        add_action('wp_footer', [$this, 'add_theme_switcher']);
        add_action('wp_ajax_flavor_save_theme_preference', [$this, 'ajax_save_preference']);
        add_action('wp_ajax_nopriv_flavor_save_theme_preference', [$this, 'ajax_save_preference']);
        add_shortcode('flavor_theme_customizer', [$this, 'render_customizer']);
    }

    /**
     * Encola estilos del tema
     */
    public function enqueue_theme_styles() {
        $current_theme = $this->get_user_theme();
        $custom_colors = $this->get_user_colors();

        // CSS variables
        $css_vars = $this->generate_css_variables($current_theme, $custom_colors);

        wp_add_inline_style('flavor-portal', $css_vars);
        wp_add_inline_style('flavor-portal', $this->get_dark_mode_css());
    }

    /**
     * Obtiene tema del usuario
     */
    private function get_user_theme() {
        if (is_user_logged_in()) {
            $saved = get_user_meta(get_current_user_id(), 'flavor_theme', true);
            if ($saved) {
                return $saved;
            }
        }

        // Cookie para no logueados
        return isset($_COOKIE['flavor_theme']) ? $_COOKIE['flavor_theme'] : 'light';
    }

    /**
     * Obtiene colores personalizados del usuario
     */
    private function get_user_colors() {
        if (is_user_logged_in()) {
            $saved = get_user_meta(get_current_user_id(), 'flavor_custom_colors', true);
            if ($saved && is_array($saved)) {
                return $saved;
            }
        }

        return $this->get_default_colors();
    }

    /**
     * Colores por defecto
     */
    private function get_default_colors() {
        return [
            'primary' => '#3b82f6',
            'secondary' => '#8b5cf6',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
        ];
    }

    /**
     * Genera CSS variables
     */
    private function generate_css_variables($theme, $colors) {
        $light_vars = "
        :root {
            --flavor-theme: 'light';
            --flavor-primary: {$colors['primary']};
            --flavor-primary-dark: " . $this->darken_color($colors['primary'], 10) . ";
            --flavor-primary-light: " . $this->lighten_color($colors['primary'], 90) . ";
            --flavor-secondary: {$colors['secondary']};
            --flavor-success: {$colors['success']};
            --flavor-warning: {$colors['warning']};
            --flavor-danger: {$colors['danger']};

            --flavor-bg: #ffffff;
            --flavor-bg-secondary: #f9fafb;
            --flavor-bg-tertiary: #f3f4f6;
            --flavor-text: #111827;
            --flavor-text-secondary: #6b7280;
            --flavor-text-tertiary: #9ca3af;
            --flavor-border: #e5e7eb;
            --flavor-shadow: rgba(0, 0, 0, 0.1);
        }
        ";

        $dark_vars = "
        [data-theme='dark'],
        .flavor-theme-dark {
            --flavor-theme: 'dark';
            --flavor-primary: {$colors['primary']};
            --flavor-primary-dark: " . $this->lighten_color($colors['primary'], 10) . ";
            --flavor-primary-light: " . $this->darken_color($colors['primary'], 50) . ";
            --flavor-secondary: {$colors['secondary']};
            --flavor-success: {$colors['success']};
            --flavor-warning: {$colors['warning']};
            --flavor-danger: {$colors['danger']};

            --flavor-bg: #111827;
            --flavor-bg-secondary: #1f2937;
            --flavor-bg-tertiary: #374151;
            --flavor-text: #f9fafb;
            --flavor-text-secondary: #d1d5db;
            --flavor-text-tertiary: #9ca3af;
            --flavor-border: #374151;
            --flavor-shadow: rgba(0, 0, 0, 0.3);
        }
        ";

        return $light_vars . "\n" . $dark_vars;
    }

    /**
     * CSS para dark mode
     */
    private function get_dark_mode_css() {
        return "
        /* Dark Mode Auto Apply */
        body {
            background-color: var(--flavor-bg);
            color: var(--flavor-text);
            transition: background-color 0.3s, color 0.3s;
        }

        [data-theme='dark'] {
            color-scheme: dark;
        }

        [data-theme='dark'] .flavor-stat-card,
        [data-theme='dark'] .flavor-portal__widget,
        [data-theme='dark'] .flavor-quick-action-card,
        [data-theme='dark'] .flavor-activity-item,
        [data-theme='dark'] .flavor-adaptive-menu {
            background: var(--flavor-bg-secondary);
            border-color: var(--flavor-border);
        }

        [data-theme='dark'] .flavor-stat-card__value,
        [data-theme='dark'] .flavor-stat-card__label,
        [data-theme='dark'] .flavor-quick-action-card__title,
        [data-theme='dark'] .flavor-portal__widget-title {
            color: var(--flavor-text);
        }

        [data-theme='dark'] .flavor-stat-card__secondary-label,
        [data-theme='dark'] .flavor-quick-action-card__description,
        [data-theme='dark'] .flavor-activity-item__text {
            color: var(--flavor-text-secondary);
        }

        [data-theme='dark'] .flavor-portal__hero {
            background: linear-gradient(135deg,
                " . $this->darken_color($this->get_user_colors()['primary'], 30) . " 0%,
                " . $this->darken_color($this->get_user_colors()['secondary'], 30) . " 100%);
        }

        [data-theme='dark'] .flavor-page-header--gradient {
            background: linear-gradient(135deg,
                " . $this->darken_color($this->get_user_colors()['primary'], 20) . " 0%,
                " . $this->darken_color($this->get_user_colors()['secondary'], 20) . " 100%);
        }

        /* Theme Switcher Floating Button */
        .flavor-theme-switcher {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .flavor-theme-switcher__button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--flavor-primary);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }

        .flavor-theme-switcher__button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        @media (max-width: 768px) {
            .flavor-theme-switcher {
                bottom: 80px;
            }
        }
        ";
    }

    /**
     * Añade switcher de tema al footer
     */
    public function add_theme_switcher() {
        $current_theme = $this->get_user_theme();
        ?>
        <div class="flavor-theme-switcher">
            <button class="flavor-theme-switcher__button"
                    id="flavor-theme-toggle"
                    aria-label="<?php esc_attr_e('Cambiar tema', 'flavor-chat-ia'); ?>"
                    title="<?php esc_attr_e('Cambiar tema', 'flavor-chat-ia'); ?>">
                <span class="flavor-theme-icon">
                    <?php echo ($current_theme === 'dark') ? '☀️' : '🌙'; ?>
                </span>
            </button>
        </div>

        <script>
        (function() {
            const html = document.documentElement;
            const savedTheme = '<?php echo esc_js($current_theme); ?>';

            // Aplicar tema guardado
            if (savedTheme) {
                html.setAttribute('data-theme', savedTheme);
            }

            // Toggle theme
            const toggle = document.getElementById('flavor-theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', function() {
                    const currentTheme = html.getAttribute('data-theme') || 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    html.setAttribute('data-theme', newTheme);

                    // Cambiar icono
                    const icon = this.querySelector('.flavor-theme-icon');
                    icon.textContent = newTheme === 'dark' ? '☀️' : '🌙';

                    // Guardar preferencia
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'flavor_save_theme_preference',
                            theme: newTheme,
                            nonce: '<?php echo wp_create_nonce('flavor_theme'); ?>'
                        })
                    });

                    // Cookie para no logueados
                    document.cookie = 'flavor_theme=' + newTheme + '; path=/; max-age=31536000';
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * Renderiza customizer completo
     */
    public function render_customizer($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $current_colors = $this->get_user_colors();

        ob_start();
        ?>
        <div class="flavor-theme-customizer">
            <h3><?php _e('Personaliza tu Tema', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-theme-customizer__section">
                <h4><?php _e('Modo de Tema', 'flavor-chat-ia'); ?></h4>
                <div class="flavor-theme-modes">
                    <button class="flavor-theme-mode" data-theme="light">
                        ☀️ <?php _e('Claro', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="flavor-theme-mode" data-theme="dark">
                        🌙 <?php _e('Oscuro', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="flavor-theme-mode" data-theme="auto">
                        🔄 <?php _e('Auto', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <div class="flavor-theme-customizer__section">
                <h4><?php _e('Colores', 'flavor-chat-ia'); ?></h4>
                <div class="flavor-color-pickers">
                    <div class="flavor-color-picker">
                        <label><?php _e('Color Principal', 'flavor-chat-ia'); ?></label>
                        <input type="color" name="primary" value="<?php echo esc_attr($current_colors['primary']); ?>">
                    </div>
                    <div class="flavor-color-picker">
                        <label><?php _e('Color Secundario', 'flavor-chat-ia'); ?></label>
                        <input type="color" name="secondary" value="<?php echo esc_attr($current_colors['secondary']); ?>">
                    </div>
                    <div class="flavor-color-picker">
                        <label><?php _e('Éxito', 'flavor-chat-ia'); ?></label>
                        <input type="color" name="success" value="<?php echo esc_attr($current_colors['success']); ?>">
                    </div>
                    <div class="flavor-color-picker">
                        <label><?php _e('Advertencia', 'flavor-chat-ia'); ?></label>
                        <input type="color" name="warning" value="<?php echo esc_attr($current_colors['warning']); ?>">
                    </div>
                    <div class="flavor-color-picker">
                        <label><?php _e('Peligro', 'flavor-chat-ia'); ?></label>
                        <input type="color" name="danger" value="<?php echo esc_attr($current_colors['danger']); ?>">
                    </div>
                </div>

                <div class="flavor-color-presets">
                    <h5><?php _e('Presets', 'flavor-chat-ia'); ?></h5>
                    <?php echo $this->render_color_presets(); ?>
                </div>
            </div>

            <div class="flavor-theme-customizer__actions">
                <button class="flavor-button flavor-button--primary" id="flavor-save-colors">
                    <?php _e('Guardar Cambios', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-button flavor-button--secondary" id="flavor-reset-colors">
                    <?php _e('Restaurar por Defecto', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>

        <style>
        .flavor-theme-customizer {
            max-width: 600px;
            margin: 40px auto;
            padding: 32px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }
        .flavor-theme-customizer__section {
            margin-bottom: 32px;
        }
        .flavor-theme-customizer__section h4 {
            margin-bottom: 16px;
            font-size: 18px;
            color: #111827;
        }
        .flavor-theme-modes {
            display: flex;
            gap: 12px;
        }
        .flavor-theme-mode {
            flex: 1;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .flavor-theme-mode:hover,
        .flavor-theme-mode.active {
            border-color: var(--flavor-primary);
            background: #eff6ff;
        }
        .flavor-color-pickers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
        .flavor-color-picker label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        .flavor-color-picker input[type="color"] {
            width: 100%;
            height: 50px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
        }
        .flavor-color-presets {
            margin-top: 24px;
        }
        .flavor-theme-customizer__actions {
            display: flex;
            gap: 12px;
        }
        [data-theme='dark'] .flavor-theme-customizer {
            background: var(--flavor-bg-secondary);
            border-color: var(--flavor-border);
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza presets de colores
     */
    private function render_color_presets() {
        $presets = [
            'default' => ['name' => 'Por Defecto', 'primary' => '#3b82f6', 'secondary' => '#8b5cf6'],
            'ocean' => ['name' => 'Océano', 'primary' => '#0ea5e9', 'secondary' => '#06b6d4'],
            'forest' => ['name' => 'Bosque', 'primary' => '#10b981', 'secondary' => '#059669'],
            'sunset' => ['name' => 'Atardecer', 'primary' => '#f59e0b', 'secondary' => '#ef4444'],
            'purple' => ['name' => 'Púrpura', 'primary' => '#8b5cf6', 'secondary' => '#a855f7'],
        ];

        ob_start();
        echo '<div class="flavor-presets-grid">';
        foreach ($presets as $key => $preset) {
            echo sprintf(
                '<button class="flavor-preset" data-preset="%s" style="background: linear-gradient(135deg, %s 0%%, %s 100%%)">
                    <span class="flavor-preset-name">%s</span>
                </button>',
                esc_attr($key),
                esc_attr($preset['primary']),
                esc_attr($preset['secondary']),
                esc_html($preset['name'])
            );
        }
        echo '</div>';
        echo '<style>
        .flavor-presets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .flavor-preset {
            height: 60px;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
        }
        .flavor-preset:hover {
            transform: scale(1.05);
            border-color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .flavor-preset-name {
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
        </style>';
        return ob_get_clean();
    }

    /**
     * AJAX: Guardar preferencia de tema
     */
    public function ajax_save_preference() {
        check_ajax_referer('flavor_theme', 'nonce');

        $theme = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : 'light';

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'flavor_theme', $theme);
        }

        wp_send_json_success(['theme' => $theme]);
    }

    /**
     * Helpers para manipular colores
     */
    private function darken_color($hex, $percent) {
        return $this->adjust_brightness($hex, -$percent);
    }

    private function lighten_color($hex, $percent) {
        return $this->adjust_brightness($hex, $percent);
    }

    private function adjust_brightness($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                  . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                  . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

// Inicializar
Flavor_Theme_Customizer::get_instance();

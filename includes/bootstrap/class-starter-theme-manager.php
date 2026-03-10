<?php
/**
 * Starter Theme Manager - Gestión del tema companion
 *
 * Esta clase maneja la instalación, activación y avisos del tema
 * companion "Flavor Starter" que viene empaquetado con el plugin.
 *
 * @package FlavorPlatform
 * @subpackage Bootstrap
 * @since 3.2.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona el tema companion Flavor Starter
 */
final class Flavor_Starter_Theme_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Starter_Theme_Manager|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Starter_Theme_Manager
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
        // Vacío - los hooks se registran explícitamente
    }

    /**
     * Registra los hooks necesarios
     *
     * @return void
     */
    public function register_hooks() {
        add_action('admin_notices', [$this, 'maybe_show_notice']);
        add_action('admin_post_flavor_activate_starter_theme', [$this, 'handle_activate']);
        add_action('admin_post_flavor_install_starter_theme', [$this, 'handle_install']);
        add_action('admin_post_flavor_dismiss_starter_theme_notice', [$this, 'handle_dismiss_notice']);
    }

    /**
     * Verifica si se debe mostrar el aviso del tema durante la activación
     *
     * @return void
     */
    public function check_on_activation() {
        $starter_theme = wp_get_theme('flavor-starter');
        if (($starter_theme->exists() || $this->is_bundled()) && get_stylesheet() !== 'flavor-starter') {
            update_option('flavor_show_starter_theme_notice', 1);
        }
    }

    /**
     * Ruta del tema companion dentro del plugin
     *
     * @return string
     */
    public function get_bundle_path() {
        return FLAVOR_CHAT_IA_PATH . 'assets/companion-theme/flavor-starter';
    }

    /**
     * Comprueba si el tema companion está empaquetado en el plugin
     *
     * @return bool
     */
    public function is_bundled() {
        $bundle_path = $this->get_bundle_path();
        return file_exists($bundle_path . '/style.css');
    }

    /**
     * Comprueba si el tema companion está instalado
     *
     * @return bool
     */
    public function is_installed() {
        $starter_theme = wp_get_theme('flavor-starter');
        return $starter_theme->exists();
    }

    /**
     * Comprueba si el tema companion está activo
     *
     * @return bool
     */
    public function is_active() {
        return get_stylesheet() === 'flavor-starter';
    }

    /**
     * Muestra aviso para activar el tema companion
     *
     * @return void
     */
    public function maybe_show_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!get_option('flavor_show_starter_theme_notice')) {
            return;
        }

        if ($this->is_active()) {
            delete_option('flavor_show_starter_theme_notice');
            return;
        }

        $is_installed = $this->is_installed();
        $is_bundled = $this->is_bundled();

        $activate_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_activate_starter_theme'),
            'flavor_activate_starter_theme'
        );
        $install_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_install_starter_theme'),
            'flavor_install_starter_theme'
        );
        $dismiss_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_dismiss_starter_theme_notice'),
            'flavor_dismiss_starter_theme_notice'
        );

        $error_message = get_option('flavor_starter_theme_notice_error');
        if (!empty($error_message)) {
            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e('Flavor Starter disponible', 'flavor-chat-ia'); ?></strong><br>
                <?php
                if ($is_installed) {
                    esc_html_e('El tema companion "Flavor Starter" está instalado y optimizado para este plugin. ¿Quieres activarlo ahora?', 'flavor-chat-ia');
                } elseif ($is_bundled) {
                    esc_html_e('El tema companion "Flavor Starter" está incluido en el plugin. ¿Quieres instalarlo y activarlo ahora?', 'flavor-chat-ia');
                } else {
                    esc_html_e('El tema companion "Flavor Starter" no está instalado. Puedes instalarlo manualmente desde Apariencia > Temas.', 'flavor-chat-ia');
                }
                ?>
            </p>
            <p>
                <?php if ($is_installed): ?>
                    <a href="<?php echo esc_url($activate_url); ?>" class="button button-primary">
                        <?php esc_html_e('Activar tema', 'flavor-chat-ia'); ?>
                    </a>
                <?php elseif ($is_bundled): ?>
                    <a href="<?php echo esc_url($install_url); ?>" class="button button-primary">
                        <?php esc_html_e('Instalar y activar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url($dismiss_url); ?>" class="button button-secondary">
                    <?php esc_html_e('Ahora no', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Activa el tema Flavor Starter
     *
     * @return void
     */
    public function handle_activate() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_nonce('flavor_activate_starter_theme')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        if ($starter_theme->exists()) {
            $this->ensure_network_enabled('flavor-starter');
            switch_theme('flavor-starter');
        }

        delete_option('flavor_starter_theme_notice_error');
        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url('themes.php'));
        exit;
    }

    /**
     * Instala y activa el tema Flavor Starter desde el bundle del plugin
     *
     * @return void
     */
    public function handle_install() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_nonce('flavor_install_starter_theme')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        $theme_root = get_theme_root();
        $dest = trailingslashit($theme_root) . 'flavor-starter';
        $source = $this->get_bundle_path();

        if (!$starter_theme->exists()) {
            if (!file_exists($source)) {
                update_option(
                    'flavor_starter_theme_notice_error',
                    __('No se encontró el bundle del tema en el plugin.', 'flavor-chat-ia')
                );
                wp_safe_redirect(admin_url());
                exit;
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();

            $result = copy_dir($source, $dest);
            if (is_wp_error($result)) {
                update_option('flavor_starter_theme_notice_error', $result->get_error_message());
                wp_safe_redirect(admin_url());
                exit;
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        if ($starter_theme->exists()) {
            $this->ensure_network_enabled('flavor-starter');
            switch_theme('flavor-starter');
        }

        delete_option('flavor_starter_theme_notice_error');
        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url('themes.php'));
        exit;
    }

    /**
     * Descarta el aviso del tema companion
     *
     * @return void
     */
    public function handle_dismiss_notice() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_nonce('flavor_dismiss_starter_theme_notice')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }

        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url());
        exit;
    }

    /**
     * Verifica nonce de acciones admin con fallback seguro para admins
     *
     * @param string $action Acción a verificar
     * @return bool
     */
    private function verify_nonce($action) {
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if ($nonce && wp_verify_nonce($nonce, $action)) {
            return true;
        }
        return current_user_can('manage_options');
    }

    /**
     * Asegura que el tema esté habilitado en multisite
     *
     * @param string $stylesheet Nombre del tema
     * @return void
     */
    private function ensure_network_enabled($stylesheet) {
        if (!is_multisite()) {
            return;
        }

        $allowed = get_site_option('allowedthemes', []);
        if (!isset($allowed[$stylesheet]) || !$allowed[$stylesheet]) {
            $allowed[$stylesheet] = true;
            update_site_option('allowedthemes', $allowed);
        }
    }
}

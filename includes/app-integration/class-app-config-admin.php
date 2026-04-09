<?php
/**
 * Panel de Administración para Configuración de Apps
 *
 * Permite configurar el comportamiento y apariencia de las apps móviles
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de configuración de apps desde el admin
 */
class Flavor_App_Config_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug de la página de configuración
     */
    private $page_slug = 'flavor-platform-apps';

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
     * Constructor
     */
    private function __construct() {
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_app_token', [$this, 'ajax_generate_token']);
        add_action('wp_ajax_revoke_app_token', [$this, 'ajax_revoke_token']);
        add_action('wp_ajax_flavor_get_menu_items', [$this, 'ajax_get_menu_items']);
        add_action('wp_ajax_flavor_toggle_module_activation', [$this, 'ajax_toggle_module_activation']);

        // AJAX handlers para nuevas herramientas
        add_action('wp_ajax_flavor_export_app_config', [$this, 'ajax_export_config']);
        add_action('wp_ajax_flavor_import_app_config', [$this, 'ajax_import_config']);
        add_action('wp_ajax_flavor_clear_app_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_flavor_regenerate_secrets', [$this, 'ajax_regenerate_secrets']);
        add_action('wp_ajax_flavor_create_app_tables', [$this, 'ajax_create_tables']);
        add_action('wp_ajax_flavor_reset_app_config', [$this, 'ajax_reset_config']);
        add_action('wp_ajax_flavor_send_test_push', [$this, 'ajax_send_test_push']);
        add_action('wp_ajax_flavor_get_api_logs', [$this, 'ajax_get_api_logs']);
        add_action('wp_ajax_flavor_get_recommended_config', [$this, 'ajax_get_recommended_config']);
        add_action('wp_ajax_flavor_sync_modules_from_web', [$this, 'ajax_sync_modules_from_web']);

        // Guardar configuración de push
        add_action('admin_init', [$this, 'handle_push_settings_save']);
    }

    /**
     * Añade página al menú de admin
     */
    public function add_menu_page() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN, // Parent slug (menú principal de Flavor Chat IA)
            __('Configuración de Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            $this->page_slug,
            [$this, 'render_page']
        );
    }

    /**
     * Registra los settings
     */
    public function register_settings() {
        // Configuración general de apps
        register_setting('flavor_apps_config', 'flavor_apps_config', [
            'sanitize_callback' => [$this, 'sanitize_config'],
        ]);

        // Seeds/peers del directorio descentralizado
        register_setting('flavor_apps_config', 'flavor_directory_peer_urls', [
            'sanitize_callback' => [$this, 'sanitize_peer_urls'],
        ]);

        // Tokens de API
        register_setting('flavor_apps_config', 'flavor_apps_tokens', [
            'sanitize_callback' => [$this, 'sanitize_tokens'],
        ]);

        // Sección: Información General
        add_settings_section(
            'general_section',
            __('Información General de la App', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_general_section'],
            $this->page_slug
        );

        // Sección: Branding
        add_settings_section(
            'branding_section',
            __('Branding y Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_branding_section'],
            $this->page_slug
        );

        // Sección: Seguridad
        add_settings_section(
            'security_section',
            __('Seguridad y Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_security_section'],
            $this->page_slug
        );

        // Sección: Módulos
        add_settings_section(
            'modules_section',
            __('Módulos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_modules_section'],
            $this->page_slug
        );

        // Campos: Información General
        add_settings_field(
            'app_name',
            __('Nombre de la App', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_app_name_field'],
            $this->page_slug,
            'general_section'
        );

        add_settings_field(
            'app_description',
            __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_app_description_field'],
            $this->page_slug,
            'general_section'
        );

        // Campos: Branding
        add_settings_field(
            'app_logo',
            __('Logo de la App', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_app_logo_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'primary_color',
            __('Color Primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_primary_color_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'secondary_color',
            __('Color Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_secondary_color_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'accent_color',
            __('Color de Acento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_accent_color_field'],
            $this->page_slug,
            'branding_section'
        );
    }

    /**
     * Encola scripts y estilos
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->page_slug) === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            [],
            null
        );

        // Alpine.js para componentes interactivos
        wp_enqueue_script(
            'alpine-js',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.x',
            true
        );
        wp_script_add_data('alpine-js', 'defer', true);

        // Chart.js para gráficos de estadísticas
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.min.js',
            [],
            '4.x',
            true
        );

        // Usar siempre assets no minificados en este panel para mantener la funcionalidad actualizada.
        $sufijo_asset = '';

        wp_enqueue_script(
            'flavor-platform-apps',
            FLAVOR_CHAT_IA_URL . "includes/app-integration/assets/apps-config{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        $config = get_option('flavor_apps_config', []);
        $logo_id = isset($config['app_logo']) ? $config['app_logo'] : get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

        wp_localize_script('flavor-apps-config', 'flavorAppsConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(),
            'nonce' => wp_create_nonce('flavor_apps_config'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'config' => $config,
            'logoUrl' => $logo_url,
            'strings' => [
                'confirmRevoke' => __('¿Estás seguro de que quieres revocar este token?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tokenGenerated' => __('Token generado con éxito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tokenRevoked' => __('Token revocado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'maxTabs' => __('Máximo 5 tabs activos permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'presetApplied' => __('Preset aplicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'moduleActivated' => __('Módulo activado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'moduleDeactivated' => __('Módulo desactivado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'moduleActivateError' => __('No se pudo actualizar el módulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'copied' => __('¡Copiado!', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);

        wp_enqueue_style(
            'flavor-platform-apps',
            FLAVOR_CHAT_IA_URL . "includes/app-integration/assets/apps-config{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Renderiza la página principal
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $config = get_option('flavor_apps_config', []);
        $logo_id = isset($config['app_logo']) ? $config['app_logo'] : get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        $primary_color = isset($config['primary_color']) ? $config['primary_color'] : '#4CAF50';
        $app_name = isset($config['app_name']) ? $config['app_name'] : get_bloginfo('name');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors('flavor_push'); ?>
            <?php settings_errors('flavor_apps_config'); ?>
            <?php $this->render_demo_data_action_notice(); ?>

            <?php $this->render_connection_qr(); ?>

            <?php
            // Tabs principales (siempre visibles)
            $main_tabs = [
                'general' => __('General', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'navigation' => __('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'branding' => __('Branding', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modules' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'push' => __('Push', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
            // Tabs avanzadas (en dropdown)
            $advanced_tabs = [
                'security' => __('Seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'stats' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'deeplinks' => __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'languages' => __('Idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'directory' => __('Directorio P2P', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'diagnostics' => __('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tools' => __('Herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
            $allowed_tabs = array_merge(array_keys($main_tabs), array_keys($advanced_tabs));
            if (!in_array($active_tab, $allowed_tabs, true)) {
                $active_tab = 'general';
            }
            $is_advanced_tab = array_key_exists($active_tab, $advanced_tabs);
            ?>
            <h2 class="nav-tab-wrapper flavor-app-tabs">
                <?php foreach ($main_tabs as $tab_key => $tab_label): ?>
                    <a href="?page=<?php echo $this->page_slug; ?>&tab=<?php echo $tab_key; ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>

                <!-- Dropdown para tabs avanzadas -->
                <span class="nav-tab flavor-more-dropdown <?php echo $is_advanced_tab ? 'nav-tab-active' : ''; ?>">
                    <span class="flavor-dropdown-toggle">
                        <?php if ($is_advanced_tab): ?>
                            <?php echo esc_html($advanced_tabs[$active_tab]); ?>
                        <?php else: ?>
                            <?php _e('Más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php endif; ?>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </span>
                    <div class="flavor-dropdown-menu">
                        <?php foreach ($advanced_tabs as $tab_key => $tab_label): ?>
                            <a href="?page=<?php echo $this->page_slug; ?>&tab=<?php echo $tab_key; ?>"
                               class="flavor-dropdown-item <?php echo $active_tab === $tab_key ? 'active' : ''; ?>">
                                <?php echo esc_html($tab_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </span>
            </h2>

            <div class="flavor-app-config-layout">
                <!-- Columna principal -->
                <div class="flavor-app-config-main">
                    <?php
                    // Tabs que guardan mediante options.php (settings API).
                    $tabs_with_options_form = [
                        'general',
                        'branding',
                        'navigation',
                        'modules',
                        'security',
                        'directory',
                        'deeplinks',
                        'languages',
                    ];
                    $uses_options_form = in_array($active_tab, $tabs_with_options_form, true);

                    if ($uses_options_form) :
                    ?>
                        <form method="post" action="options.php">
                            <?php settings_fields('flavor_apps_config'); ?>
                    <?php endif; ?>

                    <?php
                    if ($active_tab === 'general') {
                        $this->render_settings_sections(['general_section']);
                        submit_button();
                    } elseif ($active_tab === 'branding') {
                        $this->render_branding_tab();
                    } elseif ($active_tab === 'navigation') {
                        $this->render_navigation_tab();
                    } elseif ($active_tab === 'modules') {
                        $this->render_modules_tab();
                    } elseif ($active_tab === 'security') {
                        $this->render_security_tab();
                    } elseif ($active_tab === 'directory') {
                        $this->render_directory_tab();
                    } elseif ($active_tab === 'stats') {
                        $this->render_stats_tab();
                    } elseif ($active_tab === 'push') {
                        // Push usa formulario propio (admin-post/AJAX), evitar form anidado.
                        $this->render_push_tab();
                    } elseif ($active_tab === 'diagnostics') {
                        $this->render_diagnostics_tab();
                    } elseif ($active_tab === 'tools') {
                        $this->render_tools_tab();
                    } elseif ($active_tab === 'deeplinks') {
                        $this->render_deeplinks_tab();
                    } elseif ($active_tab === 'languages') {
                        $this->render_languages_tab();
                    }
                    ?>

                    <?php if ($uses_options_form) : ?>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Columna preview (phone mockup persistente) -->
                <div class="flavor-app-config-preview-column">
                    <h3><?php _e('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-phone-mockup">
                        <div class="flavor-phone-notch"></div>
                        <div class="flavor-phone-screen">
                            <!-- App Bar -->
                        <div id="mockup-app-bar" style="background-color: <?php echo esc_attr($primary_color); ?>;">
                                <button type="button" class="mockup-hamburger" aria-label="<?php esc_attr_e('Abrir menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </button>
                                <?php if ($logo_url): ?>
                                    <img id="mockup-logo-app" src="<?php echo esc_url($logo_url); ?>" alt="">
                                <?php else: ?>
                                    <img id="mockup-logo-app" src="" alt="" style="display:none;">
                                <?php endif; ?>
                                <span id="mockup-nombre-app"><?php echo esc_html($app_name); ?></span>
                            </div>

                            <!-- Contenido simulado -->
                            <div class="flavor-phone-content">
                                <div class="mockup-content-card">
                                    <div class="mockup-content-line"></div>
                                    <div class="mockup-content-line short"></div>
                                </div>
                                <div class="mockup-content-card">
                                    <div class="mockup-content-line"></div>
                                    <div class="mockup-content-line"></div>
                                    <div class="mockup-content-line short"></div>
                                </div>
                                <div class="mockup-content-card">
                                    <div class="mockup-content-line short"></div>
                                </div>
                            </div>

                            <!-- Bottom Navigation -->
                        <?php
                        $config = get_option('flavor_apps_config', []);
                        $navigation_style = isset($config['navigation_style']) ? $config['navigation_style'] : 'auto';
                        $show_bottom_nav = in_array($navigation_style, ['auto', 'bottom', 'hybrid'], true);
                        $show_drawer = in_array($navigation_style, ['hamburger', 'hybrid'], true);
                        $drawer_items = isset($config['drawer_items']) ? $config['drawer_items'] : [];
                        ?>
                        <div id="mockup-navegacion-inferior" style="<?php echo $show_bottom_nav ? '' : 'display:none;'; ?>">
                            <?php
                                $tabs = isset($config['tabs']) ? $config['tabs'] : [
                                    ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => 0],
                                    ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => 1],
                                    ['id' => 'my_tickets', 'label' => 'Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2],
                                    ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 3],
                                ];
                                usort($tabs, function($a, $b) { return ($a['order'] ?? 0) - ($b['order'] ?? 0); });
                                $active_tabs_shown = 0;
                                foreach ($tabs as $tab_index => $tab):
                                    if (empty($tab['enabled']) || $active_tabs_shown >= 5) continue;
                                    $active_tabs_shown++;
                                ?>
                                    <div class="mockup-tab-item <?php echo $tab_index === 0 ? 'active' : ''; ?>">
                                        <span class="material-icons" style="color: <?php echo $tab_index === 0 ? esc_attr($primary_color) : '#999'; ?>;">
                                            <?php echo esc_html($tab['icon'] ?? 'circle'); ?>
                                        </span>
                                        <span class="mockup-tab-label"><?php echo esc_html($tab['label'] ?? ''); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div id="mockup-drawer" style="<?php echo $show_drawer ? '' : 'display:none;'; ?>">
                            <div class="mockup-drawer-backdrop"></div>
                            <div class="mockup-drawer-panel">
                                <div class="mockup-drawer-header" style="background-color: <?php echo esc_attr($primary_color); ?>;">
                                    <div class="mockup-drawer-avatar">
                                        <span class="material-icons">person</span>
                                    </div>
                                    <div class="mockup-drawer-user">
                                        <span class="mockup-drawer-name"><?php echo esc_html__('Usuario Demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <span class="mockup-drawer-app"><?php echo esc_html($app_name); ?></span>
                                    </div>
                                </div>
                                <div class="mockup-drawer-items">
                                    <?php
                                    $shown = 0;
                                    foreach ($drawer_items as $drawer_item) {
                                        if (!empty($drawer_item['enabled']) && !empty($drawer_item['title'])) {
                                            $shown++;
                                            ?>
                                            <div class="mockup-drawer-item">
                                                <span class="material-icons"><?php echo esc_html($drawer_item['icon'] ?? 'public'); ?></span>
                                                <span><?php echo esc_html($drawer_item['title']); ?></span>
                                            </div>
                                            <?php
                                            if ($shown >= 5) {
                                                break;
                                            }
                                        }
                                    }
                                    if ($shown === 0) {
                                        ?>
                                        <div class="mockup-drawer-item">
                                            <span class="material-icons">home</span>
                                            <span><?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                        <div class="mockup-drawer-item">
                                            <span class="material-icons">extension</span>
                                            <span><?php esc_html_e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Muestra aviso de resultado tras poblar/limpiar datos demo.
     */
    private function render_demo_data_action_notice() {
        if (!isset($_GET['mensaje'])) {
            return;
        }

        $mensaje = sanitize_key(wp_unslash($_GET['mensaje']));
        $count = isset($_GET['count']) ? absint($_GET['count']) : 0;

        if ($mensaje === 'demo_data_populated') {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(__('Datos demo cargados en %d módulo(s).', FLAVOR_PLATFORM_TEXT_DOMAIN), $count))
            );
            return;
        }

        if ($mensaje === 'demo_data_cleared') {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(__('Datos demo eliminados en %d módulo(s).', FLAVOR_PLATFORM_TEXT_DOMAIN), $count))
            );
            return;
        }

        if ($mensaje === 'demo_data_error' || $mensaje === 'demo_data_clear_error') {
            echo '<div class="notice notice-error is-dismissible"><p>' .
                esc_html__('No se pudo completar la operación de datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                '</p></div>';
        }
    }

    /**
     * Renderiza la pestaña de General
     */
    public function render_general_section() {
        echo '<p>' . __('Configura la información básica de tu aplicación móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Campo: Nombre de la App
     */
    public function render_app_name_field() {
        $config = get_option('flavor_apps_config', []);
        $value = isset($config['app_name']) ? $config['app_name'] : get_bloginfo('name');
        ?>
        <input type="text"
               name="flavor_apps_config[app_name]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <p class="description">
            <?php _e('Nombre que aparecerá en la app móvil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Campo: Descripción
     */
    public function render_app_description_field() {
        $config = get_option('flavor_apps_config', []);
        $value = isset($config['app_description']) ? $config['app_description'] : get_bloginfo('description');
        ?>
        <textarea name="flavor_apps_config[app_description]"
                  rows="3"
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Descripción corta de tu comunidad o proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Renderiza sección de branding
     */
    public function render_branding_section() {
        echo '<p>' . __('Personaliza los colores y el logo de tu app.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Obtiene o genera el token secreto del sitio para admin
     *
     * @return string Token secreto
     */
    public static function get_admin_site_secret() {
        if (class_exists('Chat_IA_Mobile_API')) {
            return Chat_IA_Mobile_API::get_admin_site_secret();
        }

        $secret = get_option('chat_ia_admin_site_secret');
        if (empty($secret)) {
            $legacy_secret = get_option('flavor_app_admin_secret');
            if (!empty($legacy_secret)) {
                $secret = $legacy_secret;
                update_option('chat_ia_admin_site_secret', $secret);
                delete_option('flavor_app_admin_secret');
            } else {
                $secret = wp_generate_password(32, false, false);
                update_option('chat_ia_admin_site_secret', $secret);
            }
        }
        return $secret;
    }

    /**
     * Obtiene los datos del QR para app de admin
     * Formato JSON compatible con Flutter
     *
     * @return array Datos del QR
     */
    public static function get_admin_qr_data() {
        $config = get_option('flavor_apps_config', []);
        return [
            'url' => home_url(),
            'server_url' => home_url(),
            'name' => $config['app_name'] ?? get_bloginfo('name'),
            'api' => '/wp-json/chat-ia-mobile/v1',
            'api_namespace' => '/wp-json/chat-ia-mobile/v1',
            'api_url' => home_url('/wp-json/chat-ia-mobile/v1'),
            'type' => 'admin',
            'token' => self::get_admin_site_secret(),
        ];
    }

    /**
     * Obtiene los datos del QR para app de cliente
     * Formato JSON compatible con Flutter (sin token)
     *
     * @return array Datos del QR
     */
    public static function get_client_qr_data() {
        $config = get_option('flavor_apps_config', []);
        return [
            'url' => home_url(),
            'server_url' => home_url(),
            'name' => $config['app_name'] ?? get_bloginfo('name'),
            'api' => '/wp-json/chat-ia-mobile/v1',
            'api_namespace' => '/wp-json/chat-ia-mobile/v1',
            'api_url' => home_url('/wp-json/chat-ia-mobile/v1'),
            'type' => 'client',
        ];
    }

    /**
     * Renderiza el QR de conexión
     * Genera QR con formato JSON compatible con Flutter
     */
    private function render_connection_qr() {
        // Datos para QR de admin (incluye token)
        $admin_qr_data = self::get_admin_qr_data();
        $admin_qr_json = wp_json_encode($admin_qr_data);
        $admin_qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($admin_qr_json);

        // Datos para QR de cliente (sin token)
        $client_qr_data = self::get_client_qr_data();
        $client_qr_json = wp_json_encode($client_qr_data);
        $client_qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($client_qr_json);

        $admin_apk_url = home_url('/app-downloads/flavour-app-admin.apk');
        $client_apk_url = home_url('/app-downloads/flavour-app-cliente.apk');
        ?>
        <div class="flavor-qr-section">
            <div class="flavor-qr-section-header">
                <span class="dashicons dashicons-smartphone"></span>
                <h3><?php _e('Conectar Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>

            <div class="flavor-qr-cards">
                <!-- QR Admin -->
                <div class="flavor-qr-card flavor-qr-card--admin">
                    <h4 class="flavor-qr-card-title">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('App Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <p class="flavor-qr-card-description">
                        <?php _e('Acceso completo al panel de gestión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <div class="flavor-qr-code-wrapper">
                        <img src="<?php echo esc_url($admin_qr_url); ?>"
                             alt="QR Admin"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"/>
                        <div class="flavor-qr-code-error">
                            <?php _e('Error al cargar QR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <p class="flavor-qr-card-warning">
                        <?php _e('⚠️ No compartir - Contiene token de acceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <div class="flavor-qr-card-download">
                        <a href="<?php echo esc_url($admin_apk_url); ?>" class="button button-secondary">
                            <?php _e('Descargar APK Admin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>

                <!-- QR Cliente -->
                <div class="flavor-qr-card flavor-qr-card--client">
                    <h4 class="flavor-qr-card-title">
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('App Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <p class="flavor-qr-card-description">
                        <?php _e('Para usuarios y clientes de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <div class="flavor-qr-code-wrapper">
                        <img src="<?php echo esc_url($client_qr_url); ?>"
                             alt="QR Cliente"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"/>
                        <div class="flavor-qr-code-error">
                            <?php _e('Error al cargar QR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <p class="flavor-qr-card-warning">
                        <?php _e('✓ Seguro para compartir públicamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <div class="flavor-qr-card-download">
                        <a href="<?php echo esc_url($client_apk_url); ?>" class="button button-secondary">
                            <?php _e('Descargar APK Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </div>

            <hr class="flavor-qr-section-divider">

            <div class="flavor-qr-connection-data">
                <div class="flavor-qr-data-card flavor-qr-data-card--admin">
                    <div class="flavor-qr-data-header">
                        <h4><?php _e('Datos de conexión (Admin)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <button type="button" class="flavor-copy-btn" data-copy-target="admin-qr-json">
                            <span class="dashicons dashicons-clipboard"></span>
                            <span class="flavor-copy-text"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </button>
                    </div>
                    <textarea id="admin-qr-json" readonly onclick="this.select();"><?php echo esc_textarea($admin_qr_json); ?></textarea>
                </div>
                <div class="flavor-qr-data-card flavor-qr-data-card--client">
                    <div class="flavor-qr-data-header">
                        <h4><?php _e('Datos de conexión (Cliente)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <button type="button" class="flavor-copy-btn" data-copy-target="client-qr-json">
                            <span class="dashicons dashicons-clipboard"></span>
                            <span class="flavor-copy-text"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </button>
                    </div>
                    <textarea id="client-qr-json" readonly onclick="this.select();"><?php echo esc_textarea($client_qr_json); ?></textarea>
                </div>
            </div>

            <hr class="flavor-qr-section-divider">

            <div class="flavor-qr-instructions">
                <h4>
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php _e('¿Cómo conectar la app?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h4>
                <ol>
                    <li><?php _e('Descarga la app Flavor desde la tienda de aplicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('Abre la app y toca "Escanear QR" o "Configurar servidor"', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('Escanea el código QR correspondiente (Admin o Cliente)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('La app se configurará automáticamente con tu logo, colores y módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de Branding
     */
    private function render_branding_tab() {
        $config = get_option('flavor_apps_config', []);
        $theme_mode = isset($config['theme_mode']) ? $config['theme_mode'] : 'light';
        $dark_primary = isset($config['dark_primary_color']) ? $config['dark_primary_color'] : '#1976D2';
        $dark_secondary = isset($config['dark_secondary_color']) ? $config['dark_secondary_color'] : '#424242';
        $dark_background = isset($config['dark_background_color']) ? $config['dark_background_color'] : '#121212';
        ?>
        <div class="flavor-branding-tab" x-data="flavorBrandingEditor()">
            <!-- Presets de Color -->
            <div class="branding-section">
                <h3><?php _e('Presets de Colores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Selecciona un preset para aplicar rápidamente una paleta de colores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="color-presets-grid">
                    <button type="button" class="preset-card" @click="applyPreset('nature')" :class="{ 'active': currentPreset === 'nature' }">
                        <div class="preset-colors">
                            <span style="background: #4CAF50;"></span>
                            <span style="background: #8BC34A;"></span>
                            <span style="background: #CDDC39;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Naturaleza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                    <button type="button" class="preset-card" @click="applyPreset('ocean')" :class="{ 'active': currentPreset === 'ocean' }">
                        <div class="preset-colors">
                            <span style="background: #2196F3;"></span>
                            <span style="background: #03A9F4;"></span>
                            <span style="background: #00BCD4;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Océano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                    <button type="button" class="preset-card" @click="applyPreset('sunset')" :class="{ 'active': currentPreset === 'sunset' }">
                        <div class="preset-colors">
                            <span style="background: #FF5722;"></span>
                            <span style="background: #FF9800;"></span>
                            <span style="background: #FFC107;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Atardecer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                    <button type="button" class="preset-card" @click="applyPreset('purple')" :class="{ 'active': currentPreset === 'purple' }">
                        <div class="preset-colors">
                            <span style="background: #9C27B0;"></span>
                            <span style="background: #E91E63;"></span>
                            <span style="background: #F48FB1;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Púrpura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                    <button type="button" class="preset-card" @click="applyPreset('corporate')" :class="{ 'active': currentPreset === 'corporate' }">
                        <div class="preset-colors">
                            <span style="background: #1976D2;"></span>
                            <span style="background: #455A64;"></span>
                            <span style="background: #78909C;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Corporativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                    <button type="button" class="preset-card" @click="applyPreset('elegant')" :class="{ 'active': currentPreset === 'elegant' }">
                        <div class="preset-colors">
                            <span style="background: #212121;"></span>
                            <span style="background: #D4AF37;"></span>
                            <span style="background: #FFFFFF;"></span>
                        </div>
                        <span class="preset-name"><?php _e('Elegante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>
            </div>

            <!-- Colores Personalizados -->
            <div class="branding-section">
                <h3><?php _e('Colores Personalizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php $this->render_settings_sections(['branding_section']); ?>
            </div>

            <!-- Modo Oscuro -->
            <div class="branding-section">
                <h3><?php _e('Modo de Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Modo del tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="flavor_apps_config[theme_mode]" id="theme_mode" x-model="themeMode" @change="updatePreview()">
                                <option value="light" <?php selected($theme_mode, 'light'); ?>><?php _e('Claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="dark" <?php selected($theme_mode, 'dark'); ?>><?php _e('Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="system" <?php selected($theme_mode, 'system'); ?>><?php _e('Sistema (automático)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                            <p class="description"><?php _e('Sistema usa la preferencia del dispositivo del usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="dark-mode-colors" x-show="themeMode === 'dark' || themeMode === 'system'">
                    <h4><?php _e('Colores del Tema Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Color Primario (oscuro)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <td>
                                <input type="text" name="flavor_apps_config[dark_primary_color]"
                                       value="<?php echo esc_attr($dark_primary); ?>"
                                       class="flavor-color-picker" data-default-color="#1976D2">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Color Secundario (oscuro)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <td>
                                <input type="text" name="flavor_apps_config[dark_secondary_color]"
                                       value="<?php echo esc_attr($dark_secondary); ?>"
                                       class="flavor-color-picker" data-default-color="#424242">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Fondo (oscuro)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <td>
                                <input type="text" name="flavor_apps_config[dark_background_color]"
                                       value="<?php echo esc_attr($dark_background); ?>"
                                       class="flavor-color-picker" data-default-color="#121212">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Exportar/Importar Tema -->
            <div class="branding-section">
                <h3><?php _e('Exportar/Importar Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Guarda tu configuración de colores para usarla en otros sitios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="theme-actions">
                    <button type="button" class="button" @click="exportTheme()">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Exportar Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>

                    <input type="file" x-ref="themeFileInput" accept=".json" @change="importTheme($event)" style="display: none;">
                    <button type="button" class="button" @click="$refs.themeFileInput.click()">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Importar Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <?php submit_button(); ?>
        </div>

        <style>
            .flavor-branding-tab { padding: 20px 0; }
            .branding-section { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .branding-section h3 { margin-top: 0; }
            .color-presets-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-top: 15px; }
            .preset-card { background: #fff; border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; text-align: center; transition: all 0.2s; }
            .preset-card:hover { border-color: #2271b1; transform: translateY(-2px); }
            .preset-card.active { border-color: #2271b1; background: #f0f6fc; }
            .preset-colors { display: flex; gap: 4px; justify-content: center; margin-bottom: 8px; }
            .preset-colors span { width: 24px; height: 24px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); }
            .preset-name { font-size: 12px; color: #1d2327; }
            .dark-mode-colors { background: #2c3338; padding: 20px; border-radius: 8px; margin-top: 15px; }
            .dark-mode-colors h4 { color: #fff; margin-top: 0; }
            .dark-mode-colors .form-table th { color: #c3c4c7; }
            .theme-actions { display: flex; gap: 10px; }
            .theme-actions .button { display: inline-flex; align-items: center; gap: 5px; }
            @media (max-width: 1200px) { .color-presets-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 782px) { .color-presets-grid { grid-template-columns: repeat(2, 1fr); } }
        </style>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flavorBrandingEditor', () => ({
                currentPreset: '',
                themeMode: '<?php echo esc_js($theme_mode); ?>',

                presets: {
                    nature: { primary: '#4CAF50', secondary: '#8BC34A', accent: '#CDDC39' },
                    ocean: { primary: '#2196F3', secondary: '#03A9F4', accent: '#00BCD4' },
                    sunset: { primary: '#FF5722', secondary: '#FF9800', accent: '#FFC107' },
                    purple: { primary: '#9C27B0', secondary: '#E91E63', accent: '#F48FB1' },
                    corporate: { primary: '#1976D2', secondary: '#455A64', accent: '#78909C' },
                    elegant: { primary: '#212121', secondary: '#D4AF37', accent: '#FFFFFF' }
                },

                applyPreset(presetName) {
                    this.currentPreset = presetName;
                    const preset = this.presets[presetName];
                    if (!preset) return;

                    // Actualizar color pickers
                    this.setColorPicker('primary_color', preset.primary);
                    this.setColorPicker('secondary_color', preset.secondary);
                    this.setColorPicker('accent_color', preset.accent);

                    // Actualizar preview del mockup
                    this.updatePreview();
                },

                setColorPicker(fieldName, color) {
                    const input = document.querySelector(`input[name="flavor_apps_config[${fieldName}]"]`);
                    if (input) {
                        jQuery(input).wpColorPicker('color', color);
                    }
                },

                updatePreview() {
                    const primaryInput = document.querySelector('input[name="flavor_apps_config[primary_color]"]');
                    if (primaryInput) {
                        const color = primaryInput.value || '#4CAF50';
                        const appBar = document.getElementById('mockup-app-bar');
                        if (appBar) {
                            appBar.style.backgroundColor = color;
                        }
                    }
                },

                exportTheme() {
                    const theme = {
                        version: '1.0',
                        exported_at: new Date().toISOString(),
                        colors: {}
                    };

                    const colorFields = ['primary_color', 'secondary_color', 'accent_color', 'dark_primary_color', 'dark_secondary_color', 'dark_background_color'];
                    colorFields.forEach(field => {
                        const input = document.querySelector(`input[name="flavor_apps_config[${field}]"]`);
                        if (input) {
                            theme.colors[field] = input.value;
                        }
                    });

                    const themeModeSelect = document.getElementById('theme_mode');
                    if (themeModeSelect) {
                        theme.theme_mode = themeModeSelect.value;
                    }

                    const blob = new Blob([JSON.stringify(theme, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'flavor-theme-' + new Date().toISOString().split('T')[0] + '.json';
                    link.click();
                    URL.revokeObjectURL(url);
                },

                importTheme(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        try {
                            const theme = JSON.parse(e.target.result);
                            if (theme.colors) {
                                Object.entries(theme.colors).forEach(([field, color]) => {
                                    this.setColorPicker(field, color);
                                });
                            }
                            if (theme.theme_mode) {
                                this.themeMode = theme.theme_mode;
                                const select = document.getElementById('theme_mode');
                                if (select) select.value = theme.theme_mode;
                            }
                            this.updatePreview();
                            alert('<?php _e('Tema importado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                        } catch (err) {
                            alert('<?php _e('Error al importar el tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                        }
                    };
                    reader.readAsText(file);
                    event.target.value = '';
                },

                init() {
                    // Sincronizar preview con cambios de color
                    setTimeout(() => {
                        document.querySelectorAll('.flavor-color-picker, .wp-color-picker').forEach(input => {
                            jQuery(input).on('change', () => this.updatePreview());
                        });
                        this.updatePreview();
                    }, 500);
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza solo las secciones indicadas para evitar duplicados entre pestañas
     */
    private function render_settings_sections(array $section_ids) {
        global $wp_settings_sections, $wp_settings_fields;
        $page = $this->page_slug;

        if (!isset($wp_settings_sections[$page])) {
            return;
        }

        foreach ($section_ids as $section_id) {
            if (empty($wp_settings_sections[$page][$section_id])) {
                continue;
            }

            $section = $wp_settings_sections[$page][$section_id];
            if (!empty($section['title'])) {
                echo '<h2>' . esc_html($section['title']) . '</h2>';
            }
            if (isset($section['callback']) && is_callable($section['callback'])) {
                call_user_func($section['callback'], $section);
            }
            if (!empty($wp_settings_fields[$page][$section_id])) {
                echo '<table class="form-table">';
                do_settings_fields($page, $section_id);
                echo '</table>';
            }
        }
    }

    /**
     * Renderiza la pestaña de Navegación
     */
    private function render_navigation_tab() {
        $config = get_option('flavor_apps_config', []);
        $navigation_style = isset($config['navigation_style']) ? $config['navigation_style'] : 'auto';
        $hybrid_show_appbar = isset($config['hybrid_show_appbar']) ? (bool) $config['hybrid_show_appbar'] : true;
        $map_provider = isset($config['map_provider']) ? $config['map_provider'] : 'osm';
        $google_maps_api_key = isset($config['google_maps_api_key']) ? $config['google_maps_api_key'] : '';
        $default_tabs = [
            ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => 0],
            ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => 1],
            ['id' => 'my_tickets', 'label' => 'Mis Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2],
            ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 3],
            ['id' => 'modules', 'label' => 'Módulos', 'icon' => 'extension', 'enabled' => false, 'order' => 4],
            ['id' => 'grupos_consumo', 'label' => 'Grupos Consumo', 'icon' => 'groups', 'enabled' => false, 'order' => 5],
            ['id' => 'banco_tiempo', 'label' => 'Banco de Tiempo', 'icon' => 'handyman', 'enabled' => false, 'order' => 6],
            ['id' => 'marketplace', 'label' => 'Marketplace', 'icon' => 'store', 'enabled' => false, 'order' => 7],
        ];
        $tabs = isset($config['tabs']) ? $config['tabs'] : $default_tabs;
        usort($tabs, function($a, $b) { return ($a['order'] ?? 0) - ($b['order'] ?? 0); });

        $default_tab = isset($config['default_tab']) ? $config['default_tab'] : 'info';
        $core_tab_ids = ['chat', 'reservations', 'my_tickets', 'info', 'camps', 'experiences'];
        $menu_source = isset($config['web_sections_menu']) ? $config['web_sections_menu'] : '';

        // Presets
        $menu_items_payload = $this->get_menu_items_payload($menu_source);
        ?>
        <h2><?php _e('Navegación de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php _e('Configura la navegación de tu app móvil. Puedes tener hasta 5 tabs en el footer (barra inferior) y elementos ilimitados en el menú hamburguesa.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="navigation_style"><?php _e('Tipo de navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="flavor_apps_config[navigation_style]" id="navigation_style">
                        <option value="auto" <?php selected($navigation_style, 'auto'); ?>>
                            <?php _e('Automático (según layout)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="bottom" <?php selected($navigation_style, 'bottom'); ?>>
                            <?php _e('Barra inferior (bottom tabs)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="hamburger" <?php selected($navigation_style, 'hamburger'); ?>>
                            <?php _e('Menú hamburguesa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="hybrid" <?php selected($navigation_style, 'hybrid'); ?>>
                            <?php _e('Híbrido (tabs + hamburguesa)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Forzar navegación en la app: automático usa el layout actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('AppBar en modo híbrido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_apps_config[hybrid_show_appbar]" value="1" <?php checked($hybrid_show_appbar); ?>>
                        <?php _e('Mostrar AppBar (barra superior) cuando la navegación es híbrida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_provider"><?php _e('Proveedor de mapas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="flavor_apps_config[map_provider]" id="map_provider">
                        <option value="osm" <?php selected($map_provider, 'osm'); ?>>
                            <?php _e('OpenStreetMap (sin clave)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="google" <?php selected($map_provider, 'google'); ?>>
                            <?php _e('Google Maps (requiere clave)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="google_maps_api_key"><?php _e('Google Maps API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text"
                           id="google_maps_api_key"
                           name="flavor_apps_config[google_maps_api_key]"
                           value="<?php echo esc_attr($google_maps_api_key); ?>"
                           class="regular-text">
                    <p class="description">
                        <?php _e('Se usa solo si el proveedor es Google Maps.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="web_sections_menu"><?php _e('Menú de secciones web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="flavor_apps_config[web_sections_menu]" id="web_sections_menu">
                        <option value=""><?php _e('Automático (principal si existe)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        $locations = get_nav_menu_locations();
                        $menus = wp_get_nav_menus();
                        foreach ($locations as $location_slug => $menu_id):
                            $value = 'location:' . $location_slug;
                            ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($menu_source, $value); ?>>
                                <?php
                                echo esc_html(sprintf(__('Ubicación: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $location_slug));
                                ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (!empty($menus)): ?>
                            <?php foreach ($menus as $menu_obj): ?>
                                <?php $value = 'menu:' . $menu_obj->term_id; ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($menu_source, $value); ?>>
                                    <?php echo esc_html(sprintf(__('Menú: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $menu_obj->name)); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description">
                        <?php _e('Guarda para recargar la lista de secciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php
        // Detectar si hay módulos nuevos no configurados para mostrar badge
        $active_modules = [];
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $active_modules = Flavor_Chat_Module_Loader::get_active_modules_cached();
        }
        $current_tabs = isset($config['tabs']) ? array_column($config['tabs'], 'id') : [];
        $module_tab_ids = ['eventos', 'grupos_consumo', 'banco_tiempo', 'marketplace', 'socios', 'carpooling',
                          'comunidades', 'tramites', 'incidencias', 'avisos_municipales', 'cursos', 'biblioteca',
                          'chat_grupos', 'chat_interno', 'presupuestos_participativos', 'crowdfunding',
                          'economia_don', 'red_social', 'trabajo_digno'];
        $unconfigured_modules = 0;
        foreach ($active_modules as $mod_id) {
            $normalized_id = str_replace('-', '_', $mod_id);
            if (in_array($normalized_id, $module_tab_ids) && !in_array($normalized_id, $current_tabs)) {
                $unconfigured_modules++;
            }
        }
        ?>
        <div class="flavor-presets-bar">
            <h4><?php _e('Presets rápidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <button type="button" class="flavor-preset-btn flavor-preset-recommended" data-preset="recomendado"
                    title="<?php esc_attr_e('Configura automáticamente los tabs según los módulos activos en tu sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-yes-alt"></span> <?php _e('Recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <?php if ($unconfigured_modules > 0): ?>
                    <span class="flavor-preset-badge"><?php echo esc_html($unconfigured_modules); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="restaurante"
                    title="<?php esc_attr_e('Chat IA, reservas, tickets y WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-food"></span> <?php _e('Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="peluqueria"
                    title="<?php esc_attr_e('Citas, chat y gestión de clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-art"></span> <?php _e('Peluquería', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="comunidad"
                    title="<?php esc_attr_e('Grupos de consumo, banco de tiempo, eventos y marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-groups"></span> <?php _e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="tienda"
                    title="<?php esc_attr_e('WooCommerce, carrito, pedidos y marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-cart"></span> <?php _e('Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="empresarial"
                    title="<?php esc_attr_e('Gestión empresarial, clientes, facturas y fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-briefcase"></span> <?php _e('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <div class="flavor-tabs-editor">
            <h3><?php _e('Tabs de Navegación Inferior (Footer)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                <?php _e('<strong>Máximo 5 tabs activas</strong> - Estas pestañas aparecen en la barra inferior de la app para acceso rápido. Para más opciones, usa el menú hamburguesa (ver sección abajo).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <p>
                <button type="button" class="button" id="flavor-add-web-tab">
                    <?php _e('Añadir sección web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <select id="flavor-web-section-select" style="min-width: 220px;">
                    <option value=""><?php _e('Añadir desde menú web…', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php if (!empty($menu_items_payload)): ?>
                        <?php foreach ($menu_items_payload as $menu_item): ?>
                            <?php
                            $title = $menu_item['title'] ?? '';
                            $url = $menu_item['url'] ?? '';
                            if (!$url) continue;
                            ?>
                            <option value="<?php echo esc_url($url); ?>" data-title="<?php echo esc_attr($title); ?>">
                                <?php echo esc_html($title); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button type="button" class="button" id="flavor-add-web-tab-from-menu">
                    <?php _e('Añadir sección del menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-add-all-web-tabs">
                    <?php _e('Añadir todas las secciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-sync-web-tab-labels">
                    <?php _e('Sincronizar etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-refresh-web-sections">
                    <?php _e('Actualizar lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </p>
            <ul class="flavor-tabs-list" id="flavor-tabs-sortable">
                <?php foreach ($tabs as $tab_index => $tab): ?>
                <?php
                    $content_type = $tab['content_type'] ?? 'native_screen';
                    $content_ref = $tab['content_ref'] ?? $tab['id'] ?? '';
                    $is_core = in_array($tab['id'] ?? '', $core_tab_ids, true);
                ?>
                <li class="flavor-tab-item <?php echo empty($tab['enabled']) ? 'disabled' : ''; ?>" data-tab-id="<?php echo esc_attr($tab['id']); ?>" data-tab-core="<?php echo $is_core ? '1' : '0'; ?>">
                    <span class="flavor-tab-drag-handle dashicons dashicons-menu"></span>

                    <label class="flavor-toggle-switch">
                        <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][enabled]" value="0">
                        <input type="checkbox"
                               name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][enabled]"
                               value="1"
                               class="flavor-tab-toggle"
                               <?php checked(!empty($tab['enabled'])); ?>>
                        <span class="flavor-toggle-slider"></span>
                    </label>

                    <button type="button" class="flavor-tab-icon-btn" data-tab-index="<?php echo $tab_index; ?>">
                        <span class="material-icons"><?php echo esc_html($tab['icon'] ?? 'circle'); ?></span>
                    </button>

                    <input type="text"
                           name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][label]"
                           value="<?php echo esc_attr($tab['label'] ?? ''); ?>"
                           class="flavor-tab-label-input"
                           placeholder="<?php esc_attr_e('Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                    <select name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][content_type]" class="flavor-tab-content-type">
                        <option value="native_screen" <?php selected($content_type, 'native_screen'); ?>>
                            <?php _e('Pantalla nativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="page" <?php selected($content_type, 'page'); ?>>
                            <?php _e('Página', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="cpt" <?php selected($content_type, 'cpt'); ?>>
                            <?php _e('Contenido (CPT)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                        <option value="module" <?php selected($content_type, 'module'); ?>>
                            <?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </option>
                    </select>

                    <!-- Selector de pantalla nativa -->
                    <select name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][content_ref]"
                            class="flavor-tab-content-ref flavor-content-native-screen"
                            <?php echo $content_type !== 'native_screen' ? 'style="display:none;"' : ''; ?>>
                        <option value="info" <?php selected($content_ref, 'info'); ?>><?php _e('Info', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="chat" <?php selected($content_ref, 'chat'); ?>><?php _e('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="reservations" <?php selected($content_ref, 'reservations'); ?>><?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="my_tickets" <?php selected($content_ref, 'my_tickets'); ?>><?php _e('Mis Tickets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="profile" <?php selected($content_ref, 'profile'); ?>><?php _e('Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="notifications" <?php selected($content_ref, 'notifications'); ?>><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="settings" <?php selected($content_ref, 'settings'); ?>><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>

                    <!-- Selector de página -->
                    <select name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][content_ref_page]"
                            class="flavor-tab-content-ref flavor-content-page"
                            <?php echo $content_type !== 'page' ? 'style="display:none;"' : ''; ?>>
                        <option value=""><?php _e('Seleccionar página...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        $pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_title']);
                        foreach ($pages as $p):
                        ?>
                            <option value="<?php echo esc_attr($p->post_name); ?>" <?php selected($content_ref, $p->post_name); ?>>
                                <?php echo esc_html($p->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Selector de CPT -->
                    <select name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][content_ref_cpt]"
                            class="flavor-tab-content-ref flavor-content-cpt"
                            <?php echo $content_type !== 'cpt' ? 'style="display:none;"' : ''; ?>>
                        <option value=""><?php _e('Seleccionar tipo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        $cpts = get_post_types(['public' => true, '_builtin' => false], 'objects');
                        foreach ($cpts as $cpt):
                        ?>
                            <option value="<?php echo esc_attr($cpt->name); ?>" <?php selected($content_ref, $cpt->name); ?>>
                                <?php echo esc_html($cpt->labels->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Selector de módulo -->
                    <select name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][content_ref_module]"
                            class="flavor-tab-content-ref flavor-content-module"
                            <?php echo $content_type !== 'module' ? 'style="display:none;"' : ''; ?>>
                        <option value=""><?php _e('Seleccionar módulo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        // Obtener módulos ACTIVOS desde el Module Loader
                        $active_module_ids = [];
                        $registered_modules = [];
                        if (class_exists('Flavor_Chat_Module_Loader')) {
                            $active_module_ids = Flavor_Chat_Module_Loader::get_active_modules_cached();
                            $loader = Flavor_Chat_Module_Loader::get_instance();
                            $registered_modules = $loader->get_registered_modules();
                        }
                        // Mostrar todos los módulos activos
                        foreach ($active_module_ids as $mod_id):
                            // Buscar nombre en registered_modules o generar fallback
                            $mod_data = $registered_modules[$mod_id] ?? [];
                            $mod_label = $mod_data['name'] ?? ucwords(str_replace(['_', '-'], ' ', $mod_id));
                        ?>
                            <option value="<?php echo esc_attr($mod_id); ?>" <?php selected($content_ref, $mod_id); ?>>
                                <?php echo esc_html($mod_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!$is_core): ?>
                        <button type="button" class="button-link-delete flavor-tab-remove">
                            <?php _e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>

                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][id]" value="<?php echo esc_attr($tab['id']); ?>">
                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][icon]" value="<?php echo esc_attr($tab['icon'] ?? 'circle'); ?>" class="flavor-tab-icon-value">
                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][order]" value="<?php echo esc_attr($tab_index); ?>" class="flavor-tab-order">
                </li>
                <?php endforeach; ?>
            </ul>

            <p class="description" style="margin-top: 15px;">
                <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                <?php _e('Todo el contenido se renderiza de forma <strong>nativa</strong> en la app usando la API REST. No se usan WebViews.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="default_tab"><?php _e('Pestaña por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="flavor_apps_config[default_tab]" id="default_tab">
                        <?php foreach ($tabs as $tab): ?>
                            <option value="<?php echo esc_attr($tab['id']); ?>" <?php selected($default_tab, $tab['id']); ?>>
                                <?php echo esc_html($tab['label'] ?? $tab['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <hr>

        <h3><?php _e('Menú Hamburguesa (Drawer) - Ilimitado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p class="description">
            <span class="dashicons dashicons-menu" style="color: #2271b1;"></span>
            <?php _e('Estas secciones aparecen en el menú lateral (hamburguesa ☰) de la app. <strong>Puedes añadir tantas como quieras</strong>, sin límite de cantidad. Todo el contenido se renderiza de forma nativa.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <?php
        $saved_drawer_items = isset($config['drawer_items']) && is_array($config['drawer_items'])
            ? $config['drawer_items']
            : [];

        // Combinar items del menú con los guardados
        $combined_drawer_items = [];
        $existing_urls = [];

        // Primero, añadir items guardados que tengan enabled
        foreach ($saved_drawer_items as $drawer_item) {
            if (!empty($drawer_item['enabled'])) {
                $url = $drawer_item['url'] ?? 'drawer_' . count($combined_drawer_items);
                $combined_drawer_items[] = $drawer_item;
                $existing_urls[$url] = true;
            }
        }

        // Luego, añadir items del menú de WordPress que no estén ya
        if (!empty($menu_items_payload)) {
            foreach ($menu_items_payload as $menu_item) {
                $url = $menu_item['url'] ?? '';
                if ($url && !isset($existing_urls[$url])) {
                    // Buscar configuración guardada para este item
                    $saved = null;
                    foreach ($saved_drawer_items as $si) {
                        if (($si['url'] ?? '') === $url) {
                            $saved = $si;
                            break;
                        }
                    }
                    if ($saved) {
                        $combined_drawer_items[] = $saved;
                    } else {
                        $combined_drawer_items[] = [
                            'enabled' => true,
                            'title' => $menu_item['title'] ?? $url,
                            'url' => $url,
                            'icon' => 'public',
                            'content_type' => 'page',
                            'content_ref' => '',
                            'order' => count($combined_drawer_items),
                        ];
                    }
                    $existing_urls[$url] = true;
                }
            }
        }

        // Ordenar por order
        usort($combined_drawer_items, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // Obtener páginas y CPTs para los selectores
        $all_pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_title']);
        $all_cpts = get_post_types(['public' => true, '_builtin' => false], 'objects');

        // Obtener módulos ACTIVOS desde el Module Loader
        $active_module_ids_drawer = [];
        $registered_modules_drawer = [];
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $active_module_ids_drawer = Flavor_Chat_Module_Loader::get_active_modules_cached();
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $registered_modules_drawer = $loader->get_registered_modules();
        }
        ?>

        <!-- Botones para añadir secciones al drawer -->
        <p>
            <button type="button" class="button" id="flavor-add-drawer-item">
                <span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span>
                <?php _e('Añadir sección manual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php if (!empty($menu_items_payload)): ?>
                <select id="flavor-drawer-section-select" style="min-width: 220px;">
                    <option value=""><?php _e('Añadir desde menú web…', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($menu_items_payload as $menu_item):
                        $url = $menu_item['url'] ?? '';
                        $title = $menu_item['title'] ?? $url;
                        if (!$url) continue;
                    ?>
                        <option value="<?php echo esc_url($url); ?>" data-title="<?php echo esc_attr($title); ?>">
                            <?php echo esc_html($title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="flavor-add-drawer-from-menu">
                    <?php _e('Añadir del menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-add-all-drawer-items">
                    <?php _e('Añadir todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </p>

        <?php if (!empty($combined_drawer_items)): ?>
            <div class="flavor-info-sections-editor">
                <ul class="flavor-info-sections-list" id="flavor-drawer-sections-sortable">
                    <?php foreach ($combined_drawer_items as $index => $drawer_item): ?>
                        <?php
                        $url = $drawer_item['url'] ?? 'drawer_' . $index;
                        $title = $drawer_item['title'] ?? $url;
                        $is_enabled = !empty($drawer_item['enabled']);
                        $drawer_icon = $drawer_item['icon'] ?? 'public';
                        $drawer_content_type = $drawer_item['content_type'] ?? 'page';
                        $drawer_content_ref = $drawer_item['content_ref'] ?? '';
                        $drawer_order = $drawer_item['order'] ?? $index;

                        // Obtener content_ref del campo correcto según el tipo
                        if (empty($drawer_content_ref)) {
                            if ($drawer_content_type === 'page') {
                                $drawer_content_ref = $drawer_item['content_ref_page'] ?? '';
                            } elseif ($drawer_content_type === 'cpt') {
                                $drawer_content_ref = $drawer_item['content_ref_cpt'] ?? '';
                            } elseif ($drawer_content_type === 'module') {
                                $drawer_content_ref = $drawer_item['content_ref_module'] ?? '';
                            }
                        }

                        // Intentar inferir content_ref de la URL si no está guardado
                        if (empty($drawer_content_ref) && $drawer_content_type === 'page' && $url) {
                            $path = trim(wp_parse_url($url, PHP_URL_PATH), '/');
                            if ($path) {
                                $page = get_page_by_path($path);
                                if ($page) {
                                    $drawer_content_ref = $page->post_name;
                                }
                            }
                        }
                        ?>
                        <li class="flavor-info-section-item flavor-drawer-item" data-drawer-url="<?php echo esc_attr($url); ?>">
                            <span class="section-drag-handle dashicons dashicons-menu"></span>
                            <label class="flavor-toggle-switch">
                                <input type="hidden" name="flavor_apps_config[drawer_items][<?php echo $index; ?>][enabled]" value="0">
                                <input type="checkbox"
                                       name="flavor_apps_config[drawer_items][<?php echo $index; ?>][enabled]"
                                       value="1"
                                       <?php checked($is_enabled); ?>>
                                <span class="flavor-toggle-slider"></span>
                            </label>
                            <button type="button" class="flavor-tab-icon-btn flavor-drawer-icon-btn" data-drawer-index="<?php echo $index; ?>">
                                <span class="material-icons"><?php echo esc_html($drawer_icon); ?></span>
                            </button>
                            <span class="section-label"><?php echo esc_html($title); ?></span>

                            <!-- Selector de tipo de contenido -->
                            <select name="flavor_apps_config[drawer_items][<?php echo $index; ?>][content_type]" class="flavor-drawer-content-type">
                                <option value="native_screen" <?php selected($drawer_content_type, 'native_screen'); ?>>
                                    <?php _e('Pantalla nativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </option>
                                <option value="page" <?php selected($drawer_content_type, 'page'); ?>>
                                    <?php _e('Página', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </option>
                                <option value="cpt" <?php selected($drawer_content_type, 'cpt'); ?>>
                                    <?php _e('Contenido (CPT)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </option>
                                <option value="module" <?php selected($drawer_content_type, 'module'); ?>>
                                    <?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </option>
                            </select>

                            <!-- Selector de pantalla nativa -->
                            <select name="flavor_apps_config[drawer_items][<?php echo $index; ?>][content_ref]"
                                    class="flavor-drawer-content-ref flavor-drawer-native-screen"
                                    <?php echo $drawer_content_type !== 'native_screen' ? 'style="display:none;"' : ''; ?>>
                                <option value="info" <?php selected($drawer_content_ref, 'info'); ?>><?php _e('Info', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="chat" <?php selected($drawer_content_ref, 'chat'); ?>><?php _e('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="reservations" <?php selected($drawer_content_ref, 'reservations'); ?>><?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="my_tickets" <?php selected($drawer_content_ref, 'my_tickets'); ?>><?php _e('Mis Tickets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="profile" <?php selected($drawer_content_ref, 'profile'); ?>><?php _e('Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="notifications" <?php selected($drawer_content_ref, 'notifications'); ?>><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="settings" <?php selected($drawer_content_ref, 'settings'); ?>><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>

                            <!-- Selector de página -->
                            <select name="flavor_apps_config[drawer_items][<?php echo $index; ?>][content_ref_page]"
                                    class="flavor-drawer-content-ref flavor-drawer-page"
                                    <?php echo $drawer_content_type !== 'page' ? 'style="display:none;"' : ''; ?>>
                                <option value=""><?php _e('Seleccionar página...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($all_pages as $p): ?>
                                    <option value="<?php echo esc_attr($p->post_name); ?>" <?php selected($drawer_content_ref, $p->post_name); ?>>
                                        <?php echo esc_html($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Selector de CPT -->
                            <select name="flavor_apps_config[drawer_items][<?php echo $index; ?>][content_ref_cpt]"
                                    class="flavor-drawer-content-ref flavor-drawer-cpt"
                                    <?php echo $drawer_content_type !== 'cpt' ? 'style="display:none;"' : ''; ?>>
                                <option value=""><?php _e('Seleccionar tipo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($all_cpts as $cpt): ?>
                                    <option value="<?php echo esc_attr($cpt->name); ?>" <?php selected($drawer_content_ref, $cpt->name); ?>>
                                        <?php echo esc_html($cpt->labels->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Selector de módulo -->
                            <select name="flavor_apps_config[drawer_items][<?php echo $index; ?>][content_ref_module]"
                                    class="flavor-drawer-content-ref flavor-drawer-module"
                                    <?php echo $drawer_content_type !== 'module' ? 'style="display:none;"' : ''; ?>>
                                <option value=""><?php _e('Seleccionar módulo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($active_module_ids_drawer as $mod_id):
                                    $mod_data = $registered_modules_drawer[$mod_id] ?? [];
                                    $mod_label = $mod_data['name'] ?? ucwords(str_replace(['_', '-'], ' ', $mod_id));
                                ?>
                                    <option value="<?php echo esc_attr($mod_id); ?>" <?php selected($drawer_content_ref, $mod_id); ?>>
                                        <?php echo esc_html($mod_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="button" class="button-link-delete flavor-drawer-remove" title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>

                            <input type="hidden" name="flavor_apps_config[drawer_items][<?php echo $index; ?>][title]" value="<?php echo esc_attr($title); ?>" class="flavor-drawer-title-value">
                            <input type="hidden" name="flavor_apps_config[drawer_items][<?php echo $index; ?>][url]" value="<?php echo esc_attr($url); ?>" class="flavor-drawer-url-value">
                            <input type="hidden" name="flavor_apps_config[drawer_items][<?php echo $index; ?>][icon]" value="<?php echo esc_attr($drawer_icon); ?>" class="flavor-drawer-icon-value">
                            <input type="hidden" name="flavor_apps_config[drawer_items][<?php echo $index; ?>][order]" value="<?php echo esc_attr($drawer_order); ?>" class="flavor-drawer-order">
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="flavor-info-sections-editor">
                <ul class="flavor-info-sections-list" id="flavor-drawer-sections-sortable">
                    <!-- Items se añadirán dinámicamente via JavaScript -->
                </ul>
                <p class="description" style="margin-top: 10px;">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('No hay secciones en el menú hamburguesa. Usa los botones de arriba para añadir secciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
        <?php endif; ?>

        <p class="description" style="margin-top: 10px;">
            <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
            <?php _e('Todo el contenido del menú hamburguesa se renderiza de forma <strong>nativa</strong> en la app.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <hr>

        <h3><?php _e('Secciones de la pantalla Info', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php _e('Activa, ordena y personaliza las secciones que se muestran en la pestaña Info. Puedes editar los títulos y agregar secciones personalizadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <?php
        $default_info_sections = [
            'header' => ['label' => __('Cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'image', 'enabled' => true, 'order' => 0, 'type' => 'predefined'],
            'about' => ['label' => __('Sobre nosotros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'info', 'enabled' => true, 'order' => 1, 'type' => 'predefined'],
            'hours' => ['label' => __('Horarios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'access_time', 'enabled' => true, 'order' => 2, 'type' => 'predefined'],
            'contact' => ['label' => __('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'phone', 'enabled' => true, 'order' => 3, 'type' => 'predefined'],
            'location' => ['label' => __('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'location_on', 'enabled' => true, 'order' => 4, 'type' => 'predefined'],
            'social' => ['label' => __('Redes sociales', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'share', 'enabled' => true, 'order' => 5, 'type' => 'predefined'],
            'gallery' => ['label' => __('Galería', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'photo_library', 'enabled' => false, 'order' => 6, 'type' => 'predefined'],
            'services' => ['label' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'work', 'enabled' => false, 'order' => 7, 'type' => 'predefined'],
            'team' => ['label' => __('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'people', 'enabled' => false, 'order' => 8, 'type' => 'predefined'],
            'faq' => ['label' => __('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'help', 'enabled' => false, 'order' => 9, 'type' => 'predefined'],
        ];
        $info_sections = isset($config['info_sections']) ? $config['info_sections'] : $default_info_sections;

        // Asegurar que las secciones antiguas tengan iconos
        foreach ($info_sections as $section_id => &$section_data) {
            if (is_array($section_data) && !isset($section_data['icon'])) {
                $section_data['icon'] = $default_info_sections[$section_id]['icon'] ?? 'article';
            }
            if (is_array($section_data) && !isset($section_data['type'])) {
                $section_data['type'] = isset($default_info_sections[$section_id]) ? 'predefined' : 'custom';
            }
        }

        // Ordenar por orden
        uasort($info_sections, function($a, $b) {
            $order_a = is_array($a) ? ($a['order'] ?? 0) : 0;
            $order_b = is_array($b) ? ($b['order'] ?? 0) : 0;
            return $order_a - $order_b;
        });
        ?>

        <div class="flavor-info-sections-editor">
            <p>
                <button type="button" class="button" id="flavor-add-info-section">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Añadir sección personalizada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </p>
            <ul class="flavor-info-sections-list" id="flavor-info-sections-sortable">
                <?php
                $section_index = 0;
                foreach ($info_sections as $section_id => $section_data):
                    $section_label = is_array($section_data) ? ($section_data['label'] ?? $section_id) : $section_id;
                    $section_enabled = is_array($section_data) ? (!empty($section_data['enabled'])) : true;
                    $section_icon = is_array($section_data) ? ($section_data['icon'] ?? 'article') : 'article';
                    $section_type = is_array($section_data) ? ($section_data['type'] ?? 'predefined') : 'predefined';
                    $is_custom = $section_type === 'custom';
                ?>
                <li class="flavor-info-section-item" data-section-id="<?php echo esc_attr($section_id); ?>" data-section-type="<?php echo esc_attr($section_type); ?>">
                    <span class="section-drag-handle dashicons dashicons-menu"></span>

                    <label class="flavor-toggle-switch">
                        <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][enabled]" value="0">
                        <input type="checkbox"
                               name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][enabled]"
                               value="1"
                               <?php checked($section_enabled); ?>>
                        <span class="flavor-toggle-slider"></span>
                    </label>

                    <button type="button" class="flavor-section-icon-btn" data-section-id="<?php echo esc_attr($section_id); ?>">
                        <span class="material-icons"><?php echo esc_html($section_icon); ?></span>
                    </button>

                    <input type="text"
                           name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][label]"
                           value="<?php echo esc_attr($section_label); ?>"
                           class="flavor-section-label-input"
                           placeholder="<?php esc_attr_e('Título de la sección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                    <?php if ($is_custom): ?>
                        <button type="button" class="button-link-delete flavor-section-remove">
                            <?php _e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>

                    <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][icon]" value="<?php echo esc_attr($section_icon); ?>" class="flavor-section-icon-value">
                    <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][order]" value="<?php echo esc_attr($section_index); ?>" class="flavor-section-order">
                    <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][type]" value="<?php echo esc_attr($section_type); ?>">
                </li>
                <?php
                    $section_index++;
                endforeach;
                ?>
            </ul>

            <p class="description" style="margin-top: 15px;">
                <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                <?php _e('Arrastra las secciones para cambiar el orden. Las secciones personalizadas pueden ser eliminadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <?php submit_button(); ?>

        <!-- Modal selector de iconos -->
        <div class="flavor-icon-modal-overlay" id="flavor-icon-modal">
            <div class="flavor-icon-modal">
                <div class="flavor-icon-modal-header">
                    <h3><?php _e('Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <button type="button" class="flavor-icon-modal-close">&times;</button>
                </div>
                <div class="flavor-icon-search">
                    <input type="text" id="flavor-icon-search-input" placeholder="<?php esc_attr_e('Buscar icono...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
                <div class="flavor-icon-grid" id="flavor-icon-grid">
                    <?php
                    $material_icons = [
                        'home', 'info', 'chat_bubble', 'calendar_today', 'confirmation_number',
                        'shopping_cart', 'restaurant', 'store', 'people', 'person',
                        'favorite', 'star', 'settings', 'notifications', 'search',
                        'menu_book', 'local_offer', 'event', 'access_time', 'location_on',
                        'phone', 'email', 'photo_camera', 'image', 'map',
                        'directions_car', 'flight', 'hotel', 'spa', 'fitness_center',
                        'local_cafe', 'local_bar', 'local_pizza', 'local_pharmacy', 'local_hospital',
                        'school', 'work', 'account_balance', 'attach_money', 'receipt',
                        'build', 'handyman', 'pets', 'eco', 'recycling',
                        'volunteer_activism', 'group_work', 'forum', 'announcement', 'campaign',
                        'inventory', 'category', 'dashboard', 'analytics', 'trending_up', 'public', 'link',
                    ];
                    foreach ($material_icons as $icon_name):
                    ?>
                        <div class="flavor-icon-option" data-icon="<?php echo esc_attr($icon_name); ?>" title="<?php echo esc_attr($icon_name); ?>">
                            <span class="material-icons"><?php echo esc_html($icon_name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Presets de navegación
            var presets = {
                'recomendado': {
                    // Este preset usa los módulos activos dinámicamente
                    dynamic: true
                },
                'restaurante': {
                    tabs: ['chat', 'reservations', 'my_tickets', 'info'],
                    drawer: ['woocommerce', 'eventos']
                },
                'peluqueria': {
                    tabs: ['chat', 'reservations', 'my_tickets', 'info'],
                    drawer: []
                },
                'comunidad': {
                    tabs: ['info', 'eventos', 'red_social', 'participacion'],
                    drawer: ['grupos_consumo', 'banco_tiempo', 'marketplace', 'colectivos', 'comunidades']
                },
                'tienda': {
                    tabs: ['chat', 'woocommerce', 'my_tickets', 'info'],
                    drawer: ['marketplace']
                },
                'empresarial': {
                    tabs: ['chat', 'info', 'clientes', 'facturas'],
                    drawer: ['fichaje_empleados', 'socios']
                }
            };

            // Módulos activos desde PHP
            var activeModules = <?php
                $active = [];
                if (class_exists('Flavor_Chat_Module_Loader')) {
                    $active = Flavor_Chat_Module_Loader::get_active_modules_cached();
                }
                echo json_encode(array_values($active));
            ?>;

            $('.flavor-preset-btn').on('click', function() {
                var presetName = $(this).data('preset');
                var preset = presets[presetName];

                if (!preset) {
                    console.warn('Preset no encontrado:', presetName);
                    return;
                }

                // Marcar botón como activo
                $('.flavor-preset-btn').removeClass('active').css('opacity', '0.7');
                $(this).addClass('active').css('opacity', '1');

                if (preset.dynamic) {
                    // Preset "recomendado": activar tabs según módulos activos
                    applyRecommendedPreset(activeModules);
                } else {
                    applyStaticPreset(preset);
                }
            });

            function applyRecommendedPreset(modules) {
                var $tabsList = $('#flavor-tabs-sortable');

                // Primero, habilitar las tabs core
                var coreTabs = ['chat', 'info'];

                // Mapeo de módulos a IDs de tab
                var moduleToTab = {
                    'eventos': 'eventos',
                    'reservas': 'reservations',
                    'marketplace': 'marketplace',
                    'grupos_consumo': 'grupos_consumo',
                    'banco_tiempo': 'banco_tiempo',
                    'red_social': 'red_social',
                    'participacion': 'participacion',
                    'socios': 'socios',
                    'woocommerce': 'woocommerce',
                    'colectivos': 'colectivos',
                    'comunidades': 'comunidades',
                    'incidencias': 'incidencias',
                    'tramites': 'tramites'
                };

                // Desactivar todas las tabs primero
                $tabsList.find('.flavor-tab-toggle').prop('checked', false);
                $tabsList.find('.flavor-tab-item').addClass('disabled');

                // Activar tabs core
                coreTabs.forEach(function(tabId) {
                    var $item = $tabsList.find('[data-tab-id="' + tabId + '"]');
                    if ($item.length) {
                        $item.find('.flavor-tab-toggle').prop('checked', true);
                        $item.removeClass('disabled');
                    }
                });

                // Activar tabs de módulos activos (máximo 3 adicionales para no pasar de 5)
                var added = 0;
                modules.forEach(function(modId) {
                    if (added >= 3) return;
                    var tabId = moduleToTab[modId];
                    if (tabId) {
                        var $item = $tabsList.find('[data-tab-id="' + tabId + '"]');
                        if ($item.length && !$item.find('.flavor-tab-toggle').prop('checked')) {
                            $item.find('.flavor-tab-toggle').prop('checked', true);
                            $item.removeClass('disabled');
                            added++;
                        }
                    }
                });

                showPresetMessage('<?php echo esc_js(__('Preset "Recomendado" aplicado con', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> ' + (2 + added) + ' tabs activas. <?php echo esc_js(__('Guarda los cambios para aplicar.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            }

            function applyStaticPreset(preset) {
                var $tabsList = $('#flavor-tabs-sortable');

                // Desactivar todas las tabs
                $tabsList.find('.flavor-tab-toggle').prop('checked', false);
                $tabsList.find('.flavor-tab-item').addClass('disabled');

                // Activar las tabs del preset
                var activated = 0;
                preset.tabs.forEach(function(tabId) {
                    var $item = $tabsList.find('[data-tab-id="' + tabId + '"]');
                    if ($item.length) {
                        $item.find('.flavor-tab-toggle').prop('checked', true);
                        $item.removeClass('disabled');
                        activated++;
                    }
                });

                showPresetMessage('<?php echo esc_js(__('Preset aplicado con', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> ' + activated + ' tabs. <?php echo esc_js(__('Guarda los cambios para aplicar.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            }

            function showPresetMessage(msg) {
                // Mostrar mensaje temporal
                var $msg = $('<div class="notice notice-success" style="margin: 10px 0; padding: 10px;"><p>' + msg + '</p></div>');
                $('.flavor-presets-bar').after($msg);
                setTimeout(function() { $msg.fadeOut(function() { $(this).remove(); }); }, 4000);
            }
        });
        </script>

        <style>
        .flavor-presets-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; padding: 15px; background: #f0f0f1; border-radius: 8px; flex-wrap: wrap; }
        .flavor-presets-bar h4 { margin: 0; margin-right: 10px; }
        .flavor-preset-btn { padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; background: #fff; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; opacity: 0.7; }
        .flavor-preset-btn:hover { border-color: #2271b1; opacity: 1; }
        .flavor-preset-btn.active { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; opacity: 1; }
        .flavor-preset-btn .dashicons { font-size: 16px; width: 16px; height: 16px; }
        </style>
        <?php
    }

    /**
     * Campo: Logo
     */
    public function render_app_logo_field() {
        $config = get_option('flavor_apps_config', []);
        $logo_id = isset($config['app_logo']) ? $config['app_logo'] : get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        ?>
        <div class="flavor-logo-upload">
            <input type="hidden"
                   name="flavor_apps_config[app_logo]"
                   id="app_logo_id"
                   value="<?php echo esc_attr($logo_id); ?>">

            <div class="logo-preview">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>"
                         alt="Logo"
                         style="max-width: 200px; height: auto;">
                <?php else: ?>
                    <p><?php _e('No hay logo seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>

            <button type="button" class="button button-secondary" id="upload_logo_button">
                <?php _e('Seleccionar Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button" id="remove_logo_button" <?php echo !$logo_url ? 'style="display:none;"' : ''; ?>>
                <?php _e('Eliminar Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>

            <p class="description">
                <?php _e('Recomendado: PNG transparente, 512x512px', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Campo: Color Primario
     */
    public function render_primary_color_field() {
        $config = get_option('flavor_apps_config', []);
        $value = isset($config['primary_color']) ? $config['primary_color'] : '#4CAF50';
        ?>
        <input type="text"
               name="flavor_apps_config[primary_color]"
               value="<?php echo esc_attr($value); ?>"
               class="color-picker">
        <p class="description">
            <?php _e('Color principal de la app (botones, barras, etc.)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Campo: Color Secundario
     */
    public function render_secondary_color_field() {
        $config = get_option('flavor_apps_config', []);
        $value = isset($config['secondary_color']) ? $config['secondary_color'] : '#8BC34A';
        ?>
        <input type="text"
               name="flavor_apps_config[secondary_color]"
               value="<?php echo esc_attr($value); ?>"
               class="color-picker">
        <p class="description">
            <?php _e('Color secundario para complementar el primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Campo: Color de Acento
     */
    public function render_accent_color_field() {
        $config = get_option('flavor_apps_config', []);
        $value = isset($config['accent_color']) ? $config['accent_color'] : '#FF9800';
        ?>
        <input type="text"
               name="flavor_apps_config[accent_color]"
               value="<?php echo esc_attr($value); ?>"
               class="color-picker">
        <p class="description">
            <?php _e('Color para resaltar elementos (notificaciones, alertas)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Renderiza sección de seguridad
     */
    public function render_security_section() {
        echo '<p>' . __('Gestiona los tokens de API para las aplicaciones móviles.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Renderiza la pestaña de Seguridad
     */
    private function render_security_tab() {
        ?>
        <h2><?php _e('Tokens de API para Apps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php _e('Genera tokens de API para autenticar las aplicaciones móviles. Cada token puede tener un nombre identificativo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-tokens-section">
            <h3><?php _e('Generar Nuevo Token', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php _e('Nombre del Token', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="text" id="new_token_name" class="regular-text"
                               placeholder="<?php _e('Ej: App Android Producción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <button type="button" class="button button-primary" id="generate_token_button">
                            <?php _e('Generar Token', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </td>
                </tr>
            </table>

            <div id="new_token_display" style="display: none;" class="notice notice-success">
                <p><strong><?php _e('Token generado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                <code id="new_token_value" style="font-size: 12px; padding: 10px; display: block; background: #f0f0f0;"></code>
                <p class="description">
                    <?php _e('⚠️ Guarda este token en un lugar seguro. No podrás verlo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <h3><?php _e('Tokens Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php $this->render_active_tokens(); ?>
        </div>

        <hr>

        <h2><?php _e('Endpoints de la API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php _e('Las apps deben usar estos endpoints para comunicarse con el servidor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('URL Completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php _e('Descubrimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/info')); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/modules')); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/theme')); ?></code></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renderiza la lista de tokens activos
     */
    private function render_active_tokens() {
        $tokens = get_option('flavor_apps_tokens', []);

        if (empty($tokens)) {
            echo '<p>' . __('No hay tokens activos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Fecha de Creación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Último Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tokens as $token_id => $token_data): ?>
                <tr>
                    <td><strong><?php echo esc_html($token_data['name']); ?></strong></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $token_data['created'])); ?></td>
                    <td>
                        <?php
                        if (isset($token_data['last_used']) && $token_data['last_used']) {
                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $token_data['last_used']));
                        } else {
                            _e('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                        ?>
                    </td>
                    <td>
                        <button type="button"
                                class="button button-small revoke-token"
                                data-token-id="<?php echo esc_attr($token_id); ?>">
                            <?php _e('Revocar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renderiza la pestaña de Directorio
     */
    private function render_directory_tab() {
        $config = get_option('flavor_apps_config', []);
        $is_public = isset($config['public_in_directory']) && $config['public_in_directory'];
        $is_registered = get_option('flavor_business_registered', false);
        $last_sync = get_option('flavor_business_last_sync', 0);
        $peer_urls = get_option('flavor_directory_peer_urls', []);
        $peer_text = is_array($peer_urls) ? implode("\n", $peer_urls) : (string) $peer_urls;

        ?>
        <h2><?php _e('Directorio Público de Negocios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php _e('Permite que los usuarios de las apps descubran y se conecten a tu negocio/comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Aparecer en el Directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_apps_config[public_in_directory]"
                               value="1"
                               <?php checked($is_public, true); ?>>
                        <?php _e('Sí, hacer mi negocio visible en el directorio público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <p class="description">
                        <?php _e('Si activas esta opción, los usuarios podrán encontrar y conectarse a tu negocio desde la app.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_address"><?php _e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        name="flavor_apps_config[business_address]"
                        id="business_address"
                        class="regular-text"
                        value="<?php echo esc_attr($config['business_address'] ?? ''); ?>"
                        placeholder="<?php echo esc_attr__('Calle y número', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                    <p class="description">
                        <?php _e('Se usa para calcular automáticamente las coordenadas (lat/lng).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_city"><?php _e('Ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        name="flavor_apps_config[business_city]"
                        id="business_city"
                        class="regular-text"
                        value="<?php echo esc_attr($config['business_city'] ?? ''); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_country"><?php _e('País', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        name="flavor_apps_config[business_country]"
                        id="business_country"
                        class="regular-text"
                        value="<?php echo esc_attr($config['business_country'] ?? 'ES'); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_postal_code"><?php _e('Código Postal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        name="flavor_apps_config[business_postal_code]"
                        id="business_postal_code"
                        class="regular-text"
                        value="<?php echo esc_attr($config['business_postal_code'] ?? ''); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_lat"><?php _e('Latitud / Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input
                        type="number"
                        step="0.00000001"
                        name="flavor_apps_config[business_lat]"
                        id="business_lat"
                        style="width:150px;"
                        value="<?php echo esc_attr($config['business_lat'] ?? ''); ?>"
                        placeholder="<?php echo esc_attr__('Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                    <input
                        type="number"
                        step="0.00000001"
                        name="flavor_apps_config[business_lng]"
                        id="business_lng"
                        style="width:150px;margin-left:6px;"
                        value="<?php echo esc_attr($config['business_lng'] ?? ''); ?>"
                        placeholder="<?php echo esc_attr__('Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                    <p class="description">
                        <?php _e('Si se dejan vacías, se intentarán calcular automáticamente al guardar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_category"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="flavor_apps_config[business_category]" id="business_category" class="regular-text">
                        <option value=""><?php _e('Selecciona una categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        $categories = [
                            'cooperativa' => __('Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'asociacion' => __('Asociación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'grupo_consumo' => __('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'economia_social' => __('Economía Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'comercio_local' => __('Comercio Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'other' => __('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];
                        $current_category = isset($config['business_category']) ? $config['business_category'] : '';
                        foreach ($categories as $value => $label):
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_category, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Tipo de negocio o comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="flavor_directory_peer_urls"><?php _e('Seeds / Nodos conocidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <textarea
                        name="flavor_directory_peer_urls"
                        id="flavor_directory_peer_urls"
                        class="large-text"
                        rows="4"
                        placeholder="https://nodo1.tudominio.com&#10;https://nodo2.tudominio.com"
                    ><?php echo esc_textarea($peer_text); ?></textarea>
                    <p class="description">
                        <?php _e('Lista de nodos/semillas (uno por línea o separados por coma). Se usa para sincronizar el directorio de forma descentralizada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>

        <hr>

        <h3><?php _e('Estado del Registro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <?php if ($is_public): ?>
            <div class="notice notice-success inline">
                <p>
                    <strong><?php _e('Tu negocio está configurado como público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                </p>
            </div>

            <?php if ($is_registered): ?>
                <p><?php _e('✓ Registrado en el directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php if ($last_sync): ?>
                    <p>
                        <?php
                        printf(
                            __('Última sincronización: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync)
                        );
                        ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p><?php _e('○ Pendiente de registrar en el directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button type="button" class="button button-primary" id="register_in_directory">
                    <?php _e('Registrar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>

            <hr>

            <h4><?php _e('Información que se compartirá:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><?php _e('Nombre de tu negocio/comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('URL del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Dirección y coordenadas (lat/lng)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Módulos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
            <p class="description">
                <?php _e('Ningún dato privado o sensible se comparte. Solo información pública necesaria para que los usuarios encuentren tu negocio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong><?php _e('Tu negocio NO está visible en el directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                </p>
                <p><?php _e('Activa la opción "Aparecer en el Directorio" arriba para que los usuarios puedan encontrarte.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>

        <hr>

        <h3><?php _e('¿Cómo funciona el Directorio?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <ol>
            <li><?php _e('Activas "Aparecer en el Directorio" y guardas los cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php _e('Añades seeds/nodos conocidos para que la red se sincronice', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php _e('Los nodos comparten sus listados y se actualizan entre sí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php _e('Los usuarios de las apps pueden buscar negocios por proximidad o categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php _e('Cuando encuentren tu negocio, podrán conectarse con un solo tap', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php _e('La app se configurará automáticamente con tus colores, logo y módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
        </ol>

        <?php
    }

    /**
     * Renderiza sección de módulos
     */
    public function render_modules_section() {
        echo '<p>' . __('Estado de los módulos disponibles para las apps.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Devuelve etiquetas de categorías para la gestión de módulos.
     *
     * @return array<string, string>
     */
    private function get_module_category_labels() {
        return [
            'comercio' => __('Comercio y economía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunidad' => __('Comunidad y participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'servicios' => __('Servicios y gestión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sostenibilidad' => __('Sostenibilidad y territorio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunicacion' => __('Comunicación y contenidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'empresa' => __('Empresa y organización', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Resuelve la categoría principal de un módulo para UI.
     *
     * @param string $module_id
     * @return string
     */
    private function resolve_module_category($module_id) {
        $module_id = (string) $module_id;

        $map = [
            'comercio' => [
                'woocommerce', 'marketplace', 'grupos_consumo', 'banco_tiempo', 'economia_don',
                'crowdfunding', 'facturas',
            ],
            'comunidad' => [
                'comunidades', 'colectivos', 'participacion', 'presupuestos_participativos',
                'foros', 'red_social', 'chat_grupos', 'ayuda_vecinal', 'socios',
            ],
            'servicios' => [
                'tramites', 'incidencias', 'espacios_comunes', 'parkings', 'reservas',
                'eventos', 'talleres', 'biblioteca', 'cursos',
            ],
            'sostenibilidad' => [
                'huertos_urbanos', 'bicicletas_compartidas', 'compostaje', 'energia_comunitaria',
                'reciclaje', 'carpooling', 'transparencia',
            ],
            'comunicacion' => [
                'avisos_municipales', 'podcast', 'radio', 'multimedia', 'chat_interno',
            ],
            'empresa' => [
                'empresarial', 'clientes', 'fichaje_empleados', 'advertising', 'bares',
            ],
        ];

        foreach ($map as $category => $module_ids) {
            if (in_array($module_id, $module_ids, true)) {
                return $category;
            }
        }

        return 'otros';
    }

    /**
     * Recomendaciones de módulos complementarios por módulo base.
     *
     * @return array<string, array<int, string>>
     */
    private function get_module_recommendations_map() {
        return [
            'eventos' => ['reservas', 'talleres', 'foros', 'red_social'],
            'reservas' => ['eventos', 'espacios_comunes', 'notificaciones'],
            'grupos_consumo' => ['marketplace', 'banco_tiempo', 'colectivos', 'comunidades'],
            'marketplace' => ['grupos_consumo', 'banco_tiempo', 'chat_interno'],
            'banco_tiempo' => ['grupos_consumo', 'ayuda_vecinal', 'comunidades'],
            'foros' => ['chat_grupos', 'red_social', 'participacion'],
            'red_social' => ['foros', 'chat_grupos', 'eventos'],
            'chat_grupos' => ['foros', 'comunidades', 'red_social'],
            'comunidades' => ['colectivos', 'eventos', 'chat_grupos', 'participacion'],
            'colectivos' => ['comunidades', 'grupos_consumo', 'marketplace'],
            'participacion' => ['presupuestos_participativos', 'foros', 'avisos_municipales'],
            'presupuestos_participativos' => ['participacion', 'transparencia', 'foros'],
            'tramites' => ['incidencias', 'avisos_municipales', 'transparencia'],
            'incidencias' => ['tramites', 'avisos_municipales', 'mapa_actores'],
            'transparencia' => ['participacion', 'presupuestos_participativos', 'tramites'],
            'podcast' => ['radio', 'multimedia', 'eventos'],
            'radio' => ['podcast', 'multimedia', 'avisos_municipales'],
            'multimedia' => ['podcast', 'radio', 'red_social'],
            'huertos_urbanos' => ['compostaje', 'reciclaje', 'banco_tiempo'],
            'reciclaje' => ['compostaje', 'huella_ecologica', 'ayuda_vecinal'],
            'carpooling' => ['eventos', 'comunidades', 'reservas'],
            'empresarial' => ['clientes', 'facturas', 'fichaje_empleados', 'advertising'],
            'clientes' => ['empresarial', 'facturas', 'email_marketing'],
            'advertising' => ['clientes', 'marketplace', 'eventos'],
        ];
    }

    /**
     * Renderiza la pestaña de Módulos
     */
    private function render_modules_tab() {
        $config = get_option('flavor_apps_config', []);
        $enabled_modules = isset($config['modules']) ? $config['modules'] : [];
        $plugin_settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $plugin_settings['active_modules'] ?? [];

        ?>
        <div class="flavor-modules-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php _e('Módulos Disponibles para la App Móvil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p style="margin: 5px 0 0 0;"><?php _e('Activa o desactiva los módulos que estarán visibles en la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div>
                <button type="button" class="button button-primary" id="sync-modules-from-web" style="display: flex; align-items: center; gap: 5px;">
                    <span class="material-icons" style="font-size: 18px;">sync</span>
                    <?php _e('Sincronizar con Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>

        <div class="notice notice-info" style="margin-bottom: 20px;">
            <p>
                <span class="material-icons" style="font-size: 16px; vertical-align: middle;">info</span>
                <?php _e('Los módulos ahora se sincronizan automáticamente con el App Composer web. Usa el botón "Sincronizar con Web" para forzar la sincronización de todos los módulos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <?php
            $category_labels = $this->get_module_category_labels();
            $category_order = array_keys($category_labels);
            $recommendations_map = $this->get_module_recommendations_map();

            $known_modules = [
                'woocommerce' => [
                    'name' => __('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Integración con tienda WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_WooCommerce_API',
                    'icon' => 'local_offer',
                    'color' => '#9C27B0',
                ],
                'grupos_consumo' => [
                    'name' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Pedidos colectivos y gestión de grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Grupos_Consumo_API',
                    'icon' => 'shopping_cart',
                    'color' => '#4CAF50',
                ],
                'marketplace' => [
                    'name' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Anuncios de regalo, venta e intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Marketplace_API',
                    'icon' => 'store',
                    'color' => '#FF9800',
                ],
                'banco_tiempo' => [
                    'name' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Intercambio de servicios y tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Banco_Tiempo_API',
                    'icon' => 'access_time',
                    'color' => '#2196F3',
                ],
                'facturas' => [
                    'name' => __('Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Gestión de facturas para administradores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Facturas_Module',
                    'icon' => 'receipt',
                    'color' => '#607D8B',
                ],
                'fichaje_empleados' => [
                    'name' => __('Fichaje de Empleados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Control de horarios y asistencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Fichaje_Empleados_Module',
                    'icon' => 'work',
                    'color' => '#795548',
                ],
                'eventos' => [
                    'name' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Gestión de eventos comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Eventos_Module',
                    'icon' => 'event',
                    'color' => '#E91E63',
                ],
                'socios' => [
                    'name' => __('Gestión de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Control de miembros y cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Socios_Module',
                    'icon' => 'people',
                    'color' => '#3F51B5',
                ],
                'advertising' => [
                    'name' => __('Publicidad Ética', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Sistema de anuncios éticos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Advertising_Module',
                    'icon' => 'campaign',
                    'color' => '#FF5722',
                ],
                'foros' => [
                    'name' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Debates y conversaciones por temas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Foros_Module',
                    'icon' => 'forum',
                    'color' => '#8E24AA',
                ],
                'red_social' => [
                    'name' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Red social comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Red_Social_Module',
                    'icon' => 'public',
                    'color' => '#009688',
                ],
                'chat_grupos' => [
                    'name' => __('Chat de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Canales y grupos temáticos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Grupos_Module',
                    'icon' => 'chat',
                    'color' => '#03A9F4',
                ],
                'chat_interno' => [
                    'name' => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Mensajería privada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Interno_Module',
                    'icon' => 'chat_bubble',
                    'color' => '#0288D1',
                ],
                'comunidades' => [
                    'name' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Gestión de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Comunidades_Module',
                    'icon' => 'groups',
                    'color' => '#4CAF50',
                ],
                'colectivos' => [
                    'name' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Asociaciones y cooperativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Colectivos_Module',
                    'icon' => 'handshake',
                    'color' => '#6D4C41',
                ],
                'participacion' => [
                    'name' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Votaciones y propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Participacion_Module',
                    'icon' => 'how_to_vote',
                    'color' => '#7CB342',
                ],
                'presupuestos_participativos' => [
                    'name' => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Decide inversiones comunitarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Presupuestos_Participativos_Module',
                    'icon' => 'account_balance',
                    'color' => '#5D4037',
                ],
                'transparencia' => [
                    'name' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Portal de transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Transparencia_Module',
                    'icon' => 'visibility',
                    'color' => '#607D8B',
                ],
                'avisos_municipales' => [
                    'name' => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Comunicados oficiales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Avisos_Municipales_API',
                    'icon' => 'warning',
                    'color' => '#F57C00',
                ],
                'tramites' => [
                    'name' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Gestión de trámites online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Tramites_Module',
                    'icon' => 'assignment',
                    'color' => '#455A64',
                ],
                'huertos_urbanos' => [
                    'name' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Parcelas y cultivos comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Huertos_Urbanos_API',
                    'icon' => 'eco',
                    'color' => '#2E7D32',
                ],
                'bicicletas_compartidas' => [
                    'name' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Sistema de bicicletas comunitarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Bicicletas_Compartidas_API',
                    'icon' => 'pedal_bike',
                    'color' => '#388E3C',
                ],
                'compostaje' => [
                    'name' => __('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Compostaje comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Compostaje_Module',
                    'icon' => 'recycling',
                    'color' => '#7CB342',
                ],
                'energia_comunitaria' => [
                    'name' => __('Energia Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Comunidades energéticas, instalaciones, reparto y liquidaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Chat_Energia_Comunitaria_Module',
                    'icon' => 'bolt',
                    'color' => '#F59E0B',
                ],
                'reciclaje' => [
                    'name' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Gestión de reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Reciclaje_API',
                    'icon' => 'recycling',
                    'color' => '#009688',
                ],
                'carpooling' => [
                    'name' => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Viajes compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Carpooling_Module',
                    'icon' => 'directions_car',
                    'color' => '#3F51B5',
                ],
                'cursos' => [
                    'name' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Plataforma de cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Cursos_Module',
                    'icon' => 'menu_book',
                    'color' => '#5C6BC0',
                ],
                'podcast' => [
                    'name' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Podcast comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Podcast_Module',
                    'icon' => 'mic',
                    'color' => '#6A1B9A',
                ],
                'radio' => [
                    'name' => __('Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Radio comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Radio_Module',
                    'icon' => 'radio',
                    'color' => '#8E24AA',
                ],
                'multimedia' => [
                    'name' => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Galería y contenidos multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Multimedia_Module',
                    'icon' => 'perm_media',
                    'color' => '#5D4037',
                ],
                'biblioteca' => [
                    'name' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Biblioteca comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Biblioteca_Module',
                    'icon' => 'local_library',
                    'color' => '#455A64',
                ],
                'talleres' => [
                    'name' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Talleres y workshops', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Talleres_Module',
                    'icon' => 'build',
                    'color' => '#6D4C41',
                ],
                'incidencias' => [
                    'name' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Incidencias urbanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Incidencias_Module',
                    'icon' => 'report_problem',
                    'color' => '#E64A19',
                ],
                'espacios_comunes' => [
                    'name' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Reservas de espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Espacios_Comunes_Module',
                    'icon' => 'meeting_room',
                    'color' => '#546E7A',
                ],
                'parkings' => [
                    'name' => __('Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Parkings comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Parkings_API',
                    'icon' => 'local_parking',
                    'color' => '#455A64',
                ],
                'ayuda_vecinal' => [
                    'name' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Red de ayuda mutua', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Ayuda_Vecinal_API',
                    'icon' => 'volunteer_activism',
                    'color' => '#8BC34A',
                ],
                'empresarial' => [
                    'name' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Componentes profesionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Empresarial_Module',
                    'icon' => 'business',
                    'color' => '#37474F',
                ],
                'clientes' => [
                    'name' => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('CRM básico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Clientes_Module',
                    'icon' => 'person',
                    'color' => '#3F51B5',
                ],
                'bares' => [
                    'name' => __('Bares y Hostelería', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'description' => __('Directorio de bares', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => 'Flavor_Bares_Module',
                    'icon' => 'restaurant',
                    'color' => '#FF7043',
                ],
            ];

            $registered_modules = [];
            if (class_exists('Flavor_Chat_Module_Loader')) {
                $loader = Flavor_Chat_Module_Loader::get_instance();
                $registered_modules = $loader->get_registered_modules();
            }

            // Merge: ensure all registered modules appear in the UI
            foreach ($registered_modules as $module_id => $module_data) {
                if (isset($known_modules[$module_id])) {
                    if (empty($known_modules[$module_id]['name']) && !empty($module_data['name'])) {
                        $known_modules[$module_id]['name'] = $module_data['name'];
                    }
                    if (empty($known_modules[$module_id]['description']) && !empty($module_data['description'])) {
                        $known_modules[$module_id]['description'] = $module_data['description'];
                    }
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', $module_id));
                $known_modules[$module_id] = [
                    'name' => !empty($module_data['name']) ? $module_data['name'] : $label,
                    'description' => !empty($module_data['description'])
                        ? $module_data['description']
                        : __('Módulo disponible para la app', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'api_class' => '',
                    'icon' => 'extension',
                    'color' => '#607D8B',
                ];
            }

            $modules_enriched = [];
            foreach ($known_modules as $module_id => $module_data) {
                if (empty($module_data['name'])) {
                    $module_data['name'] = ucwords(str_replace(['_', '-'], ' ', $module_id));
                }
                if (!isset($module_data['description']) || $module_data['description'] === '') {
                    $module_data['description'] = __('Módulo disponible para la app', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
                $is_installed = isset($registered_modules[$module_id]);
                $is_active = in_array($module_id, $active_modules, true);
                $api_available = !empty($module_data['api_class'])
                    ? (class_exists($module_data['api_class']) || $is_installed)
                    : $is_installed;
                if (isset($enabled_modules[$module_id]['enabled'])) {
                    $is_enabled = (bool) $enabled_modules[$module_id]['enabled'];
                } else {
                    $is_enabled = $is_active;
                }
                $category = !empty($module_data['category']) ? (string) $module_data['category'] : $this->resolve_module_category($module_id);
                if (!isset($category_labels[$category])) {
                    $category = 'otros';
                }

                $related = $recommendations_map[$module_id] ?? [];
                $related = array_values(array_filter(array_map('sanitize_key', (array) $related), function ($related_id) use ($known_modules) {
                    return isset($known_modules[$related_id]);
                }));

                $modules_enriched[$module_id] = [
                    'id' => $module_id,
                    'name' => $module_data['name'],
                    'description' => $module_data['description'],
                    'icon' => $module_data['icon'],
                    'color' => $module_data['color'],
                    'category' => $category,
                    'related' => $related,
                    'is_installed' => $is_installed,
                    'is_active' => $is_active,
                    'is_enabled' => $is_enabled,
                    'api_available' => $api_available,
                ];
            }

            $grouped_modules = [];
            foreach ($modules_enriched as $module_id => $module_data) {
                $cat = $module_data['category'];
                if (!isset($grouped_modules[$cat])) {
                    $grouped_modules[$cat] = [];
                }
                $grouped_modules[$cat][$module_id] = $module_data;
            }

            foreach ($grouped_modules as $cat => $modules_cat) {
                uasort($modules_cat, function ($a, $b) {
                    return strcasecmp((string) $a['name'], (string) $b['name']);
                });
                $grouped_modules[$cat] = $modules_cat;
            }

            $total_modules = count($modules_enriched);
            $total_enabled = count(array_filter($modules_enriched, function ($module_data) {
                return !empty($module_data['is_enabled']);
            }));
        ?>

        <div class="flavor-modules-ux-header">
            <div class="flavor-modules-ux-kpis">
                <span class="flavor-modules-kpi"><?php echo esc_html(sprintf(__('Total: %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_modules)); ?></span>
                <span class="flavor-modules-kpi"><?php echo esc_html(sprintf(__('Visibles en app: %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_enabled)); ?></span>
            </div>
            <div class="flavor-modules-ux-search">
                <label for="flavor-module-search" class="screen-reader-text"><?php _e('Buscar módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="search" id="flavor-module-search" placeholder="<?php esc_attr_e('Buscar módulo por nombre o descripción…', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
        </div>

        <div class="flavor-modules-category-filters">
            <button type="button" class="button button-secondary is-active" data-module-category-filter="all">
                <?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php foreach ($category_order as $category_key): ?>
                <?php if (empty($grouped_modules[$category_key])) { continue; } ?>
                <button type="button" class="button button-secondary" data-module-category-filter="<?php echo esc_attr($category_key); ?>">
                    <?php
                    echo esc_html($category_labels[$category_key]);
                    echo ' (' . esc_html((string) count($grouped_modules[$category_key])) . ')';
                    ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div id="flavor-module-recommendations-panel" class="flavor-module-recommendations-panel" style="display:none;"></div>

        <?php foreach ($category_order as $category_key): ?>
            <?php if (empty($grouped_modules[$category_key])) { continue; } ?>
            <section class="flavor-module-category-block" data-module-category-block="<?php echo esc_attr($category_key); ?>">
                <header class="flavor-module-category-header">
                    <h3><?php echo esc_html($category_labels[$category_key]); ?></h3>
                    <span class="flavor-module-category-count"><?php echo esc_html(sprintf(_n('%d módulo', '%d módulos', count($grouped_modules[$category_key]), FLAVOR_PLATFORM_TEXT_DOMAIN), count($grouped_modules[$category_key]))); ?></span>
                </header>

                <div class="flavor-modules-grid flavor-modules-grid--categorized">
                    <?php foreach ($grouped_modules[$category_key] as $module_data): ?>
                        <div class="flavor-module-card <?php echo !$module_data['is_enabled'] ? 'module-disabled' : ''; ?>"
                             data-module-id="<?php echo esc_attr($module_data['id']); ?>"
                             data-module-name="<?php echo esc_attr($module_data['name']); ?>"
                             data-module-category="<?php echo esc_attr($category_key); ?>"
                             data-related-modules="<?php echo esc_attr(implode(',', $module_data['related'])); ?>">
                            <div class="flavor-module-card-header">
                                <div class="flavor-module-icon-wrapper" style="background-color: <?php echo esc_attr($module_data['color']); ?>;">
                                    <span class="material-icons"><?php echo esc_html($module_data['icon']); ?></span>
                                </div>
                                <label class="flavor-toggle-switch">
                                    <input type="hidden" name="flavor_apps_config[modules][<?php echo esc_attr($module_data['id']); ?>][enabled]" value="0">
                                    <input type="checkbox"
                                           name="flavor_apps_config[modules][<?php echo esc_attr($module_data['id']); ?>][enabled]"
                                           value="1"
                                           class="flavor-module-toggle"
                                           <?php checked($module_data['is_enabled']); ?>>
                                    <span class="flavor-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="flavor-module-card-body">
                                <h4><?php echo esc_html($module_data['name']); ?></h4>
                                <p><?php echo esc_html($module_data['description']); ?></p>
                                <?php if (!empty($module_data['related'])): ?>
                                    <p class="flavor-module-related-hint">
                                        <?php _e('Complementarios:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <?php
                                        $related_labels = [];
                                        foreach ($module_data['related'] as $related_id) {
                                            if (!empty($modules_enriched[$related_id]['name'])) {
                                                $related_labels[] = $modules_enriched[$related_id]['name'];
                                            }
                                        }
                                        echo esc_html(implode(', ', $related_labels));
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flavor-module-api-status <?php echo $module_data['api_available'] ? 'available' : 'unavailable'; ?>">
                                    <?php if ($module_data['api_available'] && $module_data['is_active']): ?>
                                        <span class="dashicons dashicons-yes-alt"></span> <?php _e('API disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php elseif ($module_data['api_available'] && !$module_data['is_active']): ?>
                                        <span class="dashicons dashicons-warning"></span> <?php _e('Disponible (no activo)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-marker"></span> <?php _e('No instalado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="flavor-module-actions">
                                    <?php if ($module_data['is_installed']): ?>
                                        <button type="button"
                                                class="button button-secondary flavor-module-activate-btn"
                                                data-module-id="<?php echo esc_attr($module_data['id']); ?>"
                                                data-active="<?php echo $module_data['is_active'] ? '1' : '0'; ?>">
                                            <?php echo $module_data['is_active'] ? esc_html__('Desactivar módulo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Activar módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="description"><?php _e('Este módulo no está instalado en el plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="button flavor-module-docs-btn"
                                            data-module-id="<?php echo esc_attr($module_data['id']); ?>"
                                            data-module-name="<?php echo esc_attr($module_data['name']); ?>"
                                            title="<?php esc_attr_e('Ver documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-info-outline"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <!-- Modal de Documentación -->
        <div id="flavor-module-docs-modal" class="flavor-docs-modal" style="display: none;">
            <div class="flavor-docs-modal-backdrop"></div>
            <div class="flavor-docs-modal-content">
                <div class="flavor-docs-modal-header">
                    <h3 id="flavor-docs-modal-title"><?php _e('Documentación del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <button type="button" class="flavor-docs-modal-close" aria-label="<?php esc_attr_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="flavor-docs-modal-body">
                    <div id="flavor-docs-loading" class="flavor-docs-loading">
                        <span class="dashicons dashicons-update flavor-docs-spinner"></span>
                        <span><?php _e('Cargando documentación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div id="flavor-docs-content" class="flavor-docs-content" style="display: none;">
                        <!-- Descripción -->
                        <div class="flavor-docs-section">
                            <h4><span class="dashicons dashicons-info"></span> <?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="flavor-docs-description"></p>
                        </div>
                        <!-- Características -->
                        <div class="flavor-docs-section" id="flavor-docs-features-section" style="display: none;">
                            <h4><span class="dashicons dashicons-yes"></span> <?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul id="flavor-docs-features"></ul>
                        </div>
                        <!-- Casos de uso -->
                        <div class="flavor-docs-section" id="flavor-docs-usecases-section" style="display: none;">
                            <h4><span class="dashicons dashicons-lightbulb"></span> <?php _e('Casos de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul id="flavor-docs-usecases"></ul>
                        </div>
                        <!-- Módulos relacionados -->
                        <div class="flavor-docs-section" id="flavor-docs-related-section" style="display: none;">
                            <h4><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Módulos Relacionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <div id="flavor-docs-related"></div>
                        </div>
                        <!-- Requisitos -->
                        <div class="flavor-docs-section" id="flavor-docs-requirements-section" style="display: none;">
                            <h4><span class="dashicons dashicons-warning"></span> <?php _e('Requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul id="flavor-docs-requirements"></ul>
                        </div>
                        <!-- Tablas de BD -->
                        <div class="flavor-docs-section" id="flavor-docs-tables-section" style="display: none;">
                            <h4><span class="dashicons dashicons-database"></span> <?php _e('Tablas de Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul id="flavor-docs-tables"></ul>
                        </div>
                    </div>
                    <div id="flavor-docs-error" class="flavor-docs-error" style="display: none;">
                        <span class="dashicons dashicons-warning"></span>
                        <span id="flavor-docs-error-message"><?php _e('No se pudo cargar la documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var $modal = $('#flavor-module-docs-modal');
            var $loading = $('#flavor-docs-loading');
            var $content = $('#flavor-docs-content');
            var $error = $('#flavor-docs-error');
            var $recommendationsPanel = $('#flavor-module-recommendations-panel');

            function normalizeText(value) {
                return String(value || '').toLowerCase();
            }

            function filterModuleCards() {
                var search = normalizeText($('#flavor-module-search').val());
                var category = $('.flavor-modules-category-filters [data-module-category-filter].is-active').data('module-category-filter') || 'all';
                var visibleCountByCategory = {};

                $('.flavor-module-card').each(function() {
                    var $card = $(this);
                    var cardCategory = String($card.data('module-category') || '');
                    var cardName = normalizeText($card.data('module-name'));
                    var cardDescription = normalizeText($card.find('.flavor-module-card-body p').first().text());
                    var matchSearch = !search || cardName.indexOf(search) !== -1 || cardDescription.indexOf(search) !== -1;
                    var matchCategory = (category === 'all') || (category === cardCategory);
                    var visible = matchSearch && matchCategory;

                    $card.toggle(visible);

                    if (visible) {
                        visibleCountByCategory[cardCategory] = (visibleCountByCategory[cardCategory] || 0) + 1;
                    }
                });

                $('.flavor-module-category-block').each(function() {
                    var $block = $(this);
                    var blockCategory = String($block.data('module-category-block') || '');
                    $block.toggle((visibleCountByCategory[blockCategory] || 0) > 0);
                });
            }

            function buildRecommendationItems(relatedIds) {
                var items = [];

                relatedIds.forEach(function(relatedId) {
                    var $relatedCard = $('.flavor-module-card[data-module-id="' + relatedId + '"]').first();
                    if (!$relatedCard.length) {
                        return;
                    }

                    var $relatedBtn = $relatedCard.find('.flavor-module-activate-btn').first();
                    if (!$relatedBtn.length) {
                        return;
                    }

                    var isAlreadyActive = String($relatedBtn.data('active')) === '1';
                    if (isAlreadyActive) {
                        return;
                    }

                    var relatedName = $relatedCard.data('module-name') || relatedId;
                    items.push(
                        '<button type="button" class="button button-secondary flavor-module-quick-activate" data-module-id="' + relatedId + '">' +
                            '<?php echo esc_js(__('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> ' + $('<div>').text(relatedName).html() +
                        '</button>'
                    );
                });

                return items;
            }

            function showModuleRecommendations(moduleId, moduleName) {
                var $sourceCard = $('.flavor-module-card[data-module-id="' + moduleId + '"]').first();
                if (!$sourceCard.length) {
                    $recommendationsPanel.hide().empty();
                    return;
                }

                var relatedRaw = String($sourceCard.data('related-modules') || '');
                var relatedIds = relatedRaw ? relatedRaw.split(',').map(function(v) { return String(v).trim(); }).filter(Boolean) : [];
                if (!relatedIds.length) {
                    $recommendationsPanel.hide().empty();
                    return;
                }

                var items = buildRecommendationItems(relatedIds);
                if (!items.length) {
                    $recommendationsPanel.hide().empty();
                    return;
                }

                var title = '<?php echo esc_js(__('Módulos complementarios recomendados tras activar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                var safeName = $('<div>').text(moduleName || moduleId).html();

                $recommendationsPanel.html(
                    '<div class="flavor-module-recommendations-title"><span class="dashicons dashicons-lightbulb"></span> ' + title + ': <strong>' + safeName + '</strong></div>' +
                    '<div class="flavor-module-recommendations-actions">' + items.join('') + '</div>'
                ).show();
            }

            // Abrir modal
            $('.flavor-module-docs-btn').on('click', function(e) {
                e.preventDefault();
                var moduleId = $(this).data('module-id');
                var moduleName = $(this).data('module-name');

                $('#flavor-docs-modal-title').text(moduleName + ' - <?php _e('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                $modal.show();
                $loading.show();
                $content.hide();
                $error.hide();

                // Cargar documentación via REST API
                $.ajax({
                    url: '<?php echo rest_url('flavor/v1/modules/docs/'); ?>' + moduleId,
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function(response) {
                        $loading.hide();

                        // Verificar si la API devolvió success: false
                        if (response.success === false) {
                            $('#flavor-docs-error-message').text(response.error || '<?php _e('Documentación no disponible para este módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                            $error.show();
                            return;
                        }

                        // La API devuelve {success: true, data: {...}}
                        var data = response.data || response;

                        if (data && (data.descripcion || data.description)) {
                            // Descripción
                            $('#flavor-docs-description').text(data.descripcion || data.description || '');

                            // Características
                            var features = data.caracteristicas || data.features || [];
                            if (features.length > 0) {
                                var $featuresList = $('#flavor-docs-features').empty();
                                features.forEach(function(f) { $featuresList.append('<li>' + f + '</li>'); });
                                $('#flavor-docs-features-section').show();
                            } else {
                                $('#flavor-docs-features-section').hide();
                            }

                            // Casos de uso
                            var usecases = data.casos_uso || data.use_cases || [];
                            if (usecases.length > 0) {
                                var $usecasesList = $('#flavor-docs-usecases').empty();
                                usecases.forEach(function(u) { $usecasesList.append('<li>' + u + '</li>'); });
                                $('#flavor-docs-usecases-section').show();
                            } else {
                                $('#flavor-docs-usecases-section').hide();
                            }

                            // Módulos relacionados
                            var related = data.modulos_relacionados || data.related_modules || [];
                            if (related.length > 0) {
                                var $related = $('#flavor-docs-related').empty();
                                related.forEach(function(r) { $related.append('<span class="related-module">' + r + '</span>'); });
                                $('#flavor-docs-related-section').show();
                            } else {
                                $('#flavor-docs-related-section').hide();
                            }

                            // Requisitos
                            var requirements = data.requisitos || data.requirements || [];
                            if (requirements.length > 0) {
                                var $reqList = $('#flavor-docs-requirements').empty();
                                requirements.forEach(function(r) { $reqList.append('<li>' + r + '</li>'); });
                                $('#flavor-docs-requirements-section').show();
                            } else {
                                $('#flavor-docs-requirements-section').hide();
                            }

                            // Tablas
                            var tables = data.tablas || data.tables || [];
                            if (tables.length > 0) {
                                var $tablesList = $('#flavor-docs-tables').empty();
                                tables.forEach(function(t) { $tablesList.append('<li><code>' + t + '</code></li>'); });
                                $('#flavor-docs-tables-section').show();
                            } else {
                                $('#flavor-docs-tables-section').hide();
                            }

                            $content.show();
                        } else {
                            $('#flavor-docs-error-message').text('<?php _e('Documentación no disponible para este módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                            $error.show();
                        }
                    },
                    error: function(xhr) {
                        $loading.hide();
                        var msg = '<?php echo esc_js(__('Error al cargar la documentación', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                        if (xhr && xhr.responseJSON) {
                            if (xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            } else if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                        }
                        if (xhr && xhr.status === 404) {
                            msg = '<?php echo esc_js(__('Documentación no disponible para este módulo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                        }
                        $('#flavor-docs-error-message').text(msg);
                        $error.show();
                    }
                });
            });

            // Cerrar modal
            $('.flavor-docs-modal-close, .flavor-docs-modal-backdrop').on('click', function() {
                $modal.hide();
            });

            // Cerrar con ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $modal.is(':visible')) {
                    $modal.hide();
                }
            });

            // Filtros por categoría
            $('.flavor-modules-category-filters').on('click', '[data-module-category-filter]', function() {
                $('.flavor-modules-category-filters [data-module-category-filter]').removeClass('is-active');
                $(this).addClass('is-active');
                filterModuleCards();
            });

            // Búsqueda en tiempo real
            $('#flavor-module-search').on('input', filterModuleCards);

            // Activar/Desactivar módulo via AJAX
            $('.flavor-module-activate-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var moduleId = $btn.data('module-id');
                var isActive = $btn.data('active') === 1 || $btn.data('active') === '1';
                var newState = !isActive;

                $btn.prop('disabled', true).text('<?php echo esc_js(__('Procesando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flavor_toggle_module_activation',
                        module_id: moduleId,
                        activate: newState ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('flavor_apps_config'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.data('active', newState ? '1' : '0');
                            $btn.text(newState ? '<?php echo esc_js(__('Desactivar módulo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>' : '<?php echo esc_js(__('Activar módulo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');

                            // Actualizar estado visual del API
                            var $card = $btn.closest('.flavor-module-card');
                            var $status = $card.find('.flavor-module-api-status');
                            if (newState) {
                                $status.removeClass('unavailable').addClass('available');
                                $status.html('<span class="dashicons dashicons-yes-alt"></span> <?php echo esc_js(__('API disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            } else {
                                $status.html('<span class="dashicons dashicons-warning"></span> <?php echo esc_js(__('Disponible (no activo)', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            }

                            // Sincronizar el toggle switch
                            var $toggle = $card.find('.flavor-module-toggle');
                            $toggle.prop('checked', newState);

                            var moduleName = String($card.data('module-name') || moduleId);
                            if (newState) {
                                showModuleRecommendations(moduleId, moduleName);
                            } else {
                                $recommendationsPanel.hide().empty();
                            }
                        } else {
                            alert(response.data?.message || '<?php echo esc_js(__('Error al cambiar estado del módulo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Sincronizar toggle switch con el botón
            $('.flavor-module-toggle').on('change', function() {
                var $toggle = $(this);
                var $card = $toggle.closest('.flavor-module-card');
                var $btn = $card.find('.flavor-module-activate-btn');

                if ($btn.length) {
                    $btn.trigger('click');
                    // Revertir el toggle hasta que el AJAX confirme
                    $toggle.prop('checked', !$toggle.prop('checked'));
                }
            });

            // Activación rápida desde recomendaciones
            $(document).on('click', '.flavor-module-quick-activate', function(e) {
                e.preventDefault();
                var targetModuleId = String($(this).data('module-id') || '');
                if (!targetModuleId) {
                    return;
                }
                var $targetCard = $('.flavor-module-card[data-module-id="' + targetModuleId + '"]').first();
                var $targetBtn = $targetCard.find('.flavor-module-activate-btn').first();
                if ($targetBtn.length) {
                    $targetBtn.trigger('click');
                }
            });

            // Botón de sincronización masiva con Web
            $('#sync-modules-from-web').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var originalHtml = $btn.html();

                $btn.prop('disabled', true).html('<span class="material-icons" style="font-size: 18px; animation: spin 1s linear infinite;">sync</span> <?php echo esc_js(__('Sincronizando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flavor_sync_modules_from_web',
                        nonce: '<?php echo wp_create_nonce('flavor_apps_config'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            // Recargar página para mostrar cambios
                            if (response.data.sincronizados && response.data.sincronizados.length > 0) {
                                location.reload();
                            }
                        } else {
                            alert(response.data?.message || '<?php echo esc_js(__('Error al sincronizar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            filterModuleCards();
        });

        // Keyframe para animación de sincronización
        var style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
        </script>

        <?php submit_button(); ?>
        <?php
    }

    /**
     * AJAX: Generar token
     */
    public function ajax_generate_token() {
        if (!check_ajax_referer('flavor_apps_config', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nonce inválido o expirado. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

        if (empty($name)) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Generar token único
        $token = wp_generate_password(40, false);
        $token_id = wp_hash($token);

        // Guardar token
        $tokens = get_option('flavor_apps_tokens', []);
        if (!is_array($tokens)) {
            $tokens = [];
        }
        $tokens[$token_id] = [
            'name' => $name,
            'created' => time(),
            'last_used' => null,
        ];
        update_option('flavor_apps_tokens', $tokens);

        wp_send_json_success([
            'token' => $token,
            'token_id' => $token_id,
        ]);
    }

    /**
     * AJAX: Revocar token
     */
    public function ajax_revoke_token() {
        if (!check_ajax_referer('flavor_apps_config', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nonce inválido o expirado. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        $token_id = isset($_POST['token_id']) ? sanitize_text_field($_POST['token_id']) : '';

        if (empty($token_id)) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $tokens = get_option('flavor_apps_tokens', []);
        if (!is_array($tokens)) {
            $tokens = [];
        }
        if (isset($tokens[$token_id])) {
            unset($tokens[$token_id]);
            update_option('flavor_apps_tokens', $tokens);
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Obtener items de menú para secciones web
     */
    public function ajax_get_menu_items() {
        if (!check_ajax_referer('flavor_apps_config', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nonce inválido o expirado. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        $menu_source = isset($_POST['menu_source']) ? sanitize_text_field($_POST['menu_source']) : '';
        $items = $this->get_menu_items_payload($menu_source);

        wp_send_json_success(['items' => $items]);
    }

    /**
     * AJAX: Activar o desactivar módulo del plugin
     */
    public function ajax_toggle_module_activation() {
        if (!check_ajax_referer('flavor_apps_config', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nonce inválido o expirado. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
        }

        $module_id = isset($_POST['module_id']) ? sanitize_key($_POST['module_id']) : '';
        $activate = isset($_POST['activate']) ? (bool) intval($_POST['activate']) : false;

        if (!$module_id) {
            wp_send_json_error(['message' => __('Módulo inválido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $registered_modules = [];
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $registered_modules = $loader->get_registered_modules();
        }

        if (!isset($registered_modules[$module_id])) {
            wp_send_json_error(['message' => __('Módulo no instalado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = isset($settings['active_modules']) && is_array($settings['active_modules'])
            ? $settings['active_modules']
            : [];

        if ($activate) {
            if (!in_array($module_id, $active_modules, true)) {
                $active_modules[] = $module_id;
            }
        } else {
            $active_modules = array_values(array_diff($active_modules, [$module_id]));
        }

        $settings['active_modules'] = $active_modules;
        update_option('flavor_chat_ia_settings', $settings);
        // Eliminado: update_option('flavor_active_modules') - fuente única de verdad

        $apps_config = get_option('flavor_apps_config', []);
        if (!isset($apps_config['modules']) || !is_array($apps_config['modules'])) {
            $apps_config['modules'] = [];
        }
        if (!isset($apps_config['modules'][$module_id])) {
            $apps_config['modules'][$module_id] = [];
        }
        $apps_config['modules'][$module_id]['enabled'] = $activate ? 1 : 0;
        update_option('flavor_apps_config', $apps_config);

        // Invalidar caché de API cuando cambian los módulos
        delete_transient('flavor_api_system_info');
        delete_transient('flavor_api_available_modules');

        wp_send_json_success([
            'module_id' => $module_id,
            'active' => $activate,
        ]);
    }

    /**
     * Sanitiza la configuración
     */
    public function sanitize_config($input) {
        // Get existing config to merge with (avoid losing data between tabs)
        $existing_config = get_option('flavor_apps_config', []);
        $sanitized = $existing_config;

        if (isset($input['app_name'])) {
            $sanitized['app_name'] = sanitize_text_field($input['app_name']);
        }

        if (isset($input['app_description'])) {
            $sanitized['app_description'] = sanitize_textarea_field($input['app_description']);
        }

        if (isset($input['app_logo'])) {
            $sanitized['app_logo'] = absint($input['app_logo']);
        }

        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }

        if (isset($input['secondary_color'])) {
            $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']);
        }

        if (isset($input['accent_color'])) {
            $sanitized['accent_color'] = sanitize_hex_color($input['accent_color']);
        }

        if (isset($input['navigation_style'])) {
            $navigation_style = sanitize_key($input['navigation_style']);
            if (!in_array($navigation_style, ['auto', 'bottom', 'hamburger', 'hybrid'], true)) {
                $navigation_style = 'auto';
            }
            $sanitized['navigation_style'] = $navigation_style;
        }

        if (isset($input['hybrid_show_appbar'])) {
            $sanitized['hybrid_show_appbar'] = !empty($input['hybrid_show_appbar']);
        }

        if (isset($input['map_provider'])) {
            $map_provider = sanitize_key($input['map_provider']);
            if (!in_array($map_provider, ['osm', 'google'], true)) {
                $map_provider = 'osm';
            }
            $sanitized['map_provider'] = $map_provider;
        }

        if (isset($input['google_maps_api_key'])) {
            $sanitized['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key']);
        }

        if (isset($input['web_sections_menu'])) {
            $menu_source = sanitize_text_field($input['web_sections_menu']);
            if (
                $menu_source !== '' &&
                !str_starts_with($menu_source, 'location:') &&
                !str_starts_with($menu_source, 'menu:')
            ) {
                $menu_source = '';
            }
            $sanitized['web_sections_menu'] = $menu_source;
        }

        if (isset($input['background_color'])) {
            $sanitized['background_color'] = sanitize_hex_color($input['background_color']);
        }

        if (isset($input['surface_color'])) {
            $sanitized['surface_color'] = sanitize_hex_color($input['surface_color']);
        }

        if (isset($input['text_primary_color'])) {
            $sanitized['text_primary_color'] = sanitize_hex_color($input['text_primary_color']);
        }

        if (isset($input['text_secondary_color'])) {
            $sanitized['text_secondary_color'] = sanitize_hex_color($input['text_secondary_color']);
        }

        // Tabs de navegación (todo renderizado nativo)
        if (isset($input['tabs']) && is_array($input['tabs'])) {
            $sanitized_tabs = [];
            foreach ($input['tabs'] as $tab_index => $tab_data) {
                // Tipo de contenido: native_screen, page, cpt, module
                $content_type = sanitize_key($tab_data['content_type'] ?? 'native_screen');
                if (!in_array($content_type, ['native_screen', 'page', 'cpt', 'module'], true)) {
                    $content_type = 'native_screen';
                }

                // Determinar la referencia al contenido según el tipo
                $content_ref = '';
                switch ($content_type) {
                    case 'native_screen':
                        $content_ref = sanitize_key($tab_data['content_ref'] ?? $tab_data['id'] ?? 'info');
                        break;
                    case 'page':
                        $content_ref = sanitize_title($tab_data['content_ref_page'] ?? '');
                        break;
                    case 'cpt':
                        $content_ref = sanitize_key($tab_data['content_ref_cpt'] ?? '');
                        break;
                    case 'module':
                        $content_ref = sanitize_key($tab_data['content_ref_module'] ?? '');
                        break;
                }

                // Generar ID único si no existe
                $tab_id = sanitize_key($tab_data['id'] ?? '');
                if (empty($tab_id)) {
                    $tab_id = $content_type . '_' . ($content_ref ?: uniqid());
                }

                // Construir endpoint de API para el contenido (renderizado nativo)
                $api_endpoint = '';
                switch ($content_type) {
                    case 'native_screen':
                        $api_endpoint = rest_url('native-content/v1/screen/' . $content_ref);
                        break;
                    case 'page':
                        $api_endpoint = rest_url('native-content/v1/content/page/' . $content_ref);
                        break;
                    case 'cpt':
                        $api_endpoint = rest_url('native-content/v1/content/list/' . $content_ref);
                        break;
                    case 'module':
                        $api_endpoint = rest_url('native-content/v1/module/' . $content_ref);
                        break;
                }

                $sanitized_tabs[] = [
                    'id' => $tab_id,
                    'label' => sanitize_text_field($tab_data['label'] ?? ''),
                    'icon' => sanitize_text_field($tab_data['icon'] ?? 'circle'),
                    'content_type' => $content_type,
                    'content_ref' => $content_ref,
                    'api_endpoint' => $api_endpoint,
                    'enabled' => !empty($tab_data['enabled']),
                    'order' => absint($tab_data['order'] ?? $tab_index),
                    // Mantener compatibilidad con apps antiguas
                    'type' => 'native',
                ];
            }
            $sanitized['tabs'] = $sanitized_tabs;
        }

        if (isset($input['drawer_items']) && is_array($input['drawer_items'])) {
            $sanitized_drawer = [];
            foreach ($input['drawer_items'] as $drawer_index => $drawer_item) {
                $url = esc_url_raw($drawer_item['url'] ?? '');
                if (!$url) continue;

                // Tipo de contenido: native_screen, page, cpt, module
                $content_type = sanitize_key($drawer_item['content_type'] ?? 'page');
                if (!in_array($content_type, ['native_screen', 'page', 'cpt', 'module'], true)) {
                    $content_type = 'page';
                }

                // Determinar la referencia al contenido según el tipo
                $content_ref = '';
                switch ($content_type) {
                    case 'native_screen':
                        $content_ref = sanitize_key($drawer_item['content_ref'] ?? 'info');
                        break;
                    case 'page':
                        $content_ref = sanitize_title($drawer_item['content_ref_page'] ?? '');
                        break;
                    case 'cpt':
                        $content_ref = sanitize_key($drawer_item['content_ref_cpt'] ?? '');
                        break;
                    case 'module':
                        $content_ref = sanitize_key($drawer_item['content_ref_module'] ?? '');
                        break;
                }

                // Construir endpoint de API para el contenido (renderizado nativo)
                $api_endpoint = '';
                switch ($content_type) {
                    case 'native_screen':
                        $api_endpoint = rest_url('native-content/v1/screen/' . $content_ref);
                        break;
                    case 'page':
                        $api_endpoint = rest_url('native-content/v1/content/page/' . $content_ref);
                        break;
                    case 'cpt':
                        $api_endpoint = rest_url('native-content/v1/content/list/' . $content_ref);
                        break;
                    case 'module':
                        $api_endpoint = rest_url('native-content/v1/module/' . $content_ref);
                        break;
                }

                $drawer_order = isset($drawer_item['order']) ? absint($drawer_item['order']) : $drawer_index;

                $sanitized_drawer[] = [
                    'title' => sanitize_text_field($drawer_item['title'] ?? ''),
                    'url' => $url, // Mantener URL original como referencia
                    'icon' => sanitize_text_field($drawer_item['icon'] ?? 'public'),
                    'content_type' => $content_type,
                    'content_ref' => $content_ref,
                    'api_endpoint' => $api_endpoint,
                    'order' => $drawer_order,
                    'enabled' => !empty($drawer_item['enabled']),
                ];
            }
            $sanitized['drawer_items'] = $sanitized_drawer;
        }

        if (isset($input['default_tab'])) {
            $sanitized['default_tab'] = sanitize_key($input['default_tab']);
        }

        // Módulos
        if (isset($input['modules']) && is_array($input['modules'])) {
            $sanitized_modules = [];
            foreach ($input['modules'] as $module_id => $module_config) {
                $sanitized_modules[sanitize_key($module_id)] = [
                    'enabled' => !empty($module_config['enabled']),
                ];
            }
            $sanitized['modules'] = $sanitized_modules;
        }

        // Info sections
        if (isset($input['info_sections']) && is_array($input['info_sections'])) {
            $sanitized_sections = [];
            foreach ($input['info_sections'] as $section_id => $section_data) {
                $sanitized_sections[sanitize_key($section_id)] = [
                    'label' => sanitize_text_field($section_data['label'] ?? $section_id),
                    'enabled' => !empty($section_data['enabled']),
                    'order' => absint($section_data['order'] ?? 0),
                ];
            }
            $sanitized['info_sections'] = $sanitized_sections;
        }

        // Directory fields
        if (isset($input['public_in_directory'])) {
            $sanitized['public_in_directory'] = !empty($input['public_in_directory']);
        }
        if (isset($input['business_region'])) {
            $sanitized['business_region'] = sanitize_text_field($input['business_region']);
        }
        if (isset($input['business_category'])) {
            $sanitized['business_category'] = sanitize_text_field($input['business_category']);
        }
        if (isset($input['business_address'])) {
            $sanitized['business_address'] = sanitize_text_field($input['business_address']);
        }
        if (isset($input['business_city'])) {
            $sanitized['business_city'] = sanitize_text_field($input['business_city']);
        }
        if (isset($input['business_country'])) {
            $sanitized['business_country'] = sanitize_text_field($input['business_country']);
        }
        if (isset($input['business_postal_code'])) {
            $sanitized['business_postal_code'] = sanitize_text_field($input['business_postal_code']);
        }
        if (isset($input['business_lat']) && $input['business_lat'] !== '') {
            $sanitized['business_lat'] = floatval($input['business_lat']);
        }
        if (isset($input['business_lng']) && $input['business_lng'] !== '') {
            $sanitized['business_lng'] = floatval($input['business_lng']);
        }

        $direccion_parts = array_filter([
            $sanitized['business_address'] ?? '',
            $sanitized['business_city'] ?? '',
            $sanitized['business_country'] ?? '',
        ]);
        $direccion_completa = implode(', ', $direccion_parts);

        if (
            $direccion_completa &&
            (empty($sanitized['business_lat']) || empty($sanitized['business_lng']))
        ) {
            $coords = $this->geocode_address($direccion_completa);
            if ($coords) {
                $sanitized['business_lat'] = $coords['lat'];
                $sanitized['business_lng'] = $coords['lng'];
            }
        }

        if (class_exists('Flavor_Network_Node')) {
            $node_data = [
                'site_url'      => get_site_url(),
                'direccion'     => $sanitized['business_address'] ?? '',
                'ciudad'        => $sanitized['business_city'] ?? '',
                'pais'          => $sanitized['business_country'] ?? '',
                'codigo_postal' => $sanitized['business_postal_code'] ?? '',
            ];

            if (isset($sanitized['business_lat'])) {
                $node_data['latitud'] = $sanitized['business_lat'];
            }
            if (isset($sanitized['business_lng'])) {
                $node_data['longitud'] = $sanitized['business_lng'];
            }

            if (!empty($sanitized['business_category'])) {
                $node_data['tipo_entidad'] = $sanitized['business_category'];
            }

            $local_node = Flavor_Network_Node::get_local_node();
            if (!$local_node) {
                $nombre = $sanitized['app_name'] ?? get_bloginfo('name');
                $node_data['nombre'] = $nombre;
                $node_data['slug'] = sanitize_title($nombre);
                $node_data['descripcion_corta'] = $sanitized['app_description'] ?? get_bloginfo('description');
            }

            Flavor_Network_Node::save_local_node($node_data);
        }

        // Tema oscuro
        if (isset($input['theme_mode'])) {
            $theme_mode = sanitize_key($input['theme_mode']);
            if (!in_array($theme_mode, ['light', 'dark', 'system'], true)) {
                $theme_mode = 'light';
            }
            $sanitized['theme_mode'] = $theme_mode;
        }

        if (isset($input['dark_primary_color'])) {
            $sanitized['dark_primary_color'] = sanitize_hex_color($input['dark_primary_color']);
        }

        if (isset($input['dark_secondary_color'])) {
            $sanitized['dark_secondary_color'] = sanitize_hex_color($input['dark_secondary_color']);
        }

        if (isset($input['dark_background_color'])) {
            $sanitized['dark_background_color'] = sanitize_hex_color($input['dark_background_color']);
        }

        // Deep Links
        if (isset($input['deep_link_domain'])) {
            $sanitized['deep_link_domain'] = sanitize_text_field($input['deep_link_domain']);
        }

        if (isset($input['app_scheme'])) {
            $scheme = sanitize_key($input['app_scheme']);
            $sanitized['app_scheme'] = $scheme ?: 'flavorapp';
        }

        // Idiomas
        if (isset($input['default_language'])) {
            $lang = sanitize_key($input['default_language']);
            $valid_langs = ['es', 'en', 'ca', 'eu', 'gl', 'fr', 'de', 'it', 'pt', 'nl'];
            $sanitized['default_language'] = in_array($lang, $valid_langs, true) ? $lang : 'es';
        }

        if (isset($input['language_auto_detect'])) {
            $sanitized['language_auto_detect'] = !empty($input['language_auto_detect']);
        }

        if (isset($input['supported_languages']) && is_array($input['supported_languages'])) {
            $valid_langs = ['es', 'en', 'ca', 'eu', 'gl', 'fr', 'de', 'it', 'pt', 'nl'];
            $sanitized['supported_languages'] = array_values(array_intersect($input['supported_languages'], $valid_langs));
        }

        if (isset($input['date_format'])) {
            $format = sanitize_text_field($input['date_format']);
            $valid_formats = ['auto', 'dd/MM/yyyy', 'MM/dd/yyyy', 'yyyy-MM-dd'];
            $sanitized['date_format'] = in_array($format, $valid_formats, true) ? $format : 'auto';
        }

        if (isset($input['time_format'])) {
            $format = sanitize_text_field($input['time_format']);
            $valid_formats = ['auto', 'HH:mm', 'hh:mm a'];
            $sanitized['time_format'] = in_array($format, $valid_formats, true) ? $format : 'auto';
        }

        // Invalidar caché de API cuando se guardan los ajustes
        delete_transient('flavor_api_system_info');
        delete_transient('flavor_api_available_modules');
        delete_transient('flavor_api_layouts');
        delete_transient('flavor_api_theme');

        return $sanitized;
    }

    /**
     * Devuelve items de menú listos para UI
     */
    private function get_menu_items_payload($menu_source) {
        $locations = get_nav_menu_locations();
        $menus = wp_get_nav_menus();
        $menu_id = null;

        if ($menu_source && str_starts_with($menu_source, 'location:')) {
            $location = substr($menu_source, strlen('location:'));
            $menu_id = $locations[$location] ?? null;
        } elseif ($menu_source && str_starts_with($menu_source, 'menu:')) {
            $menu_id = intval(substr($menu_source, strlen('menu:')));
        }

        if (!$menu_id) {
            $menu_id = $locations['primary'] ?? $locations['menu-1'] ?? null;
        }
        if (!$menu_id && !empty($menus)) {
            $menu_id = $menus[0]->term_id;
        }

        $payload = [];
        if ($menu_id) {
            $menu_items = wp_get_nav_menu_items($menu_id);
            if (!empty($menu_items)) {
                $indexed = [];
                foreach ($menu_items as $menu_item) {
                    $indexed[$menu_item->ID] = $menu_item;
                }
                foreach ($menu_items as $menu_item) {
                    $title = $menu_item->title ?? '';
                    $url = $menu_item->url ?? '';
                    if (!$url) continue;
                    $depth = 0;
                    $parent = $menu_item->menu_item_parent ?? 0;
                    while ($parent && isset($indexed[$parent])) {
                        $depth++;
                        $parent = $indexed[$parent]->menu_item_parent ?? 0;
                        if ($depth > 10) break;
                    }
                    $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                    $payload[] = [
                        'title' => $prefix . $title,
                        'url' => $url,
                        'order' => isset($menu_item->menu_order) ? intval($menu_item->menu_order) : 0,
                        'depth' => $depth,
                    ];
                }
            }
        }

        return $payload;
    }

    /**
     * Sanitiza tokens
     */
    public function sanitize_tokens($input) {
        // Los tokens se gestionan vía AJAX
        return $input;
    }

    /**
     * Sanitiza lista de peers del directorio
     */
    public function sanitize_peer_urls($input) {
        if (is_array($input)) {
            $raw = implode("\n", $input);
        } else {
            $raw = (string) $input;
        }

        $urls = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw)));
        $sanitized = [];
        foreach ($urls as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $sanitized[] = untrailingslashit($url);
            }
        }

        return array_values(array_unique($sanitized));
    }

    /**
     * Geocodifica una dirección usando Nominatim (OpenStreetMap)
     */
    private function geocode_address($direccion) {
        $direccion = trim($direccion);
        if ($direccion === '') {
            return null;
        }

        $cache_key = 'flavor_business_geocode_' . md5($direccion);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'q' => $direccion,
                'format' => 'json',
                'limit' => 1,
            ],
            'https://nominatim.openstreetmap.org/search'
        );

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'FlavorChatIA/1.0',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }

        $coords = [
            'lat' => floatval($data[0]['lat']),
            'lng' => floatval($data[0]['lon']),
        ];

        set_transient($cache_key, $coords, DAY_IN_SECONDS);

        return $coords;
    }

    /**
     * Maneja el guardado de configuración push
     */
    public function handle_push_settings_save() {
        if (!isset($_POST['save_push_settings']) || !isset($_POST['push_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['push_nonce'], 'flavor_save_push_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = [
            'enabled' => isset($_POST['push_enabled']),
            'fcm_server_key' => sanitize_text_field($_POST['fcm_server_key'] ?? ''),
            'fcm_sender_id' => sanitize_text_field($_POST['fcm_sender_id'] ?? ''),
            'topics' => array_map('sanitize_text_field', $_POST['push_topics'] ?? []),
            'topics_active' => array_map(function($v) { return (bool) $v; }, $_POST['push_topics_active'] ?? []),
        ];

        update_option('flavor_app_push_settings', $settings);

        add_settings_error('flavor_push', 'push_saved', __('Configuración de Push guardada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'success');
    }

    /**
     * AJAX: Exportar configuración de la app
     */
    public function ajax_export_config() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'flavor_dev_tools')) {
            wp_die('Nonce inválido');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }

        $config_data = [
            'version' => FLAVOR_CHAT_IA_VERSION ?? '2.0.0',
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'flavor_apps_config' => get_option('flavor_apps_config', []),
            'flavor_app_active_modules' => get_option('flavor_app_active_modules', []),
            'flavor_app_navigation' => get_option('flavor_app_navigation', []),
            'flavor_app_push_settings' => get_option('flavor_app_push_settings', []),
            'flavor_directory_peer_urls' => get_option('flavor_directory_peer_urls', ''),
        ];

        $filename = 'flavor-app-config-' . date('Y-m-d-His') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        echo wp_json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX: Importar configuración de la app
     */
    public function ajax_import_config() {
        check_ajax_referer('flavor_dev_tools', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $config_json = stripslashes($_POST['config'] ?? '');
        $config_data = json_decode($config_json, true);

        if (!is_array($config_data)) {
            wp_send_json_error(['message' => __('JSON inválido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Importar cada opción
        if (isset($config_data['flavor_apps_config'])) {
            update_option('flavor_apps_config', $config_data['flavor_apps_config']);
        }
        if (isset($config_data['flavor_app_active_modules'])) {
            update_option('flavor_app_active_modules', $config_data['flavor_app_active_modules']);
        }
        if (isset($config_data['flavor_app_navigation'])) {
            update_option('flavor_app_navigation', $config_data['flavor_app_navigation']);
        }
        if (isset($config_data['flavor_app_push_settings'])) {
            update_option('flavor_app_push_settings', $config_data['flavor_app_push_settings']);
        }
        if (isset($config_data['flavor_directory_peer_urls'])) {
            update_option('flavor_directory_peer_urls', $config_data['flavor_directory_peer_urls']);
        }

        wp_send_json_success(['message' => __('Configuración importada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Limpiar caché de la app
     */
    public function ajax_clear_cache() {
        check_ajax_referer('flavor_dev_tools', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Eliminar transients relacionados con la app
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_flavor_app_%'
             OR option_name LIKE '_transient_timeout_flavor_app_%'
             OR option_name LIKE '_transient_flavor_business_%'
             OR option_name LIKE '_transient_timeout_flavor_business_%'"
        );

        // Limpiar object cache si está disponible
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        wp_send_json_success(['message' => __('Caché limpiado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Regenerar secretos de la app
     */
    public function ajax_regenerate_secrets() {
        check_ajax_referer('flavor_dev_tools', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Generar nuevos secretos
        $new_jwt_secret = wp_generate_password(64, true, true);
        $new_api_key = wp_generate_password(32, false, false);

        update_option('flavor_app_jwt_secret', $new_jwt_secret);
        update_option('flavor_app_api_key', $new_api_key);

        // Invalidar todos los tokens existentes
        delete_option('flavor_apps_tokens');

        wp_send_json_success([
            'message' => __('Secretos regenerados. Todos los tokens han sido invalidados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Crear/actualizar tablas de la app
     */
    public function ajax_create_tables() {
        check_ajax_referer('flavor_dev_tools', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tables_created = [];

        // Tabla de push tokens
        $table_push = $wpdb->prefix . 'flavor_push_tokens';
        $sql_push = "CREATE TABLE $table_push (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            token varchar(500) NOT NULL,
            platform varchar(20) DEFAULT 'unknown',
            device_id varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token(191)),
            KEY user_id (user_id),
            KEY platform (platform)
        ) $charset_collate;";
        $tables_created[] = 'push_tokens';

        // Tabla de eventos analytics
        $table_events = $wpdb->prefix . 'flavor_app_events';
        $sql_events = "CREATE TABLE $table_events (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            device_id varchar(100) DEFAULT NULL,
            platform varchar(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        $tables_created[] = 'app_events';

        // Tabla de stats diarias
        $table_stats = $wpdb->prefix . 'flavor_app_daily_stats';
        $sql_stats = "CREATE TABLE $table_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            connections int(11) DEFAULT 0,
            unique_devices int(11) DEFAULT 0,
            module_accesses int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY stat_date (stat_date)
        ) $charset_collate;";
        $tables_created[] = 'daily_stats';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_push);
        dbDelta($sql_events);
        dbDelta($sql_stats);

        wp_send_json_success([
            'message' => sprintf(__('Tablas creadas/actualizadas: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), implode(', ', $tables_created)),
        ]);
    }

    /**
     * AJAX: Restablecer configuración de la app
     */
    public function ajax_reset_config() {
        check_ajax_referer('flavor_dev_tools', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Eliminar todas las opciones de la app
        delete_option('flavor_apps_config');
        delete_option('flavor_app_active_modules');
        delete_option('flavor_app_navigation');
        delete_option('flavor_app_push_settings');
        delete_option('flavor_apps_tokens');
        delete_option('flavor_app_jwt_secret');
        delete_option('flavor_app_api_key');
        delete_option('flavor_app_last_sync');

        // Limpiar transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_flavor_app_%'
             OR option_name LIKE '_transient_timeout_flavor_app_%'"
        );

        wp_send_json_success(['message' => __('Configuración restablecida a valores por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Enviar notificación push de prueba
     */
    public function ajax_send_test_push() {
        check_ajax_referer('flavor_push_test', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $body = sanitize_textarea_field($_POST['body'] ?? '');
        $topic = sanitize_text_field($_POST['topic'] ?? '');

        if (empty($title) || empty($body) || empty($topic)) {
            wp_send_json_error(['message' => __('Todos los campos son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $push_settings = get_option('flavor_app_push_settings', []);
        $server_key = $push_settings['fcm_server_key'] ?? '';

        if (empty($server_key)) {
            wp_send_json_error(['message' => __('FCM Server Key no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Enviar a FCM
        $message = [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'type' => 'test',
                'timestamp' => time(),
            ],
        ];

        $response = wp_remote_post('https://fcm.googleapis.com/fcm/send', [
            'headers' => [
                'Authorization' => 'key=' . $server_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($message),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['message_id'])) {
            wp_send_json_success(['message' => __('Notificación enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error([
                'message' => $response_body['error'] ?? __('Error desconocido al enviar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * AJAX: Obtener logs de API
     */
    public function ajax_get_api_logs() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'flavor_api_logs')) {
            wp_send_json_error('Nonce inválido');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        // Intentar leer el log de debug de WordPress
        $log_file = WP_CONTENT_DIR . '/debug.log';
        $logs = '';

        if (file_exists($log_file) && is_readable($log_file)) {
            // Leer últimas 100 líneas relacionadas con flavor
            $lines = file($log_file);
            $flavor_lines = array_filter($lines, function($line) {
                return stripos($line, 'flavor') !== false || stripos($line, 'app-') !== false;
            });
            $logs = implode('', array_slice($flavor_lines, -100));
        }

        if (empty($logs)) {
            $logs = __('No se encontraron logs relevantes. Asegúrate de que WP_DEBUG_LOG esté habilitado.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        wp_send_json_success($logs);
    }

    /**
     * Obtiene configuración recomendada basada en módulos activos
     */
    public function ajax_get_recommended_config() {
        check_ajax_referer('flavor_apps_config', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        // Obtener módulos activos
        $active_modules = [];
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $active_modules = Flavor_Chat_Module_Loader::get_active_modules_cached();
        }

        // Mapeo de módulos a tabs con iconos, descripciones y prioridad
        // Prioridad más baja = más importante (aparece primero)
        $module_to_tab = [
            'eventos' => [
                'id' => 'eventos',
                'label' => 'Eventos',
                'icon' => 'event',
                'description' => __('Calendario de eventos y actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 10,
            ],
            'grupos-consumo' => [
                'id' => 'grupos_consumo',
                'label' => 'Grupos Consumo',
                'icon' => 'groups',
                'description' => __('Gestión de grupos de consumo responsable', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 15,
            ],
            'grupos_consumo' => [
                'id' => 'grupos_consumo',
                'label' => 'Grupos Consumo',
                'icon' => 'groups',
                'description' => __('Gestión de grupos de consumo responsable', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 15,
            ],
            'banco-tiempo' => [
                'id' => 'banco_tiempo',
                'label' => 'Banco de Tiempo',
                'icon' => 'handyman',
                'description' => __('Intercambio de servicios y habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 20,
            ],
            'banco_tiempo' => [
                'id' => 'banco_tiempo',
                'label' => 'Banco de Tiempo',
                'icon' => 'handyman',
                'description' => __('Intercambio de servicios y habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 20,
            ],
            'marketplace' => [
                'id' => 'marketplace',
                'label' => 'Marketplace',
                'icon' => 'store',
                'description' => __('Mercado de productos locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 12,
            ],
            'socios' => [
                'id' => 'socios',
                'label' => 'Miembros',
                'icon' => 'badge',
                'description' => __('Gestión de membresías y miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 25,
            ],
            'facturas' => [
                'id' => 'facturas',
                'label' => 'Facturas',
                'icon' => 'receipt',
                'description' => __('Facturación y pagos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 30,
            ],
            'carpooling' => [
                'id' => 'carpooling',
                'label' => 'Carpooling',
                'icon' => 'directions_car',
                'description' => __('Compartir viajes en coche', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 22,
            ],
            'comunidades' => [
                'id' => 'comunidades',
                'label' => 'Comunidades',
                'icon' => 'groups',
                'description' => __('Gestión de comunidades locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 8,
            ],
            'tramites' => [
                'id' => 'tramites',
                'label' => 'Trámites',
                'icon' => 'description',
                'description' => __('Trámites y gestiones administrativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 18,
            ],
            'incidencias' => [
                'id' => 'incidencias',
                'label' => 'Incidencias',
                'icon' => 'report_problem',
                'description' => __('Reportar y gestionar incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 16,
            ],
            'avisos-municipales' => [
                'id' => 'avisos_municipales',
                'label' => 'Avisos',
                'icon' => 'campaign',
                'description' => __('Avisos y comunicados oficiales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 5,
            ],
            'avisos_municipales' => [
                'id' => 'avisos_municipales',
                'label' => 'Avisos',
                'icon' => 'campaign',
                'description' => __('Avisos y comunicados oficiales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 5,
            ],
            'cursos' => [
                'id' => 'cursos',
                'label' => 'Cursos',
                'icon' => 'school',
                'description' => __('Formación y cursos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 24,
            ],
            'biblioteca' => [
                'id' => 'biblioteca',
                'label' => 'Biblioteca',
                'icon' => 'local_library',
                'description' => __('Recursos y documentación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 28,
            ],
            'chat-grupos' => [
                'id' => 'chat_grupos',
                'label' => 'Chat Grupos',
                'icon' => 'forum',
                'description' => __('Conversaciones grupales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 6,
            ],
            'chat_grupos' => [
                'id' => 'chat_grupos',
                'label' => 'Chat Grupos',
                'icon' => 'forum',
                'description' => __('Conversaciones grupales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 6,
            ],
            'chat-interno' => [
                'id' => 'chat_interno',
                'label' => 'Mensajes',
                'icon' => 'chat',
                'description' => __('Mensajería directa entre usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 7,
            ],
            'chat_interno' => [
                'id' => 'chat_interno',
                'label' => 'Mensajes',
                'icon' => 'chat',
                'description' => __('Mensajería directa entre usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 7,
            ],
            'presupuestos-participativos' => [
                'id' => 'presupuestos_participativos',
                'label' => 'Presupuestos',
                'icon' => 'how_to_vote',
                'description' => __('Votación de presupuestos participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 14,
            ],
            'crowdfunding' => [
                'id' => 'crowdfunding',
                'label' => 'Crowdfunding',
                'icon' => 'volunteer_activism',
                'description' => __('Financiación colectiva de proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 26,
            ],
            'economia-don' => [
                'id' => 'economia_don',
                'label' => 'Economía del Don',
                'icon' => 'favorite',
                'description' => __('Intercambio basado en la generosidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 27,
            ],
            'economia_don' => [
                'id' => 'economia_don',
                'label' => 'Economía del Don',
                'icon' => 'favorite',
                'description' => __('Intercambio basado en la generosidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 27,
            ],
            'red-social' => [
                'id' => 'red_social',
                'label' => 'Red Social',
                'icon' => 'people',
                'description' => __('Publicaciones y perfiles de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 9,
            ],
            'red_social' => [
                'id' => 'red_social',
                'label' => 'Red Social',
                'icon' => 'people',
                'description' => __('Publicaciones y perfiles de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 9,
            ],
            'trabajo-digno' => [
                'id' => 'trabajo_digno',
                'label' => 'Trabajo Digno',
                'icon' => 'work',
                'description' => __('Ofertas de empleo ético y local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 23,
            ],
            'trabajo_digno' => [
                'id' => 'trabajo_digno',
                'label' => 'Trabajo Digno',
                'icon' => 'work',
                'description' => __('Ofertas de empleo ético y local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 23,
            ],
        ];

        // Tabs base con alta prioridad (siempre presentes primero)
        $recommended_tabs = [
            [
                'id' => 'info',
                'label' => 'Info',
                'icon' => 'info',
                'description' => __('Información general del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enabled' => true,
                'priority' => 1,
            ],
            [
                'id' => 'chat',
                'label' => 'Chat',
                'icon' => 'chat_bubble',
                'description' => __('Chat con asistente de IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enabled' => true,
                'priority' => 2,
            ],
        ];

        // Agregar tabs basadas en módulos activos
        $modules_added = [];
        foreach ($active_modules as $module_id) {
            $normalized_id = str_replace('-', '_', $module_id);
            $tab_config = null;

            if (isset($module_to_tab[$module_id]) && !in_array($normalized_id, $modules_added)) {
                $tab_config = $module_to_tab[$module_id];
            } elseif (isset($module_to_tab[$normalized_id]) && !in_array($normalized_id, $modules_added)) {
                $tab_config = $module_to_tab[$normalized_id];
            }

            if ($tab_config) {
                $tab_config['enabled'] = true;
                $recommended_tabs[] = $tab_config;
                $modules_added[] = $normalized_id;
            }
        }

        // Si hay reservas/experiencias activas
        if (in_array('reservas', $active_modules) || in_array('experiencias', $active_modules) || get_option('flavor_chat_reservations_enabled', false)) {
            $recommended_tabs[] = [
                'id' => 'reservations',
                'label' => 'Reservar',
                'icon' => 'calendar_today',
                'description' => __('Sistema de reservas y citas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enabled' => true,
                'priority' => 3,
            ];
            $recommended_tabs[] = [
                'id' => 'my_tickets',
                'label' => 'Mis Tickets',
                'icon' => 'confirmation_number',
                'description' => __('Tus reservas y tickets', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enabled' => true,
                'priority' => 4,
            ];
        }

        // Ordenar por prioridad (menor número = más importante)
        usort($recommended_tabs, function($a, $b) {
            return ($a['priority'] ?? 100) - ($b['priority'] ?? 100);
        });

        // Asignar orden secuencial después de ordenar por prioridad
        $order = 0;
        foreach ($recommended_tabs as &$tab) {
            $tab['order'] = $order++;
        }

        // Limitar a 5 tabs activos máximo para bottom nav
        $enabled_count = 0;
        foreach ($recommended_tabs as &$tab) {
            if ($tab['enabled']) {
                $enabled_count++;
                if ($enabled_count > 5) {
                    $tab['enabled'] = false;
                }
            }
        }

        // Preparar descripciones de módulos para mostrar en el alert
        $module_descriptions = [];
        foreach ($recommended_tabs as $tab) {
            if (!empty($tab['description'])) {
                $module_descriptions[$tab['id']] = $tab['description'];
            }
        }

        wp_send_json_success([
            'tabs' => $recommended_tabs,
            'modules' => $active_modules,
            'module_descriptions' => $module_descriptions,
            'primary_color' => '#4CAF50',
            'unconfigured_count' => count($active_modules) - count($modules_added),
        ]);
    }

    /**
     * AJAX: Sincroniza todos los módulos activos de web a la configuración móvil
     */
    public function ajax_sync_modules_from_web() {
        check_ajax_referer('flavor_apps_config', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            return;
        }

        // Obtener módulos activos del sistema web
        $plugin_settings = get_option('flavor_chat_ia_settings', []);
        $active_web_modules = $plugin_settings['active_modules'] ?? [];

        // Obtener configuración actual de apps
        $apps_config = get_option('flavor_apps_config', []);
        if (!isset($apps_config['modules']) || !is_array($apps_config['modules'])) {
            $apps_config['modules'] = [];
        }

        $sincronizados = [];
        $ya_activos = [];

        foreach ($active_web_modules as $module_id) {
            // Normalizar ID (guiones a guiones bajos)
            $module_id_normalizado = str_replace('-', '_', $module_id);

            // Verificar si ya está activo
            if (isset($apps_config['modules'][$module_id_normalizado]['enabled']) &&
                $apps_config['modules'][$module_id_normalizado]['enabled'] == 1) {
                $ya_activos[] = $module_id_normalizado;
            } else {
                // Activar módulo para móvil
                if (!isset($apps_config['modules'][$module_id_normalizado])) {
                    $apps_config['modules'][$module_id_normalizado] = [];
                }
                $apps_config['modules'][$module_id_normalizado]['enabled'] = 1;
                $sincronizados[] = $module_id_normalizado;
            }
        }

        // Guardar configuración
        update_option('flavor_apps_config', $apps_config);

        wp_send_json_success([
            'message' => sprintf(
                __('Sincronización completada: %d módulos activados, %d ya estaban activos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($sincronizados),
                count($ya_activos)
            ),
            'sincronizados' => $sincronizados,
            'ya_activos' => $ya_activos,
            'total_web' => count($active_web_modules),
        ]);
    }

    /**
     * Renderiza la pestaña de Estadísticas
     */
    private function render_stats_tab() {
        ?>
        <div class="flavor-stats-tab" x-data="flavorAppStats()">
            <div class="stats-header">
                <h2><?php _e('Estadísticas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="stats-period-selector">
                    <select x-model="period" @change="loadStats()">
                        <option value="7"><?php _e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="30"><?php _e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="90"><?php _e('Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                    <button type="button" class="button" @click="loadStats()" :disabled="loading">
                        <span x-show="!loading"><?php _e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="loading"><?php _e('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="stats-summary-cards">
                <div class="stat-card">
                    <div class="stat-icon dashicons dashicons-smartphone"></div>
                    <div class="stat-content">
                        <span class="stat-value" x-text="summary.total_connections || 0"></span>
                        <span class="stat-label"><?php _e('Conexiones Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon dashicons dashicons-admin-users"></div>
                    <div class="stat-content">
                        <span class="stat-value" x-text="summary.unique_devices || 0"></span>
                        <span class="stat-label"><?php _e('Dispositivos Únicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon dashicons dashicons-grid-view"></div>
                    <div class="stat-content">
                        <span class="stat-value" x-text="summary.module_accesses || 0"></span>
                        <span class="stat-label"><?php _e('Accesos a Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon dashicons dashicons-chart-line"></div>
                    <div class="stat-content">
                        <span class="stat-value" x-text="summary.active_tokens || 0"></span>
                        <span class="stat-label"><?php _e('Tokens Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Timeline -->
            <div class="stats-chart-container">
                <h3><?php _e('Actividad Diaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <canvas id="statsTimelineChart" height="300"></canvas>
            </div>

            <!-- Módulos más usados -->
            <div class="stats-modules-grid">
                <div class="stats-module-ranking">
                    <h3><?php _e('Módulos Más Usados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <ul class="module-list">
                        <template x-for="module in topModules" :key="module.module_id">
                            <li class="module-item">
                                <span class="module-name" x-text="module.module_id"></span>
                                <span class="module-count" x-text="module.access_count + ' accesos'"></span>
                                <div class="module-bar">
                                    <div class="module-bar-fill" :style="'width:' + (module.percentage || 0) + '%'"></div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="stats-devices">
                    <h3><?php _e('Dispositivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <canvas id="statsDevicesChart" height="200"></canvas>
                </div>
            </div>

            <!-- Actividad de Tokens -->
            <div class="stats-tokens">
                <h3><?php _e('Tokens Push Registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Token (parcial)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Última actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="token in recentTokens" :key="token.id">
                            <tr>
                                <td><code x-text="token.token_partial"></code></td>
                                <td x-text="token.platform"></td>
                                <td x-text="token.user_name || 'Anónimo'"></td>
                                <td x-text="token.last_active"></td>
                            </tr>
                        </template>
                        <tr x-show="recentTokens.length === 0">
                            <td colspan="4"><?php _e('No hay tokens registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flavorAppStats', () => ({
                period: '30',
                loading: false,
                summary: {},
                topModules: [],
                recentTokens: [],
                timelineChart: null,
                devicesChart: null,

                init() {
                    this.loadStats();
                },

                async loadStats() {
                    this.loading = true;
                    try {
                        const response = await fetch(
                            `<?php echo rest_url('flavor-app-analytics/v1/stats'); ?>?period=${this.period}`,
                            { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' } }
                        );
                        const data = await response.json();

                        this.summary = data.summary || {};
                        this.topModules = (data.modules || []).slice(0, 10).map((m, i, arr) => ({
                            ...m,
                            percentage: arr[0] ? (m.access_count / arr[0].access_count * 100) : 0
                        }));
                        this.recentTokens = data.tokens || [];

                        this.renderTimelineChart(data.timeline || []);
                        this.renderDevicesChart(data.devices || []);
                    } catch (error) {
                        console.error('Error loading stats:', error);
                    }
                    this.loading = false;
                },

                renderTimelineChart(timeline) {
                    const ctx = document.getElementById('statsTimelineChart');
                    if (!ctx) return;

                    if (this.timelineChart) this.timelineChart.destroy();

                    this.timelineChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: timeline.map(d => d.date),
                            datasets: [{
                                label: '<?php _e('Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                                data: timeline.map(d => d.connections),
                                borderColor: '#2271b1',
                                tension: 0.3,
                                fill: false
                            }, {
                                label: '<?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                                data: timeline.map(d => d.module_accesses),
                                borderColor: '#00a32a',
                                tension: 0.3,
                                fill: false
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                },

                renderDevicesChart(devices) {
                    const ctx = document.getElementById('statsDevicesChart');
                    if (!ctx) return;

                    if (this.devicesChart) this.devicesChart.destroy();

                    this.devicesChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: devices.map(d => d.platform),
                            datasets: [{
                                data: devices.map(d => d.count),
                                backgroundColor: ['#2271b1', '#00a32a', '#dba617', '#d63638']
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de Push Notifications
     */
    private function render_push_tab() {
        $push_settings = get_option('flavor_app_push_settings', [
            'fcm_server_key' => '',
            'fcm_sender_id' => '',
            'enabled' => false,
            'topics' => []
        ]);
        ?>
        <div class="flavor-push-tab">
            <h2><?php _e('Configuración de Notificaciones Push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <form method="post" action="" id="push-settings-form">
                <?php wp_nonce_field('flavor_save_push_settings', 'push_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Habilitar Push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="push_enabled" value="1" <?php checked($push_settings['enabled']); ?>>
                                <?php _e('Activar notificaciones push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('FCM Server Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="password" name="fcm_server_key" class="regular-text"
                                   value="<?php echo esc_attr($push_settings['fcm_server_key']); ?>"
                                   placeholder="AAAA...">
                            <p class="description"><?php _e('Clave del servidor de Firebase Cloud Messaging', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('FCM Sender ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="text" name="fcm_sender_id" class="regular-text"
                                   value="<?php echo esc_attr($push_settings['fcm_sender_id']); ?>"
                                   placeholder="123456789012">
                            <p class="description"><?php _e('ID del remitente de Firebase', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Topics por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Configura topics de FCM para cada módulo. Los usuarios suscritos recibirán notificaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
                    <thead>
                        <tr>
                            <th><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Topic FCM', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modules = ['noticias', 'eventos', 'marketplace', 'grupos_consumo', 'banco_tiempo', 'alertas'];
                        foreach ($modules as $module) :
                            $topic = $push_settings['topics'][$module] ?? $module;
                            $active = isset($push_settings['topics_active'][$module]) ? $push_settings['topics_active'][$module] : true;
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst(str_replace('_', ' ', $module)); ?></strong></td>
                            <td>
                                <input type="text" name="push_topics[<?php echo $module; ?>]"
                                       value="<?php echo esc_attr($topic); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="checkbox" name="push_topics_active[<?php echo $module; ?>]"
                                       value="1" <?php checked($active); ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" name="save_push_settings" class="button button-primary">
                        <?php _e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h3><?php _e('Enviar Notificación de Prueba', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="push-test-form" x-data="pushTestForm()">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td><input type="text" x-model="title" class="regular-text" placeholder="<?php _e('Título de la notificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php _e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td><textarea x-model="body" rows="3" class="large-text" placeholder="<?php _e('Cuerpo del mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th><?php _e('Topic', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select x-model="topic">
                                <option value=""><?php _e('Seleccionar topic...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($modules as $module) : ?>
                                <option value="<?php echo esc_attr($module); ?>"><?php echo ucfirst(str_replace('_', ' ', $module)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-secondary" @click="sendTest()" :disabled="sending">
                        <span x-show="!sending"><?php _e('Enviar Notificación de Prueba', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="sending"><?php _e('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </p>
                <div x-show="result" :class="'notice notice-' + (success ? 'success' : 'error')" style="padding: 10px;">
                    <span x-text="result"></span>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pushTestForm', () => ({
                title: '',
                body: '',
                topic: '',
                sending: false,
                result: '',
                success: false,

                async sendTest() {
                    if (!this.title || !this.body || !this.topic) {
                        this.result = '<?php _e('Completa todos los campos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
                        this.success = false;
                        return;
                    }

                    this.sending = true;
                    this.result = '';

                    try {
                        const response = await fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'flavor_send_test_push',
                                nonce: '<?php echo wp_create_nonce('flavor_push_test'); ?>',
                                title: this.title,
                                body: this.body,
                                topic: this.topic
                            })
                        });
                        const data = await response.json();
                        this.success = data.success;
                        this.result = data.data?.message || (data.success ? '<?php _e('Enviado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' : '<?php _e('Error al enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                    } catch (e) {
                        this.success = false;
                        this.result = '<?php _e('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
                    }

                    this.sending = false;
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de Diagnóstico
     */
    private function render_diagnostics_tab() {
        ?>
        <div class="flavor-diagnostics-tab" x-data="flavorDiagnostics()">
            <h2><?php _e('Diagnóstico de API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <!-- Info del Sistema -->
            <div class="diag-section">
                <h3><?php _e('Información del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="widefat" style="max-width: 600px;">
                    <tr><td><strong>WordPress</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                    <tr><td><strong>PHP</strong></td><td><?php echo phpversion(); ?></td></tr>
                    <tr><td><strong>REST API</strong></td><td><?php echo rest_url(); ?></td></tr>
                    <tr><td><strong>Plugin Version</strong></td><td><?php echo FLAVOR_CHAT_IA_VERSION ?? '2.0.0'; ?></td></tr>
                    <tr><td><strong>Timezone</strong></td><td><?php echo wp_timezone_string(); ?></td></tr>
                </table>
            </div>

            <!-- Tester de Endpoints -->
            <div class="diag-section">
                <h3><?php _e('Probar Endpoints', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="endpoint-tester">
                    <div class="endpoint-input">
                        <select x-model="method" style="width: 100px;">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                        <input type="text" x-model="endpoint" class="regular-text"
                               placeholder="/flavor-app/v1/config" style="flex: 1;">
                        <button type="button" class="button button-primary" @click="testEndpoint()" :disabled="testing">
                            <span x-show="!testing"><?php _e('Probar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span x-show="testing"><?php _e('Probando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </button>
                    </div>

                    <div class="endpoint-presets">
                        <strong><?php _e('Endpoints comunes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <button type="button" class="button button-small" @click="setEndpoint('/flavor-app/v1/config')">Config</button>
                        <button type="button" class="button button-small" @click="setEndpoint('/flavor-app/v1/screens')">Screens</button>
                        <button type="button" class="button button-small" @click="setEndpoint('/flavor-app/v1/modules')">Modules</button>
                        <button type="button" class="button button-small" @click="setEndpoint('/flavor-app/v1/manifest.json')">Manifest</button>
                    </div>

                    <div class="endpoint-result" x-show="response">
                        <div class="result-header">
                            <span :class="'status-badge status-' + (statusCode >= 200 && statusCode < 300 ? 'ok' : 'error')">
                                <span x-text="statusCode"></span>
                            </span>
                            <span x-text="responseTime + 'ms'"></span>
                        </div>
                        <pre x-text="response" style="max-height: 400px; overflow: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px;"></pre>
                    </div>
                </div>
            </div>

            <!-- Verificación de Endpoints -->
            <div class="diag-section">
                <h3><?php _e('Estado de Endpoints', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <button type="button" class="button" @click="checkAllEndpoints()" :disabled="checkingAll">
                    <span x-show="!checkingAll"><?php _e('Verificar Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span x-show="checkingAll"><?php _e('Verificando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>

                <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php _e('Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ep in endpoints" :key="ep.path">
                            <tr>
                                <td><code x-text="ep.path"></code></td>
                                <td>
                                    <span x-show="ep.status === null" class="status-badge status-pending">-</span>
                                    <span x-show="ep.status === 'ok'" class="status-badge status-ok">OK</span>
                                    <span x-show="ep.status === 'error'" class="status-badge status-error">Error</span>
                                </td>
                                <td x-text="ep.time ? ep.time + 'ms' : '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Logs -->
            <div class="diag-section">
                <h3><?php _e('Logs Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="logs-container">
                    <button type="button" class="button" @click="loadLogs()"><?php _e('Cargar Logs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" @click="clearLogs()"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <pre x-text="logs" style="max-height: 300px; overflow: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; margin-top: 10px;"></pre>
                </div>
            </div>
        </div>

        <style>
            .diag-section { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .endpoint-tester .endpoint-input { display: flex; gap: 10px; margin-bottom: 10px; }
            .endpoint-presets { margin-bottom: 15px; }
            .endpoint-presets .button { margin: 2px; }
            .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
            .status-ok { background: #d4edda; color: #155724; }
            .status-error { background: #f8d7da; color: #721c24; }
            .status-pending { background: #e9ecef; color: #6c757d; }
            .result-header { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        </style>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flavorDiagnostics', () => ({
                method: 'GET',
                endpoint: '/flavor-app/v1/config',
                testing: false,
                response: '',
                statusCode: 0,
                responseTime: 0,
                checkingAll: false,
                logs: '',
                endpoints: [
                    { path: '/flavor-app/v1/config', status: null, time: null },
                    { path: '/flavor-app/v1/screens', status: null, time: null },
                    { path: '/flavor-app/v1/modules', status: null, time: null },
                    { path: '/flavor-app/v1/navigation', status: null, time: null },
                    { path: '/flavor-app/v1/manifest.json', status: null, time: null },
                ],

                setEndpoint(path) {
                    this.endpoint = path;
                },

                async testEndpoint() {
                    this.testing = true;
                    this.response = '';
                    const start = performance.now();

                    try {
                        const response = await fetch('<?php echo rest_url(); ?>' + this.endpoint.replace(/^\//, ''), {
                            method: this.method,
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' }
                        });

                        this.responseTime = Math.round(performance.now() - start);
                        this.statusCode = response.status;

                        const data = await response.json();
                        this.response = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responseTime = Math.round(performance.now() - start);
                        this.statusCode = 0;
                        this.response = 'Error: ' + e.message;
                    }

                    this.testing = false;
                },

                async checkAllEndpoints() {
                    this.checkingAll = true;

                    for (let ep of this.endpoints) {
                        const start = performance.now();
                        try {
                            const response = await fetch('<?php echo rest_url(); ?>' + ep.path.replace(/^\//, ''), {
                                headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' }
                            });
                            ep.time = Math.round(performance.now() - start);
                            ep.status = response.ok ? 'ok' : 'error';
                        } catch (e) {
                            ep.time = Math.round(performance.now() - start);
                            ep.status = 'error';
                        }
                    }

                    this.checkingAll = false;
                },

                async loadLogs() {
                    try {
                        const response = await fetch(ajaxurl + '?action=flavor_get_api_logs&nonce=<?php echo wp_create_nonce('flavor_api_logs'); ?>');
                        const data = await response.json();
                        this.logs = data.success ? data.data : 'Error cargando logs';
                    } catch (e) {
                        this.logs = 'Error: ' + e.message;
                    }
                },

                clearLogs() {
                    this.logs = '';
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de Herramientas
     */
    private function render_tools_tab() {
        $modulos_activos = get_option('flavor_app_active_modules', []);
        $modulos_activos = is_array($modulos_activos) ? array_values(array_filter(array_map('sanitize_key', $modulos_activos))) : [];
        $demo_tools_by_addon = (bool) apply_filters('flavor_demo_tools_rendered_by_addon', false, $modulos_activos);
        ?>
        <div class="flavor-tools-tab" x-data="flavorDevTools()">
            <h2><?php _e('Herramientas de Desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="tools-grid">
                <!-- Exportar Configuración -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-download"></span> <?php _e('Exportar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Descarga toda la configuración de la app en formato JSON.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="button button-primary" @click="exportConfig()">
                        <?php _e('Exportar JSON', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <!-- Importar Configuración -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-upload"></span> <?php _e('Importar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Restaura la configuración desde un archivo JSON.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <input type="file" accept=".json" @change="handleImportFile($event)" id="import-file" style="display: none;">
                    <button type="button" class="button" @click="$refs.importFile.click()">
                        <?php _e('Seleccionar Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <input type="file" x-ref="importFile" accept=".json" @change="handleImportFile($event)" style="display: none;">
                    <span x-text="importFileName" style="margin-left: 10px;"></span>
                    <button type="button" class="button button-primary" x-show="importData" @click="importConfig()" :disabled="importing">
                        <span x-show="!importing"><?php _e('Importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="importing"><?php _e('Importando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>

                <!-- Limpiar Caché -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-trash"></span> <?php _e('Limpiar Caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Elimina transients y caché relacionados con la app.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="button button-secondary" @click="clearCache()" :disabled="clearingCache">
                        <span x-show="!clearingCache"><?php _e('Limpiar Caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="clearingCache"><?php _e('Limpiando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>

                <!-- Regenerar Secretos -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-admin-network"></span> <?php _e('Regenerar Secretos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Genera nuevos JWT secrets y API keys. ¡Esto invalidará tokens existentes!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="button button-secondary" @click="regenerateSecrets()" :disabled="regenerating"
                            style="background: #d63638; border-color: #d63638; color: #fff;">
                        <span x-show="!regenerating"><?php _e('Regenerar Secretos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="regenerating"><?php _e('Regenerando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>

                <!-- Crear Tablas -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-database"></span> <?php _e('Crear/Actualizar Tablas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Crea o actualiza las tablas de la base de datos del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="button" @click="createTables()" :disabled="creatingTables">
                        <span x-show="!creatingTables"><?php _e('Ejecutar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="creatingTables"><?php _e('Ejecutando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>

                <!-- Reset Config -->
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-backup"></span> <?php _e('Restablecer Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Restablece toda la configuración a los valores por defecto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="button" @click="resetConfig()" :disabled="resetting"
                            style="background: #d63638; border-color: #d63638; color: #fff;">
                        <span x-show="!resetting"><?php _e('Restablecer Todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span x-show="resetting"><?php _e('Restableciendo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </button>
                </div>

                <!-- Datos demo -->
                <?php if (!$demo_tools_by_addon) : ?>
                <div class="tool-card">
                    <h3><span class="dashicons dashicons-database-import"></span> <?php _e('Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Carga o elimina datos de demostración para los módulos del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="margin-top: -8px; color:#646970;">
                        <?php echo esc_html(sprintf(__('Módulos activos detectados: %d', FLAVOR_PLATFORM_TEXT_DOMAIN), count($modulos_activos))); ?>
                    </p>

                    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('flavor_demo_data_action'); ?>
                        <input type="hidden" name="action" value="flavor_populate_demo_data">
                        <input type="hidden" name="modulo_id" value="all">
                        <input type="hidden" name="redirect_page" value="flavor-platform-apps">
                        <input type="hidden" name="redirect_tab" value="tools">
                        <?php foreach ($modulos_activos as $modulo_activo): ?>
                            <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_activo); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="button button-primary">
                            <?php _e('Poblar activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('flavor_demo_data_action'); ?>
                        <input type="hidden" name="action" value="flavor_populate_demo_data">
                        <input type="hidden" name="modulo_id" value="all">
                        <input type="hidden" name="redirect_page" value="flavor-platform-apps">
                        <input type="hidden" name="redirect_tab" value="tools">
                        <button type="submit" class="button">
                            <?php _e('Poblar todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar los datos demo de módulos activos?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');">
                        <?php wp_nonce_field('flavor_demo_data_action'); ?>
                        <input type="hidden" name="action" value="flavor_clear_demo_data">
                        <input type="hidden" name="modulo_id" value="all">
                        <input type="hidden" name="redirect_page" value="flavor-platform-apps">
                        <input type="hidden" name="redirect_tab" value="tools">
                        <?php foreach ($modulos_activos as $modulo_activo): ?>
                            <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_activo); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="button button-secondary">
                            <?php _e('Limpiar activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar todos los datos demo?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');">
                        <?php wp_nonce_field('flavor_demo_data_action'); ?>
                        <input type="hidden" name="action" value="flavor_clear_demo_data">
                        <input type="hidden" name="modulo_id" value="all">
                        <input type="hidden" name="redirect_page" value="flavor-platform-apps">
                        <input type="hidden" name="redirect_tab" value="tools">
                        <button type="submit" class="button">
                            <?php _e('Limpiar todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php do_action('flavor_app_config_tools_cards', $modulos_activos); ?>
            </div>

            <!-- Resultado de operaciones -->
            <div class="tool-result" x-show="result" :class="'notice notice-' + (success ? 'success' : 'error')">
                <p x-text="result"></p>
            </div>

            <!-- Quick Info -->
            <div class="quick-info" style="margin-top: 30px;">
                <h3><?php _e('Información Rápida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="widefat" style="max-width: 600px;">
                    <tr>
                        <td><strong><?php _e('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php
                            $active_modules = get_option('flavor_app_active_modules', []);
                            echo count($active_modules);
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Tokens Push Registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php
                            global $wpdb;
                            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_push_tokens");
                            echo $count ?: 0;
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Última Sincronización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php
                            $last_sync = get_option('flavor_app_last_sync');
                            echo $last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : '-';
                        ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <style>
            .tools-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
            .tool-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
            .tool-card h3 { margin-top: 0; display: flex; align-items: center; gap: 8px; }
            .tool-card h3 .dashicons { color: #2271b1; }
            .tool-card p { color: #646970; margin-bottom: 15px; }
            .tool-result { padding: 12px; margin-top: 20px; }
            @media (max-width: 1200px) { .tools-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 782px) { .tools-grid { grid-template-columns: 1fr; } }
        </style>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flavorDevTools', () => ({
                result: '',
                success: false,
                importing: false,
                importData: null,
                importFileName: '',
                clearingCache: false,
                regenerating: false,
                creatingTables: false,
                resetting: false,

                showResult(message, isSuccess) {
                    this.result = message;
                    this.success = isSuccess;
                    setTimeout(() => this.result = '', 5000);
                },

                async ajaxAction(action, data = {}) {
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: action,
                            nonce: '<?php echo wp_create_nonce('flavor_dev_tools'); ?>',
                            ...data
                        })
                    });
                    return response.json();
                },

                exportConfig() {
                    window.location.href = ajaxurl + '?action=flavor_export_app_config&nonce=<?php echo wp_create_nonce('flavor_dev_tools'); ?>';
                },

                handleImportFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.importFileName = file.name;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        try {
                            this.importData = JSON.parse(e.target.result);
                        } catch (err) {
                            this.showResult('<?php _e('Archivo JSON inválido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', false);
                            this.importData = null;
                        }
                    };
                    reader.readAsText(file);
                },

                async importConfig() {
                    if (!this.importData) return;
                    if (!confirm('<?php _e('¿Estás seguro? Esto sobrescribirá la configuración actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')) return;

                    this.importing = true;
                    const result = await this.ajaxAction('flavor_import_app_config', { config: JSON.stringify(this.importData) });
                    this.showResult(result.data?.message || (result.success ? '<?php _e('Importado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' : '<?php _e('Error al importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>'), result.success);
                    this.importing = false;
                    this.importData = null;
                    this.importFileName = '';
                },

                async clearCache() {
                    this.clearingCache = true;
                    const result = await this.ajaxAction('flavor_clear_app_cache');
                    this.showResult(result.data?.message || '<?php _e('Caché limpiado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', result.success);
                    this.clearingCache = false;
                },

                async regenerateSecrets() {
                    if (!confirm('<?php _e('¿Estás seguro? Esto invalidará todos los tokens de sesión activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')) return;

                    this.regenerating = true;
                    const result = await this.ajaxAction('flavor_regenerate_secrets');
                    this.showResult(result.data?.message || '<?php _e('Secretos regenerados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', result.success);
                    this.regenerating = false;
                },

                async createTables() {
                    this.creatingTables = true;
                    const result = await this.ajaxAction('flavor_create_app_tables');
                    this.showResult(result.data?.message || '<?php _e('Tablas actualizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', result.success);
                    this.creatingTables = false;
                },

                async resetConfig() {
                    if (!confirm('<?php _e('¿Estás seguro? Esto eliminará TODA la configuración de la app.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')) return;
                    if (!confirm('<?php _e('Esta acción NO se puede deshacer. ¿Continuar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')) return;

                    this.resetting = true;
                    const result = await this.ajaxAction('flavor_reset_app_config');
                    this.showResult(result.data?.message || '<?php _e('Configuración restablecida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', result.success);
                    this.resetting = false;

                    if (result.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de Deep Links
     */
    private function render_deeplinks_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_company_configs';
        $companies = [];

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $companies = $wpdb->get_results("SELECT * FROM $table_name ORDER BY nombre ASC", ARRAY_A);
        }

        $config = get_option('flavor_apps_config', []);
        $deep_link_domain = isset($config['deep_link_domain']) ? $config['deep_link_domain'] : '';
        $app_scheme = isset($config['app_scheme']) ? $config['app_scheme'] : 'flavorapp';
        ?>
        <div class="flavor-deeplinks-tab" x-data="flavorDeepLinks()">
            <h2><?php _e('Configuración de Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Los deep links permiten abrir contenido específico de la app desde enlaces web.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <!-- Configuración General -->
            <div class="deeplinks-section">
                <h3><?php _e('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Dominio de Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="text" name="flavor_apps_config[deep_link_domain]"
                                   value="<?php echo esc_attr($deep_link_domain); ?>"
                                   class="regular-text" placeholder="link.tuapp.com">
                            <p class="description"><?php _e('Dominio personalizado para deep links (opcional). Por defecto se usa tu dominio actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Esquema de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="text" name="flavor_apps_config[app_scheme]"
                                   value="<?php echo esc_attr($app_scheme); ?>"
                                   class="regular-text" placeholder="flavorapp">
                            <p class="description"><?php _e('Esquema URI para abrir la app (ej: flavorapp://). Debe coincidir con la configuración en Flutter.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Patrones de Deep Links -->
            <div class="deeplinks-section">
                <h3><?php _e('Patrones de Enlaces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Estos son los patrones soportados automáticamente por la app:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <table class="widefat" style="max-width: 800px;">
                    <thead>
                        <tr>
                            <th><?php _e('Patrón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Pantalla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/module/{id}</code></td>
                            <td><?php _e('Módulo específico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://module/noticias</code></td>
                        </tr>
                        <tr>
                            <td><code>/post/{id}</code></td>
                            <td><?php _e('Detalle de post', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://post/123</code></td>
                        </tr>
                        <tr>
                            <td><code>/event/{id}</code></td>
                            <td><?php _e('Detalle de evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://event/456</code></td>
                        </tr>
                        <tr>
                            <td><code>/product/{id}</code></td>
                            <td><?php _e('Producto WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://product/789</code></td>
                        </tr>
                        <tr>
                            <td><code>/chat</code></td>
                            <td><?php _e('Pantalla de chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://chat</code></td>
                        </tr>
                        <tr>
                            <td><code>/profile</code></td>
                            <td><?php _e('Perfil de usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code><?php echo esc_html($app_scheme); ?>://profile</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Multi-empresa (para apps white-label) -->
            <div class="deeplinks-section">
                <h3><?php _e('Configuraciones de Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Configura diferentes empresas para apps white-label. Cada empresa puede tener su propia configuración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="company-list">
                    <?php if (empty($companies)) : ?>
                        <p><?php _e('No hay empresas configuradas. Crea la tabla desde Herramientas > Crear Tablas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Slug', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php _e('API Base', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php _e('Deep Link', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company) : ?>
                                <tr>
                                    <td><code><?php echo esc_html($company['slug']); ?></code></td>
                                    <td><?php echo esc_html($company['nombre']); ?></td>
                                    <td><small><?php echo esc_html($company['api_base']); ?></small></td>
                                    <td>
                                        <?php if ($company['activo']) : ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                        <?php else : ?>
                                            <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($app_scheme); ?>://config/<?php echo esc_html($company['slug']); ?></code>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <p style="margin-top: 15px;">
                    <a href="<?php echo admin_url('admin.php?page=flavor-platform-deep-links'); ?>" class="button">
                        <?php _e('Gestionar Empresas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </p>
            </div>

            <!-- Generador de Deep Links -->
            <div class="deeplinks-section">
                <h3><?php _e('Generador de Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="deeplink-generator">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <td>
                                <select x-model="linkType">
                                    <option value="module"><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="post"><?php _e('Post', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="event"><?php _e('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="product"><?php _e('Producto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="custom"><?php _e('Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('ID/Path', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <td>
                                <input type="text" x-model="linkId" class="regular-text" placeholder="123 o noticias">
                            </td>
                        </tr>
                    </table>

                    <div class="generated-link" x-show="generatedLink">
                        <strong><?php _e('Deep Link generado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <code x-text="generatedLink"></code>
                        <button type="button" class="button button-small" @click="copyLink()">
                            <?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>

            <?php submit_button(); ?>
        </div>

        <style>
            .deeplinks-section { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .deeplinks-section h3 { margin-top: 0; }
            .generated-link { background: #f0f6fc; padding: 15px; border-radius: 4px; margin-top: 15px; display: flex; align-items: center; gap: 10px; }
            .generated-link code { background: #e5e5e5; padding: 5px 10px; border-radius: 3px; flex: 1; }
        </style>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flavorDeepLinks', () => ({
                linkType: 'module',
                linkId: '',
                scheme: '<?php echo esc_js($app_scheme); ?>',

                get generatedLink() {
                    if (!this.linkId) return '';
                    return `${this.scheme}://${this.linkType}/${this.linkId}`;
                },

                copyLink() {
                    navigator.clipboard.writeText(this.generatedLink);
                    alert('<?php _e('Enlace copiado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                }
            }));
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de Idiomas
     */
    private function render_languages_tab() {
        $config = get_option('flavor_apps_config', []);
        $default_language = isset($config['default_language']) ? $config['default_language'] : 'es';
        $supported_languages = isset($config['supported_languages']) ? $config['supported_languages'] : ['es'];
        $auto_detect = isset($config['language_auto_detect']) ? (bool) $config['language_auto_detect'] : true;

        $available_languages = [
            'es' => 'Español',
            'en' => 'English',
            'ca' => 'Català',
            'eu' => 'Euskara',
            'gl' => 'Galego',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
        ];
        ?>
        <div class="flavor-languages-tab">
            <h2><?php _e('Configuración de Idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Configura los idiomas disponibles en la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="languages-section">
                <h3><?php _e('Idioma Principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Idioma por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="flavor_apps_config[default_language]">
                                <?php foreach ($available_languages as $code => $name) : ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($default_language, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Idioma que se usará si no se detecta otro.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Detección automática', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="flavor_apps_config[language_auto_detect]" value="1" <?php checked($auto_detect); ?>>
                                <?php _e('Detectar idioma del dispositivo automáticamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="languages-section">
                <h3><?php _e('Idiomas Soportados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Selecciona los idiomas disponibles en la app. Los usuarios podrán cambiar entre estos idiomas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="languages-grid">
                    <?php foreach ($available_languages as $code => $name) : ?>
                        <label class="language-checkbox">
                            <input type="checkbox"
                                   name="flavor_apps_config[supported_languages][]"
                                   value="<?php echo esc_attr($code); ?>"
                                   <?php checked(in_array($code, (array) $supported_languages)); ?>>
                            <span class="language-flag"><?php echo $this->get_language_flag($code); ?></span>
                            <span class="language-name"><?php echo esc_html($name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="languages-section">
                <h3><?php _e('Traducciones de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('El contenido del sitio se traduce automáticamente si tienes WPML o Polylang. Las cadenas de la app Flutter usan archivos ARB.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="translation-status">
                    <table class="widefat" style="max-width: 600px;">
                        <tr>
                            <td><strong>WPML</strong></td>
                            <td>
                                <?php if (class_exists('SitePress')) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <?php _e('Instalado y activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus" style="color: gray;"></span>
                                    <?php _e('No instalado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Polylang</strong></td>
                            <td>
                                <?php if (function_exists('pll_languages_list')) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <?php _e('Instalado y activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus" style="color: gray;"></span>
                                    <?php _e('No instalado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Archivos ARB (Flutter)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td>
                                <code>mobile-apps/lib/l10n/</code>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="languages-section">
                <h3><?php _e('Formato de Fechas y Números', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Formato de fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="flavor_apps_config[date_format]">
                                <option value="auto" <?php selected($config['date_format'] ?? 'auto', 'auto'); ?>><?php _e('Automático (según idioma)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="dd/MM/yyyy" <?php selected($config['date_format'] ?? '', 'dd/MM/yyyy'); ?>>DD/MM/YYYY (31/12/2025)</option>
                                <option value="MM/dd/yyyy" <?php selected($config['date_format'] ?? '', 'MM/dd/yyyy'); ?>>MM/DD/YYYY (12/31/2025)</option>
                                <option value="yyyy-MM-dd" <?php selected($config['date_format'] ?? '', 'yyyy-MM-dd'); ?>>YYYY-MM-DD (2025-12-31)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Formato de hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="flavor_apps_config[time_format]">
                                <option value="auto" <?php selected($config['time_format'] ?? 'auto', 'auto'); ?>><?php _e('Automático (según idioma)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="HH:mm" <?php selected($config['time_format'] ?? '', 'HH:mm'); ?>>24h (14:30)</option>
                                <option value="hh:mm a" <?php selected($config['time_format'] ?? '', 'hh:mm a'); ?>>12h (2:30 PM)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </div>

        <style>
            .languages-section { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .languages-section h3 { margin-top: 0; }
            .languages-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 15px; }
            .language-checkbox { display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
            .language-checkbox:hover { background: #f0f6fc; border-color: #2271b1; }
            .language-checkbox input:checked + .language-flag + .language-name { font-weight: bold; }
            .language-flag { font-size: 20px; }
            .translation-status { margin-top: 15px; }
            @media (max-width: 1200px) { .languages-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 782px) { .languages-grid { grid-template-columns: repeat(2, 1fr); } }
        </style>
        <?php
    }

    /**
     * Obtiene el emoji de bandera para un código de idioma
     */
    private function get_language_flag($code) {
        $flags = [
            'es' => '🇪🇸',
            'en' => '🇬🇧',
            'ca' => '🏴󠁥󠁳󠁣󠁴󠁿',
            'eu' => '🏴',
            'gl' => '🏴',
            'fr' => '🇫🇷',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'nl' => '🇳🇱',
        ];
        return $flags[$code] ?? '🏳️';
    }
}

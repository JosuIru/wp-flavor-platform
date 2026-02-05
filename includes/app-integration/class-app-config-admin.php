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
    private $page_slug = 'flavor-apps-config';

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
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_app_token', [$this, 'ajax_generate_token']);
        add_action('wp_ajax_revoke_app_token', [$this, 'ajax_revoke_token']);
    }

    /**
     * Añade página al menú de admin
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-chat-ia', // Parent slug (menú principal de Flavor Chat IA)
            __('Configuración de Apps', 'flavor-chat-ia'),
            __('Apps Móviles', 'flavor-chat-ia'),
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

        // Tokens de API
        register_setting('flavor_apps_config', 'flavor_apps_tokens', [
            'sanitize_callback' => [$this, 'sanitize_tokens'],
        ]);

        // Sección: Información General
        add_settings_section(
            'general_section',
            __('Información General de la App', 'flavor-chat-ia'),
            [$this, 'render_general_section'],
            $this->page_slug
        );

        // Sección: Branding
        add_settings_section(
            'branding_section',
            __('Branding y Apariencia', 'flavor-chat-ia'),
            [$this, 'render_branding_section'],
            $this->page_slug
        );

        // Sección: Seguridad
        add_settings_section(
            'security_section',
            __('Seguridad y Tokens', 'flavor-chat-ia'),
            [$this, 'render_security_section'],
            $this->page_slug
        );

        // Sección: Módulos
        add_settings_section(
            'modules_section',
            __('Módulos Disponibles', 'flavor-chat-ia'),
            [$this, 'render_modules_section'],
            $this->page_slug
        );

        // Campos: Información General
        add_settings_field(
            'app_name',
            __('Nombre de la App', 'flavor-chat-ia'),
            [$this, 'render_app_name_field'],
            $this->page_slug,
            'general_section'
        );

        add_settings_field(
            'app_description',
            __('Descripción', 'flavor-chat-ia'),
            [$this, 'render_app_description_field'],
            $this->page_slug,
            'general_section'
        );

        // Campos: Branding
        add_settings_field(
            'app_logo',
            __('Logo de la App', 'flavor-chat-ia'),
            [$this, 'render_app_logo_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'primary_color',
            __('Color Primario', 'flavor-chat-ia'),
            [$this, 'render_primary_color_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'secondary_color',
            __('Color Secundario', 'flavor-chat-ia'),
            [$this, 'render_secondary_color_field'],
            $this->page_slug,
            'branding_section'
        );

        add_settings_field(
            'accent_color',
            __('Color de Acento', 'flavor-chat-ia'),
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

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'flavor-apps-config',
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
                'confirmRevoke' => __('¿Estás seguro de que quieres revocar este token?', 'flavor-chat-ia'),
                'tokenGenerated' => __('Token generado con éxito', 'flavor-chat-ia'),
                'tokenRevoked' => __('Token revocado', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'maxTabs' => __('Máximo 5 tabs activos permitidos', 'flavor-chat-ia'),
                'presetApplied' => __('Preset aplicado correctamente', 'flavor-chat-ia'),
            ],
        ]);

        wp_enqueue_style(
            'flavor-apps-config',
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

            <?php $this->render_connection_qr(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $this->page_slug; ?>&tab=general"
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=branding"
                   class="nav-tab <?php echo $active_tab === 'branding' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Branding', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=navigation"
                   class="nav-tab <?php echo $active_tab === 'navigation' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Navegación', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=modules"
                   class="nav-tab <?php echo $active_tab === 'modules' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Módulos', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=security"
                   class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Seguridad', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=directory"
                   class="nav-tab <?php echo $active_tab === 'directory' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Directorio', 'flavor-chat-ia'); ?>
                </a>
            </h2>

            <div class="flavor-app-config-layout">
                <!-- Columna principal -->
                <div class="flavor-app-config-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('flavor_apps_config');

                        if ($active_tab === 'general') {
                            do_settings_sections($this->page_slug);
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
                        }
                        ?>
                    </form>
                </div>

                <!-- Columna preview (phone mockup persistente) -->
                <div class="flavor-app-config-preview-column">
                    <h3><?php _e('Vista Previa', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-phone-mockup">
                        <div class="flavor-phone-notch"></div>
                        <div class="flavor-phone-screen">
                            <!-- App Bar -->
                            <div id="mockup-app-bar" style="background-color: <?php echo esc_attr($primary_color); ?>;">
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
                            <div id="mockup-navegacion-inferior">
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
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de General
     */
    public function render_general_section() {
        echo '<p>' . __('Configura la información básica de tu aplicación móvil.', 'flavor-chat-ia') . '</p>';
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
            <?php _e('Nombre que aparecerá en la app móvil', 'flavor-chat-ia'); ?>
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
            <?php _e('Descripción corta de tu comunidad o proyecto', 'flavor-chat-ia'); ?>
        </p>
        <?php
    }

    /**
     * Renderiza sección de branding
     */
    public function render_branding_section() {
        echo '<p>' . __('Personaliza los colores y el logo de tu app.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderiza el QR de conexión
     */
    private function render_connection_qr() {
        $site_url = get_site_url();

        // Usar API alternativa de QR Server (más confiable)
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($site_url);

        ?>
        <div class="flavor-qr-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-smartphone" style="color: #2271b1;"></span>
                <?php _e('Conectar Apps Móviles', 'flavor-chat-ia'); ?>
            </h3>
            <p><?php _e('Escanea este código QR desde la app móvil para conectarte automáticamente:', 'flavor-chat-ia'); ?></p>

            <div style="text-align: center; margin: 20px 0;">
                <div style="display: inline-block; background: #f9f9f9; padding: 20px; border-radius: 8px;">
                    <img src="<?php echo esc_url($qr_url); ?>"
                         alt="QR Code"
                         style="display: block; max-width: 300px; height: auto;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"/>
                    <div style="display: none; padding: 40px; background: #f0f0f0; text-align: center; width: 300px; height: 300px; line-height: 300px;">
                        <?php _e('Error al cargar QR', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>

            <div style="text-align: center;">
                <p><strong><?php _e('URL del sitio:', 'flavor-chat-ia'); ?></strong></p>
                <input type="text"
                       value="<?php echo esc_attr($site_url); ?>"
                       readonly
                       onclick="this.select();"
                       style="width: 100%; max-width: 500px; text-align: center; font-size: 16px; padding: 8px;"
                />
                <p class="description">
                    <?php _e('También puedes copiar esta URL e introducirla manualmente en la app.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <hr style="margin: 20px 0;">

            <h4><?php _e('¿Cómo conectar la app?', 'flavor-chat-ia'); ?></h4>
            <ol>
                <li><?php _e('Abre la app móvil en tu dispositivo', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Tap en "Configurar servidor" o icono de configuración', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Escanea este código QR o introduce la URL manualmente', 'flavor-chat-ia'); ?></li>
                <li><?php _e('La app se configurará automáticamente con tu logo, colores y módulos', 'flavor-chat-ia'); ?></li>
            </ol>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de Branding
     */
    private function render_branding_tab() {
        do_settings_sections($this->page_slug);
        submit_button();
    }

    /**
     * Renderiza la pestaña de Navegación
     */
    private function render_navigation_tab() {
        $config = get_option('flavor_apps_config', []);
        $default_tabs = [
            ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => 0],
            ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => 1],
            ['id' => 'my_tickets', 'label' => 'Mis Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2],
            ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 3],
        ];
        $tabs = isset($config['tabs']) ? $config['tabs'] : $default_tabs;
        usort($tabs, function($a, $b) { return ($a['order'] ?? 0) - ($b['order'] ?? 0); });

        $default_tab = isset($config['default_tab']) ? $config['default_tab'] : 'info';

        // Presets
        ?>
        <h2><?php _e('Navegación de la App', 'flavor-chat-ia'); ?></h2>
        <p><?php _e('Configura las pestañas de navegación inferior y su orden.', 'flavor-chat-ia'); ?></p>

        <div class="flavor-presets-bar">
            <h4><?php _e('Presets rápidos', 'flavor-chat-ia'); ?></h4>
            <button type="button" class="flavor-preset-btn" data-preset="restaurante">
                <span class="dashicons dashicons-food"></span> <?php _e('Restaurante', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="peluqueria">
                <span class="dashicons dashicons-art"></span> <?php _e('Peluquería', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="comunidad">
                <span class="dashicons dashicons-groups"></span> <?php _e('Comunidad', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-preset-btn" data-preset="tienda">
                <span class="dashicons dashicons-cart"></span> <?php _e('Tienda', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <div class="flavor-tabs-editor">
            <h3><?php _e('Pestañas de Navegación (máx. 5 activas)', 'flavor-chat-ia'); ?></h3>
            <ul class="flavor-tabs-list" id="flavor-tabs-sortable">
                <?php foreach ($tabs as $tab_index => $tab): ?>
                <li class="flavor-tab-item <?php echo empty($tab['enabled']) ? 'disabled' : ''; ?>" data-tab-id="<?php echo esc_attr($tab['id']); ?>">
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
                           placeholder="<?php esc_attr_e('Etiqueta', 'flavor-chat-ia'); ?>">

                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][id]" value="<?php echo esc_attr($tab['id']); ?>">
                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][icon]" value="<?php echo esc_attr($tab['icon'] ?? 'circle'); ?>" class="flavor-tab-icon-value">
                    <input type="hidden" name="flavor_apps_config[tabs][<?php echo $tab_index; ?>][order]" value="<?php echo esc_attr($tab_index); ?>" class="flavor-tab-order">
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="default_tab"><?php _e('Pestaña por defecto', 'flavor-chat-ia'); ?></label></th>
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

        <h3><?php _e('Secciones de la pantalla Info', 'flavor-chat-ia'); ?></h3>
        <p><?php _e('Activa y ordena las secciones que se muestran en la pestaña Info.', 'flavor-chat-ia'); ?></p>

        <?php
        $default_info_sections = [
            'header' => ['label' => __('Cabecera', 'flavor-chat-ia'), 'enabled' => true, 'order' => 0],
            'about' => ['label' => __('Sobre nosotros', 'flavor-chat-ia'), 'enabled' => true, 'order' => 1],
            'hours' => ['label' => __('Horarios', 'flavor-chat-ia'), 'enabled' => true, 'order' => 2],
            'contact' => ['label' => __('Contacto', 'flavor-chat-ia'), 'enabled' => true, 'order' => 3],
            'location' => ['label' => __('Ubicación', 'flavor-chat-ia'), 'enabled' => true, 'order' => 4],
            'social' => ['label' => __('Redes sociales', 'flavor-chat-ia'), 'enabled' => true, 'order' => 5],
        ];
        $info_sections = isset($config['info_sections']) ? $config['info_sections'] : $default_info_sections;
        ?>

        <div class="flavor-info-sections-editor">
            <ul class="flavor-info-sections-list" id="flavor-info-sections-sortable">
                <?php
                $section_index = 0;
                foreach ($info_sections as $section_id => $section_data):
                    $section_label = is_array($section_data) ? ($section_data['label'] ?? $section_id) : $section_id;
                    $section_enabled = is_array($section_data) ? (!empty($section_data['enabled'])) : true;
                ?>
                <li class="flavor-info-section-item" data-section-id="<?php echo esc_attr($section_id); ?>">
                    <span class="section-drag-handle dashicons dashicons-menu"></span>

                    <label class="flavor-toggle-switch">
                        <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][enabled]" value="0">
                        <input type="checkbox"
                               name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][enabled]"
                               value="1"
                               <?php checked($section_enabled); ?>>
                        <span class="flavor-toggle-slider"></span>
                    </label>

                    <span class="section-label"><?php echo esc_html($section_label); ?></span>

                    <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][label]" value="<?php echo esc_attr($section_label); ?>">
                    <input type="hidden" name="flavor_apps_config[info_sections][<?php echo esc_attr($section_id); ?>][order]" value="<?php echo esc_attr($section_index); ?>" class="flavor-section-order">
                </li>
                <?php
                    $section_index++;
                endforeach;
                ?>
            </ul>
        </div>

        <?php submit_button(); ?>

        <!-- Modal selector de iconos -->
        <div class="flavor-icon-modal-overlay" id="flavor-icon-modal">
            <div class="flavor-icon-modal">
                <div class="flavor-icon-modal-header">
                    <h3><?php _e('Seleccionar icono', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="flavor-icon-modal-close">&times;</button>
                </div>
                <div class="flavor-icon-search">
                    <input type="text" id="flavor-icon-search-input" placeholder="<?php esc_attr_e('Buscar icono...', 'flavor-chat-ia'); ?>">
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
                        'inventory', 'category', 'dashboard', 'analytics', 'trending_up',
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
                    <p><?php _e('No hay logo seleccionado', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <button type="button" class="button button-secondary" id="upload_logo_button">
                <?php _e('Seleccionar Logo', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="button" id="remove_logo_button" <?php echo !$logo_url ? 'style="display:none;"' : ''; ?>>
                <?php _e('Eliminar Logo', 'flavor-chat-ia'); ?>
            </button>

            <p class="description">
                <?php _e('Recomendado: PNG transparente, 512x512px', 'flavor-chat-ia'); ?>
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
            <?php _e('Color principal de la app (botones, barras, etc.)', 'flavor-chat-ia'); ?>
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
            <?php _e('Color secundario para complementar el primario', 'flavor-chat-ia'); ?>
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
            <?php _e('Color para resaltar elementos (notificaciones, alertas)', 'flavor-chat-ia'); ?>
        </p>
        <?php
    }

    /**
     * Renderiza sección de seguridad
     */
    public function render_security_section() {
        echo '<p>' . __('Gestiona los tokens de API para las aplicaciones móviles.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderiza la pestaña de Seguridad
     */
    private function render_security_tab() {
        ?>
        <h2><?php _e('Tokens de API para Apps', 'flavor-chat-ia'); ?></h2>
        <p><?php _e('Genera tokens de API para autenticar las aplicaciones móviles. Cada token puede tener un nombre identificativo.', 'flavor-chat-ia'); ?></p>

        <div class="flavor-tokens-section">
            <h3><?php _e('Generar Nuevo Token', 'flavor-chat-ia'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php _e('Nombre del Token', 'flavor-chat-ia'); ?></th>
                    <td>
                        <input type="text" id="new_token_name" class="regular-text"
                               placeholder="<?php _e('Ej: App Android Producción', 'flavor-chat-ia'); ?>">
                        <button type="button" class="button button-primary" id="generate_token_button">
                            <?php _e('Generar Token', 'flavor-chat-ia'); ?>
                        </button>
                    </td>
                </tr>
            </table>

            <div id="new_token_display" style="display: none;" class="notice notice-success">
                <p><strong><?php _e('Token generado:', 'flavor-chat-ia'); ?></strong></p>
                <code id="new_token_value" style="font-size: 12px; padding: 10px; display: block; background: #f0f0f0;"></code>
                <p class="description">
                    <?php _e('⚠️ Guarda este token en un lugar seguro. No podrás verlo de nuevo.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <h3><?php _e('Tokens Activos', 'flavor-chat-ia'); ?></h3>
            <?php $this->render_active_tokens(); ?>
        </div>

        <hr>

        <h2><?php _e('Endpoints de la API', 'flavor-chat-ia'); ?></h2>
        <p><?php _e('Las apps deben usar estos endpoints para comunicarse con el servidor:', 'flavor-chat-ia'); ?></p>

        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('URL Completa', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php _e('Descubrimiento', 'flavor-chat-ia'); ?></strong></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/info')); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Módulos', 'flavor-chat-ia'); ?></strong></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/modules')); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Tema', 'flavor-chat-ia'); ?></strong></td>
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
            echo '<p>' . __('No hay tokens activos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Fecha de Creación', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Último Uso', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
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
                            _e('Nunca', 'flavor-chat-ia');
                        }
                        ?>
                    </td>
                    <td>
                        <button type="button"
                                class="button button-small revoke-token"
                                data-token-id="<?php echo esc_attr($token_id); ?>">
                            <?php _e('Revocar', 'flavor-chat-ia'); ?>
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

        ?>
        <h2><?php _e('Directorio Público de Negocios', 'flavor-chat-ia'); ?></h2>
        <p><?php _e('Permite que los usuarios de las apps descubran y se conecten a tu negocio/comunidad.', 'flavor-chat-ia'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Aparecer en el Directorio', 'flavor-chat-ia'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_apps_config[public_in_directory]"
                               value="1"
                               <?php checked($is_public, true); ?>>
                        <?php _e('Sí, hacer mi negocio visible en el directorio público', 'flavor-chat-ia'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Si activas esta opción, los usuarios podrán encontrar y conectarse a tu negocio desde la app.', 'flavor-chat-ia'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_region"><?php _e('Región', 'flavor-chat-ia'); ?></label>
                </th>
                <td>
                    <select name="flavor_apps_config[business_region]" id="business_region" class="regular-text">
                        <option value=""><?php _e('Selecciona una región', 'flavor-chat-ia'); ?></option>
                        <?php
                        $regions = [
                            'euskal_herria' => __('Euskal Herria', 'flavor-chat-ia'),
                            'cataluna' => __('Cataluña', 'flavor-chat-ia'),
                            'madrid' => __('Madrid', 'flavor-chat-ia'),
                            'andalucia' => __('Andalucía', 'flavor-chat-ia'),
                            'other_spain' => __('Otras regiones de España', 'flavor-chat-ia'),
                            'international' => __('Internacional', 'flavor-chat-ia'),
                        ];
                        $current_region = isset($config['business_region']) ? $config['business_region'] : '';
                        foreach ($regions as $value => $label):
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_region, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Ayuda a los usuarios a encontrar negocios cercanos.', 'flavor-chat-ia'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="business_category"><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                </th>
                <td>
                    <select name="flavor_apps_config[business_category]" id="business_category" class="regular-text">
                        <option value=""><?php _e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                        <?php
                        $categories = [
                            'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
                            'asociacion' => __('Asociación', 'flavor-chat-ia'),
                            'comunidad' => __('Comunidad', 'flavor-chat-ia'),
                            'grupo_consumo' => __('Grupo de Consumo', 'flavor-chat-ia'),
                            'economia_social' => __('Economía Social', 'flavor-chat-ia'),
                            'comercio_local' => __('Comercio Local', 'flavor-chat-ia'),
                            'other' => __('Otra', 'flavor-chat-ia'),
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
                        <?php _e('Tipo de negocio o comunidad.', 'flavor-chat-ia'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>

        <hr>

        <h3><?php _e('Estado del Registro', 'flavor-chat-ia'); ?></h3>

        <?php if ($is_public): ?>
            <div class="notice notice-success inline">
                <p>
                    <strong><?php _e('Tu negocio está configurado como público', 'flavor-chat-ia'); ?></strong>
                </p>
            </div>

            <?php if ($is_registered): ?>
                <p><?php _e('✓ Registrado en el directorio', 'flavor-chat-ia'); ?></p>
                <?php if ($last_sync): ?>
                    <p>
                        <?php
                        printf(
                            __('Última sincronización: %s', 'flavor-chat-ia'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync)
                        );
                        ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p><?php _e('○ Pendiente de registrar en el directorio', 'flavor-chat-ia'); ?></p>
                <button type="button" class="button button-primary" id="register_in_directory">
                    <?php _e('Registrar Ahora', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>

            <hr>

            <h4><?php _e('Información que se compartirá:', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><?php _e('Nombre de tu negocio/comunidad', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Descripción', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Logo', 'flavor-chat-ia'); ?></li>
                <li><?php _e('URL del sitio', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Región y categoría', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Módulos disponibles', 'flavor-chat-ia'); ?></li>
            </ul>
            <p class="description">
                <?php _e('Ningún dato privado o sensible se comparte. Solo información pública necesaria para que los usuarios encuentren tu negocio.', 'flavor-chat-ia'); ?>
            </p>

        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong><?php _e('Tu negocio NO está visible en el directorio', 'flavor-chat-ia'); ?></strong>
                </p>
                <p><?php _e('Activa la opción "Aparecer en el Directorio" arriba para que los usuarios puedan encontrarte.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>

        <hr>

        <h3><?php _e('¿Cómo funciona el Directorio?', 'flavor-chat-ia'); ?></h3>
        <ol>
            <li><?php _e('Activas "Aparecer en el Directorio" y guardas los cambios', 'flavor-chat-ia'); ?></li>
            <li><?php _e('Tu negocio se registra automáticamente en el directorio central', 'flavor-chat-ia'); ?></li>
            <li><?php _e('Los usuarios de las apps pueden buscar negocios por región o categoría', 'flavor-chat-ia'); ?></li>
            <li><?php _e('Cuando encuentren tu negocio, podrán conectarse con un solo tap', 'flavor-chat-ia'); ?></li>
            <li><?php _e('La app se configurará automáticamente con tus colores, logo y módulos', 'flavor-chat-ia'); ?></li>
        </ol>

        <?php
    }

    /**
     * Renderiza sección de módulos
     */
    public function render_modules_section() {
        echo '<p>' . __('Estado de los módulos disponibles para las apps.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderiza la pestaña de Módulos
     */
    private function render_modules_tab() {
        $config = get_option('flavor_apps_config', []);
        $enabled_modules = isset($config['modules']) ? $config['modules'] : [];

        ?>
        <h2><?php _e('Módulos Disponibles para las Apps', 'flavor-chat-ia'); ?></h2>
        <p><?php _e('Activa o desactiva los módulos que estarán disponibles en la app móvil.', 'flavor-chat-ia'); ?></p>

        <div class="flavor-modules-grid">
            <?php
            $known_modules = [
                'grupos_consumo' => [
                    'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                    'description' => __('Pedidos colectivos y gestión de grupos', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Grupos_Consumo_API',
                    'icon' => 'shopping_cart',
                    'color' => '#4CAF50',
                ],
                'banco_tiempo' => [
                    'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    'description' => __('Intercambio de servicios y tiempo', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Banco_Tiempo_API',
                    'icon' => 'access_time',
                    'color' => '#2196F3',
                ],
                'marketplace' => [
                    'name' => __('Marketplace', 'flavor-chat-ia'),
                    'description' => __('Anuncios de regalo, venta e intercambio', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Marketplace_API',
                    'icon' => 'store',
                    'color' => '#FF9800',
                ],
                'woocommerce' => [
                    'name' => __('WooCommerce', 'flavor-chat-ia'),
                    'description' => __('Integración con tienda WooCommerce', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_WooCommerce_API',
                    'icon' => 'local_offer',
                    'color' => '#9C27B0',
                ],
                'facturas' => [
                    'name' => __('Facturas', 'flavor-chat-ia'),
                    'description' => __('Gestión de facturas para administradores', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Chat_Facturas_Module',
                    'icon' => 'receipt',
                    'color' => '#607D8B',
                ],
                'fichaje_empleados' => [
                    'name' => __('Fichaje de Empleados', 'flavor-chat-ia'),
                    'description' => __('Control de horarios y asistencia', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Chat_Fichaje_Empleados_Module',
                    'icon' => 'work',
                    'color' => '#795548',
                ],
                'eventos' => [
                    'name' => __('Eventos', 'flavor-chat-ia'),
                    'description' => __('Gestión de eventos comunitarios', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Chat_Eventos_Module',
                    'icon' => 'event',
                    'color' => '#E91E63',
                ],
                'socios' => [
                    'name' => __('Gestión de Socios', 'flavor-chat-ia'),
                    'description' => __('Control de socios y cuotas', 'flavor-chat-ia'),
                    'api_class' => 'Flavor_Chat_Socios_Module',
                    'icon' => 'people',
                    'color' => '#3F51B5',
                ],
            ];

            foreach ($known_modules as $module_id => $module_data):
                $api_available = class_exists($module_data['api_class']);
                $is_enabled = isset($enabled_modules[$module_id]['enabled']) ? $enabled_modules[$module_id]['enabled'] : $api_available;
            ?>
            <div class="flavor-module-card <?php echo !$is_enabled ? 'module-disabled' : ''; ?>">
                <div class="flavor-module-card-header">
                    <div class="flavor-module-icon-wrapper" style="background-color: <?php echo esc_attr($module_data['color']); ?>;">
                        <span class="material-icons"><?php echo esc_html($module_data['icon']); ?></span>
                    </div>
                    <label class="flavor-toggle-switch">
                        <input type="hidden" name="flavor_apps_config[modules][<?php echo esc_attr($module_id); ?>][enabled]" value="0">
                        <input type="checkbox"
                               name="flavor_apps_config[modules][<?php echo esc_attr($module_id); ?>][enabled]"
                               value="1"
                               class="flavor-module-toggle"
                               <?php checked($is_enabled); ?>>
                        <span class="flavor-toggle-slider"></span>
                    </label>
                </div>
                <div class="flavor-module-card-body">
                    <h4><?php echo esc_html($module_data['name']); ?></h4>
                    <p><?php echo esc_html($module_data['description']); ?></p>
                    <div class="flavor-module-api-status <?php echo $api_available ? 'available' : 'unavailable'; ?>">
                        <?php if ($api_available): ?>
                            <span class="dashicons dashicons-yes-alt"></span> <?php _e('API disponible', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-marker"></span> <?php _e('No instalado', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php submit_button(); ?>
        <?php
    }

    /**
     * AJAX: Generar token
     */
    public function ajax_generate_token() {
        check_ajax_referer('flavor_apps_config', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

        if (empty($name)) {
            wp_send_json_error(['message' => 'El nombre es obligatorio']);
        }

        // Generar token único
        $token = wp_generate_password(40, false);
        $token_id = wp_hash($token);

        // Guardar token
        $tokens = get_option('flavor_apps_tokens', []);
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
        check_ajax_referer('flavor_apps_config', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $token_id = isset($_POST['token_id']) ? sanitize_text_field($_POST['token_id']) : '';

        if (empty($token_id)) {
            wp_send_json_error(['message' => 'Token ID inválido']);
        }

        $tokens = get_option('flavor_apps_tokens', []);
        if (isset($tokens[$token_id])) {
            unset($tokens[$token_id]);
            update_option('flavor_apps_tokens', $tokens);
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Token no encontrado']);
        }
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

        // Tabs de navegación
        if (isset($input['tabs']) && is_array($input['tabs'])) {
            $sanitized_tabs = [];
            foreach ($input['tabs'] as $tab_index => $tab_data) {
                $sanitized_tabs[] = [
                    'id' => sanitize_key($tab_data['id'] ?? ''),
                    'label' => sanitize_text_field($tab_data['label'] ?? ''),
                    'icon' => sanitize_text_field($tab_data['icon'] ?? 'circle'),
                    'enabled' => !empty($tab_data['enabled']),
                    'order' => absint($tab_data['order'] ?? $tab_index),
                ];
            }
            $sanitized['tabs'] = $sanitized_tabs;
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

        return $sanitized;
    }

    /**
     * Sanitiza tokens
     */
    public function sanitize_tokens($input) {
        // Los tokens se gestionan vía AJAX
        return $input;
    }
}

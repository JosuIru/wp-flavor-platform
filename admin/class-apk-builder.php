<?php
/**
 * APK Builder - Compilador de Apps desde Admin
 *
 * Sistema para generar APKs personalizadas desde el panel de WordPress.
 * Permite configurar branding, módulos y generar builds.
 *
 * @package Flavor_Platform
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_APK_Builder {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = 'flavor-apk-builder';

    /**
     * Directorio de Flutter
     */
    private $flutter_path;

    /**
     * Directorio de builds
     */
    private $builds_path;

    /**
     * Cache de disponibilidad de comandos locales.
     *
     * @var bool|null
     */
    private $command_execution_available = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->flutter_path = FLAVOR_PLATFORM_PATH . 'mobile-apps';
        $this->builds_path = FLAVOR_PLATFORM_PATH . 'builds';

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_apk_check_environment', array($this, 'ajax_check_environment'));
        add_action('wp_ajax_flavor_apk_save_config', array($this, 'ajax_save_config'));
        add_action('wp_ajax_flavor_apk_start_build', array($this, 'ajax_start_build'));
        add_action('wp_ajax_flavor_apk_check_build_status', array($this, 'ajax_check_build_status'));
        add_action('wp_ajax_flavor_apk_download_config', array($this, 'ajax_download_config'));
        add_action('wp_ajax_flavor_apk_list_builds', array($this, 'ajax_list_builds'));
        add_action('wp_ajax_flavor_apk_module_preview', array($this, 'ajax_module_preview_data'));
        add_action('wp_ajax_flavor_apk_tabs_config', array($this, 'ajax_get_tabs_config'));
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Agregar página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Compilar APK', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Compilar APK', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_page')
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-apk-builder',
            FLAVOR_PLATFORM_URL . 'admin/css/apk-builder.css',
            array(),
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-apk-builder',
            FLAVOR_PLATFORM_URL . 'admin/js/apk-builder.js',
            array('jquery', 'wp-color-picker'),
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        // Material Icons Outlined para que el preview se parezca a la APK Flutter,
        // que usa la misma familia tipográfica de iconos.
        wp_enqueue_style(
            'flavor-apk-builder-material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined',
            array(),
            null
        );
        wp_enqueue_style(
            'flavor-apk-builder-roboto',
            'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            array(),
            null
        );

        wp_localize_script('flavor-apk-builder', 'flavorApkBuilder', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_apk_builder'),
            'modulesMeta' => $this->get_available_modules(),
            'i18n' => array(
                'checking' => __('Verificando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'building' => __('Compilando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'success' => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'selectIcon' => __('Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmBuild' => __('¿Iniciar compilación? Este proceso puede tardar varios minutos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'noModulesSelected' => __('Selecciona módulos para previsualizarlos en la app', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tabHome' => __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tabSearch' => __('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tabAlerts' => __('Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tabProfile' => __('Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'searchPlaceholder' => __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'noAlerts' => __('Sin avisos nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'profileGreeting' => __('Hola, vecino/a', FLAVOR_PLATFORM_TEXT_DOMAIN),
            )
        ));
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        $config = $this->get_saved_config();
        $modules = $this->get_available_modules();
        ?>
        <div class="wrap flavor-apk-builder-wrap">
            <h1>
                <span class="dashicons dashicons-smartphone"></span>
                <?php _e('Compilador de APKs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <div class="apk-builder-layout">
                <!-- Panel izquierdo: Configuración -->
                <div class="config-panel">
                    <!-- Estado del entorno -->
                    <div class="config-section environment-check">
                        <h2><?php _e('Estado del Entorno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <div id="environment-status">
                            <div class="env-item" data-check="flutter">
                                <span class="status-icon pending"></span>
                                <span class="env-label">Flutter SDK</span>
                                <span class="env-value">-</span>
                            </div>
                            <div class="env-item" data-check="dart">
                                <span class="status-icon pending"></span>
                                <span class="env-label">Dart SDK</span>
                                <span class="env-value">-</span>
                            </div>
                            <div class="env-item" data-check="android">
                                <span class="status-icon pending"></span>
                                <span class="env-label">Android SDK</span>
                                <span class="env-value">-</span>
                            </div>
                            <div class="env-item" data-check="project">
                                <span class="status-icon pending"></span>
                                <span class="env-label">Proyecto Flutter</span>
                                <span class="env-value">-</span>
                            </div>
                        </div>
                        <button type="button" id="check-environment" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Verificar Entorno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <!-- Branding -->
                    <div class="config-section">
                        <h2><?php _e('Branding', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <form id="branding-form">
                            <div class="form-row">
                                <label for="app_name"><?php _e('Nombre de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="app_name" name="app_name"
                                       value="<?php echo esc_attr($config['app_name']); ?>"
                                       placeholder="Mi App">
                            </div>

                            <div class="form-row">
                                <label for="app_id"><?php _e('ID de la App (Package Name)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="app_id" name="app_id"
                                       value="<?php echo esc_attr($config['app_id']); ?>"
                                       placeholder="com.ejemplo.miapp">
                                <p class="description"><?php _e('Formato: com.empresa.app (solo minúsculas y puntos)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>

                            <div class="form-row">
                                <label for="app_version"><?php _e('Versión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <div class="version-inputs">
                                    <input type="text" id="app_version" name="app_version"
                                           value="<?php echo esc_attr($config['app_version']); ?>"
                                           placeholder="1.0.0">
                                    <span>+</span>
                                    <input type="number" id="app_build" name="app_build"
                                           value="<?php echo esc_attr($config['app_build']); ?>"
                                           placeholder="1" min="1">
                                </div>
                            </div>

                            <div class="form-row">
                                <label><?php _e('Icono de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <div class="icon-selector">
                                    <div class="icon-preview" id="icon-preview">
                                        <?php if ($config['app_icon']): ?>
                                            <img src="<?php echo esc_url($config['app_icon']); ?>" alt="App Icon">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-format-image"></span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" id="app_icon" name="app_icon"
                                           value="<?php echo esc_attr($config['app_icon']); ?>">
                                    <button type="button" id="select-icon" class="button">
                                        <?php _e('Seleccionar Icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <p class="description"><?php _e('Recomendado: 1024x1024px PNG', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </div>

                            <div class="form-row colors-row">
                                <div class="color-field">
                                    <label for="color_primary"><?php _e('Color Primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <input type="text" id="color_primary" name="color_primary"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_primary']); ?>">
                                </div>
                                <div class="color-field">
                                    <label for="color_secondary"><?php _e('Color Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <input type="text" id="color_secondary" name="color_secondary"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_secondary']); ?>">
                                </div>
                                <div class="color-field">
                                    <label for="color_accent"><?php _e('Color Acento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <input type="text" id="color_accent" name="color_accent"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_accent']); ?>">
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Conexión -->
                    <div class="config-section">
                        <h2><?php _e('Conexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <div class="form-row">
                            <label for="site_url"><?php _e('URL del Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="url" id="site_url" name="site_url"
                                   value="<?php echo esc_attr($config['site_url'] ?: home_url()); ?>">
                        </div>
                        <div class="form-row">
                            <label for="api_key"><?php _e('API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div class="api-key-field">
                                <input type="text" id="api_key" name="api_key"
                                       value="<?php echo esc_attr($config['api_key']); ?>"
                                       readonly>
                                <button type="button" id="generate-api-key" class="button">
                                    <?php _e('Generar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Módulos -->
                    <div class="config-section">
                        <h2><?php _e('Módulos a Incluir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <p class="description"><?php _e('Selecciona los módulos que estarán disponibles en la app.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <div class="modules-grid" id="modules-grid">
                            <?php foreach ($modules as $module_id => $module): ?>
                                <label class="module-item">
                                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module_id); ?>"
                                           <?php checked(in_array($module_id, $config['modules'])); ?>>
                                    <span class="module-icon">
                                        <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                                    </span>
                                    <span class="module-name"><?php echo esc_html($module['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Opciones avanzadas -->
                    <div class="config-section collapsible">
                        <h2 class="collapsible-header">
                            <?php _e('Opciones Avanzadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </h2>
                        <div class="collapsible-content">
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_offline" name="enable_offline"
                                           <?php checked($config['enable_offline']); ?>>
                                    <?php _e('Habilitar modo offline', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_push" name="enable_push"
                                           <?php checked($config['enable_push']); ?>>
                                    <?php _e('Habilitar notificaciones push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_biometric" name="enable_biometric"
                                           <?php checked($config['enable_biometric']); ?>>
                                    <?php _e('Habilitar autenticación biométrica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label for="min_android_version"><?php _e('Versión mínima Android', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="min_android_version" name="min_android_version">
                                    <option value="21" <?php selected($config['min_android_version'], 21); ?>>Android 5.0 (API 21)</option>
                                    <option value="23" <?php selected($config['min_android_version'], 23); ?>>Android 6.0 (API 23)</option>
                                    <option value="24" <?php selected($config['min_android_version'], 24); ?>>Android 7.0 (API 24)</option>
                                    <option value="26" <?php selected($config['min_android_version'], 26); ?>>Android 8.0 (API 26)</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="build_type"><?php _e('Tipo de Build', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="build_type" name="build_type">
                                    <option value="apk" <?php selected($config['build_type'], 'apk'); ?>>APK (Debug/Test)</option>
                                    <option value="appbundle" <?php selected($config['build_type'], 'appbundle'); ?>>App Bundle (Play Store)</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="flavor"><?php _e('Variante de App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="flavor" name="flavor">
                                    <option value="client" <?php selected($config['flavor'], 'client'); ?>>Cliente</option>
                                    <option value="admin" <?php selected($config['flavor'], 'admin'); ?>>Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="config-actions">
                        <button type="button" id="save-config" class="button button-secondary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" id="download-config" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Descargar Config', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" id="start-build" class="button button-primary button-hero">
                            <span class="dashicons dashicons-hammer"></span>
                            <?php _e('Compilar APK', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <!-- Panel derecho: Preview y Builds -->
                <div class="preview-panel">
                    <!-- Preview -->
                    <div class="preview-section">
                        <h2>
                            <?php _e('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="preview-toolbar">
                                <button type="button" class="preview-toolbar__btn" id="preview-theme-toggle" title="<?php esc_attr_e('Alternar light/dark', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="material-icons-outlined">dark_mode</span>
                                </button>
                                <button type="button" class="preview-toolbar__btn" id="preview-replay" title="<?php esc_attr_e('Reproducir splash', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="material-icons-outlined">replay</span>
                                </button>
                            </span>
                        </h2>
                        <p class="preview-hint" id="preview-hint">
                            <?php _e('Simula la app cliente Flutter en modo hybrid (4 tabs nativas + drawer con módulos). Pulsa el menú o cualquier módulo del drawer.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <div class="preview-flavor-badge preview-flavor-badge--client" id="preview-flavor-badge">
                            <span class="material-icons-outlined">person</span>
                            <span id="preview-flavor-label"><?php _e('Modo Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="phone-preview">
                            <div class="phone-frame" id="preview-frame">
                                <div class="phone-notch"></div>
                                <div class="phone-status-bar">
                                    <span class="status-time">9:41</span>
                                    <span class="status-icons">
                                        <span class="material-icons-outlined">network_wifi</span>
                                        <span class="material-icons-outlined">battery_full</span>
                                    </span>
                                </div>
                                <div class="phone-screen" id="preview-screen">

                                    <!-- SPLASH SCREEN (1.4s al iniciar) -->
                                    <div class="screen-view screen-view--splash" data-view="splash" style="display:none;">
                                        <div class="splash-logo">
                                            <span class="material-icons-outlined">apartment</span>
                                        </div>
                                        <div class="splash-name" id="preview-splash-name"><?php echo esc_html($config['app_name'] ?: 'Mi App'); ?></div>
                                        <div class="splash-progress"><div class="splash-progress__bar"></div></div>
                                    </div>

                                    <!-- VISTA TAB ACTIVA (chat, reservations, my_tickets, info) -->
                                    <div class="screen-view" data-view="tab">
                                        <div class="app-header" id="preview-header">
                                            <span class="material-icons-outlined header-icon header-icon--menu" id="preview-menu-toggle">menu</span>
                                            <span class="app-title" id="preview-tab-title"><?php echo esc_html($config['app_name'] ?: 'Mi App'); ?></span>
                                            <span class="material-icons-outlined header-icon">notifications_none</span>
                                        </div>
                                        <div class="app-content app-content--tab" id="preview-tab-body">
                                            <!-- Se llena dinámicamente según la tab activa -->
                                        </div>
                                    </div>

                                    <!-- VISTA MÓDULO (detalle) -->
                                    <div class="screen-view" data-view="module" style="display:none;">
                                        <div class="app-header app-header--module" id="preview-module-header">
                                            <span class="material-icons-outlined header-icon header-icon--back" id="preview-back">arrow_back</span>
                                            <span class="app-title" id="preview-module-title">Módulo</span>
                                            <span class="material-icons-outlined header-icon">more_vert</span>
                                        </div>
                                        <div class="app-content app-content--module">
                                            <div class="preview-module-list" id="preview-module-list">
                                                <!-- Se llena dinámicamente -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DRAWER lateral (sobre la pantalla actual) -->
                                    <div class="app-drawer" id="preview-drawer">
                                        <div class="app-drawer__header">
                                            <div class="app-drawer__avatar">
                                                <span class="material-icons-outlined">person_outline</span>
                                            </div>
                                            <div class="app-drawer__user">
                                                <div class="app-drawer__name" id="preview-drawer-name"><?php echo esc_html($config['app_name'] ?: 'Mi App'); ?></div>
                                                <div class="app-drawer__email"><?php _e('vecino@cooperativa.org', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                            </div>
                                        </div>
                                        <div class="app-drawer__list" id="preview-drawer-list">
                                            <!-- Se llena dinámicamente con tabs + módulos seleccionados -->
                                        </div>
                                        <div class="app-drawer__empty" id="preview-drawer-empty" style="display:none;">
                                            <span class="material-icons-outlined">apps</span>
                                            <p><?php _e('Selecciona módulos para que aparezcan aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                        </div>
                                    </div>
                                    <div class="app-drawer-overlay" id="preview-drawer-overlay"></div>

                                    <!-- BOTTOM NAVIGATION BAR (Material 3 NavigationBar, max 4-5 tabs como en main_client_home.dart) -->
                                    <div class="app-navbar" id="preview-navbar">
                                        <div class="nav-item" data-tab="chat">
                                            <span class="material-icons-outlined">chat_bubble</span>
                                            <span><?php _e('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                        <div class="nav-item" data-tab="reservations">
                                            <span class="material-icons-outlined">calendar_today</span>
                                            <span><?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                        <div class="nav-item" data-tab="my_tickets">
                                            <span class="material-icons-outlined">confirmation_number</span>
                                            <span><?php _e('Tickets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                        <div class="nav-item active" data-tab="info">
                                            <span class="material-icons-outlined">info</span>
                                            <span><?php _e('Info', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Builds recientes -->
                    <div class="builds-section">
                        <h2><?php _e('Builds Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <div id="builds-list" class="builds-list">
                            <div class="loading"><?php _e('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>

                    <!-- Log de compilación -->
                    <div class="build-log-section" id="build-log-section" style="display: none;">
                        <h2>
                            <?php _e('Log de Compilación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="build-status" id="build-status"></span>
                        </h2>
                        <div class="build-progress">
                            <div class="progress-bar" id="build-progress-bar"></div>
                        </div>
                        <div class="build-log" id="build-log"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener configuración guardada
     */
    private function get_saved_config() {
        $defaults = array(
            'app_name' => get_bloginfo('name'),
            'app_id' => 'com.' . sanitize_title(get_bloginfo('name')) . '.app',
            'app_version' => '1.0.0',
            'app_build' => 1,
            'app_icon' => '',
            'color_primary' => '#2271b1',
            'color_secondary' => '#135e96',
            'color_accent' => '#d63638',
            'site_url' => home_url(),
            'api_key' => '',
            'modules' => array('eventos', 'socios', 'marketplace'),
            'enable_offline' => true,
            'enable_push' => false,
            'enable_biometric' => true,
            'min_android_version' => 21,
            'build_type' => 'apk',
            'flavor' => 'client',
        );

        $saved = get_option('flavor_apk_config', array());
        return wp_parse_args($saved, $defaults);
    }

    /**
     * AJAX: devuelve las tabs configuradas por el usuario para la APK cliente.
     *
     * Lee `flavor_apps_config['tabs']` (gestionado en el panel de App Integration).
     * Si no hay config válida, devuelve las 4 tabs nativas por defecto que también
     * usa lib/main_client_home.dart::_applyDefaultConfig.
     */
    public function ajax_get_tabs_config() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $defaults = array(
            array('id' => 'chat',         'label' => 'Chat',     'icon' => 'chat_bubble',         'enabled' => true, 'order' => 0),
            array('id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today',      'enabled' => true, 'order' => 1),
            array('id' => 'my_tickets',   'label' => 'Tickets',  'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2),
            array('id' => 'info',         'label' => 'Info',     'icon' => 'info',                'enabled' => true, 'order' => 3),
        );

        $app_config = get_option('flavor_apps_config', array());
        $tabs_raw = is_array($app_config) && !empty($app_config['tabs']) && is_array($app_config['tabs'])
            ? $app_config['tabs']
            : $defaults;

        $tabs_enabled = array_values(array_filter($tabs_raw, function ($tab) {
            return !empty($tab['enabled']);
        }));

        usort($tabs_enabled, function ($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // El bottom nav de Flutter (NavigationBar) acepta máximo 5 destinations.
        $tabs_enabled = array_slice($tabs_enabled, 0, 5);

        // Si la config dejó menos de 2 tabs, recurrir a defaults.
        if (count($tabs_enabled) < 2) {
            $tabs_enabled = $defaults;
        }

        $default_tab = isset($app_config['default_tab']) && is_string($app_config['default_tab'])
            ? $app_config['default_tab']
            : 'info';

        $tabs_simplificadas = array_map(function ($tab) {
            return array(
                'id'    => sanitize_key($tab['id'] ?? ''),
                'label' => sanitize_text_field($tab['label'] ?? ucfirst($tab['id'] ?? '')),
                'icon'  => sanitize_text_field($tab['icon'] ?? 'apps'),
            );
        }, $tabs_enabled);

        // Filtrar tabs sin id válido tras sanitizar
        $tabs_simplificadas = array_values(array_filter($tabs_simplificadas, function ($t) {
            return !empty($t['id']);
        }));

        wp_send_json_success(array(
            'tabs'        => $tabs_simplificadas,
            'default_tab' => $default_tab,
            'source'      => !empty($app_config['tabs']) ? 'config' : 'default',
        ));
    }

    /**
     * Devuelve el mapeo módulo → consulta SQL para alimentar el preview con
     * datos reales del sitio. Cada entrada describe la tabla principal y los
     * campos para construir título y subtítulo de cada item.
     *
     * Solo se incluyen módulos con tablas estables y schema conocido. Los que
     * no aparezcan aquí caen al fallback de mock_items.
     */
    private function get_module_preview_queries() {
        global $wpdb;
        return array(
            'eventos' => array(
                'table'    => $wpdb->prefix . 'flavor_eventos',
                'title'    => 'titulo',
                'subtitle' => "DATE_FORMAT(fecha_inicio, '%d %b · %H:%i')",
                'where'    => "fecha_inicio >= NOW()",
                'order'    => 'fecha_inicio ASC',
            ),
            'marketplace' => array(
                'table'    => $wpdb->prefix . 'flavor_marketplace_anuncios',
                'title'    => 'titulo',
                'subtitle' => "CONCAT(IFNULL(precio, 0), '€ · ', IFNULL(estado, ''))",
                'where'    => "estado = 'activo'",
                'order'    => 'created_at DESC',
            ),
            'foros' => array(
                'table'    => $wpdb->prefix . 'flavor_foros_temas',
                'title'    => 'titulo',
                'subtitle' => "CONCAT(IFNULL(respuestas_count, 0), ' respuestas · ', IFNULL(estado, ''))",
                'where'    => '1=1',
                'order'    => 'COALESCE(ultima_actividad, updated_at, created_at) DESC',
            ),
            'socios' => array(
                'table'    => $wpdb->prefix . 'flavor_socios',
                'title'    => "CONCAT('Socio #', numero_socio)",
                'subtitle' => "CONCAT(IFNULL(tipo_socio, 'consumidor'), ' · ', IFNULL(estado, 'activo'))",
                'where'    => "estado = 'activo'",
                'order'    => 'fecha_alta DESC',
            ),
            'incidencias' => array(
                'table'    => $wpdb->prefix . 'flavor_incidencias',
                'title'    => 'titulo',
                'subtitle' => "CONCAT(IFNULL(estado, 'Reportada'), ' · ', DATE_FORMAT(fecha_reporte, '%d %b'))",
                'where'    => '1=1',
                'order'    => 'fecha_reporte DESC',
            ),
            'biblioteca' => array(
                'table'    => $wpdb->prefix . 'flavor_biblioteca_libros',
                'title'    => 'titulo',
                'subtitle' => "CONCAT(IFNULL(autor, 'Sin autor'), ' · ', IFNULL(estado, 'disponible'))",
                'where'    => '1=1',
                'order'    => 'id DESC',
            ),
            'cursos' => array(
                'table'    => $wpdb->prefix . 'flavor_cursos',
                'title'    => 'titulo',
                'subtitle' => "IFNULL(descripcion, '')",
                'where'    => '1=1',
                'order'    => 'id DESC',
            ),
            'talleres' => array(
                'table'    => $wpdb->prefix . 'flavor_talleres',
                'title'    => 'titulo',
                'subtitle' => "DATE_FORMAT(fecha, '%d %b · %H:%i')",
                'where'    => '1=1',
                'order'    => 'fecha DESC',
            ),
            'reservas' => array(
                'table'    => $wpdb->prefix . 'flavor_espacios_reservas',
                'title'    => "IFNULL(motivo, 'Reserva')",
                'subtitle' => "DATE_FORMAT(fecha_inicio, '%d %b · %H:%i')",
                'where'    => "fecha_inicio >= NOW()",
                'order'    => 'fecha_inicio ASC',
            ),
        );
    }

    /**
     * Lista de columnas referenciadas en una expresión SQL.
     * Heurística simple: busca identificadores tipo word_word fuera de strings.
     * Sirve para validar contra el schema antes de ejecutar la query.
     */
    private function extract_columns_from_expression($expr) {
        // Quitar contenido de strings 'foo'/"foo"
        $clean = preg_replace("/'[^']*'/", '', $expr);
        $clean = preg_replace('/"[^"]*"/', '', $clean);

        // Funciones y palabras reservadas a ignorar
        $reservadas = array(
            'CONCAT', 'IFNULL', 'COALESCE', 'DATE_FORMAT', 'NOW',
            'SELECT', 'FROM', 'WHERE', 'ORDER', 'BY', 'AS', 'AND', 'OR',
            'NULL', 'ASC', 'DESC', 'LIMIT', 'IS', 'NOT', 'LIKE',
        );

        preg_match_all('/[a-z_][a-z0-9_]*/i', $clean, $coincidencias);
        $candidatos = array();
        foreach ($coincidencias[0] as $token) {
            $upper = strtoupper($token);
            if (in_array($upper, $reservadas, true)) continue;
            if (is_numeric($token)) continue;
            $candidatos[$token] = true;
        }
        return array_keys($candidatos);
    }

    /**
     * Comprueba que todas las columnas referenciadas por la query existen en la tabla.
     * Devuelve true si la query es ejecutable, false si falta alguna columna.
     * Cachea el schema 5 minutos por tabla.
     */
    private function validate_query_columns($tabla, $config_modulo) {
        $clave_cache = 'flavor_apk_schema_' . md5($tabla);
        $columnas_existentes = get_transient($clave_cache);

        if (false === $columnas_existentes) {
            global $wpdb;
            $describe = $wpdb->get_col("DESCRIBE " . esc_sql($tabla));
            if (!is_array($describe) || empty($describe)) {
                return false;
            }
            $columnas_existentes = array_map('strtolower', $describe);
            set_transient($clave_cache, $columnas_existentes, 5 * MINUTE_IN_SECONDS);
        }

        $todas = array_merge(
            $this->extract_columns_from_expression($config_modulo['title']),
            $this->extract_columns_from_expression($config_modulo['subtitle']),
            $this->extract_columns_from_expression($config_modulo['where']),
            $this->extract_columns_from_expression($config_modulo['order'])
        );

        foreach ($todas as $columna) {
            if (!in_array(strtolower($columna), $columnas_existentes, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * AJAX: devuelve hasta 3 items reales del módulo solicitado.
     * Si la tabla no existe, no tiene las columnas esperadas o no hay filas,
     * devuelve success=true con items=[] y el JS aplicará el fallback a mock_items.
     */
    public function ajax_module_preview_data() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $modulo_id = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
        if (empty($modulo_id)) {
            wp_send_json_success(array('items' => array(), 'source' => 'mock'));
        }

        $queries = $this->get_module_preview_queries();
        if (!isset($queries[$modulo_id])) {
            wp_send_json_success(array('items' => array(), 'source' => 'mock'));
        }

        $config_modulo = $queries[$modulo_id];

        global $wpdb;

        // Tabla puede no existir si el módulo no se ha activado nunca.
        $tabla_escapada = esc_sql($config_modulo['table']);
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $tabla_escapada)
        );
        if (!$tabla_existe) {
            wp_send_json_success(array('items' => array(), 'source' => 'mock'));
        }

        // Validar que el schema actual soporta la query (la tabla puede haber
        // sido refactorizada y mis nombres de columna estar obsoletos).
        if (!$this->validate_query_columns($tabla_escapada, $config_modulo)) {
            wp_send_json_success(array('items' => array(), 'source' => 'mock', 'reason' => 'schema_mismatch'));
        }

        $sql = sprintf(
            "SELECT %s AS titulo, %s AS subtitulo FROM %s WHERE %s ORDER BY %s LIMIT 3",
            $config_modulo['title'],
            $config_modulo['subtitle'],
            $tabla_escapada,
            $config_modulo['where'],
            $config_modulo['order']
        );

        $resultados = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($resultados) || empty($resultados)) {
            wp_send_json_success(array('items' => array(), 'source' => 'mock'));
        }

        $items = array_map(function ($fila) {
            return array(
                'title'    => wp_strip_all_tags($fila['titulo'] ?? ''),
                'subtitle' => wp_strip_all_tags($fila['subtitulo'] ?? ''),
            );
        }, $resultados);

        wp_send_json_success(array('items' => $items, 'source' => 'real'));
    }

    /**
     * Obtener módulos disponibles.
     *
     * Cada módulo lleva metadata para dos contextos:
     *  - admin: 'icon' (dashicon) usado en el grid de selección.
     *  - preview: 'material_icon' (Material Icons name), 'color' (hex que coincide
     *    con app_colors.dart de Flutter), 'description' corta y 'mock_items' con
     *    ejemplos plausibles que se renderizan en la pantalla del módulo.
     */
    private function get_available_modules() {
        return array(
            'eventos' => array(
                'name' => 'Eventos',
                'icon' => 'dashicons-calendar-alt',
                'material_icon' => 'event',
                'color' => '#9C27B0',
                'description' => 'Calendario y agenda comunitaria',
                'mock_items' => array(
                    array('title' => 'Asamblea vecinal', 'subtitle' => 'Mañana · 19:00 · Centro Cívico'),
                    array('title' => 'Mercadillo de trueque', 'subtitle' => 'Sábado · 10:00 · Plaza Mayor'),
                    array('title' => 'Taller de compostaje', 'subtitle' => '15 may · 17:30 · Huerto urbano'),
                ),
            ),
            'socios' => array(
                'name' => 'Socios',
                'icon' => 'dashicons-groups',
                'material_icon' => 'groups',
                'color' => '#3F51B5',
                'description' => 'Directorio de personas socias',
                'mock_items' => array(
                    array('title' => 'María González', 'subtitle' => 'Socia activa · 12 contribuciones'),
                    array('title' => 'Iker Etxebarria', 'subtitle' => 'Coordinador · Comisión huertos'),
                    array('title' => 'Lucía Romero', 'subtitle' => 'Voluntaria · Banco de tiempo'),
                ),
            ),
            'marketplace' => array(
                'name' => 'Marketplace',
                'icon' => 'dashicons-cart',
                'material_icon' => 'storefront',
                'color' => '#FF9800',
                'description' => 'Anuncios entre vecinos',
                'mock_items' => array(
                    array('title' => 'Bicicleta urbana', 'subtitle' => '50€ · Buen estado'),
                    array('title' => 'Clases de guitarra', 'subtitle' => '15€/hora · Domicilio'),
                    array('title' => 'Mesa de comedor', 'subtitle' => 'Gratis · A recoger'),
                ),
            ),
            'grupos_consumo' => array(
                'name' => 'Grupos Consumo',
                'icon' => 'dashicons-carrot',
                'material_icon' => 'shopping_basket',
                'color' => '#4CAF50',
                'description' => 'Pedidos colectivos de productores',
                'mock_items' => array(
                    array('title' => 'Pedido fruta ecológica', 'subtitle' => 'Cierre: viernes · 12 socios'),
                    array('title' => 'Cesta hortelana', 'subtitle' => 'Semanal · Lunes entrega'),
                    array('title' => 'Aceite cooperativa', 'subtitle' => 'Mensual · 5L · 28€'),
                ),
            ),
            'banco_tiempo' => array(
                'name' => 'Banco de Tiempo',
                'icon' => 'dashicons-clock',
                'material_icon' => 'volunteer_activism',
                'color' => '#009688',
                'description' => 'Intercambio de servicios por horas',
                'mock_items' => array(
                    array('title' => 'Acompañamiento mayores', 'subtitle' => 'Ofrecido · 2h · Disponible'),
                    array('title' => 'Clases de inglés', 'subtitle' => 'Solicitado · 1h/sem'),
                    array('title' => 'Reparaciones bici', 'subtitle' => 'Ofrecido · 30min'),
                ),
            ),
            'reservas' => array(
                'name' => 'Reservas',
                'icon' => 'dashicons-calendar',
                'material_icon' => 'event_available',
                'color' => '#00BCD4',
                'description' => 'Reserva de espacios comunes',
                'mock_items' => array(
                    array('title' => 'Sala polivalente', 'subtitle' => 'Hoy 18:00-20:00 · Confirmada'),
                    array('title' => 'Pista de pádel', 'subtitle' => 'Mañana 17:00 · Pendiente'),
                    array('title' => 'Cocina comunitaria', 'subtitle' => 'Sáb 12:00 · Reserva activa'),
                ),
            ),
            'foros' => array(
                'name' => 'Foros',
                'icon' => 'dashicons-format-chat',
                'material_icon' => 'forum',
                'color' => '#795548',
                'description' => 'Debate y deliberación',
                'mock_items' => array(
                    array('title' => '¿Carril bici en c/ Mayor?', 'subtitle' => '23 respuestas · Activo'),
                    array('title' => 'Propuesta huerto escolar', 'subtitle' => '8 respuestas · Hace 2h'),
                    array('title' => 'Recogida residuos', 'subtitle' => '15 respuestas · Hace 1d'),
                ),
            ),
            'cursos' => array(
                'name' => 'Cursos',
                'icon' => 'dashicons-welcome-learn-more',
                'material_icon' => 'school',
                'color' => '#E91E63',
                'description' => 'Formación y aprendizaje',
                'mock_items' => array(
                    array('title' => 'Permacultura nivel 1', 'subtitle' => '4 sesiones · Inscripción abierta'),
                    array('title' => 'Costura sostenible', 'subtitle' => 'Lunes · 18:00 · Gratis'),
                    array('title' => 'Soberanía alimentaria', 'subtitle' => 'Online · Plazas limitadas'),
                ),
            ),
            'talleres' => array(
                'name' => 'Talleres',
                'icon' => 'dashicons-hammer',
                'material_icon' => 'handyman',
                'color' => '#FF5722',
                'description' => 'Talleres prácticos',
                'mock_items' => array(
                    array('title' => 'Reparación bicis', 'subtitle' => 'Sáb 11:00 · 3 plazas libres'),
                    array('title' => 'Pan casero', 'subtitle' => 'Dom 10:00 · Completo'),
                    array('title' => 'Kombucha', 'subtitle' => 'Próximamente'),
                ),
            ),
            'biblioteca' => array(
                'name' => 'Biblioteca',
                'icon' => 'dashicons-book',
                'material_icon' => 'menu_book',
                'color' => '#673AB7',
                'description' => 'Préstamo de libros y recursos',
                'mock_items' => array(
                    array('title' => 'La sociedad cooperativa', 'subtitle' => 'Disponible · Sala 2'),
                    array('title' => 'Decrecimiento', 'subtitle' => 'Prestado · Devolución 12 may'),
                    array('title' => 'Permacultura práctica', 'subtitle' => 'Reservado'),
                ),
            ),
            'transparencia' => array(
                'name' => 'Transparencia',
                'icon' => 'dashicons-visibility',
                'material_icon' => 'visibility',
                'color' => '#607D8B',
                'description' => 'Cuentas y decisiones públicas',
                'mock_items' => array(
                    array('title' => 'Balance Q1 2026', 'subtitle' => 'Publicado · 28 abr'),
                    array('title' => 'Acta asamblea abril', 'subtitle' => 'Publicada · 15 abr'),
                    array('title' => 'Presupuesto 2026', 'subtitle' => 'En consulta'),
                ),
            ),
            'participacion' => array(
                'name' => 'Participación',
                'icon' => 'dashicons-megaphone',
                'material_icon' => 'how_to_vote',
                'color' => '#2196F3',
                'description' => 'Decisiones colectivas',
                'mock_items' => array(
                    array('title' => 'Presupuesto huertos', 'subtitle' => 'Votación abierta · 142 votos'),
                    array('title' => 'Nuevos horarios biblio', 'subtitle' => 'Cierra mañana · 89 votos'),
                    array('title' => 'Calle peatonal', 'subtitle' => 'Recoge apoyos · 45/100'),
                ),
            ),
            'incidencias' => array(
                'name' => 'Incidencias',
                'icon' => 'dashicons-warning',
                'material_icon' => 'report_problem',
                'color' => '#F44336',
                'description' => 'Reporta problemas en el barrio',
                'mock_items' => array(
                    array('title' => 'Farola fundida c/ Sol', 'subtitle' => 'Reportado · En curso'),
                    array('title' => 'Bache av. Libertad', 'subtitle' => 'Reportado hace 3d'),
                    array('title' => 'Contenedor lleno', 'subtitle' => 'Resuelto · Hace 2h'),
                ),
            ),
            'tramites' => array(
                'name' => 'Trámites',
                'icon' => 'dashicons-clipboard',
                'material_icon' => 'description',
                'color' => '#3F51B5',
                'description' => 'Gestiones administrativas',
                'mock_items' => array(
                    array('title' => 'Empadronamiento', 'subtitle' => 'Cita disponible'),
                    array('title' => 'Licencia obras', 'subtitle' => '15-20 días'),
                    array('title' => 'Certificado convivencia', 'subtitle' => 'Online · 24h'),
                ),
            ),
            'chat_interno' => array(
                'name' => 'Chat Interno',
                'icon' => 'dashicons-format-chat',
                'material_icon' => 'chat_bubble',
                'color' => '#4CAF50',
                'description' => 'Mensajería entre socios',
                'mock_items' => array(
                    array('title' => 'Comisión huertos', 'subtitle' => 'María: ¿Quedamos el sábado?'),
                    array('title' => 'Banco de tiempo', 'subtitle' => 'Iker: Tengo 2h disponibles'),
                    array('title' => 'Asamblea general', 'subtitle' => '12 mensajes nuevos'),
                ),
            ),
            'red_social' => array(
                'name' => 'Red Social',
                'icon' => 'dashicons-share',
                'material_icon' => 'dynamic_feed',
                'color' => '#03A9F4',
                'description' => 'Muro de la comunidad',
                'mock_items' => array(
                    array('title' => 'Lucía R.', 'subtitle' => 'Comparte: La cosecha de hoy 🌱'),
                    array('title' => 'Comisión cultura', 'subtitle' => 'Anuncia: Concierto solidario sábado'),
                    array('title' => 'Asamblea', 'subtitle' => 'Recordatorio: jueves a las 19h'),
                ),
            ),
            'carpooling' => array(
                'name' => 'Carpooling',
                'icon' => 'dashicons-car',
                'material_icon' => 'directions_car',
                'color' => '#8BC34A',
                'description' => 'Comparte coche con vecinos',
                'mock_items' => array(
                    array('title' => 'Bilbao → Vitoria', 'subtitle' => 'Mañana 8:00 · 3 plazas · 5€'),
                    array('title' => 'A la asamblea', 'subtitle' => 'Jueves 18:30 · 2 plazas'),
                    array('title' => 'Mercadillo Gernika', 'subtitle' => 'Sáb 9:00 · 4 plazas'),
                ),
            ),
            'encuestas' => array(
                'name' => 'Encuestas',
                'icon' => 'dashicons-forms',
                'material_icon' => 'poll',
                'color' => '#FF6F00',
                'description' => 'Consultas y sondeos',
                'mock_items' => array(
                    array('title' => 'Horarios biblioteca', 'subtitle' => 'Activa · 67 respuestas'),
                    array('title' => 'Tipo de huerto', 'subtitle' => 'Activa · 89 respuestas'),
                    array('title' => 'Fiestas de barrio', 'subtitle' => 'Cerrada · 156 respuestas'),
                ),
            ),
        );
    }

    /**
     * AJAX: Verificar entorno
     */
    public function ajax_check_environment() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $checks = array(
            'flutter' => $this->check_flutter(),
            'dart' => $this->check_dart(),
            'android' => $this->check_android_sdk(),
            'project' => $this->check_flutter_project(),
        );

        wp_send_json_success($checks);
    }

    /**
     * Verificar Flutter SDK
     */
    private function check_flutter() {
        if ( ! $this->can_execute_commands() ) {
            return array(
                'status' => 'error',
                'message' => 'La ejecución de comandos del sistema no está disponible',
            );
        }

        $flutter_binary = $this->find_binary( 'flutter' );
        if ( ! $flutter_binary ) {
            return array(
                'status' => 'error',
                'message' => 'Flutter no encontrado',
            );
        }

        $output = array();
        $return_var = 0;
        exec(escapeshellarg($flutter_binary) . ' --version 2>&1', $output, $return_var);

        if ($return_var === 0 && !empty($output)) {
            preg_match('/Flutter (\d+\.\d+\.\d+)/', implode(' ', $output), $matches);
            return array(
                'status' => 'ok',
                'version' => $matches[1] ?? 'Unknown',
            );
        }

        return array(
            'status' => 'error',
            'message' => 'Flutter no encontrado',
        );
    }

    /**
     * Verificar Dart SDK
     */
    private function check_dart() {
        if ( ! $this->can_execute_commands() ) {
            return array(
                'status' => 'error',
                'message' => 'La ejecución de comandos del sistema no está disponible',
            );
        }

        $dart_binary = $this->find_binary( 'dart' );
        if ( ! $dart_binary ) {
            return array(
                'status' => 'error',
                'message' => 'Dart no encontrado',
            );
        }

        $output = array();
        $return_var = 0;
        exec(escapeshellarg($dart_binary) . ' --version 2>&1', $output, $return_var);

        if ($return_var === 0 && !empty($output)) {
            preg_match('/Dart SDK version: (\d+\.\d+\.\d+)/', implode(' ', $output), $matches);
            return array(
                'status' => 'ok',
                'version' => $matches[1] ?? 'Unknown',
            );
        }

        return array(
            'status' => 'error',
            'message' => 'Dart no encontrado',
        );
    }

    /**
     * Verificar Android SDK
     */
    private function check_android_sdk() {
        $android_home = getenv('ANDROID_HOME') ?: getenv('ANDROID_SDK_ROOT');

        if ($android_home && is_dir($android_home)) {
            return array(
                'status' => 'ok',
                'path' => $android_home,
            );
        }

        return array(
            'status' => 'warning',
            'message' => 'ANDROID_HOME no configurado',
        );
    }

    /**
     * Verificar proyecto Flutter
     */
    private function check_flutter_project() {
        $pubspec = $this->flutter_path . '/pubspec.yaml';

        if (file_exists($pubspec)) {
            $content = file_get_contents($pubspec);
            preg_match('/version:\s*(\d+\.\d+\.\d+)/', $content, $matches);
            return array(
                'status' => 'ok',
                'version' => $matches[1] ?? 'Unknown',
                'path' => $this->flutter_path,
            );
        }

        return array(
            'status' => 'error',
            'message' => 'Proyecto no encontrado',
        );
    }

    /**
     * AJAX: Guardar configuración
     */
    public function ajax_save_config() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $config = array(
            'app_name' => sanitize_text_field($_POST['app_name'] ?? ''),
            'app_id' => sanitize_text_field($_POST['app_id'] ?? ''),
            'app_version' => sanitize_text_field($_POST['app_version'] ?? '1.0.0'),
            'app_build' => intval($_POST['app_build'] ?? 1),
            'app_icon' => esc_url_raw($_POST['app_icon'] ?? ''),
            'color_primary' => sanitize_hex_color($_POST['color_primary'] ?? '#2271b1'),
            'color_secondary' => sanitize_hex_color($_POST['color_secondary'] ?? '#135e96'),
            'color_accent' => sanitize_hex_color($_POST['color_accent'] ?? '#d63638'),
            'site_url' => esc_url_raw($_POST['site_url'] ?? home_url()),
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'modules' => array_map('sanitize_text_field', $_POST['modules'] ?? array()),
            'enable_offline' => !empty($_POST['enable_offline']),
            'enable_push' => !empty($_POST['enable_push']),
            'enable_biometric' => !empty($_POST['enable_biometric']),
            'min_android_version' => intval($_POST['min_android_version'] ?? 21),
            'build_type' => sanitize_text_field($_POST['build_type'] ?? 'apk'),
            'flavor' => sanitize_text_field($_POST['flavor'] ?? 'client'),
        );

        update_option('flavor_apk_config', $config);

        // Generar archivos de configuración
        $this->generate_config_files($config);

        wp_send_json_success(array(
            'message' => 'Configuración guardada',
            'config' => $config,
        ));
    }

    /**
     * Generar archivos de configuración
     */
    private function generate_config_files($config) {
        // Generar app_config.dart consumido por Flutter
        $dart_config = $this->generate_dart_config($config);
        $config_path = $this->flutter_path . '/lib/core/config/app_config.dart';
        file_put_contents($config_path, $dart_config);

        // Generar app_colors.dart consumido por Flutter
        $colors_config = $this->generate_colors_config($config);
        $colors_path = $this->flutter_path . '/lib/core/theme/app_colors.dart';
        file_put_contents($colors_path, $colors_config);

        // Actualizar pubspec.yaml version
        $this->update_pubspec_version($config['app_version'], $config['app_build']);

        // Generar build.gradle con app_id
        $this->update_android_config($config);
    }

    /**
     * Generar configuración Dart
     */
    private function generate_dart_config($config) {
        $modules = array_map(function ($module) {
            return "    '" . $this->escape_dart_string($module) . "'";
        }, $config['modules']);
        $modules_list = empty($modules) ? '' : implode(",\n", $modules);
        $app_name = $this->escape_dart_string($config['app_name']);
        $app_id = $this->escape_dart_string($config['app_id']);
        $package_name = $this->escape_dart_string(
            $config['app_id'] . ($config['flavor'] === 'admin' ? '.admin' : '.client')
        );
        $site_url = rtrim($config['site_url'], '/');
        $site_url_escaped = $this->escape_dart_string($site_url);
        $api_url = $this->escape_dart_string($site_url . '/wp-json/chat-ia-mobile/v1');
        $api_key = $this->escape_dart_string($config['api_key']);
        $client_app_name = $this->escape_dart_string($config['app_name']);
        $admin_app_name = $this->escape_dart_string($config['app_name'] . ' Admin');

        return <<<DART
// ARCHIVO GENERADO AUTOMÁTICAMENTE - NO EDITAR MANUALMENTE
// Generado por Flavor APK Builder el {$this->get_current_datetime()}

import 'package:url_launcher/url_launcher.dart';

class AppConfig {
  static const String appName = '{$app_name}';
  static const String clientAppName = '{$client_app_name}';
  static const String adminAppName = '{$admin_app_name}';
  static const String appId = '{$app_id}';
  static const String packageName = '{$package_name}';
  static const String siteUrl = '{$site_url_escaped}';
  static const String serverUrl = '{$site_url_escaped}';
  static const String apiUrl = '{$api_url}';
  static const String apiKey = '{$api_key}';
  static const String apiVersion = '2.1.0';
  static const String appVersion = '{$config['app_version']}+{$config['app_build']}';
  static const int appBuild = {$config['app_build']};
  static const String flavor = '{$this->escape_dart_string($config['flavor'])}';
  static const bool isAdminApp = {$this->bool_to_dart($config['flavor'] === 'admin')};
  static const bool isDebug = false;
  static const int httpTimeout = 30;
  static const String? userId = null;
  static const String developerName = 'Flavor';
  static const String developerEmail = 'soporte@flavorchatia.local';
  static const String developerPhone = '+34 600 000 000';
  static const String appStoreId = '';
  static const String themeMode = 'system';
  static const bool enableOffline = {$this->bool_to_dart($config['enable_offline'])};
  static const bool enablePush = {$this->bool_to_dart($config['enable_push'])};
  static const bool enableBiometric = {$this->bool_to_dart($config['enable_biometric'])};

  static const List<String> enabledModules = [
{$modules_list}
  ];

  static Future<bool> openClientApp() async {
    final uri = Uri.parse('flavorchat://client');
    if (await canLaunchUrl(uri)) {
      return launchUrl(uri, mode: LaunchMode.externalApplication);
    }
    return false;
  }

  static Future<bool> openAdminApp() async {
    final uri = Uri.parse('flavorchat://admin');
    if (await canLaunchUrl(uri)) {
      return launchUrl(uri, mode: LaunchMode.externalApplication);
    }
    return false;
  }
}
DART;
    }

    /**
     * Generar configuración de colores
     */
    private function generate_colors_config($config) {
        $primary = $this->hex_to_dart_color($config['color_primary']);
        $secondary = $this->hex_to_dart_color($config['color_secondary']);
        $accent = $this->hex_to_dart_color($config['color_accent']);
        $primary_variant = $this->adjust_hex_color($config['color_primary'], -18);
        $secondary_variant = $this->adjust_hex_color($config['color_secondary'], -18);
        $background = '0xFFFFFFFF';
        $surface = '0xFFF8FAFC';
        $error = '0xFFEF4444';
        $on_dark = '0xFF0F172A';
        $on_light = '0xFFFFFFFF';
        $primary_dark = $this->adjust_hex_color($config['color_primary'], 20);
        $secondary_dark = $this->adjust_hex_color($config['color_secondary'], 20);
        $background_dark = '0xFF0F172A';
        $surface_dark = '0xFF1E293B';
        $error_dark = '0xFFF87171';

        return <<<DART
import 'package:flutter/material.dart';

// Colores generados automáticamente
// Generado: {$this->get_current_datetime()}

class AppColors {
  // Light Theme
  static const Color primary = Color({$primary});
  static const Color primaryVariant = Color({$primary_variant});
  static const Color secondary = Color({$secondary});
  static const Color secondaryVariant = Color({$secondary_variant});
  static const Color background = Color({$background});
  static const Color surface = Color({$surface});
  static const Color error = Color({$error});
  static const Color onPrimary = Color({$on_light});
  static const Color onSecondary = Color({$on_light});
  static const Color onBackground = Color({$on_dark});
  static const Color onSurface = Color({$on_dark});
  static const Color onError = Color({$on_light});

  // Dark Theme
  static const Color primaryDark = Color({$primary_dark});
  static const Color primaryVariantDark = Color({$primary});
  static const Color secondaryDark = Color({$secondary_dark});
  static const Color secondaryVariantDark = Color({$secondary});
  static const Color backgroundDark = Color({$background_dark});
  static const Color surfaceDark = Color({$surface_dark});
  static const Color errorDark = Color({$error_dark});
  static const Color onPrimaryDark = Color({$on_dark});
  static const Color onSecondaryDark = Color({$on_dark});
  static const Color onBackgroundDark = Color(0xFFF1F5F9);
  static const Color onSurfaceDark = Color(0xFFCBD5E1);
  static const Color onErrorDark = Color({$on_dark});
}
DART;
    }

    /**
     * Convertir hex a color Dart
     */
    private function hex_to_dart_color($hex) {
        $hex = str_replace('#', '', $hex);
        return '0xFF' . strtoupper($hex);
    }

    /**
     * Ajustar un color hex sumando/restando luminosidad.
     */
    private function adjust_hex_color($hex, $amount) {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) !== 6) {
            return '0xFF3B82F6';
        }

        $channels = str_split($hex, 2);
        $adjusted = array_map(function ($channel) use ($amount) {
            $value = hexdec($channel);
            $value = max(0, min(255, $value + $amount));
            return str_pad(strtoupper(dechex($value)), 2, '0', STR_PAD_LEFT);
        }, $channels);

        return '0xFF' . implode('', $adjusted);
    }

    /**
     * Convertir bool a Dart
     */
    private function bool_to_dart($value) {
        return $value ? 'true' : 'false';
    }

    /**
     * Escapar cadenas para literales simples de Dart.
     */
    private function escape_dart_string($value) {
        $value = (string) $value;
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace("'", "\\'", $value);
        return str_replace(array("\r", "\n"), array('\\r', '\\n'), $value);
    }

    /**
     * Obtener datetime actual
     */
    private function get_current_datetime() {
        return current_time('Y-m-d H:i:s');
    }

    /**
     * Actualizar versión en pubspec.yaml
     */
    private function update_pubspec_version($version, $build) {
        $pubspec_path = $this->flutter_path . '/pubspec.yaml';
        if (!file_exists($pubspec_path)) {
            return;
        }

        $content = file_get_contents($pubspec_path);
        $content = preg_replace(
            '/version:\s*\d+\.\d+\.\d+\+\d+/',
            "version: {$version}+{$build}",
            $content
        );
        file_put_contents($pubspec_path, $content);
    }

    /**
     * Actualizar configuración Android
     */
    private function update_android_config($config) {
        $gradle_path = $this->flutter_path . '/android/app/build.gradle';
        if (!file_exists($gradle_path)) {
            return;
        }

        $content = file_get_contents($gradle_path);

        // Actualizar applicationId
        $content = preg_replace(
            '/applicationId\s*=\s*"[^"]+"/m',
            'applicationId = "' . $config['app_id'] . '"',
            $content
        );

        // Actualizar minSdk del Gradle moderno
        $content = preg_replace(
            '/minSdk\s*=\s*\d+/m',
            'minSdk = ' . $config['min_android_version'],
            $content
        );

        file_put_contents($gradle_path, $content);
    }

    /**
     * AJAX: Iniciar build
     */
    public function ajax_start_build() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        if ( ! $this->can_execute_commands() ) {
            wp_send_json_error('La ejecución de comandos del sistema no está disponible en este entorno');
        }

        $config = $this->get_saved_config();
        $build_id = 'build_' . time();
        $log_file = $this->builds_path . '/' . $build_id . '.log';

        // Crear directorio de builds
        if (!is_dir($this->builds_path)) {
            mkdir($this->builds_path, 0755, true);
        }

        // Verificar entorno
        $flutter_check = $this->check_flutter();
        if ($flutter_check['status'] !== 'ok') {
            wp_send_json_error(array(
                'message' => 'Flutter no está instalado o no está en el PATH',
                'instructions' => $this->get_manual_build_instructions($config),
            ));
        }

        // Comando de build
        $build_type = $config['build_type'] === 'appbundle' ? 'appbundle' : 'apk';
        $flavor = $config['flavor'];
        $target = $this->get_flutter_target_for_flavor($flavor);
        $flutter_binary = $this->find_binary( 'flutter' );

        if ( ! $flutter_binary ) {
            wp_send_json_error(array(
                'message' => 'Flutter no está instalado o no está en el PATH',
                'instructions' => $this->get_manual_build_instructions($config),
            ));
        }

        $flutter_path = realpath( $this->flutter_path );
        if ( false === $flutter_path || ! is_dir( $flutter_path ) ) {
            wp_send_json_error( 'Proyecto Flutter no encontrado' );
        }

        $this->prepare_keystore_for_build();

        $command = sprintf(
            'cd %s && %s build %s --flavor %s -t %s --release',
            escapeshellarg($flutter_path),
            escapeshellarg($flutter_binary),
            escapeshellarg($build_type),
            escapeshellarg($flavor),
            escapeshellarg($target)
        );

        // Guardar info del build
        $build_info = array(
            'id' => $build_id,
            'status' => 'running',
            'started_at' => current_time('mysql'),
            'config' => $config,
            'command' => $command,
        );
        update_option('flavor_current_build', $build_info);

        // Ejecutar en background
        $this->run_build_background($command, $log_file, $build_id);

        wp_send_json_success(array(
            'build_id' => $build_id,
            'message' => 'Build iniciado',
        ));
    }

    /**
     * Ejecutar build en background
     */
    private function run_build_background($command, $log_file, $build_id) {
        $log_dir = realpath( $this->builds_path );
        if ( false === $log_dir ) {
            return;
        }

        $resolved_log = $log_dir . '/' . basename( $log_file );

        // En Linux/Mac
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            exec("nohup {$command} > " . escapeshellarg( $resolved_log ) . " 2>&1 &");
        } else {
            // En Windows
            pclose(popen("start /B {$command} > " . escapeshellarg( $resolved_log ) . " 2>&1", 'r'));
        }
    }

    /**
     * AJAX: Verificar estado del build
     */
    public function ajax_check_build_status() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $build_info = get_option('flavor_current_build');
        if (!$build_info) {
            wp_send_json_error('No hay build activo');
        }

        $log_file = $this->builds_path . '/' . $build_info['id'] . '.log';
        $log_content = file_exists($log_file) ? file_get_contents($log_file) : '';

        // Verificar si terminó
        $is_complete = strpos($log_content, 'Built build/') !== false ||
                       strpos($log_content, 'BUILD SUCCESSFUL') !== false;
        $has_error = strpos($log_content, 'BUILD FAILED') !== false ||
                     strpos($log_content, 'Error:') !== false;

        if ($is_complete) {
            $build_info['status'] = 'success';
            $build_info['completed_at'] = current_time('mysql');

            // Buscar artefacto generado
            $artifact_path = $this->find_built_artifact($build_info['config']);
            if ($artifact_path) {
                $build_info['artifact_path'] = $artifact_path;
                $build_info['apk_path'] = $artifact_path;
                $build_info['apk_size'] = filesize($artifact_path);
            }

            update_option('flavor_current_build', null);
            $this->save_build_history($build_info);
        } elseif ($has_error) {
            $build_info['status'] = 'error';
            $build_info['completed_at'] = current_time('mysql');
            update_option('flavor_current_build', null);
            $this->save_build_history($build_info);
        }

        wp_send_json_success(array(
            'status' => $build_info['status'],
            'log' => $log_content,
            'progress' => $this->calculate_progress($log_content),
            'apk_path' => $build_info['apk_path'] ?? null,
        ));
    }

    /**
     * Buscar artefacto generado
     */
    private function find_built_artifact($config) {
        $flavor = $config['flavor'];
        $build_type = $config['build_type'] === 'appbundle' ? 'appbundle' : 'apk';
        $patterns = $build_type === 'appbundle'
            ? array(
                $this->flutter_path . "/build/app/outputs/bundle/{$flavor}Release/app-{$flavor}-release.aab",
                $this->flutter_path . "/build/app/outputs/bundle/release/app-release.aab",
            )
            : array(
                $this->flutter_path . "/build/app/outputs/flutter-apk/app-{$flavor}-release.apk",
                $this->flutter_path . "/build/app/outputs/apk/{$flavor}/release/app-{$flavor}-release.apk",
            );

        foreach ($patterns as $pattern) {
            if (file_exists($pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Obtener target Dart según flavor.
     */
    private function get_flutter_target_for_flavor($flavor) {
        return $flavor === 'admin' ? 'lib/main_admin.dart' : 'lib/main_client.dart';
    }

    /**
     * Preparar key.properties si existe un keystore por defecto.
     */
    private function prepare_keystore_for_build() {
        if (!class_exists('Flavor_Keystore_Manager')) {
            return;
        }

        $manager = Flavor_Keystore_Manager::get_instance();
        if (!method_exists($manager, 'generate_key_properties')) {
            return;
        }

        $key_properties = $manager->generate_key_properties();
        if ($key_properties === false || empty($key_properties)) {
            return;
        }

        $key_properties_path = $this->flutter_path . '/android/key.properties';
        file_put_contents($key_properties_path, $key_properties);
    }

    /**
     * Verifica si el entorno permite ejecutar comandos locales.
     *
     * @return bool
     */
    private function can_execute_commands() {
        if ( null !== $this->command_execution_available ) {
            return $this->command_execution_available;
        }

        $disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
        $blocked  = array( 'exec', 'shell_exec', 'popen', 'proc_open', 'passthru' );

        $this->command_execution_available = count( array_intersect( $blocked, $disabled ) ) < 1;

        return $this->command_execution_available;
    }

    /**
     * Busca un binario permitido en el sistema.
     *
     * @param string $binary Nombre del binario.
     * @return string|null
     */
    private function find_binary( $binary ) {
        if ( ! $this->can_execute_commands() ) {
            return null;
        }

        $binary = sanitize_key( $binary );
        if ( ! in_array( $binary, array( 'flutter', 'dart' ), true ) ) {
            return null;
        }

        $output = array();
        $return_var = 1;
        $command = PHP_OS_FAMILY === 'Windows'
            ? 'where ' . escapeshellarg( $binary ) . ' 2>NUL'
            : 'command -v ' . escapeshellarg( $binary ) . ' 2>/dev/null';

        exec( $command, $output, $return_var );

        if ( 0 !== $return_var || empty( $output[0] ) ) {
            return null;
        }

        $resolved = trim( (string) $output[0] );
        return $resolved !== '' ? $resolved : null;
    }

    /**
     * Calcular progreso del build
     */
    private function calculate_progress($log) {
        $stages = array(
            'Running Gradle' => 10,
            'Compiling' => 30,
            'Merging' => 50,
            'Bundling' => 70,
            'Signing' => 85,
            'Built build/' => 100,
        );

        $progress = 0;
        foreach ($stages as $stage => $value) {
            if (strpos($log, $stage) !== false) {
                $progress = max($progress, $value);
            }
        }

        return $progress;
    }

    /**
     * Guardar historial de builds
     */
    private function save_build_history($build_info) {
        $history = get_option('flavor_build_history', array());
        array_unshift($history, $build_info);
        $history = array_slice($history, 0, 20); // Mantener últimos 20
        update_option('flavor_build_history', $history);
    }

    /**
     * AJAX: Listar builds
     */
    public function ajax_list_builds() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $history = get_option('flavor_build_history', array());
        $current = get_option('flavor_current_build');

        wp_send_json_success(array(
            'current' => $current,
            'history' => $history,
        ));
    }

    /**
     * AJAX: Descargar configuración
     */
    public function ajax_download_config() {
        check_ajax_referer('flavor_apk_builder', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $config = $this->get_saved_config();
        $instructions = $this->get_manual_build_instructions($config);

        wp_send_json_success(array(
            'config' => $config,
            'dart_config' => $this->generate_dart_config($config),
            'colors_config' => $this->generate_colors_config($config),
            'instructions' => $instructions,
        ));
    }

    /**
     * Obtener instrucciones de build manual
     */
    private function get_manual_build_instructions($config) {
        $flavor = $config['flavor'];
        $build_type = $config['build_type'] === 'appbundle' ? 'appbundle' : 'apk';

        return <<<INSTRUCTIONS
# Instrucciones para compilar manualmente

## 1. Requisitos previos
- Flutter SDK >= 3.0.0
- Android SDK
- Java JDK 11+

## 2. Configuración
Los archivos de configuración han sido generados en:
- lib/core/config/app_config.dart
- lib/core/theme/app_colors.dart

## 3. Comandos de compilación

# Instalar dependencias
cd mobile-apps
flutter pub get

# Compilar artefacto
flutter build {$build_type} --flavor {$flavor} -t {$this->get_flutter_target_for_flavor($flavor)} --release

# La APK se generará en:
# build/app/outputs/flutter-apk/app-{$flavor}-release.apk

## 4. Firmar APK (si no está firmada)
# Generar keystore (solo primera vez)
keytool -genkey -v -keystore release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias release

# Firmar APK
jarsigner -verbose -sigalg SHA256withRSA -digestalg SHA-256 -keystore release-key.jks app-{$flavor}-release.apk release

INSTRUCTIONS;
    }
}

// Inicializar
Flavor_APK_Builder::get_instance();

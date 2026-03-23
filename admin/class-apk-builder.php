<?php
/**
 * APK Builder - Compilador de Apps desde Admin
 *
 * Sistema para generar APKs personalizadas desde el panel de WordPress.
 * Permite configurar branding, módulos y generar builds.
 *
 * @package Flavor_Chat_IA
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
     * Constructor
     */
    private function __construct() {
        $this->flutter_path = FLAVOR_CHAT_IA_PATH . 'mobile-apps';
        $this->builds_path = FLAVOR_CHAT_IA_PATH . 'builds';

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_apk_check_environment', array($this, 'ajax_check_environment'));
        add_action('wp_ajax_flavor_apk_save_config', array($this, 'ajax_save_config'));
        add_action('wp_ajax_flavor_apk_start_build', array($this, 'ajax_start_build'));
        add_action('wp_ajax_flavor_apk_check_build_status', array($this, 'ajax_check_build_status'));
        add_action('wp_ajax_flavor_apk_download_config', array($this, 'ajax_download_config'));
        add_action('wp_ajax_flavor_apk_list_builds', array($this, 'ajax_list_builds'));
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
            'flavor-chat-ia',
            __('Compilar APK', 'flavor-chat-ia'),
            __('Compilar APK', 'flavor-chat-ia'),
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
            FLAVOR_CHAT_IA_URL . 'admin/css/apk-builder.css',
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-apk-builder',
            FLAVOR_CHAT_IA_URL . 'admin/js/apk-builder.js',
            array('jquery', 'wp-color-picker'),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        wp_localize_script('flavor-apk-builder', 'flavorApkBuilder', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_apk_builder'),
            'i18n' => array(
                'checking' => __('Verificando...', 'flavor-chat-ia'),
                'building' => __('Compilando...', 'flavor-chat-ia'),
                'success' => __('Completado', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
                'selectIcon' => __('Seleccionar icono', 'flavor-chat-ia'),
                'confirmBuild' => __('¿Iniciar compilación? Este proceso puede tardar varios minutos.', 'flavor-chat-ia'),
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
                <?php _e('Compilador de APKs', 'flavor-chat-ia'); ?>
            </h1>

            <div class="apk-builder-layout">
                <!-- Panel izquierdo: Configuración -->
                <div class="config-panel">
                    <!-- Estado del entorno -->
                    <div class="config-section environment-check">
                        <h2><?php _e('Estado del Entorno', 'flavor-chat-ia'); ?></h2>
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
                            <?php _e('Verificar Entorno', 'flavor-chat-ia'); ?>
                        </button>
                    </div>

                    <!-- Branding -->
                    <div class="config-section">
                        <h2><?php _e('Branding', 'flavor-chat-ia'); ?></h2>
                        <form id="branding-form">
                            <div class="form-row">
                                <label for="app_name"><?php _e('Nombre de la App', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="app_name" name="app_name"
                                       value="<?php echo esc_attr($config['app_name']); ?>"
                                       placeholder="Mi App">
                            </div>

                            <div class="form-row">
                                <label for="app_id"><?php _e('ID de la App (Package Name)', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="app_id" name="app_id"
                                       value="<?php echo esc_attr($config['app_id']); ?>"
                                       placeholder="com.ejemplo.miapp">
                                <p class="description"><?php _e('Formato: com.empresa.app (solo minúsculas y puntos)', 'flavor-chat-ia'); ?></p>
                            </div>

                            <div class="form-row">
                                <label for="app_version"><?php _e('Versión', 'flavor-chat-ia'); ?></label>
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
                                <label><?php _e('Icono de la App', 'flavor-chat-ia'); ?></label>
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
                                        <?php _e('Seleccionar Icono', 'flavor-chat-ia'); ?>
                                    </button>
                                    <p class="description"><?php _e('Recomendado: 1024x1024px PNG', 'flavor-chat-ia'); ?></p>
                                </div>
                            </div>

                            <div class="form-row colors-row">
                                <div class="color-field">
                                    <label for="color_primary"><?php _e('Color Primario', 'flavor-chat-ia'); ?></label>
                                    <input type="text" id="color_primary" name="color_primary"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_primary']); ?>">
                                </div>
                                <div class="color-field">
                                    <label for="color_secondary"><?php _e('Color Secundario', 'flavor-chat-ia'); ?></label>
                                    <input type="text" id="color_secondary" name="color_secondary"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_secondary']); ?>">
                                </div>
                                <div class="color-field">
                                    <label for="color_accent"><?php _e('Color Acento', 'flavor-chat-ia'); ?></label>
                                    <input type="text" id="color_accent" name="color_accent"
                                           class="color-picker"
                                           value="<?php echo esc_attr($config['color_accent']); ?>">
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Conexión -->
                    <div class="config-section">
                        <h2><?php _e('Conexión', 'flavor-chat-ia'); ?></h2>
                        <div class="form-row">
                            <label for="site_url"><?php _e('URL del Sitio', 'flavor-chat-ia'); ?></label>
                            <input type="url" id="site_url" name="site_url"
                                   value="<?php echo esc_attr($config['site_url'] ?: home_url()); ?>">
                        </div>
                        <div class="form-row">
                            <label for="api_key"><?php _e('API Key', 'flavor-chat-ia'); ?></label>
                            <div class="api-key-field">
                                <input type="text" id="api_key" name="api_key"
                                       value="<?php echo esc_attr($config['api_key']); ?>"
                                       readonly>
                                <button type="button" id="generate-api-key" class="button">
                                    <?php _e('Generar', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Módulos -->
                    <div class="config-section">
                        <h2><?php _e('Módulos a Incluir', 'flavor-chat-ia'); ?></h2>
                        <p class="description"><?php _e('Selecciona los módulos que estarán disponibles en la app.', 'flavor-chat-ia'); ?></p>
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
                            <?php _e('Opciones Avanzadas', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </h2>
                        <div class="collapsible-content">
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_offline" name="enable_offline"
                                           <?php checked($config['enable_offline']); ?>>
                                    <?php _e('Habilitar modo offline', 'flavor-chat-ia'); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_push" name="enable_push"
                                           <?php checked($config['enable_push']); ?>>
                                    <?php _e('Habilitar notificaciones push', 'flavor-chat-ia'); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label>
                                    <input type="checkbox" id="enable_biometric" name="enable_biometric"
                                           <?php checked($config['enable_biometric']); ?>>
                                    <?php _e('Habilitar autenticación biométrica', 'flavor-chat-ia'); ?>
                                </label>
                            </div>
                            <div class="form-row">
                                <label for="min_android_version"><?php _e('Versión mínima Android', 'flavor-chat-ia'); ?></label>
                                <select id="min_android_version" name="min_android_version">
                                    <option value="21" <?php selected($config['min_android_version'], 21); ?>>Android 5.0 (API 21)</option>
                                    <option value="23" <?php selected($config['min_android_version'], 23); ?>>Android 6.0 (API 23)</option>
                                    <option value="24" <?php selected($config['min_android_version'], 24); ?>>Android 7.0 (API 24)</option>
                                    <option value="26" <?php selected($config['min_android_version'], 26); ?>>Android 8.0 (API 26)</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="build_type"><?php _e('Tipo de Build', 'flavor-chat-ia'); ?></label>
                                <select id="build_type" name="build_type">
                                    <option value="apk" <?php selected($config['build_type'], 'apk'); ?>>APK (Debug/Test)</option>
                                    <option value="appbundle" <?php selected($config['build_type'], 'appbundle'); ?>>App Bundle (Play Store)</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="flavor"><?php _e('Variante de App', 'flavor-chat-ia'); ?></label>
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
                            <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" id="download-config" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Descargar Config', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" id="start-build" class="button button-primary button-hero">
                            <span class="dashicons dashicons-hammer"></span>
                            <?php _e('Compilar APK', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

                <!-- Panel derecho: Preview y Builds -->
                <div class="preview-panel">
                    <!-- Preview -->
                    <div class="preview-section">
                        <h2><?php _e('Vista Previa', 'flavor-chat-ia'); ?></h2>
                        <div class="phone-preview">
                            <div class="phone-frame">
                                <div class="phone-notch"></div>
                                <div class="phone-screen">
                                    <div class="app-header" id="preview-header">
                                        <span class="app-title"><?php echo esc_html($config['app_name'] ?: 'Mi App'); ?></span>
                                    </div>
                                    <div class="app-content">
                                        <div class="preview-modules" id="preview-modules">
                                            <!-- Se llena dinámicamente -->
                                        </div>
                                    </div>
                                    <div class="app-navbar" id="preview-navbar">
                                        <div class="nav-item active">
                                            <span class="dashicons dashicons-admin-home"></span>
                                            <span>Inicio</span>
                                        </div>
                                        <div class="nav-item">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <span>Eventos</span>
                                        </div>
                                        <div class="nav-item">
                                            <span class="dashicons dashicons-admin-users"></span>
                                            <span>Perfil</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Builds recientes -->
                    <div class="builds-section">
                        <h2><?php _e('Builds Recientes', 'flavor-chat-ia'); ?></h2>
                        <div id="builds-list" class="builds-list">
                            <div class="loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>

                    <!-- Log de compilación -->
                    <div class="build-log-section" id="build-log-section" style="display: none;">
                        <h2>
                            <?php _e('Log de Compilación', 'flavor-chat-ia'); ?>
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
     * Obtener módulos disponibles
     */
    private function get_available_modules() {
        return array(
            'eventos' => array('name' => 'Eventos', 'icon' => 'dashicons-calendar-alt'),
            'socios' => array('name' => 'Socios', 'icon' => 'dashicons-groups'),
            'marketplace' => array('name' => 'Marketplace', 'icon' => 'dashicons-cart'),
            'grupos_consumo' => array('name' => 'Grupos Consumo', 'icon' => 'dashicons-carrot'),
            'banco_tiempo' => array('name' => 'Banco de Tiempo', 'icon' => 'dashicons-clock'),
            'reservas' => array('name' => 'Reservas', 'icon' => 'dashicons-calendar'),
            'foros' => array('name' => 'Foros', 'icon' => 'dashicons-format-chat'),
            'cursos' => array('name' => 'Cursos', 'icon' => 'dashicons-welcome-learn-more'),
            'talleres' => array('name' => 'Talleres', 'icon' => 'dashicons-hammer'),
            'biblioteca' => array('name' => 'Biblioteca', 'icon' => 'dashicons-book'),
            'transparencia' => array('name' => 'Transparencia', 'icon' => 'dashicons-visibility'),
            'participacion' => array('name' => 'Participación', 'icon' => 'dashicons-megaphone'),
            'incidencias' => array('name' => 'Incidencias', 'icon' => 'dashicons-warning'),
            'tramites' => array('name' => 'Trámites', 'icon' => 'dashicons-clipboard'),
            'chat_interno' => array('name' => 'Chat Interno', 'icon' => 'dashicons-format-chat'),
            'red_social' => array('name' => 'Red Social', 'icon' => 'dashicons-share'),
            'carpooling' => array('name' => 'Carpooling', 'icon' => 'dashicons-car'),
            'encuestas' => array('name' => 'Encuestas', 'icon' => 'dashicons-forms'),
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
        $output = array();
        $return_var = 0;
        exec('flutter --version 2>&1', $output, $return_var);

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
        $output = array();
        $return_var = 0;
        exec('dart --version 2>&1', $output, $return_var);

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
        // Generar app_config.dart
        $dart_config = $this->generate_dart_config($config);
        $config_path = $this->flutter_path . '/lib/core/config/generated_config.dart';
        file_put_contents($config_path, $dart_config);

        // Generar colors.dart
        $colors_config = $this->generate_colors_config($config);
        $colors_path = $this->flutter_path . '/lib/core/theme/generated_colors.dart';
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
        $modules_list = implode("', '", $config['modules']);

        return <<<DART
// ARCHIVO GENERADO AUTOMÁTICAMENTE - NO EDITAR MANUALMENTE
// Generado por Flavor APK Builder el {$this->get_current_datetime()}

/// Configuración generada desde WordPress Admin
class GeneratedConfig {
  /// Nombre de la aplicación
  static const String appName = '{$config['app_name']}';

  /// ID de la aplicación (package name)
  static const String appId = '{$config['app_id']}';

  /// Versión de la aplicación
  static const String appVersion = '{$config['app_version']}';

  /// Número de build
  static const int appBuild = {$config['app_build']};

  /// URL del sitio WordPress
  static const String siteUrl = '{$config['site_url']}';

  /// API Key para autenticación
  static const String apiKey = '{$config['api_key']}';

  /// Módulos habilitados
  static const List<String> enabledModules = ['{$modules_list}'];

  /// Opciones de funcionalidad
  static const bool enableOffline = {$this->bool_to_dart($config['enable_offline'])};
  static const bool enablePush = {$this->bool_to_dart($config['enable_push'])};
  static const bool enableBiometric = {$this->bool_to_dart($config['enable_biometric'])};

  /// Variante de la app
  static const String flavor = '{$config['flavor']}';
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

        return <<<DART
// ARCHIVO GENERADO AUTOMÁTICAMENTE - NO EDITAR MANUALMENTE
// Generado por Flavor APK Builder el {$this->get_current_datetime()}

import 'package:flutter/material.dart';

/// Colores generados desde WordPress Admin
class GeneratedColors {
  static const Color primary = Color({$primary});
  static const Color secondary = Color({$secondary});
  static const Color accent = Color({$accent});

  static const MaterialColor primarySwatch = MaterialColor(
    {$primary},
    <int, Color>{
      50: Color(0xFFE3F2FD),
      100: Color(0xFFBBDEFB),
      200: Color(0xFF90CAF9),
      300: Color(0xFF64B5F6),
      400: Color(0xFF42A5F5),
      500: Color({$primary}),
      600: Color(0xFF1E88E5),
      700: Color(0xFF1976D2),
      800: Color(0xFF1565C0),
      900: Color(0xFF0D47A1),
    },
  );
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
     * Convertir bool a Dart
     */
    private function bool_to_dart($value) {
        return $value ? 'true' : 'false';
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
            '/applicationId\s+"[^"]+"/m',
            'applicationId "' . $config['app_id'] . '"',
            $content
        );

        // Actualizar minSdkVersion
        $content = preg_replace(
            '/minSdkVersion\s+\d+/m',
            'minSdkVersion ' . $config['min_android_version'],
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
        $command = "cd {$this->flutter_path} && flutter build {$build_type} --flavor {$flavor} --release 2>&1";

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
        // En Linux/Mac
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            exec("nohup {$command} > {$log_file} 2>&1 &");
        } else {
            // En Windows
            pclose(popen("start /B {$command} > {$log_file} 2>&1", 'r'));
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

            // Buscar APK generada
            $apk_path = $this->find_built_apk($build_info['config']);
            if ($apk_path) {
                $build_info['apk_path'] = $apk_path;
                $build_info['apk_size'] = filesize($apk_path);
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
     * Buscar APK generada
     */
    private function find_built_apk($config) {
        $flavor = $config['flavor'];
        $patterns = array(
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
- lib/core/config/generated_config.dart
- lib/core/theme/generated_colors.dart

## 3. Comandos de compilación

# Instalar dependencias
cd mobile-apps
flutter pub get

# Compilar APK de cliente
flutter build {$build_type} --flavor {$flavor} --release

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

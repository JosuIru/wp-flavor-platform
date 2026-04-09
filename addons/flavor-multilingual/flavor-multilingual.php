<?php
/**
 * Addon Name: Flavor Multilingual
 * Description: Sistema de traducción multiidioma para Flavor Platform con integración de IA para traducciones automáticas. Soporta español, inglés, euskera, catalán, gallego y más idiomas.
 * Version: 1.3.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Chat IA 3.0.0+
 *
 * @package     FlavorMultilingual
 * @copyright   2026 Gailu Labs
 * @license     GPL-2.0+
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del addon
define('FLAVOR_MULTILINGUAL_VERSION', '1.4.0');
define('FLAVOR_MULTILINGUAL_PATH', plugin_dir_path(__FILE__));
define('FLAVOR_MULTILINGUAL_URL', plugin_dir_url(__FILE__));
define('FLAVOR_MULTILINGUAL_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del addon Flavor Multilingual
 */
class Flavor_Multilingual {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual|null
     */
    private static $instance = null;

    /**
     * Indica si Flavor Chat IA está activo
     *
     * @var bool
     */
    private $flavor_active = false;

    /**
     * Idiomas soportados por defecto
     *
     * @var array
     */
    public static $default_languages = array(
        'es' => array(
            'locale'      => 'es_ES',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'flag'        => 'es.svg',
        ),
        'en' => array(
            'locale'      => 'en_US',
            'name'        => 'English',
            'native_name' => 'English',
            'flag'        => 'en.svg',
        ),
        'eu' => array(
            'locale'      => 'eu',
            'name'        => 'Basque',
            'native_name' => 'Euskara',
            'flag'        => 'eu.svg',
        ),
        'ca' => array(
            'locale'      => 'ca',
            'name'        => 'Catalan',
            'native_name' => 'Català',
            'flag'        => 'ca.svg',
        ),
        'gl' => array(
            'locale'      => 'gl_ES',
            'name'        => 'Galician',
            'native_name' => 'Galego',
            'flag'        => 'gl.svg',
        ),
        // Lenguas minoritarias de España
        'an' => array(
            'locale'      => 'an',
            'name'        => 'Aragonese',
            'native_name' => 'Aragonés',
            'flag'        => 'an.svg',
        ),
        'ast' => array(
            'locale'      => 'ast',
            'name'        => 'Asturian',
            'native_name' => 'Asturianu',
            'flag'        => 'ast.svg',
        ),
        'oc' => array(
            'locale'      => 'oc',
            'name'        => 'Occitan',
            'native_name' => 'Occitan / Aranés',
            'flag'        => 'oc.svg',
        ),
        // Lenguas celtas
        'cy' => array(
            'locale'      => 'cy',
            'name'        => 'Welsh',
            'native_name' => 'Cymraeg',
            'flag'        => 'cy.svg',
        ),
        'ga' => array(
            'locale'      => 'ga',
            'name'        => 'Irish',
            'native_name' => 'Gaeilge',
            'flag'        => 'ga.svg',
        ),
        'gd' => array(
            'locale'      => 'gd',
            'name'        => 'Scottish Gaelic',
            'native_name' => 'Gàidhlig',
            'flag'        => 'gd.svg',
        ),
        'br' => array(
            'locale'      => 'br',
            'name'        => 'Breton',
            'native_name' => 'Brezhoneg',
            'flag'        => 'br.svg',
        ),
        // Otras lenguas europeas minoritarias
        'co' => array(
            'locale'      => 'co',
            'name'        => 'Corsican',
            'native_name' => 'Corsu',
            'flag'        => 'co.svg',
        ),
        'lb' => array(
            'locale'      => 'lb',
            'name'        => 'Luxembourgish',
            'native_name' => 'Lëtzebuergesch',
            'flag'        => 'lb.svg',
        ),
        'fy' => array(
            'locale'      => 'fy',
            'name'        => 'Frisian',
            'native_name' => 'Frysk',
            'flag'        => 'fy.svg',
        ),
        'rm' => array(
            'locale'      => 'rm',
            'name'        => 'Romansh',
            'native_name' => 'Rumantsch',
            'flag'        => 'rm.svg',
        ),
        'fr' => array(
            'locale'      => 'fr_FR',
            'name'        => 'French',
            'native_name' => 'Français',
            'flag'        => 'fr.svg',
        ),
        'de' => array(
            'locale'      => 'de_DE',
            'name'        => 'German',
            'native_name' => 'Deutsch',
            'flag'        => 'de.svg',
        ),
        'it' => array(
            'locale'      => 'it_IT',
            'name'        => 'Italian',
            'native_name' => 'Italiano',
            'flag'        => 'it.svg',
        ),
        'pt' => array(
            'locale'      => 'pt_PT',
            'name'        => 'Portuguese',
            'native_name' => 'Português',
            'flag'        => 'pt.svg',
        ),
        'zh' => array(
            'locale'      => 'zh_CN',
            'name'        => 'Chinese',
            'native_name' => '中文',
            'flag'        => 'zh.svg',
        ),
        'ja' => array(
            'locale'      => 'ja',
            'name'        => 'Japanese',
            'native_name' => '日本語',
            'flag'        => 'ja.svg',
        ),
        'ar' => array(
            'locale'      => 'ar',
            'name'        => 'Arabic',
            'native_name' => 'العربية',
            'flag'        => 'ar.svg',
            'rtl'         => true,
        ),
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Verificar si plugins_loaded ya se ejecutó (cuando se carga desde Addon Manager)
        if (did_action('plugins_loaded')) {
            // plugins_loaded ya pasó, ejecutar directamente
            $this->check_dependencies();
            $this->init();
        } else {
            // plugins_loaded aún no se ejecutó, añadir hooks
            add_action('plugins_loaded', array($this, 'check_dependencies'), 5);
            add_action('plugins_loaded', array($this, 'init'), 20);
        }

        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Verifica dependencias del addon
     */
    public function check_dependencies() {
        $this->flavor_active = defined('FLAVOR_CHAT_IA_VERSION');

        if (!$this->flavor_active) {
            add_action('admin_notices', array($this, 'missing_flavor_notice'));
        }
    }

    /**
     * Muestra aviso si Flavor no está activo
     */
    public function missing_flavor_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong>Flavor Multilingual</strong> requiere
                <strong>Flavor Chat IA</strong> para funcionar.
                Por favor, activa el plugin principal.
            </p>
        </div>
        <?php
    }

    /**
     * Inicializa el addon
     */
    public function init() {
        if (!$this->flavor_active) {
            return;
        }

        // Cargar traducciones
        load_plugin_textdomain(
            'flavor-multilingual',
            false,
            dirname(FLAVOR_MULTILINGUAL_BASENAME) . '/languages'
        );

        // Cargar clases
        $this->load_classes();

        // Verificar y crear tablas si no existen (para activación desde Addon Manager)
        $this->maybe_create_tables();

        // Inicializar componentes
        $this->init_components();

        // Hooks de integración
        $this->register_hooks();
    }

    /**
     * Crea las tablas si no existen
     */
    private function maybe_create_tables() {
        global $wpdb;

        $table_languages = $wpdb->prefix . 'flavor_languages';

        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_languages}'") === $table_languages;

        if (!$table_exists) {
            $this->create_tables();
            $this->set_default_options();
        } else {
            // Activar idiomas cooficiales de España si no están activos (migración)
            $this->activate_coofficial_languages();

            // Sincronizar nuevos idiomas añadidos en actualizaciones
            $this->sync_new_languages();
        }
    }

    /**
     * Sincroniza los nuevos idiomas del array $default_languages con la base de datos
     * Para instalaciones existentes que actualizan el addon
     */
    private function sync_new_languages() {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_languages';

        // Obtener códigos de idiomas existentes en la base de datos
        $idiomas_existentes = $wpdb->get_col("SELECT code FROM {$table}");

        // Si no hay diferencias, salir
        $idiomas_defecto = array_keys(self::$default_languages);
        $idiomas_faltantes = array_diff($idiomas_defecto, $idiomas_existentes);

        if (empty($idiomas_faltantes)) {
            return;
        }

        // Obtener el último sort_order
        $ultimo_orden = $wpdb->get_var("SELECT MAX(sort_order) FROM {$table}");
        $orden_siguiente = $ultimo_orden !== null ? intval($ultimo_orden) + 1 : 0;

        // Añadir idiomas que no existen
        $idiomas_nuevos_insertados = 0;
        foreach ($idiomas_faltantes as $code) {
            $lang = self::$default_languages[$code];
            $wpdb->insert($table, array(
                'code'        => $code,
                'locale'      => $lang['locale'],
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'],
                'is_default'  => 0,
                'is_active'   => 0, // Los nuevos idiomas se añaden como inactivos
                'is_rtl'      => isset($lang['rtl']) ? 1 : 0,
                'sort_order'  => $orden_siguiente++,
            ));
            $idiomas_nuevos_insertados++;
        }

        // Log para depuración
        if ($idiomas_nuevos_insertados > 0) {
            error_log(sprintf(
                '[Flavor Multilingual] Sincronizados %d idiomas nuevos: %s',
                $idiomas_nuevos_insertados,
                implode(', ', $idiomas_faltantes)
            ));
        }
    }

    /**
     * Activa los idiomas cooficiales de España por defecto
     */
    private function activate_coofficial_languages() {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_languages';
        $idiomas_cooficiales = array('eu', 'ca', 'gl');

        // Solo activar si no se ha hecho antes (verificar con opción)
        if (get_option('flavor_ml_coofficial_activated')) {
            return;
        }

        foreach ($idiomas_cooficiales as $code) {
            $wpdb->update(
                $table,
                array('is_active' => 1),
                array('code' => $code)
            );
        }

        update_option('flavor_ml_coofficial_activated', true);
    }

    /**
     * Carga las clases del addon
     */
    private function load_classes() {
        $includes_path = FLAVOR_MULTILINGUAL_PATH . 'includes/';

        // Core
        require_once $includes_path . 'class-multilingual-core.php';
        require_once $includes_path . 'class-language-manager.php';
        require_once $includes_path . 'class-translation-storage.php';
        require_once $includes_path . 'class-ai-translator.php';
        require_once $includes_path . 'class-url-manager.php';
        require_once $includes_path . 'class-compatibility.php';
        require_once $includes_path . 'class-slug-translator.php';
        require_once $includes_path . 'class-content-duplicator.php';
        require_once $includes_path . 'class-po-mo-handler.php';
        require_once $includes_path . 'class-wpml-compatibility.php';

        // Sistema de caché y memoria de traducción
        require_once $includes_path . 'class-translation-cache.php';
        require_once $includes_path . 'class-translation-memory.php';
        require_once $includes_path . 'class-object-cache.php';

        // Sistema de roles y permisos
        require_once $includes_path . 'class-translation-roles.php';

        // XLIFF import/export
        require_once $includes_path . 'class-xliff-handler.php';

        // Geolocalización
        require_once $includes_path . 'class-geolocation.php';

        // Notificaciones por email
        require_once $includes_path . 'class-translation-notifications.php';

        // Admin
        if (is_admin()) {
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-admin-settings.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-metabox-translations.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-string-manager.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-taxonomy-translations.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-menu-translations.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-translation-dashboard.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-side-by-side-editor.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-progress-widget.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-posts-column.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'admin/class-translation-comments.php';
        }

        // Frontend
        if (!is_admin()) {
            // Cargar primero language-switcher porque define Flavor_Language_Switcher_Widget
            require_once FLAVOR_MULTILINGUAL_PATH . 'frontend/class-language-switcher.php';
            require_once FLAVOR_MULTILINGUAL_PATH . 'frontend/class-frontend-controller.php';
        }

        // API
        require_once FLAVOR_MULTILINGUAL_PATH . 'api/class-translation-api.php';

        // Integraciones con plugins de terceros
        $this->load_integrations();
    }

    /**
     * Carga las integraciones con plugins de terceros
     */
    private function load_integrations() {
        $integrations_path = FLAVOR_MULTILINGUAL_PATH . 'integrations/';

        // ACF - Advanced Custom Fields
        if (class_exists('ACF') || function_exists('acf_get_field_groups')) {
            require_once $integrations_path . 'class-acf-integration.php';
        }

        // WooCommerce
        if (class_exists('WooCommerce') || defined('WC_VERSION')) {
            require_once $integrations_path . 'class-woocommerce-integration.php';
        }

        // Visual Builder Pro (Flavor)
        if (class_exists('Flavor_VBP_Editor') || defined('FLAVOR_VBP_VERSION')) {
            require_once $integrations_path . 'class-vbp-integration.php';
        }

        // Media Library Integration (siempre cargar)
        require_once $integrations_path . 'class-media-integration.php';

        // Sitemap Integration (siempre cargar, detecta plugins SEO internamente)
        require_once $integrations_path . 'class-sitemap-integration.php';

        // Cargar integraciones siempre (detectan internamente si el plugin está activo)
        // Esto permite que se carguen cuando los plugins se activan después
        add_action('plugins_loaded', array($this, 'late_load_integrations'), 100);
    }

    /**
     * Carga tardía de integraciones (para plugins que se cargan después)
     */
    public function late_load_integrations() {
        $integrations_path = FLAVOR_MULTILINGUAL_PATH . 'integrations/';

        // ACF - si no se cargó antes y ahora está disponible
        if (!class_exists('Flavor_ML_ACF_Integration') && (class_exists('ACF') || function_exists('acf_get_field_groups'))) {
            require_once $integrations_path . 'class-acf-integration.php';
            Flavor_ML_ACF_Integration::get_instance();
        }

        // WooCommerce - si no se cargó antes y ahora está disponible
        if (!class_exists('Flavor_ML_WooCommerce_Integration') && class_exists('WooCommerce')) {
            require_once $integrations_path . 'class-woocommerce-integration.php';
            Flavor_ML_WooCommerce_Integration::get_instance();
        }

        // VBP - si no se cargó antes y ahora está disponible
        if (!class_exists('Flavor_ML_VBP_Integration') && (class_exists('Flavor_VBP_Editor') || defined('FLAVOR_VBP_VERSION'))) {
            require_once $integrations_path . 'class-vbp-integration.php';
            Flavor_ML_VBP_Integration::get_instance();
        }
    }

    /**
     * Inicializa componentes
     */
    private function init_components() {
        // Core siempre
        Flavor_Multilingual_Core::get_instance();
        Flavor_Slug_Translator::get_instance();
        Flavor_WPML_Compatibility::get_instance();
        Flavor_PO_MO_Handler::get_instance();

        // Sistema de caché (siempre activo para rendimiento)
        Flavor_Translation_Cache::get_instance();
        Flavor_ML_Object_Cache::get_instance();

        // Memoria de traducción y glosario
        $translation_memory = Flavor_Translation_Memory::get_instance();
        $translation_memory->maybe_create_tables();

        // Sistema de roles y permisos
        Flavor_Translation_Roles::get_instance();

        // XLIFF import/export
        Flavor_XLIFF_Handler::get_instance();

        // Geolocalización (siempre activa)
        Flavor_ML_Geolocation::get_instance();

        // Sistema de notificaciones por email
        Flavor_Translation_Notifications::get_instance();

        // Admin
        if (is_admin()) {
            Flavor_Multilingual_Admin_Settings::get_instance();
            Flavor_Multilingual_Metabox::get_instance();
            Flavor_String_Manager::get_instance();
            Flavor_Taxonomy_Translations::get_instance();
            Flavor_Menu_Translations::get_instance();
            Flavor_Content_Duplicator::get_instance();
            Flavor_Translation_Dashboard::get_instance();
            Flavor_Side_By_Side_Editor::get_instance();
            Flavor_Translation_Progress_Widget::get_instance();
            Flavor_Translation_Posts_Column::get_instance();
            Flavor_Translation_Comments::get_instance();
        }

        // Frontend
        if (!is_admin()) {
            Flavor_Multilingual_Frontend::get_instance();
        }

        // API REST
        Flavor_Translation_API::get_instance();

        // Inicializar integraciones
        $this->init_integrations();
    }

    /**
     * Inicializa las integraciones con plugins de terceros
     */
    private function init_integrations() {
        // ACF Integration
        if (class_exists('Flavor_ML_ACF_Integration')) {
            Flavor_ML_ACF_Integration::get_instance();
        }

        // WooCommerce Integration
        if (class_exists('Flavor_ML_WooCommerce_Integration')) {
            Flavor_ML_WooCommerce_Integration::get_instance();
        }

        // VBP Integration
        if (class_exists('Flavor_ML_VBP_Integration')) {
            Flavor_ML_VBP_Integration::get_instance();
        }

        // Media Library Integration
        if (class_exists('Flavor_ML_Media_Integration')) {
            Flavor_ML_Media_Integration::get_instance();
        }

        // Sitemap Integration
        if (class_exists('Flavor_ML_Sitemap_Integration')) {
            Flavor_ML_Sitemap_Integration::get_instance();
        }
    }

    /**
     * Registra hooks de integración
     */
    private function register_hooks() {
        // Los filtros de contenido están en Flavor_Multilingual_Frontend
        // Los hreflang tags están en Flavor_URL_Manager

        // Admin bar language switcher
        add_action('admin_bar_menu', array($this, 'admin_bar_language_switcher'), 100);

        // AJAX para guardar traducciones desde metabox
        add_action('wp_ajax_flavor_ml_save_translation', array($this, 'ajax_save_translation'));

        // Registrar menú del addon en el sistema de menús de Flavor Platform
        add_filter('flavor_wp_submenu_por_vista', array($this, 'register_admin_menu_visibility'));
    }

    /**
     * Registra el menú del addon en las vistas de Flavor Platform
     *
     * @param array $menus_por_vista Menús por vista
     * @return array
     */
    public function register_admin_menu_visibility($menus_por_vista) {
        // Añadir el menú de multilingual a la vista admin
        if (isset($menus_por_vista['admin']) && is_array($menus_por_vista['admin'])) {
            $menus_por_vista['admin'][] = 'flavor-multilingual';
        }

        return $menus_por_vista;
    }

    /**
     * AJAX: Guardar traducción manual desde metabox
     */
    public function ajax_save_translation() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $excerpt = sanitize_textarea_field($_POST['excerpt'] ?? '');
        $status = sanitize_key($_POST['status'] ?? 'draft');

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();

        // Guardar título
        if (!empty($title)) {
            $storage->save_translation('post', $post_id, $lang, 'title', $title, array(
                'status' => $status,
                'auto'   => false,
            ));
        }

        // Guardar excerpt
        if (!empty($excerpt)) {
            $storage->save_translation('post', $post_id, $lang, 'excerpt', $excerpt, array(
                'status' => $status,
                'auto'   => false,
            ));
        }

        wp_send_json_success(array(
            'message' => __('Traducción guardada', 'flavor-multilingual'),
        ));
    }

    /**
     * Añade selector de idioma a la admin bar
     *
     * @param WP_Admin_Bar $admin_bar
     */
    public function admin_bar_language_switcher($admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current = $core->get_current_language();
        $languages = $core->get_active_languages();

        if (count($languages) < 2) {
            return;
        }

        $current_lang = $languages[$current] ?? null;
        $flag = $current_lang ? $current_lang['flag'] : '🌐';

        $admin_bar->add_node(array(
            'id'    => 'flavor-language',
            'title' => sprintf('🌐 %s', strtoupper($current)),
            'href'  => '#',
            'meta'  => array(
                'class' => 'flavor-language-switcher',
            ),
        ));

        foreach ($languages as $code => $lang) {
            $admin_bar->add_node(array(
                'id'     => 'flavor-language-' . $code,
                'parent' => 'flavor-language',
                'title'  => sprintf('%s %s', $lang['native_name'], $code === $current ? '✓' : ''),
                'href'   => add_query_arg('lang', $code),
            ));
        }
    }

    /**
     * Activación del addon
     */
    public function activate() {
        // Crear tablas
        $this->create_tables();

        // Configuración por defecto
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivación del addon
     */
    public function deactivate() {
        // Limpiar transients
        delete_transient('flavor_multilingual_languages');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crea las tablas de base de datos
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de idiomas
        $table_languages = $wpdb->prefix . 'flavor_languages';
        $sql_languages = "CREATE TABLE IF NOT EXISTS {$table_languages} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(10) NOT NULL,
            locale VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100) NOT NULL,
            flag VARCHAR(10) DEFAULT NULL,
            is_default TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            is_rtl TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) {$charset_collate};";

        // Tabla de traducciones
        $table_translations = $wpdb->prefix . 'flavor_translations';
        $sql_translations = "CREATE TABLE IF NOT EXISTS {$table_translations} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type VARCHAR(50) NOT NULL,
            object_id BIGINT(20) UNSIGNED NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            translation LONGTEXT,
            is_auto_translated TINYINT(1) DEFAULT 0,
            translator VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY object_translation (object_type, object_id, language_code, field_name),
            KEY language_code (language_code),
            KEY object_type_id (object_type, object_id),
            KEY status (status)
        ) {$charset_collate};";

        // Tabla de strings
        $table_strings = $wpdb->prefix . 'flavor_string_translations';
        $sql_strings = "CREATE TABLE IF NOT EXISTS {$table_strings} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            string_key VARCHAR(32) NOT NULL,
            original_string TEXT NOT NULL,
            domain VARCHAR(100) DEFAULT FLAVOR_PLATFORM_TEXT_DOMAIN,
            context VARCHAR(255) DEFAULT NULL,
            language_code VARCHAR(10) NOT NULL,
            translation TEXT,
            is_auto_translated TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY string_lang (string_key, language_code),
            KEY domain (domain)
        ) {$charset_collate};";

        // Tabla de slugs traducidos
        $table_slugs = $wpdb->prefix . 'flavor_translated_slugs';
        $sql_slugs = "CREATE TABLE IF NOT EXISTS {$table_slugs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type VARCHAR(50) NOT NULL DEFAULT 'post',
            object_id BIGINT(20) UNSIGNED NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            original_slug VARCHAR(200) NOT NULL,
            translated_slug VARCHAR(200) NOT NULL,
            post_type VARCHAR(50) DEFAULT NULL,
            taxonomy VARCHAR(50) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY object_lang (object_type, object_id, language_code),
            KEY translated_slug (translated_slug),
            KEY original_slug (original_slug),
            KEY language_code (language_code)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_languages);
        dbDelta($sql_translations);
        dbDelta($sql_strings);
        dbDelta($sql_slugs);

        // Insertar idiomas por defecto si la tabla está vacía
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_languages}");
        if ($count == 0) {
            $this->insert_default_languages();
        }
    }

    /**
     * Inserta idiomas por defecto
     */
    private function insert_default_languages() {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_languages';
        $wp_locale = get_locale();
        $default_code = substr($wp_locale, 0, 2);

        // Idiomas activos por defecto: español, inglés y cooficiales de España
        $idiomas_activos_defecto = array('es', 'en', 'eu', 'ca', 'gl');

        $order = 0;
        foreach (self::$default_languages as $code => $lang) {
            $wpdb->insert($table, array(
                'code'        => $code,
                'locale'      => $lang['locale'],
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'],
                'is_default'  => ($code === $default_code) ? 1 : 0,
                'is_active'   => in_array($code, $idiomas_activos_defecto) ? 1 : 0,
                'is_rtl'      => isset($lang['rtl']) ? 1 : 0,
                'sort_order'  => $order++,
            ));
        }
    }

    /**
     * Establece opciones por defecto
     */
    private function set_default_options() {
        $defaults = array(
            'url_mode'              => 'parameter', // parameter, directory, subdomain
            'auto_detect_browser'   => true,
            'remember_user_lang'    => true,
            'auto_redirect'         => false,
            'ai_engine'             => 'claude',
            'auto_translate_new'    => false,
            'mark_auto_for_review'  => true,
            'hide_untranslated'     => false,
        );

        if (!get_option('flavor_multilingual_settings')) {
            add_option('flavor_multilingual_settings', $defaults);
        }
    }

    /**
     * Obtiene una opción del addon
     *
     * @param string $key     Clave de la opción
     * @param mixed  $default Valor por defecto
     * @return mixed
     */
    public static function get_option($key, $default = null) {
        $options = get_option('flavor_multilingual_settings', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
}

/**
 * Registra el addon en el sistema de Flavor Platform
 */
add_action('flavor_register_addons', function() {
    if (class_exists('Flavor_Addon_Manager')) {
        Flavor_Addon_Manager::register_addon('flavor-multilingual', array(
            'name'           => 'Flavor Multilingual',
            'description'    => __('Sistema de traducción multiidioma con IA para traducciones automáticas. Soporta español, inglés, euskera, catalán, gallego y más idiomas.', 'flavor-multilingual'),
            'version'        => FLAVOR_MULTILINGUAL_VERSION,
            'author'         => 'Gailu Labs',
            'author_uri'     => 'https://gailu.net',
            'requires_core'  => '3.0.0',
            'requires'       => array(),
            'init_callback'  => array('Flavor_Multilingual', 'get_instance'),
            'settings_page'  => 'admin.php?page=flavor-multilingual',
            'icon'           => 'dashicons-translation',
            'file'           => __FILE__,
            'is_premium'     => false,
            'documentation_url' => '',
        ));
    }
});

// Fallback: Inicializar directamente si el addon manager no existe
add_action('plugins_loaded', function() {
    if (!class_exists('Flavor_Addon_Manager') && defined('FLAVOR_CHAT_IA_VERSION')) {
        Flavor_Multilingual::get_instance();
    }
}, 25);

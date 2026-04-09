<?php
/**
 * Gestor de Addons para Flavor Platform
 *
 * Sistema centralizado para registrar, activar y gestionar addons
 * independientes que extienden la funcionalidad del plugin base.
 *
 * @package FlavorPlatform
 * @subpackage Core
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Singleton para gestionar addons
 *
 * Los addons son plugins independientes que se conectan a Flavor Platform
 * para proporcionar funcionalidad adicional de forma modular.
 *
 * Características:
 * - Registro automático de addons
 * - Verificación de dependencias
 * - Activación/desactivación individual
 * - Actualización de metadatos
 * - Hooks para extensibilidad
 *
 * @since 3.0.0
 */
class Flavor_Addon_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_Manager
     */
    private static $instancia = null;

    /**
     * Addons registrados
     *
     * Formato:
     * [
     *     'slug-addon' => [
     *         'name' => 'Nombre del Addon',
     *         'description' => 'Descripción',
     *         'version' => '1.0.0',
     *         'author' => 'Autor',
     *         'requires_core' => '3.0.0',
     *         'requires' => [...],  // Dependencias
     *         'init_callback' => 'callable',
     *         'settings_page' => 'url',
     *         'icon' => 'dashicons-admin-plugins',
     *         'file' => '/path/to/addon.php',
     *         'is_premium' => false
     *     ]
     * ]
     *
     * @var array
     */
    private $addons_registrados = [];

    /**
     * Addons activos (slugs)
     *
     * @var array
     */
    private $addons_activos = [];

    /**
     * Addons cargados en esta ejecución
     *
     * @var array
     */
    private $addons_cargados = [];

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        // Cargar addons activos desde la base de datos
        $this->addons_activos = get_option('flavor_active_addons', []);

        // Hooks para integración
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_Manager
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks de WordPress
     *
     * @return void
     */
    private function init_hooks() {
        // Hook para escanear addons y disparar registro
        add_action('plugins_loaded', [$this, 'fire_registration_hook'], 5);

        // Hook para cargar addons activos
        add_action('plugins_loaded', [$this, 'load_active_addons'], 10);

        // Hook para admin notices
        add_action('admin_notices', [$this, 'show_addon_notices']);

        // Página de administración DESACTIVADA - Ahora se gestiona centralmente desde Admin_Menu_Manager
        // add_action('admin_menu', [$this, 'register_admin_menu'], 90);

        // Handler de acciones de formulario
        add_action('admin_init', [$this, 'handle_addon_actions']);

        // AJAX handlers
        add_action('wp_ajax_flavor_toggle_addon', [$this, 'ajax_toggle_addon']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Escanea el directorio addons/ y carga los archivos principales de cada addon
     *
     * Busca subdirectorios en FLAVOR_CHAT_IA_PATH/addons/ que contengan
     * un archivo PHP con el mismo nombre que el directorio (convención WordPress).
     *
     * @return void
     * @since 3.0.0
     */
    public function scan_addons_directory() {
        $directorio_addons = FLAVOR_CHAT_IA_PATH . 'addons/';

        if (!is_dir($directorio_addons)) {
            return;
        }

        $subdirectorios = glob($directorio_addons . '*', GLOB_ONLYDIR);

        if (empty($subdirectorios)) {
            return;
        }

        $addons_encontrados = 0;

        foreach ($subdirectorios as $directorio_addon) {
            $nombre_addon = basename($directorio_addon);
            $archivo_principal = $directorio_addon . '/' . $nombre_addon . '.php';

            if (file_exists($archivo_principal)) {
                require_once $archivo_principal;
                $addons_encontrados++;
            }
        }

        if ($addons_encontrados > 0) {
            flavor_chat_ia_log(sprintf(
                'Escaneado directorio addons/: %d addons encontrados',
                $addons_encontrados
            ));
        }
    }

    /**
     * Dispara el hook para que los addons se registren
     *
     * @return void
     */
    public function fire_registration_hook() {
        // Primero escanear el directorio addons/ para incluir los archivos
        $this->scan_addons_directory();

        /**
         * Hook para que los addons se registren
         *
         * Los addons deben usar:
         * Flavor_Addon_Manager::register_addon('slug', $config);
         *
         * @since 3.0.0
         */
        do_action('flavor_register_addons');

        flavor_chat_ia_log(sprintf(
            'Sistema de addons iniciado. %d addons registrados',
            count($this->addons_registrados)
        ));
    }

    /**
     * Registra un addon
     *
     * @param string $slug_addon Slug único del addon
     * @param array $configuracion Configuración del addon:
     *     [
     *         'name' => 'Nombre del Addon' (requerido),
     *         'description' => 'Descripción',
     *         'version' => '1.0.0' (requerido),
     *         'author' => 'Nombre Autor',
     *         'author_uri' => 'https://...',
     *         'requires_core' => '3.0.0', // Versión mínima de Flavor Platform
     *         'requires' => [...],  // Array de dependencias
     *         'init_callback' => 'callable', // Función a llamar al cargar
     *         'settings_page' => 'admin.php?page=...', // URL de configuración
     *         'icon' => 'dashicons-admin-plugins',
     *         'file' => __FILE__, // Archivo principal del addon
     *         'is_premium' => false,
     *         'documentation_url' => 'https://...'
     *     ]
     * @return bool|WP_Error True si se registró correctamente, WP_Error si no
     */
    public static function register_addon($slug_addon, $configuracion) {
        $instancia = self::get_instance();

        // Validar slug
        if (empty($slug_addon) || !is_string($slug_addon)) {
            return new WP_Error('slug_invalido', __('El slug del addon es inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Validar campos requeridos
        if (empty($configuracion['name'])) {
            return new WP_Error('nombre_requerido', __('El addon debe tener un nombre.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if (empty($configuracion['version'])) {
            return new WP_Error('version_requerida', __('El addon debe especificar una versión.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Evitar duplicados
        if (isset($instancia->addons_registrados[$slug_addon])) {
            flavor_chat_ia_log("Addon duplicado intentó registrarse: {$slug_addon}", 'warning');
            return new WP_Error('addon_duplicado', sprintf(
                __('El addon "%s" ya está registrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $slug_addon
            ));
        }

        // Verificar compatibilidad con el core
        if (!empty($configuracion['requires_core'])) {
            if (version_compare(FLAVOR_CHAT_IA_VERSION, $configuracion['requires_core'], '<')) {
                return new WP_Error('version_core_incompatible', sprintf(
                    __('El addon "%s" requiere Flavor Platform versión %s o superior (versión actual: %s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $configuracion['name'],
                    $configuracion['requires_core'],
                    FLAVOR_CHAT_IA_VERSION
                ));
            }
        }

        // Configuración por defecto
        $configuracion_default = [
            'description' => '',
            'author' => '',
            'author_uri' => '',
            'requires_core' => '3.0.0',
            'requires' => [],
            'init_callback' => null,
            'settings_page' => '',
            'icon' => 'dashicons-admin-plugins',
            'file' => '',
            'is_premium' => false,
            'documentation_url' => ''
        ];

        $configuracion = wp_parse_args($configuracion, $configuracion_default);

        // Registrar el addon
        $instancia->addons_registrados[$slug_addon] = $configuracion;

        flavor_chat_ia_log("Addon registrado: {$slug_addon} v{$configuracion['version']}");

        /**
         * Acción después de registrar un addon
         *
         * @param string $slug_addon Slug del addon
         * @param array $configuracion Configuración del addon
         * @since 3.0.0
         */
        do_action('flavor_addon_registered', $slug_addon, $configuracion);

        return true;
    }

    /**
     * Carga todos los addons activos
     *
     * @return void
     */
    public function load_active_addons() {
        foreach ($this->addons_activos as $slug_addon) {
            $this->load_addon($slug_addon);
        }

        flavor_chat_ia_log(sprintf(
            '%d addons cargados de %d activos',
            count($this->addons_cargados),
            count($this->addons_activos)
        ));
    }

    /**
     * Carga un addon específico
     *
     * @param string $slug_addon Slug del addon
     * @return bool|WP_Error True si se cargó correctamente, WP_Error si no
     */
    public function load_addon($slug_addon) {
        // Verificar que esté registrado
        if (!isset($this->addons_registrados[$slug_addon])) {
            flavor_chat_ia_log("Addon no registrado: {$slug_addon}", 'warning');
            return new WP_Error('addon_no_registrado', sprintf(
                __('El addon "%s" no está registrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $slug_addon
            ));
        }

        // Evitar cargar dos veces
        if (isset($this->addons_cargados[$slug_addon])) {
            return true;
        }

        $configuracion = $this->addons_registrados[$slug_addon];

        // Verificar dependencias
        if (!empty($configuracion['requires'])) {
            $verificacion = Flavor_Dependency_Checker::check(
                $configuracion['requires'],
                $configuracion['name']
            );

            if (is_wp_error($verificacion)) {
                flavor_chat_ia_log("Addon {$slug_addon} falló verificación de dependencias: " . $verificacion->get_error_message(), 'error');
                return $verificacion;
            }
        }

        // Llamar al callback de inicialización
        if (!empty($configuracion['init_callback']) && is_callable($configuracion['init_callback'])) {
            call_user_func($configuracion['init_callback']);
        }

        // Marcar como cargado
        $this->addons_cargados[$slug_addon] = true;

        flavor_chat_ia_log("Addon cargado: {$slug_addon}");

        /**
         * Acción después de cargar un addon
         *
         * @param string $slug_addon Slug del addon
         * @param array $configuracion Configuración del addon
         * @since 3.0.0
         */
        do_action('flavor_addon_loaded', $slug_addon, $configuracion);

        return true;
    }

    /**
     * Activa un addon
     *
     * @param string $slug_addon Slug del addon
     * @return bool|WP_Error True si se activó, WP_Error si no
     */
    public function activate_addon($slug_addon) {
        // Verificar que esté registrado
        if (!isset($this->addons_registrados[$slug_addon])) {
            return new WP_Error('addon_no_registrado', sprintf(
                __('El addon "%s" no está registrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $slug_addon
            ));
        }

        // Verificar si ya está activo
        if (in_array($slug_addon, $this->addons_activos)) {
            return true;
        }

        $configuracion = $this->addons_registrados[$slug_addon];

        // Verificar dependencias
        if (!empty($configuracion['requires'])) {
            $verificacion = Flavor_Dependency_Checker::check(
                $configuracion['requires'],
                $configuracion['name']
            );

            if (is_wp_error($verificacion)) {
                return $verificacion;
            }
        }

        // Agregar a la lista de activos
        $this->addons_activos[] = $slug_addon;
        update_option('flavor_active_addons', $this->addons_activos);

        // Cargar el addon inmediatamente
        $resultado = $this->load_addon($slug_addon);

        if (is_wp_error($resultado)) {
            // Revertir activación si falla la carga
            $this->deactivate_addon($slug_addon);
            return $resultado;
        }

        /**
         * Acción después de activar un addon
         *
         * @param string $slug_addon Slug del addon
         * @since 3.0.0
         */
        do_action('flavor_addon_activated', $slug_addon);

        flavor_chat_ia_log("Addon activado: {$slug_addon}");

        return true;
    }

    /**
     * Desactiva un addon
     *
     * @param string $slug_addon Slug del addon
     * @return bool True si se desactivó
     */
    public function deactivate_addon($slug_addon) {
        // Remover de la lista de activos
        $clave = array_search($slug_addon, $this->addons_activos);
        if ($clave !== false) {
            unset($this->addons_activos[$clave]);
            $this->addons_activos = array_values($this->addons_activos); // Re-indexar
            update_option('flavor_active_addons', $this->addons_activos);
        }

        // Remover de la lista de cargados
        if (isset($this->addons_cargados[$slug_addon])) {
            unset($this->addons_cargados[$slug_addon]);
        }

        /**
         * Acción después de desactivar un addon
         *
         * @param string $slug_addon Slug del addon
         * @since 3.0.0
         */
        do_action('flavor_addon_deactivated', $slug_addon);

        flavor_chat_ia_log("Addon desactivado: {$slug_addon}");

        return true;
    }

    /**
     * Verifica si un addon está activo
     *
     * @param string $slug_addon Slug del addon
     * @return bool True si está activo
     */
    public static function is_addon_active($slug_addon) {
        $instancia = self::get_instance();
        return in_array($slug_addon, $instancia->addons_activos);
    }

    /**
     * Verifica si un addon está cargado
     *
     * @param string $slug_addon Slug del addon
     * @return bool True si está cargado
     */
    public static function is_addon_loaded($slug_addon) {
        $instancia = self::get_instance();
        return isset($instancia->addons_cargados[$slug_addon]);
    }

    /**
     * Verifica si un addon está registrado
     *
     * @param string $slug_addon Slug del addon
     * @return bool True si está registrado
     */
    public static function is_addon_registered($slug_addon) {
        $instancia = self::get_instance();
        return isset($instancia->addons_registrados[$slug_addon]);
    }

    /**
     * Obtiene información de un addon
     *
     * @param string $slug_addon Slug del addon
     * @return array|null Configuración del addon o null si no existe
     */
    public static function get_addon_info($slug_addon) {
        $instancia = self::get_instance();
        return isset($instancia->addons_registrados[$slug_addon])
            ? $instancia->addons_registrados[$slug_addon]
            : null;
    }

    /**
     * Obtiene todos los addons registrados
     *
     * @return array Array de addons registrados
     */
    public static function get_registered_addons() {
        $instancia = self::get_instance();
        return $instancia->addons_registrados;
    }

    /**
     * Obtiene todos los addons activos
     *
     * @return array Array de slugs de addons activos
     */
    public static function get_active_addons() {
        $instancia = self::get_instance();
        return $instancia->addons_activos;
    }

    /**
     * Obtiene todos los addons cargados
     *
     * @return array Array de slugs de addons cargados
     */
    public static function get_loaded_addons() {
        $instancia = self::get_instance();
        return array_keys($instancia->addons_cargados);
    }

    /**
     * Muestra avisos de admin relacionados con addons
     *
     * @return void
     */
    public function show_addon_notices() {
        // Verificar addons con dependencias no satisfechas
        foreach ($this->addons_activos as $slug_addon) {
            if (!isset($this->addons_cargados[$slug_addon]) && isset($this->addons_registrados[$slug_addon])) {
                $configuracion = $this->addons_registrados[$slug_addon];

                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Error en addon:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ';
                echo sprintf(
                    esc_html__('El addon "%s" está activo pero no pudo cargarse. Verifica las dependencias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($configuracion['name'])
                );
                echo '</p></div>';
            }
        }
    }

    /**
     * Obtiene estadísticas de addons
     *
     * @return array Estadísticas
     */
    public static function get_stats() {
        $instancia = self::get_instance();

        return [
            'total_registrados' => count($instancia->addons_registrados),
            'total_activos' => count($instancia->addons_activos),
            'total_cargados' => count($instancia->addons_cargados),
            'premium_count' => count(array_filter($instancia->addons_registrados, function($addon) {
                return !empty($addon['is_premium']);
            }))
        ];
    }

    /**
     * Registra el menú de administración
     *
     * @return void
     */
    public function register_admin_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-addons',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderiza la página de administración de addons
     *
     * @return void
     */
    public function render_admin_page() {
        $addons = $this->addons_registrados;
        $total_activos = count($this->addons_activos);
        $total_addons = count($addons);

        ?>
        <div class="wrap flavor-addons-page">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-plugins" style="margin-right: 10px;"></span>
                <?php esc_html_e('Gestión de Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <div class="flavor-addons-summary">
                <span class="flavor-addons-count">
                    <?php printf(
                        esc_html__('%d de %d addons activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $total_activos,
                        $total_addons
                    ); ?>
                </span>
            </div>

            <hr class="wp-header-end">

            <?php if (empty($addons)): ?>
                <div class="flavor-addons-empty">
                    <span class="dashicons dashicons-plugins-checked"></span>
                    <h2><?php esc_html_e('No hay addons instalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php esc_html_e('Los addons extienden las funcionalidades de Flavor Chat IA.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p><?php esc_html_e('Coloca los addons en la carpeta /addons/ del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-addons-grid">
                    <?php foreach ($addons as $addon_id => $addon):
                        $esta_activo = in_array($addon_id, $this->addons_activos, true);
                        $esta_cargado = isset($this->addons_cargados[$addon_id]);
                    ?>
                        <div class="flavor-addon-card <?php echo $esta_activo ? 'addon-active' : 'addon-inactive'; ?>" data-addon-id="<?php echo esc_attr($addon_id); ?>">
                            <div class="addon-header">
                                <span class="addon-icon dashicons <?php echo esc_attr($addon['icon'] ?? 'dashicons-admin-plugins'); ?>"></span>
                                <div class="addon-info">
                                    <h3 class="addon-name">
                                        <?php echo esc_html($addon['name']); ?>
                                        <?php if (!empty($addon['is_premium'])): ?>
                                            <span class="addon-premium-badge"><?php esc_html_e('PRO', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="addon-version">v<?php echo esc_html($addon['version']); ?></span>
                                </div>
                                <div class="addon-status">
                                    <?php if ($esta_activo): ?>
                                        <span class="status-badge status-active"><?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive"><?php esc_html_e('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="addon-body">
                                <p class="addon-description"><?php echo esc_html($addon['description']); ?></p>

                                <?php if (!empty($addon['author'])): ?>
                                    <p class="addon-author">
                                        <strong><?php esc_html_e('Autor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                        <?php if (!empty($addon['author_uri'])): ?>
                                            <a href="<?php echo esc_url($addon['author_uri']); ?>" target="_blank">
                                                <?php echo esc_html($addon['author']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html($addon['author']); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($addon['requires_core']) && version_compare(FLAVOR_CHAT_IA_VERSION, $addon['requires_core'], '<')): ?>
                                    <div class="addon-requirements-error">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php printf(
                                            esc_html__('Requiere Flavor Chat IA %s o superior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            esc_html($addon['requires_core'])
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="addon-footer">
                                <div class="addon-actions">
                                    <?php if ($esta_activo): ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('flavor_addon_action', 'addon_nonce'); ?>
                                            <input type="hidden" name="addon_id" value="<?php echo esc_attr($addon_id); ?>">
                                            <input type="hidden" name="addon_action" value="deactivate">
                                            <button type="submit" class="button addon-deactivate">
                                                <?php esc_html_e('Desactivar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        </form>
                                        <?php if (!empty($addon['settings_page'])): ?>
                                            <a href="<?php echo esc_url($addon['settings_page']); ?>" class="button button-primary">
                                                <?php esc_html_e('Configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('flavor_addon_action', 'addon_nonce'); ?>
                                            <input type="hidden" name="addon_id" value="<?php echo esc_attr($addon_id); ?>">
                                            <input type="hidden" name="addon_action" value="activate">
                                            <button type="submit" class="button button-primary addon-activate">
                                                <?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($addon['documentation_url'])): ?>
                                        <a href="<?php echo esc_url($addon['documentation_url']); ?>" class="button addon-docs" target="_blank" title="<?php esc_attr_e('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-book-alt"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-addons-page { max-width: 1400px; }
        .flavor-addons-summary { margin: 1rem 0; color: #666; }
        .flavor-addons-empty { text-align: center; padding: 4rem 2rem; background: #fff; border: 1px solid #ddd; border-radius: 8px; }
        .flavor-addons-empty .dashicons { font-size: 4rem; width: 4rem; height: 4rem; color: #ccc; }
        .flavor-addons-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
        .flavor-addon-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
        .flavor-addon-card.addon-active { border-color: #4caf50; box-shadow: 0 0 0 1px #4caf50; }
        .addon-header { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border-bottom: 1px solid #eee; background: #f9f9f9; }
        .addon-icon { font-size: 2rem; width: 2rem; height: 2rem; color: #2271b1; }
        .addon-info { flex: 1; }
        .addon-name { margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .addon-version { font-size: 0.8rem; color: #666; }
        .addon-premium-badge { background: linear-gradient(135deg, #ff9800, #f57c00); color: #fff; padding: 0.15rem 0.4rem; border-radius: 3px; font-size: 0.65rem; font-weight: 600; }
        .status-badge { padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #f5f5f5; color: #666; }
        .addon-body { padding: 1rem; flex: 1; }
        .addon-description { margin: 0 0 0.75rem; font-size: 0.9rem; color: #555; line-height: 1.5; }
        .addon-author { font-size: 0.85rem; color: #666; margin: 0; }
        .addon-requirements-error { background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 0.5rem; margin-top: 0.75rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
        .addon-requirements-error .dashicons { color: #ff9800; }
        .addon-footer { padding: 0.75rem 1rem; border-top: 1px solid #eee; background: #f9f9f9; }
        .addon-actions { display: flex; gap: 0.5rem; }
        .addon-docs .dashicons { vertical-align: middle; margin: 0; }
        @media (max-width: 782px) {
            .flavor-addons-grid { grid-template-columns: 1fr; }
        }
        </style>
        <?php
    }

    /**
     * Maneja las acciones de addon desde formularios
     *
     * @return void
     */
    public function handle_addon_actions() {
        if (!isset($_POST['addon_action']) || !isset($_POST['addon_id'])) {
            return;
        }

        if (!check_admin_referer('flavor_addon_action', 'addon_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $addon_id = sanitize_text_field($_POST['addon_id']);
        $action = sanitize_text_field($_POST['addon_action']);

        $resultado = null;

        switch ($action) {
            case 'activate':
                $resultado = $this->activate_addon($addon_id);
                break;

            case 'deactivate':
                $resultado = $this->deactivate_addon($addon_id);
                break;
        }

        // Guardar resultado para mostrar
        if (is_wp_error($resultado)) {
            set_transient('flavor_addon_action_result', [
                'success' => false,
                'message' => $resultado->get_error_message(),
            ], 30);
        } else {
            $mensaje = $action === 'activate'
                ? sprintf(__('Addon "%s" activado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN), $this->addons_registrados[$addon_id]['name'] ?? $addon_id)
                : sprintf(__('Addon "%s" desactivado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN), $this->addons_registrados[$addon_id]['name'] ?? $addon_id);

            set_transient('flavor_addon_action_result', [
                'success' => true,
                'message' => $mensaje,
            ], 30);
        }

        // Redirigir
        wp_redirect(admin_url('admin.php?page=flavor-addons'));
        exit;
    }

    /**
     * AJAX: Alternar estado de addon
     *
     * @return void
     */
    public function ajax_toggle_addon() {
        check_ajax_referer('flavor_addon_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $addon_id = sanitize_text_field($_POST['addon_id'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? 'toggle');

        if (empty($addon_id)) {
            wp_send_json_error(['message' => __('ID de addon requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($action === 'activate' || (!in_array($addon_id, $this->addons_activos) && $action === 'toggle')) {
            $resultado = $this->activate_addon($addon_id);
        } else {
            $resultado = $this->deactivate_addon($addon_id);
        }

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Operación completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'is_active' => in_array($addon_id, $this->addons_activos),
        ]);
    }

    /**
     * Registra rutas REST API
     *
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/addons', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_addons'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/addons/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_addon'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/addons/(?P<id>[a-zA-Z0-9_-]+)/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_activate_addon'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/addons/(?P<id>[a-zA-Z0-9_-]+)/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_deactivate_addon'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST: Obtener todos los addons
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_addons($request) {
        $addons = [];
        foreach ($this->addons_registrados as $id => $addon) {
            $addons[$id] = array_merge($addon, [
                'id' => $id,
                'is_active' => in_array($id, $this->addons_activos, true),
                'is_loaded' => isset($this->addons_cargados[$id]),
            ]);
        }

        return new WP_REST_Response([
            'addons' => $addons,
            'stats' => self::get_stats(),
        ], 200);
    }

    /**
     * REST: Obtener un addon
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_addon($request) {
        $addon_id = $request['id'];

        if (!isset($this->addons_registrados[$addon_id])) {
            return new WP_REST_Response(['message' => 'Addon not found'], 404);
        }

        $addon = $this->addons_registrados[$addon_id];
        $addon['id'] = $addon_id;
        $addon['is_active'] = in_array($addon_id, $this->addons_activos, true);
        $addon['is_loaded'] = isset($this->addons_cargados[$addon_id]);

        return new WP_REST_Response($addon, 200);
    }

    /**
     * REST: Activar addon
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_activate_addon($request) {
        $resultado = $this->activate_addon($request['id']);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Addon activado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * REST: Desactivar addon
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_deactivate_addon($request) {
        $resultado = $this->deactivate_addon($request['id']);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Addon desactivado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }
}

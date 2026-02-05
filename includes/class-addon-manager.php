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
            return new WP_Error('slug_invalido', __('El slug del addon es inválido.', 'flavor-chat-ia'));
        }

        // Validar campos requeridos
        if (empty($configuracion['name'])) {
            return new WP_Error('nombre_requerido', __('El addon debe tener un nombre.', 'flavor-chat-ia'));
        }

        if (empty($configuracion['version'])) {
            return new WP_Error('version_requerida', __('El addon debe especificar una versión.', 'flavor-chat-ia'));
        }

        // Evitar duplicados
        if (isset($instancia->addons_registrados[$slug_addon])) {
            flavor_chat_ia_log("Addon duplicado intentó registrarse: {$slug_addon}", 'warning');
            return new WP_Error('addon_duplicado', sprintf(
                __('El addon "%s" ya está registrado.', 'flavor-chat-ia'),
                $slug_addon
            ));
        }

        // Verificar compatibilidad con el core
        if (!empty($configuracion['requires_core'])) {
            if (version_compare(FLAVOR_CHAT_IA_VERSION, $configuracion['requires_core'], '<')) {
                return new WP_Error('version_core_incompatible', sprintf(
                    __('El addon "%s" requiere Flavor Platform versión %s o superior (versión actual: %s).', 'flavor-chat-ia'),
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
                __('El addon "%s" no está registrado.', 'flavor-chat-ia'),
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
                __('El addon "%s" no está registrado.', 'flavor-chat-ia'),
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
                echo '<p><strong>' . esc_html__('Error en addon:', 'flavor-chat-ia') . '</strong> ';
                echo sprintf(
                    esc_html__('El addon "%s" está activo pero no pudo cargarse. Verifica las dependencias.', 'flavor-chat-ia'),
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
}

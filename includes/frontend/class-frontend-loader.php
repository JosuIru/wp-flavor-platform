<?php
/**
 * Cargador de controladores frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que carga y gestiona los controladores frontend
 */
class Flavor_Frontend_Loader {

    /**
     * Instancia singleton
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Controladores cargados
     *
     * @var array
     */
    private $controllers = [];

    /**
     * Mapeo de módulos a controladores
     *
     * @var array
     */
    private $module_map = [
        'espacios-comunes' => 'Flavor_Frontend_Espacios_Comunes_Controller',
        'ayuda-vecinal' => 'Flavor_Frontend_Ayuda_Vecinal_Controller',
        'huertos-urbanos' => 'Flavor_Frontend_Huertos_Urbanos_Controller',
        'biblioteca' => 'Flavor_Frontend_Biblioteca_Controller',
        'cursos' => 'Flavor_Frontend_Cursos_Controller',
        'podcast' => 'Flavor_Frontend_Podcast_Controller',
        'radio' => 'Flavor_Frontend_Radio_Controller',
        'bicicletas' => 'Flavor_Frontend_Bicicletas_Controller',
        'reciclaje' => 'Flavor_Frontend_Reciclaje_Controller',
        'tienda-local' => 'Flavor_Frontend_Tienda_Local_Controller',
        'incidencias' => 'Flavor_Frontend_Incidencias_Controller',
        'grupos-consumo' => 'Flavor_Grupos_Consumo_Controller',
        'banco-tiempo' => 'Flavor_Banco_Tiempo_Controller',
        'ayuntamiento' => 'Flavor_Ayuntamiento_Controller',
        'comunidades' => 'Flavor_Comunidades_Controller',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return self
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
        $this->load_dependencies();
        $this->init_controllers();

        // Hook para flush rewrite rules cuando sea necesario
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 999);
    }

    /**
     * Carga las dependencias (clase base y controladores)
     */
    private function load_dependencies() {
        $base_path = FLAVOR_CHAT_IA_PATH . 'includes/frontend/';
        $controllers_path = $base_path . 'controllers/';

        // Cargar clase base
        require_once $base_path . 'class-frontend-controller-base.php';

        // Cargar shortcode de landing pages
        require_once $base_path . 'class-landing-shortcode.php';

        // Cargar sistema de CRUD dinámico
        require_once $base_path . 'class-dynamic-crud.php';

        // Cargar sistema de páginas dinámicas
        require_once $base_path . 'class-dynamic-pages.php';

        // Cargar búsqueda avanzada social
        require_once $base_path . 'class-social-search.php';

        // Cargar portal unificado (sistema de layouts)
        require_once $base_path . 'class-unified-portal.php';

        // Cargar todos los controladores
        foreach ($this->module_map as $slug => $class) {
            $file = $controllers_path . 'class-' . $slug . '-controller.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Inicializa los controladores activos
     */
    private function init_controllers() {
        // Inicializar sistemas dinámicos primero
        if (class_exists('Flavor_Dynamic_CRUD')) {
            Flavor_Dynamic_CRUD::get_instance();
        }

        if (class_exists('Flavor_Dynamic_Pages')) {
            Flavor_Dynamic_Pages::get_instance();
        }

        if (class_exists('Flavor_Unified_Portal')) {
            Flavor_Unified_Portal::get_instance();
        }

        // Obtener módulos activos desde la configuración
        $settings = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $settings['active_modules'] ?? [];

        // Si no hay módulos configurados, cargar todos para desarrollo
        if (empty($modulos_activos)) {
            $modulos_activos = array_keys($this->module_map);
        }

        foreach ($this->module_map as $slug => $class) {
            // Verificar si el módulo está activo
            $module_id = str_replace('-', '_', $slug);

            // Cargar el controlador si la clase existe
            if (class_exists($class)) {
                $this->controllers[$slug] = $class::get_instance();
            }
        }
    }

    /**
     * Flush rewrite rules si es necesario
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('flavor_frontend_flush_rewrite') === 'yes') {
            flush_rewrite_rules();
            delete_option('flavor_frontend_flush_rewrite');
        }
    }

    /**
     * Marca que se deben actualizar las rewrite rules
     */
    public static function schedule_flush_rewrite() {
        update_option('flavor_frontend_flush_rewrite', 'yes');
    }

    /**
     * Obtiene todos los controladores cargados
     *
     * @return array
     */
    public function get_controllers() {
        return $this->controllers;
    }

    /**
     * Obtiene un controlador específico
     *
     * @param string $slug Slug del módulo
     * @return Flavor_Frontend_Controller_Base|null
     */
    public function get_controller($slug) {
        return $this->controllers[$slug] ?? null;
    }

    /**
     * Verifica si un módulo tiene controlador frontend
     *
     * @param string $slug Slug del módulo
     * @return bool
     */
    public function has_controller($slug) {
        return isset($this->controllers[$slug]);
    }

    /**
     * Obtiene las URLs base de todos los módulos
     *
     * @return array
     */
    public function get_module_urls() {
        $urls = [];
        foreach (array_keys($this->module_map) as $slug) {
            $urls[$slug] = home_url('/' . $slug . '/');
        }
        return $urls;
    }

    /**
     * Registra un nuevo controlador dinámicamente
     *
     * @param string $slug Slug del módulo
     * @param string $class Nombre de la clase del controlador
     */
    public function register_controller($slug, $class) {
        if (class_exists($class) && !isset($this->controllers[$slug])) {
            $this->module_map[$slug] = $class;
            $this->controllers[$slug] = $class::get_instance();
        }
    }
}

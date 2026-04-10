<?php
/**
 * Cargador de Componentes del Orchestrator
 *
 * Registra automaticamente todos los componentes disponibles
 *
 * @package FlavorPlatform
 * @subpackage Orchestrator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Components_Loader
 *
 * Carga y registra los componentes del Template Orchestrator
 */
class Flavor_Components_Loader {

    /**
     * Instancia singleton
     *
     * @var Flavor_Components_Loader|null
     */
    private static $instancia = null;

    /**
     * Directorio de componentes
     *
     * @var string
     */
    private $directorio_componentes;

    /**
     * Componentes cargados
     *
     * @var array
     */
    private $componentes_cargados = [];

    /**
     * Mapeo de nombres de componente a clases
     *
     * @var array
     */
    private $mapeo_componentes = [
        'modulos'       => [
            'archivo' => 'class-module-activator.php',
            'clase'   => 'Flavor_Module_Activator',
        ],
        'tablas'        => [
            'archivo' => 'class-table-installer.php',
            'clase'   => 'Flavor_Table_Installer',
        ],
        'paginas'       => [
            'archivo' => 'class-page-generator.php',
            'clase'   => 'Flavor_Page_Generator',
        ],
        'landing'       => [
            'archivo' => 'class-landing-builder.php',
            'clase'   => 'Flavor_Landing_Builder',
        ],
        'configuracion' => [
            'archivo' => 'class-config-applier.php',
            'clase'   => 'Flavor_Config_Applier',
        ],
        'demo'          => [
            'archivo' => 'class-demo-loader.php',
            'clase'   => 'Flavor_Demo_Loader',
        ],
        'site_transformer' => [
            'archivo' => 'class-site-transformer.php',
            'clase'   => 'Flavor_Site_Transformer',
        ],
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Components_Loader
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->directorio_componentes = dirname(__FILE__) . '/components/';
        $this->init();
    }

    /**
     * Inicializa el loader
     */
    private function init() {
        // Cargar interface base
        $this->cargar_interface();

        // Registrar filtro para proporcionar componentes al orchestrator
        add_filter('flavor_template_components', [$this, 'obtener_componentes']);

        // Hook para cargar componentes temprano
        add_action('plugins_loaded', [$this, 'cargar_todos_los_componentes'], 5);
    }

    /**
     * Carga la interface base
     */
    private function cargar_interface() {
        $archivo_interface = dirname(__FILE__) . '/interface-template-component.php';

        if (file_exists($archivo_interface)) {
            require_once $archivo_interface;
        }
    }

    /**
     * Carga todos los componentes disponibles
     */
    public function cargar_todos_los_componentes() {
        foreach ($this->mapeo_componentes as $nombre => $datos) {
            $this->cargar_componente($nombre);
        }
    }

    /**
     * Carga un componente especifico
     *
     * @param string $nombre Nombre del componente
     * @return bool True si se cargo correctamente
     */
    public function cargar_componente($nombre) {
        if (isset($this->componentes_cargados[$nombre])) {
            return true;
        }

        if (!isset($this->mapeo_componentes[$nombre])) {
            return false;
        }

        $datos = $this->mapeo_componentes[$nombre];
        $ruta_archivo = $this->directorio_componentes . $datos['archivo'];

        if (!file_exists($ruta_archivo)) {
            if (function_exists('flavor_platform_log')) {
                flavor_platform_log(
                    sprintf('Componente no encontrado: %s en %s', $nombre, $ruta_archivo),
                    'warning'
                );
            }
            return false;
        }

        require_once $ruta_archivo;

        if (!class_exists($datos['clase'])) {
            if (function_exists('flavor_platform_log')) {
                flavor_platform_log(
                    sprintf('Clase de componente no encontrada: %s', $datos['clase']),
                    'warning'
                );
            }
            return false;
        }

        // Instanciar componente
        $this->componentes_cargados[$nombre] = new $datos['clase']();

        return true;
    }

    /**
     * Obtiene todos los componentes cargados
     *
     * @param array $componentes Componentes existentes (del filtro)
     * @return array
     */
    public function obtener_componentes($componentes = []) {
        // Asegurar que todos estan cargados
        $this->cargar_todos_los_componentes();

        // Combinar con componentes existentes (permitir extension)
        return array_merge($componentes, $this->componentes_cargados);
    }

    /**
     * Obtiene un componente especifico
     *
     * @param string $nombre Nombre del componente
     * @return Flavor_Template_Component_Interface|null
     */
    public function obtener_componente($nombre) {
        if (!isset($this->componentes_cargados[$nombre])) {
            $this->cargar_componente($nombre);
        }

        return $this->componentes_cargados[$nombre] ?? null;
    }

    /**
     * Verifica si un componente esta disponible
     *
     * @param string $nombre Nombre del componente
     * @return bool
     */
    public function componente_disponible($nombre) {
        return isset($this->mapeo_componentes[$nombre]);
    }

    /**
     * Obtiene la lista de componentes disponibles
     *
     * @return array
     */
    public function obtener_lista_componentes() {
        return array_keys($this->mapeo_componentes);
    }

    /**
     * Registra un nuevo componente dinamicamente
     *
     * @param string $nombre Nombre del componente
     * @param string $archivo Ruta al archivo
     * @param string $clase Nombre de la clase
     * @return bool
     */
    public function registrar_componente($nombre, $archivo, $clase) {
        $this->mapeo_componentes[$nombre] = [
            'archivo' => $archivo,
            'clase'   => $clase,
        ];

        return true;
    }

    /**
     * Obtiene informacion de un componente
     *
     * @param string $nombre Nombre del componente
     * @return array|null
     */
    public function obtener_info_componente($nombre) {
        if (!isset($this->mapeo_componentes[$nombre])) {
            return null;
        }

        $info = $this->mapeo_componentes[$nombre];
        $info['nombre'] = $nombre;
        $info['cargado'] = isset($this->componentes_cargados[$nombre]);

        if ($info['cargado']) {
            $componente = $this->componentes_cargados[$nombre];
            $info['nombre_descriptivo'] = $componente->get_nombre_descriptivo ?? $componente->get_nombre();
        }

        return $info;
    }
}

/**
 * Funcion helper para obtener el loader de componentes
 *
 * @return Flavor_Components_Loader
 */
function flavor_components_loader() {
    return Flavor_Components_Loader::get_instance();
}

// Inicializar el loader automaticamente
Flavor_Components_Loader::get_instance();

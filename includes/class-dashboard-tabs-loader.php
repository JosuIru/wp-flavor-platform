<?php
/**
 * Cargador de Dashboard Tabs para módulos
 *
 * Carga automáticamente los archivos de dashboard tabs de los módulos activos
 * para integrarlos con el sistema de tabs del dashboard del cliente.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase cargadora de Dashboard Tabs
 */
class Flavor_Dashboard_Tabs_Loader {

    /**
     * Instancia singleton
     * @var Flavor_Dashboard_Tabs_Loader|null
     */
    private static $instancia = null;

    /**
     * Tabs cargados
     * @var array
     */
    private $tabs_cargados = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        // Cargar tabs después de que los módulos estén disponibles
        add_action('init', [$this, 'cargar_dashboard_tabs'], 15);
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Dashboard_Tabs_Loader
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Carga los dashboard tabs de los módulos activos
     */
    public function cargar_dashboard_tabs() {
        // Obtener configuración de módulos activos
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        if (empty($modulos_activos)) {
            return;
        }

        $ruta_modulos = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

        // Lista de archivos de dashboard tab por módulo
        $mapeo_dashboard_tabs = [
            'incidencias' => [
                'archivo' => 'class-incidencias-dashboard-tab.php',
                'clase' => 'Flavor_Incidencias_Dashboard_Tab',
            ],
            'eventos' => [
                'archivo' => 'class-eventos-dashboard-tab.php',
                'clase' => 'Flavor_Eventos_Dashboard_Tab',
            ],
            'marketplace' => [
                'archivo' => 'class-marketplace-dashboard-tab.php',
                'clase' => 'Flavor_Marketplace_Dashboard_Tab',
            ],
            'socios' => [
                'archivo' => 'class-socios-dashboard-tab.php',
                'clase' => 'Flavor_Socios_Dashboard_Tab',
            ],
            'talleres' => [
                'archivo' => 'class-talleres-dashboard-tab.php',
                'clase' => 'Flavor_Talleres_Dashboard_Tab',
            ],
            'participacion' => [
                'archivo' => 'class-participacion-dashboard-tab.php',
                'clase' => 'Flavor_Participacion_Dashboard_Tab',
            ],
            'circulos_cuidados' => [
                'archivo' => 'class-circulos-cuidados-dashboard-tab.php',
                'clase' => 'Flavor_Circulos_Cuidados_Dashboard_Tab',
            ],
            'economia_don' => [
                'archivo' => 'class-economia-don-dashboard-tab.php',
                'clase' => 'Flavor_Economia_Don_Dashboard_Tab',
            ],
            'biodiversidad_local' => [
                'archivo' => 'class-biodiversidad-local-dashboard-tab.php',
                'clase' => 'Flavor_Biodiversidad_Local_Dashboard_Tab',
            ],
            'cursos' => [
                'archivo' => 'class-cursos-dashboard-tab.php',
                'clase' => 'Flavor_Cursos_Dashboard_Tab',
            ],
            'espacios_comunes' => [
                'archivo' => 'class-espacios-comunes-dashboard-tab.php',
                'clase' => 'Flavor_Espacios_Comunes_Dashboard_Tab',
            ],
            'huertos_urbanos' => [
                'archivo' => 'class-huertos-urbanos-dashboard-tab.php',
                'clase' => 'Flavor_Huertos_Urbanos_Dashboard_Tab',
            ],
            'presupuestos_participativos' => [
                'archivo' => 'class-presupuestos-participativos-dashboard-tab.php',
                'clase' => 'Flavor_Presupuestos_Participativos_Dashboard_Tab',
            ],
            // Módulos adicionales con dashboard tabs
            'ayuda_vecinal' => [
                'archivo' => 'frontend/class-ayuda-vecinal-frontend-controller.php',
                'clase' => 'Flavor_Ayuda_Vecinal_Frontend_Controller',
            ],
            'banco_tiempo' => [
                'archivo' => 'frontend/class-banco-tiempo-frontend-controller.php',
                'clase' => 'Flavor_Banco_Tiempo_Frontend_Controller',
            ],
            'carpooling' => [
                'archivo' => 'frontend/class-carpooling-frontend-controller.php',
                'clase' => 'Flavor_Carpooling_Frontend_Controller',
            ],
            'reservas' => [
                'archivo' => 'frontend/class-reservas-frontend-controller.php',
                'clase' => 'Flavor_Reservas_Frontend_Controller',
            ],
            // Frontend controllers con tabs integrados
            'huertos_urbanos' => [
                'archivo' => 'frontend/class-huertos-urbanos-frontend-controller.php',
                'clase' => 'Flavor_Huertos_Urbanos_Frontend_Controller',
            ],
            'presupuestos_participativos' => [
                'archivo' => 'frontend/class-presupuestos-participativos-frontend-controller.php',
                'clase' => 'Flavor_Presupuestos_Participativos_Frontend_Controller',
            ],
            'espacios_comunes' => [
                'archivo' => 'frontend/class-espacios-comunes-frontend-controller.php',
                'clase' => 'Flavor_Espacios_Comunes_Frontend_Controller',
            ],
            'comunidades' => [
                'archivo' => 'frontend/class-comunidades-frontend-controller.php',
                'clase' => 'Flavor_Comunidades_Frontend_Controller',
            ],
            'socios' => [
                'archivo' => 'frontend/class-socios-frontend-controller.php',
                'clase' => 'Flavor_Socios_Frontend_Controller',
            ],
            'participacion' => [
                'archivo' => 'frontend/class-participacion-frontend-controller.php',
                'clase' => 'Flavor_Participacion_Frontend_Controller',
            ],
            // Nuevos frontend controllers
            'foros' => [
                'archivo' => 'frontend/class-foros-frontend-controller.php',
                'clase' => 'Flavor_Foros_Frontend_Controller',
            ],
            'colectivos' => [
                'archivo' => 'frontend/class-colectivos-frontend-controller.php',
                'clase' => 'Flavor_Colectivos_Frontend_Controller',
            ],
            'biodiversidad_local' => [
                'archivo' => 'frontend/class-biodiversidad-local-frontend-controller.php',
                'clase' => 'Flavor_Biodiversidad_Local_Frontend_Controller',
            ],
            'circulos_cuidados' => [
                'archivo' => 'frontend/class-circulos-cuidados-frontend-controller.php',
                'clase' => 'Flavor_Circulos_Cuidados_Frontend_Controller',
            ],
            'economia_don' => [
                'archivo' => 'frontend/class-economia-don-frontend-controller.php',
                'clase' => 'Flavor_Economia_Don_Frontend_Controller',
            ],
            'tramites' => [
                'archivo' => 'frontend/class-tramites-frontend-controller.php',
                'clase' => 'Flavor_Tramites_Frontend_Controller',
            ],
            'saberes_ancestrales' => [
                'archivo' => 'frontend/class-saberes-ancestrales-frontend-controller.php',
                'clase' => 'Flavor_Saberes_Ancestrales_Frontend_Controller',
            ],
            'justicia_restaurativa' => [
                'archivo' => 'frontend/class-justicia-restaurativa-frontend-controller.php',
                'clase' => 'Flavor_Justicia_Restaurativa_Frontend_Controller',
            ],
            'trabajo_digno' => [
                'archivo' => 'frontend/class-trabajo-digno-frontend-controller.php',
                'clase' => 'Flavor_Trabajo_Digno_Frontend_Controller',
            ],
            'bicicletas_compartidas' => [
                'archivo' => 'frontend/class-bicicletas-compartidas-frontend-controller.php',
                'clase' => 'Flavor_Bicicletas_Compartidas_Frontend_Controller',
            ],
            'podcast' => [
                'archivo' => 'frontend/class-podcast-frontend-controller.php',
                'clase' => 'Flavor_Podcast_Frontend_Controller',
            ],
            'radio' => [
                'archivo' => 'frontend/class-radio-frontend-controller.php',
                'clase' => 'Flavor_Radio_Frontend_Controller',
            ],
            'reciclaje' => [
                'archivo' => 'frontend/class-reciclaje-frontend-controller.php',
                'clase' => 'Flavor_Reciclaje_Frontend_Controller',
            ],
            'compostaje' => [
                'archivo' => 'frontend/class-compostaje-frontend-controller.php',
                'clase' => 'Flavor_Compostaje_Frontend_Controller',
            ],
            'avisos_municipales' => [
                'archivo' => 'frontend/class-avisos-municipales-frontend-controller.php',
                'clase' => 'Flavor_Avisos_Municipales_Frontend_Controller',
            ],
            'parkings' => [
                'archivo' => 'frontend/class-parkings-frontend-controller.php',
                'clase' => 'Flavor_Parkings_Frontend_Controller',
            ],
            'huella_ecologica' => [
                'archivo' => 'class-huella-ecologica-dashboard-tab.php',
                'clase' => 'Flavor_Huella_Ecologica_Dashboard_Tab',
            ],
            'multimedia' => [
                'archivo' => 'frontend/class-multimedia-frontend-controller.php',
                'clase' => 'Flavor_Multimedia_Frontend_Controller',
            ],
            'recetas' => [
                'archivo' => 'frontend/class-recetas-frontend-controller.php',
                'clase' => 'Flavor_Recetas_Frontend_Controller',
            ],
            'mapa_actores' => [
                'archivo' => 'frontend/class-mapa-actores-frontend-controller.php',
                'clase' => 'Flavor_Mapa_Actores_Frontend_Controller',
            ],
            'seguimiento_denuncias' => [
                'archivo' => 'frontend/class-seguimiento-denuncias-frontend-controller.php',
                'clase' => 'Flavor_Seguimiento_Denuncias_Frontend_Controller',
            ],
            'transparencia' => [
                'archivo' => 'frontend/class-transparencia-frontend-controller.php',
                'clase' => 'Flavor_Transparencia_Frontend_Controller',
            ],
            'campanias' => [
                'archivo' => 'frontend/class-campanias-frontend-controller.php',
                'clase' => 'Flavor_Campanias_Frontend_Controller',
            ],
            'documentacion_legal' => [
                'archivo' => 'frontend/class-documentacion-legal-frontend-controller.php',
                'clase' => 'Flavor_Documentacion_Legal_Frontend_Controller',
            ],
        ];

        foreach ($modulos_activos as $modulo_id) {
            // Normalizar ID del módulo
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            $carpeta_modulo = str_replace('_', '-', $modulo_id);

            // Verificar si hay un dashboard tab definido para este módulo
            if (isset($mapeo_dashboard_tabs[$modulo_normalizado])) {
                $config = $mapeo_dashboard_tabs[$modulo_normalizado];
                $ruta_archivo = $ruta_modulos . $carpeta_modulo . '/' . $config['archivo'];

                if (file_exists($ruta_archivo)) {
                    $this->cargar_tab($ruta_archivo, $config['clase'], $modulo_id);
                }
            } else {
                // Buscar automáticamente archivos *-dashboard-tab.php
                $this->buscar_y_cargar_tab($ruta_modulos . $carpeta_modulo . '/', $modulo_id);
            }
        }
    }

    /**
     * Carga un archivo de dashboard tab específico
     *
     * @param string $ruta_archivo Ruta al archivo PHP
     * @param string $nombre_clase Nombre de la clase
     * @param string $modulo_id ID del módulo
     */
    private function cargar_tab($ruta_archivo, $nombre_clase, $modulo_id) {
        // Evitar cargar la misma clase dos veces
        if (class_exists($nombre_clase)) {
            // Ya está cargada, solo inicializar si tiene método get_instance
            if (method_exists($nombre_clase, 'get_instance')) {
                $instancia = $nombre_clase::get_instance();
                $this->tabs_cargados[$modulo_id] = $instancia;
            }
            return;
        }

        // Cargar el archivo
        require_once $ruta_archivo;

        // Verificar que la clase ahora existe
        if (class_exists($nombre_clase)) {
            // Inicializar usando singleton
            if (method_exists($nombre_clase, 'get_instance')) {
                $instancia = $nombre_clase::get_instance();
                $this->tabs_cargados[$modulo_id] = $instancia;
            }
        }
    }

    /**
     * Busca y carga archivos de dashboard tab automáticamente
     *
     * @param string $directorio_modulo Directorio del módulo
     * @param string $modulo_id ID del módulo
     */
    private function buscar_y_cargar_tab($directorio_modulo, $modulo_id) {
        if (!is_dir($directorio_modulo)) {
            return;
        }

        // Buscar archivos que terminen en -dashboard-tab.php
        $archivos = glob($directorio_modulo . '*-dashboard-tab.php');

        foreach ($archivos as $archivo) {
            // Extraer nombre de clase del archivo
            $nombre_archivo = basename($archivo, '.php');
            $nombre_clase = $this->nombre_archivo_a_clase($nombre_archivo);

            if (!empty($nombre_clase)) {
                $this->cargar_tab($archivo, $nombre_clase, $modulo_id);
            }
        }
    }

    /**
     * Convierte nombre de archivo a nombre de clase
     *
     * class-incidencias-dashboard-tab.php → Flavor_Incidencias_Dashboard_Tab
     *
     * @param string $nombre_archivo Nombre del archivo sin extensión
     * @return string Nombre de clase
     */
    private function nombre_archivo_a_clase($nombre_archivo) {
        // Quitar prefijo 'class-'
        if (strpos($nombre_archivo, 'class-') === 0) {
            $nombre_archivo = substr($nombre_archivo, 6);
        }

        // Convertir guiones a guiones bajos y capitalizar
        $partes = explode('-', $nombre_archivo);
        $partes = array_map('ucfirst', $partes);

        return 'Flavor_' . implode('_', $partes);
    }

    /**
     * Obtiene los tabs cargados
     *
     * @return array
     */
    public function get_tabs_cargados() {
        return $this->tabs_cargados;
    }
}

// Inicializar el cargador
Flavor_Dashboard_Tabs_Loader::get_instance();

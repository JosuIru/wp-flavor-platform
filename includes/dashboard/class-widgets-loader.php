<?php
/**
 * Cargador de Widgets del Dashboard
 *
 * Carga y registra automáticamente los widgets de los módulos
 * en el Dashboard Unificado.
 *
 * @package FlavorPlatform
 * @subpackage Dashboard
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase cargadora de widgets
 *
 * @since 4.1.0
 */
class Flavor_Widgets_Loader {

    /**
     * Instancia singleton
     *
     * @var Flavor_Widgets_Loader|null
     */
    private static $instance = null;

    /**
     * Widgets cargados
     *
     * @var array
     */
    private $widgets_cargados = [];

    /**
     * Caché de módulos activos (evita múltiples get_option)
     *
     * @var array|null
     */
    private $modulos_activos_cache = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Widgets_Loader
     */
    public static function get_instance(): Flavor_Widgets_Loader {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('flavor_register_dashboard_widgets', [$this, 'registrar_widgets'], 10);
    }

    /**
     * Registra todos los widgets de los módulos
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     */
    public function registrar_widgets(Flavor_Widget_Registry $registry): void {
        $widgets_configuracion = $this->get_widgets_config();

        foreach ($widgets_configuracion as $widget_id => $config) {
            // Verificar si el módulo está activo
            if (!$this->modulo_activo($config['modulo'])) {
                continue;
            }

            // Cargar el archivo del widget
            $archivo_widget = $this->get_ruta_widget($config);

            if (!file_exists($archivo_widget)) {
                continue;
            }

            require_once $archivo_widget;

            // Instanciar y registrar el widget
            if (class_exists($config['clase'])) {
                $widget_instancia = new $config['clase']();
                $registry->register($widget_instancia);
                $this->widgets_cargados[] = $widget_id;
            }
        }
    }

    /**
     * Obtiene la configuración de todos los widgets
     *
     * @return array
     */
    private function get_widgets_config(): array {
        $widgets = [
            // Economía
            'grupos-consumo' => [
                'modulo' => 'grupos-consumo',
                'archivo' => 'class-gc-dashboard-widget.php',
                'clase' => 'Flavor_GC_Dashboard_Widget',
            ],
            'banco-tiempo' => [
                'modulo' => 'banco-tiempo',
                'archivo' => 'class-banco-tiempo-dashboard-widget.php',
                'clase' => 'Flavor_Banco_Tiempo_Dashboard_Widget',
            ],
            'marketplace' => [
                'modulo' => 'marketplace',
                'archivo' => 'class-marketplace-dashboard-widget.php',
                'clase' => 'Flavor_Marketplace_Dashboard_Widget',
            ],

            // Comunidad
            'eventos' => [
                'modulo' => 'eventos',
                'archivo' => 'class-eventos-dashboard-widget.php',
                'clase' => 'Flavor_Eventos_Dashboard_Widget',
            ],
            'comunidades' => [
                'modulo' => 'comunidades',
                'archivo' => 'class-comunidades-dashboard-widget.php',
                'clase' => 'Flavor_Comunidades_Dashboard_Widget',
            ],
            'foros' => [
                'modulo' => 'foros',
                'archivo' => 'class-foros-dashboard-widget.php',
                'clase' => 'Flavor_Foros_Dashboard_Widget',
            ],

            // Gestión
            'socios' => [
                'modulo' => 'socios',
                'archivo' => 'class-socios-dashboard-widget.php',
                'clase' => 'Flavor_Socios_Dashboard_Widget',
            ],
            'reservas' => [
                'modulo' => 'reservas',
                'archivo' => 'class-reservas-dashboard-widget.php',
                'clase' => 'Flavor_Reservas_Dashboard_Widget',
            ],
            'incidencias' => [
                'modulo' => 'incidencias',
                'archivo' => 'class-incidencias-dashboard-widget.php',
                'clase' => 'Flavor_Incidencias_Dashboard_Widget',
            ],

            // Cursos y talleres
            'cursos' => [
                'modulo' => 'cursos',
                'archivo' => 'class-cursos-dashboard-widget.php',
                'clase' => 'Flavor_Cursos_Dashboard_Widget',
            ],
            'talleres' => [
                'modulo' => 'talleres',
                'archivo' => 'class-talleres-dashboard-widget.php',
                'clase' => 'Flavor_Talleres_Dashboard_Widget',
            ],

            // Sostenibilidad
            'huertos-urbanos' => [
                'modulo' => 'huertos-urbanos',
                'archivo' => 'class-huertos-dashboard-widget.php',
                'clase' => 'Flavor_Huertos_Dashboard_Widget',
            ],
            'carpooling' => [
                'modulo' => 'carpooling',
                'archivo' => 'class-carpooling-dashboard-widget.php',
                'clase' => 'Flavor_Carpooling_Dashboard_Widget',
            ],
            'bicicletas-compartidas' => [
                'modulo' => 'bicicletas-compartidas',
                'archivo' => 'class-bicicletas-dashboard-widget.php',
                'clase' => 'Flavor_Bicicletas_Dashboard_Widget',
            ],
            'energia-comunitaria' => [
                'modulo' => 'energia-comunitaria',
                'archivo' => 'class-energia-dashboard-widget.php',
                'clase' => 'Flavor_Energia_Comunitaria_Dashboard_Widget',
            ],

            // Comunicación
            'podcast' => [
                'modulo' => 'podcast',
                'archivo' => 'class-podcast-dashboard-widget.php',
                'clase' => 'Flavor_Podcast_Dashboard_Widget',
            ],
            'multimedia' => [
                'modulo' => 'multimedia',
                'archivo' => 'class-multimedia-dashboard-widget.php',
                'clase' => 'Flavor_Multimedia_Dashboard_Widget',
            ],

            // Participación
            'participacion' => [
                'modulo' => 'participacion',
                'archivo' => 'class-participacion-dashboard-widget.php',
                'clase' => 'Flavor_Participacion_Dashboard_Widget',
            ],
            'presupuestos-participativos' => [
                'modulo' => 'presupuestos-participativos',
                'archivo' => 'class-pp-dashboard-widget.php',
                'clase' => 'Flavor_PP_Dashboard_Widget',
            ],

            // Espacios
            'espacios-comunes' => [
                'modulo' => 'espacios-comunes',
                'archivo' => 'class-espacios-dashboard-widget.php',
                'clase' => 'Flavor_Espacios_Dashboard_Widget',
            ],

            // Ayuda
            'ayuda-vecinal' => [
                'modulo' => 'ayuda-vecinal',
                'archivo' => 'class-ayuda-vecinal-dashboard-widget.php',
                'clase' => 'Flavor_Ayuda_Vecinal_Dashboard_Widget',
            ],

            // Biblioteca y cultura
            'biblioteca' => [
                'modulo' => 'biblioteca',
                'archivo' => 'class-biblioteca-dashboard-widget.php',
                'clase' => 'Flavor_Biblioteca_Dashboard_Widget',
            ],
            'radio' => [
                'modulo' => 'radio',
                'archivo' => 'class-radio-dashboard-widget.php',
                'clase' => 'Flavor_Radio_Dashboard_Widget',
            ],

            // Participación ciudadana
            'encuestas' => [
                'modulo' => 'encuestas',
                'archivo' => 'class-encuestas-dashboard-widget.php',
                'clase' => 'Flavor_Encuestas_Dashboard_Widget',
            ],
            'colectivos' => [
                'modulo' => 'colectivos',
                'archivo' => 'class-colectivos-dashboard-widget.php',
                'clase' => 'Flavor_Colectivos_Dashboard_Widget',
            ],
            'avisos-municipales' => [
                'modulo' => 'avisos-municipales',
                'archivo' => 'class-avisos-dashboard-widget.php',
                'clase' => 'Flavor_Avisos_Dashboard_Widget',
            ],

            // Sostenibilidad adicional
            'reciclaje' => [
                'modulo' => 'reciclaje',
                'archivo' => 'class-reciclaje-dashboard-widget.php',
                'clase' => 'Flavor_Reciclaje_Dashboard_Widget',
            ],

            // Gestión pública
            'tramites' => [
                'modulo' => 'tramites',
                'archivo' => 'class-tramites-dashboard-widget.php',
                'clase' => 'Flavor_Tramites_Dashboard_Widget',
            ],
            'transparencia' => [
                'modulo' => 'transparencia',
                'archivo' => 'class-transparencia-dashboard-widget.php',
                'clase' => 'Flavor_Transparencia_Dashboard_Widget',
            ],
        ];

        /**
         * Filtro para modificar la configuración de widgets
         *
         * @param array $widgets Configuración de widgets
         */
        return apply_filters('flavor_dashboard_widgets_config', $widgets);
    }

    /**
     * Obtiene la ruta completa del archivo del widget
     *
     * @param array $config Configuración del widget
     * @return string
     */
    private function get_ruta_widget(array $config): string {
        return FLAVOR_PLATFORM_PATH . 'includes/modules/' . $config['modulo'] . '/' . $config['archivo'];
    }

    /**
     * Verifica si un módulo está activo
     *
     * @param string $modulo_id ID del módulo (puede tener guiones o guiones bajos)
     * @return bool
     */
    private function modulo_activo(string $modulo_id): bool {
        // Usar caché para evitar múltiples get_option por request
        if ($this->modulos_activos_cache === null) {
            $this->modulos_activos_cache = $this->cargar_modulos_activos();
        }

        // Normalizar el ID del módulo (guiones a guiones bajos para consistencia)
        $modulo_id_normalizado = str_replace('-', '_', $modulo_id);

        // Verificar si el módulo está en la lista de activos (ambos formatos)
        return in_array($modulo_id, $this->modulos_activos_cache, true) ||
               in_array($modulo_id_normalizado, $this->modulos_activos_cache, true);
    }

    /**
     * Carga la lista de módulos activos (una sola vez por request)
     *
     * @return array
     */
    private function cargar_modulos_activos(): array {
        // Leer de flavor_chat_ia_settings['active_modules'] (preferido)
        $settings = flavor_get_main_settings();
        $modulos_activos = $settings['active_modules'] ?? [];

        // También leer de flavor_active_modules (legacy/compatibilidad)
        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_activos_legacy));
        }

        // Si no hay módulos configurados, usar default (woocommerce)
        if (empty($modulos_activos)) {
            $modulos_activos = ['woocommerce'];
        }

        return $modulos_activos;
    }

    /**
     * Obtiene los widgets cargados
     *
     * @return array
     */
    public function get_widgets_cargados(): array {
        return $this->widgets_cargados;
    }
}

// Inicializar el loader
add_action('plugins_loaded', function() {
    Flavor_Widgets_Loader::get_instance();
}, 15);

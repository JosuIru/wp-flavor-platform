<?php
/**
 * Accessibility - Funciones de accesibilidad WCAG
 *
 * Implementa skip links y otras mejoras de accesibilidad
 * siguiendo las directrices WCAG 2.4.1
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase de accesibilidad
 */
class Flavor_Accessibility {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instancia = null;

    /**
     * Configuracion de accesibilidad
     *
     * @var array
     */
    private $configuracion_accesibilidad = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return self
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
        $this->cargar_configuracion();
        $this->inicializar_hooks();
    }

    /**
     * Carga la configuracion de accesibilidad
     */
    private function cargar_configuracion() {
        $configuracion_guardada = get_option('flavor_accessibility_settings', []);

        $this->configuracion_accesibilidad = wp_parse_args($configuracion_guardada, [
            'skip_links_enabled' => true,
            'skip_links_target' => '#main-content',
            'high_contrast_mode' => false,
            'focus_indicators' => true,
            'reduced_motion' => false,
        ]);
    }

    /**
     * Inicializa los hooks de WordPress
     */
    private function inicializar_hooks() {
        // Cargar assets de accesibilidad en frontend
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets_accesibilidad'], 5);

        // Agregar atributos de accesibilidad al body
        add_filter('body_class', [$this, 'agregar_clases_accesibilidad']);

        // Configuraciones en admin
        if (is_admin()) {
            add_action('admin_init', [$this, 'registrar_configuraciones']);
        }

        // REST API para configuraciones
        add_action('rest_api_init', [$this, 'registrar_rutas_rest']);
    }

    /**
     * Encola los assets de accesibilidad
     */
    public function encolar_assets_accesibilidad() {
        // Verificar si skip links estan habilitados
        if (!$this->configuracion_accesibilidad['skip_links_enabled']) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS de skip links
        $ruta_css_skip_links = FLAVOR_PLATFORM_PATH . 'assets/css/components/skip-links.css';
        if (file_exists($ruta_css_skip_links)) {
            wp_enqueue_style(
                'flavor-skip-links',
                FLAVOR_PLATFORM_URL . "assets/css/components/skip-links{$sufijo_asset}.css",
                [],
                filemtime($ruta_css_skip_links)
            );
        }

        // JS de skip links
        $ruta_js_skip_links = FLAVOR_PLATFORM_PATH . 'assets/js/skip-links.js';
        if (file_exists($ruta_js_skip_links)) {
            wp_enqueue_script(
                'flavor-skip-links',
                FLAVOR_PLATFORM_URL . "assets/js/skip-links{$sufijo_asset}.js",
                [],
                filemtime($ruta_js_skip_links),
                false // Cargar en head para que sea el primer elemento focusable
            );

            // Pasar configuracion al JS
            wp_localize_script('flavor-skip-links', 'flavorSkipLinksConfig', [
                'language' => $this->obtener_idioma_actual(),
                'target' => $this->configuracion_accesibilidad['skip_links_target'],
            ]);
        }
    }

    /**
     * Obtiene el idioma actual de WordPress
     *
     * @return string Codigo de idioma
     */
    private function obtener_idioma_actual() {
        // Compatibilidad con WPML
        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        }

        // Compatibilidad con Polylang
        if (function_exists('pll_current_language')) {
            return pll_current_language('slug');
        }

        // Idioma de WordPress
        $locale_wordpress = get_locale();
        return substr($locale_wordpress, 0, 2);
    }

    /**
     * Agrega clases de accesibilidad al body
     *
     * @param array $clases_existentes Clases actuales del body
     * @return array Clases modificadas
     */
    public function agregar_clases_accesibilidad($clases_existentes) {
        if ($this->configuracion_accesibilidad['high_contrast_mode']) {
            $clases_existentes[] = 'flavor-high-contrast';
        }

        if ($this->configuracion_accesibilidad['reduced_motion']) {
            $clases_existentes[] = 'flavor-reduced-motion';
        }

        if ($this->configuracion_accesibilidad['focus_indicators']) {
            $clases_existentes[] = 'flavor-focus-visible';
        }

        return $clases_existentes;
    }

    /**
     * Registra las configuraciones de accesibilidad
     */
    public function registrar_configuraciones() {
        register_setting(
            'flavor_accessibility',
            'flavor_accessibility_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizar_configuracion'],
            ]
        );
    }

    /**
     * Sanitiza la configuracion de accesibilidad
     *
     * @param array $entrada Datos de entrada
     * @return array Datos sanitizados
     */
    public function sanitizar_configuracion($entrada) {
        $configuracion_sanitizada = [];

        $configuracion_sanitizada['skip_links_enabled'] = !empty($entrada['skip_links_enabled']);
        $configuracion_sanitizada['skip_links_target'] = sanitize_text_field($entrada['skip_links_target'] ?? '#main-content');
        $configuracion_sanitizada['high_contrast_mode'] = !empty($entrada['high_contrast_mode']);
        $configuracion_sanitizada['focus_indicators'] = !empty($entrada['focus_indicators']);
        $configuracion_sanitizada['reduced_motion'] = !empty($entrada['reduced_motion']);

        return $configuracion_sanitizada;
    }

    /**
     * Registra rutas REST para configuraciones de accesibilidad
     */
    public function registrar_rutas_rest() {
        register_rest_route('flavor/v1', '/accessibility/settings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'obtener_configuracion_rest'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'guardar_configuracion_rest'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);
    }

    /**
     * Obtiene la configuracion via REST
     *
     * @return WP_REST_Response
     */
    public function obtener_configuracion_rest() {
        return new WP_REST_Response([
            'success' => true,
            'data' => $this->configuracion_accesibilidad,
        ]);
    }

    /**
     * Guarda la configuracion via REST
     *
     * @param WP_REST_Request $solicitud_rest Solicitud REST
     * @return WP_REST_Response
     */
    public function guardar_configuracion_rest($solicitud_rest) {
        $datos_recibidos = $solicitud_rest->get_json_params();
        $configuracion_sanitizada = $this->sanitizar_configuracion($datos_recibidos);

        update_option('flavor_accessibility_settings', $configuracion_sanitizada);
        $this->configuracion_accesibilidad = $configuracion_sanitizada;

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Configuracion de accesibilidad guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'data' => $configuracion_sanitizada,
        ]);
    }

    /**
     * Verifica si los skip links estan habilitados
     *
     * @return bool
     */
    public function skip_links_habilitados() {
        return !empty($this->configuracion_accesibilidad['skip_links_enabled']);
    }

    /**
     * Obtiene la configuracion actual
     *
     * @return array
     */
    public function obtener_configuracion() {
        return $this->configuracion_accesibilidad;
    }
}

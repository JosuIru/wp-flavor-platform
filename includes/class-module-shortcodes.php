<?php
/**
 * Sistema de Shortcodes Universales para Módulos
 *
 * Proporciona shortcodes genéricos que funcionan con cualquier módulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes de módulos
 */
class Flavor_Module_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Instancia del control de acceso
     */
    private $control_acceso = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->register_shortcodes();
        $this->inicializar_control_acceso();
    }

    /**
     * Inicializa el controlador de acceso
     */
    private function inicializar_control_acceso() {
        if (class_exists('Flavor_Module_Access_Control')) {
            $this->control_acceso = Flavor_Module_Access_Control::get_instance();
        }
    }

    /**
     * Verifica si el usuario tiene acceso al módulo
     *
     * @param string $module_slug Slug del módulo
     * @return bool|string True si tiene acceso, o HTML de mensaje de error
     */
    private function verificar_acceso_modulo($module_slug) {
        // Si no hay control de acceso, permitir todo
        if (!$this->control_acceso) {
            return true;
        }

        // Verificar acceso
        if ($this->control_acceso->user_can_access($module_slug)) {
            return true;
        }

        // No tiene acceso - determinar qué mensaje mostrar
        if (!is_user_logged_in()) {
            $url_redireccion = $this->obtener_url_actual();
            return $this->control_acceso->render_login_required($url_redireccion);
        }

        return $this->control_acceso->render_access_denied($module_slug);
    }

    /**
     * Obtiene la URL actual
     *
     * @return string
     */
    private function obtener_url_actual() {
        global $wp;
        return home_url(add_query_arg([], $wp->request));
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        // [flavor_module_listing module="talleres" action="talleres_disponibles"]
        add_shortcode('flavor_module_listing', [$this, 'render_listing']);

        // [flavor_module_form module="talleres" action="inscribirse"]
        add_shortcode('flavor_module_form', [$this, 'render_form']);

        // [flavor_module_detail module="talleres" id="123"]
        add_shortcode('flavor_module_detail', [$this, 'render_detail']);

        // [flavor_module_dashboard module="talleres"]
        add_shortcode('flavor_module_dashboard', [$this, 'render_dashboard']);
    }

    /**
     * Renderiza un listado de items del módulo
     */
    public function render_listing($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => '',
            'filters' => '',
            'columnas' => '3',
            'limite' => '12',
            'require_access' => 'yes', // Permitir desactivar verificación de acceso
        ], $atts);

        if (empty($atts['module'])) {
            return '<p class="flavor-error">' . __('Falta especificar el módulo', 'flavor-chat-ia') . '</p>';
        }

        // Verificar acceso al módulo (si no está desactivado)
        if ($atts['require_access'] !== 'no') {
            $verificacion_acceso = $this->verificar_acceso_modulo($atts['module']);
            if ($verificacion_acceso !== true) {
                return $verificacion_acceso;
            }
        }

        // Obtener el módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($atts['module']);

        if (!$module) {
            return '<p class="flavor-error">' . sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $atts['module']) . '</p>';
        }

        // Parsear filtros
        $filtros = $this->parse_filters($atts['filters']);
        $filtros['limite'] = intval($atts['limite']);

        // Ejecutar acción
        $action_name = !empty($atts['action']) ? $atts['action'] : $this->get_default_listing_action($atts['module']);
        $resultado = $module->execute_action($action_name, $filtros);

        if (!$resultado['success']) {
            return '<p class="flavor-error">' . esc_html($resultado['error'] ?? __('Error al cargar datos', 'flavor-chat-ia')) . '</p>';
        }

        // Renderizar usando template del procesador de formularios
        return Flavor_Form_Processor::render_listing(
            $atts['module'],
            $resultado,
            intval($atts['columnas'])
        );
    }

    /**
     * Renderiza un formulario de acción
     */
    public function render_form($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => '',
            'require_access' => 'yes',
        ], $atts);

        if (empty($atts['module']) || empty($atts['action'])) {
            return '<p class="flavor-error">' . __('Falta especificar módulo o acción', 'flavor-chat-ia') . '</p>';
        }

        // Verificar acceso al módulo
        if ($atts['require_access'] !== 'no') {
            $verificacion_acceso = $this->verificar_acceso_modulo($atts['module']);
            if ($verificacion_acceso !== true) {
                return $verificacion_acceso;
            }
        }

        // Obtener el módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($atts['module']);

        if (!$module) {
            return '<p class="flavor-error">' . sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $atts['module']) . '</p>';
        }

        // Obtener configuración del formulario
        if (!method_exists($module, 'get_form_config')) {
            return '<p class="flavor-error">' . __('Este módulo no soporta formularios', 'flavor-chat-ia') . '</p>';
        }

        $form_config = $module->get_form_config($atts['action']);

        if (empty($form_config)) {
            return '<p class="flavor-error">' . sprintf(__('Formulario no encontrado: %s', 'flavor-chat-ia'), $atts['action']) . '</p>';
        }

        // Renderizar formulario
        return Flavor_Form_Processor::render_form(
            $atts['module'],
            $atts['action'],
            $form_config,
            $atts
        );
    }

    /**
     * Renderiza detalle de un item
     */
    public function render_detail($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'id' => '',
            'require_access' => 'yes',
        ], $atts);

        if (empty($atts['module']) || empty($atts['id'])) {
            return '<p class="flavor-error">' . __('Falta especificar módulo o ID', 'flavor-chat-ia') . '</p>';
        }

        // Verificar acceso al módulo
        if ($atts['require_access'] !== 'no') {
            $verificacion_acceso = $this->verificar_acceso_modulo($atts['module']);
            if ($verificacion_acceso !== true) {
                return $verificacion_acceso;
            }
        }

        // Obtener el módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($atts['module']);

        if (!$module) {
            return '<p class="flavor-error">' . sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $atts['module']) . '</p>';
        }

        // Buscar acción de detalle (puede variar según módulo)
        $action_name = 'detalle_' . $atts['module'];
        if (!array_key_exists($action_name, $module->get_actions())) {
            // Buscar alternativas comunes
            $acciones = $module->get_actions();
            $posibles = ['ver_detalle', 'obtener_detalle', 'detalle'];
            foreach ($posibles as $posible) {
                if (array_key_exists($posible, $acciones)) {
                    $action_name = $posible;
                    break;
                }
            }
        }

        $resultado = $module->execute_action($action_name, ['id' => intval($atts['id'])]);

        if (!$resultado['success']) {
            return '<p class="flavor-error">' . esc_html($resultado['error'] ?? __('Error al cargar detalle', 'flavor-chat-ia')) . '</p>';
        }

        return Flavor_Form_Processor::render_detail(
            $atts['module'],
            $resultado
        );
    }

    /**
     * Renderiza dashboard del usuario para un módulo
     */
    public function render_dashboard($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'require_access' => 'yes',
        ], $atts);

        if (empty($atts['module'])) {
            return '<p class="flavor-error">' . __('Falta especificar el módulo', 'flavor-chat-ia') . '</p>';
        }

        // Verificar si usuario está logueado
        if (!is_user_logged_in()) {
            // Mostrar formulario de login en lugar de solo un mensaje
            if ($this->control_acceso) {
                return $this->control_acceso->render_login_required($this->obtener_url_actual());
            }
            return '<p class="flavor-error">' . __('Debes iniciar sesion para ver tu dashboard', 'flavor-chat-ia') . '</p>';
        }

        // Verificar acceso al módulo
        if ($atts['require_access'] !== 'no') {
            $verificacion_acceso = $this->verificar_acceso_modulo($atts['module']);
            if ($verificacion_acceso !== true) {
                return $verificacion_acceso;
            }
        }

        // Obtener el módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($atts['module']);

        if (!$module) {
            return '<p class="flavor-error">' . sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $atts['module']) . '</p>';
        }

        // Buscar acción de dashboard del usuario
        $acciones = $module->get_actions();
        $action_name = null;

        // Lista de patrones a buscar, en orden de prioridad
        $posibles_acciones = [
            'mi_' . $atts['module'],
            'mis_' . $atts['module'],
            'dashboard',
            'mi_dashboard',
            'estado_actual',
            'ver_fichajes_hoy',
            'resumen_mensual',
            'historial_fichajes',
            'mis_talleres',
            'mis_facturas',
            'mis_eventos',
            'mi_perfil',
            'listar',
        ];

        foreach ($posibles_acciones as $posible) {
            if (array_key_exists($posible, $acciones)) {
                $action_name = $posible;
                break;
            }
        }

        // Si no encontró ninguna, usar la primera acción disponible
        if (!$action_name && !empty($acciones)) {
            $action_name = array_key_first($acciones);
        }

        if (!$action_name) {
            return '<p class="flavor-error">' . __('Dashboard no disponible para este módulo', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $module->execute_action($action_name, []);

        if (!$resultado['success']) {
            return '<p class="flavor-info">' . esc_html($resultado['error'] ?? __('No hay datos disponibles', 'flavor-chat-ia')) . '</p>';
        }

        return Flavor_Form_Processor::render_dashboard(
            $atts['module'],
            $resultado
        );
    }

    /**
     * Parsea filtros del atributo filters
     *
     * Formato: "categoria:frutas,fecha_desde:2024-01-01"
     */
    private function parse_filters($filters_string) {
        if (empty($filters_string)) {
            return [];
        }

        $filtros = [];
        $pares = explode(',', $filters_string);

        foreach ($pares as $par) {
            $partes = explode(':', $par);
            if (count($partes) === 2) {
                $filtros[trim($partes[0])] = trim($partes[1]);
            }
        }

        return $filtros;
    }

    /**
     * Obtiene la acción por defecto para listados según el módulo
     */
    private function get_default_listing_action($module_id) {
        $defaults = [
            'talleres' => 'talleres_disponibles',
            'eventos' => 'eventos_proximos',
            'facturas' => 'mis_facturas',
            'socios' => 'listar_socios',
            'grupos_consumo' => 'listar_productos',
        ];

        return $defaults[$module_id] ?? 'listar';
    }
}

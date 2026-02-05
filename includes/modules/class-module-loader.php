<?php
/**
 * Cargador de módulos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona la carga de módulos
 */
class Flavor_Chat_Module_Loader {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Módulos registrados
     */
    private $registered_modules = [];

    /**
     * Módulos cargados
     */
    private $loaded_modules = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Module_Loader
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
        $this->discover_modules();
    }

    /**
     * Descubre los módulos disponibles
     */
    private function discover_modules() {
        $modules_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

        // Módulos incorporados
        $builtin_modules = [
            'woocommerce' => [
                'file' => $modules_path . 'woocommerce/class-woocommerce-module.php',
                'class' => 'Flavor_Chat_WooCommerce_Module',
            ],
            'banco_tiempo' => [
                'file' => $modules_path . 'banco-tiempo/class-banco-tiempo-module.php',
                'class' => 'Flavor_Chat_Banco_Tiempo_Module',
            ],
            'marketplace' => [
                'file' => $modules_path . 'marketplace/class-marketplace-module.php',
                'class' => 'Flavor_Chat_Marketplace_Module',
            ],
            'grupos_consumo' => [
                'file' => $modules_path . 'grupos-consumo/class-grupos-consumo-module.php',
                'class' => 'Flavor_Chat_Grupos_Consumo_Module',
            ],
            'facturas' => [
                'file' => $modules_path . 'facturas/class-facturas-module.php',
                'class' => 'Flavor_Chat_Facturas_Module',
            ],
            'fichaje_empleados' => [
                'file' => $modules_path . 'fichaje-empleados/class-fichaje-empleados-module.php',
                'class' => 'Flavor_Chat_Fichaje_Empleados_Module',
            ],
            'eventos' => [
                'file' => $modules_path . 'eventos/class-eventos-module.php',
                'class' => 'Flavor_Chat_Eventos_Module',
            ],
            'socios' => [
                'file' => $modules_path . 'socios/class-socios-module.php',
                'class' => 'Flavor_Chat_Socios_Module',
            ],
            'incidencias' => [
                'file' => $modules_path . 'incidencias/class-incidencias-module.php',
                'class' => 'Flavor_Chat_Incidencias_Module',
            ],
            'participacion' => [
                'file' => $modules_path . 'participacion/class-participacion-module.php',
                'class' => 'Flavor_Chat_Participacion_Module',
            ],
            'presupuestos_participativos' => [
                'file' => $modules_path . 'presupuestos-participativos/class-presupuestos-participativos-module.php',
                'class' => 'Flavor_Chat_Presupuestos_Participativos_Module',
            ],
            'avisos_municipales' => [
                'file' => $modules_path . 'avisos-municipales/class-avisos-municipales-module.php',
                'class' => 'Flavor_Chat_Avisos_Municipales_Module',
            ],
            'advertising' => [
                'file' => $modules_path . 'advertising/class-advertising-module.php',
                'class' => 'Flavor_Chat_Advertising_Module',
            ],
            'ayuda_vecinal' => [
                'file' => $modules_path . 'ayuda-vecinal/class-ayuda-vecinal-module.php',
                'class' => 'Flavor_Chat_Ayuda_Vecinal_Module',
            ],
            'biblioteca' => [
                'file' => $modules_path . 'biblioteca/class-biblioteca-module.php',
                'class' => 'Flavor_Chat_Biblioteca_Module',
            ],
            'bicicletas_compartidas' => [
                'file' => $modules_path . 'bicicletas-compartidas/class-bicicletas-compartidas-module.php',
                'class' => 'Flavor_Chat_Bicicletas_Compartidas_Module',
            ],
            'carpooling' => [
                'file' => $modules_path . 'carpooling/class-carpooling-module.php',
                'class' => 'Flavor_Chat_Carpooling_Module',
            ],
            'chat_grupos' => [
                'file' => $modules_path . 'chat-grupos/class-chat-grupos-module.php',
                'class' => 'Flavor_Chat_Chat_Grupos_Module',
            ],
            'chat_interno' => [
                'file' => $modules_path . 'chat-interno/class-chat-interno-module.php',
                'class' => 'Flavor_Chat_Chat_Interno_Module',
            ],
            'compostaje' => [
                'file' => $modules_path . 'compostaje/class-compostaje-module.php',
                'class' => 'Flavor_Chat_Compostaje_Module',
            ],
            'cursos' => [
                'file' => $modules_path . 'cursos/class-cursos-module.php',
                'class' => 'Flavor_Chat_Cursos_Module',
            ],
            'empresarial' => [
                'file' => $modules_path . 'empresarial/class-empresarial-module.php',
                'class' => 'Flavor_Chat_Empresarial_Module',
            ],
            'espacios_comunes' => [
                'file' => $modules_path . 'espacios-comunes/class-espacios-comunes-module.php',
                'class' => 'Flavor_Chat_Espacios_Comunes_Module',
            ],
            'huertos_urbanos' => [
                'file' => $modules_path . 'huertos-urbanos/class-huertos-urbanos-module.php',
                'class' => 'Flavor_Chat_Huertos_Urbanos_Module',
            ],
            'multimedia' => [
                'file' => $modules_path . 'multimedia/class-multimedia-module.php',
                'class' => 'Flavor_Chat_Multimedia_Module',
            ],
            'parkings' => [
                'file' => $modules_path . 'parkings/class-parkings-module.php',
                'class' => 'Flavor_Chat_Parkings_Module',
            ],
            'podcast' => [
                'file' => $modules_path . 'podcast/class-podcast-module.php',
                'class' => 'Flavor_Chat_Podcast_Module',
            ],
            'radio' => [
                'file' => $modules_path . 'radio/class-radio-module.php',
                'class' => 'Flavor_Chat_Radio_Module',
            ],
            'reciclaje' => [
                'file' => $modules_path . 'reciclaje/class-reciclaje-module.php',
                'class' => 'Flavor_Chat_Reciclaje_Module',
            ],
            'red_social' => [
                'file' => $modules_path . 'red-social/class-red-social-module.php',
                'class' => 'Flavor_Chat_Red_Social_Module',
            ],
            'talleres' => [
                'file' => $modules_path . 'talleres/class-talleres-module.php',
                'class' => 'Flavor_Chat_Talleres_Module',
            ],
            'tramites' => [
                'file' => $modules_path . 'tramites/class-tramites-module.php',
                'class' => 'Flavor_Chat_Tramites_Module',
            ],
            'transparencia' => [
                'file' => $modules_path . 'transparencia/class-transparencia-module.php',
                'class' => 'Flavor_Chat_Transparencia_Module',
            ],
            'colectivos' => [
                'file' => $modules_path . 'colectivos/class-colectivos-module.php',
                'class' => 'Flavor_Chat_Colectivos_Module',
            ],
            'foros' => [
                'file' => $modules_path . 'foros/class-foros-module.php',
                'class' => 'Flavor_Chat_Foros_Module',
            ],
            'clientes' => [
                'file' => $modules_path . 'clientes/class-clientes-module.php',
                'class' => 'Flavor_Chat_Clientes_Module',
            ],
            'comunidades' => [
                'file' => $modules_path . 'comunidades/class-comunidades-module.php',
                'class' => 'Flavor_Chat_Comunidades_Module',
            ],
            'bares' => [
                'file' => $modules_path . 'bares/class-bares-module.php',
                'class' => 'Flavor_Chat_Bares_Module',
            ],
            'trading_ia' => [
                'file' => $modules_path . 'trading-ia/class-trading-ia-module.php',
                'class' => 'Flavor_Chat_Trading_IA_Module',
            ],
            'dex_solana' => [
                'file' => $modules_path . 'dex-solana/class-dex-solana-module.php',
                'class' => 'Flavor_Chat_Dex_Solana_Module',
            ],
            'themacle' => [
                'file' => $modules_path . 'themacle/class-themacle-module.php',
                'class' => 'Flavor_Chat_Themacle_Module',
            ],
            'reservas' => [
                'file' => $modules_path . 'reservas/class-reservas-module.php',
                'class' => 'Flavor_Chat_Reservas_Module',
            ],
        ];

        foreach ($builtin_modules as $id => $module) {
            if (file_exists($module['file'])) {
                $this->registered_modules[$id] = $module;
            }
        }

        // Permitir que otros plugins registren módulos
        $this->registered_modules = apply_filters('flavor_chat_ia_modules', $this->registered_modules);
    }

    /**
     * Carga los módulos activos
     *
     * @return array Módulos cargados
     */
    public function load_active_modules() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $settings['active_modules'] ?? ['woocommerce'];

        foreach ($active_modules as $module_id) {
            if (isset($this->registered_modules[$module_id])) {
                $this->load_module($module_id);
            }
        }

        return $this->loaded_modules;
    }

    /**
     * Carga un módulo específico
     *
     * @param string $module_id
     * @return bool
     */
    private function load_module($module_id) {
        if (isset($this->loaded_modules[$module_id])) {
            return true; // Ya cargado
        }

        $module_info = $this->registered_modules[$module_id];

        // Cargar archivo
        if (!file_exists($module_info['file'])) {
            flavor_chat_ia_log("Módulo no encontrado: {$module_id}", 'error');
            return false;
        }

        require_once $module_info['file'];

        // Instanciar
        if (!class_exists($module_info['class'])) {
            flavor_chat_ia_log("Clase de módulo no encontrada: {$module_info['class']}", 'error');
            return false;
        }

        $module = new $module_info['class']();

        // Verificar que implementa la interface
        if (!($module instanceof Flavor_Chat_Module_Interface)) {
            flavor_chat_ia_log("Módulo no implementa interface: {$module_id}", 'error');
            return false;
        }

        // Si no puede activarse, intentar crear tablas automáticamente
        if (!$module->can_activate()) {
            flavor_chat_ia_log("Intentando crear tablas para módulo: {$module_id}", 'info');

            // Intentar activate() primero (algunos módulos lo implementan)
            if (method_exists($module, 'activate')) {
                $module->activate();
            }

            // Si sigue sin poder, intentar maybe_create_tables() directamente
            if (!$module->can_activate() && method_exists($module, 'maybe_create_tables')) {
                $module->maybe_create_tables();
            }

            // Verificar de nuevo tras crear tablas
            if (!$module->can_activate()) {
                flavor_chat_ia_log("Módulo no puede activarse tras crear tablas: {$module_id} - " . $module->get_activation_error(), 'warning');
                return false;
            }
        }

        // Inicializar
        $module->init();

        $this->loaded_modules[$module_id] = $module;

        flavor_chat_ia_log("Módulo cargado: {$module_id}", 'info');

        return true;
    }

    /**
     * Verifica si un módulo está activo (método estático para dependency checker)
     *
     * @param string $module_id ID del módulo (acepta guiones o guiones bajos)
     * @return bool
     */
    public static function is_module_active($module_id) {
        $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion_plugin['active_modules'] ?? ['woocommerce'];

        // Normalizar: el dependency checker puede enviar 'chat-core' pero
        // el loader usa 'chat_core' con guiones bajos
        $id_normalizado = str_replace('-', '_', $module_id);

        // Verificar tanto el ID original como el normalizado
        return in_array($module_id, $modulos_activos, true)
            || in_array($id_normalizado, $modulos_activos, true);
    }

    /**
     * Obtiene un módulo cargado
     *
     * @param string $module_id
     * @return Flavor_Chat_Module_Interface|null
     */
    public function get_module($module_id) {
        return $this->loaded_modules[$module_id] ?? null;
    }

    /**
     * Obtiene todos los módulos cargados
     *
     * @return array
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }

    /**
     * Obtiene todos los módulos registrados (para admin)
     *
     * @return array
     */
    public function get_registered_modules() {
        $modules_info = [];

        foreach ($this->registered_modules as $id => $module_data) {
            // Cargar temporalmente para obtener info
            if (file_exists($module_data['file'])) {
                require_once $module_data['file'];

                if (class_exists($module_data['class'])) {
                    $module = new $module_data['class']();
                    $modules_info[$id] = [
                        'id' => $module->get_id(),
                        'name' => $module->get_name(),
                        'description' => $module->get_description(),
                        'can_activate' => $module->can_activate(),
                        'activation_error' => $module->get_activation_error(),
                        'is_loaded' => isset($this->loaded_modules[$id]),
                    ];
                }
            }
        }

        return $modules_info;
    }

    /**
     * Obtiene todas las acciones disponibles de todos los módulos
     *
     * @return array
     */
    public function get_all_actions() {
        $actions = [];

        foreach ($this->loaded_modules as $module_id => $module) {
            $module_actions = $module->get_actions();
            foreach ($module_actions as $action_name => $action_info) {
                $actions[$module_id . ':' . $action_name] = array_merge($action_info, [
                    'module' => $module_id,
                ]);
            }
        }

        return $actions;
    }

    /**
     * Ejecuta una acción de un módulo
     *
     * @param string $action_name Formato: "module_id:action" o simplemente "action"
     * @param array $params
     * @return array
     */
    public function execute_action($action_name, $params = []) {
        // Detectar formato module:action
        if (strpos($action_name, ':') !== false) {
            list($module_id, $action) = explode(':', $action_name, 2);

            if (isset($this->loaded_modules[$module_id])) {
                return $this->loaded_modules[$module_id]->execute_action($action, $params);
            }
        }

        // Buscar en todos los módulos
        foreach ($this->loaded_modules as $module) {
            $actions = $module->get_actions();
            if (isset($actions[$action_name])) {
                return $module->execute_action($action_name, $params);
            }
        }

        return [
            'success' => false,
            'error' => "Acción no encontrada: {$action_name}",
        ];
    }

    /**
     * Obtiene todas las definiciones de tools para Claude
     *
     * @return array
     */
    public function get_all_tool_definitions() {
        $tools = [];

        foreach ($this->loaded_modules as $module) {
            $module_tools = $module->get_tool_definitions();
            $tools = array_merge($tools, $module_tools);
        }

        return $tools;
    }

    /**
     * Obtiene todo el conocimiento base de los módulos
     *
     * @return string
     */
    public function get_combined_knowledge_base() {
        $knowledge = [];

        foreach ($this->loaded_modules as $module) {
            $module_knowledge = $module->get_knowledge_base();
            if (!empty($module_knowledge)) {
                $knowledge[] = "## " . $module->get_name() . "\n" . $module_knowledge;
            }
        }

        return implode("\n\n", $knowledge);
    }

    /**
     * Obtiene todas las FAQs de los módulos
     *
     * @return array
     */
    public function get_all_faqs() {
        $faqs = [];

        foreach ($this->loaded_modules as $module) {
            $module_faqs = $module->get_faqs();
            $faqs = array_merge($faqs, $module_faqs);
        }

        return $faqs;
    }
}

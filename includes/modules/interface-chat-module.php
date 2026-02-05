<?php
/**
 * Interface para módulos de Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface que deben implementar todos los módulos
 */
interface Flavor_Chat_Module_Interface {

    /**
     * Obtiene el ID único del módulo
     *
     * @return string
     */
    public function get_id();

    /**
     * Obtiene el nombre del módulo
     *
     * @return string
     */
    public function get_name();

    /**
     * Obtiene la descripción del módulo
     *
     * @return string
     */
    public function get_description();

    /**
     * Verifica si el módulo puede activarse (dependencias)
     *
     * @return bool
     */
    public function can_activate();

    /**
     * Mensaje si no puede activarse
     *
     * @return string
     */
    public function get_activation_error();

    /**
     * Inicializa el módulo
     *
     * @return void
     */
    public function init();

    /**
     * Obtiene las acciones (tools) disponibles del módulo
     *
     * @return array
     */
    public function get_actions();

    /**
     * Ejecuta una acción del módulo
     *
     * @param string $action_name
     * @param array $params
     * @return array
     */
    public function execute_action($action_name, $params);

    /**
     * Obtiene las definiciones de tools para Claude
     *
     * @return array
     */
    public function get_tool_definitions();

    /**
     * Obtiene el conocimiento base del módulo (para el system prompt)
     *
     * @return string
     */
    public function get_knowledge_base();

    /**
     * Obtiene las FAQs del módulo
     *
     * @return array
     */
    public function get_faqs();
}

/**
 * Clase base abstracta para módulos
 */
abstract class Flavor_Chat_Module_Base implements Flavor_Chat_Module_Interface {

    /**
     * ID del módulo
     */
    protected $id = '';

    /**
     * Nombre del módulo
     */
    protected $name = '';

    /**
     * Descripción del módulo
     */
    protected $description = '';

    /**
     * Configuración del módulo
     */
    protected $settings = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
    }

    /**
     * Carga la configuración del módulo
     */
    protected function load_settings() {
        $all_settings = get_option('flavor_chat_ia_module_' . $this->id, []);
        $this->settings = wp_parse_args($all_settings, $this->get_default_settings());
    }

    /**
     * Obtiene la configuración por defecto
     *
     * @return array
     */
    protected function get_default_settings() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [];
    }

    /**
     * Helper: Formatear precio
     *
     * @param float $price
     * @return string
     */
    protected function format_price($price) {
        if (function_exists('wc_price')) {
            return strip_tags(wc_price($price));
        }
        return number_format($price, 2, ',', '.') . '€';
    }

    /**
     * Helper: Sanitizar entrada
     *
     * @param mixed $input
     * @return mixed
     */
    protected function sanitize_input($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize_input'], $input);
        }
        return sanitize_text_field($input);
    }

    /**
     * Obtiene un valor de configuración
     *
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    protected function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Actualiza un valor de configuración
     *
     * @param string $key Clave de configuración
     * @param mixed $value Valor
     * @return bool
     */
    protected function update_setting($key, $value) {
        $this->settings[$key] = $value;
        return update_option('flavor_chat_ia_module_' . $this->id, $this->settings);
    }
}

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

    /**
     * Obtiene la visibilidad del módulo
     *
     * @return string 'public', 'private', 'members_only'
     */
    public function get_visibility();

    /**
     * Obtiene la capacidad requerida para acceder al módulo
     *
     * @return string Capacidad de WordPress (ej: 'read', 'edit_posts', 'manage_options')
     */
    public function get_required_capability();

    /**
     * Obtiene las dependencias del módulo (IDs de otros módulos requeridos)
     *
     * @return array Array de IDs de módulos requeridos
     */
    public function get_dependencies();

    /**
     * Obtiene las definiciones de páginas del módulo
     *
     * @return array Array de definiciones de páginas para el Page Creator
     */
    public function get_pages_definition();
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
     * ID del módulo (alias para compatibilidad)
     */
    protected $module_id = '';

    /**
     * Nombre del módulo (alias para compatibilidad)
     */
    protected $module_name = '';

    /**
     * Descripción del módulo (alias para compatibilidad)
     */
    protected $module_description = '';

    /**
     * Icono del módulo (dashicon class)
     */
    protected $module_icon = 'dashicons-admin-plugins';

    /**
     * Color del módulo (hex)
     */
    protected $module_color = '#3b82f6';

    /**
     * Icono del módulo (alias sin prefijo)
     */
    protected $icon = 'dashicons-admin-plugins';

    /**
     * Color del módulo (alias sin prefijo)
     */
    protected $color = '#3b82f6';

    /**
     * Categoría del módulo (para agrupación)
     */
    protected $category = 'general';

    /**
     * Visibilidad del módulo (alias para compatibilidad con $default_visibility)
     * Opciones: 'public', 'private', 'members_only', 'registered'
     */
    protected $visibility = 'public';

    /**
     * Versión del módulo
     */
    protected $version = '1.0.0';

    /**
     * Configuración del módulo
     */
    protected $settings = [];

    /**
     * Visibilidad por defecto del módulo
     * Opciones: 'public', 'private', 'members_only'
     */
    protected $default_visibility = 'public';

    /**
     * Capacidad requerida por defecto
     */
    protected $required_capability = 'read';

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
     * Obtiene toda la configuración del módulo
     *
     * @return array
     */
    public function get_settings() {
        return $this->settings;
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

    /**
     * {@inheritdoc}
     */
    public function get_visibility() {
        // Primero verificar si hay visibilidad configurada en admin
        $visibilidades_configuradas = get_option('flavor_modules_visibility', []);

        if (isset($visibilidades_configuradas[$this->id])) {
            return $visibilidades_configuradas[$this->id];
        }

        // Si no, usar la visibilidad por defecto del módulo
        return $this->default_visibility;
    }

    /**
     * {@inheritdoc}
     */
    public function get_required_capability() {
        // Verificar si hay capacidad configurada en admin
        $capacidades_configuradas = get_option('flavor_modules_capabilities', []);

        if (isset($capacidades_configuradas[$this->id])) {
            return $capacidades_configuradas[$this->id];
        }

        return $this->required_capability;
    }

    /**
     * Obtiene la visibilidad por defecto del módulo
     *
     * @return string
     */
    public function get_default_visibility() {
        return $this->default_visibility;
    }

    /**
     * Obtiene la capacidad requerida por defecto
     *
     * @return string
     */
    public function get_default_capability() {
        return $this->required_capability;
    }

    /**
     * {@inheritdoc}
     */
    public function get_dependencies() {
        // Por defecto, ningún módulo tiene dependencias
        // Los módulos específicos pueden sobrescribir este método
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_pages_definition() {
        // Por defecto, los módulos no declaran páginas
        // Los módulos que necesiten páginas deben sobrescribir este método
        return [];
    }
}

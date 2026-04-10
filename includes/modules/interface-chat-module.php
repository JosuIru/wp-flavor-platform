<?php
/**
 * Interface para módulos de Chat IA
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface que deben implementar todos los módulos
 */
interface Flavor_Platform_Module_Interface {

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

    /**
     * Obtiene los metadatos ecosistémicos del módulo.
     *
     * @return array
     */
    public function get_ecosystem_metadata();

    /**
     * Obtiene la metadata de dashboard del módulo.
     *
     * @return array
     */
    public function get_dashboard_metadata();
}

if (!interface_exists('Flavor_Chat_Module_Interface', false)) {
    class_alias('Flavor_Platform_Module_Interface', 'Flavor_Chat_Module_Interface');
}

/**
 * Clase base abstracta para módulos
 */
abstract class Flavor_Platform_Module_Base implements Flavor_Platform_Module_Interface {

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
     * Rol del módulo dentro del ecosistema.
     * Valores esperados: base|vertical|transversal
     */
    protected $module_role = 'vertical';

    /**
     * Relaciones ecosistémicas declarativas del módulo.
     */
    protected $ecosystem_supports_modules = [];
    protected $ecosystem_measures_modules = [];
    protected $ecosystem_governs_modules = [];
    protected $ecosystem_teaches_modules = [];
    protected $ecosystem_base_for_modules = [];

    /**
     * Principios transformadores Gailu que el módulo implementa.
     * Valores posibles: economia_local, cuidados, gobernanza, regeneracion, aprendizaje
     *
     * @var array
     */
    protected $gailu_principios = [];

    /**
     * Capacidades regenerativas a las que contribuye el módulo.
     * Valores posibles: autonomia, resiliencia, cohesion, impacto
     *
     * @var array
     */
    protected $gailu_contribuye_a = [];

    /**
     * Metadata declarativa para dashboards.
     */
    protected $dashboard_parent_module = '';
    protected $dashboard_satellite_priority = 50;
    protected $dashboard_transversal_priority = 50;
    protected $dashboard_client_contexts = [];
    protected $dashboard_admin_contexts = [];

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
        $module_id = $this->id ?: $this->module_id;
        $all_settings = $module_id ? flavor_get_module_settings($module_id) : [];
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
        return $this->id ?: $this->module_id;
    }

    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return $this->name ?: $this->module_name;
    }

    /**
     * {@inheritdoc}
     */
    public function get_description() {
        return $this->description ?: $this->module_description;
    }

    /**
     * Obtiene el icono del módulo.
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon ?: $this->module_icon;
    }

    /**
     * Obtiene el color del módulo.
     *
     * @return string
     */
    public function get_color() {
        return $this->color ?: $this->module_color;
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
        return flavor_update_module_settings($this->id, $this->settings);
    }

    /**
     * {@inheritdoc}
     */
    public function get_visibility() {
        // Usar caché centralizada del Module Loader
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $visibilidades_configuradas = Flavor_Platform_Module_Loader::get_visibility_settings_cached();
        } else {
            $visibilidades_configuradas = get_option('flavor_modules_visibility', []);
        }

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
        // Usar caché centralizada del Module Loader
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $capacidades_configuradas = Flavor_Platform_Module_Loader::get_capabilities_settings_cached();
        } else {
            $capacidades_configuradas = get_option('flavor_modules_capabilities', []);
        }

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

    /**
     * {@inheritdoc}
     */
    public function get_ecosystem_metadata() {
        return [
            'module_role' => $this->module_role ?: 'vertical',
            'display_role' => $this->get_display_ecosystem_role(),
            'display_role_label' => $this->get_display_ecosystem_role_label(),
            'depends_on' => array_values(array_unique(array_filter((array) $this->get_dependencies()))),
            'ecosystem_supports_modules' => array_values(array_unique(array_filter((array) $this->get_ecosystem_supports_modules_dynamic()))),
            'supports_modules' => array_values(array_unique(array_filter((array) $this->get_ecosystem_supports_modules_dynamic()))),
            'measures_modules' => array_values(array_unique(array_filter((array) $this->ecosystem_measures_modules))),
            'governs_modules' => array_values(array_unique(array_filter((array) $this->ecosystem_governs_modules))),
            'teaches_modules' => array_values(array_unique(array_filter((array) $this->ecosystem_teaches_modules))),
            'base_for_modules' => array_values(array_unique(array_filter((array) $this->ecosystem_base_for_modules))),
            // Principios Gailu
            'gailu_principios' => array_values(array_unique(array_filter((array) $this->gailu_principios))),
            'gailu_contribuye_a' => array_values(array_unique(array_filter((array) $this->gailu_contribuye_a))),
            // Metadata dashboard
            'dashboard_parent_module' => $this->dashboard_parent_module,
            'dashboard_satellite_priority' => $this->dashboard_satellite_priority,
            'dashboard_client_contexts' => $this->dashboard_client_contexts,
            'dashboard_admin_contexts' => $this->dashboard_admin_contexts,
        ];
    }

    /**
     * Obtiene los módulos soportados dinámicamente (BD o código).
     *
     * Lee primero desde la base de datos (configuración dinámica),
     * luego desde el contexto específico, y finalmente usa el fallback del código.
     *
     * @param string $context Contexto específico (ej: 'comunidad_123'). Por defecto 'global'.
     * @return array IDs de módulos soportados
     */
    protected function get_ecosystem_supports_modules_dynamic($context = '') {
        global $wpdb;

        // Determinar contexto
        if (empty($context)) {
            $context = $this->get_current_context();
        }

        // Intentar obtener desde BD para contexto específico
        if (!empty($context) && $context !== 'global') {
            $table = $wpdb->prefix . 'flavor_module_relations';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $relaciones = $wpdb->get_col($wpdb->prepare(
                    "SELECT child_module_id
                     FROM $table
                     WHERE parent_module_id = %s AND context = %s AND enabled = 1
                     ORDER BY priority ASC",
                    $this->id,
                    $context
                ));

                if (!empty($relaciones)) {
                    return $relaciones;
                }
            }
        }

        // Intentar obtener desde BD para contexto global
        $table = $wpdb->prefix . 'flavor_module_relations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $relaciones = $wpdb->get_col($wpdb->prepare(
                "SELECT child_module_id
                 FROM $table
                 WHERE parent_module_id = %s AND context = 'global' AND enabled = 1
                 ORDER BY priority ASC",
                $this->id
            ));

            if (!empty($relaciones)) {
                return $relaciones;
            }
        }

        // Fallback: devolver configuración hardcoded
        return (array) $this->ecosystem_supports_modules;
    }

    /**
     * Obtiene el contexto actual (global o específico de comunidad/entidad).
     *
     * @return string
     */
    protected function get_current_context() {
        // Si hay un contexto específico en query params
        if (!empty($_GET['context'])) {
            return sanitize_text_field($_GET['context']);
        }

        // Si hay un contexto de comunidad activa en sesión
        if (!empty($_SESSION['flavor_current_comunidad_id'])) {
            return 'comunidad_' . intval($_SESSION['flavor_current_comunidad_id']);
        }

        // Por defecto, global
        return 'global';
    }

    /**
     * Obtiene los principios Gailu que implementa el módulo.
     *
     * @return array
     */
    public function get_gailu_principios() {
        return array_values(array_unique(array_filter((array) $this->gailu_principios)));
    }

    /**
     * Obtiene las capacidades regenerativas a las que contribuye.
     *
     * @return array
     */
    public function get_gailu_contribuciones() {
        return array_values(array_unique(array_filter((array) $this->gailu_contribuye_a)));
    }

    /**
     * Devuelve el rol visible del módulo dentro del ecosistema.
     *
     * @return string
     */
    public function get_display_ecosystem_role() {
        $role = $this->module_role ?: 'vertical';

        if ($role === 'base' && empty($this->ecosystem_base_for_modules)) {
            return 'base-standalone';
        }

        return $role;
    }

    /**
     * Devuelve la etiqueta visible del rol ecosistémico.
     *
     * @return string
     */
    public function get_display_ecosystem_role_label() {
        $labels = [
            'base' => __('Base', 'flavor-platform'),
            'base-standalone' => __('Base local', 'flavor-platform'),
            'vertical' => __('Vertical', 'flavor-platform'),
            'transversal' => __('Transversal', 'flavor-platform'),
        ];

        $role = $this->get_display_ecosystem_role();

        return $labels[$role] ?? ucfirst((string) $role);
    }

    /**
     * {@inheritdoc}
     */
    public function get_dashboard_metadata() {
        $default_parent = $this->dashboard_parent_module;
        if ($default_parent === '' && $this->module_role !== 'base') {
            $dependencies = array_values(array_unique(array_filter((array) $this->get_dependencies())));
            $default_parent = $dependencies[0] ?? '';
        }

        return [
            'parent_module' => $default_parent,
            'satellite_priority' => absint($this->dashboard_satellite_priority),
            'transversal_priority' => absint($this->dashboard_transversal_priority),
            'client_contexts' => array_values(array_unique(array_filter((array) $this->dashboard_client_contexts))),
            'admin_contexts' => array_values(array_unique(array_filter((array) $this->dashboard_admin_contexts))),
        ];
    }
}

if (!class_exists('Flavor_Chat_Module_Base', false)) {
    class_alias('Flavor_Platform_Module_Base', 'Flavor_Chat_Module_Base');
}

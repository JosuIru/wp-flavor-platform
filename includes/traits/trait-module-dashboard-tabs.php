<?php
/**
 * Trait: Module Dashboard Tabs
 *
 * Proporciona funcionalidad para definir tabs de dashboard de forma flexible.
 * Los módulos que usen este trait pueden definir sus propios tabs
 * sin depender de configuración centralizada en class-dynamic-pages.php
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Module_Dashboard_Tabs_Trait {

    /**
     * Configuración de tabs del módulo
     *
     * @var array
     */
    protected $dashboard_tabs = [];

    /**
     * Obtiene los tabs del dashboard para este módulo
     *
     * Formato de cada tab:
     * [
     *     'id' => [
     *         'label'    => 'Nombre visible',
     *         'icon'     => 'dashicons-icon-name',
     *         'content'  => '[shortcode]' | callable | 'template:nombre.php',
     *         'priority' => 10, // Opcional, para ordenar
     *         'cap'      => 'read', // Opcional, capability requerida
     *         'badge'    => callable|int, // Opcional, muestra contador
     *     ]
     * ]
     *
     * @return array Configuración de tabs
     */
    public function get_dashboard_tabs() {
        // Si hay tabs configurados manualmente, usarlos
        if (!empty($this->dashboard_tabs)) {
            $tabs = $this->dashboard_tabs;
        } else {
            // Método hook para que subclases definan tabs
            $tabs = $this->define_dashboard_tabs();
        }

        // Añadir tabs de integración de módulos de red (si el trait está disponible)
        $tabs = $this->merge_integration_tabs($tabs);

        return $this->apply_tab_filters($tabs);
    }

    /**
     * Combina los tabs base con los tabs de integración de módulos de red
     *
     * @param array $tabs Tabs base del módulo
     * @return array Tabs combinados
     */
    protected function merge_integration_tabs($tabs) {
        // Verificar si el módulo usa el trait de integraciones
        if (!method_exists($this, 'get_integration_tabs')) {
            // Cargar el trait si está disponible
            if (trait_exists('Flavor_Module_Tab_Integrations_Trait')) {
                // El trait no está usado, intentar obtener integraciones de forma alternativa
                $integration_tabs = $this->get_integration_tabs_fallback();
            } else {
                return $tabs;
            }
        } else {
            $integration_tabs = $this->get_integration_tabs();
        }

        if (empty($integration_tabs)) {
            return $tabs;
        }

        // Combinar tabs base con tabs de integración
        return array_merge($tabs, $integration_tabs);
    }

    /**
     * Obtiene tabs de integración sin usar el trait dedicado
     *
     * @return array
     */
    protected function get_integration_tabs_fallback() {
        // Verificar que el loader de módulos existe
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $tabs = [];
        $modulo_actual = $this->get_module_id_for_tabs();
        $entity_id = $this->get_current_entity_id_for_tabs();

        $loader = Flavor_Chat_Module_Loader::get_instance();

        // Módulos de red que pueden proveer tabs
        $modulos_red = ['foros', 'red_social', 'chat_grupos', 'multimedia', 'podcast', 'comunidades'];

        foreach ($modulos_red as $mod_id) {
            // No integrar consigo mismo
            if ($mod_id === str_replace('-', '_', $modulo_actual)) {
                continue;
            }

            if (!$loader->is_module_active($mod_id)) {
                continue;
            }

            $modulo = $loader->get_module($mod_id);
            if (!$modulo || !method_exists($modulo, 'get_tab_integrations')) {
                continue;
            }

            $integraciones = $modulo->get_tab_integrations();

            // Buscar para módulo actual (probar variantes del nombre)
            $variantes = [
                $modulo_actual,
                str_replace('-', '_', $modulo_actual),
                str_replace('_', '-', $modulo_actual),
            ];

            foreach ($variantes as $variante) {
                if (isset($integraciones[$variante])) {
                    $tab_config = $integraciones[$variante];

                    // Preparar el tab
                    if (isset($tab_config['id'])) {
                        $tab = $this->prepare_integration_tab_simple($tab_config, $entity_id);
                        if ($tab) {
                            $tabs[$tab_config['id']] = $tab;
                        }
                    }
                    break;
                }
            }
        }

        return $tabs;
    }

    /**
     * Prepara un tab de integración de forma simple
     *
     * @param array $config    Configuración del tab
     * @param int   $entity_id ID de la entidad
     * @return array|null
     */
    protected function prepare_integration_tab_simple($config, $entity_id) {
        if (empty($config['id']) || empty($config['label'])) {
            return null;
        }

        $tab = [
            'label'          => $config['label'],
            'icon'           => $config['icon'] ?? 'dashicons-admin-generic',
            'priority'       => $config['priority'] ?? 100,
            'is_integration' => true,
        ];

        // Procesar contenido
        $content = $config['content'] ?? '';
        if (is_string($content)) {
            $content = str_replace('{entity_id}', $entity_id, $content);
        }
        $tab['content'] = $content;

        return $tab;
    }

    /**
     * Obtiene el ID de la entidad actual (para tabs de integración)
     *
     * @return int
     */
    protected function get_current_entity_id_for_tabs() {
        // Desde query var
        $id = get_query_var('flavor_item_id', 0);
        if ($id) {
            return absint($id);
        }

        // Desde GET
        if (isset($_GET['id'])) {
            return absint($_GET['id']);
        }

        // Desde post actual
        global $post;
        if ($post) {
            return $post->ID;
        }

        return 0;
    }

    /**
     * Define los tabs del dashboard
     *
     * Los módulos deben sobrescribir este método para definir sus tabs.
     *
     * @return array
     */
    protected function define_dashboard_tabs() {
        return [];
    }

    /**
     * Registra un tab en el dashboard
     *
     * @param string $tab_id    Identificador único del tab
     * @param array  $tab_config Configuración del tab
     * @return self
     */
    public function register_dashboard_tab($tab_id, $tab_config) {
        $defaults = [
            'label'    => ucfirst(str_replace(['-', '_'], ' ', $tab_id)),
            'icon'     => 'dashicons-admin-generic',
            'content'  => '',
            'priority' => 10,
            'cap'      => 'read',
            'badge'    => null,
        ];

        $this->dashboard_tabs[$tab_id] = wp_parse_args($tab_config, $defaults);

        // Ordenar por prioridad
        uasort($this->dashboard_tabs, function($a, $b) {
            $prioridad_a = $a['priority'] ?? 10;
            $prioridad_b = $b['priority'] ?? 10;
            return $prioridad_a - $prioridad_b;
        });

        return $this;
    }

    /**
     * Elimina un tab del dashboard
     *
     * @param string $tab_id ID del tab a eliminar
     * @return self
     */
    public function unregister_dashboard_tab($tab_id) {
        unset($this->dashboard_tabs[$tab_id]);
        return $this;
    }

    /**
     * Aplica filtros a los tabs antes de retornarlos
     *
     * @param array $tabs Tabs originales
     * @return array Tabs filtrados
     */
    protected function apply_tab_filters($tabs) {
        // Obtener el ID del módulo
        $module_id = $this->get_module_id_for_tabs();

        // Filtro para que otros plugins/temas modifiquen los tabs
        $tabs = apply_filters('flavor_module_dashboard_tabs', $tabs, $module_id);
        $tabs = apply_filters("flavor_{$module_id}_dashboard_tabs", $tabs);

        // Filtrar por capabilities
        $tabs = $this->filter_tabs_by_capability($tabs);

        return $tabs;
    }

    /**
     * Filtra tabs según las capabilities del usuario
     *
     * @param array $tabs
     * @return array
     */
    protected function filter_tabs_by_capability($tabs) {
        if (!is_user_logged_in()) {
            return array_filter($tabs, function($tab) {
                return ($tab['cap'] ?? 'read') === 'read';
            });
        }

        return array_filter($tabs, function($tab) {
            $cap_requerida = $tab['cap'] ?? 'read';
            return current_user_can($cap_requerida);
        });
    }

    /**
     * Obtiene el ID del módulo para usar en filtros
     *
     * @return string
     */
    protected function get_module_id_for_tabs() {
        // Intentar obtener de propiedades comunes
        if (property_exists($this, 'module_id')) {
            return $this->module_id;
        }
        if (property_exists($this, 'id')) {
            return $this->id;
        }
        if (property_exists($this, 'slug')) {
            return $this->slug;
        }

        // Fallback: usar nombre de clase
        return strtolower(str_replace(['Flavor_', '_Module', 'Module'], '', get_class($this)));
    }

    /**
     * Renderiza el contenido de un tab
     *
     * @param string $tab_id ID del tab
     * @param array  $tab_config Configuración del tab
     * @return string HTML del contenido
     */
    public function render_tab_content($tab_id, $tab_config = null) {
        if ($tab_config === null) {
            $tabs = $this->get_dashboard_tabs();
            $tab_config = $tabs[$tab_id] ?? null;
        }

        if (!$tab_config) {
            return '<p>' . esc_html__('Tab no encontrado', 'flavor-platform') . '</p>';
        }

        $contenido = $tab_config['content'] ?? '';

        // Shortcode
        if (is_string($contenido) && strpos($contenido, '[') === 0) {
            return do_shortcode($contenido);
        }

        // Template
        if (is_string($contenido) && strpos($contenido, 'template:') === 0) {
            $template_name = str_replace('template:', '', $contenido);
            return $this->load_tab_template($template_name, $tab_id);
        }

        // Callback
        if (is_callable($contenido)) {
            ob_start();
            call_user_func($contenido, $tab_id, $this);
            return ob_get_clean();
        }

        // Método del módulo
        if (is_string($contenido) && method_exists($this, $contenido)) {
            ob_start();
            $this->{$contenido}();
            return ob_get_clean();
        }

        // String directo
        if (is_string($contenido)) {
            return $contenido;
        }

        return '';
    }

    /**
     * Carga un template para un tab
     *
     * @param string $template_name Nombre del template
     * @param string $tab_id ID del tab
     * @return string HTML
     */
    protected function load_tab_template($template_name, $tab_id) {
        $module_id = $this->get_module_id_for_tabs();
        $module_slug = str_replace('_', '-', $module_id);

        // Buscar template en orden de prioridad
        $paths = [
            get_stylesheet_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            get_template_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/tabs/{$template_name}",
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                ob_start();
                include $path;
                return ob_get_clean();
            }
        }

        return '<p>' . sprintf(
            esc_html__('Template %s no encontrado', 'flavor-platform'),
            esc_html($template_name)
        ) . '</p>';
    }

    /**
     * Obtiene el badge (contador) de un tab
     *
     * @param array $tab_config Configuración del tab
     * @return int|null
     */
    public function get_tab_badge($tab_config) {
        $badge = $tab_config['badge'] ?? null;

        if (is_null($badge)) {
            return null;
        }

        if (is_numeric($badge)) {
            return intval($badge);
        }

        if (is_callable($badge)) {
            return intval(call_user_func($badge, $this));
        }

        return null;
    }

    /**
     * Helpers para crear tabs comunes rápidamente
     */

    /**
     * Crea un tab de listado
     *
     * @param string $shortcode Shortcode del listado
     * @param array  $options   Opciones adicionales
     * @return array
     */
    protected function tab_listado($shortcode, $options = []) {
        return array_merge([
            'label'    => __('Listado', 'flavor-platform'),
            'icon'     => 'dashicons-list-view',
            'content'  => $shortcode,
            'priority' => 10,
        ], $options);
    }

    /**
     * Crea un tab "Mis X" (contenido del usuario)
     *
     * @param string $shortcode Shortcode
     * @param string $label     Label del tab
     * @param array  $options   Opciones adicionales
     * @return array
     */
    protected function tab_mis($shortcode, $label, $options = []) {
        return array_merge([
            'label'    => $label,
            'icon'     => 'dashicons-admin-users',
            'content'  => $shortcode,
            'priority' => 20,
            'cap'      => 'read',
        ], $options);
    }

    /**
     * Crea un tab de mapa
     *
     * @param string $shortcode Shortcode del mapa
     * @param array  $options   Opciones adicionales
     * @return array
     */
    protected function tab_mapa($shortcode, $options = []) {
        return array_merge([
            'label'    => __('Mapa', 'flavor-platform'),
            'icon'     => 'dashicons-location',
            'content'  => $shortcode,
            'priority' => 50,
        ], $options);
    }

    /**
     * Crea un tab de calendario
     *
     * @param string $shortcode Shortcode del calendario
     * @param array  $options   Opciones adicionales
     * @return array
     */
    protected function tab_calendario($shortcode, $options = []) {
        return array_merge([
            'label'    => __('Calendario', 'flavor-platform'),
            'icon'     => 'dashicons-calendar-alt',
            'content'  => $shortcode,
            'priority' => 40,
        ], $options);
    }

    /**
     * Crea un tab de estadísticas
     *
     * @param string $shortcode Shortcode de estadísticas
     * @param array  $options   Opciones adicionales
     * @return array
     */
    protected function tab_estadisticas($shortcode, $options = []) {
        return array_merge([
            'label'    => __('Estadísticas', 'flavor-platform'),
            'icon'     => 'dashicons-chart-bar',
            'content'  => $shortcode,
            'priority' => 60,
            'cap'      => 'read',
        ], $options);
    }
}

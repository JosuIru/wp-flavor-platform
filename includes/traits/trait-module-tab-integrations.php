<?php
/**
 * Trait: Module Tab Integrations
 *
 * Permite que módulos de red (foros, chat, red-social, multimedia, podcast)
 * inyecten tabs automáticamente en otros módulos cuando están activos.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Module_Tab_Integrations_Trait {

    /**
     * Módulos de red que pueden proveer tabs de integración
     *
     * @var array
     */
    protected static $network_modules = [
        'foros'        => 'Flavor_Chat_Foros_Module',
        'red_social'   => 'Flavor_Chat_Red_Social_Module',
        'chat_grupos'  => 'Flavor_Chat_Chat_Grupos_Module',
        'multimedia'   => 'Flavor_Chat_Multimedia_Module',
        'podcast'      => 'Flavor_Chat_Podcast_Module',
        'comunidades'  => 'Flavor_Chat_Comunidades_Module',
    ];

    /**
     * Cache de integraciones cargadas
     *
     * @var array|null
     */
    protected $integration_tabs_cache = null;

    /**
     * Obtiene tabs de integración de módulos de red activos
     *
     * @param int|null $entity_id ID de la entidad actual (grupo, evento, etc.)
     * @return array Tabs adicionales de módulos de red
     */
    public function get_integration_tabs($entity_id = null) {
        // Usar cache si existe
        if ($this->integration_tabs_cache !== null && $entity_id === null) {
            return $this->integration_tabs_cache;
        }

        $tabs = [];
        $modulo_actual = $this->get_module_id_for_integration();

        // Si no hay entity_id, intentar obtenerlo del contexto
        if ($entity_id === null) {
            $entity_id = $this->get_current_entity_id();
        }

        // Contexto para pasar a las integraciones
        $contexto = [
            'module_id'   => $modulo_actual,
            'entity_id'   => $entity_id,
            'entity_type' => $this->get_entity_type(),
            'user_id'     => get_current_user_id(),
        ];

        // Obtener el loader de módulos
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return $tabs;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();

        foreach (self::$network_modules as $mod_id => $clase) {
            // No integrar un módulo consigo mismo
            if ($mod_id === $modulo_actual) {
                continue;
            }

            // Verificar si el módulo está activo
            if (!$loader->is_module_active($mod_id)) {
                continue;
            }

            // Obtener instancia del módulo
            $modulo = $loader->get_module($mod_id);
            if (!$modulo) {
                continue;
            }

            // Verificar si el módulo provee integraciones
            if (!method_exists($modulo, 'get_tab_integrations')) {
                continue;
            }

            // Obtener integraciones del módulo
            $integraciones = $modulo->get_tab_integrations();

            // Buscar integración para el módulo actual
            if (!isset($integraciones[$modulo_actual])) {
                continue;
            }

            $tab_config = $integraciones[$modulo_actual];

            // Puede ser un solo tab o múltiples
            if (isset($tab_config['id'])) {
                // Un solo tab
                $tab = $this->prepare_integration_tab($tab_config, $contexto, $modulo);
                if ($tab) {
                    $tabs[$tab_config['id']] = $tab;
                }
            } else {
                // Múltiples tabs
                foreach ($tab_config as $single_tab) {
                    $tab = $this->prepare_integration_tab($single_tab, $contexto, $modulo);
                    if ($tab && isset($single_tab['id'])) {
                        $tabs[$single_tab['id']] = $tab;
                    }
                }
            }
        }

        // Ordenar por prioridad
        uasort($tabs, function($a, $b) {
            $priority_a = $a['priority'] ?? 100;
            $priority_b = $b['priority'] ?? 100;
            return $priority_a - $priority_b;
        });

        // Marcar tabs como de integración para CSS
        foreach ($tabs as $id => &$tab) {
            $tab['is_integration'] = true;
        }

        // Cachear resultado
        if ($entity_id === null) {
            $this->integration_tabs_cache = $tabs;
        }

        return $tabs;
    }

    /**
     * Prepara un tab de integración con el contexto actual
     *
     * @param array  $tab_config Configuración del tab
     * @param array  $contexto   Contexto actual
     * @param object $modulo     Módulo proveedor
     * @return array|null Tab preparado o null si no es válido
     */
    protected function prepare_integration_tab($tab_config, $contexto, $modulo) {
        // Verificar configuración mínima
        if (empty($tab_config['id']) || empty($tab_config['label'])) {
            return null;
        }

        // Verificar requisitos
        if (!empty($tab_config['requires'])) {
            if (!$this->check_integration_requirements($tab_config['requires'], $contexto)) {
                return null;
            }
        }

        // Verificar capability
        $cap = $tab_config['cap'] ?? 'read';
        if (!current_user_can($cap)) {
            return null;
        }

        // Preparar el tab
        $tab = [
            'label'          => $tab_config['label'],
            'icon'           => $tab_config['icon'] ?? 'dashicons-admin-generic',
            'priority'       => $tab_config['priority'] ?? 100,
            'cap'            => $cap,
            'source_module'  => $modulo->id ?? 'unknown',
            'is_integration' => true,
        ];

        // Procesar contenido
        $content = $tab_config['content'] ?? '';

        if (is_callable($content)) {
            // Contenido como callable
            $tab['content'] = function() use ($content, $contexto, $modulo) {
                return call_user_func($content, $contexto, $modulo);
            };
        } elseif (is_string($content)) {
            // Reemplazar placeholders en shortcodes
            $content = str_replace(
                ['{entity_id}', '{module_id}', '{entity_type}', '{user_id}'],
                [
                    $contexto['entity_id'],
                    $contexto['module_id'],
                    $contexto['entity_type'],
                    $contexto['user_id'],
                ],
                $content
            );
            $tab['content'] = $content;
        }

        // Procesar badge
        if (isset($tab_config['badge'])) {
            if (is_callable($tab_config['badge'])) {
                $tab['badge'] = function() use ($tab_config, $contexto, $modulo) {
                    return call_user_func($tab_config['badge'], $contexto, $modulo);
                };
            } else {
                $tab['badge'] = $tab_config['badge'];
            }
        }

        return $tab;
    }

    /**
     * Verifica requisitos de una integración
     *
     * @param mixed $requirements Requisitos a verificar
     * @param array $contexto     Contexto actual
     * @return bool
     */
    protected function check_integration_requirements($requirements, $contexto) {
        // Si es string, verificar entity_type
        if (is_string($requirements)) {
            return $contexto['entity_type'] === $requirements;
        }

        // Si es array, verificar todos
        if (is_array($requirements)) {
            foreach ($requirements as $key => $value) {
                if (is_numeric($key)) {
                    // Lista de entity_types válidos
                    if (!in_array($contexto['entity_type'], $requirements)) {
                        return false;
                    }
                    break;
                } else {
                    // Pares key => value
                    if (!isset($contexto[$key]) || $contexto[$key] !== $value) {
                        return false;
                    }
                }
            }
        }

        // Si es callable
        if (is_callable($requirements)) {
            return (bool) call_user_func($requirements, $contexto);
        }

        return true;
    }

    /**
     * Obtiene el ID del módulo para integraciones
     *
     * @return string
     */
    protected function get_module_id_for_integration() {
        // Intentar obtener de propiedades comunes
        if (property_exists($this, 'id')) {
            return $this->id;
        }
        if (property_exists($this, 'module_id')) {
            return $this->module_id;
        }

        // Fallback: usar nombre de clase
        $class = get_class($this);
        $class = str_replace(['Flavor_Chat_', '_Module', 'Flavor_'], '', $class);
        return strtolower(str_replace('_', '-', $class));
    }

    /**
     * Obtiene el ID de la entidad actual
     *
     * Los módulos pueden sobrescribir este método.
     *
     * @return int
     */
    protected function get_current_entity_id() {
        // Intentar desde query var
        $entity_id = get_query_var('flavor_item_id', 0);
        if ($entity_id) {
            return absint($entity_id);
        }

        // Intentar desde GET
        if (isset($_GET['id'])) {
            return absint($_GET['id']);
        }

        // Intentar desde el post actual
        global $post;
        if ($post && is_singular()) {
            return $post->ID;
        }

        return 0;
    }

    /**
     * Obtiene el tipo de entidad actual
     *
     * Los módulos pueden sobrescribir este método.
     *
     * @return string
     */
    protected function get_entity_type() {
        global $post;

        if ($post && is_singular()) {
            return $post->post_type;
        }

        // Fallback al ID del módulo
        return $this->get_module_id_for_integration();
    }

    /**
     * Verifica si hay integraciones disponibles para este módulo
     *
     * @return bool
     */
    public function has_integration_tabs() {
        $tabs = $this->get_integration_tabs();
        return !empty($tabs);
    }

    /**
     * Obtiene el conteo de tabs de integración
     *
     * @return int
     */
    public function count_integration_tabs() {
        return count($this->get_integration_tabs());
    }

    /**
     * Limpia la cache de integraciones
     */
    public function clear_integration_cache() {
        $this->integration_tabs_cache = null;
    }

    /**
     * Verifica si un módulo de red específico está disponible
     *
     * @param string $module_id ID del módulo
     * @return bool
     */
    public function is_network_module_available($module_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return false;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        return $loader->is_module_active($module_id);
    }
}

<?php
/**
 * Resolvedor de Dependencias entre Módulos
 *
 * Gestiona automáticamente las dependencias entre módulos:
 * - Valida dependencias antes de activar
 * - Auto-activa módulos requeridos
 * - Previene desactivar módulos si otros dependen de ellos
 * - Genera mapas visuales de dependencias
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Dependency_Resolver {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Caché del mapa de dependencias
     */
    private $dependency_map = null;

    /**
     * Caché del mapa inverso (qué módulos dependen de X)
     */
    private $reverse_dependency_map = null;

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
        // Hook antes de activar módulo
        add_filter('flavor_before_activate_module', [$this, 'validate_and_resolve_dependencies'], 10, 2);

        // Hook antes de desactivar módulo
        add_filter('flavor_before_deactivate_module', [$this, 'check_dependent_modules'], 10, 2);

        // Limpiar caché cuando se activa/desactiva módulo
        add_action('flavor_module_activated', [$this, 'clear_cache']);
        add_action('flavor_module_deactivated', [$this, 'clear_cache']);
    }

    /**
     * Valida y resuelve dependencias antes de activar un módulo
     *
     * @param bool $can_activate
     * @param string $module_id
     * @return bool|WP_Error
     */
    public function validate_and_resolve_dependencies($can_activate, $module_id) {
        if (!$can_activate) {
            return $can_activate;
        }

        $dependencies = $this->get_module_dependencies($module_id);

        if (empty($dependencies)) {
            return true; // Sin dependencias, puede activarse
        }

        $missing_dependencies = $this->get_missing_dependencies($module_id);

        if (empty($missing_dependencies)) {
            return true; // Todas las dependencias ya están activas
        }

        // Intentar auto-activar dependencias
        $auto_activated = $this->auto_activate_dependencies($missing_dependencies);

        if ($auto_activated !== true) {
            // Falló la auto-activación
            $module_name = $this->get_module_name($module_id);
            $missing_names = array_map([$this, 'get_module_name'], $missing_dependencies);

            return new WP_Error(
                'missing_dependencies',
                sprintf(
                    __('No se puede activar "%s" porque requiere los siguientes módulos: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $module_name,
                    implode(', ', $missing_names)
                ),
                ['missing' => $missing_dependencies]
            );
        }

        return true;
    }

    /**
     * Verifica si hay módulos que dependen del que se intenta desactivar
     *
     * @param bool $can_deactivate
     * @param string $module_id
     * @return bool|WP_Error
     */
    public function check_dependent_modules($can_deactivate, $module_id) {
        if (!$can_deactivate) {
            return $can_deactivate;
        }

        $dependents = $this->get_dependent_modules($module_id);

        if (empty($dependents)) {
            return true; // Ningún módulo depende de este
        }

        // Filtrar solo los dependientes que están activos
        $active_dependents = [];
        $loader = Flavor_Platform_Module_Loader::get_instance();

        foreach ($dependents as $dependent_id) {
            if (Flavor_Platform_Module_Loader::is_module_active($dependent_id)) {
                $active_dependents[] = $dependent_id;
            }
        }

        if (empty($active_dependents)) {
            return true;
        }

        // Hay módulos activos que dependen de este
        $module_name = $this->get_module_name($module_id);
        $dependent_names = array_map([$this, 'get_module_name'], $active_dependents);

        return new WP_Error(
            'has_dependents',
            sprintf(
                __('No se puede desactivar "%s" porque los siguientes módulos lo requieren: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $module_name,
                implode(', ', $dependent_names)
            ),
            ['dependents' => $active_dependents]
        );
    }

    /**
     * Obtiene las dependencias de un módulo
     *
     * @param string $module_id
     * @return array
     */
    public function get_module_dependencies($module_id) {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $instance = $loader->get_module_instance($module_id);

        if (!$instance || !method_exists($instance, 'get_dependencies')) {
            return [];
        }

        return $instance->get_dependencies();
    }

    /**
     * Obtiene las dependencias faltantes (no activas) de un módulo
     *
     * @param string $module_id
     * @return array
     */
    public function get_missing_dependencies($module_id) {
        $dependencies = $this->get_module_dependencies($module_id);

        if (empty($dependencies)) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $missing = [];

        foreach ($dependencies as $dep_id) {
            if (!Flavor_Platform_Module_Loader::is_module_active($dep_id)) {
                $missing[] = $dep_id;
            }
        }

        return $missing;
    }

    /**
     * Obtiene los módulos que dependen de un módulo dado
     *
     * @param string $module_id
     * @return array
     */
    public function get_dependent_modules($module_id) {
        $map = $this->get_reverse_dependency_map();
        return $map[$module_id] ?? [];
    }

    /**
     * Auto-activa las dependencias faltantes
     *
     * @param array $dependencies Array de IDs de módulos a activar
     * @return bool|WP_Error True si se activaron todas, WP_Error si falló alguna
     */
    private function auto_activate_dependencies($dependencies) {
        if (!class_exists('Flavor_App_Profiles')) {
            return new WP_Error('no_profiles_class', __('Sistema de perfiles no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $gestor = Flavor_App_Profiles::get_instance();
        $failed = [];

        foreach ($dependencies as $dep_id) {
            // Verificar dependencias recursivas
            $sub_dependencies = $this->get_missing_dependencies($dep_id);

            if (!empty($sub_dependencies)) {
                $sub_result = $this->auto_activate_dependencies($sub_dependencies);
                if (is_wp_error($sub_result)) {
                    $failed[] = $dep_id;
                    continue;
                }
            }

            // Activar la dependencia
            $result = $gestor->activar_modulo_opcional($dep_id);

            if (!$result) {
                $failed[] = $dep_id;
            } else {
                flavor_log_debug( "Auto-activado: {$dep_id}", 'Dependencies' );
            }
        }

        if (!empty($failed)) {
            $failed_names = array_map([$this, 'get_module_name'], $failed);
            return new WP_Error(
                'activation_failed',
                sprintf(
                    __('No se pudieron activar las siguientes dependencias: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    implode(', ', $failed_names)
                )
            );
        }

        return true;
    }

    /**
     * Construye el mapa completo de dependencias
     *
     * @return array ['module_id' => ['dep1', 'dep2', ...], ...]
     */
    public function get_dependency_map() {
        if ($this->dependency_map !== null) {
            return $this->dependency_map;
        }

        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $all_modules = $loader->get_registered_modules();
        $map = [];

        foreach ($all_modules as $module_id => $module_info) {
            $dependencies = $this->get_module_dependencies($module_id);
            $map[$module_id] = $dependencies;
        }

        $this->dependency_map = $map;
        return $map;
    }

    /**
     * Construye el mapa inverso de dependencias
     *
     * @return array ['module_id' => ['dependent1', 'dependent2', ...], ...]
     */
    public function get_reverse_dependency_map() {
        if ($this->reverse_dependency_map !== null) {
            return $this->reverse_dependency_map;
        }

        $dependency_map = $this->get_dependency_map();
        $reverse_map = [];

        foreach ($dependency_map as $module_id => $dependencies) {
            foreach ($dependencies as $dep_id) {
                if (!isset($reverse_map[$dep_id])) {
                    $reverse_map[$dep_id] = [];
                }
                $reverse_map[$dep_id][] = $module_id;
            }
        }

        $this->reverse_dependency_map = $reverse_map;
        return $reverse_map;
    }

    /**
     * Genera un gráfico visual de dependencias en formato Mermaid
     *
     * @return string
     */
    public function generate_dependency_graph() {
        $dependency_map = $this->get_dependency_map();

        // Filtrar solo módulos con dependencias
        $filtered = array_filter($dependency_map, function($deps) {
            return !empty($deps);
        });

        if (empty($filtered)) {
            return "graph TD\n    A[Sin dependencias]";
        }

        $graph = "graph TD\n";

        foreach ($filtered as $module_id => $dependencies) {
            $module_name = $this->get_module_name($module_id);

            foreach ($dependencies as $dep_id) {
                $dep_name = $this->get_module_name($dep_id);
                $graph .= "    {$dep_id}[\"{$dep_name}\"] --> {$module_id}[\"{$module_name}\"]\n";
            }
        }

        // Añadir estilos
        $graph .= "\n    classDef active fill:#10b981,stroke:#059669,color:#fff\n";
        $graph .= "    classDef inactive fill:#ef4444,stroke:#dc2626,color:#fff\n";

        // Marcar módulos activos
        $loader = Flavor_Platform_Module_Loader::get_instance();

        foreach (array_keys($filtered) as $module_id) {
            if (Flavor_Platform_Module_Loader::is_module_active($module_id)) {
                $graph .= "    class {$module_id} active\n";
            } else {
                $graph .= "    class {$module_id} inactive\n";
            }
        }

        return $graph;
    }

    /**
     * Genera un árbol de dependencias recursivo para un módulo
     *
     * @param string $module_id
     * @param int $depth Profundidad máxima
     * @return array
     */
    public function get_dependency_tree($module_id, $depth = 5) {
        if ($depth <= 0) {
            return [];
        }

        $dependencies = $this->get_module_dependencies($module_id);

        if (empty($dependencies)) {
            return [];
        }

        $tree = [];

        foreach ($dependencies as $dep_id) {
            $tree[$dep_id] = [
                'name' => $this->get_module_name($dep_id),
                'active' => Flavor_Platform_Module_Loader::is_module_active($dep_id),
                'dependencies' => $this->get_dependency_tree($dep_id, $depth - 1),
            ];
        }

        return $tree;
    }

    /**
     * Verifica si hay dependencias circulares
     *
     * @param string $module_id
     * @param array $visited
     * @return bool|array True si no hay circulares, array con el ciclo si lo hay
     */
    public function check_circular_dependencies($module_id, $visited = []) {
        if (in_array($module_id, $visited)) {
            // Ciclo detectado
            return array_merge($visited, [$module_id]);
        }

        $visited[] = $module_id;
        $dependencies = $this->get_module_dependencies($module_id);

        foreach ($dependencies as $dep_id) {
            $result = $this->check_circular_dependencies($dep_id, $visited);
            if (is_array($result)) {
                return $result; // Propagar el ciclo encontrado
            }
        }

        return true; // Sin ciclos
    }

    /**
     * Obtiene el nombre de un módulo
     *
     * @param string $module_id
     * @return string
     */
    private function get_module_name($module_id) {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return ucfirst(str_replace('_', ' ', $module_id));
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $instance = $loader->get_module_instance($module_id);

        if ($instance && method_exists($instance, 'get_name')) {
            return $instance->get_name();
        }

        return ucfirst(str_replace('_', ' ', $module_id));
    }

    /**
     * Verifica si un módulo está activo
     *
     * @param string $module_id
     * @return bool
     */
    private function is_module_active($module_id) {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return false;
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        return Flavor_Platform_Module_Loader::is_module_active($module_id);
    }

    /**
     * Limpia la caché de mapas de dependencias
     */
    public function clear_cache() {
        $this->dependency_map = null;
        $this->reverse_dependency_map = null;
    }

    /**
     * Genera un informe detallado de dependencias para admin
     *
     * @return array
     */
    public function generate_dependency_report() {
        $dependency_map = $this->get_dependency_map();
        $reverse_map = $this->get_reverse_dependency_map();
        $loader = Flavor_Platform_Module_Loader::get_instance();

        $report = [
            'modules_with_dependencies' => 0,
            'total_dependencies' => 0,
            'circular_dependencies' => [],
            'orphan_dependencies' => [],
            'critical_modules' => [], // Módulos de los que muchos dependen
            'details' => [],
        ];

        foreach ($dependency_map as $module_id => $dependencies) {
            if (!empty($dependencies)) {
                $report['modules_with_dependencies']++;
                $report['total_dependencies'] += count($dependencies);

                // Verificar dependencias circulares
                $circular = $this->check_circular_dependencies($module_id);
                if (is_array($circular)) {
                    $report['circular_dependencies'][] = $circular;
                }

                // Verificar dependencias huérfanas (no disponibles)
                foreach ($dependencies as $dep_id) {
                    if (!$loader->module_exists($dep_id)) {
                        $report['orphan_dependencies'][] = [
                            'module' => $module_id,
                            'missing' => $dep_id,
                        ];
                    }
                }
            }

            $report['details'][$module_id] = [
                'name' => $this->get_module_name($module_id),
                'active' => Flavor_Platform_Module_Loader::is_module_active($module_id),
                'requires' => $dependencies,
                'required_by' => $reverse_map[$module_id] ?? [],
            ];
        }

        // Identificar módulos críticos (más de 3 dependientes)
        foreach ($reverse_map as $module_id => $dependents) {
            if (count($dependents) >= 3) {
                $report['critical_modules'][] = [
                    'module' => $module_id,
                    'name' => $this->get_module_name($module_id),
                    'dependents_count' => count($dependents),
                    'dependents' => $dependents,
                ];
            }
        }

        return $report;
    }
}

// Inicializar
Flavor_Module_Dependency_Resolver::get_instance();

<?php
/**
 * Helper para consultar relaciones entre módulos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Relations_Helper {

    /**
     * Cache local de relaciones por defecto.
     *
     * @var array<string,array<int,string>>|null
     */
    private static $default_relations_cache = null;

    /**
     * Normaliza IDs de módulos para comparar slugs con guiones y guiones bajos.
     *
     * @param string $module_id
     * @return string
     */
    private static function normalize_module_id($module_id) {
        $module_id = sanitize_key((string) $module_id);
        return str_replace('-', '_', $module_id);
    }

    /**
     * Carga relaciones por defecto normalizadas.
     *
     * @return array<string,array<int,string>>
     */
    private static function get_default_relations_map() {
        if (self::$default_relations_cache !== null) {
            return self::$default_relations_cache;
        }

        $file = dirname(__FILE__) . '/data/default-module-relations.php';
        if (file_exists($file)) {
            require_once $file;
        }

        $relations = function_exists('flavor_get_default_module_relations')
            ? (array) flavor_get_default_module_relations()
            : [];

        $normalized = [];
        foreach ($relations as $parent_id => $children) {
            $normalized_parent = self::normalize_module_id($parent_id);
            $normalized[$normalized_parent] = array_values(array_unique(array_filter(array_map([__CLASS__, 'normalize_module_id'], (array) $children))));
        }

        self::$default_relations_cache = $normalized;

        return self::$default_relations_cache;
    }

    /**
     * Obtener módulos desde la API disponible del loader
     *
     * @return array
     */
    private static function get_modules_from_loader() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();

        if (method_exists($loader, 'get_all_modules')) {
            $modules = $loader->get_all_modules();
            return is_array($modules) ? $modules : [];
        }

        if (method_exists($loader, 'get_loaded_modules')) {
            $modules = $loader->get_loaded_modules();
            return is_array($modules) ? $modules : [];
        }

        return [];
    }

    /**
     * Obtener módulos horizontales vinculados a un módulo vertical
     *
     * @param string $parent_module_id ID del módulo vertical
     * @param string $context Contexto (global, comunidad_123, etc.)
     * @return array IDs de módulos horizontales
     */
    public static function get_child_modules($parent_module_id, $context = 'global') {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_module_relations';
        $parent_module_id = self::normalize_module_id($parent_module_id);
        $context = sanitize_text_field($context ?: 'global');

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return self::get_fallback_relations($parent_module_id);
        }

        // Buscar en contexto específico primero
        if ($context !== 'global') {
            $relaciones = $wpdb->get_col($wpdb->prepare(
                "SELECT child_module_id
                 FROM $table
                 WHERE parent_module_id = %s AND context = %s AND enabled = 1
                 ORDER BY priority ASC",
                $parent_module_id,
                $context
            ));

            if (!empty($relaciones)) {
                return array_values(array_unique(array_filter(array_map([__CLASS__, 'normalize_module_id'], (array) $relaciones))));
            }
        }

        // Buscar en contexto global
        $relaciones = $wpdb->get_col($wpdb->prepare(
            "SELECT child_module_id
             FROM $table
             WHERE parent_module_id = %s AND context = 'global' AND enabled = 1
             ORDER BY priority ASC",
            $parent_module_id
        ));

        // Si no hay relaciones en BD, usar fallback del código
        if (empty($relaciones)) {
            return self::get_fallback_relations($parent_module_id);
        }

        return array_values(array_unique(array_filter(array_map([__CLASS__, 'normalize_module_id'], (array) $relaciones))));
    }

    /**
     * Verificar si un módulo horizontal está vinculado a un módulo vertical
     *
     * @param string $parent_module_id ID del módulo vertical
     * @param string $child_module_id ID del módulo horizontal
     * @param string $context Contexto
     * @return bool
     */
    public static function is_child_of($parent_module_id, $child_module_id, $context = 'global') {
        $children = self::get_child_modules($parent_module_id, $context);
        return in_array(self::normalize_module_id($child_module_id), $children, true);
    }

    /**
     * Obtener relaciones desde el código (fallback)
     *
     * @param string $parent_module_id
     * @return array
     */
    private static function get_fallback_relations($parent_module_id) {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            $defaults = self::get_default_relations_map();
            return $defaults[self::normalize_module_id($parent_module_id)] ?? [];
        }

        $all_modules = self::get_modules_from_loader();

        if (!isset($all_modules[$parent_module_id])) {
            $defaults = self::get_default_relations_map();
            return $defaults[self::normalize_module_id($parent_module_id)] ?? [];
        }

        $module = $all_modules[$parent_module_id];
        if (!is_object($module) || !method_exists($module, 'get_ecosystem_metadata')) {
            return [];
        }

        $metadata = $module->get_ecosystem_metadata();

        $relations = $metadata['ecosystem_supports_modules'] ?? [];
        if (empty($relations)) {
            $defaults = self::get_default_relations_map();
            return $defaults[self::normalize_module_id($parent_module_id)] ?? [];
        }

        return array_values(array_unique(array_filter(array_map([__CLASS__, 'normalize_module_id'], (array) $relations))));
    }

    /**
     * Obtener todos los módulos verticales
     *
     * @return array
     */
    public static function get_vertical_modules() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $active_modules = get_option('flavor_active_modules', []);
        $all_modules = self::get_modules_from_loader();
        $verticales = [];
        $default_relations = self::get_default_relations_map();

        foreach ($all_modules as $module_id => $module) {
            if (!in_array($module_id, $active_modules)) {
                continue;
            }

            if (!is_object($module)) {
                continue;
            }

            $metadata = method_exists($module, 'get_ecosystem_metadata')
                ? (array) $module->get_ecosystem_metadata()
                : [];
            $role = $metadata['module_role'] ?? 'vertical';

            $normalized_module_id = self::normalize_module_id($module_id);
            if ($role === 'vertical' || $role === 'base' || isset($default_relations[$normalized_module_id])) {
                $verticales[$module_id] = [
                    'name' => method_exists($module, 'get_name')
                        ? $module->get_name()
                        : ucwords(str_replace(['_', '-'], ' ', (string) $module_id)),
                    'icon' => method_exists($module, 'get_icon')
                        ? $module->get_icon()
                        : 'dashicons-admin-plugins',
                    'role' => $role,
                ];
            }
        }

        return $verticales;
    }

    /**
     * Obtener todos los módulos horizontales (transversales y service)
     *
     * @return array
     */
    public static function get_horizontal_modules() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $active_modules = get_option('flavor_active_modules', []);
        $all_modules = self::get_modules_from_loader();
        $horizontales = [];
        $default_relations = self::get_default_relations_map();
        $default_children = [];
        foreach ($default_relations as $children) {
            foreach ($children as $child_id) {
                $default_children[$child_id] = true;
            }
        }

        // Módulos conocidos como horizontales
        $known_horizontals = [
            'foros', 'chat_interno', 'chat_grupos', 'multimedia', 'recetas', 'biblioteca',
            'podcast', 'radio', 'red_social', 'participacion', 'transparencia',
            'presupuestos_participativos', 'espacios_comunes', 'reservas', 'talleres',
            'cursos', 'eventos', 'socios', 'reciclaje', 'marketplace', 'trabajo_digno',
            'banco_tiempo', 'economia_don', 'incidencias', 'proyectos'
        ];

        foreach ($all_modules as $module_id => $module) {
            if (!in_array($module_id, $active_modules)) {
                continue;
            }

            if (!is_object($module)) {
                continue;
            }

            $metadata = method_exists($module, 'get_ecosystem_metadata')
                ? (array) $module->get_ecosystem_metadata()
                : [];
            $role = $metadata['module_role'] ?? 'vertical';
            $normalized_module_id = self::normalize_module_id($module_id);

            if (
                in_array($role, ['transversal', 'service', 'ecosystem'], true) ||
                in_array($normalized_module_id, $known_horizontals, true) ||
                isset($default_children[$normalized_module_id])
            ) {
                $horizontales[$module_id] = [
                    'name' => method_exists($module, 'get_name')
                        ? $module->get_name()
                        : ucwords(str_replace(['_', '-'], ' ', (string) $module_id)),
                    'icon' => method_exists($module, 'get_icon')
                        ? $module->get_icon()
                        : 'dashicons-admin-plugins',
                    'role' => $role,
                ];
            }
        }

        return $horizontales;
    }

    /**
     * Obtener relaciones completas de un módulo vertical con metadata
     *
     * @param string $parent_module_id
     * @param string $context
     * @return array Array de objetos con información del módulo horizontal
     */
    public static function get_child_modules_with_metadata($parent_module_id, $context = 'global') {
        $child_ids = self::get_child_modules($parent_module_id, $context);

        if (empty($child_ids) || !class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $all_modules = self::get_modules_from_loader();
        $result = [];

        foreach ($child_ids as $child_id) {
            if (!isset($all_modules[$child_id])) {
                continue;
            }

            $module = $all_modules[$child_id];
            $result[] = [
                'id' => $child_id,
                'name' => method_exists($module, 'get_name')
                    ? $module->get_name()
                    : ucwords(str_replace(['_', '-'], ' ', (string) $child_id)),
                'slug' => method_exists($module, 'get_slug')
                    ? $module->get_slug()
                    : $child_id,
                'icon' => method_exists($module, 'get_icon')
                    ? $module->get_icon()
                    : 'dashicons-admin-plugins',
                'url' => Flavor_Platform_Helpers::get_action_url($child_id),
            ];
        }

        return $result;
    }

    /**
     * Guardar relaciones (wrapper para uso fuera del admin)
     *
     * @param string $parent_module_id
     * @param array $child_module_ids
     * @param string $context
     * @return bool
     */
    public static function save_relations($parent_module_id, $child_module_ids, $context = 'global') {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_module_relations';

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Limpiar relaciones existentes
        $wpdb->delete($table, [
            'parent_module_id' => $parent_module_id,
            'context' => $context,
        ]);

        // Insertar nuevas relaciones
        foreach ($child_module_ids as $priority => $child_id) {
            $wpdb->insert($table, [
                'parent_module_id' => $parent_module_id,
                'child_module_id' => $child_id,
                'context' => $context,
                'priority' => ($priority + 1) * 10,
                'enabled' => 1,
                'created_at' => current_time('mysql'),
            ]);
        }

        return true;
    }
}

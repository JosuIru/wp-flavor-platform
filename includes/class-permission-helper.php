<?php
/**
 * Helper para verificacion de permisos
 *
 * Proporciona metodos estaticos para verificar permisos de usuario
 * de forma sencilla en cualquier parte del codigo.
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Permission_Helper {

    /**
     * Cache interno de verificaciones
     *
     * @var array
     */
    private static $cache_verificaciones = [];

    /**
     * Verifica si un usuario tiene un permiso especifico
     *
     * @param string   $capability Nombre de la capability a verificar
     * @param int|null $user_id    ID del usuario (null para usuario actual)
     * @return bool
     */
    public static function can($capability, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return false;
        }

        // Verificar cache
        $cache_key = $user_id . '_' . $capability;
        if (isset(self::$cache_verificaciones[$cache_key])) {
            return self::$cache_verificaciones[$cache_key];
        }

        // Verificar con WordPress
        $usuario = get_userdata($user_id);
        if (!$usuario) {
            self::$cache_verificaciones[$cache_key] = false;
            return false;
        }

        $puede = $usuario->has_cap($capability);

        // Aplicar filtro para extensibilidad
        $puede = apply_filters('flavor_user_can', $puede, $capability, $user_id);

        // Guardar en cache
        self::$cache_verificaciones[$cache_key] = $puede;

        return $puede;
    }

    /**
     * Verifica si el usuario tiene TODOS los permisos especificados (AND)
     *
     * @param array    $capabilities Array de capabilities a verificar
     * @param int|null $user_id      ID del usuario (null para usuario actual)
     * @return bool
     */
    public static function can_all(array $capabilities, $user_id = null) {
        if (empty($capabilities)) {
            return true;
        }

        foreach ($capabilities as $capability) {
            if (!self::can($capability, $user_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si el usuario tiene AL MENOS UNO de los permisos especificados (OR)
     *
     * @param array    $capabilities Array de capabilities a verificar
     * @param int|null $user_id      ID del usuario (null para usuario actual)
     * @return bool
     */
    public static function can_any(array $capabilities, $user_id = null) {
        if (empty($capabilities)) {
            return false;
        }

        foreach ($capabilities as $capability) {
            if (self::can($capability, $user_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario NO tiene un permiso
     *
     * @param string   $capability
     * @param int|null $user_id
     * @return bool
     */
    public static function cannot($capability, $user_id = null) {
        return !self::can($capability, $user_id);
    }

    /**
     * Obtiene el rol mas alto del usuario en un modulo especifico
     *
     * @param string   $module_slug Slug del modulo (grupos_consumo, eventos, etc.)
     * @param int|null $user_id     ID del usuario
     * @return string|null Slug del rol o null si no tiene
     */
    public static function get_module_role($module_slug, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return null;
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        return $role_manager->obtener_rol_modulo_usuario($user_id, $module_slug);
    }

    /**
     * Obtiene todos los roles de modulo del usuario
     *
     * @param int|null $user_id
     * @return array ['modulo' => 'rol', ...]
     */
    public static function get_all_module_roles($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        return $role_manager->obtener_roles_modulo_usuario($user_id);
    }

    /**
     * Asigna un rol de modulo a un usuario
     *
     * @param int    $user_id     ID del usuario
     * @param string $module_slug Slug del modulo
     * @param string $role        Slug del rol dentro del modulo
     * @return bool
     */
    public static function assign_module_role($user_id, $module_slug, $role) {
        if (!$user_id || !$module_slug || !$role) {
            return false;
        }

        // Verificar que el usuario que asigna tiene permisos
        if (!current_user_can('flavor_manage_permissions') && !current_user_can('administrator')) {
            return false;
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $resultado = $role_manager->asignar_rol_modulo($user_id, $module_slug, $role);

        // Limpiar cache
        self::clear_cache($user_id);

        return $resultado;
    }

    /**
     * Revoca un rol de modulo de un usuario
     *
     * @param int    $user_id     ID del usuario
     * @param string $module_slug Slug del modulo
     * @return bool
     */
    public static function revoke_module_role($user_id, $module_slug) {
        if (!$user_id || !$module_slug) {
            return false;
        }

        // Verificar que el usuario que revoca tiene permisos
        if (!current_user_can('flavor_manage_permissions') && !current_user_can('administrator')) {
            return false;
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $resultado = $role_manager->revocar_rol_modulo($user_id, $module_slug);

        // Limpiar cache
        self::clear_cache($user_id);

        return $resultado;
    }

    /**
     * Asigna automaticamente el rol de administrador/gestor del modulo al usuario
     *
     * @param int    $user_id
     * @param string $module_slug
     * @return bool
     */
    public static function assign_module_admin_to_user($user_id, $module_slug) {
        if (!$user_id || !$module_slug) {
            return false;
        }

        $role_manager = Flavor_Role_Manager::get_instance();

        // Si ya tiene rol asignado, no hacer nada
        $current_role = $role_manager->obtener_rol_modulo_usuario($user_id, $module_slug);
        if ($current_role) {
            return true;
        }

        $roles = $role_manager->obtener_roles_modulo($module_slug);
        if (!empty($roles)) {
            $priority_suffixes = ['_admin', '_gestor', '_coordinador', '_manager'];
            $selected = null;

            foreach ($priority_suffixes as $suffix) {
                foreach ($roles as $slug => $config) {
                    if (substr($slug, -strlen($suffix)) === $suffix) {
                        $selected = $slug;
                        break 2;
                    }
                }
            }

            if (!$selected) {
                // Buscar rol con "admin" en el slug
                foreach ($roles as $slug => $config) {
                    if (strpos($slug, 'admin') !== false) {
                        $selected = $slug;
                        break;
                    }
                }
            }

            if (!$selected) {
                $keys = array_keys($roles);
                $selected = $keys[0] ?? null;
            }

            if ($selected) {
                $resultado = $role_manager->asignar_rol_modulo($user_id, $module_slug, $selected);
                self::clear_cache($user_id);
                return $resultado;
            }
        }

        // Fallback: asignar todas las capabilities del modulo si existen
        $caps = $role_manager->obtener_capabilities_modulo($module_slug);
        if (empty($caps)) {
            return false;
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            return false;
        }

        // Guardar rol virtual para reflejarse en el panel de permisos
        $roles_modulo = get_user_meta($user_id, '_flavor_module_roles', true);
        if (!is_array($roles_modulo)) {
            $roles_modulo = [];
        }
        $roles_modulo[$module_slug] = $module_slug . '_admin_auto';
        update_user_meta($user_id, '_flavor_module_roles', $roles_modulo);

        foreach ($caps as $cap) {
            $usuario->add_cap($cap);
        }

        self::clear_cache($user_id);
        return true;
    }

    /**
     * Verifica si el usuario es coordinador/gestor de un modulo
     *
     * @param string   $module_slug
     * @param int|null $user_id
     * @return bool
     */
    public static function is_module_manager($module_slug, $user_id = null) {
        $role = self::get_module_role($module_slug, $user_id);

        if (!$role) {
            return false;
        }

        // Los roles que terminan en _admin, _gestor, _coordinador son gestores
        $roles_gestion = ['_admin', '_gestor', '_coordinador'];

        foreach ($roles_gestion as $sufijo) {
            if (strpos($role, $sufijo) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene acceso a un modulo (cualquier rol)
     *
     * @param string   $module_slug
     * @param int|null $user_id
     * @return bool
     */
    public static function has_module_access($module_slug, $user_id = null) {
        $role = self::get_module_role($module_slug, $user_id);
        return $role !== null;
    }

    /**
     * Obtiene todas las capabilities del usuario en un modulo
     *
     * @param string   $module_slug
     * @param int|null $user_id
     * @return array
     */
    public static function get_module_capabilities($module_slug, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $all_caps = $role_manager->obtener_capabilities_modulo($module_slug);
        $user_caps = [];

        foreach (array_keys($all_caps) as $cap) {
            if (self::can($cap, $user_id)) {
                $user_caps[] = $cap;
            }
        }

        return $user_caps;
    }

    /**
     * Verifica multiples condiciones de permisos con logica compleja
     *
     * @param array    $conditions Array de condiciones
     * @param int|null $user_id
     * @return bool
     *
     * Ejemplo de uso:
     * Flavor_Permission_Helper::check_conditions([
     *     'all' => ['gc_ver_productos', 'gc_ver_ciclos'],
     *     'any' => ['gc_gestionar_ciclos', 'gc_gestionar_productos'],
     * ]);
     */
    public static function check_conditions(array $conditions, $user_id = null) {
        $resultado = true;

        // Verificar condiciones ALL
        if (isset($conditions['all']) && is_array($conditions['all'])) {
            $resultado = $resultado && self::can_all($conditions['all'], $user_id);
        }

        // Verificar condiciones ANY
        if (isset($conditions['any']) && is_array($conditions['any'])) {
            $resultado = $resultado && self::can_any($conditions['any'], $user_id);
        }

        // Verificar condiciones NONE (no debe tener ninguna)
        if (isset($conditions['none']) && is_array($conditions['none'])) {
            foreach ($conditions['none'] as $cap) {
                if (self::can($cap, $user_id)) {
                    $resultado = false;
                    break;
                }
            }
        }

        return $resultado;
    }

    /**
     * Verifica si el usuario es propietario de un recurso
     *
     * @param int      $owner_id   ID del propietario del recurso
     * @param int|null $user_id    ID del usuario a verificar
     * @return bool
     */
    public static function is_owner($owner_id, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        return (int) $owner_id === (int) $user_id;
    }

    /**
     * Verifica si el usuario puede actuar sobre un recurso (es propietario O tiene permisos)
     *
     * @param int      $owner_id           ID del propietario del recurso
     * @param string   $manage_capability  Capability para gestionar cualquier recurso
     * @param int|null $user_id
     * @return bool
     */
    public static function can_manage_resource($owner_id, $manage_capability, $user_id = null) {
        // Si es el propietario, puede
        if (self::is_owner($owner_id, $user_id)) {
            return true;
        }

        // Si tiene capability de gestion, puede
        return self::can($manage_capability, $user_id);
    }

    /**
     * Verifica permisos y opcionalmente muestra error o redirige
     *
     * @param string|array $capabilities Una capability o array de capabilities
     * @param string       $mode         'all' o 'any'
     * @param int|null     $user_id
     * @return bool
     */
    public static function require_permission($capabilities, $mode = 'all', $user_id = null) {
        $capabilities = (array) $capabilities;

        $tiene_permiso = ($mode === 'any')
            ? self::can_any($capabilities, $user_id)
            : self::can_all($capabilities, $user_id);

        if (!$tiene_permiso) {
            if (wp_doing_ajax()) {
                wp_send_json_error([
                    'message' => __('No tienes permisos para realizar esta accion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'code' => 'permission_denied',
                ], 403);
            }

            return false;
        }

        return true;
    }

    /**
     * Wrapper para uso en templates - retorna HTML condicionalmente
     *
     * @param string   $capability
     * @param string   $html_permitido
     * @param string   $html_denegado
     * @param int|null $user_id
     * @return string
     */
    public static function if_can($capability, $html_permitido, $html_denegado = '', $user_id = null) {
        if (self::can($capability, $user_id)) {
            return $html_permitido;
        }
        return $html_denegado;
    }

    /**
     * Obtiene usuarios con una capability especifica
     *
     * @param string $capability
     * @return array Array de user IDs
     */
    public static function get_users_with_capability($capability) {
        $usuarios_con_cap = [];

        // Obtener todos los usuarios
        $usuarios = get_users(['fields' => 'ID']);

        foreach ($usuarios as $user_id) {
            if (self::can($capability, $user_id)) {
                $usuarios_con_cap[] = $user_id;
            }
        }

        return $usuarios_con_cap;
    }

    /**
     * Obtiene usuarios con un rol de modulo especifico
     *
     * @param string $module_slug
     * @param string $role
     * @return array
     */
    public static function get_users_with_module_role($module_slug, $role) {
        $role_manager = Flavor_Role_Manager::get_instance();
        return $role_manager->obtener_usuarios_por_rol_modulo($module_slug, $role);
    }

    /**
     * Genera un resumen de permisos del usuario
     *
     * @param int|null $user_id
     * @return array
     */
    public static function get_permissions_summary($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $todas_caps = $role_manager->obtener_capabilities_agrupadas();
        $resumen = [];

        foreach ($todas_caps as $grupo => $caps) {
            $resumen[$grupo] = [
                'total' => count($caps),
                'granted' => 0,
                'capabilities' => [],
            ];

            foreach ($caps as $cap => $descripcion) {
                $tiene = self::can($cap, $user_id);
                $resumen[$grupo]['capabilities'][$cap] = [
                    'granted' => $tiene,
                    'description' => $descripcion,
                ];

                if ($tiene) {
                    $resumen[$grupo]['granted']++;
                }
            }
        }

        return $resumen;
    }

    /**
     * Limpia el cache de verificaciones
     *
     * @param int|null $user_id ID especifico o null para limpiar todo
     */
    public static function clear_cache($user_id = null) {
        if ($user_id === null) {
            self::$cache_verificaciones = [];
        } else {
            // Limpiar solo las entradas de este usuario
            foreach (array_keys(self::$cache_verificaciones) as $key) {
                if (strpos($key, $user_id . '_') === 0) {
                    unset(self::$cache_verificaciones[$key]);
                }
            }
        }
    }

    /**
     * Debug: Obtiene el estado del cache
     *
     * @return array
     */
    public static function get_cache_stats() {
        return [
            'entries' => count(self::$cache_verificaciones),
            'keys' => array_keys(self::$cache_verificaciones),
        ];
    }

    /**
     * Verifica si el usuario actual es administrador de Flavor
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_flavor_admin($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return false;
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            return false;
        }

        // Verificar si tiene rol de admin de WordPress o Flavor
        return in_array('administrator', $usuario->roles, true)
            || in_array('flavor_admin', $usuario->roles, true)
            || self::can('flavor_manage_settings', $user_id);
    }

    /**
     * Verifica si el usuario puede acceder al panel de administracion de Flavor
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function can_access_admin($user_id = null) {
        return self::can_any([
            'flavor_manage_settings',
            'flavor_manage_modules',
            'flavor_manage_permissions',
            'flavor_view_analytics',
        ], $user_id) || self::is_flavor_admin($user_id);
    }

    /**
     * Registra una capability temporal para la sesion actual
     *
     * @param int    $user_id
     * @param string $capability
     * @param bool   $grant
     */
    public static function grant_temporary($user_id, $capability, $grant = true) {
        $cache_key = $user_id . '_' . $capability;
        self::$cache_verificaciones[$cache_key] = $grant;
    }

    /**
     * Copia permisos de un usuario a otro
     *
     * @param int $source_user_id
     * @param int $target_user_id
     * @param bool $include_module_roles
     * @return bool
     */
    public static function copy_permissions($source_user_id, $target_user_id, $include_module_roles = true) {
        $source_user = get_userdata($source_user_id);
        $target_user = get_userdata($target_user_id);

        if (!$source_user || !$target_user) {
            return false;
        }

        // Copiar roles de WordPress
        foreach ($source_user->roles as $role) {
            $target_user->add_role($role);
        }

        // Copiar roles de modulo si se indica
        if ($include_module_roles) {
            $role_manager = Flavor_Role_Manager::get_instance();
            $module_roles = $role_manager->obtener_roles_modulo_usuario($source_user_id);

            foreach ($module_roles as $module => $role) {
                $role_manager->asignar_rol_modulo($target_user_id, $module, $role);
            }
        }

        self::clear_cache($target_user_id);

        return true;
    }

    /**
     * Exporta la configuracion de permisos de un usuario
     *
     * @param int $user_id
     * @return array
     */
    public static function export_user_permissions($user_id) {
        $usuario = get_userdata($user_id);

        if (!$usuario) {
            return [];
        }

        $role_manager = Flavor_Role_Manager::get_instance();

        return [
            'user_id' => $user_id,
            'wp_roles' => $usuario->roles,
            'capabilities' => array_keys(array_filter($usuario->allcaps)),
            'module_roles' => $role_manager->obtener_roles_modulo_usuario($user_id),
            'exported_at' => current_time('mysql'),
        ];
    }

    /**
     * Importa la configuracion de permisos a un usuario
     *
     * @param int   $user_id
     * @param array $config
     * @return bool
     */
    public static function import_user_permissions($user_id, array $config) {
        $usuario = get_userdata($user_id);

        if (!$usuario) {
            return false;
        }

        // Limpiar roles actuales
        foreach ($usuario->roles as $role) {
            $usuario->remove_role($role);
        }

        // Asignar roles de WordPress
        if (!empty($config['wp_roles'])) {
            foreach ($config['wp_roles'] as $role) {
                $usuario->add_role($role);
            }
        }

        // Asignar roles de modulo
        if (!empty($config['module_roles'])) {
            $role_manager = Flavor_Role_Manager::get_instance();
            foreach ($config['module_roles'] as $module => $role) {
                $role_manager->asignar_rol_modulo($user_id, $module, $role);
            }
        }

        self::clear_cache($user_id);

        return true;
    }
}

/**
 * Funciones helper globales para uso rapido
 */

if (!function_exists('flavor_can')) {
    /**
     * Verifica si el usuario actual tiene una capability
     *
     * @param string $capability
     * @return bool
     */
    function flavor_can($capability) {
        return Flavor_Permission_Helper::can($capability);
    }
}

if (!function_exists('flavor_can_any')) {
    /**
     * Verifica si el usuario actual tiene alguna de las capabilities
     *
     * @param array $capabilities
     * @return bool
     */
    function flavor_can_any(array $capabilities) {
        return Flavor_Permission_Helper::can_any($capabilities);
    }
}

if (!function_exists('flavor_can_all')) {
    /**
     * Verifica si el usuario actual tiene todas las capabilities
     *
     * @param array $capabilities
     * @return bool
     */
    function flavor_can_all(array $capabilities) {
        return Flavor_Permission_Helper::can_all($capabilities);
    }
}

if (!function_exists('flavor_is_admin')) {
    /**
     * Verifica si el usuario actual es admin de Flavor
     *
     * @return bool
     */
    function flavor_is_admin() {
        return Flavor_Permission_Helper::is_flavor_admin();
    }
}

if (!function_exists('flavor_require_permission')) {
    /**
     * Requiere un permiso, envia error si no lo tiene
     *
     * @param string|array $capabilities
     * @param string       $mode 'all' o 'any'
     * @return bool
     */
    function flavor_require_permission($capabilities, $mode = 'all') {
        return Flavor_Permission_Helper::require_permission($capabilities, $mode);
    }
}

<?php
/**
 * Comandos WP-CLI para gestion de permisos
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Gestiona permisos y roles de Flavor Platform.
 *
 * ## EXAMPLES
 *
 *     # Asignar rol de modulo a usuario
 *     wp flavor permission grant 5 gc_coordinador
 *
 *     # Revocar rol de modulo
 *     wp flavor permission revoke 5 grupos_consumo
 *
 *     # Listar permisos de un usuario
 *     wp flavor permission list --user=5
 *
 *     # Listar capabilities de un modulo
 *     wp flavor permission list --module=grupos_consumo
 *
 *     # Crear rol personalizado
 *     wp flavor permission create-role mi_rol "Mi Rol" --capabilities=gc_ver_productos,gc_crear_pedido
 *
 * @package FlavorPlatform
 */
class Flavor_Permission_Command {

    /**
     * Asigna un rol de modulo a un usuario.
     *
     * ## OPTIONS
     *
     * <user_id>
     * : ID del usuario.
     *
     * <role>
     * : Slug del rol de modulo (ej: gc_coordinador, eventos_organizador).
     *
     * ## EXAMPLES
     *
     *     wp flavor permission grant 5 gc_coordinador
     *     wp flavor permission grant 12 eventos_gestor
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function grant($args, $assoc_args) {
        list($user_id, $role) = $args;
        $user_id = intval($user_id);

        if (!$user_id) {
            WP_CLI::error(__('ID de usuario invalido.', 'flavor-platform'));
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            WP_CLI::error(__('Usuario no encontrado.', 'flavor-platform'));
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $roles_por_modulo = $role_manager->obtener_roles_modulo();

        // Buscar a que modulo pertenece el rol
        $modulo_encontrado = null;
        foreach ($roles_por_modulo as $modulo_slug => $roles_modulo) {
            if (isset($roles_modulo[$role])) {
                $modulo_encontrado = $modulo_slug;
                break;
            }
        }

        if (!$modulo_encontrado) {
            WP_CLI::error(sprintf(
                __('Rol "%s" no encontrado. Usa "wp flavor permission roles" para ver roles disponibles.', 'flavor-platform'),
                $role
            ));
        }

        $resultado = $role_manager->asignar_rol_modulo($user_id, $modulo_encontrado, $role);

        if ($resultado) {
            WP_CLI::success(sprintf(
                __('Rol "%s" asignado a usuario %d (%s) en modulo "%s".', 'flavor-platform'),
                $role,
                $user_id,
                $usuario->display_name,
                $modulo_encontrado
            ));
        } else {
            WP_CLI::error(__('No se pudo asignar el rol.', 'flavor-platform'));
        }
    }

    /**
     * Revoca un rol de modulo de un usuario.
     *
     * ## OPTIONS
     *
     * <user_id>
     * : ID del usuario.
     *
     * <module>
     * : Slug del modulo (ej: grupos_consumo, eventos).
     *
     * ## EXAMPLES
     *
     *     wp flavor permission revoke 5 grupos_consumo
     *     wp flavor permission revoke 12 eventos
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function revoke($args, $assoc_args) {
        list($user_id, $module) = $args;
        $user_id = intval($user_id);

        if (!$user_id) {
            WP_CLI::error(__('ID de usuario invalido.', 'flavor-platform'));
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            WP_CLI::error(__('Usuario no encontrado.', 'flavor-platform'));
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $rol_actual = $role_manager->obtener_rol_modulo_usuario($user_id, $module);

        if (!$rol_actual) {
            WP_CLI::warning(sprintf(
                __('El usuario %d no tiene rol asignado en modulo "%s".', 'flavor-platform'),
                $user_id,
                $module
            ));
            return;
        }

        $resultado = $role_manager->revocar_rol_modulo($user_id, $module);

        if ($resultado) {
            WP_CLI::success(sprintf(
                __('Rol "%s" revocado de usuario %d (%s) en modulo "%s".', 'flavor-platform'),
                $rol_actual,
                $user_id,
                $usuario->display_name,
                $module
            ));
        } else {
            WP_CLI::error(__('No se pudo revocar el rol.', 'flavor-platform'));
        }
    }

    /**
     * Lista permisos, roles o capabilities.
     *
     * ## OPTIONS
     *
     * [--user=<user_id>]
     * : Mostrar permisos de un usuario especifico.
     *
     * [--module=<module>]
     * : Mostrar capabilities de un modulo especifico.
     *
     * [--format=<format>]
     * : Formato de salida (table, json, csv, yaml). Default: table.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission list --user=5
     *     wp flavor permission list --module=grupos_consumo
     *     wp flavor permission list --module=grupos_consumo --format=json
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function list($args, $assoc_args) {
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        $role_manager = Flavor_Role_Manager::get_instance();

        // Listar permisos de un usuario
        if (isset($assoc_args['user'])) {
            $user_id = intval($assoc_args['user']);
            $usuario = get_userdata($user_id);

            if (!$usuario) {
                WP_CLI::error(__('Usuario no encontrado.', 'flavor-platform'));
            }

            WP_CLI::line(sprintf(
                __('Permisos de: %s (%s)', 'flavor-platform'),
                $usuario->display_name,
                $usuario->user_email
            ));
            WP_CLI::line('');

            // Roles de WordPress
            WP_CLI::line(WP_CLI::colorize('%GRoles WordPress:%n ' . implode(', ', $usuario->roles)));

            // Roles de modulo
            $roles_modulo = $role_manager->obtener_roles_modulo_usuario($user_id);
            WP_CLI::line('');
            WP_CLI::line(WP_CLI::colorize('%GRoles por Modulo:%n'));

            if (empty($roles_modulo)) {
                WP_CLI::line('  (ninguno)');
            } else {
                $data = [];
                foreach ($roles_modulo as $modulo => $rol) {
                    $data[] = [
                        'modulo' => $modulo,
                        'rol' => $rol,
                    ];
                }
                WP_CLI\Utils\format_items($format, $data, ['modulo', 'rol']);
            }

            // Resumen de capabilities
            WP_CLI::line('');
            WP_CLI::line(WP_CLI::colorize('%GResumen de Capabilities:%n'));

            $resumen = Flavor_Permission_Helper::get_permissions_summary($user_id);
            $data_resumen = [];
            foreach ($resumen as $grupo => $info) {
                $data_resumen[] = [
                    'grupo' => $grupo,
                    'concedidas' => $info['granted'],
                    'total' => $info['total'],
                    'porcentaje' => round(($info['granted'] / $info['total']) * 100) . '%',
                ];
            }
            WP_CLI\Utils\format_items($format, $data_resumen, ['grupo', 'concedidas', 'total', 'porcentaje']);

            return;
        }

        // Listar capabilities de un modulo
        if (isset($assoc_args['module'])) {
            $module = sanitize_key($assoc_args['module']);
            $caps = $role_manager->obtener_capabilities_modulo($module);

            if (empty($caps)) {
                WP_CLI::error(sprintf(__('Modulo "%s" no encontrado.', 'flavor-platform'), $module));
            }

            WP_CLI::line(sprintf(__('Capabilities del modulo: %s', 'flavor-platform'), $module));
            WP_CLI::line('');

            $data = [];
            foreach ($caps as $cap => $descripcion) {
                $data[] = [
                    'capability' => $cap,
                    'descripcion' => $descripcion,
                ];
            }

            WP_CLI\Utils\format_items($format, $data, ['capability', 'descripcion']);

            // Mostrar roles del modulo
            WP_CLI::line('');
            WP_CLI::line(WP_CLI::colorize('%GRoles del modulo:%n'));

            $roles_modulo = $role_manager->obtener_roles_modulo($module);
            if (empty($roles_modulo)) {
                WP_CLI::line('  (ninguno)');
            } else {
                $data_roles = [];
                foreach ($roles_modulo as $slug => $info) {
                    $data_roles[] = [
                        'slug' => $slug,
                        'label' => $info['label'],
                        'descripcion' => $info['description'] ?? '',
                    ];
                }
                WP_CLI\Utils\format_items($format, $data_roles, ['slug', 'label', 'descripcion']);
            }

            return;
        }

        // Listar todos los modulos
        WP_CLI::line(__('Modulos disponibles:', 'flavor-platform'));
        WP_CLI::line('');

        $modulos = $role_manager->obtener_modulos_con_capabilities();
        $data = [];
        foreach ($modulos as $slug => $info) {
            $data[] = [
                'slug' => $slug,
                'label' => $info['label'],
                'capabilities' => count($info['capabilities']),
                'roles' => count($info['roles']),
            ];
        }

        WP_CLI\Utils\format_items($format, $data, ['slug', 'label', 'capabilities', 'roles']);
    }

    /**
     * Muestra todos los roles disponibles por modulo.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Formato de salida (table, json, csv, yaml). Default: table.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission roles
     *     wp flavor permission roles --format=json
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function roles($args, $assoc_args) {
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        $role_manager = Flavor_Role_Manager::get_instance();

        WP_CLI::line(__('Roles disponibles por modulo:', 'flavor-platform'));
        WP_CLI::line('');

        $roles_por_modulo = $role_manager->obtener_roles_modulo();
        $data = [];

        foreach ($roles_por_modulo as $modulo => $roles) {
            foreach ($roles as $slug => $info) {
                $data[] = [
                    'modulo' => $modulo,
                    'rol' => $slug,
                    'label' => $info['label'],
                    'descripcion' => $info['description'] ?? '',
                ];
            }
        }

        WP_CLI\Utils\format_items($format, $data, ['modulo', 'rol', 'label', 'descripcion']);
    }

    /**
     * Crea un nuevo rol personalizado.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Identificador unico del rol.
     *
     * <label>
     * : Nombre visible del rol.
     *
     * [--description=<description>]
     * : Descripcion del rol.
     *
     * [--capabilities=<capabilities>]
     * : Lista de capabilities separadas por coma.
     *
     * [--module=<module>]
     * : Modulo al que pertenece (dejar vacio para rol global).
     *
     * ## EXAMPLES
     *
     *     wp flavor permission create-role gestor_local "Gestor Local" --capabilities=gc_ver_productos,gc_crear_pedido
     *     wp flavor permission create-role super_events "Super Eventos" --module=eventos --capabilities=eventos_*
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function create_role($args, $assoc_args) {
        list($slug, $label) = $args;

        $capabilities = [];
        if (isset($assoc_args['capabilities'])) {
            $capabilities = array_map('trim', explode(',', $assoc_args['capabilities']));
        }

        $datos_rol = [
            'slug' => $slug,
            'label' => $label,
            'description' => isset($assoc_args['description']) ? $assoc_args['description'] : '',
            'capabilities' => $capabilities,
            'modulo' => isset($assoc_args['module']) ? $assoc_args['module'] : null,
        ];

        $role_manager = Flavor_Role_Manager::get_instance();
        $resultado = $role_manager->crear_rol_personalizado($datos_rol);

        if (is_wp_error($resultado)) {
            WP_CLI::error($resultado->get_error_message());
        }

        WP_CLI::success(sprintf(
            __('Rol "%s" creado correctamente.', 'flavor-platform'),
            $label
        ));
    }

    /**
     * Elimina un rol personalizado.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Identificador del rol a eliminar.
     *
     * [--yes]
     * : Confirmar eliminacion sin preguntar.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission delete-role gestor_local
     *     wp flavor permission delete-role gestor_local --yes
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function delete_role($args, $assoc_args) {
        list($slug) = $args;

        $role_manager = Flavor_Role_Manager::get_instance();
        $roles_personalizados = $role_manager->obtener_roles_personalizados();

        if (!isset($roles_personalizados[$slug])) {
            WP_CLI::error(sprintf(
                __('Rol personalizado "%s" no encontrado.', 'flavor-platform'),
                $slug
            ));
        }

        WP_CLI::confirm(
            sprintf(__('¿Seguro que quieres eliminar el rol "%s"?', 'flavor-platform'), $slug),
            $assoc_args
        );

        $resultado = $role_manager->eliminar_rol_personalizado($slug);

        if ($resultado) {
            WP_CLI::success(sprintf(
                __('Rol "%s" eliminado correctamente.', 'flavor-platform'),
                $slug
            ));
        } else {
            WP_CLI::error(__('No se pudo eliminar el rol.', 'flavor-platform'));
        }
    }

    /**
     * Verifica si un usuario tiene una capability.
     *
     * ## OPTIONS
     *
     * <user_id>
     * : ID del usuario.
     *
     * <capability>
     * : Capability a verificar.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission check 5 gc_gestionar_ciclos
     *     wp flavor permission check 12 eventos_crear
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function check($args, $assoc_args) {
        list($user_id, $capability) = $args;
        $user_id = intval($user_id);

        if (!$user_id) {
            WP_CLI::error(__('ID de usuario invalido.', 'flavor-platform'));
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            WP_CLI::error(__('Usuario no encontrado.', 'flavor-platform'));
        }

        $puede = Flavor_Permission_Helper::can($capability, $user_id);

        if ($puede) {
            WP_CLI::success(sprintf(
                __('Usuario %d (%s) TIENE la capability "%s".', 'flavor-platform'),
                $user_id,
                $usuario->display_name,
                $capability
            ));
        } else {
            WP_CLI::warning(sprintf(
                __('Usuario %d (%s) NO tiene la capability "%s".', 'flavor-platform'),
                $user_id,
                $usuario->display_name,
                $capability
            ));
        }
    }

    /**
     * Sincroniza/recrea los roles del sistema.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Forzar recreacion incluso si ya existen.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission sync
     *     wp flavor permission sync --force
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function sync($args, $assoc_args) {
        WP_CLI::line(__('Sincronizando roles de Flavor Platform...', 'flavor-platform'));

        Flavor_Role_Manager::create_roles();

        WP_CLI::success(__('Roles sincronizados correctamente.', 'flavor-platform'));

        // Mostrar estadisticas
        $role_manager = Flavor_Role_Manager::get_instance();
        $caps = $role_manager->obtener_todas_las_capabilities();
        $roles = $role_manager->obtener_roles();

        WP_CLI::line('');
        WP_CLI::line(sprintf(__('Total capabilities: %d', 'flavor-platform'), count($caps)));
        WP_CLI::line(sprintf(__('Total roles: %d', 'flavor-platform'), count($roles)));
    }

    /**
     * Exporta la configuracion de permisos de un usuario.
     *
     * ## OPTIONS
     *
     * <user_id>
     * : ID del usuario.
     *
     * [--format=<format>]
     * : Formato de salida (json, yaml). Default: json.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission export 5
     *     wp flavor permission export 5 --format=yaml
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function export($args, $assoc_args) {
        list($user_id) = $args;
        $user_id = intval($user_id);
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'json';

        $config = Flavor_Permission_Helper::export_user_permissions($user_id);

        if (empty($config)) {
            WP_CLI::error(__('Usuario no encontrado.', 'flavor-platform'));
        }

        if ($format === 'yaml') {
            WP_CLI::line(Spyc::YAMLDump($config));
        } else {
            WP_CLI::line(json_encode($config, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Importa configuracion de permisos a un usuario.
     *
     * ## OPTIONS
     *
     * <user_id>
     * : ID del usuario.
     *
     * <file>
     * : Archivo JSON con la configuracion.
     *
     * ## EXAMPLES
     *
     *     wp flavor permission import 5 permissions.json
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function import($args, $assoc_args) {
        list($user_id, $file) = $args;
        $user_id = intval($user_id);

        if (!file_exists($file)) {
            WP_CLI::error(__('Archivo no encontrado.', 'flavor-platform'));
        }

        $contenido = file_get_contents($file);
        $config = json_decode($contenido, true);

        if (!$config) {
            WP_CLI::error(__('Error al parsear el archivo JSON.', 'flavor-platform'));
        }

        $resultado = Flavor_Permission_Helper::import_user_permissions($user_id, $config);

        if ($resultado) {
            WP_CLI::success(__('Permisos importados correctamente.', 'flavor-platform'));
        } else {
            WP_CLI::error(__('Error al importar permisos.', 'flavor-platform'));
        }
    }
}

// Registrar comandos
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('flavor permission', 'Flavor_Permission_Command');
}

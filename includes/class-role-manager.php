<?php
/**
 * Gestor de Roles y Capabilities personalizados
 *
 * Define roles específicos del plugin y capabilities granulares por módulo.
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Role_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Definición de roles del plugin
     *
     * @var array
     */
    private $roles_definidos = [];

    /**
     * Capabilities del plugin agrupadas por contexto
     *
     * @var array
     */
    private $capabilities_definidas = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Role_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->definir_capabilities();
        $this->definir_roles();
    }

    /**
     * Define las capabilities del plugin agrupadas por contexto
     */
    private function definir_capabilities() {
        $this->capabilities_definidas = [
            // Dashboard usuario frontend
            'dashboard' => [
                'flavor_view_dashboard'     => __('Ver dashboard de usuario', 'flavor-chat-ia'),
                'flavor_edit_profile'       => __('Editar perfil propio', 'flavor-chat-ia'),
            ],

            // Módulo Socios
            'socios' => [
                'flavor_manage_socios'      => __('Gestionar socios', 'flavor-chat-ia'),
                'flavor_view_socios'        => __('Ver listado de socios', 'flavor-chat-ia'),
                'flavor_edit_cuotas'        => __('Gestionar cuotas', 'flavor-chat-ia'),
            ],

            // Módulo Grupos de Consumo
            'grupos_consumo' => [
                'flavor_manage_grupos'      => __('Gestionar grupos de consumo', 'flavor-chat-ia'),
                'flavor_view_grupos'        => __('Ver grupos de consumo', 'flavor-chat-ia'),
                'flavor_create_pedidos'     => __('Crear pedidos', 'flavor-chat-ia'),
            ],

            // Módulo Eventos
            'eventos' => [
                'flavor_manage_eventos'     => __('Gestionar eventos', 'flavor-chat-ia'),
                'flavor_create_eventos'     => __('Crear eventos', 'flavor-chat-ia'),
                'flavor_view_eventos'       => __('Ver eventos', 'flavor-chat-ia'),
            ],

            // Módulo Reservas
            'reservas' => [
                'flavor_manage_reservas'    => __('Gestionar todas las reservas', 'flavor-chat-ia'),
                'flavor_create_reservas'    => __('Crear reservas', 'flavor-chat-ia'),
                'flavor_view_own_reservas'  => __('Ver reservas propias', 'flavor-chat-ia'),
            ],

            // Módulo Banco de Tiempo
            'banco_tiempo' => [
                'flavor_manage_banco_tiempo' => __('Gestionar banco de tiempo', 'flavor-chat-ia'),
                'flavor_use_banco_tiempo'    => __('Participar en banco de tiempo', 'flavor-chat-ia'),
            ],

            // Módulo Incidencias
            'incidencias' => [
                'flavor_manage_incidencias' => __('Gestionar incidencias', 'flavor-chat-ia'),
                'flavor_create_incidencias' => __('Reportar incidencias', 'flavor-chat-ia'),
            ],

            // Newsletter
            'newsletter' => [
                'flavor_manage_newsletter'  => __('Gestionar campañas de newsletter', 'flavor-chat-ia'),
                'flavor_send_newsletter'    => __('Enviar newsletters', 'flavor-chat-ia'),
            ],

            // Administración general del plugin
            'admin' => [
                'flavor_manage_settings'    => __('Gestionar configuración del plugin', 'flavor-chat-ia'),
                'flavor_view_analytics'     => __('Ver analíticas', 'flavor-chat-ia'),
                'flavor_manage_modules'     => __('Activar/desactivar módulos', 'flavor-chat-ia'),
                'flavor_export_data'        => __('Exportar datos', 'flavor-chat-ia'),
                'flavor_import_data'        => __('Importar datos', 'flavor-chat-ia'),
            ],

            // Moderación
            'moderacion' => [
                'flavor_moderate_content'   => __('Moderar contenido', 'flavor-chat-ia'),
                'flavor_manage_users'       => __('Gestionar usuarios del plugin', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Define los roles del plugin con sus capabilities asignadas
     */
    private function definir_roles() {
        $this->roles_definidos = [
            'flavor_socio' => [
                'label' => __('Socio', 'flavor-chat-ia'),
                'capabilities' => [
                    // WordPress base
                    'read' => true,
                    // Flavor
                    'flavor_view_dashboard'     => true,
                    'flavor_edit_profile'       => true,
                    'flavor_view_socios'        => true,
                    'flavor_view_grupos'        => true,
                    'flavor_create_pedidos'     => true,
                    'flavor_view_eventos'       => true,
                    'flavor_create_reservas'    => true,
                    'flavor_view_own_reservas'  => true,
                    'flavor_use_banco_tiempo'   => true,
                    'flavor_create_incidencias' => true,
                ],
            ],

            'flavor_gestor' => [
                'label' => __('Gestor', 'flavor-chat-ia'),
                'capabilities' => [
                    // WordPress base
                    'read'          => true,
                    'edit_posts'    => true,
                    // Flavor - Todo lo de socio +
                    'flavor_view_dashboard'      => true,
                    'flavor_edit_profile'        => true,
                    'flavor_view_socios'         => true,
                    'flavor_manage_socios'       => true,
                    'flavor_edit_cuotas'         => true,
                    'flavor_view_grupos'         => true,
                    'flavor_manage_grupos'       => true,
                    'flavor_create_pedidos'      => true,
                    'flavor_view_eventos'        => true,
                    'flavor_manage_eventos'      => true,
                    'flavor_create_eventos'      => true,
                    'flavor_manage_reservas'     => true,
                    'flavor_create_reservas'     => true,
                    'flavor_view_own_reservas'   => true,
                    'flavor_manage_banco_tiempo' => true,
                    'flavor_use_banco_tiempo'    => true,
                    'flavor_manage_incidencias'  => true,
                    'flavor_create_incidencias'  => true,
                    'flavor_view_analytics'      => true,
                ],
            ],

            'flavor_moderador' => [
                'label' => __('Moderador', 'flavor-chat-ia'),
                'capabilities' => [
                    // WordPress base
                    'read'              => true,
                    'edit_posts'        => true,
                    'moderate_comments' => true,
                    // Flavor
                    'flavor_view_dashboard'      => true,
                    'flavor_edit_profile'        => true,
                    'flavor_moderate_content'    => true,
                    'flavor_manage_users'        => true,
                    'flavor_manage_eventos'      => true,
                    'flavor_manage_incidencias'  => true,
                    'flavor_view_analytics'      => true,
                    'flavor_view_socios'         => true,
                    'flavor_view_grupos'         => true,
                    'flavor_view_eventos'        => true,
                    'flavor_create_reservas'     => true,
                    'flavor_view_own_reservas'   => true,
                ],
            ],
        ];
    }

    /**
     * Crea los roles personalizados en WordPress.
     * Se ejecuta en la activación del plugin.
     */
    public static function create_roles() {
        $instancia = self::get_instance();

        foreach ($instancia->roles_definidos as $identificador_rol => $configuracion_rol) {
            // Eliminar si existe para actualizar capabilities
            remove_role($identificador_rol);
            add_role(
                $identificador_rol,
                $configuracion_rol['label'],
                $configuracion_rol['capabilities']
            );
        }

        // Añadir todas las capabilities de Flavor al rol administrator
        $rol_admin = get_role('administrator');
        if ($rol_admin) {
            $todas_las_capabilities = $instancia->obtener_todas_las_capabilities();
            foreach ($todas_las_capabilities as $nombre_capability => $descripcion_capability) {
                $rol_admin->add_cap($nombre_capability);
            }
        }
    }

    /**
     * Elimina los roles personalizados.
     * Se ejecuta opcionalmente en la desinstalación del plugin.
     */
    public static function remove_roles() {
        $instancia = self::get_instance();

        foreach (array_keys($instancia->roles_definidos) as $identificador_rol) {
            remove_role($identificador_rol);
        }

        // Limpiar capabilities del admin
        $rol_admin = get_role('administrator');
        if ($rol_admin) {
            $todas_las_capabilities = $instancia->obtener_todas_las_capabilities();
            foreach (array_keys($todas_las_capabilities) as $nombre_capability) {
                $rol_admin->remove_cap($nombre_capability);
            }
        }
    }

    /**
     * Obtiene todas las capabilities definidas como array plano
     *
     * @return array ['nombre_cap' => 'descripción', ...]
     */
    public function obtener_todas_las_capabilities() {
        $capabilities_planas = [];
        foreach ($this->capabilities_definidas as $capabilities_grupo) {
            $capabilities_planas = array_merge($capabilities_planas, $capabilities_grupo);
        }
        return $capabilities_planas;
    }

    /**
     * Obtiene las capabilities agrupadas por contexto
     *
     * @return array
     */
    public function obtener_capabilities_agrupadas() {
        return $this->capabilities_definidas;
    }

    /**
     * Obtiene la definición de roles
     *
     * @return array
     */
    public function obtener_roles() {
        return $this->roles_definidos;
    }

    /**
     * Verifica si un usuario tiene una capability específica de Flavor
     *
     * @param int    $usuario_id     ID del usuario
     * @param string $nombre_capability Nombre de la capability
     * @return bool
     */
    public static function usuario_puede($usuario_id, $nombre_capability) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return false;
        }
        return $usuario->has_cap($nombre_capability);
    }

    /**
     * Verifica si el usuario actual tiene una capability
     *
     * @param string $nombre_capability
     * @return bool
     */
    public static function puede($nombre_capability) {
        return current_user_can($nombre_capability);
    }

    /**
     * Obtiene los capabilities de un módulo específico
     *
     * @param string $nombre_modulo Nombre del módulo (socios, eventos, etc.)
     * @return array
     */
    public function obtener_capabilities_modulo($nombre_modulo) {
        return $this->capabilities_definidas[$nombre_modulo] ?? [];
    }

    /**
     * Añade una capability personalizada a un rol existente
     *
     * @param string $identificador_rol      Identificador del rol
     * @param string $nombre_capability  Nombre de la capability
     * @param bool   $conceder          Si conceder o denegar (default: true)
     */
    public static function asignar_capability($identificador_rol, $nombre_capability, $conceder = true) {
        $rol = get_role($identificador_rol);
        if ($rol) {
            $rol->add_cap($nombre_capability, $conceder);
        }
    }
}

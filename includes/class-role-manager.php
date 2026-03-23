<?php
/**
 * Gestor de Roles y Capabilities personalizados
 *
 * Define roles especificos del plugin y capabilities granulares por modulo.
 * Sistema de permisos granulares para Flavor Platform.
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
     * Definicion de roles del plugin
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
     * Roles especificos por modulo
     *
     * @var array
     */
    private $roles_por_modulo = [];

    /**
     * Cache de capabilities de usuario
     *
     * @var array
     */
    private $cache_permisos_usuario = [];

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
        $this->definir_roles_por_modulo();
        $this->registrar_hooks();
    }

    /**
     * Registra los hooks necesarios
     */
    private function registrar_hooks() {
        // Filtro para modificar permisos dinamicamente
        add_filter('user_has_cap', [$this, 'filtrar_capabilities_usuario'], 10, 4);

        // Hook para limpiar cache cuando cambian roles
        add_action('set_user_role', [$this, 'limpiar_cache_usuario'], 10, 1);
        add_action('add_user_role', [$this, 'limpiar_cache_usuario'], 10, 1);
        add_action('remove_user_role', [$this, 'limpiar_cache_usuario'], 10, 1);
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

            // Modulo Grupos de Consumo - Capabilities granulares
            'grupos_consumo' => [
                // Productos
                'gc_ver_productos'          => __('Ver productos del grupo', 'flavor-chat-ia'),
                'gc_gestionar_mis_productos' => __('Gestionar mis productos (productor)', 'flavor-chat-ia'),
                'gc_gestionar_productos'    => __('Gestionar todos los productos', 'flavor-chat-ia'),

                // Pedidos
                'gc_crear_pedido'           => __('Crear pedidos', 'flavor-chat-ia'),
                'gc_ver_pedidos_propios'    => __('Ver pedidos propios', 'flavor-chat-ia'),
                'gc_gestionar_pedidos'      => __('Gestionar todos los pedidos', 'flavor-chat-ia'),
                'gc_cancelar_pedido_propio' => __('Cancelar pedido propio', 'flavor-chat-ia'),

                // Ciclos
                'gc_ver_ciclos'             => __('Ver ciclos de pedidos', 'flavor-chat-ia'),
                'gc_gestionar_ciclos'       => __('Crear y gestionar ciclos', 'flavor-chat-ia'),
                'gc_cerrar_ciclos'          => __('Cerrar ciclos de pedidos', 'flavor-chat-ia'),

                // Productores
                'gc_ver_productores'        => __('Ver productores', 'flavor-chat-ia'),
                'gc_gestionar_productores'  => __('Gestionar productores', 'flavor-chat-ia'),
                'gc_aprobar_productores'    => __('Aprobar nuevos productores', 'flavor-chat-ia'),

                // Grupos
                'gc_ver_grupos'             => __('Ver grupos de consumo', 'flavor-chat-ia'),
                'gc_gestionar_grupos'       => __('Gestionar grupos de consumo', 'flavor-chat-ia'),
                'gc_crear_grupos'           => __('Crear nuevos grupos', 'flavor-chat-ia'),

                // Miembros
                'gc_gestionar_miembros'     => __('Gestionar miembros del grupo', 'flavor-chat-ia'),
                'gc_aprobar_solicitudes'    => __('Aprobar solicitudes de union', 'flavor-chat-ia'),

                // Repartos
                'gc_ver_repartos'           => __('Ver calendario de repartos', 'flavor-chat-ia'),
                'gc_gestionar_repartos'     => __('Gestionar repartos', 'flavor-chat-ia'),

                // Exportacion
                'gc_exportar_datos'         => __('Exportar datos del grupo', 'flavor-chat-ia'),
                'gc_ver_estadisticas'       => __('Ver estadisticas del grupo', 'flavor-chat-ia'),

                // Suscripciones
                'gc_gestionar_suscripciones' => __('Gestionar suscripciones', 'flavor-chat-ia'),

                // Configuracion
                'gc_configurar_grupo'       => __('Configurar ajustes del grupo', 'flavor-chat-ia'),
            ],

            // Modulo Eventos - Capabilities granulares
            'eventos' => [
                'eventos_ver'               => __('Ver eventos', 'flavor-chat-ia'),
                'eventos_ver_detalles'      => __('Ver detalles de eventos', 'flavor-chat-ia'),
                'eventos_inscribirse'       => __('Inscribirse en eventos', 'flavor-chat-ia'),
                'eventos_crear'             => __('Crear eventos', 'flavor-chat-ia'),
                'eventos_editar_propios'    => __('Editar eventos propios', 'flavor-chat-ia'),
                'eventos_gestionar'         => __('Gestionar todos los eventos', 'flavor-chat-ia'),
                'eventos_eliminar'          => __('Eliminar eventos', 'flavor-chat-ia'),
                'eventos_gestionar_asistentes' => __('Gestionar asistentes', 'flavor-chat-ia'),
                'eventos_ver_estadisticas'  => __('Ver estadisticas de eventos', 'flavor-chat-ia'),
                'eventos_exportar'          => __('Exportar datos de eventos', 'flavor-chat-ia'),
                'eventos_configurar'        => __('Configurar modulo de eventos', 'flavor-chat-ia'),
            ],

            // Modulo Socios - Capabilities granulares
            'socios' => [
                'socios_ver_propios'        => __('Ver datos propios de socio', 'flavor-chat-ia'),
                'socios_editar_propios'     => __('Editar datos propios', 'flavor-chat-ia'),
                'socios_ver_directorio'     => __('Ver directorio de socios', 'flavor-chat-ia'),
                'socios_ver_todos'          => __('Ver todos los socios', 'flavor-chat-ia'),
                'socios_gestionar'          => __('Gestionar socios', 'flavor-chat-ia'),
                'socios_crear'              => __('Crear nuevos socios', 'flavor-chat-ia'),
                'socios_eliminar'           => __('Eliminar socios', 'flavor-chat-ia'),
                'socios_gestionar_cuotas'   => __('Gestionar cuotas', 'flavor-chat-ia'),
                'socios_ver_cuotas'         => __('Ver cuotas propias', 'flavor-chat-ia'),
                'socios_importar'           => __('Importar socios', 'flavor-chat-ia'),
                'socios_exportar'           => __('Exportar socios', 'flavor-chat-ia'),
                'socios_configurar'         => __('Configurar modulo de socios', 'flavor-chat-ia'),
            ],

            // Modulo Reservas
            'reservas' => [
                'reservas_ver_propias'      => __('Ver reservas propias', 'flavor-chat-ia'),
                'reservas_crear'            => __('Crear reservas', 'flavor-chat-ia'),
                'reservas_cancelar_propias' => __('Cancelar reservas propias', 'flavor-chat-ia'),
                'reservas_ver_todas'        => __('Ver todas las reservas', 'flavor-chat-ia'),
                'reservas_gestionar'        => __('Gestionar todas las reservas', 'flavor-chat-ia'),
                'reservas_aprobar'          => __('Aprobar reservas pendientes', 'flavor-chat-ia'),
                'reservas_configurar'       => __('Configurar modulo de reservas', 'flavor-chat-ia'),
            ],

            // Modulo Banco de Tiempo
            'banco_tiempo' => [
                'bt_ver_servicios'          => __('Ver servicios disponibles', 'flavor-chat-ia'),
                'bt_ofrecer_servicio'       => __('Ofrecer servicios', 'flavor-chat-ia'),
                'bt_solicitar_servicio'     => __('Solicitar servicios', 'flavor-chat-ia'),
                'bt_ver_saldo_propio'       => __('Ver saldo propio', 'flavor-chat-ia'),
                'bt_ver_historial_propio'   => __('Ver historial propio', 'flavor-chat-ia'),
                'bt_gestionar_servicios'    => __('Gestionar todos los servicios', 'flavor-chat-ia'),
                'bt_gestionar_transacciones' => __('Gestionar transacciones', 'flavor-chat-ia'),
                'bt_ver_estadisticas'       => __('Ver estadisticas', 'flavor-chat-ia'),
                'bt_configurar'             => __('Configurar banco de tiempo', 'flavor-chat-ia'),
            ],

            // Modulo Incidencias
            'incidencias' => [
                'incidencias_ver_propias'   => __('Ver incidencias propias', 'flavor-chat-ia'),
                'incidencias_crear'         => __('Reportar incidencias', 'flavor-chat-ia'),
                'incidencias_comentar'      => __('Comentar en incidencias', 'flavor-chat-ia'),
                'incidencias_ver_todas'     => __('Ver todas las incidencias', 'flavor-chat-ia'),
                'incidencias_gestionar'     => __('Gestionar incidencias', 'flavor-chat-ia'),
                'incidencias_asignar'       => __('Asignar incidencias', 'flavor-chat-ia'),
                'incidencias_cerrar'        => __('Cerrar incidencias', 'flavor-chat-ia'),
                'incidencias_configurar'    => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Newsletter
            'newsletter' => [
                'newsletter_suscribirse'    => __('Suscribirse a newsletters', 'flavor-chat-ia'),
                'newsletter_ver_campanas'   => __('Ver campanas', 'flavor-chat-ia'),
                'newsletter_crear'          => __('Crear newsletters', 'flavor-chat-ia'),
                'newsletter_enviar'         => __('Enviar newsletters', 'flavor-chat-ia'),
                'newsletter_gestionar'      => __('Gestionar campanas', 'flavor-chat-ia'),
                'newsletter_ver_estadisticas' => __('Ver estadisticas', 'flavor-chat-ia'),
                'newsletter_configurar'     => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Espacios Comunes
            'espacios' => [
                'espacios_ver'              => __('Ver espacios disponibles', 'flavor-chat-ia'),
                'espacios_reservar'         => __('Reservar espacios', 'flavor-chat-ia'),
                'espacios_ver_reservas'     => __('Ver mis reservas', 'flavor-chat-ia'),
                'espacios_gestionar'        => __('Gestionar espacios', 'flavor-chat-ia'),
                'espacios_aprobar_reservas' => __('Aprobar reservas de espacios', 'flavor-chat-ia'),
                'espacios_configurar'       => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Cursos/Talleres
            'cursos' => [
                'cursos_ver'                => __('Ver cursos disponibles', 'flavor-chat-ia'),
                'cursos_inscribirse'        => __('Inscribirse en cursos', 'flavor-chat-ia'),
                'cursos_ver_inscritos'      => __('Ver mis inscripciones', 'flavor-chat-ia'),
                'cursos_crear'              => __('Crear cursos', 'flavor-chat-ia'),
                'cursos_gestionar'          => __('Gestionar cursos', 'flavor-chat-ia'),
                'cursos_gestionar_alumnos'  => __('Gestionar alumnos', 'flavor-chat-ia'),
                'cursos_ver_estadisticas'   => __('Ver estadisticas', 'flavor-chat-ia'),
                'cursos_configurar'         => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Huertos Urbanos
            'huertos' => [
                'huertos_ver'               => __('Ver huertos', 'flavor-chat-ia'),
                'huertos_solicitar_parcela' => __('Solicitar parcela', 'flavor-chat-ia'),
                'huertos_ver_parcela_propia' => __('Ver mi parcela', 'flavor-chat-ia'),
                'huertos_gestionar'         => __('Gestionar huertos', 'flavor-chat-ia'),
                'huertos_asignar_parcelas'  => __('Asignar parcelas', 'flavor-chat-ia'),
                'huertos_ver_estadisticas'  => __('Ver estadisticas', 'flavor-chat-ia'),
                'huertos_configurar'        => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Biblioteca
            'biblioteca' => [
                'biblioteca_ver'            => __('Ver catalogo', 'flavor-chat-ia'),
                'biblioteca_prestar'        => __('Solicitar prestamos', 'flavor-chat-ia'),
                'biblioteca_ver_prestamos'  => __('Ver mis prestamos', 'flavor-chat-ia'),
                'biblioteca_gestionar'      => __('Gestionar biblioteca', 'flavor-chat-ia'),
                'biblioteca_gestionar_prestamos' => __('Gestionar prestamos', 'flavor-chat-ia'),
                'biblioteca_configurar'     => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Modulo Carpooling
            'carpooling' => [
                'carpooling_ver_viajes'     => __('Ver viajes disponibles', 'flavor-chat-ia'),
                'carpooling_ofrecer_viaje'  => __('Ofrecer viaje', 'flavor-chat-ia'),
                'carpooling_solicitar_plaza' => __('Solicitar plaza', 'flavor-chat-ia'),
                'carpooling_gestionar'      => __('Gestionar viajes', 'flavor-chat-ia'),
                'carpooling_ver_estadisticas' => __('Ver estadisticas', 'flavor-chat-ia'),
                'carpooling_configurar'     => __('Configurar modulo', 'flavor-chat-ia'),
            ],

            // Administracion general del plugin
            'admin' => [
                'flavor_manage_settings'    => __('Gestionar configuracion del plugin', 'flavor-chat-ia'),
                'flavor_view_analytics'     => __('Ver analiticas', 'flavor-chat-ia'),
                'flavor_manage_modules'     => __('Activar/desactivar modulos', 'flavor-chat-ia'),
                'flavor_export_data'        => __('Exportar datos globales', 'flavor-chat-ia'),
                'flavor_import_data'        => __('Importar datos globales', 'flavor-chat-ia'),
                'flavor_manage_permissions' => __('Gestionar permisos', 'flavor-chat-ia'),
                'flavor_manage_roles'       => __('Gestionar roles personalizados', 'flavor-chat-ia'),
            ],

            // Moderacion
            'moderacion' => [
                'flavor_moderate_content'   => __('Moderar contenido', 'flavor-chat-ia'),
                'flavor_manage_users'       => __('Gestionar usuarios del plugin', 'flavor-chat-ia'),
                'flavor_ban_users'          => __('Suspender usuarios', 'flavor-chat-ia'),
                'flavor_view_logs'          => __('Ver logs de actividad', 'flavor-chat-ia'),
            ],
        ];

        // Permitir que otros plugins/temas extiendan capabilities
        $this->capabilities_definidas = apply_filters('flavor_module_capabilities_all', $this->capabilities_definidas);
    }

    /**
     * Define los roles del plugin con sus capabilities asignadas
     */
    private function definir_roles() {
        $this->roles_definidos = [
            'flavor_visitante' => [
                'label' => __('Visitante Flavor', 'flavor-chat-ia'),
                'capabilities' => [
                    'read' => true,
                    'gc_ver_productos' => true,
                    'gc_ver_productores' => true,
                    'gc_ver_grupos' => true,
                    'eventos_ver' => true,
                    'cursos_ver' => true,
                ],
            ],

            'flavor_socio' => [
                'label' => __('Socio', 'flavor-chat-ia'),
                'capabilities' => [
                    // WordPress base
                    'read' => true,
                    // Dashboard
                    'flavor_view_dashboard'     => true,
                    'flavor_edit_profile'       => true,
                    // Grupos de Consumo
                    'gc_ver_productos'          => true,
                    'gc_crear_pedido'           => true,
                    'gc_ver_pedidos_propios'    => true,
                    'gc_cancelar_pedido_propio' => true,
                    'gc_ver_ciclos'             => true,
                    'gc_ver_productores'        => true,
                    'gc_ver_grupos'             => true,
                    'gc_ver_repartos'           => true,
                    // Eventos
                    'eventos_ver'               => true,
                    'eventos_ver_detalles'      => true,
                    'eventos_inscribirse'       => true,
                    // Socios
                    'socios_ver_propios'        => true,
                    'socios_editar_propios'     => true,
                    'socios_ver_directorio'     => true,
                    'socios_ver_cuotas'         => true,
                    // Reservas
                    'reservas_ver_propias'      => true,
                    'reservas_crear'            => true,
                    'reservas_cancelar_propias' => true,
                    // Banco de Tiempo
                    'bt_ver_servicios'          => true,
                    'bt_ofrecer_servicio'       => true,
                    'bt_solicitar_servicio'     => true,
                    'bt_ver_saldo_propio'       => true,
                    'bt_ver_historial_propio'   => true,
                    // Incidencias
                    'incidencias_ver_propias'   => true,
                    'incidencias_crear'         => true,
                    'incidencias_comentar'      => true,
                    // Newsletter
                    'newsletter_suscribirse'    => true,
                    // Espacios
                    'espacios_ver'              => true,
                    'espacios_reservar'         => true,
                    'espacios_ver_reservas'     => true,
                    // Cursos
                    'cursos_ver'                => true,
                    'cursos_inscribirse'        => true,
                    'cursos_ver_inscritos'      => true,
                    // Huertos
                    'huertos_ver'               => true,
                    'huertos_solicitar_parcela' => true,
                    'huertos_ver_parcela_propia' => true,
                    // Biblioteca
                    'biblioteca_ver'            => true,
                    'biblioteca_prestar'        => true,
                    'biblioteca_ver_prestamos'  => true,
                    // Carpooling
                    'carpooling_ver_viajes'     => true,
                    'carpooling_ofrecer_viaje'  => true,
                    'carpooling_solicitar_plaza' => true,
                ],
            ],

            'flavor_gestor' => [
                'label' => __('Gestor', 'flavor-chat-ia'),
                'capabilities' => [
                    // WordPress base
                    'read'          => true,
                    'edit_posts'    => true,
                    // Dashboard
                    'flavor_view_dashboard'      => true,
                    'flavor_edit_profile'        => true,
                    // Grupos de Consumo - acceso completo
                    'gc_ver_productos'           => true,
                    'gc_gestionar_productos'     => true,
                    'gc_crear_pedido'            => true,
                    'gc_ver_pedidos_propios'     => true,
                    'gc_gestionar_pedidos'       => true,
                    'gc_cancelar_pedido_propio'  => true,
                    'gc_ver_ciclos'              => true,
                    'gc_gestionar_ciclos'        => true,
                    'gc_cerrar_ciclos'           => true,
                    'gc_ver_productores'         => true,
                    'gc_gestionar_productores'   => true,
                    'gc_aprobar_productores'     => true,
                    'gc_ver_grupos'              => true,
                    'gc_gestionar_grupos'        => true,
                    'gc_gestionar_miembros'      => true,
                    'gc_aprobar_solicitudes'     => true,
                    'gc_ver_repartos'            => true,
                    'gc_gestionar_repartos'      => true,
                    'gc_exportar_datos'          => true,
                    'gc_ver_estadisticas'        => true,
                    'gc_gestionar_suscripciones' => true,
                    'gc_configurar_grupo'        => true,
                    // Eventos
                    'eventos_ver'                => true,
                    'eventos_ver_detalles'       => true,
                    'eventos_inscribirse'        => true,
                    'eventos_crear'              => true,
                    'eventos_editar_propios'     => true,
                    'eventos_gestionar'          => true,
                    'eventos_eliminar'           => true,
                    'eventos_gestionar_asistentes' => true,
                    'eventos_ver_estadisticas'   => true,
                    'eventos_exportar'           => true,
                    // Socios
                    'socios_ver_propios'         => true,
                    'socios_editar_propios'      => true,
                    'socios_ver_directorio'      => true,
                    'socios_ver_todos'           => true,
                    'socios_gestionar'           => true,
                    'socios_crear'               => true,
                    'socios_gestionar_cuotas'    => true,
                    'socios_ver_cuotas'          => true,
                    'socios_exportar'            => true,
                    // Reservas
                    'reservas_ver_propias'       => true,
                    'reservas_crear'             => true,
                    'reservas_cancelar_propias'  => true,
                    'reservas_ver_todas'         => true,
                    'reservas_gestionar'         => true,
                    'reservas_aprobar'           => true,
                    // Banco de Tiempo
                    'bt_ver_servicios'           => true,
                    'bt_ofrecer_servicio'        => true,
                    'bt_solicitar_servicio'      => true,
                    'bt_ver_saldo_propio'        => true,
                    'bt_ver_historial_propio'    => true,
                    'bt_gestionar_servicios'     => true,
                    'bt_gestionar_transacciones' => true,
                    'bt_ver_estadisticas'        => true,
                    // Incidencias
                    'incidencias_ver_propias'    => true,
                    'incidencias_crear'          => true,
                    'incidencias_comentar'       => true,
                    'incidencias_ver_todas'      => true,
                    'incidencias_gestionar'      => true,
                    'incidencias_asignar'        => true,
                    'incidencias_cerrar'         => true,
                    // Newsletter
                    'newsletter_suscribirse'     => true,
                    'newsletter_ver_campanas'    => true,
                    'newsletter_crear'           => true,
                    'newsletter_enviar'          => true,
                    'newsletter_gestionar'       => true,
                    'newsletter_ver_estadisticas' => true,
                    // Espacios
                    'espacios_ver'               => true,
                    'espacios_reservar'          => true,
                    'espacios_ver_reservas'      => true,
                    'espacios_gestionar'         => true,
                    'espacios_aprobar_reservas'  => true,
                    // Cursos
                    'cursos_ver'                 => true,
                    'cursos_inscribirse'         => true,
                    'cursos_ver_inscritos'       => true,
                    'cursos_crear'               => true,
                    'cursos_gestionar'           => true,
                    'cursos_gestionar_alumnos'   => true,
                    'cursos_ver_estadisticas'    => true,
                    // Huertos
                    'huertos_ver'                => true,
                    'huertos_solicitar_parcela'  => true,
                    'huertos_ver_parcela_propia' => true,
                    'huertos_gestionar'          => true,
                    'huertos_asignar_parcelas'   => true,
                    'huertos_ver_estadisticas'   => true,
                    // Biblioteca
                    'biblioteca_ver'             => true,
                    'biblioteca_prestar'         => true,
                    'biblioteca_ver_prestamos'   => true,
                    'biblioteca_gestionar'       => true,
                    'biblioteca_gestionar_prestamos' => true,
                    // Carpooling
                    'carpooling_ver_viajes'      => true,
                    'carpooling_ofrecer_viaje'   => true,
                    'carpooling_solicitar_plaza' => true,
                    'carpooling_gestionar'       => true,
                    'carpooling_ver_estadisticas' => true,
                    // Admin
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
                    // Dashboard
                    'flavor_view_dashboard'      => true,
                    'flavor_edit_profile'        => true,
                    // Moderacion
                    'flavor_moderate_content'    => true,
                    'flavor_manage_users'        => true,
                    'flavor_view_logs'           => true,
                    // Incidencias
                    'incidencias_ver_todas'      => true,
                    'incidencias_gestionar'      => true,
                    'incidencias_asignar'        => true,
                    'incidencias_cerrar'         => true,
                    // Acceso de lectura general
                    'gc_ver_productos'           => true,
                    'gc_ver_ciclos'              => true,
                    'gc_ver_productores'         => true,
                    'gc_ver_grupos'              => true,
                    'eventos_ver'                => true,
                    'eventos_ver_detalles'       => true,
                    'socios_ver_todos'           => true,
                    'flavor_view_analytics'      => true,
                ],
            ],

            'flavor_admin' => [
                'label' => __('Administrador Flavor', 'flavor-chat-ia'),
                'capabilities' => 'all', // Marca especial para dar todas las capabilities
            ],
        ];
    }

    /**
     * Define los roles especificos por modulo
     */
    private function definir_roles_por_modulo() {
        $this->roles_por_modulo = [
            // Roles del modulo Grupos de Consumo
            'grupos_consumo' => [
                'gc_consumidor' => [
                    'label' => __('Consumidor', 'flavor-chat-ia'),
                    'description' => __('Puede ver productos y realizar pedidos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'gc_ver_productos',
                        'gc_crear_pedido',
                        'gc_ver_pedidos_propios',
                        'gc_cancelar_pedido_propio',
                        'gc_ver_ciclos',
                        'gc_ver_productores',
                        'gc_ver_grupos',
                        'gc_ver_repartos',
                    ],
                ],
                'gc_productor' => [
                    'label' => __('Productor', 'flavor-chat-ia'),
                    'description' => __('Puede gestionar sus productos y ver pedidos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'gc_ver_productos',
                        'gc_gestionar_mis_productos',
                        'gc_ver_ciclos',
                        'gc_ver_productores',
                        'gc_ver_grupos',
                        'gc_ver_repartos',
                        'gc_ver_estadisticas',
                    ],
                ],
                'gc_coordinador' => [
                    'label' => __('Coordinador GC', 'flavor-chat-ia'),
                    'description' => __('Acceso completo al modulo de grupos de consumo', 'flavor-chat-ia'),
                    'capabilities' => 'gc_*', // Wildcard para todas las gc_*
                ],
            ],

            // Roles del modulo Eventos
            'eventos' => [
                'eventos_asistente' => [
                    'label' => __('Asistente', 'flavor-chat-ia'),
                    'description' => __('Puede ver e inscribirse en eventos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'eventos_ver',
                        'eventos_ver_detalles',
                        'eventos_inscribirse',
                    ],
                ],
                'eventos_organizador' => [
                    'label' => __('Organizador', 'flavor-chat-ia'),
                    'description' => __('Puede crear y gestionar sus propios eventos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'eventos_ver',
                        'eventos_ver_detalles',
                        'eventos_inscribirse',
                        'eventos_crear',
                        'eventos_editar_propios',
                        'eventos_gestionar_asistentes',
                    ],
                ],
                'eventos_gestor' => [
                    'label' => __('Gestor de Eventos', 'flavor-chat-ia'),
                    'description' => __('Acceso completo al modulo de eventos', 'flavor-chat-ia'),
                    'capabilities' => 'eventos_*',
                ],
            ],

            // Roles del modulo Socios
            'socios' => [
                'socios_basico' => [
                    'label' => __('Socio Basico', 'flavor-chat-ia'),
                    'description' => __('Puede ver y editar sus propios datos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'socios_ver_propios',
                        'socios_editar_propios',
                        'socios_ver_directorio',
                        'socios_ver_cuotas',
                    ],
                ],
                'socios_tesorero' => [
                    'label' => __('Tesorero', 'flavor-chat-ia'),
                    'description' => __('Puede gestionar cuotas y ver todos los miembros', 'flavor-chat-ia'),
                    'capabilities' => [
                        'socios_ver_propios',
                        'socios_editar_propios',
                        'socios_ver_directorio',
                        'socios_ver_todos',
                        'socios_ver_cuotas',
                        'socios_gestionar_cuotas',
                        'socios_exportar',
                    ],
                ],
                'socios_admin' => [
                    'label' => __('Admin. Miembros', 'flavor-chat-ia'),
                    'description' => __('Acceso completo al modulo de miembros', 'flavor-chat-ia'),
                    'capabilities' => 'socios_*',
                ],
            ],

            // Roles del modulo Reservas
            'reservas' => [
                'reservas_usuario' => [
                    'label' => __('Usuario Reservas', 'flavor-chat-ia'),
                    'description' => __('Puede hacer y ver sus reservas', 'flavor-chat-ia'),
                    'capabilities' => [
                        'reservas_ver_propias',
                        'reservas_crear',
                        'reservas_cancelar_propias',
                    ],
                ],
                'reservas_gestor' => [
                    'label' => __('Gestor Reservas', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a reservas', 'flavor-chat-ia'),
                    'capabilities' => 'reservas_*',
                ],
            ],

            // Roles del modulo Banco de Tiempo
            'banco_tiempo' => [
                'bt_participante' => [
                    'label' => __('Participante BT', 'flavor-chat-ia'),
                    'description' => __('Puede ofrecer y solicitar servicios', 'flavor-chat-ia'),
                    'capabilities' => [
                        'bt_ver_servicios',
                        'bt_ofrecer_servicio',
                        'bt_solicitar_servicio',
                        'bt_ver_saldo_propio',
                        'bt_ver_historial_propio',
                    ],
                ],
                'bt_gestor' => [
                    'label' => __('Gestor BT', 'flavor-chat-ia'),
                    'description' => __('Acceso completo al banco de tiempo', 'flavor-chat-ia'),
                    'capabilities' => 'bt_*',
                ],
            ],

            // Roles del modulo Incidencias
            'incidencias' => [
                'incidencias_reportero' => [
                    'label' => __('Reportero', 'flavor-chat-ia'),
                    'description' => __('Puede reportar y seguir incidencias', 'flavor-chat-ia'),
                    'capabilities' => [
                        'incidencias_ver_propias',
                        'incidencias_crear',
                        'incidencias_comentar',
                    ],
                ],
                'incidencias_tecnico' => [
                    'label' => __('Tecnico', 'flavor-chat-ia'),
                    'description' => __('Puede gestionar y cerrar incidencias', 'flavor-chat-ia'),
                    'capabilities' => [
                        'incidencias_ver_todas',
                        'incidencias_gestionar',
                        'incidencias_comentar',
                        'incidencias_cerrar',
                    ],
                ],
                'incidencias_admin' => [
                    'label' => __('Admin. Incidencias', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a incidencias', 'flavor-chat-ia'),
                    'capabilities' => 'incidencias_*',
                ],
            ],

            // Roles del modulo Newsletter
            'newsletter' => [
                'newsletter_suscriptor' => [
                    'label' => __('Suscriptor', 'flavor-chat-ia'),
                    'description' => __('Puede suscribirse a newsletters', 'flavor-chat-ia'),
                    'capabilities' => [
                        'newsletter_suscribirse',
                    ],
                ],
                'newsletter_editor' => [
                    'label' => __('Editor Newsletter', 'flavor-chat-ia'),
                    'description' => __('Puede crear y enviar newsletters', 'flavor-chat-ia'),
                    'capabilities' => [
                        'newsletter_ver_campanas',
                        'newsletter_crear',
                        'newsletter_enviar',
                        'newsletter_ver_estadisticas',
                    ],
                ],
                'newsletter_admin' => [
                    'label' => __('Admin. Newsletter', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a newsletter', 'flavor-chat-ia'),
                    'capabilities' => 'newsletter_*',
                ],
            ],

            // Roles del modulo Espacios
            'espacios' => [
                'espacios_usuario' => [
                    'label' => __('Usuario Espacios', 'flavor-chat-ia'),
                    'description' => __('Puede ver y reservar espacios', 'flavor-chat-ia'),
                    'capabilities' => [
                        'espacios_ver',
                        'espacios_reservar',
                        'espacios_ver_reservas',
                    ],
                ],
                'espacios_gestor' => [
                    'label' => __('Gestor Espacios', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a espacios', 'flavor-chat-ia'),
                    'capabilities' => 'espacios_*',
                ],
            ],

            // Roles del modulo Cursos
            'cursos' => [
                'cursos_alumno' => [
                    'label' => __('Alumno', 'flavor-chat-ia'),
                    'description' => __('Puede inscribirse en cursos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'cursos_ver',
                        'cursos_inscribirse',
                        'cursos_ver_inscritos',
                    ],
                ],
                'cursos_profesor' => [
                    'label' => __('Profesor', 'flavor-chat-ia'),
                    'description' => __('Puede crear y gestionar cursos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'cursos_ver',
                        'cursos_crear',
                        'cursos_gestionar',
                        'cursos_gestionar_alumnos',
                        'cursos_ver_estadisticas',
                    ],
                ],
                'cursos_admin' => [
                    'label' => __('Admin. Cursos', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a cursos', 'flavor-chat-ia'),
                    'capabilities' => 'cursos_*',
                ],
            ],

            // Roles del modulo Huertos
            'huertos' => [
                'huertos_hortelano' => [
                    'label' => __('Hortelano', 'flavor-chat-ia'),
                    'description' => __('Puede ver y solicitar parcelas', 'flavor-chat-ia'),
                    'capabilities' => [
                        'huertos_ver',
                        'huertos_solicitar_parcela',
                        'huertos_ver_parcela_propia',
                    ],
                ],
                'huertos_gestor' => [
                    'label' => __('Gestor Huertos', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a huertos', 'flavor-chat-ia'),
                    'capabilities' => 'huertos_*',
                ],
            ],

            // Roles del modulo Biblioteca
            'biblioteca' => [
                'biblioteca_lector' => [
                    'label' => __('Lector', 'flavor-chat-ia'),
                    'description' => __('Puede ver y solicitar prestamos', 'flavor-chat-ia'),
                    'capabilities' => [
                        'biblioteca_ver',
                        'biblioteca_prestar',
                        'biblioteca_ver_prestamos',
                    ],
                ],
                'biblioteca_gestor' => [
                    'label' => __('Gestor Biblioteca', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a biblioteca', 'flavor-chat-ia'),
                    'capabilities' => 'biblioteca_*',
                ],
            ],

            // Roles del modulo Carpooling
            'carpooling' => [
                'carpooling_usuario' => [
                    'label' => __('Usuario Carpooling', 'flavor-chat-ia'),
                    'description' => __('Puede ofrecer y buscar viajes', 'flavor-chat-ia'),
                    'capabilities' => [
                        'carpooling_ver_viajes',
                        'carpooling_ofrecer_viaje',
                        'carpooling_solicitar_plaza',
                    ],
                ],
                'carpooling_gestor' => [
                    'label' => __('Gestor Carpooling', 'flavor-chat-ia'),
                    'description' => __('Acceso completo a carpooling', 'flavor-chat-ia'),
                    'capabilities' => 'carpooling_*',
                ],
            ],
        ];

        // Permitir extension de roles por modulo
        $this->roles_por_modulo = apply_filters('flavor_module_roles', $this->roles_por_modulo);
    }

    /**
     * Crea los roles personalizados en WordPress.
     * Se ejecuta en la activacion del plugin.
     */
    public static function create_roles() {
        $instancia = self::get_instance();
        $todas_las_capabilities = $instancia->obtener_todas_las_capabilities();

        foreach ($instancia->roles_definidos as $identificador_rol => $configuracion_rol) {
            // Eliminar si existe para actualizar capabilities
            remove_role($identificador_rol);

            $capabilities_rol = [];

            if ($configuracion_rol['capabilities'] === 'all') {
                // Dar todas las capabilities
                foreach (array_keys($todas_las_capabilities) as $cap) {
                    $capabilities_rol[$cap] = true;
                }
                // Agregar caps de WordPress tambien
                $capabilities_rol['read'] = true;
                $capabilities_rol['edit_posts'] = true;
                $capabilities_rol['delete_posts'] = true;
                $capabilities_rol['publish_posts'] = true;
                $capabilities_rol['upload_files'] = true;
            } else {
                $capabilities_rol = $configuracion_rol['capabilities'];
            }

            add_role(
                $identificador_rol,
                $configuracion_rol['label'],
                $capabilities_rol
            );
        }

        // Anadir todas las capabilities de Flavor al rol administrator
        $rol_admin = get_role('administrator');
        if ($rol_admin) {
            foreach (array_keys($todas_las_capabilities) as $nombre_capability) {
                $rol_admin->add_cap($nombre_capability);
            }
        }
    }

    /**
     * Elimina los roles personalizados.
     * Se ejecuta opcionalmente en la desinstalacion del plugin.
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
     * @return array ['nombre_cap' => 'descripcion', ...]
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
     * Obtiene la definicion de roles
     *
     * @return array
     */
    public function obtener_roles() {
        return $this->roles_definidos;
    }

    /**
     * Obtiene los roles por modulo
     *
     * @param string|null $nombre_modulo Slug del modulo o null para todos
     * @return array
     */
    public function obtener_roles_modulo($nombre_modulo = null) {
        if ($nombre_modulo !== null) {
            return $this->roles_por_modulo[$nombre_modulo] ?? [];
        }
        return $this->roles_por_modulo;
    }

    /**
     * Verifica si un usuario tiene una capability especifica de Flavor
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

        // Aplicar filtro personalizado
        $puede = $usuario->has_cap($nombre_capability);
        return apply_filters('flavor_user_can', $puede, $nombre_capability, $usuario_id);
    }

    /**
     * Verifica si el usuario actual tiene una capability
     *
     * @param string $nombre_capability
     * @return bool
     */
    public static function puede($nombre_capability) {
        $puede = current_user_can($nombre_capability);
        return apply_filters('flavor_user_can', $puede, $nombre_capability, get_current_user_id());
    }

    /**
     * Obtiene los capabilities de un modulo especifico
     *
     * @param string $nombre_modulo Nombre del modulo (socios, eventos, etc.)
     * @return array
     */
    public function obtener_capabilities_modulo($nombre_modulo) {
        $capabilities = $this->capabilities_definidas[$nombre_modulo] ?? [];
        return apply_filters('flavor_module_capabilities', $capabilities, $nombre_modulo);
    }

    /**
     * Anade una capability personalizada a un rol existente
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

    /**
     * Expande wildcards en capabilities (ej: gc_* -> todas las gc_)
     *
     * @param string|array $capabilities
     * @return array
     */
    public function expandir_capabilities($capabilities) {
        if (is_string($capabilities)) {
            // Verificar si es un wildcard
            if (strpos($capabilities, '*') !== false) {
                $prefijo = str_replace('*', '', $capabilities);
                $todas = $this->obtener_todas_las_capabilities();
                $expandidas = [];

                foreach (array_keys($todas) as $cap) {
                    if (strpos($cap, $prefijo) === 0) {
                        $expandidas[] = $cap;
                    }
                }

                return $expandidas;
            }
            return [$capabilities];
        }

        // Es array, expandir cada elemento
        $resultado = [];
        foreach ($capabilities as $cap) {
            $resultado = array_merge($resultado, $this->expandir_capabilities($cap));
        }

        return array_unique($resultado);
    }

    /**
     * Obtiene las capabilities de un rol de modulo
     *
     * @param string $slug_modulo
     * @param string $slug_rol
     * @return array
     */
    public function obtener_capabilities_rol_modulo($slug_modulo, $slug_rol) {
        $rol = $this->roles_por_modulo[$slug_modulo][$slug_rol] ?? null;

        if (!$rol) {
            return [];
        }

        return $this->expandir_capabilities($rol['capabilities']);
    }

    /**
     * Asigna un rol de modulo a un usuario
     *
     * @param int    $usuario_id
     * @param string $slug_modulo
     * @param string $slug_rol
     * @return bool
     */
    public function asignar_rol_modulo($usuario_id, $slug_modulo, $slug_rol) {
        $capabilities = $this->obtener_capabilities_rol_modulo($slug_modulo, $slug_rol);

        if (empty($capabilities)) {
            return false;
        }

        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return false;
        }

        // Guardar en user meta el rol de modulo
        $roles_modulo = get_user_meta($usuario_id, '_flavor_module_roles', true);
        if (!is_array($roles_modulo)) {
            $roles_modulo = [];
        }

        $roles_modulo[$slug_modulo] = $slug_rol;
        update_user_meta($usuario_id, '_flavor_module_roles', $roles_modulo);

        // Asignar las capabilities al usuario
        foreach ($capabilities as $cap) {
            $usuario->add_cap($cap);
        }

        // Limpiar cache
        $this->limpiar_cache_usuario($usuario_id);

        do_action('flavor_module_role_assigned', $usuario_id, $slug_modulo, $slug_rol);

        return true;
    }

    /**
     * Revoca un rol de modulo de un usuario
     *
     * @param int    $usuario_id
     * @param string $slug_modulo
     * @return bool
     */
    public function revocar_rol_modulo($usuario_id, $slug_modulo) {
        $roles_modulo = get_user_meta($usuario_id, '_flavor_module_roles', true);

        if (!is_array($roles_modulo) || !isset($roles_modulo[$slug_modulo])) {
            return false;
        }

        $slug_rol = $roles_modulo[$slug_modulo];
        $capabilities = $this->obtener_capabilities_rol_modulo($slug_modulo, $slug_rol);

        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return false;
        }

        // Remover capabilities
        foreach ($capabilities as $cap) {
            $usuario->remove_cap($cap);
        }

        // Actualizar meta
        unset($roles_modulo[$slug_modulo]);
        update_user_meta($usuario_id, '_flavor_module_roles', $roles_modulo);

        // Limpiar cache
        $this->limpiar_cache_usuario($usuario_id);

        do_action('flavor_module_role_revoked', $usuario_id, $slug_modulo, $slug_rol);

        return true;
    }

    /**
     * Obtiene el rol de modulo asignado a un usuario
     *
     * @param int    $usuario_id
     * @param string $slug_modulo
     * @return string|null
     */
    public function obtener_rol_modulo_usuario($usuario_id, $slug_modulo) {
        $roles_modulo = get_user_meta($usuario_id, '_flavor_module_roles', true);

        if (!is_array($roles_modulo)) {
            return null;
        }

        return $roles_modulo[$slug_modulo] ?? null;
    }

    /**
     * Obtiene todos los roles de modulo de un usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_roles_modulo_usuario($usuario_id) {
        $roles_modulo = get_user_meta($usuario_id, '_flavor_module_roles', true);
        return is_array($roles_modulo) ? $roles_modulo : [];
    }

    /**
     * Filtra las capabilities del usuario dinamicamente
     *
     * @param array   $allcaps
     * @param array   $caps
     * @param array   $args
     * @param WP_User $user
     * @return array
     */
    public function filtrar_capabilities_usuario($allcaps, $caps, $args, $user) {
        // Agregar capabilities de roles de modulo
        $roles_modulo = $this->obtener_roles_modulo_usuario($user->ID);

        foreach ($roles_modulo as $slug_modulo => $slug_rol) {
            $caps_modulo = $this->obtener_capabilities_rol_modulo($slug_modulo, $slug_rol);
            foreach ($caps_modulo as $cap) {
                $allcaps[$cap] = true;
            }
        }

        return $allcaps;
    }

    /**
     * Limpia el cache de permisos de un usuario
     *
     * @param int $usuario_id
     */
    public function limpiar_cache_usuario($usuario_id) {
        unset($this->cache_permisos_usuario[$usuario_id]);
        wp_cache_delete($usuario_id, 'user_meta');
    }

    /**
     * Obtiene usuarios con un rol de modulo especifico
     *
     * @param string $slug_modulo
     * @param string $slug_rol
     * @return array IDs de usuarios
     */
    public function obtener_usuarios_por_rol_modulo($slug_modulo, $slug_rol) {
        global $wpdb;

        $meta_key = '_flavor_module_roles';

        // Buscar usuarios con el meta
        $usuarios = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta}
                WHERE meta_key = %s
                AND meta_value LIKE %s",
                $meta_key,
                '%' . $wpdb->esc_like('"' . $slug_modulo . '":"' . $slug_rol . '"') . '%'
            )
        );

        return array_map('intval', $usuarios);
    }

    /**
     * Crea un rol personalizado
     *
     * @param array $datos_rol
     * @return bool|WP_Error
     */
    public function crear_rol_personalizado($datos_rol) {
        $slug = sanitize_key($datos_rol['slug']);
        $label = sanitize_text_field($datos_rol['label']);
        $capabilities = isset($datos_rol['capabilities']) ? $datos_rol['capabilities'] : [];
        $modulo = isset($datos_rol['modulo']) ? sanitize_key($datos_rol['modulo']) : null;

        if (empty($slug) || empty($label)) {
            return new WP_Error('datos_invalidos', __('Slug y label son requeridos', 'flavor-chat-ia'));
        }

        // Guardar en opciones
        $roles_personalizados = get_option('flavor_custom_roles', []);

        $roles_personalizados[$slug] = [
            'label' => $label,
            'description' => sanitize_text_field($datos_rol['description'] ?? ''),
            'capabilities' => $capabilities,
            'modulo' => $modulo,
            'created' => current_time('mysql'),
        ];

        update_option('flavor_custom_roles', $roles_personalizados);

        // Si es rol global, crear en WordPress
        if (empty($modulo)) {
            $caps_expandidas = [];
            foreach ($this->expandir_capabilities($capabilities) as $cap) {
                $caps_expandidas[$cap] = true;
            }
            add_role($slug, $label, $caps_expandidas);
        }

        do_action('flavor_custom_role_created', $slug, $datos_rol);

        return true;
    }

    /**
     * Elimina un rol personalizado
     *
     * @param string $slug
     * @return bool
     */
    public function eliminar_rol_personalizado($slug) {
        $roles_personalizados = get_option('flavor_custom_roles', []);

        if (!isset($roles_personalizados[$slug])) {
            return false;
        }

        $rol = $roles_personalizados[$slug];
        unset($roles_personalizados[$slug]);
        update_option('flavor_custom_roles', $roles_personalizados);

        // Si era rol global, remover de WordPress
        if (empty($rol['modulo'])) {
            remove_role($slug);
        }

        do_action('flavor_custom_role_deleted', $slug);

        return true;
    }

    /**
     * Obtiene los roles personalizados
     *
     * @param string|null $modulo Filtrar por modulo
     * @return array
     */
    public function obtener_roles_personalizados($modulo = null) {
        $roles = get_option('flavor_custom_roles', []);

        if ($modulo !== null) {
            return array_filter($roles, function($rol) use ($modulo) {
                return ($rol['modulo'] ?? null) === $modulo;
            });
        }

        return $roles;
    }

    /**
     * Actualiza las capabilities de un rol personalizado
     *
     * @param string $slug
     * @param array  $nuevas_capabilities
     * @return bool
     */
    public function actualizar_capabilities_rol($slug, $nuevas_capabilities) {
        $roles_personalizados = get_option('flavor_custom_roles', []);

        if (!isset($roles_personalizados[$slug])) {
            // Verificar si es un rol de WordPress
            $rol = get_role($slug);
            if (!$rol) {
                return false;
            }

            // Limpiar caps actuales de Flavor
            $todas_caps = array_keys($this->obtener_todas_las_capabilities());
            foreach ($todas_caps as $cap) {
                $rol->remove_cap($cap);
            }

            // Agregar nuevas
            foreach ($this->expandir_capabilities($nuevas_capabilities) as $cap) {
                $rol->add_cap($cap);
            }

            return true;
        }

        // Es rol personalizado
        $roles_personalizados[$slug]['capabilities'] = $nuevas_capabilities;
        $roles_personalizados[$slug]['updated'] = current_time('mysql');
        update_option('flavor_custom_roles', $roles_personalizados);

        // Si es rol global, actualizar en WordPress
        if (empty($roles_personalizados[$slug]['modulo'])) {
            $rol = get_role($slug);
            if ($rol) {
                $todas_caps = array_keys($this->obtener_todas_las_capabilities());
                foreach ($todas_caps as $cap) {
                    $rol->remove_cap($cap);
                }
                foreach ($this->expandir_capabilities($nuevas_capabilities) as $cap) {
                    $rol->add_cap($cap);
                }
            }
        }

        do_action('flavor_role_capabilities_updated', $slug, $nuevas_capabilities);

        return true;
    }

    /**
     * Obtiene todos los modulos disponibles con sus capabilities
     *
     * @return array
     */
    public function obtener_modulos_con_capabilities() {
        $modulos = [];

        foreach ($this->capabilities_definidas as $slug => $caps) {
            if ($slug === 'dashboard' || $slug === 'admin' || $slug === 'moderacion') {
                continue; // Estos no son modulos como tal
            }

            $modulos[$slug] = [
                'slug' => $slug,
                'label' => $this->obtener_label_modulo($slug),
                'capabilities' => $caps,
                'roles' => $this->roles_por_modulo[$slug] ?? [],
            ];
        }

        return $modulos;
    }

    /**
     * Obtiene el label de un modulo
     *
     * @param string $slug
     * @return string
     */
    private function obtener_label_modulo($slug) {
        $labels = [
            'grupos_consumo' => __('Grupos de Consumo', 'flavor-chat-ia'),
            'eventos' => __('Eventos', 'flavor-chat-ia'),
            'socios' => __('Miembros', 'flavor-chat-ia'),
            'reservas' => __('Reservas', 'flavor-chat-ia'),
            'banco_tiempo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'incidencias' => __('Incidencias', 'flavor-chat-ia'),
            'newsletter' => __('Newsletter', 'flavor-chat-ia'),
            'espacios' => __('Espacios Comunes', 'flavor-chat-ia'),
            'cursos' => __('Cursos y Talleres', 'flavor-chat-ia'),
            'huertos' => __('Huertos Urbanos', 'flavor-chat-ia'),
            'biblioteca' => __('Biblioteca', 'flavor-chat-ia'),
            'carpooling' => __('Carpooling', 'flavor-chat-ia'),
        ];

        return $labels[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
    }
}

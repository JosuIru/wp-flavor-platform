<?php
/**
 * REST API para gestión de Módulos
 *
 * Permite a Claude Code activar/desactivar módulos, ver configuraciones
 * y generar datos de demostración.
 *
 * @package Flavor_Platform
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de gestión de módulos
 */
class Flavor_Module_Manager_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Module_Manager_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Clave de API
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Manager_API
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $this->api_key = flavor_get_vbp_api_key();

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verifica permisos de API
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function check_permission( $request ) {
        $api_key = flavor_get_vbp_api_key_from_request( $request );
        if ( flavor_check_vbp_automation_access( $api_key, 'module_manager' ) ) {
            return true;
        }

        return current_user_can( 'manage_options' );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Listar todos los módulos disponibles
        register_rest_route( self::NAMESPACE, '/modules/available', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_available_modules' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Ver módulos activos
        register_rest_route( self::NAMESPACE, '/modules/active', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_active_modules' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Activar módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/activate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'activate_module' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Desactivar módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/deactivate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'deactivate_module' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Activar múltiples módulos
        register_rest_route( self::NAMESPACE, '/modules/activate-batch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'activate_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'modules' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // Obtener configuración de módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_module_config' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Actualizar configuración de módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/config', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_module_config' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'config' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Generar datos de demo para módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/demo-data', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_demo_data' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'count' => array(
                    'type'    => 'integer',
                    'default' => 10,
                ),
            ),
        ) );

        // Obtener estadísticas de módulo
        register_rest_route( self::NAMESPACE, '/modules/(?P<module_id>[a-z0-9-]+)/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_module_stats' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Obtener recomendaciones de módulos
        register_rest_route( self::NAMESPACE, '/modules/recommendations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_recommendations' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'use_case' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Aplicar perfil de módulos (conjunto predefinido)
        register_rest_route( self::NAMESPACE, '/modules/apply-profile', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_profile' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'profile' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // Listar perfiles disponibles
        register_rest_route( self::NAMESPACE, '/modules/profiles', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_profiles' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    /**
     * Lista todos los módulos disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_available_modules( $request ) {
        $category_filter = $request->get_param( 'category' );
        $active_modules = get_option( 'flavor_chat_modules', array() );

        $modules = $this->get_all_modules_info();

        // Filtrar por categoría
        if ( ! empty( $category_filter ) ) {
            $modules = array_filter( $modules, function( $module ) use ( $category_filter ) {
                return ( $module['category'] ?? '' ) === $category_filter;
            } );
        }

        // Añadir estado activo
        foreach ( $modules as &$module ) {
            $module['active'] = in_array( $module['id'], $active_modules, true );
        }

        // Agrupar por categoría
        $by_category = array();
        foreach ( $modules as $module ) {
            $cat = $module['category'] ?? 'otros';
            if ( ! isset( $by_category[ $cat ] ) ) {
                $by_category[ $cat ] = array();
            }
            $by_category[ $cat ][] = $module;
        }

        $categories = array_unique( array_column( $this->get_all_modules_info(), 'category' ) );

        return new WP_REST_Response( array(
            'modules'     => array_values( $modules ),
            'by_category' => $by_category,
            'categories'  => array_values( array_filter( $categories ) ),
            'total'       => count( $modules ),
            'active_count'=> count( $active_modules ),
        ), 200 );
    }

    /**
     * Obtiene información de todos los módulos
     *
     * @return array
     */
    private function get_all_modules_info() {
        return array(
            // Comunicación
            array(
                'id'          => 'chat-interno',
                'name'        => 'Chat Interno',
                'description' => 'Sistema de mensajería instantánea entre usuarios',
                'category'    => 'comunicacion',
                'icon'        => '💬',
                'features'    => array( 'Mensajes privados', 'Grupos', 'Archivos adjuntos' ),
            ),
            array(
                'id'          => 'foros',
                'name'        => 'Foros',
                'description' => 'Foros de discusión organizados por categorías',
                'category'    => 'comunicacion',
                'icon'        => '📢',
                'features'    => array( 'Categorías', 'Hilos', 'Moderación' ),
            ),
            array(
                'id'          => 'avisos-municipales',
                'name'        => 'Avisos Municipales',
                'description' => 'Sistema de avisos y comunicados oficiales',
                'category'    => 'comunicacion',
                'icon'        => '📣',
                'features'    => array( 'Avisos', 'Notificaciones', 'Historial' ),
            ),

            // Comunidad
            array(
                'id'          => 'eventos',
                'name'        => 'Eventos',
                'description' => 'Gestión de eventos con inscripciones y calendario',
                'category'    => 'comunidad',
                'icon'        => '📅',
                'features'    => array( 'Calendario', 'Inscripciones', 'Recordatorios' ),
            ),
            array(
                'id'          => 'socios',
                'name'        => 'Socios',
                'description' => 'Gestión de membresías y cuotas de socios',
                'category'    => 'comunidad',
                'icon'        => '👥',
                'features'    => array( 'Carnet digital', 'Cuotas', 'Beneficios' ),
            ),
            array(
                'id'          => 'comunidades',
                'name'        => 'Comunidades',
                'description' => 'Grupos y subcomunidades dentro de la plataforma',
                'category'    => 'comunidad',
                'icon'        => '🏘️',
                'features'    => array( 'Grupos', 'Administración', 'Feed' ),
            ),
            array(
                'id'          => 'colectivos',
                'name'        => 'Colectivos',
                'description' => 'Gestión de colectivos y asambleas',
                'category'    => 'comunidad',
                'icon'        => '✊',
                'features'    => array( 'Asambleas', 'Votaciones', 'Proyectos' ),
            ),

            // Economía
            array(
                'id'          => 'grupos-consumo',
                'name'        => 'Grupos de Consumo',
                'description' => 'Compra colectiva de productos ecológicos',
                'category'    => 'economia',
                'icon'        => '🥕',
                'features'    => array( 'Ciclos de compra', 'Productores', 'Pedidos' ),
            ),
            array(
                'id'          => 'marketplace',
                'name'        => 'Marketplace',
                'description' => 'Mercado de compraventa entre usuarios',
                'category'    => 'economia',
                'icon'        => '🛒',
                'features'    => array( 'Anuncios', 'Categorías', 'Mensajes' ),
            ),
            array(
                'id'          => 'banco-tiempo',
                'name'        => 'Banco de Tiempo',
                'description' => 'Intercambio de servicios por tiempo',
                'category'    => 'economia',
                'icon'        => '⏰',
                'features'    => array( 'Ofertas', 'Demandas', 'Balance de horas' ),
            ),
            array(
                'id'          => 'crowdfunding',
                'name'        => 'Crowdfunding',
                'description' => 'Financiación colectiva de proyectos',
                'category'    => 'economia',
                'icon'        => '💰',
                'features'    => array( 'Campañas', 'Donaciones', 'Recompensas' ),
            ),
            array(
                'id'          => 'economia-don',
                'name'        => 'Economía del Don',
                'description' => 'Sistema de regalos y donaciones',
                'category'    => 'economia',
                'icon'        => '🎁',
                'features'    => array( 'Ofrecer', 'Solicitar', 'Gratitud' ),
            ),

            // Formación
            array(
                'id'          => 'cursos',
                'name'        => 'Cursos',
                'description' => 'Plataforma de cursos y formación online',
                'category'    => 'formacion',
                'icon'        => '🎓',
                'features'    => array( 'Lecciones', 'Certificados', 'Progreso' ),
            ),
            array(
                'id'          => 'talleres',
                'name'        => 'Talleres',
                'description' => 'Gestión de talleres presenciales',
                'category'    => 'formacion',
                'icon'        => '🔧',
                'features'    => array( 'Inscripciones', 'Materiales', 'Asistencia' ),
            ),
            array(
                'id'          => 'biblioteca',
                'name'        => 'Biblioteca',
                'description' => 'Biblioteca comunitaria con préstamos',
                'category'    => 'formacion',
                'icon'        => '📚',
                'features'    => array( 'Catálogo', 'Préstamos', 'Reservas' ),
            ),

            // Ecología
            array(
                'id'          => 'huertos-urbanos',
                'name'        => 'Huertos Urbanos',
                'description' => 'Gestión de huertos comunitarios',
                'category'    => 'ecologia',
                'icon'        => '🌱',
                'features'    => array( 'Parcelas', 'Tareas', 'Cosechas' ),
            ),
            array(
                'id'          => 'compostaje',
                'name'        => 'Compostaje',
                'description' => 'Red de compostaje comunitario',
                'category'    => 'ecologia',
                'icon'        => '♻️',
                'features'    => array( 'Puntos', 'Aportaciones', 'Compost' ),
            ),
            array(
                'id'          => 'reciclaje',
                'name'        => 'Reciclaje',
                'description' => 'Campañas y puntos de reciclaje',
                'category'    => 'ecologia',
                'icon'        => '🗑️',
                'features'    => array( 'Campañas', 'Puntos limpios', 'Estadísticas' ),
            ),
            array(
                'id'          => 'biodiversidad-local',
                'name'        => 'Biodiversidad Local',
                'description' => 'Catálogo de especies y avistamientos',
                'category'    => 'ecologia',
                'icon'        => '🦋',
                'features'    => array( 'Catálogo', 'Avistamientos', 'Proyectos' ),
            ),

            // Movilidad
            array(
                'id'          => 'carpooling',
                'name'        => 'Carpooling',
                'description' => 'Viajes compartidos en coche',
                'category'    => 'movilidad',
                'icon'        => '🚗',
                'features'    => array( 'Viajes', 'Reservas', 'Valoraciones' ),
            ),
            array(
                'id'          => 'bicicletas-compartidas',
                'name'        => 'Bicicletas Compartidas',
                'description' => 'Sistema de préstamo de bicicletas',
                'category'    => 'movilidad',
                'icon'        => '🚲',
                'features'    => array( 'Estaciones', 'Préstamos', 'Estado' ),
            ),
            array(
                'id'          => 'parkings',
                'name'        => 'Parkings',
                'description' => 'Gestión de plazas de aparcamiento compartidas',
                'category'    => 'movilidad',
                'icon'        => '🅿️',
                'features'    => array( 'Plazas', 'Reservas', 'Propietarios' ),
            ),

            // Participación
            array(
                'id'          => 'encuestas',
                'name'        => 'Encuestas',
                'description' => 'Sistema de encuestas y votaciones',
                'category'    => 'participacion',
                'icon'        => '📊',
                'features'    => array( 'Encuestas', 'Votaciones', 'Resultados' ),
            ),
            array(
                'id'          => 'presupuestos-participativos',
                'name'        => 'Presupuestos Participativos',
                'description' => 'Votación de proyectos comunitarios',
                'category'    => 'participacion',
                'icon'        => '🗳️',
                'features'    => array( 'Propuestas', 'Votación', 'Seguimiento' ),
            ),
            array(
                'id'          => 'transparencia',
                'name'        => 'Transparencia',
                'description' => 'Portal de transparencia y rendición de cuentas',
                'category'    => 'participacion',
                'icon'        => '🔍',
                'features'    => array( 'Presupuestos', 'Actas', 'Contratos' ),
            ),
            array(
                'id'          => 'campanias',
                'name'        => 'Campañas',
                'description' => 'Campañas de firmas y movilización',
                'category'    => 'participacion',
                'icon'        => '✍️',
                'features'    => array( 'Firmas', 'Objetivos', 'Difusión' ),
            ),

            // Gestión
            array(
                'id'          => 'incidencias',
                'name'        => 'Incidencias',
                'description' => 'Sistema de tickets y soporte',
                'category'    => 'gestion',
                'icon'        => '🎫',
                'features'    => array( 'Tickets', 'Categorías', 'Estados' ),
            ),
            array(
                'id'          => 'reservas',
                'name'        => 'Reservas',
                'description' => 'Sistema de reserva de espacios y recursos',
                'category'    => 'gestion',
                'icon'        => '📋',
                'features'    => array( 'Recursos', 'Calendario', 'Confirmación' ),
            ),
            array(
                'id'          => 'espacios-comunes',
                'name'        => 'Espacios Comunes',
                'description' => 'Gestión de espacios compartidos',
                'category'    => 'gestion',
                'icon'        => '🏛️',
                'features'    => array( 'Espacios', 'Reservas', 'Normas' ),
            ),
            array(
                'id'          => 'tramites',
                'name'        => 'Trámites',
                'description' => 'Gestión de trámites administrativos',
                'category'    => 'gestion',
                'icon'        => '📑',
                'features'    => array( 'Solicitudes', 'Estados', 'Documentos' ),
            ),

            // Cultura
            array(
                'id'          => 'kulturaka',
                'name'        => 'Kulturaka',
                'description' => 'Agenda cultural y artistas locales',
                'category'    => 'cultura',
                'icon'        => '🎭',
                'features'    => array( 'Artistas', 'Espacios', 'Eventos' ),
            ),
            array(
                'id'          => 'radio',
                'name'        => 'Radio',
                'description' => 'Radio comunitaria online',
                'category'    => 'cultura',
                'icon'        => '📻',
                'features'    => array( 'Programas', 'Locutores', 'Streaming' ),
            ),
            array(
                'id'          => 'podcast',
                'name'        => 'Podcast',
                'description' => 'Plataforma de podcasts',
                'category'    => 'cultura',
                'icon'        => '🎙️',
                'features'    => array( 'Episodios', 'Series', 'Suscripciones' ),
            ),
            array(
                'id'          => 'multimedia',
                'name'        => 'Multimedia',
                'description' => 'Galería de fotos y vídeos',
                'category'    => 'cultura',
                'icon'        => '🖼️',
                'features'    => array( 'Galerías', 'Álbumes', 'Streaming' ),
            ),

            // Social
            array(
                'id'          => 'red-social',
                'name'        => 'Red Social',
                'description' => 'Feed social con publicaciones',
                'category'    => 'social',
                'icon'        => '📱',
                'features'    => array( 'Publicaciones', 'Comentarios', 'Likes' ),
            ),
            array(
                'id'          => 'ayuda-vecinal',
                'name'        => 'Ayuda Vecinal',
                'description' => 'Red de ayuda entre vecinos',
                'category'    => 'social',
                'icon'        => '🤝',
                'features'    => array( 'Solicitudes', 'Ofertas', 'Matching' ),
            ),
            array(
                'id'          => 'circulos-cuidados',
                'name'        => 'Círculos de Cuidados',
                'description' => 'Redes de apoyo y cuidados mutuos',
                'category'    => 'social',
                'icon'        => '💚',
                'features'    => array( 'Círculos', 'Turnos', 'Coordinación' ),
            ),

            // Trabajo
            array(
                'id'          => 'trabajo-digno',
                'name'        => 'Trabajo Digno',
                'description' => 'Bolsa de empleo y ofertas laborales',
                'category'    => 'trabajo',
                'icon'        => '💼',
                'features'    => array( 'Ofertas', 'CV', 'Candidaturas' ),
            ),
            array(
                'id'          => 'fichaje-empleados',
                'name'        => 'Fichaje Empleados',
                'description' => 'Control horario de trabajadores',
                'category'    => 'trabajo',
                'icon'        => '⏱️',
                'features'    => array( 'Fichajes', 'Horarios', 'Informes' ),
            ),

            // Otros
            array(
                'id'          => 'mapa-actores',
                'name'        => 'Mapa de Actores',
                'description' => 'Directorio geolocalizado de entidades',
                'category'    => 'otros',
                'icon'        => '🗺️',
                'features'    => array( 'Mapa', 'Directorio', 'Categorías' ),
            ),
            array(
                'id'          => 'email-marketing',
                'name'        => 'Email Marketing',
                'description' => 'Campañas de email y newsletters',
                'category'    => 'otros',
                'icon'        => '📧',
                'features'    => array( 'Campañas', 'Listas', 'Automatizaciones' ),
            ),
        );
    }

    /**
     * Obtiene los módulos activos
     *
     * @return WP_REST_Response
     */
    public function get_active_modules() {
        $active_ids = get_option( 'flavor_chat_modules', array() );
        $all_modules = $this->get_all_modules_info();

        $active_modules = array();
        foreach ( $all_modules as $module ) {
            if ( in_array( $module['id'], $active_ids, true ) ) {
                $module['active'] = true;
                $active_modules[] = $module;
            }
        }

        return new WP_REST_Response( array(
            'modules' => $active_modules,
            'total'   => count( $active_modules ),
            'ids'     => $active_ids,
        ), 200 );
    }

    /**
     * Activa un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function activate_module( $request ) {
        $module_id = $request->get_param( 'module_id' );

        // Verificar que el módulo existe
        $all_modules = $this->get_all_modules_info();
        $module_exists = false;
        $module_info = null;

        foreach ( $all_modules as $module ) {
            if ( $module['id'] === $module_id ) {
                $module_exists = true;
                $module_info = $module;
                break;
            }
        }

        if ( ! $module_exists ) {
            return new WP_REST_Response( array(
                'error'   => 'Módulo no encontrado',
                'module'  => $module_id,
                'valid'   => array_column( $all_modules, 'id' ),
            ), 404 );
        }

        // Obtener módulos activos actuales
        $active_modules = get_option( 'flavor_chat_modules', array() );

        // Verificar si ya está activo
        if ( in_array( $module_id, $active_modules, true ) ) {
            return new WP_REST_Response( array(
                'success' => true,
                'message' => 'El módulo ya estaba activo',
                'module'  => $module_info,
            ), 200 );
        }

        // Añadir módulo
        $active_modules[] = $module_id;
        update_option( 'flavor_chat_modules', $active_modules );

        // Ejecutar hook de activación del módulo
        do_action( 'flavor_module_activated', $module_id );
        do_action( "flavor_module_{$module_id}_activated" );

        return new WP_REST_Response( array(
            'success'       => true,
            'message'       => "Módulo '{$module_info['name']}' activado correctamente",
            'module'        => $module_info,
            'active_count'  => count( $active_modules ),
        ), 200 );
    }

    /**
     * Desactiva un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function deactivate_module( $request ) {
        $module_id = $request->get_param( 'module_id' );

        $active_modules = get_option( 'flavor_chat_modules', array() );

        // Verificar si está activo
        $key = array_search( $module_id, $active_modules, true );
        if ( $key === false ) {
            return new WP_REST_Response( array(
                'success' => true,
                'message' => 'El módulo ya estaba desactivado',
                'module'  => $module_id,
            ), 200 );
        }

        // Quitar módulo
        unset( $active_modules[ $key ] );
        $active_modules = array_values( $active_modules );
        update_option( 'flavor_chat_modules', $active_modules );

        // Ejecutar hook de desactivación
        do_action( 'flavor_module_deactivated', $module_id );
        do_action( "flavor_module_{$module_id}_deactivated" );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => "Módulo '{$module_id}' desactivado correctamente",
            'module'       => $module_id,
            'active_count' => count( $active_modules ),
        ), 200 );
    }

    /**
     * Activa múltiples módulos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function activate_batch( $request ) {
        $module_ids = $request->get_param( 'modules' );
        $all_modules = $this->get_all_modules_info();
        $valid_ids = array_column( $all_modules, 'id' );

        $active_modules = get_option( 'flavor_chat_modules', array() );
        $activated = array();
        $already_active = array();
        $invalid = array();

        foreach ( $module_ids as $module_id ) {
            if ( ! in_array( $module_id, $valid_ids, true ) ) {
                $invalid[] = $module_id;
                continue;
            }

            if ( in_array( $module_id, $active_modules, true ) ) {
                $already_active[] = $module_id;
                continue;
            }

            $active_modules[] = $module_id;
            $activated[] = $module_id;

            // Ejecutar hooks
            do_action( 'flavor_module_activated', $module_id );
            do_action( "flavor_module_{$module_id}_activated" );
        }

        update_option( 'flavor_chat_modules', $active_modules );

        return new WP_REST_Response( array(
            'success'        => true,
            'activated'      => $activated,
            'already_active' => $already_active,
            'invalid'        => $invalid,
            'total_active'   => count( $active_modules ),
        ), 200 );
    }

    /**
     * Obtiene configuración de un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_module_config( $request ) {
        $module_id = $request->get_param( 'module_id' );

        // Buscar info del módulo
        $all_modules = $this->get_all_modules_info();
        $module_info = null;

        foreach ( $all_modules as $module ) {
            if ( $module['id'] === $module_id ) {
                $module_info = $module;
                break;
            }
        }

        if ( ! $module_info ) {
            return new WP_REST_Response( array( 'error' => 'Módulo no encontrado' ), 404 );
        }

        // Obtener configuración guardada
        $config = get_option( "flavor_module_{$module_id}_config", array() );

        // Configuración por defecto según el módulo
        $defaults = $this->get_module_default_config( $module_id );

        return new WP_REST_Response( array(
            'module'   => $module_info,
            'config'   => array_merge( $defaults, $config ),
            'defaults' => $defaults,
        ), 200 );
    }

    /**
     * Obtiene configuración por defecto de un módulo
     *
     * @param string $module_id ID del módulo.
     * @return array
     */
    private function get_module_default_config( $module_id ) {
        $defaults = array(
            'eventos' => array(
                'enable_inscriptions' => true,
                'enable_reminders'    => true,
                'max_attendees'       => 100,
                'show_calendar'       => true,
            ),
            'grupos-consumo' => array(
                'enable_map'          => true,
                'cycle_duration_days' => 14,
                'enable_payments'     => true,
            ),
            'marketplace' => array(
                'enable_messages'   => true,
                'max_images'        => 5,
                'enable_categories' => true,
            ),
            'socios' => array(
                'enable_card'       => true,
                'enable_benefits'   => true,
                'auto_renewal'      => true,
            ),
            'cursos' => array(
                'enable_certificates' => true,
                'enable_progress'     => true,
                'enable_quiz'         => true,
            ),
        );

        return $defaults[ $module_id ] ?? array();
    }

    /**
     * Actualiza configuración de un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_module_config( $request ) {
        $module_id = $request->get_param( 'module_id' );
        $config = $request->get_param( 'config' );

        // Obtener config actual
        $current_config = get_option( "flavor_module_{$module_id}_config", array() );

        // Mezclar con nueva configuración
        $new_config = array_merge( $current_config, $config );

        // Guardar
        update_option( "flavor_module_{$module_id}_config", $new_config );

        // Ejecutar hook
        do_action( "flavor_module_{$module_id}_config_updated", $new_config );

        return new WP_REST_Response( array(
            'success' => true,
            'module'  => $module_id,
            'config'  => $new_config,
        ), 200 );
    }

    /**
     * Genera datos de demo para un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_demo_data( $request ) {
        $module_id = $request->get_param( 'module_id' );
        $count = $request->get_param( 'count' );

        // Verificar que el módulo está activo
        $active_modules = get_option( 'flavor_chat_modules', array() );
        if ( ! in_array( $module_id, $active_modules, true ) ) {
            return new WP_REST_Response( array(
                'error'   => 'El módulo debe estar activo para generar datos de demo',
                'module'  => $module_id,
                'tip'     => "Primero activa el módulo con POST /modules/{$module_id}/activate",
            ), 400 );
        }

        // Intentar usar el generador de demo del plugin
        $generator_class = 'Flavor_Demo_Data_Generator';
        if ( ! class_exists( $generator_class ) ) {
            // Intentar cargar
            $path = FLAVOR_PLATFORM_PATH . 'includes/class-demo-data-generator.php';
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        $result = array(
            'module'  => $module_id,
            'count'   => $count,
            'items'   => array(),
            'success' => false,
        );

        if ( class_exists( $generator_class ) ) {
            $generator = new $generator_class();
            $method = "generate_{$module_id}_data";
            $method_alt = 'generate_' . str_replace( '-', '_', $module_id ) . '_data';

            if ( method_exists( $generator, $method ) ) {
                $result['items'] = $generator->$method( $count );
                $result['success'] = true;
            } elseif ( method_exists( $generator, $method_alt ) ) {
                $result['items'] = $generator->$method_alt( $count );
                $result['success'] = true;
            } else {
                // Generar datos genéricos
                $result = $this->generate_generic_demo_data( $module_id, $count );
            }
        } else {
            // Generar datos genéricos
            $result = $this->generate_generic_demo_data( $module_id, $count );
        }

        return new WP_REST_Response( $result, $result['success'] ? 201 : 200 );
    }

    /**
     * Genera datos de demo genéricos
     *
     * @param string $module_id ID del módulo.
     * @param int    $count     Cantidad de items.
     * @return array
     */
    private function generate_generic_demo_data( $module_id, $count ) {
        $items = array();

        // Datos de ejemplo según el módulo
        $templates = array(
            'eventos' => array(
                'titles' => array( 'Asamblea General', 'Taller de Huerto', 'Charla Sostenibilidad', 'Encuentro Vecinal', 'Fiesta de Barrio' ),
                'type'   => 'event',
            ),
            'grupos-consumo' => array(
                'titles' => array( 'Verduras Ecológicas', 'Frutas de Temporada', 'Lácteos Artesanos', 'Pan de Masa Madre', 'Aceite Ecológico' ),
                'type'   => 'product',
            ),
            'marketplace' => array(
                'titles' => array( 'Bicicleta de segunda mano', 'Mueble vintage', 'Libros varios', 'Electrodoméstico', 'Ropa infantil' ),
                'type'   => 'listing',
            ),
            'cursos' => array(
                'titles' => array( 'Introducción a la Permacultura', 'Cocina Vegana', 'Reparación de Bicicletas', 'Costura Básica', 'Huerto en Casa' ),
                'type'   => 'course',
            ),
            'talleres' => array(
                'titles' => array( 'Taller de Compostaje', 'Arreglo de Ropa', 'Conservas Caseras', 'Jabones Naturales', 'Cosmética Natural' ),
                'type'   => 'workshop',
            ),
        );

        $template = $templates[ $module_id ] ?? array(
            'titles' => array( 'Item de demo 1', 'Item de demo 2', 'Item de demo 3', 'Item de demo 4', 'Item de demo 5' ),
            'type'   => 'generic',
        );

        for ( $i = 1; $i <= $count; $i++ ) {
            $title = $template['titles'][ ( $i - 1 ) % count( $template['titles'] ) ];
            $items[] = array(
                'id'          => $i,
                'title'       => $title . ( $i > 5 ? ' #' . $i : '' ),
                'type'        => $template['type'],
                'created'     => current_time( 'mysql' ),
                'demo'        => true,
            );
        }

        return array(
            'module'  => $module_id,
            'count'   => $count,
            'items'   => $items,
            'success' => true,
            'note'    => 'Datos de demo genéricos generados. Para datos más realistas, usa el generador específico del módulo.',
        );
    }

    /**
     * Obtiene estadísticas de un módulo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_module_stats( $request ) {
        $module_id = $request->get_param( 'module_id' );

        // Verificar que el módulo está activo
        $active_modules = get_option( 'flavor_chat_modules', array() );
        $is_active = in_array( $module_id, $active_modules, true );

        // Estadísticas básicas
        $stats = array(
            'module'     => $module_id,
            'active'     => $is_active,
            'configured' => ! empty( get_option( "flavor_module_{$module_id}_config", array() ) ),
            'has_data'   => false,
            'counts'     => array(),
        );

        if ( ! $is_active ) {
            return new WP_REST_Response( $stats, 200 );
        }

        // Intentar obtener estadísticas específicas del módulo
        global $wpdb;

        // Tablas comunes por módulo
        $table_mapping = array(
            'eventos'        => $wpdb->prefix . 'flavor_eventos',
            'grupos-consumo' => $wpdb->prefix . 'flavor_gc_grupos',
            'marketplace'    => $wpdb->prefix . 'flavor_marketplace',
            'socios'         => $wpdb->prefix . 'flavor_socios',
            'cursos'         => $wpdb->prefix . 'flavor_cursos',
            'talleres'       => $wpdb->prefix . 'flavor_talleres',
            'biblioteca'     => $wpdb->prefix . 'flavor_biblioteca',
            'incidencias'    => $wpdb->prefix . 'flavor_incidencias',
        );

        if ( isset( $table_mapping[ $module_id ] ) ) {
            $table = $table_mapping[ $module_id ];
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ) );

            if ( $table_exists ) {
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
                $stats['has_data'] = $count > 0;
                $stats['counts']['total'] = (int) $count;
            }
        }

        return new WP_REST_Response( $stats, 200 );
    }

    /**
     * Obtiene recomendaciones de módulos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_recommendations( $request ) {
        $use_case = $request->get_param( 'use_case' );

        $recommendations = array(
            'cooperativa' => array(
                'name'        => 'Cooperativa / Asociación',
                'description' => 'Módulos recomendados para cooperativas y asociaciones',
                'modules'     => array( 'socios', 'eventos', 'foros', 'encuestas', 'transparencia', 'asambleas' ),
            ),
            'grupo-consumo' => array(
                'name'        => 'Grupo de Consumo',
                'description' => 'Módulos para gestionar un grupo de consumo ecológico',
                'modules'     => array( 'grupos-consumo', 'socios', 'eventos', 'foros', 'banco-tiempo' ),
            ),
            'comunidad-vecinal' => array(
                'name'        => 'Comunidad Vecinal',
                'description' => 'Módulos para comunidades de vecinos y barrios',
                'modules'     => array( 'avisos-municipales', 'incidencias', 'eventos', 'ayuda-vecinal', 'carpooling', 'espacios-comunes' ),
            ),
            'espacio-cultural' => array(
                'name'        => 'Espacio Cultural',
                'description' => 'Módulos para centros culturales y espacios creativos',
                'modules'     => array( 'eventos', 'talleres', 'kulturaka', 'multimedia', 'reservas', 'cursos' ),
            ),
            'coworking' => array(
                'name'        => 'Coworking / Espacio Compartido',
                'description' => 'Módulos para espacios de trabajo compartido',
                'modules'     => array( 'reservas', 'espacios-comunes', 'eventos', 'foros', 'trabajo-digno', 'fichaje-empleados' ),
            ),
            'ecologista' => array(
                'name'        => 'Colectivo Ecologista',
                'description' => 'Módulos para iniciativas medioambientales',
                'modules'     => array( 'huertos-urbanos', 'compostaje', 'reciclaje', 'biodiversidad-local', 'campanias', 'eventos' ),
            ),
            'educativo' => array(
                'name'        => 'Centro Educativo / Formación',
                'description' => 'Módulos para centros de formación y educación',
                'modules'     => array( 'cursos', 'talleres', 'biblioteca', 'eventos', 'foros', 'encuestas' ),
            ),
            'ayuntamiento' => array(
                'name'        => 'Ayuntamiento / Administración',
                'description' => 'Módulos para participación ciudadana',
                'modules'     => array( 'avisos-municipales', 'tramites', 'presupuestos-participativos', 'encuestas', 'transparencia', 'incidencias' ),
            ),
            'marketplace' => array(
                'name'        => 'Marketplace Local',
                'description' => 'Módulos para mercados de compraventa',
                'modules'     => array( 'marketplace', 'socios', 'eventos', 'foros', 'email-marketing' ),
            ),
            'minimo' => array(
                'name'        => 'Configuración Mínima',
                'description' => 'Solo los módulos esenciales para empezar',
                'modules'     => array( 'eventos', 'foros', 'socios' ),
            ),
        );

        if ( ! empty( $use_case ) && isset( $recommendations[ $use_case ] ) ) {
            return new WP_REST_Response( array(
                'recommendation' => $recommendations[ $use_case ],
                'use_case'       => $use_case,
            ), 200 );
        }

        return new WP_REST_Response( array(
            'recommendations' => $recommendations,
            'use_cases'       => array_keys( $recommendations ),
            'usage'           => 'Usa POST /modules/apply-profile con { "profile": "cooperativa" } para activar los módulos recomendados',
        ), 200 );
    }

    /**
     * Aplica un perfil de módulos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_profile( $request ) {
        $profile_id = $request->get_param( 'profile' );

        // Obtener recomendaciones
        $recommendations = array(
            'cooperativa'       => array( 'socios', 'eventos', 'foros', 'encuestas', 'transparencia' ),
            'grupo-consumo'     => array( 'grupos-consumo', 'socios', 'eventos', 'foros', 'banco-tiempo' ),
            'comunidad-vecinal' => array( 'avisos-municipales', 'incidencias', 'eventos', 'ayuda-vecinal', 'carpooling', 'espacios-comunes' ),
            'espacio-cultural'  => array( 'eventos', 'talleres', 'kulturaka', 'multimedia', 'reservas', 'cursos' ),
            'coworking'         => array( 'reservas', 'espacios-comunes', 'eventos', 'foros', 'trabajo-digno' ),
            'ecologista'        => array( 'huertos-urbanos', 'compostaje', 'reciclaje', 'biodiversidad-local', 'campanias', 'eventos' ),
            'educativo'         => array( 'cursos', 'talleres', 'biblioteca', 'eventos', 'foros', 'encuestas' ),
            'ayuntamiento'      => array( 'avisos-municipales', 'tramites', 'presupuestos-participativos', 'encuestas', 'transparencia', 'incidencias' ),
            'marketplace'       => array( 'marketplace', 'socios', 'eventos', 'foros', 'email-marketing' ),
            'minimo'            => array( 'eventos', 'foros', 'socios' ),
        );

        if ( ! isset( $recommendations[ $profile_id ] ) ) {
            return new WP_REST_Response( array(
                'error'  => 'Perfil no encontrado',
                'valid'  => array_keys( $recommendations ),
            ), 404 );
        }

        $modules_to_activate = $recommendations[ $profile_id ];

        // Activar los módulos del perfil
        $active_modules = get_option( 'flavor_chat_modules', array() );
        $activated = array();

        foreach ( $modules_to_activate as $module_id ) {
            if ( ! in_array( $module_id, $active_modules, true ) ) {
                $active_modules[] = $module_id;
                $activated[] = $module_id;

                do_action( 'flavor_module_activated', $module_id );
                do_action( "flavor_module_{$module_id}_activated" );
            }
        }

        update_option( 'flavor_chat_modules', $active_modules );

        return new WP_REST_Response( array(
            'success'      => true,
            'profile'      => $profile_id,
            'activated'    => $activated,
            'total_active' => count( $active_modules ),
            'all_active'   => $active_modules,
        ), 200 );
    }

    /**
     * Lista los perfiles disponibles
     *
     * @return WP_REST_Response
     */
    public function list_profiles() {
        $profiles = array(
            array( 'id' => 'cooperativa', 'name' => 'Cooperativa / Asociación', 'modules_count' => 5 ),
            array( 'id' => 'grupo-consumo', 'name' => 'Grupo de Consumo', 'modules_count' => 5 ),
            array( 'id' => 'comunidad-vecinal', 'name' => 'Comunidad Vecinal', 'modules_count' => 6 ),
            array( 'id' => 'espacio-cultural', 'name' => 'Espacio Cultural', 'modules_count' => 6 ),
            array( 'id' => 'coworking', 'name' => 'Coworking', 'modules_count' => 5 ),
            array( 'id' => 'ecologista', 'name' => 'Colectivo Ecologista', 'modules_count' => 6 ),
            array( 'id' => 'educativo', 'name' => 'Centro Educativo', 'modules_count' => 6 ),
            array( 'id' => 'ayuntamiento', 'name' => 'Ayuntamiento', 'modules_count' => 6 ),
            array( 'id' => 'marketplace', 'name' => 'Marketplace Local', 'modules_count' => 5 ),
            array( 'id' => 'minimo', 'name' => 'Configuración Mínima', 'modules_count' => 3 ),
        );

        return new WP_REST_Response( array(
            'profiles' => $profiles,
            'total'    => count( $profiles ),
            'usage'    => 'POST /modules/apply-profile { "profile": "cooperativa" }',
        ), 200 );
    }
}

// Inicializar
Flavor_Module_Manager_API::get_instance();

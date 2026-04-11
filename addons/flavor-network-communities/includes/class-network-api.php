<?php
/**
 * REST API para la Red de Comunidades
 *
 * Endpoints públicos y autenticados para comunicación entre nodos,
 * directorio, mapa, tablón, contenido compartido y colaboraciones.
 *
 * @package FlavorPlatform\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_API {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-network/v1';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra todas las rutas REST
     */
    public function register_routes() {

        // ─── Directorio público ───
        register_rest_route(self::API_NAMESPACE, '/directory', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_directory'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => $this->get_directory_args(),
        ]);

        // ─── Perfil de nodo ───
        register_rest_route(self::API_NAMESPACE, '/node/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_node_profile'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Mapa de nodos ───
        register_rest_route(self::API_NAMESPACE, '/map', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_map_data'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => $this->get_map_args(),
        ]);

        // ─── Búsqueda por proximidad ───
        register_rest_route(self::API_NAMESPACE, '/nearby', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_nearby_nodes'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => [
                'lat'   => ['required' => true, 'type' => 'number'],
                'lng'   => ['required' => true, 'type' => 'number'],
                'radio' => ['type' => 'number', 'default' => 50],
                'limit' => ['type' => 'integer', 'default' => 20],
            ],
        ]);

        // ─── Tablón de red ───
        register_rest_route(self::API_NAMESPACE, '/board', [
            ['methods' => 'GET',  'callback' => [$this, 'get_board'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'post_board'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/board/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_board'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_board'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Contenido compartido ───
        register_rest_route(self::API_NAMESPACE, '/content', [
            ['methods' => 'GET',  'callback' => [$this, 'get_shared_content'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_shared_content'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/content/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_content_detail'],   'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'PUT',    'callback' => [$this, 'update_shared_content'], 'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_shared_content'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Catálogo de un nodo ───
        register_rest_route(self::API_NAMESPACE, '/catalog/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_node_catalog'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Eventos ───
        register_rest_route(self::API_NAMESPACE, '/events', [
            ['methods' => 'GET',  'callback' => [$this, 'get_events'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_event'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/events/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_event_detail'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'PUT',    'callback' => [$this, 'update_event'],      'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_event'],      'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Colaboraciones ───
        register_rest_route(self::API_NAMESPACE, '/collaborations', [
            ['methods' => 'GET',  'callback' => [$this, 'get_collaborations'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_collaboration'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/collaborations/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_collaboration_detail'], 'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'PUT',    'callback' => [$this, 'update_collaboration'],     'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_collaboration'],     'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/collaborations/(?P<id>\d+)/join', [
            'methods'             => 'POST',
            'callback'            => [$this, 'join_collaboration'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Conexiones entre nodos ───
        register_rest_route(self::API_NAMESPACE, '/connect', [
            'methods'             => 'POST',
            'callback'            => [$this, 'request_connection'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/connections', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_connections'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/connections/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_connection'], 'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_connection'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Mensajería entre nodos ───
        register_rest_route(self::API_NAMESPACE, '/messages', [
            ['methods' => 'GET',  'callback' => [$this, 'get_messages'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'send_message'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/messages/(?P<id>\d+)/read', [
            'methods'             => 'POST',
            'callback'            => [$this, 'mark_message_read'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Alertas solidarias ───
        register_rest_route(self::API_NAMESPACE, '/alerts', [
            ['methods' => 'GET',  'callback' => [$this, 'get_alerts'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_alert'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/alerts/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_alert'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_alert'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Ofertas de tiempo ───
        register_rest_route(self::API_NAMESPACE, '/time-offers', [
            ['methods' => 'GET',  'callback' => [$this, 'get_time_offers'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_time_offer'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/time-offers/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_time_offer'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_time_offer'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);


        // ─── QR de nodo ───
        register_rest_route(self::API_NAMESPACE, '/node/(?P<slug>[a-zA-Z0-9_-]+)/qr', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_node_qr'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Sello de calidad ───
        register_rest_route(self::API_NAMESPACE, '/verify-seal/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'verify_seal'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);


        // ─── Sellos de calidad admin ───
        register_rest_route(self::API_NAMESPACE, '/seals', [
            ['methods' => 'GET',  'callback' => [$this, 'get_seals'],     'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'create_seal'],   'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/seals/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_seal'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_seal'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Favoritos ───
        register_rest_route(self::API_NAMESPACE, '/favorites', [
            ['methods' => 'GET',  'callback' => [$this, 'get_favorites'],    'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'toggle_favorite'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Recomendaciones ───
        register_rest_route(self::API_NAMESPACE, '/recommendations', [
            ['methods' => 'GET',  'callback' => [$this, 'get_recommendations'],    'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'create_recommendation'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/recommendations/(?P<id>\d+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_recommendation'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Nodos admin (CRUD remoto) ───
        register_rest_route(self::API_NAMESPACE, '/nodes', [
            ['methods' => 'POST', 'callback' => [$this, 'create_node'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/nodes/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update_node'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_node'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Lista de nodos para selects ───
        register_rest_route(self::API_NAMESPACE, '/nodes-list', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_nodes_list'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Mensajes: responder ───
        register_rest_route(self::API_NAMESPACE, '/messages/(?P<id>\d+)/reply', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reply_message'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Mensajes: eliminar ───
        register_rest_route(self::API_NAMESPACE, '/messages/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'delete_message'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Newsletter ───
        register_rest_route(self::API_NAMESPACE, '/newsletters', [
            ['methods' => 'GET',  'callback' => [$this, 'get_newsletters'],    'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'create_newsletter'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/newsletters/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_newsletter_detail'], 'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'PUT',    'callback' => [$this, 'update_newsletter'],     'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_newsletter'],     'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/newsletters/(?P<id>\d+)/send', [
            'methods'             => 'POST',
            'callback'            => [$this, 'send_newsletter'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/newsletter-subscribers', [
            ['methods' => 'GET',  'callback' => [$this, 'get_subscribers'],    'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'add_subscriber'],     'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/newsletter-subscribers/(?P<id>\d+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'remove_subscriber'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/newsletter-auto-content', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_auto_newsletter_content'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Preguntas a la Red ───
        register_rest_route(self::API_NAMESPACE, '/questions', [
            ['methods' => 'GET',  'callback' => [$this, 'get_questions'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_question'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/questions/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_question_detail'], 'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'PUT',    'callback' => [$this, 'update_question'],     'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_question'],     'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/questions/(?P<id>\d+)/answers', [
            ['methods' => 'GET',  'callback' => [$this, 'get_answers'],  'permission_callback' => [$this, 'public_permission_check']],
            ['methods' => 'POST', 'callback' => [$this, 'create_answer'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/answers/(?P<id>\d+)/vote', [
            'methods'             => 'POST',
            'callback'            => [$this, 'vote_answer'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/answers/(?P<id>\d+)/solution', [
            'methods'             => 'POST',
            'callback'            => [$this, 'mark_solution'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Matching necesidades/excedentes ───
        register_rest_route(self::API_NAMESPACE, '/matches', [
            ['methods' => 'GET',  'callback' => [$this, 'get_matches'],       'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'generate_matches'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/matches/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'respond_match'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'dismiss_match'],  'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/matches/(?P<id>\d+)/contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'contact_match'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ─── Nodo local (admin) ───
        register_rest_route(self::API_NAMESPACE, '/local-node', [
            ['methods' => 'GET',  'callback' => [$this, 'get_local_node'],  'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'save_local_node'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        // ─── Estadísticas de red ───
        register_rest_route(self::API_NAMESPACE, '/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_network_stats'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Opciones/constantes disponibles ───
        register_rest_route(self::API_NAMESPACE, '/options', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_options'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Webhooks ───
        register_rest_route(self::API_NAMESPACE, '/webhooks', [
            ['methods' => 'GET',  'callback' => [$this, 'list_webhooks'],    'permission_callback' => [$this, 'check_admin_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'register_webhook'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/webhooks/(?P<id>[a-f0-9]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_webhook'], 'permission_callback' => [$this, 'check_admin_permission']],
        ]);

        register_rest_route(self::API_NAMESPACE, '/webhooks/(?P<id>[a-f0-9]+)/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_webhook'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/webhooks/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_webhook_stats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/webhooks/logs', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_webhook_logs'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/webhooks/event-types', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_webhook_event_types'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ─── Recibir webhooks de otros nodos ───
        register_rest_route(self::API_NAMESPACE, '/webhook/receive', [
            'methods'             => 'POST',
            'callback'            => [$this, 'receive_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    // ─── Callbacks de permisos ───

    /**
     * Permiso de admin con rate limiting para escritura
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    public function check_admin_permission($request = null) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Rate limiting para operaciones de escritura
        if ($request && in_array($request->get_method(), ['POST', 'PUT', 'DELETE'])) {
            if (class_exists('Flavor_Network_Rate_Limiter')) {
                $rate_limiter = Flavor_Network_Rate_Limiter::get_instance();
                $result = $rate_limiter->enforce_rate_limit('write');

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Permiso público con rate limiting
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    public function public_permission_check($request = null) {
        // Obtener tipo de endpoint desde la ruta
        $endpoint_type = $this->get_endpoint_type_from_request($request);

        // Verificar rate limit si el rate limiter está disponible
        if (class_exists('Flavor_Network_Rate_Limiter')) {
            $rate_limiter = Flavor_Network_Rate_Limiter::get_instance();
            $result = $rate_limiter->enforce_rate_limit($endpoint_type);

            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Determina el tipo de endpoint desde el request para rate limiting
     */
    private function get_endpoint_type_from_request($request) {
        if (!$request) {
            return 'default';
        }

        $route = $request->get_route();

        // Mapear rutas a tipos de rate limit
        $route_map = [
            '/directory'      => 'directory',
            '/map'            => 'map',
            '/nearby'         => 'nearby',
            '/board'          => 'board',
            '/content'        => 'content',
            '/events'         => 'events',
            '/collaborations' => 'content',
            '/alerts'         => 'content',
            '/questions'      => 'content',
            '/stats'          => 'default',
            '/options'        => 'default',
        ];

        foreach ($route_map as $pattern => $type) {
            if (strpos($route, $pattern) !== false) {
                return $type;
            }
        }

        return 'default';
    }

    // ─── CONSTANTES Y OPCIONES ───

    /**
     * Obtiene todas las opciones/constantes disponibles para formularios
     */
    public function get_options($request) {
        return new WP_REST_Response([
            'tipos_entidad'      => Flavor_Network_Node::TIPOS_ENTIDAD,
            'niveles_consciencia' => Flavor_Network_Node::NIVELES_CONSCIENCIA,
            'niveles_conexion'   => Flavor_Network_Node::NIVELES_CONEXION,
            'estados'            => Flavor_Network_Node::ESTADOS,
            'tipos_contenido'    => Flavor_Network_Node::TIPOS_CONTENIDO,
            'tipos_colaboracion' => Flavor_Network_Node::TIPOS_COLABORACION,
            'tipos_tablon'       => Flavor_Network_Node::TIPOS_TABLON,
            'niveles_urgencia'   => Flavor_Network_Node::NIVELES_URGENCIA,
            'ambitos'            => Flavor_Network_Node::AMBITOS,
            'estados_conexion'   => Flavor_Network_Node::ESTADOS_CONEXION,
            'modalidades'        => Flavor_Network_Node::MODALIDADES,
        ], 200);
    }

    // ─── DIRECTORIO ───

    public function get_directory($request) {
        $filtros = [
            'tipo_entidad'      => $request->get_param('tipo'),
            'sector'            => $request->get_param('sector'),
            'nivel_consciencia' => $request->get_param('nivel'),
            'pais'              => $request->get_param('pais'),
            'ciudad'            => $request->get_param('ciudad'),
            'busqueda'          => $request->get_param('busqueda'),
            'verificado'        => $request->get_param('verificado'),
        ];

        $filtros = array_filter($filtros, function($valor) {
            return $valor !== null && $valor !== '';
        });

        $pagina = max(1, (int) $request->get_param('pagina') ?: 1);
        $por_pagina = min(100, max(1, (int) $request->get_param('por_pagina') ?: 20));
        $offset = ($pagina - 1) * $por_pagina;

        $nodos = Flavor_Network_Node::query($filtros, 'nombre', 'ASC', $por_pagina, $offset);
        $total = Flavor_Network_Node::count($filtros);

        $resultado = array_map(function($nodo) {
            return $nodo->to_card_array();
        }, $nodos);

        return new WP_REST_Response([
            'nodos'      => $resultado,
            'total'      => $total,
            'pagina'     => $pagina,
            'por_pagina' => $por_pagina,
            'paginas'    => ceil($total / $por_pagina),
        ], 200);
    }

    // ─── PERFIL DE NODO ───

    public function get_node_profile($request) {
        $nodo = Flavor_Network_Node::find_by_slug($request['slug']);

        if (!$nodo) {
            return new WP_Error('nodo_no_encontrado', __('Nodo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        return new WP_REST_Response($nodo->to_public_array(), 200);
    }

    // ─── MAPA ───

    public function get_map_data($request) {
        $filtros = [
            'tipo_entidad'      => $request->get_param('tipo'),
            'nivel_consciencia' => $request->get_param('nivel'),
            'sector'            => $request->get_param('sector'),
        ];
        $filtros = array_filter($filtros, function($v) { return $v !== null && $v !== ''; });

        $datos_mapa = Flavor_Network_Node::get_map_data($filtros);

        return new WP_REST_Response([
            'nodos' => $datos_mapa,
            'total' => count($datos_mapa),
        ], 200);
    }

    // ─── PROXIMIDAD ───

    public function get_nearby_nodes($request) {
        $latitud = (float) $request->get_param('lat');
        $longitud = (float) $request->get_param('lng');
        $radio_km = (float) ($request->get_param('radio') ?: 50);
        $limite = (int) ($request->get_param('limit') ?: 20);

        $nodos_cercanos = Flavor_Network_Node::find_nearby($latitud, $longitud, $radio_km, $limite);

        $resultado = array_map(function($nodo) {
            $datos = $nodo->to_card_array();
            $datos['distancia_km'] = $nodo->distancia_km;
            return $datos;
        }, $nodos_cercanos);

        return new WP_REST_Response([
            'nodos'   => $resultado,
            'centro'  => ['lat' => $latitud, 'lng' => $longitud],
            'radio_km' => $radio_km,
        ], 200);
    }

    // ─── TABLÓN ───

    public function get_board($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('board');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $tipo = sanitize_text_field($request->get_param('tipo') ?? '');
        $pagina = max(1, (int) ($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, (int) ($request->get_param('por_pagina') ?: 15)));
        $offset = ($pagina - 1) * $por_pagina;

        $where = "b.activo = 1 AND (b.fecha_fin IS NULL OR b.fecha_fin > NOW())";
        $params = [];

        if ($tipo) {
            $where .= " AND b.tipo = %s";
            $params[] = $tipo;
        }

        $params[] = $por_pagina;
        $params[] = $offset;

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} b
             LEFT JOIN {$tabla_nodos} n ON b.nodo_id = n.id
             WHERE {$where}
             ORDER BY b.prioridad DESC, b.fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $params
        ));

        return new WP_REST_Response([
            'publicaciones' => $publicaciones ?: [],
            'pagina'        => $pagina,
        ], 200);
    }

    public function post_board($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('board');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'   => $nodo_local->id,
            'tipo'      => sanitize_text_field($request->get_param('tipo') ?: 'anuncio'),
            'titulo'    => sanitize_text_field($request->get_param('titulo')),
            'contenido' => wp_kses_post($request->get_param('contenido')),
            'imagen_url' => esc_url_raw($request->get_param('imagen_url') ?: ''),
            'ambito'    => sanitize_text_field($request->get_param('ambito') ?: 'red'),
            'prioridad' => sanitize_text_field($request->get_param('prioridad') ?: 'normal'),
        ];

        if (empty($datos['titulo']) || empty($datos['contenido'])) {
            return new WP_Error('datos_incompletos', __('Título y contenido son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Publicación creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── CONTENIDO COMPARTIDO ───

    public function get_shared_content($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $tipo_contenido = sanitize_text_field($request->get_param('tipo') ?? '');
        $busqueda = sanitize_text_field($request->get_param('busqueda') ?? '');
        $pagina = max(1, (int) ($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, (int) ($request->get_param('por_pagina') ?: 20)));
        $offset = ($pagina - 1) * $por_pagina;

        $where = "c.estado = 'activo' AND c.visible_red = 1";
        $params = [];

        if ($tipo_contenido) {
            $where .= " AND c.tipo_contenido = %s";
            $params[] = $tipo_contenido;
        }

        if ($busqueda) {
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $where .= " AND (c.titulo LIKE %s OR c.descripcion LIKE %s)";
            $params[] = $like;
            $params[] = $like;
        }

        $params[] = $por_pagina;
        $params[] = $offset;

        $contenidos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} c
             LEFT JOIN {$tabla_nodos} n ON c.nodo_id = n.id
             WHERE {$where}
             ORDER BY c.destacado DESC, c.fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $params
        ));

        return new WP_REST_Response([
            'contenidos' => $contenidos ?: [],
            'pagina'     => $pagina,
        ], 200);
    }

    public function create_shared_content($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'           => $nodo_local->id,
            'tipo_contenido'    => sanitize_text_field($request->get_param('tipo_contenido')),
            'titulo'            => sanitize_text_field($request->get_param('titulo')),
            'descripcion'       => wp_kses_post($request->get_param('descripcion') ?: ''),
            'imagen_url'        => esc_url_raw($request->get_param('imagen_url') ?: ''),
            'precio'            => floatval($request->get_param('precio') ?: 0),
            'moneda'            => sanitize_text_field($request->get_param('moneda') ?: 'EUR'),
            'unidad'            => sanitize_text_field($request->get_param('unidad') ?: ''),
            'disponibilidad'    => sanitize_text_field($request->get_param('disponibilidad') ?: 'disponible'),
            'ubicacion'         => sanitize_text_field($request->get_param('ubicacion') ?: ''),
            'contacto_nombre'   => sanitize_text_field($request->get_param('contacto_nombre') ?: ''),
            'contacto_email'    => sanitize_email($request->get_param('contacto_email') ?: ''),
            'contacto_telefono' => sanitize_text_field($request->get_param('contacto_telefono') ?: ''),
        ];

        if (!array_key_exists($datos['tipo_contenido'], Flavor_Network_Node::TIPOS_CONTENIDO)) {
            return new WP_Error('tipo_invalido', __('Tipo de contenido no válido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        if (empty($datos['titulo'])) {
            return new WP_Error('titulo_requerido', __('El título es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Contenido publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function get_content_detail($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $contenido = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} c
             LEFT JOIN {$tabla_nodos} n ON c.nodo_id = n.id
             WHERE c.id = %d AND c.estado = 'activo'",
            $request['id']
        ));

        if (!$contenido) {
            return new WP_Error('no_encontrado', __('Contenido no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        // Incrementar vistas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla} SET vistas = vistas + 1 WHERE id = %d",
            $request['id']
        ));

        return new WP_REST_Response($contenido, 200);
    }

    public function update_shared_content($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'descripcion', 'imagen_url', 'precio', 'disponibilidad', 'ubicacion', 'estado'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos_actualizar[$campo] = sanitize_text_field($valor);
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_shared_content($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');

        $wpdb->update($tabla, ['estado' => 'eliminado'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── CATÁLOGO DE NODO ───

    public function get_node_catalog($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $nodo = Flavor_Network_Node::find_by_slug($request['slug']);
        if (!$nodo) {
            return new WP_Error('nodo_no_encontrado', __('Nodo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $contenidos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE nodo_id = %d AND estado = 'activo' AND visible_red = 1
             ORDER BY destacado DESC, fecha_publicacion DESC",
            $nodo->id
        ));

        return new WP_REST_Response([
            'nodo'       => $nodo->to_card_array(),
            'contenidos' => $contenidos ?: [],
        ], 200);
    }

    // ─── EVENTOS ───

    public function get_events($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('events');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $pagina = max(1, (int) ($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, (int) ($request->get_param('por_pagina') ?: 20)));
        $offset = ($pagina - 1) * $por_pagina;

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} e
             LEFT JOIN {$tabla_nodos} n ON e.nodo_id = n.id
             WHERE e.estado = 'activo' AND e.visible_red = 1 AND e.fecha_inicio >= NOW()
             ORDER BY e.fecha_inicio ASC
             LIMIT %d OFFSET %d",
            $por_pagina, $offset
        ));

        return new WP_REST_Response([
            'eventos' => $eventos ?: [],
            'pagina'  => $pagina,
        ], 200);
    }

    public function create_event($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('events');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'      => $nodo_local->id,
            'titulo'       => sanitize_text_field($request->get_param('titulo')),
            'descripcion'  => wp_kses_post($request->get_param('descripcion') ?: ''),
            'imagen_url'   => esc_url_raw($request->get_param('imagen_url') ?: ''),
            'tipo_evento'  => sanitize_text_field($request->get_param('tipo_evento') ?: 'presencial'),
            'ubicacion'    => sanitize_text_field($request->get_param('ubicacion') ?: ''),
            'url_online'   => esc_url_raw($request->get_param('url_online') ?: ''),
            'fecha_inicio' => sanitize_text_field($request->get_param('fecha_inicio')),
            'fecha_fin'    => sanitize_text_field($request->get_param('fecha_fin') ?: ''),
            'plazas'       => intval($request->get_param('plazas') ?: 0) ?: null,
            'precio'       => floatval($request->get_param('precio') ?: 0),
        ];

        if (empty($datos['titulo']) || empty($datos['fecha_inicio'])) {
            return new WP_Error('datos_incompletos', __('Título y fecha son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Evento creado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── COLABORACIONES ───

    public function get_collaborations($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('collaborations');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $tipo = sanitize_text_field($request->get_param('tipo') ?? '');

        $where = "col.estado != 'cerrada'";
        $params = [];

        if ($tipo) {
            $where .= " AND col.tipo = %s";
            $params[] = $tipo;
        }

        $params[] = 50;
        $params[] = 0;

        $colaboraciones = $wpdb->get_results($wpdb->prepare(
            "SELECT col.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} col
             LEFT JOIN {$tabla_nodos} n ON col.nodo_creador_id = n.id
             WHERE {$where}
             ORDER BY col.fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $params
        ));

        return new WP_REST_Response(['colaboraciones' => $colaboraciones ?: []], 200);
    }

    public function create_collaboration($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('collaborations');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $tipos_validos = ['compra_colectiva', 'logistica', 'proyecto', 'alianza', 'hermanamiento', 'mentoria'];

        $datos = [
            'nodo_creador_id'   => $nodo_local->id,
            'tipo'              => sanitize_text_field($request->get_param('tipo')),
            'titulo'            => sanitize_text_field($request->get_param('titulo')),
            'descripcion'       => wp_kses_post($request->get_param('descripcion') ?: ''),
            'objetivo'          => wp_kses_post($request->get_param('objetivo') ?: ''),
            'requisitos'        => wp_kses_post($request->get_param('requisitos') ?: ''),
            'beneficios'        => wp_kses_post($request->get_param('beneficios') ?: ''),
            'max_participantes' => intval($request->get_param('max_participantes') ?: 0) ?: null,
            'fecha_limite'      => sanitize_text_field($request->get_param('fecha_limite') ?: ''),
            'ambito'            => sanitize_text_field($request->get_param('ambito') ?: 'red'),
        ];

        if (!in_array($datos['tipo'], $tipos_validos)) {
            return new WP_Error('tipo_invalido', __('Tipo de colaboración no válido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        if (empty($datos['titulo'])) {
            return new WP_Error('titulo_requerido', __('El título es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Colaboración creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function join_collaboration($request) {
        global $wpdb;
        $tabla_participantes = Flavor_Network_Installer::get_table_name('collaboration_participants');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $colaboracion_id = (int) $request['id'];

        // Verificar si ya participa
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_participantes} WHERE colaboracion_id = %d AND nodo_id = %d",
            $colaboracion_id, $nodo_local->id
        ));

        if ($existe) {
            return new WP_Error('ya_participa', __('Ya participas en esta colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla_participantes, [
            'colaboracion_id' => $colaboracion_id,
            'nodo_id'         => $nodo_local->id,
            'aportacion'      => sanitize_text_field($request->get_param('aportacion') ?: ''),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud de participación enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── CONEXIONES ───

    public function request_connection($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $nodo_destino_id = (int) $request->get_param('nodo_destino_id');

        if ($nodo_destino_id === (int) $nodo_local->id) {
            return new WP_Error('auto_conexion', __('No puedes conectarte contigo mismo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        // Verificar que no exista conexión
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla}
             WHERE (nodo_origen_id = %d AND nodo_destino_id = %d)
                OR (nodo_origen_id = %d AND nodo_destino_id = %d)",
            $nodo_local->id, $nodo_destino_id,
            $nodo_destino_id, $nodo_local->id
        ));

        if ($existe) {
            return new WP_Error('conexion_existente', __('Ya existe una conexión con este nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, [
            'nodo_origen_id'     => $nodo_local->id,
            'nodo_destino_id'    => $nodo_destino_id,
            'mensaje_solicitud'  => sanitize_text_field($request->get_param('mensaje') ?: ''),
            'solicitado_por'     => get_current_user_id(),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud de conexión enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function get_connections($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_REST_Response(['conexiones' => []], 200);
        }

        $conexiones = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*,
                    n1.nombre AS origen_nombre, n1.slug AS origen_slug, n1.logo_url AS origen_logo,
                    n2.nombre AS destino_nombre, n2.slug AS destino_slug, n2.logo_url AS destino_logo
             FROM {$tabla} c
             LEFT JOIN {$tabla_nodos} n1 ON c.nodo_origen_id = n1.id
             LEFT JOIN {$tabla_nodos} n2 ON c.nodo_destino_id = n2.id
             WHERE c.nodo_origen_id = %d OR c.nodo_destino_id = %d
             ORDER BY c.fecha_solicitud DESC",
            $nodo_local->id, $nodo_local->id
        ));

        return new WP_REST_Response(['conexiones' => $conexiones ?: []], 200);
    }

    public function update_connection($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');

        $estado = sanitize_text_field($request->get_param('estado'));
        $nivel = sanitize_text_field($request->get_param('nivel'));

        $datos_actualizar = [];
        if (in_array($estado, ['aprobada', 'rechazada', 'pendiente'])) {
            $datos_actualizar['estado'] = $estado;
            if ($estado === 'aprobada') {
                $datos_actualizar['fecha_aprobacion'] = current_time('mysql');
                $datos_actualizar['aprobado_por'] = get_current_user_id();
            }
        }
        if (in_array($nivel, ['visible', 'conectado', 'federado'])) {
            $datos_actualizar['nivel'] = $nivel;
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_cambios', __('No hay cambios válidos', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Conexión actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_connection($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');

        $wpdb->delete($tabla, ['id' => $request['id']], ['%d']);

        return new WP_REST_Response(['success' => true, 'message' => __('Conexión eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── MENSAJERÍA ───

    public function get_messages($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('messages');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_REST_Response(['mensajes' => []], 200);
        }

        $tipo = $request->get_param('tipo') ?: 'recibidos';

        if ($tipo === 'enviados') {
            $where = "m.de_nodo_id = %d";
        } else {
            $where = "m.a_nodo_id = %d";
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*,
                    n_de.nombre AS remitente_nombre, n_de.slug AS remitente_slug, n_de.logo_url AS remitente_logo,
                    n_a.nombre AS destinatario_nombre, n_a.slug AS destinatario_slug
             FROM {$tabla} m
             LEFT JOIN {$tabla_nodos} n_de ON m.de_nodo_id = n_de.id
             LEFT JOIN {$tabla_nodos} n_a ON m.a_nodo_id = n_a.id
             WHERE {$where}
             ORDER BY m.fecha_envio DESC
             LIMIT 50",
            $nodo_local->id
        ));

        return new WP_REST_Response(['mensajes' => $mensajes ?: []], 200);
    }

    public function send_message($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('messages');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'de_nodo_id' => $nodo_local->id,
            'a_nodo_id'  => (int) $request->get_param('a_nodo_id'),
            'tipo'       => sanitize_text_field($request->get_param('tipo') ?: 'mensaje'),
            'asunto'     => sanitize_text_field($request->get_param('asunto') ?: ''),
            'contenido'  => wp_kses_post($request->get_param('contenido')),
        ];

        if (empty($datos['contenido']) || empty($datos['a_nodo_id'])) {
            return new WP_Error('datos_incompletos', __('Destinatario y contenido son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Mensaje enviado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function mark_message_read($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('messages');

        $wpdb->update($tabla, [
            'leido'       => 1,
            'fecha_lectura' => current_time('mysql'),
        ], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true], 200);
    }

    // ─── ALERTAS SOLIDARIAS ───

    public function get_alerts($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('solidarity_alerts');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $alertas = $wpdb->get_results(
            "SELECT a.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} a
             LEFT JOIN {$tabla_nodos} n ON a.nodo_id = n.id
             WHERE a.estado = 'activa'
             ORDER BY FIELD(a.urgencia, 'critica', 'alta', 'media', 'baja'), a.fecha_publicacion DESC
             LIMIT 50"
        );

        return new WP_REST_Response(['alertas' => $alertas ?: []], 200);
    }

    public function create_alert($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('solidarity_alerts');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'     => $nodo_local->id,
            'tipo'        => sanitize_text_field($request->get_param('tipo') ?: 'necesidad'),
            'titulo'      => sanitize_text_field($request->get_param('titulo')),
            'descripcion' => wp_kses_post($request->get_param('descripcion')),
            'urgencia'    => sanitize_text_field($request->get_param('urgencia') ?: 'media'),
            'ubicacion'   => sanitize_text_field($request->get_param('ubicacion') ?: ''),
        ];

        if (empty($datos['titulo']) || empty($datos['descripcion'])) {
            return new WP_Error('datos_incompletos', __('Título y descripción son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Alerta solidaria publicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── OFERTAS DE TIEMPO ───

    public function get_time_offers($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('time_offers');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $ofertas = $wpdb->get_results(
            "SELECT t.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla} t
             LEFT JOIN {$tabla_nodos} n ON t.nodo_id = n.id
             WHERE t.estado = 'activa'
             ORDER BY t.fecha_publicacion DESC
             LIMIT 50"
        );

        return new WP_REST_Response(['ofertas' => $ofertas ?: []], 200);
    }

    public function create_time_offer($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('time_offers');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'          => $nodo_local->id,
            'tipo'             => sanitize_text_field($request->get_param('tipo') ?: 'oferta'),
            'titulo'           => sanitize_text_field($request->get_param('titulo')),
            'descripcion'      => wp_kses_post($request->get_param('descripcion') ?: ''),
            'categoria'        => sanitize_text_field($request->get_param('categoria') ?: ''),
            'horas_estimadas'  => floatval($request->get_param('horas_estimadas') ?: 0),
            'modalidad'        => sanitize_text_field($request->get_param('modalidad') ?: 'presencial'),
        ];

        if (empty($datos['titulo'])) {
            return new WP_Error('titulo_requerido', __('El título es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Oferta de tiempo publicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── EVENTOS: Detalle, Editar, Eliminar ───

    public function get_event_detail($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('events');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, n.nombre AS nodo_nombre, n.slug AS nodo_slug
             FROM {$tabla} e
             LEFT JOIN {$tabla_nodos} n ON e.nodo_id = n.id
             WHERE e.id = %d",
            $request['id']
        ));

        if (!$evento) {
            return new WP_Error('no_encontrado', __('Evento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        return new WP_REST_Response($evento, 200);
    }

    public function update_event($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('events');

        $campos_permitidos = ['titulo', 'descripcion', 'imagen_url', 'tipo_evento', 'ubicacion', 'url_online', 'fecha_inicio', 'fecha_fin', 'plazas', 'precio', 'estado'];
        $datos_actualizar = [];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if (in_array($campo, ['plazas'])) {
                    $datos_actualizar[$campo] = intval($valor);
                } elseif (in_array($campo, ['precio'])) {
                    $datos_actualizar[$campo] = floatval($valor);
                } elseif (in_array($campo, ['imagen_url', 'url_online'])) {
                    $datos_actualizar[$campo] = esc_url_raw($valor);
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Evento actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_event($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('events');

        $wpdb->update($tabla, ['estado' => 'eliminado'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Evento eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── TABLÓN: Editar, Eliminar ───

    public function update_board($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('board');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'contenido', 'tipo', 'prioridad', 'activo'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if ($campo === 'contenido') {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } elseif ($campo === 'activo') {
                    $datos_actualizar[$campo] = intval($valor);
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Publicación actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_board($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('board');

        $wpdb->update($tabla, ['activo' => 0], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Publicación eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── COLABORACIONES: Detalle, Editar, Eliminar ───

    public function get_collaboration_detail($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('collaborations');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $tabla_participantes = Flavor_Network_Installer::get_table_name('collaboration_participants');

        $colaboracion = $wpdb->get_row($wpdb->prepare(
            "SELECT col.*, n.nombre AS nodo_nombre, n.slug AS nodo_slug
             FROM {$tabla} col
             LEFT JOIN {$tabla_nodos} n ON col.nodo_creador_id = n.id
             WHERE col.id = %d",
            $request['id']
        ));

        if (!$colaboracion) {
            return new WP_Error('no_encontrado', __('Colaboración no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $participantes = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, n.nombre AS nodo_nombre, n.slug AS nodo_slug
             FROM {$tabla_participantes} p
             LEFT JOIN {$tabla_nodos} n ON p.nodo_id = n.id
             WHERE p.colaboracion_id = %d",
            $request['id']
        ));

        $colaboracion->participantes = $participantes ?: [];

        return new WP_REST_Response($colaboracion, 200);
    }

    public function update_collaboration($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('collaborations');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'descripcion', 'objetivo', 'requisitos', 'beneficios', 'estado', 'max_participantes', 'fecha_limite', 'ambito'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if (in_array($campo, ['descripcion', 'objetivo', 'requisitos', 'beneficios'])) {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } elseif ($campo === 'max_participantes') {
                    $datos_actualizar[$campo] = intval($valor) ?: null;
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Colaboración actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_collaboration($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('collaborations');

        $wpdb->update($tabla, ['estado' => 'cerrada'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Colaboración cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── ALERTAS: Editar, Eliminar ───

    public function update_alert($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('solidarity_alerts');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'descripcion', 'urgencia', 'ubicacion', 'estado'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if ($campo === 'descripcion') {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if ($request->get_param('estado') === 'resuelta') {
            $datos_actualizar['fecha_resolucion'] = current_time('mysql');
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Alerta actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_alert($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('solidarity_alerts');

        $wpdb->update($tabla, ['estado' => 'eliminada'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Alerta eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── OFERTAS DE TIEMPO: Editar, Eliminar ───

    public function update_time_offer($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('time_offers');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'descripcion', 'categoria', 'horas_estimadas', 'modalidad', 'estado'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if ($campo === 'horas_estimadas') {
                    $datos_actualizar[$campo] = floatval($valor);
                } elseif ($campo === 'descripcion') {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Oferta actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_time_offer($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('time_offers');

        $wpdb->update($tabla, ['estado' => 'eliminada'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Oferta eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── RECOMENDACIONES ───

    public function get_recommendations($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('recommendations');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_REST_Response(['recomendaciones' => []], 200);
        }

        $recomendaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*,
                    n_de.nombre AS de_nombre, n_de.slug AS de_slug,
                    n_a.nombre AS a_nombre, n_a.slug AS a_slug,
                    n_rec.nombre AS recomendado_nombre, n_rec.slug AS recomendado_slug,
                    n_rec.tipo_entidad AS recomendado_tipo, n_rec.ciudad AS recomendado_ciudad
             FROM {$tabla} r
             LEFT JOIN {$tabla_nodos} n_de ON r.de_nodo_id = n_de.id
             LEFT JOIN {$tabla_nodos} n_a ON r.a_nodo_id = n_a.id
             LEFT JOIN {$tabla_nodos} n_rec ON r.nodo_recomendado_id = n_rec.id
             WHERE r.de_nodo_id = %d OR r.a_nodo_id = %d
             ORDER BY r.fecha DESC",
            $nodo_local->id, $nodo_local->id
        ));

        return new WP_REST_Response(['recomendaciones' => $recomendaciones ?: []], 200);
    }

    public function create_recommendation($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('recommendations');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'de_nodo_id'          => $nodo_local->id,
            'a_nodo_id'           => (int) $request->get_param('a_nodo_id'),
            'nodo_recomendado_id' => (int) $request->get_param('nodo_recomendado_id'),
            'motivo'              => wp_kses_post($request->get_param('motivo') ?: ''),
        ];

        if (empty($datos['a_nodo_id']) || empty($datos['nodo_recomendado_id'])) {
            return new WP_Error('datos_incompletos', __('Destinatario y nodo recomendado son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Recomendación enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function delete_recommendation($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('recommendations');

        $wpdb->delete($tabla, ['id' => $request['id']], ['%d']);

        return new WP_REST_Response(['success' => true, 'message' => __('Recomendación eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── NODOS ADMIN ───

    public function create_node($request) {
        $datos = $request->get_json_params();

        if (empty($datos['nombre']) || empty($datos['slug'])) {
            return new WP_Error('datos_incompletos', __('Nombre y slug son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $nodo = Flavor_Network_Node::create($datos);

        if (!$nodo) {
            return new WP_Error('error_creacion', __('Error al crear el nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'nodo'    => $nodo->to_array(),
            'message' => __('Nodo creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function update_node($request) {
        $datos = $request->get_json_params();
        $nodo = Flavor_Network_Node::update_node($request['id'], $datos);

        if (!$nodo) {
            return new WP_Error('error_actualizacion', __('Error al actualizar el nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'nodo'    => $nodo->to_array(),
            'message' => __('Nodo actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    public function delete_node($request) {
        $resultado = Flavor_Network_Node::delete_node($request['id']);

        if (!$resultado) {
            return new WP_Error('error_eliminacion', __('No se puede eliminar este nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        return new WP_REST_Response(['success' => true, 'message' => __('Nodo eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function get_nodes_list($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $nodos = $wpdb->get_results(
            "SELECT id, nombre, slug, tipo_entidad, ciudad FROM {$tabla} WHERE estado = 'activo' ORDER BY nombre ASC"
        );

        return new WP_REST_Response(['nodos' => $nodos ?: []], 200);
    }

    // ─── MENSAJES: Responder, Eliminar ───

    public function reply_message($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('messages');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $mensaje_original = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $request['id']
        ));

        if (!$mensaje_original) {
            return new WP_Error('no_encontrado', __('Mensaje no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $datos = [
            'de_nodo_id'       => $nodo_local->id,
            'a_nodo_id'        => $mensaje_original->de_nodo_id,
            'tipo'             => 'respuesta',
            'asunto'           => 'Re: ' . $mensaje_original->asunto,
            'contenido'        => wp_kses_post($request->get_param('contenido')),
            'mensaje_padre_id' => $request['id'],
        ];

        if (empty($datos['contenido'])) {
            return new WP_Error('contenido_requerido', __('El contenido es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);

        // Marcar original como respondido
        $wpdb->update($tabla, ['respondido' => 1], ['id' => $request['id']]);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Respuesta enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function delete_message($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('messages');

        $wpdb->delete($tabla, ['id' => $request['id']], ['%d']);

        return new WP_REST_Response(['success' => true, 'message' => __('Mensaje eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── QR DE ENTIDAD ───

    public function get_node_qr($request) {
        $nodo = Flavor_Network_Node::find_by_slug($request['slug']);
        if (!$nodo) {
            return new WP_Error('no_encontrado', __('Nodo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $profile_url = $nodo->web ?: get_site_url() . '?nodo=' . $nodo->slug;
        $qr_size = $request->get_param('size') ?: 300;
        $qr_url = 'https://chart.googleapis.com/chart?chs=' . intval($qr_size) . 'x' . intval($qr_size) . '&cht=qr&chl=' . urlencode($profile_url) . '&choe=UTF-8';

        $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
        $vcard .= "FN:" . $nodo->nombre . "\n";
        $vcard .= "ORG:" . $nodo->nombre . "\n";
        if ($nodo->email) $vcard .= "EMAIL:" . $nodo->email . "\n";
        if ($nodo->telefono) $vcard .= "TEL:" . $nodo->telefono . "\n";
        if ($nodo->web) $vcard .= "URL:" . $nodo->web . "\n";
        if ($nodo->direccion) $vcard .= "ADR:;;" . $nodo->direccion . ";" . ($nodo->ciudad ?: '') . ";" . ($nodo->provincia ?: '') . ";;" . ($nodo->pais ?: '') . "\n";
        $vcard .= "END:VCARD";

        $qr_vcard_url = 'https://chart.googleapis.com/chart?chs=' . intval($qr_size) . 'x' . intval($qr_size) . '&cht=qr&chl=' . urlencode($vcard) . '&choe=UTF-8';

        return new WP_REST_Response([
            'nodo'         => $nodo->nombre,
            'slug'         => $nodo->slug,
            'profile_url'  => $profile_url,
            'qr_url'       => $qr_url,
            'qr_vcard_url' => $qr_vcard_url,
            'size'         => intval($qr_size),
        ], 200);
    }

    // ─── SELLO DE CALIDAD ───

    public function verify_seal($request) {
        global $wpdb;
        $tabla_sellos = Flavor_Network_Installer::get_table_name('quality_seals');

        $nodo = Flavor_Network_Node::find_by_slug($request['slug']);
        if (!$nodo) {
            return new WP_Error('nodo_no_encontrado', __('Nodo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $sello = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_sellos}
             WHERE nodo_id = %d AND estado = 'activo'
             ORDER BY fecha_obtencion DESC LIMIT 1",
            $nodo->id
        ));

        if (!$sello) {
            return new WP_REST_Response([
                'tiene_sello' => false,
                'nodo'        => $nodo->to_card_array(),
            ], 200);
        }

        return new WP_REST_Response([
            'tiene_sello'        => true,
            'tipo_sello'         => $sello->tipo_sello,
            'nivel'              => $sello->nivel,
            'puntuacion'         => (int) $sello->puntuacion,
            'fecha_obtencion'    => $sello->fecha_obtencion,
            'criterios_cumplidos' => json_decode($sello->criterios_cumplidos, true),
            'nodo'               => $nodo->to_card_array(),
        ], 200);
    }


    public function get_seals($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('quality_seals');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $sellos = $wpdb->get_results(
            "SELECT s.*, n.nombre AS nodo_nombre, n.slug AS nodo_slug
             FROM {$tabla} s
             LEFT JOIN {$tabla_nodos} n ON s.nodo_id = n.id
             ORDER BY s.fecha_obtencion DESC"
        );

        return new WP_REST_Response(['sellos' => $sellos ?: []], 200);
    }

    public function create_seal($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('quality_seals');

        $datos = [
            'nodo_id'             => (int) $request->get_param('nodo_id'),
            'tipo_sello'          => sanitize_text_field($request->get_param('tipo_sello') ?: 'app_consciente'),
            'nivel'               => sanitize_text_field($request->get_param('nivel') ?: 'basico'),
            'puntuacion'          => (int) $request->get_param('puntuacion'),
            'criterios_cumplidos' => wp_kses_post($request->get_param('criterios_cumplidos') ?: ''),
            'fecha_expiracion'    => sanitize_text_field($request->get_param('fecha_expiracion') ?: ''),
            'estado'              => 'activo',
        ];

        if (empty($datos['nodo_id'])) {
            return new WP_Error('datos_incompletos', __('Selecciona un nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        // Also update the node's nivel_sello field
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $wpdb->update($tabla_nodos, ['nivel_sello' => $datos['nivel']], ['id' => $datos['nodo_id']]);

        $wpdb->insert($tabla, $datos);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Sello otorgado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function update_seal($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('quality_seals');

        $datos_actualizar = [];
        $campos = ['nivel', 'puntuacion', 'criterios_cumplidos', 'estado', 'fecha_expiracion'];

        foreach ($campos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if ($campo === 'puntuacion') {
                    $datos_actualizar[$campo] = intval($valor);
                } elseif ($campo === 'criterios_cumplidos') {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (!empty($datos_actualizar['nivel'])) {
            $datos_actualizar['fecha_revision'] = current_time('mysql');
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Sello actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_seal($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('quality_seals');
        $wpdb->update($tabla, ['estado' => 'revocado'], ['id' => $request['id']]);
        return new WP_REST_Response(['success' => true, 'message' => __('Sello revocado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    // ─── FAVORITOS ───

    public function get_favorites($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('favorites');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_REST_Response(['favoritos' => []], 200);
        }

        $favoritos = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, n.nombre, n.slug, n.logo_url, n.tipo_entidad, n.ciudad, n.descripcion_corta
             FROM {$tabla} f
             LEFT JOIN {$tabla_nodos} n ON f.nodo_favorito_id = n.id
             WHERE f.nodo_id = %d AND n.estado = 'activo'
             ORDER BY f.fecha DESC",
            $nodo_local->id
        ));

        return new WP_REST_Response(['favoritos' => $favoritos ?: []], 200);
    }

    public function toggle_favorite($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('favorites');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $nodo_favorito_id = (int) $request->get_param('nodo_favorito_id');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE nodo_id = %d AND nodo_favorito_id = %d",
            $nodo_local->id, $nodo_favorito_id
        ));

        if ($existe) {
            $wpdb->delete($tabla, ['id' => $existe], ['%d']);
            return new WP_REST_Response([
                'success'  => true,
                'favorito' => false,
                'message'  => __('Eliminado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ], 200);
        }

        $wpdb->insert($tabla, [
            'nodo_id'          => $nodo_local->id,
            'nodo_favorito_id' => $nodo_favorito_id,
            'notas'            => sanitize_text_field($request->get_param('notas') ?: ''),
        ]);

        return new WP_REST_Response([
            'success'  => true,
            'favorito' => true,
            'message'  => __('Añadido a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    // ─── NODO LOCAL (ADMIN) ───

    public function get_local_node($request) {
        $nodo = Flavor_Network_Node::get_local_node();

        if (!$nodo) {
            return new WP_REST_Response([
                'configurado' => false,
                'message'     => __('El nodo local no está configurado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ], 200);
        }

        return new WP_REST_Response([
            'configurado' => true,
            'nodo'        => $nodo->to_array(),
        ], 200);
    }

    public function save_local_node($request) {
        $datos = $request->get_json_params();

        if (empty($datos['nombre']) || empty($datos['slug'])) {
            return new WP_Error('datos_incompletos', __('Nombre y slug son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos['site_url'] = get_site_url();

        $nodo = Flavor_Network_Node::save_local_node($datos);

        if (!$nodo) {
            return new WP_Error('error_guardado', __('Error al guardar el nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'nodo'    => $nodo->to_array(),
            'message' => __('Nodo local guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    // ─── ESTADÍSTICAS ───

    public function get_network_stats($request) {
        global $wpdb;

        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $tabla_conexiones = Flavor_Network_Installer::get_table_name('connections');
        $tabla_contenido = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_eventos = Flavor_Network_Installer::get_table_name('events');
        $tabla_colaboraciones = Flavor_Network_Installer::get_table_name('collaborations');

        $total_nodos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_nodos} WHERE estado = 'activo'");
        $total_conexiones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conexiones} WHERE estado = 'aprobada'");
        $total_contenido = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_contenido} WHERE estado = 'activo' AND visible_red = 1");
        $total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'activo' AND fecha_inicio >= NOW()");
        $total_colaboraciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_colaboraciones} WHERE estado = 'abierta'");

        $por_tipo = $wpdb->get_results(
            "SELECT tipo_entidad, COUNT(*) as total FROM {$tabla_nodos} WHERE estado = 'activo' GROUP BY tipo_entidad ORDER BY total DESC"
        );

        $por_pais = $wpdb->get_results(
            "SELECT pais, COUNT(*) as total FROM {$tabla_nodos} WHERE estado = 'activo' GROUP BY pais ORDER BY total DESC"
        );

        return new WP_REST_Response([
            'total_nodos'          => $total_nodos,
            'total_conexiones'     => $total_conexiones,
            'total_contenido'      => $total_contenido,
            'total_eventos'        => $total_eventos,
            'total_colaboraciones' => $total_colaboraciones,
            'por_tipo'             => $por_tipo ?: [],
            'por_pais'             => $por_pais ?: [],
        ], 200);
    }


    // ─── NEWSLETTER ───

    public function get_newsletters($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');
        $newsletters = $wpdb->get_results("SELECT * FROM {$tabla} ORDER BY fecha_creacion DESC");
        return new WP_REST_Response(['newsletters' => $newsletters ?: []], 200);
    }

    public function get_newsletter_detail($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');
        $newsletter = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $request['id']));
        if (!$newsletter) {
            return new WP_Error('no_encontrado', __('Newsletter no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }
        return new WP_REST_Response($newsletter, 200);
    }

    public function create_newsletter($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');
        $nodo_local = Flavor_Network_Node::get_local_node();

        $datos = [
            'nodo_id'           => $nodo_local ? $nodo_local->id : 0,
            'asunto'            => sanitize_text_field($request->get_param('asunto')),
            'contenido'         => wp_kses_post($request->get_param('contenido')),
            'tipo'              => sanitize_text_field($request->get_param('tipo') ?: 'resumen'),
            'estado'            => 'borrador',
            'fecha_programada'  => sanitize_text_field($request->get_param('fecha_programada') ?: ''),
        ];

        if (empty($datos['asunto'])) {
            return new WP_Error('sin_asunto', __('El asunto es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);
        return new WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id, 'message' => __('Newsletter creada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 201);
    }

    public function update_newsletter($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');

        $datos_actualizar = [];
        foreach (['asunto', 'contenido', 'tipo', 'estado', 'fecha_programada'] as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos_actualizar[$campo] = ($campo === 'contenido') ? wp_kses_post($valor) : sanitize_text_field($valor);
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, $datos_actualizar, ['id' => $request['id']]);
        return new WP_REST_Response(['success' => true, 'message' => __('Newsletter actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_newsletter($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');
        $wpdb->delete($tabla, ['id' => $request['id']], ['%d']);
        return new WP_REST_Response(['success' => true, 'message' => __('Newsletter eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function send_newsletter($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletters');
        $tabla_subs = Flavor_Network_Installer::get_table_name('newsletter_subscribers');
        $nodo_local = Flavor_Network_Node::get_local_node();

        $newsletter = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $request['id']));
        if (!$newsletter) {
            return new WP_Error('no_encontrada', __('Newsletter no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_subs} WHERE nodo_id = %d AND estado = 'activo'",
            $nodo_local ? $nodo_local->id : 0
        ));

        $enviados = 0;
        $nombre_red = $nodo_local ? $nodo_local->nombre : get_bloginfo('name');
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: ' . $nombre_red . ' <' . get_option('admin_email') . '>'];

        foreach ($suscriptores as $sub) {
            $contenido_email = '<html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
            $contenido_email .= '<div style="background:#2271b1;color:#fff;padding:20px;text-align:center;">';
            $contenido_email .= '<h1 style="margin:0;font-size:22px;">' . esc_html($nombre_red) . '</h1>';
            $contenido_email .= '</div>';
            $contenido_email .= '<div style="padding:20px;">';
            $contenido_email .= '<h2>' . esc_html($newsletter->asunto) . '</h2>';
            $contenido_email .= wpautop($newsletter->contenido);
            $contenido_email .= '</div>';
            $contenido_email .= '<div style="padding:15px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">';
            $contenido_email .= 'Red de Comunidades - ' . esc_html($nombre_red);
            $contenido_email .= '</div></body></html>';

            $resultado = wp_mail($sub->email, $newsletter->asunto, $contenido_email, $headers);
            if ($resultado) $enviados++;
        }

        $wpdb->update($tabla, [
            'estado'              => 'enviada',
            'fecha_envio'         => current_time('mysql'),
            'destinatarios_count' => $enviados,
        ], ['id' => $request['id']]);

        return new WP_REST_Response([
            'success'  => true,
            'enviados' => $enviados,
            'total'    => count($suscriptores),
            'message'  => sprintf(__('Newsletter enviada a %d suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN), $enviados),
        ], 200);
    }

    public function get_subscribers($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletter_subscribers');
        $nodo_local = Flavor_Network_Node::get_local_node();

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE nodo_id = %d ORDER BY fecha_suscripcion DESC",
            $nodo_local ? $nodo_local->id : 0
        ));

        return new WP_REST_Response(['suscriptores' => $suscriptores ?: []], 200);
    }

    public function add_subscriber($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletter_subscribers');
        $nodo_local = Flavor_Network_Node::get_local_node();

        $email = sanitize_email($request->get_param('email'));
        if (!is_email($email)) {
            return new WP_Error('email_invalido', __('Email no válido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $datos = [
            'nodo_id'    => $nodo_local ? $nodo_local->id : 0,
            'email'      => $email,
            'nombre'     => sanitize_text_field($request->get_param('nombre') ?: ''),
            'estado'     => 'activo',
            'token_baja' => wp_generate_password(32, false),
        ];

        $existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE nodo_id = %d AND email = %s",
            $datos['nodo_id'], $datos['email']
        ));

        if ($existente) {
            return new WP_Error('ya_suscrito', __('Este email ya está suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->insert($tabla, $datos);
        return new WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id, 'message' => __('Suscriptor añadido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 201);
    }

    public function remove_subscriber($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('newsletter_subscribers');
        $wpdb->delete($tabla, ['id' => $request['id']], ['%d']);
        return new WP_REST_Response(['success' => true, 'message' => __('Suscriptor eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function get_auto_newsletter_content($request) {
        global $wpdb;
        $nodo_local = Flavor_Network_Node::get_local_node();
        $dias = intval($request->get_param('dias')) ?: 7;
        $fecha_desde = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $contenido_auto = '';

        // Nuevos nodos
        $nuevos_nodos = $wpdb->get_results($wpdb->prepare(
            "SELECT nombre, tipo_entidad, ciudad FROM " . Flavor_Network_Installer::get_table_name('nodes') .
            " WHERE fecha_registro >= %s AND estado = 'activo' ORDER BY fecha_registro DESC LIMIT 10",
            $fecha_desde
        ));
        if ($nuevos_nodos) {
            $contenido_auto .= '<h3>Nuevos nodos en la red</h3><ul>';
            foreach ($nuevos_nodos as $nodo) {
                $contenido_auto .= '<li><strong>' . esc_html($nodo->nombre) . '</strong> (' . esc_html($nodo->tipo_entidad) . ')' . ($nodo->ciudad ? ' - ' . esc_html($nodo->ciudad) : '') . '</li>';
            }
            $contenido_auto .= '</ul>';
        }

        // Nuevos eventos
        $nuevos_eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, fecha_inicio, ubicacion FROM " . Flavor_Network_Installer::get_table_name('events') .
            " WHERE fecha_publicacion >= %s AND estado = 'activo' ORDER BY fecha_inicio ASC LIMIT 10",
            $fecha_desde
        ));
        if ($nuevos_eventos) {
            $contenido_auto .= '<h3>Próximos eventos</h3><ul>';
            foreach ($nuevos_eventos as $evento) {
                $contenido_auto .= '<li><strong>' . esc_html($evento->titulo) . '</strong> - ' . date('d/m/Y H:i', strtotime($evento->fecha_inicio)) . ($evento->ubicacion ? ' @ ' . esc_html($evento->ubicacion) : '') . '</li>';
            }
            $contenido_auto .= '</ul>';
        }

        // Alertas activas
        $alertas = $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, urgencia FROM " . Flavor_Network_Installer::get_table_name('solidarity_alerts') .
            " WHERE fecha_publicacion >= %s AND estado = 'activa' ORDER BY urgencia DESC LIMIT 5",
            $fecha_desde
        ));
        if ($alertas) {
            $contenido_auto .= '<h3>Alertas solidarias</h3><ul>';
            foreach ($alertas as $alerta) {
                $contenido_auto .= '<li>[' . strtoupper(esc_html($alerta->urgencia)) . '] <strong>' . esc_html($alerta->titulo) . '</strong></li>';
            }
            $contenido_auto .= '</ul>';
        }

        // Nuevas colaboraciones
        $colabs = $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, tipo FROM " . Flavor_Network_Installer::get_table_name('collaborations') .
            " WHERE fecha_creacion >= %s AND estado = 'abierta' ORDER BY fecha_creacion DESC LIMIT 5",
            $fecha_desde
        ));
        if ($colabs) {
            $contenido_auto .= '<h3>Nuevas colaboraciones</h3><ul>';
            foreach ($colabs as $colab) {
                $contenido_auto .= '<li><strong>' . esc_html($colab->titulo) . '</strong> (' . esc_html($colab->tipo) . ')</li>';
            }
            $contenido_auto .= '</ul>';
        }

        if (empty($contenido_auto)) {
            $contenido_auto = '<p>No hay novedades en los últimos ' . $dias . ' días.</p>';
        }

        return new WP_REST_Response(['contenido' => $contenido_auto, 'dias' => $dias], 200);
    }


    // ─── MATCHING NECESIDADES/EXCEDENTES ───

    public function get_matches($request) {
        global $wpdb;
        $tabla_matches = Flavor_Network_Installer::get_table_name('matches');
        $tabla_contenido = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_REST_Response(['matches' => []], 200);
        }

        $estado = sanitize_text_field($request->get_param('estado') ?: '');
        $where_estado = $estado ? $wpdb->prepare(" AND m.estado = %s", $estado) : "";

        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*,
                    cn.titulo AS necesidad_titulo, cn.descripcion AS necesidad_desc, cn.categorias AS necesidad_cats,
                    ce.titulo AS excedente_titulo, ce.descripcion AS excedente_desc, ce.categorias AS excedente_cats,
                    nn.nombre AS nodo_necesidad_nombre, nn.slug AS nodo_necesidad_slug,
                    ne.nombre AS nodo_excedente_nombre, ne.slug AS nodo_excedente_slug
             FROM {$tabla_matches} m
             LEFT JOIN {$tabla_contenido} cn ON m.necesidad_id = cn.id
             LEFT JOIN {$tabla_contenido} ce ON m.excedente_id = ce.id
             LEFT JOIN {$tabla_nodos} nn ON m.nodo_necesidad_id = nn.id
             LEFT JOIN {$tabla_nodos} ne ON m.nodo_excedente_id = ne.id
             WHERE (m.nodo_necesidad_id = %d OR m.nodo_excedente_id = %d) {$where_estado}
             ORDER BY m.puntuacion DESC, m.fecha_match DESC",
            $nodo_local->id, $nodo_local->id
        ));

        return new WP_REST_Response(['matches' => $matches ?: []], 200);
    }

    public function generate_matches($request) {
        global $wpdb;
        $tabla_contenido = Flavor_Network_Installer::get_table_name('shared_content');
        $tabla_matches = Flavor_Network_Installer::get_table_name('matches');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo', __('Configura tu nodo primero', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        // Get all active needs from local node
        $necesidades = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nodo_id, titulo, descripcion, categorias, tags, ubicacion
             FROM {$tabla_contenido}
             WHERE tipo_contenido = 'necesidad' AND estado = 'activo' AND nodo_id = %d",
            $nodo_local->id
        ));

        // Get all active surpluses from ALL other nodes
        $excedentes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nodo_id, titulo, descripcion, categorias, tags, ubicacion
             FROM {$tabla_contenido}
             WHERE tipo_contenido = 'excedente' AND estado = 'activo' AND nodo_id != %d",
            $nodo_local->id
        ));

        $nuevos_matches = 0;

        foreach ($necesidades as $necesidad) {
            foreach ($excedentes as $excedente) {
                // Check if match already exists
                $existente = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$tabla_matches} WHERE necesidad_id = %d AND excedente_id = %d",
                    $necesidad->id, $excedente->id
                ));
                if ($existente) continue;

                // Calculate match score
                $puntuacion = $this->calcular_puntuacion_match($necesidad, $excedente);

                // Only create match if score >= 20
                if ($puntuacion >= 20) {
                    $wpdb->insert($tabla_matches, [
                        'necesidad_id'      => $necesidad->id,
                        'excedente_id'      => $excedente->id,
                        'nodo_necesidad_id'  => $necesidad->nodo_id,
                        'nodo_excedente_id'  => $excedente->nodo_id,
                        'puntuacion'        => $puntuacion,
                        'estado'            => 'sugerido',
                    ]);
                    $nuevos_matches++;
                }
            }
        }

        // Also check reverse: surpluses from local, needs from others
        $excedentes_locales = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nodo_id, titulo, descripcion, categorias, tags, ubicacion
             FROM {$tabla_contenido}
             WHERE tipo_contenido = 'excedente' AND estado = 'activo' AND nodo_id = %d",
            $nodo_local->id
        ));

        $necesidades_otros = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nodo_id, titulo, descripcion, categorias, tags, ubicacion
             FROM {$tabla_contenido}
             WHERE tipo_contenido = 'necesidad' AND estado = 'activo' AND nodo_id != %d",
            $nodo_local->id
        ));

        foreach ($necesidades_otros as $necesidad) {
            foreach ($excedentes_locales as $excedente) {
                $existente = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$tabla_matches} WHERE necesidad_id = %d AND excedente_id = %d",
                    $necesidad->id, $excedente->id
                ));
                if ($existente) continue;

                $puntuacion = $this->calcular_puntuacion_match($necesidad, $excedente);
                if ($puntuacion >= 20) {
                    $wpdb->insert($tabla_matches, [
                        'necesidad_id'      => $necesidad->id,
                        'excedente_id'      => $excedente->id,
                        'nodo_necesidad_id'  => $necesidad->nodo_id,
                        'nodo_excedente_id'  => $excedente->nodo_id,
                        'puntuacion'        => $puntuacion,
                        'estado'            => 'sugerido',
                    ]);
                    $nuevos_matches++;
                }
            }
        }

        return new WP_REST_Response([
            'success'       => true,
            'nuevos_matches' => $nuevos_matches,
            'message'       => sprintf(__('Se encontraron %d nuevos matches', FLAVOR_PLATFORM_TEXT_DOMAIN), $nuevos_matches),
        ], 200);
    }

    private function calcular_puntuacion_match($necesidad, $excedente) {
        $puntuacion = 0;
        $titulo_necesidad = mb_strtolower($necesidad->titulo);
        $titulo_excedente = mb_strtolower($excedente->titulo);
        $desc_necesidad = mb_strtolower($necesidad->descripcion ?: '');
        $desc_excedente = mb_strtolower($excedente->descripcion ?: '');

        // Word matching in titles (high value)
        $palabras_necesidad = array_filter(explode(' ', preg_replace('/[^a-záéíóúñü\s]/u', '', $titulo_necesidad)));
        $palabras_excedente = array_filter(explode(' ', preg_replace('/[^a-záéíóúñü\s]/u', '', $titulo_excedente)));

        $palabras_comunes_titulo = array_intersect($palabras_necesidad, $palabras_excedente);
        // Filter out common stop words
        $stop_words = ['de', 'la', 'el', 'en', 'un', 'una', 'los', 'las', 'del', 'al', 'y', 'o', 'a', 'con', 'por', 'para', 'se', 'su', 'que', 'es'];
        $palabras_comunes_titulo = array_diff($palabras_comunes_titulo, $stop_words);
        $puntuacion += count($palabras_comunes_titulo) * 15;

        // Word matching in descriptions (lower value)
        $palabras_desc_nec = array_filter(explode(' ', preg_replace('/[^a-záéíóúñü\s]/u', '', $desc_necesidad)));
        $palabras_desc_exc = array_filter(explode(' ', preg_replace('/[^a-záéíóúñü\s]/u', '', $desc_excedente)));
        $palabras_comunes_desc = array_diff(array_intersect($palabras_desc_nec, $palabras_desc_exc), $stop_words);
        $puntuacion += min(count($palabras_comunes_desc) * 3, 30);

        // Category matching
        $cats_necesidad = json_decode($necesidad->categorias ?: '[]', true) ?: [];
        $cats_excedente = json_decode($excedente->categorias ?: '[]', true) ?: [];
        if (!empty($cats_necesidad) && !empty($cats_excedente)) {
            $cats_comunes = array_intersect(
                array_map('mb_strtolower', $cats_necesidad),
                array_map('mb_strtolower', $cats_excedente)
            );
            $puntuacion += count($cats_comunes) * 20;
        }

        // Tags matching
        $tags_necesidad = json_decode($necesidad->tags ?: '[]', true) ?: [];
        $tags_excedente = json_decode($excedente->tags ?: '[]', true) ?: [];
        if (!empty($tags_necesidad) && !empty($tags_excedente)) {
            $tags_comunes = array_intersect(
                array_map('mb_strtolower', $tags_necesidad),
                array_map('mb_strtolower', $tags_excedente)
            );
            $puntuacion += count($tags_comunes) * 10;
        }

        // Location proximity bonus
        if ($necesidad->ubicacion && $excedente->ubicacion) {
            if (mb_strtolower($necesidad->ubicacion) === mb_strtolower($excedente->ubicacion)) {
                $puntuacion += 15;
            }
        }

        return min($puntuacion, 100);
    }

    public function respond_match($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('matches');

        $estado = sanitize_text_field($request->get_param('estado'));
        $respuesta_texto = wp_kses_post($request->get_param('respuesta') ?: '');

        if (!in_array($estado, ['aceptado', 'rechazado', 'en_proceso'])) {
            return new WP_Error('estado_invalido', __('Estado no válido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla, [
            'estado'         => $estado,
            'respuesta'      => $respuesta_texto,
            'fecha_respuesta' => current_time('mysql'),
        ], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Match actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function dismiss_match($request) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('matches');
        $wpdb->update($tabla, ['estado' => 'descartado'], ['id' => $request['id']]);
        return new WP_REST_Response(['success' => true, 'message' => __('Match descartado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function contact_match($request) {
        global $wpdb;
        $tabla_matches = Flavor_Network_Installer::get_table_name('matches');
        $tabla_mensajes = Flavor_Network_Installer::get_table_name('messages');
        $nodo_local = Flavor_Network_Node::get_local_node();

        $match = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_matches} WHERE id = %d", $request['id']));
        if (!$match) {
            return new WP_Error('no_encontrado', __('Match no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        // Determine who to contact
        $nodo_destino_id = ($match->nodo_necesidad_id == $nodo_local->id)
            ? $match->nodo_excedente_id
            : $match->nodo_necesidad_id;

        $mensaje_contenido = wp_kses_post($request->get_param('mensaje') ?: __('Hola, me interesa el match entre nuestras necesidades y excedentes. ¿Podemos hablar?', FLAVOR_PLATFORM_TEXT_DOMAIN));

        $wpdb->insert($tabla_mensajes, [
            'de_nodo_id' => $nodo_local->id,
            'a_nodo_id'  => $nodo_destino_id,
            'tipo'       => 'match',
            'asunto'     => __('Contacto por match', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'contenido'  => $mensaje_contenido,
        ]);

        $wpdb->update($tabla_matches, ['estado' => 'contactado'], ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Mensaje enviado al nodo', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }


    // ─── Helpers para validación de argumentos ───

    private function get_directory_args() {
        return [
            'tipo'       => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'sector'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'nivel'      => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'pais'       => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'ciudad'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'busqueda'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'verificado' => ['type' => 'boolean'],
            'pagina'     => ['type' => 'integer', 'default' => 1],
            'por_pagina' => ['type' => 'integer', 'default' => 20],
        ];
    }

    private function get_map_args() {
        return [
            'tipo'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'nivel'  => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'sector' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
        ];
    }

    // ─── PREGUNTAS A LA RED ───

    public function get_questions($request) {
        global $wpdb;
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $categoria_filtro = sanitize_text_field($request->get_param('categoria') ?? '');
        $estado_filtro = sanitize_text_field($request->get_param('estado') ?? '');
        $busqueda_texto = sanitize_text_field($request->get_param('busqueda') ?? '');
        $pagina = max(1, (int) ($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, (int) ($request->get_param('por_pagina') ?: 20)));
        $offset = ($pagina - 1) * $por_pagina;

        $where = "1=1";
        $params = [];

        if ($categoria_filtro) {
            $where .= " AND q.categoria = %s";
            $params[] = $categoria_filtro;
        }

        if ($estado_filtro) {
            $where .= " AND q.estado = %s";
            $params[] = $estado_filtro;
        }

        if ($busqueda_texto) {
            $termino_busqueda = '%' . $wpdb->esc_like($busqueda_texto) . '%';
            $where .= " AND (q.titulo LIKE %s OR q.descripcion LIKE %s)";
            $params[] = $termino_busqueda;
            $params[] = $termino_busqueda;
        }

        // Consulta con conteo
        $consulta_conteo = "SELECT COUNT(*) FROM {$tabla_preguntas} q WHERE {$where}";
        if (!empty($params)) {
            $total_preguntas = (int) $wpdb->get_var($wpdb->prepare($consulta_conteo, $params));
        } else {
            $total_preguntas = (int) $wpdb->get_var($consulta_conteo);
        }

        $params_listado = $params;
        $params_listado[] = $por_pagina;
        $params_listado[] = $offset;

        $preguntas = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla_preguntas} q
             LEFT JOIN {$tabla_nodos} n ON q.nodo_id = n.id
             WHERE {$where}
             ORDER BY q.destacada DESC, q.fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $params_listado
        ));

        return new WP_REST_Response([
            'preguntas'  => $preguntas ?: [],
            'total'      => $total_preguntas,
            'pagina'     => $pagina,
            'por_pagina' => $por_pagina,
            'paginas'    => max(1, ceil($total_preguntas / $por_pagina)),
        ], 200);
    }

    public function get_question_detail($request) {
        global $wpdb;
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');

        $pregunta = $wpdb->get_row($wpdb->prepare(
            "SELECT q.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla_preguntas} q
             LEFT JOIN {$tabla_nodos} n ON q.nodo_id = n.id
             WHERE q.id = %d",
            $request['id']
        ));

        if (!$pregunta) {
            return new WP_Error('no_encontrada', __('Pregunta no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        // Incrementar vistas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_preguntas} SET vistas = vistas + 1 WHERE id = %d",
            $request['id']
        ));

        // Obtener respuestas
        $respuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla_respuestas} r
             LEFT JOIN {$tabla_nodos} n ON r.nodo_id = n.id
             WHERE r.pregunta_id = %d AND r.estado = 'activa'
             ORDER BY r.es_solucion DESC, r.votos_positivos DESC, r.fecha_publicacion ASC",
            $request['id']
        ));

        $pregunta->respuestas = $respuestas ?: [];

        return new WP_REST_Response($pregunta, 200);
    }

    public function create_question($request) {
        global $wpdb;
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $titulo_pregunta = sanitize_text_field($request->get_param('titulo'));
        $descripcion_pregunta = wp_kses_post($request->get_param('descripcion'));

        if (empty($titulo_pregunta) || empty($descripcion_pregunta)) {
            return new WP_Error('datos_incompletos', __('Titulo y descripcion son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $categorias_validas = ['general', 'tecnica', 'comercial', 'logistica', 'legal', 'otra'];
        $categoria_pregunta = sanitize_text_field($request->get_param('categoria') ?: 'general');
        if (!in_array($categoria_pregunta, $categorias_validas)) {
            $categoria_pregunta = 'general';
        }

        $tags_valor = $request->get_param('tags');
        $tags_pregunta = '';
        if (is_array($tags_valor)) {
            $tags_pregunta = wp_json_encode(array_map('sanitize_text_field', $tags_valor));
        } elseif (is_string($tags_valor) && !empty($tags_valor)) {
            $tags_pregunta = sanitize_text_field($tags_valor);
        }

        $datos_pregunta = [
            'nodo_id'     => $nodo_local->id,
            'titulo'      => $titulo_pregunta,
            'descripcion' => $descripcion_pregunta,
            'categoria'   => $categoria_pregunta,
            'tags'        => $tags_pregunta,
        ];

        $wpdb->insert($tabla_preguntas, $datos_pregunta);

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Pregunta publicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function update_question($request) {
        global $wpdb;
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');

        $datos_actualizar = [];
        $campos_permitidos = ['titulo', 'descripcion', 'categoria', 'tags', 'estado'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                if ($campo === 'descripcion') {
                    $datos_actualizar[$campo] = wp_kses_post($valor);
                } elseif ($campo === 'tags') {
                    if (is_array($valor)) {
                        $datos_actualizar[$campo] = wp_json_encode(array_map('sanitize_text_field', $valor));
                    } else {
                        $datos_actualizar[$campo] = sanitize_text_field($valor);
                    }
                } else {
                    $datos_actualizar[$campo] = sanitize_text_field($valor);
                }
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_datos', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $wpdb->update($tabla_preguntas, $datos_actualizar, ['id' => $request['id']]);

        return new WP_REST_Response(['success' => true, 'message' => __('Pregunta actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function delete_question($request) {
        global $wpdb;
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');

        // Eliminar respuestas asociadas
        $wpdb->delete($tabla_respuestas, ['pregunta_id' => $request['id']], ['%d']);

        // Eliminar pregunta
        $wpdb->delete($tabla_preguntas, ['id' => $request['id']], ['%d']);

        return new WP_REST_Response(['success' => true, 'message' => __('Pregunta eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function get_answers($request) {
        global $wpdb;
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $respuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo, n.slug AS nodo_slug
             FROM {$tabla_respuestas} r
             LEFT JOIN {$tabla_nodos} n ON r.nodo_id = n.id
             WHERE r.pregunta_id = %d AND r.estado = 'activa'
             ORDER BY r.es_solucion DESC, r.votos_positivos DESC, r.fecha_publicacion ASC",
            $request['id']
        ));

        return new WP_REST_Response(['respuestas' => $respuestas ?: []], 200);
    }

    public function create_answer($request) {
        global $wpdb;
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');
        $nodo_local = Flavor_Network_Node::get_local_node();

        if (!$nodo_local) {
            return new WP_Error('sin_nodo_local', __('Configura primero tu nodo local', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $contenido_respuesta = wp_kses_post($request->get_param('contenido'));

        if (empty($contenido_respuesta)) {
            return new WP_Error('contenido_requerido', __('El contenido es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        // Verificar que la pregunta existe
        $pregunta_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_preguntas} WHERE id = %d",
            $request['id']
        ));

        if (!$pregunta_existe) {
            return new WP_Error('pregunta_no_encontrada', __('Pregunta no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $wpdb->insert($tabla_respuestas, [
            'pregunta_id' => $request['id'],
            'nodo_id'     => $nodo_local->id,
            'contenido'   => $contenido_respuesta,
        ]);

        // Incrementar contador de respuestas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_preguntas} SET respuestas_count = respuestas_count + 1 WHERE id = %d",
            $request['id']
        ));

        return new WP_REST_Response([
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __('Respuesta publicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    public function vote_answer($request) {
        global $wpdb;
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');

        $tipo_voto = sanitize_text_field($request->get_param('voto'));

        if (!in_array($tipo_voto, ['positivo', 'negativo'])) {
            return new WP_Error('voto_invalido', __('Tipo de voto no valido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $columna_voto = ($tipo_voto === 'positivo') ? 'votos_positivos' : 'votos_negativos';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_respuestas} SET {$columna_voto} = {$columna_voto} + 1 WHERE id = %d",
            $request['id']
        ));

        return new WP_REST_Response(['success' => true, 'message' => __('Voto registrado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    public function mark_solution($request) {
        global $wpdb;
        $tabla_respuestas = Flavor_Network_Installer::get_table_name('answers');
        $tabla_preguntas = Flavor_Network_Installer::get_table_name('questions');

        // Obtener la respuesta y su pregunta
        $respuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_respuestas} WHERE id = %d",
            $request['id']
        ));

        if (!$respuesta) {
            return new WP_Error('no_encontrada', __('Respuesta no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        // Toggle es_solucion
        $nuevo_estado_solucion = $respuesta->es_solucion ? 0 : 1;

        // Quitar solucion de todas las respuestas de esta pregunta
        $wpdb->update($tabla_respuestas, ['es_solucion' => 0], ['pregunta_id' => $respuesta->pregunta_id]);

        if ($nuevo_estado_solucion) {
            // Marcar esta como solucion
            $wpdb->update($tabla_respuestas, ['es_solucion' => 1], ['id' => $request['id']]);

            // Marcar pregunta como respondida
            $wpdb->update($tabla_preguntas, ['estado' => 'respondida'], ['id' => $respuesta->pregunta_id]);
        } else {
            // Si se desmarca, volver la pregunta a abierta
            $wpdb->update($tabla_preguntas, ['estado' => 'abierta'], ['id' => $respuesta->pregunta_id]);
        }

        return new WP_REST_Response([
            'success'     => true,
            'es_solucion' => (bool) $nuevo_estado_solucion,
            'message'     => $nuevo_estado_solucion
                ? __('Marcada como solucion', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Solucion desmarcada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    // ─── WEBHOOKS CALLBACKS ───

    /**
     * Lista todos los webhooks registrados
     */
    public function list_webhooks($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $webhooks = $webhooks_manager->list_webhooks();

        return new WP_REST_Response([
            'webhooks' => array_values($webhooks),
            'total'    => count($webhooks),
        ], 200);
    }

    /**
     * Registra un nuevo webhook
     */
    public function register_webhook($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $url = esc_url_raw($request->get_param('url'));
        $eventos = $request->get_param('events');
        $secreto = sanitize_text_field($request->get_param('secret') ?? '');
        $nodo_id = intval($request->get_param('node_id') ?? 0);

        if (empty($url)) {
            return new WP_Error('url_requerida', __('La URL es obligatoria', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        if (empty($eventos)) {
            return new WP_Error('events_requeridos', __('Debes especificar al menos un evento', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $webhook_id = $webhooks_manager->register_webhook($url, $eventos, $secreto, $nodo_id ?: null);

        return new WP_REST_Response([
            'success'    => true,
            'webhook_id' => $webhook_id,
            'message'    => __('Webhook registrado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 201);
    }

    /**
     * Elimina un webhook
     */
    public function delete_webhook($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $webhook_id = sanitize_text_field($request['id']);

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $eliminado = $webhooks_manager->unregister_webhook($webhook_id);

        if (!$eliminado) {
            return new WP_Error('not_found', __('Webhook no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Webhook eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * Prueba un webhook enviando un ping
     */
    public function test_webhook($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $webhook_id = sanitize_text_field($request['id']);

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $resultado = $webhooks_manager->test_webhook($webhook_id);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return new WP_REST_Response([
            'success' => $resultado['success'],
            'result'  => $resultado,
            'message' => $resultado['success']
                ? __('Webhook respondió correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Error al contactar el webhook', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], $resultado['success'] ? 200 : 502);
    }

    /**
     * Obtiene estadísticas de webhooks
     */
    public function get_webhook_stats($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $estadisticas = $webhooks_manager->get_stats();

        return new WP_REST_Response($estadisticas, 200);
    }

    /**
     * Obtiene los logs de webhooks
     */
    public function get_webhook_logs($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_Error('webhooks_not_available', __('Sistema de webhooks no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 503]);
        }

        $limite = min(100, max(1, intval($request->get_param('limit') ?? 50)));

        $webhooks_manager = Flavor_Network_Webhooks::get_instance();
        $logs = $webhooks_manager->get_logs($limite);

        return new WP_REST_Response([
            'logs'  => $logs,
            'total' => count($logs),
        ], 200);
    }

    /**
     * Obtiene los tipos de eventos disponibles para webhooks
     */
    public function get_webhook_event_types($request) {
        if (!class_exists('Flavor_Network_Webhooks')) {
            return new WP_REST_Response([
                'event_types' => [],
            ], 200);
        }

        return new WP_REST_Response([
            'event_types' => Flavor_Network_Webhooks::EVENT_TYPES,
        ], 200);
    }

    /**
     * Recibe webhooks de otros nodos
     */
    public function receive_webhook($request) {
        $cuerpo = $request->get_json_params();

        if (empty($cuerpo)) {
            return new WP_Error('invalid_payload', __('Payload inválido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $evento = $cuerpo['event'] ?? 'unknown';
        $origen = $cuerpo['source'] ?? null;
        $datos = $cuerpo['data'] ?? [];
        $timestamp = $cuerpo['timestamp'] ?? current_time('c');

        // Verificar firma HMAC si hay secreto configurado
        $firma_recibida = $request->get_header('X-Webhook-Signature');
        if ($firma_recibida && $origen && !empty($origen['id'])) {
            $nodo_origen = Flavor_Network_Node::find($origen['id']);
            if ($nodo_origen && !empty($nodo_origen->api_secret)) {
                $cuerpo_raw = $request->get_body();
                $firma_esperada = 'sha256=' . hash_hmac('sha256', $cuerpo_raw, $nodo_origen->api_secret);

                if (!hash_equals($firma_esperada, $firma_recibida)) {
                    return new WP_Error('invalid_signature', __('Firma de webhook inválida', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 401]);
                }
            }
        }

        // Disparar acción para que otros componentes procesen el webhook
        do_action('flavor_network_webhook_received', $evento, $datos, $origen, $timestamp);
        do_action('flavor_network_webhook_' . str_replace('.', '_', $evento), $datos, $origen, $timestamp);

        // Log del webhook recibido
        if (defined('FLAVOR_PLATFORM_DEBUG') && FLAVOR_PLATFORM_DEBUG) {
            flavor_log_debug("Webhook recibido: {$evento} desde " . ($origen['url'] ?? 'desconocido'), 'NetworkWebhooks');
        }

        return new WP_REST_Response([
            'success'   => true,
            'received'  => $evento,
            'processed' => true,
        ], 200);
    }
}

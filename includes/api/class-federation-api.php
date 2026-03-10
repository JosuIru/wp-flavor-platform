<?php
/**
 * API de Federación para Red Social
 *
 * Endpoints para enviar y recibir publicaciones entre nodos.
 *
 * @package FlavorChatIA
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Federation_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-integration/v1';

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
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'registrar_rutas']);
    }

    /**
     * Registra las rutas de la API
     */
    public function registrar_rutas() {
        // Recibir publicación federada
        register_rest_route(self::API_NAMESPACE, '/federation/receive', [
            'methods'             => 'POST',
            'callback'            => [$this, 'recibir_publicacion'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // Verificar estado del nodo
        register_rest_route(self::API_NAMESPACE, '/federation/ping', [
            'methods'             => 'GET',
            'callback'            => [$this, 'ping'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener publicaciones públicas para federar
        register_rest_route(self::API_NAMESPACE, '/federation/feed', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_feed_federado'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Productores Federados ===

        // Listar productores compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/producers', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_productores_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => [
                    'type'              => 'number',
                    'sanitize_callback' => 'floatval',
                ],
                'lng' => [
                    'type'              => 'number',
                    'sanitize_callback' => 'floatval',
                ],
                'mensajeria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // Detalle de un productor
        register_rest_route(self::API_NAMESPACE, '/federation/producers/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_productor_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_title',
                ],
                'nodo_id' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Contactar a un productor
        register_rest_route(self::API_NAMESPACE, '/federation/producers/contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'contactar_productor'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Eventos Federados ===

        // Listar eventos compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/events', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_eventos_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'desde' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un evento
        register_rest_route(self::API_NAMESPACE, '/federation/events/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_evento_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Carpooling Federado ===

        // Listar viajes compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/carpooling', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_viajes_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'origen_lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'origen_lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'destino_lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'destino_lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'desde' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un viaje
        register_rest_route(self::API_NAMESPACE, '/federation/carpooling/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_viaje_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Talleres Federados ===

        // Listar talleres compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/workshops', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_talleres_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un taller
        register_rest_route(self::API_NAMESPACE, '/federation/workshops/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_taller_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Espacios Comunes Federados ===

        // Listar espacios compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/spaces', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_espacios_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'capacidad_min' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un espacio
        register_rest_route(self::API_NAMESPACE, '/federation/spaces/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_espacio_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Marketplace Federado ===

        // Listar anuncios compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/marketplace', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_anuncios_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un anuncio
        register_rest_route(self::API_NAMESPACE, '/federation/marketplace/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_anuncio_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Banco de Tiempo Federado ===

        // Listar servicios del banco de tiempo compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/timebank', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_servicios_tiempo_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'modalidad' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un servicio de tiempo
        register_rest_route(self::API_NAMESPACE, '/federation/timebank/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_servicio_tiempo_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Cursos Federados ===

        // Listar cursos compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/courses', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_cursos_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'nivel' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'modalidad' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'gratuitos' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un curso
        register_rest_route(self::API_NAMESPACE, '/federation/courses/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_curso_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);
    }

    /**
     * Verifica que la petición viene de un nodo autorizado
     */
    public function verificar_nodo($request) {
        global $wpdb;

        $origen = $request->get_header('X-Origin-Node');
        $token = $request->get_header('X-Node-Token');

        if (empty($origen)) {
            return new WP_Error('sin_origen', 'Falta header X-Origin-Node', ['status' => 401]);
        }

        // Verificar si el nodo está registrado y activo
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        $nodo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_nodos} WHERE site_url = %s AND activo = 1",
            $origen
        ));

        if (!$nodo) {
            // Permitir nodos no registrados pero marcar la petición
            $request->set_param('_nodo_no_registrado', true);
            return true;
        }

        // Verificar token si existe
        if (!empty($nodo->token) && $nodo->token !== $token) {
            return new WP_Error('token_invalido', 'Token de nodo inválido', ['status' => 403]);
        }

        $request->set_param('_nodo_id', $nodo->id);
        $request->set_param('_nodo_data', $nodo);

        return true;
    }

    /**
     * Endpoint: Recibir publicación federada
     */
    public function recibir_publicacion($request) {
        global $wpdb;

        $body = $request->get_json_params();

        if (empty($body['tipo']) || $body['tipo'] !== 'publicacion_compartida') {
            return new WP_Error('tipo_invalido', 'Tipo de contenido no soportado', ['status' => 400]);
        }

        if (empty($body['publicacion'])) {
            return new WP_Error('sin_contenido', 'Falta contenido de publicación', ['status' => 400]);
        }

        $pub = $body['publicacion'];
        $origen = $body['origen'] ?? '';
        $nodo_id = $request->get_param('_nodo_id');

        // Verificar si ya existe esta publicación (evitar duplicados)
        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE enlace_url = %s",
            $pub['enlace_url'] ?? ''
        ));

        if ($existe) {
            return [
                'success' => true,
                'message' => 'Publicación ya existente',
                'duplicado' => true,
            ];
        }

        // Obtener o crear usuario "nodo federado"
        $usuario_federado = $this->obtener_usuario_federado($origen, $pub['autor_nombre'] ?? 'Nodo Federado');

        // Insertar publicación
        $resultado = $wpdb->insert($tabla, [
            'usuario_id'         => $usuario_federado,
            'contenido'          => sanitize_textarea_field($pub['contenido'] ?? ''),
            'tipo'               => 'enlace',
            'enlace_url'         => esc_url_raw($pub['enlace_url'] ?? ''),
            'enlace_titulo'      => sanitize_text_field($pub['enlace_titulo'] ?? ''),
            'enlace_descripcion' => sanitize_textarea_field($pub['enlace_descripcion'] ?? ''),
            'enlace_imagen'      => esc_url_raw($pub['enlace_imagen'] ?? ''),
            'privacidad'         => 'publico',
            'estado'             => 'publicado',
            'fecha_creacion'     => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($resultado === false) {
            return new WP_Error('error_insercion', 'Error al guardar publicación', ['status' => 500]);
        }

        $publicacion_id = $wpdb->insert_id;

        // Registrar origen federado
        $tabla_federacion = $wpdb->prefix . 'flavor_social_federacion';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_federacion}'")) {
            $wpdb->insert($tabla_federacion, [
                'publicacion_id' => $publicacion_id,
                'nodo_origen'    => $origen,
                'nodo_id'        => $nodo_id,
                'fecha_recibido' => current_time('mysql'),
            ]);
        }

        do_action('flavor_publicacion_federada_recibida', $publicacion_id, $origen, $body);

        return [
            'success'        => true,
            'message'        => 'Publicación recibida',
            'publicacion_id' => $publicacion_id,
        ];
    }

    /**
     * Obtiene o crea un usuario para representar contenido federado
     */
    private function obtener_usuario_federado($origen, $nombre_autor) {
        // Usar un usuario genérico para contenido federado
        $username = 'federado_' . md5($origen);

        $user = get_user_by('login', $username);
        if ($user) {
            return $user->ID;
        }

        // Crear usuario
        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_pass'    => wp_generate_password(24),
            'user_email'   => $username . '@federado.local',
            'display_name' => $nombre_autor . ' (Federado)',
            'role'         => 'subscriber',
        ]);

        if (is_wp_error($user_id)) {
            // Fallback a admin
            return 1;
        }

        // Marcar como usuario federado
        update_user_meta($user_id, '_flavor_usuario_federado', true);
        update_user_meta($user_id, '_flavor_nodo_origen', $origen);

        return $user_id;
    }

    /**
     * Endpoint: Ping para verificar disponibilidad
     */
    public function ping($request) {
        return [
            'status'    => 'ok',
            'nodo'      => get_bloginfo('name'),
            'version'   => FLAVOR_CHAT_IA_VERSION,
            'timestamp' => current_time('c'),
        ];
    }

    /**
     * Endpoint: Obtener feed de publicaciones públicas
     */
    public function obtener_feed_federado($request) {
        global $wpdb;

        $limite = min(50, intval($request->get_param('limite')) ?: 20);
        $desde = $request->get_param('desde');

        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';

        $where = "privacidad = 'publico' AND estado = 'publicado'";
        if ($desde) {
            $where .= $wpdb->prepare(" AND fecha_creacion > %s", $desde);
        }

        $publicaciones = $wpdb->get_results(
            "SELECT p.*, u.display_name as autor_nombre
             FROM {$tabla} p
             LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE {$where}
             ORDER BY p.fecha_creacion DESC
             LIMIT {$limite}"
        );

        $resultado = [];
        foreach ($publicaciones as $pub) {
            $resultado[] = [
                'id'                 => $pub->id,
                'contenido'          => $pub->contenido,
                'tipo'               => $pub->tipo,
                'enlace_url'         => $pub->enlace_url,
                'enlace_titulo'      => $pub->enlace_titulo,
                'enlace_descripcion' => $pub->enlace_descripcion,
                'enlace_imagen'      => $pub->enlace_imagen,
                'autor_nombre'       => $pub->autor_nombre,
                'fecha'              => $pub->fecha_creacion,
                'likes'              => $pub->likes_count,
                'comentarios'        => $pub->comentarios_count,
            ];
        }

        return [
            'nodo'          => home_url(),
            'nombre'        => get_bloginfo('name'),
            'publicaciones' => $resultado,
            'total'         => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener productores compartidos en la red
     *
     * Parámetros opcionales:
     * - lat: Latitud del nodo solicitante
     * - lng: Longitud del nodo solicitante
     * - radio: Radio máximo en km (por defecto usa el del productor)
     * - mensajeria: 1 para incluir solo productores con mensajería
     */
    public function obtener_productores_federados($request) {
        global $wpdb;

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores'") !== $tabla_productores) {
            return new WP_Error('no_tabla', 'Sistema de productores federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $solo_mensajeria = $request->get_param('mensajeria') === '1';
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "compartir_en_red = 1"];

        if ($solo_mensajeria) {
            $where_clauses[] = "acepta_mensajeria = 1";
        }

        $where = implode(' AND ', $where_clauses);

        // Si tenemos coordenadas, calcular distancia
        if ($lat_solicitante && $lng_solicitante && !$solo_mensajeria) {
            // Fórmula Haversine para calcular distancia en km
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $productores = $wpdb->get_results("
                SELECT *,
                    {$haversine} AS distancia_km
                FROM {$tabla_productores}
                WHERE {$where}
                    AND latitud IS NOT NULL
                    AND longitud IS NOT NULL
                    AND (
                        acepta_mensajeria = 1
                        OR {$haversine} <= radio_entrega_km
                    )
                ORDER BY distancia_km ASC
                LIMIT {$limite}
            ");
        } else {
            $productores = $wpdb->get_results("
                SELECT *
                FROM {$tabla_productores}
                WHERE {$where}
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($productores as $prod) {
            $item = [
                'id'                => $prod->id,
                'nodo_id'           => $prod->nodo_id,
                'productor_id'      => $prod->productor_id,
                'nombre'            => $prod->nombre,
                'slug'              => $prod->slug,
                'ubicacion'         => $prod->ubicacion,
                'certificacion_eco' => (bool) $prod->certificacion_eco,
                'acepta_mensajeria' => (bool) $prod->acepta_mensajeria,
                'radio_entrega_km'  => $prod->radio_entrega_km,
            ];

            if (isset($prod->distancia_km)) {
                $item['distancia_km'] = round($prod->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'        => home_url(),
            'nombre'      => get_bloginfo('name'),
            'productores' => $resultado,
            'total'       => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un productor federado
     */
    public function obtener_productor_detalle($request) {
        global $wpdb;

        $slug = sanitize_title($request->get_param('slug'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        if (empty($slug)) {
            return new WP_Error('slug_requerido', 'Se requiere el slug del productor', ['status' => 400]);
        }

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';
        $tabla_productos = $wpdb->prefix . 'flavor_network_producer_products';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores'") !== $tabla_productores) {
            return new WP_Error('no_tabla', 'Sistema de productores federados no disponible', ['status' => 503]);
        }

        // Buscar productor
        $where = "slug = %s AND visible_en_red = 1";
        $params = [$slug];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $productor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_productores} WHERE {$where}",
            $params
        ));

        if (!$productor) {
            return new WP_Error('no_encontrado', 'Productor no encontrado', ['status' => 404]);
        }

        // Obtener productos del productor
        $productos = [];
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productos'") === $tabla_productos) {
            $productos_raw = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_productos}
                 WHERE nodo_id = %s AND productor_id = %d AND disponible = 1",
                $productor->nodo_id,
                $productor->productor_id
            ));

            foreach ($productos_raw as $prod) {
                $productos[] = [
                    'id'      => $prod->producto_id,
                    'nombre'  => $prod->nombre,
                    'precio'  => floatval($prod->precio),
                    'unidad'  => $prod->unidad,
                ];
            }
        }

        return [
            'productor' => [
                'id'                => $productor->id,
                'nodo_id'           => $productor->nodo_id,
                'productor_id'      => $productor->productor_id,
                'nombre'            => $productor->nombre,
                'slug'              => $productor->slug,
                'ubicacion'         => $productor->ubicacion,
                'latitud'           => $productor->latitud,
                'longitud'          => $productor->longitud,
                'radio_entrega_km'  => $productor->radio_entrega_km,
                'certificacion_eco' => (bool) $productor->certificacion_eco,
                'acepta_mensajeria' => (bool) $productor->acepta_mensajeria,
                'actualizado_en'    => $productor->actualizado_en,
            ],
            'productos' => $productos,
            'total_productos' => count($productos),
        ];
    }

    /**
     * Endpoint: Contactar a un productor federado
     */
    public function contactar_productor($request) {
        global $wpdb;

        $body = $request->get_json_params();

        $nodo_id = sanitize_text_field($body['nodo_id'] ?? '');
        $productor_id = absint($body['productor_id'] ?? 0);
        $mensaje = sanitize_textarea_field($body['mensaje'] ?? '');
        $email_contacto = sanitize_email($body['email'] ?? '');
        $nombre_contacto = sanitize_text_field($body['nombre'] ?? '');

        if (empty($nodo_id) || empty($productor_id) || empty($mensaje)) {
            return new WP_Error('datos_faltantes', 'Faltan datos requeridos', ['status' => 400]);
        }

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';

        // Verificar que el productor existe y acepta contacto
        $productor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_productores}
             WHERE nodo_id = %s AND productor_id = %d AND visible_en_red = 1",
            $nodo_id,
            $productor_id
        ));

        if (!$productor) {
            return new WP_Error('no_encontrado', 'Productor no encontrado', ['status' => 404]);
        }

        // Obtener email del productor desde el post original
        $email_productor = get_post_meta($productor_id, '_gc_contacto_email', true);

        if (empty($email_productor)) {
            // Intentar obtener del autor del post
            $post_productor = get_post($productor_id);
            if ($post_productor) {
                $autor = get_userdata($post_productor->post_author);
                if ($autor) {
                    $email_productor = $autor->user_email;
                }
            }
        }

        if (empty($email_productor)) {
            return new WP_Error('sin_email', 'El productor no tiene email de contacto configurado', ['status' => 400]);
        }

        // Enviar notificación por email
        $asunto = sprintf(
            __('[%s] Contacto desde la red federada', 'flavor-chat-ia'),
            get_bloginfo('name')
        );

        $cuerpo = sprintf(
            __("Has recibido un mensaje desde otro nodo de la red federada:\n\n" .
               "De: %s <%s>\n\n" .
               "Mensaje:\n%s\n\n" .
               "---\n" .
               "Este mensaje fue enviado a través de la red federada de Flavor.", 'flavor-chat-ia'),
            $nombre_contacto ?: __('Usuario anónimo', 'flavor-chat-ia'),
            $email_contacto ?: __('Sin email', 'flavor-chat-ia'),
            $mensaje
        );

        $enviado = wp_mail($email_productor, $asunto, $cuerpo);

        // Registrar el contacto
        do_action('flavor_productor_contactado_federacion', $productor_id, $body);

        return [
            'success' => $enviado,
            'message' => $enviado
                ? __('Mensaje enviado al productor', 'flavor-chat-ia')
                : __('Error al enviar el mensaje', 'flavor-chat-ia'),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // EVENTOS FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener eventos compartidos en la red
     */
    public function obtener_eventos_federados($request) {
        global $wpdb;

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return new WP_Error('no_tabla', 'Sistema de eventos federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 100;
        $desde = $request->get_param('desde') ?: date('Y-m-d H:i:s');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "fecha_inicio >= %s"];
        $params = [$desde];

        $where = implode(' AND ', $where_clauses);

        // Si tenemos coordenadas, filtrar por distancia
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $eventos = $wpdb->get_results($wpdb->prepare("
                SELECT *,
                    {$haversine} AS distancia_km
                FROM {$tabla_eventos}
                WHERE {$where}
                    AND (
                        es_online = 1
                        OR latitud IS NULL
                        OR {$haversine} <= {$radio_km}
                    )
                ORDER BY fecha_inicio ASC
                LIMIT {$limite}
            ", ...$params));
        } else {
            $eventos = $wpdb->get_results($wpdb->prepare("
                SELECT *
                FROM {$tabla_eventos}
                WHERE {$where}
                ORDER BY fecha_inicio ASC
                LIMIT {$limite}
            ", ...$params));
        }

        $resultado = [];
        foreach ($eventos as $ev) {
            $item = [
                'id'                 => $ev->id,
                'nodo_id'            => $ev->nodo_id,
                'evento_id'          => $ev->evento_id,
                'titulo'             => $ev->titulo,
                'descripcion'        => wp_trim_words($ev->descripcion, 30),
                'tipo'               => $ev->tipo,
                'fecha_inicio'       => $ev->fecha_inicio,
                'fecha_fin'          => $ev->fecha_fin,
                'ubicacion'          => $ev->ubicacion,
                'es_online'          => (bool) $ev->es_online,
                'precio'             => floatval($ev->precio),
                'aforo_maximo'       => $ev->aforo_maximo,
                'inscritos_count'    => $ev->inscritos_count,
                'organizador_nombre' => $ev->organizador_nombre,
                'imagen_url'         => $ev->imagen_url,
            ];

            if (isset($ev->distancia_km)) {
                $item['distancia_km'] = round($ev->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'    => home_url(),
            'nombre'  => get_bloginfo('name'),
            'eventos' => $resultado,
            'total'   => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un evento federado
     */
    public function obtener_evento_detalle($request) {
        global $wpdb;

        $evento_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return new WP_Error('no_tabla', 'Sistema de eventos federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$evento_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_eventos} WHERE {$where}",
            $params
        ));

        if (!$evento) {
            return new WP_Error('no_encontrado', 'Evento no encontrado', ['status' => 404]);
        }

        return [
            'evento' => [
                'id'                 => $evento->id,
                'nodo_id'            => $evento->nodo_id,
                'evento_id'          => $evento->evento_id,
                'titulo'             => $evento->titulo,
                'descripcion'        => $evento->descripcion,
                'tipo'               => $evento->tipo,
                'fecha_inicio'       => $evento->fecha_inicio,
                'fecha_fin'          => $evento->fecha_fin,
                'ubicacion'          => $evento->ubicacion,
                'direccion'          => $evento->direccion,
                'latitud'            => $evento->latitud,
                'longitud'           => $evento->longitud,
                'es_online'          => (bool) $evento->es_online,
                'url_online'         => $evento->url_online,
                'precio'             => floatval($evento->precio),
                'aforo_maximo'       => $evento->aforo_maximo,
                'inscritos_count'    => $evento->inscritos_count,
                'organizador_nombre' => $evento->organizador_nombre,
                'imagen_url'         => $evento->imagen_url,
                'actualizado_en'     => $evento->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CARPOOLING FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener viajes compartidos en la red
     */
    public function obtener_viajes_federados($request) {
        global $wpdb;

        $tabla_viajes = $wpdb->prefix . 'flavor_network_carpooling';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") !== $tabla_viajes) {
            return new WP_Error('no_tabla', 'Sistema de carpooling federado no disponible', ['status' => 503]);
        }

        $origen_lat = floatval($request->get_param('origen_lat'));
        $origen_lng = floatval($request->get_param('origen_lng'));
        $destino_lat = floatval($request->get_param('destino_lat'));
        $destino_lng = floatval($request->get_param('destino_lng'));
        $radio_km = absint($request->get_param('radio')) ?: 50;
        $desde = $request->get_param('desde') ?: date('Y-m-d H:i:s');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'activo'", "fecha_salida >= %s", "plazas_disponibles > 0"];
        $params = [$desde];

        $where = implode(' AND ', $where_clauses);

        // Calcular distancia si tenemos coordenadas de origen
        if ($origen_lat && $origen_lng) {
            $haversine_origen = "
                (6371 * acos(
                    cos(radians({$origen_lat})) *
                    cos(radians(origen_lat)) *
                    cos(radians(origen_lng) - radians({$origen_lng})) +
                    sin(radians({$origen_lat})) *
                    sin(radians(origen_lat))
                ))
            ";

            $select_extra = ", {$haversine_origen} AS distancia_origen_km";
            $where_extra = " AND {$haversine_origen} <= {$radio_km}";
            $order = "distancia_origen_km ASC";
        } else {
            $select_extra = "";
            $where_extra = "";
            $order = "fecha_salida ASC";
        }

        // Filtrar también por destino si se proporciona
        if ($destino_lat && $destino_lng) {
            $haversine_destino = "
                (6371 * acos(
                    cos(radians({$destino_lat})) *
                    cos(radians(destino_lat)) *
                    cos(radians(destino_lng) - radians({$destino_lng})) +
                    sin(radians({$destino_lat})) *
                    sin(radians(destino_lat))
                ))
            ";
            $select_extra .= ", {$haversine_destino} AS distancia_destino_km";
            $where_extra .= " AND {$haversine_destino} <= {$radio_km}";
        }

        $viajes = $wpdb->get_results($wpdb->prepare("
            SELECT * {$select_extra}
            FROM {$tabla_viajes}
            WHERE {$where} {$where_extra}
            ORDER BY {$order}
            LIMIT {$limite}
        ", ...$params));

        $resultado = [];
        foreach ($viajes as $v) {
            $item = [
                'id'                  => $v->id,
                'nodo_id'             => $v->nodo_id,
                'viaje_id'            => $v->viaje_id,
                'origen'              => $v->origen,
                'destino'             => $v->destino,
                'fecha_salida'        => $v->fecha_salida,
                'hora_salida'         => $v->hora_salida,
                'conductor_nombre'    => $v->conductor_nombre,
                'plazas_disponibles'  => $v->plazas_disponibles,
                'precio_plaza'        => floatval($v->precio_plaza),
                'permite_equipaje'    => (bool) $v->permite_equipaje,
                'permite_mascotas'    => (bool) $v->permite_mascotas,
            ];

            if (isset($v->distancia_origen_km)) {
                $item['distancia_origen_km'] = round($v->distancia_origen_km, 1);
            }
            if (isset($v->distancia_destino_km)) {
                $item['distancia_destino_km'] = round($v->distancia_destino_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'   => home_url(),
            'nombre' => get_bloginfo('name'),
            'viajes' => $resultado,
            'total'  => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un viaje federado
     */
    public function obtener_viaje_detalle($request) {
        global $wpdb;

        $viaje_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_viajes = $wpdb->prefix . 'flavor_network_carpooling';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") !== $tabla_viajes) {
            return new WP_Error('no_tabla', 'Sistema de carpooling federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$viaje_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $viaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_viajes} WHERE {$where}",
            $params
        ));

        if (!$viaje) {
            return new WP_Error('no_encontrado', 'Viaje no encontrado', ['status' => 404]);
        }

        return [
            'viaje' => [
                'id'                  => $viaje->id,
                'nodo_id'             => $viaje->nodo_id,
                'viaje_id'            => $viaje->viaje_id,
                'origen'              => $viaje->origen,
                'origen_lat'          => $viaje->origen_lat,
                'origen_lng'          => $viaje->origen_lng,
                'destino'             => $viaje->destino,
                'destino_lat'         => $viaje->destino_lat,
                'destino_lng'         => $viaje->destino_lng,
                'fecha_salida'        => $viaje->fecha_salida,
                'hora_salida'         => $viaje->hora_salida,
                'conductor_nombre'    => $viaje->conductor_nombre,
                'plazas_totales'      => $viaje->plazas_totales,
                'plazas_disponibles'  => $viaje->plazas_disponibles,
                'precio_plaza'        => floatval($viaje->precio_plaza),
                'permite_equipaje'    => (bool) $viaje->permite_equipaje,
                'permite_mascotas'    => (bool) $viaje->permite_mascotas,
                'notas'               => $viaje->notas,
                'estado'              => $viaje->estado,
                'actualizado_en'      => $viaje->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // TALLERES FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener talleres compartidos en la red
     */
    public function obtener_talleres_federados($request) {
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_network_workshops';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") !== $tabla_talleres) {
            return new WP_Error('no_tabla', 'Sistema de talleres federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 100;
        $categoria = $request->get_param('categoria');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado IN ('publicado', 'confirmado')"];
        $params = [];

        if (!empty($categoria)) {
            $where_clauses[] = "categoria = %s";
            $params[] = $categoria;
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $sql = "SELECT *, {$haversine} AS distancia_km
                    FROM {$tabla_talleres}
                    WHERE {$where}
                      AND (latitud IS NULL OR {$haversine} <= {$radio_km})
                    ORDER BY fecha_primera_sesion ASC
                    LIMIT {$limite}";
        } else {
            $sql = "SELECT *
                    FROM {$tabla_talleres}
                    WHERE {$where}
                    ORDER BY fecha_primera_sesion ASC
                    LIMIT {$limite}";
        }

        if (!empty($params)) {
            $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $talleres = $wpdb->get_results($sql);
        }

        $resultado = [];
        foreach ($talleres as $t) {
            $item = [
                'id'                   => $t->id,
                'nodo_id'              => $t->nodo_id,
                'taller_id'            => $t->taller_id,
                'titulo'               => $t->titulo,
                'slug'                 => $t->slug,
                'descripcion'          => wp_trim_words($t->descripcion, 30),
                'categoria'            => $t->categoria,
                'nivel'                => $t->nivel,
                'duracion_horas'       => floatval($t->duracion_horas),
                'numero_sesiones'      => $t->numero_sesiones,
                'max_participantes'    => $t->max_participantes,
                'inscritos_actuales'   => $t->inscritos_actuales,
                'plazas_disponibles'   => $t->max_participantes - $t->inscritos_actuales,
                'precio'               => floatval($t->precio),
                'es_gratuito'          => (bool) $t->es_gratuito,
                'ubicacion'            => $t->ubicacion,
                'organizador_nombre'   => $t->organizador_nombre,
                'imagen_url'           => $t->imagen_url,
                'fecha_primera_sesion' => $t->fecha_primera_sesion,
            ];

            if (isset($t->distancia_km)) {
                $item['distancia_km'] = round($t->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'talleres' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un taller federado
     */
    public function obtener_taller_detalle($request) {
        global $wpdb;

        $taller_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_talleres = $wpdb->prefix . 'flavor_network_workshops';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") !== $tabla_talleres) {
            return new WP_Error('no_tabla', 'Sistema de talleres federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$taller_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_talleres} WHERE {$where}",
            $params
        ));

        if (!$taller) {
            return new WP_Error('no_encontrado', 'Taller no encontrado', ['status' => 404]);
        }

        return [
            'taller' => [
                'id'                       => $taller->id,
                'nodo_id'                  => $taller->nodo_id,
                'taller_id'                => $taller->taller_id,
                'titulo'                   => $taller->titulo,
                'slug'                     => $taller->slug,
                'descripcion'              => $taller->descripcion,
                'categoria'                => $taller->categoria,
                'nivel'                    => $taller->nivel,
                'duracion_horas'           => floatval($taller->duracion_horas),
                'numero_sesiones'          => $taller->numero_sesiones,
                'max_participantes'        => $taller->max_participantes,
                'inscritos_actuales'       => $taller->inscritos_actuales,
                'plazas_disponibles'       => $taller->max_participantes - $taller->inscritos_actuales,
                'precio'                   => floatval($taller->precio),
                'es_gratuito'              => (bool) $taller->es_gratuito,
                'ubicacion'                => $taller->ubicacion,
                'latitud'                  => $taller->latitud,
                'longitud'                 => $taller->longitud,
                'organizador_nombre'       => $taller->organizador_nombre,
                'imagen_url'               => $taller->imagen_url,
                'fecha_primera_sesion'     => $taller->fecha_primera_sesion,
                'fecha_limite_inscripcion' => $taller->fecha_limite_inscripcion,
                'estado'                   => $taller->estado,
                'actualizado_en'           => $taller->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // ESPACIOS COMUNES FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener espacios compartidos en la red
     */
    public function obtener_espacios_federados($request) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_network_spaces';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") !== $tabla_espacios) {
            return new WP_Error('no_tabla', 'Sistema de espacios federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 50;
        $tipo = $request->get_param('tipo');
        $capacidad_min = absint($request->get_param('capacidad_min'));
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'disponible'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if ($capacidad_min > 0) {
            $where_clauses[] = $wpdb->prepare("capacidad_personas >= %d", $capacidad_min);
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $espacios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_espacios}
                WHERE {$where}
                  AND (latitud IS NULL OR {$haversine} <= {$radio_km})
                ORDER BY distancia_km ASC
                LIMIT {$limite}
            ");
        } else {
            $espacios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_espacios}
                WHERE {$where}
                ORDER BY nombre ASC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($espacios as $e) {
            $item = [
                'id'                 => $e->id,
                'nodo_id'            => $e->nodo_id,
                'espacio_id'         => $e->espacio_id,
                'nombre'             => $e->nombre,
                'descripcion'        => wp_trim_words($e->descripcion, 25),
                'tipo'               => $e->tipo,
                'ubicacion'          => $e->ubicacion,
                'capacidad_personas' => $e->capacidad_personas,
                'precio_hora'        => floatval($e->precio_hora),
                'precio_dia'         => floatval($e->precio_dia),
                'horario'            => $e->horario_apertura . ' - ' . $e->horario_cierre,
                'dias_disponibles'   => $e->dias_disponibles,
                'foto_principal'     => $e->foto_principal,
            ];

            if (isset($e->distancia_km)) {
                $item['distancia_km'] = round($e->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'espacios' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un espacio federado
     */
    public function obtener_espacio_detalle($request) {
        global $wpdb;

        $espacio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_espacios = $wpdb->prefix . 'flavor_network_spaces';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") !== $tabla_espacios) {
            return new WP_Error('no_tabla', 'Sistema de espacios federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$espacio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_espacios} WHERE {$where}",
            $params
        ));

        if (!$espacio) {
            return new WP_Error('no_encontrado', 'Espacio no encontrado', ['status' => 404]);
        }

        return [
            'espacio' => [
                'id'                 => $espacio->id,
                'nodo_id'            => $espacio->nodo_id,
                'espacio_id'         => $espacio->espacio_id,
                'nombre'             => $espacio->nombre,
                'descripcion'        => $espacio->descripcion,
                'tipo'               => $espacio->tipo,
                'ubicacion'          => $espacio->ubicacion,
                'latitud'            => $espacio->latitud,
                'longitud'           => $espacio->longitud,
                'capacidad_personas' => $espacio->capacidad_personas,
                'superficie_m2'      => $espacio->superficie_m2,
                'precio_hora'        => floatval($espacio->precio_hora),
                'precio_dia'         => floatval($espacio->precio_dia),
                'horario_apertura'   => $espacio->horario_apertura,
                'horario_cierre'     => $espacio->horario_cierre,
                'dias_disponibles'   => $espacio->dias_disponibles,
                'foto_principal'     => $espacio->foto_principal,
                'estado'             => $espacio->estado,
                'actualizado_en'     => $espacio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // MARKETPLACE FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener anuncios del marketplace compartidos en la red
     */
    public function obtener_anuncios_federados($request) {
        global $wpdb;

        $tabla_anuncios = $wpdb->prefix . 'flavor_network_marketplace';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            return new WP_Error('no_tabla', 'Sistema de marketplace federado no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 100;
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'publicado'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $anuncios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_anuncios}
                WHERE {$where}
                  AND (
                      envio_disponible = 1
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $anuncios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_anuncios}
                WHERE {$where}
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($anuncios as $a) {
            $item = [
                'id'               => $a->id,
                'nodo_id'          => $a->nodo_id,
                'anuncio_id'       => $a->anuncio_id,
                'titulo'           => $a->titulo,
                'slug'             => $a->slug,
                'descripcion'      => wp_trim_words($a->descripcion, 30),
                'tipo'             => $a->tipo,
                'categoria'        => $a->categoria,
                'precio'           => $a->precio !== null ? floatval($a->precio) : null,
                'es_gratuito'      => (bool) $a->es_gratuito,
                'condicion'        => $a->condicion,
                'imagen_principal' => $a->imagen_principal,
                'ubicacion'        => $a->ubicacion,
                'envio_disponible' => (bool) $a->envio_disponible,
                'usuario_nombre'   => $a->usuario_nombre,
            ];

            if (isset($a->distancia_km)) {
                $item['distancia_km'] = round($a->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'anuncios' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un anuncio federado
     */
    public function obtener_anuncio_detalle($request) {
        global $wpdb;

        $anuncio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_anuncios = $wpdb->prefix . 'flavor_network_marketplace';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            return new WP_Error('no_tabla', 'Sistema de marketplace federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$anuncio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $anuncio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_anuncios} WHERE {$where}",
            $params
        ));

        if (!$anuncio) {
            return new WP_Error('no_encontrado', 'Anuncio no encontrado', ['status' => 404]);
        }

        return [
            'anuncio' => [
                'id'               => $anuncio->id,
                'nodo_id'          => $anuncio->nodo_id,
                'anuncio_id'       => $anuncio->anuncio_id,
                'titulo'           => $anuncio->titulo,
                'slug'             => $anuncio->slug,
                'descripcion'      => $anuncio->descripcion,
                'tipo'             => $anuncio->tipo,
                'categoria'        => $anuncio->categoria,
                'precio'           => $anuncio->precio !== null ? floatval($anuncio->precio) : null,
                'es_gratuito'      => (bool) $anuncio->es_gratuito,
                'condicion'        => $anuncio->condicion,
                'imagen_principal' => $anuncio->imagen_principal,
                'ubicacion'        => $anuncio->ubicacion,
                'latitud'          => $anuncio->latitud,
                'longitud'         => $anuncio->longitud,
                'envio_disponible' => (bool) $anuncio->envio_disponible,
                'usuario_nombre'   => $anuncio->usuario_nombre,
                'estado'           => $anuncio->estado,
                'actualizado_en'   => $anuncio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // BANCO DE TIEMPO FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener servicios del banco de tiempo compartidos en la red
     */
    public function obtener_servicios_tiempo_federados($request) {
        global $wpdb;

        $tabla_servicios = $wpdb->prefix . 'flavor_network_time_bank';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") !== $tabla_servicios) {
            return new WP_Error('no_tabla', 'Sistema de banco de tiempo federado no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 50;
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $modalidad = $request->get_param('modalidad');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'activo'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        if (!empty($modalidad)) {
            $where_clauses[] = $wpdb->prepare("modalidad = %s", $modalidad);
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $servicios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_servicios}
                WHERE {$where}
                  AND (
                      modalidad = 'online'
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $servicios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_servicios}
                WHERE {$where}
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($servicios as $s) {
            $item = [
                'id'                       => $s->id,
                'nodo_id'                  => $s->nodo_id,
                'servicio_id'              => $s->servicio_id,
                'titulo'                   => $s->titulo,
                'descripcion'              => wp_trim_words($s->descripcion, 30),
                'tipo'                     => $s->tipo,
                'categoria'                => $s->categoria,
                'horas_estimadas'          => floatval($s->horas_estimadas),
                'modalidad'                => $s->modalidad,
                'disponibilidad'           => $s->disponibilidad,
                'ubicacion'                => $s->ubicacion,
                'usuario_nombre'           => $s->usuario_nombre,
                'valoracion_promedio'      => floatval($s->valoracion_promedio),
                'intercambios_completados' => (int) $s->intercambios_completados,
            ];

            if (isset($s->distancia_km)) {
                $item['distancia_km'] = round($s->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'      => home_url(),
            'nombre'    => get_bloginfo('name'),
            'servicios' => $resultado,
            'total'     => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un servicio de tiempo federado
     */
    public function obtener_servicio_tiempo_detalle($request) {
        global $wpdb;

        $servicio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_servicios = $wpdb->prefix . 'flavor_network_time_bank';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") !== $tabla_servicios) {
            return new WP_Error('no_tabla', 'Sistema de banco de tiempo federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$servicio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_servicios} WHERE {$where}",
            $params
        ));

        if (!$servicio) {
            return new WP_Error('no_encontrado', 'Servicio no encontrado', ['status' => 404]);
        }

        return [
            'servicio' => [
                'id'                       => $servicio->id,
                'nodo_id'                  => $servicio->nodo_id,
                'servicio_id'              => $servicio->servicio_id,
                'titulo'                   => $servicio->titulo,
                'descripcion'              => $servicio->descripcion,
                'tipo'                     => $servicio->tipo,
                'categoria'                => $servicio->categoria,
                'horas_estimadas'          => floatval($servicio->horas_estimadas),
                'modalidad'                => $servicio->modalidad,
                'disponibilidad'           => $servicio->disponibilidad,
                'ubicacion'                => $servicio->ubicacion,
                'latitud'                  => $servicio->latitud,
                'longitud'                 => $servicio->longitud,
                'usuario_nombre'           => $servicio->usuario_nombre,
                'valoracion_promedio'      => floatval($servicio->valoracion_promedio),
                'intercambios_completados' => (int) $servicio->intercambios_completados,
                'estado'                   => $servicio->estado,
                'actualizado_en'           => $servicio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CURSOS FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener cursos compartidos en la red
     */
    public function obtener_cursos_federados($request) {
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_network_courses';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") !== $tabla_cursos) {
            return new WP_Error('no_tabla', 'Sistema de cursos federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 100;
        $categoria = $request->get_param('categoria');
        $nivel = $request->get_param('nivel');
        $modalidad = $request->get_param('modalidad');
        $solo_gratuitos = $request->get_param('gratuitos') === '1';
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'publicado'"];

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        if (!empty($nivel)) {
            $where_clauses[] = $wpdb->prepare("nivel = %s", $nivel);
        }

        if (!empty($modalidad)) {
            $where_clauses[] = $wpdb->prepare("modalidad = %s", $modalidad);
        }

        if ($solo_gratuitos) {
            $where_clauses[] = "es_gratuito = 1";
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas (solo cursos presenciales o mixtos)
        if ($lat_solicitante && $lng_solicitante) {
            $haversine = "
                (6371 * acos(
                    cos(radians({$lat_solicitante})) *
                    cos(radians(latitud)) *
                    cos(radians(longitud) - radians({$lng_solicitante})) +
                    sin(radians({$lat_solicitante})) *
                    sin(radians(latitud))
                ))
            ";

            $cursos = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_cursos}
                WHERE {$where}
                  AND (
                      modalidad = 'online'
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY fecha_inicio ASC, actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $cursos = $wpdb->get_results("
                SELECT *
                FROM {$tabla_cursos}
                WHERE {$where}
                ORDER BY fecha_inicio ASC, actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($cursos as $c) {
            $item = [
                'id'                  => $c->id,
                'nodo_id'             => $c->nodo_id,
                'curso_id'            => $c->curso_id,
                'titulo'              => $c->titulo,
                'slug'                => $c->slug,
                'descripcion'         => wp_trim_words($c->descripcion, 30),
                'categoria'           => $c->categoria,
                'nivel'               => $c->nivel,
                'modalidad'           => $c->modalidad,
                'duracion_horas'      => floatval($c->duracion_horas),
                'numero_lecciones'    => (int) $c->numero_lecciones,
                'max_alumnos'         => (int) $c->max_alumnos,
                'inscritos_actuales'  => (int) $c->inscritos_actuales,
                'plazas_disponibles'  => (int) $c->max_alumnos - (int) $c->inscritos_actuales,
                'precio'              => floatval($c->precio),
                'es_gratuito'         => (bool) $c->es_gratuito,
                'ubicacion'           => $c->ubicacion,
                'instructor_nombre'   => $c->instructor_nombre,
                'valoracion_promedio' => floatval($c->valoracion_promedio),
                'imagen_url'          => $c->imagen_url,
                'fecha_inicio'        => $c->fecha_inicio,
                'fecha_fin'           => $c->fecha_fin,
            ];

            if (isset($c->distancia_km)) {
                $item['distancia_km'] = round($c->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'   => home_url(),
            'nombre' => get_bloginfo('name'),
            'cursos' => $resultado,
            'total'  => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un curso federado
     */
    public function obtener_curso_detalle($request) {
        global $wpdb;

        $curso_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_cursos = $wpdb->prefix . 'flavor_network_courses';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") !== $tabla_cursos) {
            return new WP_Error('no_tabla', 'Sistema de cursos federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$curso_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_cursos} WHERE {$where}",
            $params
        ));

        if (!$curso) {
            return new WP_Error('no_encontrado', 'Curso no encontrado', ['status' => 404]);
        }

        return [
            'curso' => [
                'id'                  => $curso->id,
                'nodo_id'             => $curso->nodo_id,
                'curso_id'            => $curso->curso_id,
                'titulo'              => $curso->titulo,
                'slug'                => $curso->slug,
                'descripcion'         => $curso->descripcion,
                'categoria'           => $curso->categoria,
                'nivel'               => $curso->nivel,
                'modalidad'           => $curso->modalidad,
                'duracion_horas'      => floatval($curso->duracion_horas),
                'numero_lecciones'    => (int) $curso->numero_lecciones,
                'max_alumnos'         => (int) $curso->max_alumnos,
                'inscritos_actuales'  => (int) $curso->inscritos_actuales,
                'plazas_disponibles'  => (int) $curso->max_alumnos - (int) $curso->inscritos_actuales,
                'precio'              => floatval($curso->precio),
                'es_gratuito'         => (bool) $curso->es_gratuito,
                'ubicacion'           => $curso->ubicacion,
                'latitud'             => $curso->latitud,
                'longitud'            => $curso->longitud,
                'instructor_nombre'   => $curso->instructor_nombre,
                'valoracion_promedio' => floatval($curso->valoracion_promedio),
                'imagen_url'          => $curso->imagen_url,
                'fecha_inicio'        => $curso->fecha_inicio,
                'fecha_fin'           => $curso->fecha_fin,
                'estado'              => $curso->estado,
                'actualizado_en'      => $curso->actualizado_en,
            ],
        ];
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Federation_API::get_instance();
}, 15);

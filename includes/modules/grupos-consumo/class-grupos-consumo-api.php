<?php
/**
 * API REST para Grupos de Consumo
 *
 * Endpoints para aplicaciones móviles (Flutter)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase de API REST
 */
class Flavor_Grupos_Consumo_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-chat-ia/v1';

    /**
     * Instancia singleton
     */
    private static $instance = null;

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        // GET /pedidos - Lista todos los pedidos
        register_rest_route(self::NAMESPACE, '/pedidos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pedidos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'estado' => [
                    'default' => 'abierto',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /pedidos/{id} - Obtiene un pedido específico
        register_rest_route(self::NAMESPACE, '/pedidos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pedido'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        // POST /pedidos/{id}/unirse - Unirse a un pedido
        register_rest_route(self::NAMESPACE, '/pedidos/(?P<id>\d+)/unirse', [
            'methods' => 'POST',
            'callback' => [$this, 'unirse_pedido'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'cantidad' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ],
            ],
        ]);

        // GET /mis-pedidos - Pedidos del usuario autenticado
        register_rest_route(self::NAMESPACE, '/mis-pedidos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_pedidos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /pedidos/{id}/marcar-pagado - Marcar como pagado
        register_rest_route(self::NAMESPACE, '/pedidos/(?P<id>\d+)/marcar-pagado', [
            'methods' => 'POST',
            'callback' => [$this, 'marcar_pagado'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        // POST /pedidos/{id}/marcar-recogido - Marcar como recogido
        register_rest_route(self::NAMESPACE, '/pedidos/(?P<id>\d+)/marcar-recogido', [
            'methods' => 'POST',
            'callback' => [$this, 'marcar_recogido'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        // ========================================
        // Nuevos Endpoints de Grupos de Consumo
        // ========================================

        // GET /gc/perfil - Perfil del consumidor
        register_rest_route(self::NAMESPACE, '/gc/perfil', [
            'methods' => 'GET',
            'callback' => [$this, 'get_perfil_consumidor'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // PUT /gc/preferencias - Actualizar preferencias
        register_rest_route(self::NAMESPACE, '/gc/preferencias', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_preferencias'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'preferencias_alimentarias' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'alergias' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // GET /gc/lista-compra - Obtener lista de compra
        register_rest_route(self::NAMESPACE, '/gc/lista-compra', [
            'methods' => 'GET',
            'callback' => [$this, 'get_lista_compra'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /gc/lista-compra/agregar - Agregar producto
        register_rest_route(self::NAMESPACE, '/gc/lista-compra/agregar', [
            'methods' => 'POST',
            'callback' => [$this, 'agregar_lista_compra'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'producto_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'cantidad' => [
                    'default' => 1,
                    
                ],
            ],
        ]);

        // DELETE /gc/lista-compra/{id} - Quitar producto
        register_rest_route(self::NAMESPACE, '/gc/lista-compra/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'quitar_lista_compra'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // GET /gc/suscripciones - Mis suscripciones
        register_rest_route(self::NAMESPACE, '/gc/suscripciones', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_suscripciones'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /gc/suscripciones - Crear suscripción
        register_rest_route(self::NAMESPACE, '/gc/suscripciones', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_suscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'tipo_cesta_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'frecuencia' => [
                    'default' => 'semanal',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /gc/suscripciones/{id}/pausar - Pausar
        register_rest_route(self::NAMESPACE, '/gc/suscripciones/(?P<id>\d+)/pausar', [
            'methods' => 'POST',
            'callback' => [$this, 'pausar_suscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // POST /gc/suscripciones/{id}/reanudar - Reanudar
        register_rest_route(self::NAMESPACE, '/gc/suscripciones/(?P<id>\d+)/reanudar', [
            'methods' => 'POST',
            'callback' => [$this, 'reanudar_suscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // POST /gc/suscripciones/{id}/cancelar - Cancelar
        register_rest_route(self::NAMESPACE, '/gc/suscripciones/(?P<id>\d+)/cancelar', [
            'methods' => 'POST',
            'callback' => [$this, 'cancelar_suscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'motivo' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /gc/productos - Catálogo con filtros
        register_rest_route(self::NAMESPACE, '/gc/productos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_catalogo_productos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'categoria' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'productor_id' => [
                    'validate_callback' => function($param) {
                        return empty($param) || is_numeric($param);
                    },
                ],
                'busqueda' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /gc/ciclos/calendario - Calendario de ciclos
        register_rest_route(self::NAMESPACE, '/gc/ciclos/calendario', [
            'methods' => 'GET',
            'callback' => [$this, 'get_calendario_ciclos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'meses' => [
                    'default' => 3,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /gc/pedidos/historial - Historial de pedidos
        register_rest_route(self::NAMESPACE, '/gc/pedidos/historial', [
            'methods' => 'GET',
            'callback' => [$this, 'get_historial_pedidos'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'limite' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /gc/cestas-tipo - Tipos de cestas disponibles
        register_rest_route(self::NAMESPACE, '/gc/cestas-tipo', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tipos_cestas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /gc/productores-cercanos - Productores que entregan en ubicación del usuario
        register_rest_route(self::NAMESPACE, '/gc/productores-cercanos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_productores_cercanos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'lat' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= -90 && $param <= 90;
                    },
                    
                ],
                'lng' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= -180 && $param <= 180;
                    },
                    
                ],
                'limite' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /gc/productores - Lista de todos los productores
        register_rest_route(self::NAMESPACE, '/gc/productores', [
            'methods' => 'GET',
            'callback' => [$this, 'get_productores'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'per_page' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'eco' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'con_entrega' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Verifica autenticación
     */
    public function check_authentication($request) {
        return is_user_logged_in();
    }

    /**
     * GET /pedidos - Lista pedidos
     */
    public function get_pedidos($request) {
        $estado = $request->get_param('estado');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $args = [
            'post_type' => 'pedido_colectivo',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($estado && $estado !== 'todos') {
            $args['meta_query'] = [
                [
                    'key' => '_estado',
                    'value' => $estado,
                ],
            ];
        }

        $query = new WP_Query($args);
        $pedidos = [];

        foreach ($query->posts as $post) {
            $pedidos[] = $this->format_pedido($post->ID);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages,
            ],
        ], 200);
    }

    /**
     * GET /pedidos/{id} - Obtiene un pedido
     */
    public function get_pedido($request) {
        $id = $request->get_param('id');

        $post = get_post($id);
        if (!$post || $post->post_type !== 'pedido_colectivo') {
            return new WP_Error('not_found', __('Pedido no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_pedido($id, true),
        ], 200);
    }

    /**
     * POST /pedidos/{id}/unirse - Unirse a pedido
     */
    public function unirse_pedido($request) {
        $id = $request->get_param('id');
        $cantidad = floatval($request->get_param('cantidad'));
        $user_id = get_current_user_id();

        // Verificar estado
        $estado = get_post_meta($id, '_estado', true);
        if ($estado !== 'abierto') {
            return new WP_Error('closed', __('El pedido no está abierto', 'flavor-chat-ia'), ['status' => 400]);
        }

        // Verificar si ya participa
        $participantes = get_post_meta($id, '_participantes', true) ?: [];
        foreach ($participantes as $part) {
            if ($part['user_id'] == $user_id) {
                return new WP_Error('already_joined', __('Ya participas en este pedido', 'flavor-chat-ia'), ['status' => 400]);
            }
        }

        // Añadir participante
        $precio_base = floatval(get_post_meta($id, '_precio_base', true));
        $gastos = floatval(get_post_meta($id, '_gastos_gestion', true));
        $precio_final = $precio_base * (1 + ($gastos / 100));

        $user = wp_get_current_user();
        $participantes[] = [
            'user_id' => $user_id,
            'nombre' => $user->display_name,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_final,
            'importe' => $cantidad * $precio_final,
            'pagado' => false,
            'recogido' => false,
            'fecha_inscripcion' => current_time('mysql'),
        ];

        update_post_meta($id, '_participantes', $participantes);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('¡Te has unido al pedido!', 'flavor-chat-ia'),
            'data' => [
                'importe_total' => $cantidad * $precio_final,
                'pedido' => $this->format_pedido($id),
            ],
        ], 200);
    }

    /**
     * GET /mis-pedidos - Pedidos del usuario
     */
    public function get_mis_pedidos($request) {
        $user_id = get_current_user_id();

        $args = [
            'post_type' => 'pedido_colectivo',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);
        $mis_pedidos = [];

        foreach ($query->posts as $post) {
            $participantes = get_post_meta($post->ID, '_participantes', true) ?: [];
            foreach ($participantes as $part) {
                if ($part['user_id'] == $user_id) {
                    $pedido = $this->format_pedido($post->ID);
                    $pedido['mi_participacion'] = [
                        'cantidad' => $part['cantidad'],
                        'importe' => $part['importe'],
                        'pagado' => $part['pagado'],
                        'recogido' => $part['recogido'],
                        'fecha_inscripcion' => $part['fecha_inscripcion'],
                    ];
                    $mis_pedidos[] = $pedido;
                    break;
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $mis_pedidos,
            'total' => count($mis_pedidos),
        ], 200);
    }

    /**
     * POST /pedidos/{id}/marcar-pagado
     */
    public function marcar_pagado($request) {
        $id = $request->get_param('id');
        $user_id = get_current_user_id();

        $participantes = get_post_meta($id, '_participantes', true) ?: [];
        foreach ($participantes as $index => $part) {
            if ($part['user_id'] == $user_id) {
                $participantes[$index]['pagado'] = true;
                update_post_meta($id, '_participantes', $participantes);

                return new WP_REST_Response([
                    'success' => true,
                    'message' => __('_precio_base', 'flavor-chat-ia'),
                ], 200);
            }
        }

        return new WP_Error('not_participant', __('No participas en este pedido', 'flavor-chat-ia'), ['status' => 400]);
    }

    /**
     * POST /pedidos/{id}/marcar-recogido
     */
    public function marcar_recogido($request) {
        $id = $request->get_param('id');
        $user_id = get_current_user_id();

        $participantes = get_post_meta($id, '_participantes', true) ?: [];
        foreach ($participantes as $index => $part) {
            if ($part['user_id'] == $user_id) {
                $participantes[$index]['recogido'] = true;
                update_post_meta($id, '_participantes', $participantes);

                return new WP_REST_Response([
                    'success' => true,
                    'message' => __('¡Te has unido al pedido!', 'flavor-chat-ia'),
                ], 200);
            }
        }

        return new WP_Error('not_participant', __('No participas en este pedido', 'flavor-chat-ia'), ['status' => 400]);
    }

    /**
     * Formatea un pedido para la API
     */
    private function format_pedido($id, $detallado = false) {
        $post = get_post($id);

        $participantes = get_post_meta($id, '_participantes', true) ?: [];
        $cantidad_actual = 0;
        foreach ($participantes as $part) {
            $cantidad_actual += floatval($part['cantidad'] ?? 0);
        }

        $cantidad_min = floatval(get_post_meta($id, '_cantidad_minima', true));
        $progreso = $cantidad_min > 0 ? min(100, ($cantidad_actual / $cantidad_min) * 100) : 0;

        $data = [
            'id' => $id,
            'titulo' => $post->post_title,
            'descripcion' => $detallado ? $post->post_content : wp_trim_words($post->post_content, 30),
            'productor' => get_post_meta($id, '_productor_nombre', true),
            'producto' => get_post_meta($id, '_producto_tipo', true),
            'precio_base' => floatval(get_post_meta($id, '_precio_base', true)),
            'gastos_gestion' => floatval(get_post_meta($id, '_gastos_gestion', true)),
            'unidad' => get_post_meta($id, '_unidad', true),
            'cantidad_minima' => $cantidad_min,
            'cantidad_maxima' => floatval(get_post_meta($id, '_cantidad_maxima', true)),
            'cantidad_actual' => $cantidad_actual,
            'progreso' => round($progreso, 2),
            'estado' => get_post_meta($id, '_estado', true),
            'participantes_count' => count($participantes),
            'fecha_cierre' => get_post_meta($id, '_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($id, '_fecha_entrega', true),
            'lugar_recogida' => get_post_meta($id, '_lugar_recogida', true),
            'imagen' => get_the_post_thumbnail_url($id, 'medium'),
        ];

        // Calcular precio final
        $data['precio_final'] = $data['precio_base'] * (1 + ($data['gastos_gestion'] / 100));

        // Información adicional si es detallado
        if ($detallado) {
            $data['meta'] = [
                'contacto' => get_post_meta($id, '_contacto_email', true),
                'instrucciones' => get_post_meta($id, '_instrucciones', true),
            ];
            if (!is_user_logged_in()) {
                unset($data['meta']['contacto']);
            }
        }

        return $data;
    }

    // ========================================
    // Nuevos métodos de la API
    // ========================================

    /**
     * GET /gc/perfil - Perfil del consumidor
     */
    public function get_perfil_consumidor($request) {
        $usuario_id = get_current_user_id();

        // Buscar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'es_miembro' => false,
                    'usuario' => [
                        'id' => $usuario_id,
                        'nombre' => wp_get_current_user()->display_name,
                        'email' => wp_get_current_user()->user_email,
                    ],
                ],
            ], 200);
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'es_miembro' => false,
                    'grupo_disponible' => [
                        'id' => $grupos[0]->ID,
                        'nombre' => $grupos[0]->post_title,
                    ],
                    'usuario' => [
                        'id' => $usuario_id,
                        'nombre' => wp_get_current_user()->display_name,
                        'email' => wp_get_current_user()->user_email,
                    ],
                ],
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'es_miembro' => true,
                'consumidor' => [
                    'id' => $consumidor->id,
                    'rol' => $consumidor->rol,
                    'estado' => $consumidor->estado,
                    'preferencias_alimentarias' => $consumidor->preferencias_alimentarias,
                    'alergias' => $consumidor->alergias,
                    'saldo_pendiente' => floatval($consumidor->saldo_pendiente),
                    'fecha_alta' => $consumidor->fecha_alta,
                ],
                'grupo' => [
                    'id' => $grupos[0]->ID,
                    'nombre' => $grupos[0]->post_title,
                ],
            ],
        ], 200);
    }

    /**
     * PUT /gc/preferencias - Actualizar preferencias
     */
    public function actualizar_preferencias($request) {
        $usuario_id = get_current_user_id();

        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return new WP_Error('no_grupo', __('No hay grupos disponibles', 'flavor-chat-ia'), ['status' => 404]);
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return new WP_Error('no_miembro', __('No eres miembro del grupo', 'flavor-chat-ia'), ['status' => 403]);
        }

        $datos = [
            'preferencias_alimentarias' => $request->get_param('preferencias_alimentarias'),
            'alergias' => $request->get_param('alergias'),
        ];

        $resultado = $consumidor_manager->actualizar_preferencias($consumidor->id, $datos);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('update_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * GET /gc/lista-compra - Obtener lista de compra
     */
    public function get_lista_compra($request) {
        $dashboard_tab = Flavor_GC_Dashboard_Tab::get_instance();
        $items = $dashboard_tab->obtener_lista_compra(get_current_user_id());

        $items_formateados = [];
        $total = 0;

        foreach ($items as $item) {
            $subtotal = floatval($item->precio) * floatval($item->cantidad);
            $total += $subtotal;

            $items_formateados[] = [
                'id' => $item->id,
                'producto_id' => $item->producto_id,
                'producto_nombre' => $item->producto_nombre,
                'productor' => $item->productor_nombre,
                'cantidad' => floatval($item->cantidad),
                'precio_unitario' => floatval($item->precio),
                'unidad' => $item->unidad,
                'subtotal' => $subtotal,
                'imagen' => $item->imagen_url,
                'notas' => $item->notas,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'items' => $items_formateados,
                'total' => $total,
                'count' => count($items_formateados),
            ],
        ], 200);
    }

    /**
     * POST /gc/lista-compra/agregar - Agregar producto
     */
    public function agregar_lista_compra($request) {
        $producto_id = absint($request->get_param('producto_id'));
        $cantidad = floatval($request->get_param('cantidad'));

        $dashboard_tab = Flavor_GC_Dashboard_Tab::get_instance();
        $resultado = $dashboard_tab->agregar_a_lista(get_current_user_id(), $producto_id, $cantidad);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('add_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * DELETE /gc/lista-compra/{id} - Quitar producto
     */
    public function quitar_lista_compra($request) {
        $item_id = absint($request->get_param('id'));

        $dashboard_tab = Flavor_GC_Dashboard_Tab::get_instance();
        $resultado = $dashboard_tab->quitar_de_lista(get_current_user_id(), $item_id);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('remove_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * GET /gc/suscripciones - Mis suscripciones
     */
    public function get_mis_suscripciones($request) {
        $usuario_id = get_current_user_id();

        // Buscar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [],
            ], 200);
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [],
            ], 200);
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $suscripciones = $suscripciones_manager->listar_suscripciones_consumidor($consumidor->id);

        $suscripciones_formateadas = [];
        foreach ($suscripciones as $suscripcion) {
            $suscripciones_formateadas[] = [
                'id' => $suscripcion->id,
                'tipo_cesta' => [
                    'id' => $suscripcion->tipo_cesta_id,
                    'nombre' => $suscripcion->cesta_nombre,
                    'descripcion' => $suscripcion->cesta_descripcion,
                    'precio_base' => floatval($suscripcion->precio_base),
                ],
                'frecuencia' => $suscripcion->frecuencia,
                'importe' => floatval($suscripcion->importe),
                'estado' => $suscripcion->estado,
                'fecha_inicio' => $suscripcion->fecha_inicio,
                'fecha_proximo_cargo' => $suscripcion->fecha_proximo_cargo,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $suscripciones_formateadas,
        ], 200);
    }

    /**
     * POST /gc/suscripciones - Crear suscripción
     */
    public function crear_suscripcion($request) {
        $usuario_id = get_current_user_id();
        $tipo_cesta_id = absint($request->get_param('tipo_cesta_id'));
        if (!$tipo_cesta_id) {
            $tipo_cesta_id = absint($request->get_param('cesta_id'));
        }

        if (!$tipo_cesta_id) {
            $cesta = $request->get_param('cesta');
            if (is_numeric($cesta)) {
                $tipo_cesta_id = absint($cesta);
            } elseif (is_string($cesta) && $cesta !== '') {
                $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
                $tipo_cesta_id = (int) $suscripciones_manager->obtener_tipo_cesta_por_slug(sanitize_title($cesta));
            }
        }

        $frecuencia = $request->get_param('frecuencia');

        if (!$tipo_cesta_id) {
            return new WP_Error('missing_basket', __('Debes seleccionar una cesta valida', 'flavor-chat-ia'), ['status' => 400]);
        }

        $consumidor = $this->obtener_consumidor_actual($usuario_id);

        if (!$consumidor) {
            return new WP_Error('no_miembro', __('No eres miembro del grupo', 'flavor-chat-ia'), ['status' => 403]);
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $resultado = $suscripciones_manager->crear_suscripcion($consumidor->id, $tipo_cesta_id, $frecuencia);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('create_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * POST /gc/suscripciones/{id}/pausar - Pausar
     */
    public function pausar_suscripcion($request) {
        $suscripcion_id = absint($request->get_param('id'));

        // Verificar propiedad
        if (!$this->verificar_propiedad_suscripcion($suscripcion_id)) {
            return new WP_Error('not_owner', __('No tienes permisos', 'flavor-chat-ia'), ['status' => 403]);
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $resultado = $suscripciones_manager->pausar_suscripcion($suscripcion_id);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('pause_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * POST /gc/suscripciones/{id}/reanudar - Reanudar
     */
    public function reanudar_suscripcion($request) {
        $suscripcion_id = absint($request->get_param('id'));

        if (!$this->verificar_propiedad_suscripcion($suscripcion_id)) {
            return new WP_Error('not_owner', __('No tienes permisos', 'flavor-chat-ia'), ['status' => 403]);
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $resultado = $suscripciones_manager->reanudar_suscripcion($suscripcion_id);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('resume_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * POST /gc/suscripciones/{id}/cancelar - Cancelar
     */
    public function cancelar_suscripcion($request) {
        $suscripcion_id = absint($request->get_param('id'));
        $motivo = $request->get_param('motivo') ?? '';

        // Verificar propiedad
        if (!$this->verificar_propiedad_suscripcion($suscripcion_id)) {
            return new WP_Error('not_owner', __('No tienes permisos', 'flavor-chat-ia'), ['status' => 403]);
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $resultado = $suscripciones_manager->cancelar_suscripcion($suscripcion_id, $motivo);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 200);
        } else {
            return new WP_Error('cancel_failed', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * GET /gc/productos - Catálogo con filtros
     */
    public function get_catalogo_productos($request) {
        $categoria = $request->get_param('categoria');
        $productor_id = $request->get_param('productor_id');
        $busqueda = $request->get_param('busqueda');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $args = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if ($busqueda) {
            $args['s'] = $busqueda;
        }

        if ($categoria) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'gc_categoria',
                    'field' => 'slug',
                    'terms' => $categoria,
                ],
            ];
        }

        if ($productor_id) {
            $args['meta_query'] = [
                [
                    'key' => '_gc_productor_id',
                    'value' => $productor_id,
                ],
            ];
        }

        $query = new WP_Query($args);
        $productos = [];

        foreach ($query->posts as $post) {
            $productor_id_meta = get_post_meta($post->ID, '_gc_productor_id', true);
            $productor = $productor_id_meta ? get_post($productor_id_meta) : null;

            $productos[] = [
                'id' => $post->ID,
                'nombre' => $post->post_title,
                'descripcion' => wp_trim_words($post->post_content, 30),
                'precio' => floatval(get_post_meta($post->ID, '_gc_precio', true)),
                'unidad' => get_post_meta($post->ID, '_gc_unidad', true),
                'cantidad_minima' => floatval(get_post_meta($post->ID, '_gc_cantidad_minima', true)),
                'stock' => get_post_meta($post->ID, '_gc_stock', true),
                'temporada' => get_post_meta($post->ID, '_gc_temporada', true),
                'origen' => get_post_meta($post->ID, '_gc_origen', true),
                'productor' => $productor ? [
                    'id' => $productor->ID,
                    'nombre' => $productor->post_title,
                    'eco' => get_post_meta($productor->ID, '_gc_certificacion_eco', true) === '1',
                ] : null,
                'imagen' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'categorias' => wp_get_post_terms($post->ID, 'gc_categoria', ['fields' => 'names']),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $productos,
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages,
            ],
        ], 200);
    }

    /**
     * GET /gc/ciclos/calendario - Calendario de ciclos
     */
    public function get_calendario_ciclos($request) {
        $meses = $request->get_param('meses');
        $include_productores = $request->get_param('include_productores') === '1';

        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-t', strtotime('+' . $meses . ' months'));

        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => -1,
            'post_status' => ['gc_abierto', 'gc_cerrado', 'gc_entregado', 'publish'],
            'meta_query' => [
                [
                    'key' => '_gc_fecha_entrega',
                    'value' => [$fecha_inicio, $fecha_fin],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_gc_fecha_entrega',
            'order' => 'ASC',
        ]);

        $ciclos_formateados = [];
        foreach ($ciclos as $ciclo) {
            $productor_ids = [];
            if ($include_productores) {
                $productor_ids = $this->get_productor_ids_by_ciclo($ciclo->ID);
            }
            $ciclos_formateados[] = [
                'id' => $ciclo->ID,
                'nombre' => $ciclo->post_title,
                'estado' => get_post_status($ciclo->ID),
                'fecha_inicio' => get_post_meta($ciclo->ID, '_gc_fecha_inicio', true),
                'fecha_cierre' => get_post_meta($ciclo->ID, '_gc_fecha_cierre', true),
                'fecha_entrega' => get_post_meta($ciclo->ID, '_gc_fecha_entrega', true),
                'hora_entrega' => get_post_meta($ciclo->ID, '_gc_hora_entrega', true),
                'lugar_entrega' => get_post_meta($ciclo->ID, '_gc_lugar_entrega', true),
                'notas' => get_post_meta($ciclo->ID, '_gc_notas', true),
                'productor_ids' => $include_productores ? $productor_ids : [],
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $ciclos_formateados,
        ], 200);
    }

    /**
     * Obtiene IDs de productores con pedidos en un ciclo.
     *
     * @param int $ciclo_id
     * @return array
     */
    private function get_productor_ids_by_ciclo($ciclo_id) {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
             FROM {$tabla_pedidos} p
             INNER JOIN {$wpdb->postmeta} pm
               ON pm.post_id = p.producto_id AND pm.meta_key = '_gc_productor_id'
             WHERE p.ciclo_id = %d",
            $ciclo_id
        ));

        $ids = array_map('absint', $ids);
        return array_values(array_filter($ids));
    }

    /**
     * GET /gc/pedidos/historial - Historial de pedidos
     */
    public function get_historial_pedidos($request) {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $usuario_id = get_current_user_id();
        $limite = $request->get_param('limite');

        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT ciclo_id, SUM(cantidad * precio_unitario) as total, COUNT(*) as num_items, MIN(fecha_pedido) as fecha
            FROM $tabla_pedidos
            WHERE usuario_id = %d
            GROUP BY ciclo_id
            ORDER BY fecha DESC
            LIMIT %d",
            $usuario_id,
            $limite
        ));

        $historial = [];
        foreach ($pedidos as $pedido) {
            $ciclo = get_post($pedido->ciclo_id);
            $fecha_entrega = get_post_meta($pedido->ciclo_id, '_gc_fecha_entrega', true);
            $estado = get_post_status($pedido->ciclo_id);

            $historial[] = [
                'ciclo_id' => $pedido->ciclo_id,
                'ciclo_nombre' => $ciclo ? $ciclo->post_title : null,
                'estado' => $estado,
                'fecha_entrega' => $fecha_entrega,
                'num_items' => (int) $pedido->num_items,
                'total' => floatval($pedido->total),
                'fecha_pedido' => $pedido->fecha,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $historial,
        ], 200);
    }

    /**
     * GET /gc/cestas-tipo - Tipos de cestas disponibles
     */
    public function get_tipos_cestas($request) {
        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $cestas = $suscripciones_manager->listar_tipos_cestas();

        $cestas_formateadas = [];
        foreach ($cestas as $cesta) {
            $cestas_formateadas[] = [
                'id' => $cesta->id,
                'nombre' => $cesta->nombre,
                'slug' => $cesta->slug,
                'descripcion' => $cesta->descripcion,
                'precio_base' => floatval($cesta->precio_base),
                'imagen' => $cesta->imagen_id ? wp_get_attachment_url($cesta->imagen_id) : null,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $cestas_formateadas,
        ], 200);
    }

    /**
     * Verifica si el usuario actual es propietario de la suscripción
     */
    private function verificar_propiedad_suscripcion($suscripcion_id) {
        $usuario_id = get_current_user_id();

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $suscripcion = $suscripciones_manager->obtener_suscripcion($suscripcion_id);

        if (!$suscripcion) {
            return false;
        }

        // Verificar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_manager->obtener_por_id($suscripcion->consumidor_id);

        if (!$consumidor || $consumidor->usuario_id != $usuario_id) {
            // Permitir si es admin
            return current_user_can('gc_gestionar_suscripciones');
        }

        return true;
    }

    /**
     * Obtiene la membresia activa mas reciente del usuario autenticado.
     *
     * @param int $usuario_id ID del usuario.
     * @return object|null
     */
    private function obtener_consumidor_actual($usuario_id) {
        global $wpdb;

        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT *
             FROM {$tabla_consumidores}
             WHERE usuario_id = %d
             ORDER BY (estado = 'activo') DESC, fecha_alta DESC, id DESC
             LIMIT 1",
            $usuario_id
        ));
    }

    /**
     * GET /gc/productores-cercanos - Obtiene productores que entregan en la ubicación del usuario
     *
     * Usa la fórmula Haversine para calcular la distancia entre el usuario y cada productor,
     * y filtra aquellos cuyo radio de entrega cubre la ubicación del usuario.
     *
     * @param WP_REST_Request $request Petición con lat y lng del usuario
     * @return WP_REST_Response Lista de productores que pueden entregar en esa ubicación
     */
    public function get_productores_cercanos($request) {
        global $wpdb;

        $latitud_usuario = floatval($request->get_param('lat'));
        $longitud_usuario = floatval($request->get_param('lng'));
        $limite = absint($request->get_param('limite'));

        // Radio de la Tierra en km
        $radio_tierra_km = 6371;

        // Consulta SQL con fórmula Haversine
        // Busca productores cuyo radio de entrega cubra la distancia hasta el usuario
        $consulta_sql = $wpdb->prepare("
            SELECT
                p.ID,
                p.post_title,
                p.post_content,
                pm_lat.meta_value as latitud,
                pm_lng.meta_value as longitud,
                pm_radio.meta_value as radio_entrega_km,
                pm_direccion.meta_value as direccion,
                pm_ubicacion.meta_value as ubicacion,
                pm_eco.meta_value as certificacion_eco,
                pm_telefono.meta_value as telefono,
                pm_email.meta_value as email,
                (
                    %d * ACOS(
                        LEAST(1, GREATEST(-1,
                            COS(RADIANS(%f)) * COS(RADIANS(CAST(pm_lat.meta_value AS DECIMAL(10,7)))) *
                            COS(RADIANS(CAST(pm_lng.meta_value AS DECIMAL(10,7))) - RADIANS(%f)) +
                            SIN(RADIANS(%f)) * SIN(RADIANS(CAST(pm_lat.meta_value AS DECIMAL(10,7))))
                        ))
                    )
                ) as distancia_km
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = '_gc_lat'
            INNER JOIN {$wpdb->postmeta} pm_lng ON p.ID = pm_lng.post_id AND pm_lng.meta_key = '_gc_lng'
            INNER JOIN {$wpdb->postmeta} pm_radio ON p.ID = pm_radio.post_id AND pm_radio.meta_key = '_gc_radio_entrega_km'
            LEFT JOIN {$wpdb->postmeta} pm_direccion ON p.ID = pm_direccion.post_id AND pm_direccion.meta_key = '_gc_direccion_completa'
            LEFT JOIN {$wpdb->postmeta} pm_ubicacion ON p.ID = pm_ubicacion.post_id AND pm_ubicacion.meta_key = '_gc_ubicacion'
            LEFT JOIN {$wpdb->postmeta} pm_eco ON p.ID = pm_eco.post_id AND pm_eco.meta_key = '_gc_certificacion_eco'
            LEFT JOIN {$wpdb->postmeta} pm_telefono ON p.ID = pm_telefono.post_id AND pm_telefono.meta_key = '_gc_contacto_telefono'
            LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = '_gc_contacto_email'
            WHERE p.post_type = 'gc_productor'
            AND p.post_status = 'publish'
            AND pm_lat.meta_value IS NOT NULL
            AND pm_lat.meta_value != ''
            AND pm_lng.meta_value IS NOT NULL
            AND pm_lng.meta_value != ''
            AND pm_radio.meta_value IS NOT NULL
            AND CAST(pm_radio.meta_value AS DECIMAL(10,2)) > 0
            HAVING distancia_km <= CAST(pm_radio.meta_value AS DECIMAL(10,2))
            ORDER BY distancia_km ASC
            LIMIT %d
        ",
            $radio_tierra_km,
            $latitud_usuario,
            $longitud_usuario,
            $latitud_usuario,
            $limite
        );

        $productores_encontrados = $wpdb->get_results($consulta_sql);

        $productores_formateados = [];

        foreach ($productores_encontrados as $productor) {
            // Obtener imagen destacada
            $imagen_url = get_the_post_thumbnail_url($productor->ID, 'medium');

            // Contar productos del productor
            $cantidad_productos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = '_gc_productor_id' AND meta_value = %d",
                $productor->ID
            ));

            $productores_formateados[] = [
                'id' => (int) $productor->ID,
                'nombre' => $productor->post_title,
                'descripcion' => wp_trim_words($productor->post_content, 30, '...'),
                'ubicacion' => $productor->ubicacion,
                'direccion' => $productor->direccion,
                'coordenadas' => [
                    'lat' => floatval($productor->latitud),
                    'lng' => floatval($productor->longitud),
                ],
                'radio_entrega_km' => floatval($productor->radio_entrega_km),
                'distancia_km' => round(floatval($productor->distancia_km), 2),
                'certificacion_eco' => $productor->certificacion_eco === '1',
                'contacto' => [
                    'telefono' => $productor->telefono,
                    'email' => $productor->email,
                ],
                'imagen' => $imagen_url ?: null,
                'cantidad_productos' => (int) $cantidad_productos,
                'url' => add_query_arg('productor', intval($productor->ID), home_url('/mi-portal/grupos-consumo/productores-cercanos/')),
                'entrega_disponible' => true, // Siempre true porque solo devolvemos los que pueden entregar
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $productores_formateados,
            'meta' => [
                'ubicacion_usuario' => [
                    'lat' => $latitud_usuario,
                    'lng' => $longitud_usuario,
                ],
                'total' => count($productores_formateados),
            ],
        ], 200);
    }

    /**
     * GET /gc/productores - Lista de todos los productores
     *
     * @param WP_REST_Request $request Petición
     * @return WP_REST_Response Lista de productores
     */
    public function get_productores($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $solo_eco = $request->get_param('eco') === '1';
        $con_entrega = $request->get_param('con_entrega') === '1';

        $args_query = [
            'post_type' => 'gc_productor',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        // Filtrar por certificación ecológica
        if ($solo_eco) {
            $args_query['meta_query'][] = [
                'key' => '_gc_certificacion_eco',
                'value' => '1',
            ];
        }

        // Filtrar por productores con entrega a domicilio
        if ($con_entrega) {
            $args_query['meta_query'][] = [
                'key' => '_gc_radio_entrega_km',
                'value' => '0',
                'compare' => '>',
                'type' => 'NUMERIC',
            ];
        }

        $query = new WP_Query($args_query);
        $productores = [];

        foreach ($query->posts as $post) {
            $radio_entrega = floatval(get_post_meta($post->ID, '_gc_radio_entrega_km', true));
            $latitud_productor = get_post_meta($post->ID, '_gc_lat', true);
            $longitud_productor = get_post_meta($post->ID, '_gc_lng', true);

            $productores[] = [
                'id' => $post->ID,
                'nombre' => $post->post_title,
                'descripcion' => wp_trim_words($post->post_content, 30, '...'),
                'ubicacion' => get_post_meta($post->ID, '_gc_ubicacion', true),
                'direccion' => get_post_meta($post->ID, '_gc_direccion_completa', true),
                'coordenadas' => ($latitud_productor && $longitud_productor) ? [
                    'lat' => floatval($latitud_productor),
                    'lng' => floatval($longitud_productor),
                ] : null,
                'radio_entrega_km' => $radio_entrega,
                'tiene_entrega_domicilio' => $radio_entrega > 0,
                'certificacion_eco' => get_post_meta($post->ID, '_gc_certificacion_eco', true) === '1',
                'contacto' => [
                    'nombre' => get_post_meta($post->ID, '_gc_contacto_nombre', true),
                    'telefono' => get_post_meta($post->ID, '_gc_contacto_telefono', true),
                    'email' => get_post_meta($post->ID, '_gc_contacto_email', true),
                ],
                'imagen' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'url' => add_query_arg('productor', intval($post->ID), home_url('/mi-portal/grupos-consumo/productores-cercanos/')),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $productores,
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages,
            ],
        ], 200);
    }

    /**
     * Calcula la distancia entre dos puntos geográficos usando la fórmula Haversine
     *
     * @param float $latitud1 Latitud del punto 1
     * @param float $longitud1 Longitud del punto 1
     * @param float $latitud2 Latitud del punto 2
     * @param float $longitud2 Longitud del punto 2
     * @return float Distancia en kilómetros
     */
    private function calcular_distancia_haversine($latitud1, $longitud1, $latitud2, $longitud2) {
        $radio_tierra_km = 6371; // Radio de la Tierra en km

        $diferencia_latitud = deg2rad($latitud2 - $latitud1);
        $diferencia_longitud = deg2rad($longitud2 - $longitud1);

        $valor_a = sin($diferencia_latitud / 2) * sin($diferencia_latitud / 2) +
                   cos(deg2rad($latitud1)) * cos(deg2rad($latitud2)) *
                   sin($diferencia_longitud / 2) * sin($diferencia_longitud / 2);

        $valor_c = 2 * atan2(sqrt($valor_a), sqrt(1 - $valor_a));

        return $radio_tierra_km * $valor_c;
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

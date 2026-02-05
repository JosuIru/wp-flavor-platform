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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
                    'sanitize_callback' => 'floatval',
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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
            return new WP_Error('not_found', 'Pedido no encontrado', ['status' => 404]);
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
            return new WP_Error('closed', 'El pedido no está abierto', ['status' => 400]);
        }

        // Verificar si ya participa
        $participantes = get_post_meta($id, '_participantes', true) ?: [];
        foreach ($participantes as $part) {
            if ($part['user_id'] == $user_id) {
                return new WP_Error('already_joined', 'Ya participas en este pedido', ['status' => 400]);
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
            'message' => '¡Te has unido al pedido!',
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
                    'message' => 'Marcado como pagado',
                ], 200);
            }
        }

        return new WP_Error('not_participant', 'No participas en este pedido', ['status' => 400]);
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
                    'message' => 'Marcado como recogido',
                ], 200);
            }
        }

        return new WP_Error('not_participant', 'No participas en este pedido', ['status' => 400]);
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
            return new WP_Error('no_grupo', 'No hay grupos disponibles', ['status' => 404]);
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return new WP_Error('no_miembro', 'No eres miembro del grupo', ['status' => 403]);
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
        $frecuencia = $request->get_param('frecuencia');

        // Buscar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return new WP_Error('no_grupo', 'No hay grupos disponibles', ['status' => 404]);
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return new WP_Error('no_miembro', 'No eres miembro del grupo', ['status' => 403]);
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
            return new WP_Error('not_owner', 'No tienes permisos', ['status' => 403]);
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
     * POST /gc/suscripciones/{id}/cancelar - Cancelar
     */
    public function cancelar_suscripcion($request) {
        $suscripcion_id = absint($request->get_param('id'));
        $motivo = $request->get_param('motivo') ?? '';

        // Verificar propiedad
        if (!$this->verificar_propiedad_suscripcion($suscripcion_id)) {
            return new WP_Error('not_owner', 'No tienes permisos', ['status' => 403]);
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
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $ciclos_formateados,
        ], 200);
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
}

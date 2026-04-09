<?php
/**
 * API REST para el módulo de Encuestas
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona los endpoints REST de encuestas
 */
class Flavor_Encuestas_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor/v1';

    /**
     * Base del endpoint
     */
    const REST_BASE = 'encuestas';

    /**
     * Referencia al módulo principal
     *
     * @var Flavor_Chat_Encuestas_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Encuestas_Module $module Módulo principal
     */
    public function __construct($module) {
        $this->module = $module;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Crear encuesta
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'crear_encuesta'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args'                => $this->get_crear_args(),
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'listar_encuestas'],
                'permission_callback' => [$this, 'public_read_permission'],
                'args'                => $this->get_listar_args(),
            ],
        ]);

        // Obtener/Actualizar/Eliminar encuesta
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'obtener_encuesta'],
                'permission_callback' => [$this, 'can_read_survey'],
                'args'                => [
                    'id' => [
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'actualizar_encuesta'],
                'permission_callback' => [$this, 'check_edit_permission'],
                'args'                => $this->get_actualizar_args(),
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'eliminar_encuesta'],
                'permission_callback' => [$this, 'check_edit_permission'],
            ],
        ]);

        // Campos de encuesta
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)/campos', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'agregar_campo'],
                'permission_callback' => [$this, 'check_edit_permission'],
                'args'                => $this->get_campo_args(),
            ],
        ]);

        // Responder encuesta
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)/responder', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'responder_encuesta'],
            'permission_callback' => [$this, 'can_answer_survey'],
            'args'                => [
                'respuestas' => [
                    'required'    => true,
                    'type'        => 'object',
                    'description' => __('Respuestas en formato {campo_id: valor}', 'flavor-platform'),
                ],
            ],
        ]);

        // Obtener resultados
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)/resultados', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'obtener_resultados'],
            'permission_callback' => [$this, 'check_view_results_permission'],
        ]);

        // Cerrar encuesta
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)/cerrar', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'cerrar_encuesta'],
            'permission_callback' => [$this, 'check_edit_permission'],
        ]);

        // Listar por contexto
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/contexto/(?P<tipo>[a-z_]+)/(?P<contexto_id>[\d]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'listar_por_contexto'],
            'permission_callback' => [$this, 'public_read_permission'],
            'args'                => [
                'estado' => [
                    'default' => 'activa',
                    'enum'    => ['borrador', 'activa', 'cerrada', 'archivada', ''],
                ],
                'limit' => [
                    'default'           => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    },
                ],
                'offset' => [
                    'default'           => 0,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 0;
                    },
                ],
            ],
        ]);

        // Verificar si usuario ya participó
        register_rest_route(self::NAMESPACE, '/' . self::REST_BASE . '/(?P<id>[\d]+)/participacion', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'verificar_participacion'],
            'permission_callback' => [$this, 'can_check_participation'],
        ]);
    }

    // =========================================================================
    // CALLBACKS
    // =========================================================================

    /**
     * Crear encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function crear_encuesta($request) {
        $datos = [
            'titulo'            => $request->get_param('titulo'),
            'descripcion'       => $request->get_param('descripcion'),
            'tipo'              => $request->get_param('tipo'),
            'contexto_tipo'     => $request->get_param('contexto_tipo'),
            'contexto_id'       => $request->get_param('contexto_id'),
            'es_anonima'        => $request->get_param('es_anonima'),
            'permite_multiples' => $request->get_param('permite_multiples'),
            'mostrar_resultados'=> $request->get_param('mostrar_resultados'),
            'fecha_cierre'      => $request->get_param('fecha_cierre'),
            'campos'            => $request->get_param('campos'),
            'estado'            => $request->get_param('estado') ?: 'activa',
        ];

        $resultado = $this->module->crear_encuesta($datos);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        $encuesta = $this->module->obtener_encuesta($resultado);

        return rest_ensure_response([
            'success' => true,
            'data'    => $this->preparar_encuesta_response($encuesta),
            'message' => __('Encuesta creada correctamente', 'flavor-platform'),
        ]);
    }

    /**
     * Obtener encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function obtener_encuesta($request) {
        $encuesta_id = absint($request->get_param('id'));
        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            return new WP_Error(
                'not_found',
                __('Encuesta no encontrada', 'flavor-platform'),
                ['status' => 404]
            );
        }

        // Verificar acceso si es borrador
        if ($encuesta->estado === 'borrador') {
            if (!$this->module->puede_editar_encuesta($encuesta_id)) {
                return new WP_Error(
                    'forbidden',
                    __('No tienes acceso a esta encuesta', 'flavor-platform'),
                    ['status' => 403]
                );
            }
        }

        return rest_ensure_response([
            'success' => true,
            'data'    => $this->preparar_encuesta_response($encuesta),
        ]);
    }

    /**
     * Actualizar encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function actualizar_encuesta($request) {
        $encuesta_id = absint($request->get_param('id'));

        $datos = array_filter([
            'titulo'            => $request->get_param('titulo'),
            'descripcion'       => $request->get_param('descripcion'),
            'tipo'              => $request->get_param('tipo'),
            'es_anonima'        => $request->get_param('es_anonima'),
            'permite_multiples' => $request->get_param('permite_multiples'),
            'mostrar_resultados'=> $request->get_param('mostrar_resultados'),
            'fecha_cierre'      => $request->get_param('fecha_cierre'),
            'estado'            => $request->get_param('estado'),
        ], function($value) {
            return $value !== null;
        });

        $resultado = $this->module->actualizar_encuesta($encuesta_id, $datos);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        return rest_ensure_response([
            'success' => true,
            'data'    => $this->preparar_encuesta_response($encuesta),
            'message' => __('Encuesta actualizada', 'flavor-platform'),
        ]);
    }

    /**
     * Eliminar encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function eliminar_encuesta($request) {
        $encuesta_id = absint($request->get_param('id'));
        $resultado = $this->module->eliminar_encuesta($encuesta_id);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Encuesta eliminada', 'flavor-platform'),
        ]);
    }

    /**
     * Agregar campo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function agregar_campo($request) {
        $encuesta_id = absint($request->get_param('id'));

        $datos = [
            'encuesta_id' => $encuesta_id,
            'tipo'        => $request->get_param('tipo'),
            'etiqueta'    => $request->get_param('etiqueta'),
            'descripcion' => $request->get_param('descripcion'),
            'opciones'    => $request->get_param('opciones'),
            'es_requerido'=> $request->get_param('es_requerido'),
            'orden'       => $request->get_param('orden'),
        ];

        $resultado = $this->module->crear_campo($datos);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success'  => true,
            'campo_id' => $resultado,
            'message'  => __('Campo agregado', 'flavor-platform'),
        ]);
    }

    /**
     * Responder encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function responder_encuesta($request) {
        $encuesta_id = absint($request->get_param('id'));
        $respuestas = $request->get_param('respuestas');

        $resultado = $this->module->registrar_respuestas($encuesta_id, $respuestas);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        $response = [
            'success' => true,
            'message' => __('Gracias por tu respuesta', 'flavor-platform'),
        ];

        // Incluir resultados si el usuario puede verlos
        if ($this->module->puede_ver_resultados($encuesta_id)) {
            $response['resultados'] = $this->module->obtener_resultados($encuesta_id);
        }

        return rest_ensure_response($response);
    }

    /**
     * Obtener resultados
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function obtener_resultados($request) {
        $encuesta_id = absint($request->get_param('id'));
        $resultados = $this->module->obtener_resultados($encuesta_id);

        return rest_ensure_response([
            'success' => true,
            'data'    => $resultados,
        ]);
    }

    /**
     * Cerrar encuesta
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function cerrar_encuesta($request) {
        $encuesta_id = absint($request->get_param('id'));
        $resultado = $this->module->cerrar_encuesta($encuesta_id);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Encuesta cerrada', 'flavor-platform'),
        ]);
    }

    /**
     * Listar encuestas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function listar_encuestas($request) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas';
        $estado = $request->get_param('estado');
        $tipo = $request->get_param('tipo');
        $limit = absint($request->get_param('limit'));
        $offset = absint($request->get_param('offset'));

        $where = "WHERE 1=1";
        $valores = [];

        // Solo mostrar públicas a usuarios no autenticados
        if (!is_user_logged_in()) {
            $where .= " AND estado = 'activa'";
        } elseif ($estado) {
            $where .= " AND estado = %s";
            $valores[] = $estado;
        }

        if ($tipo) {
            $where .= " AND tipo = %s";
            $valores[] = $tipo;
        }

        $sql = "SELECT * FROM {$tabla} {$where} ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d";
        $valores[] = $limit;
        $valores[] = $offset;

        $encuestas = $wpdb->get_results($wpdb->prepare($sql, $valores));

        $data = array_map([$this, 'preparar_encuesta_response'], $encuestas);

        return rest_ensure_response([
            'success' => true,
            'data'    => $data,
            'total'   => count($data),
        ]);
    }

    /**
     * Listar por contexto
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function listar_por_contexto($request) {
        $contexto_tipo = sanitize_text_field($request->get_param('tipo'));
        $contexto_id = absint($request->get_param('contexto_id'));

        $args = [
            'estado' => $request->get_param('estado'),
            'limit'  => $request->get_param('limit'),
            'offset' => $request->get_param('offset'),
        ];

        $encuestas = $this->module->listar_por_contexto($contexto_tipo, $contexto_id, $args);

        $data = array_map([$this, 'preparar_encuesta_response'], $encuestas);

        return rest_ensure_response([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Verificar participación
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function verificar_participacion($request) {
        $encuesta_id = absint($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Obtener sesión ID de cookie si existe
        $sesion_id = isset($_COOKIE['flavor_encuestas_sid'])
            ? sanitize_text_field($_COOKIE['flavor_encuestas_sid'])
            : null;

        $ya_participo = $this->module->usuario_ya_participo($encuesta_id, $usuario_id, $sesion_id);

        return rest_ensure_response([
            'success'      => true,
            'ya_participo' => $ya_participo,
        ]);
    }

    // =========================================================================
    // PERMISOS
    // =========================================================================

    /**
     * Verificar permiso de creación
     */
    public function check_create_permission($request) {
        return is_user_logged_in();
    }

    /**
     * Lectura pública explícita.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function public_read_permission($request) {
        return true;
    }

    /**
     * Permite leer encuestas visibles públicamente o editables por el usuario.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function can_read_survey($request) {
        $encuesta_id = absint($request->get_param('id'));
        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            return false;
        }

        return $encuesta->estado !== 'borrador' || $this->module->puede_editar_encuesta($encuesta_id);
    }

    /**
     * Permite responder solo encuestas activas o visibles por edición.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function can_answer_survey($request) {
        $encuesta_id = absint($request->get_param('id'));
        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            return false;
        }

        return $encuesta->estado === 'activa' || $this->module->puede_editar_encuesta($encuesta_id);
    }

    /**
     * Permite comprobar participación solo sobre encuestas visibles.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function can_check_participation($request) {
        return $this->can_read_survey($request);
    }

    /**
     * Verificar permiso de edición
     */
    public function check_edit_permission($request) {
        $encuesta_id = absint($request->get_param('id'));
        return $this->module->puede_editar_encuesta($encuesta_id);
    }

    /**
     * Verificar permiso de ver resultados
     */
    public function check_view_results_permission($request) {
        $encuesta_id = absint($request->get_param('id'));
        return $this->module->puede_ver_resultados($encuesta_id);
    }

    // =========================================================================
    // ARGUMENTOS
    // =========================================================================

    /**
     * Argumentos para crear encuesta
     */
    private function get_crear_args() {
        return [
            'titulo' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'descripcion' => [
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'tipo' => [
                'type'    => 'string',
                'default' => 'encuesta',
                'enum'    => ['encuesta', 'formulario', 'quiz'],
            ],
            'contexto_tipo' => [
                'type' => 'string',
            ],
            'contexto_id' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'es_anonima' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'permite_multiples' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'mostrar_resultados' => [
                'type'    => 'string',
                'default' => 'al_votar',
                'enum'    => ['siempre', 'al_votar', 'al_cerrar', 'nunca'],
            ],
            'fecha_cierre' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'campos' => [
                'type'    => 'array',
                'default' => [],
            ],
            'estado' => [
                'type'    => 'string',
                'default' => 'activa',
                'enum'    => ['borrador', 'activa'],
            ],
        ];
    }

    /**
     * Argumentos para actualizar encuesta
     */
    private function get_actualizar_args() {
        return [
            'titulo' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'descripcion' => [
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'tipo' => [
                'type' => 'string',
                'enum' => ['encuesta', 'formulario', 'quiz'],
            ],
            'es_anonima' => [
                'type' => 'boolean',
            ],
            'permite_multiples' => [
                'type' => 'boolean',
            ],
            'mostrar_resultados' => [
                'type' => 'string',
                'enum' => ['siempre', 'al_votar', 'al_cerrar', 'nunca'],
            ],
            'fecha_cierre' => [
                'type' => 'string',
            ],
            'estado' => [
                'type' => 'string',
                'enum' => ['borrador', 'activa', 'cerrada', 'archivada'],
            ],
        ];
    }

    /**
     * Argumentos para listar
     */
    private function get_listar_args() {
        return [
            'estado' => [
                'type' => 'string',
                'enum' => ['borrador', 'activa', 'cerrada', 'archivada', ''],
            ],
            'tipo' => [
                'type' => 'string',
                'enum' => ['encuesta', 'formulario', 'quiz', ''],
            ],
            'limit' => [
                'type'              => 'integer',
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ],
            'offset' => [
                'type'              => 'integer',
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * Argumentos para campo
     */
    private function get_campo_args() {
        return [
            'tipo' => [
                'required' => true,
                'type'     => 'string',
                'enum'     => array_keys(Flavor_Chat_Encuestas_Module::TIPOS_CAMPO),
            ],
            'etiqueta' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'descripcion' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'opciones' => [
                'type'    => 'array',
                'default' => [],
            ],
            'es_requerido' => [
                'type'    => 'boolean',
                'default' => true,
            ],
            'orden' => [
                'type'              => 'integer',
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Prepara una encuesta para respuesta
     *
     * @param object $encuesta
     * @return array
     */
    private function preparar_encuesta_response($encuesta) {
        if (!$encuesta) {
            return null;
        }

        $datos = [
            'id'                  => (int) $encuesta->id,
            'titulo'              => $encuesta->titulo,
            'descripcion'         => $encuesta->descripcion,
            'autor_id'            => (int) $encuesta->autor_id,
            'estado'              => $encuesta->estado,
            'tipo'                => $encuesta->tipo,
            'contexto_tipo'       => $encuesta->contexto_tipo,
            'contexto_id'         => $encuesta->contexto_id ? (int) $encuesta->contexto_id : null,
            'es_anonima'          => (bool) $encuesta->es_anonima,
            'permite_multiples'   => (bool) $encuesta->permite_multiples,
            'mostrar_resultados'  => $encuesta->mostrar_resultados,
            'fecha_cierre'        => $encuesta->fecha_cierre,
            'total_respuestas'    => (int) $encuesta->total_respuestas,
            'total_participantes' => (int) $encuesta->total_participantes,
            'fecha_creacion'      => $encuesta->fecha_creacion,
        ];

        // Incluir campos si están disponibles
        if (isset($encuesta->campos)) {
            $datos['campos'] = array_map(function($campo) {
                return [
                    'id'           => (int) $campo->id,
                    'tipo'         => $campo->tipo,
                    'etiqueta'     => $campo->etiqueta,
                    'descripcion'  => $campo->descripcion,
                    'opciones'     => $campo->opciones,
                    'es_requerido' => (bool) $campo->es_requerido,
                    'orden'        => (int) $campo->orden,
                ];
            }, $encuesta->campos);
        }

        // Agregar info del autor
        $autor = get_userdata($encuesta->autor_id);
        if ($autor) {
            $datos['autor'] = [
                'id'     => (int) $autor->ID,
                'nombre' => $autor->display_name,
                'avatar' => get_avatar_url($autor->ID, ['size' => 48]),
            ];
        }

        return $datos;
    }
}

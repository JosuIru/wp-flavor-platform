<?php
/**
 * API REST para el módulo Bug Tracker
 *
 * @package Flavor_Chat_IA
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona la API REST del Bug Tracker
 */
class Flavor_Bug_Tracker_API {

    /**
     * Instancia del módulo principal
     *
     * @var Flavor_Bug_Tracker_Module
     */
    private $modulo;

    /**
     * Namespace de la API
     *
     * @var string
     */
    private $namespace = 'flavor/v1';

    /**
     * Constructor
     *
     * @param Flavor_Bug_Tracker_Module $modulo Instancia del módulo
     */
    public function __construct(Flavor_Bug_Tracker_Module $modulo) {
        $this->modulo = $modulo;
    }

    /**
     * Registra las rutas de la API
     *
     * @return void
     */
    public function registrar_rutas() {
        // Listar y crear bugs
        register_rest_route($this->namespace, '/bugs', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'listar_bugs'],
                'permission_callback' => [$this, 'verificar_permisos_lectura'],
                'args' => $this->obtener_args_listar(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'crear_bug'],
                'permission_callback' => [$this, 'verificar_permisos_escritura'],
                'args' => $this->obtener_args_crear(),
            ],
        ]);

        // Obtener, actualizar y eliminar bug específico
        register_rest_route($this->namespace, '/bugs/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'obtener_bug'],
                'permission_callback' => [$this, 'verificar_permisos_lectura'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'actualizar_bug'],
                'permission_callback' => [$this, 'verificar_permisos_escritura'],
                'args' => $this->obtener_args_actualizar(),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'eliminar_bug'],
                'permission_callback' => [$this, 'verificar_permisos_admin'],
            ],
        ]);

        // Resolver bug
        register_rest_route($this->namespace, '/bugs/(?P<id>\d+)/resolve', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'resolver_bug'],
            'permission_callback' => [$this, 'verificar_permisos_escritura'],
            'args' => [
                'notas' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Notas sobre la resolución',
                ],
            ],
        ]);

        // Ignorar bug
        register_rest_route($this->namespace, '/bugs/(?P<id>\d+)/ignore', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'ignorar_bug'],
            'permission_callback' => [$this, 'verificar_permisos_escritura'],
            'args' => [
                'notas' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Razón para ignorar',
                ],
            ],
        ]);

        // Reabrir bug
        register_rest_route($this->namespace, '/bugs/(?P<id>\d+)/reopen', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'reabrir_bug'],
            'permission_callback' => [$this, 'verificar_permisos_escritura'],
        ]);

        // Estadísticas
        register_rest_route($this->namespace, '/bugs/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'obtener_estadisticas'],
            'permission_callback' => [$this, 'verificar_permisos_lectura'],
        ]);

        // Canales
        register_rest_route($this->namespace, '/bugs/channels', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'listar_canales'],
                'permission_callback' => [$this, 'verificar_permisos_admin'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'crear_canal'],
                'permission_callback' => [$this, 'verificar_permisos_admin'],
                'args' => $this->obtener_args_canal(),
            ],
        ]);

        register_rest_route($this->namespace, '/bugs/channels/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'actualizar_canal'],
                'permission_callback' => [$this, 'verificar_permisos_admin'],
                'args' => $this->obtener_args_canal(),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'eliminar_canal'],
                'permission_callback' => [$this, 'verificar_permisos_admin'],
            ],
        ]);

        // Probar canal
        register_rest_route($this->namespace, '/bugs/channels/(?P<id>\d+)/test', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'probar_canal'],
            'permission_callback' => [$this, 'verificar_permisos_admin'],
        ]);
    }

    /**
     * Verifica permisos de lectura
     *
     * @return bool
     */
    public function verificar_permisos_lectura() {
        return current_user_can('flavor_view_bugs') || current_user_can('manage_options');
    }

    /**
     * Verifica permisos de escritura
     *
     * @return bool
     */
    public function verificar_permisos_escritura() {
        return current_user_can('flavor_manage_bugs') || current_user_can('manage_options');
    }

    /**
     * Verifica permisos de administrador
     *
     * @return bool
     */
    public function verificar_permisos_admin() {
        return current_user_can('flavor_configure_bug_channels') || current_user_can('manage_options');
    }

    /**
     * Lista bugs con filtros
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function listar_bugs($request) {
        $args = [
            'estado' => $request->get_param('estado'),
            'severidad' => $request->get_param('severidad'),
            'tipo' => $request->get_param('tipo'),
            'modulo_id' => $request->get_param('modulo_id'),
            'busqueda' => $request->get_param('busqueda'),
            'orderby' => $request->get_param('orderby') ?: 'ultima_ocurrencia',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 20),
        ];

        $resultado = $this->modulo->listar_bugs($args);

        $response = new WP_REST_Response($resultado['bugs'], 200);
        $response->header('X-WP-Total', $resultado['total']);
        $response->header('X-WP-TotalPages', $resultado['paginas']);

        return $response;
    }

    /**
     * Obtiene un bug específico
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function obtener_bug($request) {
        $bug = $this->modulo->obtener_bug($request->get_param('id'));

        if (!$bug) {
            return new WP_Error('not_found', 'Bug no encontrado', ['status' => 404]);
        }

        return new WP_REST_Response($bug, 200);
    }

    /**
     * Crea un bug manual
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function crear_bug($request) {
        $bug_id = $this->modulo->reportar_bug_manual(
            $request->get_param('titulo'),
            $request->get_param('mensaje'),
            [
                'severidad' => $request->get_param('severidad') ?: 'medium',
                'modulo_id' => $request->get_param('modulo_id'),
                'contexto_extra' => $request->get_param('contexto_extra'),
            ]
        );

        if (!$bug_id) {
            return new WP_Error('create_failed', 'Error al crear el bug', ['status' => 500]);
        }

        $bug = $this->modulo->obtener_bug($bug_id);
        return new WP_REST_Response($bug, 201);
    }

    /**
     * Actualiza un bug
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function actualizar_bug($request) {
        $bug_id = $request->get_param('id');
        $bug = $this->modulo->obtener_bug($bug_id);

        if (!$bug) {
            return new WP_Error('not_found', 'Bug no encontrado', ['status' => 404]);
        }

        $estado = $request->get_param('estado');
        $notas = $request->get_param('notas');

        if ($estado) {
            $resultado = $this->modulo->actualizar_estado_bug($bug_id, $estado, $notas);
            if (!$resultado) {
                return new WP_Error('update_failed', 'Error al actualizar el bug', ['status' => 500]);
            }
        }

        $bug_actualizado = $this->modulo->obtener_bug($bug_id);
        return new WP_REST_Response($bug_actualizado, 200);
    }

    /**
     * Elimina un bug
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function eliminar_bug($request) {
        global $wpdb;

        $bug_id = $request->get_param('id');
        $bug = $this->modulo->obtener_bug($bug_id);

        if (!$bug) {
            return new WP_Error('not_found', 'Bug no encontrado', ['status' => 404]);
        }

        $resultado = $wpdb->delete($this->modulo->get_tabla_bugs(), ['id' => $bug_id]);

        if ($resultado === false) {
            return new WP_Error('delete_failed', 'Error al eliminar el bug', ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true, 'id' => $bug_id], 200);
    }

    /**
     * Resuelve un bug
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function resolver_bug($request) {
        $bug_id = $request->get_param('id');
        $notas = $request->get_param('notas');

        $resultado = $this->modulo->actualizar_estado_bug($bug_id, 'resuelto', $notas);

        if (!$resultado) {
            return new WP_Error('resolve_failed', 'Error al resolver el bug', ['status' => 500]);
        }

        $bug = $this->modulo->obtener_bug($bug_id);
        return new WP_REST_Response($bug, 200);
    }

    /**
     * Ignora un bug
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function ignorar_bug($request) {
        $bug_id = $request->get_param('id');
        $notas = $request->get_param('notas');

        $resultado = $this->modulo->actualizar_estado_bug($bug_id, 'ignorado', $notas);

        if (!$resultado) {
            return new WP_Error('ignore_failed', 'Error al ignorar el bug', ['status' => 500]);
        }

        $bug = $this->modulo->obtener_bug($bug_id);
        return new WP_REST_Response($bug, 200);
    }

    /**
     * Reabre un bug
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function reabrir_bug($request) {
        $bug_id = $request->get_param('id');

        $resultado = $this->modulo->actualizar_estado_bug($bug_id, 'abierto', 'Bug reabierto');

        if (!$resultado) {
            return new WP_Error('reopen_failed', 'Error al reabrir el bug', ['status' => 500]);
        }

        $bug = $this->modulo->obtener_bug($bug_id);
        return new WP_REST_Response($bug, 200);
    }

    /**
     * Obtiene estadísticas
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function obtener_estadisticas($request) {
        $estadisticas = $this->modulo->obtener_estadisticas();
        return new WP_REST_Response($estadisticas, 200);
    }

    /**
     * Lista canales de notificación
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function listar_canales($request) {
        $channels = $this->modulo->get_channels();
        if (!$channels) {
            return new WP_REST_Response([], 200);
        }

        $canales = $channels->obtener_canales();
        return new WP_REST_Response($canales, 200);
    }

    /**
     * Crea un canal
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function crear_canal($request) {
        $channels = $this->modulo->get_channels();
        if (!$channels) {
            return new WP_Error('channels_not_available', 'Sistema de canales no disponible', ['status' => 500]);
        }

        $canal_id = $channels->crear_canal([
            'nombre' => $request->get_param('nombre'),
            'tipo' => $request->get_param('tipo'),
            'webhook_url' => $request->get_param('webhook_url'),
            'email_destinatarios' => $request->get_param('email_destinatarios'),
            'severidad_minima' => $request->get_param('severidad_minima'),
            'tipos_incluidos' => $request->get_param('tipos_incluidos'),
            'modulos_incluidos' => $request->get_param('modulos_incluidos'),
            'activo' => $request->get_param('activo'),
        ]);

        if (!$canal_id) {
            return new WP_Error('create_failed', 'Error al crear el canal', ['status' => 500]);
        }

        $canal = $channels->obtener_canal($canal_id);
        return new WP_REST_Response($canal, 201);
    }

    /**
     * Actualiza un canal
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function actualizar_canal($request) {
        $channels = $this->modulo->get_channels();
        if (!$channels) {
            return new WP_Error('channels_not_available', 'Sistema de canales no disponible', ['status' => 500]);
        }

        $canal_id = $request->get_param('id');
        $canal = $channels->obtener_canal($canal_id);

        if (!$canal) {
            return new WP_Error('not_found', 'Canal no encontrado', ['status' => 404]);
        }

        $datos = [];
        $campos = ['nombre', 'webhook_url', 'email_destinatarios', 'severidad_minima', 'tipos_incluidos', 'modulos_incluidos', 'activo'];

        foreach ($campos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos[$campo] = $valor;
            }
        }

        $resultado = $channels->actualizar_canal($canal_id, $datos);

        if (!$resultado) {
            return new WP_Error('update_failed', 'Error al actualizar el canal', ['status' => 500]);
        }

        $canal_actualizado = $channels->obtener_canal($canal_id);
        return new WP_REST_Response($canal_actualizado, 200);
    }

    /**
     * Elimina un canal
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function eliminar_canal($request) {
        $channels = $this->modulo->get_channels();
        if (!$channels) {
            return new WP_Error('channels_not_available', 'Sistema de canales no disponible', ['status' => 500]);
        }

        $canal_id = $request->get_param('id');
        $resultado = $channels->eliminar_canal($canal_id);

        if (!$resultado) {
            return new WP_Error('delete_failed', 'Error al eliminar el canal', ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true, 'id' => $canal_id], 200);
    }

    /**
     * Prueba un canal
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function probar_canal($request) {
        $channels = $this->modulo->get_channels();
        if (!$channels) {
            return new WP_Error('channels_not_available', 'Sistema de canales no disponible', ['status' => 500]);
        }

        $canal_id = $request->get_param('id');
        $resultado = $channels->probar_canal($canal_id);

        return new WP_REST_Response([
            'success' => $resultado,
            'message' => $resultado ? 'Mensaje de prueba enviado correctamente' : 'Error al enviar mensaje de prueba',
        ], $resultado ? 200 : 500);
    }

    /**
     * Argumentos para listar bugs
     *
     * @return array
     */
    private function obtener_args_listar() {
        return [
            'estado' => [
                'type' => 'string',
                'enum' => ['nuevo', 'abierto', 'resuelto', 'ignorado'],
            ],
            'severidad' => [
                'type' => 'string',
                'enum' => ['critical', 'high', 'medium', 'low', 'info'],
            ],
            'tipo' => [
                'type' => 'string',
                'enum' => ['error_php', 'exception', 'warning', 'notice', 'manual', 'crash', 'deprecation'],
            ],
            'modulo_id' => [
                'type' => 'string',
            ],
            'busqueda' => [
                'type' => 'string',
            ],
            'orderby' => [
                'type' => 'string',
                'default' => 'ultima_ocurrencia',
            ],
            'order' => [
                'type' => 'string',
                'enum' => ['ASC', 'DESC'],
                'default' => 'DESC',
            ],
            'page' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100,
            ],
        ];
    }

    /**
     * Argumentos para crear bug
     *
     * @return array
     */
    private function obtener_args_crear() {
        return [
            'titulo' => [
                'type' => 'string',
                'required' => true,
                'minLength' => 1,
                'maxLength' => 500,
            ],
            'mensaje' => [
                'type' => 'string',
                'required' => true,
            ],
            'severidad' => [
                'type' => 'string',
                'enum' => ['critical', 'high', 'medium', 'low', 'info'],
                'default' => 'medium',
            ],
            'modulo_id' => [
                'type' => 'string',
            ],
            'contexto_extra' => [
                'type' => 'object',
            ],
        ];
    }

    /**
     * Argumentos para actualizar bug
     *
     * @return array
     */
    private function obtener_args_actualizar() {
        return [
            'estado' => [
                'type' => 'string',
                'enum' => ['nuevo', 'abierto', 'resuelto', 'ignorado'],
            ],
            'notas' => [
                'type' => 'string',
            ],
        ];
    }

    /**
     * Argumentos para canal
     *
     * @return array
     */
    private function obtener_args_canal() {
        return [
            'nombre' => [
                'type' => 'string',
                'required' => true,
                'minLength' => 1,
                'maxLength' => 100,
            ],
            'tipo' => [
                'type' => 'string',
                'enum' => ['slack', 'discord', 'email', 'webhook'],
                'required' => true,
            ],
            'webhook_url' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'email_destinatarios' => [
                'type' => 'string',
            ],
            'severidad_minima' => [
                'type' => 'string',
                'enum' => ['critical', 'high', 'medium', 'low', 'info'],
                'default' => 'high',
            ],
            'tipos_incluidos' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'modulos_incluidos' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'activo' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }
}

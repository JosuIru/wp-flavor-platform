<?php
/**
 * API REST para Banco de Tiempo (Móvil)
 *
 * Endpoints optimizados para aplicaciones móviles
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API REST para módulo Banco de Tiempo
 */
class Flavor_Banco_Tiempo_API {

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
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // GET /banco-tiempo/servicios - Lista servicios disponibles
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicios', [
            'methods' => 'GET',
            'callback' => [$this, 'get_servicios'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'busqueda' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'pagina' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // POST /banco-tiempo/servicios - Crear nuevo servicio
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicios', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_servicio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'categoria' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'horas_estimadas' => [
                    'required' => true,
                    'type' => 'number',
                ],
            ],
        ]);

        // GET /banco-tiempo/mis-servicios - Servicios del usuario
        register_rest_route(self::NAMESPACE, '/banco-tiempo/mis-servicios', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_servicios'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'default' => 'todos',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /banco-tiempo/saldo - Saldo de horas del usuario
        register_rest_route(self::NAMESPACE, '/banco-tiempo/saldo', [
            'methods' => 'GET',
            'callback' => [$this, 'get_saldo'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /banco-tiempo/transacciones - Historial de transacciones
        register_rest_route(self::NAMESPACE, '/banco-tiempo/transacciones', [
            'methods' => 'GET',
            'callback' => [$this, 'get_transacciones'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'tipo' => [
                    'type' => 'string',
                    'default' => 'todos',
                    'enum' => ['todos', 'recibidas', 'ofrecidas'],
                ],
                'estado' => [
                    'type' => 'string',
                    'default' => 'todos',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // POST /banco-tiempo/servicios/{id}/solicitar - Solicitar servicio
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicios/(?P<id>\d+)/solicitar', [
            'methods' => 'POST',
            'callback' => [$this, 'solicitar_servicio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'mensaje' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'fecha_preferida' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /banco-tiempo/transacciones/{id}/completar - Completar intercambio
        register_rest_route(self::NAMESPACE, '/banco-tiempo/transacciones/(?P<id>\d+)/completar', [
            'methods' => 'POST',
            'callback' => [$this, 'completar_transaccion'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'horas_reales' => [
                    'required' => true,
                    'type' => 'number',
                ],
                'valoracion' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                ],
                'comentario' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // DELETE /banco-tiempo/servicios/{id} - Eliminar servicio
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicios/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'eliminar_servicio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // PUT /banco-tiempo/servicio/{id} - Actualizar servicio (singular para apps)
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicio/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_servicio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'titulo' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'duracion_horas' => [
                    'type' => 'number',
                ],
                'tipo' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum' => ['ofrezco', 'necesito'],
                ],
            ],
        ]);

        // POST /banco-tiempo/servicio - Crear servicio (singular para apps, alias de /servicios)
        register_rest_route(self::NAMESPACE, '/banco-tiempo/servicio', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_servicio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'categoria' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'duracion_horas' => [
                    'required' => true,
                    'type' => 'number',
                ],
                'tipo' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum' => ['ofrezco', 'necesito'],
                ],
            ],
        ]);

        // GET /banco-tiempo/categorias - Lista de categorías
        register_rest_route(self::NAMESPACE, '/banco-tiempo/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * GET /banco-tiempo/servicios
     * Lista servicios disponibles
     */
    public function get_servicios($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $busqueda = $request->get_param('busqueda');
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite');
        $pagina = $request->get_param('pagina');
        $offset = ($pagina - 1) * $limite;

        $where = ["estado = 'activo'"];
        $valores = [];

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $valores[] = $busqueda_like;
            $valores[] = $busqueda_like;
        }

        if (!empty($categoria) && $categoria !== 'todos') {
            $where[] = "categoria = %s";
            $valores[] = $categoria;
        }

        $sql_where = implode(' AND ', $where);

        // Contar total
        $total = $wpdb->get_var(
            !empty($valores)
                ? $wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE $sql_where", ...$valores)
                : "SELECT COUNT(*) FROM $tabla WHERE $sql_where"
        );

        // Obtener servicios
        $valores[] = $limite;
        $valores[] = $offset;
        $sql = "SELECT * FROM $tabla WHERE $sql_where ORDER BY fecha_publicacion DESC LIMIT %d OFFSET %d";
        $servicios = $wpdb->get_results($wpdb->prepare($sql, ...$valores));

        $servicios_formateados = array_map([$this, 'formatear_servicio'], $servicios);

        $respuesta = [
            'success' => true,
            'servicios' => $servicios_formateados,
            'total' => (int) $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total / $limite),
        ];

        return new WP_REST_Response($this->sanitize_public_banco_tiempo_response($respuesta), 200);
    }

    /**
     * POST /banco-tiempo/servicios
     * Crear nuevo servicio
     */
    public function crear_servicio($request) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error(
                'no_auth',
                'Debes iniciar sesión',
                ['status' => 401]
            );
        }

        $titulo = $request->get_param('titulo');
        $descripcion = $request->get_param('descripcion');
        $categoria = $request->get_param('categoria');
        $horas_estimadas = floatval($request->get_param('horas_estimadas'));

        // Validaciones
        if (empty($titulo) || empty($descripcion)) {
            return new WP_Error(
                'datos_incompletos',
                'Título y descripción son obligatorios',
                ['status' => 400]
            );
        }

        if ($horas_estimadas <= 0 || $horas_estimadas > 24) {
            return new WP_Error(
                'horas_invalidas',
                'Las horas deben estar entre 0.1 y 24',
                ['status' => 400]
            );
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $resultado = $wpdb->insert(
            $tabla,
            [
                'usuario_id' => $usuario_id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'horas_estimadas' => $horas_estimadas,
                'estado' => 'activo',
                'fecha_publicacion' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error(
                'error_crear',
                'Error al crear el servicio',
                ['status' => 500]
            );
        }

        $servicio_id = $wpdb->insert_id;
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $servicio_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'servicio' => $this->formatear_servicio($servicio),
            'mensaje' => __('Servicio publicado con éxito', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * GET /banco-tiempo/mis-servicios
     * Servicios del usuario
     */
    public function get_mis_servicios($request) {
        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $where = "usuario_id = %d";
        $valores = [$usuario_id];

        if ($estado !== 'todos') {
            $where .= " AND estado = %s";
            $valores[] = $estado;
        }

        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE $where ORDER BY fecha_publicacion DESC",
            ...$valores
        ));

        return new WP_REST_Response([
            'success' => true,
            'servicios' => array_map([$this, 'formatear_servicio'], $servicios),
            'total' => count($servicios),
        ], 200);
    }

    /**
     * GET /banco-tiempo/saldo
     * Saldo de horas del usuario
     */
    public function get_saldo($request) {
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Horas ganadas (servicios prestados)
        $horas_ganadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(horas), 0) FROM $tabla
            WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        // Horas gastadas (servicios recibidos)
        $horas_gastadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(horas), 0) FROM $tabla
            WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $saldo = $horas_ganadas - $horas_gastadas;

        // Transacciones pendientes
        $pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla
            WHERE (usuario_receptor_id = %d OR usuario_solicitante_id = %d)
            AND estado IN ('pendiente', 'aceptado')",
            $usuario_id,
            $usuario_id
        ));

        // Servicios activos
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $servicios_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_servicios
            WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'saldo' => [
                'horas_ganadas' => $horas_ganadas,
                'horas_gastadas' => $horas_gastadas,
                'saldo_actual' => $saldo,
                'pendientes' => $pendientes,
                'servicios_activos' => $servicios_activos,
            ],
        ], 200);
    }

    /**
     * GET /banco-tiempo/transacciones
     * Historial de transacciones
     */
    public function get_transacciones($request) {
        $usuario_id = get_current_user_id();
        $tipo = $request->get_param('tipo');
        $estado = $request->get_param('estado');
        $limite = $request->get_param('limite');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $where = [];
        $valores = [];

        // Filtro por tipo
        if ($tipo === 'recibidas') {
            $where[] = "usuario_solicitante_id = %d";
            $valores[] = $usuario_id;
        } elseif ($tipo === 'ofrecidas') {
            $where[] = "usuario_receptor_id = %d";
            $valores[] = $usuario_id;
        } else {
            $where[] = "(usuario_receptor_id = %d OR usuario_solicitante_id = %d)";
            $valores[] = $usuario_id;
            $valores[] = $usuario_id;
        }

        // Filtro por estado
        if ($estado !== 'todos') {
            $where[] = "estado = %s";
            $valores[] = $estado;
        }

        $sql_where = implode(' AND ', $where);
        $valores[] = $limite;

        $transacciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE $sql_where ORDER BY fecha_solicitud DESC LIMIT %d",
            ...$valores
        ));

        $transacciones_formateadas = array_map(function($t) use ($usuario_id) {
            return $this->formatear_transaccion($t, $usuario_id);
        }, $transacciones);

        return new WP_REST_Response([
            'success' => true,
            'transacciones' => $transacciones_formateadas,
            'total' => count($transacciones_formateadas),
        ], 200);
    }

    /**
     * POST /banco-tiempo/servicios/{id}/solicitar
     * Solicitar un servicio
     */
    public function solicitar_servicio($request) {
        $usuario_id = get_current_user_id();
        $servicio_id = $request->get_param('id');
        $mensaje = $request->get_param('mensaje');
        $fecha_preferida = $request->get_param('fecha_preferida');

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Verificar que el servicio existe y está activo
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_servicios WHERE id = %d AND estado = 'activo'",
            $servicio_id
        ));

        if (!$servicio) {
            return new WP_Error(
                'servicio_no_encontrado',
                'El servicio no existe o ya no está disponible',
                ['status' => 404]
            );
        }

        // No puedes solicitar tu propio servicio
        if ($servicio->usuario_id == $usuario_id) {
            return new WP_Error(
                'servicio_propio',
                'No puedes solicitar tu propio servicio',
                ['status' => 400]
            );
        }

        // Crear transacción
        $resultado = $wpdb->insert(
            $tabla_transacciones,
            [
                'servicio_id' => $servicio_id,
                'usuario_solicitante_id' => $usuario_id,
                'usuario_receptor_id' => $servicio->usuario_id,
                'horas' => $servicio->horas_estimadas,
                'mensaje' => $mensaje,
                'fecha_preferida' => $fecha_preferida,
                'estado' => 'pendiente',
                'fecha_solicitud' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error(
                'error_solicitar',
                'Error al solicitar el servicio',
                ['status' => 500]
            );
        }

        $transaccion_id = $wpdb->insert_id;
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d",
            $transaccion_id
        ));

        $receptor = get_user_by('id', $servicio->usuario_id);
        if ($receptor && !empty($receptor->user_email)) {
            $asunto = sprintf(__('Nueva solicitud en Banco de Tiempo', 'flavor-chat-ia'));
            $contenido = sprintf(
                __("Hola %s,\n\nHas recibido una nueva solicitud para tu servicio \"%s\".\n\nMensaje:\n%s\n\nFecha preferida: %s\n", 'flavor-chat-ia'),
                $receptor->display_name,
                $servicio->titulo,
                $mensaje,
                $fecha_preferida ?: __('No indicada', 'flavor-chat-ia')
            );
            wp_mail($receptor->user_email, $asunto, $contenido);
        }

        return new WP_REST_Response([
            'success' => true,
            'transaccion' => $this->formatear_transaccion($transaccion, $usuario_id),
            'mensaje' => __('horas_reales', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * POST /banco-tiempo/transacciones/{id}/completar
     * Completar un intercambio
     */
    public function completar_transaccion($request) {
        $usuario_id = get_current_user_id();
        $transaccion_id = $request->get_param('id');
        $horas_reales = floatval($request->get_param('horas_reales'));
        $valoracion = $request->get_param('valoracion');
        $comentario = $request->get_param('comentario');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Verificar que la transacción existe
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $transaccion_id
        ));

        if (!$transaccion) {
            return new WP_Error(
                'transaccion_no_encontrada',
                'La transacción no existe',
                ['status' => 404]
            );
        }

        // Solo el receptor puede completar
        if ($transaccion->usuario_receptor_id != $usuario_id) {
            return new WP_Error(
                'sin_permiso',
                'Solo el receptor puede completar la transacción',
                ['status' => 403]
            );
        }

        // Actualizar transacción
        $wpdb->update(
            $tabla,
            [
                'horas' => $horas_reales,
                'valoracion' => $valoracion,
                'comentario' => $comentario,
                'estado' => 'completado',
                'fecha_completado' => current_time('mysql'),
            ],
            ['id' => $transaccion_id],
            ['%f', '%d', '%s', '%s', '%s'],
            ['%d']
        );

        // Hook para otros procesos
        do_action('flavor_banco_tiempo_transaccion_completada', $transaccion_id, $horas_reales);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Servicio publicado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * DELETE /banco-tiempo/servicios/{id}
     * Eliminar servicio
     */
    public function eliminar_servicio($request) {
        $usuario_id = get_current_user_id();
        $servicio_id = $request->get_param('id');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        // Verificar que es el dueño
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND usuario_id = %d",
            $servicio_id,
            $usuario_id
        ));

        if (!$servicio) {
            return new WP_Error(
                'sin_permiso',
                'No tienes permiso para eliminar este servicio',
                ['status' => 403]
            );
        }

        // Marcar como inactivo en lugar de eliminar
        $wpdb->update(
            $tabla,
            ['estado' => 'inactivo'],
            ['id' => $servicio_id],
            ['%s'],
            ['%d']
        );

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Educación', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * GET /banco-tiempo/categorias
     * Lista de categorías
     */
    public function get_categorias($request) {
        $categorias = [
            ['id' => 'cuidados', 'nombre' => __('Cuidados', 'flavor-chat-ia'), 'icon' => 'favorite'],
            ['id' => 'educacion', 'nombre' => __('Educación', 'flavor-chat-ia'), 'icon' => 'school'],
            ['id' => 'bricolaje', 'nombre' => __('Bricolaje', 'flavor-chat-ia'), 'icon' => 'build'],
            ['id' => 'tecnologia', 'nombre' => __('Tecnología', 'flavor-chat-ia'), 'icon' => 'computer'],
            ['id' => 'transporte', 'nombre' => __('Transporte', 'flavor-chat-ia'), 'icon' => 'directions_car'],
            ['id' => 'otros', 'nombre' => __('Otros', 'flavor-chat-ia'), 'icon' => 'more_horiz'],
        ];

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias,
        ], 200);
    }

    /**
     * Formatea un servicio para la respuesta
     */
    private function formatear_servicio($servicio) {
        if (!$servicio) {
            return null;
        }

        $usuario = get_userdata($servicio->usuario_id);

        return [
            'id' => (int) $servicio->id,
            'titulo' => $servicio->titulo,
            'descripcion' => $servicio->descripcion,
            'categoria' => $servicio->categoria,
            'horas_estimadas' => (float) $servicio->horas_estimadas,
            'estado' => $servicio->estado,
            'fecha_publicacion' => mysql2date('c', $servicio->fecha_publicacion),
            'usuario' => [
                'id' => (int) $servicio->usuario_id,
                'nombre' => $usuario ? $usuario->display_name : 'Usuario',
                'email' => $usuario ? $usuario->user_email : '',
            ],
        ];
    }

    /**
     * Formatea una transacción para la respuesta
     */
    private function formatear_transaccion($transaccion, $usuario_actual_id) {
        if (!$transaccion) {
            return null;
        }

        $solicitante = get_userdata($transaccion->usuario_solicitante_id);
        $receptor = get_userdata($transaccion->usuario_receptor_id);

        $es_receptor = $transaccion->usuario_receptor_id == $usuario_actual_id;

        return [
            'id' => (int) $transaccion->id,
            'servicio_id' => (int) $transaccion->servicio_id,
            'horas' => (float) $transaccion->horas,
            'estado' => $transaccion->estado,
            'mensaje' => $transaccion->mensaje,
            'fecha_preferida' => $transaccion->fecha_preferida,
            'fecha_solicitud' => mysql2date('c', $transaccion->fecha_solicitud),
            'fecha_completado' => $transaccion->fecha_completado ? mysql2date('c', $transaccion->fecha_completado) : null,
            'valoracion' => $transaccion->valoracion ? (int) $transaccion->valoracion : null,
            'comentario' => $transaccion->comentario,
            'tipo' => $es_receptor ? 'ofrecida' : 'recibida',
            'solicitante' => [
                'id' => (int) $transaccion->usuario_solicitante_id,
                'nombre' => $solicitante ? $solicitante->display_name : 'Usuario',
            ],
            'receptor' => [
                'id' => (int) $transaccion->usuario_receptor_id,
                'nombre' => $receptor ? $receptor->display_name : 'Usuario',
            ],
        ];
    }

    /**
     * Verifica autenticación
     */
    public function check_authentication($request) {
        return is_user_logged_in();
    }

    private function sanitize_public_banco_tiempo_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['servicios']) && is_array($respuesta['servicios'])) {
            $respuesta['servicios'] = array_map([$this, 'sanitize_public_servicio'], $respuesta['servicios']);
        }

        if (!empty($respuesta['servicio']) && is_array($respuesta['servicio'])) {
            $respuesta['servicio'] = $this->sanitize_public_servicio($respuesta['servicio']);
        }

        return $respuesta;
    }

    private function sanitize_public_servicio($servicio) {
        if (!is_array($servicio)) {
            return $servicio;
        }

        if (!empty($servicio['usuario']) && is_array($servicio['usuario'])) {
            unset($servicio['usuario']['id'], $servicio['usuario']['email']);
        }

        return $servicio;
    }

    /**
     * PUT /banco-tiempo/servicio/{id}
     * Actualizar un servicio existente
     */
    public function actualizar_servicio($request) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error(
                'no_auth',
                'Debes iniciar sesión',
                ['status' => 401]
            );
        }

        $servicio_id = $request->get_param('id');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        // Verificar que el servicio existe y pertenece al usuario
        $servicio_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $servicio_id
        ));

        if (!$servicio_existente) {
            return new WP_Error(
                'servicio_no_encontrado',
                'Servicio no encontrado',
                ['status' => 404]
            );
        }

        if ((int) $servicio_existente->usuario_id !== $usuario_id) {
            return new WP_Error(
                'no_autorizado',
                'No tienes permisos para editar este servicio',
                ['status' => 403]
            );
        }

        // Preparar datos a actualizar
        $datos_actualizar = [];
        $formatos = [];

        if ($request->has_param('titulo')) {
            $datos_actualizar['titulo'] = sanitize_text_field($request->get_param('titulo'));
            $formatos[] = '%s';
        }

        if ($request->has_param('descripcion')) {
            $datos_actualizar['descripcion'] = sanitize_textarea_field($request->get_param('descripcion'));
            $formatos[] = '%s';
        }

        if ($request->has_param('categoria')) {
            $datos_actualizar['categoria'] = sanitize_text_field($request->get_param('categoria'));
            $formatos[] = '%s';
        }

        if ($request->has_param('duracion_horas')) {
            $horas = floatval($request->get_param('duracion_horas'));
            if ($horas > 0 && $horas <= 24) {
                $datos_actualizar['horas_estimadas'] = $horas;
                $formatos[] = '%f';
            }
        }

        if ($request->has_param('tipo')) {
            $tipo = sanitize_text_field($request->get_param('tipo'));
            if (in_array($tipo, ['ofrezco', 'necesito'])) {
                $datos_actualizar['tipo'] = $tipo;
                $formatos[] = '%s';
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error(
                'sin_cambios',
                'No se enviaron datos para actualizar',
                ['status' => 400]
            );
        }

        // Actualizar
        $resultado = $wpdb->update(
            $tabla,
            $datos_actualizar,
            ['id' => $servicio_id],
            $formatos,
            ['%d']
        );

        if ($resultado === false) {
            return new WP_Error(
                'error_actualizar',
                'Error al actualizar el servicio',
                ['status' => 500]
            );
        }

        // Obtener servicio actualizado
        $servicio_actualizado = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $servicio_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'servicio' => $this->formatear_servicio($servicio_actualizado),
            'mensaje' => __('Servicio actualizado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

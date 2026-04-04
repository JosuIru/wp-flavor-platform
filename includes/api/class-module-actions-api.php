<?php
/**
 * REST API Universal para Acciones de Módulos
 *
 * Expone todas las acciones de todos los módulos vía REST API
 * para uso desde formularios frontend, apps móviles, etc.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para API REST de acciones de módulos
 */
class Flavor_Module_Actions_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor/v1';

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
     * Registra las rutas REST
     */
    public function register_routes() {
        // Ruta genérica: POST /flavor/v1/modules/{module_id}/actions/{action_name}
        register_rest_route(self::NAMESPACE, '/modules/(?P<module_id>[a-zA-Z0-9_-]+)/actions/(?P<action_name>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'execute_module_action'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'module_id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'action_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Ruta para obtener información de un módulo
        register_rest_route(self::NAMESPACE, '/modules/(?P<module_id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_module_info'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'module_id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Ruta para listar todos los módulos activos
        register_rest_route(self::NAMESPACE, '/modules', [
            'methods' => 'GET',
            'callback' => [$this, 'list_modules'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Permisos publicos con rate limit
     */
    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Verifica permisos para ejecutar acciones
     */
    public function check_permissions($request) {
        $module_id = $request->get_param('module_id');
        $action_name = $request->get_param('action_name');

        // Acciones públicas - no requieren login
        $acciones_publicas = apply_filters('flavor_public_actions', [
            // Talleres - listados públicos
            'talleres_disponibles',
            'detalle_taller',
            // Grupos de consumo - listados públicos
            'listar_productos',
            'ciclo_actual',
            // Marketplace - listados públicos
            'listar_anuncios',
            'ver_anuncio',
            'buscar_anuncios',
            // Eventos - listados públicos
            'listar_eventos',
            'eventos_proximos',
            'ver_evento',
            'detalle_evento',
            // Foros - listados públicos
            'listar_foros',
            'listar_temas',
            'ver_hilo',
            'listar_hilos',
            // Incidencias - listados y reportes públicos
            'listar_incidencias',
            'ver_incidencia',
            'mapa_incidencias',
            'reportar_incidencia',
            // Participación - propuestas públicas
            'listar_propuestas',
            'ver_propuesta',
            // Presupuestos participativos - información pública
            'info_edicion_actual',
            'listar_proyectos',
            'ver_proyecto',
            'resultados',
            'seguimiento_proyecto',
            // Avisos municipales - información pública
            'listar_avisos',
            'ver_aviso',
            'avisos_urgentes',
            // Publicidad - contenido público
            'listar_anuncios_publicitarios',
            'ver_estadisticas_publicas',
            // Bares - listados públicos
            'listar_bares',
            'ver_bar',
            'buscar_bares',
            // Banco de tiempo - listados públicos
            'listar_servicios',
            'ver_servicio',
            // Biblioteca - catálogo público
            'listar_libros',
            'buscar_libros',
            'ver_libro',
            // Carpooling - viajes públicos
            'listar_viajes',
            'buscar_viajes',
            'ver_viaje',
            // Chat grupos - grupos públicos
            'grupos_publicos',
            // Colectivos - listados públicos
            'listar_colectivos',
            'ver_colectivo',
            // Comunidades - listados públicos
            'listar_comunidades',
            'ver_comunidad',
            // Compostaje - información pública
            'listar_composteras',
            'estadisticas_compostaje',
            // Cursos - catálogo público
            'listar_cursos',
            'ver_curso',
            // Espacios comunes - listado público
            'listar_espacios',
            'ver_espacio',
            // Huertos urbanos - información pública
            'listar_huertos',
            'ver_huerto',
            // Multimedia - galería pública
            'listar_multimedia',
            'ver_multimedia',
            // Parkings - disponibilidad pública
            'listar_parkings',
            'ver_parking',
            // Podcast - contenido público
            'listar_episodios',
            'ver_episodio',
            // Radio - programación pública
            'listar_programas',
            'ver_programa',
            'programacion',
            // Reciclaje - información pública
            'listar_puntos_reciclaje',
            'estadisticas_reciclaje',
            // Red social - perfiles públicos
            'listar_publicaciones',
            'ver_publicacion',
            'ver_perfil',
            // Tienda local - productos públicos
            'listar_productos_tienda',
            'ver_producto',
            // Tramites - información pública
            'listar_tramites',
            'ver_tramite',
            // Transparencia - datos públicos
            'listar_documentos',
            'ver_documento',
            'datos_abiertos',
            // Bicicletas compartidas - disponibilidad pública
            'listar_bicicletas',
            'ver_bicicleta',
            'mapa_bicicletas',
        ]);

        $es_publica = in_array($action_name, $acciones_publicas, true);

        // Rate limit siempre en acciones públicas
        if ($es_publica) {
            $rate_limit = Flavor_API_Rate_Limiter::check_rate_limit('post');
            if (is_wp_error($rate_limit)) {
                return $rate_limit;
            }
        }

        if (!$es_publica && !is_user_logged_in()) {
            return new WP_Error(
                'unauthorized',
                __('Debes iniciar sesión para realizar esta acción', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        // Para acciones no públicas, permitir auth móvil por token/bearer
        // y exigir nonce solo en contextos web tradicionales.
        if (!$es_publica) {
            $authorization = $request->get_header('Authorization');
            $has_bearer_auth = is_string($authorization) && stripos($authorization, 'Bearer ') === 0;
            $has_app_token = (bool) $request->get_header('X-Flavor-Token');

            if ($has_bearer_auth || $has_app_token) {
                return true;
            }

            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'invalid_nonce',
                    __('Nonce inválido', 'flavor-chat-ia'),
                    ['status' => 403]
                );
            }
        }

        // Acciones solo para administradores
        $acciones_admin = [
            // Facturas - gestión administrativa
            'listar_facturas',
            'ver_factura',
            'crear_factura',
            'actualizar_estado',
            'enviar_email',
            'estadisticas',
            'buscar_facturas',
            // Clientes - CRM administrativo
            'listar_clientes',
            'ver_cliente',
            'crear_cliente',
            'actualizar_cliente',
            'buscar_clientes',
            'estadisticas_clientes',
            'clientes_por_estado',
            // Socios - gestión administrativa
            'listar_socios',
            'dar_alta_socio',
            'dar_baja_socio',
            'estadisticas_socios',
            // Avisos - creación administrativa
            'crear_aviso',
            'estadisticas_aviso',
            // Publicidad - gestión administrativa
            'crear_campana',
            'pausar_campana',
            'estadisticas_campana',
        ];

        if (in_array($action_name, $acciones_admin) && !current_user_can('manage_options')) {
            return new WP_REST_Response([
                'success' => false,
                'error' => __('No tienes permisos de administrador para esta acción.', 'flavor-chat-ia'),
            ], 403);
        }

        return true;
    }

    /**
     * Ejecuta una acción de módulo
     */
    public function execute_module_action($request) {
        $module_id = $request->get_param('module_id');
        $action_name = $request->get_param('action_name');
        $parametros = $request->get_json_params() ?: [];

        // Obtener el módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($module_id);

        if (!$module) {
            return new WP_Error(
                'module_not_found',
                sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $module_id),
                ['status' => 404]
            );
        }

        // Verificar que la acción existe
        $actions = $module->get_actions();
        if (!isset($actions[$action_name])) {
            return new WP_Error(
                'action_not_found',
                sprintf(__('Acción no encontrada: %s', 'flavor-chat-ia'), $action_name),
                ['status' => 404]
            );
        }

        // Validar parámetros
        $validation_result = $this->validate_params($module, $action_name, $parametros);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        // Sanitizar parámetros
        $parametros_sanitizados = $this->sanitize_params($parametros);

        // Ejecutar la acción
        try {
            $resultado = $module->execute_action($action_name, $parametros_sanitizados);

            // Si el resultado ya es un WP_Error, devolverlo
            if (is_wp_error($resultado)) {
                return $resultado;
            }

            // Normalizar respuesta
            if (!isset($resultado['success'])) {
                $resultado = [
                    'success' => true,
                    'data' => $resultado,
                ];
            }

            // Si la acción fue exitosa, devolver respuesta formateada
            if ($resultado['success']) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => $resultado['message'] ?? $resultado['mensaje'] ?? __('Acción ejecutada correctamente', 'flavor-chat-ia'),
                    'data' => $resultado['data'] ?? $resultado,
                    'redirect_url' => $resultado['redirect_url'] ?? null,
                ], 200);
            } else {
                return new WP_Error(
                    'action_failed',
                    $resultado['error'] ?? $resultado['mensaje'] ?? __('Error al ejecutar la acción', 'flavor-chat-ia'),
                    ['status' => 400]
                );
            }
        } catch (Exception $e) {
            flavor_chat_ia_log('Error ejecutando acción ' . $action_name . ': ' . $e->getMessage(), 'error');

            return new WP_Error(
                'execution_error',
                __('Error interno al ejecutar la acción', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }
    }

    /**
     * Valida parámetros de la acción
     */
    private function validate_params($module, $action_name, $params) {
        // Si el módulo tiene método get_form_config, usar para validación
        if (method_exists($module, 'get_form_config')) {
            $form_config = $module->get_form_config($action_name);

            if (!empty($form_config['fields'])) {
                $errores = [];

                foreach ($form_config['fields'] as $field_name => $field_config) {
                    // Verificar campos requeridos
                    if (!empty($field_config['required']) && empty($params[$field_name])) {
                        $label = $field_config['label'] ?? $field_name;
                        $errores[] = sprintf(__('%s es obligatorio', 'flavor-chat-ia'), $label);
                    }

                    // Validaciones específicas por tipo
                    if (!empty($params[$field_name])) {
                        $valor = $params[$field_name];
                        $tipo = $field_config['type'] ?? 'text';

                        switch ($tipo) {
                            case 'email':
                                if (!is_email($valor)) {
                                    $errores[] = sprintf(__('%s debe ser un email válido', 'flavor-chat-ia'), $field_config['label'] ?? $field_name);
                                }
                                break;

                            case 'number':
                                if (!is_numeric($valor)) {
                                    $errores[] = sprintf(__('%s debe ser un número', 'flavor-chat-ia'), $field_config['label'] ?? $field_name);
                                }
                                // Validar min/max
                                if (isset($field_config['min']) && $valor < $field_config['min']) {
                                    $errores[] = sprintf(__('%s debe ser mayor o igual a %s', 'flavor-chat-ia'), $field_config['label'] ?? $field_name, $field_config['min']);
                                }
                                if (isset($field_config['max']) && $valor > $field_config['max']) {
                                    $errores[] = sprintf(__('%s debe ser menor o igual a %s', 'flavor-chat-ia'), $field_config['label'] ?? $field_name, $field_config['max']);
                                }
                                break;

                            case 'url':
                                if (!filter_var($valor, FILTER_VALIDATE_URL)) {
                                    $errores[] = sprintf(__('%s debe ser una URL válida', 'flavor-chat-ia'), $field_config['label'] ?? $field_name);
                                }
                                break;
                        }
                    }
                }

                if (!empty($errores)) {
                    return new WP_Error('validation_failed', implode(', ', $errores), ['status' => 400]);
                }
            }
        }

        return true;
    }

    /**
     * Sanitiza parámetros recursivamente
     */
    private function sanitize_params($params) {
        $sanitizados = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $sanitizados[$key] = $this->sanitize_params($value);
            } else {
                // Sanitizar según el tipo de dato
                if (is_numeric($value)) {
                    $sanitizados[$key] = floatval($value);
                } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $sanitizados[$key] = sanitize_email($value);
                } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                    $sanitizados[$key] = esc_url_raw($value);
                } else {
                    $sanitizados[$key] = sanitize_text_field($value);
                }
            }
        }

        return $sanitizados;
    }

    /**
     * Obtiene información de un módulo
     *
     * Para usuarios no autenticados, solo devuelve info basica (nombre, descripcion, estado).
     * Para administradores autenticados, incluye tambien las acciones disponibles.
     */
    public function get_module_info($request) {
        $module_id = $request->get_param('module_id');

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($module_id);

        if (!$module) {
            return new WP_Error(
                'module_not_found',
                sprintf(__('Módulo no encontrado: %s', 'flavor-chat-ia'), $module_id),
                ['status' => 404]
            );
        }

        // Respuesta basica publica: solo nombre, descripcion y estado activo
        $datos_respuesta_modulo = [
            'id'          => $module_id,
            'name'        => $module->get_name(),
            'description' => $module->get_description(),
            'active'      => true,
        ];

        // Exponer acciones y formularios a usuarios autenticados para apps móviles.
        if (is_user_logged_in()) {
            $datos_respuesta_modulo['actions'] = $module->get_actions();

            if (method_exists($module, 'get_form_config')) {
                $form_configs = [];
                foreach ($module->get_actions() as $action_name => $action_config) {
                    $form_config = $module->get_form_config($action_name);
                    if (!empty($form_config['fields'])) {
                        $form_configs[$action_name] = $form_config;
                    }
                }

                if (!empty($form_configs)) {
                    $datos_respuesta_modulo['form_configs'] = $form_configs;
                }
            }
        }

        return new WP_REST_Response($datos_respuesta_modulo, 200);
    }

    /**
     * Lista todos los módulos activos
     *
     * Devuelve informacion basica publica: id, nombre, descripcion y estado.
     */
    public function list_modules($request) {
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_cargados = $loader->get_loaded_modules();

        $lista_modulos_publicos = [];
        foreach ($modulos_cargados as $identificador_modulo => $instancia_modulo) {
            $lista_modulos_publicos[] = [
                'id'          => $identificador_modulo,
                'name'        => $instancia_modulo->get_name(),
                'description' => $instancia_modulo->get_description(),
                'active'      => true,
            ];
        }

        return new WP_REST_Response([
            'modules' => $lista_modulos_publicos,
            'total'   => count($lista_modulos_publicos),
        ], 200);
    }
}

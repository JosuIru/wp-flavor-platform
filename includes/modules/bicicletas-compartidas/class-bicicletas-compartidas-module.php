<?php
/**
 * Módulo de Bicicletas Compartidas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Bicicletas Compartidas - Sistema de bici-sharing comunitario
 */
class Flavor_Chat_Bicicletas_Compartidas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'bicicletas_compartidas';
        $this->name = 'Bicicletas Compartidas'; // Translation loaded on init
        $this->description = 'Sistema de bicicletas compartidas gestionado por la comunidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Bicicletas Compartidas no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_fianza' => true,
            'importe_fianza' => 50,
            'precio_hora' => 0,
            'precio_dia' => 0,
            'precio_mes' => 10,
            'duracion_maxima_prestamo_dias' => 7,
            'permite_reservas' => true,
            'horas_anticipacion_reserva' => 2,
            'requiere_verificacion_usuario' => true,
            'notificar_mantenimiento' => true,
            'permite_reportar_problemas' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en panel unificado de gestión
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar bicicletas disponibles
        register_rest_route($namespace, '/bicicletas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_bicicletas'],
            'permission_callback' => '__return_true',
            'args' => [
                'estacion_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la estación para filtrar bicicletas',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['urbana', 'montana', 'electrica', 'infantil', 'carga'],
                    'description' => 'Tipo de bicicleta',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['disponible', 'en_uso', 'mantenimiento', 'reservada'],
                    'default' => 'disponible',
                ],
            ],
        ]);

        // Listar estaciones
        register_rest_route($namespace, '/bicicletas/estaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_estaciones'],
            'permission_callback' => '__return_true',
            'args' => [
                'lat' => [
                    'type' => 'number',
                    'description' => 'Latitud para buscar estaciones cercanas',
                ],
                'lng' => [
                    'type' => 'number',
                    'description' => 'Longitud para buscar estaciones cercanas',
                ],
                'radio_km' => [
                    'type' => 'integer',
                    'default' => 5,
                    'description' => 'Radio en kilómetros para la búsqueda',
                ],
            ],
        ]);

        // Obtener una bicicleta específica
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_bicicleta'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta',
                ],
            ],
        ]);

        // Reservar bicicleta (iniciar préstamo)
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)/reservar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_reservar_bicicleta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta a reservar',
                ],
            ],
        ]);

        // Devolver bicicleta (finalizar préstamo)
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)/devolver', [
            'methods' => 'POST',
            'callback' => [$this, 'api_devolver_bicicleta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta a devolver',
                ],
                'estacion_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la estación donde se devuelve',
                ],
                'kilometros' => [
                    'type' => 'number',
                    'description' => 'Kilómetros recorridos',
                ],
                'incidencias' => [
                    'type' => 'string',
                    'description' => 'Incidencias o problemas detectados',
                ],
                'valoracion' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                    'description' => 'Valoración del servicio (1-5)',
                ],
            ],
        ]);

        // Mis reservas/préstamos
        register_rest_route($namespace, '/bicicletas/mis-reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_reservas'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'enum' => ['activo', 'finalizado', 'todos'],
                    'default' => 'todos',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);
    }

    /**
     * Verifica que el usuario esté autenticado
     */
    public function verificar_usuario_autenticado() {
        return is_user_logged_in();
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar bicicletas disponibles
     */
    public function api_listar_bicicletas($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $condiciones_where = [];
        $valores_preparados = [];

        // Filtro por estado
        $estado = $request->get_param('estado') ?: 'disponible';
        $condiciones_where[] = 'b.estado = %s';
        $valores_preparados[] = $estado;

        // Filtro por estación
        $estacion_id = $request->get_param('estacion_id');
        if ($estacion_id) {
            $condiciones_where[] = 'b.estacion_actual_id = %d';
            $valores_preparados[] = absint($estacion_id);
        }

        // Filtro por tipo
        $tipo = $request->get_param('tipo');
        if ($tipo) {
            $condiciones_where[] = 'b.tipo = %s';
            $valores_preparados[] = sanitize_text_field($tipo);
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        $consulta_sql = "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion
            FROM $tabla_bicicletas b
            LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
            WHERE $clausula_where
            ORDER BY b.codigo ASC
            LIMIT 100";

        $bicicletas = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        $bicicletas_formateadas = array_map(function($bicicleta) {
            return [
                'id' => (int) $bicicleta->id,
                'codigo' => $bicicleta->codigo,
                'tipo' => $bicicleta->tipo,
                'marca' => $bicicleta->marca,
                'modelo' => $bicicleta->modelo,
                'color' => $bicicleta->color,
                'talla' => $bicicleta->talla,
                'estado' => $bicicleta->estado,
                'kilometros_acumulados' => (int) $bicicleta->kilometros_acumulados,
                'foto_url' => $bicicleta->foto_url,
                'equipamiento' => $bicicleta->equipamiento ? json_decode($bicicleta->equipamiento, true) : null,
                'estacion' => $bicicleta->estacion_actual_id ? [
                    'id' => (int) $bicicleta->estacion_actual_id,
                    'nombre' => $bicicleta->estacion_nombre,
                    'direccion' => $bicicleta->estacion_direccion,
                ] : null,
            ];
        }, $bicicletas);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($bicicletas_formateadas),
            'bicicletas' => $bicicletas_formateadas,
        ], 200);
    }

    /**
     * API: Listar estaciones
     */
    public function api_listar_estaciones($request) {
        $resultado = $this->action_estaciones([
            'lat' => $request->get_param('lat'),
            'lng' => $request->get_param('lng'),
            'radio_km' => $request->get_param('radio_km') ?: 5,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error'] ?? 'Error al obtener estaciones'], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener una bicicleta específica
     */
    public function api_obtener_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $bicicleta_id = absint($request->get_param('id'));

        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion, e.latitud, e.longitud
            FROM $tabla_bicicletas b
            LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
            WHERE b.id = %d",
            $bicicleta_id
        ));

        if (!$bicicleta) {
            return new WP_REST_Response(['success' => false, 'error' => 'Bicicleta no encontrada'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'bicicleta' => [
                'id' => (int) $bicicleta->id,
                'codigo' => $bicicleta->codigo,
                'tipo' => $bicicleta->tipo,
                'marca' => $bicicleta->marca,
                'modelo' => $bicicleta->modelo,
                'color' => $bicicleta->color,
                'talla' => $bicicleta->talla,
                'estado' => $bicicleta->estado,
                'kilometros_acumulados' => (int) $bicicleta->kilometros_acumulados,
                'ultima_revision' => $bicicleta->ultima_revision,
                'foto_url' => $bicicleta->foto_url,
                'equipamiento' => $bicicleta->equipamiento ? json_decode($bicicleta->equipamiento, true) : null,
                'fecha_alta' => $bicicleta->fecha_alta,
                'estacion' => $bicicleta->estacion_actual_id ? [
                    'id' => (int) $bicicleta->estacion_actual_id,
                    'nombre' => $bicicleta->estacion_nombre,
                    'direccion' => $bicicleta->estacion_direccion,
                    'lat' => (float) $bicicleta->latitud,
                    'lng' => (float) $bicicleta->longitud,
                ] : null,
            ],
        ], 200);
    }

    /**
     * API: Reservar bicicleta (iniciar préstamo)
     */
    public function api_reservar_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        $bicicleta_id = absint($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Verificar que la bicicleta existe y está disponible
        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_bicicletas WHERE id = %d",
            $bicicleta_id
        ));

        if (!$bicicleta) {
            return new WP_REST_Response(['success' => false, 'error' => 'Bicicleta no encontrada'], 404);
        }

        if ($bicicleta->estado !== 'disponible') {
            return new WP_REST_Response([
                'success' => false,
                'error' => sprintf('La bicicleta no está disponible. Estado actual: %s', $bicicleta->estado)
            ], 400);
        }

        // Verificar que el usuario no tenga ya un préstamo activo
        $prestamo_activo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if ($prestamo_activo > 0) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Ya tienes un préstamo activo. Devuelve la bicicleta actual antes de reservar otra.'
            ], 400);
        }

        // Obtener configuración de fianza
        $requiere_fianza = $this->get_setting('requiere_fianza', true);
        $importe_fianza = $requiere_fianza ? $this->get_setting('importe_fianza', 50) : 0;

        // Crear el préstamo
        $resultado_insercion = $wpdb->insert($tabla_prestamos, [
            'bicicleta_id' => $bicicleta_id,
            'usuario_id' => $usuario_id,
            'estacion_salida_id' => $bicicleta->estacion_actual_id,
            'fecha_inicio' => current_time('mysql'),
            'fianza' => $importe_fianza,
            'estado' => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ], ['%d', '%d', '%d', '%s', '%f', '%s', '%s']);

        if ($resultado_insercion === false) {
            return new WP_REST_Response(['success' => false, 'error' => 'Error al crear el préstamo'], 500);
        }

        $prestamo_id = $wpdb->insert_id;

        // Actualizar estado de la bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            ['estado' => 'en_uso', 'estacion_actual_id' => null],
            ['id' => $bicicleta_id],
            ['%s', '%d'],
            ['%d']
        );

        // Actualizar contador de bicicletas en la estación
        if ($bicicleta->estacion_actual_id) {
            $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = GREATEST(0, bicicletas_disponibles - 1) WHERE id = %d",
                $bicicleta->estacion_actual_id
            ));
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => sprintf('Bicicleta %s reservada correctamente', $bicicleta->codigo),
            'prestamo' => [
                'id' => $prestamo_id,
                'bicicleta_id' => $bicicleta_id,
                'bicicleta_codigo' => $bicicleta->codigo,
                'fecha_inicio' => current_time('mysql'),
                'fianza' => $importe_fianza,
                'estado' => 'activo',
            ],
        ], 201);
    }

    /**
     * API: Devolver bicicleta (finalizar préstamo)
     */
    public function api_devolver_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $bicicleta_id = absint($request->get_param('id'));
        $estacion_id = absint($request->get_param('estacion_id'));
        $kilometros = floatval($request->get_param('kilometros') ?: 0);
        $incidencias = sanitize_textarea_field($request->get_param('incidencias') ?: '');
        $valoracion = absint($request->get_param('valoracion') ?: 0);
        $usuario_id = get_current_user_id();

        // Verificar que existe un préstamo activo para esta bicicleta y usuario
        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE bicicleta_id = %d AND usuario_id = %d AND estado = 'activo'",
            $bicicleta_id,
            $usuario_id
        ));

        if (!$prestamo) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'No tienes un préstamo activo para esta bicicleta'
            ], 400);
        }

        // Verificar que la estación existe
        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_estaciones WHERE id = %d AND estado = 'activa'",
            $estacion_id
        ));

        if (!$estacion) {
            return new WP_REST_Response(['success' => false, 'error' => 'Estación no encontrada o no disponible'], 404);
        }

        // Verificar capacidad de la estación
        if ($estacion->bicicletas_disponibles >= $estacion->capacidad_total) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'La estación está llena. Por favor, elige otra estación.'
            ], 400);
        }

        // Calcular duración del préstamo
        $fecha_inicio = strtotime($prestamo->fecha_inicio);
        $fecha_fin = current_time('timestamp');
        $duracion_minutos = round(($fecha_fin - $fecha_inicio) / 60);

        // Calcular coste (si aplica)
        $precio_hora = $this->get_setting('precio_hora', 0);
        $coste_total = $precio_hora > 0 ? ($duracion_minutos / 60) * $precio_hora : 0;

        // Actualizar el préstamo
        $wpdb->update(
            $tabla_prestamos,
            [
                'estacion_llegada_id' => $estacion_id,
                'fecha_fin' => current_time('mysql'),
                'duracion_minutos' => $duracion_minutos,
                'kilometros_recorridos' => $kilometros,
                'coste_total' => $coste_total,
                'incidencias' => $incidencias,
                'valoracion' => $valoracion > 0 && $valoracion <= 5 ? $valoracion : null,
                'fianza_devuelta' => 1,
                'estado' => 'finalizado',
            ],
            ['id' => $prestamo->id],
            ['%d', '%s', '%d', '%f', '%f', '%s', '%d', '%d', '%s'],
            ['%d']
        );

        // Determinar estado de la bicicleta
        $estado_bicicleta = !empty($incidencias) ? 'mantenimiento' : 'disponible';

        // Actualizar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            [
                'estado' => $estado_bicicleta,
                'estacion_actual_id' => $estacion_id,
                'kilometros_acumulados' => $wpdb->get_var($wpdb->prepare(
                    "SELECT kilometros_acumulados FROM $tabla_bicicletas WHERE id = %d",
                    $bicicleta_id
                )) + $kilometros,
            ],
            ['id' => $bicicleta_id],
            ['%s', '%d', '%d'],
            ['%d']
        );

        // Actualizar contador de bicicletas en la estación (solo si está disponible)
        if ($estado_bicicleta === 'disponible') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = bicicletas_disponibles + 1 WHERE id = %d",
                $estacion_id
            ));
        }

        // Formatear duración para mostrar
        $duracion_texto = $duracion_minutos < 60
            ? $duracion_minutos . ' minutos'
            : round($duracion_minutos / 60, 1) . ' horas';

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => sprintf('Bicicleta devuelta correctamente en %s', $estacion->nombre),
            'resumen' => [
                'prestamo_id' => $prestamo->id,
                'duracion' => $duracion_texto,
                'duracion_minutos' => $duracion_minutos,
                'kilometros' => $kilometros,
                'coste' => $coste_total,
                'fianza_devuelta' => $prestamo->fianza,
                'estacion_devolucion' => $estacion->nombre,
            ],
        ], 200);
    }

    /**
     * API: Obtener préstamos/reservas del usuario
     */
    public function api_mis_reservas($request) {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado') ?: 'todos';
        $limite = absint($request->get_param('limite') ?: 20);

        $condiciones_where = ['p.usuario_id = %d'];
        $valores_preparados = [$usuario_id];

        if ($estado === 'activo') {
            $condiciones_where[] = "p.estado = 'activo'";
        } elseif ($estado === 'finalizado') {
            $condiciones_where[] = "p.estado = 'finalizado'";
        }

        $clausula_where = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite;

        $consulta_sql = "SELECT p.*,
            b.codigo as bicicleta_codigo, b.tipo as bicicleta_tipo, b.marca, b.modelo,
            es.nombre as estacion_salida_nombre,
            el.nombre as estacion_llegada_nombre
            FROM $tabla_prestamos p
            LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
            LEFT JOIN $tabla_estaciones es ON p.estacion_salida_id = es.id
            LEFT JOIN $tabla_estaciones el ON p.estacion_llegada_id = el.id
            WHERE $clausula_where
            ORDER BY p.fecha_inicio DESC
            LIMIT %d";

        $prestamos = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        $prestamos_formateados = array_map(function($prestamo) {
            $duracion_texto = null;
            if ($prestamo->duracion_minutos) {
                $duracion_texto = $prestamo->duracion_minutos < 60
                    ? $prestamo->duracion_minutos . ' min'
                    : round($prestamo->duracion_minutos / 60, 1) . ' h';
            }

            return [
                'id' => (int) $prestamo->id,
                'bicicleta' => [
                    'id' => (int) $prestamo->bicicleta_id,
                    'codigo' => $prestamo->bicicleta_codigo,
                    'tipo' => $prestamo->bicicleta_tipo,
                    'marca_modelo' => trim($prestamo->marca . ' ' . $prestamo->modelo),
                ],
                'estacion_salida' => [
                    'id' => (int) $prestamo->estacion_salida_id,
                    'nombre' => $prestamo->estacion_salida_nombre,
                ],
                'estacion_llegada' => $prestamo->estacion_llegada_id ? [
                    'id' => (int) $prestamo->estacion_llegada_id,
                    'nombre' => $prestamo->estacion_llegada_nombre,
                ] : null,
                'fecha_inicio' => $prestamo->fecha_inicio,
                'fecha_fin' => $prestamo->fecha_fin,
                'duracion' => $duracion_texto,
                'duracion_minutos' => $prestamo->duracion_minutos ? (int) $prestamo->duracion_minutos : null,
                'kilometros' => $prestamo->kilometros_recorridos ? (float) $prestamo->kilometros_recorridos : null,
                'coste' => (float) $prestamo->coste_total,
                'fianza' => (float) $prestamo->fianza,
                'fianza_devuelta' => (bool) $prestamo->fianza_devuelta,
                'valoracion' => $prestamo->valoracion ? (int) $prestamo->valoracion : null,
                'estado' => $prestamo->estado,
                'incidencias' => $prestamo->incidencias,
            ];
        }, $prestamos);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($prestamos_formateados),
            'prestamos' => $prestamos_formateados,
        ], 200);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_mantenimiento = $wpdb->prefix . 'flavor_bicicletas_mantenimiento';

        $sql_bicicletas = "CREATE TABLE $tabla_bicicletas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            tipo varchar(20) DEFAULT 'urbana',
            marca varchar(100) DEFAULT NULL,
            modelo varchar(100) DEFAULT NULL,
            color varchar(50) DEFAULT NULL,
            talla varchar(5) DEFAULT 'M',
            estacion_actual_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            kilometros_acumulados int(11) DEFAULT 0,
            ultima_revision datetime DEFAULT NULL,
            proximo_mantenimiento_km int(11) DEFAULT 500,
            foto_url varchar(500) DEFAULT NULL,
            equipamiento text DEFAULT NULL,
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY codigo (codigo),
            KEY estacion_actual_id (estacion_actual_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_prestamos = "CREATE TABLE $tabla_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estacion_salida_id bigint(20) unsigned NOT NULL,
            estacion_llegada_id bigint(20) unsigned DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            kilometros_recorridos decimal(10,2) DEFAULT NULL,
            coste_total decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            incidencias text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_estaciones = "CREATE TABLE $tabla_estaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            capacidad_total int(11) NOT NULL,
            bicicletas_disponibles int(11) DEFAULT 0,
            tipo varchar(20) DEFAULT 'publica',
            horario_apertura time DEFAULT NULL,
            horario_cierre time DEFAULT NULL,
            servicios text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY latitud (latitud),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_mantenimiento = "CREATE TABLE $tabla_mantenimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            tipo varchar(20) DEFAULT 'revision',
            descripcion text NOT NULL,
            reportado_por bigint(20) unsigned DEFAULT NULL,
            tecnico_asignado bigint(20) unsigned DEFAULT NULL,
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            coste decimal(10,2) DEFAULT NULL,
            piezas_cambiadas text DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY estado (estado),
            KEY fecha_reporte (fecha_reporte)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_bicicletas);
        dbDelta($sql_prestamos);
        dbDelta($sql_estaciones);
        dbDelta($sql_mantenimiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'estaciones' => [
                'description' => 'Listar estaciones cercanas',
                'params' => ['lat', 'lng', 'radio_km'],
            ],
            'bicicletas_disponibles' => [
                'description' => 'Ver bicicletas disponibles en estación',
                'params' => ['estacion_id'],
            ],
            'iniciar_prestamo' => [
                'description' => 'Retirar bicicleta',
                'params' => ['bicicleta_id'],
            ],
            'finalizar_prestamo' => [
                'description' => 'Devolver bicicleta',
                'params' => ['prestamo_id', 'estacion_id', 'kilometros'],
            ],
            'mis_prestamos' => [
                'description' => 'Historial de préstamos',
                'params' => [],
            ],
            'reportar_problema' => [
                'description' => 'Reportar problema con bicicleta',
                'params' => ['bicicleta_id', 'descripcion'],
            ],
            'reservar_bicicleta' => [
                'description' => 'Reservar bicicleta',
                'params' => ['bicicleta_id', 'estacion_id', 'fecha_hora'],
            ],
            // Admin actions
            'estadisticas_uso' => [
                'description' => 'Estadísticas de uso (admin)',
                'params' => ['periodo'],
            ],
            'gestion_mantenimiento' => [
                'description' => 'Gestión de mantenimiento (admin)',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Listar estaciones
     */
    private function action_estaciones($params) {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $radio_km = absint($params['radio_km'] ?? 5);

        if ($lat == 0 || $lng == 0) {
            // Sin ubicación, devolver todas las estaciones activas
            $estaciones = $wpdb->get_results("SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre");
        } else {
            // Con ubicación, calcular distancia
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_estaciones
                    WHERE estado = 'activa'
                    HAVING distancia <= %d
                    ORDER BY distancia ASC";

            $estaciones = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat, $radio_km));
        }

        return [
            'success' => true,
            'estaciones' => array_map(function($e) {
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'lat' => floatval($e->latitud),
                    'lng' => floatval($e->longitud),
                    'bicicletas_disponibles' => $e->bicicletas_disponibles,
                    'capacidad_total' => $e->capacidad_total,
                    'distancia_km' => isset($e->distancia) ? round($e->distancia, 2) : null,
                ];
            }, $estaciones),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Predicción de disponibilidad en tiempo real
     * - Sugerencia de rutas optimizadas
     * - Recomendación de tipo de bici según destino
     */
    public function get_web_components() {
        return [
            'hero_bicis' => [
                'label' => __('Hero Bicicletas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Bicicletas Compartidas', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Movilidad sostenible y saludable', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_mapa_estaciones' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/hero',
            ],
            'mapa_estaciones' => [
                'label' => __('Mapa de Estaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'zoom_inicial' => ['type' => 'number', 'default' => 13],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/mapa',
            ],
            'tipos_bicicletas' => [
                'label' => __('Tipos de Bicicletas', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Elige tu Bicicleta', 'flavor-chat-ia')],
                    'mostrar_precios' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/tipos',
            ],
            'como_usar' => [
                'label' => __('Cómo Usar', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Cómo funciona?', 'flavor-chat-ia')],
                    'paso1' => ['type' => 'text', 'default' => __('Encuentra estación cercana', 'flavor-chat-ia')],
                    'paso2' => ['type' => 'text', 'default' => __('Escanea código QR', 'flavor-chat-ia')],
                    'paso3' => ['type' => 'text', 'default' => __('¡Pedalea!', 'flavor-chat-ia')],
                ],
                'template' => 'bicicletas/como-usar',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'bicicletas_estaciones',
                'description' => 'Ver estaciones de bicicletas cercanas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Bicicletas Compartidas**

Sistema de préstamo de bicicletas gestionado por la comunidad.

**Tipos de bicicletas:**
- Urbanas
- De montaña
- Eléctricas
- Infantiles
- De carga

**Cómo funciona:**
1. Encuentra estación cercana
2. Elige bicicleta disponible
3. Escanea código QR o introduce número
4. Disfruta tu viaje
5. Devuelve en cualquier estación

**Tarifas:**
- Gratis las primeras 2 horas
- Tarifa por hora después
- Abonos mensuales disponibles
- Fianza reembolsable

**Equipamiento incluido:**
- Casco obligatorio
- Candado de seguridad
- Luces delanteras y traseras
- Kit de herramientas básico (estaciones)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si la bicicleta se avería?',
                'respuesta' => 'Reporta el problema desde la app inmediatamente. No pagarás por el tiempo de avería.',
            ],
            [
                'pregunta' => '¿Puedo reservar una bicicleta?',
                'respuesta' => 'Sí, puedes reservar con hasta 2 horas de antelación.',
            ],
            [
                'pregunta' => '¿Dónde puedo devolverla?',
                'respuesta' => 'En cualquier estación con espacio disponible, no tiene que ser la misma.',
            ],
        ];
    }

    // =========================================================================
    // PANEL UNIFICADO DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Configuración para el panel unificado de gestión
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'bicicletas_compartidas',
            'label' => __('Bicicletas', 'flavor-chat-ia'),
            'icon' => 'dashicons-car',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'flavor-bicicletas-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-bicicletas-flota',
                    'titulo' => __('Flota', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_flota'],
                ],
                [
                    'slug' => 'flavor-bicicletas-estaciones',
                    'titulo' => __('Estaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_estaciones'],
                ],
                [
                    'slug' => 'flavor-bicicletas-prestamos',
                    'titulo' => __('Préstamos Activos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_prestamos'],
                    'badge' => [$this, 'contar_prestamos_activos'],
                ],
                [
                    'slug' => 'flavor-bicicletas-configuracion',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Obtiene estadísticas para el dashboard del panel unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        $bicicletas_disponibles = 0;
        $prestamos_activos = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $bicicletas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible'"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'"
            );
        }

        return [
            [
                'icon' => 'dashicons-car',
                'valor' => $bicicletas_disponibles,
                'label' => __('Bicis disponibles', 'flavor-chat-ia'),
                'color' => 'green',
                'enlace' => admin_url('admin.php?page=flavor-bicicletas-flota'),
            ],
            [
                'icon' => 'dashicons-migrate',
                'valor' => $prestamos_activos,
                'label' => __('Préstamos activos', 'flavor-chat-ia'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=flavor-bicicletas-prestamos'),
            ],
        ];
    }

    /**
     * Cuenta préstamos activos para el badge
     *
     * @return int
     */
    public function contar_prestamos_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'"
        );
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Estadísticas
        $total_bicicletas = 0;
        $bicicletas_disponibles = 0;
        $bicicletas_en_uso = 0;
        $bicicletas_mantenimiento = 0;
        $total_estaciones = 0;
        $prestamos_hoy = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $total_bicicletas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas");
            $bicicletas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible'");
            $bicicletas_en_uso = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'en_uso'");
            $bicicletas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'mantenimiento'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $total_estaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_estaciones WHERE estado = 'activa'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos_hoy = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE DATE(fecha_inicio) = %s",
                current_time('Y-m-d')
            ));
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Bicicletas Compartidas - Dashboard', 'flavor-chat-ia')); ?>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-car"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($total_bicicletas); ?></span>
                        <span class="stat-label"><?php _e('Total Bicicletas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card green">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_disponibles); ?></span>
                        <span class="stat-label"><?php _e('Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card blue">
                    <span class="dashicons dashicons-migrate"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_en_uso); ?></span>
                        <span class="stat-label"><?php _e('En Uso', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card orange">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_mantenimiento); ?></span>
                        <span class="stat-label"><?php _e('En Mantenimiento', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-location"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($total_estaciones); ?></span>
                        <span class="stat-label"><?php _e('Estaciones Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card purple">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($prestamos_hoy); ?></span>
                        <span class="stat-label"><?php _e('Préstamos Hoy', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-admin-section">
                <h2><?php _e('Accesos Rápidos', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-flota')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-car"></span>
                        <?php _e('Gestionar Flota', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="button">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Ver Estaciones', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-prestamos')); ?>" class="button">
                        <span class="dashicons dashicons-migrate"></span>
                        <?php _e('Préstamos Activos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de gestión de flota
     */
    public function render_admin_flota() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        $bicicletas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $bicicletas = $wpdb->get_results("SELECT * FROM $tabla_bicicletas ORDER BY codigo ASC");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $this->render_page_header(
                __('Gestión de Flota', 'flavor-chat-ia'),
                [
                    [
                        'label' => __('Añadir Bicicleta', 'flavor-chat-ia'),
                        'url' => '#',
                        'class' => 'button-primary',
                    ],
                ]
            );
            ?>

            <div class="flavor-admin-section">
                <?php if (empty($bicicletas)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay bicicletas registradas. Añade la primera bicicleta a la flota.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Código', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Marca/Modelo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Talla', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Km Acumulados', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bicicletas as $bicicleta): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($bicicleta->codigo); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst($bicicleta->tipo)); ?></td>
                                    <td><?php echo esc_html($bicicleta->marca . ' ' . $bicicleta->modelo); ?></td>
                                    <td><?php echo esc_html($bicicleta->talla); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($bicicleta->estado); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $bicicleta->estado))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(number_format($bicicleta->kilometros_acumulados)); ?> km</td>
                                    <td>
                                        <a href="#" class="button button-small"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de estaciones
     */
    public function render_admin_estaciones() {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $estaciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $estaciones = $wpdb->get_results("SELECT * FROM $tabla_estaciones ORDER BY nombre ASC");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $this->render_page_header(
                __('Estaciones de Bicicletas', 'flavor-chat-ia'),
                [
                    [
                        'label' => __('Añadir Estación', 'flavor-chat-ia'),
                        'url' => '#',
                        'class' => 'button-primary',
                    ],
                ]
            );
            ?>

            <div class="flavor-admin-section">
                <?php if (empty($estaciones)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay estaciones registradas. Configura la primera estación de bicicletas.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Dirección', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Capacidad', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Disponibles', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estaciones as $estacion): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($estacion->nombre); ?></strong></td>
                                    <td><?php echo esc_html($estacion->direccion); ?></td>
                                    <td><?php echo esc_html(ucfirst($estacion->tipo)); ?></td>
                                    <td><?php echo esc_html($estacion->capacidad_total); ?></td>
                                    <td><?php echo esc_html($estacion->bicicletas_disponibles); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estacion->estado); ?>">
                                            <?php echo esc_html(ucfirst($estacion->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#" class="button button-small"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de préstamos activos
     */
    public function render_admin_prestamos() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $prestamos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos = $wpdb->get_results("
                SELECT p.*, b.codigo as bicicleta_codigo, e.nombre as estacion_nombre, u.display_name as usuario_nombre
                FROM $tabla_prestamos p
                LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
                LEFT JOIN $tabla_estaciones e ON p.estacion_salida_id = e.id
                LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
                WHERE p.estado = 'activo'
                ORDER BY p.fecha_inicio DESC
            ");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Préstamos Activos', 'flavor-chat-ia')); ?>

            <div class="flavor-admin-section">
                <?php if (empty($prestamos)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay préstamos activos en este momento.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Bicicleta', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estación Salida', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Inicio', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Duración', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos as $prestamo):
                                $inicio = strtotime($prestamo->fecha_inicio);
                                $duracion_minutos = round((time() - $inicio) / 60);
                                $duracion_texto = $duracion_minutos < 60
                                    ? $duracion_minutos . ' min'
                                    : round($duracion_minutos / 60, 1) . ' h';
                            ?>
                                <tr>
                                    <td>#<?php echo esc_html($prestamo->id); ?></td>
                                    <td><strong><?php echo esc_html($prestamo->bicicleta_codigo); ?></strong></td>
                                    <td><?php echo esc_html($prestamo->usuario_nombre); ?></td>
                                    <td><?php echo esc_html($prestamo->estacion_nombre); ?></td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', $inicio)); ?></td>
                                    <td><?php echo esc_html($duracion_texto); ?></td>
                                    <td>
                                        <a href="#" class="button button-small"><?php _e('Finalizar', 'flavor-chat-ia'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_configuracion() {
        $configuracion = $this->get_settings();

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Configuración de Bicicletas', 'flavor-chat-ia')); ?>

            <div class="flavor-admin-section">
                <form method="post" action="">
                    <?php wp_nonce_field('flavor_bicicletas_config', 'flavor_bicicletas_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="requiere_fianza"><?php _e('Requiere Fianza', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="requiere_fianza" name="requiere_fianza" value="1"
                                    <?php checked($configuracion['requiere_fianza'] ?? true); ?>>
                                <p class="description"><?php _e('Solicitar fianza para préstamos de bicicletas.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="importe_fianza"><?php _e('Importe Fianza', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="importe_fianza" name="importe_fianza"
                                    value="<?php echo esc_attr($configuracion['importe_fianza'] ?? 50); ?>"
                                    min="0" step="0.01" class="small-text"> &euro;
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="duracion_maxima_prestamo_dias"><?php _e('Duración Máxima Préstamo', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="duracion_maxima_prestamo_dias" name="duracion_maxima_prestamo_dias"
                                    value="<?php echo esc_attr($configuracion['duracion_maxima_prestamo_dias'] ?? 7); ?>"
                                    min="1" class="small-text"> <?php _e('días', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="permite_reservas"><?php _e('Permitir Reservas', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="permite_reservas" name="permite_reservas" value="1"
                                    <?php checked($configuracion['permite_reservas'] ?? true); ?>>
                                <p class="description"><?php _e('Permitir a los usuarios reservar bicicletas con antelación.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="precio_mes"><?php _e('Precio Mensual', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="precio_mes" name="precio_mes"
                                    value="<?php echo esc_attr($configuracion['precio_mes'] ?? 10); ?>"
                                    min="0" step="0.01" class="small-text"> &euro;
                                <p class="description"><?php _e('Tarifa de abono mensual (0 = gratuito).', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="guardar_config" class="button button-primary">
                            <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('bicicletas_compartidas');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('bicicletas-compartidas');
        if (!$pagina && !get_option('flavor_bicicletas_compartidas_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['bicicletas_compartidas']);
            update_option('flavor_bicicletas_compartidas_pages_created', 1, false);
        }
    }

}

<?php
/**
 * Modulo de Eventos para Chat IA
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) { exit; }

class Flavor_Chat_Eventos_Module extends Flavor_Chat_Module_Base {

    protected $id = 'eventos';
    protected $name = 'Eventos y Calendario';
    protected $description = 'Gestion de eventos, calendario e inscripciones';

    public function __construct() { parent::__construct(); }

    public function can_activate() {
        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_eventos';
        return Flavor_Chat_Helpers::tabla_existe($nombre_tabla);
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Eventos no estan creadas. Activa el modulo.', 'flavor-chat-ia');
        }
        return '';
    }

    protected function get_default_settings() {
        return [
            'requiere_aprobacion' => false,
            'aforo_maximo_defecto' => 0,
            'permite_invitados' => true,
            'enviar_recordatorios' => true,
            'dias_recordatorio' => 1,
            'precio_defecto' => 0,
            'precio_socios_defecto' => 0,
            'permitir_lista_espera' => true,
            'tipos_evento' => ['conferencia','taller','charla','festival','deportivo','cultural','social','networking'],
        ];
    }

    public function init() { add_action('init', [$this, 'maybe_create_tables']); }
    public function activate() { $this->create_tables(); }
    public function deactivate() { }

    public function maybe_create_tables() {
        global $wpdb;
        $t = $wpdb->prefix . 'flavor_eventos';
        if (!Flavor_Chat_Helpers::tabla_existe($t)) { $this->create_tables(); }
    }

    private function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $te = $wpdb->prefix . 'flavor_eventos';
        $ti = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tc = $wpdb->prefix . 'flavor_eventos_categorias';
        $tr = $wpdb->prefix . 'flavor_eventos_recordatorios';

        $sql_eventos = "CREATE TABLE IF NOT EXISTS $te (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL, descripcion text DEFAULT NULL, contenido longtext DEFAULT NULL,
            imagen varchar(500) DEFAULT NULL,
            tipo enum('conferencia','taller','charla','festival','deportivo','cultural','social','networking') DEFAULT 'conferencia',
            fecha_inicio datetime NOT NULL, fecha_fin datetime DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL, direccion varchar(500) DEFAULT NULL,
            coordenadas_lat decimal(10,8) DEFAULT NULL, coordenadas_lng decimal(11,8) DEFAULT NULL,
            organizador_id bigint(20) unsigned DEFAULT NULL,
            precio decimal(10,2) DEFAULT 0.00, precio_socios decimal(10,2) DEFAULT 0.00,
            aforo_maximo int(11) DEFAULT 0, inscritos_count int(11) DEFAULT 0,
            es_online tinyint(1) DEFAULT 0, url_online varchar(500) DEFAULT NULL,
            categorias JSON DEFAULT NULL, etiquetas JSON DEFAULT NULL,
            estado enum('borrador','publicado','cancelado','finalizado') DEFAULT 'borrador',
            es_destacado tinyint(1) DEFAULT 0,
            recurrente enum('diario','semanal','mensual','anual') DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_organizador (organizador_id), KEY idx_fecha (fecha_inicio),
            KEY idx_tipo (tipo), KEY idx_estado (estado), KEY idx_destacado (es_destacado)
        ) $charset;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $ti (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL, user_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(255) NOT NULL, email varchar(255) NOT NULL, telefono varchar(50) DEFAULT NULL,
            num_plazas int(11) DEFAULT 1,
            estado enum('confirmada','pendiente','cancelada','lista_espera') DEFAULT 'pendiente',
            notas text DEFAULT NULL, metodo_pago varchar(100) DEFAULT NULL, referencia_pago varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_evento (evento_id), KEY idx_user (user_id),
            KEY idx_estado (estado), KEY idx_email (email)
        ) $charset;";

        $sql_categorias = "CREATE TABLE IF NOT EXISTS $tc (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL, slug varchar(255) NOT NULL, descripcion text DEFAULT NULL,
            icono varchar(100) DEFAULT NULL, color varchar(20) DEFAULT NULL, orden int(11) DEFAULT 0,
            parent_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id), UNIQUE KEY idx_slug (slug), KEY idx_parent (parent_id), KEY idx_orden (orden)
        ) $charset;";

        $sql_recordatorios = "CREATE TABLE IF NOT EXISTS $tr (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL, user_id bigint(20) unsigned NOT NULL,
            tipo enum('email','push') DEFAULT 'email', dias_antes int(11) DEFAULT 1,
            enviado tinyint(1) DEFAULT 0, fecha_envio datetime DEFAULT NULL,
            PRIMARY KEY (id), KEY idx_evento_rec (evento_id), KEY idx_user_rec (user_id), KEY idx_enviado (enviado)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_eventos); dbDelta($sql_inscripciones); dbDelta($sql_categorias); dbDelta($sql_recordatorios);
    }

    // ─── Actions ─────────────────────────────────────────────

    public function get_actions() {
        return [
            'listar_eventos'     => ['description' => 'Listar proximos eventos con filtros', 'params' => ['tipo','categoria','desde','hasta','limite','estado','solo_gratuitos']],
            'ver_evento'         => ['description' => 'Ver detalles de un evento', 'params' => ['evento_id']],
            'crear_evento'       => ['description' => 'Crear un nuevo evento', 'params' => ['titulo','descripcion','contenido','tipo','fecha_inicio','fecha_fin','ubicacion','direccion','precio','precio_socios','aforo_maximo','es_online','url_online']],
            'actualizar_evento'  => ['description' => 'Actualizar un evento existente', 'params' => ['evento_id','titulo','descripcion','tipo','fecha_inicio','fecha_fin','ubicacion','precio','estado']],
            'inscribirse'        => ['description' => 'Inscribirse a un evento', 'params' => ['evento_id','nombre','email','telefono','num_plazas','notas']],
            'cancelar_inscripcion' => ['description' => 'Cancelar inscripcion', 'params' => ['evento_id','inscripcion_id']],
            'mis_eventos'        => ['description' => 'Ver eventos del usuario', 'params' => ['estado','limite']],
            'mis_inscripciones'  => ['description' => 'Ver inscripciones del usuario', 'params' => ['estado','limite']],
            'eventos_hoy'        => ['description' => 'Ver eventos de hoy', 'params' => ['tipo']],
            'estadisticas'       => ['description' => 'Obtener estadisticas de eventos', 'params' => []],
            'eventos_proximos'   => ['description' => 'Listar eventos proximos (alias de listar_eventos)', 'params' => ['tipo', 'categoria', 'limite']],
            'inscribirse_evento' => ['description' => 'Inscribirse a un evento (alias de inscribirse)', 'params' => ['evento_id', 'nombre', 'email', 'telefono', 'num_plazas', 'notas']],
        ];
    }

    public function execute_action($action_name, $params) {
        $nombre_metodo = 'action_' . $action_name;
        if (method_exists($this, $nombre_metodo)) {
            return $this->$nombre_metodo($params);
        }
        return ['success' => false, 'message' => 'Accion no encontrada'];
    }

    // ─── Action: listar_eventos ──────────────────────────────

    private function action_listar_eventos($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $where = ['1=1'];
        $values = [];
        if (!empty($params['tipo'])) {
            $where[] = 'tipo = %s';
            $values[] = sanitize_text_field($params['tipo']);
        }
        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $values[] = sanitize_text_field($params['estado']);
        } else {
            $where[] = "estado = 'publicado'";
        }
        if (!empty($params['desde'])) {
            $where[] = 'fecha_inicio >= %s';
            $values[] = sanitize_text_field($params['desde']);
        }
        if (!empty($params['hasta'])) {
            $where[] = 'fecha_inicio <= %s';
            $values[] = sanitize_text_field($params['hasta']);
        }
        if (!empty($params['solo_gratuitos'])) {
            $where[] = 'precio = 0';
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY fecha_inicio ASC LIMIT %d";
        $values[] = $limite;
        $eventos = $wpdb->get_results($wpdb->prepare($sql, ...$values), ARRAY_A);
        if (!$eventos) {
            return ['success' => true, 'data' => [], 'message' => 'No se encontraron eventos'];
        }
        $resultado = [];
        foreach ($eventos as $evento) {
            $resultado[] = [
                'id'                 => (int) $evento['id'],
                'titulo'             => $evento['titulo'],
                'tipo'               => $evento['tipo'],
                'fecha_inicio'       => $evento['fecha_inicio'],
                'fecha_fin'          => $evento['fecha_fin'],
                'ubicacion'          => $evento['ubicacion'],
                'precio'             => (float) $evento['precio'],
                'aforo_maximo'       => (int) $evento['aforo_maximo'],
                'inscritos_count'    => (int) $evento['inscritos_count'],
                'plazas_disponibles' => $this->calcular_plazas_disponibles($evento),
                'estado'             => $evento['estado'],
            ];
        }
        return ['success' => true, 'data' => $resultado, 'total' => count($resultado)];
    }

    // ─── Action: ver_evento ──────────────────────────────────

    private function action_ver_evento($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => 'Se requiere evento_id'];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_ev WHERE id = %d", $evento_id),
            ARRAY_A
        );
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento no encontrado'];
        }
        $total_inscritos = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(num_plazas), 0) FROM $tabla_ins WHERE evento_id = %d AND estado IN ('confirmada','pendiente')",
                $evento_id
            )
        );
        $evento['inscritos_real'] = $total_inscritos;
        $evento['plazas_disponibles'] = $this->calcular_plazas_disponibles($evento);
        $evento['precio_formateado'] = $this->format_price((float) $evento['precio']);
        if ((float) $evento['precio_socios'] > 0) {
            $evento['precio_socios_formateado'] = $this->format_price((float) $evento['precio_socios']);
        }
        return ['success' => true, 'data' => $evento];
    }

    // ─── Action: crear_evento ────────────────────────────────

    private function action_crear_evento($params) {
        if (empty($params['titulo']) || empty($params['fecha_inicio'])) {
            return ['success' => false, 'message' => 'Se requiere titulo y fecha_inicio'];
        }
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $datos = [
            'titulo'        => sanitize_text_field($params['titulo']),
            'descripcion'   => isset($params['descripcion']) ? sanitize_textarea_field($params['descripcion']) : '',
            'contenido'     => isset($params['contenido']) ? wp_kses_post($params['contenido']) : '',
            'tipo'          => isset($params['tipo']) ? sanitize_text_field($params['tipo']) : 'conferencia',
            'fecha_inicio'  => sanitize_text_field($params['fecha_inicio']),
            'fecha_fin'     => isset($params['fecha_fin']) ? sanitize_text_field($params['fecha_fin']) : null,
            'ubicacion'     => isset($params['ubicacion']) ? sanitize_text_field($params['ubicacion']) : null,
            'direccion'     => isset($params['direccion']) ? sanitize_text_field($params['direccion']) : null,
            'precio'        => isset($params['precio']) ? floatval($params['precio']) : 0.00,
            'precio_socios' => isset($params['precio_socios']) ? floatval($params['precio_socios']) : 0.00,
            'aforo_maximo'  => isset($params['aforo_maximo']) ? absint($params['aforo_maximo']) : 0,
            'es_online'     => !empty($params['es_online']) ? 1 : 0,
            'url_online'    => isset($params['url_online']) ? esc_url_raw($params['url_online']) : null,
            'organizador_id' => get_current_user_id(),
            'estado'        => 'borrador',
        ];
        $resultado = $wpdb->insert($tabla, $datos);
        if ($resultado === false) {
            return ['success' => false, 'message' => 'Error al crear el evento'];
        }
        return ['success' => true, 'data' => ['id' => $wpdb->insert_id], 'message' => 'Evento creado correctamente'];
    }

    // ─── Action: actualizar_evento ───────────────────────────

    private function action_actualizar_evento($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => 'Se requiere evento_id'];
        }
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $evento_id = absint($params['evento_id']);
        $campos_permitidos = ['titulo','descripcion','tipo','fecha_inicio','fecha_fin','ubicacion','precio','estado'];
        $datos_actualizar = [];
        foreach ($campos_permitidos as $campo) {
            if (isset($params[$campo])) {
                $datos_actualizar[$campo] = sanitize_text_field($params[$campo]);
            }
        }
        if (empty($datos_actualizar)) {
            return ['success' => false, 'message' => 'No hay campos para actualizar'];
        }
        $resultado = $wpdb->update($tabla, $datos_actualizar, ['id' => $evento_id]);
        if ($resultado === false) {
            return ['success' => false, 'message' => 'Error al actualizar'];
        }
        return ['success' => true, 'message' => 'Evento actualizado correctamente'];
    }

    // ─── Action: inscribirse ─────────────────────────────────

    private function action_inscribirse($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => 'Se requiere evento_id'];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_ev WHERE id = %d AND estado = 'publicado'", $evento_id),
            ARRAY_A
        );
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento no encontrado o no disponible'];
        }
        $num_plazas = isset($params['num_plazas']) ? absint($params['num_plazas']) : 1;
        $plazas_disponibles = $this->calcular_plazas_disponibles($evento);
        $estado_inscripcion = 'confirmada';
        if ((int) $evento['aforo_maximo'] > 0 && $plazas_disponibles >= 0 && $plazas_disponibles < $num_plazas) {
            if ($this->get_setting('permitir_lista_espera')) {
                $estado_inscripcion = 'lista_espera';
            } else {
                return ['success' => false, 'message' => 'No hay plazas disponibles'];
            }
        }
        if ($this->get_setting('requiere_aprobacion')) {
            $estado_inscripcion = 'pendiente';
        }
        $nombre_inscrito = !empty($params['nombre']) ? sanitize_text_field($params['nombre']) : '';
        $email_inscrito = !empty($params['email']) ? sanitize_email($params['email']) : '';
        if (empty($nombre_inscrito) || empty($email_inscrito)) {
            $usuario_actual = wp_get_current_user();
            if ($usuario_actual->ID) {
                if (empty($nombre_inscrito)) { $nombre_inscrito = $usuario_actual->display_name; }
                if (empty($email_inscrito))  { $email_inscrito = $usuario_actual->user_email; }
            } else {
                return ['success' => false, 'message' => 'Se requiere nombre y email'];
            }
        }
        $datos_inscripcion = [
            'evento_id'  => $evento_id,
            'user_id'    => get_current_user_id() ?: null,
            'nombre'     => $nombre_inscrito,
            'email'      => $email_inscrito,
            'telefono'   => isset($params['telefono']) ? sanitize_text_field($params['telefono']) : null,
            'num_plazas' => $num_plazas,
            'estado'     => $estado_inscripcion,
            'notas'      => isset($params['notas']) ? sanitize_textarea_field($params['notas']) : null,
        ];
        $resultado = $wpdb->insert($tabla_ins, $datos_inscripcion);
        if ($resultado === false) {
            return ['success' => false, 'message' => 'Error al inscribirse'];
        }
        $wpdb->query(
            $wpdb->prepare("UPDATE $tabla_ev SET inscritos_count = inscritos_count + %d WHERE id = %d", $num_plazas, $evento_id)
        );
        return [
            'success' => true,
            'data'    => ['inscripcion_id' => $wpdb->insert_id, 'estado' => $estado_inscripcion],
            'message' => 'Inscripcion realizada correctamente',
        ];
    }

    // ─── Action: cancelar_inscripcion ────────────────────────

    private function action_cancelar_inscripcion($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => 'Se requiere evento_id'];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        if (!empty($params['inscripcion_id'])) {
            $inscripcion_id = absint($params['inscripcion_id']);
            $inscripcion = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_ins WHERE id = %d AND evento_id = %d AND estado != 'cancelada'",
                    $inscripcion_id, $evento_id
                ), ARRAY_A
            );
        } else {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return ['success' => false, 'message' => 'Se requiere inscripcion_id o estar autenticado'];
            }
            $inscripcion = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_ins WHERE user_id = %d AND evento_id = %d AND estado != 'cancelada'",
                    $user_id, $evento_id
                ), ARRAY_A
            );
        }
        if (!$inscripcion) {
            return ['success' => false, 'message' => 'Inscripcion no encontrada'];
        }
        $wpdb->update($tabla_ins, ['estado' => 'cancelada'], ['id' => $inscripcion['id']]);
        $plazas_canceladas = (int) $inscripcion['num_plazas'];
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $tabla_ev SET inscritos_count = GREATEST(0, inscritos_count - %d) WHERE id = %d",
                $plazas_canceladas, $evento_id
            )
        );
        return ['success' => true, 'message' => 'Inscripcion cancelada correctamente'];
    }

    // ─── Action: mis_eventos ─────────────────────────────────

    private function action_mis_eventos($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'Debes estar autenticado'];
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $estado_filtro = '';
        $args = [$user_id];
        if (!empty($params['estado'])) {
            $estado_filtro = ' AND estado = %s';
            $args[] = sanitize_text_field($params['estado']);
        }
        $args[] = $limite;
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE organizador_id = %d$estado_filtro ORDER BY fecha_inicio DESC LIMIT %d",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $eventos ?: [], 'total' => count($eventos ?: [])];
    }

    // ─── Action: mis_inscripciones ───────────────────────────

    private function action_mis_inscripciones($params) {
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'Debes estar autenticado'];
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $estado_filtro = '';
        $args = [$user_id];
        if (!empty($params['estado'])) {
            $estado_filtro = ' AND i.estado = %s';
            $args[] = sanitize_text_field($params['estado']);
        }
        $args[] = $limite;
        $inscripciones = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, e.titulo, e.fecha_inicio, e.ubicacion, e.tipo FROM $tabla_ins i LEFT JOIN $tabla_ev e ON i.evento_id = e.id WHERE i.user_id = %d$estado_filtro ORDER BY i.created_at DESC LIMIT %d",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $inscripciones ?: [], 'total' => count($inscripciones ?: [])];
    }

    // ─── Action: eventos_hoy ─────────────────────────────────

    private function action_eventos_hoy($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $hoy_inicio = current_time('Y-m-d') . ' 00:00:00';
        $hoy_fin    = current_time('Y-m-d') . ' 23:59:59';
        $tipo_filtro = '';
        $args = [$hoy_inicio, $hoy_fin];
        if (!empty($params['tipo'])) {
            $tipo_filtro = ' AND tipo = %s';
            $args[] = sanitize_text_field($params['tipo']);
        }
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE estado = 'publicado' AND fecha_inicio BETWEEN %s AND %s$tipo_filtro ORDER BY fecha_inicio ASC",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $eventos ?: [], 'total' => count($eventos ?: []), 'fecha' => current_time('Y-m-d')];
    }

    // ─── Action: estadisticas ────────────────────────────────

    private function action_estadisticas($params) {
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ev");
        $eventos_publicados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ev WHERE estado = 'publicado'");
        $eventos_proximos = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_ev WHERE estado = 'publicado' AND fecha_inicio > %s", current_time('mysql'))
        );
        $total_inscripciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ins WHERE estado IN ('confirmada','pendiente')");
        $por_tipo = $wpdb->get_results("SELECT tipo, COUNT(*) as total FROM $tabla_ev GROUP BY tipo", ARRAY_A);
        $por_estado = $wpdb->get_results("SELECT estado, COUNT(*) as total FROM $tabla_ev GROUP BY estado", ARRAY_A);
        return [
            'success' => true,
            'data'    => [
                'total_eventos'        => $total_eventos,
                'eventos_publicados'   => $eventos_publicados,
                'eventos_proximos'     => $eventos_proximos,
                'total_inscripciones'  => $total_inscripciones,
                'por_tipo'             => $por_tipo ?: [],
                'por_estado'           => $por_estado ?: [],
            ],
        ];
    }

    // ─── AI Tool Definitions ─────────────────────────────────

    public function get_tool_definitions() {
        return [
            [
                'name'         => 'eventos_listar',
                'description'  => 'Listar eventos disponibles con filtros opcionales de tipo, fecha y precio',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo'           => ['type' => 'string', 'description' => 'Tipo: conferencia, taller, charla, festival, deportivo, cultural, social, networking'],
                        'desde'          => ['type' => 'string', 'description' => 'Fecha inicio filtro (YYYY-MM-DD)'],
                        'hasta'          => ['type' => 'string', 'description' => 'Fecha fin filtro (YYYY-MM-DD)'],
                        'limite'         => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                        'solo_gratuitos' => ['type' => 'boolean', 'description' => 'Solo mostrar eventos gratuitos'],
                    ],
                ],
            ],
            [
                'name'         => 'eventos_buscar',
                'description'  => 'Buscar eventos por texto en titulo, descripcion o ubicacion',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => ['type' => 'string', 'description' => 'Texto a buscar'],
                        'tipo'     => ['type' => 'string', 'description' => 'Filtrar por tipo de evento'],
                        'limite'   => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'eventos_inscribirse',
                'description'  => 'Inscribir al usuario en un evento',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'evento_id'  => ['type' => 'integer', 'description' => 'ID del evento'],
                        'nombre'     => ['type' => 'string', 'description' => 'Nombre del asistente'],
                        'email'      => ['type' => 'string', 'description' => 'Email del asistente'],
                        'num_plazas' => ['type' => 'integer', 'description' => 'Numero de plazas a reservar'],
                    ],
                    'required' => ['evento_id'],
                ],
            ],
            [
                'name'         => 'eventos_proximos',
                'description'  => 'Obtener los proximos eventos programados',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'dias'   => ['type' => 'integer', 'description' => 'Numero de dias a futuro (default 30)'],
                        'tipo'   => ['type' => 'string', 'description' => 'Filtrar por tipo'],
                        'limite' => ['type' => 'integer', 'description' => 'Maximo resultados'],
                    ],
                ],
            ],
        ];
    }

    // ─── Knowledge Base & FAQs ───────────────────────────────

    public function get_knowledge_base() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado'");
        $proximos = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado' AND fecha_inicio > %s", current_time('mysql'))
        );
        $tipos = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla WHERE estado = 'publicado'");
        return [
            'total_eventos_publicados' => $total,
            'eventos_proximos'         => $proximos,
            'tipos_disponibles'        => $tipos ?: [],
            'descripcion'              => 'Modulo de gestion de eventos con inscripciones, calendario y estadisticas. Soporta eventos presenciales y online.',
        ];
    }

    public function get_faqs() {
        return [
            ['pregunta' => 'Como puedo ver los proximos eventos?', 'respuesta' => 'Puedo mostrarte los proximos eventos. Dime si quieres filtrar por tipo, fecha o precio.'],
            ['pregunta' => 'Como me inscribo a un evento?', 'respuesta' => 'Dime el nombre o ID del evento y te ayudo con la inscripcion. Necesitare tu nombre y email.'],
            ['pregunta' => 'Puedo cancelar mi inscripcion?', 'respuesta' => 'Si, puedo cancelar tu inscripcion. Dime a que evento quieres cancelar.'],
            ['pregunta' => 'Hay eventos gratuitos?', 'respuesta' => 'Puedo filtrarte solo los eventos gratuitos. Quieres que te los muestre?'],
            ['pregunta' => 'Que tipos de eventos hay?', 'respuesta' => 'Tenemos conferencias, talleres, charlas, festivales, eventos deportivos, culturales, sociales y de networking.'],
            ['pregunta' => 'Como puedo crear un evento?', 'respuesta' => 'Puedo ayudarte a crear un evento. Necesitare titulo, descripcion, tipo, fecha y ubicacion como minimo.'],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_evento' => [
                'title' => __('Crear Nuevo Evento', 'flavor-chat-ia'),
                'description' => __('Organiza un evento para la comunidad', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del evento', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Ej: Encuentro vecinal de primavera', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                        'placeholder' => __('Describe el evento, qué se hará, qué incluye...', 'flavor-chat-ia'),
                    ],
                    'tipo_evento' => [
                        'type' => 'select',
                        'label' => __('Tipo de evento', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'charla' => __('Charla/Conferencia', 'flavor-chat-ia'),
                            'taller' => __('Taller práctico', 'flavor-chat-ia'),
                            'reunion' => __('Reunión', 'flavor-chat-ia'),
                            'festival' => __('Festival/Fiesta', 'flavor-chat-ia'),
                            'deportivo' => __('Actividad deportiva', 'flavor-chat-ia'),
                            'cultural' => __('Evento cultural', 'flavor-chat-ia'),
                            'networking' => __('Networking', 'flavor-chat-ia'),
                        ],
                    ],
                    'fecha_inicio' => [
                        'type' => 'datetime-local',
                        'label' => __('Fecha y hora de inicio', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'fecha_fin' => [
                        'type' => 'datetime-local',
                        'label' => __('Fecha y hora de fin', 'flavor-chat-ia'),
                    ],
                    'ubicacion' => [
                        'type' => 'text',
                        'label' => __('Ubicación', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Centro comunitario, Calle...', 'flavor-chat-ia'),
                    ],
                    'aforo_maximo' => [
                        'type' => 'number',
                        'label' => __('Aforo máximo', 'flavor-chat-ia'),
                        'min' => 1,
                        'placeholder' => __('Número de asistentes', 'flavor-chat-ia'),
                        'description' => __('Deja vacío si es ilimitado', 'flavor-chat-ia'),
                    ],
                    'precio' => [
                        'type' => 'number',
                        'label' => __('Precio entrada (€)', 'flavor-chat-ia'),
                        'step' => '0.01',
                        'min' => 0,
                        'default' => 0,
                        'description' => __('Deja en 0 si es gratuito', 'flavor-chat-ia'),
                    ],
                    'requiere_inscripcion' => [
                        'type' => 'checkbox',
                        'label' => __('Inscripción previa', 'flavor-chat-ia'),
                        'checkbox_label' => __('Requiere inscripción previa', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Crear Evento', 'flavor-chat-ia'),
                'success_message' => __('Evento creado correctamente', 'flavor-chat-ia'),
                'redirect_url' => '/eventos/mis-eventos/',
            ],
            'inscribirse_evento' => [
                'title' => __('Inscribirse en Evento', 'flavor-chat-ia'),
                'fields' => [
                    'evento_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'num_acompanantes' => [
                        'type' => 'number',
                        'label' => __('Número de acompañantes', 'flavor-chat-ia'),
                        'min' => 0,
                        'max' => 5,
                        'default' => 0,
                    ],
                    'comentarios' => [
                        'type' => 'textarea',
                        'label' => __('Comentarios', 'flavor-chat-ia'),
                        'rows' => 2,
                        'placeholder' => __('Alergias, necesidades especiales...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Confirmar Inscripción', 'flavor-chat-ia'),
                'success_message' => __('¡Inscripción confirmada! Te esperamos.', 'flavor-chat-ia'),
            ],
            'cancelar_inscripcion' => [
                'title' => __('Cancelar Inscripción', 'flavor-chat-ia'),
                'description' => __('Lamentamos que no puedas asistir', 'flavor-chat-ia'),
                'fields' => [
                    'inscripcion_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'motivo' => [
                        'type' => 'textarea',
                        'label' => __('Motivo de cancelación (opcional)', 'flavor-chat-ia'),
                        'rows' => 2,
                    ],
                ],
                'submit_text' => __('Cancelar Inscripción', 'flavor-chat-ia'),
                'success_message' => __('Inscripción cancelada', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    // ─── Web Components ──────────────────────────────────────

    public function get_web_components() {
        return [
            'eventos_hero' => [
                'label'       => 'Hero de Eventos',
                'description' => 'Seccion hero con busqueda y filtros de eventos',
                'template'    => 'eventos/hero',
                'category'    => 'eventos',
            ],
            'eventos_grid' => [
                'label'       => 'Grid de Eventos',
                'description' => 'Cuadricula de tarjetas de eventos con filtros',
                'template'    => 'eventos/eventos-grid',
                'category'    => 'eventos',
            ],
            'eventos_calendario' => [
                'label'       => 'Calendario de Eventos',
                'description' => 'Vista de calendario mensual con eventos',
                'template'    => 'eventos/eventos-calendario',
                'category'    => 'eventos',
            ],
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function calcular_plazas_disponibles($evento) {
        $aforo = (int) ($evento['aforo_maximo'] ?? 0);
        if ($aforo <= 0) { return -1; }
        $inscritos = (int) ($evento['inscritos_count'] ?? 0);
        return max(0, $aforo - $inscritos);
    }

    public function obtener_color_tipo_evento($tipo) {
        $colores = [
            'conferencia' => '#3B82F6',
            'taller'      => '#8B5CF6',
            'charla'      => '#06B6D4',
            'festival'    => '#F59E0B',
            'deportivo'   => '#10B981',
            'cultural'    => '#EC4899',
            'social'      => '#F97316',
            'networking'  => '#6366F1',
        ];
        return $colores[$tipo] ?? '#6B7280';
    }

    public function obtener_icono_tipo_evento($tipo) {
        $iconos = [
            'conferencia' => 'microphone',
            'taller'      => 'wrench',
            'charla'      => 'chat-bubble-left-right',
            'festival'    => 'musical-note',
            'deportivo'   => 'trophy',
            'cultural'    => 'paint-brush',
            'social'      => 'users',
            'networking'  => 'link',
        ];
        return $iconos[$tipo] ?? 'calendar';
    }

    // ─── Acciones delegadas para formularios frontend ─────────

    private function action_eventos_proximos($params) {
        return $this->action_listar_eventos($params);
    }

    private function action_inscribirse_evento($params) {
        return $this->action_inscribirse($params);
    }

}

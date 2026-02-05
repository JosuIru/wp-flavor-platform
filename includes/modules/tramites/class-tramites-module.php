<?php
/**
 * Módulo de Trámites para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Trámites - Gestión de trámites administrativos y solicitudes ciudadanas
 */
class Flavor_Chat_Tramites_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'tramites';
        $this->name = __('Trámites y Gestiones', 'flavor-chat-ia');
        $this->description = __('Sistema de gestión de trámites administrativos y solicitudes ciudadanas.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        return Flavor_Chat_Helpers::tabla_existe($tabla_tramites);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Trámites no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion_solicitudes' => true,
            'permite_tramites_online' => true,
            'permite_tramites_presencial' => true,
            'plazo_resolucion_maximo_dias' => 30,
            'notificar_cambio_estado' => true,
            'permite_cancelacion_solicitud' => true,
            'dias_limite_cancelacion' => 5,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_tramites)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';

        $sql_tramites = "CREATE TABLE IF NOT EXISTS $tabla_tramites (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            tipo varchar(100) DEFAULT NULL,
            requisitos text DEFAULT NULL,
            documentos_necesarios text DEFAULT NULL,
            plazo_resolucion_dias int(11) DEFAULT NULL,
            tasa decimal(10,2) DEFAULT 0,
            estado enum('activo','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tramite_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            numero_solicitud varchar(50) NOT NULL,
            nombre_solicitante varchar(255) NOT NULL,
            tipo_tramite varchar(100) DEFAULT NULL,
            datos_solicitud longtext DEFAULT NULL,
            documentos_adjuntos longtext DEFAULT NULL,
            prioridad enum('baja','normal','alta') DEFAULT 'normal',
            estado enum('pendiente','en_revision','aprobada','rechazada') DEFAULT 'pendiente',
            notas_admin text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY numero_solicitud (numero_solicitud),
            KEY tramite_id (tramite_id),
            KEY user_id (user_id),
            KEY estado (estado),
            KEY prioridad (prioridad)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_tramites);
        dbDelta($sql_solicitudes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_tramites' => [
                'description' => 'Listar trámites disponibles',
                'params' => ['categoria', 'tipo'],
            ],
            'detalle_tramite' => [
                'description' => 'Ver detalles de un trámite',
                'params' => ['tramite_id'],
            ],
            'crear_solicitud' => [
                'description' => 'Crear una nueva solicitud de trámite',
                'params' => ['tramite_id', 'datos_solicitud'],
            ],
            'mis_solicitudes' => [
                'description' => 'Ver mis solicitudes de trámites',
                'params' => ['estado'],
            ],
            'estado_solicitud' => [
                'description' => 'Consultar estado de una solicitud',
                'params' => ['solicitud_id'],
            ],
            'cancelar_solicitud' => [
                'description' => 'Cancelar una solicitud pendiente',
                'params' => ['solicitud_id'],
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
     * Acción: Listar trámites disponibles
     */
    private function action_listar_tramites($params) {
        global $wpdb;
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        $where = ["t.estado = 'activo'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 't.categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['tipo'])) {
            $where[] = 't.tipo = %s';
            $prepare_values[] = sanitize_text_field($params['tipo']);
        }

        $sql = "SELECT t.*
                FROM $tabla_tramites t
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.nombre ASC
                LIMIT 50";

        if (!empty($prepare_values)) {
            $tramites_resultado = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $tramites_resultado = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'tramites' => array_map(function($tramite) {
                return [
                    'id' => $tramite->id,
                    'nombre' => $tramite->nombre,
                    'descripcion' => wp_trim_words($tramite->descripcion, 30),
                    'categoria' => $tramite->categoria,
                    'tipo' => $tramite->tipo,
                    'plazo_resolucion_dias' => $tramite->plazo_resolucion_dias,
                    'tasa' => floatval($tramite->tasa),
                ];
            }, $tramites_resultado),
        ];
    }

    /**
     * Acción: Detalle de un trámite
     */
    private function action_detalle_tramite($params) {
        global $wpdb;
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        if (empty($params['tramite_id'])) {
            return [
                'success' => false,
                'error' => 'Se requiere el ID del trámite.',
            ];
        }

        $tramite_id_sanitizado = absint($params['tramite_id']);

        $tramite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_tramites WHERE id = %d AND estado = 'activo'",
            $tramite_id_sanitizado
        ));

        if (!$tramite) {
            return [
                'success' => false,
                'error' => 'Trámite no encontrado.',
            ];
        }

        return [
            'success' => true,
            'tramite' => [
                'id' => $tramite->id,
                'nombre' => $tramite->nombre,
                'descripcion' => $tramite->descripcion,
                'categoria' => $tramite->categoria,
                'tipo' => $tramite->tipo,
                'requisitos' => $tramite->requisitos,
                'documentos_necesarios' => $tramite->documentos_necesarios,
                'plazo_resolucion_dias' => $tramite->plazo_resolucion_dias,
                'tasa' => floatval($tramite->tasa),
            ],
        ];
    }

    /**
     * Acción: Crear solicitud de trámite
     */
    private function action_crear_solicitud($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        $usuario_actual_id = get_current_user_id();
        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para crear una solicitud.',
            ];
        }

        if (empty($params['tramite_id'])) {
            return [
                'success' => false,
                'error' => 'Se requiere el ID del trámite.',
            ];
        }

        $tramite_id_sanitizado = absint($params['tramite_id']);

        $tramite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_tramites WHERE id = %d AND estado = 'activo'",
            $tramite_id_sanitizado
        ));

        if (!$tramite) {
            return [
                'success' => false,
                'error' => 'El trámite solicitado no existe o no está disponible.',
            ];
        }

        $datos_usuario = get_userdata($usuario_actual_id);
        $numero_solicitud_generado = 'SOL-' . strtoupper(wp_generate_password(8, false));

        $datos_solicitud_json = !empty($params['datos_solicitud']) ? wp_json_encode($params['datos_solicitud']) : null;
        $documentos_adjuntos_json = !empty($params['documentos_adjuntos']) ? wp_json_encode($params['documentos_adjuntos']) : null;
        $prioridad_sanitizada = !empty($params['prioridad']) && in_array($params['prioridad'], ['baja', 'normal', 'alta']) ? $params['prioridad'] : 'normal';

        $resultado_insercion = $wpdb->insert($tabla_solicitudes, [
            'tramite_id' => $tramite_id_sanitizado,
            'user_id' => $usuario_actual_id,
            'numero_solicitud' => $numero_solicitud_generado,
            'nombre_solicitante' => $datos_usuario->display_name,
            'tipo_tramite' => $tramite->tipo,
            'datos_solicitud' => $datos_solicitud_json,
            'documentos_adjuntos' => $documentos_adjuntos_json,
            'prioridad' => $prioridad_sanitizada,
            'estado' => 'pendiente',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => 'Error al crear la solicitud. Inténtalo de nuevo.',
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Solicitud creada correctamente.',
            'solicitud' => [
                'id' => $wpdb->insert_id,
                'numero_solicitud' => $numero_solicitud_generado,
                'tramite' => $tramite->nombre,
                'estado' => 'pendiente',
                'fecha_solicitud' => current_time('d/m/Y H:i'),
            ],
        ];
    }

    /**
     * Acción: Mis solicitudes
     */
    private function action_mis_solicitudes($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        $usuario_actual_id = get_current_user_id();
        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para ver tus solicitudes.',
            ];
        }

        $where = ['s.user_id = %d'];
        $prepare_values = [$usuario_actual_id];

        if (!empty($params['estado'])) {
            $where[] = 's.estado = %s';
            $prepare_values[] = sanitize_text_field($params['estado']);
        }

        $sql = "SELECT s.*, t.nombre as nombre_tramite
                FROM $tabla_solicitudes s
                INNER JOIN $tabla_tramites t ON s.tramite_id = t.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.fecha_solicitud DESC
                LIMIT 50";

        $solicitudes_resultado = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'solicitudes' => array_map(function($solicitud) {
                return [
                    'id' => $solicitud->id,
                    'numero_solicitud' => $solicitud->numero_solicitud,
                    'tramite' => $solicitud->nombre_tramite,
                    'tipo_tramite' => $solicitud->tipo_tramite,
                    'prioridad' => $solicitud->prioridad,
                    'estado' => $solicitud->estado,
                    'fecha_solicitud' => date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)),
                    'fecha_resolucion' => $solicitud->fecha_resolucion ? date('d/m/Y H:i', strtotime($solicitud->fecha_resolucion)) : null,
                ];
            }, $solicitudes_resultado),
        ];
    }

    /**
     * Acción: Estado de una solicitud
     */
    private function action_estado_solicitud($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';
        $tabla_tramites = $wpdb->prefix . 'flavor_tramites';

        if (empty($params['solicitud_id'])) {
            return [
                'success' => false,
                'error' => 'Se requiere el ID de la solicitud.',
            ];
        }

        $solicitud_id_sanitizado = absint($params['solicitud_id']);
        $usuario_actual_id = get_current_user_id();

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.nombre as nombre_tramite, t.plazo_resolucion_dias
             FROM $tabla_solicitudes s
             INNER JOIN $tabla_tramites t ON s.tramite_id = t.id
             WHERE s.id = %d",
            $solicitud_id_sanitizado
        ));

        if (!$solicitud) {
            return [
                'success' => false,
                'error' => 'Solicitud no encontrada.',
            ];
        }

        if ($usuario_actual_id && $solicitud->user_id != $usuario_actual_id && !current_user_can('manage_options')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para ver esta solicitud.',
            ];
        }

        return [
            'success' => true,
            'solicitud' => [
                'id' => $solicitud->id,
                'numero_solicitud' => $solicitud->numero_solicitud,
                'tramite' => $solicitud->nombre_tramite,
                'nombre_solicitante' => $solicitud->nombre_solicitante,
                'tipo_tramite' => $solicitud->tipo_tramite,
                'prioridad' => $solicitud->prioridad,
                'estado' => $solicitud->estado,
                'notas_admin' => $solicitud->notas_admin,
                'plazo_resolucion_dias' => $solicitud->plazo_resolucion_dias,
                'fecha_solicitud' => date('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)),
                'fecha_resolucion' => $solicitud->fecha_resolucion ? date('d/m/Y H:i', strtotime($solicitud->fecha_resolucion)) : null,
            ],
        ];
    }

    /**
     * Acción: Cancelar solicitud
     */
    private function action_cancelar_solicitud($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';

        $usuario_actual_id = get_current_user_id();
        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para cancelar una solicitud.',
            ];
        }

        if (empty($params['solicitud_id'])) {
            return [
                'success' => false,
                'error' => 'Se requiere el ID de la solicitud.',
            ];
        }

        $solicitud_id_sanitizado = absint($params['solicitud_id']);

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d AND user_id = %d",
            $solicitud_id_sanitizado,
            $usuario_actual_id
        ));

        if (!$solicitud) {
            return [
                'success' => false,
                'error' => 'Solicitud no encontrada o no tienes permisos para cancelarla.',
            ];
        }

        if ($solicitud->estado !== 'pendiente') {
            return [
                'success' => false,
                'error' => 'Solo se pueden cancelar solicitudes en estado pendiente. Estado actual: ' . $solicitud->estado,
            ];
        }

        $configuracion_modulo = $this->get_settings();
        $dias_limite_cancelacion = isset($configuracion_modulo['dias_limite_cancelacion']) ? intval($configuracion_modulo['dias_limite_cancelacion']) : 5;
        $fecha_solicitud_timestamp = strtotime($solicitud->fecha_solicitud);
        $dias_transcurridos = (time() - $fecha_solicitud_timestamp) / DAY_IN_SECONDS;

        if ($dias_transcurridos > $dias_limite_cancelacion) {
            return [
                'success' => false,
                'error' => "El plazo de cancelación ha expirado. Solo se puede cancelar dentro de los primeros {$dias_limite_cancelacion} días.",
            ];
        }

        $resultado_actualizacion = $wpdb->update(
            $tabla_solicitudes,
            [
                'estado' => 'rechazada',
                'notas_admin' => 'Cancelada por el solicitante.',
                'fecha_resolucion' => current_time('mysql'),
            ],
            ['id' => $solicitud_id_sanitizado],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado_actualizacion === false) {
            return [
                'success' => false,
                'error' => 'Error al cancelar la solicitud. Inténtalo de nuevo.',
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Solicitud cancelada correctamente.',
            'solicitud' => [
                'id' => $solicitud->id,
                'numero_solicitud' => $solicitud->numero_solicitud,
                'estado' => 'rechazada',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Asistente guiado para completar trámites
     * - Detección automática de documentos necesarios
     * - Estimación de tiempos de resolución
     * - Notificaciones proactivas de cambios de estado
     */
    public function get_web_components() {
        return [
            'hero_tramites' => [
                'label' => __('Hero Trámites', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Trámites y Gestiones', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Realiza tus gestiones administrativas de forma sencilla', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'tramites/hero',
            ],
            'tramites_grid' => [
                'label' => __('Grid de Trámites', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Trámites Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_tasa' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'tramites/grid',
            ],
            'estado_solicitud_widget' => [
                'label' => __('Widget Estado Solicitud', 'flavor-chat-ia'),
                'category' => 'widgets',
                'icon' => 'dashicons-search',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Consulta tu Solicitud', 'flavor-chat-ia')],
                    'placeholder' => ['type' => 'text', 'default' => __('Introduce tu número de solicitud', 'flavor-chat-ia')],
                    'mostrar_detalle' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'tramites/estado-solicitud',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'listar_tramites',
                'description' => 'Ver trámites disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría del trámite'],
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
**Trámites y Gestiones Administrativas**

Gestiona tus trámites administrativos y solicitudes ciudadanas de forma sencilla.

**Categorías de trámites:**
- Empadronamiento y censo
- Licencias y permisos
- Urbanismo y obras
- Actividades económicas
- Servicios sociales
- Medio ambiente
- Vía pública y ocupación
- Tributos y tasas
- Registro y certificaciones

**Tipos de trámites:**
- Solicitudes generales
- Licencias de actividad
- Permisos de obra
- Certificados y volantes
- Reclamaciones y quejas
- Bonificaciones y ayudas

**Proceso de solicitud:**
1. Consulta los requisitos del trámite
2. Prepara la documentación necesaria
3. Presenta tu solicitud
4. Recibe tu número de seguimiento
5. Consulta el estado en cualquier momento

**Estados de una solicitud:**
- Pendiente: Recibida, en espera de revisión
- En revisión: Siendo evaluada por el área responsable
- Aprobada: Trámite resuelto favorablemente
- Rechazada: Trámite denegado (se indican los motivos)

**Información útil:**
- Cada trámite indica sus requisitos y documentos necesarios
- Se informa del plazo estimado de resolución
- Algunas gestiones pueden tener tasas asociadas
- Puedes cancelar solicitudes pendientes dentro del plazo establecido
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo puedo conocer el estado de mi solicitud?',
                'respuesta' => 'Puedes consultar el estado de tu solicitud usando tu número de seguimiento desde la sección "Mis solicitudes" o preguntando directamente al asistente.',
            ],
            [
                'pregunta' => '¿Qué documentos necesito para realizar un trámite?',
                'respuesta' => 'Cada trámite indica los documentos necesarios en su ficha. Consulta el detalle del trámite antes de iniciar la solicitud.',
            ],
            [
                'pregunta' => '¿Puedo cancelar una solicitud ya presentada?',
                'respuesta' => 'Sí, puedes cancelar solicitudes en estado pendiente dentro del plazo establecido. Una vez en revisión, no es posible cancelarla.',
            ],
        ];
    }
}

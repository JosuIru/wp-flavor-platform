<?php
/**
 * Módulo de Chat Interno para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Chat Interno - Mensajería privada entre usuarios
 */
class Flavor_Chat_Chat_Interno_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'chat_interno';
        $this->name = __('Chat Interno', 'flavor-chat-ia');
        $this->description = __('Sistema de mensajería privada uno a uno entre miembros de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';

        return Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Chat Interno no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'permite_chat_no_contactos' => true,
            'requiere_verificacion_usuario' => false,
            'permite_archivos' => true,
            'permite_notas_voz' => true,
            'permite_videollamadas' => false,
            'max_tamano_archivo_mb' => 25,
            'eliminar_mensajes_antiguos_dias' => 0, // 0 = nunca
            'encriptacion_e2e' => false,
            'notificaciones_push' => true,
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
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $sql_conversaciones = "CREATE TABLE IF NOT EXISTS $tabla_conversaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo enum('individual','soporte') DEFAULT 'individual',
            estado enum('activa','archivada','bloqueada') DEFAULT 'activa',
            ultimo_mensaje_id bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY fecha_actualizacion (fecha_actualizacion)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversacion_id bigint(20) unsigned NOT NULL,
            remitente_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            tipo enum('texto','imagen','archivo','audio','ubicacion','sistema') DEFAULT 'texto',
            adjunto_url varchar(500) DEFAULT NULL,
            adjunto_nombre varchar(255) DEFAULT NULL,
            adjunto_tamano bigint(20) DEFAULT NULL,
            responde_a bigint(20) unsigned DEFAULT NULL,
            leido tinyint(1) DEFAULT 0,
            fecha_lectura datetime DEFAULT NULL,
            editado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversacion_id (conversacion_id),
            KEY remitente_id (remitente_id),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        $sql_participantes = "CREATE TABLE IF NOT EXISTS $tabla_participantes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversacion_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('participante','soporte','admin') DEFAULT 'participante',
            silenciado tinyint(1) DEFAULT 0,
            ultimo_mensaje_leido bigint(20) DEFAULT 0,
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY conversacion_usuario (conversacion_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_conversaciones);
        dbDelta($sql_mensajes);
        dbDelta($sql_participantes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'conversaciones' => [
                'description' => 'Listar conversaciones activas',
                'params' => [],
            ],
            'iniciar_chat' => [
                'description' => 'Iniciar nueva conversación',
                'params' => ['usuario_id'],
            ],
            'mensajes' => [
                'description' => 'Ver mensajes de conversación',
                'params' => ['conversacion_id', 'desde', 'limite'],
            ],
            'enviar' => [
                'description' => 'Enviar mensaje',
                'params' => ['conversacion_id', 'mensaje', 'tipo'],
            ],
            'marcar_leido' => [
                'description' => 'Marcar mensajes como leídos',
                'params' => ['conversacion_id', 'hasta_mensaje_id'],
            ],
            'buscar_mensajes' => [
                'description' => 'Buscar en mensajes',
                'params' => ['query'],
            ],
            // Acciones de gestión (admin)
            'estadisticas_mensajeria' => [
                'description' => 'Estadísticas generales (admin)',
                'params' => ['periodo'],
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
     * Acción: Listar conversaciones
     */
    private function action_conversaciones($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $conversaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, p.ultimo_mensaje_leido, m.mensaje as ultimo_mensaje,
                    m.fecha_creacion as fecha_ultimo_mensaje, m.remitente_id as ultimo_remitente_id
             FROM $tabla_conversaciones c
             INNER JOIN $tabla_participantes p ON c.id = p.conversacion_id
             LEFT JOIN $tabla_mensajes m ON c.ultimo_mensaje_id = m.id
             WHERE p.usuario_id = %d
             AND c.estado = 'activa'
             ORDER BY c.fecha_actualizacion DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'conversaciones' => array_map(function($c) use ($usuario_id, $wpdb) {
                $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
                $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

                // Obtener el otro participante
                $otro_participante = $wpdb->get_var($wpdb->prepare(
                    "SELECT usuario_id FROM $tabla_participantes
                     WHERE conversacion_id = %d AND usuario_id != %d
                     LIMIT 1",
                    $c->id,
                    $usuario_id
                ));

                $otro_usuario = $otro_participante ? get_userdata($otro_participante) : null;

                // Contar no leídos
                $no_leidos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_mensajes
                     WHERE conversacion_id = %d
                     AND id > %d
                     AND remitente_id != %d",
                    $c->id,
                    $c->ultimo_mensaje_leido,
                    $usuario_id
                ));

                return [
                    'id' => $c->id,
                    'con_usuario' => [
                        'id' => $otro_participante,
                        'nombre' => $otro_usuario ? $otro_usuario->display_name : 'Usuario',
                        'avatar' => $otro_participante ? get_avatar_url($otro_participante) : '',
                    ],
                    'ultimo_mensaje' => $c->ultimo_mensaje,
                    'fecha' => human_time_diff(strtotime($c->fecha_ultimo_mensaje), current_time('timestamp')) . ' atrás',
                    'no_leidos' => $no_leidos,
                ];
            }, $conversaciones),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Sugerencias de respuestas inteligentes
     * - Resumen de conversaciones largas
     * - Traducción en tiempo real
     * - Filtro de spam y contenido inapropiado
     */
    public function get_web_components() {
        return [
            'hero_chat_interno' => [
                'label' => __('Hero Chat Interno', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Mensajería Privada', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Comunicación directa y privada entre vecinos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'chat-interno/hero',
            ],
            'features_chat' => [
                'label' => __('Características del Chat', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Mensajería Completa', 'flavor-chat-ia')],
                ],
                'template' => 'chat-interno/features',
            ],
            'cta_app' => [
                'label' => __('CTA Descargar App', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-smartphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Descarga la App', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Chatea desde cualquier lugar con la app móvil', 'flavor-chat-ia')],
                    'boton_ios' => ['type' => 'url', 'default' => '#'],
                    'boton_android' => ['type' => 'url', 'default' => '#'],
                ],
                'template' => 'chat-interno/cta-app',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'chat_conversaciones',
                'description' => 'Ver mis conversaciones privadas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Chat Interno Privado**

Sistema de mensajería privada uno a uno entre miembros.

**Características:**
- Mensajes de texto, voz e imágenes
- Notificaciones en tiempo real
- Indicador de "escribiendo..."
- Confirmación de lectura
- Búsqueda en historial
- Compartir ubicación
- Responder mensajes específicos

**Privacidad:**
- Conversaciones cifradas (opcional)
- Control de quién puede contactarte
- Bloqueo de usuarios
- Eliminación de mensajes

**Usos:**
- Coordinación de intercambios
- Consultas sobre servicios
- Comunicación vecinal privada
- Soporte técnico
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Puedo eliminar mensajes?',
                'respuesta' => 'Sí, puedes eliminar mensajes para ti o para todos los participantes.',
            ],
            [
                'pregunta' => '¿Son seguros mis mensajes?',
                'respuesta' => 'Sí, los mensajes están cifrados y solo los participantes pueden verlos.',
            ],
        ];
    }
}

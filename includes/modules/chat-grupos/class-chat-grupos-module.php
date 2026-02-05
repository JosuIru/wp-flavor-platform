<?php
/**
 * Módulo de Chat de Grupos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Chat de Grupos - Grupos de conversación temáticos
 */
class Flavor_Chat_Chat_Grupos_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'chat_grupos';
        $this->name = __('Chat de Grupos', 'flavor-chat-ia');
        $this->description = __('Grupos de conversación temáticos para la comunidad con canales y temas organizados.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_grupos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Chat de Grupos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas', // 'cliente', 'admin', 'ambas'
            'max_miembros_por_grupo' => 500,
            'permite_crear_grupos' => true,
            'requiere_aprobacion_grupos' => false,
            'permite_grupos_privados' => true,
            'permite_archivos' => true,
            'permite_videollamadas' => false,
            'permite_encuestas' => true,
            'historial_mensajes_dias' => 365,
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
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $sql_grupos = "CREATE TABLE IF NOT EXISTS $tabla_grupos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            creador_id bigint(20) unsigned NOT NULL,
            tipo enum('publico','privado','secreto') DEFAULT 'publico',
            categoria varchar(100) DEFAULT NULL,
            max_miembros int(11) DEFAULT 500,
            miembros_count int(11) DEFAULT 0,
            mensajes_count int(11) DEFAULT 0,
            estado enum('activo','archivado','bloqueado') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('miembro','moderador','admin') DEFAULT 'miembro',
            notificaciones enum('todas','menciones','ninguna') DEFAULT 'todas',
            silenciado_hasta datetime DEFAULT NULL,
            ultimo_mensaje_leido bigint(20) DEFAULT 0,
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario (grupo_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            tipo enum('texto','imagen','archivo','sistema','encuesta') DEFAULT 'texto',
            adjuntos text DEFAULT NULL COMMENT 'JSON',
            responde_a bigint(20) unsigned DEFAULT NULL,
            menciones text DEFAULT NULL COMMENT 'JSON de user_ids',
            editado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY grupo_id (grupo_id),
            KEY usuario_id (usuario_id),
            KEY fecha_creacion (fecha_creacion),
            FULLTEXT KEY mensaje (mensaje)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_grupos);
        dbDelta($sql_miembros);
        dbDelta($sql_mensajes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mis_grupos' => [
                'description' => 'Listar mis grupos de chat',
                'params' => [],
            ],
            'grupos_publicos' => [
                'description' => 'Buscar grupos públicos',
                'params' => ['categoria', 'busqueda'],
            ],
            'crear_grupo' => [
                'description' => 'Crear nuevo grupo',
                'params' => ['nombre', 'descripcion', 'tipo', 'categoria'],
            ],
            'unirse_grupo' => [
                'description' => 'Unirse a un grupo',
                'params' => ['grupo_id'],
            ],
            'mensajes' => [
                'description' => 'Ver mensajes de un grupo',
                'params' => ['grupo_id', 'desde', 'limite'],
            ],
            'enviar_mensaje' => [
                'description' => 'Enviar mensaje al grupo',
                'params' => ['grupo_id', 'mensaje', 'responde_a'],
            ],
            'info_grupo' => [
                'description' => 'Ver información del grupo',
                'params' => ['grupo_id'],
            ],
            'silenciar_grupo' => [
                'description' => 'Silenciar notificaciones',
                'params' => ['grupo_id', 'duracion_horas'],
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
     * Acción: Mis grupos
     */
    private function action_mis_grupos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $grupos = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, m.rol, m.notificaciones, m.ultimo_mensaje_leido
             FROM $tabla_grupos g
             INNER JOIN $tabla_miembros m ON g.id = m.grupo_id
             WHERE m.usuario_id = %d
             AND g.estado = 'activo'
             ORDER BY g.fecha_actualizacion DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'grupos' => array_map(function($g) {
                // Contar mensajes no leídos
                global $wpdb;
                $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

                $no_leidos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_mensajes
                     WHERE grupo_id = %d
                     AND id > %d",
                    $g->id,
                    $g->ultimo_mensaje_leido
                ));

                return [
                    'id' => $g->id,
                    'nombre' => $g->nombre,
                    'descripcion' => wp_trim_words($g->descripcion, 20),
                    'imagen_url' => $g->imagen_url,
                    'tipo' => $g->tipo,
                    'miembros' => $g->miembros_count,
                    'mensajes_no_leidos' => $no_leidos,
                    'mi_rol' => $g->rol,
                ];
            }, $grupos),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Resumen automático de conversaciones largas
     * - Traducción de mensajes en tiempo real
     * - Sugerencias de grupos según intereses
     * - Moderación automática de contenido
     */
    public function get_web_components() {
        return [
            'hero_chat_grupos' => [
                'label' => __('Hero Chat Grupos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Chat de Grupos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Comunícate en tiempo real con grupos de interés', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'chat-grupos/hero',
            ],
            'grupos_destacados' => [
                'label' => __('Grupos Destacados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Grupos Populares', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'tipo' => ['type' => 'select', 'options' => ['todos', 'publico', 'privado'], 'default' => 'publico'],
                ],
                'template' => 'chat-grupos/grupos-destacados',
            ],
            'como_funciona_grupos' => [
                'label' => __('Cómo Funcionan los Grupos', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Cómo Funcionan los Grupos', 'flavor-chat-ia')],
                ],
                'template' => 'chat-grupos/como-funciona',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'chat_grupos_mis_grupos',
                'description' => 'Ver mis grupos de chat',
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
**Chat de Grupos Comunitarios**

Sistema de mensajería grupal organizado por temas e intereses.

**Tipos de grupos:**
- Públicos: Cualquiera puede unirse y ver mensajes
- Privados: Requieren invitación, visibles en búsqueda
- Secretos: Solo por invitación, no aparecen en búsqueda

**Funcionalidades:**
- Chat en tiempo real
- Menciones (@usuario)
- Respuestas a mensajes
- Compartir archivos e imágenes
- Encuestas en el grupo
- Roles: Admin, Moderador, Miembro
- Silenciar notificaciones

**Categorías sugeridas:**
- General del barrio
- Avisos urgentes
- Compra/venta local
- Eventos y actividades
- Deportes y ocio
- Ayuda mutua
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cuántos grupos puedo crear?',
                'respuesta' => 'No hay límite de grupos que puedas crear o unirte.',
            ],
            [
                'pregunta' => '¿Los mensajes se guardan?',
                'respuesta' => 'Sí, el historial se mantiene durante 1 año por defecto.',
            ],
        ];
    }
}

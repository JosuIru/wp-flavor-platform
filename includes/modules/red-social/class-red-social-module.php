<?php
/**
 * Módulo de Red Social Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Red Social - Alternativa social media para la comunidad
 */
class Flavor_Chat_Red_Social_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'red_social';
        $this->name = __('Red Social Comunitaria', 'flavor-chat-ia');
        $this->description = __('Red social alternativa sin publicidad, centrada en la comunidad y sus intereses.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        return Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Red Social no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente', // 'cliente', 'admin', 'ambas'
            'publicaciones_publicas' => true,
            'requiere_moderacion' => false,
            'max_caracteres_publicacion' => 5000,
            'permite_imagenes' => true,
            'permite_videos' => true,
            'max_imagenes_por_post' => 10,
            'permite_hashtags' => true,
            'permite_menciones' => true,
            'permite_compartir' => true,
            'timeline_algoritmo' => 'cronologico', // 'cronologico' o 'relevancia'
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
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        $sql_publicaciones = "CREATE TABLE IF NOT EXISTS $tabla_publicaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            autor_id bigint(20) unsigned NOT NULL,
            contenido text NOT NULL,
            tipo enum('texto','imagen','video','enlace','evento') DEFAULT 'texto',
            adjuntos text DEFAULT NULL COMMENT 'JSON con URLs de archivos',
            visibilidad enum('publica','comunidad','seguidores','privada') DEFAULT 'comunidad',
            ubicacion varchar(255) DEFAULT NULL,
            estado enum('borrador','publicado','moderacion','oculto') DEFAULT 'publicado',
            es_fijado tinyint(1) DEFAULT 0,
            me_gusta int(11) DEFAULT 0,
            comentarios int(11) DEFAULT 0,
            compartidos int(11) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion),
            KEY visibilidad (visibilidad),
            FULLTEXT KEY contenido (contenido)
        ) $charset_collate;";

        $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            comentario_padre_id bigint(20) unsigned DEFAULT NULL,
            contenido text NOT NULL,
            me_gusta int(11) DEFAULT 0,
            estado enum('publicado','moderacion','oculto') DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY publicacion_id (publicacion_id),
            KEY autor_id (autor_id),
            KEY comentario_padre_id (comentario_padre_id)
        ) $charset_collate;";

        $sql_reacciones = "CREATE TABLE IF NOT EXISTS $tabla_reacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) unsigned DEFAULT NULL,
            comentario_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('me_gusta','me_encanta','me_divierte','me_entristece','me_enfada') DEFAULT 'me_gusta',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY publicacion_usuario (publicacion_id, usuario_id),
            UNIQUE KEY comentario_usuario (comentario_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        $sql_seguimientos = "CREATE TABLE IF NOT EXISTS $tabla_seguimientos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            seguidor_id bigint(20) unsigned NOT NULL,
            seguido_id bigint(20) unsigned NOT NULL,
            notificaciones_activas tinyint(1) DEFAULT 1,
            fecha_seguimiento datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY seguidor_seguido (seguidor_id, seguido_id),
            KEY seguido_id (seguido_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_publicaciones);
        dbDelta($sql_comentarios);
        dbDelta($sql_reacciones);
        dbDelta($sql_seguimientos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'timeline' => [
                'description' => 'Ver timeline personal',
                'params' => ['desde', 'limite'],
            ],
            'publicar' => [
                'description' => 'Crear nueva publicación',
                'params' => ['contenido', 'tipo', 'adjuntos', 'visibilidad'],
            ],
            'comentar' => [
                'description' => 'Comentar publicación',
                'params' => ['publicacion_id', 'contenido'],
            ],
            'reaccionar' => [
                'description' => 'Dar me gusta o reaccionar',
                'params' => ['publicacion_id', 'tipo'],
            ],
            'seguir' => [
                'description' => 'Seguir a un usuario',
                'params' => ['usuario_id'],
            ],
            'perfil' => [
                'description' => 'Ver perfil de usuario',
                'params' => ['usuario_id'],
            ],
            'buscar' => [
                'description' => 'Buscar publicaciones o usuarios',
                'params' => ['query', 'tipo'],
            ],
            'trending' => [
                'description' => 'Ver publicaciones populares',
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
     * Acción: Ver timeline
     */
    private function action_timeline($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        $limite = absint($params['limite'] ?? 20);
        $desde_id = absint($params['desde'] ?? 0);

        // Timeline: publicaciones propias + de usuarios seguidos + públicas de la comunidad
        $sql = "SELECT p.* FROM $tabla_publicaciones p
                WHERE p.estado = 'publicado'
                AND (
                    p.autor_id = %d
                    OR p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                    OR p.visibilidad IN ('publica', 'comunidad')
                )
                AND p.id < %d
                ORDER BY p.fecha_publicacion DESC
                LIMIT %d";

        $desde_id = $desde_id > 0 ? $desde_id : PHP_INT_MAX;
        $publicaciones = $wpdb->get_results($wpdb->prepare($sql, $usuario_id, $usuario_id, $desde_id, $limite));

        return [
            'success' => true,
            'publicaciones' => array_map(function($p) {
                $autor = get_userdata($p->autor_id);
                return [
                    'id' => $p->id,
                    'autor' => [
                        'id' => $p->autor_id,
                        'nombre' => $autor ? $autor->display_name : 'Usuario',
                        'avatar' => get_avatar_url($p->autor_id),
                    ],
                    'contenido' => $p->contenido,
                    'tipo' => $p->tipo,
                    'adjuntos' => json_decode($p->adjuntos, true),
                    'me_gusta' => $p->me_gusta,
                    'comentarios' => $p->comentarios,
                    'compartidos' => $p->compartidos,
                    'fecha' => human_time_diff(strtotime($p->fecha_publicacion), current_time('timestamp')) . ' atrás',
                ];
            }, $publicaciones),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Moderación automática de contenido inapropiado
     * - Sugerencias de conexión con vecinos afines
     * - Resumen de trending topics en la comunidad
     * - Traducción automática de publicaciones
     */
    public function get_web_components() {
        return [
            'hero_social' => [
                'label' => __('Hero Red Social', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Red Social Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Conecta con tus vecinos de forma privada y segura', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_cta' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/hero',
            ],
            'timeline_feed' => [
                'label' => __('Feed de Publicaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-rss',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Últimas Publicaciones', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 10],
                    'mostrar_formulario' => ['type' => 'toggle', 'default' => true],
                    'tipo_feed' => ['type' => 'select', 'options' => ['timeline', 'comunidad', 'trending'], 'default' => 'timeline'],
                ],
                'template' => 'red-social/feed',
            ],
            'stats_comunidad' => [
                'label' => __('Estadísticas de la Comunidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-area',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestra Comunidad', 'flavor-chat-ia')],
                    'mostrar_miembros' => ['type' => 'toggle', 'default' => true],
                    'mostrar_publicaciones' => ['type' => 'toggle', 'default' => true],
                    'mostrar_actividad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/stats',
            ],
            'sugerencias_usuarios' => [
                'label' => __('Sugerencias de Conexión', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-admin-users',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Vecinos que Quizás Conozcas', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'criterio' => ['type' => 'select', 'options' => ['cercania', 'intereses', 'aleatorio'], 'default' => 'cercania'],
                ],
                'template' => 'red-social/sugerencias',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'social_timeline',
                'description' => 'Ver timeline de la red social',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'social_publicar',
                'description' => 'Crear nueva publicación',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contenido' => ['type' => 'string', 'description' => 'Contenido de la publicación'],
                    ],
                    'required' => ['contenido'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Red Social Comunitaria**

Plataforma social alternativa sin publicidad, algoritmos transparentes y centrada en la comunidad.

**Características:**
- Timeline cronológico (sin algoritmos ocultos)
- Publicaciones de texto, fotos y videos
- Comentarios y reacciones
- Hashtags y menciones
- Sin venta de datos personales
- Moderación comunitaria

**Privacidad:**
- Control total sobre quién ve tus publicaciones
- Opciones: Pública, Comunidad, Seguidores, Privada
- Sin rastreo publicitario
- Datos alojados en servidores comunitarios

**Diferencias con redes comerciales:**
- Sin publicidad
- Sin algoritmos de manipulación
- Propiedad y control comunitario
- Transparencia total
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Es segura mi información?',
                'respuesta' => 'Sí, tus datos están en servidores comunitarios y no se venden a terceros.',
            ],
            [
                'pregunta' => '¿Por qué no hay publicidad?',
                'respuesta' => 'Es una red social comunitaria autofinanciada, no comercial.',
            ],
        ];
    }
}

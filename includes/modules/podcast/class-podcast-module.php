<?php
/**
 * Módulo de Podcast para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Podcast - Plataforma de podcasting comunitario
 */
class Flavor_Chat_Podcast_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'podcast';
        $this->name = __('Podcast', 'flavor-chat-ia');
        $this->description = __('Plataforma de podcasting comunitario - crea, publica y escucha episodios de audio.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';

        return Flavor_Chat_Helpers::tabla_existe($tabla_podcasts);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Podcast no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'permite_subir_episodios' => true,
            'requiere_moderacion' => false,
            'duracion_maxima_minutos' => 120,
            'tamano_maximo_mb' => 100,
            'formatos_permitidos' => ['mp3', 'mp4', 'ogg'],
            'permite_comentarios' => true,
            'genera_rss' => true,
            'transcripcion_automatica' => false,
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
        $tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_podcasts)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

        $sql_podcasts = "CREATE TABLE IF NOT EXISTS $tabla_podcasts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            idioma varchar(10) DEFAULT 'es',
            estado enum('borrador','publicado','pausado') DEFAULT 'publicado',
            suscriptores int(11) DEFAULT 0,
            total_episodios int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_episodios = "CREATE TABLE IF NOT EXISTS $tabla_episodios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            podcast_id bigint(20) unsigned NOT NULL,
            numero_episodio int(11) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            archivo_url varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            transcripcion text DEFAULT NULL,
            estado enum('borrador','publicado','programado') DEFAULT 'publicado',
            reproducciones int(11) DEFAULT 0,
            me_gusta int(11) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_programacion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY podcast_id (podcast_id),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion)
        ) $charset_collate;";

        $sql_suscripciones = "CREATE TABLE IF NOT EXISTS $tabla_suscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            podcast_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            notificaciones_activas tinyint(1) DEFAULT 1,
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY podcast_usuario (podcast_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_podcasts);
        dbDelta($sql_episodios);
        dbDelta($sql_suscripciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_podcasts' => [
                'description' => 'Listar podcasts disponibles',
                'params' => ['categoria', 'limite'],
            ],
            'ver_podcast' => [
                'description' => 'Ver detalles de un podcast',
                'params' => ['podcast_id'],
            ],
            'listar_episodios' => [
                'description' => 'Listar episodios de un podcast',
                'params' => ['podcast_id', 'limite'],
            ],
            'reproducir_episodio' => [
                'description' => 'Obtener datos para reproducir episodio',
                'params' => ['episodio_id'],
            ],
            'suscribirse' => [
                'description' => 'Suscribirse a un podcast',
                'params' => ['podcast_id'],
            ],
            'crear_podcast' => [
                'description' => 'Crear nuevo podcast (requiere permisos)',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'subir_episodio' => [
                'description' => 'Subir nuevo episodio',
                'params' => ['podcast_id', 'titulo', 'archivo_url'],
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
     * Acción: Listar podcasts
     */
    private function action_listar_podcasts($params) {
        global $wpdb;
        $tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';

        $where = ['estado = %s'];
        $prepare_values = ['publicado'];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_podcasts WHERE $sql_where ORDER BY fecha_actualizacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $podcasts = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'total' => count($podcasts),
            'podcasts' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'imagen_url' => $p->imagen_url,
                    'categoria' => $p->categoria,
                    'suscriptores' => $p->suscriptores,
                    'total_episodios' => $p->total_episodios,
                ];
            }, $podcasts),
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_podcast' => [
                'label' => __('Hero Podcasts', 'flavor-chat-ia'),
                'description' => __('Sección hero para podcasts comunitarios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Podcasts de la Comunidad', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Historias, conversaciones y conocimiento local', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'podcast_destacado_id' => [
                        'type' => 'number',
                        'label' => __('ID del podcast destacado', 'flavor-chat-ia'),
                        'default' => 0,
                    ],
                ],
                'template' => 'podcast/hero',
            ],
            'podcast_grid' => [
                'label' => __('Grid de Podcasts', 'flavor-chat-ia'),
                'description' => __('Listado de podcasts en tarjetas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título de sección', 'flavor-chat-ia'),
                        'default' => __('Explora Nuestros Podcasts', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número de podcasts', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'categoria' => [
                        'type' => 'text',
                        'label' => __('Filtrar por categoría', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_suscriptores' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar número de suscriptores', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'podcast/grid',
            ],
            'episodios_recientes' => [
                'label' => __('Episodios Recientes', 'flavor-chat-ia'),
                'description' => __('Lista de últimos episodios publicados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-playlist-audio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Últimos Episodios', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número de episodios', 'flavor-chat-ia'),
                        'default' => 5,
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo de lista', 'flavor-chat-ia'),
                        'options' => ['lista', 'tarjetas'],
                        'default' => 'tarjetas',
                    ],
                ],
                'template' => 'podcast/episodios-recientes',
            ],
            'cta_crear_podcast' => [
                'label' => __('CTA Crear Podcast', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para crear tu podcast', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes algo que contar?', 'flavor-chat-ia'),
                    ],
                    'texto' => [
                        'type' => 'textarea',
                        'label' => __('Texto', 'flavor-chat-ia'),
                        'default' => __('Crea tu propio podcast y comparte tu voz con la comunidad', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Crear Mi Podcast', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'podcast/cta-crear',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'podcast_listar',
                'description' => 'Ver lista de podcasts comunitarios',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoría',
                        ],
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
**Plataforma de Podcast Comunitario**

Crea y escucha podcasts creados por miembros de la comunidad.

**Características:**
- Sube episodios de audio (MP3, MP4, OGG)
- Suscríbete a tus podcasts favoritos
- Recibe notificaciones de nuevos episodios
- Genera feed RSS automático
- Transcripción automática opcional

**Categorías sugeridas:**
- Noticias locales
- Entrevistas
- Historias del barrio
- Debates comunitarios
- Cultura y arte
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Quién puede crear un podcast?',
                'respuesta' => 'Cualquier miembro verificado de la comunidad puede crear su propio podcast.',
            ],
            [
                'pregunta' => '¿Cuánto puede durar un episodio?',
                'respuesta' => 'La duración máxima se configura por el administrador, generalmente hasta 2 horas.',
            ],
        ];
    }
}

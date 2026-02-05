<?php
/**
 * Módulo de Multimedia para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Multimedia - Galería y contenidos audiovisuales comunitarios
 */
class Flavor_Chat_Multimedia_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'multimedia';
        $this->name = __('Multimedia', 'flavor-chat-ia');
        $this->description = __('Galería de fotos, videos y contenidos audiovisuales de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        return Flavor_Chat_Helpers::tabla_existe($tabla_multimedia);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Multimedia no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'permite_subir' => true,
            'requiere_moderacion' => false,
            'formatos_imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'formatos_video' => ['mp4', 'mov', 'avi', 'webm'],
            'max_tamano_imagen_mb' => 10,
            'max_tamano_video_mb' => 100,
            'genera_thumbnails' => true,
            'permite_albumes' => true,
            'permite_geolocalizacion' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    public function maybe_create_tables() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        $tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';

        $sql_multimedia = "CREATE TABLE IF NOT EXISTS $tabla_multimedia (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            album_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('imagen','video','audio') DEFAULT 'imagen',
            archivo_url varchar(500) NOT NULL,
            thumbnail_url varchar(500) DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            ancho int(11) DEFAULT NULL,
            alto int(11) DEFAULT NULL,
            duracion_segundos int(11) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            vistas int(11) DEFAULT 0,
            me_gusta int(11) DEFAULT 0,
            estado enum('publico','privado','comunidad') DEFAULT 'comunidad',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY album_id (album_id),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_albumes = "CREATE TABLE IF NOT EXISTS $tabla_albumes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            portada_id bigint(20) unsigned DEFAULT NULL,
            privacidad enum('publico','privado','comunidad') DEFAULT 'comunidad',
            archivos_count int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_multimedia);
        dbDelta($sql_albumes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'galeria' => [
                'description' => 'Ver galería de multimedia',
                'params' => ['tipo', 'album_id', 'limite'],
            ],
            'subir' => [
                'description' => 'Subir archivo multimedia',
                'params' => ['archivo_url', 'tipo', 'titulo'],
            ],
            'albumes' => [
                'description' => 'Listar álbumes',
                'params' => ['usuario_id'],
            ],
            'crear_album' => [
                'description' => 'Crear álbum',
                'params' => ['nombre', 'descripcion'],
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
        return ['success' => false, 'error' => "Acción no implementada: {$action_name}"];
    }

    private function action_galeria($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $where = ["estado IN ('publico', 'comunidad')"];
        $prepare_values = [];

        if (!empty($params['tipo'])) {
            $where[] = 'tipo = %s';
            $prepare_values[] = $params['tipo'];
        }

        if (!empty($params['album_id'])) {
            $where[] = 'album_id = %d';
            $prepare_values[] = absint($params['album_id']);
        }

        $limite = absint($params['limite'] ?? 20);
        $sql = "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) . " ORDER BY fecha_creacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $archivos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'archivos' => array_map(function($a) {
                $usuario = get_userdata($a->usuario_id);
                return [
                    'id' => $a->id,
                    'tipo' => $a->tipo,
                    'titulo' => $a->titulo,
                    'url' => $a->archivo_url,
                    'thumbnail' => $a->thumbnail_url,
                    'autor' => $usuario ? $usuario->display_name : 'Usuario',
                    'vistas' => $a->vistas,
                    'me_gusta' => $a->me_gusta,
                ];
            }, $archivos),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Reconocimiento facial para etiquetado automático
     * - Búsqueda de imágenes por contenido
     * - Generación automática de álbumes por evento
     * - Sugerencias de fotos similares
     */
    public function get_web_components() {
        return [
            'hero_multimedia' => [
                'label' => __('Hero Multimedia', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Galería Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Momentos y recuerdos de nuestra comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_contador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'multimedia/hero',
            ],
            'galeria_grid' => [
                'label' => __('Grid de Galería', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Galería de Fotos', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [3, 4, 5, 6], 'default' => 4],
                    'limite' => ['type' => 'number', 'default' => 12],
                    'tipo' => ['type' => 'select', 'options' => ['todos', 'imagen', 'video'], 'default' => 'todos'],
                    'album_id' => ['type' => 'number', 'default' => 0],
                ],
                'template' => 'multimedia/galeria-grid',
            ],
            'albumes' => [
                'label' => __('Álbumes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Álbumes de la Comunidad', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                ],
                'template' => 'multimedia/albumes',
            ],
            'carousel_destacado' => [
                'label' => __('Carrusel Destacado', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-images-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Momentos Destacados', 'flavor-chat-ia')],
                    'album_id' => ['type' => 'number', 'default' => 0],
                    'autoplay' => ['type' => 'toggle', 'default' => true],
                    'intervalo_segundos' => ['type' => 'number', 'default' => 5],
                ],
                'template' => 'multimedia/carousel',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'multimedia_galeria',
                'description' => 'Ver galería de fotos y videos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'enum' => ['imagen', 'video'], 'description' => 'Tipo de contenido'],
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
**Galería Multimedia Comunitaria**

Espacio para compartir fotos, videos y contenido audiovisual de la comunidad.

**Características:**
- Sube fotos y videos
- Organiza en álbumes
- Etiqueta ubicaciones
- Comparte momentos del barrio
- Descarga en alta calidad

**Privacidad:**
- Público, Comunidad o Privado
- Control de descargas
- Moderación opcional
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            ['pregunta' => '¿Puedo descargar las fotos?', 'respuesta' => 'Sí, si el autor lo permite.'],
            ['pregunta' => '¿Cuánto espacio tengo?', 'respuesta' => 'Depende de la configuración del administrador.'],
        ];
    }
}

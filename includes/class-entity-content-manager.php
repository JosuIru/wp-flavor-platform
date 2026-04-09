<?php
/**
 * Entity Content Manager
 *
 * Gestiona las relaciones entre entidades (grupos, eventos, comunidades)
 * y su contenido asociado de módulos de red (foros, chats, galerías).
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Entity_Content_Manager {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Nombre de la tabla
     *
     * @var string
     */
    private $table_name;

    /**
     * Cache de relaciones
     *
     * @var array
     */
    private $cache = [];

    /**
     * Tipos de contenido soportados
     *
     * @var array
     */
    private static $content_types = [
        'foro'        => ['module' => 'foros', 'post_type' => null, 'table' => 'flavor_foros'],
        'chat_grupo'  => ['module' => 'chat_grupos', 'post_type' => null, 'table' => 'flavor_chat_grupos'],
        'rs_feed'     => ['module' => 'red_social', 'post_type' => null, 'table' => null],
        'galeria'     => ['module' => 'multimedia', 'post_type' => 'flavor_galeria', 'table' => null],
        'podcast'     => ['module' => 'podcast', 'post_type' => 'flavor_podcast', 'table' => null],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_entity_content';

        // Crear tabla si no existe
        add_action('plugins_loaded', [$this, 'maybe_create_table'], 5);
    }

    /**
     * Obtener instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Crear tabla si no existe
     */
    public function maybe_create_table() {
        global $wpdb;

        // Verificar si ya existe
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name)
        );

        if ($table_exists) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            content_type VARCHAR(50) NOT NULL,
            content_id BIGINT UNSIGNED NOT NULL,
            meta_data LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entity_content (entity_type, entity_id, content_type),
            KEY content_lookup (content_type, content_id),
            KEY entity_lookup (entity_type, entity_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Log
        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log('[EntityContent] Tabla creada: ' . $this->table_name);
        }
    }

    /**
     * Obtiene el contenido relacionado para una entidad
     *
     * @param string $entity_type Tipo de entidad (gc_grupo, evento, comunidad)
     * @param int    $entity_id   ID de la entidad
     * @param string $content_type Tipo de contenido (foro, chat_grupo, galeria)
     * @return int|null ID del contenido o null si no existe
     */
    public function get_content($entity_type, $entity_id, $content_type) {
        $cache_key = "{$entity_type}_{$entity_id}_{$content_type}";

        // Revisar cache
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        global $wpdb;

        $content_id = $wpdb->get_var($wpdb->prepare(
            "SELECT content_id FROM {$this->table_name}
             WHERE entity_type = %s AND entity_id = %d AND content_type = %s",
            $entity_type,
            $entity_id,
            $content_type
        ));

        $result = $content_id ? absint($content_id) : null;

        // Guardar en cache
        $this->cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Obtiene o crea contenido para una entidad
     *
     * @param string $entity_type  Tipo de entidad
     * @param int    $entity_id    ID de la entidad
     * @param string $content_type Tipo de contenido a crear
     * @param array  $args         Argumentos adicionales para la creación
     * @return int|false ID del contenido o false en error
     */
    public function get_or_create($entity_type, $entity_id, $content_type, $args = []) {
        // Primero intentar obtener existente
        $content_id = $this->get_content($entity_type, $entity_id, $content_type);

        if ($content_id) {
            return $content_id;
        }

        // Crear nuevo contenido
        $content_id = $this->create_content($entity_type, $entity_id, $content_type, $args);

        if (!$content_id) {
            return false;
        }

        // Guardar relación
        $this->link_content($entity_type, $entity_id, $content_type, $content_id, $args);

        return $content_id;
    }

    /**
     * Crea contenido nuevo según el tipo
     *
     * @param string $entity_type  Tipo de entidad
     * @param int    $entity_id    ID de la entidad
     * @param string $content_type Tipo de contenido
     * @param array  $args         Argumentos adicionales
     * @return int|false
     */
    protected function create_content($entity_type, $entity_id, $content_type, $args = []) {
        // Obtener nombre de la entidad para títulos
        $entity_name = $this->get_entity_name($entity_type, $entity_id);

        switch ($content_type) {
            case 'foro':
                return $this->create_foro($entity_type, $entity_id, $entity_name, $args);

            case 'chat_grupo':
                return $this->create_chat_grupo($entity_type, $entity_id, $entity_name, $args);

            case 'galeria':
                return $this->create_galeria($entity_type, $entity_id, $entity_name, $args);

            case 'rs_feed':
                // Los feeds de red social no se "crean", son consultas dinámicas
                // Retornamos un ID virtual basado en la entidad
                return $this->generate_virtual_id($entity_type, $entity_id, 'rs_feed');

            default:
                // Permitir que otros módulos manejen sus propios tipos
                $content_id = apply_filters(
                    'flavor_create_entity_content',
                    false,
                    $content_type,
                    $entity_type,
                    $entity_id,
                    $args
                );
                return $content_id;
        }
    }

    /**
     * Crea un foro para una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $entity_name
     * @param array  $args
     * @return int|false
     */
    protected function create_foro($entity_type, $entity_id, $entity_name, $args = []) {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'") !== $tabla_foros) {
            return false;
        }

        $titulo = $args['titulo'] ?? sprintf(
            __('Foro de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $entity_name
        );

        $descripcion = $args['descripcion'] ?? sprintf(
            __('Espacio de discusión para %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $entity_name
        );

        $result = $wpdb->insert($tabla_foros, [
            'nombre'       => $titulo,
            'descripcion'  => $descripcion,
            'tipo'         => 'entidad',
            'entity_type'  => $entity_type,
            'entity_id'    => $entity_id,
            'estado'       => 'activo',
            'creado_por'   => get_current_user_id(),
            'created_at'   => current_time('mysql'),
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Crea un chat de grupo para una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $entity_name
     * @param array  $args
     * @return int|false
     */
    protected function create_chat_grupo($entity_type, $entity_id, $entity_name, $args = []) {
        global $wpdb;
        $tabla_chats = $wpdb->prefix . 'flavor_chat_grupos';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_chats'") !== $tabla_chats) {
            return false;
        }

        $nombre = $args['nombre'] ?? sprintf(
            __('Chat de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $entity_name
        );

        $result = $wpdb->insert($tabla_chats, [
            'nombre'       => $nombre,
            'descripcion'  => $args['descripcion'] ?? '',
            'tipo'         => 'entidad',
            'entity_type'  => $entity_type,
            'entity_id'    => $entity_id,
            'es_privado'   => $args['es_privado'] ?? 0,
            'creado_por'   => get_current_user_id(),
            'created_at'   => current_time('mysql'),
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Crea una galería para una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $entity_name
     * @param array  $args
     * @return int|false
     */
    protected function create_galeria($entity_type, $entity_id, $entity_name, $args = []) {
        $titulo = $args['titulo'] ?? sprintf(
            __('Galería de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $entity_name
        );

        $post_id = wp_insert_post([
            'post_type'   => 'flavor_galeria',
            'post_title'  => $titulo,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'meta_input'  => [
                '_entity_type' => $entity_type,
                '_entity_id'   => $entity_id,
            ],
        ]);

        return $post_id && !is_wp_error($post_id) ? $post_id : false;
    }

    /**
     * Genera un ID virtual para contenido dinámico
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $content_type
     * @return int
     */
    protected function generate_virtual_id($entity_type, $entity_id, $content_type) {
        // Generar un hash numérico único
        $hash = crc32("{$entity_type}_{$entity_id}_{$content_type}");
        return abs($hash);
    }

    /**
     * Vincula contenido existente a una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $content_type
     * @param int    $content_id
     * @param array  $meta
     * @return bool
     */
    public function link_content($entity_type, $entity_id, $content_type, $content_id, $meta = []) {
        global $wpdb;

        $result = $wpdb->replace($this->table_name, [
            'entity_type'  => $entity_type,
            'entity_id'    => $entity_id,
            'content_type' => $content_type,
            'content_id'   => $content_id,
            'meta_data'    => !empty($meta) ? wp_json_encode($meta) : null,
            'updated_at'   => current_time('mysql'),
        ]);

        // Limpiar cache
        $cache_key = "{$entity_type}_{$entity_id}_{$content_type}";
        unset($this->cache[$cache_key]);

        return $result !== false;
    }

    /**
     * Desvincula contenido de una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $content_type
     * @return bool
     */
    public function unlink_content($entity_type, $entity_id, $content_type) {
        global $wpdb;

        $result = $wpdb->delete($this->table_name, [
            'entity_type'  => $entity_type,
            'entity_id'    => $entity_id,
            'content_type' => $content_type,
        ]);

        // Limpiar cache
        $cache_key = "{$entity_type}_{$entity_id}_{$content_type}";
        unset($this->cache[$cache_key]);

        return $result !== false;
    }

    /**
     * Obtiene todo el contenido vinculado a una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @return array
     */
    public function get_all_content($entity_type, $entity_id) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT content_type, content_id, meta_data, created_at
             FROM {$this->table_name}
             WHERE entity_type = %s AND entity_id = %d",
            $entity_type,
            $entity_id
        ), ARRAY_A);

        $content = [];
        foreach ($results as $row) {
            $content[$row['content_type']] = [
                'id'         => absint($row['content_id']),
                'meta'       => $row['meta_data'] ? json_decode($row['meta_data'], true) : [],
                'created_at' => $row['created_at'],
            ];
        }

        return $content;
    }

    /**
     * Obtiene todas las entidades vinculadas a un contenido
     *
     * @param string $content_type
     * @param int    $content_id
     * @return array
     */
    public function get_linked_entities($content_type, $content_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT entity_type, entity_id, meta_data
             FROM {$this->table_name}
             WHERE content_type = %s AND content_id = %d",
            $content_type,
            $content_id
        ), ARRAY_A);
    }

    /**
     * Obtiene el nombre de una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @return string
     */
    protected function get_entity_name($entity_type, $entity_id) {
        // Intentar obtener de post types
        $post = get_post($entity_id);
        if ($post) {
            return $post->post_title;
        }

        // Intentar desde tablas personalizadas
        global $wpdb;

        $mappings = [
            'gc_grupo'   => ['tabla' => $wpdb->prefix . 'flavor_gc_grupos', 'campo' => 'nombre'],
            'evento'     => ['tabla' => $wpdb->prefix . 'flavor_eventos', 'campo' => 'titulo'],
            'comunidad'  => ['tabla' => $wpdb->prefix . 'flavor_comunidades', 'campo' => 'nombre'],
        ];

        if (isset($mappings[$entity_type])) {
            $config = $mappings[$entity_type];
            $nombre = $wpdb->get_var($wpdb->prepare(
                "SELECT {$config['campo']} FROM {$config['tabla']} WHERE id = %d",
                $entity_id
            ));
            if ($nombre) {
                return $nombre;
            }
        }

        // Fallback
        return sprintf(__('Entidad #%d', FLAVOR_PLATFORM_TEXT_DOMAIN), $entity_id);
    }

    /**
     * Elimina todo el contenido vinculado cuando se elimina una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param bool   $delete_content También eliminar el contenido (no solo la relación)
     */
    public function cleanup_entity($entity_type, $entity_id, $delete_content = false) {
        global $wpdb;

        if ($delete_content) {
            // Obtener todo el contenido vinculado
            $contents = $this->get_all_content($entity_type, $entity_id);

            foreach ($contents as $type => $data) {
                $this->delete_content($type, $data['id']);
            }
        }

        // Eliminar todas las relaciones
        $wpdb->delete($this->table_name, [
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
        ]);

        // Limpiar cache relacionado
        foreach (array_keys($this->cache) as $key) {
            if (strpos($key, "{$entity_type}_{$entity_id}_") === 0) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Elimina contenido según su tipo
     *
     * @param string $content_type
     * @param int    $content_id
     * @return bool
     */
    protected function delete_content($content_type, $content_id) {
        global $wpdb;

        switch ($content_type) {
            case 'foro':
                $tabla = $wpdb->prefix . 'flavor_foros';
                return $wpdb->delete($tabla, ['id' => $content_id]) !== false;

            case 'chat_grupo':
                $tabla = $wpdb->prefix . 'flavor_chat_grupos';
                return $wpdb->delete($tabla, ['id' => $content_id]) !== false;

            case 'galeria':
                return wp_delete_post($content_id, true) !== false;

            default:
                // Hook para otros tipos
                return apply_filters(
                    'flavor_delete_entity_content',
                    false,
                    $content_type,
                    $content_id
                );
        }
    }

    /**
     * Verifica si existe contenido para una entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @param string $content_type
     * @return bool
     */
    public function has_content($entity_type, $entity_id, $content_type) {
        return $this->get_content($entity_type, $entity_id, $content_type) !== null;
    }

    /**
     * Obtiene estadísticas de contenido por entidad
     *
     * @param string $entity_type
     * @param int    $entity_id
     * @return array
     */
    public function get_content_stats($entity_type, $entity_id) {
        $contents = $this->get_all_content($entity_type, $entity_id);
        $stats = [];

        foreach ($contents as $type => $data) {
            $stats[$type] = [
                'exists' => true,
                'id'     => $data['id'],
            ];

            // Añadir conteos específicos según el tipo
            switch ($type) {
                case 'foro':
                    $stats[$type]['count'] = $this->count_foro_posts($data['id']);
                    break;
                case 'chat_grupo':
                    $stats[$type]['count'] = $this->count_chat_messages($data['id']);
                    break;
                case 'galeria':
                    $stats[$type]['count'] = $this->count_galeria_items($data['id']);
                    break;
            }
        }

        return $stats;
    }

    /**
     * Cuenta posts de un foro
     */
    protected function count_foro_posts($foro_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_foro_posts';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE foro_id = %d",
            $foro_id
        ));
    }

    /**
     * Cuenta mensajes de un chat
     */
    protected function count_chat_messages($chat_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_chat_mensajes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE grupo_id = %d",
            $chat_id
        ));
    }

    /**
     * Cuenta items de una galería
     */
    protected function count_galeria_items($galeria_id) {
        return (int) get_post_meta($galeria_id, '_item_count', true) ?: 0;
    }
}

// Inicializar
Flavor_Entity_Content_Manager::get_instance();

<?php
/**
 * Repositorio de integraciones entre módulos
 *
 * Centraliza las queries y configuración de tablas de integración
 * entre posts y módulos del ecosistema Flavor.
 *
 * @package FlavorPlatform
 * @subpackage Repositories
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio para queries de integraciones de módulos
 */
class Flavor_Module_Integration_Repository {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    private $prefix;

    /**
     * Caché de verificación de tablas
     *
     * @var array
     */
    private $table_exists_cache = [];

    /**
     * Configuración de módulos con sus tablas y campos
     *
     * @var array
     */
    private const MODULE_CONFIG = [
        'eventos' => [
            'tabla_relacion' => 'eventos_posts',
            'tabla_entidad'  => 'eventos',
            'campo_fk'       => 'evento_id',
            'campo_nombre'   => 'titulo',
        ],
        'cursos' => [
            'tabla_relacion' => 'cursos_posts',
            'tabla_entidad'  => 'cursos',
            'campo_fk'       => 'curso_id',
            'campo_nombre'   => 'titulo',
        ],
        'talleres' => [
            'tabla_relacion' => 'talleres_posts',
            'tabla_entidad'  => 'talleres',
            'campo_fk'       => 'taller_id',
            'campo_nombre'   => 'titulo',
        ],
        'campanias' => [
            'tabla_relacion' => 'campanias_posts',
            'tabla_entidad'  => 'campanias',
            'campo_fk'       => 'campania_id',
            'campo_nombre'   => 'titulo',
        ],
        'comunidades' => [
            'tabla_relacion' => 'comunidades_posts',
            'tabla_entidad'  => 'comunidades',
            'campo_fk'       => 'comunidad_id',
            'campo_nombre'   => 'nombre',
        ],
        'colectivos' => [
            'tabla_relacion' => 'colectivos_posts',
            'tabla_entidad'  => 'colectivos',
            'campo_fk'       => 'colectivo_id',
            'campo_nombre'   => 'nombre',
        ],
        'email_marketing' => [
            'tabla_relacion' => 'email_newsletter_posts',
            'tabla_entidad'  => 'email_campaigns',
            'campo_fk'       => 'newsletter_id',
            'campo_nombre'   => 'nombre',
        ],
        'biblioteca' => [
            'tabla_relacion' => 'biblioteca_posts',
            'tabla_entidad'  => 'biblioteca_categorias',
            'campo_fk'       => 'categoria_id',
            'campo_nombre'   => 'nombre',
        ],
        'foros' => [
            'tabla_relacion' => 'foros_posts',
            'tabla_entidad'  => 'foros',
            'campo_fk'       => 'foro_id',
            'campo_nombre'   => 'nombre',
        ],
    ];

    /**
     * Obtiene la instancia singleton
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
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = ($wpdb && isset($wpdb->prefix)) ? $wpdb->prefix . 'flavor_' : 'wp_flavor_';
    }

    /**
     * Obtiene la configuración de un módulo
     *
     * @param string $modulo ID del módulo.
     * @return array|null Configuración o null si no existe.
     */
    public function get_module_config($modulo) {
        return self::MODULE_CONFIG[$modulo] ?? null;
    }

    /**
     * Obtiene todos los módulos configurados
     *
     * @return array IDs de módulos disponibles.
     */
    public function get_available_modules() {
        return array_keys(self::MODULE_CONFIG);
    }

    /**
     * Verifica si una tabla existe (con caché)
     *
     * @param string $tabla Nombre completo de la tabla.
     * @return bool
     */
    public function table_exists($tabla) {
        if (isset($this->table_exists_cache[$tabla])) {
            return $this->table_exists_cache[$tabla];
        }

        $transient_key = 'flavor_tbl_' . md5($tabla);
        $cached = get_transient($transient_key);

        if ($cached !== false) {
            $this->table_exists_cache[$tabla] = (bool) $cached;
            return $this->table_exists_cache[$tabla];
        }

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;

        $this->table_exists_cache[$tabla] = $exists;
        set_transient($transient_key, $exists ? '1' : '0', HOUR_IN_SECONDS);

        return $exists;
    }

    /**
     * Obtiene posts integrados con un elemento de módulo
     *
     * @param string $modulo     ID del módulo.
     * @param int    $elemento_id ID del elemento.
     * @param int    $limite     Máximo de resultados.
     * @return WP_Post[] Posts encontrados.
     */
    public function get_posts_by_module_element($modulo, $elemento_id, $limite = 10) {
        $config = $this->get_module_config($modulo);
        if (!$config) {
            return [];
        }

        $tabla = $this->prefix . $config['tabla_relacion'];
        if (!$this->table_exists($tabla)) {
            return [];
        }

        global $wpdb;
        $campo_fk = $config['campo_fk'];
        $limite = max(1, min(50, (int) $limite));

        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$tabla} WHERE {$campo_fk} = %d ORDER BY fecha DESC LIMIT %d",
            $elemento_id,
            $limite
        ));

        if (empty($post_ids)) {
            return [];
        }

        return get_posts([
            'post__in'       => $post_ids,
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => $limite,
            'orderby'        => 'post__in',
        ]);
    }

    /**
     * Obtiene las integraciones de un post
     *
     * @param int  $post_id    ID del post.
     * @param bool $usar_cache Si usar caché de transients.
     * @return array Integraciones encontradas.
     */
    public function get_post_integrations($post_id, $usar_cache = true) {
        $cache_key = 'flavor_post_int_' . $post_id;

        if ($usar_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        global $wpdb;
        $integraciones = [];

        foreach (self::MODULE_CONFIG as $modulo => $config) {
            $tabla_relacion = $this->prefix . $config['tabla_relacion'];

            if (!$this->table_exists($tabla_relacion)) {
                continue;
            }

            $campo_fk = $config['campo_fk'];
            $relaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT {$campo_fk}, fecha FROM {$tabla_relacion} WHERE post_id = %d",
                $post_id
            ));

            if (empty($relaciones)) {
                continue;
            }

            foreach ($relaciones as $rel) {
                $elemento_id = $rel->{$campo_fk} ?? null;
                $nombre = $this->get_element_name($modulo, $elemento_id);

                $integraciones[] = [
                    'modulo'          => $modulo,
                    'elemento_id'     => $elemento_id,
                    'nombre_elemento' => $nombre,
                    'fecha'           => $rel->fecha ?? '',
                ];
            }
        }

        if ($usar_cache && !empty($integraciones)) {
            set_transient($cache_key, $integraciones, 5 * MINUTE_IN_SECONDS);
        }

        return $integraciones;
    }

    /**
     * Obtiene el nombre de un elemento de módulo
     *
     * @param string $modulo     ID del módulo.
     * @param int    $elemento_id ID del elemento.
     * @return string Nombre del elemento o cadena vacía.
     */
    public function get_element_name($modulo, $elemento_id) {
        if (!$elemento_id) {
            return '';
        }

        $config = $this->get_module_config($modulo);
        if (!$config) {
            return '';
        }

        $tabla = $this->prefix . $config['tabla_entidad'];
        if (!$this->table_exists($tabla)) {
            return '';
        }

        global $wpdb;
        $campo_nombre = $config['campo_nombre'];

        return (string) $wpdb->get_var($wpdb->prepare(
            "SELECT {$campo_nombre} FROM {$tabla} WHERE id = %d",
            $elemento_id
        ));
    }

    /**
     * Registra una nueva integración
     *
     * @param int    $post_id     ID del post.
     * @param string $modulo      ID del módulo.
     * @param int    $elemento_id ID del elemento.
     * @return int|false ID de la inserción o false si falla.
     */
    public function create_integration($post_id, $modulo, $elemento_id) {
        $config = $this->get_module_config($modulo);
        if (!$config) {
            return false;
        }

        $tabla = $this->prefix . $config['tabla_relacion'];
        if (!$this->table_exists($tabla)) {
            return false;
        }

        global $wpdb;
        $campo_fk = $config['campo_fk'];

        $inserted = $wpdb->insert($tabla, [
            'post_id'   => $post_id,
            $campo_fk   => $elemento_id,
            'fecha'     => current_time('mysql'),
        ]);

        if ($inserted) {
            // Invalidar caché del post
            delete_transient('flavor_post_int_' . $post_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Elimina una integración
     *
     * @param int    $post_id     ID del post.
     * @param string $modulo      ID del módulo.
     * @param int    $elemento_id ID del elemento (opcional, si no se pasa elimina todas del módulo).
     * @return int Número de filas eliminadas.
     */
    public function delete_integration($post_id, $modulo, $elemento_id = null) {
        $config = $this->get_module_config($modulo);
        if (!$config) {
            return 0;
        }

        $tabla = $this->prefix . $config['tabla_relacion'];
        if (!$this->table_exists($tabla)) {
            return 0;
        }

        global $wpdb;
        $where = ['post_id' => $post_id];

        if ($elemento_id !== null) {
            $where[$config['campo_fk']] = $elemento_id;
        }

        $deleted = $wpdb->delete($tabla, $where);

        if ($deleted) {
            delete_transient('flavor_post_int_' . $post_id);
        }

        return $deleted ?: 0;
    }

    /**
     * Cuenta integraciones de un elemento
     *
     * @param string $modulo     ID del módulo.
     * @param int    $elemento_id ID del elemento.
     * @return int Número de integraciones.
     */
    public function count_element_integrations($modulo, $elemento_id) {
        $config = $this->get_module_config($modulo);
        if (!$config) {
            return 0;
        }

        $tabla = $this->prefix . $config['tabla_relacion'];
        if (!$this->table_exists($tabla)) {
            return 0;
        }

        global $wpdb;
        $campo_fk = $config['campo_fk'];

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE {$campo_fk} = %d",
            $elemento_id
        ));
    }

    /**
     * Invalida caché de verificación de tablas
     *
     * @return void
     */
    public function invalidate_table_cache() {
        $this->table_exists_cache = [];
    }
}

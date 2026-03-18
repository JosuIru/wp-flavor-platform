<?php
/**
 * Traductor de Slugs/URLs
 *
 * Permite traducir slugs de posts, páginas y CPTs para URLs multilingües.
 * Ejemplo: /productos/zapato-rojo/ → /en/products/red-shoe/
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Slug_Translator {

    /**
     * Instancia singleton
     *
     * @var Flavor_Slug_Translator|null
     */
    private static $instance = null;

    /**
     * Tabla de slugs traducidos
     *
     * @var string
     */
    private $table_slugs;

    /**
     * Cache de slugs
     *
     * @var array
     */
    private $slug_cache = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Slug_Translator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->table_slugs = $wpdb->prefix . 'flavor_translated_slugs';

        // Hooks para reescritura de URLs
        add_filter('post_link', array($this, 'filter_post_permalink'), 20, 3);
        add_filter('page_link', array($this, 'filter_page_permalink'), 20, 3);
        add_filter('post_type_link', array($this, 'filter_cpt_permalink'), 20, 4);
        add_filter('term_link', array($this, 'filter_term_permalink'), 20, 3);

        // Hook para resolver slugs traducidos
        add_action('parse_request', array($this, 'resolve_translated_slug'), 5);

        // Hooks admin
        if (is_admin()) {
            add_action('save_post', array($this, 'maybe_generate_slug_translation'), 20, 2);
            add_action('wp_ajax_flavor_ml_save_slug', array($this, 'ajax_save_slug'));
            add_action('wp_ajax_flavor_ml_translate_slug_ai', array($this, 'ajax_translate_slug_ai'));
        }

        // Rewrite rules para slugs traducidos
        add_action('init', array($this, 'add_rewrite_rules'), 20);
    }

    /**
     * Crea la tabla de slugs traducidos
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'flavor_translated_slugs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type varchar(50) NOT NULL DEFAULT 'post',
            object_id bigint(20) UNSIGNED NOT NULL,
            language_code varchar(10) NOT NULL,
            original_slug varchar(200) NOT NULL,
            translated_slug varchar(200) NOT NULL,
            post_type varchar(50) DEFAULT NULL,
            taxonomy varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY object_lang (object_type, object_id, language_code),
            KEY translated_slug (translated_slug),
            KEY original_slug (original_slug),
            KEY language_code (language_code)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Guarda un slug traducido
     *
     * @param string $object_type Tipo de objeto (post, term)
     * @param int    $object_id   ID del objeto
     * @param string $lang        Código de idioma
     * @param string $original    Slug original
     * @param string $translated  Slug traducido
     * @param array  $meta        Metadatos adicionales
     * @return bool|int
     */
    public function save_slug($object_type, $object_id, $lang, $original, $translated, $meta = array()) {
        global $wpdb;

        // Sanitizar el slug traducido
        $translated = sanitize_title($translated);

        if (empty($translated)) {
            return false;
        }

        // Verificar si ya existe
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_slugs}
             WHERE object_type = %s AND object_id = %d AND language_code = %s",
            $object_type, $object_id, $lang
        ));

        $data = array(
            'object_type'     => $object_type,
            'object_id'       => $object_id,
            'language_code'   => $lang,
            'original_slug'   => $original,
            'translated_slug' => $translated,
            'post_type'       => $meta['post_type'] ?? null,
            'taxonomy'        => $meta['taxonomy'] ?? null,
            'updated_at'      => current_time('mysql'),
        );

        if ($existing_id) {
            $result = $wpdb->update($this->table_slugs, $data, array('id' => $existing_id));
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($this->table_slugs, $data);
        }

        // Limpiar cache
        $this->clear_cache($object_type, $object_id);

        // Flush rewrite rules
        flush_rewrite_rules(false);

        return $result;
    }

    /**
     * Obtiene el slug traducido
     *
     * @param string $object_type Tipo de objeto
     * @param int    $object_id   ID del objeto
     * @param string $lang        Código de idioma
     * @return string|null
     */
    public function get_translated_slug($object_type, $object_id, $lang) {
        $cache_key = "{$object_type}_{$object_id}_{$lang}";

        if (isset($this->slug_cache[$cache_key])) {
            return $this->slug_cache[$cache_key];
        }

        global $wpdb;

        $slug = $wpdb->get_var($wpdb->prepare(
            "SELECT translated_slug FROM {$this->table_slugs}
             WHERE object_type = %s AND object_id = %d AND language_code = %s",
            $object_type, $object_id, $lang
        ));

        $this->slug_cache[$cache_key] = $slug;

        return $slug;
    }

    /**
     * Obtiene el objeto por slug traducido
     *
     * @param string $translated_slug Slug traducido
     * @param string $lang            Código de idioma
     * @param string $object_type     Tipo de objeto (opcional)
     * @return object|null
     */
    public function get_object_by_translated_slug($translated_slug, $lang, $object_type = null) {
        global $wpdb;

        $sql = "SELECT object_type, object_id, original_slug, post_type, taxonomy
                FROM {$this->table_slugs}
                WHERE translated_slug = %s AND language_code = %s";

        $params = array($translated_slug, $lang);

        if ($object_type) {
            $sql .= " AND object_type = %s";
            $params[] = $object_type;
        }

        return $wpdb->get_row($wpdb->prepare($sql, $params));
    }

    /**
     * Obtiene todos los slugs traducidos de un objeto
     *
     * @param string $object_type Tipo de objeto
     * @param int    $object_id   ID del objeto
     * @return array
     */
    public function get_all_slugs($object_type, $object_id) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, translated_slug, original_slug
             FROM {$this->table_slugs}
             WHERE object_type = %s AND object_id = %d",
            $object_type, $object_id
        ), ARRAY_A);

        $slugs = array();
        foreach ($results as $row) {
            $slugs[$row['language_code']] = $row['translated_slug'];
        }

        return $slugs;
    }

    /**
     * Filtra el permalink de posts
     *
     * @param string  $permalink Permalink original
     * @param WP_Post $post      Objeto post
     * @param bool    $leavename Dejar el nombre
     * @return string
     */
    public function filter_post_permalink($permalink, $post, $leavename = false) {
        if (is_admin() && !wp_doing_ajax()) {
            return $permalink;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        if ($core->is_default_language()) {
            return $permalink;
        }

        $translated_slug = $this->get_translated_slug('post', $post->ID, $current_lang);

        if (!$translated_slug) {
            return $permalink;
        }

        // Reemplazar el slug original por el traducido
        $original_slug = $post->post_name;
        return str_replace("/{$original_slug}/", "/{$translated_slug}/", $permalink);
    }

    /**
     * Filtra el permalink de páginas
     *
     * @param string $link    Link original
     * @param int    $post_id ID del post
     * @param bool   $sample  Es muestra
     * @return string
     */
    public function filter_page_permalink($link, $post_id, $sample = false) {
        $post = get_post($post_id);
        if (!$post) {
            return $link;
        }

        return $this->filter_post_permalink($link, $post, false);
    }

    /**
     * Filtra el permalink de CPTs
     *
     * @param string  $link      Link original
     * @param WP_Post $post      Objeto post
     * @param bool    $leavename Dejar el nombre
     * @param bool    $sample    Es muestra
     * @return string
     */
    public function filter_cpt_permalink($link, $post, $leavename = false, $sample = false) {
        return $this->filter_post_permalink($link, $post, $leavename);
    }

    /**
     * Filtra el permalink de términos
     *
     * @param string $link     Link original
     * @param object $term     Objeto término
     * @param string $taxonomy Taxonomía
     * @return string
     */
    public function filter_term_permalink($link, $term, $taxonomy) {
        if (is_admin() && !wp_doing_ajax()) {
            return $link;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        if ($core->is_default_language()) {
            return $link;
        }

        $translated_slug = $this->get_translated_slug('term', $term->term_id, $current_lang);

        if (!$translated_slug) {
            return $link;
        }

        // Reemplazar el slug original por el traducido
        return str_replace("/{$term->slug}/", "/{$translated_slug}/", $link);
    }

    /**
     * Resuelve slugs traducidos en las peticiones
     *
     * @param WP $wp Objeto WP
     */
    public function resolve_translated_slug($wp) {
        if (empty($wp->request)) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        if ($core->is_default_language()) {
            return;
        }

        // Obtener el último segmento de la URL (el slug)
        $path_parts = explode('/', trim($wp->request, '/'));
        $potential_slug = end($path_parts);

        // Buscar si es un slug traducido
        $object = $this->get_object_by_translated_slug($potential_slug, $current_lang);

        if (!$object) {
            return;
        }

        // Redirigir la consulta al objeto correcto
        if ($object->object_type === 'post') {
            $post = get_post($object->object_id);
            if ($post) {
                if ($post->post_type === 'page') {
                    $wp->query_vars['pagename'] = $post->post_name;
                    $wp->query_vars['page_id'] = $post->ID;
                } else {
                    $wp->query_vars['name'] = $post->post_name;
                    $wp->query_vars['p'] = $post->ID;
                    $wp->query_vars['post_type'] = $post->post_type;
                }
            }
        } elseif ($object->object_type === 'term') {
            $term = get_term($object->object_id);
            if ($term && !is_wp_error($term)) {
                $wp->query_vars[$term->taxonomy] = $term->slug;
            }
        }
    }

    /**
     * Añade reglas de reescritura
     */
    public function add_rewrite_rules() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $lang_codes = implode('|', array_keys($languages));

        // Regla para capturar slugs traducidos con prefijo de idioma
        add_rewrite_rule(
            "^({$lang_codes})/(.+?)/?$",
            'index.php?lang=$matches[1]&flavor_translated_path=$matches[2]',
            'top'
        );

        add_rewrite_tag('%flavor_translated_path%', '([^&]+)');
    }

    /**
     * Genera traducción automática de slug al guardar post
     *
     * @param int     $post_id ID del post
     * @param WP_Post $post    Objeto post
     */
    public function maybe_generate_slug_translation($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_status !== 'publish') {
            return;
        }

        // Solo si está habilitada la auto-traducción de slugs
        if (!Flavor_Multilingual::get_option('auto_translate_slugs')) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $original_slug = $post->post_name;

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            // Verificar si ya tiene slug traducido
            $existing = $this->get_translated_slug('post', $post_id, $code);
            if ($existing) {
                continue;
            }

            // Generar traducción con IA
            $translated_slug = $this->translate_slug_with_ai($original_slug, $post->post_title, $code);

            if ($translated_slug) {
                $this->save_slug('post', $post_id, $code, $original_slug, $translated_slug, array(
                    'post_type' => $post->post_type,
                ));
            }
        }
    }

    /**
     * Traduce un slug usando IA
     *
     * @param string $slug       Slug original
     * @param string $title      Título para contexto
     * @param string $target_lang Idioma destino
     * @return string|null
     */
    public function translate_slug_with_ai($slug, $title, $target_lang) {
        if (!class_exists('Flavor_AI_Translator')) {
            return null;
        }

        $ai_translator = Flavor_AI_Translator::get_instance();

        // Convertir slug a texto legible
        $readable_slug = str_replace(array('-', '_'), ' ', $slug);

        // Traducir
        $translated = $ai_translator->translate($readable_slug, $target_lang, array(
            'context' => 'url_slug',
            'title'   => $title,
        ));

        if ($translated && !is_wp_error($translated)) {
            // Convertir a slug válido
            return sanitize_title($translated);
        }

        return null;
    }

    /**
     * AJAX: Guarda un slug traducido
     */
    public function ajax_save_slug() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $object_type = sanitize_key($_POST['object_type'] ?? 'post');
        $object_id = absint($_POST['object_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $slug = sanitize_title($_POST['slug'] ?? '');

        if (!$object_id || !$lang || !$slug) {
            wp_send_json_error(array('message' => 'Datos incompletos'));
        }

        // Obtener slug original
        if ($object_type === 'post') {
            $post = get_post($object_id);
            $original_slug = $post ? $post->post_name : '';
            $meta = array('post_type' => $post ? $post->post_type : '');
        } else {
            $term = get_term($object_id);
            $original_slug = $term && !is_wp_error($term) ? $term->slug : '';
            $meta = array('taxonomy' => $term && !is_wp_error($term) ? $term->taxonomy : '');
        }

        $result = $this->save_slug($object_type, $object_id, $lang, $original_slug, $slug, $meta);

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Slug guardado',
                'slug'    => $slug,
            ));
        } else {
            wp_send_json_error(array('message' => 'Error al guardar'));
        }
    }

    /**
     * AJAX: Traduce un slug con IA
     */
    public function ajax_translate_slug_ai() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $object_type = sanitize_key($_POST['object_type'] ?? 'post');
        $object_id = absint($_POST['object_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$object_id || !$lang) {
            wp_send_json_error(array('message' => 'Datos incompletos'));
        }

        // Obtener datos del objeto
        if ($object_type === 'post') {
            $post = get_post($object_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Post no encontrado'));
            }
            $original_slug = $post->post_name;
            $title = $post->post_title;
        } else {
            $term = get_term($object_id);
            if (!$term || is_wp_error($term)) {
                wp_send_json_error(array('message' => 'Término no encontrado'));
            }
            $original_slug = $term->slug;
            $title = $term->name;
        }

        $translated_slug = $this->translate_slug_with_ai($original_slug, $title, $lang);

        if ($translated_slug) {
            wp_send_json_success(array(
                'slug'     => $translated_slug,
                'original' => $original_slug,
            ));
        } else {
            wp_send_json_error(array('message' => 'Error en la traducción'));
        }
    }

    /**
     * Limpia la cache
     *
     * @param string $object_type Tipo de objeto
     * @param int    $object_id   ID del objeto
     */
    private function clear_cache($object_type = null, $object_id = null) {
        if ($object_type && $object_id) {
            foreach ($this->slug_cache as $key => $value) {
                if (strpos($key, "{$object_type}_{$object_id}_") === 0) {
                    unset($this->slug_cache[$key]);
                }
            }
        } else {
            $this->slug_cache = array();
        }
    }

    /**
     * Elimina los slugs de un objeto
     *
     * @param string $object_type Tipo de objeto
     * @param int    $object_id   ID del objeto
     * @param string $lang        Idioma específico (opcional)
     * @return int|false
     */
    public function delete_slugs($object_type, $object_id, $lang = null) {
        global $wpdb;

        $where = array(
            'object_type' => $object_type,
            'object_id'   => $object_id,
        );

        if ($lang) {
            $where['language_code'] = $lang;
        }

        $result = $wpdb->delete($this->table_slugs, $where);

        $this->clear_cache($object_type, $object_id);

        return $result;
    }

    /**
     * Genera la URL completa con slug traducido
     *
     * @param int    $post_id ID del post
     * @param string $lang    Código de idioma
     * @return string
     */
    public function get_translated_permalink($post_id, $lang) {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }

        $translated_slug = $this->get_translated_slug('post', $post_id, $lang);
        $original_permalink = get_permalink($post_id);

        if (!$translated_slug) {
            // Añadir solo el idioma
            $url_manager = Flavor_URL_Manager::get_instance();
            return $url_manager->add_language_to_url($original_permalink, $lang);
        }

        // Reemplazar slug y añadir idioma
        $translated_permalink = str_replace(
            "/{$post->post_name}/",
            "/{$translated_slug}/",
            $original_permalink
        );

        $url_manager = Flavor_URL_Manager::get_instance();
        return $url_manager->add_language_to_url($translated_permalink, $lang);
    }
}

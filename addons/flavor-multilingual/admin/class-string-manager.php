<?php
/**
 * Gestor de cadenas traducibles (strings)
 *
 * Permite capturar, gestionar y traducir cadenas estáticas del tema y plugins.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_String_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_String_Manager|null
     */
    private static $instance = null;

    /**
     * Dominios a capturar
     *
     * @var array
     */
    private $tracked_domains = array();

    /**
     * Cadenas capturadas en esta request
     *
     * @var array
     */
    private $captured_strings = array();

    /**
     * Si está en modo captura
     *
     * @var bool
     */
    private $capture_mode = false;

    /**
     * Tabla de cadenas
     *
     * @var string
     */
    private $table;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_String_Manager
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
        $this->table = $wpdb->prefix . 'flavor_string_translations';

        // Dominios por defecto a rastrear
        $this->tracked_domains = apply_filters('flavor_multilingual_tracked_domains', array(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            'flavor-multilingual',
            get_template(),
            get_stylesheet(),
        ));

        // Hook de gettext para captura y traducción
        add_filter('gettext', array($this, 'filter_gettext'), 10, 3);
        add_filter('gettext_with_context', array($this, 'filter_gettext_with_context'), 10, 4);
        add_filter('ngettext', array($this, 'filter_ngettext'), 10, 5);

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_get_strings', array($this, 'ajax_get_strings'));
        add_action('wp_ajax_flavor_ml_save_string', array($this, 'ajax_save_string'));
        add_action('wp_ajax_flavor_ml_scan_strings', array($this, 'ajax_scan_strings'));
        add_action('wp_ajax_flavor_ml_delete_string', array($this, 'ajax_delete_string'));
        add_action('wp_ajax_flavor_ml_translate_string_ai', array($this, 'ajax_translate_string_ai'));
    }

    /**
     * Activa el modo captura
     */
    public function enable_capture_mode() {
        $this->capture_mode = true;
    }

    /**
     * Desactiva el modo captura
     */
    public function disable_capture_mode() {
        $this->capture_mode = false;
    }

    /**
     * Filtro de gettext
     *
     * @param string $translation Traducción
     * @param string $text        Texto original
     * @param string $domain      Dominio
     * @return string
     */
    public function filter_gettext($translation, $text, $domain) {
        // Capturar cadena si está en modo captura
        if ($this->capture_mode && in_array($domain, $this->tracked_domains)) {
            $this->capture_string($text, $domain);
        }

        // Aplicar traducción personalizada si existe
        return $this->maybe_translate($text, $domain, $translation);
    }

    /**
     * Filtro de gettext con contexto
     *
     * @param string $translation Traducción
     * @param string $text        Texto original
     * @param string $context     Contexto
     * @param string $domain      Dominio
     * @return string
     */
    public function filter_gettext_with_context($translation, $text, $context, $domain) {
        if ($this->capture_mode && in_array($domain, $this->tracked_domains)) {
            $this->capture_string($text, $domain, $context);
        }

        return $this->maybe_translate($text, $domain, $translation, $context);
    }

    /**
     * Filtro de ngettext (plural)
     *
     * @param string $translation Traducción
     * @param string $single      Singular
     * @param string $plural      Plural
     * @param int    $number      Número
     * @param string $domain      Dominio
     * @return string
     */
    public function filter_ngettext($translation, $single, $plural, $number, $domain) {
        if ($this->capture_mode && in_array($domain, $this->tracked_domains)) {
            $this->capture_string($single, $domain, '', 'singular');
            $this->capture_string($plural, $domain, '', 'plural');
        }

        $text = ($number == 1) ? $single : $plural;
        return $this->maybe_translate($text, $domain, $translation);
    }

    /**
     * Captura una cadena
     *
     * @param string $text    Texto
     * @param string $domain  Dominio
     * @param string $context Contexto
     * @param string $type    Tipo (normal, singular, plural)
     */
    private function capture_string($text, $domain, $context = '', $type = 'normal') {
        $key = md5($text . $domain . $context);

        if (!isset($this->captured_strings[$key])) {
            $this->captured_strings[$key] = array(
                'text'    => $text,
                'domain'  => $domain,
                'context' => $context,
                'type'    => $type,
            );
        }
    }

    /**
     * Aplica traducción si existe
     *
     * @param string $text        Texto original
     * @param string $domain      Dominio
     * @param string $default     Traducción por defecto
     * @param string $context     Contexto
     * @return string
     */
    private function maybe_translate($text, $domain, $default, $context = '') {
        // Solo en frontend y si no es idioma por defecto
        if (is_admin() || !class_exists('Flavor_Multilingual_Core')) {
            return $default;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $default;
        }

        $current_lang = $core->get_current_language();

        // Buscar traducción en cache o BD
        $translation = $this->get_string_translation($text, $current_lang, $domain, $context);

        return $translation !== null ? $translation : $default;
    }

    /**
     * Obtiene traducción de cadena
     *
     * @param string $text    Texto original
     * @param string $lang    Código de idioma
     * @param string $domain  Dominio
     * @param string $context Contexto
     * @return string|null
     */
    public function get_string_translation($text, $lang, $domain = '', $context = '') {
        global $wpdb;

        static $cache = array();

        $cache_key = md5($text . $lang . $domain . $context);

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $string_key = md5($text);

        $query = $wpdb->prepare(
            "SELECT translation FROM {$this->table}
             WHERE string_key = %s AND language_code = %s",
            $string_key,
            $lang
        );

        if (!empty($domain)) {
            $query .= $wpdb->prepare(" AND domain = %s", $domain);
        }

        $result = $wpdb->get_var($query);

        $cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Guarda traducción de cadena
     *
     * @param string $text        Texto original
     * @param string $lang        Código de idioma
     * @param string $translation Traducción
     * @param string $domain      Dominio
     * @param string $context     Contexto
     * @param bool   $is_auto     Si es traducción automática
     * @return bool
     */
    public function save_string_translation($text, $lang, $translation, $domain = '', $context = '', $is_auto = false) {
        global $wpdb;

        $string_key = md5($text);

        // Verificar si existe
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table}
             WHERE string_key = %s AND language_code = %s",
            $string_key,
            $lang
        ));

        if ($existing_id) {
            return $wpdb->update(
                $this->table,
                array(
                    'translation'        => $translation,
                    'is_auto_translated' => $is_auto ? 1 : 0,
                    'updated_at'         => current_time('mysql'),
                ),
                array('id' => $existing_id)
            ) !== false;
        }

        return $wpdb->insert($this->table, array(
            'string_key'         => $string_key,
            'original_string'    => $text,
            'domain'             => $domain,
            'context'            => $context,
            'language_code'      => $lang,
            'translation'        => $translation,
            'is_auto_translated' => $is_auto ? 1 : 0,
            'created_at'         => current_time('mysql'),
        )) !== false;
    }

    /**
     * Registra una cadena en la base de datos (sin traducción)
     *
     * @param string $text    Texto
     * @param string $domain  Dominio
     * @param string $context Contexto
     * @return bool
     */
    public function register_string($text, $domain = '', $context = '') {
        global $wpdb;

        $string_key = md5($text);

        // Verificar si ya existe (en cualquier idioma)
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE string_key = %s LIMIT 1",
            $string_key
        ));

        if ($exists) {
            return true;
        }

        // Insertar cadena base sin traducción
        $core = Flavor_Multilingual_Core::get_instance();
        $default_lang = $core->get_default_language();

        return $wpdb->insert($this->table, array(
            'string_key'      => $string_key,
            'original_string' => $text,
            'domain'          => $domain,
            'context'         => $context,
            'language_code'   => $default_lang,
            'translation'     => $text, // En idioma por defecto, la traducción es el original
            'created_at'      => current_time('mysql'),
        )) !== false;
    }

    /**
     * Obtiene todas las cadenas registradas
     *
     * @param array $args Argumentos de filtrado
     * @return array
     */
    public function get_strings($args = array()) {
        global $wpdb;

        $defaults = array(
            'domain'    => '',
            'search'    => '',
            'lang'      => '',
            'status'    => '', // translated, untranslated
            'page'      => 1,
            'per_page'  => 50,
        );

        $args = wp_parse_args($args, $defaults);

        // Obtener idioma por defecto para la consulta base
        $core = Flavor_Multilingual_Core::get_instance();
        $default_lang = $core->get_default_language();

        // Construir query
        $where = array("s.language_code = %s");
        $params = array($default_lang);

        if (!empty($args['domain'])) {
            $where[] = "s.domain = %s";
            $params[] = $args['domain'];
        }

        if (!empty($args['search'])) {
            $where[] = "(s.original_string LIKE %s OR t.translation LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $where_clause = implode(' AND ', $where);

        // Query principal
        $target_lang = !empty($args['lang']) ? $args['lang'] : $default_lang;

        $sql = "SELECT
                    s.id,
                    s.string_key,
                    s.original_string,
                    s.domain,
                    s.context,
                    t.translation,
                    t.is_auto_translated,
                    t.updated_at
                FROM {$this->table} s
                LEFT JOIN {$this->table} t
                    ON s.string_key = t.string_key
                    AND t.language_code = %s
                WHERE {$where_clause}
                GROUP BY s.string_key
                ORDER BY s.original_string ASC";

        // Añadir idioma destino al inicio de params
        array_unshift($params, $target_lang);

        // Filtrar por estado
        if ($args['status'] === 'translated') {
            $sql = str_replace('ORDER BY', 'HAVING t.translation IS NOT NULL ORDER BY', $sql);
        } elseif ($args['status'] === 'untranslated') {
            $sql = str_replace('ORDER BY', 'HAVING t.translation IS NULL ORDER BY', $sql);
        }

        // Paginación
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);

        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Contar total
        $count_sql = "SELECT COUNT(DISTINCT s.string_key)
                      FROM {$this->table} s
                      LEFT JOIN {$this->table} t
                          ON s.string_key = t.string_key
                          AND t.language_code = %s
                      WHERE {$where_clause}";

        $total = $wpdb->get_var($wpdb->prepare($count_sql, $params));

        return array(
            'strings'  => $results,
            'total'    => (int) $total,
            'page'     => $args['page'],
            'per_page' => $args['per_page'],
            'pages'    => ceil($total / $args['per_page']),
        );
    }

    /**
     * Obtiene los dominios disponibles
     *
     * @return array
     */
    public function get_available_domains() {
        global $wpdb;

        return $wpdb->get_col(
            "SELECT DISTINCT domain FROM {$this->table} WHERE domain != '' ORDER BY domain"
        );
    }

    /**
     * Escanea archivos PHP para capturar cadenas
     *
     * @param string $path Ruta a escanear
     * @return int Número de cadenas encontradas
     */
    public function scan_directory($path) {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $count += $this->extract_strings_from_content($content);
        }

        return $count;
    }

    /**
     * Extrae cadenas de contenido PHP
     *
     * @param string $content Contenido del archivo
     * @return int Número de cadenas extraídas
     */
    private function extract_strings_from_content($content) {
        $count = 0;

        // Patrones para funciones de traducción
        $patterns = array(
            // __('text', 'domain')
            "/__\(\s*['\"](.+?)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/s",
            // _e('text', 'domain')
            "/_e\(\s*['\"](.+?)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/s",
            // esc_html__('text', 'domain')
            "/esc_html__\(\s*['\"](.+?)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/s",
            // esc_html_e('text', 'domain')
            "/esc_html_e\(\s*['\"](.+?)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/s",
            // esc_attr__('text', 'domain')
            "/esc_attr__\(\s*['\"](.+?)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/s",
        );

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $text = stripslashes($match[1]);
                    $domain = $match[2];

                    if (in_array($domain, $this->tracked_domains)) {
                        $this->register_string($text, $domain);
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * AJAX: Obtener cadenas
     */
    public function ajax_get_strings() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $args = array(
            'domain'   => sanitize_text_field($_POST['domain'] ?? ''),
            'search'   => sanitize_text_field($_POST['search'] ?? ''),
            'lang'     => sanitize_key($_POST['lang'] ?? ''),
            'status'   => sanitize_key($_POST['status'] ?? ''),
            'page'     => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 50),
        );

        $result = $this->get_strings($args);

        wp_send_json_success($result);
    }

    /**
     * AJAX: Guardar traducción de cadena
     */
    public function ajax_save_string() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $original = wp_unslash($_POST['original'] ?? '');
        $translation = wp_unslash($_POST['translation'] ?? '');
        $lang = sanitize_key($_POST['lang'] ?? '');
        $domain = sanitize_text_field($_POST['domain'] ?? '');

        if (empty($original) || empty($lang)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        $result = $this->save_string_translation($original, $lang, $translation, $domain);

        if ($result) {
            wp_send_json_success(array('message' => __('Traducción guardada', 'flavor-multilingual')));
        } else {
            wp_send_json_error(__('Error al guardar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Escanear cadenas
     */
    public function ajax_scan_strings() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $paths = array(
            FLAVOR_MULTILINGUAL_PATH,
            FLAVOR_CHAT_IA_PATH,
            get_template_directory(),
        );

        if (get_template_directory() !== get_stylesheet_directory()) {
            $paths[] = get_stylesheet_directory();
        }

        $total = 0;
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $total += $this->scan_directory($path);
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Escaneadas %d cadenas', 'flavor-multilingual'), $total),
            'count'   => $total,
        ));
    }

    /**
     * AJAX: Eliminar cadena
     */
    public function ajax_delete_string() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $string_key = sanitize_text_field($_POST['string_key'] ?? '');

        if (empty($string_key)) {
            wp_send_json_error(__('Cadena no especificada', 'flavor-multilingual'));
        }

        global $wpdb;
        $result = $wpdb->delete($this->table, array('string_key' => $string_key));

        if ($result) {
            wp_send_json_success(array('message' => __('Cadena eliminada', 'flavor-multilingual')));
        } else {
            wp_send_json_error(__('Error al eliminar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Traducir cadena con IA
     */
    public function ajax_translate_string_ai() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $original = wp_unslash($_POST['original'] ?? '');
        $lang = sanitize_key($_POST['lang'] ?? '');
        $domain = sanitize_text_field($_POST['domain'] ?? '');

        if (empty($original) || empty($lang)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $translation = $translator->translate_text($original, $from_lang, $lang, 'Cadena de interfaz de usuario');

        if (is_wp_error($translation)) {
            wp_send_json_error($translation->get_error_message());
        }

        // Guardar traducción
        $this->save_string_translation($original, $lang, $translation, $domain, '', true);

        wp_send_json_success(array(
            'translation' => $translation,
            'message'     => __('Traducido con IA', 'flavor-multilingual'),
        ));
    }

    /**
     * Obtiene las cadenas capturadas
     *
     * @return array
     */
    public function get_captured_strings() {
        return $this->captured_strings;
    }

    /**
     * Guarda las cadenas capturadas en la BD
     *
     * @return int Número de cadenas guardadas
     */
    public function save_captured_strings() {
        $count = 0;

        foreach ($this->captured_strings as $string) {
            if ($this->register_string($string['text'], $string['domain'], $string['context'])) {
                $count++;
            }
        }

        $this->captured_strings = array();

        return $count;
    }
}

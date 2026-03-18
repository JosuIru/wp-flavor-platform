<?php
/**
 * Controlador principal del sistema multiidioma
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Multilingual_Core {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual_Core|null
     */
    private static $instance = null;

    /**
     * Idioma actual
     *
     * @var string
     */
    private $current_language;

    /**
     * Idioma por defecto
     *
     * @var string
     */
    private $default_language;

    /**
     * Idiomas activos cacheados
     *
     * @var array
     */
    private $active_languages = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual_Core
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
        $this->load_languages();
        $this->detect_current_language();

        // Hooks
        add_action('init', array($this, 'register_rewrite_rules'), 1);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_language_request'));
    }

    /**
     * Carga los idiomas desde la base de datos
     */
    private function load_languages() {
        global $wpdb;

        $cache_key = 'flavor_multilingual_languages';
        $cached = wp_cache_get($cache_key);

        if ($cached !== false) {
            $this->active_languages = $cached['active'];
            $this->default_language = $cached['default'];
            return;
        }

        $table = $wpdb->prefix . 'flavor_languages';
        $results = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC",
            ARRAY_A
        );

        $this->active_languages = array();
        $this->default_language = 'es'; // Fallback

        foreach ($results as $row) {
            $this->active_languages[$row['code']] = array(
                'locale'      => $row['locale'],
                'name'        => $row['name'],
                'native_name' => $row['native_name'],
                'flag'        => $row['flag'],
                'is_rtl'      => (bool) $row['is_rtl'],
            );

            if ($row['is_default']) {
                $this->default_language = $row['code'];
            }
        }

        wp_cache_set($cache_key, array(
            'active'  => $this->active_languages,
            'default' => $this->default_language,
        ), '', 3600);
    }

    /**
     * Detecta el idioma actual
     */
    private function detect_current_language() {
        // 1. Verificar parámetro en URL
        if (isset($_GET['lang']) && $this->is_valid_language($_GET['lang'])) {
            $this->current_language = sanitize_key($_GET['lang']);
            $this->maybe_save_user_preference();
            return;
        }

        // 2. Verificar cookie/sesión del usuario
        if (Flavor_Multilingual::get_option('remember_user_lang')) {
            $saved = isset($_COOKIE['flavor_language']) ? sanitize_key($_COOKIE['flavor_language']) : null;
            if ($saved && $this->is_valid_language($saved)) {
                $this->current_language = $saved;
                return;
            }
        }

        // 3. Detectar idioma del navegador
        if (Flavor_Multilingual::get_option('auto_detect_browser')) {
            $browser_lang = $this->detect_browser_language();
            if ($browser_lang) {
                $this->current_language = $browser_lang;
                return;
            }
        }

        // 4. Usar idioma por defecto
        $this->current_language = $this->default_language;
    }

    /**
     * Detecta el idioma del navegador
     *
     * @return string|null
     */
    private function detect_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = explode(',', $accept_language);

        foreach ($langs as $lang) {
            $lang = explode(';', $lang)[0];
            $lang = substr($lang, 0, 2);

            if ($this->is_valid_language($lang)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Guarda la preferencia de idioma del usuario
     */
    private function maybe_save_user_preference() {
        if (!Flavor_Multilingual::get_option('remember_user_lang')) {
            return;
        }

        setcookie(
            'flavor_language',
            $this->current_language,
            time() + (365 * 24 * 60 * 60),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }

    /**
     * Verifica si un código de idioma es válido
     *
     * @param string $code Código de idioma
     * @return bool
     */
    public function is_valid_language($code) {
        return isset($this->active_languages[$code]);
    }

    /**
     * Obtiene el idioma actual
     *
     * @return string
     */
    public function get_current_language() {
        return $this->current_language;
    }

    /**
     * Establece el idioma actual
     *
     * @param string $code Código de idioma
     */
    public function set_current_language($code) {
        if ($this->is_valid_language($code)) {
            $old_lang = $this->current_language;
            $this->current_language = $code;

            do_action('flavor_multilingual_language_changed', $code, $old_lang);
        }
    }

    /**
     * Obtiene el idioma por defecto
     *
     * @return string
     */
    public function get_default_language() {
        return $this->default_language;
    }

    /**
     * Verifica si el idioma actual es el por defecto
     *
     * @return bool
     */
    public function is_default_language() {
        return $this->current_language === $this->default_language;
    }

    /**
     * Obtiene los idiomas activos
     *
     * @return array
     */
    public function get_active_languages() {
        return $this->active_languages;
    }

    /**
     * Obtiene información de un idioma
     *
     * @param string $code Código de idioma
     * @return array|null
     */
    public function get_language($code) {
        return $this->active_languages[$code] ?? null;
    }

    /**
     * Registra reglas de rewrite para URLs con idioma
     */
    public function register_rewrite_rules() {
        $url_mode = Flavor_Multilingual::get_option('url_mode', 'parameter');

        if ($url_mode === 'directory') {
            $lang_codes = implode('|', array_keys($this->active_languages));

            add_rewrite_rule(
                "^({$lang_codes})/?$",
                'index.php?lang=$matches[1]',
                'top'
            );

            add_rewrite_rule(
                "^({$lang_codes})/(.+?)/?$",
                'index.php?lang=$matches[1]&pagename=$matches[2]',
                'top'
            );
        }
    }

    /**
     * Añade variables de query
     *
     * @param array $vars Variables existentes
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'lang';
        return $vars;
    }

    /**
     * Parsea la petición para detectar idioma
     *
     * @param WP $wp
     */
    public function parse_language_request($wp) {
        if (isset($wp->query_vars['lang'])) {
            $lang = sanitize_key($wp->query_vars['lang']);
            if ($this->is_valid_language($lang)) {
                $this->set_current_language($lang);
            }
        }
    }

    /**
     * Obtiene la URL de la página actual en otro idioma
     *
     * @param string $lang Código de idioma destino
     * @return string
     */
    public function get_current_page_url($lang) {
        $url_manager = Flavor_URL_Manager::get_instance();
        return $url_manager->get_translated_url(null, $lang);
    }

    /**
     * Obtiene todas las traducciones de la página actual
     *
     * @return array Array de [code => url]
     */
    public function get_current_page_translations() {
        $translations = array();

        foreach ($this->active_languages as $code => $lang) {
            $translations[$code] = array(
                'url'         => $this->get_current_page_url($code),
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'],
                'is_current'  => ($code === $this->current_language),
            );
        }

        return $translations;
    }

    /**
     * Limpia la cache de idiomas
     */
    public function clear_cache() {
        wp_cache_delete('flavor_multilingual_languages');
        $this->load_languages();
    }
}

<?php
/**
 * Gestor de URLs multilingües
 *
 * Maneja la generación de URLs traducidas y etiquetas hreflang.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_URL_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_URL_Manager|null
     */
    private static $instance = null;

    /**
     * Modo de URL (parameter, directory, subdomain)
     *
     * @var string
     */
    private $url_mode;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_URL_Manager
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
        $this->url_mode = Flavor_Multilingual::get_option('url_mode', 'parameter');

        // Hooks para modificar URLs
        add_filter('the_permalink', array($this, 'filter_permalink'), 10, 2);
        add_filter('page_link', array($this, 'filter_page_link'), 10, 3);
        add_filter('post_link', array($this, 'filter_post_link'), 10, 3);
        add_filter('post_type_link', array($this, 'filter_post_type_link'), 10, 4);
        add_filter('term_link', array($this, 'filter_term_link'), 10, 3);

        // Añadir hreflang al head
        add_action('wp_head', array($this, 'output_hreflang_tags'));
    }

    /**
     * Obtiene la URL traducida de un objeto
     *
     * @param mixed  $object Post ID, URL o null para página actual
     * @param string $lang   Código de idioma
     * @return string
     */
    public function get_translated_url($object, $lang) {
        $core = Flavor_Multilingual_Core::get_instance();

        if (!$core->is_valid_language($lang)) {
            return '';
        }

        // Si es null, usar la URL actual
        if ($object === null) {
            $url = $this->get_current_url();
        } elseif (is_numeric($object)) {
            $url = get_permalink($object);
        } else {
            $url = $object;
        }

        if (empty($url)) {
            return home_url();
        }

        return $this->add_language_to_url($url, $lang);
    }

    /**
     * Añade el idioma a una URL
     *
     * @param string $url  URL original
     * @param string $lang Código de idioma
     * @return string
     */
    public function add_language_to_url($url, $lang) {
        $core = Flavor_Multilingual_Core::get_instance();
        $default_lang = $core->get_default_language();

        // Si es el idioma por defecto y está configurado para no mostrarlo
        if ($lang === $default_lang && !Flavor_Multilingual::get_option('show_default_in_url')) {
            return $this->remove_language_from_url($url);
        }

        switch ($this->url_mode) {
            case 'directory':
                return $this->add_language_directory($url, $lang);

            case 'subdomain':
                return $this->add_language_subdomain($url, $lang);

            case 'parameter':
            default:
                return $this->add_language_parameter($url, $lang);
        }
    }

    /**
     * Añade idioma como directorio (/es/pagina)
     *
     * @param string $url  URL
     * @param string $lang Idioma
     * @return string
     */
    private function add_language_directory($url, $lang) {
        // Primero quitar cualquier idioma existente
        $url = $this->remove_language_from_url($url);

        $parsed = wp_parse_url($url);
        $base = $parsed['scheme'] . '://' . $parsed['host'];

        if (isset($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }

        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        // Asegurar que el path comience con /
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        return $base . '/' . $lang . $path . $query;
    }

    /**
     * Añade idioma como subdominio (es.dominio.com)
     *
     * @param string $url  URL
     * @param string $lang Idioma
     * @return string
     */
    private function add_language_subdomain($url, $lang) {
        $url = $this->remove_language_from_url($url);

        $parsed = wp_parse_url($url);
        $host = $parsed['host'];

        // Quitar subdominio de idioma existente si hay
        $core = Flavor_Multilingual_Core::get_instance();
        $active_langs = array_keys($core->get_active_languages());
        foreach ($active_langs as $active_lang) {
            if (strpos($host, $active_lang . '.') === 0) {
                $host = substr($host, strlen($active_lang) + 1);
                break;
            }
        }

        $new_host = $lang . '.' . $host;

        $base = $parsed['scheme'] . '://' . $new_host;
        if (isset($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }

        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return $base . $path . $query;
    }

    /**
     * Añade idioma como parámetro (?lang=es)
     *
     * @param string $url  URL
     * @param string $lang Idioma
     * @return string
     */
    private function add_language_parameter($url, $lang) {
        // Quitar parámetro lang existente
        $url = remove_query_arg('lang', $url);

        return add_query_arg('lang', $lang, $url);
    }

    /**
     * Quita el idioma de una URL
     *
     * @param string $url URL
     * @return string
     */
    public function remove_language_from_url($url) {
        $core = Flavor_Multilingual_Core::get_instance();
        $active_langs = array_keys($core->get_active_languages());

        // Quitar parámetro
        $url = remove_query_arg('lang', $url);

        // Quitar directorio de idioma
        $parsed = wp_parse_url($url);
        if (isset($parsed['path'])) {
            $path = $parsed['path'];
            foreach ($active_langs as $lang) {
                $pattern = '/^\/' . preg_quote($lang, '/') . '(\/|$)/';
                if (preg_match($pattern, $path)) {
                    $path = preg_replace($pattern, '/', $path);
                    break;
                }
            }
            $parsed['path'] = $path;
        }

        // Reconstruir URL
        $new_url = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port'])) {
            $new_url .= ':' . $parsed['port'];
        }
        $new_url .= $parsed['path'] ?? '/';
        if (isset($parsed['query'])) {
            $new_url .= '?' . $parsed['query'];
        }

        return $new_url;
    }

    /**
     * Obtiene la URL actual
     *
     * @return string
     */
    public function get_current_url() {
        global $wp;

        $current_url = home_url(add_query_arg(array(), $wp->request));

        // Añadir query string si existe
        if (!empty($_SERVER['QUERY_STRING'])) {
            $query_string = sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));
            // Quitar el parámetro lang
            parse_str($query_string, $params);
            unset($params['lang']);
            if (!empty($params)) {
                $current_url .= '?' . http_build_query($params);
            }
        }

        return $current_url;
    }

    /**
     * Detecta el idioma de una URL
     *
     * @param string $url URL a analizar
     * @return string|null
     */
    public function detect_language_from_url($url) {
        $core = Flavor_Multilingual_Core::get_instance();
        $active_langs = array_keys($core->get_active_languages());

        // Verificar parámetro
        $parsed = wp_parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            if (isset($params['lang']) && in_array($params['lang'], $active_langs)) {
                return $params['lang'];
            }
        }

        // Verificar directorio
        if (isset($parsed['path'])) {
            foreach ($active_langs as $lang) {
                if (preg_match('/^\/' . preg_quote($lang, '/') . '(\/|$)/', $parsed['path'])) {
                    return $lang;
                }
            }
        }

        // Verificar subdominio
        if (isset($parsed['host'])) {
            foreach ($active_langs as $lang) {
                if (strpos($parsed['host'], $lang . '.') === 0) {
                    return $lang;
                }
            }
        }

        return null;
    }

    /**
     * Filtra el permalink
     *
     * @param string $permalink Permalink
     * @param mixed  $post      Post object o ID
     * @return string
     */
    public function filter_permalink($permalink, $post = null) {
        if (is_admin() || wp_doing_ajax()) {
            return $permalink;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        return $this->add_language_to_url($permalink, $current_lang);
    }

    /**
     * Filtra el link de página
     *
     * @param string $link     Link
     * @param int    $post_id  ID del post
     * @param bool   $sample   Es muestra
     * @return string
     */
    public function filter_page_link($link, $post_id, $sample) {
        return $this->filter_permalink($link, $post_id);
    }

    /**
     * Filtra el link de post
     *
     * @param string  $link    Link
     * @param WP_Post $post    Post
     * @param bool    $leavename Dejar nombre
     * @return string
     */
    public function filter_post_link($link, $post, $leavename) {
        return $this->filter_permalink($link, $post);
    }

    /**
     * Filtra el link de CPT
     *
     * @param string  $link      Link
     * @param WP_Post $post      Post
     * @param bool    $leavename Dejar nombre
     * @param bool    $sample    Es muestra
     * @return string
     */
    public function filter_post_type_link($link, $post, $leavename, $sample) {
        return $this->filter_permalink($link, $post);
    }

    /**
     * Filtra el link de término
     *
     * @param string $link     Link
     * @param object $term     Término
     * @param string $taxonomy Taxonomía
     * @return string
     */
    public function filter_term_link($link, $term, $taxonomy) {
        if (is_admin() || wp_doing_ajax()) {
            return $link;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        return $this->add_language_to_url($link, $current_lang);
    }

    /**
     * Genera las etiquetas hreflang
     */
    public function output_hreflang_tags() {
        if (!Flavor_Multilingual::get_option('add_hreflang')) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();

        echo "\n<!-- Flavor Multilingual - hreflang tags -->\n";

        foreach ($active_languages as $code => $lang) {
            $url = $this->get_translated_url(null, $code);
            $locale = str_replace('_', '-', $lang['locale']);

            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                esc_attr($locale),
                esc_url($url)
            );
        }

        // Añadir x-default
        $default_lang = $core->get_default_language();
        $default_url = $this->get_translated_url(null, $default_lang);
        printf(
            '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
            esc_url($default_url)
        );

        echo "<!-- /Flavor Multilingual -->\n\n";
    }

    /**
     * Genera el atributo hreflang para un link
     *
     * @param string $lang Código de idioma
     * @return string
     */
    public function get_hreflang_attribute($lang) {
        $core = Flavor_Multilingual_Core::get_instance();
        $language = $core->get_language($lang);

        if (!$language) {
            return $lang;
        }

        return str_replace('_', '-', $language['locale']);
    }

    /**
     * Obtiene todas las URLs alternativas de la página actual
     *
     * @return array
     */
    public function get_alternate_urls() {
        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();

        $urls = array();
        foreach ($active_languages as $code => $lang) {
            $urls[$code] = array(
                'url'      => $this->get_translated_url(null, $code),
                'hreflang' => $this->get_hreflang_attribute($code),
                'lang'     => $lang,
            );
        }

        return $urls;
    }
}

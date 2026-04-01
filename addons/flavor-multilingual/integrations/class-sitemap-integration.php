<?php
/**
 * Integración con Sitemaps multilingües
 *
 * Genera sitemaps XML con soporte para hreflang y múltiples idiomas.
 * Compatible con WordPress core sitemap, Yoast SEO, RankMath y otros.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_Sitemap_Integration {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_Sitemap_Integration|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_Sitemap_Integration
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
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // WordPress Core Sitemap (WP 5.5+)
        add_filter('wp_sitemaps_posts_query_args', array($this, 'filter_posts_query'), 10, 2);
        add_filter('wp_sitemaps_posts_entry', array($this, 'add_hreflang_to_entry'), 10, 3);

        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_sitemap_entry', array($this, 'yoast_add_hreflang'), 10, 3);
            add_filter('wpseo_sitemap_url', array($this, 'yoast_filter_url'), 10, 2);
            add_action('wpseo_sitemap_index', array($this, 'yoast_add_language_sitemaps'));
        }

        // RankMath
        if (class_exists('RankMath')) {
            add_filter('rank_math/sitemap/entry', array($this, 'rankmath_add_hreflang'), 10, 3);
            add_filter('rank_math/sitemap/url', array($this, 'rankmath_filter_url'), 10, 2);
        }

        // All in One SEO
        if (defined('AIOSEO_VERSION')) {
            add_filter('aioseo_sitemap_entry', array($this, 'aioseo_add_hreflang'), 10, 3);
        }

        // Registro de sitemap propio si no hay plugin SEO
        add_action('init', array($this, 'maybe_register_own_sitemap'));

        // API REST para sitemaps
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    }

    // ================================================================
    // WORDPRESS CORE SITEMAP
    // ================================================================

    /**
     * Filtra los argumentos de query para incluir todos los posts con traducciones
     *
     * @param array  $args      Argumentos de query
     * @param string $post_type Tipo de post
     * @return array
     */
    public function filter_posts_query($args, $post_type) {
        // No modificar, solo asegurar que se incluyan todos los posts publicados
        return $args;
    }

    /**
     * Añade hreflang a las entradas del sitemap de WP
     *
     * @param array   $entry     Entrada del sitemap
     * @param WP_Post $post      Post
     * @param string  $post_type Tipo de post
     * @return array
     */
    public function add_hreflang_to_entry($entry, $post, $post_type) {
        $hreflang_links = $this->get_hreflang_links($post->ID);

        if (!empty($hreflang_links)) {
            // WordPress core sitemap no soporta xhtml:link directamente
            // Pero podemos añadir datos personalizados que serán ignorados
            $entry['languages'] = $hreflang_links;
        }

        return $entry;
    }

    // ================================================================
    // YOAST SEO
    // ================================================================

    /**
     * Añade hreflang a las entradas de Yoast
     *
     * @param array  $url    URL data
     * @param string $type   Tipo
     * @param object $object Objeto
     * @return array
     */
    public function yoast_add_hreflang($url, $type, $object) {
        if ($type !== 'post' || !isset($object->ID)) {
            return $url;
        }

        $hreflang_links = $this->get_hreflang_links($object->ID);

        if (!empty($hreflang_links)) {
            $url['languages'] = $hreflang_links;
        }

        return $url;
    }

    /**
     * Filtra URL de Yoast para incluir idioma
     *
     * @param string $url  URL
     * @param object $post Post
     * @return string
     */
    public function yoast_filter_url($url, $post) {
        // Mantener URL original, los hreflang se añaden aparte
        return $url;
    }

    /**
     * Añade sitemaps por idioma al índice de Yoast
     */
    public function yoast_add_language_sitemaps() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        foreach ($languages as $code => $lang) {
            // Generar entrada de sitemap por idioma
            printf(
                '<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',
                esc_url(home_url("/sitemap-{$code}.xml")),
                date('c')
            );
        }
    }

    // ================================================================
    // RANKMATH
    // ================================================================

    /**
     * Añade hreflang a las entradas de RankMath
     *
     * @param array  $entry  Entrada
     * @param string $type   Tipo
     * @param object $object Objeto
     * @return array
     */
    public function rankmath_add_hreflang($entry, $type, $object) {
        if ($type !== 'post' || !isset($object->ID)) {
            return $entry;
        }

        $hreflang_links = $this->get_hreflang_links($object->ID);

        if (!empty($hreflang_links)) {
            $entry['languages'] = $hreflang_links;
        }

        return $entry;
    }

    /**
     * Filtra URL de RankMath
     *
     * @param string $url  URL
     * @param object $post Post
     * @return string
     */
    public function rankmath_filter_url($url, $post) {
        return $url;
    }

    // ================================================================
    // ALL IN ONE SEO
    // ================================================================

    /**
     * Añade hreflang a las entradas de AIOSEO
     *
     * @param array  $entry  Entrada
     * @param string $type   Tipo
     * @param object $object Objeto
     * @return array
     */
    public function aioseo_add_hreflang($entry, $type, $object) {
        if ($type !== 'post' || !isset($object->ID)) {
            return $entry;
        }

        $hreflang_links = $this->get_hreflang_links($object->ID);

        if (!empty($hreflang_links)) {
            $entry['languages'] = $hreflang_links;
        }

        return $entry;
    }

    // ================================================================
    // SITEMAP PROPIO
    // ================================================================

    /**
     * Registra sitemap propio si no hay plugin SEO
     */
    public function maybe_register_own_sitemap() {
        // Solo si no hay Yoast, RankMath o AIOSEO
        if (defined('WPSEO_VERSION') || class_exists('RankMath') || defined('AIOSEO_VERSION')) {
            return;
        }

        // Registrar rewrite rule para sitemap multilingüe
        add_rewrite_rule(
            'sitemap-ml\.xml$',
            'index.php?flavor_ml_sitemap=index',
            'top'
        );

        add_rewrite_rule(
            'sitemap-ml-([a-z]{2})\.xml$',
            'index.php?flavor_ml_sitemap=language&flavor_ml_lang=$matches[1]',
            'top'
        );

        add_filter('query_vars', function($vars) {
            $vars[] = 'flavor_ml_sitemap';
            $vars[] = 'flavor_ml_lang';
            return $vars;
        });

        add_action('template_redirect', array($this, 'render_sitemap'));
    }

    /**
     * Renderiza el sitemap
     */
    public function render_sitemap() {
        $sitemap_type = get_query_var('flavor_ml_sitemap');

        if (!$sitemap_type) {
            return;
        }

        header('Content-Type: application/xml; charset=UTF-8');

        if ($sitemap_type === 'index') {
            $this->render_sitemap_index();
        } else if ($sitemap_type === 'language') {
            $lang = get_query_var('flavor_ml_lang');
            $this->render_language_sitemap($lang);
        }

        exit;
    }

    /**
     * Renderiza el índice de sitemaps
     */
    private function render_sitemap_index() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($languages as $code => $lang) {
            printf(
                '<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>' . "\n",
                esc_url(home_url("/sitemap-ml-{$code}.xml")),
                date('c')
            );
        }

        echo '</sitemapindex>';
    }

    /**
     * Renderiza el sitemap de un idioma
     *
     * @param string $lang Código de idioma
     */
    private function render_language_sitemap($lang) {
        $core = Flavor_Multilingual_Core::get_instance();
        $url_manager = Flavor_URL_Manager::get_instance();

        if (!$core->is_valid_language($lang)) {
            status_header(404);
            exit;
        }

        $languages = $core->get_active_languages();

        // Obtener posts publicados
        $posts = get_posts(array(
            'post_type'      => array('post', 'page'),
            'post_status'    => 'publish',
            'posts_per_page' => 50000,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ));

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        echo ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($posts as $post) {
            // URL principal para este idioma
            $url = $url_manager->get_translated_url($post->ID, $lang);

            echo '<url>' . "\n";
            echo '<loc>' . esc_url($url) . '</loc>' . "\n";
            echo '<lastmod>' . get_post_modified_time('c', true, $post) . '</lastmod>' . "\n";

            // Añadir hreflang para todos los idiomas
            foreach ($languages as $alt_code => $alt_lang) {
                $alt_url = $url_manager->get_translated_url($post->ID, $alt_code);
                $hreflang = str_replace('_', '-', $alt_lang['locale']);

                printf(
                    '<xhtml:link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                    esc_attr($hreflang),
                    esc_url($alt_url)
                );
            }

            // x-default
            $default_url = $url_manager->get_translated_url($post->ID, $core->get_default_language());
            echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";

            echo '</url>' . "\n";
        }

        echo '</urlset>';
    }

    // ================================================================
    // API REST
    // ================================================================

    /**
     * Registra endpoints de API
     */
    public function register_rest_endpoints() {
        register_rest_route('flavor-multilingual/v1', '/sitemap', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'api_get_sitemap_info'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('flavor-multilingual/v1', '/sitemap/(?P<lang>[a-z]{2})', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'api_get_language_sitemap'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * API: Obtener información del sitemap
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_sitemap_info($request) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        $sitemaps = array();
        foreach ($languages as $code => $lang) {
            $sitemaps[$code] = array(
                'url'      => home_url("/sitemap-ml-{$code}.xml"),
                'language' => $lang['name'],
            );
        }

        return rest_ensure_response(array(
            'index_url' => home_url('/sitemap-ml.xml'),
            'sitemaps'  => $sitemaps,
        ));
    }

    /**
     * API: Obtener sitemap de un idioma (JSON)
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_language_sitemap($request) {
        $lang = $request->get_param('lang');
        $core = Flavor_Multilingual_Core::get_instance();
        $url_manager = Flavor_URL_Manager::get_instance();

        if (!$core->is_valid_language($lang)) {
            return new WP_Error('invalid_language', __('Idioma no válido', 'flavor-multilingual'), array('status' => 404));
        }

        $posts = get_posts(array(
            'post_type'      => array('post', 'page'),
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ));

        $urls = array();
        foreach ($posts as $post) {
            $urls[] = array(
                'loc'      => $url_manager->get_translated_url($post->ID, $lang),
                'lastmod'  => get_post_modified_time('c', true, $post),
                'hreflang' => $this->get_hreflang_links($post->ID),
            );
        }

        return rest_ensure_response(array(
            'language' => $lang,
            'count'    => count($urls),
            'urls'     => $urls,
        ));
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Obtiene los enlaces hreflang para un post
     *
     * @param int $post_id ID del post
     * @return array
     */
    private function get_hreflang_links($post_id) {
        $core = Flavor_Multilingual_Core::get_instance();
        $url_manager = Flavor_URL_Manager::get_instance();
        $languages = $core->get_active_languages();

        $links = array();

        foreach ($languages as $code => $lang) {
            $url = $url_manager->get_translated_url($post_id, $code);
            $hreflang = str_replace('_', '-', $lang['locale']);

            $links[] = array(
                'hreflang' => $hreflang,
                'href'     => $url,
                'language' => $lang['name'],
            );
        }

        // Añadir x-default
        $default_lang = $core->get_default_language();
        $default_url = $url_manager->get_translated_url($post_id, $default_lang);

        $links[] = array(
            'hreflang' => 'x-default',
            'href'     => $default_url,
            'language' => 'Default',
        );

        return $links;
    }

    /**
     * Genera el XML de hreflang para un post
     *
     * @param int $post_id ID del post
     * @return string XML
     */
    public function get_hreflang_xml($post_id) {
        $links = $this->get_hreflang_links($post_id);
        $xml = '';

        foreach ($links as $link) {
            $xml .= sprintf(
                '<xhtml:link rel="alternate" hreflang="%s" href="%s" />',
                esc_attr($link['hreflang']),
                esc_url($link['href'])
            );
        }

        return $xml;
    }

    /**
     * Invalida el caché del sitemap
     */
    public function invalidate_sitemap_cache() {
        // Yoast
        if (class_exists('WPSEO_Sitemaps_Cache')) {
            WPSEO_Sitemaps_Cache::clear();
        }

        // RankMath
        if (function_exists('rank_math_clear_sitemaps_cache')) {
            rank_math_clear_sitemaps_cache();
        }

        // WordPress transients
        delete_transient('flavor_ml_sitemap_cache');
    }

    /**
     * Notifica a buscadores sobre actualización del sitemap
     */
    public function ping_search_engines() {
        $sitemap_url = urlencode(home_url('/sitemap-ml.xml'));

        // Google
        wp_remote_get("https://www.google.com/ping?sitemap={$sitemap_url}", array('blocking' => false));

        // Bing
        wp_remote_get("https://www.bing.com/ping?sitemap={$sitemap_url}", array('blocking' => false));
    }
}

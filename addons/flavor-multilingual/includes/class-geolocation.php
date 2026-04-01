<?php
/**
 * Geolocalización para detección automática de idioma
 *
 * Detecta el idioma preferido del usuario mediante:
 * 1. Geolocalización por IP (MaxMind GeoLite2, IP-API, ipinfo.io)
 * 2. Headers Accept-Language del navegador
 * 3. Cookies de preferencia guardada
 *
 * @package FlavorMultilingual
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_Geolocation {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_Geolocation|null
     */
    private static $instance = null;

    /**
     * Caché de resultados de geolocalización
     *
     * @var array
     */
    private $cache = array();

    /**
     * Nombre de la cookie de idioma
     *
     * @var string
     */
    private $cookie_name = 'flavor_ml_user_lang';

    /**
     * APIs de geolocalización disponibles
     *
     * @var array
     */
    private $geo_apis = array(
        'ip-api' => array(
            'url'           => 'http://ip-api.com/json/{ip}?fields=status,countryCode,country',
            'free_limit'    => 45, // Por minuto
            'needs_key'     => false,
        ),
        'ipinfo' => array(
            'url'           => 'https://ipinfo.io/{ip}/json',
            'free_limit'    => 50000, // Por mes
            'needs_key'     => true,
        ),
        'ipgeolocation' => array(
            'url'           => 'https://api.ipgeolocation.io/ipgeo?apiKey={key}&ip={ip}&fields=country_code2',
            'free_limit'    => 1000, // Por día
            'needs_key'     => true,
        ),
    );

    /**
     * Mapeo de países a idiomas preferidos
     *
     * @var array
     */
    private $country_to_language = array(
        // España y regiones
        'ES' => 'es',

        // Países hispanohablantes
        'MX' => 'es',
        'AR' => 'es',
        'CO' => 'es',
        'PE' => 'es',
        'VE' => 'es',
        'CL' => 'es',
        'EC' => 'es',
        'GT' => 'es',
        'CU' => 'es',
        'BO' => 'es',
        'DO' => 'es',
        'HN' => 'es',
        'PY' => 'es',
        'SV' => 'es',
        'NI' => 'es',
        'CR' => 'es',
        'PA' => 'es',
        'UY' => 'es',
        'PR' => 'es',
        'GQ' => 'es',

        // Anglófonos
        'US' => 'en',
        'GB' => 'en',
        'CA' => 'en',
        'AU' => 'en',
        'NZ' => 'en',
        'IE' => 'en',

        // Francófonos
        'FR' => 'fr',
        'BE' => 'fr',
        'CH' => 'fr', // Suiza multilingüe, defecto francés
        'LU' => 'lb', // Luxemburgués

        // Germanófonos
        'DE' => 'de',
        'AT' => 'de',

        // Otros europeos
        'IT' => 'it',
        'PT' => 'pt',
        'BR' => 'pt',

        // Asiáticos
        'CN' => 'zh',
        'TW' => 'zh',
        'HK' => 'zh',
        'JP' => 'ja',

        // Árabes
        'SA' => 'ar',
        'AE' => 'ar',
        'EG' => 'ar',
        'MA' => 'ar',

        // Celtas
        'IE' => 'ga',
        'CY' => 'cy', // País de Gales - código ficticio, normalmente en UK
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_Geolocation
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Hook para detectar idioma al inicio
        add_action('init', array($this, 'maybe_detect_language'), 5);

        // Guardar preferencia de idioma en cookie
        add_action('flavor_ml_language_switched', array($this, 'save_language_preference'));

        // AJAX para obtener geolocalización
        add_action('wp_ajax_nopriv_flavor_ml_geolocate', array($this, 'ajax_geolocate'));
        add_action('wp_ajax_flavor_ml_geolocate', array($this, 'ajax_geolocate'));

        // Limpiar caché de geolocalización periódicamente
        add_action('flavor_ml_cleanup_geo_cache', array($this, 'cleanup_cache'));

        if (!wp_next_scheduled('flavor_ml_cleanup_geo_cache')) {
            wp_schedule_event(time(), 'daily', 'flavor_ml_cleanup_geo_cache');
        }
    }

    /**
     * Detecta y establece el idioma si está habilitado
     */
    public function maybe_detect_language() {
        // No en admin ni en REST API
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        // Verificar si la detección automática está habilitada
        $settings = get_option('flavor_multilingual_settings', array());
        if (empty($settings['auto_detect_browser'])) {
            return;
        }

        // No detectar si ya hay parámetro de idioma en URL
        if (isset($_GET['lang']) || isset($_COOKIE[$this->cookie_name])) {
            return;
        }

        // No detectar en bots/crawlers
        if ($this->is_bot()) {
            return;
        }

        // Detectar idioma
        $detected_lang = $this->detect_language();

        if ($detected_lang) {
            // Establecer idioma detectado
            $core = Flavor_Multilingual_Core::get_instance();
            $active_languages = $core->get_active_languages();

            if (isset($active_languages[$detected_lang])) {
                $current = $core->get_current_language();

                if ($detected_lang !== $current) {
                    // Redirigir si está configurado
                    if (!empty($settings['auto_redirect'])) {
                        $this->redirect_to_language($detected_lang);
                    } else {
                        // Solo establecer para este request
                        $core->set_current_language($detected_lang);
                    }
                }
            }
        }
    }

    /**
     * Detecta el idioma preferido del usuario
     *
     * @return string|false Código de idioma o false
     */
    public function detect_language() {
        // 1. Intentar por cookie guardada
        $from_cookie = $this->get_language_from_cookie();
        if ($from_cookie) {
            return $from_cookie;
        }

        // 2. Intentar por headers del navegador
        $from_browser = $this->get_language_from_browser();
        if ($from_browser) {
            return $from_browser;
        }

        // 3. Intentar por geolocalización IP
        $from_geo = $this->get_language_from_geolocation();
        if ($from_geo) {
            return $from_geo;
        }

        return false;
    }

    /**
     * Obtiene idioma de la cookie guardada
     *
     * @return string|false
     */
    private function get_language_from_cookie() {
        if (isset($_COOKIE[$this->cookie_name])) {
            $lang = sanitize_key($_COOKIE[$this->cookie_name]);

            // Verificar que es un idioma válido
            $core = Flavor_Multilingual_Core::get_instance();
            $active = $core->get_active_languages();

            if (isset($active[$lang])) {
                return $lang;
            }
        }

        return false;
    }

    /**
     * Obtiene idioma de los headers Accept-Language del navegador
     *
     * @return string|false
     */
    private function get_language_from_browser() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }

        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        // Parsear Accept-Language header
        // Formato: es-ES,es;q=0.9,en;q=0.8
        $languages = array();

        foreach (explode(',', $accept_language) as $part) {
            $part = trim($part);

            if (strpos($part, ';q=') !== false) {
                list($lang, $quality) = explode(';q=', $part);
                $quality = (float) $quality;
            } else {
                $lang = $part;
                $quality = 1.0;
            }

            // Normalizar código de idioma
            $lang = strtolower(trim($lang));

            // Extraer solo el idioma principal (es de es-ES)
            if (strpos($lang, '-') !== false) {
                $lang = substr($lang, 0, strpos($lang, '-'));
            }

            $languages[$lang] = $quality;
        }

        // Ordenar por calidad
        arsort($languages);

        // Buscar primer idioma activo
        $core = Flavor_Multilingual_Core::get_instance();
        $active = $core->get_active_languages();

        foreach (array_keys($languages) as $lang) {
            if (isset($active[$lang])) {
                return $lang;
            }
        }

        return false;
    }

    /**
     * Obtiene idioma por geolocalización IP
     *
     * @return string|false
     */
    private function get_language_from_geolocation() {
        $ip = $this->get_client_ip();

        if (!$ip || $this->is_local_ip($ip)) {
            return false;
        }

        // Verificar caché
        $cache_key = 'flavor_ml_geo_' . md5($ip);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached ?: false;
        }

        // Obtener país por IP
        $country_code = $this->get_country_from_ip($ip);

        if (!$country_code) {
            // Guardar resultado negativo en caché
            set_transient($cache_key, '', HOUR_IN_SECONDS);
            return false;
        }

        // Mapear país a idioma
        $language = $this->map_country_to_language($country_code);

        // Verificar que el idioma está activo
        if ($language) {
            $core = Flavor_Multilingual_Core::get_instance();
            $active = $core->get_active_languages();

            if (!isset($active[$language])) {
                $language = false;
            }
        }

        // Guardar en caché
        set_transient($cache_key, $language ?: '', DAY_IN_SECONDS);

        return $language;
    }

    /**
     * Obtiene el código de país desde una IP
     *
     * @param string $ip Dirección IP
     * @return string|false Código de país (ISO 3166-1 alpha-2)
     */
    private function get_country_from_ip($ip) {
        $settings = get_option('flavor_multilingual_settings', array());
        $api = $settings['geo_api'] ?? 'ip-api';

        // Intentar con la API configurada
        $country = $this->query_geo_api($ip, $api);

        if ($country) {
            return $country;
        }

        // Fallback a otras APIs
        $fallback_apis = array_keys($this->geo_apis);
        $fallback_apis = array_diff($fallback_apis, array($api));

        foreach ($fallback_apis as $fallback) {
            $country = $this->query_geo_api($ip, $fallback);
            if ($country) {
                return $country;
            }
        }

        return false;
    }

    /**
     * Consulta una API de geolocalización
     *
     * @param string $ip  Dirección IP
     * @param string $api Nombre de la API
     * @return string|false Código de país
     */
    private function query_geo_api($ip, $api) {
        if (!isset($this->geo_apis[$api])) {
            return false;
        }

        $api_config = $this->geo_apis[$api];
        $settings = get_option('flavor_multilingual_settings', array());

        // Verificar si necesita API key
        if ($api_config['needs_key']) {
            $api_key = $settings['geo_api_key'] ?? '';
            if (empty($api_key)) {
                return false;
            }
        } else {
            $api_key = '';
        }

        // Construir URL
        $url = str_replace(
            array('{ip}', '{key}'),
            array($ip, $api_key),
            $api_config['url']
        );

        // Hacer request
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            return false;
        }

        // Extraer código de país según la API
        switch ($api) {
            case 'ip-api':
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['countryCode'] ?? false;
                }
                break;

            case 'ipinfo':
                return $data['country'] ?? false;

            case 'ipgeolocation':
                return $data['country_code2'] ?? false;
        }

        return false;
    }

    /**
     * Mapea código de país a código de idioma
     *
     * @param string $country_code Código de país ISO
     * @return string|false Código de idioma
     */
    private function map_country_to_language($country_code) {
        $country_code = strtoupper($country_code);

        // Permitir personalización
        $mapping = apply_filters('flavor_ml_country_language_mapping', $this->country_to_language);

        return isset($mapping[$country_code]) ? $mapping[$country_code] : false;
    }

    /**
     * Guarda la preferencia de idioma del usuario en cookie
     *
     * @param string $lang Código de idioma
     */
    public function save_language_preference($lang) {
        $settings = get_option('flavor_multilingual_settings', array());

        if (empty($settings['remember_user_lang'])) {
            return;
        }

        // Cookie válida por 1 año
        $expiry = time() + YEAR_IN_SECONDS;

        setcookie(
            $this->cookie_name,
            $lang,
            $expiry,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // HttpOnly
        );
    }

    /**
     * Redirige al usuario a la versión del idioma detectado
     *
     * @param string $lang Código de idioma
     */
    private function redirect_to_language($lang) {
        $url_manager = Flavor_URL_Manager::get_instance();
        $current_url = home_url(add_query_arg(array()));

        $translated_url = $url_manager->get_translated_url($current_url, $lang);

        if ($translated_url && $translated_url !== $current_url) {
            wp_redirect($translated_url, 302);
            exit;
        }
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Obtiene la IP real del cliente
     *
     * @return string|false
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxies
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // X-Forwarded-For puede tener múltiples IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return false;
    }

    /**
     * Verifica si es una IP local
     *
     * @param string $ip Dirección IP
     * @return bool
     */
    private function is_local_ip($ip) {
        if (empty($ip)) {
            return true;
        }

        // IPs locales comunes
        $local_ips = array('127.0.0.1', '::1', 'localhost');
        if (in_array($ip, $local_ips)) {
            return true;
        }

        // Rangos privados
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        return false;
    }

    /**
     * Detecta si el visitante es un bot/crawler
     *
     * @return bool
     */
    private function is_bot() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $bot_patterns = array(
            'googlebot',
            'bingbot',
            'slurp',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'sogou',
            'exabot',
            'facebookexternalhit',
            'facebot',
            'ia_archiver',
            'mj12bot',
            'semrushbot',
            'ahrefsbot',
            'dotbot',
            'rogerbot',
            'seznambot',
            'crawler',
            'spider',
            'bot/',
            'bot;',
        );

        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limpia caché de geolocalización antiguo
     */
    public function cleanup_cache() {
        global $wpdb;

        // Eliminar transients de geolocalización antiguos
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_flavor_ml_geo_%'
             OR option_name LIKE '_transient_timeout_flavor_ml_geo_%'"
        );
    }

    // ================================================================
    // AJAX
    // ================================================================

    /**
     * AJAX: Obtener información de geolocalización
     */
    public function ajax_geolocate() {
        $ip = $this->get_client_ip();

        if (!$ip) {
            wp_send_json_error(__('No se pudo obtener IP', 'flavor-multilingual'));
        }

        $country = $this->get_country_from_ip($ip);
        $language = $country ? $this->map_country_to_language($country) : false;
        $browser_lang = $this->get_language_from_browser();

        wp_send_json_success(array(
            'ip'              => $ip,
            'country'         => $country,
            'geo_language'    => $language,
            'browser_language' => $browser_lang,
            'detected'        => $language ?: $browser_lang,
        ));
    }

    // ================================================================
    // ADMIN
    // ================================================================

    /**
     * Obtiene opciones de configuración para admin
     *
     * @return array
     */
    public function get_admin_settings() {
        return array(
            'geo_apis' => array(
                'ip-api' => array(
                    'name'        => 'IP-API',
                    'description' => __('Gratis, 45 requests/minuto, no requiere API key', 'flavor-multilingual'),
                    'needs_key'   => false,
                ),
                'ipinfo' => array(
                    'name'        => 'ipinfo.io',
                    'description' => __('Gratis hasta 50k/mes, requiere API key', 'flavor-multilingual'),
                    'needs_key'   => true,
                ),
                'ipgeolocation' => array(
                    'name'        => 'ipgeolocation.io',
                    'description' => __('Gratis 1k/día, requiere API key', 'flavor-multilingual'),
                    'needs_key'   => true,
                ),
            ),
        );
    }

    /**
     * Prueba la configuración de geolocalización
     *
     * @param string $test_ip IP para probar (opcional)
     * @return array Resultado del test
     */
    public function test_configuration($test_ip = '') {
        if (empty($test_ip)) {
            $test_ip = $this->get_client_ip();
        }

        if (!$test_ip) {
            return array(
                'success' => false,
                'error'   => __('No se pudo obtener una IP para probar', 'flavor-multilingual'),
            );
        }

        $country = $this->get_country_from_ip($test_ip);

        if (!$country) {
            return array(
                'success' => false,
                'ip'      => $test_ip,
                'error'   => __('No se pudo obtener el país de la IP', 'flavor-multilingual'),
            );
        }

        $language = $this->map_country_to_language($country);

        return array(
            'success'  => true,
            'ip'       => $test_ip,
            'country'  => $country,
            'language' => $language ?: __('(no mapeado)', 'flavor-multilingual'),
        );
    }
}

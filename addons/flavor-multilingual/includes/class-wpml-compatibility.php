<?php
/**
 * Capa de Compatibilidad con WPML
 *
 * Proporciona funciones icl_* y filtros compatibles con WPML
 * para que plugins de terceros funcionen sin modificación.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_WPML_Compatibility {

    /**
     * Instancia singleton
     *
     * @var Flavor_WPML_Compatibility|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_WPML_Compatibility
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
        // Solo cargar si WPML no está activo
        if (!defined('ICL_SITEPRESS_VERSION')) {
            $this->define_wpml_functions();
            $this->register_wpml_hooks();
        }
    }

    /**
     * Define las funciones icl_* compatibles con WPML
     */
    private function define_wpml_functions() {
        // Definir constante de compatibilidad
        if (!defined('FLAVOR_WPML_COMPAT')) {
            define('FLAVOR_WPML_COMPAT', true);
        }

        // Las funciones se definen en el scope global
        require_once dirname(__FILE__) . '/wpml-functions.php';
    }

    /**
     * Registra hooks compatibles con WPML
     */
    private function register_wpml_hooks() {
        // Filtro wpml_current_language
        add_filter('wpml_current_language', array($this, 'get_current_language'));

        // Filtro wpml_default_language
        add_filter('wpml_default_language', array($this, 'get_default_language'));

        // Filtro wpml_active_languages
        add_filter('wpml_active_languages', array($this, 'get_active_languages'), 10, 2);

        // Filtro wpml_element_link
        add_filter('wpml_element_link', array($this, 'get_element_link'), 10, 7);

        // Filtro wpml_permalink
        add_filter('wpml_permalink', array($this, 'get_permalink'), 10, 2);

        // Filtro wpml_home_url
        add_filter('wpml_home_url', array($this, 'get_home_url'));

        // Filtro wpml_object_id
        add_filter('wpml_object_id', array($this, 'get_object_id'), 10, 4);

        // Filtro wpml_translate_single_string
        add_filter('wpml_translate_single_string', array($this, 'translate_string'), 10, 6);

        // Acción wpml_switch_language
        add_action('wpml_switch_language', array($this, 'switch_language'));

        // Filtro wpml_element_language_code
        add_filter('wpml_element_language_code', array($this, 'get_element_language'), 10, 2);

        // Filtro wpml_post_language_details
        add_filter('wpml_post_language_details', array($this, 'get_post_language_details'), 10, 2);

        // Filtro wpml_language_is_active
        add_filter('wpml_language_is_active', array($this, 'is_language_active'), 10, 2);

        // Filtro wpml_element_has_translations
        add_filter('wpml_element_has_translations', array($this, 'element_has_translations'), 10, 3);

        // Filtro wpml_is_rtl
        add_filter('wpml_is_rtl', array($this, 'is_rtl'));

        // Acción wpml_register_single_string
        add_action('wpml_register_single_string', array($this, 'register_string'), 10, 3);
    }

    /**
     * Obtiene el idioma actual
     *
     * @param string $default Valor por defecto
     * @return string
     */
    public function get_current_language($default = '') {
        $core = Flavor_Multilingual_Core::get_instance();
        return $core->get_current_language() ?: $default;
    }

    /**
     * Obtiene el idioma por defecto
     *
     * @param string $default Valor por defecto
     * @return string
     */
    public function get_default_language($default = '') {
        $core = Flavor_Multilingual_Core::get_instance();
        return $core->get_default_language() ?: $default;
    }

    /**
     * Obtiene los idiomas activos
     *
     * @param mixed $default Valor por defecto
     * @param array $args    Argumentos
     * @return array
     */
    public function get_active_languages($default = array(), $args = array()) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $current = $core->get_current_language();

        $skip_current = isset($args['skip_missing']) ? $args['skip_missing'] : false;
        $orderby = isset($args['orderby']) ? $args['orderby'] : 'id';

        $result = array();

        foreach ($languages as $code => $lang) {
            if ($skip_current && $code === $current) {
                continue;
            }

            $result[$code] = array(
                'id'               => $code,
                'code'             => $code,
                'language_code'    => $code,
                'native_name'      => $lang['native_name'],
                'translated_name'  => $lang['name'],
                'default_locale'   => $lang['locale'],
                'active'           => 1,
                'tag'              => str_replace('_', '-', $lang['locale']),
                'url'              => $this->get_language_url($code),
                'country_flag_url' => FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $code . '.svg',
                'is_rtl'           => $lang['is_rtl'] ? 1 : 0,
            );
        }

        return $result;
    }

    /**
     * Obtiene la URL para un idioma
     *
     * @param string $lang Código de idioma
     * @return string
     */
    private function get_language_url($lang) {
        $url_manager = Flavor_URL_Manager::get_instance();
        return $url_manager->get_translated_url(null, $lang);
    }

    /**
     * Obtiene el link de un elemento traducido
     *
     * @param string $link          Link por defecto
     * @param int    $element_id    ID del elemento
     * @param string $language_code Código de idioma
     * @param string $type          Tipo de elemento
     * @param bool   $use_slug      Usar slug traducido
     * @param int    $return_type   Tipo de retorno
     * @param bool   $use_fallback  Usar fallback
     * @return string
     */
    public function get_element_link($link, $element_id, $language_code, $type = 'post', $use_slug = false, $return_type = 0, $use_fallback = false) {
        if ($type === 'post' || strpos($type, 'post_') === 0) {
            $url_manager = Flavor_URL_Manager::get_instance();
            return $url_manager->get_translated_url($element_id, $language_code);
        }

        return $link;
    }

    /**
     * Obtiene el permalink traducido
     *
     * @param string $permalink Permalink original
     * @param string $lang      Código de idioma
     * @return string
     */
    public function get_permalink($permalink, $lang = null) {
        if (!$lang) {
            return $permalink;
        }

        $url_manager = Flavor_URL_Manager::get_instance();
        return $url_manager->add_language_to_url($permalink, $lang);
    }

    /**
     * Obtiene la URL home traducida
     *
     * @param string $url URL original
     * @return string
     */
    public function get_home_url($url = '') {
        $core = Flavor_Multilingual_Core::get_instance();
        $lang = $core->get_current_language();
        $url_manager = Flavor_URL_Manager::get_instance();

        return $url_manager->add_language_to_url(home_url('/'), $lang);
    }

    /**
     * Obtiene el ID del objeto en otro idioma
     *
     * En Flavor ML usamos el mismo post con traducciones inline,
     * así que devolvemos el mismo ID
     *
     * @param int    $object_id     ID original
     * @param string $element_type  Tipo de elemento
     * @param bool   $return_original Devolver original si no hay traducción
     * @param string $language_code Código de idioma
     * @return int
     */
    public function get_object_id($object_id, $element_type = 'post', $return_original = true, $language_code = null) {
        // En nuestro sistema, el mismo post contiene todas las traducciones
        return $object_id;
    }

    /**
     * Traduce una cadena
     *
     * @param string $original Original
     * @param string $context  Contexto
     * @param string $name     Nombre
     * @param string $domain   Dominio
     * @param string $lang     Idioma
     * @param bool   $has_translation Si tiene traducción
     * @return string
     */
    public function translate_string($original, $context = '', $name = '', $domain = 'flavor-multilingual', $lang = null, &$has_translation = null) {
        if (!$lang) {
            $core = Flavor_Multilingual_Core::get_instance();
            $lang = $core->get_current_language();
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_string_translation($original, $lang, $domain);

        if ($translation !== null) {
            $has_translation = true;
            return $translation;
        }

        $has_translation = false;
        return $original;
    }

    /**
     * Cambia el idioma actual
     *
     * @param string $lang Código de idioma
     */
    public function switch_language($lang = null) {
        if ($lang === null) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $core->set_current_language($lang);
    }

    /**
     * Obtiene el idioma de un elemento
     *
     * @param string $default    Valor por defecto
     * @param array  $args       Argumentos con element_id y element_type
     * @return string
     */
    public function get_element_language($default = '', $args = array()) {
        // En nuestro sistema, todos los elementos están en el idioma por defecto
        // con traducciones inline
        $core = Flavor_Multilingual_Core::get_instance();
        return $core->get_default_language();
    }

    /**
     * Obtiene detalles del idioma de un post
     *
     * @param mixed $default Valor por defecto
     * @param int   $post_id ID del post
     * @return array|null
     */
    public function get_post_language_details($default = null, $post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return $default;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $lang_code = $core->get_default_language();
        $lang = $core->get_language($lang_code);

        if (!$lang) {
            return $default;
        }

        return array(
            'language_code'        => $lang_code,
            'native_name'          => $lang['native_name'],
            'display_name'         => $lang['name'],
            'different_language'   => false,
            'text_direction'       => $lang['is_rtl'] ? 'rtl' : 'ltr',
        );
    }

    /**
     * Verifica si un idioma está activo
     *
     * @param bool   $default Valor por defecto
     * @param string $lang    Código de idioma
     * @return bool
     */
    public function is_language_active($default = false, $lang = '') {
        $core = Flavor_Multilingual_Core::get_instance();
        return $core->is_valid_language($lang);
    }

    /**
     * Verifica si un elemento tiene traducciones
     *
     * @param bool   $default      Valor por defecto
     * @param int    $element_id   ID del elemento
     * @param string $element_type Tipo de elemento
     * @return bool
     */
    public function element_has_translations($default = false, $element_id = null, $element_type = 'post') {
        if (!$element_id) {
            return $default;
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $type = ($element_type === 'post' || strpos($element_type, 'post_') === 0) ? 'post' : 'term';
        $translations = $storage->get_all_translations($type, $element_id);

        return !empty($translations);
    }

    /**
     * Verifica si el idioma actual es RTL
     *
     * @param bool $default Valor por defecto
     * @return bool
     */
    public function is_rtl($default = false) {
        $core = Flavor_Multilingual_Core::get_instance();
        $current = $core->get_current_language();
        $lang = $core->get_language($current);

        return $lang ? (bool) $lang['is_rtl'] : $default;
    }

    /**
     * Registra una cadena para traducción
     *
     * @param string $context  Contexto
     * @param string $name     Nombre
     * @param string $value    Valor
     */
    public function register_string($context, $name, $value) {
        if (class_exists('Flavor_String_Manager')) {
            $string_manager = Flavor_String_Manager::get_instance();
            $string_manager->register_string($value, $context);
        }
    }

    /**
     * Obtiene las traducciones de un elemento
     *
     * @param int    $element_id   ID del elemento
     * @param string $element_type Tipo de elemento
     * @return array
     */
    public function get_element_translations($element_id, $element_type = 'post') {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $storage = Flavor_Translation_Storage::get_instance();

        $type = ($element_type === 'post' || strpos($element_type, 'post_') === 0) ? 'post' : 'term';
        $translations = $storage->get_all_translations($type, $element_id);

        $result = array();
        foreach ($languages as $code => $lang) {
            $result[$code] = array(
                'element_id'          => $element_id,
                'language_code'       => $code,
                'source_language_code' => $core->get_default_language(),
                'translated'          => isset($translations[$code]),
            );
        }

        return $result;
    }
}

/**
 * Función helper para obtener la instancia
 *
 * @return Flavor_WPML_Compatibility
 */
function flavor_wpml_compat() {
    return Flavor_WPML_Compatibility::get_instance();
}

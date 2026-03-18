<?php
/**
 * Funciones globales compatibles con WPML (icl_*)
 *
 * Estas funciones son usadas por muchos plugins y temas que soportan WPML.
 * Proporcionamos implementaciones compatibles para que funcionen con Flavor Multilingual.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

// Solo definir si WPML no está activo
if (defined('ICL_SITEPRESS_VERSION')) {
    return;
}

/**
 * Obtiene el idioma actual
 *
 * @return string Código del idioma actual
 */
if (!function_exists('icl_get_current_language')) {
    function icl_get_current_language() {
        return apply_filters('wpml_current_language', '');
    }
}

/**
 * Obtiene el idioma por defecto
 *
 * @return string Código del idioma por defecto
 */
if (!function_exists('icl_get_default_language')) {
    function icl_get_default_language() {
        return apply_filters('wpml_default_language', '');
    }
}

/**
 * Obtiene los idiomas activos
 *
 * @param string $skip Código de idioma a omitir
 * @return array Lista de idiomas activos
 */
if (!function_exists('icl_get_languages')) {
    function icl_get_languages($skip = '') {
        $args = array();
        if ($skip) {
            $args['skip_missing'] = true;
        }
        return apply_filters('wpml_active_languages', array(), $args);
    }
}

/**
 * Obtiene el ID del objeto traducido
 *
 * @param int    $element_id   ID del elemento
 * @param string $element_type Tipo de elemento (post, page, category, etc.)
 * @param bool   $return_original Devolver original si no hay traducción
 * @param string $language_code Código del idioma objetivo
 * @return int ID del elemento traducido
 */
if (!function_exists('icl_object_id')) {
    function icl_object_id($element_id, $element_type = 'post', $return_original = true, $language_code = null) {
        return apply_filters('wpml_object_id', $element_id, $element_type, $return_original, $language_code);
    }
}

/**
 * Traduce una cadena registrada
 *
 * @param string $context Contexto de la cadena
 * @param string $name    Nombre identificador
 * @param string $value   Valor original (fallback)
 * @return string Cadena traducida
 */
if (!function_exists('icl_t')) {
    function icl_t($context, $name, $value = '') {
        return apply_filters('wpml_translate_single_string', $value, $context, $name);
    }
}

/**
 * Registra una cadena para traducción
 *
 * @param string $context Contexto
 * @param string $name    Nombre identificador
 * @param string $value   Valor original
 */
if (!function_exists('icl_register_string')) {
    function icl_register_string($context, $name, $value) {
        do_action('wpml_register_single_string', $context, $name, $value);
    }
}

/**
 * Obtiene el enlace del elemento en otro idioma
 *
 * @param int    $element_id    ID del elemento
 * @param string $element_type  Tipo de elemento
 * @param string $language_code Código de idioma
 * @param bool   $skip_empty    Omitir si no hay traducción
 * @return string URL del elemento
 */
if (!function_exists('icl_link_to_element')) {
    function icl_link_to_element($element_id, $element_type = 'post', $language_code = '', $skip_empty = false) {
        if (!$language_code) {
            $language_code = icl_get_current_language();
        }
        return apply_filters('wpml_element_link', '', $element_id, $language_code, $element_type);
    }
}

/**
 * Obtiene la URL traducida
 *
 * @param string $url  URL original
 * @param string $lang Código de idioma (opcional)
 * @return string URL traducida
 */
if (!function_exists('icl_get_home_url')) {
    function icl_get_home_url() {
        return apply_filters('wpml_home_url', home_url('/'));
    }
}

/**
 * Verifica si un idioma está activo
 *
 * @param string $language_code Código de idioma
 * @return bool
 */
if (!function_exists('icl_language_active')) {
    function icl_language_active($language_code) {
        return apply_filters('wpml_language_is_active', false, $language_code);
    }
}

/**
 * Cambia el idioma actual temporalmente
 *
 * @param string $language_code Código de idioma
 */
if (!function_exists('icl_switch_language')) {
    function icl_switch_language($language_code) {
        do_action('wpml_switch_language', $language_code);
    }
}

/**
 * Restaura el idioma original después de icl_switch_language
 */
if (!function_exists('icl_restore_language')) {
    function icl_restore_language() {
        $default = icl_get_default_language();
        do_action('wpml_switch_language', $default);
    }
}

/**
 * Añade el selector de idioma
 *
 * @param array  $args     Argumentos del selector
 * @param string $position Posición (unused, para compatibilidad)
 */
if (!function_exists('icl_get_flag_url')) {
    function icl_get_flag_url($lang = '') {
        if (!$lang) {
            $lang = icl_get_current_language();
        }
        return FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang . '.svg';
    }
}

/**
 * Obtiene el nombre nativo de un idioma
 *
 * @param string $lang Código de idioma
 * @return string
 */
if (!function_exists('icl_get_native_name')) {
    function icl_get_native_name($lang = '') {
        $languages = apply_filters('wpml_active_languages', array());
        if (isset($languages[$lang])) {
            return $languages[$lang]['native_name'];
        }
        return $lang;
    }
}

/**
 * Obtiene el nombre traducido de un idioma
 *
 * @param string $lang Código de idioma
 * @return string
 */
if (!function_exists('icl_get_display_name')) {
    function icl_get_display_name($lang = '') {
        $languages = apply_filters('wpml_active_languages', array());
        if (isset($languages[$lang])) {
            return $languages[$lang]['translated_name'];
        }
        return $lang;
    }
}

/**
 * Obtiene los detalles del idioma del post
 *
 * @param int $post_id ID del post (opcional)
 * @return array|null
 */
if (!function_exists('icl_get_post_language_details')) {
    function icl_get_post_language_details($post_id = null) {
        return apply_filters('wpml_post_language_details', null, $post_id);
    }
}

/**
 * Verifica si el idioma actual es RTL
 *
 * @return bool
 */
if (!function_exists('icl_is_rtl')) {
    function icl_is_rtl() {
        return apply_filters('wpml_is_rtl', false);
    }
}

/**
 * Formatea un idioma para mostrar
 *
 * @param array  $lang_data Datos del idioma
 * @param string $template  Plantilla
 * @return string
 */
if (!function_exists('icl_disp_language')) {
    function icl_disp_language($lang_data, $template = '%s') {
        if (isset($lang_data['native_name'])) {
            return sprintf($template, $lang_data['native_name']);
        }
        return '';
    }
}

/**
 * Clase SitePress simulada para compatibilidad avanzada
 *
 * Algunos plugins verifican la existencia de esta clase.
 */
if (!class_exists('SitePress')) {
    class SitePress {

        /**
         * Obtiene el idioma actual
         *
         * @return string
         */
        public function get_current_language() {
            return icl_get_current_language();
        }

        /**
         * Obtiene el idioma por defecto
         *
         * @return string
         */
        public function get_default_language() {
            return icl_get_default_language();
        }

        /**
         * Obtiene idiomas activos
         *
         * @param bool $refresh Refrescar cache
         * @return array
         */
        public function get_active_languages($refresh = false) {
            return icl_get_languages();
        }

        /**
         * Cambia el idioma
         *
         * @param string $code Código de idioma
         */
        public function switch_lang($code, $cookie = false) {
            icl_switch_language($code);
        }

        /**
         * Verifica si el idioma actual es el por defecto
         *
         * @return bool
         */
        public function is_translated_post_type($post_type) {
            return in_array($post_type, array('post', 'page', 'flavor_landing'));
        }

        /**
         * Verifica si es RTL
         *
         * @return bool
         */
        public function is_rtl($lang = false) {
            return icl_is_rtl();
        }

        /**
         * Obtiene el tipo de elemento
         *
         * @param int $id ID del elemento
         * @return string
         */
        public function get_element_type($id) {
            $post = get_post($id);
            return $post ? 'post_' . $post->post_type : 'post_post';
        }

        /**
         * Obtiene el link traducido
         *
         * @param int    $id   ID del elemento
         * @param string $lang Idioma
         * @return string
         */
        public function language_url($lang = false) {
            if (!$lang) {
                $lang = $this->get_current_language();
            }
            return apply_filters('wpml_permalink', home_url('/'), $lang);
        }
    }
}

/**
 * Variable global $sitepress para compatibilidad
 */
if (!isset($GLOBALS['sitepress'])) {
    $GLOBALS['sitepress'] = new SitePress();
}

/**
 * Constante de versión simulada
 */
if (!defined('ICL_SITEPRESS_VERSION')) {
    // No definimos esta constante para evitar conflictos
    // Pero algunos plugins la verifican de forma diferente
}

/**
 * Constante de idioma (para temas)
 */
if (!defined('ICL_LANGUAGE_CODE')) {
    define('ICL_LANGUAGE_CODE', icl_get_current_language());
}

/**
 * Constante de nombre de idioma
 */
if (!defined('ICL_LANGUAGE_NAME')) {
    define('ICL_LANGUAGE_NAME', icl_get_native_name(icl_get_current_language()));
}

/**
 * Filtro para obtener traducciones de posts
 * Usado por algunos page builders
 */
add_filter('icl_post_alternative_languages', function($alternatives, $post_id) {
    $core = Flavor_Multilingual_Core::get_instance();
    $languages = $core->get_active_languages();
    $default = $core->get_default_language();

    $result = array();
    foreach ($languages as $code => $lang) {
        if ($code === $default) {
            continue;
        }
        $result[$code] = array(
            'id'       => $post_id, // Mismo post, diferentes traducciones
            'language' => $code,
            'title'    => get_the_title($post_id),
        );
    }
    return $result;
}, 10, 2);

/**
 * Shortcode de selector de idioma compatible
 * [wpml_language_switcher]
 */
add_shortcode('wpml_language_switcher', function($atts) {
    return do_shortcode('[flavor_language_switcher]');
});

/**
 * Shortcode de contenido condicional por idioma
 * [wpml_language lang="es"]Contenido solo en español[/wpml_language]
 */
add_shortcode('wpml_language', function($atts, $content = '') {
    $atts = shortcode_atts(array('lang' => ''), $atts);
    return do_shortcode('[flavor_translated lang="' . esc_attr($atts['lang']) . '"]' . $content . '[/flavor_translated]');
});

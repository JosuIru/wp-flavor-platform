<?php
/**
 * Capa de compatibilidad con otros plugins multilingües
 *
 * Detecta y se integra con WPML, Polylang, TranslatePress, etc.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Multilingual_Compatibility {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual_Compatibility|null
     */
    private static $instance = null;

    /**
     * Plugin multilingüe detectado
     *
     * @var string|null
     */
    private $detected_plugin = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual_Compatibility
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
        $this->detect_multilingual_plugin();
    }

    /**
     * Detecta si hay otro plugin multilingüe activo
     */
    private function detect_multilingual_plugin() {
        // WPML
        if (defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress')) {
            $this->detected_plugin = 'wpml';
            return;
        }

        // Polylang
        if (defined('POLYLANG_VERSION') || function_exists('pll_current_language')) {
            $this->detected_plugin = 'polylang';
            return;
        }

        // TranslatePress
        if (defined('TRP_PLUGIN_VERSION') || class_exists('TRP_Translate_Press')) {
            $this->detected_plugin = 'translatepress';
            return;
        }

        // Weglot
        if (defined('WEGLOT_VERSION') || class_exists('WeglotWP\Helpers\Helper_API')) {
            $this->detected_plugin = 'weglot';
            return;
        }

        // qTranslate-XT
        if (defined('QTX_VERSION')) {
            $this->detected_plugin = 'qtranslate';
            return;
        }

        // MultilingualPress
        if (class_exists('Inpsyde\MultilingualPress\MultilingualPress')) {
            $this->detected_plugin = 'multilingualpress';
            return;
        }

        $this->detected_plugin = null;
    }

    /**
     * Verifica si hay otro plugin multilingüe activo
     *
     * @return bool
     */
    public function has_external_multilingual() {
        return $this->detected_plugin !== null;
    }

    /**
     * Obtiene el nombre del plugin detectado
     *
     * @return string|null
     */
    public function get_detected_plugin() {
        return $this->detected_plugin;
    }

    /**
     * Obtiene el idioma actual del plugin externo
     *
     * @return string|null
     */
    public function get_external_current_language() {
        switch ($this->detected_plugin) {
            case 'wpml':
                return $this->get_wpml_language();

            case 'polylang':
                return $this->get_polylang_language();

            case 'translatepress':
                return $this->get_translatepress_language();

            case 'weglot':
                return $this->get_weglot_language();

            default:
                return null;
        }
    }

    /**
     * Obtiene el idioma actual de WPML
     *
     * @return string|null
     */
    private function get_wpml_language() {
        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        }

        if (function_exists('wpml_get_current_language')) {
            return wpml_get_current_language();
        }

        global $sitepress;
        if (is_object($sitepress) && method_exists($sitepress, 'get_current_language')) {
            return $sitepress->get_current_language();
        }

        return null;
    }

    /**
     * Obtiene el idioma actual de Polylang
     *
     * @return string|null
     */
    private function get_polylang_language() {
        if (function_exists('pll_current_language')) {
            return pll_current_language();
        }

        return null;
    }

    /**
     * Obtiene el idioma actual de TranslatePress
     *
     * @return string|null
     */
    private function get_translatepress_language() {
        global $TRP_LANGUAGE;

        if (isset($TRP_LANGUAGE)) {
            // TranslatePress usa locales, convertir a código
            return substr($TRP_LANGUAGE, 0, 2);
        }

        return null;
    }

    /**
     * Obtiene el idioma actual de Weglot
     *
     * @return string|null
     */
    private function get_weglot_language() {
        if (function_exists('weglot_get_current_language')) {
            return weglot_get_current_language();
        }

        return null;
    }

    /**
     * Obtiene los idiomas activos del plugin externo
     *
     * @return array
     */
    public function get_external_languages() {
        switch ($this->detected_plugin) {
            case 'wpml':
                return $this->get_wpml_languages();

            case 'polylang':
                return $this->get_polylang_languages();

            case 'translatepress':
                return $this->get_translatepress_languages();

            default:
                return array();
        }
    }

    /**
     * Obtiene los idiomas de WPML
     *
     * @return array
     */
    private function get_wpml_languages() {
        if (!function_exists('icl_get_languages')) {
            return array();
        }

        $wpml_langs = icl_get_languages('skip_missing=0');
        $languages = array();

        foreach ($wpml_langs as $code => $lang) {
            $languages[$code] = array(
                'code'        => $code,
                'name'        => $lang['translated_name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['country_flag_url'] ?? '',
                'is_default'  => $lang['active'] ?? false,
            );
        }

        return $languages;
    }

    /**
     * Obtiene los idiomas de Polylang
     *
     * @return array
     */
    private function get_polylang_languages() {
        if (!function_exists('pll_languages_list')) {
            return array();
        }

        $pll_langs = pll_languages_list(array('fields' => array()));
        $languages = array();

        foreach ($pll_langs as $lang) {
            $languages[$lang->slug] = array(
                'code'        => $lang->slug,
                'name'        => $lang->name,
                'native_name' => $lang->name,
                'flag'        => $lang->flag_url ?? '',
                'is_default'  => pll_default_language() === $lang->slug,
            );
        }

        return $languages;
    }

    /**
     * Obtiene los idiomas de TranslatePress
     *
     * @return array
     */
    private function get_translatepress_languages() {
        $settings = get_option('trp_settings', array());

        if (!isset($settings['publish-languages'])) {
            return array();
        }

        $languages = array();
        $trp_languages = class_exists('TRP_Languages') ? new TRP_Languages() : null;

        foreach ($settings['publish-languages'] as $locale) {
            $code = substr($locale, 0, 2);
            $name = $trp_languages ? $trp_languages->get_language_name($locale) : $locale;

            $languages[$code] = array(
                'code'        => $code,
                'locale'      => $locale,
                'name'        => $name,
                'native_name' => $name,
                'is_default'  => $locale === ($settings['default-language'] ?? ''),
            );
        }

        return $languages;
    }

    /**
     * Sincroniza el idioma con el plugin externo
     *
     * Cuando Flavor cambia de idioma, intentar sincronizar con el plugin externo.
     *
     * @param string $lang Código de idioma
     */
    public function sync_with_external($lang) {
        if (!$this->has_external_multilingual()) {
            return;
        }

        switch ($this->detected_plugin) {
            case 'wpml':
                $this->sync_wpml($lang);
                break;

            case 'polylang':
                $this->sync_polylang($lang);
                break;
        }
    }

    /**
     * Sincroniza con WPML
     *
     * @param string $lang Código de idioma
     */
    private function sync_wpml($lang) {
        global $sitepress;

        if (is_object($sitepress) && method_exists($sitepress, 'switch_lang')) {
            $sitepress->switch_lang($lang);
        }
    }

    /**
     * Sincroniza con Polylang
     *
     * @param string $lang Código de idioma
     */
    private function sync_polylang($lang) {
        if (function_exists('PLL') && method_exists(PLL(), 'curlang')) {
            // Polylang no tiene un método directo para cambiar idioma en runtime
            // Se debe hacer via URL
        }
    }

    /**
     * Obtiene información de compatibilidad
     *
     * @return array
     */
    public function get_compatibility_info() {
        $info = array(
            'external_plugin' => $this->detected_plugin,
            'has_external'    => $this->has_external_multilingual(),
            'external_name'   => $this->get_plugin_display_name(),
            'mode'            => $this->get_recommended_mode(),
        );

        if ($this->has_external_multilingual()) {
            $info['external_current_lang'] = $this->get_external_current_language();
            $info['external_languages'] = $this->get_external_languages();
        }

        return $info;
    }

    /**
     * Obtiene el nombre para mostrar del plugin detectado
     *
     * @return string
     */
    public function get_plugin_display_name() {
        $names = array(
            'wpml'            => 'WPML',
            'polylang'        => 'Polylang',
            'translatepress'  => 'TranslatePress',
            'weglot'          => 'Weglot',
            'qtranslate'      => 'qTranslate-XT',
            'multilingualpress' => 'MultilingualPress',
        );

        return $names[$this->detected_plugin] ?? __('Ninguno', 'flavor-multilingual');
    }

    /**
     * Obtiene el modo recomendado de operación
     *
     * @return string
     */
    public function get_recommended_mode() {
        if ($this->has_external_multilingual()) {
            return 'bridge'; // Actuar como puente
        }

        return 'standalone'; // Sistema independiente
    }

    /**
     * Verifica si debe usar modo puente
     *
     * @return bool
     */
    public function should_use_bridge_mode() {
        if (!$this->has_external_multilingual()) {
            return false;
        }

        return Flavor_Multilingual::get_option('use_bridge_mode', true);
    }
}

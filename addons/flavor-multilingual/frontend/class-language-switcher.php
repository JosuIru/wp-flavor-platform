<?php
/**
 * Selector de idioma
 *
 * Genera diferentes estilos de selectores de idioma.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Language_Switcher {

    /**
     * Instancia singleton
     *
     * @var Flavor_Language_Switcher|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Language_Switcher
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
        // Nada por ahora
    }

    /**
     * Renderiza el selector de idioma
     *
     * @param array $args Argumentos
     * @return string
     */
    public function render($args = array()) {
        $defaults = array(
            'style'           => 'horizontal', // horizontal, vertical, dropdown, flags-only
            'show_flags'      => true,
            'show_names'      => true,
            'show_native'     => false,
            'hide_current'    => false,
            'class'           => '',
            'before_item'     => '',
            'after_item'      => '',
        );

        $args = wp_parse_args($args, $defaults);

        $core = Flavor_Multilingual_Core::get_instance();
        $translations = $core->get_current_page_translations();

        if (empty($translations)) {
            return '';
        }

        $method = 'render_' . str_replace('-', '_', $args['style']);

        if (method_exists($this, $method)) {
            return $this->$method($translations, $args);
        }

        return $this->render_horizontal($translations, $args);
    }

    /**
     * Renderiza estilo horizontal
     *
     * @param array $translations Traducciones
     * @param array $args         Argumentos
     * @return string
     */
    private function render_horizontal($translations, $args) {
        $classes = array('flavor-ml-switcher', 'flavor-ml-switcher-horizontal');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $output = '<ul class="' . esc_attr(implode(' ', $classes)) . '">';

        foreach ($translations as $code => $data) {
            if ($args['hide_current'] && $data['is_current']) {
                continue;
            }

            $output .= $this->render_item($code, $data, $args);
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Renderiza estilo vertical
     *
     * @param array $translations Traducciones
     * @param array $args         Argumentos
     * @return string
     */
    private function render_vertical($translations, $args) {
        $classes = array('flavor-ml-switcher', 'flavor-ml-switcher-vertical');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $output = '<ul class="' . esc_attr(implode(' ', $classes)) . '">';

        foreach ($translations as $code => $data) {
            if ($args['hide_current'] && $data['is_current']) {
                continue;
            }

            $output .= $this->render_item($code, $data, $args);
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Renderiza estilo dropdown
     *
     * @param array $translations Traducciones
     * @param array $args         Argumentos
     * @return string
     */
    private function render_dropdown($translations, $args) {
        $classes = array('flavor-ml-switcher', 'flavor-ml-switcher-dropdown');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $current_lang = null;
        foreach ($translations as $code => $data) {
            if ($data['is_current']) {
                $current_lang = array('code' => $code, 'data' => $data);
                break;
            }
        }

        if (!$current_lang) {
            return '';
        }

        $output = '<div class="' . esc_attr(implode(' ', $classes)) . '">';

        // Botón del dropdown
        $output .= '<button type="button" class="flavor-ml-dropdown-toggle" aria-expanded="false">';
        $output .= $this->render_item_content($current_lang['code'], $current_lang['data'], $args, false);
        $output .= '<span class="flavor-ml-dropdown-arrow"></span>';
        $output .= '</button>';

        // Lista desplegable
        $output .= '<ul class="flavor-ml-dropdown-menu">';

        foreach ($translations as $code => $data) {
            if ($data['is_current']) {
                continue;
            }

            $output .= $this->render_item($code, $data, $args);
        }

        $output .= '</ul>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza estilo solo banderas
     *
     * @param array $translations Traducciones
     * @param array $args         Argumentos
     * @return string
     */
    private function render_flags_only($translations, $args) {
        $args['show_names'] = false;
        $args['show_flags'] = true;

        $classes = array('flavor-ml-switcher', 'flavor-ml-switcher-flags');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $output = '<ul class="' . esc_attr(implode(' ', $classes)) . '">';

        foreach ($translations as $code => $data) {
            if ($args['hide_current'] && $data['is_current']) {
                continue;
            }

            $output .= $this->render_item($code, $data, $args);
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Renderiza un item de idioma
     *
     * @param string $code Código de idioma
     * @param array  $data Datos del idioma
     * @param array  $args Argumentos
     * @return string
     */
    private function render_item($code, $data, $args) {
        $classes = array('flavor-ml-lang-item');

        if ($data['is_current']) {
            $classes[] = 'flavor-ml-current';
        }

        $output = '<li class="' . esc_attr(implode(' ', $classes)) . '">';
        $output .= $args['before_item'];

        if ($data['is_current']) {
            $output .= '<span class="flavor-ml-lang-link flavor-ml-lang-current">';
            $output .= $this->render_item_content($code, $data, $args);
            $output .= '</span>';
        } else {
            $output .= '<a href="' . esc_url($data['url']) . '" class="flavor-ml-lang-link" hreflang="' . esc_attr($code) . '">';
            $output .= $this->render_item_content($code, $data, $args);
            $output .= '</a>';
        }

        $output .= $args['after_item'];
        $output .= '</li>';

        return $output;
    }

    /**
     * Renderiza el contenido de un item
     *
     * @param string $code     Código de idioma
     * @param array  $data     Datos del idioma
     * @param array  $args     Argumentos
     * @param bool   $as_label Si es solo etiqueta (sin link)
     * @return string
     */
    private function render_item_content($code, $data, $args, $as_label = true) {
        $output = '';

        // Bandera
        if ($args['show_flags'] && $data['flag']) {
            $flag_url = FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $data['flag'];
            $output .= '<img src="' . esc_url($flag_url) . '" alt="" class="flavor-ml-flag" width="20" height="13">';
        }

        // Nombre
        if ($args['show_names']) {
            $name = $args['show_native'] ? $data['native_name'] : $data['name'];
            $output .= '<span class="flavor-ml-lang-name">' . esc_html($name) . '</span>';
        }

        // Si no hay nada, mostrar el código
        if (empty($output)) {
            $output = '<span class="flavor-ml-lang-code">' . esc_html(strtoupper($code)) . '</span>';
        }

        return $output;
    }

    /**
     * Renderiza como select nativo
     *
     * @param array $args Argumentos
     * @return string
     */
    public function render_select($args = array()) {
        $defaults = array(
            'id'          => 'flavor-ml-select',
            'name'        => 'lang',
            'class'       => '',
            'show_native' => false,
            'auto_submit' => true,
        );

        $args = wp_parse_args($args, $defaults);

        $core = Flavor_Multilingual_Core::get_instance();
        $translations = $core->get_current_page_translations();
        $current_lang = $core->get_current_language();

        $classes = array('flavor-ml-select');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $on_change = $args['auto_submit'] ? 'onchange="window.location.href=this.value"' : '';

        $output = sprintf(
            '<select id="%s" name="%s" class="%s" %s>',
            esc_attr($args['id']),
            esc_attr($args['name']),
            esc_attr(implode(' ', $classes)),
            $on_change
        );

        foreach ($translations as $code => $data) {
            $name = $args['show_native'] ? $data['native_name'] : $data['name'];
            $selected = $code === $current_lang ? 'selected' : '';

            $output .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($data['url']),
                $selected,
                esc_html($name)
            );
        }

        $output .= '</select>';

        return $output;
    }

    /**
     * Renderiza solo el idioma actual
     *
     * @param array $args Argumentos
     * @return string
     */
    public function render_current($args = array()) {
        $defaults = array(
            'show_flag'   => true,
            'show_name'   => true,
            'show_native' => false,
            'class'       => '',
        );

        $args = wp_parse_args($args, $defaults);

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();
        $language = $core->get_language($current_lang);

        if (!$language) {
            return '';
        }

        $classes = array('flavor-ml-current-lang');
        if ($args['class']) {
            $classes[] = $args['class'];
        }

        $output = '<span class="' . esc_attr(implode(' ', $classes)) . '">';

        if ($args['show_flag'] && $language['flag']) {
            $flag_url = FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $language['flag'];
            $output .= '<img src="' . esc_url($flag_url) . '" alt="" class="flavor-ml-flag" width="20" height="13">';
        }

        if ($args['show_name']) {
            $name = $args['show_native'] ? $language['native_name'] : $language['name'];
            $output .= '<span class="flavor-ml-lang-name">' . esc_html($name) . '</span>';
        }

        $output .= '</span>';

        return $output;
    }

    /**
     * Obtiene los datos para JavaScript
     *
     * @return array
     */
    public function get_js_data() {
        $core = Flavor_Multilingual_Core::get_instance();

        return array(
            'currentLang'  => $core->get_current_language(),
            'defaultLang'  => $core->get_default_language(),
            'translations' => $core->get_current_page_translations(),
        );
    }
}

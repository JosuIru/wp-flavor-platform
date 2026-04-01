<?php
/**
 * Selector de idioma configurable
 *
 * Genera diferentes estilos de selectores de idioma con configuración completa.
 *
 * @package FlavorMultilingual
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Language_Switcher {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Estilos disponibles
     */
    private $available_styles = array();

    /**
     * Configuración por defecto
     */
    private $default_config = array(
        'style'           => 'dropdown',
        'show_flags'      => true,
        'show_names'      => true,
        'show_native'     => true,
        'hide_current'    => false,
        'flag_style'      => 'rounded', // rounded, square, circle, none
        'flag_size'       => 'medium',  // small, medium, large
        'dropdown_align'  => 'left',    // left, right, center
        'animation'       => 'fade',    // fade, slide, none
        'show_arrow'      => true,
        'mobile_style'    => 'dropdown', // mismo o diferente en móvil
    );

    /**
     * Obtiene la instancia singleton
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
        $this->init_styles();
        $this->init_hooks();
    }

    /**
     * Inicializar estilos disponibles
     */
    private function init_styles() {
        $this->available_styles = array(
            'dropdown' => array(
                'label'       => __('Desplegable', 'flavor-multilingual'),
                'description' => __('Menú desplegable compacto', 'flavor-multilingual'),
                'icon'        => 'dashicons-menu-alt',
            ),
            'horizontal' => array(
                'label'       => __('Horizontal', 'flavor-multilingual'),
                'description' => __('Lista horizontal de idiomas', 'flavor-multilingual'),
                'icon'        => 'dashicons-ellipsis',
            ),
            'vertical' => array(
                'label'       => __('Vertical', 'flavor-multilingual'),
                'description' => __('Lista vertical de idiomas', 'flavor-multilingual'),
                'icon'        => 'dashicons-list-view',
            ),
            'flags-only' => array(
                'label'       => __('Solo banderas', 'flavor-multilingual'),
                'description' => __('Muestra solo las banderas', 'flavor-multilingual'),
                'icon'        => 'dashicons-flag',
            ),
            'minimal' => array(
                'label'       => __('Minimal', 'flavor-multilingual'),
                'description' => __('Solo códigos de idioma (ES | EN | EU)', 'flavor-multilingual'),
                'icon'        => 'dashicons-text',
            ),
            'globe' => array(
                'label'       => __('Globo', 'flavor-multilingual'),
                'description' => __('Icono de globo con desplegable', 'flavor-multilingual'),
                'icon'        => 'dashicons-admin-site',
            ),
            'select' => array(
                'label'       => __('Select nativo', 'flavor-multilingual'),
                'description' => __('Selector HTML nativo', 'flavor-multilingual'),
                'icon'        => 'dashicons-arrow-down-alt2',
            ),
        );
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Shortcodes
        add_shortcode('flavor_language_switcher', array($this, 'shortcode'));
        add_shortcode('flavor_lang_switcher', array($this, 'shortcode'));
        add_shortcode('fml_switcher', array($this, 'shortcode'));

        // Widget
        add_action('widgets_init', array($this, 'register_widget'));

        // Menú de navegación
        add_filter('wp_nav_menu_items', array($this, 'add_to_menu'), 10, 2);

        // Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX
        add_action('wp_ajax_flavor_ml_switch_lang', array($this, 'ajax_switch_language'));
        add_action('wp_ajax_nopriv_flavor_ml_switch_lang', array($this, 'ajax_switch_language'));

        // Admin settings section
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registrar widget
     */
    public function register_widget() {
        register_widget('Flavor_Language_Switcher_Widget');
    }

    /**
     * Registrar configuración
     */
    public function register_settings() {
        register_setting('flavor_multilingual_switcher', 'flavor_ml_switcher_config', array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize_config'),
            'default'           => $this->default_config,
        ));
    }

    /**
     * Sanitizar configuración
     */
    public function sanitize_config($config) {
        $clean = array();

        $clean['style'] = sanitize_text_field($config['style'] ?? 'dropdown');
        $clean['show_flags'] = !empty($config['show_flags']);
        $clean['show_names'] = !empty($config['show_names']);
        $clean['show_native'] = !empty($config['show_native']);
        $clean['hide_current'] = !empty($config['hide_current']);
        $clean['flag_style'] = sanitize_text_field($config['flag_style'] ?? 'rounded');
        $clean['flag_size'] = sanitize_text_field($config['flag_size'] ?? 'medium');
        $clean['dropdown_align'] = sanitize_text_field($config['dropdown_align'] ?? 'left');
        $clean['animation'] = sanitize_text_field($config['animation'] ?? 'fade');
        $clean['show_arrow'] = !empty($config['show_arrow']);
        $clean['mobile_style'] = sanitize_text_field($config['mobile_style'] ?? 'dropdown');

        return $clean;
    }

    /**
     * Obtener configuración
     */
    public function get_config() {
        $saved = get_option('flavor_ml_switcher_config', array());
        return wp_parse_args($saved, $this->default_config);
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'flavor-ml-switcher',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/language-switcher.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-ml-switcher',
            FLAVOR_MULTILINGUAL_URL . 'assets/js/language-switcher.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-ml-switcher', 'flavorMLSwitcher', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_ml_switch'),
            'config'  => $this->get_config(),
        ));
    }

    /**
     * Shortcode
     */
    public function shortcode($atts) {
        $config = $this->get_config();

        $atts = shortcode_atts(array(
            'style'        => $config['style'],
            'show_flags'   => $config['show_flags'] ? 'yes' : 'no',
            'show_names'   => $config['show_names'] ? 'yes' : 'no',
            'show_native'  => $config['show_native'] ? 'yes' : 'no',
            'hide_current' => $config['hide_current'] ? 'yes' : 'no',
            'flag_style'   => $config['flag_style'],
            'flag_size'    => $config['flag_size'],
            'class'        => '',
        ), $atts);

        // Convertir yes/no a boolean
        $args = array(
            'style'        => $atts['style'],
            'show_flags'   => $atts['show_flags'] === 'yes',
            'show_names'   => $atts['show_names'] === 'yes',
            'show_native'  => $atts['show_native'] === 'yes',
            'hide_current' => $atts['hide_current'] === 'yes',
            'flag_style'   => $atts['flag_style'],
            'flag_size'    => $atts['flag_size'],
            'class'        => $atts['class'],
        );

        return $this->render($args);
    }

    /**
     * Renderiza el selector de idioma
     */
    public function render($args = array()) {
        $config = $this->get_config();
        $defaults = array_merge($config, array(
            'class'        => '',
            'before_item'  => '',
            'after_item'   => '',
        ));

        $args = wp_parse_args($args, $defaults);

        // Obtener traducciones disponibles
        $core = Flavor_Multilingual_Core::get_instance();
        $translations = $core->get_current_page_translations();

        if (empty($translations) || count($translations) < 2) {
            return '';
        }

        // Aplicar filtro para personalización
        $args = apply_filters('flavor_ml_switcher_args', $args);

        $method = 'render_' . str_replace('-', '_', $args['style']);

        if (method_exists($this, $method)) {
            return $this->$method($translations, $args);
        }

        return $this->render_dropdown($translations, $args);
    }

    /**
     * Clases CSS base
     */
    private function get_base_classes($style, $args) {
        $classes = array(
            'flavor-ml-switcher',
            'flavor-ml-switcher-' . $style,
        );

        if ($args['show_flags']) {
            $classes[] = 'has-flags';
            $classes[] = 'flag-' . $args['flag_style'];
            $classes[] = 'flag-size-' . $args['flag_size'];
        } else {
            $classes[] = 'no-flags';
        }

        if (!$args['show_names']) {
            $classes[] = 'no-names';
        }

        if ($args['class']) {
            $classes[] = $args['class'];
        }

        return implode(' ', $classes);
    }

    /**
     * Renderiza estilo dropdown
     */
    private function render_dropdown($translations, $args) {
        $config = $this->get_config();
        $classes = $this->get_base_classes('dropdown', $args);
        $classes .= ' align-' . $config['dropdown_align'];
        $classes .= ' anim-' . $config['animation'];

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

        $output = '<div class="' . esc_attr($classes) . '" data-style="dropdown">';

        // Botón toggle
        $output .= '<button type="button" class="flavor-ml-toggle" aria-expanded="false" aria-haspopup="listbox">';
        $output .= $this->render_item_content($current_lang['code'], $current_lang['data'], $args);
        if ($config['show_arrow']) {
            $output .= '<span class="flavor-ml-arrow" aria-hidden="true">';
            $output .= '<svg width="10" height="6" viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>';
            $output .= '</span>';
        }
        $output .= '</button>';

        // Lista
        $output .= '<ul class="flavor-ml-list" role="listbox">';
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
     * Renderiza estilo horizontal
     */
    private function render_horizontal($translations, $args) {
        $classes = $this->get_base_classes('horizontal', $args);

        $output = '<ul class="' . esc_attr($classes) . '">';

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
     */
    private function render_vertical($translations, $args) {
        $classes = $this->get_base_classes('vertical', $args);

        $output = '<ul class="' . esc_attr($classes) . '">';

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
     * Renderiza estilo solo banderas
     */
    private function render_flags_only($translations, $args) {
        $args['show_names'] = false;
        $args['show_flags'] = true;

        $classes = $this->get_base_classes('flags-only', $args);

        $output = '<ul class="' . esc_attr($classes) . '">';

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
     * Renderiza estilo minimal
     */
    private function render_minimal($translations, $args) {
        $args['show_flags'] = false;
        $args['show_names'] = false;

        $classes = $this->get_base_classes('minimal', $args);

        $output = '<div class="' . esc_attr($classes) . '">';
        $items = array();

        foreach ($translations as $code => $data) {
            if ($args['hide_current'] && $data['is_current']) {
                continue;
            }

            $item_class = 'flavor-ml-code';
            if ($data['is_current']) {
                $item_class .= ' current';
            }

            if ($data['is_current']) {
                $items[] = '<span class="' . esc_attr($item_class) . '">' . strtoupper($code) . '</span>';
            } else {
                $items[] = '<a href="' . esc_url($data['url']) . '" class="' . esc_attr($item_class) . '" hreflang="' . esc_attr($code) . '">' . strtoupper($code) . '</a>';
            }
        }

        $output .= implode('<span class="separator">|</span>', $items);
        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza estilo globo
     */
    private function render_globe($translations, $args) {
        $config = $this->get_config();
        $classes = $this->get_base_classes('globe', $args);
        $classes .= ' align-' . $config['dropdown_align'];

        $current_lang = null;
        foreach ($translations as $code => $data) {
            if ($data['is_current']) {
                $current_lang = array('code' => $code, 'data' => $data);
                break;
            }
        }

        $output = '<div class="' . esc_attr($classes) . '" data-style="globe">';

        // Botón globo
        $output .= '<button type="button" class="flavor-ml-toggle flavor-ml-globe-btn" aria-expanded="false">';
        $output .= '<svg class="flavor-ml-globe-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $output .= '<circle cx="12" cy="12" r="10"/>';
        $output .= '<path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>';
        $output .= '</svg>';
        if ($current_lang) {
            $output .= '<span class="flavor-ml-current-code">' . strtoupper($current_lang['code']) . '</span>';
        }
        $output .= '</button>';

        // Lista
        $output .= '<ul class="flavor-ml-list" role="listbox">';
        foreach ($translations as $code => $data) {
            $output .= $this->render_item($code, $data, $args, true);
        }
        $output .= '</ul>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza estilo select nativo
     */
    private function render_select($translations, $args) {
        $classes = $this->get_base_classes('select', $args);
        $current_lang = '';

        foreach ($translations as $code => $data) {
            if ($data['is_current']) {
                $current_lang = $code;
                break;
            }
        }

        $output = '<div class="' . esc_attr($classes) . '">';
        $output .= '<select class="flavor-ml-native-select" onchange="window.location.href=this.value">';

        foreach ($translations as $code => $data) {
            $selected = $code === $current_lang ? 'selected' : '';
            $name = $args['show_native'] ? $data['native_name'] : $data['name'];

            $output .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($data['url']),
                $selected,
                esc_html($name)
            );
        }

        $output .= '</select>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza un item de idioma
     */
    private function render_item($code, $data, $args, $show_code = false) {
        $classes = array('flavor-ml-item');

        if ($data['is_current']) {
            $classes[] = 'current';
        }

        $output = '<li class="' . esc_attr(implode(' ', $classes)) . '" role="option"' . ($data['is_current'] ? ' aria-selected="true"' : '') . '>';

        if ($data['is_current'] && $args['style'] !== 'globe') {
            $output .= '<span class="flavor-ml-link current">';
        } else {
            $output .= '<a href="' . esc_url($data['url']) . '" class="flavor-ml-link" hreflang="' . esc_attr($code) . '">';
        }

        $output .= $this->render_item_content($code, $data, $args);

        if ($show_code) {
            $output .= '<span class="flavor-ml-code-suffix">(' . strtoupper($code) . ')</span>';
        }

        if ($data['is_current'] && $args['style'] !== 'globe') {
            $output .= '</span>';
        } else {
            $output .= '</a>';
        }

        $output .= '</li>';

        return $output;
    }

    /**
     * Renderiza contenido de un item
     */
    private function render_item_content($code, $data, $args) {
        $output = '';

        // Bandera (si está habilitada)
        if ($args['show_flags'] && !empty($data['flag'])) {
            $output .= $this->render_flag($data['flag'], $code, $args);
        }

        // Nombre (si está habilitado)
        if ($args['show_names']) {
            $name = $args['show_native'] ? $data['native_name'] : $data['name'];
            $output .= '<span class="flavor-ml-name">' . esc_html($name) . '</span>';
        }

        // Si no hay nada, mostrar código
        if (empty($output)) {
            $output = '<span class="flavor-ml-code">' . strtoupper($code) . '</span>';
        }

        return $output;
    }

    /**
     * Renderiza bandera
     */
    private function render_flag($flag, $code, $args) {
        $flag_style = $args['flag_style'] ?? 'rounded';
        $flag_size = $args['flag_size'] ?? 'medium';

        // Si flag_style es 'none', no mostrar bandera
        if ($flag_style === 'none') {
            return '';
        }

        $sizes = array(
            'small'  => array('width' => 16, 'height' => 11),
            'medium' => array('width' => 20, 'height' => 14),
            'large'  => array('width' => 28, 'height' => 19),
        );

        $size = $sizes[$flag_size] ?? $sizes['medium'];
        $flag_url = FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $flag;

        $classes = array('flavor-ml-flag', 'flag-' . $flag_style);

        return sprintf(
            '<img src="%s" alt="%s" class="%s" width="%d" height="%d" loading="lazy">',
            esc_url($flag_url),
            esc_attr($code),
            esc_attr(implode(' ', $classes)),
            $size['width'],
            $size['height']
        );
    }

    /**
     * Añadir al menú de navegación
     */
    public function add_to_menu($items, $args) {
        $settings = get_option('flavor_multilingual_settings', array());
        $menu_location = $settings['switcher_menu_location'] ?? '';

        if (empty($menu_location) || $args->theme_location !== $menu_location) {
            return $items;
        }

        $switcher = $this->render(array(
            'class' => 'in-nav-menu',
        ));

        $position = $settings['switcher_menu_position'] ?? 'end';

        if ($position === 'start') {
            $items = '<li class="menu-item flavor-ml-menu-item">' . $switcher . '</li>' . $items;
        } else {
            $items .= '<li class="menu-item flavor-ml-menu-item">' . $switcher . '</li>';
        }

        return $items;
    }

    /**
     * AJAX: Cambiar idioma
     */
    public function ajax_switch_language() {
        check_ajax_referer('flavor_ml_switch', 'nonce');

        $lang = sanitize_text_field($_POST['lang'] ?? '');

        if (empty($lang)) {
            wp_send_json_error(array('message' => __('Idioma no especificado', 'flavor-multilingual')));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        if (!isset($languages[$lang])) {
            wp_send_json_error(array('message' => __('Idioma no válido', 'flavor-multilingual')));
        }

        // Guardar preferencia
        setcookie('flavor_ml_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // Obtener URL traducida
        $url = $_POST['current_url'] ?? home_url();
        $url_manager = Flavor_URL_Manager::get_instance();
        $translated_url = $url_manager->get_url_for_language($lang, $url);

        wp_send_json_success(array(
            'redirect' => $translated_url,
            'language' => $lang,
        ));
    }

    /**
     * Obtener estilos disponibles
     */
    public function get_available_styles() {
        return $this->available_styles;
    }

    /**
     * Obtener configuración por defecto
     */
    public function get_default_config() {
        return $this->default_config;
    }

    /**
     * Renderizar panel de configuración (para admin)
     */
    public function render_config_panel() {
        $config = $this->get_config();
        $styles = $this->get_available_styles();
        ?>
        <div class="flavor-ml-switcher-config">
            <h3><?php _e('Configuración del Selector de Idioma', 'flavor-multilingual'); ?></h3>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Estilo', 'flavor-multilingual'); ?></th>
                    <td>
                        <div class="flavor-ml-style-selector">
                            <?php foreach ($styles as $key => $style) : ?>
                                <label class="style-option <?php echo $config['style'] === $key ? 'selected' : ''; ?>">
                                    <input type="radio" name="flavor_ml_switcher_config[style]" value="<?php echo esc_attr($key); ?>" <?php checked($config['style'], $key); ?>>
                                    <span class="dashicons <?php echo esc_attr($style['icon']); ?>"></span>
                                    <span class="label"><?php echo esc_html($style['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Mostrar banderas', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_switcher_config[show_flags]" value="1" <?php checked($config['show_flags']); ?>>
                            <?php _e('Mostrar banderas de los idiomas', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>

                <tr class="flag-options" <?php echo !$config['show_flags'] ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php _e('Estilo de banderas', 'flavor-multilingual'); ?></th>
                    <td>
                        <select name="flavor_ml_switcher_config[flag_style]">
                            <option value="rounded" <?php selected($config['flag_style'], 'rounded'); ?>><?php _e('Redondeadas', 'flavor-multilingual'); ?></option>
                            <option value="square" <?php selected($config['flag_style'], 'square'); ?>><?php _e('Cuadradas', 'flavor-multilingual'); ?></option>
                            <option value="circle" <?php selected($config['flag_style'], 'circle'); ?>><?php _e('Circulares', 'flavor-multilingual'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr class="flag-options" <?php echo !$config['show_flags'] ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php _e('Tamaño de banderas', 'flavor-multilingual'); ?></th>
                    <td>
                        <select name="flavor_ml_switcher_config[flag_size]">
                            <option value="small" <?php selected($config['flag_size'], 'small'); ?>><?php _e('Pequeño (16px)', 'flavor-multilingual'); ?></option>
                            <option value="medium" <?php selected($config['flag_size'], 'medium'); ?>><?php _e('Mediano (20px)', 'flavor-multilingual'); ?></option>
                            <option value="large" <?php selected($config['flag_size'], 'large'); ?>><?php _e('Grande (28px)', 'flavor-multilingual'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Mostrar nombres', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_switcher_config[show_names]" value="1" <?php checked($config['show_names']); ?>>
                            <?php _e('Mostrar nombre del idioma', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Nombres nativos', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_switcher_config[show_native]" value="1" <?php checked($config['show_native']); ?>>
                            <?php _e('Usar nombre nativo (ej: "Euskara" en lugar de "Vasco")', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Ocultar idioma actual', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_switcher_config[hide_current]" value="1" <?php checked($config['hide_current']); ?>>
                            <?php _e('No mostrar el idioma actualmente seleccionado', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Alineación del menú', 'flavor-multilingual'); ?></th>
                    <td>
                        <select name="flavor_ml_switcher_config[dropdown_align]">
                            <option value="left" <?php selected($config['dropdown_align'], 'left'); ?>><?php _e('Izquierda', 'flavor-multilingual'); ?></option>
                            <option value="center" <?php selected($config['dropdown_align'], 'center'); ?>><?php _e('Centro', 'flavor-multilingual'); ?></option>
                            <option value="right" <?php selected($config['dropdown_align'], 'right'); ?>><?php _e('Derecha', 'flavor-multilingual'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Animación', 'flavor-multilingual'); ?></th>
                    <td>
                        <select name="flavor_ml_switcher_config[animation]">
                            <option value="fade" <?php selected($config['animation'], 'fade'); ?>><?php _e('Desvanecer', 'flavor-multilingual'); ?></option>
                            <option value="slide" <?php selected($config['animation'], 'slide'); ?>><?php _e('Deslizar', 'flavor-multilingual'); ?></option>
                            <option value="none" <?php selected($config['animation'], 'none'); ?>><?php _e('Sin animación', 'flavor-multilingual'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <h4><?php _e('Vista previa', 'flavor-multilingual'); ?></h4>
            <div class="flavor-ml-preview">
                <?php echo $this->render(); ?>
            </div>

            <h4><?php _e('Uso', 'flavor-multilingual'); ?></h4>
            <p><?php _e('Usa el shortcode en cualquier página:', 'flavor-multilingual'); ?></p>
            <code>[flavor_language_switcher]</code>

            <p><?php _e('Con opciones personalizadas:', 'flavor-multilingual'); ?></p>
            <code>[flavor_language_switcher style="horizontal" show_flags="no" show_names="yes"]</code>

            <p><?php _e('O usa el widget "Selector de Idioma" en Apariencia > Widgets.', 'flavor-multilingual'); ?></p>
        </div>
        <?php
    }

    /**
     * Obtener datos para JavaScript
     */
    public function get_js_data() {
        $core = Flavor_Multilingual_Core::get_instance();

        return array(
            'currentLang'  => $core->get_current_language(),
            'defaultLang'  => $core->get_default_language(),
            'translations' => $core->get_current_page_translations(),
            'config'       => $this->get_config(),
        );
    }
}

/**
 * Widget del selector de idioma
 */
class Flavor_Language_Switcher_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'flavor_language_switcher',
            __('Selector de Idioma', 'flavor-multilingual'),
            array(
                'description' => __('Muestra un selector para cambiar de idioma', 'flavor-multilingual'),
                'classname'   => 'widget-flavor-ml-switcher',
            )
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $switcher = Flavor_Language_Switcher::get_instance();
        echo $switcher->render(array(
            'style'        => $instance['style'] ?? 'dropdown',
            'show_flags'   => isset($instance['show_flags']) ? (bool) $instance['show_flags'] : true,
            'show_names'   => isset($instance['show_names']) ? (bool) $instance['show_names'] : true,
            'show_native'  => isset($instance['show_native']) ? (bool) $instance['show_native'] : true,
            'hide_current' => !empty($instance['hide_current']),
        ));

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = $instance['title'] ?? '';
        $style = $instance['style'] ?? 'dropdown';
        $show_flags = isset($instance['show_flags']) ? (bool) $instance['show_flags'] : true;
        $show_names = isset($instance['show_names']) ? (bool) $instance['show_names'] : true;
        $show_native = isset($instance['show_native']) ? (bool) $instance['show_native'] : true;
        $hide_current = !empty($instance['hide_current']);

        $switcher = Flavor_Language_Switcher::get_instance();
        $styles = $switcher->get_available_styles();
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Título:', 'flavor-multilingual'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Estilo:', 'flavor-multilingual'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <?php foreach ($styles as $key => $data) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($style, $key); ?>><?php echo esc_html($data['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('show_flags'); ?>" name="<?php echo $this->get_field_name('show_flags'); ?>" value="1" <?php checked($show_flags); ?>>
            <label for="<?php echo $this->get_field_id('show_flags'); ?>"><?php _e('Mostrar banderas', 'flavor-multilingual'); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('show_names'); ?>" name="<?php echo $this->get_field_name('show_names'); ?>" value="1" <?php checked($show_names); ?>>
            <label for="<?php echo $this->get_field_id('show_names'); ?>"><?php _e('Mostrar nombres', 'flavor-multilingual'); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('show_native'); ?>" name="<?php echo $this->get_field_name('show_native'); ?>" value="1" <?php checked($show_native); ?>>
            <label for="<?php echo $this->get_field_id('show_native'); ?>"><?php _e('Usar nombres nativos', 'flavor-multilingual'); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('hide_current'); ?>" name="<?php echo $this->get_field_name('hide_current'); ?>" value="1" <?php checked($hide_current); ?>>
            <label for="<?php echo $this->get_field_id('hide_current'); ?>"><?php _e('Ocultar idioma actual', 'flavor-multilingual'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        return array(
            'title'        => sanitize_text_field($new_instance['title'] ?? ''),
            'style'        => sanitize_text_field($new_instance['style'] ?? 'dropdown'),
            'show_flags'   => !empty($new_instance['show_flags']),
            'show_names'   => !empty($new_instance['show_names']),
            'show_native'  => !empty($new_instance['show_native']),
            'hide_current' => !empty($new_instance['hide_current']),
        );
    }
}

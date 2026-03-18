<?php
/**
 * Controlador del frontend multilingüe
 *
 * Maneja la visualización de contenido traducido y la integración con el tema.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Multilingual_Frontend {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual_Frontend|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual_Frontend
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
        // Filtros de contenido
        add_filter('the_title', array($this, 'filter_title'), 10, 2);
        add_filter('the_content', array($this, 'filter_content'), 10);
        add_filter('the_excerpt', array($this, 'filter_excerpt'), 10);
        add_filter('get_the_excerpt', array($this, 'filter_get_excerpt'), 10, 2);

        // Filtros de metadatos
        add_filter('document_title_parts', array($this, 'filter_document_title'), 10);
        add_filter('get_post_metadata', array($this, 'filter_post_meta'), 10, 4);

        // Filtros de menús
        add_filter('wp_nav_menu_items', array($this, 'maybe_add_switcher_to_menu'), 10, 2);

        // Widgets del selector de idioma
        add_action('widgets_init', array($this, 'register_widgets'));

        // Shortcodes
        add_shortcode('flavor_language_switcher', array($this, 'shortcode_language_switcher'));
        add_shortcode('flavor_translated', array($this, 'shortcode_translated_content'));

        // Atributo lang en el HTML
        add_filter('language_attributes', array($this, 'filter_language_attributes'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Body class
        add_filter('body_class', array($this, 'add_body_classes'));
    }

    /**
     * Filtra el título del post
     *
     * @param string $title   Título original
     * @param int    $post_id ID del post
     * @return string
     */
    public function filter_title($title, $post_id = 0) {
        if (is_admin() || !$post_id) {
            return $title;
        }

        $translated = $this->get_translated_field($post_id, 'title');

        return $translated !== null ? $translated : $title;
    }

    /**
     * Filtra el contenido del post
     *
     * @param string $content Contenido original
     * @return string
     */
    public function filter_content($content) {
        if (is_admin()) {
            return $content;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return $content;
        }

        $translated = $this->get_translated_field($post_id, 'content');

        return $translated !== null ? $translated : $content;
    }

    /**
     * Filtra el extracto
     *
     * @param string $excerpt Extracto original
     * @return string
     */
    public function filter_excerpt($excerpt) {
        if (is_admin()) {
            return $excerpt;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return $excerpt;
        }

        $translated = $this->get_translated_field($post_id, 'excerpt');

        return $translated !== null ? $translated : $excerpt;
    }

    /**
     * Filtra get_the_excerpt
     *
     * @param string  $excerpt Extracto
     * @param WP_Post $post    Post
     * @return string
     */
    public function filter_get_excerpt($excerpt, $post) {
        if (is_admin() || !$post) {
            return $excerpt;
        }

        $translated = $this->get_translated_field($post->ID, 'excerpt');

        return $translated !== null ? $translated : $excerpt;
    }

    /**
     * Filtra el título del documento
     *
     * @param array $title_parts Partes del título
     * @return array
     */
    public function filter_document_title($title_parts) {
        if (is_singular()) {
            $post_id = get_the_ID();
            if ($post_id) {
                $translated = $this->get_translated_field($post_id, 'title');
                if ($translated !== null) {
                    $title_parts['title'] = $translated;
                }
            }
        }

        return $title_parts;
    }

    /**
     * Filtra metadatos del post
     *
     * @param mixed  $value     Valor original
     * @param int    $object_id ID del objeto
     * @param string $meta_key  Clave meta
     * @param bool   $single    Si es valor único
     * @return mixed
     */
    public function filter_post_meta($value, $object_id, $meta_key, $single) {
        if (is_admin()) {
            return $value;
        }

        // Solo filtrar ciertos campos meta
        $translatable_meta = array(
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_flavor_meta_title',
            '_flavor_meta_description',
        );

        if (!in_array($meta_key, $translatable_meta)) {
            return $value;
        }

        // Buscar traducción del meta
        $field_map = array(
            '_yoast_wpseo_title'       => 'meta_title',
            '_yoast_wpseo_metadesc'    => 'meta_description',
            '_flavor_meta_title'       => 'meta_title',
            '_flavor_meta_description' => 'meta_description',
        );

        $field = $field_map[$meta_key] ?? null;
        if (!$field) {
            return $value;
        }

        $translated = $this->get_translated_field($object_id, $field);

        if ($translated !== null) {
            return $single ? $translated : array($translated);
        }

        return $value;
    }

    /**
     * Obtiene un campo traducido
     *
     * @param int    $post_id ID del post
     * @param string $field   Campo a obtener
     * @return string|null
     */
    private function get_translated_field($post_id, $field) {
        static $cache = array();

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        // Si es el idioma por defecto, no traducir
        if ($core->is_default_language()) {
            return null;
        }

        $cache_key = "{$post_id}_{$field}_{$current_lang}";

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('post', $post_id, $current_lang, $field);

        $cache[$cache_key] = $translation;

        return $translation;
    }

    /**
     * Puede añadir el selector de idioma al menú
     *
     * @param string   $items Items del menú
     * @param stdClass $args  Argumentos
     * @return string
     */
    public function maybe_add_switcher_to_menu($items, $args) {
        $menu_locations = Flavor_Multilingual::get_option('switcher_menu_locations', array());

        if (!in_array($args->theme_location, $menu_locations)) {
            return $items;
        }

        $switcher = Flavor_Language_Switcher::get_instance();
        $switcher_html = $switcher->render(array(
            'style'      => 'dropdown',
            'show_flags' => true,
            'show_names' => true,
        ));

        $items .= '<li class="menu-item flavor-ml-menu-switcher">' . $switcher_html . '</li>';

        return $items;
    }

    /**
     * Registra widgets
     */
    public function register_widgets() {
        register_widget('Flavor_Language_Switcher_Widget');
    }

    /**
     * Shortcode del selector de idioma
     *
     * @param array $atts Atributos
     * @return string
     */
    public function shortcode_language_switcher($atts) {
        $atts = shortcode_atts(array(
            'style'      => 'horizontal',
            'show_flags' => 'true',
            'show_names' => 'true',
            'show_native' => 'false',
        ), $atts);

        $switcher = Flavor_Language_Switcher::get_instance();

        return $switcher->render(array(
            'style'       => $atts['style'],
            'show_flags'  => $atts['show_flags'] === 'true',
            'show_names'  => $atts['show_names'] === 'true',
            'show_native' => $atts['show_native'] === 'true',
        ));
    }

    /**
     * Shortcode para contenido condicional por idioma
     *
     * @param array  $atts    Atributos
     * @param string $content Contenido
     * @return string
     */
    public function shortcode_translated_content($atts, $content = '') {
        $atts = shortcode_atts(array(
            'lang' => '',
        ), $atts);

        if (empty($atts['lang'])) {
            return $content;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();

        $allowed_langs = array_map('trim', explode(',', $atts['lang']));

        if (in_array($current_lang, $allowed_langs)) {
            return do_shortcode($content);
        }

        return '';
    }

    /**
     * Filtra los atributos del idioma HTML
     *
     * @param string $output Salida actual
     * @return string
     */
    public function filter_language_attributes($output) {
        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();
        $language = $core->get_language($current_lang);

        if (!$language) {
            return $output;
        }

        // Reemplazar el atributo lang
        $locale = str_replace('_', '-', $language['locale']);
        $output = preg_replace('/lang="[^"]*"/', 'lang="' . esc_attr($locale) . '"', $output);

        // Añadir dir="rtl" si es necesario
        if ($language['is_rtl'] && strpos($output, 'dir=') === false) {
            $output .= ' dir="rtl"';
        }

        return $output;
    }

    /**
     * Encola assets del frontend
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'flavor-multilingual-frontend',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/frontend.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        // RTL support
        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();
        $language = $core->get_language($current_lang);

        if ($language && $language['is_rtl']) {
            wp_enqueue_style(
                'flavor-multilingual-rtl',
                FLAVOR_MULTILINGUAL_URL . 'assets/css/rtl.css',
                array('flavor-multilingual-frontend'),
                FLAVOR_MULTILINGUAL_VERSION
            );
        }

        wp_enqueue_script(
            'flavor-multilingual-frontend',
            FLAVOR_MULTILINGUAL_URL . 'assets/js/frontend.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-multilingual-frontend', 'flavorML', array(
            'currentLang' => $current_lang,
            'defaultLang' => $core->get_default_language(),
            'ajaxUrl'     => admin_url('admin-ajax.php'),
        ));
    }

    /**
     * Añade clases al body
     *
     * @param array $classes Clases actuales
     * @return array
     */
    public function add_body_classes($classes) {
        $core = Flavor_Multilingual_Core::get_instance();
        $current_lang = $core->get_current_language();
        $language = $core->get_language($current_lang);

        $classes[] = 'flavor-ml-lang-' . $current_lang;

        if ($language && $language['is_rtl']) {
            $classes[] = 'flavor-ml-rtl';
        }

        if ($core->is_default_language()) {
            $classes[] = 'flavor-ml-default-lang';
        }

        return $classes;
    }

    /**
     * Helper: Traduce un texto inline
     *
     * @param string $text    Texto original
     * @param string $context Contexto
     * @return string
     */
    public function translate_inline($text, $context = '') {
        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $text;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $translation = $storage->get_string_translation($text, $current_lang);

        return $translation !== null ? $translation : $text;
    }
}

/**
 * Widget del selector de idioma
 */
class Flavor_Language_Switcher_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'flavor_language_switcher',
            __('Selector de Idioma', 'flavor-multilingual'),
            array(
                'description' => __('Muestra un selector de idioma para el sitio.', 'flavor-multilingual'),
            )
        );
    }

    /**
     * Renderiza el widget
     *
     * @param array $args     Argumentos del widget
     * @param array $instance Instancia
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        }

        $switcher = Flavor_Language_Switcher::get_instance();
        echo $switcher->render(array(
            'style'       => $instance['style'] ?? 'vertical',
            'show_flags'  => !empty($instance['show_flags']),
            'show_names'  => !empty($instance['show_names']),
            'show_native' => !empty($instance['show_native']),
        ));

        echo $args['after_widget'];
    }

    /**
     * Formulario del widget
     *
     * @param array $instance Instancia
     */
    public function form($instance) {
        $title = $instance['title'] ?? '';
        $style = $instance['style'] ?? 'vertical';
        $show_flags = !empty($instance['show_flags']);
        $show_names = isset($instance['show_names']) ? $instance['show_names'] : true;
        $show_native = !empty($instance['show_native']);

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Título:', 'flavor-multilingual'); ?>
            </label>
            <input class="widefat" type="text"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('style')); ?>">
                <?php esc_html_e('Estilo:', 'flavor-multilingual'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('style')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('style')); ?>">
                <option value="vertical" <?php selected($style, 'vertical'); ?>>
                    <?php esc_html_e('Lista vertical', 'flavor-multilingual'); ?>
                </option>
                <option value="horizontal" <?php selected($style, 'horizontal'); ?>>
                    <?php esc_html_e('Lista horizontal', 'flavor-multilingual'); ?>
                </option>
                <option value="dropdown" <?php selected($style, 'dropdown'); ?>>
                    <?php esc_html_e('Dropdown', 'flavor-multilingual'); ?>
                </option>
                <option value="flags-only" <?php selected($style, 'flags-only'); ?>>
                    <?php esc_html_e('Solo banderas', 'flavor-multilingual'); ?>
                </option>
            </select>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_flags')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_flags')); ?>"
                   <?php checked($show_flags); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_flags')); ?>">
                <?php esc_html_e('Mostrar banderas', 'flavor-multilingual'); ?>
            </label>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_names')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_names')); ?>"
                   <?php checked($show_names); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_names')); ?>">
                <?php esc_html_e('Mostrar nombres', 'flavor-multilingual'); ?>
            </label>
        </p>

        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_native')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_native')); ?>"
                   <?php checked($show_native); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_native')); ?>">
                <?php esc_html_e('Usar nombres nativos', 'flavor-multilingual'); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Actualiza el widget
     *
     * @param array $new_instance Nueva instancia
     * @param array $old_instance Instancia anterior
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance = array();

        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['style'] = in_array($new_instance['style'] ?? '', array('vertical', 'horizontal', 'dropdown', 'flags-only'))
            ? $new_instance['style']
            : 'vertical';
        $instance['show_flags'] = !empty($new_instance['show_flags']);
        $instance['show_names'] = !empty($new_instance['show_names']);
        $instance['show_native'] = !empty($new_instance['show_native']);

        return $instance;
    }
}

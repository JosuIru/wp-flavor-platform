<?php
/**
 * Gestión de traducciones de menús de navegación
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Menu_Translations {

    /**
     * Instancia singleton
     *
     * @var Flavor_Menu_Translations|null
     */
    private static $instance = null;

    /**
     * Tabla de traducciones
     *
     * @var string
     */
    private $table;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Menu_Translations
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
        global $wpdb;
        $this->table = $wpdb->prefix . 'flavor_translations';

        // Hook para añadir campos en el editor de menú
        add_action('wp_nav_menu_item_custom_fields', array($this, 'render_menu_item_fields'), 10, 5);

        // Guardar campos personalizados
        add_action('wp_update_nav_menu_item', array($this, 'save_menu_item_translations'), 10, 3);

        // Filtrar items de menú en frontend
        add_filter('wp_nav_menu_objects', array($this, 'filter_menu_items'), 10, 2);

        // Metabox en la página de menús
        add_action('admin_head-nav-menus.php', array($this, 'add_menu_metabox'));

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_save_menu_item', array($this, 'ajax_save_menu_item'));
        add_action('wp_ajax_flavor_ml_translate_menu_item_ai', array($this, 'ajax_translate_menu_item'));

        // Enqueue scripts en página de menús
        add_action('admin_enqueue_scripts', array($this, 'enqueue_menu_scripts'));
    }

    /**
     * Encola scripts para la página de menús
     *
     * @param string $hook Hook actual
     */
    public function enqueue_menu_scripts($hook) {
        if ($hook !== 'nav-menus.php') {
            return;
        }

        wp_enqueue_style(
            'flavor-ml-menu-admin',
            FLAVOR_MULTILINGUAL_URL . 'admin/css/menu-admin.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-ml-menu-admin',
            FLAVOR_MULTILINGUAL_URL . 'admin/js/menu-admin.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-ml-menu-admin', 'flavorMLMenu', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_multilingual'),
            'i18n'    => array(
                'translating' => __('Traduciendo...', 'flavor-multilingual'),
                'translated'  => __('Traducido', 'flavor-multilingual'),
                'error'       => __('Error', 'flavor-multilingual'),
            ),
        ));
    }

    /**
     * Renderiza campos de traducción en cada item del menú
     *
     * @param int      $item_id   ID del item
     * @param object   $item      Item del menú
     * @param int      $depth     Profundidad
     * @param stdClass $args      Argumentos
     * @param int      $id        ID
     */
    public function render_menu_item_fields($item_id, $item, $depth, $args, $id) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        ?>
        <div class="flavor-ml-menu-translations field-flavor-translations description-wide">
            <p class="description">
                <strong><?php esc_html_e('Traducciones', 'flavor-multilingual'); ?></strong>
            </p>

            <div class="flavor-ml-menu-item-langs">
                <?php foreach ($languages as $code => $lang) : ?>
                    <?php
                    if ($code === $default_lang) {
                        continue;
                    }

                    $title_trans = $this->get_menu_item_translation($item_id, $code, 'title');
                    $attr_title_trans = $this->get_menu_item_translation($item_id, $code, 'attr_title');
                    ?>
                    <div class="flavor-ml-menu-lang-row" data-lang="<?php echo esc_attr($code); ?>">
                        <span class="flavor-ml-lang-label">
                            <?php if ($lang['flag']) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                     width="16" height="11">
                            <?php endif; ?>
                            <?php echo esc_html($code); ?>
                        </span>

                        <input type="text"
                               name="flavor_ml_menu[<?php echo esc_attr($item_id); ?>][<?php echo esc_attr($code); ?>][title]"
                               value="<?php echo esc_attr($title_trans); ?>"
                               placeholder="<?php esc_attr_e('Título', 'flavor-multilingual'); ?>"
                               class="widefat flavor-ml-menu-title">

                        <input type="text"
                               name="flavor_ml_menu[<?php echo esc_attr($item_id); ?>][<?php echo esc_attr($code); ?>][attr_title]"
                               value="<?php echo esc_attr($attr_title_trans); ?>"
                               placeholder="<?php esc_attr_e('Atributo title', 'flavor-multilingual'); ?>"
                               class="widefat flavor-ml-menu-attr">

                        <button type="button" class="button button-small flavor-ml-translate-menu-item-ai"
                                data-item-id="<?php echo esc_attr($item_id); ?>"
                                data-lang="<?php echo esc_attr($code); ?>"
                                title="<?php esc_attr_e('Traducir con IA', 'flavor-multilingual'); ?>">
                            <span class="dashicons dashicons-translation"></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda traducciones del item de menú
     *
     * @param int   $menu_id       ID del menú
     * @param int   $menu_item_id  ID del item
     * @param array $args          Argumentos
     */
    public function save_menu_item_translations($menu_id, $menu_item_id, $args) {
        if (!isset($_POST['flavor_ml_menu'][$menu_item_id])) {
            return;
        }

        $translations = $_POST['flavor_ml_menu'][$menu_item_id];

        foreach ($translations as $lang => $fields) {
            $lang = sanitize_key($lang);

            foreach ($fields as $field => $value) {
                $field = sanitize_key($field);
                $value = sanitize_text_field($value);

                if (!empty($value)) {
                    $this->save_menu_item_translation($menu_item_id, $lang, $field, $value);
                } else {
                    $this->delete_menu_item_translation($menu_item_id, $lang, $field);
                }
            }
        }
    }

    /**
     * Guarda una traducción de item de menú
     *
     * @param int    $item_id ID del item
     * @param string $lang    Código de idioma
     * @param string $field   Campo
     * @param string $value   Valor
     * @param bool   $is_auto Si es automática
     * @return bool
     */
    public function save_menu_item_translation($item_id, $lang, $field, $value, $is_auto = false) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table}
             WHERE object_type = 'nav_menu_item' AND object_id = %d
             AND language_code = %s AND field_name = %s",
            $item_id, $lang, $field
        ));

        if ($existing) {
            return $wpdb->update(
                $this->table,
                array(
                    'translation'        => $value,
                    'is_auto_translated' => $is_auto ? 1 : 0,
                    'updated_at'         => current_time('mysql'),
                ),
                array('id' => $existing)
            ) !== false;
        }

        return $wpdb->insert($this->table, array(
            'object_type'        => 'nav_menu_item',
            'object_id'          => $item_id,
            'language_code'      => $lang,
            'field_name'         => $field,
            'translation'        => $value,
            'is_auto_translated' => $is_auto ? 1 : 0,
            'status'             => 'published',
            'created_at'         => current_time('mysql'),
        )) !== false;
    }

    /**
     * Obtiene traducción de item de menú
     *
     * @param int    $item_id ID del item
     * @param string $lang    Código de idioma
     * @param string $field   Campo
     * @return string|null
     */
    public function get_menu_item_translation($item_id, $lang, $field) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT translation FROM {$this->table}
             WHERE object_type = 'nav_menu_item' AND object_id = %d
             AND language_code = %s AND field_name = %s",
            $item_id, $lang, $field
        ));
    }

    /**
     * Elimina traducción de item de menú
     *
     * @param int    $item_id ID del item
     * @param string $lang    Código de idioma
     * @param string $field   Campo
     * @return bool
     */
    public function delete_menu_item_translation($item_id, $lang, $field) {
        global $wpdb;

        return $wpdb->delete($this->table, array(
            'object_type'   => 'nav_menu_item',
            'object_id'     => $item_id,
            'language_code' => $lang,
            'field_name'    => $field,
        )) !== false;
    }

    /**
     * Filtra items de menú en el frontend
     *
     * @param array    $items Items del menú
     * @param stdClass $args  Argumentos
     * @return array
     */
    public function filter_menu_items($items, $args) {
        if (is_admin()) {
            return $items;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $items;
        }

        $current_lang = $core->get_current_language();

        foreach ($items as $item) {
            // Traducir título
            $title_trans = $this->get_menu_item_translation($item->ID, $current_lang, 'title');
            if ($title_trans) {
                $item->title = $title_trans;
            }

            // Traducir atributo title
            $attr_trans = $this->get_menu_item_translation($item->ID, $current_lang, 'attr_title');
            if ($attr_trans) {
                $item->attr_title = $attr_trans;
            }

            // Añadir parámetro de idioma al enlace si es interno
            $site_url = site_url();
            if (strpos($item->url, $site_url) === 0) {
                $url_mode = Flavor_Multilingual::get_option('url_mode', 'parameter');
                if ($url_mode === 'parameter') {
                    $item->url = add_query_arg('lang', $current_lang, $item->url);
                }
            }
        }

        return $items;
    }

    /**
     * Añade metabox en la página de menús
     */
    public function add_menu_metabox() {
        add_meta_box(
            'flavor-ml-menu-info',
            __('Multiidioma', 'flavor-multilingual'),
            array($this, 'render_menu_metabox'),
            'nav-menus',
            'side',
            'default'
        );
    }

    /**
     * Renderiza metabox de información
     */
    public function render_menu_metabox() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        ?>
        <div class="flavor-ml-menu-metabox">
            <p class="description">
                <?php esc_html_e('Cada elemento del menú tiene campos de traducción. Expande un elemento para ver y editar sus traducciones.', 'flavor-multilingual'); ?>
            </p>

            <h4><?php esc_html_e('Idiomas activos', 'flavor-multilingual'); ?></h4>
            <ul class="flavor-ml-active-langs">
                <?php foreach ($languages as $code => $lang) : ?>
                    <li>
                        <?php if ($lang['flag']) : ?>
                            <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                 width="16" height="11">
                        <?php endif; ?>
                        <?php echo esc_html($lang['name']); ?>
                        <?php if ($code === $default_lang) : ?>
                            <span class="flavor-ml-default-badge"><?php esc_html_e('Por defecto', 'flavor-multilingual'); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p>
                <button type="button" class="button" id="flavor-ml-translate-all-menu-items">
                    <span class="dashicons dashicons-translation"></span>
                    <?php esc_html_e('Traducir todo el menú con IA', 'flavor-multilingual'); ?>
                </button>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX: Guardar traducción de item de menú
     */
    public function ajax_save_menu_item() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $item_id = intval($_POST['item_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $field = sanitize_key($_POST['field'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        if (!$item_id || !$lang || !$field) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        $result = $this->save_menu_item_translation($item_id, $lang, $field, $value);

        if ($result) {
            wp_send_json_success(array('message' => __('Guardado', 'flavor-multilingual')));
        } else {
            wp_send_json_error(__('Error al guardar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Traducir item de menú con IA
     */
    public function ajax_translate_menu_item() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $item_id = intval($_POST['item_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$item_id || !$lang) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        // Obtener el item del menú
        $item = wp_setup_nav_menu_item(get_post($item_id));

        if (!$item) {
            wp_send_json_error(__('Item no encontrado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $translations = array();

        // Traducir título
        if (!empty($item->title)) {
            $trans = $translator->translate_text($item->title, $from_lang, $lang, 'Elemento de menú de navegación');
            if (!is_wp_error($trans)) {
                $translations['title'] = $trans;
                $this->save_menu_item_translation($item_id, $lang, 'title', $trans, true);
            }
        }

        // Traducir atributo title si existe
        if (!empty($item->attr_title)) {
            $trans = $translator->translate_text($item->attr_title, $from_lang, $lang, 'Atributo title de enlace');
            if (!is_wp_error($trans)) {
                $translations['attr_title'] = $trans;
                $this->save_menu_item_translation($item_id, $lang, 'attr_title', $trans, true);
            }
        }

        wp_send_json_success(array(
            'translations' => $translations,
            'message'      => __('Traducido', 'flavor-multilingual'),
        ));
    }

    /**
     * Traduce todo el menú con IA
     *
     * @param int    $menu_id ID del menú
     * @param string $lang    Código de idioma destino
     * @return array Resultados
     */
    public function translate_full_menu($menu_id, $lang) {
        $items = wp_get_nav_menu_items($menu_id);

        if (!$items) {
            return array('success' => false, 'message' => __('Menú vacío', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $translated_count = 0;

        foreach ($items as $item) {
            // Solo traducir si tiene título
            if (empty($item->title)) {
                continue;
            }

            // Verificar si ya tiene traducción
            $existing = $this->get_menu_item_translation($item->ID, $lang, 'title');
            if ($existing) {
                continue;
            }

            $trans = $translator->translate_text($item->title, $from_lang, $lang, 'Elemento de menú');

            if (!is_wp_error($trans)) {
                $this->save_menu_item_translation($item->ID, $lang, 'title', $trans, true);
                $translated_count++;
            }
        }

        return array(
            'success' => true,
            'count'   => $translated_count,
            'message' => sprintf(__('Traducidos %d elementos', 'flavor-multilingual'), $translated_count),
        );
    }
}

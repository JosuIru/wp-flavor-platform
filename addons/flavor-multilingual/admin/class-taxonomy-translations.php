<?php
/**
 * Gestión de traducciones de taxonomías y términos
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Taxonomy_Translations {

    /**
     * Instancia singleton
     *
     * @var Flavor_Taxonomy_Translations|null
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
     * @return Flavor_Taxonomy_Translations
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

        // Hooks para admin de términos
        $this->register_taxonomy_hooks();

        // Filtros de frontend
        add_filter('get_term', array($this, 'filter_term'), 10, 2);
        add_filter('get_terms', array($this, 'filter_terms'), 10, 4);
        add_filter('term_link', array($this, 'filter_term_link'), 10, 3);

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_save_term_translation', array($this, 'ajax_save_term'));
        add_action('wp_ajax_flavor_ml_get_term_translations', array($this, 'ajax_get_term_translations'));
        add_action('wp_ajax_flavor_ml_translate_term_ai', array($this, 'ajax_translate_term_ai'));
    }

    /**
     * Registra hooks para todas las taxonomías públicas
     */
    private function register_taxonomy_hooks() {
        $taxonomies = get_taxonomies(array('public' => true), 'names');

        foreach ($taxonomies as $taxonomy) {
            // Formulario de edición de término
            add_action("{$taxonomy}_edit_form_fields", array($this, 'render_term_translation_fields'), 10, 2);

            // Guardar término
            add_action("edited_{$taxonomy}", array($this, 'save_term_translations'), 10, 2);

            // Columna en listado
            add_filter("manage_edit-{$taxonomy}_columns", array($this, 'add_translation_column'));
            add_filter("manage_{$taxonomy}_custom_column", array($this, 'render_translation_column'), 10, 3);
        }
    }

    /**
     * Renderiza campos de traducción en el formulario de edición de término
     *
     * @param WP_Term $term     Término
     * @param string  $taxonomy Taxonomía
     */
    public function render_term_translation_fields($term, $taxonomy) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        wp_nonce_field('flavor_ml_term_translations', 'flavor_ml_term_nonce');

        ?>
        <tr class="form-field">
            <th scope="row" colspan="2">
                <h2><?php esc_html_e('Traducciones', 'flavor-multilingual'); ?></h2>
            </th>
        </tr>
        <?php

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $name_trans = $this->get_term_translation($term->term_id, $code, 'name');
            $desc_trans = $this->get_term_translation($term->term_id, $code, 'description');
            $slug_trans = $this->get_term_translation($term->term_id, $code, 'slug');

            ?>
            <tr class="form-field flavor-ml-term-translation" data-lang="<?php echo esc_attr($code); ?>">
                <th scope="row">
                    <?php if ($lang['flag']) : ?>
                        <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                             width="20" height="13" style="vertical-align: middle; margin-right: 5px;">
                    <?php endif; ?>
                    <?php echo esc_html($lang['name']); ?>
                </th>
                <td>
                    <div class="flavor-ml-term-fields">
                        <p>
                            <label><?php esc_html_e('Nombre', 'flavor-multilingual'); ?></label>
                            <input type="text" name="flavor_ml_term[<?php echo esc_attr($code); ?>][name]"
                                   value="<?php echo esc_attr($name_trans); ?>" class="widefat">
                        </p>
                        <p>
                            <label><?php esc_html_e('Slug', 'flavor-multilingual'); ?></label>
                            <input type="text" name="flavor_ml_term[<?php echo esc_attr($code); ?>][slug]"
                                   value="<?php echo esc_attr($slug_trans); ?>" class="widefat">
                        </p>
                        <p>
                            <label><?php esc_html_e('Descripción', 'flavor-multilingual'); ?></label>
                            <textarea name="flavor_ml_term[<?php echo esc_attr($code); ?>][description]"
                                      rows="3" class="widefat"><?php echo esc_textarea($desc_trans); ?></textarea>
                        </p>
                        <p>
                            <button type="button" class="button flavor-ml-translate-term-ai"
                                    data-term-id="<?php echo esc_attr($term->term_id); ?>"
                                    data-lang="<?php echo esc_attr($code); ?>">
                                <span class="dashicons dashicons-translation"></span>
                                <?php esc_html_e('Traducir con IA', 'flavor-multilingual'); ?>
                            </button>
                        </p>
                    </div>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Guarda traducciones del término
     *
     * @param int $term_id   ID del término
     * @param int $tt_id     ID de term_taxonomy
     */
    public function save_term_translations($term_id, $tt_id) {
        if (!isset($_POST['flavor_ml_term_nonce']) ||
            !wp_verify_nonce($_POST['flavor_ml_term_nonce'], 'flavor_ml_term_translations')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        $translations = isset($_POST['flavor_ml_term']) ? $_POST['flavor_ml_term'] : array();

        foreach ($translations as $lang => $fields) {
            $lang = sanitize_key($lang);

            foreach ($fields as $field => $value) {
                $field = sanitize_key($field);
                $value = $field === 'description'
                    ? wp_kses_post($value)
                    : sanitize_text_field($value);

                if (!empty($value)) {
                    $this->save_term_translation($term_id, $lang, $field, $value);
                } else {
                    $this->delete_term_translation($term_id, $lang, $field);
                }
            }
        }
    }

    /**
     * Guarda una traducción de término
     *
     * @param int    $term_id ID del término
     * @param string $lang    Código de idioma
     * @param string $field   Campo (name, slug, description)
     * @param string $value   Valor traducido
     * @param bool   $is_auto Si es traducción automática
     * @return bool
     */
    public function save_term_translation($term_id, $lang, $field, $value, $is_auto = false) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table}
             WHERE object_type = 'term' AND object_id = %d
             AND language_code = %s AND field_name = %s",
            $term_id, $lang, $field
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
            'object_type'        => 'term',
            'object_id'          => $term_id,
            'language_code'      => $lang,
            'field_name'         => $field,
            'translation'        => $value,
            'is_auto_translated' => $is_auto ? 1 : 0,
            'status'             => 'published',
            'created_at'         => current_time('mysql'),
        )) !== false;
    }

    /**
     * Obtiene traducción de término
     *
     * @param int    $term_id ID del término
     * @param string $lang    Código de idioma
     * @param string $field   Campo
     * @return string|null
     */
    public function get_term_translation($term_id, $lang, $field) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT translation FROM {$this->table}
             WHERE object_type = 'term' AND object_id = %d
             AND language_code = %s AND field_name = %s",
            $term_id, $lang, $field
        ));
    }

    /**
     * Elimina traducción de término
     *
     * @param int    $term_id ID del término
     * @param string $lang    Código de idioma
     * @param string $field   Campo
     * @return bool
     */
    public function delete_term_translation($term_id, $lang, $field) {
        global $wpdb;

        return $wpdb->delete($this->table, array(
            'object_type'   => 'term',
            'object_id'     => $term_id,
            'language_code' => $lang,
            'field_name'    => $field,
        )) !== false;
    }

    /**
     * Añade columna de traducciones al listado de términos
     *
     * @param array $columns Columnas
     * @return array
     */
    public function add_translation_column($columns) {
        $columns['translations'] = __('Traducciones', 'flavor-multilingual');
        return $columns;
    }

    /**
     * Renderiza columna de traducciones
     *
     * @param string $content  Contenido
     * @param string $column   Columna
     * @param int    $term_id  ID del término
     * @return string
     */
    public function render_translation_column($content, $column, $term_id) {
        if ($column !== 'translations') {
            return $content;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $output = '<div class="flavor-ml-term-translations-status">';

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $has_trans = $this->get_term_translation($term_id, $code, 'name') !== null;

            $output .= sprintf(
                '<span class="flavor-ml-lang-status %s" title="%s">%s</span>',
                $has_trans ? 'has-translation' : 'no-translation',
                esc_attr($lang['name']),
                esc_html(strtoupper($code))
            );
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Filtra un término para mostrar traducción
     *
     * @param WP_Term $term     Término
     * @param string  $taxonomy Taxonomía
     * @return WP_Term
     */
    public function filter_term($term, $taxonomy) {
        if (is_admin() || !is_object($term)) {
            return $term;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $term;
        }

        $current_lang = $core->get_current_language();

        // Traducir nombre
        $name_trans = $this->get_term_translation($term->term_id, $current_lang, 'name');
        if ($name_trans) {
            $term->name = $name_trans;
        }

        // Traducir descripción
        $desc_trans = $this->get_term_translation($term->term_id, $current_lang, 'description');
        if ($desc_trans) {
            $term->description = $desc_trans;
        }

        // Traducir slug (para URLs)
        $slug_trans = $this->get_term_translation($term->term_id, $current_lang, 'slug');
        if ($slug_trans) {
            $term->slug = $slug_trans;
        }

        return $term;
    }

    /**
     * Filtra array de términos
     *
     * @param array         $terms      Términos
     * @param array|null    $taxonomies Taxonomías
     * @param array         $query_vars Variables de query
     * @param WP_Term_Query $term_query Query
     * @return array
     */
    public function filter_terms($terms, $taxonomies, $query_vars, $term_query) {
        if (is_admin() || empty($terms)) {
            return $terms;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $terms;
        }

        foreach ($terms as $term) {
            if (is_object($term)) {
                $this->filter_term($term, $term->taxonomy ?? '');
            }
        }

        return $terms;
    }

    /**
     * Filtra enlace del término
     *
     * @param string $link     URL
     * @param object $term     Término
     * @param string $taxonomy Taxonomía
     * @return string
     */
    public function filter_term_link($link, $term, $taxonomy) {
        if (is_admin()) {
            return $link;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $link;
        }

        // Añadir parámetro de idioma a la URL
        $current_lang = $core->get_current_language();
        $url_mode = Flavor_Multilingual::get_option('url_mode', 'parameter');

        if ($url_mode === 'parameter') {
            $link = add_query_arg('lang', $current_lang, $link);
        }

        return $link;
    }

    /**
     * AJAX: Guardar traducción de término
     */
    public function ajax_save_term() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $term_id = intval($_POST['term_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $field = sanitize_key($_POST['field'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        if (!$term_id || !$lang || !$field) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        $result = $this->save_term_translation($term_id, $lang, $field, $value);

        if ($result) {
            wp_send_json_success(array('message' => __('Traducción guardada', 'flavor-multilingual')));
        } else {
            wp_send_json_error(__('Error al guardar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Obtener traducciones de término
     */
    public function ajax_get_term_translations() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        $term_id = intval($_POST['term_id'] ?? 0);

        if (!$term_id) {
            wp_send_json_error(__('Término no especificado', 'flavor-multilingual'));
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, field_name, translation
             FROM {$this->table}
             WHERE object_type = 'term' AND object_id = %d",
            $term_id
        ), ARRAY_A);

        $translations = array();
        foreach ($results as $row) {
            $lang = $row['language_code'];
            if (!isset($translations[$lang])) {
                $translations[$lang] = array();
            }
            $translations[$lang][$row['field_name']] = $row['translation'];
        }

        wp_send_json_success($translations);
    }

    /**
     * AJAX: Traducir término con IA
     */
    public function ajax_translate_term_ai() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $term_id = intval($_POST['term_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$term_id || !$lang) {
            wp_send_json_error(__('Datos incompletos', 'flavor-multilingual'));
        }

        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(__('Término no encontrado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $translations = array();

        // Traducir nombre
        if (!empty($term->name)) {
            $trans = $translator->translate_text($term->name, $from_lang, $lang, 'Nombre de categoría/etiqueta');
            if (!is_wp_error($trans)) {
                $translations['name'] = $trans;
                $this->save_term_translation($term_id, $lang, 'name', $trans, true);
            }
        }

        // Traducir descripción
        if (!empty($term->description)) {
            $trans = $translator->translate_text($term->description, $from_lang, $lang, 'Descripción de categoría');
            if (!is_wp_error($trans)) {
                $translations['description'] = $trans;
                $this->save_term_translation($term_id, $lang, 'description', $trans, true);
            }
        }

        // Generar slug traducido
        if (!empty($translations['name'])) {
            $slug = sanitize_title($translations['name']);
            $translations['slug'] = $slug;
            $this->save_term_translation($term_id, $lang, 'slug', $slug, true);
        }

        wp_send_json_success(array(
            'translations' => $translations,
            'message'      => __('Traducido con IA', 'flavor-multilingual'),
        ));
    }

    /**
     * Obtiene taxonomías traducibles
     *
     * @return array
     */
    public static function get_translatable_taxonomies() {
        $taxonomies = get_taxonomies(array('public' => true), 'objects');

        $excluded = apply_filters('flavor_multilingual_excluded_taxonomies', array(
            'post_format',
            'nav_menu',
            'link_category',
            'wp_theme',
            'wp_template_part_area',
        ));

        foreach ($excluded as $tax) {
            unset($taxonomies[$tax]);
        }

        return apply_filters('flavor_multilingual_translatable_taxonomies', $taxonomies);
    }
}

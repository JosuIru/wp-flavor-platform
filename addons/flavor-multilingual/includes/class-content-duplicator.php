<?php
/**
 * Duplicador de Contenido para Traducción
 *
 * Permite duplicar posts/páginas para traducir, con opción de traducción automática con IA.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Content_Duplicator {

    /**
     * Instancia singleton
     *
     * @var Flavor_Content_Duplicator|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Content_Duplicator
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
        // Acciones de fila en listados
        add_filter('post_row_actions', array($this, 'add_row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_row_actions'), 10, 2);

        // Metabox en editor
        add_action('add_meta_boxes', array($this, 'add_meta_box'));

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_duplicate_for_translation', array($this, 'ajax_duplicate'));
        add_action('wp_ajax_flavor_ml_duplicate_and_translate', array($this, 'ajax_duplicate_and_translate'));
        add_action('wp_ajax_flavor_ml_translate_all_fields', array($this, 'ajax_translate_all_fields'));
        add_action('wp_ajax_flavor_ml_get_translation_status', array($this, 'ajax_get_status'));

        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Añade acciones a las filas de posts
     *
     * @param array   $actions Acciones existentes
     * @param WP_Post $post    Objeto post
     * @return array
     */
    public function add_row_actions($actions, $post) {
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        // Solo mostrar si hay más de un idioma
        if (count($languages) < 2) {
            return $actions;
        }

        // Dropdown de idiomas para duplicar
        $dropdown_items = array();
        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $dropdown_items[] = sprintf(
                '<a href="#" class="flavor-ml-duplicate-link" data-post-id="%d" data-lang="%s" data-action="duplicate">%s %s</a>',
                $post->ID,
                esc_attr($code),
                esc_html($lang['flag'] ? '🔄' : ''),
                esc_html($lang['native_name'])
            );

            $dropdown_items[] = sprintf(
                '<a href="#" class="flavor-ml-duplicate-link" data-post-id="%d" data-lang="%s" data-action="translate">%s %s (+ IA)</a>',
                $post->ID,
                esc_attr($code),
                esc_html($lang['flag'] ? '🤖' : ''),
                esc_html($lang['native_name'])
            );
        }

        if (!empty($dropdown_items)) {
            $actions['flavor_ml_duplicate'] = sprintf(
                '<span class="flavor-ml-duplicate-dropdown">
                    <a href="#" class="flavor-ml-duplicate-trigger">%s ▼</a>
                    <span class="flavor-ml-duplicate-menu">%s</span>
                </span>',
                esc_html__('Traducir', 'flavor-multilingual'),
                implode('', array_map(function($item) {
                    return '<span class="flavor-ml-dropdown-item">' . $item . '</span>';
                }, $dropdown_items))
            );
        }

        return $actions;
    }

    /**
     * Añade metabox en el editor
     */
    public function add_meta_box() {
        $post_types = get_post_types(array('public' => true), 'names');

        foreach ($post_types as $post_type) {
            add_meta_box(
                'flavor_ml_duplicator',
                __('🌍 Traducciones', 'flavor-multilingual'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Renderiza el metabox
     *
     * @param WP_Post $post Objeto post
     */
    public function render_meta_box($post) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Obtener traducciones existentes
        $translations = $storage->get_all_translations('post', $post->ID);

        wp_nonce_field('flavor_ml_duplicator', 'flavor_ml_duplicator_nonce');
        ?>
        <div class="flavor-ml-duplicator-box">
            <p class="description">
                <?php esc_html_e('Traduce este contenido a otros idiomas:', 'flavor-multilingual'); ?>
            </p>

            <div class="flavor-ml-lang-list">
                <?php foreach ($languages as $code => $lang) :
                    if ($code === $default_lang) continue;

                    $has_translation = isset($translations[$code]['title']);
                    $status_class = $has_translation ? 'has-translation' : 'no-translation';
                    $status_icon = $has_translation ? '✓' : '○';
                    ?>
                    <div class="flavor-ml-lang-item <?php echo esc_attr($status_class); ?>" data-lang="<?php echo esc_attr($code); ?>">
                        <span class="flavor-ml-lang-info">
                            <span class="flavor-ml-status-icon"><?php echo $status_icon; ?></span>
                            <strong><?php echo esc_html($lang['native_name']); ?></strong>
                        </span>

                        <span class="flavor-ml-lang-actions">
                            <?php if ($has_translation) : ?>
                                <button type="button" class="button button-small flavor-ml-edit-translation"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        data-lang="<?php echo esc_attr($code); ?>">
                                    <?php esc_html_e('Editar', 'flavor-multilingual'); ?>
                                </button>
                            <?php else : ?>
                                <button type="button" class="button button-small flavor-ml-duplicate-btn"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        data-lang="<?php echo esc_attr($code); ?>"
                                        data-action="duplicate">
                                    <?php esc_html_e('Crear', 'flavor-multilingual'); ?>
                                </button>
                                <button type="button" class="button button-small button-primary flavor-ml-translate-ai-btn"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        data-lang="<?php echo esc_attr($code); ?>"
                                        data-action="translate"
                                        title="<?php esc_attr_e('Traducir con IA', 'flavor-multilingual'); ?>">
                                    🤖 IA
                                </button>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr style="margin: 15px 0;">

            <div class="flavor-ml-bulk-actions">
                <button type="button" class="button button-primary flavor-ml-translate-all-btn"
                        data-post-id="<?php echo esc_attr($post->ID); ?>">
                    🤖 <?php esc_html_e('Traducir TODO con IA', 'flavor-multilingual'); ?>
                </button>
            </div>

            <div class="flavor-ml-progress" style="display: none;">
                <div class="flavor-ml-progress-bar">
                    <div class="flavor-ml-progress-fill"></div>
                </div>
                <p class="flavor-ml-progress-text"></p>
            </div>
        </div>

        <style>
            .flavor-ml-duplicator-box { padding: 5px 0; }
            .flavor-ml-lang-list { margin: 10px 0; }
            .flavor-ml-lang-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px;
                margin-bottom: 5px;
                background: #f9f9f9;
                border-radius: 4px;
                border-left: 3px solid #ddd;
            }
            .flavor-ml-lang-item.has-translation { border-left-color: #00a32a; background: #f0fff0; }
            .flavor-ml-lang-item.no-translation { border-left-color: #dba617; }
            .flavor-ml-status-icon { margin-right: 8px; }
            .flavor-ml-lang-actions { display: flex; gap: 5px; }
            .flavor-ml-lang-actions .button { padding: 0 8px; font-size: 11px; }
            .flavor-ml-bulk-actions { text-align: center; }
            .flavor-ml-bulk-actions .button { width: 100%; }
            .flavor-ml-progress { margin-top: 15px; }
            .flavor-ml-progress-bar {
                height: 8px;
                background: #ddd;
                border-radius: 4px;
                overflow: hidden;
            }
            .flavor-ml-progress-fill {
                height: 100%;
                background: #2271b1;
                width: 0%;
                transition: width 0.3s;
            }
            .flavor-ml-progress-text {
                font-size: 11px;
                color: #666;
                text-align: center;
                margin-top: 5px;
            }
        </style>
        <?php
    }

    /**
     * Encola scripts del admin
     *
     * @param string $hook Hook actual
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        wp_enqueue_script(
            'flavor-ml-duplicator',
            FLAVOR_MULTILINGUAL_URL . 'admin/js/duplicator.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-ml-duplicator', 'flavorMLDuplicator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_multilingual_admin'),
            'i18n'    => array(
                'translating'     => __('Traduciendo...', 'flavor-multilingual'),
                'translated'      => __('Traducido', 'flavor-multilingual'),
                'error'           => __('Error en la traducción', 'flavor-multilingual'),
                'confirm_all'     => __('¿Traducir a TODOS los idiomas con IA? Esto puede tardar unos momentos.', 'flavor-multilingual'),
                'progress'        => __('Traduciendo %s...', 'flavor-multilingual'),
                'complete'        => __('¡Traducciones completadas!', 'flavor-multilingual'),
            ),
        ));

        // Estilos inline para el dropdown en listados
        wp_add_inline_style('wp-admin', '
            .flavor-ml-duplicate-dropdown { position: relative; }
            .flavor-ml-duplicate-menu {
                display: none;
                position: absolute;
                background: #fff;
                border: 1px solid #ddd;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                min-width: 180px;
                border-radius: 4px;
                left: 0;
                top: 100%;
            }
            .flavor-ml-duplicate-dropdown:hover .flavor-ml-duplicate-menu { display: block; }
            .flavor-ml-dropdown-item { display: block; padding: 8px 12px; border-bottom: 1px solid #eee; }
            .flavor-ml-dropdown-item:last-child { border-bottom: none; }
            .flavor-ml-dropdown-item a { text-decoration: none; display: block; }
            .flavor-ml-dropdown-item a:hover { color: #2271b1; }
        ');
    }

    /**
     * Duplica un post para traducción (sin traducir)
     *
     * @param int    $post_id ID del post original
     * @param string $lang    Código de idioma
     * @return array Resultado con traducciones vacías creadas
     */
    public function duplicate_for_translation($post_id, $lang) {
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'message' => 'Post no encontrado');
        }

        $storage = Flavor_Translation_Storage::get_instance();

        // Crear entradas vacías para todos los campos
        $fields = array(
            'title'            => '',
            'content'          => '',
            'excerpt'          => '',
            'meta_title'       => '',
            'meta_description' => '',
        );

        foreach ($fields as $field => $value) {
            $storage->save_translation('post', $post_id, $lang, $field, $value, array(
                'status' => 'draft',
                'auto'   => false,
            ));
        }

        // Duplicar slug
        if (class_exists('Flavor_Slug_Translator')) {
            $slug_translator = Flavor_Slug_Translator::get_instance();
            $slug_translator->save_slug('post', $post_id, $lang, $post->post_name, $post->post_name, array(
                'post_type' => $post->post_type,
            ));
        }

        return array(
            'success' => true,
            'message' => 'Duplicado creado, listo para traducir',
            'post_id' => $post_id,
            'lang'    => $lang,
        );
    }

    /**
     * Duplica y traduce un post con IA
     *
     * @param int    $post_id ID del post original
     * @param string $lang    Código de idioma
     * @return array Resultado con traducciones
     */
    public function duplicate_and_translate($post_id, $lang) {
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'message' => 'Post no encontrado');
        }

        if (!class_exists('Flavor_AI_Translator')) {
            return array('success' => false, 'message' => 'Traductor IA no disponible');
        }

        $ai_translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();
        $translated_fields = array();

        // Traducir título
        $translated_title = $ai_translator->translate($post->post_title, $lang, array(
            'context' => 'post_title',
            'format'  => 'plain',
        ));

        if ($translated_title && !is_wp_error($translated_title)) {
            $storage->save_translation('post', $post_id, $lang, 'title', $translated_title, array(
                'status' => 'draft',
                'auto'   => true,
            ));
            $translated_fields['title'] = $translated_title;
        }

        // Traducir contenido
        $translated_content = $ai_translator->translate($post->post_content, $lang, array(
            'context' => 'post_content',
            'format'  => 'html',
        ));

        if ($translated_content && !is_wp_error($translated_content)) {
            $storage->save_translation('post', $post_id, $lang, 'content', $translated_content, array(
                'status' => 'draft',
                'auto'   => true,
            ));
            $translated_fields['content'] = $translated_content;
        }

        // Traducir extracto
        if (!empty($post->post_excerpt)) {
            $translated_excerpt = $ai_translator->translate($post->post_excerpt, $lang, array(
                'context' => 'post_excerpt',
                'format'  => 'plain',
            ));

            if ($translated_excerpt && !is_wp_error($translated_excerpt)) {
                $storage->save_translation('post', $post_id, $lang, 'excerpt', $translated_excerpt, array(
                    'status' => 'draft',
                    'auto'   => true,
                ));
                $translated_fields['excerpt'] = $translated_excerpt;
            }
        }

        // Traducir meta SEO
        $meta_title = get_post_meta($post_id, '_yoast_wpseo_title', true) ?: get_post_meta($post_id, '_flavor_meta_title', true);
        $meta_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: get_post_meta($post_id, '_flavor_meta_description', true);

        if ($meta_title) {
            $translated_meta_title = $ai_translator->translate($meta_title, $lang, array(
                'context' => 'seo_title',
                'format'  => 'plain',
            ));

            if ($translated_meta_title && !is_wp_error($translated_meta_title)) {
                $storage->save_translation('post', $post_id, $lang, 'meta_title', $translated_meta_title, array(
                    'status' => 'draft',
                    'auto'   => true,
                ));
                $translated_fields['meta_title'] = $translated_meta_title;
            }
        }

        if ($meta_desc) {
            $translated_meta_desc = $ai_translator->translate($meta_desc, $lang, array(
                'context' => 'seo_description',
                'format'  => 'plain',
            ));

            if ($translated_meta_desc && !is_wp_error($translated_meta_desc)) {
                $storage->save_translation('post', $post_id, $lang, 'meta_description', $translated_meta_desc, array(
                    'status' => 'draft',
                    'auto'   => true,
                ));
                $translated_fields['meta_description'] = $translated_meta_desc;
            }
        }

        // Traducir slug
        if (class_exists('Flavor_Slug_Translator')) {
            $slug_translator = Flavor_Slug_Translator::get_instance();
            $translated_slug = $slug_translator->translate_slug_with_ai($post->post_name, $post->post_title, $lang);

            if ($translated_slug) {
                $slug_translator->save_slug('post', $post_id, $lang, $post->post_name, $translated_slug, array(
                    'post_type' => $post->post_type,
                ));
                $translated_fields['slug'] = $translated_slug;
            }
        }

        return array(
            'success'      => true,
            'message'      => 'Contenido traducido con IA',
            'post_id'      => $post_id,
            'lang'         => $lang,
            'translations' => $translated_fields,
        );
    }

    /**
     * Traduce un post a TODOS los idiomas con IA
     *
     * @param int $post_id ID del post
     * @return array Resultados por idioma
     */
    public function translate_to_all_languages($post_id) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $results = array();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $result = $this->duplicate_and_translate($post_id, $code);
            $results[$code] = $result;
        }

        return $results;
    }

    /**
     * AJAX: Duplica contenido para traducción
     */
    public function ajax_duplicate() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$lang) {
            wp_send_json_error(array('message' => 'Datos incompletos'));
        }

        $result = $this->duplicate_for_translation($post_id, $lang);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Duplica y traduce con IA
     */
    public function ajax_duplicate_and_translate() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$lang) {
            wp_send_json_error(array('message' => 'Datos incompletos'));
        }

        $result = $this->duplicate_and_translate($post_id, $lang);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Traduce a todos los idiomas
     */
    public function ajax_translate_all_fields() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $post_id = absint($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(array('message' => 'ID de post requerido'));
        }

        $results = $this->translate_to_all_languages($post_id);

        wp_send_json_success(array(
            'message' => 'Traducciones completadas',
            'results' => $results,
        ));
    }

    /**
     * AJAX: Obtiene estado de traducciones
     */
    public function ajax_get_status() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(array('message' => 'ID de post requerido'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('post', $post_id);

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $status = array();
        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $status[$code] = array(
                'name'           => $lang['native_name'],
                'has_title'      => !empty($translations[$code]['title']['value']),
                'has_content'    => !empty($translations[$code]['content']['value']),
                'is_complete'    => !empty($translations[$code]['title']['value']) && !empty($translations[$code]['content']['value']),
                'is_auto'        => !empty($translations[$code]['title']['auto']),
                'status'         => $translations[$code]['title']['status'] ?? 'none',
            );
        }

        wp_send_json_success(array(
            'post_id' => $post_id,
            'status'  => $status,
        ));
    }

    /**
     * Duplica contenido entre posts (para crear versión en otro idioma)
     *
     * @param int    $source_post_id ID del post origen
     * @param string $target_lang    Idioma destino
     * @param bool   $translate      Si traducir con IA
     * @return int|WP_Error ID del nuevo post o error
     */
    public function create_translated_copy($source_post_id, $target_lang, $translate = false) {
        $source_post = get_post($source_post_id);
        if (!$source_post) {
            return new WP_Error('invalid_post', 'Post origen no encontrado');
        }

        // Crear copia del post
        $new_post_data = array(
            'post_title'   => $source_post->post_title . ' (' . strtoupper($target_lang) . ')',
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => $source_post->post_type,
            'post_author'  => get_current_user_id(),
        );

        // Si se debe traducir
        if ($translate && class_exists('Flavor_AI_Translator')) {
            $ai_translator = Flavor_AI_Translator::get_instance();

            $translated_title = $ai_translator->translate($source_post->post_title, $target_lang);
            if ($translated_title && !is_wp_error($translated_title)) {
                $new_post_data['post_title'] = $translated_title;
            }

            $translated_content = $ai_translator->translate($source_post->post_content, $target_lang, array('format' => 'html'));
            if ($translated_content && !is_wp_error($translated_content)) {
                $new_post_data['post_content'] = $translated_content;
            }

            if (!empty($source_post->post_excerpt)) {
                $translated_excerpt = $ai_translator->translate($source_post->post_excerpt, $target_lang);
                if ($translated_excerpt && !is_wp_error($translated_excerpt)) {
                    $new_post_data['post_excerpt'] = $translated_excerpt;
                }
            }
        }

        $new_post_id = wp_insert_post($new_post_data);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        // Copiar meta datos
        $meta_keys = get_post_custom_keys($source_post_id);
        if ($meta_keys) {
            foreach ($meta_keys as $meta_key) {
                if (strpos($meta_key, '_edit_') === 0) {
                    continue;
                }

                $meta_values = get_post_meta($source_post_id, $meta_key);
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, $meta_value);
                }
            }
        }

        // Copiar taxonomías
        $taxonomies = get_object_taxonomies($source_post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($source_post_id, $taxonomy, array('fields' => 'ids'));
            if (!is_wp_error($terms)) {
                wp_set_object_terms($new_post_id, $terms, $taxonomy);
            }
        }

        // Guardar relación entre posts
        update_post_meta($new_post_id, '_flavor_ml_source_post', $source_post_id);
        update_post_meta($new_post_id, '_flavor_ml_language', $target_lang);

        // Añadir a la lista de traducciones del post original
        $translations = get_post_meta($source_post_id, '_flavor_ml_translations', true) ?: array();
        $translations[$target_lang] = $new_post_id;
        update_post_meta($source_post_id, '_flavor_ml_translations', $translations);

        return $new_post_id;
    }
}

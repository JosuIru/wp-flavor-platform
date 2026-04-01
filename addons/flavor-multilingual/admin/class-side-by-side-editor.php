<?php
/**
 * Editor lado a lado para traducciones
 *
 * Permite ver y editar traducciones comparando con el original.
 *
 * @package FlavorMultilingual
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Side_By_Side_Editor {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
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
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_ml_save_translation_field', array($this, 'ajax_save_field'));
        add_action('wp_ajax_flavor_ml_ai_translate_field', array($this, 'ajax_ai_translate_field'));
        add_action('wp_ajax_flavor_ml_get_tm_suggestions', array($this, 'ajax_get_tm_suggestions'));
    }

    /**
     * Añadir página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            null, // No visible en menú
            __('Editor de Traducción', 'flavor-multilingual'),
            __('Editor', 'flavor-multilingual'),
            'flavor_translate',
            'flavor-multilingual-translate',
            array($this, 'render_page')
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'admin_page_flavor-multilingual-translate') {
            return;
        }

        wp_enqueue_style(
            'flavor-ml-sbs-editor',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/side-by-side-editor.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-ml-sbs-editor',
            FLAVOR_MULTILINGUAL_URL . 'assets/js/side-by-side-editor.js',
            array('jquery', 'wp-util'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-ml-sbs-editor', 'flavorMLEditor', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('flavor_ml_editor'),
            'strings'      => array(
                'saving'       => __('Guardando...', 'flavor-multilingual'),
                'saved'        => __('Guardado', 'flavor-multilingual'),
                'error'        => __('Error al guardar', 'flavor-multilingual'),
                'translating'  => __('Traduciendo...', 'flavor-multilingual'),
                'translated'   => __('Traducido', 'flavor-multilingual'),
                'confirm_ai'   => __('¿Traducir este campo con IA?', 'flavor-multilingual'),
                'unsaved'      => __('Tienes cambios sin guardar. ¿Salir de todos modos?', 'flavor-multilingual'),
            ),
        ));
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        $target_lang = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '';

        if (!$post_id) {
            echo '<div class="notice notice-error"><p>' . __('Post no especificado', 'flavor-multilingual') . '</p></div>';
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            echo '<div class="notice notice-error"><p>' . __('Post no encontrado', 'flavor-multilingual') . '</p></div>';
            return;
        }

        // Verificar permisos
        if (!current_user_can('flavor_translate')) {
            echo '<div class="notice notice-error"><p>' . __('No tienes permisos para traducir', 'flavor-multilingual') . '</p></div>';
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        // Si no hay idioma seleccionado, mostrar selector
        if (empty($target_lang) || !isset($active_languages[$target_lang])) {
            $this->render_language_selector($post, $active_languages, $default_lang);
            return;
        }

        // Renderizar editor
        $this->render_editor($post, $target_lang, $active_languages, $default_lang);
    }

    /**
     * Renderizar selector de idioma
     */
    private function render_language_selector($post, $languages, $default_lang) {
        ?>
        <div class="wrap flavor-ml-editor-wrap">
            <h1><?php echo esc_html($post->post_title); ?></h1>
            <p><?php _e('Selecciona el idioma de destino para la traducción:', 'flavor-multilingual'); ?></p>

            <div class="flavor-ml-lang-grid">
                <?php foreach ($languages as $code => $lang) :
                    if ($code === $default_lang) continue;

                    $storage = Flavor_Translation_Storage::get_instance();
                    $progress = $storage->get_translation_progress($post->ID, $code);
                    $url = add_query_arg(array(
                        'page'    => 'flavor-multilingual-translate',
                        'post_id' => $post->ID,
                        'lang'    => $code,
                    ), admin_url('admin.php'));
                ?>
                    <a href="<?php echo esc_url($url); ?>" class="flavor-ml-lang-card">
                        <div class="lang-header">
                            <?php if (!empty($lang['flag'])) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>" alt="" class="flag">
                            <?php endif; ?>
                            <span class="lang-name"><?php echo esc_html($lang['native_name']); ?></span>
                        </div>
                        <div class="lang-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                            </div>
                            <span class="progress-text"><?php echo esc_html($progress); ?>%</span>
                        </div>
                        <span class="lang-action"><?php _e('Traducir', 'flavor-multilingual'); ?> &rarr;</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar editor lado a lado
     */
    private function render_editor($post, $target_lang, $languages, $default_lang) {
        $storage = Flavor_Translation_Storage::get_instance();
        $source_lang = $languages[$default_lang] ?? array();
        $target_language = $languages[$target_lang] ?? array();

        // Obtener campos traducibles
        $fields = $this->get_translatable_fields($post);

        // Obtener traducciones existentes
        $translations = array();
        foreach ($fields as $field_key => $field) {
            $translations[$field_key] = $storage->get_translation('post', $post->ID, $target_lang, $field_key);
        }

        // Obtener estado
        $status = get_post_meta($post->ID, '_flavor_ml_status_' . $target_lang, true) ?: 'pending';
        ?>
        <div class="wrap flavor-ml-editor-wrap">
            <div class="flavor-ml-editor-header">
                <div class="header-left">
                    <a href="<?php echo esc_url(add_query_arg('page', 'flavor-multilingual-translate', admin_url('admin.php')) . '&post_id=' . $post->ID); ?>" class="back-link">
                        &larr; <?php _e('Cambiar idioma', 'flavor-multilingual'); ?>
                    </a>
                    <h1><?php echo esc_html($post->post_title); ?></h1>
                </div>
                <div class="header-right">
                    <span class="status-badge status-<?php echo esc_attr($status); ?>">
                        <?php echo esc_html($this->get_status_label($status)); ?>
                    </span>
                    <button type="button" id="flavor-ml-save-all" class="button button-primary">
                        <?php _e('Guardar todo', 'flavor-multilingual'); ?>
                    </button>
                    <button type="button" id="flavor-ml-translate-all" class="button">
                        <?php _e('Traducir todo con IA', 'flavor-multilingual'); ?>
                    </button>
                </div>
            </div>

            <div class="flavor-ml-editor-toolbar">
                <div class="toolbar-left">
                    <span class="source-lang">
                        <?php if (!empty($source_lang['flag'])) : ?>
                            <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $source_lang['flag']); ?>" alt="">
                        <?php endif; ?>
                        <?php echo esc_html($source_lang['native_name'] ?? $default_lang); ?>
                    </span>
                    <span class="lang-arrow">&rarr;</span>
                    <span class="target-lang">
                        <?php if (!empty($target_language['flag'])) : ?>
                            <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $target_language['flag']); ?>" alt="">
                        <?php endif; ?>
                        <?php echo esc_html($target_language['native_name'] ?? $target_lang); ?>
                    </span>
                </div>
                <div class="toolbar-right">
                    <label class="toggle-option">
                        <input type="checkbox" id="show-tm-suggestions" checked>
                        <?php _e('Mostrar sugerencias TM', 'flavor-multilingual'); ?>
                    </label>
                </div>
            </div>

            <form id="flavor-ml-editor-form" data-post-id="<?php echo esc_attr($post->ID); ?>" data-lang="<?php echo esc_attr($target_lang); ?>">
                <?php wp_nonce_field('flavor_ml_editor', 'flavor_ml_editor_nonce'); ?>

                <div class="flavor-ml-fields-container">
                    <?php foreach ($fields as $field_key => $field) :
                        $original_value = $field['value'];
                        $translated_value = $translations[$field_key] ?? '';
                        $is_translated = !empty($translated_value);
                    ?>
                        <div class="flavor-ml-field-row <?php echo $is_translated ? 'is-translated' : 'not-translated'; ?>" data-field="<?php echo esc_attr($field_key); ?>">
                            <div class="field-header">
                                <span class="field-label"><?php echo esc_html($field['label']); ?></span>
                                <span class="field-type"><?php echo esc_html($field['type']); ?></span>
                                <div class="field-actions">
                                    <button type="button" class="ai-translate-btn" title="<?php esc_attr_e('Traducir con IA', 'flavor-multilingual'); ?>">
                                        <span class="dashicons dashicons-translation"></span>
                                    </button>
                                    <button type="button" class="copy-source-btn" title="<?php esc_attr_e('Copiar original', 'flavor-multilingual'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="field-columns">
                                <!-- Original -->
                                <div class="field-column source-column">
                                    <div class="column-header"><?php _e('Original', 'flavor-multilingual'); ?></div>
                                    <?php $this->render_source_field($field_key, $field, $original_value); ?>
                                </div>

                                <!-- Traducción -->
                                <div class="field-column target-column">
                                    <div class="column-header">
                                        <?php _e('Traducción', 'flavor-multilingual'); ?>
                                        <span class="save-indicator"></span>
                                    </div>
                                    <?php $this->render_target_field($field_key, $field, $translated_value); ?>
                                </div>
                            </div>

                            <!-- Sugerencias TM -->
                            <div class="tm-suggestions" data-field="<?php echo esc_attr($field_key); ?>" style="display:none;">
                                <div class="tm-header">
                                    <span class="dashicons dashicons-database"></span>
                                    <?php _e('Sugerencias de la memoria de traducción', 'flavor-multilingual'); ?>
                                </div>
                                <div class="tm-list"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-ml-editor-footer">
                    <div class="footer-left">
                        <select id="translation-status" name="translation_status">
                            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pendiente', 'flavor-multilingual'); ?></option>
                            <option value="in_progress" <?php selected($status, 'in_progress'); ?>><?php _e('En progreso', 'flavor-multilingual'); ?></option>
                            <option value="needs_review" <?php selected($status, 'needs_review'); ?>><?php _e('Necesita revisión', 'flavor-multilingual'); ?></option>
                            <?php if (current_user_can('flavor_review_translations')) : ?>
                                <option value="approved" <?php selected($status, 'approved'); ?>><?php _e('Aprobada', 'flavor-multilingual'); ?></option>
                                <option value="published" <?php selected($status, 'published'); ?>><?php _e('Publicada', 'flavor-multilingual'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="footer-right">
                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button">
                            <?php _e('Volver al post', 'flavor-multilingual'); ?>
                        </a>
                        <button type="submit" class="button button-primary button-large">
                            <?php _e('Guardar y continuar', 'flavor-multilingual'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Obtener campos traducibles de un post
     */
    private function get_translatable_fields($post) {
        $fields = array();

        // Campos estándar
        $fields['title'] = array(
            'label' => __('Título', 'flavor-multilingual'),
            'type'  => 'text',
            'value' => $post->post_title,
        );

        $fields['content'] = array(
            'label' => __('Contenido', 'flavor-multilingual'),
            'type'  => 'editor',
            'value' => $post->post_content,
        );

        if (!empty($post->post_excerpt)) {
            $fields['excerpt'] = array(
                'label' => __('Extracto', 'flavor-multilingual'),
                'type'  => 'textarea',
                'value' => $post->post_excerpt,
            );
        }

        // Meta SEO
        $seo_title = get_post_meta($post->ID, '_yoast_wpseo_title', true)
            ?: get_post_meta($post->ID, 'rank_math_title', true)
            ?: get_post_meta($post->ID, '_flavor_seo_title', true);

        if ($seo_title) {
            $fields['seo_title'] = array(
                'label' => __('Título SEO', 'flavor-multilingual'),
                'type'  => 'text',
                'value' => $seo_title,
            );
        }

        $seo_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)
            ?: get_post_meta($post->ID, 'rank_math_description', true)
            ?: get_post_meta($post->ID, '_flavor_seo_description', true);

        if ($seo_desc) {
            $fields['seo_description'] = array(
                'label' => __('Meta descripción', 'flavor-multilingual'),
                'type'  => 'textarea',
                'value' => $seo_desc,
            );
        }

        // ACF fields
        $fields = apply_filters('flavor_ml_translatable_fields', $fields, $post);

        return $fields;
    }

    /**
     * Renderizar campo fuente (solo lectura)
     */
    private function render_source_field($key, $field, $value) {
        switch ($field['type']) {
            case 'editor':
                echo '<div class="source-content">' . wp_kses_post(wpautop($value)) . '</div>';
                break;

            case 'textarea':
                echo '<div class="source-content"><pre>' . esc_html($value) . '</pre></div>';
                break;

            default:
                echo '<div class="source-content">' . esc_html($value) . '</div>';
        }
    }

    /**
     * Renderizar campo destino (editable)
     */
    private function render_target_field($key, $field, $value) {
        $name = 'translation[' . esc_attr($key) . ']';

        switch ($field['type']) {
            case 'editor':
                wp_editor($value, 'translation_' . $key, array(
                    'textarea_name' => $name,
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'quicktags'     => true,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,link,unlink,bullist,numlist,blockquote',
                        'toolbar2' => '',
                    ),
                ));
                break;

            case 'textarea':
                echo '<textarea name="' . esc_attr($name) . '" class="translation-field large-text" rows="4">' . esc_textarea($value) . '</textarea>';
                break;

            default:
                echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="translation-field regular-text">';
        }
    }

    /**
     * Obtener etiqueta de estado
     */
    private function get_status_label($status) {
        $labels = array(
            'pending'      => __('Pendiente', 'flavor-multilingual'),
            'in_progress'  => __('En progreso', 'flavor-multilingual'),
            'needs_review' => __('Revisión pendiente', 'flavor-multilingual'),
            'approved'     => __('Aprobada', 'flavor-multilingual'),
            'rejected'     => __('Rechazada', 'flavor-multilingual'),
            'published'    => __('Publicada', 'flavor-multilingual'),
        );

        return $labels[$status] ?? $status;
    }

    /**
     * AJAX: Guardar campo individual
     */
    public function ajax_save_field() {
        check_ajax_referer('flavor_ml_editor', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $lang = sanitize_text_field($_POST['lang'] ?? '');
        $field = sanitize_text_field($_POST['field'] ?? '');
        $value = wp_kses_post($_POST['value'] ?? '');

        if (!$post_id || !$lang || !$field) {
            wp_send_json_error(array('message' => __('Parámetros inválidos', 'flavor-multilingual')));
        }

        if (!current_user_can('flavor_translate')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'flavor-multilingual')));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $result = $storage->save_translation('post', $post_id, $lang, $field, $value, array(
            'auto' => false,
        ));

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Guardado', 'flavor-multilingual'),
                'field'   => $field,
            ));
        } else {
            wp_send_json_error(array('message' => __('Error al guardar', 'flavor-multilingual')));
        }
    }

    /**
     * AJAX: Traducir campo con IA
     */
    public function ajax_ai_translate_field() {
        check_ajax_referer('flavor_ml_editor', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $source_lang = sanitize_text_field($_POST['source_lang'] ?? '');
        $target_lang = sanitize_text_field($_POST['target_lang'] ?? '');
        $field = sanitize_text_field($_POST['field'] ?? '');
        $text = wp_kses_post($_POST['text'] ?? '');

        if (!$text || !$source_lang || !$target_lang) {
            wp_send_json_error(array('message' => __('Parámetros inválidos', 'flavor-multilingual')));
        }

        $translator = Flavor_AI_Translator::get_instance();
        $result = $translator->translate($text, $source_lang, $target_lang, array(
            'context'    => 'post_' . $field,
            'is_html'    => ($field === 'content'),
            'field_type' => $field,
        ));

        if ($result && !is_wp_error($result)) {
            wp_send_json_success(array(
                'translation' => $result,
                'field'       => $field,
            ));
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : __('Error al traducir', 'flavor-multilingual');
            wp_send_json_error(array('message' => $error_message));
        }
    }

    /**
     * AJAX: Obtener sugerencias de TM
     */
    public function ajax_get_tm_suggestions() {
        check_ajax_referer('flavor_ml_editor', 'nonce');

        $text = sanitize_text_field($_POST['text'] ?? '');
        $source_lang = sanitize_text_field($_POST['source_lang'] ?? '');
        $target_lang = sanitize_text_field($_POST['target_lang'] ?? '');

        if (empty($text) || strlen($text) < 10) {
            wp_send_json_success(array('suggestions' => array()));
        }

        $tm = Flavor_Translation_Memory::get_instance();
        $suggestions = $tm->find_similar($text, $source_lang, $target_lang, 0.6, 5);

        wp_send_json_success(array('suggestions' => $suggestions));
    }
}

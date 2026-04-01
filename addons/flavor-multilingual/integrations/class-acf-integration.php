<?php
/**
 * Integración con Advanced Custom Fields (ACF)
 *
 * Permite traducir campos personalizados de ACF.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_ACF_Integration {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_ACF_Integration|null
     */
    private static $instance = null;

    /**
     * Campos traducibles por tipo
     *
     * @var array
     */
    private $translatable_field_types = array(
        'text',
        'textarea',
        'wysiwyg',
        'url',
        'link',
        'oembed',
        'flexible_content',
        'repeater',
        'group',
    );

    /**
     * Campos que NO deben traducirse
     *
     * @var array
     */
    private $non_translatable_types = array(
        'image',
        'file',
        'gallery',
        'select',
        'checkbox',
        'radio',
        'button_group',
        'true_false',
        'number',
        'range',
        'email',
        'password',
        'color_picker',
        'date_picker',
        'date_time_picker',
        'time_picker',
        'post_object',
        'page_link',
        'relationship',
        'taxonomy',
        'user',
        'google_map',
        'accordion',
        'tab',
        'message',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_ACF_Integration
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
        // Solo inicializar si ACF está activo
        if (!$this->is_acf_active()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Verifica si ACF está activo
     *
     * @return bool
     */
    private function is_acf_active() {
        return class_exists('ACF') || function_exists('acf_get_field_groups');
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Filtrar valores de campos ACF en el frontend
        add_filter('acf/format_value', array($this, 'filter_acf_value'), 10, 3);

        // Añadir campos de traducción en el admin
        add_action('acf/render_field_settings', array($this, 'add_translation_settings'), 10, 1);

        // Metabox para traducciones de ACF
        add_action('add_meta_boxes', array($this, 'add_acf_translation_metabox'), 20);

        // AJAX para traducir campos ACF
        add_action('wp_ajax_flavor_ml_translate_acf', array($this, 'ajax_translate_acf_fields'));
        add_action('wp_ajax_flavor_ml_save_acf_translation', array($this, 'ajax_save_acf_translation'));
        add_action('wp_ajax_flavor_ml_get_acf_translations', array($this, 'ajax_get_acf_translations'));

        // Extender la traducción de posts para incluir ACF
        add_filter('flavor_multilingual_post_fields_to_translate', array($this, 'add_acf_fields_to_translation'), 10, 2);

        // Registrar campos ACF como traducibles
        add_filter('flavor_multilingual_translatable_meta_keys', array($this, 'register_acf_meta_keys'), 10, 2);
    }

    /**
     * Filtra valores de ACF para mostrar traducción
     *
     * @param mixed  $value   Valor del campo
     * @param int    $post_id ID del post
     * @param array  $field   Configuración del campo
     * @return mixed
     */
    public function filter_acf_value($value, $post_id, $field) {
        // No filtrar en admin
        if (is_admin() && !wp_doing_ajax()) {
            return $value;
        }

        // Verificar si el campo es traducible
        if (!$this->is_field_translatable($field)) {
            return $value;
        }

        // Obtener idioma actual
        $core = Flavor_Multilingual_Core::get_instance();

        // Si es el idioma por defecto, devolver valor original
        if ($core->is_default_language()) {
            return $value;
        }

        $current_lang = $core->get_current_language();
        $field_key = $field['key'] ?? $field['name'];

        // Buscar traducción
        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('acf', $post_id, $current_lang, $field_key);

        if ($translation !== null && $translation !== '') {
            // Para campos complejos, decodificar JSON
            if (in_array($field['type'], array('repeater', 'flexible_content', 'group'))) {
                $decoded = json_decode($translation, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            return $translation;
        }

        return $value;
    }

    /**
     * Verifica si un campo es traducible
     *
     * @param array $field Configuración del campo
     * @return bool
     */
    private function is_field_translatable($field) {
        $type = $field['type'] ?? '';

        // Verificar si está en la lista de no traducibles
        if (in_array($type, $this->non_translatable_types)) {
            return false;
        }

        // Verificar configuración específica del campo
        if (isset($field['flavor_translatable']) && !$field['flavor_translatable']) {
            return false;
        }

        // Verificar si está en la lista de traducibles
        return in_array($type, $this->translatable_field_types);
    }

    /**
     * Añade configuración de traducción a los campos ACF
     *
     * @param array $field Campo ACF
     */
    public function add_translation_settings($field) {
        // Solo para tipos traducibles
        if (!in_array($field['type'], $this->translatable_field_types)) {
            return;
        }

        acf_render_field_setting($field, array(
            'label'        => __('Traducible', 'flavor-multilingual'),
            'instructions' => __('Permitir traducir este campo a otros idiomas', 'flavor-multilingual'),
            'name'         => 'flavor_translatable',
            'type'         => 'true_false',
            'ui'           => 1,
            'default_value' => 1,
        ));
    }

    /**
     * Añade metabox para traducciones de ACF
     */
    public function add_acf_translation_metabox() {
        // Obtener post types con campos ACF
        $post_types = $this->get_post_types_with_acf();

        foreach ($post_types as $post_type) {
            add_meta_box(
                'flavor-ml-acf-translations',
                __('🌐 Traducciones de Campos Personalizados', 'flavor-multilingual'),
                array($this, 'render_acf_metabox'),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Obtiene post types que tienen campos ACF
     *
     * @return array
     */
    private function get_post_types_with_acf() {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }

        $field_groups = acf_get_field_groups();
        $post_types = array();

        foreach ($field_groups as $group) {
            $locations = $group['location'] ?? array();

            foreach ($locations as $location_group) {
                foreach ($location_group as $rule) {
                    if ($rule['param'] === 'post_type' && $rule['operator'] === '==') {
                        $post_types[] = $rule['value'];
                    }
                }
            }
        }

        return array_unique($post_types);
    }

    /**
     * Renderiza el metabox de traducciones ACF
     *
     * @param WP_Post $post Post actual
     */
    public function render_acf_metabox($post) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        // Obtener campos ACF traducibles del post
        $translatable_fields = $this->get_translatable_fields_for_post($post->ID);

        if (empty($translatable_fields)) {
            echo '<p>' . __('No hay campos personalizados traducibles en este contenido.', 'flavor-multilingual') . '</p>';
            return;
        }

        wp_nonce_field('flavor_ml_acf', 'flavor_ml_acf_nonce');
        ?>
        <div class="flavor-ml-acf-translations">
            <p class="description">
                <?php _e('Traduce los campos personalizados de ACF a otros idiomas.', 'flavor-multilingual'); ?>
            </p>

            <div class="flavor-ml-acf-tabs">
                <ul class="flavor-ml-acf-lang-tabs">
                    <?php foreach ($languages as $code => $lang) : ?>
                        <?php if ($code === $default_lang) continue; ?>
                        <li>
                            <a href="#flavor-ml-acf-<?php echo esc_attr($code); ?>"
                               class="<?php echo $code === array_keys($languages)[1] ? 'active' : ''; ?>">
                                <?php if ($lang['flag']) : ?>
                                    <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                         alt="" width="16" height="11">
                                <?php endif; ?>
                                <?php echo esc_html($lang['native_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php foreach ($languages as $code => $lang) : ?>
                    <?php if ($code === $default_lang) continue; ?>
                    <div id="flavor-ml-acf-<?php echo esc_attr($code); ?>"
                         class="flavor-ml-acf-tab-content"
                         data-lang="<?php echo esc_attr($code); ?>"
                         style="<?php echo $code !== array_keys($languages)[1] ? 'display:none;' : ''; ?>">

                        <table class="form-table flavor-ml-acf-fields">
                            <?php foreach ($translatable_fields as $field) : ?>
                                <?php $this->render_field_translation_row($field, $code, $post->ID); ?>
                            <?php endforeach; ?>
                        </table>

                        <p class="submit">
                            <button type="button" class="button button-primary flavor-ml-save-acf-trans"
                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                    data-lang="<?php echo esc_attr($code); ?>">
                                <?php _e('Guardar traducciones', 'flavor-multilingual'); ?>
                            </button>
                            <button type="button" class="button flavor-ml-translate-acf-ai"
                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                    data-lang="<?php echo esc_attr($code); ?>">
                                <span class="dashicons dashicons-translation" style="margin-top: 3px;"></span>
                                <?php _e('Traducir con IA', 'flavor-multilingual'); ?>
                            </button>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .flavor-ml-acf-translations { padding: 10px 0; }
            .flavor-ml-acf-lang-tabs { display: flex; gap: 5px; margin: 0 0 15px; padding: 0; list-style: none; border-bottom: 1px solid #ccc; }
            .flavor-ml-acf-lang-tabs li a { display: flex; align-items: center; gap: 6px; padding: 8px 15px; text-decoration: none; color: #2271b1; border: 1px solid transparent; border-bottom: none; margin-bottom: -1px; background: #f0f0f1; border-radius: 4px 4px 0 0; }
            .flavor-ml-acf-lang-tabs li a.active { background: #fff; border-color: #ccc; color: #1d2327; }
            .flavor-ml-acf-lang-tabs li a:hover { background: #fff; }
            .flavor-ml-acf-fields th { width: 200px; font-weight: 500; }
            .flavor-ml-acf-fields .original-value { background: #f9f9f9; padding: 8px; border-radius: 4px; margin-bottom: 8px; font-size: 13px; color: #666; }
            .flavor-ml-acf-fields .original-label { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 4px; }
            .flavor-ml-acf-fields textarea, .flavor-ml-acf-fields input[type="text"] { width: 100%; }
            .flavor-ml-acf-fields textarea { min-height: 80px; }
        </style>

        <script>
        jQuery(function($) {
            // Tabs
            $('.flavor-ml-acf-lang-tabs a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $('.flavor-ml-acf-lang-tabs a').removeClass('active');
                $(this).addClass('active');
                $('.flavor-ml-acf-tab-content').hide();
                $(target).show();
            });

            // Guardar traducciones
            $('.flavor-ml-save-acf-trans').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                var lang = $btn.data('lang');
                var $container = $btn.closest('.flavor-ml-acf-tab-content');
                var fields = {};

                $container.find('[data-field-key]').each(function() {
                    var key = $(this).data('field-key');
                    var value = $(this).val();
                    fields[key] = value;
                });

                $btn.prop('disabled', true).text('<?php _e('Guardando...', 'flavor-multilingual'); ?>');

                $.post(ajaxurl, {
                    action: 'flavor_ml_save_acf_translation',
                    nonce: $('#flavor_ml_acf_nonce').val(),
                    post_id: postId,
                    lang: lang,
                    fields: fields
                }, function(response) {
                    $btn.prop('disabled', false).text('<?php _e('Guardar traducciones', 'flavor-multilingual'); ?>');
                    if (response.success) {
                        alert('<?php _e('Traducciones guardadas', 'flavor-multilingual'); ?>');
                    } else {
                        alert(response.data || '<?php _e('Error al guardar', 'flavor-multilingual'); ?>');
                    }
                });
            });

            // Traducir con IA
            $('.flavor-ml-translate-acf-ai').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                var lang = $btn.data('lang');

                $btn.prop('disabled', true);
                $btn.find('.dashicons').removeClass('dashicons-translation').addClass('dashicons-update spin');

                $.post(ajaxurl, {
                    action: 'flavor_ml_translate_acf',
                    nonce: $('#flavor_ml_acf_nonce').val(),
                    post_id: postId,
                    lang: lang
                }, function(response) {
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-translation');

                    if (response.success && response.data.translations) {
                        // Rellenar campos con traducciones
                        var $container = $btn.closest('.flavor-ml-acf-tab-content');
                        $.each(response.data.translations, function(key, value) {
                            $container.find('[data-field-key="' + key + '"]').val(value);
                        });
                    } else {
                        alert(response.data || '<?php _e('Error al traducir', 'flavor-multilingual'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza una fila de traducción para un campo
     *
     * @param array  $field   Campo ACF
     * @param string $lang    Código de idioma
     * @param int    $post_id ID del post
     */
    private function render_field_translation_row($field, $lang, $post_id) {
        $field_key = $field['key'];
        $field_name = $field['label'];
        $field_type = $field['type'];
        $original_value = get_field($field['name'], $post_id);

        // Obtener traducción existente
        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('acf', $post_id, $lang, $field_key);

        // Preparar valor original para mostrar
        $display_value = $original_value;
        if (is_array($original_value)) {
            $display_value = wp_json_encode($original_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        ?>
        <tr>
            <th>
                <label for="acf-trans-<?php echo esc_attr($field_key); ?>-<?php echo esc_attr($lang); ?>">
                    <?php echo esc_html($field_name); ?>
                </label>
                <br><small style="color: #999;"><?php echo esc_html($field_type); ?></small>
            </th>
            <td>
                <div class="original-value">
                    <div class="original-label"><?php _e('Original:', 'flavor-multilingual'); ?></div>
                    <?php if (is_array($original_value)) : ?>
                        <code><?php echo esc_html(mb_substr($display_value, 0, 200)); ?>...</code>
                    <?php else : ?>
                        <?php echo esc_html(mb_substr($display_value, 0, 300)); ?>
                    <?php endif; ?>
                </div>

                <?php if ($field_type === 'wysiwyg') : ?>
                    <textarea id="acf-trans-<?php echo esc_attr($field_key); ?>-<?php echo esc_attr($lang); ?>"
                              data-field-key="<?php echo esc_attr($field_key); ?>"
                              rows="5"><?php echo esc_textarea($translation ?? ''); ?></textarea>
                <?php elseif ($field_type === 'textarea') : ?>
                    <textarea id="acf-trans-<?php echo esc_attr($field_key); ?>-<?php echo esc_attr($lang); ?>"
                              data-field-key="<?php echo esc_attr($field_key); ?>"
                              rows="3"><?php echo esc_textarea($translation ?? ''); ?></textarea>
                <?php else : ?>
                    <input type="text"
                           id="acf-trans-<?php echo esc_attr($field_key); ?>-<?php echo esc_attr($lang); ?>"
                           data-field-key="<?php echo esc_attr($field_key); ?>"
                           value="<?php echo esc_attr($translation ?? ''); ?>"
                           class="regular-text">
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Obtiene los campos traducibles de un post
     *
     * @param int $post_id ID del post
     * @return array
     */
    public function get_translatable_fields_for_post($post_id) {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return array();
        }

        $post_type = get_post_type($post_id);
        $field_groups = acf_get_field_groups(array('post_type' => $post_type));
        $translatable_fields = array();

        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);

            if (!$fields) {
                continue;
            }

            foreach ($fields as $field) {
                if ($this->is_field_translatable($field)) {
                    $translatable_fields[] = $field;
                }

                // Manejar campos anidados (group, repeater)
                if (in_array($field['type'], array('group', 'repeater', 'flexible_content'))) {
                    $nested = $this->get_nested_translatable_fields($field);
                    $translatable_fields = array_merge($translatable_fields, $nested);
                }
            }
        }

        return $translatable_fields;
    }

    /**
     * Obtiene campos traducibles anidados
     *
     * @param array $parent_field Campo padre
     * @return array
     */
    private function get_nested_translatable_fields($parent_field) {
        $nested_fields = array();
        $sub_fields = $parent_field['sub_fields'] ?? array();

        foreach ($sub_fields as $field) {
            if ($this->is_field_translatable($field)) {
                $field['parent_label'] = $parent_field['label'];
                $nested_fields[] = $field;
            }
        }

        return $nested_fields;
    }

    /**
     * AJAX: Traducir campos ACF con IA
     */
    public function ajax_translate_acf_fields() {
        check_ajax_referer('flavor_ml_acf', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $to_lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$to_lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        // Obtener campos traducibles
        $fields = $this->get_translatable_fields_for_post($post_id);

        if (empty($fields)) {
            wp_send_json_error(__('No hay campos para traducir', 'flavor-multilingual'));
        }

        // Preparar textos para traducción
        $texts_to_translate = array();
        foreach ($fields as $field) {
            $value = get_field($field['name'], $post_id);

            // Solo traducir valores de texto
            if (is_string($value) && !empty($value)) {
                $texts_to_translate[$field['key']] = $value;
            }
        }

        if (empty($texts_to_translate)) {
            wp_send_json_error(__('No hay textos para traducir', 'flavor-multilingual'));
        }

        // Traducir con IA
        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $translations = array();

        foreach ($texts_to_translate as $key => $text) {
            $result = $translator->translate_text($text, $from_lang, $to_lang, 'Campo personalizado ACF');

            if (!is_wp_error($result)) {
                $translations[$key] = $result;
            }
        }

        wp_send_json_success(array(
            'message'      => sprintf(__('Traducidos %d campos', 'flavor-multilingual'), count($translations)),
            'translations' => $translations,
        ));
    }

    /**
     * AJAX: Guardar traducción de campos ACF
     */
    public function ajax_save_acf_translation() {
        check_ajax_referer('flavor_ml_acf', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();

        if (!$post_id || !$lang || empty($fields)) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $saved_count = 0;

        foreach ($fields as $field_key => $value) {
            $field_key = sanitize_key($field_key);
            $value = wp_kses_post($value);

            if (!empty($value)) {
                $result = $storage->save_translation('acf', $post_id, $lang, $field_key, $value, array(
                    'status' => 'published',
                    'auto'   => false,
                ));

                if ($result) {
                    $saved_count++;
                }
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Guardados %d campos', 'flavor-multilingual'), $saved_count),
            'count'   => $saved_count,
        ));
    }

    /**
     * AJAX: Obtener traducciones de campos ACF
     */
    public function ajax_get_acf_translations() {
        check_ajax_referer('flavor_ml_acf', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('acf', $post_id);

        $lang_translations = $translations[$lang] ?? array();

        wp_send_json_success(array(
            'translations' => $lang_translations,
        ));
    }

    /**
     * Añade campos ACF a la traducción de posts
     *
     * @param array $fields    Campos a traducir
     * @param int   $post_id   ID del post
     * @return array
     */
    public function add_acf_fields_to_translation($fields, $post_id) {
        $acf_fields = $this->get_translatable_fields_for_post($post_id);

        foreach ($acf_fields as $field) {
            $value = get_field($field['name'], $post_id);

            if (is_string($value) && !empty($value)) {
                $fields['acf_' . $field['key']] = array(
                    'value'   => $value,
                    'context' => sprintf('Campo ACF: %s', $field['label']),
                    'type'    => 'acf',
                    'key'     => $field['key'],
                );
            }
        }

        return $fields;
    }

    /**
     * Registra las claves meta de ACF como traducibles
     *
     * @param array $meta_keys Claves meta
     * @param int   $post_id   ID del post
     * @return array
     */
    public function register_acf_meta_keys($meta_keys, $post_id) {
        $acf_fields = $this->get_translatable_fields_for_post($post_id);

        foreach ($acf_fields as $field) {
            $meta_keys[] = $field['name'];
            $meta_keys[] = '_' . $field['name']; // ACF usa prefijo _ para internal
        }

        return array_unique($meta_keys);
    }

    /**
     * Traduce todos los campos ACF de un post
     *
     * @param int    $post_id ID del post
     * @param string $to_lang Idioma destino
     * @return array|WP_Error Traducciones o error
     */
    public function translate_all_fields($post_id, $to_lang) {
        $fields = $this->get_translatable_fields_for_post($post_id);

        if (empty($fields)) {
            return array();
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();
        $translations = array();

        foreach ($fields as $field) {
            $value = get_field($field['name'], $post_id);

            if (!is_string($value) || empty($value)) {
                continue;
            }

            $result = $translator->translate_text(
                $value,
                $from_lang,
                $to_lang,
                sprintf('Campo personalizado: %s', $field['label'])
            );

            if (!is_wp_error($result)) {
                $translations[$field['key']] = $result;

                // Guardar traducción
                $storage->save_translation('acf', $post_id, $to_lang, $field['key'], $result, array(
                    'status' => 'draft',
                    'auto'   => true,
                ));
            }
        }

        return $translations;
    }
}

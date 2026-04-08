<?php
/**
 * Integración con Media Library
 *
 * Permite traducir títulos, alt text, descripciones y captions de archivos multimedia.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_Media_Integration {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_Media_Integration|null
     */
    private static $instance = null;

    /**
     * Campos traducibles de medios
     *
     * @var array
     */
    private $translatable_fields = array(
        'post_title'   => 'title',      // Título
        'post_content' => 'description', // Descripción
        'post_excerpt' => 'caption',     // Leyenda/Caption
        '_wp_attachment_image_alt' => 'alt', // Texto alternativo
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_Media_Integration
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
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Filtrar datos de medios en el frontend
        add_filter('wp_get_attachment_image_attributes', array($this, 'filter_image_attributes'), 10, 3);
        add_filter('wp_get_attachment_caption', array($this, 'filter_caption'), 10, 2);
        add_filter('the_title', array($this, 'filter_attachment_title'), 10, 2);
        add_filter('get_the_excerpt', array($this, 'filter_attachment_excerpt'), 10, 2);

        // Admin - Campos de traducción en modal de medios
        add_filter('attachment_fields_to_edit', array($this, 'add_translation_fields'), 10, 2);
        add_filter('attachment_fields_to_save', array($this, 'save_translation_fields'), 10, 2);

        // Admin - Columna en biblioteca de medios
        add_filter('manage_media_columns', array($this, 'add_translation_column'));
        add_action('manage_media_custom_column', array($this, 'render_translation_column'), 10, 2);

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_translate_media', array($this, 'ajax_translate_media'));
        add_action('wp_ajax_flavor_ml_bulk_translate_media', array($this, 'ajax_bulk_translate_media'));

        // API REST
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));

        // Enqueue scripts en media library
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    // ================================================================
    // FILTROS DE FRONTEND
    // ================================================================

    /**
     * Filtra atributos de imagen (alt, title)
     *
     * @param array   $attr       Atributos
     * @param WP_Post $attachment Attachment
     * @param mixed   $size       Tamaño
     * @return array
     */
    public function filter_image_attributes($attr, $attachment, $size) {
        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $attr;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Traducir alt
        $translated_alt = $storage->get_translation('attachment', $attachment->ID, $current_lang, 'alt');
        if ($translated_alt) {
            $attr['alt'] = $translated_alt;
        }

        // Traducir title si existe
        if (isset($attr['title'])) {
            $translated_title = $storage->get_translation('attachment', $attachment->ID, $current_lang, 'title');
            if ($translated_title) {
                $attr['title'] = $translated_title;
            }
        }

        return $attr;
    }

    /**
     * Filtra el caption de una imagen
     *
     * @param string $caption       Caption original
     * @param int    $attachment_id ID del attachment
     * @return string
     */
    public function filter_caption($caption, $attachment_id) {
        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $caption;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $translated = $storage->get_translation('attachment', $attachment_id, $current_lang, 'caption');

        return $translated ?: $caption;
    }

    /**
     * Filtra el título del attachment
     *
     * @param string $title   Título
     * @param int    $post_id ID del post
     * @return string
     */
    public function filter_attachment_title($title, $post_id) {
        if (get_post_type($post_id) !== 'attachment') {
            return $title;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $title;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $translated = $storage->get_translation('attachment', $post_id, $current_lang, 'title');

        return $translated ?: $title;
    }

    /**
     * Filtra el excerpt/descripción del attachment
     *
     * @param string  $excerpt Excerpt
     * @param WP_Post $post    Post
     * @return string
     */
    public function filter_attachment_excerpt($excerpt, $post) {
        if (!$post || $post->post_type !== 'attachment') {
            return $excerpt;
        }

        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $excerpt;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $translated = $storage->get_translation('attachment', $post->ID, $current_lang, 'description');

        return $translated ?: $excerpt;
    }

    // ================================================================
    // ADMIN - CAMPOS EN MODAL DE MEDIOS
    // ================================================================

    /**
     * Añade campos de traducción al modal de medios
     *
     * @param array   $form_fields Campos del formulario
     * @param WP_Post $post        Attachment
     * @return array
     */
    public function add_translation_fields($form_fields, $post) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Sección de traducciones
        $form_fields['flavor_ml_header'] = array(
            'label' => '',
            'input' => 'html',
            'html'  => '<div class="flavor-ml-media-header">
                <h4 style="margin: 15px 0 10px; padding-top: 15px; border-top: 1px solid #ddd;">
                    🌐 ' . __('Traducciones', 'flavor-multilingual') . '
                </h4>
                <button type="button" class="button button-small flavor-ml-translate-all-media" data-attachment-id="' . esc_attr($post->ID) . '">
                    <span class="dashicons dashicons-translation" style="font-size: 14px; line-height: 1.8;"></span>
                    ' . __('Traducir con IA', 'flavor-multilingual') . '
                </button>
            </div>',
        );

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $flag_html = $lang['flag']
                ? '<img src="' . esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']) . '" width="16" height="11" style="margin-right: 5px;">'
                : '';

            // Título traducido
            $translated_title = $storage->get_translation('attachment', $post->ID, $code, 'title') ?? '';
            $form_fields["flavor_ml_title_{$code}"] = array(
                'label' => $flag_html . sprintf(__('Título (%s)', 'flavor-multilingual'), $lang['native_name']),
                'input' => 'text',
                'value' => $translated_title,
                'helps' => sprintf(__('Título en %s', 'flavor-multilingual'), $lang['name']),
            );

            // Alt traducido
            $translated_alt = $storage->get_translation('attachment', $post->ID, $code, 'alt') ?? '';
            $form_fields["flavor_ml_alt_{$code}"] = array(
                'label' => $flag_html . sprintf(__('Alt (%s)', 'flavor-multilingual'), $lang['native_name']),
                'input' => 'text',
                'value' => $translated_alt,
                'helps' => sprintf(__('Texto alternativo en %s', 'flavor-multilingual'), $lang['name']),
            );

            // Caption traducido
            $translated_caption = $storage->get_translation('attachment', $post->ID, $code, 'caption') ?? '';
            $form_fields["flavor_ml_caption_{$code}"] = array(
                'label' => $flag_html . sprintf(__('Leyenda (%s)', 'flavor-multilingual'), $lang['native_name']),
                'input' => 'textarea',
                'value' => $translated_caption,
                'helps' => sprintf(__('Leyenda en %s', 'flavor-multilingual'), $lang['name']),
            );

            // Descripción traducida
            $translated_desc = $storage->get_translation('attachment', $post->ID, $code, 'description') ?? '';
            $form_fields["flavor_ml_desc_{$code}"] = array(
                'label' => $flag_html . sprintf(__('Descripción (%s)', 'flavor-multilingual'), $lang['native_name']),
                'input' => 'textarea',
                'value' => $translated_desc,
                'helps' => sprintf(__('Descripción en %s', 'flavor-multilingual'), $lang['name']),
            );
        }

        return $form_fields;
    }

    /**
     * Guarda los campos de traducción del modal de medios
     *
     * @param array $post       Datos del post
     * @param array $attachment Datos del attachment
     * @return array
     */
    public function save_translation_fields($post, $attachment) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            // Guardar título
            $title_key = "flavor_ml_title_{$code}";
            if (isset($attachment[$title_key])) {
                $value = sanitize_text_field($attachment[$title_key]);
                if (!empty($value)) {
                    $storage->save_translation('attachment', $post['ID'], $code, 'title', $value);
                }
            }

            // Guardar alt
            $alt_key = "flavor_ml_alt_{$code}";
            if (isset($attachment[$alt_key])) {
                $value = sanitize_text_field($attachment[$alt_key]);
                if (!empty($value)) {
                    $storage->save_translation('attachment', $post['ID'], $code, 'alt', $value);
                }
            }

            // Guardar caption
            $caption_key = "flavor_ml_caption_{$code}";
            if (isset($attachment[$caption_key])) {
                $value = sanitize_textarea_field($attachment[$caption_key]);
                if (!empty($value)) {
                    $storage->save_translation('attachment', $post['ID'], $code, 'caption', $value);
                }
            }

            // Guardar descripción
            $desc_key = "flavor_ml_desc_{$code}";
            if (isset($attachment[$desc_key])) {
                $value = sanitize_textarea_field($attachment[$desc_key]);
                if (!empty($value)) {
                    $storage->save_translation('attachment', $post['ID'], $code, 'description', $value);
                }
            }
        }

        return $post;
    }

    // ================================================================
    // ADMIN - COLUMNA EN BIBLIOTECA
    // ================================================================

    /**
     * Añade columna de traducciones a la biblioteca de medios
     *
     * @param array $columns Columnas
     * @return array
     */
    public function add_translation_column($columns) {
        $columns['flavor_translations'] = __('🌐 Traducciones', 'flavor-multilingual');
        return $columns;
    }

    /**
     * Renderiza la columna de traducciones
     *
     * @param string $column_name Nombre de columna
     * @param int    $post_id     ID del post
     */
    public function render_translation_column($column_name, $post_id) {
        if ($column_name !== 'flavor_translations') {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $output = '<div class="flavor-ml-media-status">';

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            // Verificar si tiene al menos el alt traducido
            $has_alt = $storage->get_translation('attachment', $post_id, $code, 'alt');
            $has_title = $storage->get_translation('attachment', $post_id, $code, 'title');

            $status = ($has_alt && $has_title) ? 'complete' : ($has_alt || $has_title ? 'partial' : 'none');
            $icon = $status === 'complete' ? '✅' : ($status === 'partial' ? '⚠️' : '❌');
            $color = $status === 'complete' ? '#46b450' : ($status === 'partial' ? '#f0ad4e' : '#ccc');

            if ($lang['flag']) {
                $output .= sprintf(
                    '<span title="%s" style="margin-right: 3px;">
                        <img src="%s" width="14" height="10" style="opacity: %s;">
                    </span>',
                    esc_attr($lang['name']),
                    esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']),
                    $status === 'none' ? '0.3' : '1'
                );
            }
        }

        $output .= '</div>';

        echo $output;
    }

    // ================================================================
    // AJAX HANDLERS
    // ================================================================

    /**
     * AJAX: Traducir un archivo multimedia
     */
    public function ajax_translate_media() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        $to_lang = isset($_POST['lang']) ? sanitize_key($_POST['lang']) : '';

        if (!$attachment_id) {
            wp_send_json_error(__('ID de archivo no válido', 'flavor-multilingual'));
        }

        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            wp_send_json_error(__('Archivo no encontrado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();

        // Si no se especifica idioma, traducir a todos
        $languages = $to_lang ? array($to_lang => true) : $core->get_active_languages();
        $results = array();

        foreach ($languages as $code => $lang) {
            if ($code === $from_lang) {
                continue;
            }

            $translations = array();

            // Traducir título
            if (!empty($attachment->post_title)) {
                $result = $translator->translate_text($attachment->post_title, $from_lang, $code, 'Título de imagen');
                if (!is_wp_error($result)) {
                    $translations['title'] = $result;
                    $storage->save_translation('attachment', $attachment_id, $code, 'title', $result, array('auto' => true));
                }
            }

            // Traducir alt
            $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (!empty($alt)) {
                $result = $translator->translate_text($alt, $from_lang, $code, 'Texto alternativo de imagen para accesibilidad');
                if (!is_wp_error($result)) {
                    $translations['alt'] = $result;
                    $storage->save_translation('attachment', $attachment_id, $code, 'alt', $result, array('auto' => true));
                }
            }

            // Traducir caption
            if (!empty($attachment->post_excerpt)) {
                $result = $translator->translate_text($attachment->post_excerpt, $from_lang, $code, 'Leyenda de imagen');
                if (!is_wp_error($result)) {
                    $translations['caption'] = $result;
                    $storage->save_translation('attachment', $attachment_id, $code, 'caption', $result, array('auto' => true));
                }
            }

            // Traducir descripción
            if (!empty($attachment->post_content)) {
                $result = $translator->translate_text($attachment->post_content, $from_lang, $code, 'Descripción de imagen');
                if (!is_wp_error($result)) {
                    $translations['description'] = $result;
                    $storage->save_translation('attachment', $attachment_id, $code, 'description', $result, array('auto' => true));
                }
            }

            $results[$code] = $translations;
        }

        wp_send_json_success(array(
            'message'      => __('Archivo traducido', 'flavor-multilingual'),
            'translations' => $results,
        ));
    }

    /**
     * AJAX: Traducción masiva de archivos multimedia
     */
    public function ajax_bulk_translate_media() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $attachment_ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : array();
        $to_lang = isset($_POST['lang']) ? sanitize_key($_POST['lang']) : '';

        if (empty($attachment_ids)) {
            wp_send_json_error(__('No hay archivos seleccionados', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();

        $languages = $to_lang ? array($to_lang => true) : $core->get_active_languages();
        $translated_count = 0;

        foreach ($attachment_ids as $attachment_id) {
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                continue;
            }

            foreach ($languages as $code => $lang) {
                if ($code === $from_lang) {
                    continue;
                }

                // Verificar si ya está traducido
                $existing = $storage->get_translation('attachment', $attachment_id, $code, 'alt');
                if ($existing) {
                    continue;
                }

                // Traducir alt (el más importante para SEO/accesibilidad)
                $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                if (!empty($alt)) {
                    $result = $translator->translate_text($alt, $from_lang, $code, 'Texto alternativo de imagen');
                    if (!is_wp_error($result)) {
                        $storage->save_translation('attachment', $attachment_id, $code, 'alt', $result, array('auto' => true));
                    }
                }

                // Traducir título
                if (!empty($attachment->post_title)) {
                    $result = $translator->translate_text($attachment->post_title, $from_lang, $code, 'Título de imagen');
                    if (!is_wp_error($result)) {
                        $storage->save_translation('attachment', $attachment_id, $code, 'title', $result, array('auto' => true));
                    }
                }

                $translated_count++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Traducidos %d archivos', 'flavor-multilingual'), $translated_count),
            'count'   => $translated_count,
        ));
    }

    // ================================================================
    // API REST
    // ================================================================

    /**
     * Registra endpoints de API
     */
    public function register_rest_endpoints() {
        register_rest_route('flavor-multilingual/v1', '/media/(?P<id>\d+)/translations', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'api_get_translations'),
                'permission_callback' => array($this, 'can_read_media_translations'),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_save_translations'),
                'permission_callback' => function() {
                    return current_user_can('upload_files');
                },
            ),
        ));

        register_rest_route('flavor-multilingual/v1', '/media/(?P<id>\d+)/translate/(?P<lang>[a-z]{2})', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_translate_media'),
                'permission_callback' => function() {
                    return current_user_can('upload_files');
                },
            ),
        ));
    }

    /**
     * Verifica si una traducción de media puede exponerse públicamente.
     *
     * @param WP_REST_Request $request Request actual.
     * @return bool
     */
    public function can_read_media_translations($request) {
        $attachment_id = (int) $request->get_param('id');
        $attachment = get_post($attachment_id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }

        if (current_user_can('read_post', $attachment_id) || current_user_can('upload_files')) {
            return true;
        }

        $parent_id = (int) $attachment->post_parent;
        if ($parent_id > 0) {
            $parent = get_post($parent_id);
            $parent_type = $parent ? get_post_type_object($parent->post_type) : null;

            return $parent
                && $parent->post_status === 'publish'
                && $parent_type
                && !empty($parent_type->public);
        }

        return false;
    }

    /**
     * API: Obtener traducciones de un archivo
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_translations($request) {
        $attachment_id = $request->get_param('id');
        $storage = Flavor_Translation_Storage::get_instance();

        return rest_ensure_response(
            $storage->get_all_translations('attachment', $attachment_id)
        );
    }

    /**
     * API: Guardar traducciones de un archivo
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_save_translations($request) {
        $attachment_id = $request->get_param('id');
        $lang = $request->get_param('lang');
        $fields = $request->get_param('fields');

        if (!$lang || !$fields) {
            return new WP_Error('missing_params', __('Faltan parámetros', 'flavor-multilingual'), array('status' => 400));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $saved = 0;

        foreach ($fields as $field => $value) {
            $field = sanitize_key($field);
            $value = sanitize_text_field($value);

            if (!empty($value)) {
                $storage->save_translation('attachment', $attachment_id, $lang, $field, $value);
                $saved++;
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'saved'   => $saved,
        ));
    }

    /**
     * API: Traducir archivo con IA
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_translate_media($request) {
        $attachment_id = $request->get_param('id');
        $lang = $request->get_param('lang');

        $_POST['attachment_id'] = $attachment_id;
        $_POST['lang'] = $lang;
        $_POST['nonce'] = wp_create_nonce('flavor_multilingual');

        // Reutilizar lógica AJAX
        ob_start();
        $this->ajax_translate_media();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        if ($response && $response['success']) {
            return rest_ensure_response($response['data']);
        }

        return new WP_Error('translation_failed', $response['data'] ?? __('Error', 'flavor-multilingual'), array('status' => 500));
    }

    // ================================================================
    // SCRIPTS ADMIN
    // ================================================================

    /**
     * Encola scripts en admin
     *
     * @param string $hook Hook actual
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php') {
            return;
        }

        wp_add_inline_script('media-views', $this->get_media_modal_script());
    }

    /**
     * Obtiene el script para el modal de medios
     *
     * @return string
     */
    private function get_media_modal_script() {
        return "
        jQuery(document).ready(function($) {
            // Evento para el botón de traducir todo
            $(document).on('click', '.flavor-ml-translate-all-media', function(e) {
                e.preventDefault();
                var \$btn = $(this);
                var attachmentId = \$btn.data('attachment-id');

                \$btn.prop('disabled', true).text('" . esc_js(__('Traduciendo...', 'flavor-multilingual')) . "');

                $.post(ajaxurl, {
                    action: 'flavor_ml_translate_media',
                    nonce: '" . wp_create_nonce('flavor_multilingual') . "',
                    attachment_id: attachmentId
                }, function(response) {
                    \$btn.prop('disabled', false).html('<span class=\"dashicons dashicons-translation\" style=\"font-size: 14px; line-height: 1.8;\"></span> " . esc_js(__('Traducir con IA', 'flavor-multilingual')) . "');

                    if (response.success && response.data.translations) {
                        // Actualizar campos en el modal
                        $.each(response.data.translations, function(lang, fields) {
                            $.each(fields, function(field, value) {
                                var inputName = 'attachments[' + attachmentId + '][flavor_ml_' + field + '_' + lang + ']';
                                $('input[name=\"' + inputName + '\"], textarea[name=\"' + inputName + '\"]').val(value);
                            });
                        });

                        alert('" . esc_js(__('Traducción completada', 'flavor-multilingual')) . "');
                    } else {
                        alert(response.data || '" . esc_js(__('Error al traducir', 'flavor-multilingual')) . "');
                    }
                });
            });
        });
        ";
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Obtiene las traducciones de un archivo
     *
     * @param int $attachment_id ID del attachment
     * @return array
     */
    public function get_attachment_translations($attachment_id) {
        $storage = Flavor_Translation_Storage::get_instance();
        return $storage->get_all_translations('attachment', $attachment_id);
    }

    /**
     * Traduce un archivo a todos los idiomas
     *
     * @param int $attachment_id ID del attachment
     * @return array Resultados por idioma
     */
    public function translate_attachment($attachment_id) {
        $_POST['attachment_id'] = $attachment_id;
        $_POST['nonce'] = wp_create_nonce('flavor_multilingual');

        ob_start();
        $this->ajax_translate_media();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        if ($response && $response['success']) {
            return $response['data']['translations'];
        }

        return array();
    }
}

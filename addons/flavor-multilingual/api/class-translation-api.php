<?php
/**
 * API REST para traducciones
 *
 * Endpoints para gestionar traducciones programáticamente.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_API {

    /**
     * Namespace de la API
     *
     * @var string
     */
    private $namespace = 'flavor-multilingual/v1';

    /**
     * Instancia singleton
     *
     * @var Flavor_Translation_API|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Translation_API
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
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        // Idiomas
        register_rest_route($this->namespace, '/languages', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_languages'),
                'permission_callback' => '__return_true',
            ),
        ));

        register_rest_route($this->namespace, '/languages/(?P<code>[a-z]{2})', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_language'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'code' => array(
                        'required'          => true,
                        'validate_callback' => array($this, 'validate_language_code'),
                    ),
                ),
            ),
        ));

        // Traducciones de posts
        register_rest_route($this->namespace, '/posts/(?P<id>\d+)/translations', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_post_translations'),
                'permission_callback' => array($this, 'can_read_post_translations'),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'save_post_translation'),
                'permission_callback' => array($this, 'can_edit_translations'),
                'args'                => array(
                    'id'   => array('required' => true),
                    'lang' => array('required' => true),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/posts/(?P<id>\d+)/translations/(?P<lang>[a-z]{2})', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_post_translation'),
                'permission_callback' => array($this, 'can_read_post_translations'),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_post_translation'),
                'permission_callback' => array($this, 'can_edit_translations'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_post_translation'),
                'permission_callback' => array($this, 'can_edit_translations'),
            ),
        ));

        // Traducción con IA
        register_rest_route($this->namespace, '/translate', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'translate_text'),
                'permission_callback' => array($this, 'can_edit_translations'),
                'args'                => array(
                    'text'      => array('required' => true),
                    'from_lang' => array('required' => true),
                    'to_lang'   => array('required' => true),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/translate/post/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'translate_post'),
                'permission_callback' => array($this, 'can_edit_translations'),
                'args'                => array(
                    'id'      => array('required' => true),
                    'to_lang' => array('required' => true),
                ),
            ),
        ));

        // Estadísticas
        register_rest_route($this->namespace, '/stats', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_stats'),
                'permission_callback' => array($this, 'can_view_stats'),
            ),
        ));

        // Strings
        register_rest_route($this->namespace, '/strings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_strings'),
                'permission_callback' => array($this, 'can_edit_translations'),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'save_string'),
                'permission_callback' => array($this, 'can_edit_translations'),
            ),
        ));

        // Idioma actual
        register_rest_route($this->namespace, '/current-language', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_current_language'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // --- Callbacks de idiomas ---

    /**
     * Obtiene todos los idiomas activos
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function get_languages($request) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        $data = array();
        foreach ($languages as $code => $lang) {
            $data[] = $this->prepare_language_response($code, $lang);
        }

        return rest_ensure_response($data);
    }

    /**
     * Obtiene un idioma específico
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function get_language($request) {
        $code = $request->get_param('code');
        $core = Flavor_Multilingual_Core::get_instance();
        $language = $core->get_language($code);

        if (!$language) {
            return new WP_Error(
                'language_not_found',
                __('Idioma no encontrado', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        return rest_ensure_response($this->prepare_language_response($code, $language));
    }

    /**
     * Obtiene el idioma actual
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function get_current_language($request) {
        $core = Flavor_Multilingual_Core::get_instance();
        $current = $core->get_current_language();
        $language = $core->get_language($current);

        return rest_ensure_response(array(
            'code'       => $current,
            'language'   => $language ? $this->prepare_language_response($current, $language) : null,
            'is_default' => $core->is_default_language(),
        ));
    }

    // --- Callbacks de traducciones ---

    /**
     * Obtiene traducciones de un post
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function get_post_translations($request) {
        $post_id = (int) $request->get_param('id');

        if (!get_post($post_id)) {
            return new WP_Error(
                'post_not_found',
                __('Post no encontrado', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('post', $post_id);

        return rest_ensure_response($translations);
    }

    /**
     * Obtiene traducción de un post en un idioma
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function get_post_translation($request) {
        $post_id = (int) $request->get_param('id');
        $lang = $request->get_param('lang');

        if (!get_post($post_id)) {
            return new WP_Error(
                'post_not_found',
                __('Post no encontrado', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('post', $post_id);

        $lang_translations = $translations[$lang] ?? array();

        if (empty($lang_translations)) {
            return new WP_Error(
                'translation_not_found',
                __('Traducción no encontrada', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        return rest_ensure_response(array(
            'post_id'      => $post_id,
            'language'     => $lang,
            'translations' => $lang_translations,
        ));
    }

    /**
     * Guarda traducción de un post
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function save_post_translation($request) {
        $post_id = (int) $request->get_param('id');
        $lang = sanitize_key($request->get_param('lang'));
        $fields = $request->get_param('fields');

        if (!get_post($post_id)) {
            return new WP_Error(
                'post_not_found',
                __('Post no encontrado', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        if (!$this->validate_language_code($lang)) {
            return new WP_Error(
                'invalid_language',
                __('Código de idioma no válido', 'flavor-multilingual'),
                array('status' => 400)
            );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $status = sanitize_key($request->get_param('status')) ?: 'draft';

        $saved = array();
        foreach ($fields as $field => $value) {
            $field = sanitize_key($field);
            $value = wp_kses_post($value);

            $result = $storage->save_translation('post', $post_id, $lang, $field, $value, array(
                'status' => $status,
                'auto'   => false,
            ));

            $saved[$field] = $result !== false;
        }

        return rest_ensure_response(array(
            'success' => true,
            'saved'   => $saved,
        ));
    }

    /**
     * Actualiza traducción de un post
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function update_post_translation($request) {
        return $this->save_post_translation($request);
    }

    /**
     * Elimina traducción de un post
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_post_translation($request) {
        $post_id = (int) $request->get_param('id');
        $lang = $request->get_param('lang');

        $storage = Flavor_Translation_Storage::get_instance();
        $result = $storage->delete_translations('post', $post_id, $lang);

        return rest_ensure_response(array(
            'success' => $result !== false,
            'deleted' => $result,
        ));
    }

    // --- Callbacks de traducción IA ---

    /**
     * Traduce texto con IA
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function translate_text($request) {
        $text = $request->get_param('text');
        $from_lang = sanitize_key($request->get_param('from_lang'));
        $to_lang = sanitize_key($request->get_param('to_lang'));
        $context = sanitize_text_field($request->get_param('context') ?? '');
        $is_html = (bool) $request->get_param('is_html');

        $translator = Flavor_AI_Translator::get_instance();

        if ($is_html) {
            $result = $translator->translate_html($text, $from_lang, $to_lang, $context);
        } else {
            $result = $translator->translate_text($text, $from_lang, $to_lang, $context);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'original'    => $text,
            'translation' => $result,
            'from'        => $from_lang,
            'to'          => $to_lang,
        ));
    }

    /**
     * Traduce un post completo con IA
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function translate_post($request) {
        $post_id = (int) $request->get_param('id');
        $to_lang = sanitize_key($request->get_param('to_lang'));
        $save = (bool) $request->get_param('save');

        if (!get_post($post_id)) {
            return new WP_Error(
                'post_not_found',
                __('Post no encontrado', 'flavor-multilingual'),
                array('status' => 404)
            );
        }

        $translator = Flavor_AI_Translator::get_instance();
        $translations = $translator->translate_post($post_id, $to_lang);

        if (is_wp_error($translations)) {
            return $translations;
        }

        // Guardar si se solicita
        if ($save) {
            $storage = Flavor_Translation_Storage::get_instance();
            foreach ($translations as $field => $value) {
                $storage->save_translation('post', $post_id, $to_lang, $field, $value, array(
                    'status' => 'draft',
                    'auto'   => true,
                ));
            }
        }

        return rest_ensure_response(array(
            'post_id'      => $post_id,
            'language'     => $to_lang,
            'translations' => $translations,
            'saved'        => $save,
        ));
    }

    // --- Callbacks de estadísticas ---

    /**
     * Obtiene estadísticas de traducción
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function get_stats($request) {
        $storage = Flavor_Translation_Storage::get_instance();
        $stats = $storage->get_translation_stats();

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        $response = array();
        foreach ($languages as $code => $lang) {
            $response[$code] = array(
                'language' => $lang['name'],
                'stats'    => $stats[$code] ?? array('total' => 0, 'published' => 0, 'draft' => 0),
            );
        }

        return rest_ensure_response($response);
    }

    // --- Callbacks de strings ---

    /**
     * Obtiene strings traducibles
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function get_strings($request) {
        global $wpdb;

        $lang = sanitize_key($request->get_param('lang'));
        $domain = sanitize_key($request->get_param('domain')) ?: 'flavor-chat-ia';
        $page = (int) ($request->get_param('page') ?: 1);
        $per_page = (int) ($request->get_param('per_page') ?: 50);

        $table = $wpdb->prefix . 'flavor_string_translations';
        $offset = ($page - 1) * $per_page;

        $where = array('domain = %s');
        $params = array($domain);

        if ($lang) {
            $where[] = 'language_code = %s';
            $params[] = $lang;
        }

        $where_clause = implode(' AND ', $where);
        $params[] = $per_page;
        $params[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY original_string ASC LIMIT %d OFFSET %d",
            ...$params
        ), ARRAY_A);

        return rest_ensure_response(array(
            'strings' => $results,
            'page'    => $page,
            'per_page' => $per_page,
        ));
    }

    /**
     * Guarda una traducción de string
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function save_string($request) {
        $original = $request->get_param('original');
        $lang = sanitize_key($request->get_param('lang'));
        $translation = $request->get_param('translation');
        $domain = sanitize_key($request->get_param('domain')) ?: 'flavor-chat-ia';

        if (empty($original) || empty($lang) || empty($translation)) {
            return new WP_Error(
                'missing_params',
                __('Faltan parámetros requeridos', 'flavor-multilingual'),
                array('status' => 400)
            );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $result = $storage->save_string_translation($original, $lang, $translation, $domain);

        return rest_ensure_response(array(
            'success' => $result !== false,
        ));
    }

    // --- Helpers ---

    /**
     * Prepara respuesta de idioma
     *
     * @param string $code Código
     * @param array  $lang Datos del idioma
     * @return array
     */
    private function prepare_language_response($code, $lang) {
        $core = Flavor_Multilingual_Core::get_instance();

        return array(
            'code'        => $code,
            'locale'      => $lang['locale'],
            'name'        => $lang['name'],
            'native_name' => $lang['native_name'],
            'flag'        => $lang['flag'] ? FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag'] : null,
            'is_rtl'      => $lang['is_rtl'],
            'is_default'  => $code === $core->get_default_language(),
            'is_current'  => $code === $core->get_current_language(),
        );
    }

    /**
     * Valida código de idioma
     *
     * @param string $code Código a validar
     * @return bool
     */
    public function validate_language_code($code) {
        $core = Flavor_Multilingual_Core::get_instance();
        return $core->is_valid_language($code);
    }

    /**
     * Verifica si puede editar traducciones
     *
     * @return bool
     */
    public function can_edit_translations() {
        return current_user_can('edit_posts');
    }

    /**
     * Verifica si puede leer traducciones de un post.
     *
     * Permite contenido publico publicado y, en cualquier otro caso,
     * exige capacidad de lectura sobre el post concreto.
     *
     * @param WP_REST_Request $request Request actual.
     * @return bool
     */
    public function can_read_post_translations($request) {
        $post_id = (int) $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return false;
        }

        if (current_user_can('read_post', $post_id) || current_user_can('edit_post', $post_id)) {
            return true;
        }

        $post_type_object = get_post_type_object($post->post_type);

        return $post->post_status === 'publish'
            && $post_type_object
            && !empty($post_type_object->public);
    }

    /**
     * Verifica si puede ver estadísticas
     *
     * @return bool
     */
    public function can_view_stats() {
        return current_user_can('manage_options');
    }
}

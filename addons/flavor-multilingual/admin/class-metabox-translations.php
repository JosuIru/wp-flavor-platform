<?php
/**
 * Metabox de traducciones en el editor de posts
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Multilingual_Metabox {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual_Metabox|null
     */
    private static $instance = null;

    /**
     * Post types soportados
     *
     * @var array
     */
    private $supported_post_types = array('post', 'page', 'flavor_landing');

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual_Metabox
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
        add_action('add_meta_boxes', array($this, 'register_metabox'));
        add_action('save_post', array($this, 'save_metabox'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Filtrar post types soportados
        $this->supported_post_types = apply_filters(
            'flavor_multilingual_supported_post_types',
            $this->supported_post_types
        );
    }

    /**
     * Registra el metabox
     */
    public function register_metabox() {
        foreach ($this->supported_post_types as $post_type) {
            add_meta_box(
                'flavor-multilingual-translations',
                __('Traducciones', 'flavor-multilingual'),
                array($this, 'render_metabox'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Encola assets para el metabox
     *
     * @param string $hook Hook actual
     */
    public function enqueue_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        global $post;
        if (!$post || !in_array($post->post_type, $this->supported_post_types)) {
            return;
        }

        wp_enqueue_style(
            'flavor-multilingual-metabox',
            FLAVOR_MULTILINGUAL_URL . 'admin/css/metabox.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-multilingual-metabox',
            FLAVOR_MULTILINGUAL_URL . 'admin/js/metabox.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-multilingual-metabox', 'flavorMLMetabox', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_multilingual'),
            'postId'  => $post->ID,
            'i18n'    => array(
                'translating'     => __('Traduciendo con IA...', 'flavor-multilingual'),
                'translated'      => __('Traducido', 'flavor-multilingual'),
                'error'           => __('Error al traducir', 'flavor-multilingual'),
                'confirmOverwrite' => __('Ya existe una traducción. ¿Desea sobrescribirla?', 'flavor-multilingual'),
            ),
        ));
    }

    /**
     * Renderiza el metabox
     *
     * @param WP_Post $post Post actual
     */
    public function render_metabox($post) {
        $core = Flavor_Multilingual_Core::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();

        $default_lang = $core->get_default_language();
        $languages = $core->get_active_languages();
        $existing_translations = $storage->get_all_translations('post', $post->ID);

        wp_nonce_field('flavor_multilingual_metabox', 'flavor_ml_nonce');

        ?>
        <div class="flavor-ml-metabox">
            <p class="flavor-ml-original-lang">
                <strong><?php esc_html_e('Idioma original:', 'flavor-multilingual'); ?></strong>
                <?php
                $default_info = $languages[$default_lang] ?? array('name' => $default_lang);
                echo esc_html($default_info['name']);
                ?>
            </p>

            <div class="flavor-ml-translations-list">
                <?php foreach ($languages as $code => $lang) : ?>
                    <?php
                    if ($code === $default_lang) {
                        continue;
                    }

                    $has_translation = isset($existing_translations[$code]);
                    $translation_status = $has_translation
                        ? ($existing_translations[$code]['title']['status'] ?? 'draft')
                        : 'none';
                    $is_auto = $has_translation && ($existing_translations[$code]['title']['auto'] ?? false);
                    ?>
                    <div class="flavor-ml-lang-item" data-lang="<?php echo esc_attr($code); ?>">
                        <div class="flavor-ml-lang-header">
                            <?php if ($lang['flag']) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                     alt="" width="20" height="13" class="flavor-ml-flag">
                            <?php endif; ?>
                            <span class="flavor-ml-lang-name"><?php echo esc_html($lang['name']); ?></span>

                            <span class="flavor-ml-status flavor-ml-status-<?php echo esc_attr($translation_status); ?>">
                                <?php
                                switch ($translation_status) {
                                    case 'published':
                                        esc_html_e('Publicada', 'flavor-multilingual');
                                        break;
                                    case 'draft':
                                        esc_html_e('Borrador', 'flavor-multilingual');
                                        break;
                                    default:
                                        esc_html_e('Sin traducir', 'flavor-multilingual');
                                }
                                ?>
                            </span>
                        </div>

                        <div class="flavor-ml-lang-actions">
                            <?php if ($has_translation) : ?>
                                <button type="button" class="button button-small flavor-ml-edit-translation"
                                        data-lang="<?php echo esc_attr($code); ?>">
                                    <?php esc_html_e('Editar', 'flavor-multilingual'); ?>
                                </button>
                            <?php endif; ?>

                            <button type="button" class="button button-small flavor-ml-ai-translate"
                                    data-lang="<?php echo esc_attr($code); ?>">
                                <span class="dashicons dashicons-translation"></span>
                                <?php echo $has_translation
                                    ? esc_html__('Retraducir', 'flavor-multilingual')
                                    : esc_html__('Traducir con IA', 'flavor-multilingual'); ?>
                            </button>
                        </div>

                        <?php if ($is_auto) : ?>
                            <p class="flavor-ml-auto-notice">
                                <span class="dashicons dashicons-info-outline"></span>
                                <?php esc_html_e('Traducción automática - revisar antes de publicar', 'flavor-multilingual'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flavor-ml-bulk-actions">
                <button type="button" class="button flavor-ml-translate-all" id="flavor-ml-translate-all">
                    <span class="dashicons dashicons-translation"></span>
                    <?php esc_html_e('Traducir a todos los idiomas', 'flavor-multilingual'); ?>
                </button>
            </div>
        </div>

        <!-- Modal de edición -->
        <div id="flavor-ml-edit-modal" class="flavor-ml-modal" style="display: none;">
            <div class="flavor-ml-modal-content">
                <div class="flavor-ml-modal-header">
                    <h3><?php esc_html_e('Editar traducción', 'flavor-multilingual'); ?></h3>
                    <button type="button" class="flavor-ml-modal-close">&times;</button>
                </div>
                <div class="flavor-ml-modal-body">
                    <input type="hidden" id="flavor-ml-edit-lang" value="">

                    <p>
                        <label for="flavor-ml-edit-title">
                            <strong><?php esc_html_e('Título:', 'flavor-multilingual'); ?></strong>
                        </label>
                        <input type="text" id="flavor-ml-edit-title" class="widefat">
                    </p>

                    <p>
                        <label for="flavor-ml-edit-excerpt">
                            <strong><?php esc_html_e('Extracto:', 'flavor-multilingual'); ?></strong>
                        </label>
                        <textarea id="flavor-ml-edit-excerpt" class="widefat" rows="3"></textarea>
                    </p>

                    <p>
                        <label>
                            <strong><?php esc_html_e('Estado:', 'flavor-multilingual'); ?></strong>
                        </label>
                        <select id="flavor-ml-edit-status">
                            <option value="draft"><?php esc_html_e('Borrador', 'flavor-multilingual'); ?></option>
                            <option value="published"><?php esc_html_e('Publicada', 'flavor-multilingual'); ?></option>
                        </select>
                    </p>

                    <p class="flavor-ml-edit-content-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('El contenido completo se puede editar en la pestaña de traducción completa.', 'flavor-multilingual'); ?>
                    </p>
                </div>
                <div class="flavor-ml-modal-footer">
                    <button type="button" class="button" id="flavor-ml-edit-cancel">
                        <?php esc_html_e('Cancelar', 'flavor-multilingual'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="flavor-ml-edit-save">
                        <?php esc_html_e('Guardar', 'flavor-multilingual'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda los datos del metabox
     *
     * @param int     $post_id ID del post
     * @param WP_Post $post    Objeto post
     */
    public function save_metabox($post_id, $post) {
        // Verificar nonce
        if (!isset($_POST['flavor_ml_nonce']) ||
            !wp_verify_nonce($_POST['flavor_ml_nonce'], 'flavor_multilingual_metabox')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verificar post type
        if (!in_array($post->post_type, $this->supported_post_types)) {
            return;
        }

        // Los datos de traducción se guardan via AJAX, no en el submit del form
        // Este método está preparado por si se quiere añadir funcionalidad adicional
    }

    /**
     * Obtiene las traducciones para el editor de bloques (REST API)
     *
     * @param int $post_id ID del post
     * @return array
     */
    public function get_translations_for_rest($post_id) {
        $storage = Flavor_Translation_Storage::get_instance();
        $core = Flavor_Multilingual_Core::get_instance();

        $translations = $storage->get_all_translations('post', $post_id);
        $languages = $core->get_active_languages();

        $result = array();
        foreach ($languages as $code => $lang) {
            $result[$code] = array(
                'language'     => $lang,
                'translations' => $translations[$code] ?? array(),
                'has_content'  => isset($translations[$code]),
            );
        }

        return $result;
    }
}

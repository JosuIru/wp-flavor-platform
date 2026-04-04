<?php
/**
 * Integración con Visual Builder Pro (VBP)
 *
 * Permite traducir contenido creado con el Visual Builder Pro de Flavor Platform.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_VBP_Integration {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_VBP_Integration|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_VBP_Integration
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
        // Solo inicializar si VBP está activo
        if (!$this->is_vbp_active()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Verifica si VBP está activo
     *
     * @return bool
     */
    private function is_vbp_active() {
        return class_exists('Flavor_VBP_Editor') || defined('FLAVOR_VBP_VERSION');
    }

    /**
     * Obtiene el documento VBP actual normalizado.
     *
     * @param int $post_id ID del post.
     * @return array|null
     */
    private function get_vbp_document($post_id) {
        if (class_exists('Flavor_VBP_Editor')) {
            return Flavor_VBP_Editor::get_instance()->obtener_datos_documento($post_id);
        }

        $document = get_post_meta($post_id, '_flavor_vbp_data', true);
        if (is_array($document)) {
            return $document;
        }

        $legacy = get_post_meta($post_id, '_vbp_content', true);
        if (is_array($legacy)) {
            return array(
                'version'  => 'legacy',
                'elements' => isset($legacy['blocks']) && is_array($legacy['blocks']) ? $legacy['blocks'] : $legacy,
                'settings' => array(),
            );
        }

        return null;
    }

    /**
     * Devuelve la lista de bloques/elementos traducibles desde un documento.
     *
     * @param array $document Documento VBP.
     * @return array
     */
    private function get_translatable_blocks($document) {
        if (!is_array($document)) {
            return array();
        }

        if (isset($document['elements']) && is_array($document['elements'])) {
            return $document['elements'];
        }

        if (isset($document['blocks']) && is_array($document['blocks'])) {
            return $document['blocks'];
        }

        return array();
    }

    /**
     * Reconstruye el documento tras traducir sus bloques/elementos.
     *
     * @param array $document          Documento original.
     * @param array $translated_blocks Bloques traducidos.
     * @param string $from_lang        Idioma origen.
     * @return array
     */
    private function build_translated_document($document, $translated_blocks, $from_lang) {
        $translated = is_array($document) ? $document : array();

        if (isset($translated['elements']) && is_array($translated['elements'])) {
            $translated['elements'] = $translated_blocks;
        } else {
            $translated['blocks'] = $translated_blocks;
        }

        $translated['version'] = $translated['version'] ?? '1.0';
        $translated['translated'] = true;
        $translated['from_lang'] = $from_lang;

        return $translated;
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Filtrar contenido VBP en el frontend
        add_filter('flavor_vbp_render_content', array($this, 'filter_vbp_content'), 10, 2);
        add_filter('flavor_vbp_block_content', array($this, 'filter_vbp_block'), 10, 3);

        // Añadir traducciones al editor VBP
        add_action('flavor_vbp_editor_sidebar', array($this, 'render_translation_panel'));
        add_action('flavor_vbp_editor_scripts', array($this, 'enqueue_translation_scripts'));

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_translate_vbp_page', array($this, 'ajax_translate_vbp_page'));
        add_action('wp_ajax_flavor_ml_save_vbp_translation', array($this, 'ajax_save_vbp_translation'));
        add_action('wp_ajax_flavor_ml_get_vbp_translation', array($this, 'ajax_get_vbp_translation'));
        add_action('wp_ajax_flavor_ml_translate_vbp_block', array($this, 'ajax_translate_vbp_block'));

        // API REST - Extender API de VBP
        add_action('rest_api_init', array($this, 'register_vbp_translation_endpoints'));

        // Hooks de guardado
        add_action('flavor_vbp_page_saved', array($this, 'on_vbp_page_saved'), 10, 2);

        // Selector de idioma en el editor
        add_action('flavor_vbp_editor_toolbar', array($this, 'render_language_selector'));

        // Filtrar datos del store para incluir idioma
        add_filter('flavor_vbp_store_data', array($this, 'add_language_to_store'));

        // Extender bloques traducibles
        add_filter('flavor_vbp_block_schema', array($this, 'extend_block_schema'));
    }

    // ================================================================
    // FILTROS DE RENDERIZADO
    // ================================================================

    /**
     * Filtra el contenido completo de VBP
     *
     * @param string $content Contenido HTML renderizado
     * @param int    $post_id ID del post
     * @return string
     */
    public function filter_vbp_content($content, $post_id) {
        $core = Flavor_Multilingual_Core::get_instance();

        // Si es el idioma por defecto, devolver sin cambios
        if ($core->is_default_language()) {
            return $content;
        }

        // Buscar contenido VBP traducido completo
        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Opción 1: Buscar contenido traducido completo (JSON de bloques)
        $translated_json = $storage->get_translation('vbp', $post_id, $current_lang, 'content_json');

        if ($translated_json) {
            // Renderizar el JSON traducido
            $translated_content = $this->render_translated_vbp($translated_json, $post_id);
            if ($translated_content) {
                return $translated_content;
            }
        }

        // Opción 2: Traducir bloque por bloque (fallback)
        return $this->translate_rendered_content($content, $post_id, $current_lang);
    }

    /**
     * Filtra un bloque individual de VBP
     *
     * @param array  $block   Datos del bloque
     * @param string $output  HTML del bloque
     * @param int    $post_id ID del post
     * @return string
     */
    public function filter_vbp_block($block, $output, $post_id) {
        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $output;
        }

        $current_lang = $core->get_current_language();
        $block_id = $block['id'] ?? '';

        if (!$block_id) {
            return $output;
        }

        // Buscar traducción del bloque específico
        $storage = Flavor_Translation_Storage::get_instance();
        $translated_block = $storage->get_translation('vbp_block', $post_id, $current_lang, $block_id);

        if ($translated_block) {
            $decoded = json_decode($translated_block, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                // Re-renderizar el bloque con los datos traducidos
                return $this->render_single_block($decoded);
            }
        }

        return $output;
    }

    /**
     * Renderiza contenido VBP desde JSON traducido
     *
     * @param string $json    JSON del contenido
     * @param int    $post_id ID del post
     * @return string|false
     */
    private function render_translated_vbp($json, $post_id) {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['blocks'])) {
            return false;
        }

        // Usar el renderizador de VBP si está disponible
        if (class_exists('Flavor_VBP_Renderer')) {
            $renderer = Flavor_VBP_Renderer::get_instance();
            return $renderer->render($data['blocks']);
        }

        // Fallback: renderizar manualmente
        return $this->render_blocks_array($data['blocks']);
    }

    /**
     * Traduce contenido renderizado buscando textos traducibles
     *
     * @param string $content      Contenido HTML
     * @param int    $post_id      ID del post
     * @param string $current_lang Idioma actual
     * @return string
     */
    private function translate_rendered_content($content, $post_id, $current_lang) {
        // Buscar traducciones de textos específicos dentro del HTML
        $storage = Flavor_Translation_Storage::get_instance();

        // Obtener todas las traducciones de este post
        $translations = $storage->get_all_translations('vbp_text', $post_id);
        $lang_translations = $translations[$current_lang] ?? array();

        if (empty($lang_translations)) {
            return $content;
        }

        // Reemplazar textos traducidos
        foreach ($lang_translations as $field => $data) {
            $original = $data['original'] ?? '';
            $translated = $data['value'] ?? '';

            if ($original && $translated && $original !== $translated) {
                $content = str_replace($original, $translated, $content);
            }
        }

        return $content;
    }

    /**
     * Renderiza un bloque individual
     *
     * @param array $block Datos del bloque
     * @return string
     */
    private function render_single_block($block) {
        $type = $block['type'] ?? 'text';
        $props = $block['props'] ?? array();
        $children = $block['children'] ?? array();

        // Renderizar según tipo
        switch ($type) {
            case 'section':
                $class = $props['className'] ?? '';
                $bg = isset($props['background']) ? $this->get_background_style($props['background']) : '';
                $inner = $this->render_blocks_array($children);
                return sprintf('<section class="vbp-section %s" style="%s">%s</section>', esc_attr($class), esc_attr($bg), $inner);

            case 'container':
                $inner = $this->render_blocks_array($children);
                return sprintf('<div class="vbp-container">%s</div>', $inner);

            case 'heading':
                $level = $props['level'] ?? 2;
                $text = $props['text'] ?? '';
                $align = $props['align'] ?? 'left';
                $color = $props['color'] ?? '';
                $style = $color ? "color: {$color};" : '';
                return sprintf('<h%d class="vbp-heading" style="text-align: %s; %s">%s</h%d>',
                    $level, esc_attr($align), esc_attr($style), esc_html($text), $level);

            case 'text':
            case 'paragraph':
                $content = $props['content'] ?? $props['text'] ?? '';
                $align = $props['align'] ?? 'left';
                $color = $props['color'] ?? '';
                $style = $color ? "color: {$color};" : '';
                return sprintf('<p class="vbp-text" style="text-align: %s; %s">%s</p>',
                    esc_attr($align), esc_attr($style), wp_kses_post($content));

            case 'button':
                $text = $props['text'] ?? '';
                $url = $props['url'] ?? '#';
                $style_class = $props['style'] ?? 'primary';
                $size = $props['size'] ?? 'medium';
                return sprintf('<a href="%s" class="vbp-button vbp-button-%s vbp-button-%s">%s</a>',
                    esc_url($url), esc_attr($style_class), esc_attr($size), esc_html($text));

            case 'image':
                $src = $props['src'] ?? '';
                $alt = $props['alt'] ?? '';
                return sprintf('<img src="%s" alt="%s" class="vbp-image">', esc_url($src), esc_attr($alt));

            case 'columns':
                $cols = $props['columns'] ?? 2;
                $gap = $props['gap'] ?? '20px';
                $inner = $this->render_blocks_array($children);
                return sprintf('<div class="vbp-columns vbp-columns-%d" style="gap: %s;">%s</div>',
                    $cols, esc_attr($gap), $inner);

            case 'column':
                $inner = $this->render_blocks_array($children);
                return sprintf('<div class="vbp-column">%s</div>', $inner);

            default:
                // Para otros tipos, intentar renderizar hijos
                if (!empty($children)) {
                    return $this->render_blocks_array($children);
                }
                return '';
        }
    }

    /**
     * Renderiza un array de bloques
     *
     * @param array $blocks Array de bloques
     * @return string
     */
    private function render_blocks_array($blocks) {
        $output = '';

        foreach ($blocks as $block) {
            $output .= $this->render_single_block($block);
        }

        return $output;
    }

    /**
     * Obtiene estilo CSS de fondo
     *
     * @param array $bg Configuración de fondo
     * @return string
     */
    private function get_background_style($bg) {
        if (!is_array($bg)) {
            return '';
        }

        $type = $bg['type'] ?? 'color';

        switch ($type) {
            case 'color':
                return 'background-color: ' . ($bg['color'] ?? '#fff') . ';';
            case 'gradient':
                $from = $bg['from'] ?? '#fff';
                $to = $bg['to'] ?? '#eee';
                $direction = $bg['direction'] ?? '180deg';
                return "background: linear-gradient({$direction}, {$from}, {$to});";
            case 'image':
                $url = $bg['url'] ?? '';
                return "background-image: url('{$url}'); background-size: cover;";
            default:
                return '';
        }
    }

    // ================================================================
    // EDITOR VBP - UI
    // ================================================================

    /**
     * Renderiza el panel de traducciones en el sidebar del editor
     */
    public function render_translation_panel() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        ?>
        <div class="vbp-panel vbp-translations-panel" id="vbp-translations-panel">
            <div class="vbp-panel-header">
                <h3>🌐 <?php _e('Traducciones', 'flavor-multilingual'); ?></h3>
            </div>
            <div class="vbp-panel-content">
                <p class="vbp-panel-description">
                    <?php _e('Traduce esta página a otros idiomas.', 'flavor-multilingual'); ?>
                </p>

                <div class="vbp-translations-list">
                    <?php foreach ($languages as $code => $lang) : ?>
                        <?php if ($code === $default_lang) continue; ?>
                        <div class="vbp-translation-item" data-lang="<?php echo esc_attr($code); ?>">
                            <div class="vbp-translation-info">
                                <?php if ($lang['flag']) : ?>
                                    <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                         alt="" width="20" height="14">
                                <?php endif; ?>
                                <span class="vbp-translation-name"><?php echo esc_html($lang['native_name']); ?></span>
                                <span class="vbp-translation-status" data-status="unknown">
                                    <span class="dashicons dashicons-minus"></span>
                                </span>
                            </div>
                            <div class="vbp-translation-actions">
                                <button type="button" class="vbp-btn vbp-btn-sm vbp-btn-translate-ai"
                                        data-lang="<?php echo esc_attr($code); ?>"
                                        title="<?php _e('Traducir con IA', 'flavor-multilingual'); ?>">
                                    <span class="dashicons dashicons-translation"></span>
                                </button>
                                <button type="button" class="vbp-btn vbp-btn-sm vbp-btn-edit-translation"
                                        data-lang="<?php echo esc_attr($code); ?>"
                                        title="<?php _e('Editar traducción', 'flavor-multilingual'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="vbp-translations-actions">
                    <button type="button" class="vbp-btn vbp-btn-primary vbp-btn-translate-all">
                        <span class="dashicons dashicons-translation"></span>
                        <?php _e('Traducir a todos los idiomas', 'flavor-multilingual'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el selector de idioma en la toolbar del editor
     */
    public function render_language_selector() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $editing_lang = isset($_GET['edit_lang']) ? sanitize_key($_GET['edit_lang']) : $default_lang;
        ?>
        <div class="vbp-toolbar-item vbp-language-selector">
            <label><?php _e('Editando:', 'flavor-multilingual'); ?></label>
            <select id="vbp-editing-language">
                <?php foreach ($languages as $code => $lang) : ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($editing_lang, $code); ?>>
                        <?php echo esc_html($lang['native_name']); ?>
                        <?php if ($code === $default_lang) echo ' (' . __('Original', 'flavor-multilingual') . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Encola scripts para el editor VBP
     */
    public function enqueue_translation_scripts() {
        wp_enqueue_script(
            'flavor-ml-vbp-integration',
            FLAVOR_MULTILINGUAL_URL . 'assets/js/vbp-integration.js',
            array('jquery', 'vbp-app'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-ml-vbp-integration', 'flavorMLVBP', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('flavor_ml_vbp'),
            'defaultLang' => Flavor_Multilingual_Core::get_instance()->get_default_language(),
            'i18n'        => array(
                'translating'        => __('Traduciendo...', 'flavor-multilingual'),
                'translated'         => __('Traducido', 'flavor-multilingual'),
                'error'              => __('Error al traducir', 'flavor-multilingual'),
                'saving'             => __('Guardando...', 'flavor-multilingual'),
                'saved'              => __('Guardado', 'flavor-multilingual'),
                'confirmTranslateAll' => __('¿Traducir esta página a todos los idiomas? Esto puede tardar.', 'flavor-multilingual'),
            ),
        ));

        wp_enqueue_style(
            'flavor-ml-vbp-integration',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/vbp-integration.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );
    }

    /**
     * Añade datos de idioma al store de VBP
     *
     * @param array $data Datos del store
     * @return array
     */
    public function add_language_to_store($data) {
        $core = Flavor_Multilingual_Core::get_instance();

        $data['multilingual'] = array(
            'enabled'      => true,
            'currentLang'  => $core->get_current_language(),
            'defaultLang'  => $core->get_default_language(),
            'editingLang'  => isset($_GET['edit_lang']) ? sanitize_key($_GET['edit_lang']) : $core->get_default_language(),
            'languages'    => $core->get_active_languages(),
        );

        return $data;
    }

    /**
     * Extiende el schema de bloques para incluir campos traducibles
     *
     * @param array $schema Schema del bloque
     * @return array
     */
    public function extend_block_schema($schema) {
        // Marcar campos traducibles
        $translatable_props = array('text', 'content', 'title', 'label', 'placeholder', 'alt', 'caption');

        foreach ($translatable_props as $prop) {
            if (isset($schema['props'][$prop])) {
                $schema['props'][$prop]['translatable'] = true;
            }
        }

        return $schema;
    }

    // ================================================================
    // AJAX HANDLERS
    // ================================================================

    /**
     * AJAX: Traducir página VBP completa
     */
    public function ajax_translate_vbp_page() {
        check_ajax_referer('flavor_ml_vbp', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $to_lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$to_lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        // Obtener documento VBP del post.
        $content_data = $this->get_vbp_document($post_id);
        if (empty($content_data)) {
            wp_send_json_error(__('No hay contenido VBP en esta página', 'flavor-multilingual'));
        }

        $blocks = $this->get_translatable_blocks($content_data);
        if (empty($blocks)) {
            wp_send_json_error(__('Contenido VBP inválido', 'flavor-multilingual'));
        }

        // Traducir bloques
        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translated_blocks = $this->translate_blocks_recursive($blocks, $from_lang, $to_lang);

        // Guardar traducción
        $storage = Flavor_Translation_Storage::get_instance();
        $translated_content = $this->build_translated_document($content_data, $translated_blocks, $from_lang);

        $storage->save_translation('vbp', $post_id, $to_lang, 'content_json', wp_json_encode($translated_content), array(
            'auto'   => true,
            'status' => 'draft',
        ));

        wp_send_json_success(array(
            'message' => __('Página traducida correctamente', 'flavor-multilingual'),
            'blocks'  => $translated_blocks,
        ));
    }

    /**
     * Traduce bloques recursivamente
     *
     * @param array  $blocks    Array de bloques
     * @param string $from_lang Idioma origen
     * @param string $to_lang   Idioma destino
     * @return array
     */
    private function translate_blocks_recursive($blocks, $from_lang, $to_lang) {
        $translator = Flavor_AI_Translator::get_instance();
        $translated_blocks = array();

        foreach ($blocks as $block) {
            $translated_block = $block;

            // Traducir props traducibles
            if (isset($block['props'])) {
                $translated_block['props'] = $this->translate_block_props($block['props'], $from_lang, $to_lang, $translator);
            }

            // Traducir hijos recursivamente
            if (!empty($block['children'])) {
                $translated_block['children'] = $this->translate_blocks_recursive($block['children'], $from_lang, $to_lang);
            }

            $translated_blocks[] = $translated_block;
        }

        return $translated_blocks;
    }

    /**
     * Traduce las propiedades de un bloque
     *
     * @param array              $props      Propiedades
     * @param string             $from_lang  Idioma origen
     * @param string             $to_lang    Idioma destino
     * @param Flavor_AI_Translator $translator Traductor
     * @return array
     */
    private function translate_block_props($props, $from_lang, $to_lang, $translator) {
        $translatable_keys = array('text', 'content', 'title', 'label', 'placeholder', 'alt', 'caption', 'buttonText', 'heading');

        foreach ($translatable_keys as $key) {
            if (isset($props[$key]) && is_string($props[$key]) && !empty($props[$key])) {
                // Determinar si es HTML
                $is_html = ($key === 'content' || strpos($props[$key], '<') !== false);

                if ($is_html) {
                    $result = $translator->translate_html($props[$key], $from_lang, $to_lang, 'Contenido de página web');
                } else {
                    $result = $translator->translate_text($props[$key], $from_lang, $to_lang, 'Elemento de página web');
                }

                if (!is_wp_error($result)) {
                    $props[$key] = $result;
                }
            }
        }

        // Manejar arrays de items (como listas, botones múltiples, etc.)
        if (isset($props['items']) && is_array($props['items'])) {
            foreach ($props['items'] as $i => $item) {
                if (is_array($item)) {
                    $props['items'][$i] = $this->translate_block_props($item, $from_lang, $to_lang, $translator);
                }
            }
        }

        return $props;
    }

    /**
     * AJAX: Guardar traducción de VBP
     */
    public function ajax_save_vbp_translation() {
        check_ajax_referer('flavor_ml_vbp', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $content = isset($_POST['content']) ? $_POST['content'] : '';

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        // Validar que es JSON válido
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(__('Contenido JSON inválido', 'flavor-multilingual'));
            }
        } else {
            $content = wp_json_encode($content);
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $result = $storage->save_translation('vbp', $post_id, $lang, 'content_json', $content, array(
            'auto'   => false,
            'status' => 'published',
        ));

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Traducción guardada', 'flavor-multilingual'),
            ));
        } else {
            wp_send_json_error(__('Error al guardar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Obtener traducción de VBP
     */
    public function ajax_get_vbp_translation() {
        check_ajax_referer('flavor_ml_vbp', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('vbp', $post_id, $lang, 'content_json');

        if ($translation) {
            $decoded = json_decode($translation, true);
            wp_send_json_success(array(
                'content' => $decoded,
                'raw'     => $translation,
            ));
        } else {
            // Devolver contenido original
            wp_send_json_success(array(
                'content'  => $this->get_vbp_document($post_id),
                'original' => true,
            ));
        }
    }

    /**
     * AJAX: Traducir un bloque específico
     */
    public function ajax_translate_vbp_block() {
        check_ajax_referer('flavor_ml_vbp', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $block = isset($_POST['block']) ? $_POST['block'] : array();
        $to_lang = sanitize_key($_POST['lang'] ?? '');

        if (empty($block) || !$to_lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        if (is_string($block)) {
            $block = json_decode(stripslashes($block), true);
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();

        // Traducir el bloque
        $translated_block = $block;
        if (isset($block['props'])) {
            $translated_block['props'] = $this->translate_block_props($block['props'], $from_lang, $to_lang, $translator);
        }

        if (!empty($block['children'])) {
            $translated_block['children'] = $this->translate_blocks_recursive($block['children'], $from_lang, $to_lang);
        }

        wp_send_json_success(array(
            'block' => $translated_block,
        ));
    }

    // ================================================================
    // API REST
    // ================================================================

    /**
     * Registra endpoints de API para VBP
     */
    public function register_vbp_translation_endpoints() {
        // Obtener traducción de página VBP
        register_rest_route('flavor-multilingual/v1', '/vbp/(?P<id>\d+)/translations', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'api_get_vbp_translations'),
                'permission_callback' => '__return_true',
            ),
        ));

        // Obtener traducción específica
        register_rest_route('flavor-multilingual/v1', '/vbp/(?P<id>\d+)/translations/(?P<lang>[a-z]{2})', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'api_get_vbp_translation_lang'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_save_vbp_translation'),
                'permission_callback' => function() {
                    return current_user_can('edit_posts');
                },
            ),
        ));

        // Traducir página con IA
        register_rest_route('flavor-multilingual/v1', '/vbp/(?P<id>\d+)/translate/(?P<lang>[a-z]{2})', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_translate_vbp_page'),
                'permission_callback' => function() {
                    return current_user_can('edit_posts');
                },
            ),
        ));

        // Traducir a todos los idiomas
        register_rest_route('flavor-multilingual/v1', '/vbp/(?P<id>\d+)/translate-all', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_translate_vbp_all'),
                'permission_callback' => function() {
                    return current_user_can('edit_posts');
                },
            ),
        ));
    }

    /**
     * API: Obtener todas las traducciones de una página VBP
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_vbp_translations($request) {
        $post_id = $request->get_param('id');
        $storage = Flavor_Translation_Storage::get_instance();

        return rest_ensure_response(
            $storage->get_all_translations('vbp', $post_id)
        );
    }

    /**
     * API: Obtener traducción de un idioma específico
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_vbp_translation_lang($request) {
        $post_id = $request->get_param('id');
        $lang = $request->get_param('lang');

        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('vbp', $post_id, $lang, 'content_json');

        if (!$translation) {
            return new WP_Error('not_found', __('Traducción no encontrada', 'flavor-multilingual'), array('status' => 404));
        }

        return rest_ensure_response(array(
            'post_id'  => $post_id,
            'language' => $lang,
            'content'  => json_decode($translation, true),
        ));
    }

    /**
     * API: Guardar traducción de página VBP
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_save_vbp_translation($request) {
        $post_id = $request->get_param('id');
        $lang = $request->get_param('lang');
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_Error('missing_content', __('Contenido requerido', 'flavor-multilingual'), array('status' => 400));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $json_content = is_array($content) ? wp_json_encode($content) : $content;

        $result = $storage->save_translation('vbp', $post_id, $lang, 'content_json', $json_content, array(
            'auto'   => false,
            'status' => 'published',
        ));

        return rest_ensure_response(array(
            'success' => (bool) $result,
            'message' => $result ? __('Guardado', 'flavor-multilingual') : __('Error', 'flavor-multilingual'),
        ));
    }

    /**
     * API: Traducir página VBP con IA
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_translate_vbp_page($request) {
        $post_id = $request->get_param('id');
        $lang = $request->get_param('lang');

        // Simular request AJAX
        $_POST['post_id'] = $post_id;
        $_POST['lang'] = $lang;
        $_POST['nonce'] = wp_create_nonce('flavor_ml_vbp');

        // Usar el mismo método que AJAX
        return $this->translate_vbp_page_internal($post_id, $lang);
    }

    /**
     * API: Traducir a todos los idiomas
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_translate_vbp_all($request) {
        $post_id = $request->get_param('id');

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $results = array();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $result = $this->translate_vbp_page_internal($post_id, $code);
            $results[$code] = array(
                'success' => !is_wp_error($result),
                'message' => is_wp_error($result) ? $result->get_error_message() : __('Traducido', 'flavor-multilingual'),
            );
        }

        return rest_ensure_response(array(
            'post_id' => $post_id,
            'results' => $results,
        ));
    }

    /**
     * Traduce una página VBP internamente
     *
     * @param int    $post_id ID del post
     * @param string $to_lang Idioma destino
     * @return array|WP_Error
     */
    private function translate_vbp_page_internal($post_id, $to_lang) {
        $content_data = $this->get_vbp_document($post_id);
        if (empty($content_data)) {
            return new WP_Error('no_content', __('No hay contenido VBP', 'flavor-multilingual'));
        }

        $blocks = $this->get_translatable_blocks($content_data);
        if (empty($blocks)) {
            return new WP_Error('invalid_content', __('Contenido VBP inválido', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translated_blocks = $this->translate_blocks_recursive($blocks, $from_lang, $to_lang);

        $storage = Flavor_Translation_Storage::get_instance();
        $translated_content = $this->build_translated_document($content_data, $translated_blocks, $from_lang);

        $storage->save_translation('vbp', $post_id, $to_lang, 'content_json', wp_json_encode($translated_content), array(
            'auto'   => true,
            'status' => 'draft',
        ));

        return array(
            'success' => true,
            'blocks'  => $translated_blocks,
        );
    }

    // ================================================================
    // HOOKS DE GUARDADO
    // ================================================================

    /**
     * Hook cuando se guarda una página VBP
     *
     * @param int   $post_id ID del post
     * @param array $data    Datos guardados
     */
    public function on_vbp_page_saved($post_id, $data) {
        // Si hay auto-traducción habilitada, traducir automáticamente
        $auto_translate = Flavor_Multilingual::get_option('auto_translate_new', false);

        if (!$auto_translate) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            // Verificar si ya existe traducción
            $storage = Flavor_Translation_Storage::get_instance();
            $existing = $storage->get_translation('vbp', $post_id, $code, 'content_json');

            if (!$existing) {
                // Traducir automáticamente (en background idealmente)
                $this->translate_vbp_page_internal($post_id, $code);
            }
        }
    }
}

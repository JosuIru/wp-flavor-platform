<?php
/**
 * Flavor Visual Builder - Sistema Unificado de Construcción Visual
 *
 * Unifica Landing Editor y Web Builder Pro en un sistema moderno con:
 * - Modo Secciones (simple, predefinidas)
 * - Modo Componentes (avanzado, flexible)
 * - Migración automática de sistemas antiguos
 * - Compatibilidad total hacia atrás
 *
 * @package FlavorChatIA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del Visual Builder unificado
 */
class Flavor_Visual_Builder {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Versión del sistema
     */
    const VERSION = '1.0.0';

    /**
     * Modos disponibles
     */
    const MODE_SECTIONS = 'sections';    // Secciones predefinidas (simple)
    const MODE_COMPONENTS = 'components'; // Componentes individuales (avanzado)

    /**
     * Meta keys
     */
    const META_MODE = '_flavor_vb_mode';
    const META_DATA = '_flavor_vb_data';
    const META_VERSION = '_flavor_vb_version';
    const META_LEGACY = '_flavor_vb_legacy';
    const META_SETTINGS = '_flavor_vb_settings';

    /**
     * Componentes registrados
     */
    private $components = [];

    /**
     * Secciones registradas
     */
    private $sections = [];

    /**
     * Templates disponibles
     */
    private $templates = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Visual_Builder
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init_hooks();
        $this->register_core_components();
        $this->register_core_sections();
    }

    /**
     * Inicializar hooks de WordPress
     */
    private function init_hooks() {
        // CPT y taxonomías
        add_action('init', [$this, 'register_post_type']);

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_builder_data'], 10, 2);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // AJAX handlers
        add_action('wp_ajax_fvb_save_data', [$this, 'ajax_save_data']);
        add_action('wp_ajax_fvb_load_data', [$this, 'ajax_load_data']);
        add_action('wp_ajax_fvb_switch_mode', [$this, 'ajax_switch_mode']);
        add_action('wp_ajax_fvb_preview', [$this, 'ajax_preview']);
        add_action('wp_ajax_fvb_get_component', [$this, 'ajax_get_component']);
        add_action('wp_ajax_fvb_autosave', [$this, 'ajax_autosave']);
        add_action('wp_ajax_fvb_undo', [$this, 'ajax_undo']);
        add_action('wp_ajax_fvb_redo', [$this, 'ajax_redo']);

        // Renderizado
        add_filter('the_content', [$this, 'render_builder_content'], 10);
        add_filter('single_template', [$this, 'load_builder_template']);

        // Shortcodes
        add_shortcode('flavor_visual_builder', [$this, 'render_shortcode']);

        // Migración automática
        add_action('admin_init', [$this, 'check_auto_migration']);
    }

    /**
     * Registrar post type unificado
     */
    public function register_post_type() {
        // Solo registrar si no existe (compatibilidad)
        if (post_type_exists('flavor_landing')) {
            return;
        }

        register_post_type('flavor_landing', [
            'labels' => [
                'name' => __('Landing Pages', 'flavor-chat-ia'),
                'singular_name' => __('Landing Page', 'flavor-chat-ia'),
                'add_new' => __('Añadir Nueva', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Landing', 'flavor-chat-ia'),
                'edit_item' => __('Editar con Visual Builder', 'flavor-chat-ia'),
                'view_item' => __('Ver Landing', 'flavor-chat-ia'),
                'all_items' => __('Todas las Landings', 'flavor-chat-ia'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'flavor-platform',
            'menu_position' => 25,
            'menu_icon' => 'dashicons-editor-table',
            'supports' => ['title', 'custom-fields', 'revisions'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'landing', 'with_front' => false],
            'capability_type' => 'page',
            'show_in_rest' => false,
        ]);
    }

    /**
     * Añadir meta boxes
     *
     * No registra el metabox si el Page Builder Pro (Vue.js) está activo,
     * ya que ese addon proporciona un editor más avanzado.
     */
    public function add_meta_boxes() {
        // No registrar si Page Builder Pro está activo (evitar duplicados)
        if (class_exists('Flavor_Page_Builder') || class_exists('Flavor_VBP_Page_Builder')) {
            return;
        }

        $post_types = ['flavor_landing', 'page', 'post'];

        foreach ($post_types as $post_type) {
            add_meta_box(
                'flavor_visual_builder',
                __('Flavor Visual Builder', 'flavor-chat-ia'),
                [$this, 'render_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Renderizar meta box del builder
     */
    public function render_meta_box($post) {
        // Verificar permisos
        if (!current_user_can('edit_post', $post->ID)) {
            echo '<p>' . __('No tienes permisos para editar este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Cargar datos existentes
        $mode = $this->get_builder_mode($post->ID);
        $data = $this->get_builder_data($post->ID);
        $settings = $this->get_builder_settings($post->ID);

        // Nonce para seguridad
        wp_nonce_field('flavor_vb_save', 'flavor_vb_nonce');

        // Incluir vista del builder
        include __DIR__ . '/views/builder-interface.php';
    }

    /**
     * Guardar datos del builder
     */
    public function save_builder_data($post_id, $post) {
        // Verificaciones de seguridad
        if (!isset($_POST['flavor_vb_nonce']) || !wp_verify_nonce($_POST['flavor_vb_nonce'], 'flavor_vb_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar modo
        if (isset($_POST['flavor_vb_mode'])) {
            update_post_meta($post_id, self::META_MODE, sanitize_text_field($_POST['flavor_vb_mode']));
        }

        // Guardar datos (JSON)
        if (isset($_POST['flavor_vb_data'])) {
            $data = json_decode(stripslashes($_POST['flavor_vb_data']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                update_post_meta($post_id, self::META_DATA, $data);
                update_post_meta($post_id, self::META_VERSION, self::VERSION);
            }
        }

        // Guardar settings
        if (isset($_POST['flavor_vb_settings'])) {
            $settings = json_decode(stripslashes($_POST['flavor_vb_settings']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                update_post_meta($post_id, self::META_SETTINGS, $settings);
            }
        }
    }

    /**
     * Obtener modo del builder
     */
    public function get_builder_mode($post_id) {
        $mode = get_post_meta($post_id, self::META_MODE, true);

        // Si no existe, detectar de datos antiguos
        if (empty($mode)) {
            $mode = $this->detect_legacy_mode($post_id);
        }

        // Default: modo secciones
        return in_array($mode, [self::MODE_SECTIONS, self::MODE_COMPONENTS]) ? $mode : self::MODE_SECTIONS;
    }

    /**
     * Obtener datos del builder
     */
    public function get_builder_data($post_id) {
        $data = get_post_meta($post_id, self::META_DATA, true);

        // Si no existe, intentar migrar datos antiguos
        if (empty($data)) {
            $data = $this->migrate_legacy_data($post_id);
        }

        return is_array($data) ? $data : [
            'mode' => self::MODE_SECTIONS,
            'version' => self::VERSION,
            'content' => [],
        ];
    }

    /**
     * Obtener configuración del builder
     */
    public function get_builder_settings($post_id) {
        $settings = get_post_meta($post_id, self::META_SETTINGS, true);

        return is_array($settings) ? $settings : [
            'responsive' => true,
            'animations' => true,
            'lazy_load' => true,
            'optimize_images' => true,
        ];
    }

    /**
     * Detectar modo desde datos legacy
     */
    private function detect_legacy_mode($post_id) {
        // Verificar Landing Editor
        $landing_structure = get_post_meta($post_id, '_flavor_landing_structure', true);
        if (!empty($landing_structure)) {
            return self::MODE_SECTIONS;
        }

        // Verificar Web Builder Pro
        $page_layout = get_post_meta($post_id, '_flavor_page_layout', true);
        if (!empty($page_layout)) {
            return self::MODE_COMPONENTS;
        }

        return self::MODE_SECTIONS;
    }

    /**
     * Migrar datos de sistemas antiguos
     */
    private function migrate_legacy_data($post_id) {
        // Intentar migrar desde Landing Editor
        $landing_data = $this->migrate_from_landing_editor($post_id);
        if ($landing_data !== false) {
            return $landing_data;
        }

        // Intentar migrar desde Web Builder Pro
        $builder_data = $this->migrate_from_web_builder($post_id);
        if ($builder_data !== false) {
            return $builder_data;
        }

        return false;
    }

    /**
     * Migrar desde Landing Editor
     */
    private function migrate_from_landing_editor($post_id) {
        $structure = get_post_meta($post_id, '_flavor_landing_structure', true);

        if (empty($structure) || !is_array($structure)) {
            return false;
        }

        // Guardar backup
        update_post_meta($post_id, self::META_LEGACY, [
            'source' => 'landing_editor',
            'data' => $structure,
            'migrated_at' => current_time('mysql'),
        ]);

        // Convertir formato
        $migrated = [
            'mode' => self::MODE_SECTIONS,
            'version' => self::VERSION,
            'migrated_from' => 'landing_editor',
            'content' => [],
        ];

        foreach ($structure as $section) {
            $migrated['content'][] = [
                'type' => 'section',
                'component' => $section['type'] ?? 'hero',
                'variant' => $section['variant'] ?? 'default',
                'data' => $section,
            ];
        }

        // Guardar datos migrados
        update_post_meta($post_id, self::META_DATA, $migrated);
        update_post_meta($post_id, self::META_MODE, self::MODE_SECTIONS);
        update_post_meta($post_id, self::META_VERSION, self::VERSION);

        return $migrated;
    }

    /**
     * Migrar desde Web Builder Pro
     */
    private function migrate_from_web_builder($post_id) {
        $layout = get_post_meta($post_id, '_flavor_page_layout', true);

        if (empty($layout)) {
            return false;
        }

        // Guardar backup
        update_post_meta($post_id, self::META_LEGACY, [
            'source' => 'web_builder_pro',
            'data' => $layout,
            'migrated_at' => current_time('mysql'),
        ]);

        // Convertir formato
        $migrated = [
            'mode' => self::MODE_COMPONENTS,
            'version' => self::VERSION,
            'migrated_from' => 'web_builder_pro',
            'content' => [],
        ];

        // Parsear layout antiguo
        if (is_array($layout)) {
            foreach ($layout as $component) {
                $migrated['content'][] = [
                    'type' => 'component',
                    'component' => $component['type'] ?? 'text',
                    'data' => $component,
                ];
            }
        }

        // Guardar datos migrados
        update_post_meta($post_id, self::META_DATA, $migrated);
        update_post_meta($post_id, self::META_MODE, self::MODE_COMPONENTS);
        update_post_meta($post_id, self::META_VERSION, self::VERSION);

        return $migrated;
    }

    /**
     * Verificar y ejecutar migración automática
     */
    public function check_auto_migration() {
        // Solo en admin
        if (!is_admin()) {
            return;
        }

        // Verificar si hay páginas pendientes de migración
        $pending = get_option('flavor_vb_migration_pending');
        if ($pending === false) {
            // Primera ejecución: buscar páginas legacy
            $this->scan_legacy_pages();
        }
    }

    /**
     * Escanear páginas con formato antiguo
     */
    private function scan_legacy_pages() {
        global $wpdb;

        // Buscar páginas con meta de sistemas antiguos
        $legacy_pages = $wpdb->get_col("
            SELECT DISTINCT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ('_flavor_landing_structure', '_flavor_page_layout')
            AND post_id NOT IN (
                SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = '" . self::META_VERSION . "'
            )
        ");

        update_option('flavor_vb_migration_pending', count($legacy_pages));
        update_option('flavor_vb_migration_pages', $legacy_pages);

        // Si hay páginas, mostrar aviso
        if (count($legacy_pages) > 0) {
            add_action('admin_notices', [$this, 'show_migration_notice']);
        }
    }

    /**
     * Mostrar aviso de migración
     */
    public function show_migration_notice() {
        $pending = get_option('flavor_vb_migration_pending', 0);

        if ($pending > 0) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('Flavor Visual Builder', 'flavor-chat-ia'); ?>:</strong>
                    <?php printf(
                        __('Se encontraron %d páginas con formato antiguo. Se migrarán automáticamente al editarlas.', 'flavor-chat-ia'),
                        $pending
                    ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Registrar componente
     */
    public function register_component($id, $args) {
        $this->components[$id] = wp_parse_args($args, [
            'label' => '',
            'description' => '',
            'icon' => 'dashicons-admin-generic',
            'category' => 'basic',
            'fields' => [],
            'render_callback' => null,
        ]);
    }

    /**
     * Registrar sección
     */
    public function register_section($id, $args) {
        $this->sections[$id] = wp_parse_args($args, [
            'label' => '',
            'description' => '',
            'icon' => 'dashicons-admin-generic',
            'variants' => [],
            'fields' => [],
            'render_callback' => null,
        ]);
    }

    /**
     * Obtener componentes registrados
     */
    public function get_components() {
        return apply_filters('flavor_vb_components', $this->components);
    }

    /**
     * Obtener secciones registradas
     */
    public function get_sections() {
        return apply_filters('flavor_vb_sections', $this->sections);
    }

    /**
     * Registrar componentes core
     */
    private function register_core_components() {
        // Los componentes básicos se registrarán en archivos separados
        // Por ahora dejamos la estructura preparada
        do_action('flavor_vb_register_components', $this);
    }

    /**
     * Registrar secciones core
     */
    private function register_core_sections() {
        // Las secciones se registrarán en archivos separados
        // Por ahora dejamos la estructura preparada
        do_action('flavor_vb_register_sections', $this);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post;

        // No cargar si Page Builder Pro está activo (usa sus propios assets)
        if (class_exists('Flavor_Page_Builder') || class_exists('Flavor_VBP_Page_Builder')) {
            return;
        }

        // Solo cargar en páginas de edición
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        // Verificar si el post type soporta el builder
        if (!in_array($post->post_type, ['flavor_landing', 'page', 'post'])) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'flavor-visual-builder',
            plugins_url('assets/css/visual-builder.css', __FILE__),
            [],
            self::VERSION
        );

        // JS
        wp_enqueue_script(
            'flavor-visual-builder',
            plugins_url('assets/js/visual-builder.js', __FILE__),
            ['jquery', 'jquery-ui-sortable', 'wp-util'],
            self::VERSION,
            true
        );

        // Localización
        wp_localize_script('flavor-visual-builder', 'flavorVB', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_id' => $post->ID,
            'nonce' => wp_create_nonce('flavor_vb_ajax'),
            'mode' => $this->get_builder_mode($post->ID),
            'components' => $this->get_components(),
            'sections' => $this->get_sections(),
            'i18n' => [
                'save' => __('Guardar', 'flavor-chat-ia'),
                'preview' => __('Vista Previa', 'flavor-chat-ia'),
                'undo' => __('Deshacer', 'flavor-chat-ia'),
                'redo' => __('Rehacer', 'flavor-chat-ia'),
                'mode_sections' => __('Modo Secciones', 'flavor-chat-ia'),
                'mode_components' => __('Modo Componentes', 'flavor-chat-ia'),
            ],
        ]);

        // Media Uploader
        wp_enqueue_media();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular()) {
            return;
        }

        global $post;

        // Verificar si la página usa el builder
        $data = get_post_meta($post->ID, self::META_DATA, true);
        if (empty($data)) {
            return;
        }

        // CSS Frontend
        wp_enqueue_style(
            'flavor-vb-frontend',
            plugins_url('assets/css/visual-builder-frontend.css', __FILE__),
            [],
            self::VERSION
        );

        // JS Frontend (si es necesario)
        wp_enqueue_script(
            'flavor-vb-frontend',
            plugins_url('assets/js/visual-builder-frontend.js', __FILE__),
            ['jquery'],
            self::VERSION,
            true
        );
    }

    /**
     * Renderizar contenido del builder
     */
    public function render_builder_content($content) {
        if (!is_singular()) {
            return $content;
        }

        global $post;

        $data = $this->get_builder_data($post->ID);

        if (empty($data) || empty($data['content'])) {
            return $content;
        }

        // Renderizar contenido del builder
        $output = '';

        foreach ($data['content'] as $item) {
            if ($item['type'] === 'section') {
                $output .= $this->render_section($item);
            } elseif ($item['type'] === 'component') {
                $output .= $this->render_component($item);
            }
        }

        return $output;
    }

    /**
     * Renderizar sección
     */
    private function render_section($item) {
        $section_id = $item['component'] ?? '';
        $variant = $item['variant'] ?? 'default';
        $data = $item['data'] ?? [];

        // Verificar si la sección existe
        if (!isset($this->sections[$section_id])) {
            return '';
        }

        $section = $this->sections[$section_id];

        // Ejecutar callback si existe
        if (is_callable($section['render_callback'])) {
            return call_user_func($section['render_callback'], $data, $variant);
        }

        // Renderizado por defecto
        return $this->render_section_default($section_id, $variant, $data);
    }

    /**
     * Renderizar componente
     */
    private function render_component($item) {
        $component_id = $item['component'] ?? '';
        $data = $item['data'] ?? [];

        // Verificar si el componente existe
        if (!isset($this->components[$component_id])) {
            return '';
        }

        $component = $this->components[$component_id];

        // Ejecutar callback si existe
        if (is_callable($component['render_callback'])) {
            return call_user_func($component['render_callback'], $data);
        }

        // Renderizado por defecto
        return $this->render_component_default($component_id, $data);
    }

    /**
     * Renderizado por defecto de sección
     */
    private function render_section_default($section_id, $variant, $data) {
        // Template básico
        ob_start();
        ?>
        <section class="flavor-vb-section flavor-vb-section-<?php echo esc_attr($section_id); ?> flavor-vb-variant-<?php echo esc_attr($variant); ?>">
            <div class="flavor-vb-container">
                <?php echo wp_kses_post($data['content'] ?? ''); ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizado por defecto de componente
     */
    private function render_component_default($component_id, $data) {
        // Template básico
        ob_start();
        ?>
        <div class="flavor-vb-component flavor-vb-component-<?php echo esc_attr($component_id); ?>">
            <?php echo wp_kses_post($data['content'] ?? ''); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Guardar datos
     */
    public function ajax_save_data() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $data = json_decode(stripslashes($_POST['data'] ?? '{}'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('JSON inválido', 'flavor-chat-ia')]);
        }

        update_post_meta($post_id, self::META_DATA, $data);
        update_post_meta($post_id, self::META_VERSION, self::VERSION);

        wp_send_json_success(['message' => __('Guardado correctamente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Cargar datos
     */
    public function ajax_load_data() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $data = $this->get_builder_data($post_id);

        wp_send_json_success(['data' => $data]);
    }

    /**
     * AJAX: Cambiar modo
     */
    public function ajax_switch_mode() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $mode = sanitize_text_field($_POST['mode'] ?? '');

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        if (!in_array($mode, [self::MODE_SECTIONS, self::MODE_COMPONENTS])) {
            wp_send_json_error(['message' => __('Modo inválido', 'flavor-chat-ia')]);
        }

        update_post_meta($post_id, self::META_MODE, $mode);

        wp_send_json_success(['mode' => $mode]);
    }

    /**
     * AJAX: Preview
     */
    public function ajax_preview() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');

        $data = json_decode(stripslashes($_POST['data'] ?? '{}'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('JSON inválido', 'flavor-chat-ia')]);
        }

        // Renderizar preview
        $output = '';
        foreach ($data['content'] ?? [] as $item) {
            if ($item['type'] === 'section') {
                $output .= $this->render_section($item);
            } elseif ($item['type'] === 'component') {
                $output .= $this->render_component($item);
            }
        }

        wp_send_json_success(['html' => $output]);
    }

    /**
     * AJAX: Obtener componente
     */
    public function ajax_get_component() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');

        $component_id = sanitize_text_field($_POST['component_id'] ?? '');

        if (isset($this->components[$component_id])) {
            wp_send_json_success(['component' => $this->components[$component_id]]);
        } elseif (isset($this->sections[$component_id])) {
            wp_send_json_success(['section' => $this->sections[$component_id]]);
        }

        wp_send_json_error(['message' => __('Componente no encontrado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Autosave
     */
    public function ajax_autosave() {
        // Similar a ajax_save_data pero sin mensaje de confirmación
        $this->ajax_save_data();
    }

    /**
     * AJAX: Undo
     */
    public function ajax_undo() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');
        // TODO: Implementar sistema de historial
        wp_send_json_success(['message' => __('Deshacer', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Redo
     */
    public function ajax_redo() {
        check_ajax_referer('flavor_vb_ajax', 'nonce');
        // TODO: Implementar sistema de historial
        wp_send_json_success(['message' => __('Rehacer', 'flavor-chat-ia')]);
    }

    /**
     * Template para landing pages
     */
    public function load_builder_template($template) {
        global $post;

        if ($post->post_type === 'flavor_landing') {
            $builder_template = __DIR__ . '/views/landing-template.php';
            if (file_exists($builder_template)) {
                return $builder_template;
            }
        }

        return $template;
    }

    /**
     * Shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $post_id = intval($atts['id']);

        if (!$post_id) {
            return '';
        }

        $data = $this->get_builder_data($post_id);

        if (empty($data) || empty($data['content'])) {
            return '';
        }

        // Renderizar contenido
        $output = '';
        foreach ($data['content'] as $item) {
            if ($item['type'] === 'section') {
                $output .= $this->render_section($item);
            } elseif ($item['type'] === 'component') {
                $output .= $this->render_component($item);
            }
        }

        return $output;
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Visual_Builder::get_instance();
});

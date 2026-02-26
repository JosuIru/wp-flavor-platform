<?php
/**
 * Settings de Diseño y Apariencia
 *
 * Configuración global de estilos para componentes del Page Builder
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestión de configuración de diseño
 */
class Flavor_Design_Settings {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug de la página de settings
     */
    const PAGE_SLUG = 'flavor-design-settings';

    /**
     * Option name
     */
    const OPTION_NAME = 'flavor_design_settings';

    /**
     * Obtener instancia singleton
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
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_head', [$this, 'output_custom_css'], 99);
    }

    /**
     * Añadir página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=flavor_landing',
            __('Diseño y Apariencia', 'flavor-chat-ia'),
            __('Diseño', 'flavor-chat-ia'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Registrar settings
     */
    public function register_settings() {
        register_setting(self::OPTION_NAME . '_group', self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        // Sección: Colores
        add_settings_section(
            'colors_section',
            __('Colores Principales', 'flavor-chat-ia'),
            [$this, 'render_colors_section'],
            self::PAGE_SLUG
        );

        // Sección: Tipografía
        add_settings_section(
            'typography_section',
            __('Tipografía', 'flavor-chat-ia'),
            [$this, 'render_typography_section'],
            self::PAGE_SLUG
        );

        // Sección: Espaciados
        add_settings_section(
            'spacing_section',
            __('Espaciados y Layout', 'flavor-chat-ia'),
            [$this, 'render_spacing_section'],
            self::PAGE_SLUG
        );

        // Sección: Botones
        add_settings_section(
            'buttons_section',
            __('Botones', 'flavor-chat-ia'),
            [$this, 'render_buttons_section'],
            self::PAGE_SLUG
        );

        // Sección: Componentes
        add_settings_section(
            'components_section',
            __('Componentes', 'flavor-chat-ia'),
            [$this, 'render_components_section'],
            self::PAGE_SLUG
        );

        $this->register_all_fields();
    }

    /**
     * Registrar todos los campos
     */
    private function register_all_fields() {
        // COLORES
        $color_fields = [
            'primary_color' => ['label' => __('Color Primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
            'secondary_color' => ['label' => __('Color Secundario', 'flavor-chat-ia'), 'default' => '#8b5cf6'],
            'accent_color' => ['label' => __('Color de Acento', 'flavor-chat-ia'), 'default' => '#f59e0b'],
            'success_color' => ['label' => __('Color Éxito', 'flavor-chat-ia'), 'default' => '#10b981'],
            'warning_color' => ['label' => __('Color Advertencia', 'flavor-chat-ia'), 'default' => '#f59e0b'],
            'error_color' => ['label' => __('Color Error', 'flavor-chat-ia'), 'default' => '#ef4444'],
            'background_color' => ['label' => __('Color de Fondo', 'flavor-chat-ia'), 'default' => '#ffffff'],
            'text_color' => ['label' => __('Color de Texto', 'flavor-chat-ia'), 'default' => '#1f2937'],
            'text_muted_color' => ['label' => __('Color Texto Secundario', 'flavor-chat-ia'), 'default' => '#6b7280'],
        ];

        foreach ($color_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_color_field'],
                self::PAGE_SLUG,
                'colors_section',
                ['field' => $field, 'default' => $config['default']]
            );
        }

        // TIPOGRAFÍA
        $typography_fields = [
            'font_family_headings' => ['label' => __('Fuente Títulos', 'flavor-chat-ia'), 'type' => 'select'],
            'font_family_body' => ['label' => __('Fuente Cuerpo', 'flavor-chat-ia'), 'type' => 'select'],
            'font_size_base' => ['label' => __('Tamaño Base', 'flavor-chat-ia'), 'type' => 'number', 'suffix' => 'px', 'default' => '16'],
            'font_size_h1' => ['label' => __('Tamaño H1', 'flavor-chat-ia'), 'type' => 'number', 'suffix' => 'px', 'default' => '48'],
            'font_size_h2' => ['label' => __('Tamaño H2', 'flavor-chat-ia'), 'type' => 'number', 'suffix' => 'px', 'default' => '36'],
            'font_size_h3' => ['label' => __('Tamaño H3', 'flavor-chat-ia'), 'type' => 'number', 'suffix' => 'px', 'default' => '28'],
            'line_height_base' => ['label' => __('Interlineado Base', 'flavor-chat-ia'), 'type' => 'number', 'step' => '0.1', 'default' => '1.5'],
            'line_height_headings' => ['label' => __('Interlineado Títulos', 'flavor-chat-ia'), 'type' => 'number', 'step' => '0.1', 'default' => '1.2'],
        ];

        foreach ($typography_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_typography_field'],
                self::PAGE_SLUG,
                'typography_section',
                $config + ['field' => $field]
            );
        }

        // ESPACIADOS
        $spacing_fields = [
            'container_max_width' => ['label' => __('Ancho Máximo Contenedor', 'flavor-chat-ia'), 'default' => '1280', 'suffix' => 'px'],
            'section_padding_y' => ['label' => __('Padding Vertical Sección', 'flavor-chat-ia'), 'default' => '80', 'suffix' => 'px'],
            'section_padding_x' => ['label' => __('Padding Horizontal Sección', 'flavor-chat-ia'), 'default' => '20', 'suffix' => 'px'],
            'grid_gap' => ['label' => __('Espaciado Grid', 'flavor-chat-ia'), 'default' => '24', 'suffix' => 'px'],
            'card_padding' => ['label' => __('Padding Tarjetas', 'flavor-chat-ia'), 'default' => '24', 'suffix' => 'px'],
        ];

        foreach ($spacing_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_number_field'],
                self::PAGE_SLUG,
                'spacing_section',
                ['field' => $field] + $config
            );
        }

        // BOTONES
        $button_fields = [
            'button_border_radius' => ['label' => __('Radio Bordes Botones', 'flavor-chat-ia'), 'default' => '8', 'suffix' => 'px'],
            'button_padding_y' => ['label' => __('Padding Vertical Botón', 'flavor-chat-ia'), 'default' => '12', 'suffix' => 'px'],
            'button_padding_x' => ['label' => __('Padding Horizontal Botón', 'flavor-chat-ia'), 'default' => '24', 'suffix' => 'px'],
            'button_font_size' => ['label' => __('Tamaño Texto Botón', 'flavor-chat-ia'), 'default' => '16', 'suffix' => 'px'],
            'button_font_weight' => ['label' => __('Grosor Texto Botón', 'flavor-chat-ia'), 'default' => '600'],
        ];

        foreach ($button_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_number_field'],
                self::PAGE_SLUG,
                'buttons_section',
                ['field' => $field] + $config
            );
        }

        // COMPONENTES
        $component_fields = [
            'card_border_radius' => ['label' => __('Radio Bordes Tarjetas', 'flavor-chat-ia'), 'default' => '12', 'suffix' => 'px'],
            'card_shadow' => ['label' => __('Sombra Tarjetas', 'flavor-chat-ia'), 'type' => 'select'],
            'hero_overlay_opacity' => ['label' => __('Opacidad Overlay Hero', 'flavor-chat-ia'), 'default' => '0.6', 'step' => '0.1', 'max' => '1'],
            'image_border_radius' => ['label' => __('Radio Bordes Imágenes', 'flavor-chat-ia'), 'default' => '8', 'suffix' => 'px'],
        ];

        foreach ($component_fields as $field => $config) {
            if (isset($config['type']) && $config['type'] === 'select') {
                add_settings_field(
                    $field,
                    $config['label'],
                    [$this, 'render_shadow_field'],
                    self::PAGE_SLUG,
                    'components_section',
                    ['field' => $field]
                );
            } else {
                add_settings_field(
                    $field,
                    $config['label'],
                    [$this, 'render_number_field'],
                    self::PAGE_SLUG,
                    'components_section',
                    ['field' => $field] + $config
                );
            }
        }
    }

    /**
     * Renderizar secciones
     */
    public function render_colors_section() {
        echo '<p>' . __('Configura la paleta de colores principal de tus componentes.', 'flavor-chat-ia') . '</p>';
    }

    public function render_typography_section() {
        echo '<p>' . __('Configura las fuentes y tamaños de texto.', 'flavor-chat-ia') . '</p>';
    }

    public function render_spacing_section() {
        echo '<p>' . __('Configura espaciados, anchos y márgenes.', 'flavor-chat-ia') . '</p>';
    }

    public function render_buttons_section() {
        echo '<p>' . __('Configura el estilo de los botones.', 'flavor-chat-ia') . '</p>';
    }

    public function render_components_section() {
        echo '<p>' . __('Configuración específica de componentes.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderizar campo de color
     */
    public function render_color_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? $args['default'];
        ?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
            value="<?php echo esc_attr($value); ?>"
            class="flavor-color-picker"
            data-default-color="<?php echo esc_attr($args['default']); ?>"
        />
        <?php
    }

    /**
     * Renderizar campo de tipografía
     */
    public function render_typography_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? ($args['default'] ?? '');

        if ($args['type'] === 'select' && strpos($args['field'], 'font_family') !== false) {
            $fonts = $this->get_google_fonts();
            ?>
            <select name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>" class="regular-text">
                <option value=""><?php _e('Sistema por defecto', 'flavor-chat-ia'); ?></option>
                <?php foreach ($fonts as $font): ?>
                    <option value="<?php echo esc_attr($font); ?>" <?php selected($value, $font); ?>>
                        <?php echo esc_html($font); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        } else {
            ?>
            <input
                type="number"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="small-text"
                step="<?php echo esc_attr($args['step'] ?? '1'); ?>"
            />
            <?php if (isset($args['suffix'])): ?>
                <span class="description"><?php echo esc_html($args['suffix']); ?></span>
            <?php endif; ?>
            <?php
        }
    }

    /**
     * Renderizar campo numérico
     */
    public function render_number_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? $args['default'];
        ?>
        <input
            type="number"
            name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
            value="<?php echo esc_attr($value); ?>"
            class="small-text"
            step="<?php echo esc_attr($args['step'] ?? '1'); ?>"
            <?php if (isset($args['max'])): ?>max="<?php echo esc_attr($args['max']); ?>"<?php endif; ?>
        />
        <?php if (isset($args['suffix'])): ?>
            <span class="description"><?php echo esc_html($args['suffix']); ?></span>
        <?php endif; ?>
        <?php
    }

    /**
     * Renderizar campo de sombra
     */
    public function render_shadow_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? 'medium';

        $shadows = [
            'none' => __('Sin sombra', 'flavor-chat-ia'),
            'small' => __('Pequeña', 'flavor-chat-ia'),
            'medium' => __('Media', 'flavor-chat-ia'),
            'large' => __('Grande', 'flavor-chat-ia'),
            'xl' => __('Extra Grande', 'flavor-chat-ia'),
        ];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>">
            <?php foreach ($shadows as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Google Fonts populares
     */
    private function get_google_fonts() {
        return [
            'Inter',
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Poppins',
            'Raleway',
            'Nunito',
            'Playfair Display',
            'Merriweather',
            'Source Sans Pro',
            'Ubuntu',
            'Work Sans',
            'Quicksand',
            'DM Sans',
        ];
    }

    /**
     * Renderizar página de settings
     */
    public function render_settings_page() {
        $nonce_temas = wp_create_nonce('flavor_admin_nonce');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Selector de Temas -->
            <div class="flavor-theme-selector-wrapper">
                <h2><?php _e('Temas Predefinidos', 'flavor-chat-ia'); ?></h2>
                <p class="description"><?php _e('Selecciona un tema base por sector. Los ajustes manuales de abajo sobreescriben los valores del tema.', 'flavor-chat-ia'); ?></p>

                <!-- Filtro por Categoría -->
                <div id="flavor-category-filter" class="flavor-category-filter" style="margin: 16px 0 20px;">
                    <label for="flavor-category-select" style="margin-right: 10px; font-weight: 500;">
                        <?php _e('Filtrar por sector:', 'flavor-chat-ia'); ?>
                    </label>
                    <select id="flavor-category-select" class="flavor-category-select">
                        <option value="all"><?php _e('Todos los temas', 'flavor-chat-ia'); ?></option>
                    </select>
                    <span id="flavor-themes-count" class="flavor-themes-count" style="margin-left: 12px; color: #6b7280; font-size: 13px;"></span>
                </div>

                <div id="flavor-themes-grid" class="flavor-themes-grid">
                    <p class="flavor-themes-loading"><?php _e('Cargando temas...', 'flavor-chat-ia'); ?></p>
                </div>
                <div id="flavor-theme-feedback" class="flavor-theme-feedback" style="display:none;"></div>
            </div>

            <!-- Importar Plantillas Web -->
            <div class="flavor-web-templates-wrapper" style="margin-top:30px;">
                <h2><?php _e('Plantillas de Sitios Web', 'flavor-chat-ia'); ?></h2>
                <p class="description"><?php _e('Importa todas las páginas de un diseño web. Se crearán como Landing Pages en borrador con el tema correspondiente activado.', 'flavor-chat-ia'); ?></p>
                <div id="flavor-web-templates-grid" class="flavor-themes-grid" style="margin-top:15px;">
                    <?php
                    $web_themes_info = [
                        'zunbeltz' => ['label' => 'Zunbeltz', 'desc' => 'Comunidad Ecológica', 'color' => '#2D5F2E', 'icon' => 'dashicons-palmtree'],
                        'naarq' => ['label' => 'Naarq', 'desc' => 'Estudi d\'Arquitectura', 'color' => '#1a1a1a', 'icon' => 'dashicons-building'],
                        'campi' => ['label' => 'Campi', 'desc' => 'Espai Cultural & Teatre', 'color' => '#1a1b3a', 'icon' => 'dashicons-tickets-alt'],
                        'denendako' => ['label' => 'Denendako', 'desc' => 'Herri Sarea', 'color' => '#333333', 'icon' => 'dashicons-networking'],
                        'escena-familiar' => ['label' => 'Escena Familiar', 'desc' => 'Teatre Familiar', 'color' => '#7c3aed', 'icon' => 'dashicons-groups'],
                        'grupos-consumo' => ['label' => 'Grupos de Consumo', 'desc' => 'App Grupo de Consumo', 'color' => '#4a7c59', 'icon' => 'dashicons-carrot'],
                        'comunidad-viva' => ['label' => 'Comunidad Viva', 'desc' => 'Red Social Cooperativa', 'color' => '#4f46e5', 'icon' => 'dashicons-groups'],
                        'jantoki' => ['label' => 'Jantoki', 'desc' => 'Restaurante Cooperativo', 'color' => '#8b5a2b', 'icon' => 'dashicons-food'],
                        'mercado-espiral' => ['label' => 'Mercado Espiral', 'desc' => 'Marketplace km0', 'color' => '#2e7d32', 'icon' => 'dashicons-store'],
                        'spiral-bank' => ['label' => 'Spiral Bank', 'desc' => 'Banca Cooperativa', 'color' => '#764ba2', 'icon' => 'dashicons-money-alt'],
                        'red-cuidados' => ['label' => 'Red de Cuidados', 'desc' => 'Apoyo Mutuo Comunitario', 'color' => '#ec4899', 'icon' => 'dashicons-heart'],
                        'academia-espiral' => ['label' => 'Academia Espiral', 'desc' => 'Educación P2P', 'color' => '#d97706', 'icon' => 'dashicons-welcome-learn-more'],
                        'democracia-universal' => ['label' => 'Democracia Universal', 'desc' => 'Gobernanza Participativa', 'color' => '#8b5cf6', 'icon' => 'dashicons-megaphone'],
                        'flujo' => ['label' => 'FLUJO', 'desc' => 'Red de Vídeo Consciente', 'color' => '#166534', 'icon' => 'dashicons-video-alt3'],
                        'kulturaka' => ['label' => 'Kulturaka', 'desc' => 'Cultura Cooperativa', 'color' => '#e63946', 'icon' => 'dashicons-tickets-alt'],
                        'pueblo-vivo' => ['label' => 'Pueblo Vivo', 'desc' => 'Revitalización Rural', 'color' => '#c2703a', 'icon' => 'dashicons-admin-home'],
                        'ecos-comunitarios' => ['label' => 'Ecos Comunitarios', 'desc' => 'Espacios Compartidos', 'color' => '#0891b2', 'icon' => 'dashicons-admin-multisite'],
                    ];
                    foreach ($web_themes_info as $sector_id => $info):
                    ?>
                    <div class="flavor-theme-card flavor-web-template-card" style="cursor:default;">
                        <div class="flavor-theme-card__preview" style="background:<?php echo esc_attr($info['color']); ?>;">
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                                <span class="dashicons <?php echo esc_attr($info['icon']); ?>" style="font-size:48px;width:48px;height:48px;color:rgba(255,255,255,0.9);"></span>
                            </div>
                        </div>
                        <div class="flavor-theme-card__info">
                            <div class="flavor-theme-card__name"><?php echo esc_html($info['label']); ?></div>
                            <div class="flavor-theme-card__desc"><?php echo esc_html($info['desc']); ?></div>
                            <button type="button" class="button button-primary flavor-import-web-templates" data-sector="<?php echo esc_attr($sector_id); ?>" style="margin-top:8px;">
                                <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                                <?php _e('Importar Sitio Completo', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="flavor-import-feedback" class="flavor-theme-feedback" style="display:none;"></div>
            </div>

            <div class="flavor-design-settings">
                <form method="post" action="options.php">
                    <?php
                    settings_fields(self::OPTION_NAME . '_group');
                    do_settings_sections(self::PAGE_SLUG);
                    submit_button(__('Guardar Cambios', 'flavor-chat-ia'));
                    ?>
                </form>

                <div class="flavor-design-preview">
                    <h2><?php _e('Vista Previa', 'flavor-chat-ia'); ?></h2>
                    <div class="flavor-preview-content">
                        <?php $this->render_preview(); ?>
                    </div>
                </div>
            </div>

            <div class="flavor-design-actions">
                <h3><?php _e('Acciones', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-starter-theme-action" style="margin-bottom: 10px;">
                    <button type="button" class="button button-primary" id="flavor-install-starter-theme">
                        <?php _e('Instalar y activar tema Flavor Starter', 'flavor-chat-ia'); ?>
                    </button>
                    <span id="flavor-starter-theme-status" style="margin-left:10px;color:#6b7280;font-size:12px;"></span>
                </div>
                <button type="button" class="button" id="flavor-reset-defaults">
                    <?php _e('Restaurar Valores por Defecto', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button" id="flavor-export-settings">
                    <?php _e('Exportar Configuración', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button" id="flavor-import-settings">
                    <?php _e('Importar Configuración', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Exportar Design Tokens -->
            <div class="flavor-export-tokens-wrapper" style="margin-top:30px;">
                <h2><?php _e('Exportar Design Tokens', 'flavor-chat-ia'); ?></h2>
                <p class="description"><?php _e('Exporta tus tokens de diseño en diferentes formatos para usarlos en otros proyectos.', 'flavor-chat-ia'); ?></p>

                <div class="flavor-export-tokens-controls" style="display:flex;gap:16px;align-items:flex-start;margin-top:16px;">
                    <div style="flex:0 0 auto;">
                        <label for="flavor-token-format" style="display:block;margin-bottom:6px;font-weight:500;">
                            <?php _e('Formato:', 'flavor-chat-ia'); ?>
                        </label>
                        <select id="flavor-token-format" class="flavor-category-select" style="min-width:200px;">
                            <option value="w3c"><?php _e('W3C Design Tokens (JSON)', 'flavor-chat-ia'); ?></option>
                            <option value="css"><?php _e('CSS Custom Properties', 'flavor-chat-ia'); ?></option>
                            <option value="js"><?php _e('JavaScript/TypeScript', 'flavor-chat-ia'); ?></option>
                            <option value="tailwind"><?php _e('Tailwind Config', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <div style="flex:0 0 auto;padding-top:24px;">
                        <button type="button" class="button" id="flavor-preview-tokens">
                            <span class="dashicons dashicons-visibility" style="margin-top:3px;"></span>
                            <?php _e('Vista Previa', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="flavor-download-tokens">
                            <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                            <?php _e('Descargar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

                <div id="flavor-tokens-preview" class="flavor-tokens-preview" style="display:none;margin-top:20px;">
                    <div class="flavor-tokens-preview-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span class="flavor-tokens-filename" style="font-family:monospace;font-size:13px;color:#6b7280;"></span>
                        <button type="button" class="button button-small" id="flavor-copy-tokens">
                            <span class="dashicons dashicons-clipboard" style="font-size:14px;width:14px;height:14px;margin-top:2px;"></span>
                            <?php _e('Copiar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <pre class="flavor-tokens-code" style="background:#1e1e2e;color:#cdd6f4;padding:16px;border-radius:8px;overflow-x:auto;max-height:400px;font-size:12px;line-height:1.5;"><code></code></pre>
                </div>

                <div id="flavor-tokens-feedback" class="flavor-theme-feedback" style="display:none;margin-top:12px;"></div>
            </div>
        </div>

        <script>
        (function() {
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            var nonce = '<?php echo esc_js($nonce_temas); ?>';
            var starterThemeUrl = '<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=flavor_install_starter_theme'), 'flavor_install_starter_theme')); ?>';
            var gridEl = document.getElementById('flavor-themes-grid');
            var feedbackEl = document.getElementById('flavor-theme-feedback');
            var categorySelect = document.getElementById('flavor-category-select');
            var themesCountEl = document.getElementById('flavor-themes-count');
            var temasCargados = {};
            var categoriasDisponibles = {};
            var temaActivoActual = 'default';

            function mostrarFeedback(mensaje, tipo) {
                feedbackEl.textContent = mensaje;
                feedbackEl.className = 'flavor-theme-feedback flavor-theme-feedback--' + tipo;
                feedbackEl.style.display = 'block';
                setTimeout(function() { feedbackEl.style.display = 'none'; }, 3000);
            }

            function cargarTemas(categoria) {
                categoria = categoria || 'all';

                var formData = new FormData();
                formData.append('action', 'flavor_get_themes');
                formData.append('nonce', nonce);
                formData.append('category', categoria);

                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(respuesta) {
                        if (!respuesta.success) {
                            gridEl.innerHTML = '<p>Error al cargar temas.</p>';
                            return;
                        }
                        temasCargados = respuesta.data.themes;
                        temaActivoActual = respuesta.data.active_theme;

                        // Actualizar selector de categorías (solo la primera vez)
                        if (respuesta.data.categories && Object.keys(categoriasDisponibles).length === 0) {
                            categoriasDisponibles = respuesta.data.categories;
                            actualizarSelectorCategorias();
                        }

                        renderizarTemas(temasCargados, temaActivoActual);
                    })
                    .catch(function() {
                        gridEl.innerHTML = '<p>Error de conexión.</p>';
                    });
            }

            function actualizarSelectorCategorias() {
                categorySelect.innerHTML = '';
                for (var catId in categoriasDisponibles) {
                    if (!categoriasDisponibles.hasOwnProperty(catId)) continue;
                    var opt = document.createElement('option');
                    opt.value = catId;
                    opt.textContent = categoriasDisponibles[catId];
                    categorySelect.appendChild(opt);
                }
            }

            var starterBtn = document.getElementById('flavor-install-starter-theme');
            var starterStatus = document.getElementById('flavor-starter-theme-status');
            if (starterBtn) {
                starterBtn.addEventListener('click', function() {
                    starterBtn.disabled = true;
                    starterStatus.textContent = '<?php echo esc_js(__('Instalando y activando...', 'flavor-chat-ia')); ?>';
                    window.location.href = starterThemeUrl;
                });
            }

            // Extraer colores de las variables del tema
            function obtenerColoresDeTema(tema) {
                // Intentar extraer de variables CSS si existen
                var colorPrimario = '#3b82f6';
                var colorFondo = '#ffffff';
                var colorTexto = '#1f2937';
                var colorTextoSec = '#6b7280';

                // Colores hardcodeados para temas conocidos (fallback)
                var coloresTema = {
                    'default': '#3b82f6', 'modern-purple': '#8b5cf6', 'ocean-blue': '#0891b2',
                    'forest-green': '#16a34a', 'sunset-orange': '#ea580c', 'dark-mode': '#60a5fa',
                    'minimal': '#171717', 'corporate': '#1e40af', 'themacle': '#5660b9',
                    'themacle-dark': '#7b84d1', 'zunbeltz': '#2D5F2E', 'naarq': '#1a1a1a',
                    'campi': '#1a1b3a', 'denendako': '#333333', 'escena-familiar': '#7c3aed',
                    'grupos-consumo': '#4a7c59', 'comunidad-viva': '#4f46e5', 'jantoki': '#8b5a2b',
                    'mercado-espiral': '#2e7d32', 'spiral-bank': '#764ba2', 'red-cuidados': '#ec4899',
                    'academia-espiral': '#d97706', 'democracia-universal': '#8b5cf6', 'flujo': '#166534',
                    'kulturaka': '#e63946', 'pueblo-vivo': '#c2703a', 'ecos-comunitarios': '#0891b2',
                    // Nuevos temas por sector
                    'salud-vital': '#0d9488', 'academia-moderna': '#7c3aed', 'fitness-energy': '#dc2626',
                    'galeria-arte': '#1f2937', 'tech-startup': '#6366f1', 'organic-fresh': '#65a30d',
                    'real-estate-pro': '#0369a1', 'corporate-trust': '#1e3a5f', 'gastro-deluxe': '#92400e',
                    'kids-fun': '#f97316'
                };

                var fondosTema = {
                    'dark-mode': '#111827', 'themacle-dark': '#1a1a2e', 'minimal': '#fafafa',
                    'naarq': '#f5f0e8', 'campi': '#0d0e24', 'denendako': '#fafafa',
                    'comunidad-viva': '#f8f9ff', 'jantoki': '#fdf8f3', 'mercado-espiral': '#f9fdf9',
                    'spiral-bank': '#faf8ff', 'red-cuidados': '#fef7fb', 'academia-espiral': '#fffbf5',
                    'democracia-universal': '#faf8ff', 'flujo': '#f7fdf9', 'kulturaka': '#fffaf9',
                    'pueblo-vivo': '#fdf9f5', 'ecos-comunitarios': '#f5fcff',
                    // Nuevos temas
                    'salud-vital': '#f0fdfa', 'academia-moderna': '#faf5ff', 'fitness-energy': '#fafafa',
                    'galeria-arte': '#fafafa', 'tech-startup': '#f8fafc', 'organic-fresh': '#f7fee7',
                    'real-estate-pro': '#f8fafc', 'corporate-trust': '#f8fafc', 'gastro-deluxe': '#fffbeb',
                    'kids-fun': '#fffbeb'
                };

                var temasOscuros = ['dark-mode', 'themacle-dark', 'campi'];

                colorPrimario = coloresTema[tema.id] || colorPrimario;
                colorFondo = fondosTema[tema.id] || colorFondo;

                var esOscuro = temasOscuros.indexOf(tema.id) !== -1;
                colorTexto = esOscuro ? '#e5e7eb' : '#1f2937';
                colorTextoSec = esOscuro ? '#9ca3af' : '#6b7280';

                return {
                    primario: colorPrimario,
                    fondo: colorFondo,
                    texto: colorTexto,
                    textoSecundario: colorTextoSec,
                    esOscuro: esOscuro
                };
            }

            function renderizarTemas(temas, temaActivo) {
                var html = '';
                var contadorTemas = 0;

                for (var id in temas) {
                    if (!temas.hasOwnProperty(id)) continue;
                    var tema = temas[id];
                    tema.id = id;
                    contadorTemas++;

                    var esActivo = (id === temaActivo);
                    var colores = obtenerColoresDeTema(tema);

                    html += '<div class="flavor-theme-card' + (esActivo ? ' flavor-theme-card--active' : '') + '" data-theme-id="' + id + '" data-category="' + (tema.category || 'general') + '">';

                    // Preview visual mejorado
                    html += '<div class="flavor-theme-card__preview" style="background:' + colores.fondo + ';">';
                    html += '  <div class="flavor-theme-card__preview-header" style="background:' + colores.primario + ';"></div>';
                    html += '  <div class="flavor-theme-card__preview-body">';
                    html += '    <div class="flavor-theme-card__preview-title" style="background:' + colores.texto + ';"></div>';
                    html += '    <div class="flavor-theme-card__preview-text" style="background:' + colores.textoSecundario + ';"></div>';
                    html += '    <div class="flavor-theme-card__preview-text flavor-theme-card__preview-text--short" style="background:' + colores.textoSecundario + ';"></div>';
                    html += '    <div class="flavor-theme-card__preview-btn" style="background:' + colores.primario + ';"></div>';
                    html += '  </div>';
                    html += '</div>';

                    // Info mejorada
                    html += '<div class="flavor-theme-card__info">';
                    html += '  <div class="flavor-theme-card__name">' + escapeHtml(tema.name) + '</div>';
                    html += '  <div class="flavor-theme-card__desc">' + escapeHtml(tema.description || '') + '</div>';

                    // Mostrar "ideal para" si existe
                    if (tema.ideal_for) {
                        html += '  <div class="flavor-theme-card__ideal-for" title="' + escapeHtml(tema.ideal_for) + '">';
                        html += '    <span class="dashicons dashicons-lightbulb" style="font-size:12px;width:12px;height:12px;margin-right:4px;color:#f59e0b;"></span>';
                        html += '    <span style="font-size:10px;color:#6b7280;">' + escapeHtml(truncarTexto(tema.ideal_for, 40)) + '</span>';
                        html += '  </div>';
                    }

                    // Badges de categoría
                    if (tema.category_label && tema.category !== 'general') {
                        html += '  <span class="flavor-theme-card__badge flavor-theme-card__badge--category">' + escapeHtml(tema.category_label) + '</span>';
                    }

                    if (esActivo) {
                        html += '  <span class="flavor-theme-card__badge"><?php echo esc_js(__('Activo', 'flavor-chat-ia')); ?></span>';
                    }
                    if (tema.is_custom) {
                        html += '  <span class="flavor-theme-card__badge flavor-theme-card__badge--custom"><?php echo esc_js(__('Custom', 'flavor-chat-ia')); ?></span>';
                    }
                    html += '</div>';

                    html += '</div>';
                }

                gridEl.innerHTML = html;

                // Actualizar contador
                themesCountEl.textContent = contadorTemas + ' <?php echo esc_js(__('temas disponibles', 'flavor-chat-ia')); ?>';

                // Bind clicks
                var tarjetas = gridEl.querySelectorAll('.flavor-theme-card');
                tarjetas.forEach(function(tarjeta) {
                    tarjeta.addEventListener('click', function() {
                        var idTema = this.getAttribute('data-theme-id');
                        aplicarTema(idTema, this);
                    });
                });
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function truncarTexto(texto, maxLength) {
                if (texto.length <= maxLength) return texto;
                return texto.substring(0, maxLength) + '...';
            }

            // Event listener para filtro de categoría
            categorySelect.addEventListener('change', function() {
                var categoriaSeleccionada = this.value;
                cargarTemas(categoriaSeleccionada);
            });

            function aplicarTema(idTema, tarjetaEl) {
                // Deshabilitar clicks temporalmente
                var tarjetas = gridEl.querySelectorAll('.flavor-theme-card');
                tarjetas.forEach(function(t) { t.style.pointerEvents = 'none'; });

                tarjetaEl.classList.add('flavor-theme-card--loading');

                var formData = new FormData();
                formData.append('action', 'flavor_set_theme');
                formData.append('nonce', nonce);
                formData.append('theme_id', idTema);

                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(respuesta) {
                        tarjetaEl.classList.remove('flavor-theme-card--loading');
                        tarjetas.forEach(function(t) { t.style.pointerEvents = ''; });

                        if (respuesta.success) {
                            // Actualizar estado visual
                            tarjetas.forEach(function(t) { t.classList.remove('flavor-theme-card--active'); });
                            tarjetaEl.classList.add('flavor-theme-card--active');

                            // Remover badges activo y añadir al nuevo
                            gridEl.querySelectorAll('.flavor-theme-card__badge').forEach(function(b) {
                                if (b.classList.contains('flavor-theme-card__badge--custom')) return;
                                b.remove();
                            });
                            var infoEl = tarjetaEl.querySelector('.flavor-theme-card__info');
                            var badgeActivo = document.createElement('span');
                            badgeActivo.className = 'flavor-theme-card__badge';
                            badgeActivo.textContent = '<?php echo esc_js(__('Activo', 'flavor-chat-ia')); ?>';
                            infoEl.appendChild(badgeActivo);

                            mostrarFeedback('<?php echo esc_js(__('Tema aplicado correctamente. Recargando...', 'flavor-chat-ia')); ?>', 'success');
                            // Recargar para que el formulario refleje los valores del nuevo tema
                            setTimeout(function() { window.location.reload(); }, 600);
                        } else {
                            mostrarFeedback(respuesta.data.message || 'Error', 'error');
                        }
                    })
                    .catch(function() {
                        tarjetaEl.classList.remove('flavor-theme-card--loading');
                        tarjetas.forEach(function(t) { t.style.pointerEvents = ''; });
                        mostrarFeedback('Error de conexión', 'error');
                    });
            }

            // Cargar temas al iniciar
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', cargarTemas);
            } else {
                cargarTemas();
            }

            // ─── Exportar Design Tokens ───
            var tokenFormatSelect = document.getElementById('flavor-token-format');
            var previewTokensBtn = document.getElementById('flavor-preview-tokens');
            var downloadTokensBtn = document.getElementById('flavor-download-tokens');
            var copyTokensBtn = document.getElementById('flavor-copy-tokens');
            var tokensPreview = document.getElementById('flavor-tokens-preview');
            var tokensCode = tokensPreview ? tokensPreview.querySelector('code') : null;
            var tokensFilename = tokensPreview ? tokensPreview.querySelector('.flavor-tokens-filename') : null;
            var tokensFeedback = document.getElementById('flavor-tokens-feedback');

            var tokenFilenames = {
                'w3c': 'design-tokens.json',
                'css': 'design-tokens.css',
                'js': 'design-tokens.js',
                'tailwind': 'tailwind.config.js'
            };

            function mostrarTokensFeedback(mensaje, tipo) {
                tokensFeedback.textContent = mensaje;
                tokensFeedback.className = 'flavor-theme-feedback flavor-theme-feedback--' + tipo;
                tokensFeedback.style.display = 'block';
                setTimeout(function() { tokensFeedback.style.display = 'none'; }, 3000);
            }

            function obtenerTokens(formato, callback) {
                var formData = new FormData();
                formData.append('action', 'flavor_export_tokens_' + formato);
                formData.append('nonce', nonce);

                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(respuesta) {
                        if (respuesta.success) {
                            callback(respuesta.data.content, respuesta.data.filename);
                        } else {
                            mostrarTokensFeedback(respuesta.data.message || 'Error', 'error');
                        }
                    })
                    .catch(function() {
                        mostrarTokensFeedback('Error de conexión', 'error');
                    });
            }

            if (previewTokensBtn) {
                previewTokensBtn.addEventListener('click', function() {
                    var formato = tokenFormatSelect.value;
                    previewTokensBtn.disabled = true;

                    obtenerTokens(formato, function(contenido, filename) {
                        previewTokensBtn.disabled = false;
                        tokensCode.textContent = contenido;
                        tokensFilename.textContent = filename || tokenFilenames[formato];
                        tokensPreview.style.display = 'block';
                    });

                    setTimeout(function() { previewTokensBtn.disabled = false; }, 5000);
                });
            }

            if (downloadTokensBtn) {
                downloadTokensBtn.addEventListener('click', function() {
                    var formato = tokenFormatSelect.value;
                    downloadTokensBtn.disabled = true;

                    obtenerTokens(formato, function(contenido, filename) {
                        downloadTokensBtn.disabled = false;
                        var blob = new Blob([contenido], { type: 'text/plain' });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = filename || tokenFilenames[formato];
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        mostrarTokensFeedback('<?php echo esc_js(__('Archivo descargado', 'flavor-chat-ia')); ?>', 'success');
                    });

                    setTimeout(function() { downloadTokensBtn.disabled = false; }, 5000);
                });
            }

            if (copyTokensBtn) {
                copyTokensBtn.addEventListener('click', function() {
                    var texto = tokensCode.textContent;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(texto).then(function() {
                            mostrarTokensFeedback('<?php echo esc_js(__('Código copiado al portapapeles', 'flavor-chat-ia')); ?>', 'success');
                        });
                    } else {
                        var textarea = document.createElement('textarea');
                        textarea.value = texto;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        mostrarTokensFeedback('<?php echo esc_js(__('Código copiado al portapapeles', 'flavor-chat-ia')); ?>', 'success');
                    }
                });
            }

            // ─── Importar plantillas web ───
            var importFeedback = document.getElementById('flavor-import-feedback');

            function mostrarImportFeedback(mensaje, tipo) {
                importFeedback.textContent = mensaje;
                importFeedback.className = 'flavor-theme-feedback flavor-theme-feedback--' + tipo;
                importFeedback.style.display = 'block';
                if (tipo === 'success') {
                    setTimeout(function() { importFeedback.style.display = 'none'; }, 8000);
                }
            }

            document.querySelectorAll('.flavor-import-web-templates').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var sectorId = this.getAttribute('data-sector');
                    var btnEl = this;

                    if (!confirm('<?php echo esc_js(__('¿Importar todas las plantillas de este diseño? Se crearán como Landing Pages en borrador.', 'flavor-chat-ia')); ?>')) {
                        return;
                    }

                    btnEl.disabled = true;
                    btnEl.textContent = '<?php echo esc_js(__('Importando...', 'flavor-chat-ia')); ?>';

                    var formData = new FormData();
                    formData.append('action', 'flavor_import_theme_templates');
                    formData.append('nonce', nonce);
                    formData.append('sector_id', sectorId);

                    fetch(ajaxUrl, { method: 'POST', body: formData })
                        .then(function(r) { return r.json(); })
                        .then(function(respuesta) {
                            btnEl.disabled = false;
                            btnEl.innerHTML = '<span class="dashicons dashicons-download" style="margin-top:3px;"></span> <?php echo esc_js(__('Importar Sitio Completo', 'flavor-chat-ia')); ?>';

                            if (respuesta.success) {
                                var msg = respuesta.data.message;
                                var links = respuesta.data.pages.map(function(p) {
                                    return '<a href="' + p.edit + '" target="_blank">' + p.title + '</a>';
                                }).join(' | ');
                                importFeedback.innerHTML = msg + '<br><small>' + links + '</small>';
                                importFeedback.className = 'flavor-theme-feedback flavor-theme-feedback--success';
                                importFeedback.style.display = 'block';

                                // Recargar temas para mostrar el nuevo tema activo
                                cargarTemas();
                            } else {
                                mostrarImportFeedback(respuesta.data.message || 'Error', 'error');
                            }
                        })
                        .catch(function() {
                            btnEl.disabled = false;
                            btnEl.innerHTML = '<span class="dashicons dashicons-download" style="margin-top:3px;"></span> <?php echo esc_js(__('Importar Sitio Completo', 'flavor-chat-ia')); ?>';
                            mostrarImportFeedback('Error de conexión', 'error');
                        });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderizar vista previa
     */
    private function render_preview() {
        $settings = $this->get_settings();
        ?>
        <div class="flavor-preview-section" style="background: <?php echo esc_attr($settings['background_color'] ?? '#ffffff'); ?>; padding: 40px; border-radius: 12px;">
            <h1 style="color: <?php echo esc_attr($settings['primary_color'] ?? '#3b82f6'); ?>; font-size: <?php echo esc_attr($settings['font_size_h1'] ?? '48'); ?>px;">
                <?php _e('Título de Ejemplo', 'flavor-chat-ia'); ?>
            </h1>
            <h2 style="color: <?php echo esc_attr($settings['secondary_color'] ?? '#8b5cf6'); ?>; font-size: <?php echo esc_attr($settings['font_size_h2'] ?? '36'); ?>px;">
                <?php _e('Subtítulo de Ejemplo', 'flavor-chat-ia'); ?>
            </h2>
            <p style="color: <?php echo esc_attr($settings['text_color'] ?? '#1f2937'); ?>; font-size: <?php echo esc_attr($settings['font_size_base'] ?? '16'); ?>px;">
                <?php _e('Este es un párrafo de ejemplo para visualizar cómo se verán los textos con la configuración actual.', 'flavor-chat-ia'); ?>
            </p>
            <p style="color: <?php echo esc_attr($settings['text_muted_color'] ?? '#6b7280'); ?>;">
                <?php _e('Texto secundario o descripción complementaria.', 'flavor-chat-ia'); ?>
            </p>
            <div style="display: flex; gap: 16px; margin-top: 24px;">
                <button class="flavor-preview-button" style="
                    background: <?php echo esc_attr($settings['primary_color'] ?? '#3b82f6'); ?>;
                    color: white;
                    padding: <?php echo esc_attr($settings['button_padding_y'] ?? '12'); ?>px <?php echo esc_attr($settings['button_padding_x'] ?? '24'); ?>px;
                    border-radius: <?php echo esc_attr($settings['button_border_radius'] ?? '8'); ?>px;
                    font-size: <?php echo esc_attr($settings['button_font_size'] ?? '16'); ?>px;
                    font-weight: <?php echo esc_attr($settings['button_font_weight'] ?? '600'); ?>;
                    border: none;
                    cursor: pointer;
                ">
                    <?php _e('Botón Primario', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-preview-button" style="
                    background: <?php echo esc_attr($settings['secondary_color'] ?? '#8b5cf6'); ?>;
                    color: white;
                    padding: <?php echo esc_attr($settings['button_padding_y'] ?? '12'); ?>px <?php echo esc_attr($settings['button_padding_x'] ?? '24'); ?>px;
                    border-radius: <?php echo esc_attr($settings['button_border_radius'] ?? '8'); ?>px;
                    font-size: <?php echo esc_attr($settings['button_font_size'] ?? '16'); ?>px;
                    font-weight: <?php echo esc_attr($settings['button_font_weight'] ?? '600'); ?>;
                    border: none;
                    cursor: pointer;
                ">
                    <?php _e('Botón Secundario', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".flavor-color-picker").wpColorPicker();
            });
        ');

        wp_add_inline_style('wp-admin', '
            /* Theme Selector */
            .flavor-theme-selector-wrapper {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
                margin: 20px 0 30px;
            }
            .flavor-theme-selector-wrapper h2 {
                margin-top: 0;
                margin-bottom: 4px;
            }
            .flavor-theme-selector-wrapper > .description {
                margin-bottom: 20px;
                color: #666;
            }
            .flavor-themes-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
            }
            .flavor-themes-loading {
                color: #888;
                font-style: italic;
            }
            .flavor-theme-card {
                border: 2px solid #e5e7eb;
                border-radius: 10px;
                overflow: hidden;
                cursor: pointer;
                transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
                background: #fff;
            }
            .flavor-theme-card:hover {
                border-color: #93c5fd;
                box-shadow: 0 4px 12px rgba(59,130,246,0.15);
                transform: translateY(-2px);
            }
            .flavor-theme-card--active {
                border-color: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59,130,246,0.3);
            }
            .flavor-theme-card--loading {
                opacity: 0.6;
                pointer-events: none;
            }
            .flavor-theme-card__preview {
                height: 120px;
                position: relative;
                overflow: hidden;
                border-bottom: 1px solid #f3f4f6;
            }
            .flavor-theme-card__preview-header {
                height: 32px;
                width: 100%;
            }
            .flavor-theme-card__preview-body {
                padding: 10px 14px;
            }
            .flavor-theme-card__preview-title {
                height: 10px;
                width: 65%;
                border-radius: 3px;
                margin-bottom: 8px;
                opacity: 0.85;
            }
            .flavor-theme-card__preview-text {
                height: 6px;
                width: 90%;
                border-radius: 3px;
                margin-bottom: 5px;
                opacity: 0.35;
            }
            .flavor-theme-card__preview-text--short {
                width: 55%;
            }
            .flavor-theme-card__preview-btn {
                height: 14px;
                width: 50px;
                border-radius: 4px;
                margin-top: 8px;
                opacity: 0.9;
            }
            .flavor-theme-card__info {
                padding: 12px 14px;
            }
            .flavor-theme-card__name {
                font-weight: 600;
                font-size: 13px;
                color: #1f2937;
                margin-bottom: 2px;
            }
            .flavor-theme-card__desc {
                font-size: 11px;
                color: #9ca3af;
                line-height: 1.3;
                margin-bottom: 6px;
            }
            .flavor-theme-card__badge {
                display: inline-block;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 2px 8px;
                border-radius: 9999px;
                background: #dbeafe;
                color: #1d4ed8;
            }
            .flavor-theme-card__badge--custom {
                background: #fef3c7;
                color: #92400e;
            }
            .flavor-theme-card__badge--category {
                background: #f0fdf4;
                color: #166534;
                margin-right: 4px;
            }
            .flavor-theme-card__ideal-for {
                display: flex;
                align-items: center;
                margin-bottom: 6px;
                padding: 4px 0;
            }
            .flavor-category-filter {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
            }
            .flavor-category-select {
                min-width: 200px;
                padding: 6px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 14px;
                background: #fff;
            }
            .flavor-category-select:focus {
                border-color: #3b82f6;
                outline: none;
                box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
            }
            .flavor-themes-count {
                font-size: 13px;
                color: #6b7280;
            }
            .flavor-theme-feedback {
                margin-top: 16px;
                padding: 10px 16px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 500;
            }
            .flavor-theme-feedback--success {
                background: #ecfdf5;
                color: #065f46;
                border: 1px solid #a7f3d0;
            }
            .flavor-theme-feedback--error {
                background: #fef2f2;
                color: #991b1b;
                border: 1px solid #fecaca;
            }

            /* Design Settings Grid */
            .flavor-design-settings {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 40px;
                margin-top: 20px;
            }
            .flavor-design-preview {
                position: sticky;
                top: 32px;
                background: white;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 8px;
                height: fit-content;
            }
            .flavor-design-actions {
                margin-top: 40px;
                padding: 20px;
                background: #f9fafb;
                border-radius: 8px;
            }
            .flavor-design-actions .button {
                margin-right: 10px;
            }
            @media (max-width: 1400px) {
                .flavor-design-settings {
                    grid-template-columns: 1fr;
                }
                .flavor-design-preview {
                    position: relative;
                    top: 0;
                }
            }
            @media (max-width: 600px) {
                .flavor-themes-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            /* Export Tokens Section */
            .flavor-export-tokens-wrapper {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
            }
            .flavor-export-tokens-wrapper h2 {
                margin-top: 0;
                margin-bottom: 4px;
            }
            .flavor-export-tokens-wrapper > .description {
                margin-bottom: 0;
                color: #666;
            }
            .flavor-tokens-preview {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                overflow: hidden;
            }
            .flavor-tokens-preview-header {
                background: #f9fafb;
                padding: 8px 12px;
                border-bottom: 1px solid #e5e7eb;
            }
            .flavor-tokens-code {
                margin: 0;
                border-radius: 0 0 8px 8px;
            }
            .flavor-tokens-code::-webkit-scrollbar {
                height: 8px;
                width: 8px;
            }
            .flavor-tokens-code::-webkit-scrollbar-track {
                background: #313244;
            }
            .flavor-tokens-code::-webkit-scrollbar-thumb {
                background: #585b70;
                border-radius: 4px;
            }
        ');
    }

    /**
     * Sanitizar settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        // Campos de color
        $color_fields = ['primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'error_color', 'background_color', 'text_color', 'text_muted_color'];
        foreach ($color_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_hex_color($input[$field]);
            }
        }

        // Campos numéricos
        $number_fields = ['font_size_base', 'font_size_h1', 'font_size_h2', 'font_size_h3', 'line_height_base', 'line_height_headings', 'container_max_width', 'section_padding_y', 'section_padding_x', 'grid_gap', 'card_padding', 'button_border_radius', 'button_padding_y', 'button_padding_x', 'button_font_size', 'button_font_weight', 'card_border_radius', 'hero_overlay_opacity', 'image_border_radius'];
        foreach ($number_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = floatval($input[$field]);
            }
        }

        // Campos de texto
        $text_fields = ['font_family_headings', 'font_family_body', 'card_shadow'];
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Obtener settings actuales
     *
     * Prioridad:
     * 1. Configuración guardada manualmente (flavor_design_settings)
     * 2. Valores del tema activo (del Theme Manager)
     * 3. Valores por defecto
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $saved = get_option(self::OPTION_NAME, []);

        // Si hay configuración guardada, usarla
        if (!empty($saved)) {
            return wp_parse_args($saved, $defaults);
        }

        // Si no hay configuración guardada, obtener valores del tema activo
        $theme_values = $this->get_theme_values_as_settings();

        return wp_parse_args($theme_values, $defaults);
    }

    /**
     * Obtener los valores del tema activo convertidos a formato de settings
     *
     * @return array Settings derivados del tema activo
     */
    private function get_theme_values_as_settings() {
        // Verificar si el Theme Manager está disponible
        if (!class_exists('Flavor_Theme_Manager')) {
            return [];
        }

        $theme_manager = Flavor_Theme_Manager::get_instance();
        $active_theme_id = get_option('flavor_active_theme', 'default');
        $theme = $theme_manager->get_theme($active_theme_id);

        if (!$theme || empty($theme['variables'])) {
            return [];
        }

        $variables = $theme['variables'];

        // Mapeo de variables CSS del tema a campos de settings
        $settings = [];

        // Colores
        if (isset($variables['--flavor-primary'])) {
            $settings['primary_color'] = $variables['--flavor-primary'];
        }
        if (isset($variables['--flavor-secondary'])) {
            $settings['secondary_color'] = $variables['--flavor-secondary'];
        }
        if (isset($variables['--flavor-accent']) || isset($variables['--flavor-warning'])) {
            $settings['accent_color'] = $variables['--flavor-accent'] ?? $variables['--flavor-warning'];
        }
        if (isset($variables['--flavor-success'])) {
            $settings['success_color'] = $variables['--flavor-success'];
        }
        if (isset($variables['--flavor-warning'])) {
            $settings['warning_color'] = $variables['--flavor-warning'];
        }
        if (isset($variables['--flavor-error'])) {
            $settings['error_color'] = $variables['--flavor-error'];
        }
        if (isset($variables['--flavor-bg'])) {
            $settings['background_color'] = $variables['--flavor-bg'];
        }
        if (isset($variables['--flavor-text'])) {
            $settings['text_color'] = $variables['--flavor-text'];
        }
        if (isset($variables['--flavor-text-muted']) || isset($variables['--flavor-text-secondary'])) {
            $settings['text_muted_color'] = $variables['--flavor-text-muted'] ?? $variables['--flavor-text-secondary'];
        }

        // Bordes redondeados
        if (isset($variables['--flavor-radius'])) {
            $radius = $this->parse_css_value($variables['--flavor-radius']);
            $settings['button_border_radius'] = $radius;
            $settings['image_border_radius'] = $radius;
        }
        if (isset($variables['--flavor-radius-lg'])) {
            $settings['card_border_radius'] = $this->parse_css_value($variables['--flavor-radius-lg']);
        }

        return $settings;
    }

    /**
     * Parsear valor CSS a número
     *
     * @param string $value Valor CSS (ej: "12px", "1rem")
     * @return float Valor numérico
     */
    private function parse_css_value($value) {
        // Extraer el número del valor CSS
        preg_match('/^([\d.]+)/', $value, $matches);
        return isset($matches[1]) ? floatval($matches[1]) : 0;
    }

    /**
     * Obtener settings por defecto
     */
    public function get_default_settings() {
        return [
            // Colores
            'primary_color' => '#3b82f6',
            'secondary_color' => '#8b5cf6',
            'accent_color' => '#f59e0b',
            'success_color' => '#10b981',
            'warning_color' => '#f59e0b',
            'error_color' => '#ef4444',
            'background_color' => '#ffffff',
            'text_color' => '#1f2937',
            'text_muted_color' => '#6b7280',
            // Tipografía
            'font_family_headings' => 'Inter',
            'font_family_body' => 'Inter',
            'font_size_base' => 16,
            'font_size_h1' => 48,
            'font_size_h2' => 36,
            'font_size_h3' => 28,
            'line_height_base' => 1.5,
            'line_height_headings' => 1.2,
            // Espaciados
            'container_max_width' => 1280,
            'section_padding_y' => 80,
            'section_padding_x' => 20,
            'grid_gap' => 24,
            'card_padding' => 24,
            // Botones
            'button_border_radius' => 8,
            'button_padding_y' => 12,
            'button_padding_x' => 24,
            'button_font_size' => 16,
            'button_font_weight' => 600,
            // Componentes
            'card_border_radius' => 12,
            'card_shadow' => 'medium',
            'hero_overlay_opacity' => 0.6,
            'image_border_radius' => 8,
        ];
    }

    /**
     * Output CSS personalizado en el frontend
     * Siempre genera las variables CSS (usa defaults si no hay ajustes guardados)
     */
    public function output_custom_css() {
        // Obtener settings (con defaults si no hay guardados)
        $settings = $this->get_settings();

        // Cargar Google Fonts si están seleccionadas
        $fonts_to_load = [];
        if (!empty($settings['font_family_headings']) && $settings['font_family_headings'] !== 'Sistema por defecto') {
            $fonts_to_load[] = str_replace(' ', '+', $settings['font_family_headings']) . ':400,600,700';
        }
        if (!empty($settings['font_family_body']) && $settings['font_family_body'] !== 'Sistema por defecto' && $settings['font_family_body'] !== $settings['font_family_headings']) {
            $fonts_to_load[] = str_replace(' ', '+', $settings['font_family_body']) . ':400,600';
        }

        if (!empty($fonts_to_load)) {
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            echo '<link href="https://fonts.googleapis.com/css2?family=' . implode('&family=', $fonts_to_load) . '&display=swap" rel="stylesheet">' . "\n";
        }

        // CSS personalizado
        $css = $this->generate_custom_css($settings);
        echo '<style id="flavor-design-custom-css">' . $css . '</style>' . "\n";
    }

    /**
     * Generar CSS personalizado
     */
    private function generate_custom_css($settings) {
        ob_start();
        ?>
:root {
    /* Colores */
    --flavor-primary: <?php echo esc_attr($settings['primary_color']); ?>;
    --flavor-secondary: <?php echo esc_attr($settings['secondary_color']); ?>;
    --flavor-accent: <?php echo esc_attr($settings['accent_color']); ?>;
    --flavor-success: <?php echo esc_attr($settings['success_color']); ?>;
    --flavor-warning: <?php echo esc_attr($settings['warning_color']); ?>;
    --flavor-error: <?php echo esc_attr($settings['error_color']); ?>;
    --flavor-bg: <?php echo esc_attr($settings['background_color']); ?>;
    --flavor-text: <?php echo esc_attr($settings['text_color']); ?>;
    --flavor-text-muted: <?php echo esc_attr($settings['text_muted_color']); ?>;

    /* Tipografía */
    --flavor-font-headings: <?php echo !empty($settings['font_family_headings']) ? '"' . esc_attr($settings['font_family_headings']) . '", ' : ''; ?>-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --flavor-font-body: <?php echo !empty($settings['font_family_body']) ? '"' . esc_attr($settings['font_family_body']) . '", ' : ''; ?>-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --flavor-font-size-base: <?php echo esc_attr($settings['font_size_base']); ?>px;
    --flavor-font-size-h1: <?php echo esc_attr($settings['font_size_h1']); ?>px;
    --flavor-font-size-h2: <?php echo esc_attr($settings['font_size_h2']); ?>px;
    --flavor-font-size-h3: <?php echo esc_attr($settings['font_size_h3']); ?>px;
    --flavor-line-height-base: <?php echo esc_attr($settings['line_height_base']); ?>;
    --flavor-line-height-headings: <?php echo esc_attr($settings['line_height_headings']); ?>;

    /* Espaciados */
    --flavor-container-max: <?php echo esc_attr($settings['container_max_width']); ?>px;
    --flavor-section-py: <?php echo esc_attr($settings['section_padding_y']); ?>px;
    --flavor-section-px: <?php echo esc_attr($settings['section_padding_x']); ?>px;
    --flavor-grid-gap: <?php echo esc_attr($settings['grid_gap']); ?>px;
    --flavor-card-padding: <?php echo esc_attr($settings['card_padding']); ?>px;

    /* Botones */
    --flavor-button-radius: <?php echo esc_attr($settings['button_border_radius']); ?>px;
    --flavor-button-py: <?php echo esc_attr($settings['button_padding_y']); ?>px;
    --flavor-button-px: <?php echo esc_attr($settings['button_padding_x']); ?>px;
    --flavor-button-font-size: <?php echo esc_attr($settings['button_font_size']); ?>px;
    --flavor-button-weight: <?php echo esc_attr($settings['button_font_weight']); ?>;

    /* Componentes */
    --flavor-card-radius: <?php echo esc_attr($settings['card_border_radius']); ?>px;
    --flavor-card-shadow: <?php echo $this->get_shadow_value($settings['card_shadow']); ?>;
    --flavor-hero-overlay: <?php echo esc_attr($settings['hero_overlay_opacity']); ?>;
    --flavor-image-radius: <?php echo esc_attr($settings['image_border_radius']); ?>px;
}

/* Aplicar estilos globales */
.flavor-component {
    font-family: var(--flavor-font-body);
    font-size: var(--flavor-font-size-base);
    line-height: var(--flavor-line-height-base);
    color: var(--flavor-text);
}

.flavor-component h1,
.flavor-component h2,
.flavor-component h3,
.flavor-component h4,
.flavor-component h5,
.flavor-component h6 {
    font-family: var(--flavor-font-headings);
    line-height: var(--flavor-line-height-headings);
}

.flavor-component h1 { font-size: var(--flavor-font-size-h1); }
.flavor-component h2 { font-size: var(--flavor-font-size-h2); }
.flavor-component h3 { font-size: var(--flavor-font-size-h3); }

.flavor-container {
    max-width: var(--flavor-container-max);
    margin-left: auto;
    margin-right: auto;
    padding-left: var(--flavor-section-px);
    padding-right: var(--flavor-section-px);
}

.flavor-section {
    padding-top: var(--flavor-section-py);
    padding-bottom: var(--flavor-section-py);
}

.flavor-grid {
    display: grid;
    gap: var(--flavor-grid-gap);
}

.flavor-card {
    padding: var(--flavor-card-padding);
    border-radius: var(--flavor-card-radius);
    box-shadow: var(--flavor-card-shadow);
}

.flavor-button,
.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--flavor-button-py) var(--flavor-button-px);
    border-radius: var(--flavor-button-radius);
    font-size: var(--flavor-button-font-size);
    font-weight: var(--flavor-button-weight);
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.flavor-button-primary {
    background-color: var(--flavor-primary);
    color: white;
}

.flavor-button-primary:hover {
    opacity: 0.9;
}

.flavor-button-secondary {
    background-color: var(--flavor-secondary);
    color: white;
}

.flavor-button-secondary:hover {
    opacity: 0.9;
}

.flavor-image {
    border-radius: var(--flavor-image-radius);
}
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener valor de sombra CSS
     */
    private function get_shadow_value($shadow) {
        $shadows = [
            'none' => 'none',
            'small' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'medium' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            'large' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        ];

        return $shadows[$shadow] ?? $shadows['medium'];
    }
}

// Inicializar
Flavor_Design_Settings::get_instance();

/**
 * Helper function para obtener un setting de diseño
 *
 * @param string $key Clave del setting
 * @param mixed $default Valor por defecto si no existe
 * @return mixed
 */
function flavor_design_get($key, $default = null) {
    $settings = Flavor_Design_Settings::get_instance()->get_settings();
    return $settings[$key] ?? $default;
}

/**
 * Helper function para obtener todos los settings de diseño
 *
 * @return array
 */
function flavor_design_get_all() {
    return Flavor_Design_Settings::get_instance()->get_settings();
}

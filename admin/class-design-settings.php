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
     * Obtener módulos activos con sus metadatos (id, nombre, color)
     *
     * @return array Array de módulos con id, name, color
     */
    public function get_active_modules_with_colors() {
        $modulos_con_colores = [];

        // Intentar obtener desde Module Loader
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $metadata_cache = $loader->rebuild_metadata_cache();

            foreach ($metadata_cache as $module_id => $meta) {
                $modulos_con_colores[$module_id] = [
                    'id' => $module_id,
                    'name' => $meta['name'] ?? ucfirst(str_replace('_', ' ', $module_id)),
                    'color' => $meta['color'] ?? '#3b82f6',
                    'icon' => $meta['icon'] ?? 'dashicons-admin-plugins',
                ];
            }
        }

        // Ordenar alfabéticamente por nombre
        uasort($modulos_con_colores, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $modulos_con_colores;
    }

    /**
     * Añadir página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=flavor_landing',
            __('Diseño y Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            __('Colores Principales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_colors_section'],
            self::PAGE_SLUG
        );

        // Sección: Tipografía
        add_settings_section(
            'typography_section',
            __('Tipografía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_typography_section'],
            self::PAGE_SLUG
        );

        // Sección: Espaciados
        add_settings_section(
            'spacing_section',
            __('Espaciados y Layout', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_spacing_section'],
            self::PAGE_SLUG
        );

        // Sección: Botones
        add_settings_section(
            'buttons_section',
            __('Botones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_buttons_section'],
            self::PAGE_SLUG
        );

        // Sección: Componentes
        add_settings_section(
            'components_section',
            __('Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_components_section'],
            self::PAGE_SLUG
        );

        // Sección: Header y Footer
        add_settings_section(
            'layout_section',
            __('Header y Footer', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_layout_section'],
            self::PAGE_SLUG
        );

        // Sección: Portal Unificado
        add_settings_section(
            'portal_section',
            __('Portal Unificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_portal_section'],
            self::PAGE_SLUG
        );

        // Sección: Módulos y Dashboards
        add_settings_section(
            'modules_section',
            __('Modulos y Dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_modules_section'],
            self::PAGE_SLUG
        );

        // Sección: Colores de Módulos Activos
        add_settings_section(
            'module_colors_section',
            __('Colores de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_module_colors_section'],
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
            'primary_color' => ['label' => __('Color Primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
            'secondary_color' => ['label' => __('Color Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#8b5cf6'],
            'accent_color' => ['label' => __('Color de Acento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f59e0b'],
            'success_color' => ['label' => __('Color Éxito', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#10b981'],
            'warning_color' => ['label' => __('Color Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f59e0b'],
            'error_color' => ['label' => __('Color Error', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ef4444'],
            'background_color' => ['label' => __('Color de Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ffffff'],
            'text_color' => ['label' => __('Color de Texto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#1f2937'],
            'text_muted_color' => ['label' => __('Color Texto Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#6b7280'],
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
            'font_family_headings' => ['label' => __('Fuente Títulos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select'],
            'font_family_body' => ['label' => __('Fuente Cuerpo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select'],
            'font_size_base' => ['label' => __('Tamaño Base', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'suffix' => 'px', 'default' => '16'],
            'font_size_h1' => ['label' => __('Tamaño H1', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'suffix' => 'px', 'default' => '48'],
            'font_size_h2' => ['label' => __('Tamaño H2', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'suffix' => 'px', 'default' => '36'],
            'font_size_h3' => ['label' => __('Tamaño H3', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'suffix' => 'px', 'default' => '28'],
            'line_height_base' => ['label' => __('Interlineado Base', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'step' => '0.1', 'default' => '1.5'],
            'line_height_headings' => ['label' => __('Interlineado Títulos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'number', 'step' => '0.1', 'default' => '1.2'],
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
            'container_max_width' => ['label' => __('Ancho Máximo Contenedor', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '1280', 'suffix' => 'px'],
            'section_padding_y' => ['label' => __('Padding Vertical Sección', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '80', 'suffix' => 'px'],
            'section_padding_x' => ['label' => __('Padding Horizontal Sección', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '20', 'suffix' => 'px'],
            'grid_gap' => ['label' => __('Espaciado Grid', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '24', 'suffix' => 'px'],
            'card_padding' => ['label' => __('Padding Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '24', 'suffix' => 'px'],
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
            'button_border_radius' => ['label' => __('Radio Bordes Botones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '8', 'suffix' => 'px'],
            'button_padding_y' => ['label' => __('Padding Vertical Botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '12', 'suffix' => 'px'],
            'button_padding_x' => ['label' => __('Padding Horizontal Botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '24', 'suffix' => 'px'],
            'button_font_size' => ['label' => __('Tamaño Texto Botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '16', 'suffix' => 'px'],
            'button_font_weight' => ['label' => __('Grosor Texto Botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '600'],
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
            'card_border_radius' => ['label' => __('Radio Bordes Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '12', 'suffix' => 'px'],
            'card_shadow' => ['label' => __('Sombra Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select'],
            'hero_overlay_opacity' => ['label' => __('Opacidad Overlay Hero', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '0.6', 'step' => '0.1', 'max' => '1'],
            'image_border_radius' => ['label' => __('Radio Bordes Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '8', 'suffix' => 'px'],
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

        // HEADER Y FOOTER
        $layout_fields = [
            'header_bg_color' => ['label' => __('Fondo Header', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ffffff'],
            'header_text_color' => ['label' => __('Texto Header', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#1f2937'],
            'footer_bg_color' => ['label' => __('Fondo Footer (Dark)', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#1f2937'],
            'footer_text_color' => ['label' => __('Texto Footer (Dark)', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ffffff'],
        ];

        foreach ($layout_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_color_field'],
                self::PAGE_SLUG,
                'layout_section',
                ['field' => $field, 'default' => $config['default']]
            );
        }

        // PORTAL UNIFICADO
        add_settings_field(
            'portal_layout',
            __('Layout del Portal', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_portal_layout_field'],
            self::PAGE_SLUG,
            'portal_section',
            ['field' => 'portal_layout', 'default' => 'legacy']
        );

        // MÓDULOS Y DASHBOARDS
        $module_fields = [
            'module_card_style' => [
                'label' => __('Estilo Tarjetas Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type' => 'select',
                'options' => [
                    'elevated' => __('Elevadas (con sombra)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'outlined' => __('Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'flat' => __('Planas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'glass' => __('Glassmorphism', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'default' => 'elevated',
            ],
            'module_icon_style' => [
                'label' => __('Estilo Iconos Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type' => 'select',
                'options' => [
                    'filled' => __('Relleno color', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'outlined' => __('Solo contorno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'gradient' => __('Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'minimal' => __('Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'default' => 'filled',
            ],
            'module_border_style' => [
                'label' => __('Indicador de Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type' => 'select',
                'options' => [
                    'left' => __('Borde izquierdo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'top' => __('Borde superior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'full' => __('Borde completo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'none' => __('Sin indicador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'default' => 'left',
            ],
            'dashboard_density' => [
                'label' => __('Densidad Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type' => 'select',
                'options' => [
                    'compact' => __('Compacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'normal' => __('Normal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'comfortable' => __('Espacioso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'default' => 'normal',
            ],
            'widget_animations' => [
                'label' => __('Animaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type' => 'select',
                'options' => [
                    'all' => __('Todas habilitadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'minimal' => __('Solo hover', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'none' => __('Sin animaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'default' => 'all',
            ],
        ];

        foreach ($module_fields as $field => $config) {
            add_settings_field(
                $field,
                $config['label'],
                [$this, 'render_select_field'],
                self::PAGE_SLUG,
                'modules_section',
                ['field' => $field, 'options' => $config['options'], 'default' => $config['default']]
            );
        }

        // Campo de color global para botones primarios del dashboard
        add_settings_field(
            'dashboard_btn_primary_color',
            __('Color Fondo Botones (Global)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_color_field'],
            self::PAGE_SLUG,
            'modules_section',
            ['field' => 'dashboard_btn_primary_color', 'default' => '']
        );

        // Campo de color de texto para botones del dashboard
        add_settings_field(
            'dashboard_btn_text_color',
            __('Color Texto Botones (Global)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_color_field'],
            self::PAGE_SLUG,
            'modules_section',
            ['field' => 'dashboard_btn_text_color', 'default' => '#ffffff']
        );
    }

    /**
     * Renderizar campo select genérico
     */
    public function render_select_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? $args['default'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>">
            <?php foreach ($args['options'] as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Renderizar campo de layout del portal
     */
    public function render_portal_layout_field($args) {
        $settings = $this->get_settings();
        $value = $settings[$args['field']] ?? $args['default'];

        // Obtener layouts disponibles
        $layouts = [];
        if (class_exists('Flavor_Unified_Portal')) {
            $layouts = Flavor_Unified_Portal::get_available_layouts();
        } else {
            $layouts = [
                'legacy'      => __('Legacy (Original)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ecosystem'   => __('Ecosistema (Jerárquico)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cards'       => __('Cards (Grid modular)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sidebar'     => __('Sidebar (Panel lateral)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'compact'     => __('Compacto (Lista)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dashboard'   => __('Dashboard (Widgets)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        ?>
        <div class="flavor-portal-layout-selector">
            <?php foreach ($layouts as $layout_id => $layout_label): ?>
            <label class="flavor-layout-option<?php echo $value === $layout_id ? ' flavor-layout-option--active' : ''; ?>">
                <input type="radio"
                       name="<?php echo esc_attr(self::OPTION_NAME . '[portal_layout]'); ?>"
                       value="<?php echo esc_attr($layout_id); ?>"
                       <?php checked($value, $layout_id); ?>>
                <div class="flavor-layout-option__preview flavor-layout-option__preview--<?php echo esc_attr($layout_id); ?>">
                    <?php $this->render_layout_preview_icon($layout_id); ?>
                </div>
                <span class="flavor-layout-option__label"><?php echo esc_html($layout_label); ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <style>
            .flavor-portal-layout-selector {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
                margin-top: 8px;
            }
            .flavor-layout-option {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .flavor-layout-option:hover {
                border-color: #93c5fd;
            }
            .flavor-layout-option--active {
                border-color: #3b82f6;
                background: #eff6ff;
            }
            .flavor-layout-option input {
                display: none;
            }
            .flavor-layout-option__preview {
                width: 80px;
                height: 60px;
                background: #f9fafb;
                border-radius: 4px;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .flavor-layout-option__preview svg {
                width: 50px;
                height: 40px;
                color: #6b7280;
            }
            .flavor-layout-option--active .flavor-layout-option__preview svg {
                color: #3b82f6;
            }
            .flavor-layout-option__label {
                font-size: 12px;
                font-weight: 500;
                color: #374151;
                text-align: center;
            }
        </style>
        <script>
        (function() {
            document.querySelectorAll('.flavor-portal-layout-selector .flavor-layout-option input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Quitar clase activa de todas las opciones
                    document.querySelectorAll('.flavor-portal-layout-selector .flavor-layout-option').forEach(function(opt) {
                        opt.classList.remove('flavor-layout-option--active');
                    });
                    // Añadir clase activa a la opción seleccionada
                    if (this.checked) {
                        this.closest('.flavor-layout-option').classList.add('flavor-layout-option--active');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderizar icono de preview del layout
     */
    private function render_layout_preview_icon($layout_id) {
        $icons = [
            'ecosystem' => '<svg viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="5" y="2" width="40" height="6" rx="1"/>
                <rect x="5" y="12" width="18" height="12" rx="1"/>
                <rect x="27" y="12" width="18" height="12" rx="1"/>
                <rect x="5" y="28" width="10" height="8" rx="1"/>
                <rect x="20" y="28" width="10" height="8" rx="1"/>
                <rect x="35" y="28" width="10" height="8" rx="1"/>
            </svg>',
            'cards' => '<svg viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="12" height="16" rx="1"/>
                <rect x="19" y="3" width="12" height="16" rx="1"/>
                <rect x="35" y="3" width="12" height="16" rx="1"/>
                <rect x="3" y="23" width="12" height="14" rx="1"/>
                <rect x="19" y="23" width="12" height="14" rx="1"/>
                <rect x="35" y="23" width="12" height="14" rx="1"/>
            </svg>',
            'sidebar' => '<svg viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="12" height="34" rx="1"/>
                <rect x="18" y="3" width="29" height="34" rx="1"/>
                <line x1="6" y1="8" x2="12" y2="8"/>
                <line x1="6" y1="14" x2="12" y2="14"/>
                <line x1="6" y1="20" x2="12" y2="20"/>
            </svg>',
            'compact' => '<svg viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="5" y="4" width="40" height="8" rx="1"/>
                <rect x="5" y="16" width="40" height="8" rx="1"/>
                <rect x="5" y="28" width="40" height="8" rx="1"/>
            </svg>',
            'dashboard' => '<svg viewBox="0 0 50 40" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="28" height="16" rx="1"/>
                <rect x="35" y="3" width="12" height="16" rx="1"/>
                <rect x="3" y="23" width="12" height="14" rx="1"/>
                <rect x="19" y="23" width="12" height="14" rx="1"/>
                <rect x="35" y="23" width="12" height="14" rx="1"/>
            </svg>',
        ];

        echo $icons[$layout_id] ?? '';
    }

    /**
     * Renderizar secciones
     */
    public function render_colors_section() {
        // Obtener tema activo del Theme Manager
        $active_theme_id = get_option('flavor_active_theme', 'default');
        $theme_manager = class_exists('Flavor_Theme_Manager') ? Flavor_Theme_Manager::get_instance() : null;
        $theme_name = $active_theme_id;

        if ($theme_manager) {
            $theme = $theme_manager->get_theme($active_theme_id);
            $theme_name = $theme['name'] ?? $active_theme_id;
        }
        ?>
        <div class="flavor-colors-intro" style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <p style="margin: 0 0 12px;">
                    <?php _e('Configura la paleta de colores principal. Estos colores se aplican a:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <ul style="margin: 0 0 12px; padding-left: 20px; font-size: 13px; color: #6b7280;">
                    <li><?php _e('Botones, enlaces y elementos interactivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('Tarjetas de módulos y widgets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('Badges, alertas y notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php _e('Headers, footers y navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                </ul>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; min-width: 220px;">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php _e('Tema activo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div style="font-weight: 600; color: #1e293b;"><?php echo esc_html($theme_name); ?></div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                    <?php _e('Los valores aqui sobreescriben los del tema.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_typography_section() {
        echo '<p>' . __('Configura las fuentes y tamaños de texto.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_spacing_section() {
        echo '<p>' . __('Configura espaciados, anchos y márgenes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_buttons_section() {
        echo '<p>' . __('Configura el estilo de los botones.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_components_section() {
        echo '<p>' . __('Configuración específica de componentes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_layout_section() {
        echo '<p>' . __('Personaliza los colores del header y footer de tu sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_portal_section() {
        echo '<p>' . __('Configura la apariencia del portal unificado de módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    public function render_modules_section() {
        ?>
        <div class="flavor-section-intro" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 16px 20px; border-radius: 10px; margin-bottom: 16px; border-left: 4px solid #0ea5e9;">
            <p style="margin: 0 0 10px; font-size: 14px; color: #0c4a6e;">
                <strong><?php _e('Estos estilos se aplican globalmente a:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            </p>
            <ul style="margin: 0; padding-left: 20px; color: #0369a1; font-size: 13px;">
                <li><?php _e('Tarjetas de módulos en el portal unificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Widgets del dashboard de usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Listas de módulos en la navegación lateral', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Paneles de administración de cada módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Renderizar sección de colores de módulos activos
     */
    public function render_module_colors_section() {
        ?>
        <div class="flavor-section-intro" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 16px 20px; border-radius: 10px; margin-bottom: 16px; border-left: 4px solid #f59e0b;">
            <p style="margin: 0; font-size: 14px; color: #92400e;">
                <strong><?php _e('Personaliza el color de cada módulo activo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <?php _e('Estos colores se usan en botones, iconos y elementos destacados de cada módulo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <?php
        $this->render_module_color_fields();
    }

    /**
     * Renderizar campos de color para cada módulo activo
     */
    private function render_module_color_fields() {
        $modulos = $this->get_active_modules_with_colors();
        $settings = $this->get_settings();

        if (empty($modulos)) {
            echo '<p class="description">' . __('No hay módulos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        echo '<div class="flavor-module-colors-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 16px;">';

        foreach ($modulos as $module_id => $module_data) {
            $field_name = 'module_color_' . $module_id;
            $default_color = $module_data['color'];
            $current_color = $settings[$field_name] ?? $default_color;
            $icon_class = $module_data['icon'];
            ?>
            <div class="flavor-module-color-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div class="flavor-module-color-preview" style="width: 48px; height: 48px; border-radius: 10px; background: <?php echo esc_attr($current_color); ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="dashicons <?php echo esc_attr($icon_class); ?>" style="color: #fff; font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <div class="flavor-module-color-info" style="flex: 1; min-width: 0;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 6px; font-size: 13px;">
                        <?php echo esc_html($module_data['name']); ?>
                    </label>
                    <input
                        type="text"
                        name="<?php echo esc_attr(self::OPTION_NAME . '[' . $field_name . ']'); ?>"
                        value="<?php echo esc_attr($current_color); ?>"
                        class="flavor-color-picker flavor-module-color-input"
                        data-default-color="<?php echo esc_attr($default_color); ?>"
                        data-module-id="<?php echo esc_attr($module_id); ?>"
                        style="width: 100%;"
                    />
                </div>
            </div>
            <?php
        }

        echo '</div>';

        // Script para actualizar preview en tiempo real
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.flavor-module-color-input').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    $(this).closest('.flavor-module-color-card').find('.flavor-module-color-preview').css('background', color);
                }
            });
        });
        </script>
        <?php
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
                <option value=""><?php _e('Sistema por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
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
            'none' => __('Sin sombra', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'small' => __('Pequeña', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'medium' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'large' => __('Grande', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'xl' => __('Extra Grande', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                <h2><?php _e('Temas Predefinidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="description"><?php _e('Selecciona un tema base por sector. Los ajustes manuales de abajo sobreescriben los valores del tema.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <!-- Filtro por Categoría -->
                <div id="flavor-category-filter" class="flavor-category-filter" style="margin: 16px 0 20px;">
                    <label for="flavor-category-select" style="margin-right: 10px; font-weight: 500;">
                        <?php _e('Filtrar por sector:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <select id="flavor-category-select" class="flavor-category-select">
                        <option value="all"><?php _e('Todos los temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                    <span id="flavor-themes-count" class="flavor-themes-count" style="margin-left: 12px; color: #6b7280; font-size: 13px;"></span>
                </div>

                <div id="flavor-themes-grid" class="flavor-themes-grid">
                    <p class="flavor-themes-loading"><?php _e('Cargando temas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div id="flavor-theme-feedback" class="flavor-theme-feedback" style="display:none;"></div>
            </div>

            <!-- Importar Plantillas Web -->
            <div class="flavor-web-templates-wrapper" style="margin-top:30px;">
                <h2><?php _e('Plantillas de Sitios Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="flavor-web-templates-info" style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 8px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-info" style="font-size: 18px;"></span>
                        <?php _e('Que hacen las plantillas web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 13px; line-height: 1.6;">
                        <li><strong><?php _e('Importan paginas completas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Home, Servicios, Contacto, etc. como Landing Pages en borrador.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php _e('Activan el tema correspondiente:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Cada plantilla tiene un tema de colores asociado que se aplica automaticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php _e('Incluyen contenido de ejemplo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Textos, imagenes y estructura listos para personalizar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php _e('Son editables:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Puedes modificar todo desde el Visual Builder despues de importar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
                <p class="description"><?php _e('Elige una plantilla segun el tipo de proyecto. Las paginas se crean en borrador para que puedas revisarlas antes de publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
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
                                <?php _e('Importar Sitio Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                    submit_button(__('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN));
                    ?>
                </form>

                <div class="flavor-design-preview">
                    <h2><?php _e('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div class="flavor-preview-content">
                        <?php $this->render_preview(); ?>
                    </div>
                </div>
            </div>

            <div class="flavor-design-actions">
                <h3><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-starter-theme-action" style="margin-bottom: 10px;">
                    <button type="button" class="button button-primary" id="flavor-install-starter-theme">
                        <?php _e('Instalar y activar tema Flavor Starter', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <span id="flavor-starter-theme-status" style="margin-left:10px;color:#6b7280;font-size:12px;"></span>
                </div>
                <button type="button" class="button" id="flavor-reset-defaults">
                    <?php _e('Restaurar Valores por Defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-export-settings">
                    <?php _e('Exportar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="flavor-import-settings">
                    <?php _e('Importar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- Exportar Design Tokens -->
            <div class="flavor-export-tokens-wrapper" style="margin-top:30px;">
                <h2><?php _e('Exportar Design Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="description"><?php _e('Exporta tus tokens de diseño en diferentes formatos para usarlos en otros proyectos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="flavor-export-tokens-controls" style="display:flex;gap:16px;align-items:flex-start;margin-top:16px;">
                    <div style="flex:0 0 auto;">
                        <label for="flavor-token-format" style="display:block;margin-bottom:6px;font-weight:500;">
                            <?php _e('Formato:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="flavor-token-format" class="flavor-category-select" style="min-width:200px;">
                            <option value="w3c"><?php _e('W3C Design Tokens (JSON)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="css"><?php _e('CSS Custom Properties', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="js"><?php _e('JavaScript/TypeScript', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="tailwind"><?php _e('Tailwind Config', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div style="flex:0 0 auto;padding-top:24px;">
                        <button type="button" class="button" id="flavor-preview-tokens">
                            <span class="dashicons dashicons-visibility" style="margin-top:3px;"></span>
                            <?php _e('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="button button-primary" id="flavor-download-tokens">
                            <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                            <?php _e('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <div id="flavor-tokens-preview" class="flavor-tokens-preview" style="display:none;margin-top:20px;">
                    <div class="flavor-tokens-preview-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span class="flavor-tokens-filename" style="font-family:monospace;font-size:13px;color:#6b7280;"></span>
                        <button type="button" class="button button-small" id="flavor-copy-tokens">
                            <span class="dashicons dashicons-clipboard" style="font-size:14px;width:14px;height:14px;margin-top:2px;"></span>
                            <?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                    starterStatus.textContent = '<?php echo esc_js(__('Instalando y activando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
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
                        html += '  <span class="flavor-theme-card__badge"><?php echo esc_js(__('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>';
                    }
                    if (tema.is_custom) {
                        html += '  <span class="flavor-theme-card__badge flavor-theme-card__badge--custom"><?php echo esc_js(__('Custom', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>';
                    }
                    html += '</div>';

                    html += '</div>';
                }

                gridEl.innerHTML = html;

                // Actualizar contador
                themesCountEl.textContent = contadorTemas + ' <?php echo esc_js(__('temas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

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
                            badgeActivo.textContent = '<?php echo esc_js(__('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                            infoEl.appendChild(badgeActivo);

                            mostrarFeedback('<?php echo esc_js(__('Tema aplicado correctamente. Recargando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
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
                        mostrarTokensFeedback('<?php echo esc_js(__('Archivo descargado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
                    });

                    setTimeout(function() { downloadTokensBtn.disabled = false; }, 5000);
                });
            }

            if (copyTokensBtn) {
                copyTokensBtn.addEventListener('click', function() {
                    var texto = tokensCode.textContent;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(texto).then(function() {
                            mostrarTokensFeedback('<?php echo esc_js(__('Código copiado al portapapeles', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
                        });
                    } else {
                        var textarea = document.createElement('textarea');
                        textarea.value = texto;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        mostrarTokensFeedback('<?php echo esc_js(__('Código copiado al portapapeles', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
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

                    if (!confirm('<?php echo esc_js(__('¿Importar todas las plantillas de este diseño? Se crearán como Landing Pages en borrador.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                        return;
                    }

                    btnEl.disabled = true;
                    btnEl.textContent = '<?php echo esc_js(__('Importando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

                    var formData = new FormData();
                    formData.append('action', 'flavor_import_theme_templates');
                    formData.append('nonce', nonce);
                    formData.append('sector_id', sectorId);

                    fetch(ajaxUrl, { method: 'POST', body: formData })
                        .then(function(r) { return r.json(); })
                        .then(function(respuesta) {
                            btnEl.disabled = false;
                            btnEl.innerHTML = '<span class="dashicons dashicons-download" style="margin-top:3px;"></span> <?php echo esc_js(__('Importar Sitio Completo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

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
                            btnEl.innerHTML = '<span class="dashicons dashicons-download" style="margin-top:3px;"></span> <?php echo esc_js(__('Importar Sitio Completo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
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
        $bg_color = $settings['background_color'] ?? '#ffffff';
        $primary_color = $settings['primary_color'] ?? '#3b82f6';
        $secondary_color = $settings['secondary_color'] ?? '#8b5cf6';
        $accent_color = $settings['accent_color'] ?? '#f59e0b';
        $success_color = $settings['success_color'] ?? '#10b981';
        $error_color = $settings['error_color'] ?? '#ef4444';
        $text_color = $settings['text_color'] ?? '#1f2937';
        $text_muted = $settings['text_muted_color'] ?? '#6b7280';
        $btn_radius = ($settings['button_border_radius'] ?? '8') . 'px';
        $card_radius = ($settings['card_border_radius'] ?? '12') . 'px';
        $btn_py = ($settings['button_padding_y'] ?? '12') . 'px';
        $btn_px = ($settings['button_padding_x'] ?? '24') . 'px';
        $btn_font = ($settings['button_font_size'] ?? '16') . 'px';
        $btn_weight = $settings['button_font_weight'] ?? '600';

        // Detectar si es tema oscuro
        $is_dark = $this->is_dark_color($bg_color);
        $card_bg = $is_dark ? $this->lighten_color($bg_color, 10) : '#ffffff';
        $border_color = $is_dark ? 'rgba(255,255,255,0.1)' : '#e5e7eb';
        ?>
        <div class="flavor-preview-section" id="flavor-live-preview" style="background: <?php echo esc_attr($bg_color); ?>; padding: 24px; border-radius: 12px; max-height: 600px; overflow-y: auto;">

            <!-- Tab Navigation -->
            <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid <?php echo esc_attr($border_color); ?>; padding-bottom: 12px;">
                <button type="button" class="flavor-preview-tab flavor-preview-tab--active" data-tab="general" style="background: <?php echo esc_attr($primary_color); ?>; color: white; border: none; padding: 8px 16px; border-radius: <?php echo $btn_radius; ?>; font-size: 13px; cursor: pointer;">
                    <?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="flavor-preview-tab" data-tab="cards" style="background: transparent; color: <?php echo esc_attr($text_muted); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; padding: 8px 16px; border-radius: <?php echo $btn_radius; ?>; font-size: 13px; cursor: pointer;">
                    <?php _e('Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="flavor-preview-tab" data-tab="forms" style="background: transparent; color: <?php echo esc_attr($text_muted); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; padding: 8px 16px; border-radius: <?php echo $btn_radius; ?>; font-size: 13px; cursor: pointer;">
                    <?php _e('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="flavor-preview-tab" data-tab="modules" style="background: transparent; color: <?php echo esc_attr($text_muted); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; padding: 8px 16px; border-radius: <?php echo $btn_radius; ?>; font-size: 13px; cursor: pointer;">
                    <?php _e('Modulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- Tab: General -->
            <div class="flavor-preview-tab-content" data-tab-content="general">
                <h1 style="color: <?php echo esc_attr($primary_color); ?>; font-size: <?php echo esc_attr(($settings['font_size_h1'] ?? '48') / 1.5); ?>px; margin: 0 0 12px 0;">
                    <?php _e('Titulo de Ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h1>
                <h2 style="color: <?php echo esc_attr($secondary_color); ?>; font-size: <?php echo esc_attr(($settings['font_size_h2'] ?? '36') / 1.5); ?>px; margin: 0 0 12px 0;">
                    <?php _e('Subtitulo de Ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p style="color: <?php echo esc_attr($text_color); ?>; font-size: <?php echo esc_attr($settings['font_size_base'] ?? '16'); ?>px; margin: 0 0 8px 0;">
                    <?php _e('Este es un parrafo de ejemplo para visualizar como se veran los textos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <p style="color: <?php echo esc_attr($text_muted); ?>; font-size: 14px; margin: 0 0 16px 0;">
                    <?php _e('Texto secundario o descripcion complementaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>

                <!-- Botones -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                    <button style="background: <?php echo esc_attr($primary_color); ?>; color: white; padding: <?php echo $btn_py; ?> <?php echo $btn_px; ?>; border-radius: <?php echo $btn_radius; ?>; font-size: <?php echo $btn_font; ?>; font-weight: <?php echo $btn_weight; ?>; border: none;">
                        <?php _e('Primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button style="background: <?php echo esc_attr($secondary_color); ?>; color: white; padding: <?php echo $btn_py; ?> <?php echo $btn_px; ?>; border-radius: <?php echo $btn_radius; ?>; font-size: <?php echo $btn_font; ?>; font-weight: <?php echo $btn_weight; ?>; border: none;">
                        <?php _e('Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button style="background: transparent; color: <?php echo esc_attr($primary_color); ?>; padding: <?php echo $btn_py; ?> <?php echo $btn_px; ?>; border-radius: <?php echo $btn_radius; ?>; font-size: <?php echo $btn_font; ?>; font-weight: <?php echo $btn_weight; ?>; border: 2px solid <?php echo esc_attr($primary_color); ?>;">
                        <?php _e('Outline', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <!-- Badges -->
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <span style="background: <?php echo esc_attr($primary_color); ?>20; color: <?php echo esc_attr($primary_color); ?>; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500;">Primario</span>
                    <span style="background: <?php echo esc_attr($success_color); ?>20; color: <?php echo esc_attr($success_color); ?>; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500;">Exito</span>
                    <span style="background: <?php echo esc_attr($accent_color); ?>20; color: <?php echo esc_attr($accent_color); ?>; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500;">Acento</span>
                    <span style="background: <?php echo esc_attr($error_color); ?>20; color: <?php echo esc_attr($error_color); ?>; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500;">Error</span>
                </div>
            </div>

            <!-- Tab: Tarjetas -->
            <div class="flavor-preview-tab-content" data-tab-content="cards" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <!-- Card 1 -->
                    <div style="background: <?php echo esc_attr($card_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $card_radius; ?>; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 40px; height: 40px; background: <?php echo esc_attr($primary_color); ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-calendar-alt" style="color: white;"></span>
                            </div>
                            <div>
                                <h4 style="margin: 0; color: <?php echo esc_attr($text_color); ?>; font-size: 14px;"><?php _e('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                                <span style="color: <?php echo esc_attr($text_muted); ?>; font-size: 12px;">3 <?php _e('activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <p style="margin: 0; color: <?php echo esc_attr($text_muted); ?>; font-size: 13px;"><?php _e('Proximos eventos de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>

                    <!-- Card 2 -->
                    <div style="background: <?php echo esc_attr($card_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $card_radius; ?>; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 40px; height: 40px; background: <?php echo esc_attr($secondary_color); ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-groups" style="color: white;"></span>
                            </div>
                            <div>
                                <h4 style="margin: 0; color: <?php echo esc_attr($text_color); ?>; font-size: 14px;"><?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                                <span style="color: <?php echo esc_attr($text_muted); ?>; font-size: 12px;">5 <?php _e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <p style="margin: 0; color: <?php echo esc_attr($text_muted); ?>; font-size: 13px;"><?php _e('Espacios colaborativos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>

                    <!-- Card 3: Stats -->
                    <div style="background: <?php echo esc_attr($card_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $card_radius; ?>; padding: 16px; grid-column: span 2;">
                        <h4 style="margin: 0 0 12px; color: <?php echo esc_attr($text_color); ?>; font-size: 14px;"><?php _e('Estadisticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div style="display: flex; gap: 24px;">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: <?php echo esc_attr($primary_color); ?>;">42</div>
                                <div style="font-size: 11px; color: <?php echo esc_attr($text_muted); ?>;"><?php _e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: <?php echo esc_attr($success_color); ?>;">18</div>
                                <div style="font-size: 11px; color: <?php echo esc_attr($text_muted); ?>;"><?php _e('Modulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: <?php echo esc_attr($accent_color); ?>;">127</div>
                                <div style="font-size: 11px; color: <?php echo esc_attr($text_muted); ?>;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Formularios -->
            <div class="flavor-preview-tab-content" data-tab-content="forms" style="display: none;">
                <div style="background: <?php echo esc_attr($card_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $card_radius; ?>; padding: 20px;">
                    <h4 style="margin: 0 0 16px; color: <?php echo esc_attr($text_color); ?>; font-size: 16px;"><?php _e('Formulario de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: <?php echo esc_attr($text_color); ?>; font-size: 13px; font-weight: 500;"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $btn_radius; ?>; font-size: 14px; background: <?php echo esc_attr($bg_color); ?>; color: <?php echo esc_attr($text_color); ?>; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: <?php echo esc_attr($text_color); ?>; font-size: 13px; font-weight: 500;"><?php _e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="email" placeholder="tu@email.com" style="width: 100%; padding: 10px 14px; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $btn_radius; ?>; font-size: 14px; background: <?php echo esc_attr($bg_color); ?>; color: <?php echo esc_attr($text_color); ?>; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 8px; color: <?php echo esc_attr($text_color); ?>; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" style="accent-color: <?php echo esc_attr($primary_color); ?>;">
                            <?php _e('Acepto los terminos y condiciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </div>

                    <button style="background: <?php echo esc_attr($primary_color); ?>; color: white; padding: <?php echo $btn_py; ?> <?php echo $btn_px; ?>; border-radius: <?php echo $btn_radius; ?>; font-size: <?php echo $btn_font; ?>; font-weight: <?php echo $btn_weight; ?>; border: none; width: 100%;">
                        <?php _e('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <!-- Tab: Modulos -->
            <div class="flavor-preview-tab-content" data-tab-content="modules" style="display: none;">
                <?php
                // Obtener configuración de módulos
                $module_card_style = $settings['module_card_style'] ?? 'elevated';
                $module_icon_style = $settings['module_icon_style'] ?? 'filled';
                $module_border_style = $settings['module_border_style'] ?? 'left';
                $dashboard_density = $settings['dashboard_density'] ?? 'normal';

                // Estilos dinámicos según configuración
                $card_styles = [
                    'elevated' => "box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: none;",
                    'outlined' => "box-shadow: none; border: 1px solid {$border_color};",
                    'flat' => "box-shadow: none; border: none; background: " . ($is_dark ? 'rgba(255,255,255,0.05)' : '#f9fafb') . ";",
                    'glass' => "background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);",
                ];

                $border_styles = [
                    'left' => "border-left: 4px solid {$primary_color};",
                    'top' => "border-top: 4px solid {$primary_color};",
                    'full' => "border: 2px solid {$primary_color};",
                    'none' => "",
                ];

                $density_padding = [
                    'compact' => '10px 12px',
                    'normal' => '14px 16px',
                    'comfortable' => '18px 20px',
                ];
                ?>

                <div style="display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                    <div style="background: <?php echo esc_attr($primary_color); ?>15; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
                        <strong><?php _e('Tarjeta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(ucfirst($module_card_style)); ?>
                    </div>
                    <div style="background: <?php echo esc_attr($secondary_color); ?>15; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
                        <strong><?php _e('Icono:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(ucfirst($module_icon_style)); ?>
                    </div>
                    <div style="background: <?php echo esc_attr($accent_color); ?>15; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
                        <strong><?php _e('Borde:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(ucfirst($module_border_style)); ?>
                    </div>
                    <div style="background: <?php echo esc_attr($success_color); ?>15; padding: 6px 12px; border-radius: 6px; font-size: 11px;">
                        <strong><?php _e('Densidad:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(ucfirst($dashboard_density)); ?>
                    </div>
                </div>

                <!-- Module Cards Preview -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <?php
                    $sample_modules = [
                        ['icon' => 'dashicons-groups', 'name' => 'Comunidades', 'desc' => '5 activas', 'color' => $primary_color],
                        ['icon' => 'dashicons-calendar-alt', 'name' => 'Eventos', 'desc' => '3 proximos', 'color' => $secondary_color],
                        ['icon' => 'dashicons-carrot', 'name' => 'Huertos', 'desc' => '2 parcelas', 'color' => $success_color],
                        ['icon' => 'dashicons-store', 'name' => 'Marketplace', 'desc' => '12 anuncios', 'color' => $accent_color],
                    ];

                    foreach ($sample_modules as $mod):
                        // Generar estilo de icono
                        $icon_style_css = '';
                        switch ($module_icon_style) {
                            case 'filled':
                                $icon_style_css = "background: {$mod['color']}; color: white;";
                                break;
                            case 'outlined':
                                $icon_style_css = "background: transparent; border: 2px solid {$mod['color']}; color: {$mod['color']};";
                                break;
                            case 'gradient':
                                $icon_style_css = "background: linear-gradient(135deg, {$mod['color']} 0%, {$secondary_color} 100%); color: white;";
                                break;
                            case 'minimal':
                                $icon_style_css = "background: {$mod['color']}15; color: {$mod['color']};";
                                break;
                        }

                        // Combinar estilos de tarjeta
                        $combined_card_style = ($card_styles[$module_card_style] ?? '') . ' ' . ($border_styles[$module_border_style] ?? '');
                    ?>
                    <div style="padding: <?php echo esc_attr($density_padding[$dashboard_density] ?? '14px 16px'); ?>; background: <?php echo esc_attr($card_bg); ?>; border-radius: <?php echo $card_radius; ?>; <?php echo $combined_card_style; ?>">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; <?php echo $icon_style_css; ?>">
                                <span class="dashicons <?php echo esc_attr($mod['icon']); ?>" style="font-size: 20px;"></span>
                            </div>
                            <div>
                                <div style="color: <?php echo esc_attr($text_color); ?>; font-size: 14px; font-weight: 600;"><?php echo esc_html($mod['name']); ?></div>
                                <div style="color: <?php echo esc_attr($text_muted); ?>; font-size: 12px;"><?php echo esc_html($mod['desc']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dashboard Widget Preview -->
                <div style="margin-top: 16px; padding: 16px; background: <?php echo esc_attr($card_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo $card_radius; ?>;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: <?php echo esc_attr($text_color); ?>; font-size: 14px; font-weight: 600;"><?php _e('Widget de Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span style="background: <?php echo esc_attr($primary_color); ?>; color: white; padding: 2px 8px; border-radius: 9999px; font-size: 11px;">3</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <div style="flex: 1; height: 8px; background: <?php echo esc_attr($primary_color); ?>; border-radius: 4px;"></div>
                        <div style="flex: 0.6; height: 8px; background: <?php echo esc_attr($secondary_color); ?>; border-radius: 4px;"></div>
                        <div style="flex: 0.3; height: 8px; background: <?php echo esc_attr($accent_color); ?>; border-radius: 4px;"></div>
                    </div>
                    <p style="margin: 12px 0 0; color: <?php echo esc_attr($text_muted); ?>; font-size: 12px;"><?php _e('Los widgets usaran la densidad y animaciones configuradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var tabs = document.querySelectorAll('.flavor-preview-tab');
            var contents = document.querySelectorAll('.flavor-preview-tab-content');

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var targetTab = this.getAttribute('data-tab');

                    // Update tab styles
                    tabs.forEach(function(t) {
                        t.classList.remove('flavor-preview-tab--active');
                        t.style.background = 'transparent';
                        t.style.color = '<?php echo esc_js($text_muted); ?>';
                    });
                    this.classList.add('flavor-preview-tab--active');
                    this.style.background = '<?php echo esc_js($primary_color); ?>';
                    this.style.color = 'white';

                    // Show/hide content
                    contents.forEach(function(c) {
                        c.style.display = c.getAttribute('data-tab-content') === targetTab ? 'block' : 'none';
                    });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Comprobar si un color es oscuro
     */
    private function is_dark_color($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness < 128;
    }

    /**
     * Aclarar un color
     */
    private function lighten_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = min(255, $r + ($percent * 2.55));
        $g = min(255, $g + ($percent * 2.55));
        $b = min(255, $b + ($percent * 2.55));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Oscurecer un color
     */
    private function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, $r - ($percent * 2.55));
        $g = max(0, $g - ($percent * 2.55));
        $b = max(0, $b - ($percent * 2.55));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
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
        $color_fields = ['primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'error_color', 'background_color', 'text_color', 'text_muted_color', 'header_bg_color', 'header_text_color', 'footer_bg_color', 'footer_text_color'];
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

        // Campos de texto y select
        $text_fields = [
            'font_family_headings', 'font_family_body', 'card_shadow', 'portal_layout',
            'module_card_style', 'module_icon_style', 'module_border_style',
            'dashboard_density', 'widget_animations'
        ];
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }

        // Colores de módulos (campos dinámicos module_color_*)
        foreach ($input as $key => $value) {
            if (strpos($key, 'module_color_') === 0) {
                $sanitized[$key] = sanitize_hex_color($value);
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
            // Header y Footer
            'header_bg_color' => '#ffffff',
            'header_text_color' => '#1f2937',
            'footer_bg_color' => '#1f2937',
            'footer_text_color' => '#ffffff',
            // Portal
            'portal_layout' => 'ecosystem',
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

    /* Header y Footer */
    --flavor-header-bg: <?php echo esc_attr($settings['header_bg_color'] ?? '#ffffff'); ?>;
    --flavor-header-text: <?php echo esc_attr($settings['header_text_color'] ?? '#1f2937'); ?>;
    --flavor-footer-bg-dark: <?php echo esc_attr($settings['footer_bg_color'] ?? '#1f2937'); ?>;
    --flavor-footer-text-dark: <?php echo esc_attr($settings['footer_text_color'] ?? '#ffffff'); ?>;

    /* Módulos y Dashboards */
    --flavor-module-card-style: <?php echo esc_attr($settings['module_card_style'] ?? 'elevated'); ?>;
    --flavor-module-icon-style: <?php echo esc_attr($settings['module_icon_style'] ?? 'filled'); ?>;
    --flavor-module-border-style: <?php echo esc_attr($settings['module_border_style'] ?? 'left'); ?>;
    --flavor-dashboard-density: <?php echo esc_attr($settings['dashboard_density'] ?? 'normal'); ?>;
    --flavor-widget-animations: <?php echo esc_attr($settings['widget_animations'] ?? 'all'); ?>;

    /* Colores globales de botones dashboard */
<?php if (!empty($settings['dashboard_btn_primary_color'])): ?>
    --flavor-dashboard-btn-primary: <?php echo esc_attr($settings['dashboard_btn_primary_color']); ?>;
<?php endif; ?>
<?php if (!empty($settings['dashboard_btn_text_color'])): ?>
    --flavor-dashboard-btn-text: <?php echo esc_attr($settings['dashboard_btn_text_color']); ?>;
<?php endif; ?>

    /* Colores de Módulos Activos */
<?php
$modulos_colores = $this->get_active_modules_with_colors();
foreach ($modulos_colores as $module_id => $module_data) {
    $field_name = 'module_color_' . $module_id;
    $color = $settings[$field_name] ?? $module_data['color'];
    echo "    --flavor-module-{$module_id}: " . esc_attr($color) . ";\n";
}
?>

    /* ================================================
       Variables del Sistema de Diseño (fl-*)
       Usadas por: Unified Portal, Dashboards, Widgets
       ================================================ */
    --fl-primary-500: var(--flavor-primary);
    --fl-primary-100: <?php echo esc_attr($this->lighten_color($settings['primary_color'], 40)); ?>;
    --fl-primary-200: <?php echo esc_attr($this->lighten_color($settings['primary_color'], 30)); ?>;
    --fl-primary-300: <?php echo esc_attr($this->lighten_color($settings['primary_color'], 20)); ?>;
    --fl-primary-400: <?php echo esc_attr($this->lighten_color($settings['primary_color'], 10)); ?>;
    --fl-primary-600: <?php echo esc_attr($this->darken_color($settings['primary_color'], 10)); ?>;
    --fl-primary-700: <?php echo esc_attr($this->darken_color($settings['primary_color'], 20)); ?>;
    --fl-primary-800: <?php echo esc_attr($this->darken_color($settings['primary_color'], 30)); ?>;
    --fl-primary-900: <?php echo esc_attr($this->darken_color($settings['primary_color'], 40)); ?>;

    --fl-secondary-500: var(--flavor-secondary);
    --fl-accent-500: var(--flavor-accent);

    --fl-neutral-0: #ffffff;
    --fl-neutral-50: <?php echo esc_attr($settings['background_color']); ?>;
    --fl-neutral-100: #f3f4f6;
    --fl-neutral-200: #e5e7eb;
    --fl-neutral-300: #d1d5db;
    --fl-neutral-400: <?php echo esc_attr($settings['text_muted_color']); ?>;
    --fl-neutral-500: #6b7280;
    --fl-neutral-600: #4b5563;
    --fl-neutral-700: #374151;
    --fl-neutral-800: #1f2937;
    --fl-neutral-900: <?php echo esc_attr($settings['text_color']); ?>;

    --fl-success-500: var(--flavor-success);
    --fl-warning-500: var(--flavor-warning);
    --fl-error-500: var(--flavor-error);

    --fl-radius-sm: 0.375rem;
    --fl-radius-md: var(--flavor-button-radius);
    --fl-radius-lg: var(--flavor-card-radius);
    --fl-radius-xl: calc(var(--flavor-card-radius) * 1.5);

    --fl-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --fl-shadow-md: var(--flavor-card-shadow);
    --fl-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);

    --fl-space-1: 0.25rem;
    --fl-space-2: 0.5rem;
    --fl-space-3: 0.75rem;
    --fl-space-4: 1rem;
    --fl-space-5: 1.25rem;
    --fl-space-6: 1.5rem;
    --fl-space-8: 2rem;

    --fl-font-sans: var(--flavor-font-body);
    --fl-text-base: var(--flavor-font-size-base);
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

/* ========================================
   ESTILOS DE MÓDULOS Y DASHBOARDS
   ======================================== */

/* Estilos de tarjeta de módulo */
.flavor-module-card {
    background: var(--flavor-bg, #ffffff);
    border-radius: var(--flavor-card-radius, 12px);
    transition: all 0.2s ease;
}

/* Estilo: elevated (por defecto) */
.flavor-module-card[data-card-style="elevated"],
:root:has([data-module-card-style="elevated"]) .flavor-module-card {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: none;
}
.flavor-module-card[data-card-style="elevated"]:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

/* Estilo: outlined */
.flavor-module-card[data-card-style="outlined"] {
    box-shadow: none;
    border: 1px solid var(--flavor-border, #e5e7eb);
}
.flavor-module-card[data-card-style="outlined"]:hover {
    border-color: var(--flavor-primary, #3b82f6);
}

/* Estilo: flat */
.flavor-module-card[data-card-style="flat"] {
    box-shadow: none;
    border: none;
    background: var(--flavor-bg-secondary, #f9fafb);
}

/* Estilo: glass */
.flavor-module-card[data-card-style="glass"] {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

/* Estilos de icono de módulo */
.flavor-module-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    font-size: 24px;
}

/* Icono: filled */
.flavor-module-icon[data-icon-style="filled"] {
    background: var(--flavor-primary, #3b82f6);
    color: white;
}

/* Icono: outlined */
.flavor-module-icon[data-icon-style="outlined"] {
    background: transparent;
    border: 2px solid var(--flavor-primary, #3b82f6);
    color: var(--flavor-primary, #3b82f6);
}

/* Icono: gradient */
.flavor-module-icon[data-icon-style="gradient"] {
    background: linear-gradient(135deg, var(--flavor-primary, #3b82f6) 0%, var(--flavor-secondary, #8b5cf6) 100%);
    color: white;
}

/* Icono: minimal */
.flavor-module-icon[data-icon-style="minimal"] {
    background: color-mix(in srgb, var(--flavor-primary, #3b82f6) 10%, transparent);
    color: var(--flavor-primary, #3b82f6);
}

/* Estilos de borde de módulo */
.flavor-module-card[data-border-style="left"] {
    border-left: 4px solid var(--flavor-primary, #3b82f6);
}
.flavor-module-card[data-border-style="top"] {
    border-top: 4px solid var(--flavor-primary, #3b82f6);
}
.flavor-module-card[data-border-style="full"] {
    border: 2px solid var(--flavor-primary, #3b82f6);
}
.flavor-module-card[data-border-style="none"] {
    border: none;
}

/* Densidad del dashboard */
.flavor-dashboard[data-density="compact"] {
    --dashboard-gap: 12px;
    --dashboard-padding: 12px;
    --dashboard-widget-padding: 12px;
}
.flavor-dashboard[data-density="normal"] {
    --dashboard-gap: 16px;
    --dashboard-padding: 16px;
    --dashboard-widget-padding: 16px;
}
.flavor-dashboard[data-density="comfortable"] {
    --dashboard-gap: 24px;
    --dashboard-padding: 24px;
    --dashboard-widget-padding: 20px;
}

.flavor-dashboard {
    gap: var(--dashboard-gap, 16px);
    padding: var(--dashboard-padding, 16px);
}
.flavor-dashboard-widget {
    padding: var(--dashboard-widget-padding, 16px);
}

/* Animaciones de widgets */
.flavor-dashboard[data-animations="all"] .flavor-dashboard-widget {
    transition: all 0.3s ease;
}
.flavor-dashboard[data-animations="all"] .flavor-dashboard-widget:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.flavor-dashboard[data-animations="minimal"] .flavor-dashboard-widget {
    transition: box-shadow 0.2s ease;
}
.flavor-dashboard[data-animations="minimal"] .flavor-dashboard-widget:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.flavor-dashboard[data-animations="none"] .flavor-dashboard-widget {
    transition: none;
}

/* ========================================
   INTEGRACIÓN CON DASHBOARD UNIFICADO (fud-*)
   Aplica las configuraciones de módulos a las clases existentes
   ======================================== */

/* Densidad aplicada a widgets del dashboard unificado */
:root {
    --fud-density-gap: <?php
        $density_settings = [
            'compact' => '0.75rem',
            'normal' => '1rem',
            'comfortable' => '1.5rem',
        ];
        echo $density_settings[$settings['dashboard_density'] ?? 'normal'];
    ?>;
    --fud-density-padding: <?php
        $density_padding = [
            'compact' => '1rem',
            'normal' => '1.25rem',
            'comfortable' => '1.75rem',
        ];
        echo $density_padding[$settings['dashboard_density'] ?? 'normal'];
    ?>;
}

.fud-widgets-grid {
    gap: var(--fud-density-gap, 1rem);
}

.fud-widget,
.fud-priority-panel {
    padding: var(--fud-density-padding, 1.25rem);
}

/* Animaciones aplicadas a widgets existentes */
<?php if (($settings['widget_animations'] ?? 'all') === 'all'): ?>
.fud-widget,
.fud-priority-panel {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.fud-widget:hover,
.fud-priority-panel:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
}
<?php elseif (($settings['widget_animations'] ?? 'all') === 'minimal'): ?>
.fud-widget,
.fud-priority-panel {
    transition: box-shadow 0.2s ease;
}
.fud-widget:hover,
.fud-priority-panel:hover {
    box-shadow: 0 6px 12px -4px rgba(0, 0, 0, 0.1);
}
<?php else: ?>
.fud-widget,
.fud-priority-panel {
    transition: none;
}
<?php endif; ?>

/* Estilo de tarjeta aplicado a widgets existentes */
<?php
$card_style = $settings['module_card_style'] ?? 'elevated';
switch ($card_style):
    case 'outlined':
?>
.fud-widget,
.fud-priority-panel {
    box-shadow: none;
    border: 1px solid var(--fl-border-default, var(--flavor-border, #e5e7eb));
}
<?php break;
    case 'flat':
?>
.fud-widget,
.fud-priority-panel {
    box-shadow: none;
    border: none;
    background: var(--fl-bg-muted, var(--flavor-bg-tertiary, #f3f4f6));
}
<?php break;
    case 'glass':
?>
.fud-widget,
.fud-priority-panel {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}
<?php break;
    default: // elevated
?>
.fud-widget,
.fud-priority-panel {
    box-shadow: 0 4px 12px -4px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
}
<?php endswitch; ?>

/* ========================================
   BOTONES DEL PORTAL UNIFICADO
   ======================================== */
.fup-btn--primary,
.flavor-unified-portal .fup-btn--primary,
a.fup-btn--primary {
    background: var(--fl-primary-500, var(--flavor-primary, #3b82f6)) !important;
    color: <?php echo !empty($settings['dashboard_btn_text_color']) ? esc_attr($settings['dashboard_btn_text_color']) : '#ffffff'; ?> !important;
    border: none;
}

.fup-btn--primary:hover,
.flavor-unified-portal .fup-btn--primary:hover,
a.fup-btn--primary:hover {
    background: var(--fl-primary-700, <?php echo esc_attr($this->darken_color($settings['primary_color'], 20)); ?>) !important;
    color: <?php echo !empty($settings['dashboard_btn_text_color']) ? esc_attr($settings['dashboard_btn_text_color']) : '#ffffff'; ?> !important;
    text-decoration: none;
}

.fup-btn--secondary,
a.fup-btn--secondary {
    background: var(--fl-neutral-100, #f3f4f6);
    color: var(--fl-neutral-700, #374151);
    border: 1px solid var(--fl-neutral-200, #e5e7eb);
}

.fup-btn--secondary:hover,
a.fup-btn--secondary:hover {
    background: var(--fl-neutral-200, #e5e7eb);
    color: var(--fl-neutral-900, #111827);
    text-decoration: none;
}

.fup-btn--outline,
a.fup-btn--outline {
    background: transparent;
    color: var(--fl-primary-500, var(--flavor-primary, #3b82f6));
    border: 2px solid var(--fl-primary-500, var(--flavor-primary, #3b82f6));
}

.fup-btn--outline:hover,
a.fup-btn--outline:hover {
    background: var(--fl-primary-500, var(--flavor-primary, #3b82f6));
    color: #ffffff;
    text-decoration: none;
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

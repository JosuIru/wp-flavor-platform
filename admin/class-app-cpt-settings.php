<?php
/**
 * Página de Configuración de CPTs para Apps
 *
 * Permite configurar qué Custom Post Types mostrar en las apps móviles
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para configuración de CPTs en apps
 *
 * @since 3.0.0
 */
class Flavor_App_CPT_Settings {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_CPT_Settings
     */
    private static $instancia = null;

    /**
     * CPT Manager
     *
     * @var Flavor_App_CPT_Manager
     */
    private $cpt_manager;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_CPT_Settings
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->cpt_manager = Flavor_App_CPT_Manager::get_instance();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Agrega página al menú
     *
     * @return void
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Contenido de Apps', 'flavor-chat-ia'),
            __('Contenido Apps', 'flavor-chat-ia'),
            'manage_options',
            'flavor-app-cpts',
            [$this, 'render_page']
        );
    }

    /**
     * Carga assets
     *
     * @param string $hook_suffix
     * @return void
     */
    public function enqueue_assets($hook_suffix) {
        if ($hook_suffix !== 'flavor-platform_page_flavor-app-cpts') {
            return;
        }

        // Iconos de Material Design
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            [],
            null
        );

        // Estilos
        $css = "
            .flavor-cpt-settings-wrapper {
                max-width: 1400px;
                margin: 20px 0;
            }
            .flavor-cpt-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 30px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .flavor-cpt-header h1 {
                color: #fff;
                margin: 0 0 10px 0;
            }
            .flavor-cpt-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
            }
            .flavor-cpt-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                transition: all 0.2s;
            }
            .flavor-cpt-card.enabled {
                border-color: #2271b1;
                box-shadow: 0 2px 8px rgba(34, 113, 177, 0.1);
            }
            .flavor-cpt-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #f0f0f1;
            }
            .flavor-cpt-card-title {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .flavor-cpt-card-title h3 {
                margin: 0;
                font-size: 16px;
            }
            .flavor-cpt-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .flavor-cpt-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .flavor-cpt-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            .flavor-cpt-toggle-slider:before {
                position: absolute;
                content: \"\";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            .flavor-cpt-toggle input:checked + .flavor-cpt-toggle-slider {
                background-color: #2271b1;
            }
            .flavor-cpt-toggle input:checked + .flavor-cpt-toggle-slider:before {
                transform: translateX(26px);
            }
            .flavor-cpt-config {
                display: none;
                margin-top: 15px;
            }
            .flavor-cpt-card.enabled .flavor-cpt-config {
                display: block;
            }
            .flavor-cpt-field {
                margin-bottom: 15px;
            }
            .flavor-cpt-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                font-size: 13px;
            }
            .flavor-cpt-field input[type=\"text\"],
            .flavor-cpt-field input[type=\"number\"],
            .flavor-cpt-field select,
            .flavor-cpt-field textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .flavor-cpt-field textarea {
                min-height: 60px;
                resize: vertical;
            }
            .flavor-cpt-field-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .flavor-cpt-icon-picker {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 5px;
            }
            .flavor-cpt-icon-option {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .flavor-cpt-icon-option:hover,
            .flavor-cpt-icon-option.selected {
                border-color: #2271b1;
                background: #f0f6fc;
            }
            .flavor-cpt-icon-option .material-icons {
                font-size: 24px;
                color: #646970;
            }
            .flavor-cpt-icon-option.selected .material-icons {
                color: #2271b1;
            }
            .flavor-cpt-color-picker {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 5px;
            }
            .flavor-cpt-color-option {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.2s;
            }
            .flavor-cpt-color-option:hover,
            .flavor-cpt-color-option.selected {
                border-color: #2271b1;
                transform: scale(1.1);
            }
            .flavor-cpt-stats {
                display: flex;
                gap: 15px;
                padding: 10px;
                background: #f6f7f7;
                border-radius: 4px;
                margin-bottom: 15px;
                font-size: 13px;
            }
            .flavor-cpt-stat {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .flavor-cpt-stat .material-icons {
                font-size: 16px;
                color: #646970;
            }
            .flavor-cpt-checkboxes {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-top: 10px;
            }
            .flavor-cpt-checkbox {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .flavor-cpt-checkbox input {
                margin: 0;
            }
            .flavor-cpt-checkbox label {
                margin: 0;
                font-weight: normal;
                font-size: 13px;
            }
            .flavor-cpt-save-btn {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 15px 30px;
                border-radius: 50px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);
                transition: all 0.2s;
                z-index: 1000;
            }
            .flavor-cpt-save-btn:hover {
                background: #135e96;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(34, 113, 177, 0.4);
            }
            .flavor-cpt-save-btn.saving {
                opacity: 0.7;
                cursor: not-allowed;
            }
            .flavor-cpt-preview {
                margin-top: 10px;
                padding: 10px;
                background: #f0f6fc;
                border-radius: 4px;
                font-size: 12px;
            }
            .flavor-cpt-preview-title {
                font-weight: 600;
                margin-bottom: 5px;
            }
            .flavor-cpt-preview-posts {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .flavor-cpt-preview-post {
                background: #fff;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 11px;
            }
            .flavor-cpt-empty {
                text-align: center;
                padding: 60px 20px;
                color: #646970;
            }
            .flavor-cpt-empty .material-icons {
                font-size: 64px;
                color: #ddd;
                margin-bottom: 15px;
            }
        ";

        wp_add_inline_style('wp-admin', $css);

        // JavaScript
        wp_enqueue_script('flavor-cpt-settings', '', ['jquery'], FLAVOR_CHAT_IA_VERSION, true);

        $js = "
            (function($) {
                'use strict';

                window.FlavorCPTSettings = {
                    config: {},

                    init: function() {
                        this.loadConfig();
                        this.bindEvents();
                    },

                    loadConfig: function() {
                        var self = this;
                        $('.flavor-cpt-card').each(function() {
                            var card = $(this);
                            var postType = card.data('post-type');

                            self.config[postType] = {
                                enabled: card.find('.cpt-enabled').prop('checked'),
                                app_name: card.find('.cpt-app-name').val(),
                                description: card.find('.cpt-description').val(),
                                icon: card.find('.cpt-icon').val(),
                                color: card.find('.cpt-color').val(),
                                order: card.find('.cpt-order').val(),
                                show_in_navigation: card.find('.cpt-show-nav').prop('checked'),
                                show_featured_image: card.find('.cpt-show-image').prop('checked'),
                                show_author: card.find('.cpt-show-author').prop('checked'),
                                show_date: card.find('.cpt-show-date').prop('checked'),
                                show_excerpt: card.find('.cpt-show-excerpt').prop('checked'),
                                show_categories: card.find('.cpt-show-categories').prop('checked'),
                                show_tags: card.find('.cpt-show-tags').prop('checked'),
                                enable_search: card.find('.cpt-enable-search').prop('checked'),
                                enable_filters: card.find('.cpt-enable-filters').prop('checked'),
                            };
                        });
                    },

                    bindEvents: function() {
                        var self = this;

                        // Toggle enabled
                        $(document).on('change', '.cpt-enabled', function() {
                            var card = $(this).closest('.flavor-cpt-card');
                            if ($(this).prop('checked')) {
                                card.addClass('enabled');
                            } else {
                                card.removeClass('enabled');
                            }
                            self.loadConfig();
                        });

                        // Icon picker
                        $(document).on('click', '.flavor-cpt-icon-option', function() {
                            var card = $(this).closest('.flavor-cpt-card');
                            var icon = $(this).data('icon');

                            card.find('.flavor-cpt-icon-option').removeClass('selected');
                            $(this).addClass('selected');
                            card.find('.cpt-icon').val(icon);

                            self.loadConfig();
                        });

                        // Color picker
                        $(document).on('click', '.flavor-cpt-color-option', function() {
                            var card = $(this).closest('.flavor-cpt-card');
                            var color = $(this).data('color');

                            card.find('.flavor-cpt-color-option').removeClass('selected');
                            $(this).addClass('selected');
                            card.find('.cpt-color').val(color);

                            self.loadConfig();
                        });

                        // Cualquier cambio en inputs
                        $(document).on('change keyup', '.flavor-cpt-card input, .flavor-cpt-card textarea, .flavor-cpt-card select', function() {
                            self.loadConfig();
                        });

                        // Guardar configuración
                        $(document).on('click', '.flavor-cpt-save-btn', function(e) {
                            e.preventDefault();
                            self.saveConfig();
                        });
                    },

                    saveConfig: function() {
                        var btn = $('.flavor-cpt-save-btn');
                        btn.addClass('saving').text('Guardando...');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_save_cpt_config',
                                nonce: '" . wp_create_nonce('flavor_app_cpts_nonce') . "',
                                config: JSON.stringify(this.config)
                            },
                            success: function(response) {
                                if (response.success) {
                                    btn.text('✓ Guardado');
                                    setTimeout(function() {
                                        btn.removeClass('saving').text('Guardar Cambios');
                                    }, 2000);
                                } else {
                                    alert('Error al guardar: ' + response.data);
                                    btn.removeClass('saving').text('Guardar Cambios');
                                }
                            },
                            error: function() {
                                alert('Error de conexión');
                                btn.removeClass('saving').text('Guardar Cambios');
                            }
                        });
                    }
                };

                $(document).ready(function() {
                    if ($('.flavor-cpt-settings-wrapper').length) {
                        FlavorCPTSettings.init();
                    }
                });

            })(jQuery);
        ";

        wp_add_inline_script('jquery', $js);
    }

    /**
     * Renderiza la página
     *
     * @return void
     */
    public function render_page() {
        $available_cpts = $this->cpt_manager->get_available_cpts();
        $config = $this->cpt_manager->get_config();

        // Iconos de Material Design disponibles
        $icons = [
            'article', 'description', 'shopping_bag', 'event', 'school',
            'work', 'format_quote', 'help', 'people', 'build',
            'download', 'videocam', 'mic', 'restaurant', 'home',
            'work_outline', 'local_offer', 'photo', 'music_note', 'location_on',
        ];

        // Colores disponibles
        $colors = [
            '#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#F44336',
            '#00BCD4', '#FFEB3B', '#795548', '#607D8B', '#E91E63',
        ];

        ?>
        <div class="wrap flavor-cpt-settings-wrapper">
            <div class="flavor-cpt-header">
                <h1><?php echo esc_html__('Contenido de Apps Móviles', 'flavor-chat-ia'); ?></h1>
                <p><?php echo esc_html__('Configura qué tipos de contenido se mostrarán en tus apps móviles como secciones navegables.', 'flavor-chat-ia'); ?></p>
            </div>

            <?php if (empty($available_cpts)): ?>
                <div class="flavor-cpt-empty">
                    <span class="material-icons">inbox</span>
                    <h2><?php echo esc_html__('No hay tipos de contenido disponibles', 'flavor-chat-ia'); ?></h2>
                    <p><?php echo esc_html__('Crea Custom Post Types para poder mostrarlos en las apps.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-cpt-grid">
                    <?php foreach ($available_cpts as $cpt_name => $cpt): ?>
                        <?php
                        $cpt_config = isset($config[$cpt_name]) ? $config[$cpt_name] : $this->get_default_config();
                        $is_enabled = isset($config[$cpt_name]) && $config[$cpt_name]['enabled'];
                        ?>
                        <div class="flavor-cpt-card <?php echo $is_enabled ? 'enabled' : ''; ?>" data-post-type="<?php echo esc_attr($cpt_name); ?>">
                            <div class="flavor-cpt-card-header">
                                <div class="flavor-cpt-card-title">
                                    <h3><?php echo esc_html($cpt['label']); ?></h3>
                                    <code><?php echo esc_html($cpt_name); ?></code>
                                </div>
                                <label class="flavor-cpt-toggle">
                                    <input type="checkbox" class="cpt-enabled" <?php checked($is_enabled); ?>>
                                    <span class="flavor-cpt-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="flavor-cpt-stats">
                                <div class="flavor-cpt-stat">
                                    <span class="material-icons">article</span>
                                    <?php echo esc_html($cpt['count']); ?> publicados
                                </div>
                                <?php if (!empty($cpt['taxonomies'])): ?>
                                <div class="flavor-cpt-stat">
                                    <span class="material-icons">label</span>
                                    <?php echo esc_html(implode(', ', $cpt['taxonomies'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-cpt-config">
                                <!-- Nombre en la app -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Nombre en la App', 'flavor-chat-ia'); ?></label>
                                    <input type="text" class="cpt-app-name" value="<?php echo esc_attr($cpt_config['app_name'] ?: $cpt['label']); ?>" placeholder="<?php echo esc_attr($cpt['label']); ?>">
                                </div>

                                <!-- Descripción -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Descripción', 'flavor-chat-ia'); ?></label>
                                    <textarea class="cpt-description" placeholder="Descripción visible en la app"><?php echo esc_textarea($cpt_config['description'] ?: $cpt['description']); ?></textarea>
                                </div>

                                <!-- Icono -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Icono (Material Icons)', 'flavor-chat-ia'); ?></label>
                                    <input type="hidden" class="cpt-icon" value="<?php echo esc_attr($cpt_config['icon']); ?>">
                                    <div class="flavor-cpt-icon-picker">
                                        <?php foreach ($icons as $icon): ?>
                                            <div class="flavor-cpt-icon-option <?php echo $cpt_config['icon'] === $icon ? 'selected' : ''; ?>" data-icon="<?php echo esc_attr($icon); ?>">
                                                <span class="material-icons"><?php echo esc_html($icon); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Color -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Color', 'flavor-chat-ia'); ?></label>
                                    <input type="hidden" class="cpt-color" value="<?php echo esc_attr($cpt_config['color']); ?>">
                                    <div class="flavor-cpt-color-picker">
                                        <?php foreach ($colors as $color): ?>
                                            <div class="flavor-cpt-color-option <?php echo $cpt_config['color'] === $color ? 'selected' : ''; ?>" data-color="<?php echo esc_attr($color); ?>" style="background-color: <?php echo esc_attr($color); ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Orden -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Orden (menor = primero)', 'flavor-chat-ia'); ?></label>
                                    <input type="number" class="cpt-order" value="<?php echo esc_attr($cpt_config['order']); ?>" min="0" step="10">
                                </div>

                                <!-- Opciones de visualización -->
                                <div class="flavor-cpt-field">
                                    <label><?php echo esc_html__('Opciones de Visualización', 'flavor-chat-ia'); ?></label>
                                    <div class="flavor-cpt-checkboxes">
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-nav" id="nav-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_in_navigation']); ?>>
                                            <label for="nav-<?php echo esc_attr($cpt_name); ?>">Mostrar en navegación</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-image" id="img-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_featured_image']); ?>>
                                            <label for="img-<?php echo esc_attr($cpt_name); ?>">Imagen destacada</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-author" id="author-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_author']); ?>>
                                            <label for="author-<?php echo esc_attr($cpt_name); ?>">Mostrar autor</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-date" id="date-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_date']); ?>>
                                            <label for="date-<?php echo esc_attr($cpt_name); ?>">Mostrar fecha</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-excerpt" id="excerpt-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_excerpt']); ?>>
                                            <label for="excerpt-<?php echo esc_attr($cpt_name); ?>">Mostrar extracto</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-categories" id="cat-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_categories']); ?>>
                                            <label for="cat-<?php echo esc_attr($cpt_name); ?>">Mostrar categorías</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-show-tags" id="tags-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['show_tags']); ?>>
                                            <label for="tags-<?php echo esc_attr($cpt_name); ?>">Mostrar tags</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-enable-search" id="search-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['enable_search']); ?>>
                                            <label for="search-<?php echo esc_attr($cpt_name); ?>">Permitir búsqueda</label>
                                        </div>
                                        <div class="flavor-cpt-checkbox">
                                            <input type="checkbox" class="cpt-enable-filters" id="filters-<?php echo esc_attr($cpt_name); ?>" <?php checked($cpt_config['enable_filters']); ?>>
                                            <label for="filters-<?php echo esc_attr($cpt_name); ?>">Permitir filtros</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="flavor-cpt-save-btn">Guardar Cambios</button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene configuración por defecto para un CPT
     *
     * @return array
     */
    private function get_default_config() {
        return [
            'enabled' => false,
            'app_name' => '',
            'description' => '',
            'icon' => 'description',
            'color' => '#2196F3',
            'order' => 100,
            'show_in_navigation' => true,
            'show_featured_image' => true,
            'show_author' => false,
            'show_date' => true,
            'show_excerpt' => true,
            'show_categories' => true,
            'show_tags' => false,
            'enable_search' => true,
            'enable_filters' => true,
        ];
    }
}

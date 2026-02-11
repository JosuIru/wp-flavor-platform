<?php
/**
 * Page Builder - Constructor visual de páginas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema de construcción de páginas con componentes flexibles
 */
class Flavor_Page_Builder {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Post types soportados
     * NOTA: flavor_landing usa el Landing Editor dedicado (class-landing-editor.php)
     */
    private $post_types = ['post', 'page'];

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
        $this->init();
    }

    /**
     * Inicializar
     */
    private function init() {
        add_action('init', [$this, 'register_post_types']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_page_layout']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('the_content', [$this, 'render_page_content']);
        add_filter('single_template', [$this, 'load_landing_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        // AJAX handlers para vista previa
        add_action('wp_ajax_flavor_save_preview', [$this, 'ajax_save_preview']);
        add_action('wp_ajax_flavor_preview_component', [$this, 'ajax_preview_component']);

        // AJAX handler para importar plantillas de un tema web
        add_action('wp_ajax_flavor_import_theme_templates', [$this, 'ajax_import_theme_templates']);

        // Handler para renderizar vista previa
        add_action('template_redirect', [$this, 'handle_preview_request']);
    }

    /**
     * Registrar custom post types
     */
    public function register_post_types() {
        register_post_type('flavor_landing', [
            'labels' => [
                'name' => __('Landing Pages', 'flavor-chat-ia'),
                'singular_name' => __('Landing Page', 'flavor-chat-ia'),
                'add_new' => __('Añadir Nueva', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Landing Page', 'flavor-chat-ia'),
                'edit_item' => __('Editar Landing Page', 'flavor-chat-ia'),
                'view_item' => __('Ver Landing Page', 'flavor-chat-ia'),
                'all_items' => __('Todas las Landing Pages', 'flavor-chat-ia'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'landing', 'with_front' => false],
            'capability_type' => 'page',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => ['title', 'custom-fields'],
            'menu_icon' => 'dashicons-layout',
            'show_in_rest' => false,
        ]);

        // Flush rewrite rules si es necesario
        if (get_option('flavor_landing_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('flavor_landing_flush_rewrite_rules');
        }
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                'flavor_page_builder',
                __('Page Builder', 'flavor-chat-ia'),
                [$this, 'render_page_builder_metabox'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Renderizar metabox del page builder
     */
    public function render_page_builder_metabox($post) {
        wp_nonce_field('flavor_page_builder', 'flavor_page_builder_nonce');

        $layout = get_post_meta($post->ID, '_flavor_page_layout', true);
        if (!is_array($layout)) {
            $layout = [];
        }

        // Determinar si usar Vue Builder
        $use_vue_builder = apply_filters('flavor_page_builder_use_vue', true);
        $vue_builder_file = FLAVOR_WEB_BUILDER_PATH . 'vue-builder/dist/vue-page-builder.umd.js';
        $use_vue = $use_vue_builder && file_exists($vue_builder_file);

        ?>
        <?php if ($use_vue): ?>
        <!-- CSS Override para layout de WordPress - Máxima prioridad -->
        <style id="flavor-pb-layout-override">
            /* Forzar layout de 1 columna para el Page Builder */
            body.wp-admin.post-type-flavor_landing #poststuff {
                padding-top: 0 !important;
            }
            body.wp-admin.post-type-flavor_landing #post-body.columns-2 {
                margin-right: 0 !important;
            }
            body.wp-admin.post-type-flavor_landing #postbox-container-1 {
                display: none !important;
                width: 0 !important;
                float: none !important;
            }
            body.wp-admin.post-type-flavor_landing #postbox-container-2 {
                float: none !important;
                width: 100% !important;
                margin-right: 0 !important;
            }
            body.wp-admin.post-type-flavor_landing #post-body-content {
                display: none !important;
            }
            body.wp-admin.post-type-flavor_landing #titlediv {
                display: block !important;
                margin-bottom: 15px !important;
            }
            /* Metabox del Page Builder */
            #flavor_page_builder {
                margin: 0 !important;
            }
            #flavor_page_builder .postbox-header {
                display: none !important;
            }
            #flavor_page_builder .inside {
                margin: 0 !important;
                padding: 0 !important;
            }
            /* Vue app ocupa todo el espacio */
            .flavor-pb-vue-wrapper,
            #flavor-vue-page-builder {
                width: 100% !important;
                max-width: 100% !important;
                display: block !important;
            }
        </style>
        <!-- Script para reorganizar layout de WordPress -->
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Mover el título antes del metabox si está oculto
                var titleDiv = document.getElementById('titlediv');
                var postStuff = document.getElementById('poststuff');
                var metabox = document.getElementById('flavor_page_builder');

                if (titleDiv && postStuff && metabox) {
                    // Mover título al inicio de poststuff
                    postStuff.insertBefore(titleDiv, postStuff.firstChild);
                }

                // Forzar la clase columns-1 en lugar de columns-2
                var postBody = document.getElementById('post-body');
                if (postBody) {
                    postBody.classList.remove('columns-2');
                    postBody.classList.add('columns-1');
                }
            });
        })();
        </script>
        <!-- Vue 3 Page Builder -->
        <div id="flavor-vue-page-builder" class="flavor-pb-vue-wrapper">
            <!-- Vue App se monta aquí automáticamente -->
        </div>
        <input type="hidden" name="flavor_page_layout" id="flavor-page-layout-field" value="<?php echo esc_attr(json_encode($layout)); ?>" />
        <?php else: ?>
        <!-- Legacy jQuery Page Builder -->
        <div id="flavor-page-builder" class="flavor-pb-wrapper">
            <!-- Toolbar -->
            <div class="flavor-pb-toolbar">
                <div class="flavor-pb-toolbar-actions">
                    <button type="button" class="button button-primary" id="flavor-pb-add-component">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Añadir Componente', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button" id="flavor-pb-load-template">
                        <span class="dashicons dashicons-schedule"></span>
                        <?php _e('Cargar Plantilla', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button flavor-pb-ai-btn" id="flavor-pb-ai-assistant-btn">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <?php _e('Asistente IA', 'flavor-chat-ia'); ?>
                    </button>

                    <div class="flavor-pb-responsive-toggle">
                        <button type="button" class="active" data-viewport="desktop" title="<?php esc_attr_e('Escritorio', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" data-viewport="tablet" title="<?php esc_attr_e('Tablet', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-tablet"></span>
                        </button>
                        <button type="button" data-viewport="mobile" title="<?php esc_attr_e('Móvil', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>

                    <div class="flavor-pb-undo-redo">
                        <button type="button" id="flavor-pb-undo" title="<?php esc_attr_e('Deshacer (Ctrl+Z)', 'flavor-chat-ia'); ?>" disabled>
                            <span class="dashicons dashicons-undo"></span>
                        </button>
                        <button type="button" id="flavor-pb-redo" title="<?php esc_attr_e('Rehacer (Ctrl+Y)', 'flavor-chat-ia'); ?>" disabled>
                            <span class="dashicons dashicons-redo"></span>
                        </button>
                    </div>
                </div>
                <div class="flavor-pb-toolbar-secondary">
                    <button type="button" class="button" id="flavor-pb-preview">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Vista Previa', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button" id="flavor-pb-save-template">
                        <span class="dashicons dashicons-category"></span>
                        <?php _e('Guardar como Plantilla', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <!-- Editor Layout (canvas + properties panel) -->
            <div class="flavor-pb-editor-layout">
                <!-- Canvas de construcción -->
                <div class="flavor-pb-canvas-wrapper">
                    <div class="flavor-pb-canvas-viewport viewport-desktop">
                        <div class="flavor-pb-components-list" id="flavor-pb-components-list">
                            <?php
                            if (!empty($layout)) {
                                foreach ($layout as $index => $component_data) {
                                    $this->render_component_item($index, $component_data);
                                }
                            } else {
                                echo '<div class="flavor-pb-empty-state">';
                                echo '<span class="dashicons dashicons-admin-page"></span>';
                                echo '<p>' . __('Comienza añadiendo componentes a tu página', 'flavor-chat-ia') . '</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral de propiedades -->
                <div id="flavor-pb-properties-panel" style="display: none;">
                    <div class="flavor-pb-properties-header">
                        <h3><?php _e('Propiedades', 'flavor-chat-ia'); ?></h3>
                        <button type="button" class="flavor-pb-properties-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="flavor-pb-properties-tabs">
                        <button type="button" class="flavor-pb-prop-tab active" data-tab="content">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Contenido', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="flavor-pb-prop-tab" data-tab="design">
                            <span class="dashicons dashicons-art"></span>
                            <?php _e('Diseño', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <div id="flavor-pb-properties-body">
                        <div class="flavor-pb-tab-content active" data-tab-content="content">
                            <!-- Campos de contenido dinámicos -->
                        </div>
                        <div class="flavor-pb-tab-content" data-tab-content="design">
                            <!-- Campos de diseño dinámicos -->
                        </div>
                    </div>
                    <div class="flavor-pb-properties-footer">
                        <button type="button" class="button" id="flavor-pb-cancel-props">
                            <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="flavor-pb-save-props">
                            <?php _e('Guardar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar de componentes disponibles -->
            <div class="flavor-pb-sidebar" id="flavor-pb-sidebar" style="display: none;">
                <div class="flavor-pb-sidebar-header">
                    <h3><?php _e('Componentes Disponibles', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="flavor-pb-close-sidebar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="flavor-pb-sidebar-content">
                    <?php $this->render_components_library(); ?>
                </div>
            </div>

            <!-- Panel del Asistente IA -->
            <?php
            if (class_exists('Flavor_AI_Template_Assistant')) {
                Flavor_AI_Template_Assistant::get_instance()->render_assistant_panel();
            }
            ?>

            <!-- Modal de edición de componente (mantener para compatibilidad) -->
            <div class="flavor-pb-modal" id="flavor-pb-edit-modal" style="display: none;">
                <div class="flavor-pb-modal-content">
                    <div class="flavor-pb-modal-header">
                        <h3><?php _e('Editar Componente', 'flavor-chat-ia'); ?></h3>
                        <button type="button" class="flavor-pb-close-modal">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="flavor-pb-modal-body" id="flavor-pb-edit-modal-body">
                        <!-- Campos dinámicos -->
                    </div>
                    <div class="flavor-pb-modal-footer">
                        <button type="button" class="button" id="flavor-pb-cancel-edit">
                            <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="flavor-pb-save-component">
                            <?php _e('Guardar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de plantillas -->
            <div class="flavor-pb-modal flavor-pb-templates-modal" id="flavor-pb-templates-modal" style="display: none;">
                <div class="flavor-pb-modal-content flavor-pb-templates-modal-content">
                    <div class="flavor-pb-modal-header">
                        <h3><?php _e('Plantillas de Ejemplo', 'flavor-chat-ia'); ?></h3>
                        <button type="button" class="flavor-pb-close-modal">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="flavor-pb-modal-body">
                        <?php $this->render_templates_library(); ?>
                    </div>
                </div>
            </div>

            <!-- Fullscreen Preview Overlay -->
            <div id="flavor-pb-fullscreen-preview" style="display: none;">
                <div class="flavor-pb-fullscreen-toolbar">
                    <h3><?php _e('Vista Previa', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="flavor-pb-fullscreen-close">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php _e('Cerrar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <div class="flavor-pb-fullscreen-iframe-wrapper">
                    <iframe id="flavor-pb-fullscreen-iframe" src="about:blank"></iframe>
                </div>
            </div>

            <!-- Input oculto con los datos -->
            <input type="hidden"
                   name="flavor_page_layout"
                   id="flavor_page_layout"
                   value="<?php echo esc_attr(json_encode($layout)); ?>">
        </div>
        <?php endif; // End Vue/Legacy conditional ?>
        <?php
    }

    /**
     * Renderizar item de componente en el canvas
     */
    private function render_component_item($index, $component_data) {
        $registry = Flavor_Component_Registry::get_instance();
        $component = $registry->get_component($component_data['component_id']);

        if (!$component) {
            return;
        }

        ?>
        <div class="flavor-pb-component-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="flavor-pb-component-header">
                <div class="flavor-pb-component-info">
                    <span class="dashicons <?php echo esc_attr($component['icon']); ?>"></span>
                    <span class="flavor-pb-component-label"><?php echo esc_html($component['label']); ?></span>
                </div>
                <div class="flavor-pb-component-actions">
                    <button type="button" class="flavor-pb-edit-component" title="<?php _e('Editar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="flavor-pb-duplicate-component" title="<?php _e('Duplicar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                    <button type="button" class="flavor-pb-move-up" title="<?php _e('Subir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="flavor-pb-move-down" title="<?php _e('Bajar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <button type="button" class="flavor-pb-delete-component" title="<?php _e('Eliminar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="flavor-pb-component-preview">
                <div class="flavor-pb-preview-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Cargando preview...', 'flavor-chat-ia'); ?>
                </div>
                <div class="flavor-pb-preview-overlay" title="<?php esc_attr_e('Click para editar propiedades', 'flavor-chat-ia'); ?>"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar librería de componentes con buscador, filtros y acordeones
     */
    private function render_components_library() {
        $registry = Flavor_Component_Registry::get_instance();
        $categories = $registry->get_categories();
        $modules_available = $registry->get_modules_with_components();
        $total_components = count($registry->get_components());

        ?>
        <!-- Buscador y filtros -->
        <div class="flavor-pb-search-filters">
            <div class="flavor-pb-search-box">
                <span class="dashicons dashicons-search"></span>
                <input type="text"
                       id="flavor-pb-component-search"
                       placeholder="<?php esc_attr_e('Buscar componente...', 'flavor-chat-ia'); ?>"
                       autocomplete="off">
                <span class="flavor-pb-search-count"><?php echo $total_components; ?> <?php esc_html_e('componentes', 'flavor-chat-ia'); ?></span>
            </div>

            <div class="flavor-pb-filter-row">
                <select id="flavor-pb-filter-module" class="flavor-pb-filter-select">
                    <option value=""><?php esc_html_e('Todos los módulos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($modules_available as $mod_id => $mod_label): ?>
                        <option value="<?php echo esc_attr($mod_id); ?>"><?php echo esc_html($mod_label); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="flavor-pb-filter-category" class="flavor-pb-filter-select">
                    <option value=""><?php esc_html_e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categories as $cat_id => $cat_label): ?>
                        <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_label); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="button" id="flavor-pb-clear-filters" class="flavor-pb-clear-btn" style="display:none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div id="flavor-pb-no-results" class="flavor-pb-no-results" style="display:none;">
                <span class="dashicons dashicons-info-outline"></span>
                <p><?php esc_html_e('No se encontraron componentes con esos filtros.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <!-- Acordeón de componentes -->
        <div class="flavor-pb-components-accordion">
            <?php
            $category_index = 0;
            foreach ($categories as $category_id => $category_label):
                $components = $registry->get_components_by_category($category_id);

                if (empty($components)) {
                    continue;
                }

                $is_first = $category_index === 0;
                $category_index++;
            ?>
                <div class="flavor-pb-accordion-item" data-category-group="<?php echo esc_attr($category_id); ?>">
                    <div class="flavor-pb-accordion-header <?php echo $is_first ? 'active' : ''; ?>"
                         data-category="<?php echo esc_attr($category_id); ?>">
                        <span class="flavor-pb-accordion-icon dashicons dashicons-arrow-down"></span>
                        <h4><?php echo esc_html($category_label); ?></h4>
                        <span class="flavor-pb-component-count">(<?php echo count($components); ?>)</span>
                    </div>
                    <div class="flavor-pb-accordion-content" style="display: <?php echo $is_first ? 'block' : 'none'; ?>;">
                        <div class="flavor-pb-component-grid">
                            <?php foreach ($components as $component_id => $component): ?>
                                <div class="flavor-pb-component-card<?php echo !empty($component['variants']) ? ' flavor-pb-has-variants' : ''; ?>"
                                     data-component-id="<?php echo esc_attr($component_id); ?>"
                                     data-module="<?php echo esc_attr($component['module_id'] ?? ''); ?>"
                                     data-has-variants="<?php echo !empty($component['variants']) ? '1' : '0'; ?>"
                                     data-search-text="<?php echo esc_attr(strtolower(($component['label'] ?? '') . ' ' . ($component['description'] ?? '') . ' ' . ($component['module_id'] ?? ''))); ?>"
                                     title="<?php echo esc_attr($component['description'] ?? ''); ?>">
                                    <div class="flavor-pb-component-card-icon">
                                        <?php if (!empty($component['preview'])): ?>
                                            <img src="<?php echo esc_url($component['preview']); ?>" alt="">
                                        <?php else: ?>
                                            <span class="dashicons <?php echo esc_attr($component['icon']); ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flavor-pb-component-card-info">
                                        <strong><?php echo esc_html($component['label']); ?></strong>
                                        <?php if (!empty($component['variants'])): ?>
                                            <span class="flavor-pb-variant-badge"><?php echo count($component['variants']); ?> variantes</span>
                                        <?php elseif (!empty($component['description'])): ?>
                                            <p><?php echo esc_html($component['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        (function() {
            var searchInput = document.getElementById('flavor-pb-component-search');
            var filterModule = document.getElementById('flavor-pb-filter-module');
            var filterCategory = document.getElementById('flavor-pb-filter-category');
            var clearBtn = document.getElementById('flavor-pb-clear-filters');
            var noResults = document.getElementById('flavor-pb-no-results');
            var searchCount = document.querySelector('.flavor-pb-search-count');

            if (!searchInput) return;

            var debounceTimer;

            function applyFilters() {
                var query = searchInput.value.toLowerCase().trim();
                var selectedModule = filterModule.value;
                var selectedCategory = filterCategory.value;
                var hasActiveFilter = query !== '' || selectedModule !== '' || selectedCategory !== '';

                clearBtn.style.display = hasActiveFilter ? 'inline-flex' : 'none';

                var accordionItems = document.querySelectorAll('.flavor-pb-accordion-item');
                var totalVisible = 0;

                accordionItems.forEach(function(accordionItem) {
                    var categoryGroup = accordionItem.getAttribute('data-category-group');
                    var cards = accordionItem.querySelectorAll('.flavor-pb-component-card');
                    var visibleInCategory = 0;

                    // Si se filtra por categoría y no coincide, ocultar grupo entero
                    if (selectedCategory && categoryGroup !== selectedCategory) {
                        accordionItem.style.display = 'none';
                        return;
                    }

                    cards.forEach(function(card) {
                        var matchesSearch = true;
                        var matchesModule = true;

                        if (query) {
                            var searchText = card.getAttribute('data-search-text') || '';
                            matchesSearch = searchText.indexOf(query) !== -1;
                        }

                        if (selectedModule) {
                            matchesModule = card.getAttribute('data-module') === selectedModule;
                        }

                        if (matchesSearch && matchesModule) {
                            card.style.display = '';
                            visibleInCategory++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Actualizar contador de la categoría
                    var countSpan = accordionItem.querySelector('.flavor-pb-component-count');
                    if (countSpan) {
                        countSpan.textContent = '(' + visibleInCategory + ')';
                    }

                    // Ocultar categoría si no tiene componentes visibles
                    if (visibleInCategory === 0) {
                        accordionItem.style.display = 'none';
                    } else {
                        accordionItem.style.display = '';
                        totalVisible += visibleInCategory;
                        // Expandir automáticamente al buscar
                        if (hasActiveFilter) {
                            var content = accordionItem.querySelector('.flavor-pb-accordion-content');
                            var header = accordionItem.querySelector('.flavor-pb-accordion-header');
                            if (content) content.style.display = 'block';
                            if (header) header.classList.add('active');
                        }
                    }
                });

                // Actualizar contador total
                if (searchCount) {
                    searchCount.textContent = totalVisible + ' componente' + (totalVisible !== 1 ? 's' : '');
                }

                // Mostrar mensaje de sin resultados
                noResults.style.display = totalVisible === 0 ? 'flex' : 'none';
            }

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(applyFilters, 200);
            });

            filterModule.addEventListener('change', applyFilters);
            filterCategory.addEventListener('change', applyFilters);

            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterModule.value = '';
                filterCategory.value = '';
                applyFilters();
                // Colapsar todos excepto el primero
                var items = document.querySelectorAll('.flavor-pb-accordion-item');
                items.forEach(function(item, idx) {
                    var content = item.querySelector('.flavor-pb-accordion-content');
                    var header = item.querySelector('.flavor-pb-accordion-header');
                    if (content) content.style.display = idx === 0 ? 'block' : 'none';
                    if (header) {
                        if (idx === 0) header.classList.add('active');
                        else header.classList.remove('active');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Guardar layout de página
     */
    public function save_page_layout($post_id) {
        // Verificaciones de seguridad
        if (!isset($_POST['flavor_page_builder_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['flavor_page_builder_nonce'], 'flavor_page_builder')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar layout
        if (isset($_POST['flavor_page_layout'])) {
            $layout = json_decode(stripslashes($_POST['flavor_page_layout']), true);
            update_post_meta($post_id, '_flavor_page_layout', $layout);
        }
    }

    /**
     * Enqueue scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        if (!in_array($post->post_type, $this->post_types)) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Determinar si usar Vue Builder o legacy jQuery
        $use_vue_builder = apply_filters('flavor_page_builder_use_vue', true);
        $vue_builder_file = FLAVOR_WEB_BUILDER_PATH . 'vue-builder/dist/vue-page-builder.umd.js';

        // CSS del page builder (común para ambas versiones)
        wp_enqueue_style(
            'flavor-page-builder',
            FLAVOR_WEB_BUILDER_URL . "assets/css/page-builder{$sufijo_asset}.css",
            [],
            FLAVOR_WEB_BUILDER_VERSION
        );

        // Media uploader (común)
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');

        if ($use_vue_builder && file_exists($vue_builder_file)) {
            // Vue 3 Page Builder
            $this->enqueue_vue_builder_scripts($post);
        } else {
            // Fallback: jQuery Page Builder (legacy)
            $this->enqueue_legacy_builder_scripts($post, $sufijo_asset);
        }
    }

    /**
     * Enqueue Vue 3 Page Builder scripts
     */
    private function enqueue_vue_builder_scripts($post) {
        // Vue 3 desde CDN
        wp_enqueue_script(
            'vue',
            'https://unpkg.com/vue@3.4.38/dist/vue.global.prod.js',
            [],
            '3.4.38',
            true
        );

        // Shim para VueDemi - Pinia lo requiere pero podemos simularlo con Vue 3
        wp_add_inline_script('vue', '
            window.VueDemi = (function(Vue) {
                return {
                    Vue: Vue,
                    version: Vue.version,
                    isVue2: false,
                    isVue3: true,
                    install: function() {},
                    set: function(target, key, val) { target[key] = val; return val; },
                    del: function(target, key) { delete target[key]; },
                    // Re-export Vue 3 APIs
                    ref: Vue.ref,
                    reactive: Vue.reactive,
                    readonly: Vue.readonly,
                    computed: Vue.computed,
                    watch: Vue.watch,
                    watchEffect: Vue.watchEffect,
                    watchPostEffect: Vue.watchPostEffect,
                    watchSyncEffect: Vue.watchSyncEffect,
                    isRef: Vue.isRef,
                    unref: Vue.unref,
                    toRef: Vue.toRef,
                    toRefs: Vue.toRefs,
                    isProxy: Vue.isProxy,
                    isReactive: Vue.isReactive,
                    isReadonly: Vue.isReadonly,
                    shallowRef: Vue.shallowRef,
                    triggerRef: Vue.triggerRef,
                    customRef: Vue.customRef,
                    shallowReactive: Vue.shallowReactive,
                    shallowReadonly: Vue.shallowReadonly,
                    toRaw: Vue.toRaw,
                    markRaw: Vue.markRaw,
                    effectScope: Vue.effectScope,
                    getCurrentScope: Vue.getCurrentScope,
                    onScopeDispose: Vue.onScopeDispose,
                    getCurrentInstance: Vue.getCurrentInstance,
                    inject: Vue.inject,
                    provide: Vue.provide,
                    nextTick: Vue.nextTick,
                    defineComponent: Vue.defineComponent,
                    defineAsyncComponent: Vue.defineAsyncComponent,
                    h: Vue.h,
                    createApp: Vue.createApp,
                    hasInjectionContext: function() { return !!Vue.getCurrentInstance(); }
                };
            })(Vue);
        ', 'after');

        // Pinia desde CDN
        wp_enqueue_script(
            'pinia',
            'https://unpkg.com/pinia@2.1.7/dist/pinia.iife.prod.js',
            ['vue'],
            '2.1.7',
            true
        );

        // Tailwind CSS Play CDN - compila clases dinámicamente
        wp_enqueue_script(
            'tailwindcss-play',
            'https://cdn.tailwindcss.com',
            [],
            '3.4',
            false // En el header para que esté disponible antes del contenido
        );

        // Configurar Tailwind Play CDN
        wp_add_inline_script('tailwindcss-play', '
            if (typeof tailwind !== "undefined") {
                tailwind.config = {
                    corePlugins: {
                        preflight: false
                    },
                    theme: {
                        extend: {}
                    }
                };
            }
        ', 'after');

        // CSS base de Flavor (variables CSS del tema)
        wp_enqueue_style(
            'flavor-base-preview',
            FLAVOR_CHAT_IA_URL . 'assets/css/flavor-base.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // CSS específico de Vue Builder
        wp_enqueue_style(
            'flavor-vue-page-builder',
            FLAVOR_WEB_BUILDER_URL . 'vue-builder/dist/vue-page-builder.css',
            ['flavor-base-preview'],
            FLAVOR_WEB_BUILDER_VERSION
        );

        // CSS de componentes del frontend (para preview correcto)
        $components_css_min = FLAVOR_CHAT_IA_PATH . 'assets/css/components.min.css';
        $components_css_url = FLAVOR_CHAT_IA_URL . 'assets/css/components.min.css';
        if (!file_exists($components_css_min)) {
            $components_css_url = FLAVOR_CHAT_IA_URL . 'assets/css/components.css';
        }
        wp_enqueue_style(
            'flavor-components-preview',
            $components_css_url,
            ['flavor-vue-page-builder'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Bundle Vue Page Builder - depende de vue y pinia globales
        wp_enqueue_script(
            'flavor-vue-page-builder',
            FLAVOR_WEB_BUILDER_URL . 'vue-builder/dist/vue-page-builder.umd.js',
            ['pinia'],
            FLAVOR_WEB_BUILDER_VERSION,
            true
        );

        // Obtener layout actual
        $layout = get_post_meta($post->ID, '_flavor_page_layout', true);
        if (!is_array($layout)) {
            $layout = [];
        }

        // Localizar script para Vue
        wp_localize_script('flavor-vue-page-builder', 'flavorPageBuilder', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_page_builder'),
            'postId' => $post ? $post->ID : 0,
            'components' => $this->get_components_data(),
            'categories' => $this->get_component_categories(),
            'layout' => $layout,
            'previewUrl' => add_query_arg('flavor_preview', '1', get_permalink($post->ID)),
            'templates' => $this->get_template_library(),
            'i18n' => [
                'confirmDelete' => __('¿Estás seguro de eliminar este componente?', 'flavor-chat-ia'),
                'confirmLoadTemplate' => __('¿Cargar esta plantilla? Se reemplazará el contenido actual.', 'flavor-chat-ia'),
                'saveSuccess' => __('Cambios guardados correctamente', 'flavor-chat-ia'),
                'saveError' => __('Error al guardar los cambios', 'flavor-chat-ia'),
                'preview' => __('Preview', 'flavor-chat-ia'),
                'hidePreview' => __('Ocultar preview', 'flavor-chat-ia'),
                'selectVariant' => __('Seleccionar variante', 'flavor-chat-ia'),
                'selectPreset' => __('O elegir un preset:', 'flavor-chat-ia'),
                'variants' => __('Variantes', 'flavor-chat-ia'),
                'presets' => __('Presets', 'flavor-chat-ia'),
                'addBlank' => __('Añadir en blanco', 'flavor-chat-ia'),
                'variantLabel' => __('Variante', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Enqueue legacy jQuery Page Builder scripts (fallback)
     */
    private function enqueue_legacy_builder_scripts($post, $sufijo_asset) {
        // JS del page builder legacy
        wp_enqueue_script(
            'flavor-page-builder',
            FLAVOR_WEB_BUILDER_URL . "assets/js/page-builder{$sufijo_asset}.js",
            ['jquery', 'jquery-ui-sortable', 'wp-color-picker'],
            FLAVOR_WEB_BUILDER_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');

        // Localizar script
        wp_localize_script('flavor-page-builder', 'flavorPageBuilder', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_page_builder'),
            'postId' => $post ? $post->ID : 0,
            'components' => $this->get_components_data(),
            'templates' => $this->get_template_library(),
            'i18n' => [
                'confirmDelete' => __('¿Estás seguro de eliminar este componente?', 'flavor-chat-ia'),
                'confirmLoadTemplate' => __('¿Cargar esta plantilla? Se reemplazará el contenido actual.', 'flavor-chat-ia'),
                'saveSuccess' => __('Cambios guardados correctamente', 'flavor-chat-ia'),
                'saveError' => __('Error al guardar los cambios', 'flavor-chat-ia'),
                'preview' => __('Preview', 'flavor-chat-ia'),
                'hidePreview' => __('Ocultar preview', 'flavor-chat-ia'),
                'selectVariant' => __('Seleccionar variante', 'flavor-chat-ia'),
                'selectPreset' => __('O elegir un preset:', 'flavor-chat-ia'),
                'variants' => __('Variantes', 'flavor-chat-ia'),
                'presets' => __('Presets', 'flavor-chat-ia'),
                'addBlank' => __('Añadir en blanco', 'flavor-chat-ia'),
                'variantLabel' => __('Variante', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtener categorías de componentes
     */
    private function get_component_categories() {
        return apply_filters('flavor_page_builder_categories', [
            ['id' => 'structure', 'label' => __('Estructura', 'flavor-chat-ia'), 'icon' => 'dashicons-layout'],
            ['id' => 'content', 'label' => __('Contenido', 'flavor-chat-ia'), 'icon' => 'dashicons-text'],
            ['id' => 'media', 'label' => __('Medios', 'flavor-chat-ia'), 'icon' => 'dashicons-format-image'],
            ['id' => 'forms', 'label' => __('Formularios', 'flavor-chat-ia'), 'icon' => 'dashicons-feedback'],
            ['id' => 'widgets', 'label' => __('Widgets', 'flavor-chat-ia'), 'icon' => 'dashicons-screenoptions'],
            ['id' => 'advanced', 'label' => __('Avanzado', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-tools'],
            ['id' => 'general', 'label' => __('General', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-generic'],
        ]);
    }

    /**
     * Obtener datos de componentes para JS
     */
    private function get_components_data() {
        $registry = Flavor_Component_Registry::get_instance();
        $components = $registry->get_components();

        $data = [];
        foreach ($components as $id => $component) {
            $component_data = [
                'label' => $component['label'],
                'icon' => $component['icon'],
                'fields' => $component['fields'],
            ];

            // Incluir variants si existen
            if (!empty($component['variants'])) {
                $component_data['variants'] = $component['variants'];
            }

            // Incluir presets si existen
            if (!empty($component['presets'])) {
                $component_data['presets'] = $component['presets'];
            }

            // Marcar como deprecated si aplica
            if (!empty($component['deprecated'])) {
                $component_data['deprecated'] = true;
            }

            $data[$id] = $component_data;
        }

        return $data;
    }

    /**
     * Enqueue scripts del frontend
     */
    public function enqueue_frontend_scripts() {
        // Tailwind CSS
        wp_enqueue_style(
            'tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            [],
            '2.2.19'
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Estilos personalizados de componentes (en el plugin principal)
        wp_enqueue_style(
            'flavor-components',
            FLAVOR_CHAT_IA_URL . "assets/css/components{$sufijo_asset}.css",
            ['tailwindcss'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Scripts de componentes interactivos (en el plugin principal)
        wp_enqueue_script(
            'flavor-components',
            FLAVOR_CHAT_IA_URL . "assets/js/components{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );
    }

    /**
     * Renderizar contenido de página construida
     */
    /**
     * AJAX: Obtener campos de componente
     */
    public function ajax_get_component_fields() {
        check_ajax_referer('flavor_page_builder', 'nonce');

        $component_id = sanitize_text_field($_POST['component_id'] ?? '');
        $registry = Flavor_Component_Registry::get_instance();
        $fields = $registry->get_component_fields_schema($component_id);

        wp_send_json_success(['fields' => $fields]);
    }

    /**
     * Renderizar librería de plantillas
     */
    private function render_templates_library() {
        $templates = $this->get_template_library();

        foreach ($templates as $sector => $sector_templates) {
            ?>
            <div class="flavor-pb-template-sector">
                <h4><?php echo esc_html($sector_templates['label']); ?></h4>
                <div class="flavor-pb-templates-grid">
                    <?php foreach ($sector_templates['templates'] as $template_id => $template): ?>
                        <div class="flavor-pb-template-card" data-template-id="<?php echo esc_attr($template_id); ?>">
                            <div class="flavor-pb-template-preview">
                                <?php if (!empty($template['preview'])): ?>
                                    <img src="<?php echo esc_url($template['preview']); ?>" alt="">
                                <?php else: ?>
                                    <div class="flavor-pb-template-icon">
                                        <span class="dashicons <?php echo esc_attr($template['icon']); ?>"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-pb-template-info">
                                <h5><?php echo esc_html($template['name']); ?></h5>
                                <p><?php echo esc_html($template['description']); ?></p>
                                <button type="button" class="button button-primary flavor-pb-use-template">
                                    <?php _e('Usar Plantilla', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Obtener librería de plantillas por sectores
     */
    private function get_template_library() {
        return [
            'movilidad' => [
                'label' => __('Movilidad', 'flavor-chat-ia'),
                'templates' => [
                    'carpooling_landing' => [
                        'name' => __('Landing Carpooling', 'flavor-chat-ia'),
                        'description' => __('Página completa para compartir viajes', 'flavor-chat-ia'),
                        'icon' => 'dashicons-car',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'carpooling_hero',
                                'data' => [
                                    'titulo' => 'Comparte tu Viaje',
                                    'subtitulo' => 'Ahorra dinero y reduce tu huella de carbono compartiendo coche con tus vecinos',
                                    'imagen_fondo' => '',
                                    'mostrar_buscador' => true,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'carpooling_viajes_grid',
                                'data' => [
                                    'titulo' => 'Viajes Disponibles',
                                    'columnas' => 3,
                                    'limite' => 9,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'carpooling_como_funciona',
                                'data' => [
                                    'titulo' => 'Cómo Funciona',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'carpooling_cta_conductor',
                                'data' => [
                                    'titulo' => '¿Tienes un viaje programado?',
                                    'descripcion' => 'Publica tu ruta y comparte gastos con otros viajeros',
                                    'boton_texto' => 'Publicar Viaje',
                                    'boton_url' => '#',
                                    'color_fondo' => '#3b82f6',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'bicicletas_landing' => [
                        'name' => __('Landing Bicicletas', 'flavor-chat-ia'),
                        'description' => __('Página para sistema de bicicletas compartidas', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-site',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'bicicletas_compartidas_hero',
                                'data' => [
                                    'titulo' => 'Bicicletas Compartidas',
                                    'subtitulo' => 'Movilidad sostenible y saludable para tu comunidad',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'bicicletas_compartidas_mapa',
                                'data' => [
                                    'titulo' => 'Encuentra tu Estación',
                                    'altura_mapa' => 400,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'bicicletas_compartidas_como_funciona',
                                'data' => [
                                    'titulo' => '¿Cómo funciona?',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'bicicletas_compartidas_tarifas',
                                'data' => [
                                    'titulo' => 'Tarifas',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'parkings_landing' => [
                        'name' => __('Landing Parkings', 'flavor-chat-ia'),
                        'description' => __('Página para compartir plazas de parking', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-multisite',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'hero_parkings',
                                'data' => [
                                    'titulo' => 'Parkings Compartidos',
                                    'subtitulo' => 'Alquila o comparte tu plaza de parking',
                                    'imagen_fondo' => '',
                                    'mostrar_buscador' => true,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'parkings_grid',
                                'data' => [
                                    'titulo' => 'Plazas Disponibles',
                                    'columnas' => 3,
                                    'limite' => 9,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'cta_propietario',
                                'data' => [
                                    'titulo' => '¿Tienes una Plaza Libre?',
                                    'descripcion' => 'Genera ingresos extras compartiendo tu parking',
                                    'boton_texto' => 'Publicar mi Plaza',
                                    'boton_url' => '#',
                                    'color_fondo' => '#10b981',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'educacion' => [
                'label' => __('Educación', 'flavor-chat-ia'),
                'templates' => [
                    'cursos_landing' => [
                        'name' => __('Landing Cursos', 'flavor-chat-ia'),
                        'description' => __('Plataforma de cursos comunitarios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-welcome-learn-more',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'cursos_hero',
                                'data' => [
                                    'titulo' => 'Cursos y Talleres',
                                    'subtitulo' => 'Aprende nuevas habilidades con tu comunidad',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'cursos_categorias',
                                'data' => [
                                    'titulo' => 'Explora por Categoría',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'cursos_grid',
                                'data' => [
                                    'titulo' => 'Cursos Disponibles',
                                    'columnas' => 3,
                                    'limite' => 6,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'cursos_cta_instructor',
                                'data' => [
                                    'titulo' => '¿Quieres impartir un curso?',
                                    'descripcion' => 'Comparte tu conocimiento con la comunidad',
                                    'boton_texto' => 'Proponer Curso',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'biblioteca_landing' => [
                        'name' => __('Landing Biblioteca', 'flavor-chat-ia'),
                        'description' => __('Biblioteca comunitaria de libros', 'flavor-chat-ia'),
                        'icon' => 'dashicons-book',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'biblioteca_hero',
                                'data' => [
                                    'titulo' => 'Biblioteca Comunitaria',
                                    'subtitulo' => 'Comparte y descubre libros con tus vecinos',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'biblioteca_buscador',
                                'data' => [
                                    'titulo' => 'Buscar Libros',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'biblioteca_libros_grid',
                                'data' => [
                                    'titulo' => 'Libros Disponibles',
                                    'columnas' => 4,
                                    'limite' => 8,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'biblioteca_como_funciona',
                                'data' => [
                                    'titulo' => '¿Cómo funciona?',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'biblioteca_cta_donar',
                                'data' => [
                                    'titulo' => '¿Tienes libros que ya no lees?',
                                    'descripcion' => 'Dónalos a la biblioteca y compártelos con la comunidad',
                                    'boton_texto' => 'Donar Libro',
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                        ],
                    ],
                    'talleres_landing' => [
                        'name' => __('Landing Talleres', 'flavor-chat-ia'),
                        'description' => __('Talleres prácticos comunitarios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-hammer',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'hero_talleres',
                                'data' => [
                                    'titulo' => 'Talleres Prácticos',
                                    'subtitulo' => 'Aprende nuevas habilidades con tu comunidad',
                                    'imagen_fondo' => '',
                                    'mostrar_buscador' => true,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'talleres_grid',
                                'data' => [
                                    'titulo' => 'Próximos Talleres',
                                    'columnas' => 3,
                                    'limite' => 9,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'categorias_talleres',
                                'data' => [
                                    'titulo' => 'Explora por Categoría',
                                    'estilo' => 'grid',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                ],
            ],
            'medio_ambiente' => [
                'label' => __('Medio Ambiente', 'flavor-chat-ia'),
                'templates' => [
                    'huertos_landing' => [
                        'name' => __('Landing Huertos', 'flavor-chat-ia'),
                        'description' => __('Huertos urbanos comunitarios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-carrot',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'huertos_urbanos_hero',
                                'data' => [
                                    'titulo' => 'Huertos Urbanos',
                                    'subtitulo' => 'Cultiva tu propio huerto en la ciudad',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'huertos_urbanos_mapa',
                                'data' => [
                                    'titulo' => 'Encuentra tu Huerto',
                                    'altura_mapa' => 400,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'huertos_urbanos_parcelas',
                                'data' => [
                                    'titulo' => 'Parcelas Disponibles',
                                    'columnas' => 3,
                                    'limite' => 6,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'huertos_urbanos_beneficios',
                                'data' => [
                                    'titulo' => 'Beneficios de Tener un Huerto',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'huertos_urbanos_cta',
                                'data' => [
                                    'titulo' => '¿Quieres tu propia parcela?',
                                    'descripcion' => 'Solicita tu huerto y empieza a cultivar',
                                    'boton_texto' => 'Solicitar Parcela',
                                ],
                                'settings' => ['background' => 'green'],
                            ],
                        ],
                    ],
                    'reciclaje_landing' => [
                        'name' => __('Landing Reciclaje', 'flavor-chat-ia'),
                        'description' => __('Sistema de reciclaje comunitario', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-site',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'reciclaje_hero',
                                'data' => [
                                    'titulo' => 'Reciclaje',
                                    'subtitulo' => 'Reduce, reutiliza, recicla',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'reciclaje_puntos_mapa',
                                'data' => [
                                    'titulo' => 'Puntos de Reciclaje',
                                    'altura_mapa' => 400,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'reciclaje_guia',
                                'data' => [
                                    'titulo' => 'Guía de Reciclaje',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'reciclaje_estadisticas',
                                'data' => [
                                    'titulo' => 'Nuestro Impacto',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'reciclaje_consejos',
                                'data' => [
                                    'titulo' => 'Consejos para Reciclar Mejor',
                                ],
                                'settings' => ['background' => 'green-light'],
                            ],
                        ],
                    ],
                    'compostaje_landing' => [
                        'name' => __('Landing Compostaje', 'flavor-chat-ia'),
                        'description' => __('Compostaje comunitario', 'flavor-chat-ia'),
                        'icon' => 'dashicons-carrot',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'hero_compostaje',
                                'data' => [
                                    'titulo' => 'Compostaje Comunitario',
                                    'subtitulo' => 'Convierte residuos orgánicos en abono natural',
                                    'imagen_fondo' => '',
                                    'mostrar_impacto' => true,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'mapa_composteras',
                                'data' => [
                                    'titulo' => 'Encuentra tu Compostera',
                                    'altura_mapa' => 500,
                                    'mostrar_estado' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'guia_compostaje',
                                'data' => [
                                    'titulo' => 'Qué Compostar',
                                    'estilo' => 'tarjetas',
                                ],
                                'settings' => ['background' => 'white'],
                            ],
                            [
                                'component_id' => 'proceso_compostaje',
                                'data' => [
                                    'titulo' => 'Cómo Funciona',
                                    'mostrar_fases' => true,
                                ],
                                'settings' => ['background' => 'green-light'],
                            ],
                        ],
                    ],
                ],
            ],
            'comunidad' => [
                'label' => __('Comunidad', 'flavor-chat-ia'),
                'templates' => [
                    'espacios_landing' => [
                        'name' => __('Landing Espacios Comunes', 'flavor-chat-ia'),
                        'description' => __('Reserva de espacios comunitarios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-building',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'espacios_comunes_hero',
                                'data' => [
                                    'titulo' => 'Espacios Comunes',
                                    'subtitulo' => 'Reserva salas y espacios para tus actividades',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'espacios_comunes_listado',
                                'data' => [
                                    'titulo' => 'Espacios Disponibles',
                                    'columnas' => 3,
                                    'limite' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'espacios_comunes_calendario',
                                'data' => [
                                    'titulo' => 'Disponibilidad',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'espacios_comunes_como_reservar',
                                'data' => [
                                    'titulo' => '¿Cómo reservar?',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'ayuda_vecinal_landing' => [
                        'name' => __('Landing Ayuda Vecinal', 'flavor-chat-ia'),
                        'description' => __('Red de ayuda entre vecinos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-heart',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'ayuda_vecinal_hero',
                                'data' => [
                                    'titulo' => 'Ayuda Vecinal',
                                    'subtitulo' => 'Vecinos que ayudan a vecinos',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'ayuda_vecinal_solicitudes',
                                'data' => [
                                    'titulo' => 'Solicitudes de Ayuda',
                                    'columnas' => 2,
                                    'limite' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'ayuda_vecinal_ofertas',
                                'data' => [
                                    'titulo' => 'Vecinos que Ofrecen Ayuda',
                                    'columnas' => 3,
                                    'limite' => 6,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'ayuda_vecinal_categorias',
                                'data' => [
                                    'titulo' => 'Tipos de Ayuda',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'ayuda_vecinal_cta',
                                'data' => [
                                    'titulo' => '¿Necesitas ayuda o quieres ayudar?',
                                    'boton_texto' => 'Publicar Solicitud',
                                ],
                                'settings' => ['background' => 'orange'],
                            ],
                        ],
                    ],
                    'grupos_consumo_landing' => [
                        'name' => __('Landing Grupos de Consumo', 'flavor-chat-ia'),
                        'description' => __('Grupos de consumo local y ecológico', 'flavor-chat-ia'),
                        'icon' => 'dashicons-carrot',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'grupos_consumo_hero',
                                'data' => [
                                    'titulo' => 'Grupos de Consumo',
                                    'subtitulo' => 'Consume local, apoya a productores cercanos',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'grupos_consumo_listado',
                                'data' => [
                                    'titulo' => 'Grupos Disponibles',
                                    'columnas' => 3,
                                    'limite' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'grupos_consumo_productores',
                                'data' => [
                                    'titulo' => 'Nuestros Productores',
                                    'limite' => 6,
                                    'mostrar_ubicacion' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'grupos_consumo_como_funciona',
                                'data' => [
                                    'titulo' => '¿Cómo Funciona?',
                                    'pasos' => ['unirse', 'pedir', 'recoger'],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'grupos_consumo_proximo_pedido',
                                'data' => [
                                    'titulo' => 'Próximo Pedido',
                                    'mostrar_cuenta_atras' => true,
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'grupos_consumo_cta_unirse',
                                'data' => [
                                    'titulo' => '¿Quieres unirte?',
                                    'subtitulo' => 'Forma parte de un grupo de consumo y disfruta de productos locales y de temporada',
                                    'texto_boton' => 'Unirse a un Grupo',
                                    'url_boton' => '/unirse-grupo-consumo',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'banco_tiempo_landing' => [
                        'name' => __('Landing Banco de Tiempo', 'flavor-chat-ia'),
                        'description' => __('Intercambio de servicios y habilidades', 'flavor-chat-ia'),
                        'icon' => 'dashicons-clock',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'banco_tiempo_hero',
                                'data' => [
                                    'titulo' => 'Banco de Tiempo',
                                    'subtitulo' => 'Intercambia habilidades con tu comunidad',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'banco_tiempo_categorias',
                                'data' => [
                                    'titulo' => 'Categorías de Servicios',
                                    'categorias' => ['hogar', 'cuidados', 'educacion', 'tecnologia', 'creatividad', 'otros'],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'banco_tiempo_servicios',
                                'data' => [
                                    'titulo' => 'Servicios Disponibles',
                                    'tipo' => 'todos',
                                    'limite' => 8,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'banco_tiempo_como_funciona',
                                'data' => [
                                    'titulo' => '¿Cómo Funciona?',
                                    'pasos' => ['registrate', 'ofrece', 'solicita', 'intercambia'],
                                ],
                                'settings' => ['background' => 'white'],
                            ],
                            [
                                'component_id' => 'banco_tiempo_estadisticas',
                                'data' => [
                                    'titulo' => 'Nuestra Comunidad',
                                    'mostrar_usuarios' => true,
                                    'mostrar_horas_intercambiadas' => true,
                                    'mostrar_servicios' => true,
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'banco_tiempo_cta_unirse',
                                'data' => [
                                    'titulo' => '¿Tienes habilidades que compartir?',
                                    'subtitulo' => 'Únete al banco de tiempo y empieza a intercambiar servicios',
                                    'texto_boton' => 'Registrarme',
                                    'url_boton' => '/registro-banco-tiempo',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'comunidades_landing' => [
                        'name' => __('Landing Comunidades', 'flavor-chat-ia'),
                        'description' => __('Red de comunidades vecinales', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'comunidades_hero',
                                'data' => [
                                    'titulo' => 'Comunidades',
                                    'subtitulo' => 'Conecta con tu vecindario',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'comunidades_listado',
                                'data' => [
                                    'titulo' => 'Comunidades Activas',
                                    'tipo' => 'todas',
                                    'limite' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'comunidades_mapa',
                                'data' => [
                                    'titulo' => 'Encuentra tu Comunidad',
                                    'altura_mapa' => 400,
                                    'mostrar_mi_ubicacion' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'comunidades_actividad_reciente',
                                'data' => [
                                    'titulo' => 'Actividad Reciente',
                                    'limite' => 10,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'comunidades_estadisticas',
                                'data' => [
                                    'titulo' => 'En Números',
                                    'mostrar_comunidades' => true,
                                    'mostrar_vecinos' => true,
                                    'mostrar_eventos' => true,
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'comunidades_cta_crear',
                                'data' => [
                                    'titulo' => '¿No encuentras tu comunidad?',
                                    'subtitulo' => 'Crea una nueva comunidad y conecta con tus vecinos',
                                    'texto_boton' => 'Crear Comunidad',
                                    'url_boton' => '/crear-comunidad',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'incidencias_landing' => [
                        'name' => __('Landing Incidencias', 'flavor-chat-ia'),
                        'description' => __('Sistema de reporte de incidencias', 'flavor-chat-ia'),
                        'icon' => 'dashicons-warning',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'incidencias_hero',
                                'data' => [
                                    'titulo' => 'Reportar Incidencias',
                                    'subtitulo' => 'Ayúdanos a mejorar tu barrio reportando problemas',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'incidencias_mapa',
                                'data' => [
                                    'titulo' => 'Mapa de Incidencias',
                                    'descripcion' => 'Visualiza las incidencias reportadas en tu zona',
                                    'zoom_inicial' => 14,
                                    'mostrar_filtros' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'incidencias_categorias',
                                'data' => [
                                    'titulo' => 'Tipos de Incidencias',
                                    'categorias' => ['alumbrado', 'baches', 'limpieza', 'mobiliario', 'arbolado', 'otros'],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'incidencias_grid',
                                'data' => [
                                    'titulo' => 'Últimas Incidencias',
                                    'limite' => 6,
                                    'mostrar_estado' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'incidencias_estadisticas',
                                'data' => [
                                    'titulo' => 'Estadísticas',
                                    'mostrar_resueltas' => true,
                                    'mostrar_pendientes' => true,
                                    'mostrar_tiempo_medio' => true,
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'incidencias_cta_reportar',
                                'data' => [
                                    'titulo' => '¿Has visto algún problema?',
                                    'subtitulo' => 'Reporta incidencias en tu barrio de forma rápida y sencilla',
                                    'texto_boton' => 'Reportar Incidencia',
                                    'url_boton' => '/reportar-incidencia',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'tienda_local_landing' => [
                        'name' => __('Landing Tienda Local', 'flavor-chat-ia'),
                        'description' => __('Comercios y tiendas locales', 'flavor-chat-ia'),
                        'icon' => 'dashicons-store',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'tienda_local_hero',
                                'data' => [
                                    'titulo' => 'Comercios Locales',
                                    'subtitulo' => 'Apoya el comercio de tu barrio',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'tienda_local_buscador',
                                'data' => [
                                    'placeholder' => 'Busca comercios, productos o servicios...',
                                    'mostrar_filtros' => true,
                                    'filtros' => ['categoria', 'barrio', 'abierto_ahora'],
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'tienda_local_categorias',
                                'data' => [
                                    'titulo' => 'Explora por Categoría',
                                    'categorias' => ['alimentacion', 'moda', 'servicios', 'restauracion', 'salud', 'hogar'],
                                    'mostrar_iconos' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'tienda_local_destacados',
                                'data' => [
                                    'titulo' => 'Comercios Destacados',
                                    'limite' => 6,
                                    'mostrar_valoraciones' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'tienda_local_mapa',
                                'data' => [
                                    'titulo' => 'Encuentra Comercios Cerca de Ti',
                                    'zoom_inicial' => 15,
                                    'mostrar_mi_ubicacion' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'tienda_local_ofertas',
                                'data' => [
                                    'titulo' => 'Ofertas y Promociones',
                                    'limite' => 4,
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'tienda_local_cta_registrar',
                                'data' => [
                                    'titulo' => '¿Tienes un comercio local?',
                                    'subtitulo' => 'Registra tu negocio y conecta con vecinos de tu zona',
                                    'texto_boton' => 'Registrar mi Comercio',
                                    'url_boton' => '/registrar-comercio',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'gobierno' => [
                'label' => __('Gobierno / Institucional', 'flavor-chat-ia'),
                'templates' => [
                    'ayuntamiento_landing' => [
                        'name' => __('Landing Ayuntamiento', 'flavor-chat-ia'),
                        'description' => __('Portal ciudadano municipal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-building',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'ayuntamiento_hero',
                                'data' => [
                                    'titulo' => 'Portal Ciudadano',
                                    'subtitulo' => 'Tu ayuntamiento a un clic',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'ayuntamiento_tramites',
                                'data' => [
                                    'titulo' => 'Trámites más solicitados',
                                    'limite' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'ayuntamiento_noticias',
                                'data' => [
                                    'titulo' => 'Últimas Noticias',
                                    'limite' => 4,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                ],
            ],
            'comunicacion' => [
                'label' => __('Comunicación', 'flavor-chat-ia'),
                'templates' => [
                    'podcast_landing' => [
                        'name' => __('Landing Podcast', 'flavor-chat-ia'),
                        'description' => __('Plataforma de podcasting comunitario', 'flavor-chat-ia'),
                        'icon' => 'dashicons-microphone',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'podcast_hero',
                                'data' => [
                                    'titulo' => 'Nuestro Podcast',
                                    'subtitulo' => 'Voces de la comunidad',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'podcast_ultimo_episodio',
                                'data' => [
                                    'titulo' => 'Último Episodio',
                                    'mostrar_player' => true,
                                    'mostrar_descripcion' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'podcast_episodios_grid',
                                'data' => [
                                    'titulo' => 'Todos los Episodios',
                                    'limite' => 9,
                                    'columnas' => 3,
                                    'mostrar_duracion' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'podcast_categorias',
                                'data' => [
                                    'titulo' => 'Temáticas',
                                    'categorias' => ['entrevistas', 'cultura', 'actualidad', 'deportes', 'historia'],
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'podcast_presentadores',
                                'data' => [
                                    'titulo' => 'Nuestros Presentadores',
                                    'limite' => 4,
                                    'mostrar_bio' => true,
                                ],
                                'settings' => ['background' => 'white'],
                            ],
                            [
                                'component_id' => 'podcast_suscribir',
                                'data' => [
                                    'titulo' => 'Suscríbete',
                                    'subtitulo' => 'Escúchanos en tu plataforma favorita',
                                    'plataformas' => ['spotify', 'apple_podcasts', 'google_podcasts', 'ivoox', 'rss'],
                                ],
                                'settings' => ['background' => 'primary'],
                            ],
                            [
                                'component_id' => 'podcast_cta_participar',
                                'data' => [
                                    'titulo' => '¿Quieres participar?',
                                    'subtitulo' => 'Envíanos tus preguntas, sugerencias o propuestas de temas',
                                    'texto_boton' => 'Contactar',
                                    'url_boton' => '/contacto-podcast',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'radio_landing' => [
                        'name' => __('Landing Radio', 'flavor-chat-ia'),
                        'description' => __('Radio comunitaria en vivo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-microphone',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'radio_hero',
                                'data' => [
                                    'titulo' => 'Radio Comunitaria',
                                    'subtitulo' => 'La voz de tu barrio',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'radio_player_en_vivo',
                                'data' => [
                                    'titulo' => 'Escucha en Vivo',
                                    'mostrar_programa_actual' => true,
                                    'mostrar_siguiente' => true,
                                    'url_stream' => '',
                                ],
                                'settings' => ['padding' => 'large', 'background' => 'primary'],
                            ],
                            [
                                'component_id' => 'radio_programacion',
                                'data' => [
                                    'titulo' => 'Programación Semanal',
                                    'mostrar_dias' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'],
                                    'formato_hora' => '24h',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'radio_programas',
                                'data' => [
                                    'titulo' => 'Nuestros Programas',
                                    'limite' => 6,
                                    'mostrar_horario' => true,
                                    'mostrar_descripcion' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'radio_locutores',
                                'data' => [
                                    'titulo' => 'El Equipo',
                                    'limite' => 8,
                                    'mostrar_programa' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'radio_archivo',
                                'data' => [
                                    'titulo' => 'Programas Anteriores',
                                    'limite' => 6,
                                    'mostrar_player' => true,
                                ],
                                'settings' => ['background' => 'white'],
                            ],
                            [
                                'component_id' => 'radio_cta_colaborar',
                                'data' => [
                                    'titulo' => '¿Quieres colaborar?',
                                    'subtitulo' => 'Únete a la radio comunitaria como locutor, técnico o colaborador',
                                    'texto_boton' => 'Quiero Participar',
                                    'url_boton' => '/colaborar-radio',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'multimedia_landing' => [
                        'name' => __('Landing Multimedia', 'flavor-chat-ia'),
                        'description' => __('Galería de fotos y videos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-format-gallery',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'hero_multimedia',
                                'data' => [
                                    'titulo' => 'Galería Comunitaria',
                                    'subtitulo' => 'Momentos y recuerdos de nuestra comunidad',
                                    'imagen_fondo' => '',
                                    'mostrar_contador' => true,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'carousel_destacado',
                                'data' => [
                                    'titulo' => 'Momentos Destacados',
                                    'autoplay' => true,
                                    'intervalo_segundos' => 5,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'galeria_grid',
                                'data' => [
                                    'titulo' => 'Galería de Fotos',
                                    'columnas' => 4,
                                    'limite' => 12,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'albumes',
                                'data' => [
                                    'titulo' => 'Álbumes de la Comunidad',
                                    'columnas' => 3,
                                    'limite' => 9,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                ],
            ],
            'empresarial' => [
                'label' => __('Empresarial', 'flavor-chat-ia'),
                'templates' => [
                    'corporate_landing' => [
                        'name' => __('Landing Corporativa', 'flavor-chat-ia'),
                        'description' => __('Página corporativa profesional completa', 'flavor-chat-ia'),
                        'icon' => 'dashicons-building',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'empresarial_hero',
                                'data' => [
                                    'titulo' => 'Soluciones Empresariales de Calidad',
                                    'subtitulo' => 'Potencia tu negocio con nuestros servicios profesionales y tecnología de vanguardia',
                                    'texto_boton_principal' => 'Solicitar Demo',
                                    'url_boton_principal' => '#contacto',
                                    'texto_boton_secundario' => 'Ver Servicios',
                                    'url_boton_secundario' => '#servicios',
                                    'mostrar_video' => false,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_stats',
                                'data' => [
                                    'titulo_seccion' => 'Resultados que Hablan por Sí Solos',
                                    'estilo' => 'highlighted',
                                    'stat_1_numero' => '500+',
                                    'stat_1_texto' => 'Clientes Satisfechos',
                                    'stat_2_numero' => '15+',
                                    'stat_2_texto' => 'Años de Experiencia',
                                    'stat_3_numero' => '98%',
                                    'stat_3_texto' => 'Tasa de Satisfacción',
                                    'stat_4_numero' => '24/7',
                                    'stat_4_texto' => 'Soporte Disponible',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_servicios',
                                'data' => [
                                    'titulo_seccion' => 'Nuestros Servicios',
                                    'descripcion_seccion' => 'Soluciones integrales diseñadas para hacer crecer tu negocio',
                                    'columnas' => '3',
                                    'estilo' => 'cards',
                                    'numero_servicios' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'empresarial_testimonios',
                                'data' => [
                                    'titulo_seccion' => 'Lo Que Dicen Nuestros Clientes',
                                    'layout' => 'carousel',
                                    'numero_testimonios' => 6,
                                    'mostrar_foto' => true,
                                    'mostrar_empresa' => true,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'empresarial_contacto',
                                'data' => [
                                    'titulo_seccion' => 'Contacta con Nosotros',
                                    'descripcion_seccion' => 'Estamos aquí para ayudarte. Envíanos tu consulta y te responderemos pronto.',
                                    'layout' => 'dos_columnas',
                                    'mostrar_telefono' => true,
                                    'telefono' => '+34 900 000 000',
                                    'mostrar_direccion' => true,
                                    'direccion' => 'Calle Principal 123, 28001 Madrid, España',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'services_landing' => [
                        'name' => __('Landing Servicios', 'flavor-chat-ia'),
                        'description' => __('Página de presentación de servicios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-grid-view',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'empresarial_hero',
                                'data' => [
                                    'titulo' => 'Nuestros Servicios Profesionales',
                                    'subtitulo' => 'Soluciones adaptadas a las necesidades de tu empresa',
                                    'texto_boton_principal' => 'Ver Todos los Servicios',
                                    'url_boton_principal' => '#servicios',
                                    'texto_boton_secundario' => 'Contactar',
                                    'url_boton_secundario' => '#contacto',
                                    'mostrar_video' => false,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_servicios',
                                'data' => [
                                    'titulo_seccion' => 'Catálogo de Servicios',
                                    'descripcion_seccion' => 'Descubre todas nuestras soluciones empresariales',
                                    'columnas' => '3',
                                    'estilo' => 'cards',
                                    'numero_servicios' => 6,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'empresarial_pricing',
                                'data' => [
                                    'titulo_seccion' => 'Planes y Precios',
                                    'descripcion_seccion' => 'Elige el plan perfecto para tu negocio',
                                    'numero_planes' => '3',
                                    'periodo' => 'mensual',
                                    'destacar_plan' => 2,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'empresarial_portfolio',
                                'data' => [
                                    'titulo_seccion' => 'Casos de Éxito',
                                    'descripcion_seccion' => 'Proyectos que transformaron negocios',
                                    'layout' => 'masonry',
                                    'columnas' => '3',
                                    'numero_proyectos' => 6,
                                    'mostrar_filtros' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                        ],
                    ],
                    'team_landing' => [
                        'name' => __('Landing Equipo', 'flavor-chat-ia'),
                        'description' => __('Página sobre nosotros y equipo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'empresarial_hero',
                                'data' => [
                                    'titulo' => 'Conoce Nuestro Equipo',
                                    'subtitulo' => 'Profesionales apasionados comprometidos con tu éxito',
                                    'texto_boton_principal' => 'Únete al Equipo',
                                    'url_boton_principal' => '#trabaja',
                                    'texto_boton_secundario' => 'Contactar',
                                    'url_boton_secundario' => '#contacto',
                                    'mostrar_video' => true,
                                    'url_video' => '',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_stats',
                                'data' => [
                                    'titulo_seccion' => 'Nuestra Trayectoria',
                                    'estilo' => 'cards',
                                    'stat_1_numero' => '50+',
                                    'stat_1_texto' => 'Profesionales',
                                    'stat_2_numero' => '15+',
                                    'stat_2_texto' => 'Años de Experiencia',
                                    'stat_3_numero' => '30+',
                                    'stat_3_texto' => 'Países',
                                    'stat_4_numero' => '95%',
                                    'stat_4_texto' => 'Retención de Talento',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_equipo',
                                'data' => [
                                    'titulo_seccion' => 'Nuestro Equipo',
                                    'descripcion_seccion' => 'Conoce a las personas que hacen posible nuestro éxito',
                                    'layout' => 'grid',
                                    'columnas' => '4',
                                    'mostrar_redes_sociales' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'empresarial_testimonios',
                                'data' => [
                                    'titulo_seccion' => 'Lo Que Dice Nuestro Equipo',
                                    'layout' => 'grid',
                                    'numero_testimonios' => 6,
                                    'mostrar_foto' => true,
                                    'mostrar_empresa' => false,
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'contact_landing' => [
                        'name' => __('Landing Contacto', 'flavor-chat-ia'),
                        'description' => __('Página de contacto profesional', 'flavor-chat-ia'),
                        'icon' => 'dashicons-email',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'empresarial_hero',
                                'data' => [
                                    'titulo' => 'Hablemos de Tu Proyecto',
                                    'subtitulo' => 'Estamos aquí para ayudarte a hacer realidad tus ideas',
                                    'texto_boton_principal' => 'Solicitar Llamada',
                                    'url_boton_principal' => '#formulario',
                                    'texto_boton_secundario' => 'Chat en Vivo',
                                    'url_boton_secundario' => '#chat',
                                    'mostrar_video' => false,
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_contacto',
                                'data' => [
                                    'titulo_seccion' => 'Contáctanos',
                                    'descripcion_seccion' => 'Completa el formulario y te responderemos en menos de 24 horas',
                                    'layout' => 'con_mapa',
                                    'mostrar_telefono' => true,
                                    'telefono' => '+34 900 000 000',
                                    'mostrar_direccion' => true,
                                    'direccion' => 'Calle Principal 123, 28001 Madrid, España',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'empresarial_stats',
                                'data' => [
                                    'titulo_seccion' => 'Estamos Disponibles',
                                    'estilo' => 'minimal',
                                    'stat_1_numero' => '< 2h',
                                    'stat_1_texto' => 'Tiempo de Respuesta',
                                    'stat_2_numero' => '24/7',
                                    'stat_2_texto' => 'Soporte',
                                    'stat_3_numero' => '10+',
                                    'stat_3_texto' => 'Idiomas',
                                    'stat_4_numero' => '100%',
                                    'stat_4_texto' => 'Confidencialidad',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                ],
            ],
            'ofimatica' => [
                'label' => __('Ofimática / Productividad', 'flavor-chat-ia'),
                'templates' => [
                    'ofimatica_landing' => [
                        'name' => __('Landing Suite Ofimática', 'flavor-chat-ia'),
                        'description' => __('Página para suite de productividad en la nube', 'flavor-chat-ia'),
                        'icon' => 'dashicons-media-document',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'ofimatica_hero',
                                'data' => [
                                    'titulo' => 'Suite de Productividad',
                                    'subtitulo' => 'Documentos, hojas de cálculo y presentaciones en la nube',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'ofimatica_apps',
                                'data' => [
                                    'titulo' => 'Nuestras Aplicaciones',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'ofimatica_features',
                                'data' => [
                                    'titulo' => 'Características Principales',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'empresarial_pricing',
                                'data' => [
                                    'titulo' => 'Planes y Precios',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'saas' => [
                'label' => __('SaaS / Software', 'flavor-chat-ia'),
                'templates' => [
                    'saas_landing' => [
                        'name' => __('Landing SaaS', 'flavor-chat-ia'),
                        'description' => __('Página para producto de software como servicio', 'flavor-chat-ia'),
                        'icon' => 'dashicons-cloud',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'saas_hero',
                                'data' => [
                                    'titulo' => 'Transforma tu negocio con tecnología',
                                    'subtitulo' => 'Automatiza procesos, mejora la colaboración y escala tu empresa',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'saas_features',
                                'data' => [
                                    'titulo' => 'Todo lo que necesitas en un solo lugar',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'empresarial_pricing',
                                'data' => [
                                    'titulo' => 'Planes y Precios',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'empresarial_testimonios',
                                'data' => [
                                    'titulo' => 'Lo que dicen nuestros clientes',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_contacto',
                                'data' => [
                                    'titulo' => 'Contacta con nosotros',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                ],
            ],
            'agencia' => [
                'label' => __('Agencia / Creativo', 'flavor-chat-ia'),
                'templates' => [
                    'agencia_landing' => [
                        'name' => __('Landing Agencia Creativa', 'flavor-chat-ia'),
                        'description' => __('Página para agencia de diseño o marketing', 'flavor-chat-ia'),
                        'icon' => 'dashicons-art',
                        'preview' => '',
                        'layout' => [
                            [
                                'component_id' => 'agencia_hero',
                                'data' => [
                                    'titulo' => 'Diseñamos experiencias que inspiran',
                                    'subtitulo' => 'Branding, diseño web y estrategia digital para marcas que quieren destacar',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'agencia_portfolio',
                                'data' => [
                                    'titulo' => 'Proyectos Destacados',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'empresarial_testimonios',
                                'data' => [
                                    'titulo' => 'Lo que dicen nuestros clientes',
                                ],
                                'settings' => ['background' => 'dark'],
                            ],
                            [
                                'component_id' => 'empresarial_contacto',
                                'data' => [
                                    'titulo' => 'Hablemos de tu proyecto',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── THEMACLE: Plantillas de diseño web ───────────────

            'zunbeltz' => [
                'label' => __('Zunbeltz - Comunidad Ecológica', 'flavor-chat-ia'),
                'templates' => [
                    'zunbeltz_inicio' => [
                        'name' => __('Página de Inicio', 'flavor-chat-ia'),
                        'description' => __('Landing principal para comunidad ecológica con estilo orgánico y natural', 'flavor-chat-ia'),
                        'icon' => 'dashicons-palmtree',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'zunbeltz',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Comunidad Ecológica Zunbeltz',
                                    'subtitulo' => 'Vivimos en armonía con la naturaleza. Únete a nuestra comunidad sostenible.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Conoce nuestro proyecto',
                                    'url_cta' => '#proyecto',
                                    'overlay_color' => '#1b3d1c',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Nuestros Pilares',
                                    'subtitulo' => 'Una forma de vida basada en el respeto al medio ambiente',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-carrot', 'titulo' => 'Agricultura Ecológica', 'descripcion' => 'Cultivamos alimentos sin pesticidas ni químicos'],
                                        ['icono' => 'dashicons-admin-home', 'titulo' => 'Bioconstrucción', 'descripcion' => 'Viviendas construidas con materiales naturales'],
                                        ['icono' => 'dashicons-groups', 'titulo' => 'Vida en Comunidad', 'descripcion' => 'Compartimos recursos y nos apoyamos mutuamente'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Nuestro Proyecto',
                                    'contenido' => 'Zunbeltz es una comunidad ecológica donde la sostenibilidad es el centro de todo lo que hacemos. Desde la producción de alimentos hasta la gestión de residuos, cada aspecto de nuestra vida está diseñado para minimizar nuestro impacto ambiental.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Actividades',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Talleres de Huerto', 'descripcion' => 'Aprende a cultivar tus propios alimentos', 'url' => '#'],
                                        ['titulo' => 'Mercado Ecológico', 'descripcion' => 'Productos frescos cada sábado', 'url' => '#'],
                                        ['titulo' => 'Voluntariado', 'descripcion' => 'Colabora en nuestros proyectos', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Quieres formar parte?',
                                    'subtitulo' => 'Visítanos y descubre cómo es vivir de forma sostenible',
                                    'texto_cta' => 'Agendar Visita',
                                    'url_cta' => '#contacto',
                                    'color_fondo' => '#2D5F2E',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_newsletter',
                                'data' => [
                                    'titulo' => 'Mantente informado',
                                    'subtitulo' => 'Recibe noticias sobre nuestras actividades y eventos',
                                    'texto_placeholder' => 'Tu email',
                                    'texto_boton' => 'Suscribirme',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'zunbeltz_proyectos' => [
                        'name' => __('Página de Proyectos', 'flavor-chat-ia'),
                        'description' => __('Listado de proyectos ecológicos de la comunidad', 'flavor-chat-ia'),
                        'icon' => 'dashicons-portfolio',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'zunbeltz',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestros Proyectos',
                                    'subtitulo' => 'Iniciativas que transforman nuestra comunidad y el entorno',
                                    'texto_cta' => 'Participar',
                                    'url_cta' => '#participar',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f0f4ea',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Bosque Comestible', 'descripcion' => 'Reforestación con árboles frutales y plantas comestibles', 'url' => '#'],
                                        ['titulo' => 'Energía Solar', 'descripcion' => 'Instalación de paneles solares comunitarios', 'url' => '#'],
                                        ['titulo' => 'Compostera', 'descripcion' => 'Gestión de residuos orgánicos', 'url' => '#'],
                                        ['titulo' => 'Banco de Semillas', 'descripcion' => 'Preservación de variedades autóctonas', 'url' => '#'],
                                        ['titulo' => 'Agua y Riego', 'descripcion' => 'Sistema de recogida de agua de lluvia', 'url' => '#'],
                                        ['titulo' => 'Educación Ambiental', 'descripcion' => 'Talleres para escuelas y visitantes', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbers',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── NAARQ: Estudio de Arquitectura ───
            // Figma: Home, Projectes, Projectes Single, Nosaltres, Contacta, Procés de treball, Bloc, Bloc-Post
            'naarq' => [
                'label' => __('Naarq - Estudi d\'Arquitectura', 'flavor-chat-ia'),
                'templates' => [
                    'naarq_inicio' => [
                        'name' => __('Home', 'flavor-chat-ia'),
                        'description' => __('Pàgina principal minimalista amb slider de projectes', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-home',
                        'preview' => '',
                        'menu_type' => 'minimal',
                        'footer_type' => 'compact',
                        'theme' => 'naarq',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_slider',
                                'data' => [
                                    'slides' => [
                                        [
                                            'titulo' => 'Habitatge unifamiliar',
                                            'subtitulo' => 'Navarra, 2024',
                                            'imagen' => '',
                                            'texto_cta' => 'Veure projecte',
                                            'url_cta' => '#projecte-1',
                                        ],
                                        [
                                            'titulo' => 'Rehabilitació integral',
                                            'subtitulo' => 'Bilbao, 2023',
                                            'imagen' => '',
                                            'texto_cta' => 'Veure projecte',
                                            'url_cta' => '#projecte-2',
                                        ],
                                        [
                                            'titulo' => 'Equipament cultural',
                                            'subtitulo' => 'Donostia, 2023',
                                            'imagen' => '',
                                            'texto_cta' => 'Veure projecte',
                                            'url_cta' => '#projecte-3',
                                        ],
                                    ],
                                    'autoplay' => true,
                                    'intervalo' => 5000,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => '',
                                    'items' => [
                                        ['titulo' => 'Arquitectura Residencial', 'descripcion' => 'Habitatges que s\'adapten a la teva forma de viure', 'url' => '#residencial'],
                                        ['titulo' => 'Interiorisme', 'descripcion' => 'Disseny d\'interiors amb atenció al detall', 'url' => '#interiorisme'],
                                        ['titulo' => 'Rehabilitació', 'descripcion' => 'Transformació d\'espais existents', 'url' => '#rehabilitacio'],
                                    ],
                                    'estilo' => 'minimal',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Projectes seleccionats',
                                    'columnas' => 2,
                                    'estilo_card' => 'flat',
                                    'items' => [
                                        ['titulo' => 'Casa Pedra', 'descripcion' => 'Habitatge unifamiliar — Navarra, 2024', 'url' => '#'],
                                        ['titulo' => 'Loft Industrial', 'descripcion' => 'Rehabilitació — Bilbao, 2023', 'url' => '#'],
                                        ['titulo' => 'Centre Cultural', 'descripcion' => 'Equipament públic — Donostia, 2023', 'url' => '#'],
                                        ['titulo' => 'Apartaments Mar', 'descripcion' => 'Residencial — Hondarribia, 2022', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Filosofia',
                                    'contenido' => 'Creiem en l\'arquitectura com a resposta precisa al context. Cada projecte és una oportunitat per explorar la relació entre forma, funció i materialitat.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'overlay',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Tens un projecte?',
                                    'subtitulo' => 'Parlem. Cada projecte comença amb una conversa.',
                                    'texto_cta' => 'Contacta\'ns',
                                    'url_cta' => '#contacta',
                                    'color_fondo' => '#1a1a1a',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'naarq_projectes' => [
                        'name' => __('Projectes', 'flavor-chat-ia'),
                        'description' => __('Llistat de projectes amb filtre per categoria', 'flavor-chat-ia'),
                        'icon' => 'dashicons-portfolio',
                        'preview' => '',
                        'menu_type' => 'minimal',
                        'footer_type' => 'compact',
                        'theme' => 'naarq',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Projectes',
                                    'subtitulo' => 'Habitatge, rehabilitació, interiorisme',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f5f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'underline',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 2,
                                    'estilo_card' => 'flat',
                                    'items' => [
                                        ['titulo' => 'Casa Pedra', 'descripcion' => 'Habitatge unifamiliar — Navarra', 'url' => '#'],
                                        ['titulo' => 'Loft Industrial', 'descripcion' => 'Rehabilitació — Bilbao', 'url' => '#'],
                                        ['titulo' => 'Centre Cultural', 'descripcion' => 'Equipament — Donostia', 'url' => '#'],
                                        ['titulo' => 'Apartaments Mar', 'descripcion' => 'Residencial — Hondarribia', 'url' => '#'],
                                        ['titulo' => 'Edifici Comerç', 'descripcion' => 'Comercial — Iruña', 'url' => '#'],
                                        ['titulo' => 'Reforma Eixample', 'descripcion' => 'Interiorisme — Barcelona', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'simple'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'naarq_nosaltres' => [
                        'name' => __('Nosaltres', 'flavor-chat-ia'),
                        'description' => __('Pàgina sobre l\'estudi i l\'equip', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'minimal',
                        'footer_type' => 'compact',
                        'theme' => 'naarq',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nosaltres',
                                    'subtitulo' => 'Un estudi d\'arquitectura amb base a Navarra. Projectes que dialoguen amb el territori.',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'color_fondo' => '#f5f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Procés de treball',
                                    'contenido' => 'El nostre procés comença escoltant. Entenem el context, les necessitats i les aspiracions de cada projecte abans de dibuixar la primera línia. Treballem amb materials honestos i solucions que perduren.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Equip',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-admin-users', 'titulo' => 'Arquitectura', 'descripcion' => 'Disseny i direcció de projectes'],
                                        ['icono' => 'dashicons-admin-tools', 'titulo' => 'Construcció', 'descripcion' => 'Seguiment i execució d\'obra'],
                                        ['icono' => 'dashicons-art', 'titulo' => 'Interiorisme', 'descripcion' => 'Espais que inspiren'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'naarq_contacta' => [
                        'name' => __('Contacta', 'flavor-chat-ia'),
                        'description' => __('Pàgina de contacte amb mapa i formulari', 'flavor-chat-ia'),
                        'icon' => 'dashicons-email',
                        'preview' => '',
                        'menu_type' => 'minimal',
                        'footer_type' => 'compact',
                        'theme' => 'naarq',
                        'layout' => [
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Contacta',
                                    'direccion' => 'Carrer Major 15, Pamplona',
                                    'telefono' => '+34 948 000 000',
                                    'email' => 'info@naarq.com',
                                    'horario' => "Dilluns a divendres: 9:00 - 18:00",
                                    'mostrar_formulario' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                        ],
                    ],
                    'naarq_bloc' => [
                        'name' => __('Bloc', 'flavor-chat-ia'),
                        'description' => __('Llistat d\'articles del bloc', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-post',
                        'preview' => '',
                        'menu_type' => 'minimal',
                        'footer_type' => 'compact',
                        'theme' => 'naarq',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Bloc',
                                    'subtitulo' => 'Reflexions sobre arquitectura, materials i procés',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f5f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'flat',
                                    'items' => [
                                        ['titulo' => 'Materialitat i context', 'descripcion' => 'Com escollir els materials adequats per a cada projecte', 'url' => '#'],
                                        ['titulo' => 'Rehabilitació sostenible', 'descripcion' => 'Donar nova vida a edificis existents', 'url' => '#'],
                                        ['titulo' => 'Llum natural', 'descripcion' => 'L\'element clau en el disseny d\'espais', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbers'],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── CAMPI: Espai Cultural & Teatre ───
            // Figma: Inici, Espectacles, Espectacles Filtre, Espectacles Single, Contacte, Agenda, Menu
            'campi' => [
                'label' => __('Campi - Espai Cultural & Teatre', 'flavor-chat-ia'),
                'templates' => [
                    'campi_inicio' => [
                        'name' => __('Inici', 'flavor-chat-ia'),
                        'description' => __('Pàgina principal amb programació i agenda cultural', 'flavor-chat-ia'),
                        'icon' => 'dashicons-tickets-alt',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'campi',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_slider',
                                'data' => [
                                    'slides' => [
                                        [
                                            'titulo' => 'De petits espectacles',
                                            'subtitulo' => 'Temporada 2025/2026',
                                            'imagen' => '',
                                            'texto_cta' => 'Programació',
                                            'url_cta' => '#programacio',
                                        ],
                                        [
                                            'titulo' => 'Tallers de teatre',
                                            'subtitulo' => 'Per a totes les edats. Inscripcions obertes.',
                                            'imagen' => '',
                                            'texto_cta' => 'Inscriure\'s',
                                            'url_cta' => '#tallers',
                                        ],
                                        [
                                            'titulo' => 'Espai per a Esdeveniments',
                                            'subtitulo' => 'Lloga el nostre espai per al teu event',
                                            'imagen' => '',
                                            'texto_cta' => 'Més info',
                                            'url_cta' => '#esdeveniments',
                                        ],
                                    ],
                                    'autoplay' => true,
                                    'intervalo' => 6000,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Propers Espectacles',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'El Gran Teatre del Món', 'descripcion' => '15-17 Mar — Drama clàssic revisat', 'url' => '#'],
                                        ['titulo' => 'Improvisa!', 'descripcion' => '22 Mar — Show d\'improvisació', 'url' => '#'],
                                        ['titulo' => 'Dansa Contemporània', 'descripcion' => '28-30 Mar — Companyia convidada', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'El nostre espai',
                                    'contenido' => 'Campi és un espai cultural independent dedicat a les arts escèniques. Amb un aforament de 200 persones, oferim una experiència íntima i propera entre artistes i públic.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Tallers i Formació',
                                    'subtitulo' => 'Aprèn de professionals del teatre',
                                    'columnas' => 4,
                                    'items' => [
                                        ['icono' => 'dashicons-microphone', 'titulo' => 'Teatre Adults', 'descripcion' => 'Dimarts i dijous 19:00'],
                                        ['icono' => 'dashicons-smiley', 'titulo' => 'Teatre Infantil', 'descripcion' => 'Dissabtes 11:00'],
                                        ['icono' => 'dashicons-format-audio', 'titulo' => 'Improvisació', 'descripcion' => 'Dimecres 20:00'],
                                        ['icono' => 'dashicons-admin-appearance', 'titulo' => 'Dansa', 'descripcion' => 'Dilluns i divendres 18:00'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Fes-te Soci/a',
                                    'subtitulo' => 'Accedeix a descomptes, prevenda d\'entrades i esdeveniments exclusius',
                                    'texto_cta' => 'Vull ser soci/a',
                                    'url_cta' => '#socis',
                                    'color_fondo' => '#1a1b3a',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_newsletter',
                                'data' => [
                                    'titulo' => 'No et perdis res',
                                    'subtitulo' => 'Rep la nostra programació setmanal per email',
                                    'texto_placeholder' => 'El teu email',
                                    'texto_boton' => 'Subscriure\'m',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'campi_espectacles' => [
                        'name' => __('Espectacles', 'flavor-chat-ia'),
                        'description' => __('Llistat d\'espectacles amb filtres per categoria', 'flavor-chat-ia'),
                        'icon' => 'dashicons-format-video',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'campi',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Espectacles',
                                    'subtitulo' => 'Tota la programació del Campi',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#1a1b3a',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'El Gran Teatre del Món', 'descripcion' => '15-17 Mar — Drama', 'url' => '#'],
                                        ['titulo' => 'Improvisa!', 'descripcion' => '22 Mar — Improvisació', 'url' => '#'],
                                        ['titulo' => 'Dansa Contemporània', 'descripcion' => '28-30 Mar — Dansa', 'url' => '#'],
                                        ['titulo' => 'Monòleg Nocturn', 'descripcion' => '5 Abr — Comèdia', 'url' => '#'],
                                        ['titulo' => 'Petit Circ', 'descripcion' => '12 Abr — Circ', 'url' => '#'],
                                        ['titulo' => 'Jazz & Poesia', 'descripcion' => '19 Abr — Música', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'load-more'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'campi_agenda' => [
                        'name' => __('Agenda', 'flavor-chat-ia'),
                        'description' => __('Calendari d\'esdeveniments i activitats', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar-alt',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'campi',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Agenda',
                                    'subtitulo' => 'Tots els esdeveniments del Campi',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#1a1b3a',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Març 2026',
                                    'columnas' => 2,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => '15 Mar — El Gran Teatre del Món', 'descripcion' => '20:00h — Sala Principal', 'url' => '#'],
                                        ['titulo' => '22 Mar — Improvisa!', 'descripcion' => '21:00h — Sala Petita', 'url' => '#'],
                                        ['titulo' => '28 Mar — Dansa Contemporània', 'descripcion' => '19:30h — Sala Principal', 'url' => '#'],
                                        ['titulo' => '30 Mar — Taller obert', 'descripcion' => '11:00h — Sala d\'assaig', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'simple'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'campi_contacte' => [
                        'name' => __('Contacte', 'flavor-chat-ia'),
                        'description' => __('Informació de contacte i ubicació', 'flavor-chat-ia'),
                        'icon' => 'dashicons-location',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'campi',
                        'layout' => [
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Contacte',
                                    'direccion' => 'Carrer del Teatre 5, Barcelona',
                                    'telefono' => '+34 933 000 000',
                                    'email' => 'info@campi.cat',
                                    'horario' => "Taquilla: Dimarts a dissabte 17:00 - 21:00\nOficina: Dilluns a divendres 10:00 - 14:00",
                                    'mostrar_formulario' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Lloga el nostre espai',
                                    'subtitulo' => 'Per a events privats, assaigs o produccions',
                                    'texto_cta' => 'Demanar pressupost',
                                    'url_cta' => '#lloguer',
                                    'color_fondo' => '#1a1b3a',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── DENENDAKO: Kooperatiba / Herri Sarea ───
            // Figma: HASIERA, NOR GARA, AGENDA, AGENDA SINGLE, BAZKIDEAK, BAZKIDEAK SINGLE, ALBISTEAK, ALBISTEAK SINGLE, PARTAIDE EGIN, KONTAKTO
            'denendako' => [
                'label' => __('Denendako - Herri Sarea', 'flavor-chat-ia'),
                'templates' => [
                    'denendako_hasiera' => [
                        'name' => __('Hasiera (Inicio)', 'flavor-chat-ia'),
                        'description' => __('Orri nagusia herri sare baterako — Página principal para red vecinal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-home',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Denendako',
                                    'subtitulo' => 'Denon artean, denentzako. Herriko sarea, herriko indarra.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Ezagutu proiektua',
                                    'url_cta' => '#nor-gara',
                                    'overlay_color' => '#333333',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Zer eskaintzen dugu',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-calendar', 'titulo' => 'Agenda', 'descripcion' => 'Herriko ekitaldiak eta jarduerak'],
                                        ['icono' => 'dashicons-groups', 'titulo' => 'Bazkideak', 'descripcion' => 'Elkarte eta kolektiboak'],
                                        ['icono' => 'dashicons-megaphone', 'titulo' => 'Albisteak', 'descripcion' => 'Herriko berriak eta informazioa'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Azken albisteak',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Herriko azoka berria', 'descripcion' => 'Larunbatero merkatu ekologikoa plaza nagusian', 'url' => '#'],
                                        ['titulo' => 'Boluntariotza kanpaina', 'descripcion' => 'Herriko parkea garbitzeko jardunaldia', 'url' => '#'],
                                        ['titulo' => 'Euskara ikastaroak', 'descripcion' => 'Helduei zuzendutako euskara klaseak', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Nor gara',
                                    'contenido' => 'Denendako herritarren arteko sarea da. Elkarte, kolektibo eta bizilagun guztientzako espazio digitala, herrian gertatzen dena jakiteko eta parte hartzeko.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Proiektu honen parte izan nahi al duzu?',
                                    'subtitulo' => 'Partaidea egin eta herriko sarean parte hartu',
                                    'texto_cta' => 'Partaidea Egin',
                                    'url_cta' => '#partaide-egin',
                                    'color_fondo' => '#f5c518',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'denendako_nor_gara' => [
                        'name' => __('Nor Gara (Quiénes somos)', 'flavor-chat-ia'),
                        'description' => __('Proiektuaren aurkezpena — Presentación del proyecto', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nor Gara',
                                    'subtitulo' => 'Denendako herritarren sarea da, denon artean sortua.',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#ffffff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Gure historia',
                                    'contenido' => 'Denendako 2020an sortu zen, herriko elkarte eta kolektiboen arteko koordinazioa hobetzeko asmoz. Gaur egun, herritarren arteko komunikazio gune nagusia da.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Gure helburuak',
                                    'contenido' => 'Herritarren parte hartzea sustatzea, elkarte eta kolektiboen ikusgarritasuna handitzea, eta herriko bizitza kulturala indartzea.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'overlay',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Proiektu honen parte izan nahi al duzu?',
                                    'subtitulo' => '',
                                    'texto_cta' => 'Partaidea Egin',
                                    'url_cta' => '#partaide-egin',
                                    'color_fondo' => '#f5c518',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'denendako_agenda' => [
                        'name' => __('Agenda', 'flavor-chat-ia'),
                        'description' => __('Herriko ekitaldien zerrenda — Listado de eventos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar-alt',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Agenda',
                                    'subtitulo' => 'Herriko ekitaldi eta jarduerak',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#ffffff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'underline',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Herriko azoka', 'descripcion' => 'Larunbata, 10:00 — Plaza nagusia', 'url' => '#'],
                                        ['titulo' => 'Euskara ikastaroa', 'descripcion' => 'Astelehena, 18:00 — Kultur etxea', 'url' => '#'],
                                        ['titulo' => 'Kontzertu akustikoa', 'descripcion' => 'Ostirala, 20:30 — Gaztetxea', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbers'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'denendako_bazkideak' => [
                        'name' => __('Bazkideak (Socios)', 'flavor-chat-ia'),
                        'description' => __('Elkarte eta kolektiboen zerrenda — Directorio de asociaciones', 'flavor-chat-ia'),
                        'icon' => 'dashicons-networking',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Bazkideak',
                                    'subtitulo' => 'Herriko elkarte eta kolektiboak',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#ffffff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 4,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Kultur Elkartea', 'descripcion' => 'Kultura eta arteak', 'url' => '#'],
                                        ['titulo' => 'Kirol Taldea', 'descripcion' => 'Futbola, pelota, atletismoa', 'url' => '#'],
                                        ['titulo' => 'Gazte Asanblada', 'descripcion' => 'Gazteen elkartea', 'url' => '#'],
                                        ['titulo' => 'Emakumeen Taldea', 'descripcion' => 'Berdintasuna eta ahalduntzea', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbers'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'denendako_albisteak' => [
                        'name' => __('Albisteak (Noticias)', 'flavor-chat-ia'),
                        'description' => __('Herriko berrien zerrenda — Listado de noticias', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-post',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Albisteak',
                                    'subtitulo' => 'Herriko berriak eta informazioa',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#ffffff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Herriko azoka berria', 'descripcion' => 'Larunbatero merkatu ekologikoa', 'url' => '#'],
                                        ['titulo' => 'Boluntariotza kanpaina', 'descripcion' => 'Parkea garbitzeko jardunaldia', 'url' => '#'],
                                        ['titulo' => 'Euskara ikastaroak', 'descripcion' => 'Helduei zuzendutako klaseak', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbers'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'denendako_partaide_egin' => [
                        'name' => __('Partaide Egin (Hacerse socio)', 'flavor-chat-ia'),
                        'description' => __('Bazkide berria izateko formularioa — Formulario para nuevos miembros', 'flavor-chat-ia'),
                        'icon' => 'dashicons-welcome-add-page',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Partaidea Egin',
                                    'subtitulo' => 'Denendako sarean parte hartu eta herriko bizitzan lagundu.',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f5c518',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Bazkide izatearen abantailak',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-megaphone', 'titulo' => 'Informazioa', 'descripcion' => 'Herriko berri guztiak lehen eskutik'],
                                        ['icono' => 'dashicons-groups', 'titulo' => 'Parte-hartzea', 'descripcion' => 'Erabaki garrantzitsuetan parte hartu'],
                                        ['icono' => 'dashicons-heart', 'titulo' => 'Komunitatea', 'descripcion' => 'Herritarrekin konektatu'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Ohiko galderak',
                                    'items' => [
                                        ['pregunta' => 'Nola egin naiteke bazkide?', 'respuesta' => 'Beheko formularioa bete edo gure bulegora etorri.'],
                                        ['pregunta' => 'Zenbat kostatzen da?', 'respuesta' => 'Kuota urteko 10€ da, baina inork ez du geratzen ekonomia arrazoiengatik.'],
                                        ['pregunta' => 'Elkarteak ere parte hartu dezake?', 'respuesta' => 'Bai, elkarteak ere bazkide izan daitezke.'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'denendako_kontakto' => [
                        'name' => __('Kontakto (Contacto)', 'flavor-chat-ia'),
                        'description' => __('Kontaktu informazioa — Información de contacto', 'flavor-chat-ia'),
                        'icon' => 'dashicons-email',
                        'preview' => '',
                        'menu_type' => 'classic',
                        'footer_type' => 'multi-column',
                        'theme' => 'denendako',
                        'layout' => [
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Kontaktua',
                                    'direccion' => 'Plaza Coronación 2, Estella-Lizarra',
                                    'telefono' => '948 555 555',
                                    'email' => 'denendako.lizarraldea@gmail.com',
                                    'horario' => "Astelehenetik ostiralera: 10:00 - 14:00",
                                    'mostrar_formulario' => false,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Proiektu honen parte izan nahi al duzu?',
                                    'subtitulo' => '',
                                    'texto_cta' => 'Partaidea Egin',
                                    'url_cta' => '#partaide-egin',
                                    'color_fondo' => '#f5c518',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── ESCENA FAMILIAR: Teatre Familiar & Infantil ───
            // Figma: Inici, Espectacles, Agenda, Actuacions, Descobreix, Butlletí, Club, Sobre_nosaltres, Contacte, Companyies, Cercador
            'escena_familiar' => [
                'label' => __('Escena Familiar - Teatre Familiar', 'flavor-chat-ia'),
                'templates' => [
                    'escena_familiar_inici' => [
                        'name' => __('Inici', 'flavor-chat-ia'),
                        'description' => __('Pàgina principal colorida per a programació teatral familiar', 'flavor-chat-ia'),
                        'icon' => 'dashicons-smiley',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Escena Familiar',
                                    'subtitulo' => 'Teatre, tallers i diversió per a tota la família',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Veure Activitats',
                                    'url_cta' => '#activitats',
                                    'overlay_color' => '#7c3aed',
                                    'overlay_opacidad' => 35,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Properes Activitats',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Contacontes', 'descripcion' => 'Dissabtes 11:00 — Per a nens de 3 a 6 anys', 'url' => '#'],
                                        ['titulo' => 'Taller de Titelles', 'descripcion' => 'Diumenges 12:00 — Crea el teu personatge', 'url' => '#'],
                                        ['titulo' => 'Teatre en Família', 'descripcion' => 'Proper dissabte 17:00 — Entrada lliure', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Els nostres Programes',
                                    'subtitulo' => 'Activitats dissenyades per a cada etapa',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-smiley', 'titulo' => 'Nadons (0-2)', 'descripcion' => 'Estimulació primerenca i joc sensorial'],
                                        ['icono' => 'dashicons-heart', 'titulo' => 'Infantil (3-6)', 'descripcion' => 'Contes, música i moviment'],
                                        ['icono' => 'dashicons-star-filled', 'titulo' => 'Juvenil (7-12)', 'descripcion' => 'Teatre, dansa i arts plàstiques'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Un espai pensat per a famílies',
                                    'contenido' => 'Escena Familiar és un espai cultural dedicat al públic infantil i familiar. Oferim una programació variada d\'espectacles, tallers i activitats que fomenten la creativitat i l\'aprenentatge a través del joc.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_gallery',
                                'data' => [
                                    'titulo' => 'Moments',
                                    'columnas' => 4,
                                    'imagenes' => [],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Celebra l\'aniversari amb nosaltres',
                                    'subtitulo' => 'Organitzem festes d\'aniversari amb espectacle inclòs',
                                    'texto_cta' => 'Demanar informació',
                                    'url_cta' => '#aniversaris',
                                    'color_fondo' => '#7c3aed',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_newsletter',
                                'data' => [
                                    'titulo' => 'Agenda familiar setmanal',
                                    'subtitulo' => 'Rep les activitats de la setmana al teu email',
                                    'texto_placeholder' => 'El teu email',
                                    'texto_boton' => 'Subscriure\'m',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'escena_familiar_espectacles' => [
                        'name' => __('Espectacles', 'flavor-chat-ia'),
                        'description' => __('Llistat d\'espectacles amb filtres i cerca', 'flavor-chat-ia'),
                        'icon' => 'dashicons-format-video',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Espectacles',
                                    'subtitulo' => 'Tota la programació d\'Escena Familiar',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f3e8ff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Contacontes Màgic', 'descripcion' => 'Per a nens de 3 a 6 anys', 'url' => '#'],
                                        ['titulo' => 'El Petit Príncep', 'descripcion' => 'Teatre de titelles — Familiar', 'url' => '#'],
                                        ['titulo' => 'Dansa amb Nadons', 'descripcion' => 'Per a nadons de 0 a 2 anys', 'url' => '#'],
                                        ['titulo' => 'Circ en Família', 'descripcion' => 'Acrobàcies i pallassos', 'url' => '#'],
                                        ['titulo' => 'Taller de Música', 'descripcion' => 'Instruments per als més petits', 'url' => '#'],
                                        ['titulo' => 'Teatre d\'Ombres', 'descripcion' => 'Espectacle visual — Per a tots', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'load-more'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'escena_familiar_companyies' => [
                        'name' => __('Companyies', 'flavor-chat-ia'),
                        'description' => __('Directori de companyies de teatre familiar', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Companyies',
                                    'subtitulo' => 'Les companyies que fan possible Escena Familiar',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'color_fondo' => '#fce7f3',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 4,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Cia. Petits Somnis', 'descripcion' => 'Teatre per a nadons', 'url' => '#'],
                                        ['titulo' => 'Titelles del Bosc', 'descripcion' => 'Titelles i marionetes', 'url' => '#'],
                                        ['titulo' => 'Dansa Familiar', 'descripcion' => 'Moviment i expressió', 'url' => '#'],
                                        ['titulo' => 'Circ Petit', 'descripcion' => 'Circ per a famílies', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbers'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'escena_familiar_sobre_nosaltres' => [
                        'name' => __('Sobre Nosaltres', 'flavor-chat-ia'),
                        'description' => __('Informació sobre l\'espai i l\'equip', 'flavor-chat-ia'),
                        'icon' => 'dashicons-info',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Sobre Nosaltres',
                                    'subtitulo' => 'Un projecte nascut de l\'amor pel teatre i per les famílies',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f3e8ff',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'La nostra missió',
                                    'contenido' => 'Escena Familiar treballa per apropar les arts escèniques al públic familiar, creant espais segurs, inclusius i inspiradors on nens i adults puguin gaudir junts del teatre, la dansa i la música.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'El nostre equip',
                                    'items' => [
                                        ['titulo' => 'Direcció Artística', 'descripcion' => 'Selecció i curadoria d\'espectacles', 'url' => ''],
                                        ['titulo' => 'Producció', 'descripcion' => 'Logística i coordinació d\'espais', 'url' => ''],
                                        ['titulo' => 'Comunicació', 'descripcion' => 'Difusió i xarxes socials', 'url' => ''],
                                        ['titulo' => 'Educació', 'descripcion' => 'Tallers i activitats pedagògiques', 'url' => ''],
                                    ],
                                    'estilo' => 'cards',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntes Freqüents',
                                    'items' => [
                                        ['pregunta' => 'A partir de quina edat poden venir els nens?', 'respuesta' => 'Tenim activitats des dels 0 mesos. Cada activitat indica l\'edat recomanada.'],
                                        ['pregunta' => 'Cal reservar?', 'respuesta' => 'Per als tallers sí, ja que les places són limitades. Els espectacles són amb entrada lliure fins a completar aforament.'],
                                        ['pregunta' => 'On sou?', 'respuesta' => 'Som al centre, amb fàcil accés en transport públic i pàrquing proper.'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                        ],
                    ],
                    'escena_familiar_contacte' => [
                        'name' => __('Contacte', 'flavor-chat-ia'),
                        'description' => __('Informació de contacte i ubicació', 'flavor-chat-ia'),
                        'icon' => 'dashicons-email',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Contacte',
                                    'direccion' => 'Carrer de les Arts 10, Barcelona',
                                    'telefono' => '+34 933 000 000',
                                    'email' => 'hola@escenafamiliar.cat',
                                    'horario' => "Taquilla: Dissabtes i diumenges 10:00 - 14:00\nOficina: Dilluns a divendres 9:00 - 14:00",
                                    'mostrar_formulario' => true,
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_newsletter',
                                'data' => [
                                    'titulo' => 'Butlletí setmanal',
                                    'subtitulo' => 'Rep les activitats de la setmana al teu correu',
                                    'texto_placeholder' => 'El teu email',
                                    'texto_boton' => 'Subscriure\'m',
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                        ],
                    ],
                    'escena_familiar_club' => [
                        'name' => __('Club', 'flavor-chat-ia'),
                        'description' => __('Programa de fidelització per a famílies', 'flavor-chat-ia'),
                        'icon' => 'dashicons-awards',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'escena-familiar',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Club Escena Familiar',
                                    'subtitulo' => 'Fes-te del club i gaudeix d\'avantatges exclusius per a tota la família',
                                    'texto_cta' => 'Fer-me del club',
                                    'url_cta' => '#registre',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#7c3aed',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Avantatges del Club',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        ['icono' => 'dashicons-tag', 'titulo' => 'Descomptes', 'descripcion' => '20% en tots els espectacles'],
                                        ['icono' => 'dashicons-calendar', 'titulo' => 'Prevenda', 'descripcion' => 'Accés anticipat a entrades'],
                                        ['icono' => 'dashicons-star-filled', 'titulo' => 'Exclusius', 'descripcion' => 'Events i tallers especials'],
                                        ['icono' => 'dashicons-email', 'titulo' => 'Butlletí', 'descripcion' => 'Informació setmanal personalitzada'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Quota familiar: 25€/any',
                                    'subtitulo' => 'Inclou tots els membres de la unitat familiar',
                                    'texto_cta' => 'Apuntar-nos',
                                    'url_cta' => '#registre',
                                    'color_fondo' => '#ec4899',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ─── Grupos de Consumo ───
            'grupos-consumo' => [
                'label' => __('Grupos de Consumo - App Consumo Local', 'flavor-chat-ia'),
                'templates' => [
                    'gc_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Página principal del grupo de consumo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-carrot',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Consumo Local, Comunidad Real',
                                    'subtitulo' => 'Productos frescos directamente de productores locales a tu mesa',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Únete al grupo',
                                    'url_cta' => '#hazte-socio',
                                    'overlay_color' => '#4a7c59',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => ['margin_bottom' => 'none'],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Cómo Funciona',
                                    'subtitulo' => 'Un modelo simple, justo y sostenible',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-location', 'titulo' => 'Productores Locales', 'descripcion' => 'Trabajamos con agricultores y ganaderos de la zona que cultivan de forma responsable'],
                                        ['icono' => 'dashicons-clipboard', 'titulo' => 'Pedido Colectivo', 'descripcion' => 'Cada semana abres el catálogo, eliges tus productos y haces tu pedido online'],
                                        ['icono' => 'dashicons-cart', 'titulo' => 'Recogida Semanal', 'descripcion' => 'Recoge tu cesta en el punto de distribución del barrio, fresca y lista'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Productos de Temporada',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Tomates ecológicos', 'descripcion' => 'Baserri Lurra — 3,20 €/kg', 'url' => '#'],
                                        ['titulo' => 'Queso de oveja', 'descripcion' => 'Artzain Gazta — 12,50 €/ud', 'url' => '#'],
                                        ['titulo' => 'Pan de masa madre', 'descripcion' => 'Okin Labea — 4,00 €/ud', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Sobre Nuestro Grupo',
                                    'contenido' => 'Somos un grupo de consumo que conecta a familias del barrio con productores locales. Desde 2018 apostamos por un modelo alimentario justo, ecológico y de cercanía. Cada semana organizamos pedidos colectivos para ofrecer productos frescos a precio justo, apoyando la economía local y reduciendo la huella ecológica.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Nuestro Impacto',
                                    'columnas' => 3,
                                    'items' => [
                                        ['valor' => '12', 'etiqueta' => 'Productores locales'],
                                        ['valor' => '85', 'etiqueta' => 'Familias socias'],
                                        ['valor' => '2.400', 'etiqueta' => 'Kg repartidos al mes'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Hazte socio/a',
                                    'subtitulo' => 'Únete a una comunidad que apuesta por el consumo responsable',
                                    'texto_cta' => 'Quiero participar',
                                    'url_cta' => '#hazte-socio',
                                    'color_fondo' => '#4a7c59',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'gc_productos' => [
                        'name' => __('Catálogo de Productos', 'flavor-chat-ia'),
                        'description' => __('Catálogo de productos disponibles con filtros', 'flavor-chat-ia'),
                        'icon' => 'dashicons-products',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestros Productos',
                                    'subtitulo' => 'Frescos, locales y de temporada',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#e8f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Frutas'],
                                        ['label' => 'Verduras'],
                                        ['label' => 'Lácteos'],
                                        ['label' => 'Carne'],
                                        ['label' => 'Pan'],
                                        ['label' => 'Conservas'],
                                        ['label' => 'Bebidas'],
                                    ],
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Tomates ecológicos', 'descripcion' => 'Baserri Lurra — 3,20 €/kg', 'url' => '#'],
                                        ['titulo' => 'Lechugas variadas', 'descripcion' => 'Baserri Lurra — 1,80 €/ud', 'url' => '#'],
                                        ['titulo' => 'Queso de oveja', 'descripcion' => 'Artzain Gazta — 12,50 €/ud', 'url' => '#'],
                                        ['titulo' => 'Yogur natural', 'descripcion' => 'Esne Ona — 3,60 €/ud', 'url' => '#'],
                                        ['titulo' => 'Manzanas', 'descripcion' => 'Sagarra Baserria — 2,90 €/kg', 'url' => '#'],
                                        ['titulo' => 'Pan integral', 'descripcion' => 'Okin Labea — 4,20 €/ud', 'url' => '#'],
                                        ['titulo' => 'Huevos camperos', 'descripcion' => 'Etxeko Arrautzak — 3,50 €/docena', 'url' => '#'],
                                        ['titulo' => 'Miel artesana', 'descripcion' => 'Ezti Etxea — 8,00 €/tarro', 'url' => '#'],
                                        ['titulo' => 'Txakoli', 'descripcion' => 'Bodega Mendizabal — 9,50 €/bot', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbered'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'gc_productores' => [
                        'name' => __('Nuestros Productores', 'flavor-chat-ia'),
                        'description' => __('Directorio de productores locales', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Conoce a Nuestros Productores',
                                    'subtitulo' => 'Las personas detrás de cada producto',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#e8f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'pills',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Ecológico'],
                                        ['label' => 'Convencional'],
                                    ],
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Baserri Lurra', 'descripcion' => 'Verduras y hortalizas ecológicas — Hernani', 'url' => '#'],
                                        ['titulo' => 'Artzain Gazta', 'descripcion' => 'Quesos artesanos de oveja latxa — Arantzazu', 'url' => '#'],
                                        ['titulo' => 'Okin Labea', 'descripcion' => 'Panadería artesana de masa madre — Tolosa', 'url' => '#'],
                                        ['titulo' => 'Esne Ona', 'descripcion' => 'Lácteos artesanos ecológicos — Azpeitia', 'url' => '#'],
                                        ['titulo' => 'Sagarra Baserria', 'descripcion' => 'Frutas de temporada — Astigarraga', 'url' => '#'],
                                        ['titulo' => 'Etxeko Arrautzak', 'descripcion' => 'Huevos camperos y aves — Oiartzun', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => '¿Eres productor/a local?',
                                    'contenido' => 'Si produces alimentos de calidad en la zona y quieres formar parte de nuestra red, nos encantaría conocerte. Buscamos productores comprometidos con la calidad, la cercanía y la sostenibilidad.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['background' => 'gray', 'padding' => 'large'],
                            ],
                        ],
                    ],
                    'gc_como_funciona' => [
                        'name' => __('Cómo Funciona', 'flavor-chat-ia'),
                        'description' => __('Explicación del proceso y FAQ', 'flavor-chat-ia'),
                        'icon' => 'dashicons-info',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Cómo Funciona Nuestro Grupo',
                                    'subtitulo' => 'Un proceso sencillo para comer bien y apoyar lo local',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#e8f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => '4 Pasos Sencillos',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        ['icono' => 'dashicons-admin-users', 'titulo' => '1. Hazte socio/a', 'descripcion' => 'Rellena el formulario de inscripción y paga la cuota anual'],
                                        ['icono' => 'dashicons-search', 'titulo' => '2. Consulta productos', 'descripcion' => 'Cada semana publicamos el catálogo con los productos disponibles'],
                                        ['icono' => 'dashicons-clipboard', 'titulo' => '3. Haz tu pedido', 'descripcion' => 'Elige lo que necesitas antes de la fecha de cierre del ciclo'],
                                        ['icono' => 'dashicons-cart', 'titulo' => '4. Recoge tu cesta', 'descripcion' => 'Pasa por el punto de recogida en el horario indicado'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'El Ciclo de Pedido',
                                    'contenido' => 'Cada semana abrimos un nuevo ciclo de pedido. Los productores nos envían la lista de productos disponibles, la publicamos en la plataforma y los socios hacen sus pedidos antes del miércoles. El jueves preparamos las cestas y el viernes las tenéis listas para recoger.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'La Recogida',
                                    'contenido' => 'El punto de recogida está en el local del barrio. Abrimos los viernes de 17:00 a 20:00. Es un momento de encuentro donde además puedes conocer a otros socios, intercambiar recetas y participar en la vida del grupo.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['background' => 'gray', 'padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas Frecuentes',
                                    'items' => [
                                        ['titulo' => '¿Cada cuánto hay pedidos?', 'contenido' => 'Los pedidos son semanales. Cada lunes se abre el catálogo y se cierra el miércoles a las 23:59. La recogida es el viernes.'],
                                        ['titulo' => '¿Hay un pedido mínimo?', 'contenido' => 'No hay pedido mínimo por persona. El grupo realiza un pedido conjunto a cada productor, así que cuantos más participemos, mejor para todos.'],
                                        ['titulo' => '¿Puedo modificar mi pedido?', 'contenido' => 'Puedes modificar tu pedido en cualquier momento antes del cierre (miércoles 23:59). Una vez cerrado el ciclo, no se pueden hacer cambios.'],
                                        ['titulo' => '¿Qué pasa si no recojo mi pedido?', 'contenido' => 'Si no puedes recoger tu cesta el viernes, puedes enviar a alguien en tu nombre o contactar con nosotros para buscar una solución. Los productos no recogidos se donan.'],
                                        ['titulo' => '¿Los productos son ecológicos?', 'contenido' => 'Trabajamos con productores que priorizan prácticas sostenibles. Algunos tienen certificación ecológica oficial y otros practican agricultura natural sin certificación. En la ficha de cada productor indicamos su método.'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                        ],
                    ],
                    'gc_pedido' => [
                        'name' => __('Pedido Actual', 'flavor-chat-ia'),
                        'description' => __('Página del ciclo de pedido actual', 'flavor-chat-ia'),
                        'icon' => 'dashicons-clipboard',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Ciclo de Pedido Actual',
                                    'subtitulo' => 'Pedido abierto — Cierre: miércoles 23:59',
                                    'texto_cta' => 'Hacer mi pedido',
                                    'url_cta' => '#pedido',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#4a7c59',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Fechas de este Ciclo',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-calendar-alt', 'titulo' => 'Apertura', 'descripcion' => 'Lunes 10:00'],
                                        ['icono' => 'dashicons-clock', 'titulo' => 'Cierre de Pedidos', 'descripcion' => 'Miércoles 23:59'],
                                        ['icono' => 'dashicons-location', 'titulo' => 'Recogida', 'descripcion' => 'Viernes 17:00–20:00'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Productos Disponibles este Ciclo',
                                    'columnas' => 4,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        ['titulo' => 'Tomates', 'descripcion' => '3,20 €/kg — Baserri Lurra', 'url' => '#'],
                                        ['titulo' => 'Lechugas', 'descripcion' => '1,80 €/ud — Baserri Lurra', 'url' => '#'],
                                        ['titulo' => 'Queso fresco', 'descripcion' => '8,50 €/ud — Artzain Gazta', 'url' => '#'],
                                        ['titulo' => 'Pan de centeno', 'descripcion' => '4,50 €/ud — Okin Labea', 'url' => '#'],
                                        ['titulo' => 'Huevos camperos', 'descripcion' => '3,50 €/docena — Etxeko', 'url' => '#'],
                                        ['titulo' => 'Yogur natural', 'descripcion' => '3,60 €/ud — Esne Ona', 'url' => '#'],
                                        ['titulo' => 'Manzanas', 'descripcion' => '2,90 €/kg — Sagarra', 'url' => '#'],
                                        ['titulo' => 'Miel', 'descripcion' => '8,00 €/tarro — Ezti Etxea', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Necesitas ayuda con tu pedido?',
                                    'subtitulo' => 'Nuestro asistente puede resolver tus dudas al instante',
                                    'texto_cta' => 'Hablar con el asistente',
                                    'url_cta' => '#chat',
                                    'color_fondo' => '#d4953a',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'gc_eventos' => [
                        'name' => __('Eventos y Talleres', 'flavor-chat-ia'),
                        'description' => __('Agenda de eventos del grupo de consumo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Eventos y Talleres',
                                    'subtitulo' => 'Actividades para la comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#e8f0eb',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'category',
                                    'estilo' => 'underline',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Talleres'],
                                        ['label' => 'Visitas'],
                                        ['label' => 'Degustaciones'],
                                        ['label' => 'Asambleas'],
                                    ],
                                ],
                                'settings' => ['padding' => 'medium'],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        ['titulo' => 'Taller de conservas', 'descripcion' => '15 Feb — Taller — Aprende a hacer conservas caseras con producto de temporada', 'url' => '#'],
                                        ['titulo' => 'Visita a Baserri Lurra', 'descripcion' => '22 Feb — Visita — Conoce la huerta donde crecen tus verduras', 'url' => '#'],
                                        ['titulo' => 'Cata de quesos', 'descripcion' => '1 Mar — Degustación — Descubre las variedades de Artzain Gazta', 'url' => '#'],
                                        ['titulo' => 'Asamblea trimestral', 'descripcion' => '8 Mar — Asamblea — Revisamos cuentas y planificamos el siguiente trimestre', 'url' => '#'],
                                        ['titulo' => 'Taller de pan', 'descripcion' => '15 Mar — Taller — Masa madre: de cero a tu primer pan', 'url' => '#'],
                                        ['titulo' => 'Mercadillo de primavera', 'descripcion' => '22 Mar — Degustación — Jornada abierta con todos nuestros productores', 'url' => '#'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => ['estilo' => 'numbered'],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'gc_hazte_socio' => [
                        'name' => __('Hazte Socio/a', 'flavor-chat-ia'),
                        'description' => __('Página de captación e inscripción', 'flavor-chat-ia'),
                        'icon' => 'dashicons-welcome-add-page',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Únete a Nuestro Grupo de Consumo',
                                    'subtitulo' => 'Come mejor, apoya lo local, forma parte de la comunidad',
                                    'texto_cta' => 'Inscribirme ahora',
                                    'url_cta' => '#inscripcion',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#d4953a',
                                ],
                                'settings' => [],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Ventajas de ser Socio/a',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        ['icono' => 'dashicons-carrot', 'titulo' => 'Productos Frescos', 'descripcion' => 'Recibe cada semana productos recién recolectados, sin intermediarios ni largos transportes'],
                                        ['icono' => 'dashicons-money-alt', 'titulo' => 'Precio Justo', 'descripcion' => 'El productor recibe un precio digno y tú pagas menos que en tienda. Sin márgenes especulativos'],
                                        ['icono' => 'dashicons-groups', 'titulo' => 'Comunidad', 'descripcion' => 'Forma parte de una red de personas comprometidas con el consumo responsable y la soberanía alimentaria'],
                                    ],
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Tipos de Socio/a',
                                    'contenido' => '<strong>Socio/a Consumidor/a:</strong> Participas en los pedidos semanales y en las asambleas. Cuota anual de 30€.<br><br><strong>Socio/a Colaborador/a:</strong> Además de consumir, te implicas en la gestión del grupo (logística, comunicación, eventos). Cuota reducida de 15€.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Cuotas y Condiciones',
                                    'columnas' => 3,
                                    'estilo' => 'cards',
                                    'items' => [
                                        ['valor' => '30€', 'etiqueta' => 'Cuota anual consumidor/a'],
                                        ['valor' => '15€', 'etiqueta' => 'Cuota anual colaborador/a'],
                                        ['valor' => '0€', 'etiqueta' => 'Sin pedido mínimo'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray'],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas sobre la Inscripción',
                                    'items' => [
                                        ['titulo' => '¿Cómo me apunto?', 'contenido' => 'Rellena el formulario de inscripción o ven a una de nuestras asambleas. Te daremos de alta en la plataforma de pedidos.'],
                                        ['titulo' => '¿Cuánto cuesta?', 'contenido' => 'La cuota anual es de 30€ para socios consumidores y 15€ para colaboradores. No hay cuota mensual ni pedido mínimo.'],
                                        ['titulo' => '¿Puedo darme de baja?', 'contenido' => 'Puedes darte de baja en cualquier momento. La cuota no es reembolsable pero tampoco hay permanencia.'],
                                        ['titulo' => '¿Hay compromiso mínimo de compra?', 'contenido' => 'No exigimos un gasto mínimo, pero animamos a participar regularmente para que el grupo funcione bien y los productores puedan planificar.'],
                                    ],
                                ],
                                'settings' => ['background' => 'gray', 'padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Listo/a para empezar?',
                                    'subtitulo' => 'Únete hoy y empieza a disfrutar de productos locales y de temporada',
                                    'texto_cta' => 'Inscribirme',
                                    'url_cta' => '#inscripcion',
                                    'color_fondo' => '#4a7c59',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                    'gc_contacto' => [
                        'name' => __('Contacto', 'flavor-chat-ia'),
                        'description' => __('Página de contacto con mapa y datos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-location',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'grupos-consumo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Punto de Recogida',
                                    'direccion' => 'Calle del Huerto, 12 — Local Comunitario',
                                    'telefono' => '600 123 456',
                                    'email' => 'hola@grupoconsumo.eus',
                                    'horario' => 'Viernes de 17:00 a 20:00',
                                ],
                                'settings' => ['padding' => 'large'],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Tienes dudas? Habla con nuestro asistente',
                                    'subtitulo' => 'Responde al instante sobre productos, pedidos y horarios',
                                    'texto_cta' => 'Abrir chat',
                                    'url_cta' => '#chat',
                                    'color_fondo' => '#4a7c59',
                                ],
                                'settings' => [],
                            ],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════════════
            // GAILU ECOSYSTEM APPS (11 sectors)
            // ═══════════════════════════════════════════════════════════════


            'comunidad-viva' => [
                'label' => __('Comunidad Viva - Red Social Cooperativa', 'flavor-chat-ia'),
                'templates' => [
                    'cv_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Página principal de la red social cooperativa Comunidad Viva', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'comunidad-viva',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Comunidad Viva',
                                    'subtitulo' => 'La red social que fortalece tu territorio. Conecta con vecinos, productores y proyectos locales.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Únete a la comunidad',
                                    'url_cta' => '#unete',
                                    'overlay_color' => '#4f46e5',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Una comunidad que funciona',
                                    'subtitulo' => 'Tres pilares para transformar tu entorno',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-networking',
                                            'titulo' => 'Conecta',
                                            'descripcion' => 'Encuentra personas, proyectos y organizaciones de tu entorno',
                                        ],
                                        [
                                            'icono' => 'dashicons-megaphone',
                                            'titulo' => 'Participa',
                                            'descripcion' => 'Propón, debate y vota en las decisiones que afectan a tu comunidad',
                                        ],
                                        [
                                            'icono' => 'dashicons-chart-area',
                                            'titulo' => 'Impacta',
                                            'descripcion' => 'Mide el impacto social, ecológico y económico de las acciones colectivas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Proyectos Destacados',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Huerto comunitario Zubiak',
                                            'descripcion' => 'Espacio de cultivo colectivo que conecta 40 familias del barrio con la tierra y la alimentación sostenible.',
                                            'imagen' => '',
                                            'url' => '#huerto-zubiak',
                                        ],
                                        [
                                            'titulo' => 'Banco de tiempo Elkartasuna',
                                            'descripcion' => 'Red de intercambio de servicios donde el tiempo es la única moneda. Ya sumamos más de 3.500 horas compartidas.',
                                            'imagen' => '',
                                            'url' => '#banco-tiempo',
                                        ],
                                        [
                                            'titulo' => 'Grupo de consumo Bizilagun',
                                            'descripcion' => 'Compra colectiva directa a productores locales. Cestas semanales de alimentos frescos y ecológicos.',
                                            'imagen' => '',
                                            'url' => '#grupo-consumo',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Nuestra comunidad en cifras',
                                    'columnas' => 4,
                                    'estilo' => 'gradient',
                                    'items' => [
                                        [
                                            'valor' => '1.200',
                                            'etiqueta' => 'Miembros activos',
                                            'icono' => 'dashicons-groups',
                                        ],
                                        [
                                            'valor' => '45',
                                            'etiqueta' => 'Proyectos en marcha',
                                            'icono' => 'dashicons-lightbulb',
                                        ],
                                        [
                                            'valor' => '3.500',
                                            'etiqueta' => 'Horas intercambiadas',
                                            'icono' => 'dashicons-clock',
                                        ],
                                        [
                                            'valor' => '12',
                                            'etiqueta' => 'Barrios conectados',
                                            'icono' => 'dashicons-location',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Un Ecosistema Conectado',
                                    'contenido' => 'Comunidad Viva es el corazón digital del ecosistema Gailu. Desde aquí accedes al mercado local, al banco de tiempo, a la gobernanza participativa y a todas las herramientas que necesita una comunidad organizada.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Construye comunidad real',
                                    'subtitulo' => 'Cada conexión fortalece el tejido social',
                                    'texto_cta' => 'Crear mi perfil',
                                    'url_cta' => '#crear-perfil',
                                    'color_fondo' => '#4f46e5',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'cv_directorio' => [
                        'name' => __('Directorio', 'flavor-chat-ia'),
                        'description' => __('Directorio de personas, organizaciones y proyectos de la comunidad', 'flavor-chat-ia'),
                        'icon' => 'dashicons-businessperson',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'comunidad-viva',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Directorio Comunitario',
                                    'subtitulo' => 'Encuentra personas, organizaciones y proyectos',
                                    'texto_cta' => 'Buscar',
                                    'url_cta' => '#buscar',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#eef2ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'tipo_entidad',
                                    'estilo' => 'pills',
                                    'items' => [
                                        [
                                            'label' => 'Todos',
                                        ],
                                        [
                                            'label' => 'Personas',
                                        ],
                                        [
                                            'label' => 'Organizaciones',
                                        ],
                                        [
                                            'label' => 'Proyectos',
                                        ],
                                        [
                                            'label' => 'Cooperativas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Kooperatiba Lurra',
                                            'descripcion' => 'Cooperativa agrícola especializada en producción ecológica y venta directa al consumidor.',
                                            'imagen' => '',
                                            'url' => '#kooperatiba-lurra',
                                        ],
                                        [
                                            'titulo' => 'Elkarte Digitala',
                                            'descripcion' => 'Asociación tech que impulsa la soberanía tecnológica y las herramientas digitales libres para la comunidad.',
                                            'imagen' => '',
                                            'url' => '#elkarte-digitala',
                                        ],
                                        [
                                            'titulo' => 'María Goikoetxea',
                                            'descripcion' => 'Permacultura y diseño regenerativo. Asesora en transición ecológica para proyectos comunitarios.',
                                            'imagen' => '',
                                            'url' => '#maria-goikoetxea',
                                        ],
                                        [
                                            'titulo' => 'Grupo Bizi',
                                            'descripcion' => 'Colectivo vecinal que organiza actividades culturales, deportivas y sociales en el barrio.',
                                            'imagen' => '',
                                            'url' => '#grupo-bizi',
                                        ],
                                        [
                                            'titulo' => 'Dendak Elkartea',
                                            'descripcion' => 'Asociación de comercio local que promueve la compra de proximidad y el tejido comercial de barrio.',
                                            'imagen' => '',
                                            'url' => '#dendak-elkartea',
                                        ],
                                        [
                                            'titulo' => 'Ikasgune',
                                            'descripcion' => 'Espacio educativo comunitario con talleres, charlas y formación continua para todas las edades.',
                                            'imagen' => '',
                                            'url' => '#ikasgune',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'cv_actividad' => [
                        'name' => __('Actividad', 'flavor-chat-ia'),
                        'description' => __('Feed de actividad reciente de la comunidad', 'flavor-chat-ia'),
                        'icon' => 'dashicons-rss',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'comunidad-viva',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Actividad de la Comunidad',
                                    'subtitulo' => 'Lo que está pasando ahora en tu entorno',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#eef2ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Últimas novedades',
                                    'columnas' => 2,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Nuevo proyecto: Biblioteca de semillas',
                                            'descripcion' => 'Se ha lanzado una biblioteca de semillas comunitaria para preservar las variedades locales y facilitar el intercambio entre hortelanos.',
                                            'imagen' => '',
                                            'url' => '#biblioteca-semillas',
                                        ],
                                        [
                                            'titulo' => 'Asamblea barrial convocada para el viernes',
                                            'descripcion' => 'La asamblea mensual del barrio se celebrará este viernes a las 18:30. Orden del día: presupuestos participativos y huertos.',
                                            'imagen' => '',
                                            'url' => '#asamblea',
                                        ],
                                        [
                                            'titulo' => '15 nuevas familias se unieron este mes',
                                            'descripcion' => 'La comunidad sigue creciendo. Este mes se han sumado 15 nuevas familias al ecosistema Comunidad Viva.',
                                            'imagen' => '',
                                            'url' => '#nuevas-familias',
                                        ],
                                        [
                                            'titulo' => 'Mercado semanal: 40 productores confirmados',
                                            'descripcion' => 'El mercado del sábado contará con 40 productores locales. Productos frescos, artesanía y actividades para toda la familia.',
                                            'imagen' => '',
                                            'url' => '#mercado-semanal',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Esta semana',
                                    'columnas' => 3,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '24',
                                            'etiqueta' => 'Publicaciones esta semana',
                                            'icono' => 'dashicons-edit',
                                        ],
                                        [
                                            'valor' => '156',
                                            'etiqueta' => 'Interacciones',
                                            'icono' => 'dashicons-heart',
                                        ],
                                        [
                                            'valor' => '8',
                                            'etiqueta' => 'Nuevos miembros',
                                            'icono' => 'dashicons-admin-users',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'cv_mapa' => [
                        'name' => __('Mapa del Ecosistema', 'flavor-chat-ia'),
                        'description' => __('Mapa interactivo de recursos, espacios y proyectos del territorio', 'flavor-chat-ia'),
                        'icon' => 'dashicons-location-alt',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'comunidad-viva',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Mapa del Ecosistema',
                                    'subtitulo' => 'Explora los recursos, espacios y proyectos de tu territorio',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#eef2ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Puntos del Ecosistema',
                                    'direccion' => 'Territorio Gailu',
                                    'telefono' => '',
                                    'email' => 'mapa@comunidadviva.eus',
                                    'horario' => '',
                                    'latitud' => '',
                                    'longitud' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Qué encontrarás en el mapa',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-carrot',
                                            'titulo' => 'Huertos',
                                            'descripcion' => '12 huertos comunitarios',
                                        ],
                                        [
                                            'icono' => 'dashicons-building',
                                            'titulo' => 'Espacios',
                                            'descripcion' => '8 espacios compartidos',
                                        ],
                                        [
                                            'icono' => 'dashicons-store',
                                            'titulo' => 'Comercios',
                                            'descripcion' => '34 comercios adheridos',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-tools',
                                            'titulo' => 'Servicios',
                                            'descripcion' => '15 servicios comunitarios',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'jantoki' => [
                'label' => __('Jantoki - Restaurante Cooperativo', 'flavor-chat-ia'),
                'templates' => [
                    'jt_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Página principal del restaurante cooperativo Jantoki', 'flavor-chat-ia'),
                        'icon' => 'dashicons-food',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'jantoki',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Jantoki',
                                    'subtitulo' => 'Cocina comunitaria de cercanía. Cada plato tiene nombre, apellido y tierra.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Ver menú del día',
                                    'url_cta' => '#menu-dia',
                                    'overlay_color' => '#8b5a2b',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Nuestra filosofía',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-location',
                                            'titulo' => 'Km0',
                                            'descripcion' => 'Ingredientes de productores a menos de 50km',
                                        ],
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Cooperativo',
                                            'descripcion' => 'Propiedad colectiva, decisiones compartidas',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-site',
                                            'titulo' => 'Sostenible',
                                            'descripcion' => 'Residuo cero, compostaje, envases retornables',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Platos del Día',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Crema de calabaza con semillas tostadas — 8,50€',
                                            'descripcion' => 'Calabaza ecológica de Baserri Goikoa, con semillas de girasol y un toque de jengibre fresco.',
                                            'imagen' => '',
                                            'url' => '#plato-crema',
                                        ],
                                        [
                                            'titulo' => 'Txuleta de vaca pirenaica con pimientos — 14,00€',
                                            'descripcion' => 'Carne de raza pirenaica criada en pasto, acompañada de pimientos de Lodosa asados al carbón.',
                                            'imagen' => '',
                                            'url' => '#plato-txuleta',
                                        ],
                                        [
                                            'titulo' => 'Tarta de manzana reineta con helado artesano — 5,50€',
                                            'descripcion' => 'Manzana reineta de Sagardotegia Aia con helado de leche de Ardi Latxa y canela.',
                                            'imagen' => '',
                                            'url' => '#plato-tarta',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Nuestra Historia',
                                    'contenido' => 'Jantoki nació en 2020 como un proyecto cooperativo de alimentación comunitaria. Creemos que comer bien es un derecho, no un privilegio. Trabajamos con 15 productores locales para ofrecer menús diarios accesibles, nutritivos y preparados con ingredientes de temporada.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Jantoki en cifras',
                                    'columnas' => 4,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '15',
                                            'etiqueta' => 'Productores locales',
                                            'icono' => 'dashicons-carrot',
                                        ],
                                        [
                                            'valor' => '120',
                                            'etiqueta' => 'Menús/día',
                                            'icono' => 'dashicons-food',
                                        ],
                                        [
                                            'valor' => '95%',
                                            'etiqueta' => 'Ingredientes km0',
                                            'icono' => 'dashicons-location',
                                        ],
                                        [
                                            'valor' => '0',
                                            'etiqueta' => 'Residuo al vertedero',
                                            'icono' => 'dashicons-trash',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Reserva tu mesa',
                                    'subtitulo' => 'Menú del día desde 10,50€',
                                    'texto_cta' => 'Reservar',
                                    'url_cta' => '#reservar',
                                    'color_fondo' => '#8b5a2b',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'jt_menu' => [
                        'name' => __('Carta y Menú', 'flavor-chat-ia'),
                        'description' => __('Carta completa y menú del día del restaurante Jantoki', 'flavor-chat-ia'),
                        'icon' => 'dashicons-editor-ul',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'jantoki',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestra Carta',
                                    'subtitulo' => 'Cocina de temporada, fresca cada día',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fdf8f3',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'categoria_plato',
                                    'estilo' => 'pills',
                                    'items' => [
                                        [
                                            'label' => 'Todo',
                                        ],
                                        [
                                            'label' => 'Entrantes',
                                        ],
                                        [
                                            'label' => 'Principales',
                                        ],
                                        [
                                            'label' => 'Postres',
                                        ],
                                        [
                                            'label' => 'Bebidas',
                                        ],
                                        [
                                            'label' => 'Menú del día',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Ensalada de productores locales — 7,50€',
                                            'descripcion' => 'Lechuga, tomate, cebolla morada, queso fresco y vinagreta de sidra. Todo de nuestros productores.',
                                            'imagen' => '',
                                            'url' => '#ensalada',
                                        ],
                                        [
                                            'titulo' => 'Txistorra con sidra natural — 6,00€',
                                            'descripcion' => 'Txistorra artesana de caserío a la parrilla, flambeada con sidra natural de Sagardotegia Aia.',
                                            'imagen' => '',
                                            'url' => '#txistorra',
                                        ],
                                        [
                                            'titulo' => 'Bacalao al pil-pil — 13,50€',
                                            'descripcion' => 'Bacalao desalado lentamente, con aceite de oliva virgen extra y guindillas de Ibarra.',
                                            'imagen' => '',
                                            'url' => '#bacalao',
                                        ],
                                        [
                                            'titulo' => 'Chuletón de Navarra — 16,00€',
                                            'descripcion' => 'Carne madurada 30 días de raza pirenaica criada en pasto, a la brasa de encina.',
                                            'imagen' => '',
                                            'url' => '#chuleton',
                                        ],
                                        [
                                            'titulo' => 'Arroz con verduras — 9,00€',
                                            'descripcion' => 'Arroz bomba con alcachofas, espárragos, pimiento y caldo de verduras casero.',
                                            'imagen' => '',
                                            'url' => '#arroz',
                                        ],
                                        [
                                            'titulo' => 'Natillas caseras — 4,50€',
                                            'descripcion' => 'Natillas elaboradas con leche fresca de Ardi Latxa, vainilla natural y canela.',
                                            'imagen' => '',
                                            'url' => '#natillas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Alérgenos y Dietética',
                                    'contenido' => 'Todos nuestros platos incluyen información de alérgenos. Ofrecemos opciones vegetarianas, veganas y sin gluten cada día. Consulta con nuestro equipo.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'card',
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'jt_productores' => [
                        'name' => __('Productores', 'flavor-chat-ia'),
                        'description' => __('Productores locales que abastecen al restaurante Jantoki', 'flavor-chat-ia'),
                        'icon' => 'dashicons-carrot',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'jantoki',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestros Productores',
                                    'subtitulo' => 'Las manos que cultivan lo que cocinamos',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fdf8f3',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Baserri Goikoa',
                                            'descripcion' => 'Verduras ecológicas cultivadas con técnicas de permacultura a 12km del restaurante.',
                                            'imagen' => '',
                                            'url' => '#baserri-goikoa',
                                        ],
                                        [
                                            'titulo' => 'Ardi Latxa',
                                            'descripcion' => 'Quesos artesanos de oveja latxa elaborados en el caserío con leche cruda y cuajo natural.',
                                            'imagen' => '',
                                            'url' => '#ardi-latxa',
                                        ],
                                        [
                                            'titulo' => 'Okin Zaharra',
                                            'descripcion' => 'Panadería de masa madre con harinas de trigo antiguo molido en piedra. Pan con alma.',
                                            'imagen' => '',
                                            'url' => '#okin-zaharra',
                                        ],
                                        [
                                            'titulo' => 'Sagardotegia Aia',
                                            'descripcion' => 'Sidra natural y manzana ecológica de variedades locales. Tradición sagardotera viva.',
                                            'imagen' => '',
                                            'url' => '#sagardotegia-aia',
                                        ],
                                        [
                                            'titulo' => 'Arrantzale Elkartea',
                                            'descripcion' => 'Pesca artesanal del Cantábrico. Pescado del día capturado con artes sostenibles.',
                                            'imagen' => '',
                                            'url' => '#arrantzale',
                                        ],
                                        [
                                            'titulo' => 'Eztia Baserria',
                                            'descripcion' => 'Miel y derivados apícolas. Abejas cuidadas con métodos naturales en entornos libres de pesticidas.',
                                            'imagen' => '',
                                            'url' => '#eztia-baserria',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Mapa de Productores',
                                    'direccion' => 'Red de productores Jantoki',
                                    'telefono' => '',
                                    'email' => '',
                                    'horario' => '',
                                    'latitud' => '',
                                    'longitud' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Nuestra red',
                                    'columnas' => 3,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '15',
                                            'etiqueta' => 'Productores',
                                            'icono' => 'dashicons-groups',
                                        ],
                                        [
                                            'valor' => '50km',
                                            'etiqueta' => 'Radio máximo',
                                            'icono' => 'dashicons-location',
                                        ],
                                        [
                                            'valor' => '100%',
                                            'etiqueta' => 'Trazabilidad',
                                            'icono' => 'dashicons-yes-alt',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'jt_reservas' => [
                        'name' => __('Reservas', 'flavor-chat-ia'),
                        'description' => __('Reserva de mesas y horarios del restaurante Jantoki', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar-alt',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'jantoki',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Reserva tu Mesa',
                                    'subtitulo' => 'Garantiza tu sitio para el menú del día',
                                    'texto_cta' => 'Llamar',
                                    'url_cta' => 'tel:+34943000000',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'color_fondo' => '#8b5a2b',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Horarios',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-clock',
                                            'titulo' => 'Comida',
                                            'descripcion' => '13:00 - 15:30',
                                        ],
                                        [
                                            'icono' => 'dashicons-clock',
                                            'titulo' => 'Cena',
                                            'descripcion' => '20:00 - 22:30',
                                        ],
                                        [
                                            'icono' => 'dashicons-calendar',
                                            'titulo' => 'Eventos',
                                            'descripcion' => 'Consultar disponibilidad',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas frecuentes',
                                    'items' => [
                                        [
                                            'pregunta' => '¿Necesito reservar?',
                                            'respuesta' => 'Recomendamos reservar, especialmente los fines de semana y para el menú del día, ya que las plazas son limitadas. Entre semana suele haber disponibilidad sin reserva.',
                                        ],
                                        [
                                            'pregunta' => '¿Puedo pedir para llevar?',
                                            'respuesta' => 'Sí, ofrecemos servicio de comida para llevar en envases retornables. Puedes llamar con 30 minutos de antelación para tener tu pedido preparado.',
                                        ],
                                        [
                                            'pregunta' => '¿Aceptáis grupos grandes?',
                                            'respuesta' => 'Aceptamos grupos de hasta 20 personas con reserva previa de al menos 48 horas. Para grupos mayores o eventos privados, contacta con nosotros.',
                                        ],
                                        [
                                            'pregunta' => '¿Tenéis terraza?',
                                            'respuesta' => 'Sí, disponemos de una terraza con 8 mesas que abrimos de abril a octubre, según el tiempo. En verano es mejor reservar terraza con antelación.',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'jt_eventos' => [
                        'name' => __('Eventos Gastronómicos', 'flavor-chat-ia'),
                        'description' => __('Eventos, talleres y cenas especiales de Jantoki', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'jantoki',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Eventos y Talleres',
                                    'subtitulo' => 'Aprende, comparte y disfruta de la gastronomía local',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fdf8f3',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Próximos eventos',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Taller de fermentados',
                                            'descripcion' => 'Sábado 15 marzo — Aprende a elaborar chucrut, kimchi y kombucha con ingredientes locales. Incluye kit para casa.',
                                            'imagen' => '',
                                            'url' => '#taller-fermentados',
                                        ],
                                        [
                                            'titulo' => 'Cena de productores',
                                            'descripcion' => 'Viernes 21 marzo — Cena especial donde cada plato es presentado por el productor que cultivó los ingredientes.',
                                            'imagen' => '',
                                            'url' => '#cena-productores',
                                        ],
                                        [
                                            'titulo' => 'Curso de conservas',
                                            'descripcion' => 'Sábado 5 abril — Técnicas de conserva tradicional: encurtidos, mermeladas, confituras y escabeches artesanos.',
                                            'imagen' => '',
                                            'url' => '#curso-conservas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Quieres organizar un evento?',
                                    'subtitulo' => 'Ofrecemos nuestro espacio para talleres y celebraciones',
                                    'texto_cta' => 'Contactar',
                                    'url_cta' => '#contactar-eventos',
                                    'color_fondo' => '#d4953a',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'mercado-espiral' => [
                'label' => __('Mercado Espiral - Marketplace km0', 'flavor-chat-ia'),
                'templates' => [
                    'me_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Página principal del marketplace cooperativo Mercado Espiral', 'flavor-chat-ia'),
                        'icon' => 'dashicons-store',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'mercado-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Mercado Espiral',
                                    'subtitulo' => 'Tu marketplace cooperativo local. Compra directamente a productores de tu zona y recibe cashback en SEMILLA.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Explorar productos',
                                    'url_cta' => '#explorar',
                                    'overlay_color' => '#2e7d32',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => '¿Por qué Mercado Espiral?',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-location',
                                            'titulo' => 'Directo',
                                            'descripcion' => 'Del productor a tu mesa, sin intermediarios',
                                        ],
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Justo',
                                            'descripcion' => 'Precio justo para productores y consumidores',
                                        ],
                                        [
                                            'icono' => 'dashicons-update',
                                            'titulo' => 'Circular',
                                            'descripcion' => 'Cashback en SEMILLA que revierte en la economía local',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Productos Destacados',
                                    'columnas' => 4,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Cesta de temporada — 15,00€',
                                            'descripcion' => 'Selección semanal de frutas y verduras ecológicas de temporada de productores locales.',
                                            'imagen' => '',
                                            'url' => '#cesta-temporada',
                                        ],
                                        [
                                            'titulo' => 'Aceite de oliva ecológico — 12,00€/L',
                                            'descripcion' => 'Aceite virgen extra de oliva arbequina cultivada sin pesticidas, prensado en frío.',
                                            'imagen' => '',
                                            'url' => '#aceite-oliva',
                                        ],
                                        [
                                            'titulo' => 'Mermelada artesana — 5,50€',
                                            'descripcion' => 'Mermelada de fruta de temporada elaborada en pequeños lotes con métodos tradicionales.',
                                            'imagen' => '',
                                            'url' => '#mermelada',
                                        ],
                                        [
                                            'titulo' => 'Jabón natural — 4,00€',
                                            'descripcion' => 'Jabón artesano de aceite de oliva y plantas aromáticas locales, sin químicos añadidos.',
                                            'imagen' => '',
                                            'url' => '#jabon-natural',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'El Mercado en cifras',
                                    'columnas' => 4,
                                    'estilo' => 'gradient',
                                    'items' => [
                                        [
                                            'valor' => '45',
                                            'etiqueta' => 'Productores',
                                            'icono' => 'dashicons-groups',
                                        ],
                                        [
                                            'valor' => '320',
                                            'etiqueta' => 'Productos',
                                            'icono' => 'dashicons-products',
                                        ],
                                        [
                                            'valor' => '850',
                                            'etiqueta' => 'Familias',
                                            'icono' => 'dashicons-admin-home',
                                        ],
                                        [
                                            'valor' => '12.000kg',
                                            'etiqueta' => 'Vendidos/mes',
                                            'icono' => 'dashicons-cart',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Economía Circular Real',
                                    'contenido' => 'Cada compra en el Mercado Espiral genera un 3% de cashback en SEMILLA, nuestra moneda social local. Con SEMILLA puedes pagar en otros comercios del ecosistema, creando un círculo virtuoso que fortalece la economía local.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Vende en el Mercado',
                                    'subtitulo' => 'Si eres productor/a local, únete a nuestra red',
                                    'texto_cta' => 'Quiero vender',
                                    'url_cta' => '#quiero-vender',
                                    'color_fondo' => '#2e7d32',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'me_catalogo' => [
                        'name' => __('Catálogo', 'flavor-chat-ia'),
                        'description' => __('Catálogo completo de productos locales del Mercado Espiral', 'flavor-chat-ia'),
                        'icon' => 'dashicons-products',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'mercado-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Productos Locales',
                                    'subtitulo' => 'Frescos, artesanos y de temporada',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f0f7f0',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'categoria_producto',
                                    'estilo' => 'pills',
                                    'items' => [
                                        [
                                            'label' => 'Todos',
                                        ],
                                        [
                                            'label' => 'Frutas y Verduras',
                                        ],
                                        [
                                            'label' => 'Lácteos',
                                        ],
                                        [
                                            'label' => 'Carne y Pescado',
                                        ],
                                        [
                                            'label' => 'Pan y Repostería',
                                        ],
                                        [
                                            'label' => 'Conservas',
                                        ],
                                        [
                                            'label' => 'Artesanía',
                                        ],
                                        [
                                            'label' => 'Bebidas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 4,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Tomates ecológicos — 3,20€/kg',
                                            'descripcion' => 'Tomates de temporada cultivados sin pesticidas en huerta local. Variedad raf y corazón de buey.',
                                            'imagen' => '',
                                            'url' => '#tomates',
                                        ],
                                        [
                                            'titulo' => 'Queso Idiazabal — 14,50€/ud',
                                            'descripcion' => 'Queso artesano de oveja latxa con D.O. Idiazabal. Curación mínima de 4 meses.',
                                            'imagen' => '',
                                            'url' => '#queso-idiazabal',
                                        ],
                                        [
                                            'titulo' => 'Pan de espelta — 4,80€/ud',
                                            'descripcion' => 'Pan de masa madre con harina de espelta integral molida en piedra. Fermentación lenta de 24h.',
                                            'imagen' => '',
                                            'url' => '#pan-espelta',
                                        ],
                                        [
                                            'titulo' => 'Miel de montaña — 9,00€',
                                            'descripcion' => 'Miel cruda multifloral de montaña. Sin pasteurizar ni filtrar, con todas sus propiedades intactas.',
                                            'imagen' => '',
                                            'url' => '#miel-montana',
                                        ],
                                        [
                                            'titulo' => 'Sidra natural — 6,50€/bot',
                                            'descripcion' => 'Sidra natural elaborada con manzanas autóctonas según el método tradicional. Sin gas añadido.',
                                            'imagen' => '',
                                            'url' => '#sidra-natural',
                                        ],
                                        [
                                            'titulo' => 'Merluza de costa — 18,00€/kg',
                                            'descripcion' => 'Merluza de pincho capturada con anzuelo en la costa cantábrica. Pesca artesanal y sostenible.',
                                            'imagen' => '',
                                            'url' => '#merluza-costa',
                                        ],
                                        [
                                            'titulo' => 'Yogur artesano — 3,80€/ud',
                                            'descripcion' => 'Yogur natural de leche fresca de oveja latxa. Elaboración artesana en pequeños lotes.',
                                            'imagen' => '',
                                            'url' => '#yogur-artesano',
                                        ],
                                        [
                                            'titulo' => 'Aceite arbequina — 11,00€/L',
                                            'descripcion' => 'Aceite de oliva virgen extra de variedad arbequina. Prensado en frío, sabor suave y afrutado.',
                                            'imagen' => '',
                                            'url' => '#aceite-arbequina',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'me_productores' => [
                        'name' => __('Productores', 'flavor-chat-ia'),
                        'description' => __('Red de productores locales del Mercado Espiral', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'mercado-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestros Productores',
                                    'subtitulo' => 'Conoce quién cultiva, elabora y cuida lo que consumes',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f0f7f0',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Baserri Goikoa — Amorebieta',
                                            'descripcion' => 'Verduras ecológicas de temporada. Producción diversificada con más de 40 variedades locales.',
                                            'imagen' => '',
                                            'url' => '#baserri-goikoa',
                                        ],
                                        [
                                            'titulo' => 'Ardi Latxa — Ordizia',
                                            'descripcion' => 'Quesos y lácteos artesanos de oveja latxa. Producción familiar con 200 ovejas en pastoreo.',
                                            'imagen' => '',
                                            'url' => '#ardi-latxa',
                                        ],
                                        [
                                            'titulo' => 'Okin Zaharra — Tolosa',
                                            'descripcion' => 'Panadería artesana de masa madre y harinas de trigo antiguo. Pan, bollería y repostería.',
                                            'imagen' => '',
                                            'url' => '#okin-zaharra',
                                        ],
                                        [
                                            'titulo' => 'Sagardotegia Aia — Aia',
                                            'descripcion' => 'Sidra natural y derivados de manzana ecológica. Sagardotegi familiar con 300 años de historia.',
                                            'imagen' => '',
                                            'url' => '#sagardotegia',
                                        ],
                                        [
                                            'titulo' => 'Arrantzale Elkartea — Getaria',
                                            'descripcion' => 'Cofradía de pescadores artesanales. Pesca del día con artes selectivas y sostenibles.',
                                            'imagen' => '',
                                            'url' => '#arrantzale',
                                        ],
                                        [
                                            'titulo' => 'Eztia Baserria — Arantzazu',
                                            'descripcion' => 'Apicultura ecológica. Miel, polen, propóleo y cera de abejas en entorno de montaña.',
                                            'imagen' => '',
                                            'url' => '#eztia-baserria',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_map_section',
                                'data' => [
                                    'titulo' => 'Red de Productores',
                                    'direccion' => 'Territorio Gailu — 50km',
                                    'telefono' => '',
                                    'email' => '',
                                    'horario' => '',
                                    'latitud' => '',
                                    'longitud' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => '¿Quieres vender en el Mercado?',
                                    'contenido' => 'Buscamos productores locales comprometidos con la calidad y la sostenibilidad. Sin intermediarios, con precio justo y una comunidad que te respalda.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'me_temporada' => [
                        'name' => __('De Temporada', 'flavor-chat-ia'),
                        'description' => __('Productos de temporada y cestas semanales del Mercado Espiral', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'mercado-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Productos de Temporada',
                                    'subtitulo' => 'Lo mejor de cada época del año',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f0f7f0',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Calendario de temporada',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-carrot',
                                            'titulo' => 'Primavera',
                                            'descripcion' => 'Espárragos, guisantes, fresas',
                                        ],
                                        [
                                            'icono' => 'dashicons-palmtree',
                                            'titulo' => 'Verano',
                                            'descripcion' => 'Tomates, pimientos, melocotones',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-site',
                                            'titulo' => 'Otoño',
                                            'descripcion' => 'Setas, calabaza, manzanas',
                                        ],
                                        [
                                            'icono' => 'dashicons-cloud',
                                            'titulo' => 'Invierno',
                                            'descripcion' => 'Coles, puerros, cítricos',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Cestas de Temporada',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Cesta pequeña — 12€',
                                            'descripcion' => 'Ideal para 1-2 personas. 3-4kg de frutas y verduras frescas de temporada seleccionadas por nuestros productores.',
                                            'imagen' => '',
                                            'url' => '#cesta-pequena',
                                        ],
                                        [
                                            'titulo' => 'Cesta mediana — 20€',
                                            'descripcion' => 'Para 2-3 personas. 5-7kg de productos variados de temporada, incluyendo verduras, frutas y algún producto artesano.',
                                            'imagen' => '',
                                            'url' => '#cesta-mediana',
                                        ],
                                        [
                                            'titulo' => 'Cesta grande — 30€',
                                            'descripcion' => 'Para familias de 4+. 8-10kg de productos de temporada, con verduras, frutas, huevos, pan artesano y queso.',
                                            'imagen' => '',
                                            'url' => '#cesta-grande',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Suscríbete a la cesta semanal',
                                    'subtitulo' => 'Recibe cada semana los mejores productos de temporada',
                                    'texto_cta' => 'Suscribirme',
                                    'url_cta' => '#suscribirme',
                                    'color_fondo' => '#2e7d32',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'spiral-bank' => [
                'label' => __('Spiral Bank - Banca Cooperativa', 'flavor-chat-ia'),
                'templates' => [
                    'sb_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Página principal de la banca cooperativa Spiral Bank', 'flavor-chat-ia'),
                        'icon' => 'dashicons-money-alt',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'spiral-bank',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Spiral Bank',
                                    'subtitulo' => 'Tu banca cooperativa multi-moneda. Opera con EUR, SEMILLA, Horas y ESTRELLAS desde una sola cartera.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Abrir cuenta',
                                    'url_cta' => '#abrir-cuenta',
                                    'overlay_color' => '#764ba2',
                                    'overlay_opacidad' => 40,
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Un banco diferente',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Multi-moneda',
                                            'descripcion' => 'EUR + SEMILLA + Horas + ESTRELLAS',
                                        ],
                                        [
                                            'icono' => 'dashicons-no',
                                            'titulo' => 'Sin intereses',
                                            'descripcion' => 'Micropréstamos a 0% entre miembros',
                                        ],
                                        [
                                            'icono' => 'dashicons-visibility',
                                            'titulo' => 'Transparente',
                                            'descripcion' => 'Toda transacción es trazable y auditable',
                                        ],
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Cooperativo',
                                            'descripcion' => '1 persona = 1 voto en las decisiones',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Spiral Bank en cifras',
                                    'columnas' => 4,
                                    'estilo' => 'cards',
                                    'items' => [
                                        [
                                            'valor' => '2.500',
                                            'etiqueta' => 'Cuentas activas',
                                            'icono' => 'dashicons-admin-users',
                                        ],
                                        [
                                            'valor' => '45.000€',
                                            'etiqueta' => 'SEMILLA en circulación',
                                            'icono' => 'dashicons-money-alt',
                                        ],
                                        [
                                            'valor' => '1.200',
                                            'etiqueta' => 'Horas intercambiadas',
                                            'icono' => 'dashicons-clock',
                                        ],
                                        [
                                            'valor' => '0%',
                                            'etiqueta' => 'Tipo de interés',
                                            'icono' => 'dashicons-heart',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Finanzas para las Personas',
                                    'contenido' => 'Spiral Bank es un sistema financiero cooperativo donde el dinero circula al servicio de la comunidad. Cada euro que depositas financia proyectos locales. Cada SEMILLA que ganas fortalece la economía circular.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Abre tu cuenta cooperativa',
                                    'subtitulo' => 'Sin comisiones, sin intereses, con impacto real',
                                    'texto_cta' => 'Empezar',
                                    'url_cta' => '#empezar',
                                    'color_fondo' => '#764ba2',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'sb_wallet' => [
                        'name' => __('Mi Cartera', 'flavor-chat-ia'),
                        'description' => __('Panel de gestión de cartera multi-moneda de Spiral Bank', 'flavor-chat-ia'),
                        'icon' => 'dashicons-portfolio',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'spiral-bank',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Mi Cartera',
                                    'subtitulo' => 'Gestiona todos tus activos desde un solo lugar',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f3f0ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Mis saldos',
                                    'columnas' => 4,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'EUR',
                                            'descripcion' => 'Saldo: 350,00€ — Moneda fiduciaria para transacciones con el exterior y pagos convencionales.',
                                            'imagen' => '',
                                            'url' => '#saldo-eur',
                                        ],
                                        [
                                            'titulo' => 'SEMILLA',
                                            'descripcion' => 'Saldo: 1.250 — Moneda social local generada por cashback y participación en el ecosistema.',
                                            'imagen' => '',
                                            'url' => '#saldo-semilla',
                                        ],
                                        [
                                            'titulo' => 'Horas',
                                            'descripcion' => 'Saldo: 24h — Moneda de tiempo del banco de tiempo comunitario. 1 hora = 1 hora de servicio.',
                                            'imagen' => '',
                                            'url' => '#saldo-horas',
                                        ],
                                        [
                                            'titulo' => 'ESTRELLAS',
                                            'descripcion' => 'Saldo: 180 — Tokens de reputación y reconocimiento. Se ganan contribuyendo a la comunidad.',
                                            'imagen' => '',
                                            'url' => '#saldo-estrellas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Acciones rápidas',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-upload',
                                            'titulo' => 'Enviar',
                                            'descripcion' => 'Transfiere a cualquier miembro',
                                        ],
                                        [
                                            'icono' => 'dashicons-download',
                                            'titulo' => 'Recibir',
                                            'descripcion' => 'Genera un código QR de pago',
                                        ],
                                        [
                                            'icono' => 'dashicons-update',
                                            'titulo' => 'Convertir',
                                            'descripcion' => 'Intercambia entre monedas en el Bridge',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'sb_transferencias' => [
                        'name' => __('Transferencias', 'flavor-chat-ia'),
                        'description' => __('Sistema de transferencias P2P entre miembros de Spiral Bank', 'flavor-chat-ia'),
                        'icon' => 'dashicons-randomize',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'spiral-bank',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Transferencias P2P',
                                    'subtitulo' => 'Envía y recibe pagos entre miembros de la comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f3f0ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Cómo Funciona',
                                    'contenido' => 'Las transferencias en Spiral Bank son instantáneas y sin comisiones. Puedes enviar EUR, SEMILLA, Horas o ESTRELLAS a cualquier miembro. Solo necesitas su nombre o código de usuario.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Ventajas',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-performance',
                                            'titulo' => 'Instantáneas',
                                            'descripcion' => 'Se procesan al momento',
                                        ],
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Sin comisiones',
                                            'descripcion' => '0% de coste',
                                        ],
                                        [
                                            'icono' => 'dashicons-update',
                                            'titulo' => 'Multi-moneda',
                                            'descripcion' => 'Elige la moneda que quieras',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas frecuentes',
                                    'items' => [
                                        [
                                            'pregunta' => '¿Cuánto tardo en recibir?',
                                            'respuesta' => 'Las transferencias entre miembros de Spiral Bank son instantáneas en todas las monedas. En el momento en que el remitente confirma el envío, el saldo aparece en tu cartera.',
                                        ],
                                        [
                                            'pregunta' => '¿Hay límite de envío?',
                                            'respuesta' => 'Para EUR el límite diario es de 1.000€. Para SEMILLA y ESTRELLAS no hay límite. Para Horas, el límite es de 8h por transacción para garantizar el equilibrio del sistema.',
                                        ],
                                        [
                                            'pregunta' => '¿Puedo enviar a personas fuera de la comunidad?',
                                            'respuesta' => 'Las monedas sociales (SEMILLA, Horas, ESTRELLAS) solo se pueden transferir entre miembros registrados. Para EUR, se puede hacer una transferencia externa con una pequeña comisión de gestión.',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                    'sb_prestamos' => [
                        'name' => __('Préstamos Solidarios', 'flavor-chat-ia'),
                        'description' => __('Micropréstamos a 0% interés entre miembros de Spiral Bank', 'flavor-chat-ia'),
                        'icon' => 'dashicons-heart',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'spiral-bank',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Préstamos Solidarios',
                                    'subtitulo' => 'Micropréstamos a 0% interés entre miembros de la comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'color_fondo' => '#764ba2',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Condiciones',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-no',
                                            'titulo' => '0% Interés',
                                            'descripcion' => 'Sin intereses ni comisiones ocultas',
                                        ],
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Comunitario',
                                            'descripcion' => 'Avalados por tu reputación en la red',
                                        ],
                                        [
                                            'icono' => 'dashicons-calendar',
                                            'titulo' => 'Flexible',
                                            'descripcion' => 'Plazos adaptados a cada situación',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Finanzas Solidarias',
                                    'contenido' => 'Los préstamos de Spiral Bank no cobran intereses. Funcionan con un sistema de avales comunitarios basado en la confianza y la participación. Tu historial de contribución a la comunidad es tu mejor garantía.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Resultados',
                                    'columnas' => 3,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '85',
                                            'etiqueta' => 'Préstamos concedidos',
                                            'icono' => 'dashicons-yes-alt',
                                        ],
                                        [
                                            'valor' => '98%',
                                            'etiqueta' => 'Tasa de retorno',
                                            'icono' => 'dashicons-chart-line',
                                        ],
                                        [
                                            'valor' => '500€',
                                            'etiqueta' => 'Préstamo medio',
                                            'icono' => 'dashicons-money-alt',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => '¿Necesitas un préstamo?',
                                    'subtitulo' => 'Consulta las condiciones y solicita el tuyo',
                                    'texto_cta' => 'Solicitar',
                                    'url_cta' => '#solicitar-prestamo',
                                    'color_fondo' => '#764ba2',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'sb_circulos' => [
                        'name' => __('Círculos de Ahorro', 'flavor-chat-ia'),
                        'description' => __('Grupos rotativos de ahorro colectivo — Tontinas cooperativas', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'spiral-bank',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Círculos de Ahorro',
                                    'subtitulo' => 'Grupos rotativos de ahorro colectivo — Tontinas cooperativas',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f3f0ff',
                                ],
                                'settings' => [
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => '¿Qué es un Círculo de Ahorro?',
                                    'contenido' => 'Los círculos de ahorro son grupos de 8-12 personas que aportan una cuota mensual fija. Cada mes, un miembro recibe el pozo completo. Es un sistema ancestral de ahorro colectivo, digitalizado y transparente.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Cómo funciona',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-shield',
                                            'titulo' => 'Confianza',
                                            'descripcion' => 'Basado en relaciones reales',
                                        ],
                                        [
                                            'icono' => 'dashicons-update',
                                            'titulo' => 'Rotativo',
                                            'descripcion' => 'Cada mes cobra un miembro',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-generic',
                                            'titulo' => 'Flexible',
                                            'descripcion' => 'Desde 25€/mes',
                                        ],
                                        [
                                            'icono' => 'dashicons-visibility',
                                            'titulo' => 'Transparente',
                                            'descripcion' => 'Todo el historial visible',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Círculos activos',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Círculo Arrabal',
                                            'descripcion' => '10 miembros — 50€/mes — Pozo mensual: 500€. Grupo consolidado con 2 años de funcionamiento.',
                                            'imagen' => '',
                                            'url' => '#circulo-arrabal',
                                        ],
                                        [
                                            'titulo' => 'Círculo Txantrea',
                                            'descripcion' => '8 miembros — 30€/mes — Pozo mensual: 240€. Grupo abierto, quedan 2 plazas disponibles.',
                                            'imagen' => '',
                                            'url' => '#circulo-txantrea',
                                        ],
                                        [
                                            'titulo' => 'Círculo Iturrama',
                                            'descripcion' => '12 miembros — 100€/mes — Pozo mensual: 1.200€. Grupo completo, lista de espera abierta.',
                                            'imagen' => '',
                                            'url' => '#circulo-iturrama',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'large',
                                ],
                            ],
                        ],
                    ],
                ],
            ],


            // --- Red de Cuidados - Apoyo Mutuo ---
            'red-cuidados' => [
                'label' => __('Red de Cuidados - Apoyo Mutuo', 'flavor-chat-ia'),
                'templates' => [
                    'rc_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Pagina principal de la Red de Cuidados', 'flavor-chat-ia'),
                        'icon' => 'dashicons-heart',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'red-cuidados',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Red de Cuidados',
                                    'subtitulo' => 'Nadie cuida solo/a. Un sistema comunitario de apoyo mutuo donde dar y recibir cuidados fortalece el tejido social.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Unete a la red',
                                    'url_cta' => '#',
                                    'overlay_color' => '#ec4899',
                                    'overlay_opacidad' => '0.7',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Estructura de la Red',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-admin-users',
                                            'titulo' => 'Celulas de cuidado',
                                            'descripcion' => '5-15 personas, apoyo intimo',
                                        ],
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Circulos de cuidado',
                                            'descripcion' => '50-150 personas, red barrial',
                                        ],
                                        [
                                            'icono' => 'dashicons-networking',
                                            'titulo' => 'Red territorial',
                                            'descripcion' => '500-2.000 personas, servicios compartidos',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-site',
                                            'titulo' => 'Red biorregional',
                                            'descripcion' => '10.000+ personas, coordinacion comarcal',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Tipos de Cuidado',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Cuidado de emergencia',
                                            'descripcion' => 'Red de respuesta rapida',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cuidado regular',
                                            'descripcion' => 'Acompanamiento semanal',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Talleres terapeuticos',
                                            'descripcion' => 'Grupos de apoyo emocional',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'La Red en Numeros',
                                    'columnas' => 4,
                                    'estilo' => 'gradient',
                                    'items' => [
                                        [
                                            'valor' => '340',
                                            'etiqueta' => 'Cuidadores/as activos',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '2.800',
                                            'etiqueta' => 'Horas intercambiadas',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '12',
                                            'etiqueta' => 'Circulos activos',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '15 min',
                                            'etiqueta' => 'Tiempo medio de respuesta',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Prueba de Ayuda',
                                    'contenido' => 'En la Red de Cuidados, tu reputacion se basa en las horas que has dado a los demas. No hay rankings ni competicion: solo un sistema de confianza basado en la reciprocidad real.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Unete a un circulo de cuidados',
                                    'subtitulo' => 'Cada hora que das, fortalece la red',
                                    'texto_cta' => 'Quiero participar',
                                    'url_cta' => '#',
                                    'color_fondo' => '#ec4899',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'rc_profesionales' => [
                        'name' => __('Profesionales', 'flavor-chat-ia'),
                        'description' => __('Red de profesionales de la salud comunitaria', 'flavor-chat-ia'),
                        'icon' => 'dashicons-businessperson',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'red-cuidados',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Profesionales de la Salud',
                                    'subtitulo' => 'Red de profesionales comprometidos con la salud comunitaria',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fef0f6',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'especialidad',
                                    'estilo' => 'pills',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Medicina'],
                                        ['label' => 'Psicologia'],
                                        ['label' => 'Fisioterapia'],
                                        ['label' => 'Enfermeria'],
                                        ['label' => 'Trabajo social'],
                                        ['label' => 'Terapias complementarias'],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Dra. Amaia Etxeberria',
                                            'descripcion' => 'Medicina familiar',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Jon Zabala',
                                            'descripcion' => 'Psicologia comunitaria',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Miren Aguirre',
                                            'descripcion' => 'Fisioterapia',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ane Iriarte',
                                            'descripcion' => 'Enfermeria domiciliaria',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Koldo Navarro',
                                            'descripcion' => 'Trabajo social',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Leire Mendoza',
                                            'descripcion' => 'Osteopatia',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                        ],
                    ],
                    'rc_banco_tiempo' => [
                        'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                        'description' => __('Sistema de intercambio de tiempo comunitario', 'flavor-chat-ia'),
                        'icon' => 'dashicons-clock',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'red-cuidados',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Banco de Tiempo',
                                    'subtitulo' => '1 hora = 1 hora. Sin importar el servicio.',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fef0f6',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Asi de Simple',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-upload',
                                            'titulo' => 'Ofrece',
                                            'descripcion' => 'Publica las habilidades y tiempo que puedes ofrecer',
                                        ],
                                        [
                                            'icono' => 'dashicons-download',
                                            'titulo' => 'Solicita',
                                            'descripcion' => 'Pide ayuda cuando la necesites',
                                        ],
                                        [
                                            'icono' => 'dashicons-update',
                                            'titulo' => 'Intercambia',
                                            'descripcion' => '1 hora dada = 1 hora recibida, siempre',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Como Funciona',
                                    'contenido' => 'El banco de tiempo es un sistema de intercambio donde todas las horas valen lo mismo. Una hora de cuidado infantil vale lo mismo que una hora de asesoria legal. Lo que importa es el tiempo que dedicas a los demas.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Servicios Disponibles',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Cuidado infantil',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Acompanamiento personas mayores',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Clases de idiomas',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Reparaciones del hogar',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cocina comunitaria',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Transporte solidario',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'El Banco en Cifras',
                                    'columnas' => 3,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '2.800',
                                            'etiqueta' => 'Horas en circulacion',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '340',
                                            'etiqueta' => 'Personas participantes',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '42',
                                            'etiqueta' => 'Tipos de servicio',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'rc_circulos' => [
                        'name' => __('Circulos de Apoyo', 'flavor-chat-ia'),
                        'description' => __('Espacios seguros de escucha y acompanamiento', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'red-cuidados',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Circulos de Apoyo',
                                    'subtitulo' => 'Espacios seguros de escucha, acompanamiento y crecimiento colectivo',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Nuestros Circulos',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Circulo de maternidad y crianza',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Circulo de duelo y perdida',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Circulo de salud mental',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Circulo de personas mayores',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Quieres crear un circulo?',
                                    'contenido' => 'Si sientes la necesidad de un espacio de apoyo que no existe, te ayudamos a crearlo. Facilitamos la formacion, el espacio y las herramientas.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas Frecuentes',
                                    'items' => [
                                        [
                                            'pregunta' => 'Que es un circulo de apoyo?',
                                            'respuesta' => 'Un grupo de 8-12 personas que se reunen periodicamente para compartir experiencias y apoyarse mutuamente.',
                                        ],
                                        [
                                            'pregunta' => 'Son confidenciales?',
                                            'respuesta' => 'Si, todo lo compartido en un circulo es estrictamente confidencial.',
                                        ],
                                        [
                                            'pregunta' => 'Necesito experiencia previa?',
                                            'respuesta' => 'No, solo ganas de compartir y escuchar.',
                                        ],
                                        [
                                            'pregunta' => 'Cuanto cuestan?',
                                            'respuesta' => 'Son gratuitos, financiados por el banco de tiempo.',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'rc_seguro_mutuo' => [
                        'name' => __('Seguro Mutuo', 'flavor-chat-ia'),
                        'description' => __('Mutualidad cooperativa de salud comunitaria', 'flavor-chat-ia'),
                        'icon' => 'dashicons-shield',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'red-cuidados',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Seguro Mutuo de Salud',
                                    'subtitulo' => 'Mutualidad cooperativa de salud con cobertura comunitaria',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'color_fondo' => '#ec4899',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Caracteristicas',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Comunitario',
                                            'descripcion' => 'Gestionado por y para los miembros',
                                        ],
                                        [
                                            'icono' => 'dashicons-plus-alt',
                                            'titulo' => 'Integral',
                                            'descripcion' => 'Medicina convencional + complementaria',
                                        ],
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Accesible',
                                            'descripcion' => 'Cuotas segun capacidad economica',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'En Cifras',
                                    'columnas' => 3,
                                    'estilo' => 'cards',
                                    'items' => [
                                        [
                                            'valor' => '250',
                                            'etiqueta' => 'Mutualistas',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '15 euros/mes',
                                            'etiqueta' => 'Cuota media',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '92%',
                                            'etiqueta' => 'Satisfaccion',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas Frecuentes',
                                    'items' => [
                                        [
                                            'pregunta' => 'Que cubre el seguro mutuo?',
                                            'respuesta' => 'Cubre consultas de medicina general, especialistas, terapias complementarias, urgencias y acompanamiento hospitalario.',
                                        ],
                                        [
                                            'pregunta' => 'Como se calculan las cuotas?',
                                            'respuesta' => 'Las cuotas se calculan segun la capacidad economica de cada miembro, siguiendo un modelo de economia solidaria.',
                                        ],
                                        [
                                            'pregunta' => 'Puedo mantener mi seguro convencional?',
                                            'respuesta' => 'Si, el seguro mutuo es complementario y compatible con cualquier otro seguro de salud.',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Protege tu salud en comunidad',
                                    'subtitulo' => 'Mas que un seguro, una red de confianza',
                                    'texto_cta' => 'Informarme',
                                    'url_cta' => '#',
                                    'color_fondo' => '#ec4899',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // --- Academia Espiral - Educacion P2P ---
            'academia-espiral' => [
                'label' => __('Academia Espiral - Educacion P2P', 'flavor-chat-ia'),
                'templates' => [
                    'ae_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Pagina principal de la Academia Espiral', 'flavor-chat-ia'),
                        'icon' => 'dashicons-welcome-learn-more',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'academia-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Academia Espiral',
                                    'subtitulo' => 'Aprende ensenando, ensena aprendiendo. Educacion entre iguales con recompensas en SEMILLA.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Explorar cursos',
                                    'url_cta' => '#',
                                    'overlay_color' => '#d97706',
                                    'overlay_opacidad' => '0.7',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Tu Camino de Aprendizaje',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-search',
                                            'titulo' => 'Curioso',
                                            'descripcion' => 'Explora y descubre nuevas areas',
                                        ],
                                        [
                                            'icono' => 'dashicons-book',
                                            'titulo' => 'Estudiante',
                                            'descripcion' => 'Sigue rutas de aprendizaje',
                                        ],
                                        [
                                            'icono' => 'dashicons-hammer',
                                            'titulo' => 'Practicante',
                                            'descripcion' => 'Aplica y ensena lo aprendido',
                                        ],
                                        [
                                            'icono' => 'dashicons-star-filled',
                                            'titulo' => 'Maestro/a',
                                            'descripcion' => 'Guia a otros en su camino',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Cursos Destacados',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Permacultura urbana',
                                            'descripcion' => '12 sesiones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cooperativismo practico',
                                            'descripcion' => '8 sesiones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Moneda social y economia circular',
                                            'descripcion' => '6 sesiones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'La Academia en Numeros',
                                    'columnas' => 4,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '85',
                                            'etiqueta' => 'Cursos activos',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '340',
                                            'etiqueta' => 'Estudiantes',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '45',
                                            'etiqueta' => 'Mentores',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '1.200',
                                            'etiqueta' => 'Horas de aprendizaje',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Educacion que Transforma',
                                    'contenido' => 'La Academia Espiral no tiene profesores ni alumnos tradicionales. Aqui todos somos aprendices y todos podemos ensenar. Cada conocimiento compartido genera recompensas en SEMILLA y construye tu reputacion como persona de valor en la comunidad.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Ensena lo que sabes',
                                    'subtitulo' => 'Tu conocimiento tiene valor. Compartelo y recibe SEMILLA a cambio.',
                                    'texto_cta' => 'Crear un curso',
                                    'url_cta' => '#',
                                    'color_fondo' => '#d97706',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'ae_cursos' => [
                        'name' => __('Catalogo de Cursos', 'flavor-chat-ia'),
                        'description' => __('Catalogo completo de cursos disponibles', 'flavor-chat-ia'),
                        'icon' => 'dashicons-book',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'academia-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Cursos Disponibles',
                                    'subtitulo' => 'Aprende a tu ritmo con mentores de tu comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#fff8f0',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'categoria_curso',
                                    'estilo' => 'pills',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Agricultura'],
                                        ['label' => 'Tecnologia'],
                                        ['label' => 'Cooperativismo'],
                                        ['label' => 'Artesania'],
                                        ['label' => 'Idiomas'],
                                        ['label' => 'Salud'],
                                        ['label' => 'Oficios'],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Huerto ecologico en casa',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Programacion web basica',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Contabilidad cooperativa',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ceramica artesanal',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Euskera nivel A1',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Primeros auxilios',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Carpinteria basica',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cocina de aprovechamiento',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Reparacion de bicicletas',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                        ],
                    ],
                    'ae_mentores' => [
                        'name' => __('Mentores', 'flavor-chat-ia'),
                        'description' => __('Mentores de la comunidad que comparten conocimiento', 'flavor-chat-ia'),
                        'icon' => 'dashicons-businessperson',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'academia-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Nuestros Mentores',
                                    'subtitulo' => 'Personas de la comunidad que comparten su conocimiento',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Iker Larranaga',
                                            'descripcion' => 'Permacultura',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Amaia Ruiz',
                                            'descripcion' => 'Cooperativismo',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Mikel Zabala',
                                            'descripcion' => 'Programacion',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Nerea Etxebarria',
                                            'descripcion' => 'Artesania textil',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Joseba Irigoyen',
                                            'descripcion' => 'Carpinteria',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Leire Arana',
                                            'descripcion' => 'Medicina natural',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Quieres ser mentor/a?',
                                    'contenido' => 'Si tienes una habilidad o conocimiento que compartir, la Academia te proporciona las herramientas y el espacio. Cada hora de mentoria se recompensa con SEMILLA.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'ae_rutas' => [
                        'name' => __('Rutas de Aprendizaje', 'flavor-chat-ia'),
                        'description' => __('Itinerarios formativos completos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-links',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'academia-espiral',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Rutas de Aprendizaje',
                                    'subtitulo' => 'Itinerarios formativos completos para adquirir nuevas competencias',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Rutas Disponibles',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Ruta Agroecologia',
                                            'descripcion' => '5 cursos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ruta Digital',
                                            'descripcion' => '4 cursos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ruta Cooperativa',
                                            'descripcion' => '6 cursos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ruta Oficios',
                                            'descripcion' => '5 cursos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Ventajas de las Rutas',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-editor-ol',
                                            'titulo' => 'Estructuradas',
                                            'descripcion' => 'Cursos en orden logico',
                                        ],
                                        [
                                            'icono' => 'dashicons-chart-line',
                                            'titulo' => 'Progresivas',
                                            'descripcion' => 'De basico a avanzado',
                                        ],
                                        [
                                            'icono' => 'dashicons-awards',
                                            'titulo' => 'Certificadas',
                                            'descripcion' => 'Certificado comunitario al completar',
                                        ],
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Recompensadas',
                                            'descripcion' => 'SEMILLA por cada curso completado',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // --- Democracia Universal - Gobernanza Participativa ---
            'democracia-universal' => [
                'label' => __('Democracia Universal - Gobernanza Participativa', 'flavor-chat-ia'),
                'templates' => [
                    'du_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Pagina principal de Democracia Universal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-megaphone',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'democracia-universal',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'Democracia Universal',
                                    'subtitulo' => 'Gobernanza participativa real. Voto directo, delegacion revocable y presupuestos colaborativos.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Ver propuestas activas',
                                    'url_cta' => '#',
                                    'overlay_color' => '#8b5cf6',
                                    'overlay_opacidad' => '0.7',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Sistemas de Votacion',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-randomize',
                                            'titulo' => 'Democracia liquida',
                                            'descripcion' => 'Vota directamente o delega tu voto de forma revocable',
                                        ],
                                        [
                                            'icono' => 'dashicons-chart-bar',
                                            'titulo' => 'Votacion cuadratica',
                                            'descripcion' => 'Expresa la intensidad de tus preferencias',
                                        ],
                                        [
                                            'icono' => 'dashicons-clock',
                                            'titulo' => 'Votacion por conviccion',
                                            'descripcion' => 'Tu voto pesa mas cuanto mas tiempo lo mantienes',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Propuestas Activas',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Presupuesto participativo 2025',
                                            'descripcion' => '45 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Nuevo huerto comunitario en Txantrea',
                                            'descripcion' => '32 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ampliacion horario biblioteca',
                                            'descripcion' => '28 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Participacion en Cifras',
                                    'columnas' => 4,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '156',
                                            'etiqueta' => 'Propuestas resueltas',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '78%',
                                            'etiqueta' => 'Participacion media',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '12',
                                            'etiqueta' => 'Asambleas/ano',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '1 persona = 1 voto',
                                            'etiqueta' => '',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Gobernanza Distribuida',
                                    'contenido' => 'En Democracia Universal, cada persona tiene una voz igual. Las decisiones se toman de forma transparente, los presupuestos son publicos y cualquier miembro puede proponer, debatir y votar.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Propon, debate, decide',
                                    'subtitulo' => 'Tu voz importa en cada decision',
                                    'texto_cta' => 'Crear propuesta',
                                    'url_cta' => '#',
                                    'color_fondo' => '#8b5cf6',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'du_propuestas' => [
                        'name' => __('Propuestas Activas', 'flavor-chat-ia'),
                        'description' => __('Listado de propuestas activas para votacion', 'flavor-chat-ia'),
                        'icon' => 'dashicons-clipboard',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'democracia-universal',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Propuestas Activas',
                                    'subtitulo' => 'Participa en las decisiones que afectan a tu comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f5f3ff',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'categoria_propuesta',
                                    'estilo' => 'underline',
                                    'items' => [
                                        ['label' => 'Todas'],
                                        ['label' => 'Infraestructura'],
                                        ['label' => 'Cultura'],
                                        ['label' => 'Economia'],
                                        ['label' => 'Medio ambiente'],
                                        ['label' => 'Educacion'],
                                        ['label' => 'Gobernanza'],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 2,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Mercado semanal en plaza',
                                            'descripcion' => '67 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Carril bici Txantrea-centro',
                                            'descripcion' => '54 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Programa de compostaje',
                                            'descripcion' => '41 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Festival de barrio',
                                            'descripcion' => '38 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Ayuda escolar cooperativa',
                                            'descripcion' => '35 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Huertos solares comunitarios',
                                            'descripcion' => '29 votos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                        ],
                    ],
                    'du_como_funciona' => [
                        'name' => __('Como Funciona', 'flavor-chat-ia'),
                        'description' => __('Explicacion del sistema de gobernanza participativa', 'flavor-chat-ia'),
                        'icon' => 'dashicons-editor-help',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'democracia-universal',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Como Funciona?',
                                    'subtitulo' => 'Gobernanza participativa paso a paso',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'El Proceso',
                                    'subtitulo' => '',
                                    'columnas' => 4,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-edit',
                                            'titulo' => '1. Propon',
                                            'descripcion' => 'Cualquier miembro puede crear una propuesta',
                                        ],
                                        [
                                            'icono' => 'dashicons-format-chat',
                                            'titulo' => '2. Debate',
                                            'descripcion' => 'La comunidad discute, mejora y enriquece',
                                        ],
                                        [
                                            'icono' => 'dashicons-yes-alt',
                                            'titulo' => '3. Vota',
                                            'descripcion' => 'Voto directo o delegado, tu decides',
                                        ],
                                        [
                                            'icono' => 'dashicons-hammer',
                                            'titulo' => '4. Ejecuta',
                                            'descripcion' => 'Las propuestas aprobadas se implementan',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Democracia Liquida',
                                    'contenido' => 'Puedes votar directamente en cada propuesta, o delegar tu voto en una persona de confianza. La delegacion es revocable en cualquier momento: si cambias de opinion, recuperas tu voto.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Votacion Cuadratica',
                                    'contenido' => 'Este sistema permite expresar no solo tu preferencia sino su intensidad. Votar 1 vez cuesta 1 token, votar 2 veces cuesta 4 tokens, 3 veces cuesta 9 tokens. Asi se evita que una minoria intensa domine sobre la mayoria.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_accordion',
                                'data' => [
                                    'titulo' => 'Preguntas Frecuentes',
                                    'items' => [
                                        [
                                            'pregunta' => 'Quien puede votar?',
                                            'respuesta' => 'Cualquier miembro registrado con al menos 1 mes de antiguedad.',
                                        ],
                                        [
                                            'pregunta' => 'Como funciona la delegacion?',
                                            'respuesta' => 'Eliges a una persona de confianza que vota por ti. Puedes revocarla en cualquier momento.',
                                        ],
                                        [
                                            'pregunta' => 'Que pasa si una propuesta es aprobada?',
                                            'respuesta' => 'Se asigna presupuesto y un equipo responsable de ejecutarla.',
                                        ],
                                        [
                                            'pregunta' => 'Puedo proponer cualquier cosa?',
                                            'respuesta' => 'Si, siempre que cumpla los valores de la comunidad y sea viable.',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'du_delegaciones' => [
                        'name' => __('Delegaciones', 'flavor-chat-ia'),
                        'description' => __('Sistema de delegaciones de voto', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-users',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'democracia-universal',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Delegaciones de Voto',
                                    'subtitulo' => 'Confia tu voto a personas expertas en cada tema',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#f5f3ff',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Que es la Delegacion?',
                                    'contenido' => 'En la democracia liquida, puedes delegar tu voto en diferentes personas segun el tema. Por ejemplo, puedes delegar temas de medio ambiente a una ecologista experta y temas economicos a un cooperativista. Siempre puedes recuperar tu voto y votar directamente.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Caracteristicas de la Delegacion',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-category',
                                            'titulo' => 'Por tema',
                                            'descripcion' => 'Delega por area: cultura, economia, medio ambiente',
                                        ],
                                        [
                                            'icono' => 'dashicons-undo',
                                            'titulo' => 'Revocable',
                                            'descripcion' => 'Recupera tu voto en cualquier momento',
                                        ],
                                        [
                                            'icono' => 'dashicons-visibility',
                                            'titulo' => 'Transparente',
                                            'descripcion' => 'Todas las delegaciones son publicas',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Delegados Destacados',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Amaia G.',
                                            'descripcion' => 'Medio ambiente — 23 delegaciones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Inaki R.',
                                            'descripcion' => 'Economia — 18 delegaciones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Miren L.',
                                            'descripcion' => 'Cultura — 15 delegaciones',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // --- FLUJO - Red de Video Consciente ---
            'flujo' => [
                'label' => __('FLUJO - Red de Video Consciente', 'flavor-chat-ia'),
                'templates' => [
                    'fl_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'),
                        'description' => __('Pagina principal de FLUJO', 'flavor-chat-ia'),
                        'icon' => 'dashicons-video-alt3',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'flujo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_fullscreen',
                                'data' => [
                                    'titulo' => 'FLUJO',
                                    'subtitulo' => 'La red de video que recompensa impacto, no adiccion. Comparte conocimiento y gana SEMILLA.',
                                    'imagen_fondo' => '',
                                    'texto_cta' => 'Explorar videos',
                                    'url_cta' => '#',
                                    'overlay_color' => '#166534',
                                    'overlay_opacidad' => '0.7',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Por que FLUJO?',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-chart-area',
                                            'titulo' => 'Impacto real',
                                            'descripcion' => 'Tu contenido se mide por su valor educativo y social, no por clics',
                                        ],
                                        [
                                            'icono' => 'dashicons-money-alt',
                                            'titulo' => 'Recompensas SEMILLA',
                                            'descripcion' => 'Gana moneda social por crear contenido de alto valor',
                                        ],
                                        [
                                            'icono' => 'dashicons-shield',
                                            'titulo' => 'Sin algoritmos toxicos',
                                            'descripcion' => 'Sin autoplay infinito, sin clickbait, sin burbujas de filtro',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => 'Videos Destacados',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Como montar un huerto en tu balcon',
                                            'descripcion' => '3x valor',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cooperativismo: guia practica',
                                            'descripcion' => '3x valor',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Taller de compostaje casero',
                                            'descripcion' => '3x valor',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'FLUJO en Numeros',
                                    'columnas' => 4,
                                    'estilo' => 'minimal',
                                    'items' => [
                                        [
                                            'valor' => '450',
                                            'etiqueta' => 'Videos',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '120',
                                            'etiqueta' => 'Creadores',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '8.500',
                                            'etiqueta' => 'Horas vistas',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '3x',
                                            'etiqueta' => 'Multiplicador de impacto',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Video con Proposito',
                                    'contenido' => 'FLUJO premia el contenido que ensena, inspira y transforma. Los tutoriales, documentales y videos educativos reciben un multiplicador 3x en recompensas SEMILLA. Aqui no ganas por views sino por el valor real que aportas.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'Comparte tu conocimiento',
                                    'subtitulo' => 'Cada video que subes puede transformar vidas',
                                    'texto_cta' => 'Subir video',
                                    'url_cta' => '#',
                                    'color_fondo' => '#166534',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'fl_explorar' => [
                        'name' => __('Explorar', 'flavor-chat-ia'),
                        'description' => __('Explorar videos de la comunidad', 'flavor-chat-ia'),
                        'icon' => 'dashicons-search',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'flujo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Explorar Videos',
                                    'subtitulo' => 'Contenido de valor creado por tu comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '#ecfdf5',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_filters_bar',
                                'data' => [
                                    'taxonomia' => 'categoria_video',
                                    'estilo' => 'pills',
                                    'items' => [
                                        ['label' => 'Todos'],
                                        ['label' => 'Tutoriales'],
                                        ['label' => 'Permacultura'],
                                        ['label' => 'Cooperacion'],
                                        ['label' => 'Innovacion social'],
                                        ['label' => 'Cultura local'],
                                        ['label' => 'Oficios'],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'small',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'border',
                                    'items' => [
                                        [
                                            'titulo' => 'Jabon artesanal paso a paso',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Asamblea cooperativa: guia',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Cocina de temporada: enero',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Huerto permacultural: diseno',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Reparar en vez de tirar',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Moneda social: como funciona',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Yoga comunitario: sesion 1',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Arquitectura bioclimatica: intro',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Conservas caseras: tecnicas',
                                            'descripcion' => '',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_pagination',
                                'data' => [
                                    'estilo' => 'numbered',
                                ],
                                'settings' => [
                                    'padding' => 'small',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                        ],
                    ],
                    'fl_creadores' => [
                        'name' => __('Creadores', 'flavor-chat-ia'),
                        'description' => __('Creadores de contenido en FLUJO', 'flavor-chat-ia'),
                        'icon' => 'dashicons-groups',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'flujo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Creadores de FLUJO',
                                    'subtitulo' => 'Personas que comparten su conocimiento con la comunidad',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_card_grid',
                                'data' => [
                                    'titulo' => '',
                                    'columnas' => 3,
                                    'estilo_card' => 'shadow',
                                    'items' => [
                                        [
                                            'titulo' => 'Ane Permacultura',
                                            'descripcion' => '34 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Joseba Oficios',
                                            'descripcion' => '28 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Miren Cocina',
                                            'descripcion' => '45 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Inaki Coop',
                                            'descripcion' => '22 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Leire Yoga',
                                            'descripcion' => '18 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                        [
                                            'titulo' => 'Koldo Tech',
                                            'descripcion' => '31 videos',
                                            'imagen' => '',
                                            'url' => '#',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'medium',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Se creador/a en FLUJO',
                                    'contenido' => 'Si tienes conocimiento que compartir, FLUJO te da las herramientas. Sube tus videos, recibe feedback de la comunidad y gana SEMILLA por tu impacto educativo y social.',
                                    'imagen' => '',
                                    'invertir' => true,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                    'fl_impacto' => [
                        'name' => __('Impacto', 'flavor-chat-ia'),
                        'description' => __('Panel de impacto de contenido en FLUJO', 'flavor-chat-ia'),
                        'icon' => 'dashicons-chart-area',
                        'preview' => '',
                        'menu_type' => 'centered',
                        'footer_type' => 'multi-column',
                        'theme' => 'flujo',
                        'layout' => [
                            [
                                'component_id' => 'themacle_hero_split',
                                'data' => [
                                    'titulo' => 'Panel de Impacto',
                                    'subtitulo' => 'Mide el valor real del contenido en FLUJO',
                                    'texto_cta' => '',
                                    'url_cta' => '',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'color_fondo' => '',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_highlights',
                                'data' => [
                                    'titulo' => 'Metricas Globales',
                                    'columnas' => 4,
                                    'estilo' => 'cards',
                                    'items' => [
                                        [
                                            'valor' => '450',
                                            'etiqueta' => 'Videos publicados',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '8.500',
                                            'etiqueta' => 'Horas de aprendizaje',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '12.400',
                                            'etiqueta' => 'SEMILLA distribuidas',
                                            'icono' => '',
                                        ],
                                        [
                                            'valor' => '3x',
                                            'etiqueta' => 'Multiplicador alto valor',
                                            'icono' => '',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_feature_grid',
                                'data' => [
                                    'titulo' => 'Categorias de Impacto',
                                    'subtitulo' => '',
                                    'columnas' => 3,
                                    'items' => [
                                        [
                                            'icono' => 'dashicons-welcome-learn-more',
                                            'titulo' => 'Educativo',
                                            'descripcion' => 'Tutoriales y formacion',
                                        ],
                                        [
                                            'icono' => 'dashicons-groups',
                                            'titulo' => 'Social',
                                            'descripcion' => 'Cooperacion y comunidad',
                                        ],
                                        [
                                            'icono' => 'dashicons-admin-site',
                                            'titulo' => 'Ecologico',
                                            'descripcion' => 'Permacultura y sostenibilidad',
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'white',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_text_media',
                                'data' => [
                                    'titulo' => 'Como se Mide el Impacto',
                                    'contenido' => 'FLUJO usa un sistema de valoracion comunitaria. Los videos son evaluados por su utilidad, claridad y potencial transformador. Las categorias de alto valor (tutoriales, permacultura, cooperacion) reciben un multiplicador 3x en recompensas.',
                                    'imagen' => '',
                                    'invertir' => false,
                                    'estilo' => 'simple',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'gray',
                                    'margin_bottom' => 'medium',
                                ],
                            ],
                            [
                                'component_id' => 'themacle_cta_banner',
                                'data' => [
                                    'titulo' => 'El conocimiento libre transforma',
                                    'subtitulo' => 'Contribuye al bien comun desde tu camara',
                                    'texto_cta' => 'Empezar a crear',
                                    'url_cta' => '#',
                                    'color_fondo' => '#166534',
                                ],
                                'settings' => [
                                    'padding' => 'large',
                                    'background' => 'primary',
                                    'margin_bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],


            // ─── Kulturaka ───
            'kulturaka' => [
                'label' => __('Kulturaka - Cultura Cooperativa', 'flavor-chat-ia'),
                'templates' => [
                    'ku_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'), 'description' => __('Página principal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-tickets-alt', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'kulturaka',
                        'layout' => [
                            ['component_id' => 'themacle_hero_fullscreen', 'data' => ['titulo' => 'Cultura Viva, Cultura Cooperativa', 'subtitulo' => 'Eventos, artistas y producción cultural de base', 'imagen_fondo' => '', 'texto_cta' => 'Ver agenda', 'url_cta' => '#eventos', 'overlay_color' => '#e63946', 'overlay_opacidad' => 40], 'settings' => ['margin_bottom' => 'none']],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Cultura para Todos', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-format-audio', 'titulo' => 'Conciertos', 'descripcion' => 'Música en vivo de artistas locales y emergentes'], ['icono' => 'dashicons-art', 'titulo' => 'Talleres', 'descripcion' => 'Aprende técnicas artísticas con creadores de la zona'], ['icono' => 'dashicons-format-image', 'titulo' => 'Exposiciones', 'descripcion' => 'Arte visual en espacios comunitarios']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Próximos Eventos', 'columnas' => 3, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Concierto: Kalakan', 'descripcion' => '22 Feb — Música — Plaza Mayor — Gratis', 'url' => '#'], ['titulo' => 'Taller de cerámica', 'descripcion' => '28 Feb — Taller — Centro Cultural — 15€', 'url' => '#'], ['titulo' => 'Expo: Paisajes rurales', 'descripcion' => '1-15 Mar — Exposición — Galería — Gratis', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'Cultura en Cifras', 'columnas' => 4, 'items' => [['valor' => '120', 'etiqueta' => 'Eventos anuales'], ['valor' => '65', 'etiqueta' => 'Artistas'], ['valor' => '8.400', 'etiqueta' => 'Asistentes'], ['valor' => '24K€', 'etiqueta' => 'Recaudación']]], 'settings' => ['background' => 'gray']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Organiza un Evento', 'subtitulo' => 'La cultura cooperativa la hacemos entre todos', 'texto_cta' => 'Proponer evento', 'url_cta' => '#organizar', 'color_fondo' => '#e63946'], 'settings' => []],
                        ],
                    ],
                    'ku_eventos' => [
                        'name' => __('Agenda', 'flavor-chat-ia'), 'description' => __('Agenda cultural', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'kulturaka',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Agenda Cultural', 'subtitulo' => 'Todos los eventos de la comunidad', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fff0f0'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'pills', 'items' => [['label' => 'Todos'], ['label' => 'Conciertos'], ['label' => 'Teatro'], ['label' => 'Exposiciones'], ['label' => 'Talleres'], ['label' => 'Cine'], ['label' => 'Literatura']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 3, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Bertso jaia', 'descripcion' => '15 Feb — Literatura — Teatro Principal', 'url' => '#'], ['titulo' => 'Danza contemporánea', 'descripcion' => '22 Feb — Danza — Centro Cultural', 'url' => '#'], ['titulo' => 'Ciclo documental', 'descripcion' => '1 Mar — Cine — Sala Areto', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                    'ku_artistas' => [
                        'name' => __('Artistas', 'flavor-chat-ia'), 'description' => __('Directorio de artistas', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-customizer', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'kulturaka',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Artistas y Creadores', 'subtitulo' => 'El talento de nuestra comunidad', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fff0f0'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'underline', 'items' => [['label' => 'Todos'], ['label' => 'Música'], ['label' => 'Artes visuales'], ['label' => 'Teatro'], ['label' => 'Danza'], ['label' => 'Audiovisual']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 3, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Kalakan', 'descripcion' => 'Música — Percusión y voz', 'url' => '#'], ['titulo' => 'Amaia Eskisabel', 'descripcion' => 'Artes visuales — Pintura y grabado', 'url' => '#'], ['titulo' => 'Tanttaka Teatroa', 'descripcion' => 'Teatro — Compañía de teatro social', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                    'ku_streaming' => [
                        'name' => __('En Directo', 'flavor-chat-ia'), 'description' => __('Eventos en directo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-controls-play', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'kulturaka',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Eventos en Directo', 'subtitulo' => 'Cultura sin fronteras', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#e63946'], 'settings' => []],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'En Directo Ahora', 'columnas' => 2, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Concierto acústico — En vivo', 'descripcion' => '45 espectadores conectados', 'url' => '#'], ['titulo' => 'Lectura poética — En vivo', 'descripcion' => '28 espectadores conectados', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Próximas Retransmisiones', 'columnas' => 3, 'estilo_card' => 'border', 'items' => [['titulo' => 'Festival de cortometrajes', 'descripcion' => '28 Feb — 20:00', 'url' => '#'], ['titulo' => 'Jam session jazz', 'descripcion' => '7 Mar — 21:00', 'url' => '#'], ['titulo' => 'Monólogo en euskera', 'descripcion' => '14 Mar — 19:30', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Transmite tu Evento', 'subtitulo' => 'Llega a más público retransmitiendo en directo', 'texto_cta' => 'Solicitar streaming', 'url_cta' => '#streaming', 'color_fondo' => '#e63946'], 'settings' => []],
                        ],
                    ],
                    'ku_biblioteca' => [
                        'name' => __('Archivo', 'flavor-chat-ia'), 'description' => __('Archivo cultural', 'flavor-chat-ia'),
                        'icon' => 'dashicons-media-archive', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'kulturaka',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Archivo Cultural', 'subtitulo' => 'Memoria viva de nuestra cultura', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fff0f0'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'pills', 'items' => [['label' => 'Todo'], ['label' => 'Documentales'], ['label' => 'Grabaciones'], ['label' => 'Fotografía'], ['label' => 'Textos']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 4, 'estilo_card' => 'border', 'items' => [['titulo' => 'Documental: Herri bat', 'descripcion' => 'Documental — 2024 — 45 min', 'url' => '#'], ['titulo' => 'Concierto fiestas 2024', 'descripcion' => 'Grabación — Audio + Vídeo', 'url' => '#'], ['titulo' => 'Fotos del mercado', 'descripcion' => 'Fotografía — 48 imágenes', 'url' => '#'], ['titulo' => 'Poemas del taller', 'descripcion' => 'Textos — Antología 2024', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                ],
            ],


            // ─── Pueblo Vivo ───
            'pueblo-vivo' => [
                'label' => __('Pueblo Vivo - Revitalización Rural', 'flavor-chat-ia'),
                'templates' => [
                    'pv_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'), 'description' => __('Página principal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-home', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'pueblo-vivo',
                        'layout' => [
                            ['component_id' => 'themacle_hero_fullscreen', 'data' => ['titulo' => 'Pueblo Vivo, Futuro Rural', 'subtitulo' => 'Combatir la despoblación con comunidad e infraestructura digital', 'imagen_fondo' => '', 'texto_cta' => 'Ven a vivir aquí', 'url_cta' => '#casas', 'overlay_color' => '#c2703a', 'overlay_opacidad' => 40], 'settings' => ['margin_bottom' => 'none']],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Qué Ofrecemos', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-admin-home', 'titulo' => 'Casas Guardián', 'descripcion' => 'Vive en una casa rural a cambio de cuidarla y mantenerla'], ['icono' => 'dashicons-desktop', 'titulo' => 'Hub Rural', 'descripcion' => 'Coworking, taller y sala polivalente en el pueblo'], ['icono' => 'dashicons-clock', 'titulo' => 'Banco de Tiempo', 'descripcion' => 'Intercambia servicios con tus vecinos y vecinas']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Casas Disponibles', 'columnas' => 3, 'estilo_card' => 'border', 'items' => [['titulo' => 'Casa de piedra rehabilitada', 'descripcion' => '120 m² — Modelo guardián — 200€/mes', 'url' => '#'], ['titulo' => 'Apartamento centro pueblo', 'descripcion' => '65 m² — Alquiler — 350€/mes', 'url' => '#'], ['titulo' => 'Caserío con huerto', 'descripcion' => '200 m² + 500 m² huerto — Guardián — 250€/mes', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'El Pueblo', 'columnas' => 4, 'items' => [['valor' => '340', 'etiqueta' => 'Población'], ['valor' => '12', 'etiqueta' => 'Casas recuperadas'], ['valor' => '8', 'etiqueta' => 'Comercios locales'], ['valor' => '5', 'etiqueta' => 'Huertos comunitarios']]], 'settings' => ['background' => 'gray']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Ven a Vivir Aquí', 'subtitulo' => 'Un pueblo con futuro te espera', 'texto_cta' => 'Ver casas disponibles', 'url_cta' => '#casas', 'color_fondo' => '#c2703a'], 'settings' => []],
                        ],
                    ],
                    'pv_casas' => [
                        'name' => __('Casas', 'flavor-chat-ia'), 'description' => __('Banco de casas', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-multisite', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'pueblo-vivo',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Banco de Casas Vacías', 'subtitulo' => 'Encuentra tu hogar en el pueblo', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fdf2e9'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'pills', 'items' => [['label' => 'Todas'], ['label' => 'Guardián'], ['label' => 'Alquiler'], ['label' => 'Rehabilitar'], ['label' => 'Compartir']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 3, 'estilo_card' => 'border', 'items' => [['titulo' => 'Caserío Etxeberri', 'descripcion' => '180m² — Guardián — Jardín amplio', 'url' => '#'], ['titulo' => 'Piso Plaza Mayor', 'descripcion' => '75m² — Alquiler — Céntrico', 'url' => '#'], ['titulo' => 'Casa adosada', 'descripcion' => '110m² — Rehabilitar — Con ayudas', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                    'pv_hub' => [
                        'name' => __('Hub Rural', 'flavor-chat-ia'), 'description' => __('Espacio compartido', 'flavor-chat-ia'),
                        'icon' => 'dashicons-desktop', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'pueblo-vivo',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Hub Rural', 'subtitulo' => 'Trabaja, crea y comparte en el pueblo', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#c2703a'], 'settings' => []],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Espacios', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-desktop', 'titulo' => 'Coworking', 'descripcion' => '12 puestos con fibra óptica y monitor'], ['icono' => 'dashicons-hammer', 'titulo' => 'Taller', 'descripcion' => 'Herramientas compartidas para proyectos manuales'], ['icono' => 'dashicons-groups', 'titulo' => 'Sala Polivalente', 'descripcion' => 'Para reuniones, eventos y formaciones']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_text_media', 'data' => ['titulo' => 'Un Espacio para Todos', 'contenido' => 'El Hub Rural es el corazón productivo del pueblo. Un espacio donde teletrabajadores, artesanos y emprendedores locales comparten recursos e ideas. Abierto de lunes a sábado.', 'imagen' => '', 'invertir' => false, 'estilo' => 'simple'], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'En Cifras', 'columnas' => 3, 'items' => [['valor' => '12', 'etiqueta' => 'Puestos coworking'], ['valor' => '8', 'etiqueta' => 'Talleres al mes'], ['valor' => '45', 'etiqueta' => 'Usuarios']]], 'settings' => ['background' => 'gray']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Reserva tu Espacio', 'subtitulo' => 'Ven a trabajar al pueblo', 'texto_cta' => 'Reservar', 'url_cta' => '#reservar', 'color_fondo' => '#c2703a'], 'settings' => []],
                        ],
                    ],
                    'pv_servicios' => [
                        'name' => __('Servicios', 'flavor-chat-ia'), 'description' => __('Servicios itinerantes', 'flavor-chat-ia'),
                        'icon' => 'dashicons-car', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'pueblo-vivo',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Servicios Itinerantes', 'subtitulo' => 'Servicios que vienen al pueblo', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fdf2e9'], 'settings' => []],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Servicios Disponibles', 'subtitulo' => '', 'columnas' => 4, 'items' => [['icono' => 'dashicons-heart', 'titulo' => 'Clínica Móvil', 'descripcion' => 'Médico y enfermería cada miércoles'], ['icono' => 'dashicons-store', 'titulo' => 'Mercado Semanal', 'descripcion' => 'Productos frescos cada sábado en la plaza'], ['icono' => 'dashicons-businessman', 'titulo' => 'Asesoría Legal', 'descripcion' => 'Consultas jurídicas el primer viernes de mes'], ['icono' => 'dashicons-book', 'titulo' => 'Biblioteca', 'descripcion' => 'Bibliobús cada 15 días']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Próximas Visitas', 'columnas' => 2, 'estilo_card' => 'border', 'items' => [['titulo' => 'Clínica móvil — Miércoles 12 Feb', 'descripcion' => '10:00-14:00 — Centro social', 'url' => '#'], ['titulo' => 'Mercado semanal — Sábado 15 Feb', 'descripcion' => '09:00-13:00 — Plaza Mayor', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_accordion', 'data' => ['titulo' => 'FAQ Servicios', 'items' => [['titulo' => '¿Necesito cita previa para la clínica?', 'contenido' => 'No es obligatoria pero se recomienda para evitar esperas.'], ['titulo' => '¿Cómo solicito un servicio nuevo?', 'contenido' => 'A través de una propuesta en la plataforma de gobernanza.'], ['titulo' => '¿Los servicios son gratuitos?', 'contenido' => 'La clínica y la biblioteca son gratuitas. El mercado tiene precios de productor.'], ['titulo' => '¿Puedo ofrecer un servicio itinerante?', 'contenido' => 'Sí, contacta con el equipo del hub rural.']]], 'settings' => ['padding' => 'large']],
                        ],
                    ],
                    'pv_raices' => [
                        'name' => __('Raíces', 'flavor-chat-ia'), 'description' => __('Gamificación de arraigo', 'flavor-chat-ia'),
                        'icon' => 'dashicons-palmtree', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'pueblo-vivo',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Programa Raíces', 'subtitulo' => 'Tu camino de arraigo en el pueblo', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#fdf2e9'], 'settings' => []],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Niveles de Arraigo', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-visibility', 'titulo' => 'Visitante → Explorador', 'descripcion' => 'Conoce el pueblo, participa en eventos, explora el entorno'], ['icono' => 'dashicons-admin-home', 'titulo' => 'Vecino → Arraigado', 'descripcion' => 'Vive en el pueblo, trabaja local, participa en la comunidad'], ['icono' => 'dashicons-shield', 'titulo' => 'Guardián → Sabio', 'descripcion' => 'Cuida el pueblo, mentoriza a nuevos vecinos, lidera proyectos']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'El Programa', 'columnas' => 3, 'estilo' => 'cards', 'items' => [['valor' => 'Vecino', 'etiqueta' => 'Nivel medio'], ['valor' => '28', 'etiqueta' => 'Personas arraigadas'], ['valor' => '8', 'etiqueta' => 'Guardianes']]], 'settings' => ['background' => 'gray']],
                            ['component_id' => 'themacle_text_media', 'data' => ['titulo' => 'Cómo Funciona', 'contenido' => 'El programa Raíces reconoce tu implicación en el pueblo a través de 6 niveles. Cada nivel se consigue participando en la vida comunitaria: eventos, banco de tiempo, gobernanza y cuidado del entorno. Al subir de nivel desbloqueas beneficios y responsabilidades.', 'imagen' => '', 'invertir' => false, 'estilo' => 'simple'], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Empieza tu Camino', 'subtitulo' => 'Cada paso cuenta para echar raíces', 'texto_cta' => 'Ver mi nivel', 'url_cta' => '#perfil', 'color_fondo' => '#c2703a'], 'settings' => []],
                        ],
                    ],
                ],
            ],


            // ─── Ecos Comunitarios ───
            'ecos-comunitarios' => [
                'label' => __('Ecos Comunitarios - Espacios Compartidos', 'flavor-chat-ia'),
                'templates' => [
                    'ec_inicio' => [
                        'name' => __('Inicio', 'flavor-chat-ia'), 'description' => __('Página principal', 'flavor-chat-ia'),
                        'icon' => 'dashicons-building', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'ecos-comunitarios',
                        'layout' => [
                            ['component_id' => 'themacle_hero_fullscreen', 'data' => ['titulo' => 'Espacios para Todos', 'subtitulo' => 'Gestión comunitaria de espacios y recursos compartidos', 'imagen_fondo' => '', 'texto_cta' => 'Reservar espacio', 'url_cta' => '#espacios', 'overlay_color' => '#0891b2', 'overlay_opacidad' => 40], 'settings' => ['margin_bottom' => 'none']],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Qué Compartimos', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-building', 'titulo' => 'Espacios', 'descripcion' => 'Salas, talleres, cocinas y huertos compartidos'], ['icono' => 'dashicons-hammer', 'titulo' => 'Herramientas', 'descripcion' => 'Préstamo de herramientas y maquinaria'], ['icono' => 'dashicons-calendar-alt', 'titulo' => 'Reservas Online', 'descripcion' => 'Sistema de reservas fácil y transparente']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Espacios Destacados', 'columnas' => 3, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Sala de reuniones — Centro Social', 'descripcion' => '20 personas — Proyector — Wifi', 'url' => '#'], ['titulo' => 'Cocina comunitaria', 'descripcion' => '8 personas — Equipada — 2h/reserva', 'url' => '#'], ['titulo' => 'Huerto urbano parcela', 'descripcion' => '25m² — Herramientas incluidas', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'Impacto', 'columnas' => 4, 'items' => [['valor' => '18', 'etiqueta' => 'Espacios'], ['valor' => '240', 'etiqueta' => 'Reservas/mes'], ['valor' => '1.800', 'etiqueta' => 'Horas compartidas'], ['valor' => '12K€', 'etiqueta' => 'Ahorro colectivo']]], 'settings' => ['background' => 'gray']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Reserva un Espacio', 'subtitulo' => 'Comparte recursos, multiplica posibilidades', 'texto_cta' => 'Ver espacios', 'url_cta' => '#espacios', 'color_fondo' => '#0891b2'], 'settings' => []],
                        ],
                    ],
                    'ec_espacios' => [
                        'name' => __('Espacios', 'flavor-chat-ia'), 'description' => __('Catálogo de espacios', 'flavor-chat-ia'),
                        'icon' => 'dashicons-admin-multisite', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'ecos-comunitarios',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Catálogo de Espacios', 'subtitulo' => 'Encuentra el espacio que necesitas', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#ecfeff'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'pills', 'items' => [['label' => 'Todos'], ['label' => 'Salas reunión'], ['label' => 'Talleres'], ['label' => 'Cocinas'], ['label' => 'Huertos'], ['label' => 'Almacenes']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 3, 'estilo_card' => 'shadow', 'items' => [['titulo' => 'Sala multiusos grande', 'descripcion' => '50 pers. — Equipada — Centro social', 'url' => '#'], ['titulo' => 'Taller de carpintería', 'descripcion' => '6 pers. — Herramientas incluidas', 'url' => '#'], ['titulo' => 'Cocina profesional', 'descripcion' => '4 pers. — Totalmente equipada', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                    'ec_recursos' => [
                        'name' => __('Recursos', 'flavor-chat-ia'), 'description' => __('Recursos compartidos', 'flavor-chat-ia'),
                        'icon' => 'dashicons-hammer', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'ecos-comunitarios',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Recursos Compartidos', 'subtitulo' => 'Herramientas y materiales de uso comunitario', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#ecfeff'], 'settings' => []],
                            ['component_id' => 'themacle_filters_bar', 'data' => ['taxonomia' => 'category', 'estilo' => 'pills', 'items' => [['label' => 'Todos'], ['label' => 'Herramientas'], ['label' => 'Maquinaria'], ['label' => 'Electrónica'], ['label' => 'Cocina'], ['label' => 'Deportes']]], 'settings' => ['padding' => 'medium']],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => '', 'columnas' => 4, 'estilo_card' => 'border', 'items' => [['titulo' => 'Taladro percutor', 'descripcion' => 'Herramientas — Disponible', 'url' => '#'], ['titulo' => 'Desbrozadora', 'descripcion' => 'Maquinaria — Reservada hasta 15 Feb', 'url' => '#'], ['titulo' => 'Proyector portátil', 'descripcion' => 'Electrónica — Disponible', 'url' => '#'], ['titulo' => 'Kayak doble', 'descripcion' => 'Deportes — Disponible', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_pagination', 'data' => ['estilo' => 'numbered'], 'settings' => []],
                        ],
                    ],
                    'ec_reservas' => [
                        'name' => __('Reservas', 'flavor-chat-ia'), 'description' => __('Mis reservas', 'flavor-chat-ia'),
                        'icon' => 'dashicons-calendar-alt', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'ecos-comunitarios',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Mis Reservas', 'subtitulo' => 'Gestiona tus reservas de espacios y recursos', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#0891b2'], 'settings' => []],
                            ['component_id' => 'themacle_card_grid', 'data' => ['titulo' => 'Reservas Activas', 'columnas' => 2, 'estilo_card' => 'border', 'items' => [['titulo' => 'Sala reuniones — Martes 12 Feb', 'descripcion' => '10:00-12:00 — Centro social', 'url' => '#'], ['titulo' => 'Taladro percutor — 15-17 Feb', 'descripcion' => 'Recoger en almacén', 'url' => '#']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_text_media', 'data' => ['titulo' => 'Cómo Reservar', 'contenido' => 'Busca el espacio o recurso que necesitas, selecciona fecha y hora, y confirma tu reserva. Recibirás una confirmación por email. Puedes cancelar con 24h de antelación.', 'imagen' => '', 'invertir' => false, 'estilo' => 'simple'], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_accordion', 'data' => ['titulo' => 'FAQ Reservas', 'items' => [['titulo' => '¿Cuánto cuesta reservar?', 'contenido' => 'Los espacios comunitarios son gratuitos para socios. Los recursos tienen un depósito simbólico.'], ['titulo' => '¿Puedo cancelar?', 'contenido' => 'Sí, con al menos 24h de antelación.'], ['titulo' => '¿Hay límite de reservas?', 'contenido' => 'Máximo 2 reservas simultáneas por persona.'], ['titulo' => '¿Qué pasa si rompo algo?', 'contenido' => 'El depósito cubre daños menores. Para daños mayores hay un seguro comunitario.']]], 'settings' => ['padding' => 'large']],
                        ],
                    ],
                    'ec_impacto' => [
                        'name' => __('Impacto', 'flavor-chat-ia'), 'description' => __('Métricas de impacto', 'flavor-chat-ia'),
                        'icon' => 'dashicons-chart-area', 'preview' => '', 'menu_type' => 'centered', 'footer_type' => 'multi-column', 'theme' => 'ecos-comunitarios',
                        'layout' => [
                            ['component_id' => 'themacle_hero_split', 'data' => ['titulo' => 'Impacto Comunitario', 'subtitulo' => 'El poder de compartir recursos', 'imagen' => '', 'texto_cta' => '', 'url_cta' => '', 'color_fondo' => '#ecfeff'], 'settings' => []],
                            ['component_id' => 'themacle_highlights', 'data' => ['titulo' => 'Resultados', 'columnas' => 4, 'estilo' => 'cards', 'items' => [['valor' => '680€', 'etiqueta' => 'Ahorro individual/año'], ['valor' => '40%', 'etiqueta' => 'Reducción residuos'], ['valor' => '180', 'etiqueta' => 'Usuarios activos'], ['valor' => '94%', 'etiqueta' => 'Satisfacción']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_text_media', 'data' => ['titulo' => 'Economía Circular en Acción', 'contenido' => 'Compartir recursos reduce el consumo individual, el desperdicio y la huella ecológica. Cada herramienta compartida son decenas de herramientas que no hace falta fabricar. Cada espacio compartido es un espacio que no hace falta construir.', 'imagen' => '', 'invertir' => false, 'estilo' => 'simple'], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_feature_grid', 'data' => ['titulo' => 'Principios', 'subtitulo' => '', 'columnas' => 3, 'items' => [['icono' => 'dashicons-share', 'titulo' => 'Compartir', 'descripcion' => 'Lo que no usas, alguien lo necesita'], ['icono' => 'dashicons-update', 'titulo' => 'Reutilizar', 'descripcion' => 'Alargar la vida útil de las cosas'], ['icono' => 'dashicons-minus', 'titulo' => 'Reducir', 'descripcion' => 'Menos producción, menos residuos']]], 'settings' => ['padding' => 'large']],
                            ['component_id' => 'themacle_cta_banner', 'data' => ['titulo' => 'Únete al Movimiento', 'subtitulo' => 'Comparte recursos y multiplica posibilidades', 'texto_cta' => 'Registrarme', 'url_cta' => '#registro', 'color_fondo' => '#0891b2'], 'settings' => []],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Renderiza el contenido de la página en el frontend
     */
    public function render_page_content($content) {
        // Solo aplicar en páginas que usan el page builder
        if (!is_singular($this->post_types)) {
            return $content;
        }

        global $post;

        // Obtener layout guardado
        $layout = get_post_meta($post->ID, '_flavor_page_layout', true);

        if (empty($layout)) {
            return $content;
        }

        // Decodificar si es JSON
        if (is_string($layout)) {
            $layout = json_decode($layout, true);
        }

        if (empty($layout) || !is_array($layout)) {
            return $content;
        }

        // Verificar si hay componentes/bloques reales en el layout
        // Si todas las secciones están vacías, mostrar el contenido original
        $has_real_content = false;
        foreach ($layout as $section) {
            // Verificar si tiene blocks o component_id
            if (!empty($section['blocks'])) {
                $has_real_content = true;
                break;
            }
            if (!empty($section['component_id'])) {
                $has_real_content = true;
                break;
            }
        }

        // Si no hay contenido real en el Page Builder, devolver el contenido original
        // Esto permite que shortcodes como [flavor_landing] funcionen
        if (!$has_real_content) {
            return $content;
        }

        // Renderizar componentes
        ob_start();
        $renderer = new Flavor_Component_Renderer();
        $renderer->render_layout($layout);
        $rendered_content = ob_get_clean();

        // Reemplazar o añadir al contenido
        return $rendered_content;
    }

    /**
     * Carga plantilla full-width para flavor_landing
     */
    public function load_landing_template($template) {
        if (get_post_type() === 'flavor_landing') {
            $plugin_template = FLAVOR_WEB_BUILDER_PATH . 'templates/single-flavor_landing.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * AJAX: Renderizar preview de un solo componente
     */
    public function ajax_preview_component() {
        check_ajax_referer('flavor_page_builder', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $component_id = isset($_POST['component_id']) ? sanitize_text_field($_POST['component_id']) : '';
        $component_data_raw = isset($_POST['component_data']) ? $_POST['component_data'] : '{}';
        $component_settings_raw = isset($_POST['component_settings']) ? $_POST['component_settings'] : '{}';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($component_id)) {
            wp_send_json_error(['message' => __('Falta el ID del componente', 'flavor-chat-ia')]);
        }

        $component_data = json_decode(stripslashes($component_data_raw), true);
        if (!is_array($component_data)) {
            $component_data = [];
        }

        $component_settings = json_decode(stripslashes($component_settings_raw), true);
        if (!is_array($component_settings)) {
            $component_settings = [];
        }

        // Construir layout con un solo componente
        $layout_item = [
            'component_id' => $component_id,
            'data' => $component_data,
            'settings' => $component_settings,
        ];

        // Renderizar
        ob_start();

        // Cargar estilos del tema si hay post_id
        if ($post_id && class_exists('Flavor_Theme_Manager')) {
            $page_theme = get_post_meta($post_id, '_flavor_page_theme', true);
            if (!empty($page_theme)) {
                $theme_manager = Flavor_Theme_Manager::get_instance();
                $theme = $theme_manager->get_theme($page_theme);
                if ($theme && !empty($theme['variables'])) {
                    echo '<style>:root{';
                    foreach ($theme['variables'] as $var_name => $var_value) {
                        echo esc_attr($var_name) . ':' . esc_attr($var_value) . ';';
                    }
                    echo '}</style>';
                }
            }
        }

        $renderer = new Flavor_Component_Renderer();
        $renderer->render_layout([$layout_item]);
        $rendered_html = ob_get_clean();

        wp_send_json_success(['html' => $rendered_html]);
    }

    /**
     * AJAX: Guardar preview temporal y devolver URL
     */
    public function ajax_save_preview() {
        check_ajax_referer('flavor_page_builder', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $layout = isset($_POST['layout']) ? $_POST['layout'] : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($layout)) {
            wp_send_json_error(['message' => __('No hay layout para previsualizar', 'flavor-chat-ia')]);
        }

        // Generar ID único para el preview
        $preview_id = 'preview_' . uniqid('', true);

        // Guardar en transient (expira en 1 hora)
        set_transient('flavor_preview_' . $preview_id, [
            'layout' => $layout,
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'created' => time(),
        ], HOUR_IN_SECONDS);

        // Generar URL de preview
        $preview_url = add_query_arg([
            'flavor_preview' => '1',
            'preview_id' => $preview_id,
            '_wpnonce' => wp_create_nonce('flavor_preview_' . $preview_id),
        ], home_url('/'));

        wp_send_json_success([
            'preview_url' => $preview_url,
            'preview_id' => $preview_id,
        ]);
    }

    /**
     * Manejar solicitud de vista previa
     */
    public function handle_preview_request() {
        if (!isset($_GET['flavor_preview']) || $_GET['flavor_preview'] !== '1') {
            return;
        }

        $preview_id = isset($_GET['preview_id']) ? sanitize_text_field($_GET['preview_id']) : '';

        if (empty($preview_id)) {
            wp_die(__('Vista previa no válida', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 400]);
        }

        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'flavor_preview_' . $preview_id)) {
            wp_die(__('Enlace de vista previa expirado o no válido', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 403]);
        }

        // Obtener datos del preview
        $preview_data = get_transient('flavor_preview_' . $preview_id);

        if (!$preview_data) {
            wp_die(__('Vista previa expirada. Por favor, genera una nueva.', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 410]);
        }

        // Decodificar layout
        $layout = json_decode($preview_data['layout'], true);

        if (empty($layout) || !is_array($layout)) {
            wp_die(__('Layout de vista previa no válido', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 400]);
        }

        // Renderizar la vista previa
        $this->render_preview_page($layout, $preview_data);
        exit;
    }

    /**
     * Renderizar página de vista previa
     */
    private function render_preview_page($layout, $preview_data) {
        // Registrar y cargar scripts/estilos del frontend
        $this->enqueue_frontend_scripts();

        // Cargar dashicons para los iconos
        wp_enqueue_style('dashicons');

        // Obtener título
        $post_id = $preview_data['post_id'] ?? 0;
        $title = $post_id ? get_the_title($post_id) : __('Vista Previa', 'flavor-chat-ia');

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <title><?php echo esc_html($title); ?> - <?php _e('Vista Previa', 'flavor-chat-ia'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                }
                .flavor-preview-bar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 10px 20px;
                    z-index: 99999;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 14px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                }
                .flavor-preview-bar-title {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .flavor-preview-bar-title span.dashicons {
                    font-size: 20px;
                    width: 20px;
                    height: 20px;
                }
                .flavor-preview-bar-actions {
                    display: flex;
                    gap: 10px;
                }
                .flavor-preview-bar-actions a,
                .flavor-preview-bar-actions button {
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    font-size: 13px;
                    transition: background 0.2s;
                }
                .flavor-preview-bar-actions a:hover,
                .flavor-preview-bar-actions button:hover {
                    background: rgba(255,255,255,0.3);
                }
                .flavor-preview-content {
                    margin-top: 50px;
                }
                /* Responsive preview controls */
                .flavor-preview-device-buttons {
                    display: flex;
                    gap: 5px;
                    background: rgba(0,0,0,0.2);
                    padding: 3px;
                    border-radius: 4px;
                }
                .flavor-preview-device-buttons button {
                    background: transparent;
                    padding: 5px 10px;
                }
                .flavor-preview-device-buttons button.active {
                    background: rgba(255,255,255,0.3);
                }
            </style>
        </head>
        <body class="flavor-preview-body">
            <div class="flavor-preview-bar">
                <div class="flavor-preview-bar-title">
                    <span class="dashicons dashicons-visibility"></span>
                    <strong><?php _e('Vista Previa', 'flavor-chat-ia'); ?></strong>
                    <span>- <?php echo esc_html($title); ?></span>
                </div>
                <div class="flavor-preview-bar-actions">
                    <div class="flavor-preview-device-buttons">
                        <button type="button" onclick="setPreviewWidth('100%')" class="active" title="Desktop">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" onclick="setPreviewWidth('768px')" title="Tablet">
                            <span class="dashicons dashicons-tablet"></span>
                        </button>
                        <button type="button" onclick="setPreviewWidth('375px')" title="Mobile">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>
                    <button type="button" onclick="window.close()">
                        <span class="dashicons dashicons-no"></span>
                        <?php _e('Cerrar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <div class="flavor-preview-content" id="flavor-preview-content">
                <?php
                // Renderizar el layout
                if (class_exists('Flavor_Component_Renderer')) {
                    $renderer = new Flavor_Component_Renderer();
                    $renderer->render_layout($layout);
                } else {
                    echo '<div style="padding: 40px; text-align: center; color: #666;">';
                    echo '<p>' . __('No se pudo cargar el renderizador de componentes.', 'flavor-chat-ia') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <script>
                function setPreviewWidth(width) {
                    const content = document.getElementById('flavor-preview-content');
                    const buttons = document.querySelectorAll('.flavor-preview-device-buttons button');

                    buttons.forEach(btn => btn.classList.remove('active'));
                    event.target.closest('button').classList.add('active');

                    if (width === '100%') {
                        content.style.maxWidth = '';
                        content.style.margin = '';
                        content.style.boxShadow = '';
                    } else {
                        content.style.maxWidth = width;
                        content.style.margin = '20px auto';
                        content.style.boxShadow = '0 0 20px rgba(0,0,0,0.1)';
                    }
                }
            </script>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Obtiene los sectores que corresponden a temas web (Themacle)
     *
     * @return array IDs de sectores web
     */
    public function get_web_theme_sectors() {
        return ['zunbeltz', 'naarq', 'campi', 'denendako', 'escena-familiar', 'grupos-consumo', 'comunidad-viva', 'jantoki', 'mercado-espiral', 'spiral-bank', 'red-cuidados', 'academia-espiral', 'democracia-universal', 'flujo', 'kulturaka', 'pueblo-vivo', 'ecos-comunitarios'];
    }

    /**
     * AJAX: Importar todas las plantillas de un tema web
     */
    public function ajax_import_theme_templates() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $sector_id = sanitize_text_field($_POST['sector_id'] ?? '');

        if (!$sector_id) {
            wp_send_json_error(['message' => __('ID de sector requerido', 'flavor-chat-ia')]);
        }

        $web_sectors = $this->get_web_theme_sectors();
        if (!in_array($sector_id, $web_sectors, true)) {
            wp_send_json_error(['message' => __('Sector no válido', 'flavor-chat-ia')]);
        }

        $library = $this->get_template_library();

        if (!isset($library[$sector_id])) {
            wp_send_json_error(['message' => __('Sector no encontrado', 'flavor-chat-ia')]);
        }

        $sector = $library[$sector_id];
        $created_pages = [];

        foreach ($sector['templates'] as $template_id => $template) {
            $post_id = wp_insert_post([
                'post_title'  => $template['name'],
                'post_type'   => 'flavor_landing',
                'post_status' => 'draft',
            ]);

            if (is_wp_error($post_id)) {
                continue;
            }

            // Guardar layout
            update_post_meta($post_id, '_flavor_page_layout', $template['layout']);

            // Guardar metadatos del tema
            if (!empty($template['theme'])) {
                update_post_meta($post_id, '_flavor_page_theme', $template['theme']);
            }
            if (!empty($template['menu_type'])) {
                update_post_meta($post_id, '_flavor_page_menu_type', $template['menu_type']);
            }
            if (!empty($template['footer_type'])) {
                update_post_meta($post_id, '_flavor_page_footer_type', $template['footer_type']);
            }

            $created_pages[] = [
                'id'    => $post_id,
                'title' => $template['name'],
                'edit'  => get_edit_post_link($post_id, 'raw'),
            ];
        }

        if (empty($created_pages)) {
            wp_send_json_error(['message' => __('No se pudo crear ninguna página', 'flavor-chat-ia')]);
        }

        // Activar el tema correspondiente
        if (!empty($sector_id)) {
            $theme_id = str_replace('-', '-', $sector_id);
            $theme_manager = Flavor_Theme_Manager::get_instance();
            $themes = $theme_manager->get_themes();
            if (isset($themes[$theme_id])) {
                update_option('flavor_active_theme', $theme_id);
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Se han creado %d páginas para "%s"', 'flavor-chat-ia'),
                count($created_pages),
                $sector['label']
            ),
            'pages' => $created_pages,
        ]);
    }
}

// Inicializar
Flavor_Page_Builder::get_instance();

// AJAX handlers
add_action('wp_ajax_flavor_get_component_fields', [Flavor_Page_Builder::get_instance(), 'ajax_get_component_fields']);

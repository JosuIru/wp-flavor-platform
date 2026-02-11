<?php
/**
 * Página de configuración del restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Restaurant_Settings {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Obtener instancia
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
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_restaurant_settings', [$this, 'save_settings_ajax']);
        add_action('wp_ajax_get_available_cpts', [$this, 'get_available_cpts_ajax']);
    }

    /**
     * Agregar página al menú
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-platform',
            __('Configuración de Restaurante', 'flavor-restaurant-ordering'),
            __('Restaurante', 'flavor-restaurant-ordering'),
            'manage_options',
            'flavor-restaurant-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'flavor-platform_page_flavor-restaurant-settings') {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-restaurant-settings',
            FLAVOR_RESTAURANT_URL . "assets/css/restaurant-settings{$sufijo_asset}.css",
            [],
            FLAVOR_RESTAURANT_VERSION
        );

        wp_enqueue_script(
            'flavor-restaurant-settings',
            FLAVOR_RESTAURANT_URL . "assets/js/restaurant-settings{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_RESTAURANT_VERSION,
            true
        );

        wp_localize_script('flavor-restaurant-settings', 'flavorRestaurantSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_restaurant_settings'),
            'strings' => [
                'save_success' => __('Configuración guardada exitosamente', 'flavor-restaurant-ordering'),
                'save_error' => __('Error al guardar la configuración', 'flavor-restaurant-ordering'),
                'save_error_connection' => __('Error de conexión al guardar', 'flavor-restaurant-ordering'),
            ],
        ]);
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $settings = $restaurant_manager->get_settings();
        $menu_cpts = $settings['menu_cpts'];

        ?>
        <div class="wrap flavor-restaurant-settings">
            <h1>
                <span class="dashicons dashicons-food" style="font-size: 32px; width: 32px; height: 32px;"></span>
                <?php esc_html_e('Configuración de Restaurante', 'flavor-restaurant-ordering'); ?>
            </h1>

            <div class="flavor-restaurant-container">
                <!-- Sección: CPTs del Menú -->
                <div class="flavor-card">
                    <h2>
                        <span class="dashicons dashicons-menu"></span>
                        <?php esc_html_e('Configurar Menú', 'flavor-restaurant-ordering'); ?>
                    </h2>
                    <p class="description">
                        <?php esc_html_e('Selecciona qué Custom Post Types de tu WordPress formarán parte del menú del restaurante.', 'flavor-restaurant-ordering'); ?>
                        <?php esc_html_e('Organízalos en categorías para que aparezcan correctamente en la app.', 'flavor-restaurant-ordering'); ?>
                    </p>

                    <div class="menu-categories">
                        <?php
                        $categorias_menu = [
                            'dishes' => [
                                'titulo' => __('Platos', 'flavor-restaurant-ordering'),
                                'icono' => 'dashicons-carrot',
                                'color' => '#FF5722',
                                'descripcion' => __('Platos principales, entradas, ensaladas, etc.', 'flavor-restaurant-ordering'),
                            ],
                            'drinks' => [
                                'titulo' => __('Bebidas', 'flavor-restaurant-ordering'),
                                'icono' => 'dashicons-coffee',
                                'color' => '#2196F3',
                                'descripcion' => __('Bebidas, refrescos, vinos, cervezas, etc.', 'flavor-restaurant-ordering'),
                            ],
                            'desserts' => [
                                'titulo' => __('Postres', 'flavor-restaurant-ordering'),
                                'icono' => 'dashicons-admin-home',
                                'color' => '#E91E63',
                                'descripcion' => __('Postres, dulces, cafés, etc.', 'flavor-restaurant-ordering'),
                            ],
                        ];

                        foreach ($categorias_menu as $categoria_slug => $categoria_info):
                            $seleccionados = $menu_cpts[$categoria_slug] ?? [];
                        ?>
                        <div class="menu-category" data-category="<?php echo esc_attr($categoria_slug); ?>">
                            <h3>
                                <span class="dashicons <?php echo esc_attr($categoria_info['icono']); ?>" style="color: <?php echo esc_attr($categoria_info['color']); ?>;"></span>
                                <?php echo esc_html($categoria_info['titulo']); ?>
                            </h3>
                            <p class="description"><?php echo esc_html($categoria_info['descripcion']); ?></p>

                            <div class="cpt-selector">
                                <div class="cpt-selector-available">
                                    <label class="cpt-selector-label"><?php esc_html_e('Disponibles', 'flavor-restaurant-ordering'); ?></label>
                                    <select class="cpt-select" multiple size="8">
                                        <?php $this->render_cpt_options($seleccionados); ?>
                                    </select>
                                    <div class="cpt-selector-actions">
                                        <button type="button" class="button add-selected-cpts" title="<?php esc_attr_e('Agregar seleccionados', 'flavor-restaurant-ordering'); ?>">
                                            <span class="dashicons dashicons-arrow-right-alt"></span>
                                            <?php esc_html_e('Agregar', 'flavor-restaurant-ordering'); ?>
                                        </button>
                                    </div>
                                    <p class="description cpt-selector-hint"><?php esc_html_e('Selecciona uno o varios (Ctrl+clic) y pulsa Agregar.', 'flavor-restaurant-ordering'); ?></p>
                                </div>
                                <div class="cpt-selector-selected">
                                    <label class="cpt-selector-label"><?php esc_html_e('Seleccionados', 'flavor-restaurant-ordering'); ?></label>
                                    <div class="selected-cpts" data-category="<?php echo esc_attr($categoria_slug); ?>">
                                        <?php $this->render_selected_cpts($seleccionados, $categoria_slug); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sección: Configuración General -->
                <div class="flavor-card">
                    <h2>
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Configuración General', 'flavor-restaurant-ordering'); ?>
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="table_prefix">Prefijo de Mesas</label>
                            </th>
                            <td>
                                <input type="text"
                                       id="table_prefix"
                                       name="table_prefix"
                                       value="<?php echo esc_attr($settings['table_prefix']); ?>"
                                       class="regular-text">
                                <p class="description">
                                    Prefijo para los números de mesa (ej: MESA-001)
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="currency">Moneda</label>
                            </th>
                            <td>
                                <select id="currency" name="currency">
                                    <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>Euro (EUR)</option>
                                    <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>Dólar (USD)</option>
                                    <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>Libra (GBP)</option>
                                    <option value="MXN" <?php selected($settings['currency'], 'MXN'); ?>>Peso Mexicano (MXN)</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="currency_symbol">Símbolo de Moneda</label>
                            </th>
                            <td>
                                <input type="text"
                                       id="currency_symbol"
                                       name="currency_symbol"
                                       value="<?php echo esc_attr($settings['currency_symbol']); ?>"
                                       class="small-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="tax_rate">Tasa de Impuesto (%)</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="tax_rate"
                                       name="tax_rate"
                                       value="<?php echo esc_attr($settings['tax_rate']); ?>"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       class="small-text">
                                <p class="description">
                                    Porcentaje de impuesto (IVA) a aplicar a los pedidos
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Opciones</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox"
                                               name="enable_table_qr"
                                               value="1"
                                               <?php checked($settings['enable_table_qr'], true); ?>>
                                        Habilitar códigos QR para mesas
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox"
                                               name="enable_notifications"
                                               value="1"
                                               <?php checked($settings['enable_notifications'], true); ?>>
                                        Habilitar notificaciones de pedidos
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sección: Estados de Pedido -->
                <div class="flavor-card">
                    <h2>
                        <span class="dashicons dashicons-flag"></span>
                        Estados de Pedido
                    </h2>
                    <p class="description">
                        Personaliza los estados que pueden tener los pedidos en tu restaurante.
                    </p>

                    <table class="widefat order-statuses-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Etiqueta</th>
                                <th width="100">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="order-statuses-list">
                            <?php foreach ($settings['order_statuses'] as $status => $label): ?>
                            <tr data-status="<?php echo esc_attr($status); ?>">
                                <td><code><?php echo esc_html($status); ?></code></td>
                                <td>
                                    <input type="text"
                                           name="order_statuses[<?php echo esc_attr($status); ?>]"
                                           value="<?php echo esc_attr($label); ?>"
                                           class="regular-text">
                                </td>
                                <td>
                                    <?php if (!in_array($status, ['pending', 'completed', 'cancelled'])): ?>
                                    <button type="button" class="button button-small remove-status">Eliminar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p>
                        <button type="button" class="button" id="add-order-status">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Agregar Estado', 'flavor-restaurant-ordering'); ?>
                        </button>
                    </p>
                </div>

                <!-- Sección: Información de Endpoints -->
                <div class="flavor-card flavor-info">
                    <h2>
                        <span class="dashicons dashicons-rest-api"></span>
                        Endpoints de la API
                    </h2>

                    <p>Usa estos endpoints en tu app para interactuar con el sistema:</p>

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Endpoint</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>GET /wp-json/restaurant/v1/menu</code></td>
                                <td>Obtener menú completo</td>
                            </tr>
                            <tr>
                                <td><code>GET /wp-json/restaurant/v1/tables</code></td>
                                <td>Listar mesas</td>
                            </tr>
                            <tr>
                                <td><code>POST /wp-json/restaurant/v1/orders</code></td>
                                <td>Crear nuevo pedido</td>
                            </tr>
                            <tr>
                                <td><code>GET /wp-json/restaurant/v1/orders</code></td>
                                <td>Listar pedidos</td>
                            </tr>
                            <tr>
                                <td><code>PUT /wp-json/restaurant/v1/orders/{id}/status</code></td>
                                <td>Actualizar estado de pedido</td>
                            </tr>
                        </tbody>
                    </table>

                    <p>
                        <a href="<?php echo rest_url('restaurant/v1/menu'); ?>" target="_blank" class="button">
                            <span class="dashicons dashicons-external"></span>
                            Probar API de Menú
                        </a>
                    </p>
                </div>
            </div>

            <!-- Botón Guardar Flotante -->
            <div class="flavor-floating-save">
                <button type="button" id="save-restaurant-settings" class="button button-primary button-large">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Guardar Cambios', 'flavor-restaurant-ordering'); ?>
                </button>
            </div>

            <!-- Loading overlay -->
            <div class="flavor-loading-overlay" style="display: none;">
                <div class="flavor-spinner"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar opciones de CPTs disponibles
     */
    private function render_cpt_options($selected = []) {
        $post_types = get_post_types([
            'public' => true,
            'show_ui' => true,
        ], 'objects');

        $exclude = ['attachment', 'wp_block'];

        foreach ($post_types as $post_type) {
            if (in_array($post_type->name, $exclude)) {
                continue;
            }

            if (in_array($post_type->name, $selected)) {
                continue; // Ya está seleccionado
            }

            $count = wp_count_posts($post_type->name)->publish ?? 0;

            printf(
                '<option value="%s">%s (%d items)</option>',
                esc_attr($post_type->name),
                esc_html($post_type->labels->name),
                $count
            );
        }
    }

    /**
     * Renderizar CPTs seleccionados
     */
    private function render_selected_cpts($cpts, $category) {
        if (empty($cpts)) {
            echo '<p class="no-cpts">No hay CPTs seleccionados para esta categoría.</p>';
            return;
        }

        foreach ($cpts as $cpt_slug) {
            $post_type = get_post_type_object($cpt_slug);
            if (!$post_type) {
                continue;
            }

            $count = wp_count_posts($cpt_slug)->publish ?? 0;

            printf(
                '<div class="selected-cpt" data-cpt="%s">
                    <span class="cpt-name">%s</span>
                    <span class="cpt-count">%d items</span>
                    <button type="button" class="remove-cpt" title="Eliminar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <input type="hidden" name="menu_cpts[%s][]" value="%s">
                </div>',
                esc_attr($cpt_slug),
                esc_html($post_type->labels->name),
                $count,
                esc_attr($category),
                esc_attr($cpt_slug)
            );
        }
    }

    /**
     * Guardar configuración via AJAX
     */
    public function save_settings_ajax() {
        check_ajax_referer('flavor_restaurant_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
        }

        $settings = [
            'menu_cpts' => [
                'dishes' => isset($_POST['menu_cpts']['dishes']) ? array_map('sanitize_text_field', $_POST['menu_cpts']['dishes']) : [],
                'drinks' => isset($_POST['menu_cpts']['drinks']) ? array_map('sanitize_text_field', $_POST['menu_cpts']['drinks']) : [],
                'desserts' => isset($_POST['menu_cpts']['desserts']) ? array_map('sanitize_text_field', $_POST['menu_cpts']['desserts']) : [],
            ],
            'table_prefix' => sanitize_text_field($_POST['table_prefix'] ?? 'MESA'),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'EUR'),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol'] ?? '€'),
            'tax_rate' => floatval($_POST['tax_rate'] ?? 10),
            'enable_table_qr' => isset($_POST['enable_table_qr']),
            'enable_notifications' => isset($_POST['enable_notifications']),
            'order_statuses' => [],
        ];

        // Procesar estados de pedido
        if (isset($_POST['order_statuses']) && is_array($_POST['order_statuses'])) {
            foreach ($_POST['order_statuses'] as $status => $label) {
                $settings['order_statuses'][sanitize_key($status)] = sanitize_text_field($label);
            }
        }

        // Asegurar estados mínimos requeridos
        $required_statuses = [
            'pending' => __('Pendiente', 'flavor-restaurant-ordering'),
            'completed' => __('Completado', 'flavor-restaurant-ordering'),
            'cancelled' => __('Cancelado', 'flavor-restaurant-ordering'),
        ];

        $settings['order_statuses'] = array_merge($required_statuses, $settings['order_statuses']);

        update_option('flavor_restaurant_settings', $settings);

        wp_send_json_success([
            'message' => __('Configuración guardada exitosamente', 'flavor-restaurant-ordering'),
            'settings' => $settings,
        ]);
    }

    /**
     * Obtener CPTs disponibles via AJAX
     */
    public function get_available_cpts_ajax() {
        check_ajax_referer('flavor_restaurant_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
        }

        $post_types = get_post_types([
            'public' => true,
            'show_ui' => true,
        ], 'objects');

        $exclude = ['attachment', 'wp_block'];
        $cpts = [];

        foreach ($post_types as $post_type) {
            if (in_array($post_type->name, $exclude)) {
                continue;
            }

            $count = wp_count_posts($post_type->name)->publish ?? 0;

            $cpts[] = [
                'slug' => $post_type->name,
                'name' => $post_type->labels->name,
                'singular_name' => $post_type->labels->singular_name,
                'count' => $count,
            ];
        }

        wp_send_json_success(['cpts' => $cpts]);
    }
}

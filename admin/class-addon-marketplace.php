<?php
/**
 * Marketplace Integrado de Addons
 *
 * Permite explorar, instalar y gestionar addons desde el panel de administración
 * Integrado con el servidor de addons de Flavor Platform
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
 * Clase para el marketplace de addons
 *
 * @since 3.0.0
 */
class Flavor_Addon_Marketplace {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_Marketplace
     */
    private static $instancia = null;

    /**
     * URL del marketplace
     *
     * @var string
     */
    private $marketplace_url = 'https://api.gailu.net/v1/marketplace';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_Marketplace
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
        $this->configure_marketplace_url();
        $this->init_hooks();
    }

    /**
     * Ajusta la URL del marketplace según la configuración de licencias.
     *
     * @return void
     */
    private function configure_marketplace_url() {
        $license_server = get_option('flavor_license_server_url');

        if (defined('FLAVOR_LICENSE_SERVER_URL') && FLAVOR_LICENSE_SERVER_URL) {
            $license_server = FLAVOR_LICENSE_SERVER_URL;
        }

        if (empty($license_server)) {
            return;
        }

        $license_server = trailingslashit($license_server);

        if (strpos($license_server, '/fls/v1/') !== false) {
            $this->marketplace_url = untrailingslashit($license_server);
        }
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager

        // Registrar assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_marketplace_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_browse_marketplace', [$this, 'ajax_browse_marketplace']);
        add_action('wp_ajax_flavor_get_addon_details', [$this, 'ajax_get_addon_details']);
        add_action('wp_ajax_flavor_install_addon', [$this, 'ajax_install_addon']);
        add_action('wp_ajax_flavor_search_marketplace', [$this, 'ajax_search_marketplace']);
    }

    /**
     * Agrega página del marketplace
     *
     * @return void
     */
    public function add_marketplace_page() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' <span class="dashicons dashicons-star-filled" style="font-size: 14px; color: #f0c33c;"></span>',
            'manage_options',
            'flavor-marketplace',
            [$this, 'render_marketplace_page'],
            15
        );
    }

    /**
     * Registra assets del marketplace
     *
     * @param string $hook_suffix Sufijo del hook
     * @return void
     */
    public function enqueue_marketplace_assets($hook_suffix) {
        if ($hook_suffix !== 'flavor-platform_page_flavor-marketplace') {
            return;
        }

        // Estilos
        $css = "
            .flavor-marketplace-wrapper {
                padding: 20px 0;
            }
            .flavor-marketplace-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 40px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .flavor-marketplace-header h1 {
                color: #fff;
                margin: 0 0 10px 0;
            }
            .flavor-marketplace-search {
                margin-top: 20px;
                max-width: 600px;
            }
            .flavor-marketplace-search input {
                width: 100%;
                padding: 12px 20px;
                border: none;
                border-radius: 4px;
                font-size: 16px;
            }
            .flavor-marketplace-filters {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            .flavor-marketplace-filter {
                padding: 8px 16px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .flavor-marketplace-filter:hover,
            .flavor-marketplace-filter.active {
                background: #2271b1;
                color: #fff;
                border-color: #2271b1;
            }
            .flavor-marketplace-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .flavor-marketplace-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                overflow: hidden;
                transition: transform 0.2s, box-shadow 0.2s;
                cursor: pointer;
            }
            .flavor-marketplace-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .flavor-marketplace-card-banner {
                width: 100%;
                height: 150px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 48px;
            }
            .flavor-marketplace-card-body {
                padding: 20px;
            }
            .flavor-marketplace-card h3 {
                margin: 0 0 10px 0;
                font-size: 18px;
            }
            .flavor-marketplace-card-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
                font-size: 13px;
                color: #646970;
            }
            .flavor-marketplace-card-price {
                font-weight: 600;
                color: #2271b1;
                font-size: 16px;
            }
            .flavor-marketplace-card-price.free {
                color: #00a32a;
            }
            .flavor-marketplace-card-description {
                color: #646970;
                line-height: 1.6;
                margin-bottom: 15px;
            }
            .flavor-marketplace-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .flavor-marketplace-rating {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .flavor-marketplace-stars {
                color: #f0c33c;
            }
            .flavor-marketplace-install-btn {
                padding: 8px 16px;
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .flavor-marketplace-install-btn:hover {
                background: #135e96;
            }
            .flavor-marketplace-install-btn:disabled {
                background: #c3c4c7;
                cursor: not-allowed;
            }
            .flavor-marketplace-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .flavor-marketplace-badge.premium {
                background: #f0c33c;
                color: #000;
            }
            .flavor-marketplace-badge.popular {
                background: #00a32a;
                color: #fff;
            }
            .flavor-marketplace-badge.new {
                background: #2271b1;
                color: #fff;
            }
            .flavor-marketplace-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                z-index: 100000;
                align-items: center;
                justify-content: center;
            }
            .flavor-marketplace-modal.active {
                display: flex;
            }
            .flavor-marketplace-modal-content {
                background: #fff;
                max-width: 800px;
                max-height: 90vh;
                overflow-y: auto;
                border-radius: 8px;
                position: relative;
            }
            .flavor-marketplace-modal-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(0,0,0,0.1);
                border: none;
                border-radius: 50%;
                width: 32px;
                height: 32px;
                cursor: pointer;
                font-size: 20px;
                line-height: 1;
            }
            .flavor-marketplace-modal-banner {
                width: 100%;
                height: 200px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .flavor-marketplace-modal-body {
                padding: 30px;
            }
            .flavor-marketplace-loading {
                text-align: center;
                padding: 40px;
            }
            .flavor-marketplace-empty {
                text-align: center;
                padding: 60px 20px;
                color: #646970;
            }
        ";

        wp_add_inline_style('wp-admin', $css);

        // JavaScript
        wp_enqueue_script('flavor-marketplace', '', ['jquery'], FLAVOR_PLATFORM_VERSION, true);

        $js = "
            (function($) {
                'use strict';

                window.FlavorMarketplace = {
                    currentFilter: 'all',
                    searchQuery: '',

                    init: function() {
                        this.bindEvents();
                        this.loadAddons();
                    },

                    bindEvents: function() {
                        $(document).on('click', '.flavor-marketplace-filter', function() {
                            $('.flavor-marketplace-filter').removeClass('active');
                            $(this).addClass('active');
                            FlavorMarketplace.currentFilter = $(this).data('filter');
                            FlavorMarketplace.loadAddons();
                        });

                        $(document).on('input', '#flavor-marketplace-search', function() {
                            clearTimeout(FlavorMarketplace.searchTimeout);
                            FlavorMarketplace.searchTimeout = setTimeout(function() {
                                FlavorMarketplace.searchQuery = $('#flavor-marketplace-search').val();
                                FlavorMarketplace.loadAddons();
                            }, 500);
                        });

                        $(document).on('click', '.flavor-marketplace-card', function() {
                            var slug = $(this).data('slug');
                            FlavorMarketplace.showAddonDetails(slug);
                        });

                        $(document).on('click', '.flavor-marketplace-install-btn', function(e) {
                            e.stopPropagation();
                            var slug = $(this).data('slug');
                            FlavorMarketplace.installAddon(slug);
                        });

                        $(document).on('click', '.flavor-marketplace-modal-close, .flavor-marketplace-modal', function(e) {
                            if (e.target === this) {
                                $('.flavor-marketplace-modal').removeClass('active');
                            }
                        });
                    },

                    loadAddons: function() {
                        $('#flavor-marketplace-grid').html('<div class=\"flavor-marketplace-loading\"><span class=\"spinner is-active\"></span><p>Cargando addons...</p></div>');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_browse_marketplace',
                                nonce: '" . wp_create_nonce('flavor_marketplace_nonce') . "',
                                filter: this.currentFilter,
                                search: this.searchQuery
                            },
                            success: function(response) {
                                if (response.success) {
                                    FlavorMarketplace.renderAddons(response.data.addons);
                                } else {
                                    $('#flavor-marketplace-grid').html('<div class=\"flavor-marketplace-empty\"><p>Error cargando addons</p></div>');
                                }
                            },
                            error: function() {
                                $('#flavor-marketplace-grid').html('<div class=\"flavor-marketplace-empty\"><p>Error de conexión</p></div>');
                            }
                        });
                    },

                    renderAddons: function(addons) {
                        if (addons.length === 0) {
                            $('#flavor-marketplace-grid').html('<div class=\"flavor-marketplace-empty\"><p>No se encontraron addons</p></div>');
                            return;
                        }

                        var html = '';
                        $.each(addons, function(index, addon) {
                            html += FlavorMarketplace.renderAddonCard(addon);
                        });

                        $('#flavor-marketplace-grid').html(html);
                    },

                    renderAddonCard: function(addon) {
                        var badge = '';
                        if (addon.is_premium) {
                            badge = '<span class=\"flavor-marketplace-badge premium\">Premium</span>';
                        } else if (addon.is_popular) {
                            badge = '<span class=\"flavor-marketplace-badge popular\">Popular</span>';
                        } else if (addon.is_new) {
                            badge = '<span class=\"flavor-marketplace-badge new\">Nuevo</span>';
                        }

                        var price = addon.price === 0
                            ? '<span class=\"flavor-marketplace-card-price free\">Gratis</span>'
                            : '<span class=\"flavor-marketplace-card-price\">$' + addon.price + '</span>';

                        var stars = '';
                        for (var i = 0; i < 5; i++) {
                            stars += i < addon.rating ? '★' : '☆';
                        }

                        return '<div class=\"flavor-marketplace-card\" data-slug=\"' + addon.slug + '\">' +
                            '<div class=\"flavor-marketplace-card-banner\">' +
                            '<span class=\"dashicons dashicons-' + (addon.icon || 'admin-plugins') + '\"></span>' +
                            '</div>' +
                            '<div class=\"flavor-marketplace-card-body\">' +
                            '<div class=\"flavor-marketplace-card-meta\">' +
                            badge + price +
                            '</div>' +
                            '<h3>' + addon.name + '</h3>' +
                            '<p class=\"flavor-marketplace-card-description\">' + addon.description + '</p>' +
                            '<div class=\"flavor-marketplace-card-footer\">' +
                            '<div class=\"flavor-marketplace-rating\">' +
                            '<span class=\"flavor-marketplace-stars\">' + stars + '</span>' +
                            '<span>(' + addon.reviews + ')</span>' +
                            '</div>' +
                            '<button class=\"flavor-marketplace-install-btn\" data-slug=\"' + addon.slug + '\" data-label=\"' + (addon.cta_label || (addon.installed ? 'Instalado' : 'Instalar')) + '\">' +
                            (addon.cta_label || (addon.installed ? 'Instalado' : 'Instalar')) +
                            '</button>' +
                            '</div>' +
                            '</div>' +
                            '</div>';
                    },

                    showAddonDetails: function(slug) {
                        $('.flavor-marketplace-modal').addClass('active');
                        $('.flavor-marketplace-modal-content').html('<div class=\"flavor-marketplace-loading\"><span class=\"spinner is-active\"></span></div>');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_get_addon_details',
                                nonce: '" . wp_create_nonce('flavor_marketplace_nonce') . "',
                                slug: slug
                            },
                            success: function(response) {
                                if (response.success) {
                                    FlavorMarketplace.renderAddonDetails(response.data.addon);
                                }
                            }
                        });
                    },

                    renderAddonDetails: function(addon) {
                        var html = '<button class=\"flavor-marketplace-modal-close\">×</button>' +
                            '<div class=\"flavor-marketplace-modal-banner\"></div>' +
                            '<div class=\"flavor-marketplace-modal-body\">' +
                            '<h2>' + addon.name + '</h2>' +
                            '<p>' + addon.long_description + '</p>' +
                            '<h3>Características</h3>' +
                            '<ul>' + addon.features.map(f => '<li>' + f + '</li>').join('') + '</ul>' +
                            '<h3>Changelog</h3>' +
                            '<pre>' + addon.changelog + '</pre>' +
                            '</div>';

                        $('.flavor-marketplace-modal-content').html(html);
                    },

                    installAddon: function(slug) {
                        if (!confirm('¿Instalar este addon?')) return;

                        var btn = $('.flavor-marketplace-install-btn[data-slug=\"' + slug + '\"]');
                        var defaultLabel = btn.data('label') || 'Instalar';
                        btn.prop('disabled', true).text('Instalando...');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_install_addon',
                                nonce: '" . wp_create_nonce('flavor_marketplace_nonce') . "',
                                slug: slug
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (response.data.checkout_url) {
                                        window.location.href = response.data.checkout_url;
                                        return;
                                    }

                                    btn.text('Instalado');
                                    alert(response.data.message || 'Addon instalado correctamente');
                                } else {
                                    btn.prop('disabled', false).text(defaultLabel);
                                    alert('Error: ' + response.data);
                                }
                            },
                            error: function() {
                                btn.prop('disabled', false).text(defaultLabel);
                                alert('Error de conexión');
                            }
                        });
                    }
                };

                $(document).ready(function() {
                    if ($('.flavor-marketplace-wrapper').length) {
                        FlavorMarketplace.init();
                    }
                });

            })(jQuery);
        ";

        wp_add_inline_script('jquery', $js);
    }

    /**
     * Renderiza página del marketplace
     *
     * @return void
     */
    public function render_marketplace_page() {
        ?>
        <div class="wrap flavor-marketplace-wrapper">
            <div class="flavor-marketplace-header">
                <h1><?php echo esc_html__('Marketplace de Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
                <p><?php echo esc_html__('Descubre y añade nuevas funcionalidades a tu plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="flavor-marketplace-search">
                    <input type="text" id="flavor-marketplace-search" placeholder="<?php esc_attr_e('Buscar addons...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="flavor-marketplace-filters">
                <button class="flavor-marketplace-filter active" data-filter="all">
                    <?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="free">
                    <?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="premium">
                    <?php echo esc_html__('Premium', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="popular">
                    <?php echo esc_html__('Populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="new">
                    <?php echo esc_html__('Nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <div class="flavor-marketplace-grid" id="flavor-marketplace-grid">
                <!-- Los addons se cargan via AJAX -->
            </div>

            <!-- Modal para detalles -->
            <div class="flavor-marketplace-modal">
                <div class="flavor-marketplace-modal-content">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Explorar marketplace
     *
     * @return void
     */
    public function ajax_browse_marketplace() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $search = sanitize_text_field($_POST['search'] ?? '');

        $addons = $this->fetch_marketplace_addons($filter, $search);

        wp_send_json_success([
            'addons' => $addons,
            'count' => count($addons),
        ]);
    }

    /**
     * Obtiene addons del marketplace
     *
     * @param string $filter Filtro
     * @param string $search Búsqueda
     * @return array
     */
    private function fetch_marketplace_addons($filter = 'all', $search = '') {
        // Verificar cache
        $cache_key = 'flavor_marketplace_' . $filter . '_' . md5($search);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Hacer request al servidor
        if ($this->is_license_server_marketplace()) {
            $url = $this->marketplace_url . '/addons';
        } else {
            $url = add_query_arg([
                'filter' => $filter,
                'search' => $search,
            ], $this->marketplace_url . '/browse');
        }

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['addons']) || !is_array($data['addons'])) {
            return [];
        }

        $addons = $this->is_license_server_marketplace()
            ? $this->normalize_license_server_addons($data['addons'], $filter, $search)
            : $data['addons'];

        // Cachear por 1 hora
        set_transient($cache_key, $addons, HOUR_IN_SECONDS);

        return $addons;
    }

    /**
     * AJAX: Obtener detalles de addon
     *
     * @return void
     */
    public function ajax_get_addon_details() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $slug = sanitize_text_field($_POST['slug'] ?? '');

        if (empty($slug)) {
            wp_send_json_error(__('Slug requerido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $addon = $this->fetch_addon_details($slug);

        if (!$addon) {
            wp_send_json_error(__('Addon no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        wp_send_json_success(['addon' => $addon]);
    }

    /**
     * Obtiene detalles de un addon
     *
     * @param string $slug Slug del addon
     * @return array|false
     */
    private function fetch_addon_details($slug) {
        if ($this->is_license_server_marketplace()) {
            $response = wp_remote_get($this->marketplace_url . '/addons/' . rawurlencode($slug), ['timeout' => 15]);

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data['addon']) || !is_array($data['addon'])) {
                return false;
            }

            return $this->normalize_license_server_addon($data['addon']);
        }

        $url = $this->marketplace_url . '/addon/' . $slug;

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['addon'] ?? false;
    }

    /**
     * AJAX: Instalar addon
     *
     * @return void
     */
    public function ajax_install_addon() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $slug = sanitize_text_field($_POST['slug'] ?? '');

        if (empty($slug)) {
            wp_send_json_error(__('Slug requerido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener URL de descarga
        $addon = $this->fetch_addon_details($slug);

        if (!$addon) {
            wp_send_json_error(__('Addon no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if ($this->is_license_server_marketplace() && empty($addon['download_url']) && !empty($addon['owned'])) {
            $download_url = $this->create_license_server_download($addon);

            if (is_wp_error($download_url)) {
                wp_send_json_error($download_url->get_error_message());
            }

            $addon['download_url'] = $download_url;
        }

        if (empty($addon['download_url'])) {
            if ($this->is_license_server_marketplace()) {
                if (!empty($addon['price']) && floatval($addon['price']) > 0) {
                    $checkout = $this->create_license_server_checkout($addon);

                    if (is_wp_error($checkout)) {
                        wp_send_json_error($checkout->get_error_message());
                    }

                    wp_send_json_success([
                        'message' => __('Redirigiendo al checkout del addon', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'checkout_url' => $checkout,
                    ]);
                }

                wp_send_json_error(__('Este addon no ofrece descarga directa desde el servidor de licencias.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            wp_send_json_error(__('No se pudo obtener URL de descarga', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Usar WordPress upgrader
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install($addon['download_url']);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Activar el addon
        $plugin_file = $upgrader->plugin_info();
        if ($plugin_file) {
            activate_plugin($plugin_file);
        }

        wp_send_json_success([
            'message' => __('Addon instalado y activado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Indica si el marketplace usa Flavor License Server.
     *
     * @return bool
     */
    private function is_license_server_marketplace() {
        return strpos($this->marketplace_url, '/fls/v1') !== false;
    }

    /**
     * Normaliza addons del servidor de licencias al formato esperado por la UI.
     *
     * @param array $addons Datos remotos.
     * @param string $filter Filtro solicitado.
     * @param string $search Texto de búsqueda.
     * @return array
     */
    private function normalize_license_server_addons($addons, $filter = 'all', $search = '') {
        $normalized = [];
        $search = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);

        foreach ($addons as $addon) {
            $normalized_addon = $this->normalize_license_server_addon($addon);
            $name = $normalized_addon['name'];
            $description = $normalized_addon['description'];
            $haystack = function_exists('mb_strtolower')
                ? mb_strtolower($name . ' ' . $description)
                : strtolower($name . ' ' . $description);
            $price = (float) $normalized_addon['price'];
            $is_premium = !empty($normalized_addon['is_premium']);

            if ($filter === 'free' && $is_premium) {
                continue;
            }

            if ($filter === 'premium' && !$is_premium) {
                continue;
            }

            if ($search !== '' && strpos($haystack, $search) === false) {
                continue;
            }

            $normalized[] = $normalized_addon;
        }

        return array_values($normalized);
    }

    /**
     * Normaliza un addon del servidor de licencias al formato esperado por la UI.
     *
     * @param array $addon Datos remotos.
     * @return array
     */
    private function normalize_license_server_addon($addon) {
        $price = isset($addon['price']) ? (float) $addon['price'] : 0.0;
        $modules = isset($addon['modules']) && is_array($addon['modules']) ? array_values($addon['modules']) : [];
        $owned = $this->license_owns_addon($addon['slug'] ?? '', $modules);
        $is_premium = $price > 0;
        $cta_label = $owned
            ? __('Instalar', FLAVOR_PLATFORM_TEXT_DOMAIN)
            : ($is_premium ? __('Comprar', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN));

        return [
            'id' => intval($addon['id'] ?? 0),
            'slug' => $addon['slug'] ?? '',
            'name' => $addon['name'] ?? '',
            'description' => $addon['description'] ?? '',
            'long_description' => $addon['long_description'] ?? ($addon['description'] ?? ''),
            'price' => $price,
            'billing_period' => $addon['billing_period'] ?? 'one_time',
            'icon' => !empty($addon['icon']) ? $addon['icon'] : 'admin-plugins',
            'features' => isset($addon['features']) && is_array($addon['features']) ? array_values($addon['features']) : $modules,
            'modules' => $modules,
            'changelog' => $addon['changelog'] ?? __('La instalación y las actualizaciones se gestionan desde el servidor de licencias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'rating' => isset($addon['rating']) ? intval($addon['rating']) : 5,
            'reviews' => isset($addon['reviews']) ? intval($addon['reviews']) : 0,
            'installed' => false,
            'owned' => $owned,
            'can_download' => $owned,
            'is_premium' => $is_premium,
            'is_popular' => !empty($addon['is_popular']),
            'is_new' => !empty($addon['is_new']),
            'download_url' => !empty($addon['download_url']) ? $addon['download_url'] : '',
            'version' => $addon['version'] ?? '',
            'cta_label' => $cta_label,
        ];
    }

    /**
     * Indica si la licencia activa ya incluye un addon concreto.
     *
     * @param string $slug Slug del addon.
     * @param array $modules Módulos del addon.
     * @return bool
     */
    private function license_owns_addon($slug, $modules = []) {
        $license_manager = flavor_get_license_manager();
        $license_data = $license_manager ? $license_manager->get_license_data() : null;

        if (empty($license_data['key'])) {
            return false;
        }

        if (!empty($license_data['addons']) && is_array($license_data['addons'])) {
            foreach ($license_data['addons'] as $addon) {
                if (($addon['slug'] ?? '') === $slug) {
                    return true;
                }
            }
        }

        if (!empty($license_data['addon_modules']) && is_array($license_data['addon_modules']) && !empty($modules)) {
            return count(array_intersect($license_data['addon_modules'], $modules)) > 0;
        }

        return false;
    }

    /**
     * Crea un checkout para un addon premium en Flavor License Server.
     *
     * @param array $addon Addon normalizado.
     * @return string|WP_Error
     */
    private function create_license_server_checkout($addon) {
        if (empty($addon['id'])) {
            return new WP_Error('missing_addon_id', __('El addon no tiene identificador de compra.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $license_manager = flavor_get_license_manager();
        $license_data = $license_manager ? $license_manager->get_license_data() : null;
        $license_key = $license_data['key'] ?? '';

        if (empty($license_key)) {
            return new WP_Error('missing_license', __('Activa primero una licencia del sitio para comprar addons.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $response = wp_remote_post($this->marketplace_url . '/addons/purchase', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'FlavorPlatform/' . FLAVOR_PLATFORM_VERSION,
            ],
            'body' => wp_json_encode([
                'addon_id' => intval($addon['id']),
                'license_key' => $license_key,
                'gateway' => 'stripe',
                'success_url' => admin_url('admin.php?page=flavor-marketplace'),
                'cancel_url' => admin_url('admin.php?page=flavor-marketplace'),
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) !== 200 || empty($result['success'])) {
            return new WP_Error(
                'checkout_error',
                $result['message'] ?? __('No se pudo crear el checkout del addon.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        if (empty($result['checkout_url'])) {
            return new WP_Error('missing_checkout_url', __('El servidor no devolvió URL de checkout.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        return esc_url_raw($result['checkout_url']);
    }

    /**
     * Solicita una descarga autorizada de addon al servidor de licencias.
     *
     * @param array $addon Addon normalizado.
     * @return string|WP_Error
     */
    private function create_license_server_download($addon) {
        $license_manager = flavor_get_license_manager();
        $license_data = $license_manager ? $license_manager->get_license_data() : null;
        $license_key = $license_data['key'] ?? '';

        if (empty($license_key)) {
            return new WP_Error('missing_license', __('Activa primero una licencia del sitio para descargar addons.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $response = wp_remote_post($this->marketplace_url . '/addons/download', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'FlavorPlatform/' . FLAVOR_PLATFORM_VERSION,
            ],
            'body' => wp_json_encode([
                'addon_slug' => $addon['slug'] ?? '',
                'license_key' => $license_key,
                'site_url' => get_site_url(),
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) !== 200 || empty($result['success'])) {
            return new WP_Error(
                'download_error',
                $result['message'] ?? __('No se pudo preparar la descarga del addon.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        if (empty($result['download_url'])) {
            return new WP_Error('missing_download_url', __('El servidor no devolvió URL de descarga.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        return esc_url_raw($result['download_url']);
    }

    /**
     * AJAX: Buscar en marketplace
     *
     * @return void
     */
    public function ajax_search_marketplace() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $search = sanitize_text_field($_POST['search'] ?? '');

        $addons = $this->fetch_marketplace_addons('all', $search);

        wp_send_json_success([
            'addons' => $addons,
            'count' => count($addons),
        ]);
    }
}

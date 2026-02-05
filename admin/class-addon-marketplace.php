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
        $this->init_hooks();
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
            'flavor-chat-ia',
            __('Marketplace', 'flavor-chat-ia'),
            __('Marketplace', 'flavor-chat-ia') . ' <span class="dashicons dashicons-star-filled" style="font-size: 14px; color: #f0c33c;"></span>',
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
        wp_enqueue_script('flavor-marketplace', '', ['jquery'], FLAVOR_CHAT_IA_VERSION, true);

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
                            '<button class=\"flavor-marketplace-install-btn\" data-slug=\"' + addon.slug + '\">' +
                            (addon.installed ? 'Instalado' : 'Instalar') +
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
                                    btn.text('Instalado');
                                    alert('Addon instalado correctamente');
                                } else {
                                    btn.prop('disabled', false).text('Instalar');
                                    alert('Error: ' + response.data);
                                }
                            },
                            error: function() {
                                btn.prop('disabled', false).text('Instalar');
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
                <h1><?php echo esc_html__('Marketplace de Addons', 'flavor-chat-ia'); ?></h1>
                <p><?php echo esc_html__('Descubre y añade nuevas funcionalidades a tu plataforma', 'flavor-chat-ia'); ?></p>

                <div class="flavor-marketplace-search">
                    <input type="text" id="flavor-marketplace-search" placeholder="<?php esc_attr_e('Buscar addons...', 'flavor-chat-ia'); ?>">
                </div>
            </div>

            <div class="flavor-marketplace-filters">
                <button class="flavor-marketplace-filter active" data-filter="all">
                    <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="free">
                    <?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="premium">
                    <?php echo esc_html__('Premium', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="popular">
                    <?php echo esc_html__('Populares', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-marketplace-filter" data-filter="new">
                    <?php echo esc_html__('Nuevos', 'flavor-chat-ia'); ?>
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
            wp_send_json_error('No tienes permisos');
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
        $url = add_query_arg([
            'filter' => $filter,
            'search' => $search,
        ], $this->marketplace_url . '/browse');

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['addons']) || !is_array($data['addons'])) {
            return [];
        }

        // Cachear por 1 hora
        set_transient($cache_key, $data['addons'], HOUR_IN_SECONDS);

        return $data['addons'];
    }

    /**
     * AJAX: Obtener detalles de addon
     *
     * @return void
     */
    public function ajax_get_addon_details() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $slug = sanitize_text_field($_POST['slug'] ?? '');

        if (empty($slug)) {
            wp_send_json_error('Slug requerido');
        }

        $addon = $this->fetch_addon_details($slug);

        if (!$addon) {
            wp_send_json_error('Addon no encontrado');
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
            wp_send_json_error('No tienes permisos');
        }

        $slug = sanitize_text_field($_POST['slug'] ?? '');

        if (empty($slug)) {
            wp_send_json_error('Slug requerido');
        }

        // Obtener URL de descarga
        $addon = $this->fetch_addon_details($slug);

        if (!$addon || empty($addon['download_url'])) {
            wp_send_json_error('No se pudo obtener URL de descarga');
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
            'message' => __('Addon instalado y activado correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Buscar en marketplace
     *
     * @return void
     */
    public function ajax_search_marketplace() {
        check_ajax_referer('flavor_marketplace_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $search = sanitize_text_field($_POST['search'] ?? '');

        $addons = $this->fetch_marketplace_addons('all', $search);

        wp_send_json_success([
            'addons' => $addons,
            'count' => count($addons),
        ]);
    }
}

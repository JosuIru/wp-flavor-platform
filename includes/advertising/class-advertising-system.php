<?php
/**
 * Sistema de Publicidad Ética
 *
 * Gestión completa de anuncios, anunciantes, pagos y reparto de beneficios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema principal de publicidad
 */
class Flavor_Advertising_System {

    /**
     * Instancia singleton
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
        $this->init();
    }

    /**
     * Inicializar
     */
    private function init() {
        // Registrar custom post types
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        // Admin
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_flavor_ad', [$this, 'save_ad_meta']);

        // Tracking
        add_action('wp_ajax_flavor_ad_impression', [$this, 'track_impression']);
        add_action('wp_ajax_nopriv_flavor_ad_impression', [$this, 'track_impression']);
        add_action('wp_ajax_flavor_ad_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_flavor_ad_click', [$this, 'track_click']);

        // Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        // Crear tablas
        register_activation_hook(__FILE__, [$this, 'create_tables']);
    }

    /**
     * Registrar post types
     */
    public function register_post_types() {
        // Anuncios
        register_post_type('flavor_ad', [
            'labels' => [
                'name' => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Añadir Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new_item' => __('Añadir Nuevo Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'edit_item' => __('Editar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'view_item' => __('Ver Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 25,
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
            'show_in_menu' => false, // Lo añadiremos manualmente
        ]);

        // Anunciantes
        register_post_type('flavor_advertiser', [
            'labels' => [
                'name' => __('Anunciantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Añadir Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-businessman',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_menu' => false,
        ]);

        // Campañas
        register_post_type('flavor_campaign', [
            'labels' => [
                'name' => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Campaña', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor'],
            'show_in_menu' => false,
        ]);
    }

    /**
     * Registrar taxonomías
     */
    public function register_taxonomies() {
        // Categorías de anuncios
        register_taxonomy('flavor_ad_category', 'flavor_ad', [
            'labels' => [
                'name' => __('Categorías de Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
        ]);

        // Tags de anuncios
        register_taxonomy('flavor_ad_tag', 'flavor_ad', [
            'labels' => [
                'name' => __('Etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
        ]);
    }

    /**
     * Añadir menús admin
     */
    public function add_admin_menus() {
        // Menú principal
        add_menu_page(
            __('Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-advertising',
            [$this, 'render_dashboard'],
            'dashicons-megaphone',
            25
        );

        // Submenús
        add_submenu_page(
            'flavor-advertising',
            __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-advertising',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'flavor-advertising',
            __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'edit.php?post_type=flavor_ad'
        );

        add_submenu_page(
            'flavor-advertising',
            __('Anunciantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Anunciantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'edit.php?post_type=flavor_advertiser'
        );

        add_submenu_page(
            'flavor-advertising',
            __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'edit.php?post_type=flavor_campaign'
        );

        add_submenu_page(
            'flavor-advertising',
            __('Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-ad-network',
            [$this, 'render_network_page']
        );

        add_submenu_page(
            'flavor-advertising',
            __('Pagos y Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-ad-payments',
            [$this, 'render_payments_page']
        );

        add_submenu_page(
            'flavor-advertising',
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-ad-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        // Para anuncios
        add_meta_box(
            'flavor_ad_details',
            __('Detalles del Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_ad_meta_box'],
            'flavor_ad',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_ad_targeting',
            __('Segmentación y Alcance', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_targeting_meta_box'],
            'flavor_ad',
            'side',
            'default'
        );

        add_meta_box(
            'flavor_ad_stats',
            __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_stats_meta_box'],
            'flavor_ad',
            'side',
            'default'
        );
    }

    /**
     * Renderizar meta box de detalles
     */
    public function render_ad_meta_box($post) {
        wp_nonce_field('flavor_ad_meta', 'flavor_ad_meta_nonce');

        $ad_type = get_post_meta($post->ID, '_ad_type', true);
        $ad_size = get_post_meta($post->ID, '_ad_size', true);
        $ad_url = get_post_meta($post->ID, '_ad_url', true);
        $ad_cta = get_post_meta($post->ID, '_ad_cta', true);
        $advertiser_id = get_post_meta($post->ID, '_advertiser_id', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ad_type"><?php _e('Tipo de Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="ad_type" id="ad_type" class="regular-text">
                        <option value="banner" <?php selected($ad_type, 'banner'); ?>><?php _e('Banner', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="card" <?php selected($ad_type, 'card'); ?>><?php _e('Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="native" <?php selected($ad_type, 'native'); ?>><?php _e('Nativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="video" <?php selected($ad_type, 'video'); ?>><?php _e('Video', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ad_size"><?php _e('Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="ad_size" id="ad_size" class="regular-text">
                        <option value="leaderboard" <?php selected($ad_size, 'leaderboard'); ?>>Leaderboard (728x90)</option>
                        <option value="banner" <?php selected($ad_size, 'banner'); ?>>Banner (468x60)</option>
                        <option value="rectangle" <?php selected($ad_size, 'rectangle'); ?>>Rectangle (300x250)</option>
                        <option value="skyscraper" <?php selected($ad_size, 'skyscraper'); ?>>Skyscraper (160x600)</option>
                        <option value="custom" <?php selected($ad_size, 'custom'); ?>><?php _e('Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ad_url"><?php _e('URL de Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="url" name="ad_url" id="ad_url" value="<?php echo esc_url($ad_url); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ad_cta"><?php _e('Texto del Botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="ad_cta" id="ad_cta" value="<?php echo esc_attr($ad_cta); ?>" class="regular-text" placeholder="Ej: Más información">
                </td>
            </tr>
            <tr>
                <th><label for="advertiser_id"><?php _e('Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <?php
                    $advertisers = get_posts(['post_type' => 'flavor_advertiser', 'numberposts' => -1]);
                    ?>
                    <select name="advertiser_id" id="advertiser_id" class="regular-text">
                        <option value=""><?php _e('Seleccionar anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($advertisers as $advertiser): ?>
                            <option value="<?php echo $advertiser->ID; ?>" <?php selected($advertiser_id, $advertiser->ID); ?>>
                                <?php echo esc_html($advertiser->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar meta box de segmentación
     */
    public function render_targeting_meta_box($post) {
        $scope = get_post_meta($post->ID, '_ad_scope', true) ?: 'local';
        $networks = get_post_meta($post->ID, '_ad_networks', true) ?: [];
        $locations = get_post_meta($post->ID, '_ad_locations', true) ?: [];
        $start_date = get_post_meta($post->ID, '_ad_start_date', true);
        $end_date = get_post_meta($post->ID, '_ad_end_date', true);
        $budget = get_post_meta($post->ID, '_ad_budget', true);
        $revenue_share = get_post_meta($post->ID, '_revenue_share', true) ?: 70;
        ?>
        <div class="flavor-ad-targeting">
            <p>
                <label><strong><?php _e('Alcance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <label>
                    <input type="radio" name="ad_scope" value="local" <?php checked($scope, 'local'); ?>>
                    <?php _e('Solo este sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label><br>
                <label>
                    <input type="radio" name="ad_scope" value="network" <?php checked($scope, 'network'); ?>>
                    <?php _e('Red de sitios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label><br>
                <label>
                    <input type="radio" name="ad_scope" value="global" <?php checked($scope, 'global'); ?>>
                    <?php _e('Global (todos los sitios)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </p>

            <?php if ($scope !== 'local'): ?>
            <p id="network-selection" style="display: <?php echo $scope === 'network' ? 'block' : 'none'; ?>;">
                <label><strong><?php _e('Sitios de la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <?php
                $network_sites = $this->get_network_sites();
                foreach ($network_sites as $site):
                ?>
                    <label>
                        <input type="checkbox" name="ad_networks[]" value="<?php echo $site['id']; ?>" <?php checked(in_array($site['id'], $networks)); ?>>
                        <?php echo esc_html($site['name']); ?>
                    </label><br>
                <?php endforeach; ?>
            </p>
            <?php endif; ?>

            <p>
                <label><strong><?php _e('Ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <?php
                $available_positions = [
                    'header' => __('Cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'sidebar' => __('Barra lateral', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'content_top' => __('Antes del contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'content_middle' => __('En el contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'content_bottom' => __('Después del contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'footer' => __('Pie de página', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                foreach ($available_positions as $key => $label):
                ?>
                    <label>
                        <input type="checkbox" name="ad_locations[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $locations)); ?>>
                        <?php echo $label; ?>
                    </label><br>
                <?php endforeach; ?>
            </p>

            <p>
                <label><strong><?php _e('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input type="date" name="ad_start_date" value="<?php echo esc_attr($start_date); ?>" class="widefat">
            </p>

            <p>
                <label><strong><?php _e('Fecha fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input type="date" name="ad_end_date" value="<?php echo esc_attr($end_date); ?>" class="widefat">
            </p>

            <p>
                <label><strong><?php _e('Presupuesto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input type="number" name="ad_budget" value="<?php echo esc_attr($budget); ?>" step="0.01" class="widefat">
            </p>

            <p>
                <label><strong><?php _e('Reparto de ingresos (%)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input type="number" name="revenue_share" value="<?php echo esc_attr($revenue_share); ?>" min="0" max="100" class="widefat">
                <small><?php _e('% que reciben los sitios que muestran el anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="ad_scope"]').on('change', function() {
                $('#network-selection').toggle($(this).val() === 'network');
            });
        });
        </script>
        <?php
    }

    /**
     * Renderizar meta box de estadísticas
     */
    public function render_stats_meta_box($post) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_ad_stats';

        $impressions = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(impressions) FROM $table WHERE ad_id = %d",
            $post->ID
        )) ?: 0;

        $clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(clicks) FROM $table WHERE ad_id = %d",
            $post->ID
        )) ?: 0;

        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;

        $revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(revenue) FROM $table WHERE ad_id = %d",
            $post->ID
        )) ?: 0;
        ?>
        <div class="flavor-ad-stats">
            <p>
                <strong><?php _e('Impresiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong><br>
                <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($impressions); ?></span>
            </p>
            <p>
                <strong><?php _e('Clics', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong><br>
                <span style="font-size: 24px; color: #46b450;"><?php echo number_format($clicks); ?></span>
            </p>
            <p>
                <strong><?php _e('CTR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong><br>
                <span style="font-size: 24px;"><?php echo number_format($ctr, 2); ?>%</span>
            </p>
            <p>
                <strong><?php _e('Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong><br>
                <span style="font-size: 24px; color: #46b450;">€<?php echo number_format($revenue, 2); ?></span>
            </p>
        </div>
        <?php
    }

    /**
     * Guardar meta del anuncio
     */
    public function save_ad_meta($post_id) {
        if (!isset($_POST['flavor_ad_meta_nonce']) || !wp_verify_nonce($_POST['flavor_ad_meta_nonce'], 'flavor_ad_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar campos
        $fields = [
            'ad_type', 'ad_size', 'ad_url', 'ad_cta', 'advertiser_id',
            'ad_scope', 'ad_start_date', 'ad_end_date', 'ad_budget', 'revenue_share'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Arrays
        if (isset($_POST['ad_networks'])) {
            update_post_meta($post_id, '_ad_networks', array_map('sanitize_text_field', $_POST['ad_networks']));
        } else {
            delete_post_meta($post_id, '_ad_networks');
        }

        if (isset($_POST['ad_locations'])) {
            update_post_meta($post_id, '_ad_locations', array_map('sanitize_text_field', $_POST['ad_locations']));
        } else {
            delete_post_meta($post_id, '_ad_locations');
        }
    }

    /**
     * Crear tablas de tracking
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de estadísticas
        $table_stats = $wpdb->prefix . 'flavor_ad_stats';
        $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) NOT NULL,
            site_id varchar(255) DEFAULT '',
            date date NOT NULL,
            impressions int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ad_id (ad_id),
            KEY site_id (site_id),
            KEY date (date)
        ) $charset_collate;";

        // Tabla de transacciones
        $table_transactions = $wpdb->prefix . 'flavor_ad_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) NOT NULL,
            site_id varchar(255) DEFAULT '',
            amount decimal(10,2) NOT NULL,
            type varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            paid_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ad_id (ad_id),
            KEY site_id (site_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_stats);
        dbDelta($sql_transactions);
    }

    /**
     * Obtener sitios de la red
     */
    private function get_network_sites() {
        // Por ahora devolver array de ejemplo
        // En producción, esto consultaría una API central o base de datos de red
        return [
            ['id' => 'site_1', 'name' => 'Comunidad Basabe'],
            ['id' => 'site_2', 'name' => 'Cooperativa Bilbao'],
            ['id' => 'site_3', 'name' => 'Grupo Consumo Vitoria'],
        ];
    }

    /**
     * Track impression
     */
    public function track_impression() {
        check_ajax_referer('flavor_ad_tracking', 'nonce');

        $ad_id = intval($_POST['ad_id']);
        $this->record_stat($ad_id, 'impression');

        wp_send_json_success();
    }

    /**
     * Track click
     */
    public function track_click() {
        check_ajax_referer('flavor_ad_tracking', 'nonce');

        $ad_id = intval($_POST['ad_id']);
        $this->record_stat($ad_id, 'click');

        wp_send_json_success();
    }

    /**
     * Registrar estadística
     */
    private function record_stat($ad_id, $type) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_ad_stats';
        $today = current_time('Y-m-d');
        $site_id = get_option('flavor_site_id', 'local');

        // Buscar registro de hoy
        $stat = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE ad_id = %d AND site_id = %s AND date = %s",
            $ad_id, $site_id, $today
        ));

        if ($stat) {
            // Actualizar
            if ($type === 'impression') {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET impressions = impressions + 1 WHERE id = %d",
                    $stat->id
                ));
            } else {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET clicks = clicks + 1 WHERE id = %d",
                    $stat->id
                ));
            }
        } else {
            // Insertar
            $wpdb->insert($table, [
                'ad_id' => $ad_id,
                'site_id' => $site_id,
                'date' => $today,
                'impressions' => $type === 'impression' ? 1 : 0,
                'clicks' => $type === 'click' ? 1 : 0,
            ]);
        }
    }

    /**
     * Enqueue scripts frontend
     */
    public function enqueue_frontend_scripts() {
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'flavor-ad-tracking',
            plugins_url("assets/js/ad-tracking{$sufijo_asset}.js", dirname(dirname(__FILE__))),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-ad-tracking', 'flavorAds', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_ad_tracking'),
        ]);
    }

    /**
     * Renderizar dashboard
     */
    public function render_dashboard() {
        include __DIR__ . '/views/dashboard.php';
    }

    /**
     * Renderizar página de red
     */
    public function render_network_page() {
        include __DIR__ . '/views/network.php';
    }

    /**
     * Renderizar página de pagos
     */
    public function render_payments_page() {
        include __DIR__ . '/views/payments.php';
    }

    /**
     * Renderizar página de settings
     */
    public function render_settings_page() {
        include __DIR__ . '/views/settings.php';
    }
}

// Inicializar
Flavor_Advertising_System::get_instance();

<?php
/**
 * Dashboard Tab para Economía del Don
 *
 * @package FlavorChatIA
 * @subpackage Modules\EconomiaDon
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Economia_Don_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Economia_Don_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Economia_Don_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['economia-don-resumen'] = [
            'label' => __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'gift',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 62,
        ];

        $tabs['economia-don-dones'] = [
            'label' => __('Mis Dones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'heart',
            'callback' => [$this, 'render_tab_mis_dones'],
            'orden' => 63,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de resumen
     */
    public function render_tab_resumen() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_dones = $wpdb->prefix . 'flavor_dones';
        $tabla_intercambios = $wpdb->prefix . 'flavor_don_intercambios';

        // KPIs
        $dones_disponibles = 0;
        $mis_dones_ofrecidos = 0;
        $dones_recibidos = 0;
        $intercambios_completados = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_dones)) {
            $dones_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_dones} WHERE estado = 'disponible'");
            $mis_dones_ofrecidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_dones} WHERE donante_id = %d",
                $user_id
            ));
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_intercambios)) {
            $dones_recibidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_intercambios} WHERE receptor_id = %d AND estado = 'completado'",
                $user_id
            ));
            $intercambios_completados = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_intercambios} WHERE (donante_id = %d OR receptor_id = %d) AND estado = 'completado'",
                $user_id,
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-economia-don-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Dar sin esperar, recibir con gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-megaphone"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($dones_disponibles); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Dones Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-share-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_dones_ofrecidos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Dones que Ofrezco', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-download"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($dones_recibidos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Dones Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($intercambios_completados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Ofrecer un Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_don', 'explorar')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar Dones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis dones
     */
    public function render_tab_mis_dones() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_dones = $wpdb->prefix . 'flavor_dones';

        $mis_dones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_dones)) {
            $mis_dones = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_dones} WHERE donante_id = %d ORDER BY fecha_creacion DESC LIMIT 20",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-mis-dones-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Mis Dones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Ofrecer Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($mis_dones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('Aún no has ofrecido ningún don a la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('La economía del don se basa en dar libremente sin esperar algo a cambio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ofrecer mi primer don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($mis_dones as $don): ?>
                        <div class="flavor-card flavor-don-card">
                            <div class="flavor-card-header">
                                <h4><?php echo esc_html($don->titulo); ?></h4>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($don->estado); ?>">
                                    <?php echo esc_html(ucfirst($don->estado)); ?>
                                </span>
                            </div>
                            <div class="flavor-card-body">
                                <p><?php echo esc_html(wp_trim_words($don->descripcion, 20)); ?></p>
                            </div>
                            <div class="flavor-card-footer">
                                <span class="flavor-text-muted">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($don->fecha_creacion))); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Enqueue de assets
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }
    }
}

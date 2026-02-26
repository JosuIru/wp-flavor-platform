<?php
/**
 * Dashboard Tab para Saberes Ancestrales
 *
 * @package FlavorChatIA
 * @subpackage Modules\SaberesAncestrales
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Saberes_Ancestrales_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Saberes_Ancestrales_Dashboard_Tab|null
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
     * @return Flavor_Saberes_Ancestrales_Dashboard_Tab
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
        $tabs['saberes-resumen'] = [
            'label' => __('Saberes Ancestrales', 'flavor-chat-ia'),
            'icon' => 'book-open',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 68,
        ];

        $tabs['saberes-aportes'] = [
            'label' => __('Mis Aportes', 'flavor-chat-ia'),
            'icon' => 'scroll',
            'callback' => [$this, 'render_tab_aportes'],
            'orden' => 69,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de resumen
     */
    public function render_tab_resumen() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_saberes = $wpdb->prefix . 'flavor_saberes';
        $tabla_transmisiones = $wpdb->prefix . 'flavor_saberes_transmisiones';

        // KPIs
        $total_saberes = 0;
        $mis_aportes = 0;
        $transmisiones_activas = 0;
        $categorias_saberes = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_saberes)) {
            $total_saberes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_saberes} WHERE estado = 'publicado'");
            $mis_aportes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_saberes} WHERE autor_id = %d",
                $user_id
            ));
            $categorias_saberes = (int) $wpdb->get_var("SELECT COUNT(DISTINCT categoria) FROM {$tabla_saberes}");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_transmisiones)) {
            $transmisiones_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_transmisiones} WHERE estado = 'activa'"
            );
        }

        ?>
        <div class="flavor-panel flavor-saberes-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-book-alt"></span> <?php esc_html_e('Saberes Ancestrales', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Preservando la sabiduría de nuestros ancestros', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-book"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_saberes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Saberes Documentados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-edit"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_aportes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Aportes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-welcome-learn-more"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($transmisiones_activas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Transmisiones Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-category"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($categorias_saberes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Categorías', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Áreas de Sabiduría', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-areas-grid">
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-carrot"></span>
                        <span><?php esc_html_e('Agricultura tradicional', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-heart"></span>
                        <span><?php esc_html_e('Medicina natural', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-art"></span>
                        <span><?php esc_html_e('Artesanías', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-microphone"></span>
                        <span><?php esc_html_e('Tradición oral', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-food"></span>
                        <span><?php esc_html_e('Gastronomía ancestral', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-area-item">
                        <span class="dashicons dashicons-star-filled"></span>
                        <span><?php esc_html_e('Rituales y ceremonias', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/saberes/explorar/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar Saberes', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/saberes/compartir/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Compartir un Saber', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/saberes/transmisiones/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e('Transmisiones', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de aportes
     */
    public function render_tab_aportes() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_saberes = $wpdb->prefix . 'flavor_saberes';

        $mis_saberes = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_saberes)) {
            $mis_saberes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_saberes} WHERE autor_id = %d ORDER BY fecha_creacion DESC LIMIT 20",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-aportes-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-edit"></span> <?php esc_html_e('Mis Aportes', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/saberes/compartir/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Compartir Saber', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($mis_saberes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-book-alt"></span>
                    <p><?php esc_html_e('Aún no has compartido ningún saber ancestral.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Tu conocimiento es valioso. Compártelo con la comunidad para que perdure.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/saberes/compartir/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Compartir mi primer saber', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($mis_saberes as $saber): ?>
                        <div class="flavor-card flavor-saber-card">
                            <div class="flavor-card-header">
                                <h4><?php echo esc_html($saber->titulo); ?></h4>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($saber->estado); ?>">
                                    <?php echo esc_html(ucfirst($saber->estado)); ?>
                                </span>
                            </div>
                            <div class="flavor-card-body">
                                <?php if (!empty($saber->categoria)): ?>
                                    <p class="flavor-saber-categoria">
                                        <span class="dashicons dashicons-category"></span>
                                        <?php echo esc_html($saber->categoria); ?>
                                    </p>
                                <?php endif; ?>
                                <p><?php echo esc_html(wp_trim_words($saber->descripcion, 15)); ?></p>
                            </div>
                            <div class="flavor-card-footer">
                                <span class="flavor-text-muted">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($saber->fecha_creacion))); ?>
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

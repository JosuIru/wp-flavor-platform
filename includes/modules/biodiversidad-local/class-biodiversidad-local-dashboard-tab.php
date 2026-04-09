<?php
/**
 * Dashboard Tab para Biodiversidad Local
 *
 * @package FlavorChatIA
 * @subpackage Modules\BiodiversidadLocal
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Biodiversidad_Local_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Biodiversidad_Local_Dashboard_Tab|null
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
     * @return Flavor_Biodiversidad_Local_Dashboard_Tab
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
        $tabs['biodiversidad-resumen'] = [
            'label' => __('Biodiversidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'leaf',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 60,
        ];

        $tabs['biodiversidad-avistamientos'] = [
            'label' => __('Mis Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'binoculars',
            'callback' => [$this, 'render_tab_avistamientos'],
            'orden' => 61,
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
        $tabla_especies = $wpdb->prefix . 'flavor_especies';
        $tabla_avistamientos = $wpdb->prefix . 'flavor_avistamientos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_proyectos_conservacion';

        // KPIs
        $total_especies = 0;
        $mis_avistamientos = 0;
        $proyectos_activos = 0;
        $especies_amenazadas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_especies)) {
            $total_especies = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_especies}");
            $especies_amenazadas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_especies} WHERE estado_conservacion IN ('vulnerable', 'en_peligro', 'en_peligro_critico')"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_avistamientos)) {
            $mis_avistamientos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_avistamientos} WHERE usuario_id = %d",
                $user_id
            ));
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            $proyectos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado = 'activo'"
            );
        }

        ?>
        <div class="flavor-panel flavor-biodiversidad-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Biodiversidad Local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-palmtree"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_especies); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Especies Catalogadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-visibility"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_avistamientos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($proyectos_activos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Proyectos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-warning"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($especies_amenazadas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Especies Amenazadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad_local', 'catalogo')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad_local', 'nuevo-avistamiento')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Registrar Avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad_local', 'proyectos')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e('Ver Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de avistamientos
     */
    public function render_tab_avistamientos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_avistamientos = $wpdb->prefix . 'flavor_avistamientos';
        $tabla_especies = $wpdb->prefix . 'flavor_especies';

        $avistamientos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_avistamientos)) {
            $avistamientos = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, e.nombre_comun, e.nombre_cientifico
                 FROM {$tabla_avistamientos} a
                 LEFT JOIN {$tabla_especies} e ON a.especie_id = e.id
                 WHERE a.usuario_id = %d
                 ORDER BY a.fecha DESC
                 LIMIT 20",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-avistamientos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Mis Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad_local', 'nuevo-avistamiento')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($avistamientos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-visibility"></span>
                    <p><?php esc_html_e('Aún no has registrado ningún avistamiento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad_local', 'nuevo-avistamiento')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Registrar mi primer avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Especie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avistamientos as $avistamiento): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($avistamiento->fecha))); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($avistamiento->nombre_comun ?: __('Sin identificar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                        <?php if (!empty($avistamiento->nombre_cientifico)): ?>
                                            <br><em class="flavor-text-muted"><?php echo esc_html($avistamiento->nombre_cientifico); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($avistamiento->ubicacion ?? '-'); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($avistamiento->estado ?? 'pendiente'); ?>">
                                            <?php echo esc_html(ucfirst($avistamiento->estado ?? 'pendiente')); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

        wp_enqueue_style(
            'flavor-biodiversidad-dashboard',
            plugins_url('assets/css/biodiversidad-frontend.css', __FILE__),
            [],
            '1.0.0'
        );
    }
}

<?php
/**
 * Dashboard Tab para Justicia Restaurativa
 *
 * @package FlavorPlatform
 * @subpackage Modules\JusticiaRestaurativa
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Justicia_Restaurativa_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Justicia_Restaurativa_Dashboard_Tab|null
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
     * @return Flavor_Justicia_Restaurativa_Dashboard_Tab
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
        $tabs['justicia-resumen'] = [
            'label' => __('Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'balance',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 72,
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
        $tabla_circulos = $wpdb->prefix . 'flavor_circulos_justicia';
        $tabla_mediaciones = $wpdb->prefix . 'flavor_mediaciones';
        $tabla_facilitadores = $wpdb->prefix . 'flavor_facilitadores';

        // KPIs
        $circulos_comunidad = 0;
        $mis_participaciones = 0;
        $mediaciones_exitosas = 0;
        $facilitadores_activos = 0;

        if (Flavor_Platform_Helpers::tabla_existe($tabla_circulos)) {
            $circulos_comunidad = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_circulos} WHERE estado IN ('completado', 'activo')"
            );
            $mis_participaciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_circulos} WHERE FIND_IN_SET(%d, participantes_ids) > 0",
                $user_id
            ));
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_mediaciones)) {
            $mediaciones_exitosas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_mediaciones} WHERE resultado = 'acuerdo'"
            );
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_facilitadores)) {
            $facilitadores_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_facilitadores} WHERE estado = 'activo'"
            );
        }

        ?>
        <div class="flavor-panel flavor-justicia-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Reparar relaciones, reconstruir comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($circulos_comunidad); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Círculos Realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-users"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_participaciones); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Participaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mediaciones_exitosas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Acuerdos Logrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-businessman"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($facilitadores_activos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Facilitadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Principios de la Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-principles-grid">
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-heart"></span>
                        <div>
                            <strong><?php esc_html_e('Reparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Enfocada en sanar el daño causado, no en castigar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-admin-users"></span>
                        <div>
                            <strong><?php esc_html_e('Encuentro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Diálogo directo entre las personas afectadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-groups"></span>
                        <div>
                            <strong><?php esc_html_e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('La comunidad participa en la resolución.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-update"></span>
                        <div>
                            <strong><?php esc_html_e('Transformación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Cambio positivo para todos los involucrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Procesos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-processes-list">
                    <div class="flavor-process-item">
                        <span class="dashicons dashicons-format-chat"></span>
                        <div>
                            <strong><?php esc_html_e('Mediación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Facilitación de diálogo entre dos partes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                    <div class="flavor-process-item">
                        <span class="dashicons dashicons-groups"></span>
                        <div>
                            <strong><?php esc_html_e('Círculos Restaurativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Proceso grupal con participación comunitaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                    <div class="flavor-process-item">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <div>
                            <strong><?php esc_html_e('Conferencias Familiares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <p><?php esc_html_e('Inclusión del entorno familiar en la resolución.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('justicia_restaurativa', 'solicitar')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php esc_html_e('Solicitar Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('justicia_restaurativa', 'formacion')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php esc_html_e('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('justicia_restaurativa', 'facilitadores')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-businessman"></span>
                    <?php esc_html_e('Ser Facilitador/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
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

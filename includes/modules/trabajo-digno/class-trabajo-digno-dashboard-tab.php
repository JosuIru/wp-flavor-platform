<?php
/**
 * Dashboard Tab para Trabajo Digno
 *
 * @package FlavorPlatform
 * @subpackage Modules\TrabajoDigno
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Trabajo_Digno_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Trabajo_Digno_Dashboard_Tab|null
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
     * @return Flavor_Trabajo_Digno_Dashboard_Tab
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
        $tabs['trabajo-digno-resumen'] = [
            'label' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'briefcase',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 66,
        ];

        $tabs['trabajo-digno-ofertas'] = [
            'label' => __('Bolsa de Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'clipboard-list',
            'callback' => [$this, 'render_tab_ofertas'],
            'orden' => 67,
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
        $tabla_ofertas = $wpdb->prefix . 'flavor_trabajo_ofertas';
        $tabla_candidaturas = $wpdb->prefix . 'flavor_trabajo_candidaturas';
        $tabla_cooperativas = $wpdb->prefix . 'flavor_cooperativas';

        // KPIs
        $ofertas_activas = 0;
        $mis_candidaturas = 0;
        $cooperativas_registradas = 0;
        $empleos_dignos_creados = 0;

        if (Flavor_Platform_Helpers::tabla_existe($tabla_ofertas)) {
            $ofertas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_ofertas} WHERE estado = 'activa' AND fecha_limite >= CURDATE()"
            );
            $empleos_dignos_creados = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_ofertas} WHERE estado = 'cubierta'"
            );
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_candidaturas)) {
            $mis_candidaturas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_candidaturas} WHERE candidato_id = %d",
                $user_id
            ));
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_cooperativas)) {
            $cooperativas_registradas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_cooperativas} WHERE estado = 'activa'"
            );
        }

        ?>
        <div class="flavor-panel flavor-trabajo-digno-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-businessman"></span> <?php esc_html_e('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Empleo justo, equitativo y sostenible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-megaphone"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($ofertas_activas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Ofertas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clipboard"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_candidaturas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($cooperativas_registradas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Cooperativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($empleos_dignos_creados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Empleos Creados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Criterios de Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-criteria-list">
                    <div class="flavor-criteria-item">
                        <span class="dashicons dashicons-money-alt"></span>
                        <span><?php esc_html_e('Salario justo y transparente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-criteria-item">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php esc_html_e('Horarios que respetan la vida personal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-criteria-item">
                        <span class="dashicons dashicons-universal-access"></span>
                        <span><?php esc_html_e('Igualdad y no discriminación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flavor-criteria-item">
                        <span class="dashicons dashicons-shield"></span>
                        <span><?php esc_html_e('Seguridad y derechos laborales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('trabajo_digno', 'ofertas')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Ver Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('trabajo_digno', 'mi-perfil')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e('Mi Perfil Laboral', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('trabajo_digno', 'cooperativas')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e('Cooperativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de ofertas
     */
    public function render_tab_ofertas() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_trabajo_ofertas';

        $ofertas = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_ofertas)) {
            $ofertas = $wpdb->get_results(
                "SELECT * FROM {$tabla_ofertas}
                 WHERE estado = 'activa' AND fecha_limite >= CURDATE()
                 ORDER BY fecha_publicacion DESC
                 LIMIT 10"
            );
        }

        ?>
        <div class="flavor-panel flavor-ofertas-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e('Bolsa de Empleo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($ofertas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay ofertas de trabajo activas en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($ofertas as $oferta): ?>
                        <div class="flavor-card flavor-oferta-card">
                            <div class="flavor-card-header">
                                <h4><?php echo esc_html($oferta->titulo); ?></h4>
                                <?php if (!empty($oferta->tipo_contrato)): ?>
                                    <span class="flavor-badge"><?php echo esc_html($oferta->tipo_contrato); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-body">
                                <p class="flavor-oferta-empresa">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php echo esc_html($oferta->empresa ?? __('Empresa no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </p>
                                <p class="flavor-oferta-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($oferta->ubicacion ?? __('Ubicación no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </p>
                                <?php if (!empty($oferta->salario_min) || !empty($oferta->salario_max)): ?>
                                    <p class="flavor-oferta-salario">
                                        <span class="dashicons dashicons-money-alt"></span>
                                        <?php
                                        if ($oferta->salario_min && $oferta->salario_max) {
                                            echo esc_html(number_format_i18n($oferta->salario_min) . ' - ' . number_format_i18n($oferta->salario_max) . ' €');
                                        } elseif ($oferta->salario_min) {
                                            echo esc_html__('Desde ', FLAVOR_PLATFORM_TEXT_DOMAIN) . number_format_i18n($oferta->salario_min) . ' €';
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <span class="flavor-text-muted">
                                    <?php printf(
                                        esc_html__('Límite: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        date_i18n(get_option('date_format'), strtotime($oferta->fecha_limite))
                                    ); ?>
                                </span>
                                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('trabajo_digno', 'oferta') . '/' . $oferta->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
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

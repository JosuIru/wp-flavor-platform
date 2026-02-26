<?php
/**
 * Dashboard Tab para Círculos de Cuidados
 *
 * @package FlavorChatIA
 * @subpackage Modules\CirculosCuidados
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Circulos_Cuidados_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Circulos_Cuidados_Dashboard_Tab|null
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
     * @return Flavor_Circulos_Cuidados_Dashboard_Tab
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
        $tabs['cuidados-resumen'] = [
            'label' => __('Círculos de Cuidados', 'flavor-chat-ia'),
            'icon' => 'hands-helping',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 73,
        ];

        $tabs['cuidados-mis-circulos'] = [
            'label' => __('Mis Círculos', 'flavor-chat-ia'),
            'icon' => 'users',
            'callback' => [$this, 'render_tab_mis_circulos'],
            'orden' => 74,
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
        $tabla_circulos = $wpdb->prefix . 'flavor_circulos_cuidados';
        $tabla_necesidades = $wpdb->prefix . 'flavor_necesidades_cuidado';
        $tabla_miembros = $wpdb->prefix . 'flavor_circulos_miembros';

        // KPIs
        $circulos_activos = 0;
        $mis_circulos = 0;
        $necesidades_pendientes = 0;
        $cuidados_ofrecidos = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_circulos)) {
            $circulos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_circulos} WHERE estado = 'activo'"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            $mis_circulos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros} WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_necesidades)) {
            $necesidades_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_necesidades} WHERE estado = 'pendiente'"
            );
            $cuidados_ofrecidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_necesidades} WHERE cuidador_id = %d AND estado = 'completado'",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-cuidados-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Círculos de Cuidados', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Cuidarnos mutuamente, tejer comunidad', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($circulos_activos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Círculos Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-users"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_circulos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Círculos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-megaphone"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($necesidades_pendientes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Necesidades Pendientes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($cuidados_ofrecidos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Cuidados Ofrecidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Tipos de Cuidados', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-cuidados-grid">
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php esc_html_e('Acompañamiento', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-cart"></span>
                        <span><?php esc_html_e('Recados y compras', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-food"></span>
                        <span><?php esc_html_e('Comidas', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-car"></span>
                        <span><?php esc_html_e('Transporte', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-smiley"></span>
                        <span><?php esc_html_e('Cuidado infantil', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-cuidado-tipo">
                        <span class="dashicons dashicons-admin-home"></span>
                        <span><?php esc_html_e('Tareas domésticas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/necesidades/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Ver Necesidades', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/solicitar/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Solicitar Cuidado', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/unirse/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Unirse a Círculo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis círculos
     */
    public function render_tab_mis_circulos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_circulos = $wpdb->prefix . 'flavor_circulos_cuidados';
        $tabla_miembros = $wpdb->prefix . 'flavor_circulos_miembros';

        $mis_circulos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_miembros) && Flavor_Chat_Helpers::tabla_existe($tabla_circulos)) {
            $mis_circulos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, m.rol, m.fecha_union
                 FROM {$tabla_circulos} c
                 INNER JOIN {$tabla_miembros} m ON c.id = m.circulo_id
                 WHERE m.usuario_id = %d AND m.estado = 'activo'
                 ORDER BY m.fecha_union DESC",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-mis-circulos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Mis Círculos', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/crear-circulo/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Crear Círculo', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($mis_circulos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('Aún no perteneces a ningún círculo de cuidados.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Los círculos de cuidados son redes de apoyo mutuo entre vecinos.', 'flavor-chat-ia'); ?></p>
                    <div class="flavor-empty-actions">
                        <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/unirse/')); ?>" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Unirse a un círculo', 'flavor-chat-ia'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/crear-circulo/')); ?>" class="flavor-btn flavor-btn-outline">
                            <?php esc_html_e('Crear un círculo', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($mis_circulos as $circulo): ?>
                        <div class="flavor-card flavor-circulo-card">
                            <div class="flavor-card-header">
                                <h4><?php echo esc_html($circulo->nombre); ?></h4>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($circulo->rol); ?>">
                                    <?php echo esc_html(ucfirst($circulo->rol)); ?>
                                </span>
                            </div>
                            <div class="flavor-card-body">
                                <?php if (!empty($circulo->descripcion)): ?>
                                    <p><?php echo esc_html(wp_trim_words($circulo->descripcion, 15)); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($circulo->zona)): ?>
                                    <p class="flavor-circulo-zona">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($circulo->zona); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <span class="flavor-text-muted">
                                    <?php printf(
                                        esc_html__('Miembro desde %s', 'flavor-chat-ia'),
                                        date_i18n(get_option('date_format'), strtotime($circulo->fecha_union))
                                    ); ?>
                                </span>
                                <a href="<?php echo esc_url(home_url('/mi-portal/cuidados/circulo/' . $circulo->id)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
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

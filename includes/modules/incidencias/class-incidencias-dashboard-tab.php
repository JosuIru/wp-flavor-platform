<?php
/**
 * Dashboard Tab para Incidencias
 *
 * Compatible con el sistema de tabs de dashboard de cliente
 *
 * @package FlavorChatIA
 * @subpackage Modules\Incidencias
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Incidencias_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Incidencias_Dashboard_Tab|null
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
     * @return Flavor_Incidencias_Dashboard_Tab
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
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['incidencias-resumen'] = [
            'label' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'warning',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 40,
        ];

        $tabs['incidencias-mis-reportes'] = [
            'label' => __('Mis Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'clipboard',
            'callback' => [$this, 'render_tab_mis_reportes'],
            'orden' => 41,
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
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        // KPIs
        $total_incidencias = 0;
        $mis_incidencias = 0;
        $pendientes = 0;
        $resueltas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $total_incidencias = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE es_publica = 1"
            );

            $mis_incidencias = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d",
                $user_id
            ));

            $pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('pendiente', 'en_revision')"
            );

            $resueltas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'resuelta'"
            );
        }

        ?>
        <div class="flavor-panel flavor-incidencias-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Incidencias del Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Reporta y sigue el estado de las incidencias urbanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-megaphone"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_incidencias); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total Reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-users"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_incidencias); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($pendientes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($resueltas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/incidencias/nueva/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Reportar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/incidencias/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php esc_html_e('Ver Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis reportes
     */
    public function render_tab_mis_reportes() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        $mis_incidencias = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $mis_incidencias = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla} WHERE usuario_id = %d ORDER BY created_at DESC LIMIT 10",
                $user_id
            ));
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'en_revision' => 'info',
            'en_progreso' => 'primary',
            'resuelta' => 'success',
            'cerrada' => 'secondary',
            'rechazada' => 'danger',
        ];

        ?>
        <div class="flavor-panel flavor-mis-incidencias-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Mis Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(home_url('/incidencias/nueva/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nuevo Reporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($mis_incidencias)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No has reportado ninguna incidencia todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/incidencias/nueva/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Reportar primera incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_incidencias as $incidencia): ?>
                                <tr>
                                    <td>#<?php echo esc_html($incidencia->id); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($incidencia->titulo, 8)); ?></td>
                                    <td><?php echo esc_html(ucfirst($incidencia->categoria)); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$incidencia->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia->estado))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($incidencia->created_at))); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(home_url('/incidencias/' . $incidencia->id)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flavor-panel-footer">
                    <a href="<?php echo esc_url(home_url('/incidencias/mis-incidencias/')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php esc_html_e('Ver todos mis reportes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

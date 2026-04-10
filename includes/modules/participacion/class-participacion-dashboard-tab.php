<?php
/**
 * Dashboard Tab para Participación Ciudadana
 *
 * @package FlavorPlatform
 * @subpackage Modules\Participacion
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Participacion_Dashboard_Tab {

    private static $instancia = null;

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function get_portal_url($action, array $args = []) {
        $url = Flavor_Platform_Helpers::get_action_url('participacion', trim($action, '/'));
        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    public function registrar_tabs($tabs) {
        $tabs['participacion-resumen'] = [
            'label' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'megaphone',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 60,
        ];

        $tabs['participacion-mis-propuestas'] = [
            'label' => __('Mis Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'lightbulb',
            'callback' => [$this, 'render_tab_mis_propuestas'],
            'orden' => 61,
        ];

        return $tabs;
    }

    public function render_tab_resumen() {
        $user_id = get_current_user_id();
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_participacion_propuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_participacion_votos';
        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';

        $total_propuestas = 0;
        $en_votacion = 0;
        $mis_votos = 0;
        $encuestas_activas = 0;
        $propuestas_destacadas = [];

        if (Flavor_Platform_Helpers::tabla_existe($tabla)) {
            $total_propuestas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado NOT IN ('borrador', 'rechazada')"
            );

            $en_votacion = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'en_votacion'"
            );

            $propuestas_destacadas = $wpdb->get_results(
                "SELECT * FROM {$tabla}
                 WHERE estado = 'en_votacion'
                 ORDER BY votos_favor DESC LIMIT 5"
            );
        }

        if ($user_id && Flavor_Platform_Helpers::tabla_existe($tabla_votos)) {
            $mis_votos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_votos} WHERE usuario_id = %d",
                $user_id
            ));
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_encuestas)) {
            $encuestas_activas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_encuestas}
                 WHERE estado = 'activa' AND fecha_fin >= %s",
                current_time('mysql')
            ));
        }

        ?>
        <div class="flavor-panel flavor-participacion-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e('Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Tu voz importa. Participa en las decisiones del barrio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-lightbulb"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_propuestas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-primary">
                    <span class="flavor-kpi-icon dashicons dashicons-chart-bar"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($en_votacion); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En Votación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_votos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-forms"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($encuestas_activas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Encuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($propuestas_destacadas)): ?>
                <div class="flavor-panel-section">
                    <h3><?php esc_html_e('Propuestas en votación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-propuestas-lista">
                        <?php foreach ($propuestas_destacadas as $propuesta): ?>
                            <div class="flavor-propuesta-item">
                                <div class="propuesta-votos">
                                    <span class="votos-count"><?php echo number_format_i18n($propuesta->votos_favor); ?></span>
                                    <span class="votos-label"><?php esc_html_e('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="propuesta-info">
                                    <h4><?php echo esc_html($propuesta->titulo); ?></h4>
                                    <p><?php echo esc_html(wp_trim_words($propuesta->descripcion, 15)); ?></p>
                                </div>
                                <a href="<?php echo esc_url($this->get_portal_url('detalle', ['propuesta_id' => $propuesta->id])); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Votar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url($this->get_portal_url('crear')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url($this->get_portal_url('propuestas')); ?>" class="flavor-btn flavor-btn-secondary">
                    <?php esc_html_e('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php if ($encuestas_activas > 0): ?>
                    <a href="<?php echo esc_url($this->get_portal_url('votaciones')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php esc_html_e('Responder encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_tab_mis_propuestas() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('Debes iniciar sesión para ver tus propuestas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p><a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_participacion_propuestas';

        $propuestas = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla)) {
            $propuestas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla} WHERE usuario_id = %d ORDER BY created_at DESC LIMIT 10",
                $user_id
            ));
        }

        $estados_colores = [
            'borrador' => 'secondary',
            'pendiente' => 'warning',
            'en_revision' => 'info',
            'aprobada' => 'primary',
            'rechazada' => 'danger',
            'en_votacion' => 'success',
            'aceptada' => 'success',
            'implementada' => 'success',
        ];

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('Mis Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url($this->get_portal_url('crear')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($propuestas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php esc_html_e('No has creado ninguna propuesta todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url($this->get_portal_url('crear')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Crear mi primera propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propuestas as $propuesta): ?>
                                <tr>
                                    <td><?php echo esc_html(wp_trim_words($propuesta->titulo, 8)); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$propuesta->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $propuesta->estado))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="votos-favor">+<?php echo number_format_i18n($propuesta->votos_favor); ?></span>
                                        <span class="votos-contra">-<?php echo number_format_i18n($propuesta->votos_contra); ?></span>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($propuesta->created_at))); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($this->get_portal_url('detalle', ['propuesta_id' => $propuesta->id])); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
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
}

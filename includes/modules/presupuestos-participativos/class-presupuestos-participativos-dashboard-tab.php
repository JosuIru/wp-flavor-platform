<?php
/**
 * Dashboard Tab para Presupuestos Participativos
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Presupuestos_Participativos_Dashboard_Tab {

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

    public function registrar_tabs($tabs) {
        $tabs['presupuestos-activos'] = [
            'label' => __('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'chart-pie',
            'callback' => [$this, 'render_tab_presupuestos'],
            'orden' => 62,
        ];

        $tabs['presupuestos-mis-propuestas'] = [
            'label' => __('Mis Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'admin-post',
            'callback' => [$this, 'render_tab_mis_propuestas'],
            'orden' => 63,
        ];

        return $tabs;
    }

    public function render_tab_presupuestos() {
        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        $proceso_activo = null;
        $total_propuestas = 0;
        $presupuesto_total = 0;
        $mis_votos = 0;
        $propuestas_destacadas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_procesos)) {
            $proceso_activo = $wpdb->get_row(
                "SELECT * FROM {$tabla_procesos}
                 WHERE estado = 'votacion' OR estado = 'propuestas'
                 ORDER BY fecha_inicio DESC LIMIT 1"
            );

            if ($proceso_activo) {
                $presupuesto_total = $proceso_activo->presupuesto_total;

                if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
                    $total_propuestas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_propuestas}
                         WHERE proceso_id = %d AND estado NOT IN ('borrador', 'rechazada')",
                        $proceso_activo->id
                    ));

                    $propuestas_destacadas = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$tabla_propuestas}
                         WHERE proceso_id = %d AND estado = 'en_votacion'
                         ORDER BY votos_total DESC LIMIT 5",
                        $proceso_activo->id
                    ));
                }

                if ($user_id && Flavor_Chat_Helpers::tabla_existe($tabla_votos)) {
                    $mis_votos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_votos}
                         WHERE proceso_id = %d AND usuario_id = %d",
                        $proceso_activo->id,
                        $user_id
                    ));
                }
            }
        }

        ?>
        <div class="flavor-panel flavor-presupuestos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Decide cómo se invierte el presupuesto de tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <?php if ($proceso_activo): ?>
                <div class="flavor-proceso-activo">
                    <h3><?php echo esc_html($proceso_activo->titulo); ?></h3>
                    <p class="proceso-fase">
                        <span class="flavor-badge flavor-badge-<?php echo $proceso_activo->estado === 'votacion' ? 'success' : 'primary'; ?>">
                            <?php echo $proceso_activo->estado === 'votacion' ? esc_html__('Fase de Votación', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Fase de Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    </p>
                </div>

                <div class="flavor-panel-kpis">
                    <div class="flavor-kpi-card flavor-kpi-primary">
                        <span class="flavor-kpi-icon dashicons dashicons-money-alt"></span>
                        <div class="flavor-kpi-content">
                            <span class="flavor-kpi-value"><?php echo number_format_i18n($presupuesto_total, 0); ?> €</span>
                            <span class="flavor-kpi-label"><?php esc_html_e('Presupuesto Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                    <div class="flavor-kpi-card">
                        <span class="flavor-kpi-icon dashicons dashicons-lightbulb"></span>
                        <div class="flavor-kpi-content">
                            <span class="flavor-kpi-value"><?php echo number_format_i18n($total_propuestas); ?></span>
                            <span class="flavor-kpi-label"><?php esc_html_e('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                    <div class="flavor-kpi-card">
                        <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                        <div class="flavor-kpi-content">
                            <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_votos); ?></span>
                            <span class="flavor-kpi-label"><?php esc_html_e('Mis Votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($propuestas_destacadas) && $proceso_activo->estado === 'votacion'): ?>
                    <div class="flavor-panel-section">
                        <h3><?php esc_html_e('Proyectos más votados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-propuestas-ranking">
                            <?php foreach ($propuestas_destacadas as $indice => $propuesta): ?>
                                <div class="flavor-propuesta-ranking-item">
                                    <span class="ranking-numero"><?php echo $indice + 1; ?></span>
                                    <div class="propuesta-info">
                                        <h4><?php echo esc_html($propuesta->titulo); ?></h4>
                                        <p class="flavor-text-muted">
                                            <?php echo esc_html(number_format_i18n($propuesta->presupuesto_estimado, 0)); ?> €
                                        </p>
                                    </div>
                                    <div class="propuesta-votos">
                                        <span class="votos-count"><?php echo number_format_i18n($propuesta->votos_total); ?></span>
                                        <span class="votos-label"><?php esc_html_e('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    </div>
                                    <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso_activo->slug . '/proyecto/' . $propuesta->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                        <?php esc_html_e('Votar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flavor-panel-actions">
                    <?php if ($proceso_activo->estado === 'propuestas'): ?>
                        <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso_activo->slug . '/nueva-propuesta')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php esc_html_e('Presentar Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso_activo->slug)); ?>" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Ver todos los proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <p><?php esc_html_e('No hay procesos de presupuestos participativos activos en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Ver procesos anteriores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_tab_mis_propuestas() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        $propuestas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas) && Flavor_Chat_Helpers::tabla_existe($tabla_procesos)) {
            $propuestas = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, pr.titulo as proceso_titulo, pr.slug as proceso_slug
                 FROM {$tabla_propuestas} p
                 JOIN {$tabla_procesos} pr ON p.proceso_id = pr.id
                 WHERE p.usuario_id = %d
                 ORDER BY p.created_at DESC LIMIT 10",
                $user_id
            ));
        }

        $estados_colores = [
            'borrador' => 'secondary',
            'pendiente' => 'warning',
            'en_revision' => 'info',
            'en_votacion' => 'primary',
            'aprobada' => 'success',
            'rechazada' => 'danger',
            'en_ejecucion' => 'success',
            'completada' => 'secondary',
        ];

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-admin-post"></span> <?php esc_html_e('Mis Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($propuestas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php esc_html_e('No has presentado ningún proyecto todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver procesos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propuestas as $propuesta): ?>
                                <tr>
                                    <td><?php echo esc_html(wp_trim_words($propuesta->titulo, 6)); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($propuesta->proceso_titulo, 4)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n($propuesta->presupuesto_estimado, 0)); ?> €</td>
                                    <td><?php echo number_format_i18n($propuesta->votos_total ?? 0); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$propuesta->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $propuesta->estado))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(home_url('/presupuestos/' . $propuesta->proceso_slug . '/proyecto/' . $propuesta->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                        <?php if ($propuesta->estado === 'borrador'): ?>
                                            <a href="<?php echo esc_url(home_url('/presupuestos/' . $propuesta->proceso_slug . '/editar/' . $propuesta->id)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                                                <?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </a>
                                        <?php endif; ?>
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

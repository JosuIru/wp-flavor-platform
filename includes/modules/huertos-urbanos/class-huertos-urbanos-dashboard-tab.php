<?php
/**
 * Dashboard Tab para Huertos Urbanos
 *
 * @package FlavorChatIA
 * @subpackage Modules\HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Huertos_Urbanos_Dashboard_Tab {

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
        $tabs['huertos-mapa'] = [
            'label' => __('Huertos', 'flavor-chat-ia'),
            'icon' => 'carrot',
            'callback' => [$this, 'render_tab_huertos'],
            'orden' => 55,
        ];

        $tabs['huertos-mi-parcela'] = [
            'label' => __('Mi Parcela', 'flavor-chat-ia'),
            'icon' => 'admin-site-alt3',
            'callback' => [$this, 'render_tab_mi_parcela'],
            'orden' => 56,
        ];

        return $tabs;
    }

    public function render_tab_huertos() {
        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';

        $total_huertos = 0;
        $parcelas_disponibles = 0;
        $huertos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
            $total_huertos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_huertos} WHERE estado = 'activo'"
            );

            $huertos = $wpdb->get_results(
                "SELECT * FROM {$tabla_huertos}
                 WHERE estado = 'activo'
                 ORDER BY nombre ASC LIMIT 6"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_parcelas)) {
            $parcelas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_parcelas} WHERE estado = 'disponible'"
            );
        }

        ?>
        <div class="flavor-panel flavor-huertos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-carrot"></span> <?php esc_html_e('Huertos Urbanos', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Cultiva tu propia comida en espacios comunitarios', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-site-alt3"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_huertos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Huertos Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($parcelas_disponibles); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Parcelas Libres', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($huertos)): ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($huertos as $huerto): ?>
                        <div class="flavor-card flavor-huerto-card">
                            <?php if (!empty($huerto->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($huerto->imagen_destacada); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($huerto->nombre); ?></h4>
                                <?php if (!empty($huerto->direccion)): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html(wp_trim_words($huerto->direccion, 6)); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-grid-view"></span>
                                    <?php printf(
                                        esc_html__('%d parcelas', 'flavor-chat-ia'),
                                        $huerto->total_parcelas ?? 0
                                    ); ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/huertos/' . $huerto->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Ver huerto', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No hay huertos urbanos disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/huertos-urbanos/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <?php esc_html_e('Ver todos los huertos', 'flavor-chat-ia'); ?>
                </a>
                <?php if ($parcelas_disponibles > 0): ?>
                    <a href="<?php echo esc_url(home_url('/huertos-urbanos/solicitar-parcela/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Solicitar parcela', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_tab_mi_parcela() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        $mis_parcelas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_asignaciones) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_parcelas) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
            $mis_parcelas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, p.numero as parcela_numero, p.superficie, h.nombre as huerto_nombre, h.slug as huerto_slug
                 FROM {$tabla_asignaciones} a
                 JOIN {$tabla_parcelas} p ON a.parcela_id = p.id
                 JOIN {$tabla_huertos} h ON p.huerto_id = h.id
                 WHERE a.usuario_id = %d AND a.estado = 'activa'
                 ORDER BY a.fecha_asignacion DESC",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Mi Parcela', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($mis_parcelas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <h3><?php esc_html_e('No tienes parcela asignada', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Solicita una parcela en uno de nuestros huertos urbanos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/huertos-urbanos/solicitar-parcela/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Solicitar parcela', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($mis_parcelas as $parcela): ?>
                        <div class="flavor-card flavor-parcela-card">
                            <div class="flavor-card-body">
                                <div class="parcela-header">
                                    <span class="parcela-numero">
                                        <?php printf(esc_html__('Parcela %s', 'flavor-chat-ia'), $parcela->parcela_numero); ?>
                                    </span>
                                    <span class="flavor-badge flavor-badge-success">
                                        <?php esc_html_e('Activa', 'flavor-chat-ia'); ?>
                                    </span>
                                </div>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <?php echo esc_html($parcela->huerto_nombre); ?>
                                </p>
                                <?php if ($parcela->superficie): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-editor-expand"></span>
                                        <?php echo esc_html($parcela->superficie); ?> m²
                                    </p>
                                <?php endif; ?>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php printf(
                                        esc_html__('Desde %s', 'flavor-chat-ia'),
                                        date_i18n('d/m/Y', strtotime($parcela->fecha_asignacion))
                                    ); ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/huertos/' . $parcela->huerto_slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver huerto', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/huertos/mi-parcela/diario')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Mi diario', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

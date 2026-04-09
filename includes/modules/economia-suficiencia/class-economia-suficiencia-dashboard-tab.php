<?php
/**
 * Dashboard Tab para Economía de la Suficiencia
 *
 * @package FlavorChatIA
 * @subpackage Modules\EconomiaSuficiencia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Economia_Suficiencia_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Economia_Suficiencia_Dashboard_Tab|null
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
     * @return Flavor_Economia_Suficiencia_Dashboard_Tab
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
        $tabs['suficiencia-resumen'] = [
            'label' => __('Suficiencia', 'flavor-platform'),
            'icon' => 'balance-scale',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 64,
        ];

        $tabs['suficiencia-recursos'] = [
            'label' => __('Mis Recursos', 'flavor-platform'),
            'icon' => 'chart-pie',
            'callback' => [$this, 'render_tab_recursos'],
            'orden' => 65,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de resumen
     */
    public function render_tab_resumen() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_compromisos = $wpdb->prefix . 'flavor_suficiencia_compromisos';
        $tabla_recursos = $wpdb->prefix . 'flavor_suficiencia_recursos';

        // KPIs
        $mis_compromisos = 0;
        $compromisos_cumplidos = 0;
        $recursos_compartidos = 0;
        $comunidad_participantes = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_compromisos)) {
            $mis_compromisos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_compromisos} WHERE usuario_id = %d",
                $user_id
            ));
            $compromisos_cumplidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_compromisos} WHERE usuario_id = %d AND estado = 'cumplido'",
                $user_id
            ));
            $comunidad_participantes = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_compromisos}"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_recursos)) {
            $recursos_compartidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_recursos} WHERE propietario_id = %d AND compartido = 1",
                $user_id
            ));
        }

        $porcentaje_cumplimiento = $mis_compromisos > 0 ? round(($compromisos_cumplidos / $mis_compromisos) * 100) : 0;

        ?>
        <div class="flavor-panel flavor-suficiencia-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Economía de la Suficiencia', 'flavor-platform'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Tener suficiente, no más de lo necesario', 'flavor-platform'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-flag"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_compromisos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Compromisos', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo $porcentaje_cumplimiento; ?>%</span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Cumplimiento', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-share"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($recursos_compartidos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Recursos Compartidos', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($comunidad_participantes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Participantes', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Principios de Suficiencia', 'flavor-platform'); ?></h3>
                <div class="flavor-principles-grid">
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-minus"></span>
                        <span><?php esc_html_e('Reducir lo innecesario', 'flavor-platform'); ?></span>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-update"></span>
                        <span><?php esc_html_e('Reutilizar antes de comprar', 'flavor-platform'); ?></span>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-share-alt2"></span>
                        <span><?php esc_html_e('Compartir recursos', 'flavor-platform'); ?></span>
                    </div>
                    <div class="flavor-principle">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php esc_html_e('Tiempo sobre consumo', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_suficiencia', 'nuevo-compromiso')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-flag"></span>
                    <?php esc_html_e('Nuevo Compromiso', 'flavor-platform'); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_suficiencia', 'biblioteca')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e('Biblioteca', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de recursos
     */
    public function render_tab_recursos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_suficiencia_recursos';

        $mis_recursos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_recursos)) {
            $mis_recursos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_recursos} WHERE propietario_id = %d ORDER BY fecha_registro DESC LIMIT 20",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-recursos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e('Mis Recursos', 'flavor-platform'); ?></h2>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_suficiencia', 'nuevo-recurso')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Añadir', 'flavor-platform'); ?>
                </a>
            </div>

            <?php if (empty($mis_recursos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-portfolio"></span>
                    <p><?php esc_html_e('No has registrado ningún recurso todavía.', 'flavor-platform'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Inventaría tus recursos para gestionar mejor tu suficiencia.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Recurso', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Categoría', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Compartido', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_recursos as $recurso): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($recurso->nombre); ?></strong></td>
                                    <td><?php echo esc_html($recurso->categoria ?? '-'); ?></td>
                                    <td>
                                        <?php if ($recurso->compartido): ?>
                                            <span class="dashicons dashicons-yes" style="color: #22c55e;"></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-no" style="color: #9ca3af;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($recurso->estado ?? 'activo'); ?>">
                                            <?php echo esc_html(ucfirst($recurso->estado ?? 'activo')); ?>
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
    }
}

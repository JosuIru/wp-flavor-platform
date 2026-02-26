<?php
/**
 * Dashboard Tab para Huella Ecológica
 *
 * @package FlavorChatIA
 * @subpackage Modules\HuellaEcologica
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_Huella_Ecologica_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Huella_Ecologica_Dashboard_Tab|null
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
     * @return Flavor_Huella_Ecologica_Dashboard_Tab
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
        $tabs['huella-resumen'] = [
            'label' => __('Huella Ecológica', 'flavor-chat-ia'),
            'icon' => 'globe',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 70,
        ];

        $tabs['huella-seguimiento'] = [
            'label' => __('Mi Seguimiento', 'flavor-chat-ia'),
            'icon' => 'chart-line',
            'callback' => [$this, 'render_tab_seguimiento'],
            'orden' => 71,
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
        $tabla_huella = $wpdb->prefix . 'flavor_huella_registros';
        $tabla_objetivos = $wpdb->prefix . 'flavor_huella_objetivos';

        // KPIs
        $mi_huella_actual = 0.0;
        $huella_media_comunidad = 0.0;
        $mis_registros = 0;
        $objetivos_cumplidos = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_huella)) {
            // Última huella calculada
            $mi_huella_actual = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT valor_co2 FROM {$tabla_huella} WHERE usuario_id = %d ORDER BY fecha DESC LIMIT 1",
                $user_id
            ));
            $mis_registros = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_huella} WHERE usuario_id = %d",
                $user_id
            ));
            $huella_media_comunidad = (float) $wpdb->get_var(
                "SELECT AVG(valor_co2) FROM {$tabla_huella} WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_objetivos)) {
            $objetivos_cumplidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_objetivos} WHERE usuario_id = %d AND estado = 'cumplido'",
                $user_id
            ));
        }

        // Comparación con media
        $comparacion = 0;
        if ($huella_media_comunidad > 0 && $mi_huella_actual > 0) {
            $comparacion = round((($mi_huella_actual - $huella_media_comunidad) / $huella_media_comunidad) * 100);
        }

        ?>
        <div class="flavor-panel flavor-huella-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-admin-site-alt"></span> <?php esc_html_e('Mi Huella Ecológica', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Mide, reduce y compensa tu impacto ambiental', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card <?php echo $mi_huella_actual <= $huella_media_comunidad ? 'flavor-kpi-success' : 'flavor-kpi-warning'; ?>">
                    <span class="flavor-kpi-icon dashicons dashicons-chart-pie"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mi_huella_actual, 1); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Kg CO₂ (último registro)', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($huella_media_comunidad, 1); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Media Comunidad', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clipboard"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_registros); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Registros', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($objetivos_cumplidos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Objetivos Cumplidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($comparacion !== 0): ?>
                <div class="flavor-panel-highlight <?php echo $comparacion <= 0 ? 'flavor-highlight-success' : 'flavor-highlight-warning'; ?>">
                    <?php if ($comparacion <= 0): ?>
                        <span class="dashicons dashicons-thumbs-up"></span>
                        <p><?php printf(esc_html__('Tu huella es un %d%% menor que la media de la comunidad.', 'flavor-chat-ia'), abs($comparacion)); ?></p>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning"></span>
                        <p><?php printf(esc_html__('Tu huella es un %d%% mayor que la media. ¡Sigue trabajando!', 'flavor-chat-ia'), $comparacion); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-section">
                <h3><?php esc_html_e('Áreas de Impacto', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-impact-grid">
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-car"></span>
                        <span><?php esc_html_e('Transporte', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-admin-home"></span>
                        <span><?php esc_html_e('Hogar', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-food"></span>
                        <span><?php esc_html_e('Alimentación', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-cart"></span>
                        <span><?php esc_html_e('Consumo', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/huella/calcular/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-calculator"></span>
                    <?php esc_html_e('Calcular Huella', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/huella/consejos/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php esc_html_e('Consejos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/huella/compensar/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-palmtree"></span>
                    <?php esc_html_e('Compensar', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de seguimiento
     */
    public function render_tab_seguimiento() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_huella = $wpdb->prefix . 'flavor_huella_registros';

        $registros = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_huella)) {
            $registros = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_huella} WHERE usuario_id = %d ORDER BY fecha DESC LIMIT 12",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-seguimiento-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Mi Seguimiento', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/huella/calcular/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Nuevo Registro', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($registros)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('Aún no has registrado tu huella ecológica.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Calcular tu huella es el primer paso para reducirla.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/huella/calcular/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Calcular mi huella', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('CO₂ (kg)', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Tendencia', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $anterior = null;
                            foreach ($registros as $registro):
                                $tendencia = '';
                                $tendencia_class = '';
                                if ($anterior !== null) {
                                    if ($registro->valor_co2 < $anterior) {
                                        $tendencia = '↓';
                                        $tendencia_class = 'flavor-trend-down';
                                    } elseif ($registro->valor_co2 > $anterior) {
                                        $tendencia = '↑';
                                        $tendencia_class = 'flavor-trend-up';
                                    } else {
                                        $tendencia = '→';
                                        $tendencia_class = 'flavor-trend-neutral';
                                    }
                                }
                                $anterior = $registro->valor_co2;
                            ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($registro->fecha))); ?></td>
                                    <td><?php echo esc_html($registro->categoria ?? '-'); ?></td>
                                    <td><strong><?php echo number_format_i18n($registro->valor_co2, 2); ?></strong></td>
                                    <td>
                                        <span class="flavor-trend <?php echo esc_attr($tendencia_class); ?>">
                                            <?php echo esc_html($tendencia); ?>
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

<?php
/**
 * Dashboard Tab del cliente para el modulo de Reciclaje
 *
 * @package FlavorPlatform
 * @subpackage Reciclaje
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar las tabs del dashboard del cliente
 * en el modulo de reciclaje
 */
class Flavor_Reciclaje_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Reciclaje_Dashboard_Tab
     */
    private static $instance = null;

    /**
     * Prefijo de la base de datos
     *
     * @var string
     */
    private $db_prefix;

    /**
     * Configuracion del modulo
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        global $wpdb;
        $this->db_prefix = $wpdb->prefix;
        $this->settings = get_option('flavor_reciclaje_settings', [
            'puntos_por_kg' => 10,
            'permite_canje_puntos' => true,
        ]);

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Reciclaje_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_reciclaje_dashboard_load_tab', [$this, 'ajax_load_tab_content']);
    }

    /**
     * Registra las tabs en el dashboard del cliente
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs con las nuevas agregadas
     */
    public function registrar_tabs($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $tabs['reciclaje-mis-aportes'] = [
            'label'    => __('Mis Aportes', 'flavor-platform'),
            'icon'     => 'dashicons-image-rotate',
            'callback' => [$this, 'render_tab_mis_aportes'],
            'priority' => 30,
            'group'    => 'reciclaje',
            'badge'    => $this->obtener_contador_aportes_pendientes(),
        ];

        $tabs['reciclaje-mis-puntos'] = [
            'label'    => __('Mis Puntos', 'flavor-platform'),
            'icon'     => 'dashicons-star-filled',
            'callback' => [$this, 'render_tab_mis_puntos'],
            'priority' => 31,
            'group'    => 'reciclaje',
            'badge'    => $this->obtener_puntos_disponibles(),
        ];

        $tabs['reciclaje-recompensas'] = [
            'label'    => __('Recompensas', 'flavor-platform'),
            'icon'     => 'dashicons-awards',
            'callback' => [$this, 'render_tab_recompensas'],
            'priority' => 32,
            'group'    => 'reciclaje',
        ];

        $tabs['reciclaje-estadisticas'] = [
            'label'    => __('Mi Impacto', 'flavor-platform'),
            'icon'     => 'dashicons-chart-area',
            'callback' => [$this, 'render_tab_estadisticas'],
            'priority' => 33,
            'group'    => 'reciclaje',
        ];

        return $tabs;
    }

    /**
     * Encola los assets necesarios para las tabs
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        $version = defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : '1.0.0';

        wp_enqueue_style(
            'flavor-reciclaje-dashboard',
            plugins_url('assets/css/dashboard-tab.css', __FILE__),
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-reciclaje-dashboard',
            plugins_url('assets/js/dashboard-tab.js', __FILE__),
            ['jquery', 'wp-util'],
            $version,
            true
        );

        wp_localize_script('flavor-reciclaje-dashboard', 'flavorReciclajeDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('reciclaje_dashboard_nonce'),
            'i18n'    => [
                'loading'      => __('Cargando...', 'flavor-platform'),
                'error'        => __('Error al cargar los datos', 'flavor-platform'),
                'confirmCanje' => __('Confirmar canje de puntos?', 'flavor-platform'),
                'success'      => __('Operacion completada', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Renderiza la tab de Mis Aportes
     */
    public function render_tab_mis_aportes() {
        $usuario_id = get_current_user_id();
        $aportes = $this->obtener_aportes_usuario($usuario_id);
        $resumen_mensual = $this->obtener_resumen_mensual($usuario_id);

        ?>
        <div class="flavor-dashboard-tab reciclaje-mis-aportes">
            <div class="tab-header">
                <h2><?php esc_html_e('Mis Aportes de Reciclaje', 'flavor-platform'); ?></h2>
                <p class="descripcion"><?php esc_html_e('Historial de todos tus depositos de reciclaje', 'flavor-platform'); ?></p>
            </div>

            <!-- Resumen del mes -->
            <div class="resumen-mensual">
                <h3><?php esc_html_e('Este Mes', 'flavor-platform'); ?></h3>
                <div class="stats-grid stats-grid-4">
                    <div class="stat-card">
                        <span class="stat-icon dashicons dashicons-image-rotate"></span>
                        <span class="stat-valor"><?php echo esc_html(number_format_i18n($resumen_mensual['depositos_mes'])); ?></span>
                        <span class="stat-label"><?php esc_html_e('Depositos', 'flavor-platform'); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon dashicons dashicons-cart"></span>
                        <span class="stat-valor"><?php echo esc_html(number_format_i18n($resumen_mensual['kg_mes'], 2)); ?> kg</span>
                        <span class="stat-label"><?php esc_html_e('Material', 'flavor-platform'); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon dashicons dashicons-star-filled"></span>
                        <span class="stat-valor"><?php echo esc_html(number_format_i18n($resumen_mensual['puntos_mes'])); ?></span>
                        <span class="stat-label"><?php esc_html_e('Puntos ganados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon dashicons dashicons-yes-alt"></span>
                        <span class="stat-valor"><?php echo esc_html($resumen_mensual['verificados_mes']); ?></span>
                        <span class="stat-label"><?php esc_html_e('Verificados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Listado de aportes -->
            <div class="listado-aportes">
                <h3><?php esc_html_e('Historial de Depositos', 'flavor-platform'); ?></h3>

                <?php if (!empty($aportes)): ?>
                    <div class="aportes-tabla-wrapper">
                        <table class="aportes-tabla">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                                    <th><?php esc_html_e('Punto de Reciclaje', 'flavor-platform'); ?></th>
                                    <th><?php esc_html_e('Material', 'flavor-platform'); ?></th>
                                    <th><?php esc_html_e('Cantidad', 'flavor-platform'); ?></th>
                                    <th><?php esc_html_e('Puntos', 'flavor-platform'); ?></th>
                                    <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aportes as $aporte): ?>
                                    <tr class="aporte-row <?php echo $aporte->verificado ? 'verificado' : 'pendiente'; ?>">
                                        <td class="fecha">
                                            <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($aporte->fecha_deposito))); ?>
                                        </td>
                                        <td class="punto">
                                            <?php echo esc_html($aporte->punto_nombre ?: __('Punto eliminado', 'flavor-platform')); ?>
                                        </td>
                                        <td class="material">
                                            <span class="material-badge material-<?php echo esc_attr(sanitize_title($aporte->tipo_material)); ?>">
                                                <?php echo esc_html(ucfirst($aporte->tipo_material)); ?>
                                            </span>
                                        </td>
                                        <td class="cantidad">
                                            <?php echo esc_html(number_format_i18n($aporte->cantidad_kg, 2)); ?> kg
                                        </td>
                                        <td class="puntos">
                                            <strong>+<?php echo esc_html(number_format_i18n($aporte->puntos_ganados)); ?></strong>
                                        </td>
                                        <td class="estado">
                                            <?php if ($aporte->verificado): ?>
                                                <span class="estado-badge estado-verificado">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <?php esc_html_e('Verificado', 'flavor-platform'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="estado-badge estado-pendiente">
                                                    <span class="dashicons dashicons-clock"></span>
                                                    <?php esc_html_e('Pendiente', 'flavor-platform'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <p><?php esc_html_e('Aun no has realizado ningun deposito de reciclaje.', 'flavor-platform'); ?></p>
                        <a href="<?php echo esc_url(get_permalink(get_option('flavor_reciclaje_puntos_page'))); ?>" class="button button-primary">
                            <?php esc_html_e('Encontrar puntos de reciclaje', 'flavor-platform'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la tab de Mis Puntos
     */
    public function render_tab_mis_puntos() {
        $usuario_id = get_current_user_id();
        $resumen_puntos = $this->obtener_resumen_puntos($usuario_id);
        $ranking_usuario = $this->obtener_ranking_usuario($usuario_id);
        $historial_puntos = $this->obtener_historial_puntos($usuario_id);

        ?>
        <div class="flavor-dashboard-tab reciclaje-mis-puntos">
            <div class="tab-header">
                <h2><?php esc_html_e('Mis Puntos de Reciclaje', 'flavor-platform'); ?></h2>
                <p class="descripcion"><?php esc_html_e('Puntos acumulados y tu posicion en el ranking comunitario', 'flavor-platform'); ?></p>
            </div>

            <!-- Tarjeta principal de puntos -->
            <div class="puntos-hero">
                <div class="puntos-disponibles">
                    <span class="puntos-numero"><?php echo esc_html(number_format_i18n($resumen_puntos['disponibles'])); ?></span>
                    <span class="puntos-label"><?php esc_html_e('Puntos disponibles', 'flavor-platform'); ?></span>
                </div>
                <div class="puntos-detalles">
                    <div class="detalle">
                        <span class="valor"><?php echo esc_html(number_format_i18n($resumen_puntos['total_ganados'])); ?></span>
                        <span class="label"><?php esc_html_e('Total ganados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="detalle">
                        <span class="valor"><?php echo esc_html(number_format_i18n($resumen_puntos['canjeados'])); ?></span>
                        <span class="label"><?php esc_html_e('Canjeados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Ranking del usuario -->
            <div class="ranking-section">
                <h3><?php esc_html_e('Tu Posicion en el Ranking', 'flavor-platform'); ?></h3>
                <div class="ranking-card">
                    <div class="ranking-posicion">
                        <span class="posicion-numero">#<?php echo esc_html($ranking_usuario['posicion']); ?></span>
                        <span class="posicion-label"><?php esc_html_e('de', 'flavor-platform'); ?> <?php echo esc_html($ranking_usuario['total_participantes']); ?></span>
                    </div>
                    <div class="ranking-progreso">
                        <div class="progreso-bar">
                            <div class="progreso-fill" style="width: <?php echo esc_attr($ranking_usuario['percentil']); ?>%"></div>
                        </div>
                        <span class="progreso-texto">
                            <?php
                            printf(
                                esc_html__('Estas en el top %d%% de recicladores', 'flavor-platform'),
                                100 - intval($ranking_usuario['percentil'])
                            );
                            ?>
                        </span>
                    </div>

                    <!-- Badges de logros -->
                    <?php $badges = $this->obtener_badges_usuario($usuario_id); ?>
                    <?php if (!empty($badges)): ?>
                        <div class="badges-container">
                            <h4><?php esc_html_e('Insignias Obtenidas', 'flavor-platform'); ?></h4>
                            <div class="badges-grid">
                                <?php foreach ($badges as $badge): ?>
                                    <div class="badge <?php echo esc_attr($badge['clase']); ?>" title="<?php echo esc_attr($badge['descripcion']); ?>">
                                        <span class="badge-icon"><?php echo esc_html($badge['icono']); ?></span>
                                        <span class="badge-nombre"><?php echo esc_html($badge['nombre']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 5 del ranking -->
            <div class="top-ranking">
                <h3><?php esc_html_e('Top 5 Recicladores', 'flavor-platform'); ?></h3>
                <?php $top_usuarios = $this->obtener_top_usuarios(5); ?>
                <ol class="top-lista">
                    <?php foreach ($top_usuarios as $posicion => $usuario_ranking): ?>
                        <li class="top-item <?php echo $usuario_ranking->usuario_id == $usuario_id ? 'es-usuario-actual' : ''; ?>">
                            <span class="top-posicion"><?php echo $posicion + 1; ?></span>
                            <span class="top-avatar">
                                <?php echo get_avatar($usuario_ranking->usuario_id, 40); ?>
                            </span>
                            <span class="top-nombre"><?php echo esc_html($usuario_ranking->display_name); ?></span>
                            <span class="top-puntos"><?php echo esc_html(number_format_i18n($usuario_ranking->total_puntos)); ?> pts</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <!-- Grafico de progreso mensual -->
            <div class="grafico-progreso">
                <h3><?php esc_html_e('Puntos por Mes', 'flavor-platform'); ?></h3>
                <div class="grafico-container">
                    <?php $puntos_mensuales = $this->obtener_puntos_mensuales($usuario_id, 6); ?>
                    <div class="grafico-barras">
                        <?php
                        $maximo_mensual = max(array_column($puntos_mensuales, 'puntos'));
                        $maximo_mensual = $maximo_mensual > 0 ? $maximo_mensual : 1;
                        foreach ($puntos_mensuales as $mes_data):
                            $porcentaje_altura = ($mes_data['puntos'] / $maximo_mensual) * 100;
                        ?>
                            <div class="barra-mes">
                                <div class="barra" style="height: <?php echo esc_attr($porcentaje_altura); ?>%">
                                    <span class="barra-valor"><?php echo esc_html($mes_data['puntos']); ?></span>
                                </div>
                                <span class="barra-label"><?php echo esc_html($mes_data['mes_corto']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la tab de Recompensas
     */
    public function render_tab_recompensas() {
        $usuario_id = get_current_user_id();
        $puntos_disponibles = $this->obtener_puntos_disponibles();
        $recompensas_disponibles = $this->obtener_recompensas_disponibles();
        $canjes_usuario = $this->obtener_canjes_usuario($usuario_id);

        ?>
        <div class="flavor-dashboard-tab reciclaje-recompensas">
            <div class="tab-header">
                <h2><?php esc_html_e('Recompensas de Reciclaje', 'flavor-platform'); ?></h2>
                <p class="descripcion"><?php esc_html_e('Canjea tus puntos por increibles recompensas', 'flavor-platform'); ?></p>
            </div>

            <!-- Puntos disponibles -->
            <div class="puntos-banner">
                <span class="puntos-icono dashicons dashicons-star-filled"></span>
                <span class="puntos-texto">
                    <?php esc_html_e('Tienes', 'flavor-platform'); ?>
                    <strong><?php echo esc_html(number_format_i18n($puntos_disponibles)); ?></strong>
                    <?php esc_html_e('puntos disponibles para canjear', 'flavor-platform'); ?>
                </span>
            </div>

            <!-- Recompensas disponibles -->
            <div class="recompensas-disponibles">
                <h3><?php esc_html_e('Recompensas Disponibles', 'flavor-platform'); ?></h3>

                <?php if (!empty($recompensas_disponibles)): ?>
                    <div class="recompensas-grid">
                        <?php foreach ($recompensas_disponibles as $recompensa): ?>
                            <?php
                            $puntos_necesarios = intval(get_post_meta($recompensa->ID, '_puntos_necesarios', true));
                            $stock_disponible = intval(get_post_meta($recompensa->ID, '_stock_disponible', true));
                            $puede_canjear = $puntos_disponibles >= $puntos_necesarios && ($stock_disponible === -1 || $stock_disponible > 0);
                            ?>
                            <div class="recompensa-card <?php echo $puede_canjear ? 'disponible' : 'no-disponible'; ?>">
                                <div class="recompensa-imagen">
                                    <?php if (has_post_thumbnail($recompensa->ID)): ?>
                                        <?php echo get_the_post_thumbnail($recompensa->ID, 'medium'); ?>
                                    <?php else: ?>
                                        <div class="placeholder-imagen">
                                            <span class="dashicons dashicons-awards"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="recompensa-contenido">
                                    <h4 class="recompensa-titulo"><?php echo esc_html($recompensa->post_title); ?></h4>
                                    <p class="recompensa-descripcion"><?php echo esc_html(wp_trim_words($recompensa->post_excerpt ?: $recompensa->post_content, 20)); ?></p>
                                    <div class="recompensa-footer">
                                        <span class="recompensa-puntos">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <?php echo esc_html(number_format_i18n($puntos_necesarios)); ?> <?php esc_html_e('puntos', 'flavor-platform'); ?>
                                        </span>
                                        <?php if ($puede_canjear): ?>
                                            <button class="button button-primary btn-canjear" data-recompensa-id="<?php echo esc_attr($recompensa->ID); ?>" data-puntos="<?php echo esc_attr($puntos_necesarios); ?>">
                                                <?php esc_html_e('Canjear', 'flavor-platform'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="puntos-faltantes">
                                                <?php
                                                $faltantes = $puntos_necesarios - $puntos_disponibles;
                                                printf(esc_html__('Faltan %s pts', 'flavor-platform'), number_format_i18n($faltantes));
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="dashicons dashicons-awards"></span>
                        <p><?php esc_html_e('No hay recompensas disponibles en este momento.', 'flavor-platform'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historial de canjes -->
            <div class="historial-canjes">
                <h3><?php esc_html_e('Mis Canjes', 'flavor-platform'); ?></h3>

                <?php if (!empty($canjes_usuario)): ?>
                    <div class="canjes-lista">
                        <?php foreach ($canjes_usuario as $canje): ?>
                            <div class="canje-item">
                                <div class="canje-icono">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="canje-info">
                                    <span class="canje-titulo"><?php echo esc_html($canje['titulo']); ?></span>
                                    <span class="canje-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($canje['fecha']))); ?></span>
                                </div>
                                <span class="canje-puntos">-<?php echo esc_html(number_format_i18n($canje['puntos'])); ?> pts</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="sin-canjes"><?php esc_html_e('Aun no has realizado ningun canje.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la tab de Estadisticas e Impacto Ambiental
     */
    public function render_tab_estadisticas() {
        $usuario_id = get_current_user_id();
        $impacto = $this->calcular_impacto_ambiental($usuario_id);
        $estadisticas_material = $this->obtener_estadisticas_por_material($usuario_id);
        $comparativa = $this->obtener_comparativa_comunidad($usuario_id);

        ?>
        <div class="flavor-dashboard-tab reciclaje-estadisticas">
            <div class="tab-header">
                <h2><?php esc_html_e('Mi Impacto Ambiental', 'flavor-platform'); ?></h2>
                <p class="descripcion"><?php esc_html_e('Descubre el impacto positivo de tu reciclaje en el medio ambiente', 'flavor-platform'); ?></p>
            </div>

            <!-- Impacto ambiental hero -->
            <div class="impacto-hero">
                <div class="impacto-total">
                    <span class="impacto-numero"><?php echo esc_html(number_format_i18n($impacto['kg_total'], 1)); ?></span>
                    <span class="impacto-unidad">kg</span>
                    <span class="impacto-label"><?php esc_html_e('Total reciclado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <!-- Metricas de impacto -->
            <div class="impacto-metricas">
                <h3><?php esc_html_e('Tu Contribucion Equivale A', 'flavor-platform'); ?></h3>
                <div class="metricas-grid">
                    <div class="metrica-card">
                        <span class="metrica-icono">&#127795;</span>
                        <span class="metrica-valor"><?php echo esc_html(number_format_i18n($impacto['arboles_salvados'], 1)); ?></span>
                        <span class="metrica-label"><?php esc_html_e('Arboles salvados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="metrica-card">
                        <span class="metrica-icono">&#128167;</span>
                        <span class="metrica-valor"><?php echo esc_html(number_format_i18n($impacto['litros_agua_ahorrados'])); ?></span>
                        <span class="metrica-label"><?php esc_html_e('Litros de agua ahorrados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="metrica-card">
                        <span class="metrica-icono">&#9889;</span>
                        <span class="metrica-valor"><?php echo esc_html(number_format_i18n($impacto['energia_kwh'], 1)); ?></span>
                        <span class="metrica-label"><?php esc_html_e('kWh de energia ahorrados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="metrica-card">
                        <span class="metrica-icono">&#127757;</span>
                        <span class="metrica-valor"><?php echo esc_html(number_format_i18n($impacto['co2_evitado'], 1)); ?></span>
                        <span class="metrica-label"><?php esc_html_e('kg CO2 evitados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Desglose por material -->
            <div class="desglose-materiales">
                <h3><?php esc_html_e('Desglose por Material', 'flavor-platform'); ?></h3>
                <?php if (!empty($estadisticas_material)): ?>
                    <div class="materiales-lista">
                        <?php
                        $total_kg = array_sum(array_column($estadisticas_material, 'total_kg'));
                        foreach ($estadisticas_material as $material):
                            $porcentaje = $total_kg > 0 ? ($material['total_kg'] / $total_kg) * 100 : 0;
                            $clase_color = $this->obtener_color_material($material['tipo_material']);
                        ?>
                            <div class="material-item">
                                <div class="material-info">
                                    <span class="material-nombre"><?php echo esc_html(ucfirst($material['tipo_material'])); ?></span>
                                    <span class="material-stats">
                                        <?php echo esc_html(number_format_i18n($material['total_kg'], 2)); ?> kg
                                        (<?php echo esc_html($material['num_depositos']); ?> <?php esc_html_e('depositos', 'flavor-platform'); ?>)
                                    </span>
                                </div>
                                <div class="material-barra">
                                    <div class="barra-fill <?php echo esc_attr($clase_color); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                                </div>
                                <span class="material-porcentaje"><?php echo esc_html(number_format_i18n($porcentaje, 1)); ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="sin-datos"><?php esc_html_e('Aun no tienes depositos verificados.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Comparativa con la comunidad -->
            <div class="comparativa-comunidad">
                <h3><?php esc_html_e('Comparativa con la Comunidad', 'flavor-platform'); ?></h3>
                <div class="comparativa-grid">
                    <div class="comparativa-item">
                        <span class="comparativa-label"><?php esc_html_e('Tu promedio mensual', 'flavor-platform'); ?></span>
                        <span class="comparativa-valor tu-valor"><?php echo esc_html(number_format_i18n($comparativa['promedio_usuario'], 2)); ?> kg</span>
                    </div>
                    <div class="comparativa-item">
                        <span class="comparativa-label"><?php esc_html_e('Promedio comunidad', 'flavor-platform'); ?></span>
                        <span class="comparativa-valor comunidad"><?php echo esc_html(number_format_i18n($comparativa['promedio_comunidad'], 2)); ?> kg</span>
                    </div>
                    <div class="comparativa-item destacado">
                        <?php
                        $diferencia = $comparativa['promedio_usuario'] - $comparativa['promedio_comunidad'];
                        $clase_diferencia = $diferencia >= 0 ? 'positiva' : 'negativa';
                        $icono_diferencia = $diferencia >= 0 ? 'arrow-up-alt' : 'arrow-down-alt';
                        ?>
                        <span class="comparativa-label"><?php esc_html_e('Tu rendimiento', 'flavor-platform'); ?></span>
                        <span class="comparativa-valor diferencia <?php echo esc_attr($clase_diferencia); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($icono_diferencia); ?>"></span>
                            <?php echo esc_html(number_format_i18n(abs($diferencia), 2)); ?> kg
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grafico de evolucion -->
            <div class="evolucion-anual">
                <h3><?php esc_html_e('Tu Evolucion Anual', 'flavor-platform'); ?></h3>
                <?php $evolucion = $this->obtener_evolucion_anual($usuario_id); ?>
                <div class="evolucion-grafico">
                    <?php
                    $maximo_anual = max(array_column($evolucion, 'kg'));
                    $maximo_anual = $maximo_anual > 0 ? $maximo_anual : 1;
                    foreach ($evolucion as $mes_data):
                        $porcentaje_altura = ($mes_data['kg'] / $maximo_anual) * 100;
                    ?>
                        <div class="evolucion-barra">
                            <div class="barra" style="height: <?php echo esc_attr($porcentaje_altura); ?>%">
                                <span class="barra-tooltip"><?php echo esc_html(number_format_i18n($mes_data['kg'], 1)); ?> kg</span>
                            </div>
                            <span class="barra-mes"><?php echo esc_html($mes_data['mes_corto']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // METODOS DE CONSULTA DE DATOS
    // =========================================================================

    /**
     * Obtiene los aportes/depositos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Limite de resultados
     * @return array
     */
    private function obtener_aportes_usuario($usuario_id, $limite = 50) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';
        $tabla_puntos = $this->db_prefix . 'flavor_reciclaje_puntos';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, p.nombre as punto_nombre
             FROM {$tabla_depositos} d
             LEFT JOIN {$tabla_puntos} p ON d.punto_reciclaje_id = p.id
             WHERE d.usuario_id = %d
             ORDER BY d.fecha_deposito DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));
    }

    /**
     * Obtiene el resumen mensual del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_resumen_mensual($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';
        $primer_dia_mes = date('Y-m-01 00:00:00');

        $resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as depositos_mes,
                COALESCE(SUM(cantidad_kg), 0) as kg_mes,
                COALESCE(SUM(puntos_ganados), 0) as puntos_mes,
                SUM(CASE WHEN verificado = 1 THEN 1 ELSE 0 END) as verificados_mes
             FROM {$tabla_depositos}
             WHERE usuario_id = %d AND fecha_deposito >= %s",
            $usuario_id,
            $primer_dia_mes
        ));

        return [
            'depositos_mes'   => intval($resultado->depositos_mes ?? 0),
            'kg_mes'          => floatval($resultado->kg_mes ?? 0),
            'puntos_mes'      => intval($resultado->puntos_mes ?? 0),
            'verificados_mes' => intval($resultado->verificados_mes ?? 0),
        ];
    }

    /**
     * Obtiene el contador de aportes pendientes de verificacion
     *
     * @return int
     */
    private function obtener_contador_aportes_pendientes() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_depositos} WHERE usuario_id = %d AND verificado = 0",
            $usuario_id
        )));
    }

    /**
     * Obtiene los puntos disponibles del usuario
     *
     * @return int
     */
    private function obtener_puntos_disponibles() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        $total_ganados = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(puntos_ganados), 0) FROM {$tabla_depositos} WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        )));

        $canjeados = intval(get_user_meta($usuario_id, '_reciclaje_puntos_canjeados', true));

        return max(0, $total_ganados - $canjeados);
    }

    /**
     * Obtiene el resumen de puntos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_resumen_puntos($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        $total_ganados = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(puntos_ganados), 0) FROM {$tabla_depositos} WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        )));

        $canjeados = intval(get_user_meta($usuario_id, '_reciclaje_puntos_canjeados', true));

        return [
            'total_ganados' => $total_ganados,
            'canjeados'     => $canjeados,
            'disponibles'   => max(0, $total_ganados - $canjeados),
        ];
    }

    /**
     * Obtiene el ranking del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_ranking_usuario($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        // Total de participantes con depositos verificados
        $total_participantes = intval($wpdb->get_var(
            "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_depositos} WHERE verificado = 1"
        ));

        if ($total_participantes === 0) {
            return [
                'posicion'            => 1,
                'total_participantes' => 1,
                'percentil'           => 100,
            ];
        }

        // Puntos del usuario
        $puntos_usuario = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(puntos_ganados), 0) FROM {$tabla_depositos} WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        )));

        // Cuantos usuarios tienen mas puntos
        $usuarios_con_mas_puntos = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id)
             FROM {$tabla_depositos}
             WHERE verificado = 1
             GROUP BY usuario_id
             HAVING SUM(puntos_ganados) > %d",
            $puntos_usuario
        )));

        // La posicion es el numero de usuarios con mas puntos + 1
        // Si hay 0 usuarios con mas puntos, estamos en posicion 1
        $posicion = $usuarios_con_mas_puntos + 1;

        $percentil = $total_participantes > 0 ? (($posicion / $total_participantes) * 100) : 100;

        return [
            'posicion'            => $posicion,
            'total_participantes' => $total_participantes,
            'percentil'           => round($percentil, 1),
        ];
    }

    /**
     * Obtiene el historial de puntos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Limite de resultados
     * @return array
     */
    private function obtener_historial_puntos($usuario_id, $limite = 20) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_material, puntos_ganados, fecha_deposito, verificado
             FROM {$tabla_depositos}
             WHERE usuario_id = %d
             ORDER BY fecha_deposito DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));
    }

    /**
     * Obtiene los badges/insignias del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_badges_usuario($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_depositos,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_ganados), 0) as total_puntos,
                COUNT(DISTINCT tipo_material) as tipos_diferentes
             FROM {$tabla_depositos}
             WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        ));

        $badges = [];

        // Badge por primer deposito
        if ($estadisticas->total_depositos >= 1) {
            $badges[] = [
                'nombre'      => __('Iniciado', 'flavor-platform'),
                'icono'       => '&#127793;',
                'descripcion' => __('Primer deposito realizado', 'flavor-platform'),
                'clase'       => 'badge-bronce',
            ];
        }

        // Badge por 10 depositos
        if ($estadisticas->total_depositos >= 10) {
            $badges[] = [
                'nombre'      => __('Constante', 'flavor-platform'),
                'icono'       => '&#127942;',
                'descripcion' => __('10 depositos realizados', 'flavor-platform'),
                'clase'       => 'badge-plata',
            ];
        }

        // Badge por 50 depositos
        if ($estadisticas->total_depositos >= 50) {
            $badges[] = [
                'nombre'      => __('Experto', 'flavor-platform'),
                'icono'       => '&#127941;',
                'descripcion' => __('50 depositos realizados', 'flavor-platform'),
                'clase'       => 'badge-oro',
            ];
        }

        // Badge por kg reciclados
        if ($estadisticas->total_kg >= 100) {
            $badges[] = [
                'nombre'      => __('Eco Guerrero', 'flavor-platform'),
                'icono'       => '&#127795;',
                'descripcion' => __('100 kg reciclados', 'flavor-platform'),
                'clase'       => 'badge-verde',
            ];
        }

        // Badge por diversidad de materiales
        if ($estadisticas->tipos_diferentes >= 5) {
            $badges[] = [
                'nombre'      => __('Diversificado', 'flavor-platform'),
                'icono'       => '&#127752;',
                'descripcion' => __('5 tipos de materiales diferentes', 'flavor-platform'),
                'clase'       => 'badge-arcoiris',
            ];
        }

        // Badge por puntos
        if ($estadisticas->total_puntos >= 1000) {
            $badges[] = [
                'nombre'      => __('Mil Puntos', 'flavor-platform'),
                'icono'       => '&#11088;',
                'descripcion' => __('1000 puntos acumulados', 'flavor-platform'),
                'clase'       => 'badge-estrella',
            ];
        }

        return $badges;
    }

    /**
     * Obtiene los top usuarios del ranking
     *
     * @param int $limite Limite de usuarios
     * @return array
     */
    private function obtener_top_usuarios($limite = 5) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.usuario_id, u.display_name, SUM(d.puntos_ganados) as total_puntos
             FROM {$tabla_depositos} d
             JOIN {$wpdb->users} u ON d.usuario_id = u.ID
             WHERE d.verificado = 1
             GROUP BY d.usuario_id
             ORDER BY total_puntos DESC
             LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtiene los puntos mensuales del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $meses Numero de meses hacia atras
     * @return array
     */
    private function obtener_puntos_mensuales($usuario_id, $meses = 6) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        $datos_mensuales = [];

        for ($indice_mes = $meses - 1; $indice_mes >= 0; $indice_mes--) {
            $fecha_inicio = date('Y-m-01', strtotime("-{$indice_mes} months"));
            $fecha_fin = date('Y-m-t', strtotime("-{$indice_mes} months"));

            $puntos_del_mes = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(puntos_ganados), 0)
                 FROM {$tabla_depositos}
                 WHERE usuario_id = %d AND verificado = 1
                 AND fecha_deposito BETWEEN %s AND %s",
                $usuario_id,
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59'
            )));

            $datos_mensuales[] = [
                'mes'       => date_i18n('F Y', strtotime($fecha_inicio)),
                'mes_corto' => date_i18n('M', strtotime($fecha_inicio)),
                'puntos'    => $puntos_del_mes,
            ];
        }

        return $datos_mensuales;
    }

    /**
     * Obtiene las recompensas disponibles
     *
     * @return array
     */
    private function obtener_recompensas_disponibles() {
        $argumentos_consulta = [
            'post_type'      => 'recompensa_reciclaje',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_puntos_necesarios',
            'order'          => 'ASC',
        ];

        return get_posts($argumentos_consulta);
    }

    /**
     * Obtiene los canjes del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_canjes_usuario($usuario_id) {
        // Los canjes se guardan como user_meta
        // Formato: array de ['recompensa_id' => X, 'puntos' => Y, 'fecha' => Z]
        $canjes_guardados = get_user_meta($usuario_id, '_reciclaje_historial_canjes', true);

        if (empty($canjes_guardados) || !is_array($canjes_guardados)) {
            return [];
        }

        $canjes_con_titulo = [];
        foreach ($canjes_guardados as $canje) {
            $titulo_recompensa = get_the_title($canje['recompensa_id']);
            if (!$titulo_recompensa) {
                $titulo_recompensa = __('Recompensa eliminada', 'flavor-platform');
            }
            $canjes_con_titulo[] = [
                'titulo' => $titulo_recompensa,
                'puntos' => $canje['puntos'],
                'fecha'  => $canje['fecha'],
            ];
        }

        return array_reverse($canjes_con_titulo); // Mas recientes primero
    }

    /**
     * Calcula el impacto ambiental del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function calcular_impacto_ambiental($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        // Obtener kg por tipo de material
        $materiales = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_material, SUM(cantidad_kg) as total_kg
             FROM {$tabla_depositos}
             WHERE usuario_id = %d AND verificado = 1
             GROUP BY tipo_material",
            $usuario_id
        ));

        $total_kg = 0;
        $arboles_salvados = 0;
        $litros_agua_ahorrados = 0;
        $energia_kwh = 0;
        $co2_evitado = 0;

        // Factores de conversion aproximados
        $factores_impacto = [
            'papel'       => ['arboles' => 0.017, 'agua' => 30, 'energia' => 4, 'co2' => 0.9],
            'plastico'    => ['arboles' => 0, 'agua' => 100, 'energia' => 5.8, 'co2' => 1.5],
            'vidrio'      => ['arboles' => 0, 'agua' => 50, 'energia' => 0.4, 'co2' => 0.3],
            'organico'    => ['arboles' => 0, 'agua' => 10, 'energia' => 0.1, 'co2' => 0.2],
            'electronico' => ['arboles' => 0, 'agua' => 200, 'energia' => 20, 'co2' => 2.0],
            'ropa'        => ['arboles' => 0, 'agua' => 2700, 'energia' => 3.5, 'co2' => 1.0],
            'aceite'      => ['arboles' => 0, 'agua' => 1000, 'energia' => 0.5, 'co2' => 0.5],
            'pilas'       => ['arboles' => 0, 'agua' => 50, 'energia' => 10, 'co2' => 0.8],
        ];

        foreach ($materiales as $material) {
            $tipo_material = strtolower($material->tipo_material);
            $kg_material = floatval($material->total_kg);
            $total_kg += $kg_material;

            if (isset($factores_impacto[$tipo_material])) {
                $factores = $factores_impacto[$tipo_material];
                $arboles_salvados += $kg_material * $factores['arboles'];
                $litros_agua_ahorrados += $kg_material * $factores['agua'];
                $energia_kwh += $kg_material * $factores['energia'];
                $co2_evitado += $kg_material * $factores['co2'];
            }
        }

        return [
            'kg_total'              => $total_kg,
            'arboles_salvados'      => $arboles_salvados,
            'litros_agua_ahorrados' => $litros_agua_ahorrados,
            'energia_kwh'           => $energia_kwh,
            'co2_evitado'           => $co2_evitado,
        ];
    }

    /**
     * Obtiene estadisticas por material del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_por_material($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_material, SUM(cantidad_kg) as total_kg, COUNT(*) as num_depositos
             FROM {$tabla_depositos}
             WHERE usuario_id = %d AND verificado = 1
             GROUP BY tipo_material
             ORDER BY total_kg DESC",
            $usuario_id
        ), ARRAY_A);

        return $resultados ?: [];
    }

    /**
     * Obtiene la comparativa con la comunidad
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_comparativa_comunidad($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        // Promedio mensual del usuario (ultimos 6 meses)
        $fecha_inicio_6_meses = date('Y-m-01', strtotime('-6 months'));

        $kg_usuario_6_meses = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad_kg), 0)
             FROM {$tabla_depositos}
             WHERE usuario_id = %d AND verificado = 1 AND fecha_deposito >= %s",
            $usuario_id,
            $fecha_inicio_6_meses
        )));

        $promedio_mensual_usuario = $kg_usuario_6_meses / 6;

        // Promedio de la comunidad
        $datos_comunidad = $wpdb->get_row(
            "SELECT
                COUNT(DISTINCT usuario_id) as total_usuarios,
                COALESCE(SUM(cantidad_kg), 0) as total_kg
             FROM {$tabla_depositos}
             WHERE verificado = 1 AND fecha_deposito >= '{$fecha_inicio_6_meses}'"
        );

        $promedio_comunidad = 0;
        if ($datos_comunidad->total_usuarios > 0) {
            $promedio_comunidad = ($datos_comunidad->total_kg / $datos_comunidad->total_usuarios) / 6;
        }

        return [
            'promedio_usuario'   => $promedio_mensual_usuario,
            'promedio_comunidad' => $promedio_comunidad,
        ];
    }

    /**
     * Obtiene la evolucion anual del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_evolucion_anual($usuario_id) {
        global $wpdb;
        $tabla_depositos = $this->db_prefix . 'flavor_reciclaje_depositos';

        $datos_evolucion = [];

        for ($indice_mes = 11; $indice_mes >= 0; $indice_mes--) {
            $fecha_inicio = date('Y-m-01', strtotime("-{$indice_mes} months"));
            $fecha_fin = date('Y-m-t', strtotime("-{$indice_mes} months"));

            $kg_del_mes = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(cantidad_kg), 0)
                 FROM {$tabla_depositos}
                 WHERE usuario_id = %d AND verificado = 1
                 AND fecha_deposito BETWEEN %s AND %s",
                $usuario_id,
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59'
            )));

            $datos_evolucion[] = [
                'mes'       => date_i18n('F', strtotime($fecha_inicio)),
                'mes_corto' => date_i18n('M', strtotime($fecha_inicio)),
                'kg'        => $kg_del_mes,
            ];
        }

        return $datos_evolucion;
    }

    /**
     * Obtiene el color CSS para un tipo de material
     *
     * @param string $tipo_material Tipo de material
     * @return string Clase CSS de color
     */
    private function obtener_color_material($tipo_material) {
        $colores_material = [
            'papel'       => 'color-papel',
            'plastico'    => 'color-plastico',
            'vidrio'      => 'color-vidrio',
            'organico'    => 'color-organico',
            'electronico' => 'color-electronico',
            'ropa'        => 'color-ropa',
            'aceite'      => 'color-aceite',
            'pilas'       => 'color-pilas',
        ];

        $tipo_normalizado = strtolower($tipo_material);

        return $colores_material[$tipo_normalizado] ?? 'color-default';
    }

    /**
     * AJAX handler para cargar contenido de tab dinamicamente
     */
    public function ajax_load_tab_content() {
        check_ajax_referer('reciclaje_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-platform')]);
        }

        $tab_solicitada = sanitize_text_field($_POST['tab'] ?? '');

        ob_start();

        switch ($tab_solicitada) {
            case 'reciclaje-mis-aportes':
                $this->render_tab_mis_aportes();
                break;
            case 'reciclaje-mis-puntos':
                $this->render_tab_mis_puntos();
                break;
            case 'reciclaje-recompensas':
                $this->render_tab_recompensas();
                break;
            case 'reciclaje-estadisticas':
                $this->render_tab_estadisticas();
                break;
            default:
                wp_send_json_error(['message' => __('Tab no valida', 'flavor-platform')]);
                return;
        }

        $contenido_html = ob_get_clean();

        wp_send_json_success(['html' => $contenido_html]);
    }
}

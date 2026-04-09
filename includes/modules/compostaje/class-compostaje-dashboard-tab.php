<?php
/**
 * Dashboard Tabs para Compostaje Comunitario
 *
 * Registra y renderiza las tabs del dashboard del usuario
 * para el modulo de compostaje comunitario.
 *
 * Tabs disponibles:
 * - compostaje-mis-aportes: Historial de aportaciones del usuario
 * - compostaje-mi-balance: Puntos acumulados y kg compostados
 * - compostaje-turnos: Turnos asignados al usuario
 * - compostaje-ranking: Posicion en el ranking comunitario
 *
 * @package FlavorChatIA
 * @subpackage Modules\Compostaje
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar las tabs del dashboard de Compostaje
 */
class Flavor_Compostaje_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Compostaje_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * ID del modulo
     */
    const MODULO_ID = 'compostaje';

    /**
     * Version
     */
    const VERSION = '1.0.0';

    /**
     * Factor de conversion kg organico a CO2 evitado
     */
    const CO2_POR_KG_ORGANICO = 0.5;

    /**
     * Niveles de gamificacion del sistema
     *
     * @var array
     */
    private $niveles_gamificacion = [
        1 => ['nombre' => 'Semilla', 'kg_minimo' => 0, 'puntos_bonus' => 0, 'icono' => 'seedling', 'emoji' => '🌱'],
        2 => ['nombre' => 'Brote', 'kg_minimo' => 10, 'puntos_bonus' => 5, 'icono' => 'leaf', 'emoji' => '🌿'],
        3 => ['nombre' => 'Planta', 'kg_minimo' => 50, 'puntos_bonus' => 10, 'icono' => 'plant', 'emoji' => '🌾'],
        4 => ['nombre' => 'Arbol', 'kg_minimo' => 150, 'puntos_bonus' => 15, 'icono' => 'tree', 'emoji' => '🌳'],
        5 => ['nombre' => 'Bosque', 'kg_minimo' => 500, 'puntos_bonus' => 25, 'icono' => 'forest', 'emoji' => '🌲'],
        6 => ['nombre' => 'Ecosistema', 'kg_minimo' => 1000, 'puntos_bonus' => 50, 'icono' => 'globe', 'emoji' => '🌍'],
    ];

    /**
     * Categorias de materiales compostables
     *
     * @var array
     */
    private $categorias_material = [
        'verde' => [
            'color' => '#4caf50',
            'icono' => 'dashicons-carrot',
            'label' => 'Material Verde',
        ],
        'marron' => [
            'color' => '#8d6e63',
            'icono' => 'dashicons-admin-page',
            'label' => 'Material Marron',
        ],
        'especial' => [
            'color' => '#ff9800',
            'icono' => 'dashicons-star-filled',
            'label' => 'Material Especial',
        ],
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Compostaje_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa los hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Registrar tabs en el dashboard del usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 15, 1);

        // Assets para las tabs
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers para datos dinamicos
        add_action('wp_ajax_compostaje_tab_load_aportes', [$this, 'ajax_cargar_aportes']);
        add_action('wp_ajax_compostaje_tab_load_ranking', [$this, 'ajax_cargar_ranking']);
        add_action('wp_ajax_compostaje_tab_export_datos', [$this, 'ajax_exportar_datos']);
    }

    /**
     * Registra las tabs en el dashboard del usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificadas
     */
    public function registrar_tabs($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        // Tab principal de compostaje con sub-tabs
        $tabs['compostaje-mis-aportes'] = [
            'titulo' => __('Mis Aportes', 'flavor-platform'),
            'icono' => 'dashicons-carrot',
            'callback' => [$this, 'render_tab_mis_aportes'],
            'orden' => 56,
            'parent' => 'compostaje',
            'descripcion' => __('Historial de aportaciones a composteras', 'flavor-platform'),
        ];

        $tabs['compostaje-mi-balance'] = [
            'titulo' => __('Mi Balance', 'flavor-platform'),
            'icono' => 'dashicons-chart-area',
            'callback' => [$this, 'render_tab_mi_balance'],
            'orden' => 57,
            'parent' => 'compostaje',
            'descripcion' => __('Puntos acumulados y kg compostados', 'flavor-platform'),
        ];

        $tabs['compostaje-turnos'] = [
            'titulo' => __('Mis Turnos', 'flavor-platform'),
            'icono' => 'dashicons-calendar-alt',
            'callback' => [$this, 'render_tab_turnos'],
            'orden' => 58,
            'parent' => 'compostaje',
            'descripcion' => __('Turnos de mantenimiento asignados', 'flavor-platform'),
        ];

        $tabs['compostaje-ranking'] = [
            'titulo' => __('Ranking', 'flavor-platform'),
            'icono' => 'dashicons-awards',
            'callback' => [$this, 'render_tab_ranking'],
            'orden' => 59,
            'parent' => 'compostaje',
            'descripcion' => __('Posicion en el ranking comunitario', 'flavor-platform'),
        ];

        return $tabs;
    }

    /**
     * Registra los assets necesarios
     *
     * @return void
     */
    public function registrar_assets() {
        $modulo_url = plugin_dir_url(__FILE__);

        wp_register_style(
            'flavor-compostaje-dashboard-tab',
            $modulo_url . 'assets/css/compostaje-dashboard-tab.css',
            ['flavor-compostaje-frontend'],
            self::VERSION
        );

        wp_register_script(
            'flavor-compostaje-dashboard-tab',
            $modulo_url . 'assets/js/compostaje-dashboard-tab.js',
            ['jquery', 'flavor-compostaje-frontend'],
            self::VERSION,
            true
        );

        // Chart.js para graficos
        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
    }

    /**
     * Encola assets cuando se necesitan
     *
     * @return void
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-compostaje-dashboard-tab');
        wp_enqueue_script('flavor-compostaje-dashboard-tab');
        wp_enqueue_script('chartjs');

        wp_localize_script('flavor-compostaje-dashboard-tab', 'flavorCompostajeDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('compostaje_dashboard_nonce'),
            'usuarioId' => get_current_user_id(),
            'co2Factor' => self::CO2_POR_KG_ORGANICO,
            'strings' => [
                'cargando' => __('Cargando datos...', 'flavor-platform'),
                'error' => __('Error al cargar los datos', 'flavor-platform'),
                'sin_datos' => __('No hay datos disponibles', 'flavor-platform'),
                'kg_compostados' => __('kg compostados', 'flavor-platform'),
                'co2_evitado' => __('kg CO2 evitado', 'flavor-platform'),
                'puntos' => __('puntos', 'flavor-platform'),
            ],
        ]);
    }

    // ==========================================
    // TAB: MIS APORTES
    // ==========================================

    /**
     * Renderiza la tab de mis aportes
     *
     * @return void
     */
    public function render_tab_mis_aportes() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        $aportaciones = $this->obtener_aportaciones_usuario($usuario_id);
        $totales = $this->calcular_totales_aportaciones($aportaciones);
        $estadisticas_mensuales = $this->obtener_estadisticas_mensuales($usuario_id);
        ?>
        <div class="flavor-dashboard-tab flavor-compostaje-aportes">
            <!-- Resumen rapido -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde">
                        <span class="dashicons dashicons-carrot"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($totales['total_aportaciones']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Aportaciones', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($totales['total_kg'], 1); ?> kg</span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total Compostado', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde-claro">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($totales['total_co2'], 1); ?> kg</span>
                        <span class="flavor-kpi-label"><?php esc_html_e('CO2 Evitado', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon naranja">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($totales['total_puntos']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Puntos Ganados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Grafico de evolucion mensual -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Evolucion Mensual', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <canvas id="compostaje-grafico-mensual" height="200"
                            data-estadisticas='<?php echo esc_attr(json_encode($estadisticas_mensuales)); ?>'></canvas>
                </div>
            </div>

            <!-- Distribucion por categoria -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Distribucion por Tipo de Material', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-distribucion-grid">
                        <?php
                        $distribucion = $this->obtener_distribucion_materiales($usuario_id);
                        foreach ($distribucion as $categoria => $datos):
                            $categoria_info = $this->categorias_material[$categoria] ?? [];
                        ?>
                            <div class="flavor-distribucion-item" style="--color: <?php echo esc_attr($categoria_info['color'] ?? '#666'); ?>">
                                <div class="flavor-distribucion-icono">
                                    <span class="dashicons <?php echo esc_attr($categoria_info['icono'] ?? 'dashicons-marker'); ?>"></span>
                                </div>
                                <div class="flavor-distribucion-datos">
                                    <span class="flavor-distribucion-valor"><?php echo number_format($datos['kg'], 1); ?> kg</span>
                                    <span class="flavor-distribucion-label"><?php echo esc_html($categoria_info['label'] ?? ucfirst($categoria)); ?></span>
                                    <div class="flavor-progress-bar">
                                        <div class="flavor-progress-fill" style="width: <?php echo esc_attr($datos['porcentaje']); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Listado de aportaciones -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Historial de Aportaciones', 'flavor-platform'); ?></h3>
                    <div class="flavor-panel-actions">
                        <select id="filtro-periodo-aportes" class="flavor-select flavor-select-sm">
                            <option value=""><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                            <option value="7"><?php esc_html_e('Ultimos 7 dias', 'flavor-platform'); ?></option>
                            <option value="30"><?php esc_html_e('Ultimo mes', 'flavor-platform'); ?></option>
                            <option value="90"><?php esc_html_e('Ultimos 3 meses', 'flavor-platform'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($aportaciones)): ?>
                        <div class="flavor-tabla-responsive">
                            <table class="flavor-tabla flavor-tabla-striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Compostera', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Material', 'flavor-platform'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Cantidad', 'flavor-platform'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Puntos', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="lista-aportaciones">
                                    <?php foreach ($aportaciones as $aportacion): ?>
                                        <tr>
                                            <td>
                                                <span class="flavor-fecha">
                                                    <?php echo esc_html(date_i18n('d M Y', strtotime($aportacion->fecha_aportacion))); ?>
                                                </span>
                                                <span class="flavor-hora">
                                                    <?php echo esc_html(date_i18n('H:i', strtotime($aportacion->fecha_aportacion))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="flavor-truncate" title="<?php echo esc_attr($aportacion->nombre_punto); ?>">
                                                    <?php echo esc_html($aportacion->nombre_punto ?: __('Punto eliminado', 'flavor-platform')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($aportacion->categoria_material); ?>">
                                                    <?php echo esc_html($this->obtener_nombre_material($aportacion->tipo_material)); ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo number_format($aportacion->cantidad_kg, 2); ?></strong> kg
                                            </td>
                                            <td class="text-right">
                                                <span class="flavor-puntos">+<?php echo intval($aportacion->puntos_obtenidos); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($aportacion->validado): ?>
                                                    <span class="flavor-badge flavor-badge-success">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php esc_html_e('Validado', 'flavor-platform'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="flavor-badge flavor-badge-warning">
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
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-carrot"></span>
                            <h4><?php esc_html_e('Sin aportaciones todavia', 'flavor-platform'); ?></h4>
                            <p><?php esc_html_e('Empieza a compostar y registra tu primera aportacion.', 'flavor-platform'); ?></p>
                            <a href="<?php echo esc_url(home_url('/compostaje/')); ?>" class="flavor-btn flavor-btn-primary">
                                <?php esc_html_e('Ir a Compostar', 'flavor-platform'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    // ==========================================
    // TAB: MI BALANCE
    // ==========================================

    /**
     * Renderiza la tab de mi balance
     *
     * @return void
     */
    public function render_tab_mi_balance() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        $balance = $this->obtener_balance_usuario($usuario_id);
        $nivel_actual = $this->calcular_nivel_usuario($balance['total_kg']);
        $impacto_ambiental = $this->calcular_impacto_ambiental($balance);
        $historial_puntos = $this->obtener_historial_puntos($usuario_id);
        $solicitudes_compost = $this->obtener_solicitudes_compost_usuario($usuario_id, 5);
        $resumen_solicitudes = $this->obtener_resumen_solicitudes_compost($usuario_id);
        ?>
        <div class="flavor-dashboard-tab flavor-compostaje-balance">
            <!-- Tarjeta de nivel y progreso -->
            <div class="flavor-nivel-hero">
                <div class="flavor-nivel-badge nivel-<?php echo esc_attr($nivel_actual['nivel']); ?>">
                    <span class="flavor-nivel-emoji"><?php echo esc_html($nivel_actual['emoji']); ?></span>
                    <div class="flavor-nivel-info">
                        <span class="flavor-nivel-nombre"><?php echo esc_html($nivel_actual['nombre']); ?></span>
                        <span class="flavor-nivel-numero"><?php printf(esc_html__('Nivel %d', 'flavor-platform'), $nivel_actual['nivel']); ?></span>
                    </div>
                </div>

                <div class="flavor-nivel-progreso">
                    <?php
                    $progreso = $this->calcular_progreso_nivel($balance['total_kg'], $nivel_actual['nivel']);
                    ?>
                    <div class="flavor-progreso-barra">
                        <div class="flavor-progreso-relleno" style="width: <?php echo esc_attr($progreso['porcentaje']); ?>%"></div>
                    </div>
                    <div class="flavor-progreso-texto">
                        <span><?php echo number_format($balance['total_kg'], 1); ?> kg</span>
                        <span><?php echo number_format($progreso['siguiente_kg'], 0); ?> kg</span>
                    </div>
                    <?php if ($progreso['kg_faltantes'] > 0): ?>
                        <p class="flavor-progreso-meta">
                            <?php printf(
                                esc_html__('Faltan %.1f kg para alcanzar el nivel %s', 'flavor-platform'),
                                $progreso['kg_faltantes'],
                                $progreso['siguiente_nombre']
                            ); ?>
                        </p>
                    <?php else: ?>
                        <p class="flavor-progreso-meta flavor-texto-success">
                            <?php esc_html_e('Has alcanzado el nivel maximo', 'flavor-platform'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Balance de puntos -->
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card flavor-kpi-destacado">
                    <div class="flavor-kpi-icon morado">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($balance['puntos_totales']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Puntos Totales', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($balance['total_kg'], 1); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('kg Compostados', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($balance['turnos_completados']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Turnos Completados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Mis Solicitudes de Compost', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-kpi-grid flavor-grid-3">
                        <div class="flavor-kpi-card">
                            <div class="flavor-kpi-icon verde">
                                <span class="dashicons dashicons-hourglass"></span>
                            </div>
                            <div class="flavor-kpi-content">
                                <span class="flavor-kpi-valor"><?php echo intval($resumen_solicitudes['pendientes']); ?></span>
                                <span class="flavor-kpi-label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></span>
                            </div>
                        </div>
                        <div class="flavor-kpi-card">
                            <div class="flavor-kpi-icon azul">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </div>
                            <div class="flavor-kpi-content">
                                <span class="flavor-kpi-valor"><?php echo intval($resumen_solicitudes['aprobadas']); ?></span>
                                <span class="flavor-kpi-label"><?php esc_html_e('Aprobadas', 'flavor-platform'); ?></span>
                            </div>
                        </div>
                        <div class="flavor-kpi-card">
                            <div class="flavor-kpi-icon morado">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </div>
                            <div class="flavor-kpi-content">
                                <span class="flavor-kpi-valor"><?php echo number_format($resumen_solicitudes['kg_solicitados'], 1); ?> kg</span>
                                <span class="flavor-kpi-label"><?php esc_html_e('Solicitados', 'flavor-platform'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($solicitudes_compost)): ?>
                        <div class="flavor-lista-simple">
                            <?php foreach ($solicitudes_compost as $solicitud): ?>
                                <?php
                                $estado_clase = sanitize_html_class((string) $solicitud->estado);
                                $estado_label = ucfirst((string) $solicitud->estado);
                                ?>
                                <div class="flavor-item-simple">
                                    <div class="flavor-item-simple__main">
                                        <strong><?php echo esc_html($solicitud->nombre_punto ?: __('Punto de compostaje', 'flavor-platform')); ?></strong>
                                        <span>
                                            <?php
                                            printf(
                                                esc_html__('%1$s kg solicitados el %2$s', 'flavor-platform'),
                                                number_format((float) $solicitud->cantidad_kg, 1),
                                                esc_html(date_i18n(get_option('date_format'), strtotime($solicitud->fecha_solicitud)))
                                            );
                                            ?>
                                        </span>
                                    </div>
                                    <div class="flavor-item-simple__meta">
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_clase); ?>">
                                            <?php echo esc_html($estado_label); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="flavor-texto-muted"><?php esc_html_e('Aún no has solicitado compost maduro.', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Impacto ambiental -->
            <div class="flavor-panel flavor-panel-impacto">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-palmtree"></span>
                        <?php esc_html_e('Tu Impacto Ambiental', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-impacto-grid">
                        <div class="flavor-impacto-item">
                            <div class="flavor-impacto-icono co2">
                                <span class="dashicons dashicons-cloud"></span>
                            </div>
                            <div class="flavor-impacto-datos">
                                <span class="flavor-impacto-valor"><?php echo number_format($impacto_ambiental['co2_evitado'], 1); ?></span>
                                <span class="flavor-impacto-unidad">kg CO2</span>
                                <span class="flavor-impacto-label"><?php esc_html_e('Emisiones evitadas', 'flavor-platform'); ?></span>
                            </div>
                            <div class="flavor-impacto-equivalencia">
                                <span class="dashicons dashicons-car"></span>
                                <span>
                                    <?php printf(
                                        esc_html__('Equivale a %s km en coche', 'flavor-platform'),
                                        number_format($impacto_ambiental['km_coche'], 0)
                                    ); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flavor-impacto-item">
                            <div class="flavor-impacto-icono arboles">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                            </div>
                            <div class="flavor-impacto-datos">
                                <span class="flavor-impacto-valor"><?php echo number_format($impacto_ambiental['arboles_equivalentes'], 1); ?></span>
                                <span class="flavor-impacto-unidad"><?php esc_html_e('arboles', 'flavor-platform'); ?></span>
                                <span class="flavor-impacto-label"><?php esc_html_e('Absorcion anual equivalente', 'flavor-platform'); ?></span>
                            </div>
                            <div class="flavor-impacto-equivalencia">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span>
                                    <?php printf(
                                        esc_html__('Como plantar %s arboles nuevos', 'flavor-platform'),
                                        number_format($impacto_ambiental['arboles_equivalentes'], 0)
                                    ); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flavor-impacto-item">
                            <div class="flavor-impacto-icono vertedero">
                                <span class="dashicons dashicons-trash"></span>
                            </div>
                            <div class="flavor-impacto-datos">
                                <span class="flavor-impacto-valor"><?php echo number_format($impacto_ambiental['kg_vertedero'], 1); ?></span>
                                <span class="flavor-impacto-unidad">kg</span>
                                <span class="flavor-impacto-label"><?php esc_html_e('Desviados del vertedero', 'flavor-platform'); ?></span>
                            </div>
                            <div class="flavor-impacto-equivalencia">
                                <span class="dashicons dashicons-update"></span>
                                <span>
                                    <?php printf(
                                        esc_html__('Transformados en %s kg de compost', 'flavor-platform'),
                                        number_format($impacto_ambiental['kg_compost_generado'], 1)
                                    ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafico de impacto acumulado -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Evolucion del Impacto', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <canvas id="compostaje-grafico-impacto" height="250"
                            data-historial='<?php echo esc_attr(json_encode($historial_puntos)); ?>'></canvas>
                </div>
            </div>

            <!-- Desglose de puntos -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Desglose de Puntos', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-desglose-lista">
                        <div class="flavor-desglose-item">
                            <span class="flavor-desglose-icono verde">
                                <span class="dashicons dashicons-carrot"></span>
                            </span>
                            <span class="flavor-desglose-concepto"><?php esc_html_e('Por aportaciones', 'flavor-platform'); ?></span>
                            <span class="flavor-desglose-valor">+<?php echo number_format($balance['puntos_aportaciones']); ?></span>
                        </div>

                        <div class="flavor-desglose-item">
                            <span class="flavor-desglose-icono azul">
                                <span class="dashicons dashicons-groups"></span>
                            </span>
                            <span class="flavor-desglose-concepto"><?php esc_html_e('Por turnos completados', 'flavor-platform'); ?></span>
                            <span class="flavor-desglose-valor">+<?php echo number_format($balance['puntos_turnos']); ?></span>
                        </div>

                        <div class="flavor-desglose-item">
                            <span class="flavor-desglose-icono morado">
                                <span class="dashicons dashicons-star-filled"></span>
                            </span>
                            <span class="flavor-desglose-concepto"><?php esc_html_e('Bonus de nivel', 'flavor-platform'); ?></span>
                            <span class="flavor-desglose-valor">+<?php echo number_format($balance['puntos_bonus']); ?></span>
                        </div>

                        <div class="flavor-desglose-total">
                            <span class="flavor-desglose-concepto"><?php esc_html_e('Total acumulado', 'flavor-platform'); ?></span>
                            <span class="flavor-desglose-valor"><?php echo number_format($balance['puntos_totales']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ==========================================
    // TAB: MIS TURNOS
    // ==========================================

    /**
     * Renderiza la tab de turnos
     *
     * @return void
     */
    public function render_tab_turnos() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        $turnos_proximos = $this->obtener_turnos_usuario($usuario_id, 'proximos');
        $turnos_pasados = $this->obtener_turnos_usuario($usuario_id, 'pasados');
        $estadisticas_turnos = $this->obtener_estadisticas_turnos($usuario_id);
        ?>
        <div class="flavor-dashboard-tab flavor-compostaje-turnos">
            <!-- Resumen de turnos -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas_turnos['total_inscrito']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Turnos Inscritos', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas_turnos['completados']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completados', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon naranja">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas_turnos['pendientes']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon morado">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas_turnos['puntos_ganados']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Puntos por Turnos', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Proximos turnos -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Proximos Turnos', 'flavor-platform'); ?>
                    </h3>
                    <a href="<?php echo esc_url(home_url('/compostaje/turnos/')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php esc_html_e('Ver Disponibles', 'flavor-platform'); ?>
                    </a>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($turnos_proximos)): ?>
                        <div class="flavor-turnos-lista">
                            <?php foreach ($turnos_proximos as $turno): ?>
                                <div class="flavor-turno-card" data-turno-id="<?php echo esc_attr($turno->turno_id); ?>">
                                    <div class="flavor-turno-fecha-badge">
                                        <span class="flavor-turno-dia"><?php echo esc_html(date_i18n('d', strtotime($turno->fecha_turno))); ?></span>
                                        <span class="flavor-turno-mes"><?php echo esc_html(date_i18n('M', strtotime($turno->fecha_turno))); ?></span>
                                    </div>

                                    <div class="flavor-turno-contenido">
                                        <div class="flavor-turno-header">
                                            <span class="flavor-turno-tipo">
                                                <span class="dashicons dashicons-<?php echo $this->obtener_icono_tarea($turno->tipo_tarea); ?>"></span>
                                                <?php echo esc_html(ucfirst($turno->tipo_tarea)); ?>
                                            </span>
                                            <span class="flavor-badge flavor-badge-<?php echo $turno->estado_inscripcion === 'confirmado' ? 'success' : 'info'; ?>">
                                                <?php echo esc_html(ucfirst($turno->estado_inscripcion)); ?>
                                            </span>
                                        </div>

                                        <div class="flavor-turno-detalles">
                                            <span class="flavor-turno-ubicacion">
                                                <span class="dashicons dashicons-location"></span>
                                                <?php echo esc_html($turno->nombre_punto); ?>
                                            </span>
                                            <span class="flavor-turno-horario">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php echo esc_html(substr($turno->hora_inicio, 0, 5) . ' - ' . substr($turno->hora_fin, 0, 5)); ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($turno->descripcion)): ?>
                                            <p class="flavor-turno-descripcion">
                                                <?php echo esc_html($turno->descripcion); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flavor-turno-acciones">
                                        <span class="flavor-turno-puntos">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            +<?php echo intval($turno->puntos_recompensa); ?>
                                        </span>
                                        <?php if ($turno->estado_inscripcion !== 'cancelado'): ?>
                                            <button class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-cancelar-turno"
                                                    data-turno-id="<?php echo esc_attr($turno->turno_id); ?>">
                                                <span class="dashicons dashicons-no"></span>
                                                <?php esc_html_e('Cancelar', 'flavor-platform'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h4><?php esc_html_e('Sin turnos programados', 'flavor-platform'); ?></h4>
                            <p><?php esc_html_e('No tienes turnos de mantenimiento asignados. Inscribete a uno para ganar puntos extra.', 'flavor-platform'); ?></p>
                            <a href="<?php echo esc_url(home_url('/compostaje/turnos/')); ?>" class="flavor-btn flavor-btn-primary">
                                <?php esc_html_e('Ver Turnos Disponibles', 'flavor-platform'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de turnos -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php esc_html_e('Historial de Turnos', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($turnos_pasados)): ?>
                        <div class="flavor-tabla-responsive">
                            <table class="flavor-tabla flavor-tabla-striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Compostera', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Tarea', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Puntos', 'flavor-platform'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($turnos_pasados as $turno): ?>
                                        <tr>
                                            <td><?php echo esc_html(date_i18n('d M Y', strtotime($turno->fecha_turno))); ?></td>
                                            <td><?php echo esc_html($turno->nombre_punto); ?></td>
                                            <td><?php echo esc_html(ucfirst($turno->tipo_tarea)); ?></td>
                                            <td>
                                                <span class="flavor-badge flavor-badge-<?php echo $turno->estado_inscripcion === 'asistio' ? 'success' : 'danger'; ?>">
                                                    <?php echo esc_html($turno->estado_inscripcion === 'asistio' ? __('Asistio', 'flavor-platform') : __('No asistio', 'flavor-platform')); ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($turno->estado_inscripcion === 'asistio'): ?>
                                                    <span class="flavor-puntos">+<?php echo intval($turno->puntos_obtenidos); ?></span>
                                                <?php else: ?>
                                                    <span class="flavor-texto-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="flavor-texto-muted flavor-text-center">
                            <?php esc_html_e('No hay turnos en el historial todavia.', 'flavor-platform'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    // ==========================================
    // TAB: RANKING
    // ==========================================

    /**
     * Renderiza la tab de ranking
     *
     * @return void
     */
    public function render_tab_ranking() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        $posicion_usuario = $this->obtener_posicion_ranking($usuario_id);
        $ranking_general = $this->obtener_ranking('total', 10);
        $ranking_mensual = $this->obtener_ranking('mes', 10);
        $ranking_semanal = $this->obtener_ranking('semana', 10);
        ?>
        <div class="flavor-dashboard-tab flavor-compostaje-ranking">
            <!-- Posicion del usuario -->
            <div class="flavor-ranking-hero">
                <div class="flavor-ranking-posicion">
                    <span class="flavor-ranking-numero">#<?php echo intval($posicion_usuario['posicion']); ?></span>
                    <span class="flavor-ranking-label"><?php esc_html_e('Tu posicion en el ranking general', 'flavor-platform'); ?></span>
                </div>
                <div class="flavor-ranking-stats">
                    <div class="flavor-ranking-stat">
                        <span class="flavor-ranking-stat-valor"><?php echo number_format($posicion_usuario['total_kg'], 1); ?></span>
                        <span class="flavor-ranking-stat-label"><?php esc_html_e('kg compostados', 'flavor-platform'); ?></span>
                    </div>
                    <div class="flavor-ranking-stat">
                        <span class="flavor-ranking-stat-valor"><?php echo number_format($posicion_usuario['total_puntos']); ?></span>
                        <span class="flavor-ranking-stat-label"><?php esc_html_e('puntos totales', 'flavor-platform'); ?></span>
                    </div>
                    <div class="flavor-ranking-stat">
                        <span class="flavor-ranking-stat-valor"><?php echo intval($posicion_usuario['participantes_por_detras']); ?></span>
                        <span class="flavor-ranking-stat-label"><?php esc_html_e('personas por detras', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Tabs de periodos -->
            <div class="flavor-tabs-wrapper">
                <div class="flavor-tabs" role="tablist">
                    <button class="flavor-tab active" data-tab="ranking-general" role="tab">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php esc_html_e('General', 'flavor-platform'); ?>
                    </button>
                    <button class="flavor-tab" data-tab="ranking-mensual" role="tab">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php esc_html_e('Este Mes', 'flavor-platform'); ?>
                    </button>
                    <button class="flavor-tab" data-tab="ranking-semanal" role="tab">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Esta Semana', 'flavor-platform'); ?>
                    </button>
                </div>

                <!-- Ranking General -->
                <div class="flavor-tab-panel active" id="ranking-general" role="tabpanel">
                    <?php $this->render_tabla_ranking($ranking_general, $usuario_id); ?>
                </div>

                <!-- Ranking Mensual -->
                <div class="flavor-tab-panel" id="ranking-mensual" role="tabpanel">
                    <?php $this->render_tabla_ranking($ranking_mensual, $usuario_id); ?>
                </div>

                <!-- Ranking Semanal -->
                <div class="flavor-tab-panel" id="ranking-semanal" role="tabpanel">
                    <?php $this->render_tabla_ranking($ranking_semanal, $usuario_id); ?>
                </div>
            </div>

            <!-- Insignias y logros -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-awards"></span>
                        <?php esc_html_e('Tus Logros', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="flavor-panel-body">
                    <?php
                    $logros = $this->obtener_logros_usuario($usuario_id);
                    if (!empty($logros)):
                    ?>
                        <div class="flavor-logros-grid">
                            <?php foreach ($logros as $logro): ?>
                                <div class="flavor-logro-card">
                                    <div class="flavor-logro-icono">
                                        <span class="dashicons dashicons-<?php echo esc_attr($this->obtener_icono_logro($logro->tipo_logro)); ?>"></span>
                                    </div>
                                    <div class="flavor-logro-info">
                                        <span class="flavor-logro-nombre"><?php echo esc_html($this->obtener_nombre_logro($logro->tipo_logro)); ?></span>
                                        <span class="flavor-logro-nivel"><?php printf(esc_html__('Nivel %d', 'flavor-platform'), $logro->nivel); ?></span>
                                        <span class="flavor-logro-fecha">
                                            <?php echo esc_html(date_i18n('d M Y', strtotime($logro->fecha_obtencion))); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state flavor-empty-state-sm">
                            <span class="dashicons dashicons-awards"></span>
                            <p><?php esc_html_e('Sigue compostando para desbloquear logros', 'flavor-platform'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza tabla de ranking
     *
     * @param array $ranking Datos del ranking
     * @param int   $usuario_id_actual ID del usuario actual
     * @return void
     */
    private function render_tabla_ranking($ranking, $usuario_id_actual) {
        if (empty($ranking)) {
            echo '<div class="flavor-empty-state flavor-empty-state-sm">';
            echo '<span class="dashicons dashicons-chart-bar"></span>';
            echo '<p>' . esc_html__('No hay datos de ranking todavia', 'flavor-platform') . '</p>';
            echo '</div>';
            return;
        }
        ?>
        <div class="flavor-ranking-lista">
            <?php
            $posicion = 0;
            foreach ($ranking as $participante):
                $posicion++;
                $es_usuario_actual = ($participante->usuario_id == $usuario_id_actual);
                $clase_destacado = $es_usuario_actual ? 'flavor-ranking-item--destacado' : '';
                $nivel = $this->calcular_nivel_usuario($participante->total_kg);
            ?>
                <div class="flavor-ranking-item <?php echo esc_attr($clase_destacado); ?>">
                    <div class="flavor-ranking-pos <?php echo $posicion <= 3 ? 'top-' . $posicion : ''; ?>">
                        <?php if ($posicion === 1): ?>
                            <span class="flavor-medal oro">
                                <span class="dashicons dashicons-star-filled"></span>
                            </span>
                        <?php elseif ($posicion === 2): ?>
                            <span class="flavor-medal plata">
                                <span class="dashicons dashicons-star-filled"></span>
                            </span>
                        <?php elseif ($posicion === 3): ?>
                            <span class="flavor-medal bronce">
                                <span class="dashicons dashicons-star-filled"></span>
                            </span>
                        <?php else: ?>
                            <span class="flavor-ranking-posicion-num"><?php echo intval($posicion); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-ranking-avatar">
                        <?php echo get_avatar($participante->usuario_id, 40); ?>
                    </div>

                    <div class="flavor-ranking-info">
                        <span class="flavor-ranking-nombre">
                            <?php echo esc_html($participante->display_name); ?>
                            <?php if ($es_usuario_actual): ?>
                                <span class="flavor-badge flavor-badge-info"><?php esc_html_e('Tu', 'flavor-platform'); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="flavor-ranking-nivel">
                            <?php echo esc_html($nivel['emoji'] . ' ' . $nivel['nombre']); ?>
                        </span>
                    </div>

                    <div class="flavor-ranking-datos">
                        <span class="flavor-ranking-kg"><?php echo number_format($participante->total_kg, 1); ?> kg</span>
                        <span class="flavor-ranking-puntos"><?php echo number_format($participante->total_puntos); ?> pts</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // ==========================================
    // METODOS DE CONSULTA DE DATOS
    // ==========================================

    /**
     * Obtiene las aportaciones de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Limite de resultados
     * @return array
     */
    private function obtener_aportaciones_usuario($usuario_id, $limite = 50) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.nombre as nombre_punto
             FROM $tabla_aportaciones a
             LEFT JOIN $tabla_puntos p ON a.punto_id = p.id
             WHERE a.usuario_id = %d
             ORDER BY a.fecha_aportacion DESC
             LIMIT %d",
            $usuario_id, $limite
        ));
    }

    /**
     * Calcula los totales de aportaciones
     *
     * @param array $aportaciones Lista de aportaciones
     * @return array
     */
    private function calcular_totales_aportaciones($aportaciones) {
        $total_aportaciones = count($aportaciones);
        $total_kg = 0;
        $total_co2 = 0;
        $total_puntos = 0;

        foreach ($aportaciones as $aportacion) {
            if ($aportacion->validado) {
                $total_kg += floatval($aportacion->cantidad_kg);
                $total_co2 += floatval($aportacion->co2_evitado_kg);
                $total_puntos += intval($aportacion->puntos_obtenidos);
            }
        }

        return [
            'total_aportaciones' => $total_aportaciones,
            'total_kg' => $total_kg,
            'total_co2' => $total_co2,
            'total_puntos' => $total_puntos,
        ];
    }

    /**
     * Obtiene estadisticas mensuales del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_mensuales($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(fecha_aportacion, '%%Y-%%m') as mes,
                DATE_FORMAT(fecha_aportacion, '%%b %%Y') as mes_label,
                SUM(cantidad_kg) as kg_mes,
                SUM(co2_evitado_kg) as co2_mes,
                SUM(puntos_obtenidos) as puntos_mes,
                COUNT(*) as aportaciones_mes
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1
               AND fecha_aportacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(fecha_aportacion, '%%Y-%%m')
             ORDER BY mes ASC",
            $usuario_id
        ));

        return array_map(function($row) {
            return [
                'mes' => $row->mes,
                'label' => $row->mes_label,
                'kg' => floatval($row->kg_mes),
                'co2' => floatval($row->co2_mes),
                'puntos' => intval($row->puntos_mes),
                'aportaciones' => intval($row->aportaciones_mes),
            ];
        }, $resultados);
    }

    /**
     * Obtiene distribucion de materiales del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_distribucion_materiales($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT
                categoria_material,
                SUM(cantidad_kg) as kg_categoria,
                COUNT(*) as aportaciones
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1
             GROUP BY categoria_material",
            $usuario_id
        ));

        $total_kg = 0;
        foreach ($resultados as $row) {
            $total_kg += floatval($row->kg_categoria);
        }

        $distribucion = [];
        foreach ($resultados as $row) {
            $porcentaje = $total_kg > 0 ? (floatval($row->kg_categoria) / $total_kg) * 100 : 0;
            $distribucion[$row->categoria_material] = [
                'kg' => floatval($row->kg_categoria),
                'aportaciones' => intval($row->aportaciones),
                'porcentaje' => round($porcentaje, 1),
            ];
        }

        // Asegurar que todas las categorias estan presentes
        foreach (['verde', 'marron', 'especial'] as $categoria) {
            if (!isset($distribucion[$categoria])) {
                $distribucion[$categoria] = ['kg' => 0, 'aportaciones' => 0, 'porcentaje' => 0];
            }
        }

        return $distribucion;
    }

    /**
     * Obtiene el balance completo del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_balance_usuario($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        // Estadisticas de aportaciones
        $stats_aportaciones = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_obtenidos), 0) as puntos_aportaciones,
                COALESCE(SUM(bonus_nivel), 0) as puntos_bonus,
                COALESCE(SUM(co2_evitado_kg), 0) as co2_total
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        // Estadisticas de turnos
        $stats_turnos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as turnos_completados,
                COALESCE(SUM(puntos_obtenidos), 0) as puntos_turnos
             FROM $tabla_inscripciones
             WHERE usuario_id = %d AND estado = 'asistio'",
            $usuario_id
        ));

        return [
            'total_kg' => floatval($stats_aportaciones->total_kg ?? 0),
            'puntos_aportaciones' => intval($stats_aportaciones->puntos_aportaciones ?? 0),
            'puntos_bonus' => intval($stats_aportaciones->puntos_bonus ?? 0),
            'puntos_turnos' => intval($stats_turnos->puntos_turnos ?? 0),
            'puntos_totales' => intval($stats_aportaciones->puntos_aportaciones ?? 0) + intval($stats_aportaciones->puntos_bonus ?? 0) + intval($stats_turnos->puntos_turnos ?? 0),
            'co2_total' => floatval($stats_aportaciones->co2_total ?? 0),
            'turnos_completados' => intval($stats_turnos->turnos_completados ?? 0),
        ];
    }

    /**
     * Obtiene las solicitudes de compost del usuario
     *
     * @param int $usuario_id ID del usuario.
     * @param int $limite Limite de resultados.
     * @return array
     */
    private function obtener_solicitudes_compost_usuario($usuario_id, $limite = 5) {
        global $wpdb;

        $tabla_solicitudes = $wpdb->prefix . 'flavor_solicitudes_compost';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.nombre AS nombre_punto
             FROM {$tabla_solicitudes} s
             LEFT JOIN {$tabla_puntos} p ON s.punto_id = p.id
             WHERE s.usuario_id = %d
             ORDER BY s.fecha_solicitud DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));
    }

    /**
     * Obtiene un resumen de solicitudes de compost del usuario
     *
     * @param int $usuario_id ID del usuario.
     * @return array
     */
    private function obtener_resumen_solicitudes_compost($usuario_id) {
        global $wpdb;

        $tabla_solicitudes = $wpdb->prefix . 'flavor_solicitudes_compost';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return [
                'pendientes' => 0,
                'aprobadas' => 0,
                'kg_solicitados' => 0,
            ];
        }

        $resumen = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas,
                COALESCE(SUM(cantidad_kg), 0) AS kg_solicitados
             FROM {$tabla_solicitudes}
             WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        return [
            'pendientes' => intval($resumen['pendientes'] ?? 0),
            'aprobadas' => intval($resumen['aprobadas'] ?? 0),
            'kg_solicitados' => floatval($resumen['kg_solicitados'] ?? 0),
        ];
    }

    /**
     * Calcula el impacto ambiental
     *
     * @param array $balance Balance del usuario
     * @return array
     */
    private function calcular_impacto_ambiental($balance) {
        $co2_evitado = $balance['co2_total'];

        // Conversiones aproximadas
        $km_coche = $co2_evitado / 0.12; // ~120g CO2/km promedio
        $arboles_equivalentes = $co2_evitado / 22; // Un arbol absorbe ~22kg CO2/ano
        $kg_compost = $balance['total_kg'] * 0.4; // ~40% del material organico se convierte en compost

        return [
            'co2_evitado' => $co2_evitado,
            'km_coche' => $km_coche,
            'arboles_equivalentes' => $arboles_equivalentes,
            'kg_vertedero' => $balance['total_kg'],
            'kg_compost_generado' => $kg_compost,
        ];
    }

    /**
     * Obtiene historial de puntos para grafico
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_historial_puntos($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(fecha_aportacion) as fecha,
                SUM(cantidad_kg) as kg_dia,
                SUM(co2_evitado_kg) as co2_dia,
                SUM(puntos_obtenidos) as puntos_dia
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1
               AND fecha_aportacion >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             GROUP BY DATE(fecha_aportacion)
             ORDER BY fecha ASC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Obtiene turnos del usuario
     *
     * @param int    $usuario_id ID del usuario
     * @param string $tipo 'proximos' o 'pasados'
     * @return array
     */
    private function obtener_turnos_usuario($usuario_id, $tipo = 'proximos') {
        global $wpdb;

        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $comparador = $tipo === 'proximos' ? '>=' : '<';
        $orden = $tipo === 'proximos' ? 'ASC' : 'DESC';
        $estado_condicion = $tipo === 'proximos' ? "i.estado IN ('inscrito', 'confirmado')" : "i.estado IN ('asistio', 'no_asistio')";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.nombre as nombre_punto, p.direccion,
                    i.estado as estado_inscripcion, i.puntos_obtenidos, i.turno_id
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_turnos t ON i.turno_id = t.id
             LEFT JOIN $tabla_puntos p ON t.punto_id = p.id
             WHERE i.usuario_id = %d
               AND t.fecha_turno $comparador CURDATE()
               AND $estado_condicion
             ORDER BY t.fecha_turno $orden
             LIMIT 20",
            $usuario_id
        ));
    }

    /**
     * Obtiene estadisticas de turnos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_turnos($usuario_id) {
        global $wpdb;

        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_inscrito,
                SUM(CASE WHEN estado = 'asistio' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado IN ('inscrito', 'confirmado') THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'asistio' THEN puntos_obtenidos ELSE 0 END) as puntos_ganados
             FROM $tabla_inscripciones
             WHERE usuario_id = %d",
            $usuario_id
        ));

        return [
            'total_inscrito' => intval($stats->total_inscrito ?? 0),
            'completados' => intval($stats->completados ?? 0),
            'pendientes' => intval($stats->pendientes ?? 0),
            'puntos_ganados' => intval($stats->puntos_ganados ?? 0),
        ];
    }

    /**
     * Obtiene posicion del usuario en el ranking
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_posicion_ranking($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        // Obtener totales del usuario
        $usuario_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(cantidad_kg) as total_kg,
                SUM(puntos_obtenidos) as total_puntos
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        $total_kg = floatval($usuario_stats->total_kg ?? 0);
        $total_puntos = intval($usuario_stats->total_puntos ?? 0);

        // Contar cuantos usuarios tienen mas kg
        $posicion = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) + 1
             FROM $tabla_aportaciones
             WHERE validado = 1
             GROUP BY usuario_id
             HAVING SUM(cantidad_kg) > %f",
            $total_kg
        )) ?: 1;

        // Total de participantes
        $total_participantes = $wpdb->get_var(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_aportaciones WHERE validado = 1"
        );

        return [
            'posicion' => intval($posicion),
            'total_kg' => $total_kg,
            'total_puntos' => $total_puntos,
            'total_participantes' => intval($total_participantes),
            'participantes_por_detras' => max(0, intval($total_participantes) - intval($posicion)),
        ];
    }

    /**
     * Obtiene el ranking de usuarios
     *
     * @param string $periodo 'total', 'mes' o 'semana'
     * @param int    $limite Limite de resultados
     * @return array
     */
    private function obtener_ranking($periodo = 'total', $limite = 10) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $where_periodo = '';
        if ($periodo === 'mes') {
            $where_periodo = "AND MONTH(fecha_aportacion) = MONTH(CURDATE()) AND YEAR(fecha_aportacion) = YEAR(CURDATE())";
        } elseif ($periodo === 'semana') {
            $where_periodo = "AND YEARWEEK(fecha_aportacion, 1) = YEARWEEK(CURDATE(), 1)";
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                a.usuario_id,
                u.display_name,
                SUM(a.cantidad_kg) as total_kg,
                SUM(a.puntos_obtenidos) as total_puntos,
                COUNT(*) as num_aportaciones
             FROM $tabla_aportaciones a
             INNER JOIN {$wpdb->users} u ON a.usuario_id = u.ID
             WHERE a.validado = 1 $where_periodo
             GROUP BY a.usuario_id
             ORDER BY total_kg DESC
             LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtiene logros del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_logros_usuario($usuario_id) {
        global $wpdb;

        $tabla_logros = $wpdb->prefix . 'flavor_logros_compostaje';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_logros)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logros WHERE usuario_id = %d ORDER BY fecha_obtencion DESC",
            $usuario_id
        ));
    }

    // ==========================================
    // METODOS AUXILIARES
    // ==========================================

    /**
     * Calcula el nivel del usuario
     *
     * @param float $total_kg Kg totales aportados
     * @return array
     */
    private function calcular_nivel_usuario($total_kg) {
        $nivel_actual = 1;

        foreach ($this->niveles_gamificacion as $nivel => $datos) {
            if ($total_kg >= $datos['kg_minimo']) {
                $nivel_actual = $nivel;
            }
        }

        return array_merge($this->niveles_gamificacion[$nivel_actual], ['nivel' => $nivel_actual]);
    }

    /**
     * Calcula progreso hacia el siguiente nivel
     *
     * @param float $total_kg Kg totales
     * @param int   $nivel_actual Nivel actual
     * @return array
     */
    private function calcular_progreso_nivel($total_kg, $nivel_actual) {
        $siguiente_nivel = $nivel_actual + 1;

        if (!isset($this->niveles_gamificacion[$siguiente_nivel])) {
            return [
                'porcentaje' => 100,
                'siguiente_kg' => $this->niveles_gamificacion[$nivel_actual]['kg_minimo'],
                'kg_faltantes' => 0,
                'siguiente_nombre' => '',
            ];
        }

        $kg_minimo_actual = $this->niveles_gamificacion[$nivel_actual]['kg_minimo'];
        $kg_siguiente = $this->niveles_gamificacion[$siguiente_nivel]['kg_minimo'];
        $rango = $kg_siguiente - $kg_minimo_actual;
        $progreso = $total_kg - $kg_minimo_actual;
        $porcentaje = $rango > 0 ? min(100, ($progreso / $rango) * 100) : 100;

        return [
            'porcentaje' => round($porcentaje),
            'siguiente_kg' => $kg_siguiente,
            'kg_faltantes' => max(0, $kg_siguiente - $total_kg),
            'siguiente_nombre' => $this->niveles_gamificacion[$siguiente_nivel]['nombre'],
        ];
    }

    /**
     * Obtiene nombre legible del material
     *
     * @param string $codigo Codigo del material
     * @return string
     */
    private function obtener_nombre_material($codigo) {
        $materiales = [
            'frutas_verduras' => __('Frutas y verduras', 'flavor-platform'),
            'posos_cafe' => __('Posos de cafe', 'flavor-platform'),
            'cesped_fresco' => __('Cesped fresco', 'flavor-platform'),
            'restos_cocina' => __('Restos de cocina', 'flavor-platform'),
            'plantas_verdes' => __('Plantas verdes', 'flavor-platform'),
            'hojas_secas' => __('Hojas secas', 'flavor-platform'),
            'papel_carton' => __('Papel y carton', 'flavor-platform'),
            'ramas_poda' => __('Ramas y poda', 'flavor-platform'),
            'serrin' => __('Serrin', 'flavor-platform'),
            'paja' => __('Paja', 'flavor-platform'),
            'cascaras_huevo' => __('Cascaras de huevo', 'flavor-platform'),
            'bolsas_te' => __('Bolsas de te', 'flavor-platform'),
        ];

        return $materiales[$codigo] ?? ucfirst(str_replace('_', ' ', $codigo));
    }

    /**
     * Obtiene icono para tipo de tarea
     *
     * @param string $tipo_tarea Tipo de tarea
     * @return string
     */
    private function obtener_icono_tarea($tipo_tarea) {
        $iconos = [
            'volteo' => 'update',
            'riego' => 'palmtree',
            'medicion' => 'chart-line',
            'tamizado' => 'filter',
            'limpieza' => 'trash',
            'revision' => 'visibility',
        ];

        return $iconos[$tipo_tarea] ?? 'admin-generic';
    }

    /**
     * Obtiene icono para tipo de logro
     *
     * @param string $tipo_logro Tipo de logro
     * @return string
     */
    private function obtener_icono_logro($tipo_logro) {
        $iconos = [
            'primera_aportacion' => 'welcome-add-page',
            'kg_total' => 'chart-bar',
            'aportaciones_consecutivas' => 'calendar-alt',
            'turno_completado' => 'groups',
            'nivel_alcanzado' => 'star-filled',
            'mentor' => 'admin-users',
        ];

        return $iconos[$tipo_logro] ?? 'awards';
    }

    /**
     * Obtiene nombre para tipo de logro
     *
     * @param string $tipo_logro Tipo de logro
     * @return string
     */
    private function obtener_nombre_logro($tipo_logro) {
        $nombres = [
            'primera_aportacion' => __('Primera Aportacion', 'flavor-platform'),
            'kg_total' => __('Compostador Experto', 'flavor-platform'),
            'aportaciones_consecutivas' => __('Constancia', 'flavor-platform'),
            'turno_completado' => __('Voluntario', 'flavor-platform'),
            'nivel_alcanzado' => __('Nivel Alcanzado', 'flavor-platform'),
            'mentor' => __('Mentor', 'flavor-platform'),
        ];

        return $nombres[$tipo_logro] ?? ucfirst(str_replace('_', ' ', $tipo_logro));
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Carga aportes filtrados
     *
     * @return void
     */
    public function ajax_cargar_aportes() {
        check_ajax_referer('compostaje_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
        }

        $dias = intval($_POST['dias'] ?? 0);

        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $where_fecha = '';
        if ($dias > 0) {
            $where_fecha = $wpdb->prepare(" AND a.fecha_aportacion >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $dias);
        }

        $aportaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.nombre as nombre_punto
             FROM $tabla_aportaciones a
             LEFT JOIN $tabla_puntos p ON a.punto_id = p.id
             WHERE a.usuario_id = %d $where_fecha
             ORDER BY a.fecha_aportacion DESC
             LIMIT 100",
            $usuario_id
        ));

        wp_send_json_success(['aportaciones' => $aportaciones]);
    }

    /**
     * AJAX: Carga ranking actualizado
     *
     * @return void
     */
    public function ajax_cargar_ranking() {
        check_ajax_referer('compostaje_dashboard_nonce', 'nonce');

        $periodo = sanitize_text_field($_POST['periodo'] ?? 'total');
        $limite = intval($_POST['limite'] ?? 10);

        $ranking = $this->obtener_ranking($periodo, $limite);

        wp_send_json_success(['ranking' => $ranking]);
    }

    /**
     * AJAX: Exporta datos del usuario
     *
     * @return void
     */
    public function ajax_exportar_datos() {
        check_ajax_referer('compostaje_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
        }

        $formato = sanitize_text_field($_POST['formato'] ?? 'json');
        $aportaciones = $this->obtener_aportaciones_usuario($usuario_id, 1000);
        $balance = $this->obtener_balance_usuario($usuario_id);

        $datos = [
            'usuario' => wp_get_current_user()->display_name,
            'fecha_exportacion' => current_time('mysql'),
            'balance' => $balance,
            'aportaciones' => $aportaciones,
        ];

        if ($formato === 'csv') {
            // Preparar CSV
            $csv_content = $this->generar_csv($aportaciones);
            wp_send_json_success(['csv' => $csv_content, 'filename' => 'compostaje-export-' . date('Y-m-d') . '.csv']);
        } else {
            wp_send_json_success(['datos' => $datos]);
        }
    }

    /**
     * Genera contenido CSV de aportaciones
     *
     * @param array $aportaciones Lista de aportaciones
     * @return string
     */
    private function generar_csv($aportaciones) {
        $lineas = [];
        $lineas[] = implode(',', ['Fecha', 'Compostera', 'Material', 'Categoria', 'Cantidad (kg)', 'Puntos', 'CO2 Evitado (kg)', 'Estado']);

        foreach ($aportaciones as $aportacion) {
            $lineas[] = implode(',', [
                $aportacion->fecha_aportacion,
                '"' . str_replace('"', '""', $aportacion->nombre_punto) . '"',
                $this->obtener_nombre_material($aportacion->tipo_material),
                $aportacion->categoria_material,
                number_format($aportacion->cantidad_kg, 2),
                $aportacion->puntos_obtenidos,
                number_format($aportacion->co2_evitado_kg, 2),
                $aportacion->validado ? 'Validado' : 'Pendiente',
            ]);
        }

        return implode("\n", $lineas);
    }
}

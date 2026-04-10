<?php
/**
 * Dashboard Tabs para Carpooling
 *
 * Proporciona tabs de usuario para gestionar viajes compartidos,
 * reservas y estadisticas de impacto ambiental.
 *
 * @package FlavorPlatform
 * @subpackage Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para los tabs del dashboard de usuario de Carpooling
 */
class Flavor_Carpooling_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Carpooling_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Tabla de viajes
     *
     * @var string
     */
    private $tabla_viajes;

    /**
     * Tabla de reservas
     *
     * @var string
     */
    private $tabla_reservas;

    /**
     * Tabla de valoraciones
     *
     * @var string
     */
    private $tabla_valoraciones;

    /**
     * Tabla de vehiculos
     *
     * @var string
     */
    private $tabla_vehiculos;

    /**
     * Tabla de rutas recurrentes
     *
     * @var string
     */
    private $tabla_rutas_recurrentes;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;

        $this->tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
        $this->tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
        $this->tabla_valoraciones = $wpdb->prefix . 'flavor_carpooling_valoraciones';
        $this->tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';
        $this->tabla_rutas_recurrentes = $wpdb->prefix . 'flavor_carpooling_rutas_recurrentes';

        $this->init();
    }

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Carpooling_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializacion de hooks
     */
    private function init() {
        // Registrar tabs en el dashboard de usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 50);

        // Encolar assets cuando se necesiten
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers para el dashboard
        add_action('wp_ajax_carpooling_dashboard_cancelar_viaje', [$this, 'ajax_cancelar_viaje']);
        add_action('wp_ajax_carpooling_dashboard_finalizar_viaje', [$this, 'ajax_finalizar_viaje']);
        add_action('wp_ajax_carpooling_dashboard_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_carpooling_dashboard_valorar', [$this, 'ajax_valorar']);
    }

    /**
     * Registrar assets del dashboard
     */
    public function registrar_assets() {
        $plugin_url = plugins_url('/', __FILE__);
        $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

        // CSS del dashboard
        wp_register_style(
            'carpooling-dashboard',
            $plugin_url . 'assets/css/carpooling-dashboard.css',
            [],
            $version
        );

        // JavaScript del dashboard
        wp_register_script(
            'carpooling-dashboard',
            $plugin_url . 'assets/js/carpooling-dashboard.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('carpooling-dashboard', 'carpoolingDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('carpooling_dashboard_nonce'),
            'i18n' => [
                'confirmarCancelar' => __('¿Seguro que quieres cancelar este viaje?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarCancelarReserva' => __('¿Seguro que quieres cancelar esta reserva?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarFinalizar' => __('¿Marcar este viaje como finalizado?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'gracias' => __('¡Gracias por tu valoración!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets cuando se muestran los tabs
     */
    private function encolar_assets() {
        wp_enqueue_style('carpooling-dashboard');
        wp_enqueue_script('carpooling-dashboard');

        // Leaflet para mapas mini
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs actualizados
     */
    public function registrar_tabs($tabs) {
        if (!$this->tablas_existen()) {
            return $tabs;
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $tabs;
        }

        // Tab: Mis Viajes (como conductor)
        $tabs['carpooling-mis-viajes'] = [
            'label' => __('Mis Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'car',
            'callback' => [$this, 'render_tab_mis_viajes'],
            'orden' => 50,
            'badge' => $this->contar_viajes_proximos($usuario_id),
            'grupo' => 'carpooling',
        ];

        // Tab: Mis Reservas (como pasajero)
        $tabs['carpooling-mis-reservas'] = [
            'label' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'tickets-alt',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden' => 51,
            'badge' => $this->contar_reservas_activas($usuario_id),
            'grupo' => 'carpooling',
        ];

        // Tab: Estadisticas
        $tabs['carpooling-estadisticas'] = [
            'label' => __('Impacto Ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'chart-area',
            'callback' => [$this, 'render_tab_estadisticas'],
            'orden' => 52,
            'grupo' => 'carpooling',
        ];

        return $tabs;
    }

    /**
     * Verificar si las tablas existen
     *
     * @return bool
     */
    private function tablas_existen() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->tabla_viajes)) === $this->tabla_viajes;
    }

    /**
     * Contar viajes proximos como conductor
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_viajes_proximos($usuario_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_viajes}
             WHERE conductor_id = %d
             AND estado IN ('activo', 'completo')
             AND fecha_salida >= NOW()",
            $usuario_id
        ));
    }

    /**
     * Contar reservas activas como pasajero
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_reservas_activas($usuario_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.pasajero_id = %d
             AND r.estado IN ('pendiente', 'confirmada')
             AND v.fecha_salida >= NOW()",
            $usuario_id
        ));
    }

    /**
     * Renderizar tab: Mis Viajes
     */
    public function render_tab_mis_viajes() {
        $this->encolar_assets();

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p class="carpooling-login-requerido">' . esc_html__('Inicia sesión para ver tus viajes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;

        // Obtener viajes proximos
        $viajes_proximos = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*,
                    (SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE viaje_id = v.id AND estado = 'confirmada') as reservas_confirmadas,
                    (SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE viaje_id = v.id AND estado = 'pendiente') as reservas_pendientes
             FROM {$this->tabla_viajes} v
             WHERE v.conductor_id = %d
             AND v.fecha_salida >= NOW()
             ORDER BY v.fecha_salida ASC
             LIMIT 20",
            $usuario_id
        ));

        // Obtener historial de viajes
        $viajes_historial = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*,
                    (SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE viaje_id = v.id AND estado IN ('confirmada', 'completada')) as pasajeros_totales
             FROM {$this->tabla_viajes} v
             WHERE v.conductor_id = %d
             AND (v.fecha_salida < NOW() OR v.estado = 'finalizado')
             ORDER BY v.fecha_salida DESC
             LIMIT 10",
            $usuario_id
        ));

        // Estadisticas rapidas
        $estadisticas_conductor = $this->obtener_estadisticas_conductor($usuario_id);
        ?>
        <div class="carpooling-dashboard-tab carpooling-mis-viajes">
            <!-- Resumen rapido -->
            <div class="carpooling-resumen-rapido">
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo esc_html($estadisticas_conductor['total_viajes']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Viajes publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo esc_html($estadisticas_conductor['pasajeros_transportados']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="resumen-item valoracion">
                    <span class="resumen-numero">
                        <?php if ($estadisticas_conductor['valoracion_media'] > 0): ?>
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php echo number_format($estadisticas_conductor['valoracion_media'], 1); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                    <span class="resumen-label"><?php esc_html_e('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Accion: Publicar viaje -->
            <div class="carpooling-accion-principal">
                <a href="<?php echo esc_url($this->obtener_url_publicar_viaje()); ?>" class="btn btn-primary btn-publicar-viaje">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Publicar nuevo viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <!-- Proximos viajes -->
            <div class="carpooling-seccion">
                <h3 class="seccion-titulo">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Próximos viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <?php if (empty($viajes_proximos)): ?>
                    <div class="carpooling-vacio">
                        <span class="dashicons dashicons-car"></span>
                        <p><?php esc_html_e('No tienes viajes programados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo esc_url($this->obtener_url_publicar_viaje()); ?>" class="btn btn-outline">
                            <?php esc_html_e('Publicar mi primer viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="carpooling-viajes-lista">
                        <?php foreach ($viajes_proximos as $viaje): ?>
                            <?php $this->render_viaje_conductor_card($viaje); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historial -->
            <?php if (!empty($viajes_historial)): ?>
                <div class="carpooling-seccion carpooling-historial">
                    <h3 class="seccion-titulo">
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Historial de viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="carpooling-viajes-historial">
                        <?php foreach ($viajes_historial as $viaje): ?>
                            <?php $this->render_viaje_historial_card($viaje); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar tab: Mis Reservas
     */
    public function render_tab_mis_reservas() {
        $this->encolar_assets();

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p class="carpooling-login-requerido">' . esc_html__('Inicia sesión para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;

        // Obtener reservas activas (proximos viajes)
        $reservas_activas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, v.*,
                    r.id as reserva_id,
                    r.estado as estado_reserva,
                    v.estado as estado_viaje,
                    u.display_name as nombre_conductor,
                    u.ID as conductor_id
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             JOIN {$wpdb->users} u ON v.conductor_id = u.ID
             WHERE r.pasajero_id = %d
             AND r.estado IN ('pendiente', 'confirmada')
             AND v.fecha_salida >= NOW()
             ORDER BY v.fecha_salida ASC
             LIMIT 20",
            $usuario_id
        ));

        // Historial de reservas
        $reservas_historial = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, v.*,
                    r.id as reserva_id,
                    r.estado as estado_reserva,
                    v.estado as estado_viaje,
                    u.display_name as nombre_conductor,
                    u.ID as conductor_id,
                    (SELECT puntuacion FROM {$this->tabla_valoraciones}
                     WHERE viaje_id = v.id AND valorador_id = %d LIMIT 1) as ya_valorado
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             JOIN {$wpdb->users} u ON v.conductor_id = u.ID
             WHERE r.pasajero_id = %d
             AND (r.estado IN ('completada', 'cancelada') OR v.fecha_salida < NOW())
             ORDER BY v.fecha_salida DESC
             LIMIT 10",
            $usuario_id,
            $usuario_id
        ));

        // Estadisticas como pasajero
        $estadisticas_pasajero = $this->obtener_estadisticas_pasajero($usuario_id);
        ?>
        <div class="carpooling-dashboard-tab carpooling-mis-reservas">
            <!-- Resumen rapido -->
            <div class="carpooling-resumen-rapido">
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo esc_html($estadisticas_pasajero['viajes_realizados']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Viajes realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="resumen-item ahorro">
                    <span class="resumen-numero"><?php echo number_format($estadisticas_pasajero['dinero_ahorrado'], 2); ?>€</span>
                    <span class="resumen-label"><?php esc_html_e('Ahorrado (aprox.)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="resumen-item eco">
                    <span class="resumen-numero"><?php echo number_format($estadisticas_pasajero['co2_evitado'], 1); ?> kg</span>
                    <span class="resumen-label"><?php esc_html_e('CO₂ evitado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Accion: Buscar viaje -->
            <div class="carpooling-accion-principal">
                <a href="<?php echo esc_url($this->obtener_url_buscar_viaje()); ?>" class="btn btn-primary btn-buscar-viaje">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Buscar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <!-- Reservas activas -->
            <div class="carpooling-seccion">
                <h3 class="seccion-titulo">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <?php esc_html_e('Mis próximos viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <?php if (empty($reservas_activas)): ?>
                    <div class="carpooling-vacio">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        <p><?php esc_html_e('No tienes reservas activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo esc_url($this->obtener_url_buscar_viaje()); ?>" class="btn btn-outline">
                            <?php esc_html_e('Buscar un viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="carpooling-reservas-lista">
                        <?php foreach ($reservas_activas as $reserva): ?>
                            <?php $this->render_reserva_card($reserva); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historial -->
            <?php if (!empty($reservas_historial)): ?>
                <div class="carpooling-seccion carpooling-historial">
                    <h3 class="seccion-titulo">
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Historial de viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="carpooling-reservas-historial">
                        <?php foreach ($reservas_historial as $reserva): ?>
                            <?php $this->render_reserva_historial_card($reserva); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar tab: Estadisticas
     */
    public function render_tab_estadisticas() {
        $this->encolar_assets();

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p class="carpooling-login-requerido">' . esc_html__('Inicia sesión para ver tus estadísticas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $estadisticas = $this->obtener_estadisticas_completas($usuario_id);
        ?>
        <div class="carpooling-dashboard-tab carpooling-estadisticas">
            <div class="estadisticas-header">
                <h3><?php esc_html_e('Tu impacto ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="estadisticas-subtitulo">
                    <?php esc_html_e('Gracias por compartir coche, estos son tus logros:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- KPIs principales -->
            <div class="estadisticas-kpis">
                <div class="kpi-card kpi-co2">
                    <div class="kpi-icono">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <div class="kpi-contenido">
                        <span class="kpi-valor"><?php echo number_format($estadisticas['co2_total_evitado'], 1); ?></span>
                        <span class="kpi-unidad">kg CO₂</span>
                        <span class="kpi-label"><?php esc_html_e('Emisiones evitadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="kpi-equivalencia">
                        <span class="dashicons dashicons-editor-help"></span>
                        <span class="tooltip">
                            <?php
                            $arboles_equivalentes = round($estadisticas['co2_total_evitado'] / 21, 1);
                            printf(
                                esc_html__('Equivale a lo que absorben %s árboles en un año', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                '<strong>' . esc_html($arboles_equivalentes) . '</strong>'
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <div class="kpi-card kpi-km">
                    <div class="kpi-icono">
                        <span class="dashicons dashicons-location-alt"></span>
                    </div>
                    <div class="kpi-contenido">
                        <span class="kpi-valor"><?php echo number_format($estadisticas['km_compartidos'], 0); ?></span>
                        <span class="kpi-unidad">km</span>
                        <span class="kpi-label"><?php esc_html_e('Kilómetros compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="kpi-card kpi-ahorro">
                    <div class="kpi-icono">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="kpi-contenido">
                        <span class="kpi-valor"><?php echo number_format($estadisticas['ahorro_estimado'], 2); ?></span>
                        <span class="kpi-unidad">€</span>
                        <span class="kpi-label"><?php esc_html_e('Ahorro estimado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="kpi-card kpi-combustible">
                    <div class="kpi-icono">
                        <span class="dashicons dashicons-dashboard"></span>
                    </div>
                    <div class="kpi-contenido">
                        <span class="kpi-valor"><?php echo number_format($estadisticas['litros_combustible_ahorrados'], 1); ?></span>
                        <span class="kpi-unidad">L</span>
                        <span class="kpi-label"><?php esc_html_e('Combustible ahorrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Desglose conductor/pasajero -->
            <div class="estadisticas-desglose">
                <div class="desglose-columna desglose-conductor">
                    <h4>
                        <span class="dashicons dashicons-car"></span>
                        <?php esc_html_e('Como conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <ul class="desglose-lista">
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Viajes realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo esc_html($estadisticas['viajes_como_conductor']); ?></span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Pasajeros transportados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo esc_html($estadisticas['pasajeros_transportados']); ?></span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Km como conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo number_format($estadisticas['km_como_conductor'], 0); ?> km</span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Ingresos totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo number_format($estadisticas['ingresos_conductor'], 2); ?>€</span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Valoración media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor">
                                <?php if ($estadisticas['valoracion_conductor'] > 0): ?>
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php echo number_format($estadisticas['valoracion_conductor'], 1); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="desglose-columna desglose-pasajero">
                    <h4>
                        <span class="dashicons dashicons-groups"></span>
                        <?php esc_html_e('Como pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <ul class="desglose-lista">
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Viajes realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo esc_html($estadisticas['viajes_como_pasajero']); ?></span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Km como pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo number_format($estadisticas['km_como_pasajero'], 0); ?> km</span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Gastos totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor"><?php echo number_format($estadisticas['gastos_pasajero'], 2); ?>€</span>
                        </li>
                        <li>
                            <span class="desglose-label"><?php esc_html_e('Ahorro vs coche propio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="desglose-valor ahorro"><?php echo number_format($estadisticas['ahorro_vs_coche_propio'], 2); ?>€</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Proximo viaje con mapa -->
            <?php $proximo_viaje = $this->obtener_proximo_viaje($usuario_id); ?>
            <?php if ($proximo_viaje): ?>
                <div class="estadisticas-proximo-viaje">
                    <h4>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Tu próximo viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <div class="proximo-viaje-card">
                        <div class="viaje-info">
                            <div class="viaje-ruta">
                                <span class="origen"><?php echo esc_html($proximo_viaje->origen); ?></span>
                                <span class="flecha">→</span>
                                <span class="destino"><?php echo esc_html($proximo_viaje->destino); ?></span>
                            </div>
                            <div class="viaje-fecha">
                                <span class="dashicons dashicons-clock"></span>
                                <?php
                                echo esc_html(date_i18n('l j F, H:i', strtotime($proximo_viaje->fecha_salida)));
                                ?>
                            </div>
                            <div class="viaje-rol">
                                <?php if ($proximo_viaje->es_conductor): ?>
                                    <span class="badge badge-conductor"><?php esc_html_e('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-pasajero"><?php esc_html_e('Pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($proximo_viaje->origen_lat && $proximo_viaje->destino_lat): ?>
                            <div class="viaje-mapa-mini" id="mapa-proximo-viaje"
                                 data-origen-lat="<?php echo esc_attr($proximo_viaje->origen_lat); ?>"
                                 data-origen-lng="<?php echo esc_attr($proximo_viaje->origen_lng); ?>"
                                 data-destino-lat="<?php echo esc_attr($proximo_viaje->destino_lat); ?>"
                                 data-destino-lng="<?php echo esc_attr($proximo_viaje->destino_lng); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Logros/Badges -->
            <div class="estadisticas-logros">
                <h4>
                    <span class="dashicons dashicons-awards"></span>
                    <?php esc_html_e('Tus logros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h4>
                <div class="logros-grid">
                    <?php echo wp_kses_post($this->render_logros($estadisticas)); ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Inicializar mapa mini del proximo viaje
            var mapaContainer = document.getElementById('mapa-proximo-viaje');
            if (mapaContainer && typeof L !== 'undefined') {
                var origenLat = parseFloat(mapaContainer.dataset.origenLat);
                var origenLng = parseFloat(mapaContainer.dataset.origenLng);
                var destinoLat = parseFloat(mapaContainer.dataset.destinoLat);
                var destinoLng = parseFloat(mapaContainer.dataset.destinoLng);

                var mapa = L.map('mapa-proximo-viaje', {
                    scrollWheelZoom: false,
                    dragging: false,
                    zoomControl: false
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapa);

                var markerOrigen = L.marker([origenLat, origenLng]).addTo(mapa);
                var markerDestino = L.marker([destinoLat, destinoLng]).addTo(mapa);

                var linea = L.polyline([
                    [origenLat, origenLng],
                    [destinoLat, destinoLng]
                ], {color: '#3b82f6', weight: 3, dashArray: '10, 10'}).addTo(mapa);

                var bounds = L.latLngBounds([
                    [origenLat, origenLng],
                    [destinoLat, destinoLng]
                ]);
                mapa.fitBounds(bounds, {padding: [30, 30]});
            }
        });
        </script>
        <?php
    }

    /**
     * Renderizar card de viaje (como conductor)
     *
     * @param object $viaje Datos del viaje
     */
    private function render_viaje_conductor_card($viaje) {
        $es_proximo = strtotime($viaje->fecha_salida) <= strtotime('+24 hours');
        $estado_clase = 'estado-' . sanitize_html_class($viaje->estado);
        ?>
        <div class="carpooling-viaje-card conductor-card <?php echo esc_attr($estado_clase); ?> <?php echo $es_proximo ? 'es-proximo' : ''; ?>"
             data-viaje-id="<?php echo esc_attr($viaje->id); ?>">

            <?php if ($es_proximo): ?>
                <div class="viaje-badge-proximo">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e('Próximamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php endif; ?>

            <div class="viaje-ruta-visual">
                <div class="punto-origen">
                    <span class="punto-marker"></span>
                    <span class="punto-texto"><?php echo esc_html($viaje->origen); ?></span>
                </div>
                <div class="ruta-linea"></div>
                <div class="punto-destino">
                    <span class="punto-marker"></span>
                    <span class="punto-texto"><?php echo esc_html($viaje->destino); ?></span>
                </div>
            </div>

            <div class="viaje-fecha-hora">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html(date_i18n('l j F Y, H:i', strtotime($viaje->fecha_salida))); ?>
            </div>

            <div class="viaje-detalles">
                <div class="detalle plazas">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="valor">
                        <?php
                        $plazas_ocupadas = $viaje->plazas_disponibles - ($viaje->plazas_disponibles - intval($viaje->reservas_confirmadas ?? 0));
                        printf(
                            esc_html__('%d/%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            $viaje->reservas_confirmadas ?? 0,
                            $viaje->plazas_disponibles + intval($viaje->reservas_confirmadas ?? 0)
                        );
                        ?>
                    </span>
                </div>
                <?php if ($viaje->precio_por_plaza > 0): ?>
                    <div class="detalle precio">
                        <span class="dashicons dashicons-money-alt"></span>
                        <span class="valor"><?php echo number_format($viaje->precio_por_plaza, 2); ?>€/plaza</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($viaje->reservas_pendientes > 0): ?>
                <div class="viaje-alerta-reservas">
                    <span class="dashicons dashicons-bell"></span>
                    <?php
                    printf(
                        esc_html(_n(
                            '%d solicitud pendiente',
                            '%d solicitudes pendientes',
                            $viaje->reservas_pendientes,
                            FLAVOR_PLATFORM_TEXT_DOMAIN
                        )),
                        $viaje->reservas_pendientes
                    );
                    ?>
                </div>
            <?php endif; ?>

            <div class="viaje-acciones">
                <a href="<?php echo esc_url($this->obtener_url_detalle_viaje($viaje->id)); ?>" class="btn btn-sm btn-outline">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php if ($viaje->estado === 'activo'): ?>
                    <button type="button" class="btn btn-sm btn-danger btn-cancelar-viaje" data-viaje-id="<?php echo esc_attr($viaje->id); ?>">
                        <span class="dashicons dashicons-no"></span>
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Mapa mini si hay coordenadas -->
            <?php if ($viaje->origen_lat && $viaje->destino_lat): ?>
                <div class="viaje-mapa-mini-thumb"
                     data-origen-lat="<?php echo esc_attr($viaje->origen_lat); ?>"
                     data-origen-lng="<?php echo esc_attr($viaje->origen_lng); ?>"
                     data-destino-lat="<?php echo esc_attr($viaje->destino_lat); ?>"
                     data-destino-lng="<?php echo esc_attr($viaje->destino_lng); ?>">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar card de viaje del historial
     *
     * @param object $viaje Datos del viaje
     */
    private function render_viaje_historial_card($viaje) {
        ?>
        <div class="carpooling-viaje-historial-card">
            <div class="historial-fecha">
                <?php echo esc_html(date_i18n('j M Y', strtotime($viaje->fecha_salida))); ?>
            </div>
            <div class="historial-ruta">
                <span class="origen"><?php echo esc_html($viaje->origen); ?></span>
                <span class="flecha">→</span>
                <span class="destino"><?php echo esc_html($viaje->destino); ?></span>
            </div>
            <div class="historial-info">
                <span class="pasajeros">
                    <span class="dashicons dashicons-groups"></span>
                    <?php echo esc_html($viaje->pasajeros_totales ?? 0); ?>
                </span>
                <span class="estado estado-<?php echo esc_attr($viaje->estado); ?>">
                    <?php echo esc_html($this->traducir_estado($viaje->estado)); ?>
                </span>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar card de reserva
     *
     * @param object $reserva Datos de la reserva con viaje
     */
    private function render_reserva_card($reserva) {
        $es_proximo = strtotime($reserva->fecha_salida) <= strtotime('+24 hours');
        ?>
        <div class="carpooling-reserva-card <?php echo $es_proximo ? 'es-proximo' : ''; ?> estado-<?php echo esc_attr($reserva->estado_reserva); ?>"
             data-reserva-id="<?php echo esc_attr($reserva->reserva_id); ?>">

            <?php if ($es_proximo): ?>
                <div class="viaje-badge-proximo">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e('Próximamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php endif; ?>

            <div class="reserva-estado">
                <?php if ($reserva->estado_reserva === 'pendiente'): ?>
                    <span class="badge badge-warning"><?php esc_html_e('Pendiente de confirmación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php elseif ($reserva->estado_reserva === 'confirmada'): ?>
                    <span class="badge badge-success"><?php esc_html_e('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>

            <div class="viaje-ruta-visual">
                <div class="punto-origen">
                    <span class="punto-marker"></span>
                    <span class="punto-texto"><?php echo esc_html($reserva->origen); ?></span>
                </div>
                <div class="ruta-linea"></div>
                <div class="punto-destino">
                    <span class="punto-marker"></span>
                    <span class="punto-texto"><?php echo esc_html($reserva->destino); ?></span>
                </div>
            </div>

            <div class="viaje-fecha-hora">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html(date_i18n('l j F Y, H:i', strtotime($reserva->fecha_salida))); ?>
            </div>

            <div class="reserva-conductor">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Conductor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <strong><?php echo esc_html($reserva->nombre_conductor); ?></strong>
            </div>

            <div class="reserva-detalles">
                <div class="detalle plazas">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <?php
                    printf(
                        esc_html(_n('%d plaza', '%d plazas', $reserva->numero_plazas, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $reserva->numero_plazas
                    );
                    ?>
                </div>
                <?php if ($reserva->precio_total > 0): ?>
                    <div class="detalle precio">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php echo number_format($reserva->precio_total, 2); ?>€
                    </div>
                <?php endif; ?>
            </div>

            <div class="reserva-acciones">
                <?php if ($reserva->estado_reserva !== 'cancelada'): ?>
                    <button type="button" class="btn btn-sm btn-danger btn-cancelar-reserva" data-reserva-id="<?php echo esc_attr($reserva->reserva_id); ?>">
                        <span class="dashicons dashicons-no"></span>
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Mapa mini -->
            <?php if ($reserva->origen_lat && $reserva->destino_lat): ?>
                <div class="viaje-mapa-mini-thumb"
                     data-origen-lat="<?php echo esc_attr($reserva->origen_lat); ?>"
                     data-origen-lng="<?php echo esc_attr($reserva->origen_lng); ?>"
                     data-destino-lat="<?php echo esc_attr($reserva->destino_lat); ?>"
                     data-destino-lng="<?php echo esc_attr($reserva->destino_lng); ?>">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar card de reserva del historial
     *
     * @param object $reserva Datos de la reserva
     */
    private function render_reserva_historial_card($reserva) {
        ?>
        <div class="carpooling-reserva-historial-card">
            <div class="historial-fecha">
                <?php echo esc_html(date_i18n('j M Y', strtotime($reserva->fecha_salida))); ?>
            </div>
            <div class="historial-ruta">
                <span class="origen"><?php echo esc_html($reserva->origen); ?></span>
                <span class="flecha">→</span>
                <span class="destino"><?php echo esc_html($reserva->destino); ?></span>
            </div>
            <div class="historial-conductor">
                <?php echo esc_html($reserva->nombre_conductor); ?>
            </div>
            <div class="historial-acciones">
                <?php if ($reserva->estado_reserva === 'completada' && !$reserva->ya_valorado): ?>
                    <button type="button" class="btn btn-sm btn-primary btn-valorar" data-reserva-id="<?php echo esc_attr($reserva->reserva_id); ?>" data-conductor-id="<?php echo esc_attr($reserva->conductor_id); ?>">
                        <span class="dashicons dashicons-star-empty"></span>
                        <?php esc_html_e('Valorar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php elseif ($reserva->ya_valorado): ?>
                    <span class="valorado">
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php echo esc_html($reserva->ya_valorado); ?>
                    </span>
                <?php endif; ?>
                <span class="estado estado-<?php echo esc_attr($reserva->estado_reserva); ?>">
                    <?php echo esc_html($this->traducir_estado_reserva($reserva->estado_reserva)); ?>
                </span>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadisticas como conductor
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_conductor($usuario_id) {
        global $wpdb;

        $total_viajes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE conductor_id = %d",
            $usuario_id
        ));

        $pasajeros = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(r.numero_plazas), 0)
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE v.conductor_id = %d AND r.estado IN ('confirmada', 'completada')",
            $usuario_id
        ));

        $valoracion = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(puntuacion) FROM {$this->tabla_valoraciones}
             WHERE valorado_id = %d AND tipo_valoracion = 'conductor'",
            $usuario_id
        ));

        return [
            'total_viajes' => $total_viajes,
            'pasajeros_transportados' => $pasajeros,
            'valoracion_media' => floatval($valoracion) ?: 0,
        ];
    }

    /**
     * Obtener estadisticas como pasajero
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_pasajero($usuario_id) {
        global $wpdb;

        $resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(DISTINCT r.id) as viajes_realizados,
                COALESCE(SUM(r.precio_total), 0) as gastos_totales
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.pasajero_id = %d
             AND r.estado IN ('confirmada', 'completada')
             AND v.fecha_salida < NOW()",
            $usuario_id
        ));

        // Calcular km aproximados y CO2 evitado
        $km_totales = $this->calcular_km_como_pasajero($usuario_id);
        $co2_evitado = $km_totales * 0.12; // ~120g CO2 por km en coche medio

        // Ahorro estimado (comparado con taxi/VTC)
        $ahorro_estimado = $km_totales * 0.80 - floatval($resultado->gastos_totales ?? 0);

        return [
            'viajes_realizados' => intval($resultado->viajes_realizados ?? 0),
            'dinero_ahorrado' => max(0, $ahorro_estimado),
            'co2_evitado' => $co2_evitado,
        ];
    }

    /**
     * Obtener estadisticas completas
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_estadisticas_completas($usuario_id) {
        global $wpdb;

        // Estadisticas como conductor
        $conductor_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(DISTINCT v.id) as viajes_conductor,
                COALESCE(SUM(r.numero_plazas), 0) as pasajeros_transportados
             FROM {$this->tabla_viajes} v
             LEFT JOIN {$this->tabla_reservas} r ON v.id = r.viaje_id AND r.estado IN ('confirmada', 'completada')
             WHERE v.conductor_id = %d
             AND v.estado = 'finalizado'",
            $usuario_id
        ));

        // Ingresos como conductor
        $ingresos_conductor = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(r.precio_total), 0)
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE v.conductor_id = %d
             AND r.estado IN ('confirmada', 'completada')",
            $usuario_id
        ));

        // Estadisticas como pasajero
        $pasajero_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as viajes_pasajero,
                COALESCE(SUM(r.precio_total), 0) as gastos_totales
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.pasajero_id = %d
             AND r.estado IN ('confirmada', 'completada')
             AND v.fecha_salida < NOW()",
            $usuario_id
        ));

        // Calcular kilometros
        $km_conductor = $this->calcular_km_como_conductor($usuario_id);
        $km_pasajero = $this->calcular_km_como_pasajero($usuario_id);
        $km_totales = $km_conductor + $km_pasajero;

        // Calcular impacto ambiental
        // Factor: 120g CO2 por km (coche medio)
        // Al compartir, cada pasajero "ahorra" esas emisiones que hubiera generado viajando solo
        $pasajeros_totales = intval($conductor_stats->pasajeros_transportados ?? 0);
        $co2_evitado_conductor = $km_conductor * $pasajeros_totales * 0.12;
        $co2_evitado_pasajero = $km_pasajero * 0.12;
        $co2_total = $co2_evitado_conductor + $co2_evitado_pasajero;

        // Combustible ahorrado (7L/100km media)
        $litros_ahorrados = ($km_conductor * $pasajeros_totales + $km_pasajero) * 0.07;

        // Ahorro economico estimado
        // Conductor: compartir gastos reduce coste individual
        // Pasajero: vs taxi (~1€/km) o coche propio (~0.30€/km)
        $ahorro_estimado = ($km_pasajero * 0.60) + ($km_conductor * $pasajeros_totales * 0.15);

        // Valoraciones
        $valoracion_conductor = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(puntuacion) FROM {$this->tabla_valoraciones}
             WHERE valorado_id = %d AND tipo_valoracion = 'conductor'",
            $usuario_id
        ));

        return [
            'viajes_como_conductor' => intval($conductor_stats->viajes_conductor ?? 0),
            'viajes_como_pasajero' => intval($pasajero_stats->viajes_pasajero ?? 0),
            'pasajeros_transportados' => intval($conductor_stats->pasajeros_transportados ?? 0),
            'km_como_conductor' => $km_conductor,
            'km_como_pasajero' => $km_pasajero,
            'km_compartidos' => $km_totales,
            'ingresos_conductor' => $ingresos_conductor,
            'gastos_pasajero' => floatval($pasajero_stats->gastos_totales ?? 0),
            'co2_total_evitado' => $co2_total,
            'litros_combustible_ahorrados' => $litros_ahorrados,
            'ahorro_estimado' => $ahorro_estimado,
            'ahorro_vs_coche_propio' => $km_pasajero * 0.30 - floatval($pasajero_stats->gastos_totales ?? 0),
            'valoracion_conductor' => $valoracion_conductor ?: 0,
        ];
    }

    /**
     * Calcular km totales como conductor
     *
     * @param int $usuario_id ID del usuario
     * @return float
     */
    private function calcular_km_como_conductor($usuario_id) {
        global $wpdb;

        $viajes = $wpdb->get_results($wpdb->prepare(
            "SELECT origen_lat, origen_lng, destino_lat, destino_lng
             FROM {$this->tabla_viajes}
             WHERE conductor_id = %d
             AND estado = 'finalizado'
             AND origen_lat IS NOT NULL
             AND destino_lat IS NOT NULL",
            $usuario_id
        ));

        $km_total = 0;
        foreach ($viajes as $viaje) {
            $km_total += $this->calcular_distancia_km(
                $viaje->origen_lat,
                $viaje->origen_lng,
                $viaje->destino_lat,
                $viaje->destino_lng
            );
        }

        return $km_total;
    }

    /**
     * Calcular km totales como pasajero
     *
     * @param int $usuario_id ID del usuario
     * @return float
     */
    private function calcular_km_como_pasajero($usuario_id) {
        global $wpdb;

        $viajes = $wpdb->get_results($wpdb->prepare(
            "SELECT v.origen_lat, v.origen_lng, v.destino_lat, v.destino_lng
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.pasajero_id = %d
             AND r.estado IN ('confirmada', 'completada')
             AND v.fecha_salida < NOW()
             AND v.origen_lat IS NOT NULL
             AND v.destino_lat IS NOT NULL",
            $usuario_id
        ));

        $km_total = 0;
        foreach ($viajes as $viaje) {
            $km_total += $this->calcular_distancia_km(
                $viaje->origen_lat,
                $viaje->origen_lng,
                $viaje->destino_lat,
                $viaje->destino_lng
            );
        }

        return $km_total;
    }

    /**
     * Calcular distancia en km usando formula Haversine
     *
     * @param float $lat1 Latitud origen
     * @param float $lng1 Longitud origen
     * @param float $lat2 Latitud destino
     * @param float $lng2 Longitud destino
     * @return float
     */
    private function calcular_distancia_km($lat1, $lng1, $lat2, $lng2) {
        $radio_tierra = 6371; // km

        $lat1_rad = deg2rad($lat1);
        $lat2_rad = deg2rad($lat2);
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lng = deg2rad($lng2 - $lng1);

        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lng / 2) * sin($delta_lng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radio_tierra * $c;
    }

    /**
     * Obtener proximo viaje del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return object|null
     */
    private function obtener_proximo_viaje($usuario_id) {
        global $wpdb;

        // Buscar como conductor
        $viaje_conductor = $wpdb->get_row($wpdb->prepare(
            "SELECT *, 1 as es_conductor
             FROM {$this->tabla_viajes}
             WHERE conductor_id = %d
             AND estado IN ('activo', 'completo')
             AND fecha_salida >= NOW()
             ORDER BY fecha_salida ASC
             LIMIT 1",
            $usuario_id
        ));

        // Buscar como pasajero
        $viaje_pasajero = $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, 0 as es_conductor
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.pasajero_id = %d
             AND r.estado = 'confirmada'
             AND v.fecha_salida >= NOW()
             ORDER BY v.fecha_salida ASC
             LIMIT 1",
            $usuario_id
        ));

        // Retornar el mas proximo
        if (!$viaje_conductor && !$viaje_pasajero) {
            return null;
        }

        if (!$viaje_conductor) {
            return $viaje_pasajero;
        }

        if (!$viaje_pasajero) {
            return $viaje_conductor;
        }

        return strtotime($viaje_conductor->fecha_salida) <= strtotime($viaje_pasajero->fecha_salida)
            ? $viaje_conductor
            : $viaje_pasajero;
    }

    /**
     * Renderizar logros
     *
     * @param array $estadisticas Estadisticas del usuario
     * @return string HTML de los logros
     */
    private function render_logros($estadisticas) {
        $logros = [];

        // Logro: Primer viaje
        if ($estadisticas['viajes_como_conductor'] > 0 || $estadisticas['viajes_como_pasajero'] > 0) {
            $logros[] = [
                'icono' => 'flag',
                'nombre' => __('Primera vez', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Completaste tu primer viaje compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'conseguido' => true,
            ];
        }

        // Logro: 10 viajes
        $total_viajes = $estadisticas['viajes_como_conductor'] + $estadisticas['viajes_como_pasajero'];
        $logros[] = [
            'icono' => 'car',
            'nombre' => __('Viajero habitual', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Completa 10 viajes compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'conseguido' => $total_viajes >= 10,
            'progreso' => min(100, ($total_viajes / 10) * 100),
        ];

        // Logro: 100 km
        $logros[] = [
            'icono' => 'location-alt',
            'nombre' => __('Kilómetros verdes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Comparte 100 km', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'conseguido' => $estadisticas['km_compartidos'] >= 100,
            'progreso' => min(100, ($estadisticas['km_compartidos'] / 100) * 100),
        ];

        // Logro: 50 kg CO2
        $logros[] = [
            'icono' => 'cloud',
            'nombre' => __('Eco-héroe', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Evita 50 kg de CO₂', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'conseguido' => $estadisticas['co2_total_evitado'] >= 50,
            'progreso' => min(100, ($estadisticas['co2_total_evitado'] / 50) * 100),
        ];

        // Logro: 5 estrellas
        if ($estadisticas['valoracion_conductor'] >= 4.8 && $estadisticas['viajes_como_conductor'] >= 5) {
            $logros[] = [
                'icono' => 'star-filled',
                'nombre' => __('Conductor estrella', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Mantén una valoración de 4.8+ con 5 viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'conseguido' => true,
            ];
        }

        // Logro: 10 pasajeros
        $logros[] = [
            'icono' => 'groups',
            'nombre' => __('Socialista de la carretera', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Transporta a 10 pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'conseguido' => $estadisticas['pasajeros_transportados'] >= 10,
            'progreso' => min(100, ($estadisticas['pasajeros_transportados'] / 10) * 100),
        ];

        $html = '';
        foreach ($logros as $logro) {
            $clase_conseguido = $logro['conseguido'] ? 'conseguido' : 'bloqueado';
            $html .= '<div class="logro-item ' . esc_attr($clase_conseguido) . '">';
            $html .= '<div class="logro-icono"><span class="dashicons dashicons-' . esc_attr($logro['icono']) . '"></span></div>';
            $html .= '<div class="logro-info">';
            $html .= '<span class="logro-nombre">' . esc_html($logro['nombre']) . '</span>';
            $html .= '<span class="logro-descripcion">' . esc_html($logro['descripcion']) . '</span>';
            if (!$logro['conseguido'] && isset($logro['progreso'])) {
                $html .= '<div class="logro-progreso"><div class="progreso-barra" style="width:' . esc_attr($logro['progreso']) . '%"></div></div>';
            }
            $html .= '</div>';
            if ($logro['conseguido']) {
                $html .= '<span class="logro-check dashicons dashicons-yes-alt"></span>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Traducir estado de viaje
     *
     * @param string $estado Estado del viaje
     * @return string
     */
    private function traducir_estado($estado) {
        $estados = [
            'activo' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'completo' => __('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cancelado' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'finalizado' => __('Finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $estados[$estado] ?? $estado;
    }

    /**
     * Traducir estado de reserva
     *
     * @param string $estado Estado de la reserva
     * @return string
     */
    private function traducir_estado_reserva($estado) {
        $estados = [
            'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'completada' => __('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $estados[$estado] ?? $estado;
    }

    /**
     * Obtener URL para publicar viaje
     *
     * @return string
     */
    private function obtener_url_publicar_viaje() {
        $pagina = get_page_by_path('carpooling-publicar');
        if ($pagina) {
            return get_permalink($pagina->ID);
        }
        return home_url('/carpooling/publicar/');
    }

    /**
     * Obtener URL para buscar viaje
     *
     * @return string
     */
    private function obtener_url_buscar_viaje() {
        $pagina = get_page_by_path('carpooling-buscar');
        if ($pagina) {
            return get_permalink($pagina->ID);
        }
        return home_url('/carpooling/buscar/');
    }

    /**
     * Obtener URL de detalle de viaje
     *
     * @param int $viaje_id ID del viaje
     * @return string
     */
    private function obtener_url_detalle_viaje($viaje_id) {
        return add_query_arg('viaje', $viaje_id, home_url('/carpooling/viaje/'));
    }

    /**
     * AJAX: Cancelar viaje
     */
    public function ajax_cancelar_viaje() {
        check_ajax_referer('carpooling_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $usuario_id = get_current_user_id();

        // Verificar que el viaje pertenece al usuario
        $viaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_viajes} WHERE id = %d AND conductor_id = %d",
            $viaje_id,
            $usuario_id
        ));

        if (!$viaje) {
            wp_send_json_error(['message' => __('Viaje no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar estado
        $wpdb->update(
            $this->tabla_viajes,
            ['estado' => 'cancelado', 'updated_at' => current_time('mysql')],
            ['id' => $viaje_id]
        );

        // Cancelar reservas pendientes y confirmas
        $wpdb->update(
            $this->tabla_reservas,
            ['estado' => 'cancelada'],
            ['viaje_id' => $viaje_id, 'estado' => ['pendiente', 'confirmada']]
        );

        // Notificar a los pasajeros (implementar segun el sistema de notificaciones)
        do_action('carpooling_viaje_cancelado', $viaje_id);

        wp_send_json_success(['message' => __('Viaje cancelado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Finalizar viaje
     */
    public function ajax_finalizar_viaje() {
        check_ajax_referer('carpooling_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $usuario_id = get_current_user_id();

        // Verificar propiedad
        $viaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_viajes} WHERE id = %d AND conductor_id = %d",
            $viaje_id,
            $usuario_id
        ));

        if (!$viaje) {
            wp_send_json_error(['message' => __('Viaje no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar estados
        $wpdb->update(
            $this->tabla_viajes,
            ['estado' => 'finalizado', 'updated_at' => current_time('mysql')],
            ['id' => $viaje_id]
        );

        $wpdb->update(
            $this->tabla_reservas,
            ['estado' => 'completada'],
            ['viaje_id' => $viaje_id, 'estado' => 'confirmada']
        );

        do_action('carpooling_viaje_finalizado', $viaje_id);

        wp_send_json_success(['message' => __('Viaje marcado como finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('carpooling_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $reserva_id = absint($_POST['reserva_id'] ?? 0);
        $usuario_id = get_current_user_id();

        // Verificar propiedad
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.plazas_disponibles, v.plazas_ocupadas
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d AND r.pasajero_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar estado de reserva
        $wpdb->update(
            $this->tabla_reservas,
            ['estado' => 'cancelada'],
            ['id' => $reserva_id]
        );

        // Devolver plazas al viaje si estaba confirmada
        if ($reserva->estado === 'confirmada') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_viajes}
                 SET plazas_disponibles = plazas_disponibles + %d,
                     plazas_ocupadas = plazas_ocupadas - %d,
                     estado = CASE WHEN estado = 'completo' THEN 'activo' ELSE estado END
                 WHERE id = %d",
                $reserva->numero_plazas,
                $reserva->numero_plazas,
                $reserva->viaje_id
            ));
        }

        do_action('carpooling_reserva_cancelada', $reserva_id);

        wp_send_json_success(['message' => __('Reserva cancelada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Valorar viaje/conductor
     */
    public function ajax_valorar() {
        check_ajax_referer('carpooling_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $reserva_id = absint($_POST['reserva_id'] ?? 0);
        $puntuacion = absint($_POST['puntuacion'] ?? 0);
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');
        $usuario_id = get_current_user_id();

        if ($puntuacion < 1 || $puntuacion > 5) {
            wp_send_json_error(['message' => __('Puntuación inválida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que puede valorar
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.id as viaje_id
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d AND r.pasajero_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que no ha valorado ya
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_valoraciones}
             WHERE viaje_id = %d AND valorador_id = %d",
            $reserva->viaje_id,
            $usuario_id
        ));

        if ($existente) {
            wp_send_json_error(['message' => __('Ya has valorado este viaje', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Insertar valoracion
        $wpdb->insert(
            $this->tabla_valoraciones,
            [
                'viaje_id' => $reserva->viaje_id,
                'valorador_id' => $usuario_id,
                'valorado_id' => $reserva->conductor_id,
                'tipo_valoracion' => 'conductor',
                'puntuacion' => $puntuacion,
                'comentario' => $comentario,
                'fecha_valoracion' => current_time('mysql'),
            ]
        );

        wp_send_json_success(['message' => __('¡Gracias por tu valoración!', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }
}

// Inicializar
Flavor_Carpooling_Dashboard_Tab::get_instance();

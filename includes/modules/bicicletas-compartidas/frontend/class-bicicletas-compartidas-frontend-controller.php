<?php
/**
 * Frontend Controller para Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador frontend del módulo Bicicletas Compartidas
 */
class Flavor_Bicicletas_Compartidas_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Bicicletas_Compartidas_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Configuración del módulo
     * @var array
     */
    private $config = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->config = get_option('flavor_chat_ia_settings', []);
        $this->init();
    }

    /**
     * Obtiene instancia singleton
     * @return Flavor_Bicicletas_Compartidas_Frontend_Controller
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa el controlador
     */
    private function init() {
        // Shortcodes
        $shortcodes = [
            'flavor_bicicletas_mapa' => 'shortcode_mapa',
            'flavor_bicicletas_estaciones' => 'shortcode_estaciones',
            'flavor_bicicletas_disponibles' => 'shortcode_disponibles',
            'flavor_bicicletas_detalle' => 'shortcode_detalle',
            'flavor_bicicletas_reservar' => 'shortcode_reservar',
            'flavor_bicicletas_mis_prestamos' => 'shortcode_mis_prestamos',
            'flavor_bicicletas_prestamo_activo' => 'shortcode_prestamo_activo',
            'flavor_bicicletas_estadisticas' => 'shortcode_estadisticas',
            // Alias para compatibilidad
            'bicicletas_estaciones_cercanas' => 'shortcode_estaciones',
            'bicicletas_prestamo_actual' => 'shortcode_prestamo_activo',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_bicicletas_reservar', [$this, 'ajax_reservar']);
        add_action('wp_ajax_flavor_bicicletas_devolver', [$this, 'ajax_devolver']);
        add_action('wp_ajax_flavor_bicicletas_reportar_problema', [$this, 'ajax_reportar_problema']);
        add_action('wp_ajax_flavor_bicicletas_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_flavor_bicicletas_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_flavor_bicicletas_buscar_estaciones', [$this, 'ajax_buscar_estaciones']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);
    }

    /**
     * Registra assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugin_dir_url(dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-bicicletas-frontend',
            $base_url . 'assets/css/bicicletas-frontend.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-bicicletas-frontend',
            $base_url . 'assets/js/bicicletas-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-bicicletas-frontend', 'flavorBicicletasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_bicicletas_nonce'),
            'strings' => [
                'procesando' => __('Procesando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarReserva' => __('¿Confirmas la reserva de esta bicicleta?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarDevolucion' => __('¿Confirmas la devolución en esta estación?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarCancelar' => __('¿Cancelar esta reserva?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'bicicletaReservada' => __('Bicicleta reservada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'bicicletaDevuelta' => __('Bicicleta devuelta correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinUbicacion' => __('No se pudo obtener tu ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    private function cargar_assets() {
        wp_enqueue_style('flavor-bicicletas-frontend');
        wp_enqueue_script('flavor-bicicletas-frontend');

        // Leaflet para mapas
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
    }

    /**
     * Registra tabs en dashboard del usuario
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['bicicletas'] = [
            'titulo' => __('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-bike',
            'prioridad' => 50,
            'callback' => [$this, 'render_dashboard_tab'],
        ];
        return $tabs;
    }

    /**
     * Renderiza tab del dashboard
     */
    public function render_dashboard_tab() {
        $this->cargar_assets();
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        // Préstamo activo
        $prestamo_activo = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, b.codigo, b.tipo, b.marca, b.modelo, b.color
             FROM $tabla_prestamos p
             JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
             WHERE p.usuario_id = %d AND p.estado = 'activo'",
            $usuario_id
        ));

        // Estadísticas del usuario
        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_prestamos,
                SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) as completados,
                SUM(COALESCE(kilometros_recorridos, 0)) as km_totales,
                SUM(COALESCE(duracion_minutos, 0)) as minutos_totales,
                AVG(CASE WHEN valoracion > 0 THEN valoracion ELSE NULL END) as valoracion_promedio
             FROM $tabla_prestamos
             WHERE usuario_id = %d",
            $usuario_id
        ));

        // Historial reciente
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, b.codigo, b.tipo, b.marca, b.modelo
             FROM $tabla_prestamos p
             JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
             WHERE p.usuario_id = %d
             ORDER BY p.fecha_creacion DESC
             LIMIT 10",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-bicicletas-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-chart-bar"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas->total_prestamos ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Total préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-location"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($estadisticas->km_totales ?? 0, 1); ?> km</span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Km recorridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo $this->formatear_duracion($estadisticas->minutos_totales ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Tiempo pedaleando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-awards"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format(($estadisticas->km_totales ?? 0) * 0.21, 1); ?> kg</span>
                        <span class="flavor-kpi-etiqueta"><?php _e('CO₂ ahorrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($prestamo_activo): ?>
            <!-- Préstamo Activo -->
            <div class="flavor-panel flavor-panel-destacado">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-bike"></span>
                    <?php _e('Préstamo Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="flavor-prestamo-activo">
                    <div class="flavor-bici-info">
                        <div class="flavor-bici-codigo"><?php echo esc_html($prestamo_activo->codigo); ?></div>
                        <div class="flavor-bici-detalles">
                            <?php echo esc_html($prestamo_activo->marca . ' ' . $prestamo_activo->modelo); ?>
                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($prestamo_activo->tipo); ?>">
                                <?php echo esc_html(ucfirst($prestamo_activo->tipo)); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flavor-prestamo-tiempo">
                        <div class="flavor-tiempo-transcurrido"
                             data-inicio="<?php echo esc_attr($prestamo_activo->fecha_inicio); ?>">
                            <?php
                            $minutos_transcurridos = (time() - strtotime($prestamo_activo->fecha_inicio)) / 60;
                            echo $this->formatear_duracion($minutos_transcurridos);
                            ?>
                        </div>
                        <small><?php _e('Tiempo de uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                    </div>
                    <div class="flavor-prestamo-acciones">
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-devolver"
                                data-prestamo-id="<?php echo intval($prestamo_activo->id); ?>"
                                data-bicicleta-id="<?php echo intval($prestamo_activo->bicicleta_id); ?>">
                            <span class="dashicons dashicons-location"></span>
                            <?php _e('Devolver bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-reportar"
                                data-bicicleta-id="<?php echo intval($prestamo_activo->bicicleta_id); ?>">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Reportar problema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Acceso rápido -->
            <div class="flavor-acciones-rapidas">
                <a href="#mapa-estaciones" class="flavor-accion-card">
                    <span class="dashicons dashicons-location-alt"></span>
                    <span><?php _e('Ver estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <a href="#bicicletas-disponibles" class="flavor-accion-card">
                    <span class="dashicons dashicons-bike"></span>
                    <span><?php _e('Reservar bici', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Historial -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Historial de préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <?php if (!empty($historial)): ?>
                <div class="flavor-historial-lista">
                    <?php foreach ($historial as $item): ?>
                    <div class="flavor-historial-item <?php echo $item->estado === 'activo' ? 'activo' : ''; ?>">
                        <div class="flavor-historial-icono">
                            <span class="dashicons dashicons-bike"></span>
                        </div>
                        <div class="flavor-historial-info">
                            <strong><?php echo esc_html($item->codigo); ?></strong>
                            <span class="flavor-historial-tipo"><?php echo esc_html($item->marca . ' - ' . ucfirst($item->tipo)); ?></span>
                        </div>
                        <div class="flavor-historial-datos">
                            <span class="flavor-fecha"><?php echo date_i18n('j M Y', strtotime($item->fecha_inicio)); ?></span>
                            <?php if ($item->estado === 'finalizado'): ?>
                            <span class="flavor-duracion"><?php echo $this->formatear_duracion($item->duracion_minutos); ?></span>
                            <?php if ($item->kilometros_recorridos > 0): ?>
                            <span class="flavor-km"><?php echo number_format($item->kilometros_recorridos, 1); ?> km</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-historial-estado">
                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($item->estado); ?>">
                                <?php echo esc_html($this->obtener_etiqueta_estado($item->estado)); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('Aún no has usado el servicio de bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>

            <!-- Modal de devolución -->
            <div id="modal-devolucion" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-contenido">
                    <button type="button" class="flavor-modal-cerrar">&times;</button>
                    <h3><?php _e('Devolver bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <form id="form-devolver-bicicleta">
                        <input type="hidden" name="prestamo_id" id="devolver-prestamo-id">
                        <input type="hidden" name="bicicleta_id" id="devolver-bicicleta-id">

                        <div class="flavor-form-grupo">
                            <label><?php _e('Estación de devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="estacion_id" id="devolver-estacion" required>
                                <option value=""><?php _e('Selecciona una estación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                            <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e('Usar mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>

                        <div class="flavor-form-grupo">
                            <label><?php _e('Kilómetros recorridos (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" name="kilometros" step="0.1" min="0" max="100" placeholder="0.0">
                        </div>

                        <div class="flavor-form-grupo">
                            <label><?php _e('¿Alguna incidencia?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <textarea name="incidencias" rows="2" placeholder="<?php esc_attr_e('Describe cualquier problema con la bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                        </div>

                        <div class="flavor-form-grupo">
                            <label><?php _e('Valoración del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div class="flavor-valoracion-estrellas">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="valoracion" id="val-<?php echo $i; ?>" value="<?php echo $i; ?>">
                                <label for="val-<?php echo $i; ?>" title="<?php echo $i; ?> estrellas">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="flavor-form-acciones">
                            <button type="button" class="flavor-btn flavor-btn-outline flavor-modal-cerrar-btn">
                                <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Confirmar devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de estaciones
     */
    public function shortcode_mapa($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $estaciones = $wpdb->get_results(
            "SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre ASC"
        );

        $atts = shortcode_atts([
            'altura' => '400px',
            'lat' => '40.4168',
            'lng' => '-3.7038',
            'zoom' => '13',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-bicicletas-mapa-container">
            <div id="flavor-mapa-estaciones"
                 style="height: <?php echo esc_attr($atts['altura']); ?>"
                 data-lat="<?php echo esc_attr($atts['lat']); ?>"
                 data-lng="<?php echo esc_attr($atts['lng']); ?>"
                 data-zoom="<?php echo esc_attr($atts['zoom']); ?>">
            </div>
            <script type="application/json" id="estaciones-data">
                <?php echo wp_json_encode(array_map(function($e) {
                    return [
                        'id' => $e->id,
                        'nombre' => $e->nombre,
                        'direccion' => $e->direccion,
                        'lat' => floatval($e->latitud),
                        'lng' => floatval($e->longitud),
                        'capacidad' => intval($e->capacidad_total),
                        'disponibles' => intval($e->bicicletas_disponibles),
                        'horario' => $e->horario,
                    ];
                }, $estaciones)); ?>
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de estaciones
     */
    public function shortcode_estaciones($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $estaciones = $wpdb->get_results(
            "SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre ASC"
        );

        ob_start();
        ?>
        <div class="flavor-estaciones-grid">
            <?php foreach ($estaciones as $estacion): ?>
            <div class="flavor-estacion-card" data-id="<?php echo intval($estacion->id); ?>">
                <div class="flavor-estacion-header">
                    <h4><?php echo esc_html($estacion->nombre); ?></h4>
                    <span class="flavor-badge <?php echo $estacion->bicicletas_disponibles > 0 ? 'flavor-badge-success' : 'flavor-badge-warning'; ?>">
                        <?php echo intval($estacion->bicicletas_disponibles); ?> / <?php echo intval($estacion->capacidad_total); ?>
                    </span>
                </div>
                <p class="flavor-estacion-direccion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($estacion->direccion); ?>
                </p>
                <?php if (!empty($estacion->horario)): ?>
                <p class="flavor-estacion-horario">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html($estacion->horario); ?>
                </p>
                <?php endif; ?>
                <div class="flavor-estacion-barra">
                    <div class="flavor-barra-progreso"
                         style="width: <?php echo intval($estacion->capacidad_total) > 0 ? (intval($estacion->bicicletas_disponibles) / intval($estacion->capacidad_total) * 100) : 0; ?>%">
                    </div>
                </div>
                <a href="?estacion=<?php echo intval($estacion->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <?php _e('Ver bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Bicicletas disponibles
     */
    public function shortcode_disponibles($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $atts = shortcode_atts([
            'estacion_id' => isset($_GET['estacion']) ? absint($_GET['estacion']) : 0,
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        $where = ["b.estado = 'disponible'"];
        $params = [];

        if ($atts['estacion_id'] > 0) {
            $where[] = "b.estacion_actual_id = %d";
            $params[] = $atts['estacion_id'];
        }

        if (!empty($atts['tipo'])) {
            $where[] = "b.tipo = %s";
            $params[] = sanitize_text_field($atts['tipo']);
        }

        $sql = "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion
                FROM $tabla_bicicletas b
                LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.fecha_alta DESC
                LIMIT " . intval($atts['limite']);

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $bicicletas = $wpdb->get_results($sql);

        ob_start();
        ?>
        <div class="flavor-bicicletas-disponibles">
            <!-- Filtros -->
            <div class="flavor-filtros">
                <select id="filtro-tipo-bici" class="flavor-select">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="urbana"><?php _e('Urbana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="montana"><?php _e('Montaña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="electrica"><?php _e('Eléctrica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="infantil"><?php _e('Infantil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="carga"><?php _e('Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <!-- Grid de bicicletas -->
            <div class="flavor-bicicletas-grid">
                <?php if (empty($bicicletas)): ?>
                <p class="flavor-vacio"><?php _e('No hay bicicletas disponibles con los filtros seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($bicicletas as $bici): ?>
                    <div class="flavor-bicicleta-card" data-tipo="<?php echo esc_attr($bici->tipo); ?>">
                        <?php if (!empty($bici->foto_url)): ?>
                        <div class="flavor-bici-imagen">
                            <img src="<?php echo esc_url($bici->foto_url); ?>" alt="<?php echo esc_attr($bici->codigo); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="flavor-bici-info">
                            <div class="flavor-bici-codigo"><?php echo esc_html($bici->codigo); ?></div>
                            <div class="flavor-bici-modelo"><?php echo esc_html($bici->marca . ' ' . $bici->modelo); ?></div>
                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($bici->tipo); ?>">
                                <?php echo esc_html(ucfirst($bici->tipo)); ?>
                            </span>
                            <?php if (!empty($bici->talla)): ?>
                            <span class="flavor-badge flavor-badge-outline">Talla <?php echo esc_html($bici->talla); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($bici->estacion_nombre): ?>
                        <div class="flavor-bici-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($bici->estacion_nombre); ?>
                        </div>
                        <?php endif; ?>
                        <div class="flavor-bici-acciones">
                            <?php if (is_user_logged_in()): ?>
                            <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-reservar"
                                    data-bicicleta-id="<?php echo intval($bici->id); ?>"
                                    data-codigo="<?php echo esc_attr($bici->codigo); ?>">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <?php else: ?>
                            <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>" class="flavor-btn flavor-btn-outline">
                                <?php _e('Inicia sesión para reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de bicicleta
     */
    public function shortcode_detalle($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $atts = shortcode_atts([
            'id' => isset($_GET['bici']) ? absint($_GET['bici']) : 0,
        ], $atts);

        if (!$atts['id']) {
            return '<p class="flavor-aviso">' . __('Bicicleta no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $bici = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion, e.latitud, e.longitud
             FROM $tabla_bicicletas b
             LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
             WHERE b.id = %d",
            $atts['id']
        ));

        if (!$bici) {
            return '<p class="flavor-error">' . __('Bicicleta no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-bicicleta-detalle">
            <div class="flavor-detalle-grid">
                <div class="flavor-detalle-imagen">
                    <?php if (!empty($bici->foto_url)): ?>
                    <img src="<?php echo esc_url($bici->foto_url); ?>" alt="<?php echo esc_attr($bici->codigo); ?>">
                    <?php else: ?>
                    <div class="flavor-placeholder-imagen">
                        <span class="dashicons dashicons-bike"></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flavor-detalle-info">
                    <h2><?php echo esc_html($bici->codigo); ?></h2>
                    <p class="flavor-modelo"><?php echo esc_html($bici->marca . ' ' . $bici->modelo); ?></p>

                    <div class="flavor-badges">
                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($bici->tipo); ?>">
                            <?php echo esc_html(ucfirst($bici->tipo)); ?>
                        </span>
                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($bici->estado); ?>">
                            <?php echo esc_html($this->obtener_etiqueta_estado($bici->estado)); ?>
                        </span>
                    </div>

                    <dl class="flavor-detalles-lista">
                        <?php if (!empty($bici->color)): ?>
                        <dt><?php _e('Color', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></dt>
                        <dd><?php echo esc_html($bici->color); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($bici->talla)): ?>
                        <dt><?php _e('Talla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></dt>
                        <dd><?php echo esc_html($bici->talla); ?></dd>
                        <?php endif; ?>

                        <dt><?php _e('Km acumulados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></dt>
                        <dd><?php echo number_format($bici->kilometros_acumulados, 0); ?> km</dd>

                        <?php if (!empty($bici->ultima_revision)): ?>
                        <dt><?php _e('Última revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></dt>
                        <dd><?php echo date_i18n('j M Y', strtotime($bici->ultima_revision)); ?></dd>
                        <?php endif; ?>
                    </dl>

                    <?php if ($bici->estacion_nombre): ?>
                    <div class="flavor-ubicacion-actual">
                        <h4><?php _e('Ubicación actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <p>
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($bici->estacion_nombre); ?><br>
                            <small><?php echo esc_html($bici->estacion_direccion); ?></small>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($bici->estado === 'disponible' && is_user_logged_in()): ?>
                    <button type="button" class="flavor-btn flavor-btn-lg flavor-btn-primary flavor-btn-reservar"
                            data-bicicleta-id="<?php echo intval($bici->id); ?>"
                            data-codigo="<?php echo esc_attr($bici->codigo); ?>">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Reservar esta bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php elseif (!is_user_logged_in()): ?>
                    <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>" class="flavor-btn flavor-btn-lg flavor-btn-primary">
                        <?php _e('Inicia sesión para reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis préstamos
     */
    public function shortcode_mis_prestamos($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-aviso">' . __('Inicia sesión para ver tus préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->cargar_assets();
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $atts = shortcode_atts([
            'limite' => 20,
            'estado' => '',
        ], $atts);

        $where = ["p.usuario_id = %d"];
        $params = [$usuario_id];

        if (!empty($atts['estado'])) {
            $where[] = "p.estado = %s";
            $params[] = sanitize_text_field($atts['estado']);
        }

        $prestamos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, b.codigo, b.tipo, b.marca, b.modelo,
                    es.nombre as estacion_salida, el.nombre as estacion_llegada
             FROM $tabla_prestamos p
             JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
             LEFT JOIN $tabla_estaciones es ON p.estacion_salida_id = es.id
             LEFT JOIN $tabla_estaciones el ON p.estacion_llegada_id = el.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.fecha_creacion DESC
             LIMIT %d",
            array_merge($params, [intval($atts['limite'])])
        ));

        ob_start();
        ?>
        <div class="flavor-mis-prestamos">
            <?php if (empty($prestamos)): ?>
            <p class="flavor-vacio"><?php _e('No tienes préstamos registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
            <div class="flavor-prestamos-lista">
                <?php foreach ($prestamos as $prestamo): ?>
                <div class="flavor-prestamo-card <?php echo esc_attr($prestamo->estado); ?>">
                    <div class="flavor-prestamo-header">
                        <div class="flavor-bici-info">
                            <strong><?php echo esc_html($prestamo->codigo); ?></strong>
                            <span><?php echo esc_html($prestamo->marca . ' ' . $prestamo->modelo); ?></span>
                        </div>
                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($prestamo->estado); ?>">
                            <?php echo esc_html($this->obtener_etiqueta_estado($prestamo->estado)); ?>
                        </span>
                    </div>
                    <div class="flavor-prestamo-detalles">
                        <div class="flavor-detalle">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo date_i18n('j M Y H:i', strtotime($prestamo->fecha_inicio)); ?>
                        </div>
                        <?php if ($prestamo->estacion_salida): ?>
                        <div class="flavor-detalle">
                            <span class="dashicons dashicons-location"></span>
                            <span><?php _e('Salida:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php echo esc_html($prestamo->estacion_salida); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($prestamo->fecha_fin): ?>
                        <div class="flavor-detalle">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo $this->formatear_duracion($prestamo->duracion_minutos); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($prestamo->kilometros_recorridos > 0): ?>
                        <div class="flavor-detalle">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php echo number_format($prestamo->kilometros_recorridos, 1); ?> km
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($prestamo->estado === 'activo'): ?>
                    <div class="flavor-prestamo-acciones">
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-devolver"
                                data-prestamo-id="<?php echo intval($prestamo->id); ?>"
                                data-bicicleta-id="<?php echo intval($prestamo->bicicleta_id); ?>">
                            <?php _e('Devolver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Préstamo activo
     */
    public function shortcode_prestamo_activo($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->cargar_assets();
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, b.codigo, b.tipo, b.marca, b.modelo, b.color
             FROM $tabla_prestamos p
             JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
             WHERE p.usuario_id = %d AND p.estado = 'activo'",
            $usuario_id
        ));

        if (!$prestamo) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-prestamo-activo-banner">
            <div class="flavor-prestamo-info">
                <span class="dashicons dashicons-bike"></span>
                <div>
                    <strong><?php echo esc_html($prestamo->codigo); ?></strong>
                    <span><?php echo esc_html($prestamo->marca . ' ' . $prestamo->modelo); ?></span>
                </div>
            </div>
            <div class="flavor-tiempo-transcurrido" data-inicio="<?php echo esc_attr($prestamo->fecha_inicio); ?>">
                <?php echo $this->formatear_duracion((time() - strtotime($prestamo->fecha_inicio)) / 60); ?>
            </div>
            <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-devolver"
                    data-prestamo-id="<?php echo intval($prestamo->id); ?>"
                    data-bicicleta-id="<?php echo intval($prestamo->bicicleta_id); ?>">
                <?php _e('Devolver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas
     */
    public function shortcode_estadisticas($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $stats = $wpdb->get_row(
            "SELECT
                (SELECT COUNT(*) FROM $tabla_bicicletas) as total_bicicletas,
                (SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible') as disponibles,
                (SELECT COUNT(*) FROM $tabla_estaciones WHERE estado = 'activa') as estaciones,
                (SELECT COUNT(*) FROM $tabla_prestamos) as total_prestamos,
                (SELECT SUM(COALESCE(kilometros_recorridos, 0)) FROM $tabla_prestamos WHERE estado = 'finalizado') as km_totales,
                (SELECT COUNT(DISTINCT usuario_id) FROM $tabla_prestamos) as usuarios_activos"
        );

        ob_start();
        ?>
        <div class="flavor-estadisticas-bicicletas">
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-bike"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->total_bicicletas ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-yes-alt"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->disponibles ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-location-alt"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->estaciones ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-chart-bar"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->total_prestamos ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-chart-line"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->km_totales ?? 0); ?> km</div>
                    <div class="flavor-stat-etiqueta"><?php _e('Km recorridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-awards"></span>
                    <div class="flavor-stat-valor"><?php echo number_format(($stats->km_totales ?? 0) * 0.21); ?> kg</div>
                    <div class="flavor-stat-etiqueta"><?php _e('CO₂ ahorrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Reservar bicicleta
     */
    public function ajax_reservar() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $usuario_id = get_current_user_id();
        $bicicleta_id = absint($_POST['bicicleta_id'] ?? 0);
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Verificar bicicleta disponible
        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_bicicletas WHERE id = %d AND estado = 'disponible'",
            $bicicleta_id
        ));

        if (!$bicicleta) {
            wp_send_json_error(['message' => __('Bicicleta no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que no tenga préstamo activo
        $tiene_activo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if ($tiene_activo > 0) {
            wp_send_json_error(['message' => __('Ya tienes un préstamo activo', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Crear préstamo
        $resultado = $wpdb->insert($tabla_prestamos, [
            'bicicleta_id' => $bicicleta_id,
            'usuario_id' => $usuario_id,
            'estacion_salida_id' => $bicicleta->estacion_actual_id,
            'fecha_inicio' => current_time('mysql'),
            'fianza' => 0,
            'estado' => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al crear el préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar estado bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            ['estado' => 'en_uso', 'estacion_actual_id' => null],
            ['id' => $bicicleta_id]
        );

        // Actualizar contador estación
        if ($bicicleta->estacion_actual_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = GREATEST(0, bicicletas_disponibles - 1) WHERE id = %d",
                $bicicleta->estacion_actual_id
            ));
        }

        wp_send_json_success([
            'message' => sprintf(__('Bicicleta %s reservada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN), $bicicleta->codigo),
            'prestamo_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Devolver bicicleta
     */
    public function ajax_devolver() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $usuario_id = get_current_user_id();
        $prestamo_id = absint($_POST['prestamo_id'] ?? 0);
        $estacion_id = absint($_POST['estacion_id'] ?? 0);
        $kilometros = floatval($_POST['kilometros'] ?? 0);
        $incidencias = sanitize_textarea_field($_POST['incidencias'] ?? '');
        $valoracion = absint($_POST['valoracion'] ?? 0);

        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Verificar préstamo activo del usuario
        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d AND usuario_id = %d AND estado = 'activo'",
            $prestamo_id,
            $usuario_id
        ));

        if (!$prestamo) {
            wp_send_json_error(['message' => __('Préstamo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar estación
        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_estaciones WHERE id = %d AND estado = 'activa'",
            $estacion_id
        ));

        if (!$estacion) {
            wp_send_json_error(['message' => __('Estación no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Calcular duración
        $duracion_minutos = round((time() - strtotime($prestamo->fecha_inicio)) / 60);

        // Actualizar préstamo
        $wpdb->update(
            $tabla_prestamos,
            [
                'estacion_llegada_id' => $estacion_id,
                'fecha_fin' => current_time('mysql'),
                'duracion_minutos' => $duracion_minutos,
                'kilometros_recorridos' => $kilometros,
                'incidencias' => $incidencias,
                'valoracion' => $valoracion > 0 && $valoracion <= 5 ? $valoracion : null,
                'estado' => 'finalizado',
            ],
            ['id' => $prestamo_id]
        );

        // Determinar estado de bicicleta
        $estado_bici = !empty($incidencias) ? 'mantenimiento' : 'disponible';

        // Actualizar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            [
                'estado' => $estado_bici,
                'estacion_actual_id' => $estacion_id,
                'kilometros_acumulados' => $wpdb->get_var($wpdb->prepare(
                    "SELECT kilometros_acumulados FROM $tabla_bicicletas WHERE id = %d",
                    $prestamo->bicicleta_id
                )) + $kilometros,
            ],
            ['id' => $prestamo->bicicleta_id]
        );

        // Actualizar contador estación
        if ($estado_bici === 'disponible') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = bicicletas_disponibles + 1 WHERE id = %d",
                $estacion_id
            ));
        }

        wp_send_json_success([
            'message' => __('Bicicleta devuelta correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion' => $this->formatear_duracion($duracion_minutos),
            'kilometros' => $kilometros,
        ]);
    }

    /**
     * AJAX: Reportar problema
     */
    public function ajax_reportar_problema() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $bicicleta_id = absint($_POST['bicicleta_id'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo_problema = sanitize_text_field($_POST['tipo_problema'] ?? 'otro');

        if (empty($descripcion)) {
            wp_send_json_error(['message' => __('Describe el problema', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Crear incidencia (si existe tabla de incidencias de bicicletas)
        $tabla_incidencias = $wpdb->prefix . 'flavor_bicicletas_incidencias';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            $wpdb->insert($tabla_incidencias, [
                'bicicleta_id' => $bicicleta_id,
                'usuario_id' => get_current_user_id(),
                'tipo' => $tipo_problema,
                'descripcion' => $descripcion,
                'estado' => 'pendiente',
                'fecha_creacion' => current_time('mysql'),
            ]);
        }

        // Marcar bicicleta para revisión si es problema grave
        if (in_array($tipo_problema, ['frenos', 'ruedas', 'seguridad'])) {
            $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
            $wpdb->update(
                $tabla_bicicletas,
                ['estado' => 'mantenimiento'],
                ['id' => $bicicleta_id]
            );
        }

        wp_send_json_success([
            'message' => __('Problema reportado correctamente. Gracias por tu colaboración.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $prestamo_id = absint($_POST['prestamo_id'] ?? 0);
        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d AND usuario_id = %d AND estado = 'reservado'",
            $prestamo_id,
            $usuario_id
        ));

        if (!$prestamo) {
            wp_send_json_error(['message' => __('Reserva no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar préstamo
        $wpdb->update(
            $tabla_prestamos,
            ['estado' => 'cancelado'],
            ['id' => $prestamo_id]
        );

        // Liberar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            ['estado' => 'disponible'],
            ['id' => $prestamo->bicicleta_id]
        );

        wp_send_json_success([
            'message' => __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Valorar préstamo
     */
    public function ajax_valorar() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $prestamo_id = absint($_POST['prestamo_id'] ?? 0);
        $valoracion = absint($_POST['valoracion'] ?? 0);
        $usuario_id = get_current_user_id();

        if ($valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(['message' => __('Valoración no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        $resultado = $wpdb->update(
            $tabla_prestamos,
            ['valoracion' => $valoracion],
            ['id' => $prestamo_id, 'usuario_id' => $usuario_id]
        );

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al guardar la valoración', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success([
            'message' => __('Gracias por tu valoración', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Buscar estaciones cercanas
     */
    public function ajax_buscar_estaciones() {
        check_ajax_referer('flavor_bicicletas_nonce', 'nonce');

        global $wpdb;
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $radio = floatval($_POST['radio'] ?? 5); // km

        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Fórmula Haversine para distancia
        $estaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT *,
                (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
             FROM $tabla_estaciones
             WHERE estado = 'activa'
             HAVING distancia <= %f
             ORDER BY distancia ASC
             LIMIT 10",
            $lat, $lng, $lat, $radio
        ));

        wp_send_json_success([
            'estaciones' => array_map(function($e) {
                return [
                    'id' => intval($e->id),
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'lat' => floatval($e->latitud),
                    'lng' => floatval($e->longitud),
                    'disponibles' => intval($e->bicicletas_disponibles),
                    'capacidad' => intval($e->capacidad_total),
                    'distancia' => round($e->distancia, 2),
                ];
            }, $estaciones),
        ]);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Formatea duración en minutos a texto legible
     */
    private function formatear_duracion($minutos) {
        $minutos = intval($minutos);

        if ($minutos < 60) {
            return sprintf(__('%d min', FLAVOR_PLATFORM_TEXT_DOMAIN), $minutos);
        }

        $horas = floor($minutos / 60);
        $mins = $minutos % 60;

        if ($horas < 24) {
            return $mins > 0
                ? sprintf(__('%dh %dmin', FLAVOR_PLATFORM_TEXT_DOMAIN), $horas, $mins)
                : sprintf(__('%dh', FLAVOR_PLATFORM_TEXT_DOMAIN), $horas);
        }

        $dias = floor($horas / 24);
        $horas = $horas % 24;

        return sprintf(__('%dd %dh', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias, $horas);
    }

    /**
     * Obtiene etiqueta para estado
     */
    private function obtener_etiqueta_estado($estado) {
        $etiquetas = [
            'disponible' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'en_uso' => __('En uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'reservada' => __('Reservada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mantenimiento' => __('En mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'activo' => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'finalizado' => __('Finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cancelado' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $etiquetas[$estado] ?? ucfirst($estado);
    }
}

// Inicializar
Flavor_Bicicletas_Compartidas_Frontend_Controller::get_instance();

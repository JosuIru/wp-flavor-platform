<?php
/**
 * Frontend Controller para Parkings Comunitarios
 *
 * Maneja vistas frontend, shortcodes, AJAX y dashboard tabs
 * para el sistema de parkings comunitarios.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Parkings
 */
class Flavor_Parkings_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instancia = null;

    /**
     * Versión del controlador
     */
    const VERSION = '1.0.0';

    /**
     * Nombres de tablas
     */
    private $tablas = [];

    /**
     * Estados de plaza
     */
    private $estados_plaza = [
        'libre' => ['label' => 'Libre', 'color' => '#22c55e', 'icon' => 'yes-alt'],
        'ocupada' => ['label' => 'Ocupada', 'color' => '#ef4444', 'icon' => 'no-alt'],
        'reservada' => ['label' => 'Reservada', 'color' => '#f59e0b', 'icon' => 'clock'],
        'mantenimiento' => ['label' => 'Mantenimiento', 'color' => '#6b7280', 'icon' => 'admin-tools'],
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->tablas = [
            'parkings' => $wpdb->prefix . 'flavor_parkings',
            'plazas' => $wpdb->prefix . 'flavor_parkings_plazas',
            'reservas' => $wpdb->prefix . 'flavor_parkings_reservas',
            'asignaciones' => $wpdb->prefix . 'flavor_parkings_asignaciones',
            'lista_espera' => $wpdb->prefix . 'flavor_parkings_lista_espera',
        ];

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'registrar_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_parkings_reservar', [$this, 'ajax_reservar']);
        add_action('wp_ajax_flavor_parkings_cancelar', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_flavor_parkings_lista_espera', [$this, 'ajax_apuntar_lista_espera']);
        add_action('wp_ajax_flavor_parkings_liberar', [$this, 'ajax_liberar_plaza']);
        add_action('wp_ajax_flavor_parkings_buscar', [$this, 'ajax_buscar_plazas']);
        add_action('wp_ajax_nopriv_flavor_parkings_buscar', [$this, 'ajax_buscar_plazas']);

        // Dashboard tab
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tab'], 10, 1);
    }

    /**
     * Registra shortcodes
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'flavor_parkings_mapa' => 'shortcode_mapa',
            'flavor_parkings_listado' => 'shortcode_listado',
            'flavor_parkings_disponibles' => 'shortcode_disponibles',
            'flavor_parkings_reservar' => 'shortcode_reservar',
            'flavor_parkings_mis_reservas' => 'shortcode_mis_reservas',
            'flavor_parkings_mi_plaza' => 'shortcode_mi_plaza',
            'flavor_parkings_lista_espera' => 'shortcode_lista_espera',
            'flavor_parkings_dashboard' => 'shortcode_dashboard',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Registra assets
     */
    public function registrar_assets() {
        $modulo_url = plugin_dir_url(dirname(__FILE__));

        wp_register_style(
            'flavor-parkings-frontend',
            $modulo_url . 'assets/css/parkings-frontend.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'flavor-parkings-frontend',
            $modulo_url . 'assets/js/parkings-frontend.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_register_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_register_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets($con_mapa = false) {
        wp_enqueue_style('flavor-parkings-frontend');
        wp_enqueue_script('flavor-parkings-frontend');

        if ($con_mapa) {
            wp_enqueue_style('leaflet');
            wp_enqueue_script('leaflet');
        }

        wp_localize_script('flavor-parkings-frontend', 'flavorParkingsConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_parkings_nonce'),
            'usuarioId' => get_current_user_id(),
            'estados' => $this->estados_plaza,
            'strings' => [
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'reserva_exitosa' => __('Reserva realizada con éxito', 'flavor-chat-ia'),
                'confirmar_cancelar' => __('¿Cancelar esta reserva?', 'flavor-chat-ia'),
                'confirmar_liberar' => __('¿Liberar esta plaza temporalmente?', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registra tab en dashboard
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $tabs['parkings'] = [
            'titulo' => __('Parking', 'flavor-chat-ia'),
            'icono' => 'dashicons-car',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 45,
        ];

        return $tabs;
    }

    /**
     * Renderiza el dashboard tab
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets(true);
        $usuario_id = get_current_user_id();

        $mi_plaza = $this->obtener_plaza_asignada($usuario_id);
        $mis_reservas = $this->obtener_reservas_activas($usuario_id);
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $parkings_cercanos = $this->obtener_parkings_cercanos();
        ?>
        <div class="flavor-parkings-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon <?php echo $mi_plaza ? 'verde' : 'gris'; ?>">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo $mi_plaza ? esc_html($mi_plaza->numero_plaza) : '-'; ?></span>
                        <span class="flavor-kpi-label"><?php _e('Mi Plaza', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo count($mis_reservas); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Reservas Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon naranja">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas['horas_uso']); ?>h</span>
                        <span class="flavor-kpi-label"><?php _e('Horas Usadas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon morado">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($estadisticas['gasto_total'], 2); ?>€</span>
                        <span class="flavor-kpi-label"><?php _e('Gasto Total', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Mi Plaza Asignada -->
            <?php if ($mi_plaza): ?>
                <div class="flavor-panel">
                    <div class="flavor-panel-header">
                        <h3>
                            <span class="dashicons dashicons-location"></span>
                            <?php _e('Mi Plaza Asignada', 'flavor-chat-ia'); ?>
                        </h3>
                        <span class="flavor-badge flavor-badge-success">
                            <?php echo esc_html($this->estados_plaza[$mi_plaza->estado]['label']); ?>
                        </span>
                    </div>
                    <div class="flavor-panel-body">
                        <div class="flavor-plaza-info-card">
                            <div class="flavor-plaza-numero">
                                <span class="numero"><?php echo esc_html($mi_plaza->numero_plaza); ?></span>
                                <span class="planta"><?php printf(__('Planta %s', 'flavor-chat-ia'), $mi_plaza->planta ?: '-'); ?></span>
                            </div>
                            <div class="flavor-plaza-detalles">
                                <p><strong><?php echo esc_html($mi_plaza->nombre_parking); ?></strong></p>
                                <p><?php echo esc_html($mi_plaza->direccion); ?></p>
                                <?php if ($mi_plaza->tipo_plaza): ?>
                                    <span class="flavor-badge flavor-badge-outline">
                                        <?php echo esc_html(ucfirst($mi_plaza->tipo_plaza)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-plaza-acciones">
                                <button class="flavor-btn flavor-btn-outline flavor-btn-liberar"
                                        data-plaza-id="<?php echo esc_attr($mi_plaza->id); ?>">
                                    <span class="dashicons dashicons-unlock"></span>
                                    <?php _e('Liberar Temporalmente', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-panel flavor-panel-info">
                    <div class="flavor-panel-body">
                        <div class="flavor-cta-box">
                            <span class="dashicons dashicons-car"></span>
                            <h4><?php _e('¿Necesitas una plaza de parking?', 'flavor-chat-ia'); ?></h4>
                            <p><?php _e('Apúntate a la lista de espera o busca plazas disponibles para reservar.', 'flavor-chat-ia'); ?></p>
                            <div class="flavor-cta-buttons">
                                <a href="<?php echo esc_url(add_query_arg('seccion', 'lista-espera')); ?>" class="flavor-btn flavor-btn-primary">
                                    <?php _e('Lista de Espera', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(add_query_arg('seccion', 'disponibles')); ?>" class="flavor-btn flavor-btn-outline">
                                    <?php _e('Ver Disponibles', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mis Reservas Activas -->
            <?php if (!empty($mis_reservas)): ?>
                <div class="flavor-panel">
                    <div class="flavor-panel-header">
                        <h3><?php _e('Mis Reservas Activas', 'flavor-chat-ia'); ?></h3>
                    </div>
                    <div class="flavor-panel-body">
                        <div class="flavor-reservas-list">
                            <?php foreach ($mis_reservas as $reserva): ?>
                                <div class="flavor-reserva-card" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                                    <div class="flavor-reserva-fecha">
                                        <span class="dia"><?php echo date_i18n('d', strtotime($reserva->fecha_inicio)); ?></span>
                                        <span class="mes"><?php echo date_i18n('M', strtotime($reserva->fecha_inicio)); ?></span>
                                    </div>
                                    <div class="flavor-reserva-info">
                                        <span class="flavor-reserva-plaza">
                                            <?php printf(__('Plaza %s', 'flavor-chat-ia'), $reserva->numero_plaza); ?>
                                        </span>
                                        <span class="flavor-reserva-parking">
                                            <?php echo esc_html($reserva->nombre_parking); ?>
                                        </span>
                                        <span class="flavor-reserva-horario">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php echo date_i18n('H:i', strtotime($reserva->fecha_inicio)); ?> -
                                            <?php echo date_i18n('H:i', strtotime($reserva->fecha_fin)); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-reserva-estado">
                                        <span class="flavor-badge flavor-badge-<?php echo $reserva->estado === 'confirmada' ? 'success' : 'warning'; ?>">
                                            <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                        </span>
                                        <button class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-cancelar"
                                                data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acciones Rápidas -->
            <div class="flavor-acciones-grid flavor-grid-3">
                <a href="<?php echo esc_url(add_query_arg('seccion', 'reservar')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php _e('Reservar Plaza', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('seccion', 'mapa')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-location"></span>
                    <span><?php _e('Ver Mapa', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('seccion', 'historial')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-list-view"></span>
                    <span><?php _e('Historial', 'flavor-chat-ia'); ?></span>
                </a>
            </div>

            <!-- Mapa de Parkings -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Parkings Disponibles', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <div id="flavor-parkings-mapa" class="flavor-mapa-container"
                         data-parkings='<?php echo json_encode($parkings_cercanos); ?>'></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mapa de parkings
     */
    public function shortcode_mapa($atts) {
        $atts = shortcode_atts([
            'altura' => '400px',
            'comunidad_id' => 0,
        ], $atts);

        $this->enqueue_assets(true);
        $parkings = $this->obtener_parkings($atts);

        ob_start();
        ?>
        <div class="flavor-parkings-mapa-wrapper">
            <div id="flavor-parkings-mapa-<?php echo uniqid(); ?>"
                 class="flavor-mapa-container"
                 style="height: <?php echo esc_attr($atts['altura']); ?>"
                 data-parkings='<?php echo json_encode($parkings); ?>'>
            </div>
            <div class="flavor-mapa-leyenda">
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker verde"></span> <?php _e('Disponible', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker naranja"></span> <?php _e('Pocas plazas', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker rojo"></span> <?php _e('Completo', 'flavor-chat-ia'); ?>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de parkings
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
            'tipo' => '',
        ], $atts);

        $this->enqueue_assets();
        $parkings = $this->obtener_parkings($atts);

        ob_start();
        ?>
        <div class="flavor-parkings-listado">
            <?php if (!empty($parkings)): ?>
                <div class="flavor-parkings-grid">
                    <?php foreach ($parkings as $parking): ?>
                        <?php $this->render_parking_card($parking); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-car"></span>
                    <p><?php _e('No hay parkings disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de parking
     */
    private function render_parking_card($parking) {
        $ocupacion = $this->calcular_ocupacion($parking->id);
        $clase_ocupacion = $ocupacion >= 90 ? 'alto' : ($ocupacion >= 70 ? 'medio' : 'bajo');
        ?>
        <div class="flavor-parking-card" data-parking-id="<?php echo esc_attr($parking->id); ?>">
            <div class="flavor-parking-header">
                <h4><?php echo esc_html($parking->nombre); ?></h4>
                <span class="flavor-badge flavor-badge-<?php echo $clase_ocupacion; ?>">
                    <?php echo intval($ocupacion); ?>% ocupado
                </span>
            </div>

            <p class="flavor-parking-direccion">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($parking->direccion); ?>
            </p>

            <div class="flavor-parking-stats">
                <div class="flavor-stat">
                    <span class="flavor-stat-valor"><?php echo intval($parking->total_plazas); ?></span>
                    <span class="flavor-stat-label"><?php _e('Total', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat">
                    <span class="flavor-stat-valor verde"><?php echo intval($parking->plazas_libres); ?></span>
                    <span class="flavor-stat-label"><?php _e('Libres', 'flavor-chat-ia'); ?></span>
                </div>
                <?php if ($parking->plazas_carga_electrica > 0): ?>
                    <div class="flavor-stat">
                        <span class="flavor-stat-valor azul"><?php echo intval($parking->plazas_carga_electrica); ?></span>
                        <span class="flavor-stat-label"><?php _e('Eléctricos', 'flavor-chat-ia'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flavor-parking-info">
                <span class="flavor-info-item">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo $parking->acceso_24h ? __('24h', 'flavor-chat-ia') : esc_html($parking->horario_apertura . ' - ' . $parking->horario_cierre); ?>
                </span>
                <span class="flavor-info-item">
                    <span class="dashicons dashicons-<?php echo $parking->tipo_acceso === 'app' ? 'smartphone' : 'admin-network'; ?>"></span>
                    <?php echo esc_html(ucfirst($parking->tipo_acceso)); ?>
                </span>
            </div>

            <?php if ($parking->precio_hora_visitante): ?>
                <div class="flavor-parking-precios">
                    <span class="flavor-precio">
                        <?php echo number_format($parking->precio_hora_visitante, 2); ?>€/h
                    </span>
                    <?php if ($parking->precio_dia_visitante): ?>
                        <span class="flavor-precio">
                            <?php echo number_format($parking->precio_dia_visitante, 2); ?>€/día
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-parking-actions">
                <a href="<?php echo esc_url(add_query_arg(['seccion' => 'reservar', 'parking_id' => $parking->id])); ?>"
                   class="flavor-btn flavor-btn-primary">
                    <?php _e('Reservar', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'parking_id' => $parking->id])); ?>"
                   class="flavor-btn flavor-btn-outline">
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Plazas disponibles
     */
    public function shortcode_disponibles($atts) {
        $atts = shortcode_atts([
            'parking_id' => 0,
            'fecha' => '',
        ], $atts);

        $this->enqueue_assets();
        $plazas = $this->obtener_plazas_disponibles($atts);

        ob_start();
        ?>
        <div class="flavor-plazas-disponibles">
            <!-- Filtros -->
            <div class="flavor-filtros-bar">
                <input type="date" id="filtro-fecha" class="flavor-input"
                       value="<?php echo esc_attr($atts['fecha'] ?: date('Y-m-d')); ?>">
                <select id="filtro-tipo" class="flavor-select">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <option value="normal"><?php _e('Normal', 'flavor-chat-ia'); ?></option>
                    <option value="grande"><?php _e('Grande', 'flavor-chat-ia'); ?></option>
                    <option value="movilidad_reducida"><?php _e('Movilidad reducida', 'flavor-chat-ia'); ?></option>
                    <option value="electrico"><?php _e('Carga eléctrica', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <?php if (!empty($plazas)): ?>
                <div class="flavor-plazas-grid">
                    <?php foreach ($plazas as $plaza): ?>
                        <div class="flavor-plaza-card" data-plaza-id="<?php echo esc_attr($plaza->id); ?>">
                            <div class="flavor-plaza-numero-badge">
                                <?php echo esc_html($plaza->numero_plaza); ?>
                            </div>
                            <div class="flavor-plaza-info">
                                <span class="flavor-plaza-parking"><?php echo esc_html($plaza->nombre_parking); ?></span>
                                <span class="flavor-plaza-planta">
                                    <?php printf(__('Planta %s', 'flavor-chat-ia'), $plaza->planta ?: '0'); ?>
                                </span>
                                <?php if ($plaza->tipo_plaza && $plaza->tipo_plaza !== 'normal'): ?>
                                    <span class="flavor-badge flavor-badge-outline">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $plaza->tipo_plaza))); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button class="flavor-btn flavor-btn-primary flavor-btn-sm flavor-btn-reservar"
                                    data-plaza-id="<?php echo esc_attr($plaza->id); ?>">
                                <?php _e('Reservar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-car"></span>
                    <p><?php _e('No hay plazas disponibles para la fecha seleccionada.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de reserva
     */
    public function shortcode_reservar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para reservar.', 'flavor-chat-ia') .
                   '</div>';
        }

        $atts = shortcode_atts([
            'parking_id' => isset($_GET['parking_id']) ? intval($_GET['parking_id']) : 0,
            'plaza_id' => isset($_GET['plaza_id']) ? intval($_GET['plaza_id']) : 0,
        ], $atts);

        $this->enqueue_assets();
        $parkings = $this->obtener_parkings(['con_plazas_libres' => true]);

        ob_start();
        ?>
        <div class="flavor-parkings-reservar">
            <form id="flavor-form-reserva" class="flavor-form">
                <?php wp_nonce_field('flavor_parkings_nonce', 'parkings_nonce'); ?>

                <div class="flavor-form-group">
                    <label for="parking_id"><?php _e('Parking', 'flavor-chat-ia'); ?></label>
                    <select name="parking_id" id="parking_id" required class="flavor-select">
                        <option value=""><?php _e('Selecciona un parking...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($parkings as $p): ?>
                            <option value="<?php echo esc_attr($p->id); ?>"
                                    <?php selected($atts['parking_id'], $p->id); ?>
                                    data-precio="<?php echo esc_attr($p->precio_hora_visitante); ?>">
                                <?php echo esc_html($p->nombre); ?> (<?php echo intval($p->plazas_libres); ?> libres)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="fecha_inicio"><?php _e('Fecha y hora inicio', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" required
                               class="flavor-input" min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="fecha_fin"><?php _e('Fecha y hora fin', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" name="fecha_fin" id="fecha_fin" required
                               class="flavor-input">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="matricula"><?php _e('Matrícula del vehículo', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="matricula" id="matricula" required
                           class="flavor-input" placeholder="1234 ABC" maxlength="10">
                </div>

                <div class="flavor-form-group">
                    <label for="notas"><?php _e('Notas (opcional)', 'flavor-chat-ia'); ?></label>
                    <textarea name="notas" id="notas" rows="2" class="flavor-textarea"></textarea>
                </div>

                <div id="resumen-reserva" class="flavor-resumen-reserva" style="display: none;">
                    <h4><?php _e('Resumen de la reserva', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-resumen-linea">
                        <span><?php _e('Duración:', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-duracion">-</span>
                    </div>
                    <div class="flavor-resumen-linea total">
                        <span><?php _e('Total estimado:', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-total">0.00€</span>
                    </div>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg flavor-btn-block">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Confirmar Reserva', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas
     */
    public function shortcode_mis_reservas($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $reservas = $this->obtener_todas_reservas($usuario_id);

        ob_start();
        ?>
        <div class="flavor-mis-reservas">
            <?php if (!empty($reservas)): ?>
                <div class="flavor-reservas-tabla">
                    <table class="flavor-tabla">
                        <thead>
                            <tr>
                                <th><?php _e('Parking', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Plaza', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Horario', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Importe', 'flavor-chat-ia'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r): ?>
                                <tr>
                                    <td><?php echo esc_html($r->nombre_parking); ?></td>
                                    <td><?php echo esc_html($r->numero_plaza); ?></td>
                                    <td><?php echo date_i18n('d/m/Y', strtotime($r->fecha_inicio)); ?></td>
                                    <td>
                                        <?php echo date_i18n('H:i', strtotime($r->fecha_inicio)); ?> -
                                        <?php echo date_i18n('H:i', strtotime($r->fecha_fin)); ?>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo $r->estado; ?>">
                                            <?php echo esc_html(ucfirst($r->estado)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($r->importe_total, 2); ?>€</td>
                                    <td>
                                        <?php if ($r->estado === 'pendiente' || $r->estado === 'confirmada'): ?>
                                            <button class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-cancelar"
                                                    data-reserva-id="<?php echo esc_attr($r->id); ?>">
                                                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php _e('No tienes reservas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi plaza asignada
     */
    public function shortcode_mi_plaza($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $plaza = $this->obtener_plaza_asignada($usuario_id);

        if (!$plaza) {
            return '<div class="flavor-notice flavor-notice-info">' .
                   __('No tienes una plaza asignada actualmente.', 'flavor-chat-ia') .
                   '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-mi-plaza">
            <div class="flavor-plaza-grande">
                <div class="flavor-plaza-numero-grande">
                    <?php echo esc_html($plaza->numero_plaza); ?>
                </div>
                <div class="flavor-plaza-detalles-grande">
                    <h3><?php echo esc_html($plaza->nombre_parking); ?></h3>
                    <p><?php echo esc_html($plaza->direccion); ?></p>
                    <div class="flavor-plaza-meta">
                        <span><strong><?php _e('Planta:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($plaza->planta ?: '0'); ?></span>
                        <span><strong><?php _e('Tipo:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(ucfirst($plaza->tipo_plaza)); ?></span>
                        <span><strong><?php _e('Desde:', 'flavor-chat-ia'); ?></strong> <?php echo date_i18n('d/m/Y', strtotime($plaza->fecha_asignacion)); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de espera
     */
    public function shortcode_lista_espera($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para acceder a la lista de espera.', 'flavor-chat-ia') .
                   '</div>';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $en_lista = $this->usuario_en_lista_espera($usuario_id);
        $posicion = $en_lista ? $this->obtener_posicion_lista($usuario_id) : 0;

        ob_start();
        ?>
        <div class="flavor-lista-espera">
            <?php if ($en_lista): ?>
                <div class="flavor-lista-estado">
                    <div class="flavor-posicion-badge">
                        <span class="numero"><?php echo intval($posicion); ?></span>
                        <span class="texto"><?php _e('Tu posición', 'flavor-chat-ia'); ?></span>
                    </div>
                    <p><?php _e('Estás en la lista de espera. Te notificaremos cuando haya una plaza disponible.', 'flavor-chat-ia'); ?></p>
                    <button class="flavor-btn flavor-btn-danger flavor-btn-salir-lista">
                        <?php _e('Salir de la lista', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="flavor-lista-inscripcion">
                    <h3><?php _e('Apúntate a la lista de espera', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Te avisaremos cuando haya una plaza disponible para ti.', 'flavor-chat-ia'); ?></p>

                    <form id="form-lista-espera" class="flavor-form">
                        <?php wp_nonce_field('flavor_parkings_nonce', 'parkings_nonce'); ?>

                        <div class="flavor-form-group">
                            <label><?php _e('Tipo de plaza preferida', 'flavor-chat-ia'); ?></label>
                            <div class="flavor-checkbox-group">
                                <label><input type="checkbox" name="tipos[]" value="normal" checked> <?php _e('Normal', 'flavor-chat-ia'); ?></label>
                                <label><input type="checkbox" name="tipos[]" value="grande"> <?php _e('Grande', 'flavor-chat-ia'); ?></label>
                                <label><input type="checkbox" name="tipos[]" value="electrico"> <?php _e('Con carga eléctrica', 'flavor-chat-ia'); ?></label>
                            </div>
                        </div>

                        <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                            <?php _e('Apuntarme a la lista', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard
     */
    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para acceder.', 'flavor-chat-ia') .
                   '</div>';
        }

        ob_start();
        $this->render_dashboard_tab();
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Reservar plaza
     */
    public function ajax_reservar() {
        check_ajax_referer('flavor_parkings_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        global $wpdb;

        $parking_id = intval($_POST['parking_id'] ?? 0);
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($_POST['fecha_fin'] ?? '');
        $matricula = strtoupper(sanitize_text_field($_POST['matricula'] ?? ''));
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        // Validaciones
        if (!$parking_id || !$fecha_inicio || !$fecha_fin || !$matricula) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        // Buscar plaza disponible
        $plaza = $wpdb->get_row($wpdb->prepare(
            "SELECT p.* FROM {$this->tablas['plazas']} p
             WHERE p.parking_id = %d AND p.estado = 'libre'
             ORDER BY p.numero_plaza ASC LIMIT 1",
            $parking_id
        ));

        if (!$plaza) {
            wp_send_json_error(['message' => __('No hay plazas disponibles', 'flavor-chat-ia')]);
        }

        // Calcular precio
        $horas = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 3600;
        $parking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['parkings']} WHERE id = %d",
            $parking_id
        ));
        $precio_hora = floatval($parking->precio_hora_visitante ?? 1.50);
        $importe = $horas * $precio_hora;

        // Crear reserva
        $resultado = $wpdb->insert($this->tablas['reservas'], [
            'plaza_id' => $plaza->id,
            'usuario_id' => $usuario_id,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'tipo_reserva' => 'temporal',
            'estado' => 'confirmada',
            'matricula' => $matricula,
            'importe_total' => $importe,
            'notas' => $notas,
            'created_at' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s']);

        if ($resultado) {
            // Actualizar estado de plaza
            $wpdb->update(
                $this->tablas['plazas'],
                ['estado' => 'reservada'],
                ['id' => $plaza->id],
                ['%s'],
                ['%d']
            );

            wp_send_json_success([
                'message' => __('¡Reserva confirmada!', 'flavor-chat-ia'),
                'reserva_id' => $wpdb->insert_id,
                'plaza' => $plaza->numero_plaza,
                'importe' => number_format($importe, 2),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear la reserva', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('flavor_parkings_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $reserva_id = intval($_POST['reserva_id'] ?? 0);

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['reservas']} WHERE id = %d AND usuario_id = %d",
            $reserva_id, $usuario_id
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada', 'flavor-chat-ia')]);
        }

        // Cancelar
        $wpdb->update(
            $this->tablas['reservas'],
            ['estado' => 'cancelada'],
            ['id' => $reserva_id],
            ['%s'],
            ['%d']
        );

        // Liberar plaza
        $wpdb->update(
            $this->tablas['plazas'],
            ['estado' => 'libre'],
            ['id' => $reserva->plaza_id],
            ['%s'],
            ['%d']
        );

        wp_send_json_success(['message' => __('Reserva cancelada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Apuntarse a lista de espera
     */
    public function ajax_apuntar_lista_espera() {
        check_ajax_referer('flavor_parkings_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        global $wpdb;

        if ($this->usuario_en_lista_espera($usuario_id)) {
            wp_send_json_error(['message' => __('Ya estás en la lista de espera', 'flavor-chat-ia')]);
        }

        $tipos = isset($_POST['tipos']) ? array_map('sanitize_text_field', $_POST['tipos']) : ['normal'];

        $resultado = $wpdb->insert($this->tablas['lista_espera'], [
            'usuario_id' => $usuario_id,
            'tipos_preferidos' => implode(',', $tipos),
            'estado' => 'activo',
            'created_at' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s']);

        if ($resultado) {
            $posicion = $this->obtener_posicion_lista($usuario_id);
            wp_send_json_success([
                'message' => __('Te has apuntado a la lista de espera', 'flavor-chat-ia'),
                'posicion' => $posicion,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al apuntarse', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Liberar plaza temporalmente
     */
    public function ajax_liberar_plaza() {
        check_ajax_referer('flavor_parkings_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $plaza_id = intval($_POST['plaza_id'] ?? 0);

        // Verificar que la plaza es del usuario
        $asignacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['asignaciones']} WHERE plaza_id = %d AND usuario_id = %d AND estado = 'activa'",
            $plaza_id, $usuario_id
        ));

        if (!$asignacion) {
            wp_send_json_error(['message' => __('No tienes esta plaza asignada', 'flavor-chat-ia')]);
        }

        // Marcar como disponible temporalmente
        $wpdb->update(
            $this->tablas['plazas'],
            ['estado' => 'libre'],
            ['id' => $plaza_id],
            ['%s'],
            ['%d']
        );

        wp_send_json_success(['message' => __('Plaza liberada temporalmente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Buscar plazas
     */
    public function ajax_buscar_plazas() {
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $parking_id = intval($_POST['parking_id'] ?? 0);

        $plazas = $this->obtener_plazas_disponibles([
            'fecha' => $fecha,
            'tipo' => $tipo,
            'parking_id' => $parking_id,
        ]);

        wp_send_json_success(['plazas' => $plazas]);
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    private function obtener_parkings($filtros = []) {
        global $wpdb;

        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM {$this->tablas['plazas']} pl WHERE pl.parking_id = p.id AND pl.estado = 'libre') as plazas_libres
                FROM {$this->tablas['parkings']} p
                WHERE p.estado = 'activo'";

        if (!empty($filtros['con_plazas_libres'])) {
            $sql .= " HAVING plazas_libres > 0";
        }

        $sql .= " ORDER BY p.nombre ASC";

        if (!empty($filtros['limite'])) {
            $sql .= $wpdb->prepare(" LIMIT %d", $filtros['limite']);
        }

        return $wpdb->get_results($sql);
    }

    private function obtener_parkings_cercanos($lat = null, $lng = null, $radio = 5) {
        return $this->obtener_parkings(['limite' => 10]);
    }

    private function obtener_plazas_disponibles($filtros = []) {
        global $wpdb;

        $where = ["pl.estado = 'libre'"];
        $params = [];

        if (!empty($filtros['parking_id'])) {
            $where[] = 'pl.parking_id = %d';
            $params[] = $filtros['parking_id'];
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'pl.tipo_plaza = %s';
            $params[] = $filtros['tipo'];
        }

        $sql = "SELECT pl.*, p.nombre as nombre_parking
                FROM {$this->tablas['plazas']} pl
                LEFT JOIN {$this->tablas['parkings']} p ON pl.parking_id = p.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.nombre, pl.numero_plaza";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    private function obtener_plaza_asignada($usuario_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT pl.*, p.nombre as nombre_parking, p.direccion, a.fecha_asignacion
             FROM {$this->tablas['asignaciones']} a
             INNER JOIN {$this->tablas['plazas']} pl ON a.plaza_id = pl.id
             INNER JOIN {$this->tablas['parkings']} p ON pl.parking_id = p.id
             WHERE a.usuario_id = %d AND a.estado = 'activa'",
            $usuario_id
        ));
    }

    private function obtener_reservas_activas($usuario_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, pl.numero_plaza, p.nombre as nombre_parking
             FROM {$this->tablas['reservas']} r
             INNER JOIN {$this->tablas['plazas']} pl ON r.plaza_id = pl.id
             INNER JOIN {$this->tablas['parkings']} p ON pl.parking_id = p.id
             WHERE r.usuario_id = %d AND r.estado IN ('pendiente', 'confirmada')
               AND r.fecha_fin >= NOW()
             ORDER BY r.fecha_inicio ASC",
            $usuario_id
        ));
    }

    private function obtener_todas_reservas($usuario_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, pl.numero_plaza, p.nombre as nombre_parking
             FROM {$this->tablas['reservas']} r
             INNER JOIN {$this->tablas['plazas']} pl ON r.plaza_id = pl.id
             INNER JOIN {$this->tablas['parkings']} p ON pl.parking_id = p.id
             WHERE r.usuario_id = %d
             ORDER BY r.fecha_inicio DESC
             LIMIT 50",
            $usuario_id
        ));
    }

    private function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $horas = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin))
             FROM {$this->tablas['reservas']}
             WHERE usuario_id = %d AND estado = 'completada'",
            $usuario_id
        ));

        $gasto = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(importe_total)
             FROM {$this->tablas['reservas']}
             WHERE usuario_id = %d AND estado IN ('completada', 'confirmada')",
            $usuario_id
        ));

        return [
            'horas_uso' => floatval($horas ?? 0),
            'gasto_total' => floatval($gasto ?? 0),
        ];
    }

    private function calcular_ocupacion($parking_id) {
        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['plazas']} WHERE parking_id = %d",
            $parking_id
        ));

        $ocupadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['plazas']} WHERE parking_id = %d AND estado != 'libre'",
            $parking_id
        ));

        return $total > 0 ? ($ocupadas / $total) * 100 : 0;
    }

    private function usuario_en_lista_espera($usuario_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['lista_espera']} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));
    }

    private function obtener_posicion_lista($usuario_id) {
        global $wpdb;

        $mi_fecha = $wpdb->get_var($wpdb->prepare(
            "SELECT created_at FROM {$this->tablas['lista_espera']} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (!$mi_fecha) return 0;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM {$this->tablas['lista_espera']}
             WHERE estado = 'activo' AND created_at < %s",
            $mi_fecha
        ));
    }
}

// Inicializar
Flavor_Parkings_Frontend_Controller::get_instance();

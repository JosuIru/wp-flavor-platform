<?php
/**
 * Frontend Controller para Reciclaje
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador frontend del módulo Reciclaje
 */
class Flavor_Reciclaje_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Reciclaje_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Configuración del módulo
     * @var array
     */
    private $config = [];

    /**
     * Categorías de reciclaje
     */
    private $categorias = [
        'papel' => ['nombre' => 'Papel y Cartón', 'color' => '#3b82f6', 'icono' => 'dashicons-media-default'],
        'plastico' => ['nombre' => 'Plástico', 'color' => '#eab308', 'icono' => 'dashicons-star-filled'],
        'vidrio' => ['nombre' => 'Vidrio', 'color' => '#22c55e', 'icono' => 'dashicons-visibility'],
        'organico' => ['nombre' => 'Orgánico', 'color' => '#84cc16', 'icono' => 'dashicons-carrot'],
        'electronico' => ['nombre' => 'Electrónico', 'color' => '#f97316', 'icono' => 'dashicons-laptop'],
        'ropa' => ['nombre' => 'Textil', 'color' => '#8b5cf6', 'icono' => 'dashicons-businessman'],
        'aceite' => ['nombre' => 'Aceite', 'color' => '#f59e0b', 'icono' => 'dashicons-admin-appearance'],
        'pilas' => ['nombre' => 'Pilas', 'color' => '#ef4444', 'icono' => 'dashicons-warning'],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->config = flavor_get_main_settings();
        $this->init();
    }

    /**
     * Obtiene instancia singleton
     * @return Flavor_Reciclaje_Frontend_Controller
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
            'flavor_reciclaje_mapa' => 'shortcode_mapa',
            'flavor_reciclaje_puntos' => 'shortcode_puntos',
            'flavor_reciclaje_registrar' => 'shortcode_registrar',
            'flavor_reciclaje_mis_registros' => 'shortcode_mis_registros',
            'flavor_reciclaje_canjear' => 'shortcode_canjear',
            'flavor_reciclaje_guia' => 'shortcode_guia',
            'flavor_reciclaje_estadisticas' => 'shortcode_estadisticas',
            'flavor_reciclaje_reportar' => 'shortcode_reportar',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_reciclaje_registrar', [$this, 'ajax_registrar']);
        add_action('wp_ajax_flavor_reciclaje_canjear', [$this, 'ajax_canjear']);
        add_action('wp_ajax_flavor_reciclaje_reportar', [$this, 'ajax_reportar']);
        add_action('wp_ajax_flavor_reciclaje_buscar_puntos', [$this, 'ajax_buscar_puntos']);
        add_action('wp_ajax_nopriv_flavor_reciclaje_buscar_puntos', [$this, 'ajax_buscar_puntos']);

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
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-reciclaje-frontend',
            $base_url . 'assets/css/reciclaje-frontend.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-reciclaje-frontend',
            $base_url . 'assets/js/reciclaje-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-reciclaje-frontend', 'flavorReciclajeConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_reciclaje_nonce'),
            'categorias' => $this->categorias,
            'strings' => [
                'procesando' => __('Procesando...', 'flavor-platform'),
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'registrado' => __('Reciclaje registrado correctamente', 'flavor-platform'),
                'canjeExitoso' => __('Canje realizado correctamente', 'flavor-platform'),
                'reporteEnviado' => __('Reporte enviado correctamente', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    private function cargar_assets() {
        wp_enqueue_style('flavor-reciclaje-frontend');
        wp_enqueue_script('flavor-reciclaje-frontend');

        // Leaflet para mapas
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
    }

    /**
     * Registra tabs en dashboard del usuario
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['reciclaje'] = [
            'titulo' => __('Reciclaje', 'flavor-platform'),
            'icono' => 'dashicons-image-rotate',
            'prioridad' => 65,
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
        $tabla_registros = $wpdb->prefix . 'flavor_reciclaje_registros';
        $tabla_puntos_usuario = $wpdb->prefix . 'flavor_reciclaje_puntos_usuario';

        // Puntos acumulados
        $puntos_info = null;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_puntos_usuario)) {
            $puntos_info = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_puntos_usuario WHERE usuario_id = %d",
                $usuario_id
            ));
        }

        $puntos_totales = $puntos_info->puntos_totales ?? 0;
        $puntos_disponibles = $puntos_info->puntos_disponibles ?? 0;

        // Estadísticas por categoría
        $estadisticas_categoria = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_registros)) {
            $estadisticas_categoria = $wpdb->get_results($wpdb->prepare(
                "SELECT categoria, SUM(cantidad_kg) as total_kg, COUNT(*) as registros
                 FROM $tabla_registros
                 WHERE usuario_id = %d
                 GROUP BY categoria",
                $usuario_id
            ), OBJECT_K);
        }

        // Total reciclado
        $total_kg = array_sum(array_column((array) $estadisticas_categoria, 'total_kg'));

        // Registros recientes
        $registros_recientes = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_registros)) {
            $registros_recientes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_registros WHERE usuario_id = %d ORDER BY fecha_registro DESC LIMIT 10",
                $usuario_id
            ));
        }

        // Impacto ambiental estimado
        $co2_ahorrado = $total_kg * 2.5; // kg CO2 por kg reciclado (estimación)
        $arboles_salvados = $total_kg * 0.017; // árboles por kg papel

        ob_start();
        ?>
        <div class="flavor-reciclaje-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-image-rotate"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($total_kg, 1); ?> kg</span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Total reciclado', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-star-filled"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($puntos_disponibles); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Puntos disponibles', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-cloud"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($co2_ahorrado, 1); ?> kg</span>
                        <span class="flavor-kpi-etiqueta"><?php _e('CO₂ ahorrado', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-palmtree"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($arboles_salvados, 1); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Árboles salvados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-acciones-rapidas">
                <button type="button" class="flavor-accion-card" id="btn-registrar-reciclaje">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span><?php _e('Registrar reciclaje', 'flavor-platform'); ?></span>
                </button>
                <a href="#mapa-puntos" class="flavor-accion-card">
                    <span class="dashicons dashicons-location-alt"></span>
                    <span><?php _e('Puntos cercanos', 'flavor-platform'); ?></span>
                </a>
                <a href="#canjear" class="flavor-accion-card">
                    <span class="dashicons dashicons-awards"></span>
                    <span><?php _e('Canjear puntos', 'flavor-platform'); ?></span>
                </a>
            </div>

            <!-- Estadísticas por categoría -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php _e('Mi reciclaje', 'flavor-platform'); ?>
                </h3>
                <div class="flavor-categorias-grid">
                    <?php foreach ($this->categorias as $cat_id => $cat): ?>
                    <?php $cat_stats = $estadisticas_categoria[$cat_id] ?? null; ?>
                    <div class="flavor-categoria-stat" style="--cat-color: <?php echo esc_attr($cat['color']); ?>">
                        <div class="flavor-categoria-icono">
                            <span class="<?php echo esc_attr($cat['icono']); ?>"></span>
                        </div>
                        <div class="flavor-categoria-info">
                            <span class="flavor-categoria-nombre"><?php echo esc_html($cat['nombre']); ?></span>
                            <span class="flavor-categoria-valor"><?php echo number_format($cat_stats->total_kg ?? 0, 1); ?> kg</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Historial reciente -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Registros recientes', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($registros_recientes)): ?>
                <div class="flavor-registros-lista">
                    <?php foreach ($registros_recientes as $registro): ?>
                    <div class="flavor-registro-item">
                        <div class="flavor-registro-icono" style="background: <?php echo esc_attr($this->categorias[$registro->categoria]['color'] ?? '#64748b'); ?>">
                            <span class="<?php echo esc_attr($this->categorias[$registro->categoria]['icono'] ?? 'dashicons-image-rotate'); ?>"></span>
                        </div>
                        <div class="flavor-registro-info">
                            <strong><?php echo esc_html($this->categorias[$registro->categoria]['nombre'] ?? $registro->categoria); ?></strong>
                            <span><?php echo number_format($registro->cantidad_kg, 2); ?> kg</span>
                        </div>
                        <div class="flavor-registro-meta">
                            <span class="flavor-fecha"><?php echo date_i18n('j M Y', strtotime($registro->fecha_registro)); ?></span>
                            <span class="flavor-puntos">+<?php echo intval($registro->puntos_ganados); ?> pts</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('Aún no has registrado reciclaje', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Modal registrar -->
            <div id="modal-registrar-reciclaje" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-contenido">
                    <button type="button" class="flavor-modal-cerrar">&times;</button>
                    <h3><?php _e('Registrar reciclaje', 'flavor-platform'); ?></h3>
                    <form id="form-registrar-reciclaje">
                        <div class="flavor-form-grupo">
                            <label><?php _e('Categoría', 'flavor-platform'); ?></label>
                            <div class="flavor-categorias-selector">
                                <?php foreach ($this->categorias as $cat_id => $cat): ?>
                                <label class="flavor-categoria-opcion" style="--cat-color: <?php echo esc_attr($cat['color']); ?>">
                                    <input type="radio" name="categoria" value="<?php echo esc_attr($cat_id); ?>" required>
                                    <span class="<?php echo esc_attr($cat['icono']); ?>"></span>
                                    <span><?php echo esc_html($cat['nombre']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Cantidad (kg)', 'flavor-platform'); ?></label>
                            <input type="number" name="cantidad_kg" step="0.1" min="0.1" max="500" required placeholder="0.0">
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Punto de reciclaje (opcional)', 'flavor-platform'); ?></label>
                            <select name="punto_id" id="select-punto-reciclaje">
                                <option value=""><?php _e('Seleccionar...', 'flavor-platform'); ?></option>
                            </select>
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Notas (opcional)', 'flavor-platform'); ?></label>
                            <textarea name="notas" rows="2" placeholder="<?php esc_attr_e('Información adicional...', 'flavor-platform'); ?>"></textarea>
                        </div>
                        <div class="flavor-form-acciones">
                            <button type="button" class="flavor-btn flavor-btn-outline flavor-modal-cerrar-btn">
                                <?php _e('Cancelar', 'flavor-platform'); ?>
                            </button>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Registrar', 'flavor-platform'); ?>
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
     * Shortcode: Mapa de puntos de reciclaje
     */
    public function shortcode_mapa($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
            return '<p class="flavor-aviso">' . __('Mapa no disponible', 'flavor-platform') . '</p>';
        }

        $puntos = $wpdb->get_results(
            "SELECT * FROM $tabla_puntos WHERE estado = 'activo' ORDER BY nombre ASC"
        );

        $atts = shortcode_atts([
            'altura' => '450px',
            'lat' => '40.4168',
            'lng' => '-3.7038',
            'zoom' => '13',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-reciclaje-mapa-container" id="mapa-puntos">
            <div id="flavor-mapa-reciclaje"
                 style="height: <?php echo esc_attr($atts['altura']); ?>"
                 data-lat="<?php echo esc_attr($atts['lat']); ?>"
                 data-lng="<?php echo esc_attr($atts['lng']); ?>"
                 data-zoom="<?php echo esc_attr($atts['zoom']); ?>">
            </div>
            <script type="application/json" id="puntos-reciclaje-data">
                <?php echo wp_json_encode(array_map(function($p) {
                    return [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'direccion' => $p->direccion,
                        'lat' => floatval($p->latitud),
                        'lng' => floatval($p->longitud),
                        'tipo' => $p->tipo,
                        'categorias' => json_decode($p->categorias_aceptadas ?? '[]'),
                        'horario' => $p->horario,
                    ];
                }, $puntos)); ?>
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de puntos de reciclaje
     */
    public function shortcode_puntos($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
            return '';
        }

        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
        ], $atts);

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categorias_aceptadas LIKE %s";
            $params[] = '%' . $wpdb->esc_like($atts['categoria']) . '%';
        }

        $sql = "SELECT * FROM $tabla_puntos WHERE " . implode(' AND ', $where) . " ORDER BY nombre ASC LIMIT " . intval($atts['limite']);

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $puntos = $wpdb->get_results($sql);

        ob_start();
        ?>
        <div class="flavor-puntos-reciclaje-grid">
            <?php foreach ($puntos as $punto): ?>
            <div class="flavor-punto-card">
                <div class="flavor-punto-header">
                    <h4><?php echo esc_html($punto->nombre); ?></h4>
                    <span class="flavor-badge"><?php echo esc_html(ucfirst($punto->tipo)); ?></span>
                </div>
                <p class="flavor-punto-direccion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($punto->direccion); ?>
                </p>
                <?php if (!empty($punto->horario)): ?>
                <p class="flavor-punto-horario">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html($punto->horario); ?>
                </p>
                <?php endif; ?>
                <div class="flavor-punto-categorias">
                    <?php
                    $categorias = json_decode($punto->categorias_aceptadas ?? '[]', true);
                    foreach ((array) $categorias as $cat):
                        if (isset($this->categorias[$cat])):
                    ?>
                    <span class="flavor-mini-badge" style="background: <?php echo esc_attr($this->categorias[$cat]['color']); ?>">
                        <?php echo esc_html($this->categorias[$cat]['nombre']); ?>
                    </span>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas globales
     */
    public function shortcode_estadisticas($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_registros = $wpdb->prefix . 'flavor_reciclaje_registros';
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        $stats = (object)[
            'total_kg' => 0,
            'usuarios' => 0,
            'puntos_reciclaje' => 0,
        ];

        if (Flavor_Platform_Helpers::tabla_existe($tabla_registros)) {
            $stats->total_kg = $wpdb->get_var("SELECT SUM(cantidad_kg) FROM $tabla_registros") ?? 0;
            $stats->usuarios = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_registros") ?? 0;
        }
        if (Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
            $stats->puntos_reciclaje = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos WHERE estado = 'activo'") ?? 0;
        }

        $co2_ahorrado = $stats->total_kg * 2.5;

        ob_start();
        ?>
        <div class="flavor-reciclaje-estadisticas">
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-image-rotate"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->total_kg, 0); ?> kg</div>
                    <div class="flavor-stat-etiqueta"><?php _e('Total reciclado', 'flavor-platform'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-cloud"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($co2_ahorrado, 0); ?> kg</div>
                    <div class="flavor-stat-etiqueta"><?php _e('CO₂ ahorrado', 'flavor-platform'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-groups"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->usuarios); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Participantes', 'flavor-platform'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-location-alt"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->puntos_reciclaje); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Puntos de reciclaje', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Guía de reciclaje
     */
    public function shortcode_guia($atts) {
        $this->cargar_assets();

        ob_start();
        ?>
        <div class="flavor-guia-reciclaje">
            <h2><?php _e('Guía de reciclaje', 'flavor-platform'); ?></h2>
            <div class="flavor-guia-grid">
                <?php foreach ($this->categorias as $cat_id => $cat): ?>
                <div class="flavor-guia-card" style="--cat-color: <?php echo esc_attr($cat['color']); ?>">
                    <div class="flavor-guia-icono">
                        <span class="<?php echo esc_attr($cat['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($cat['nombre']); ?></h3>
                    <p><?php echo esc_html($this->obtener_descripcion_categoria($cat_id)); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Registrar reciclaje
     */
    public function ajax_registrar() {
        check_ajax_referer('flavor_reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $usuario_id = get_current_user_id();
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $cantidad_kg = floatval($_POST['cantidad_kg'] ?? 0);
        $punto_id = absint($_POST['punto_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        $tabla_registros = $wpdb->prefix . 'flavor_reciclaje_registros';
        $tabla_puntos_usuario = $wpdb->prefix . 'flavor_reciclaje_puntos_usuario';

        if (!isset($this->categorias[$categoria])) {
            wp_send_json_error(['message' => __('Categoría no válida', 'flavor-platform')]);
        }

        if ($cantidad_kg <= 0 || $cantidad_kg > 500) {
            wp_send_json_error(['message' => __('Cantidad no válida', 'flavor-platform')]);
        }

        // Calcular puntos (10 puntos por kg por defecto)
        $puntos_por_kg = $this->config['reciclaje']['puntos_por_kg'] ?? 10;
        $puntos_ganados = round($cantidad_kg * $puntos_por_kg);

        // Registrar
        $resultado = $wpdb->insert($tabla_registros, [
            'usuario_id' => $usuario_id,
            'categoria' => $categoria,
            'cantidad_kg' => $cantidad_kg,
            'punto_reciclaje_id' => $punto_id > 0 ? $punto_id : null,
            'puntos_ganados' => $puntos_ganados,
            'notas' => $notas,
            'fecha_registro' => current_time('mysql'),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al registrar', 'flavor-platform')]);
        }

        // Actualizar puntos del usuario
        if (Flavor_Platform_Helpers::tabla_existe($tabla_puntos_usuario)) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_puntos_usuario WHERE usuario_id = %d",
                $usuario_id
            ));

            if ($existe) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_puntos_usuario SET
                     puntos_totales = puntos_totales + %d,
                     puntos_disponibles = puntos_disponibles + %d,
                     total_kg_reciclado = total_kg_reciclado + %f
                     WHERE usuario_id = %d",
                    $puntos_ganados, $puntos_ganados, $cantidad_kg, $usuario_id
                ));
            } else {
                $wpdb->insert($tabla_puntos_usuario, [
                    'usuario_id' => $usuario_id,
                    'puntos_totales' => $puntos_ganados,
                    'puntos_disponibles' => $puntos_ganados,
                    'total_kg_reciclado' => $cantidad_kg,
                ]);
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Registrado: %s kg de %s. Has ganado %d puntos.', 'flavor-platform'),
                number_format($cantidad_kg, 2),
                $this->categorias[$categoria]['nombre'],
                $puntos_ganados
            ),
            'puntos_ganados' => $puntos_ganados,
        ]);
    }

    /**
     * AJAX: Canjear puntos
     */
    public function ajax_canjear() {
        check_ajax_referer('flavor_reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $premio_id = absint($_POST['premio_id'] ?? 0);
        $usuario_id = get_current_user_id();

        $tabla_premios = $wpdb->prefix . 'flavor_reciclaje_premios';
        $tabla_puntos_usuario = $wpdb->prefix . 'flavor_reciclaje_puntos_usuario';
        $tabla_canjes = $wpdb->prefix . 'flavor_reciclaje_canjes';

        $premio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_premios WHERE id = %d AND estado = 'activo'",
            $premio_id
        ));

        if (!$premio) {
            wp_send_json_error(['message' => __('Premio no disponible', 'flavor-platform')]);
        }

        $puntos_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT puntos_disponibles FROM $tabla_puntos_usuario WHERE usuario_id = %d",
            $usuario_id
        )) ?? 0;

        if ($puntos_usuario < $premio->puntos_requeridos) {
            wp_send_json_error(['message' => __('No tienes suficientes puntos', 'flavor-platform')]);
        }

        // Realizar canje
        $wpdb->insert($tabla_canjes, [
            'usuario_id' => $usuario_id,
            'premio_id' => $premio_id,
            'puntos_usados' => $premio->puntos_requeridos,
            'estado' => 'pendiente',
            'fecha_canje' => current_time('mysql'),
        ]);

        // Descontar puntos
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_puntos_usuario SET puntos_disponibles = puntos_disponibles - %d WHERE usuario_id = %d",
            $premio->puntos_requeridos, $usuario_id
        ));

        wp_send_json_success([
            'message' => sprintf(__('Has canjeado: %s', 'flavor-platform'), $premio->nombre),
        ]);
    }

    /**
     * AJAX: Reportar contenedor
     */
    public function ajax_reportar() {
        check_ajax_referer('flavor_reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $punto_id = absint($_POST['punto_id'] ?? 0);
        $tipo_problema = sanitize_text_field($_POST['tipo_problema'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        $tabla_reportes = $wpdb->prefix . 'flavor_reciclaje_reportes';

        if (Flavor_Platform_Helpers::tabla_existe($tabla_reportes)) {
            $wpdb->insert($tabla_reportes, [
                'punto_id' => $punto_id,
                'usuario_id' => get_current_user_id(),
                'tipo' => $tipo_problema,
                'descripcion' => $descripcion,
                'estado' => 'pendiente',
                'fecha' => current_time('mysql'),
            ]);
        }

        wp_send_json_success([
            'message' => __('Reporte enviado. Gracias por colaborar.', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Buscar puntos de reciclaje
     */
    public function ajax_buscar_puntos() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($categoria)) {
            $where[] = "categorias_aceptadas LIKE %s";
            $params[] = '%' . $wpdb->esc_like($categoria) . '%';
        }

        $sql = "SELECT * FROM $tabla_puntos WHERE " . implode(' AND ', $where);

        if ($lat && $lng) {
            $sql .= " ORDER BY (POW(latitud - $lat, 2) + POW(longitud - $lng, 2)) ASC";
        } else {
            $sql .= " ORDER BY nombre ASC";
        }

        $sql .= " LIMIT 20";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $puntos = $wpdb->get_results($sql);

        wp_send_json_success([
            'puntos' => array_map(function($p) {
                return [
                    'id' => intval($p->id),
                    'nombre' => $p->nombre,
                    'direccion' => $p->direccion,
                    'lat' => floatval($p->latitud),
                    'lng' => floatval($p->longitud),
                ];
            }, $puntos),
        ]);
    }

    /**
     * Shortcode: Canjear puntos por recompensas
     */
    public function shortcode_canjear($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso">' . __('Inicia sesión para canjear tus puntos.', 'flavor-platform') . '</div>';
        }

        $this->cargar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $tabla_recompensas = $wpdb->prefix . 'flavor_reciclaje_recompensas';

        // Obtener puntos del usuario
        $puntos_usuario = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
            $puntos_usuario = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(puntos), 0) FROM $tabla_puntos WHERE usuario_id = %d",
                $usuario_id
            ));
        }

        // Obtener recompensas disponibles
        $recompensas = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_recompensas)) {
            $recompensas = $wpdb->get_results(
                "SELECT * FROM $tabla_recompensas WHERE activa = 1 AND stock > 0 ORDER BY puntos_necesarios ASC"
            );
        }

        ob_start();
        ?>
        <div class="flavor-reciclaje-canjear">
            <div class="flavor-puntos-header">
                <h3><?php _e('Canjear Puntos', 'flavor-platform'); ?></h3>
                <div class="flavor-puntos-usuario">
                    <span class="flavor-puntos-icono">🌿</span>
                    <span class="flavor-puntos-valor"><?php echo number_format($puntos_usuario); ?></span>
                    <span class="flavor-puntos-label"><?php _e('puntos disponibles', 'flavor-platform'); ?></span>
                </div>
            </div>

            <?php if (empty($recompensas)): ?>
                <div class="flavor-vacio">
                    <p><?php _e('No hay recompensas disponibles en este momento.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-recompensas-grid">
                    <?php foreach ($recompensas as $recompensa): ?>
                        <?php $puede_canjear = $puntos_usuario >= $recompensa->puntos_necesarios; ?>
                        <div class="flavor-recompensa-card <?php echo !$puede_canjear ? 'no-disponible' : ''; ?>">
                            <?php if (!empty($recompensa->imagen)): ?>
                                <img src="<?php echo esc_url($recompensa->imagen); ?>" alt="" class="flavor-recompensa-imagen">
                            <?php else: ?>
                                <div class="flavor-recompensa-icono">🎁</div>
                            <?php endif; ?>

                            <div class="flavor-recompensa-info">
                                <h4><?php echo esc_html($recompensa->nombre); ?></h4>
                                <p><?php echo esc_html($recompensa->descripcion); ?></p>
                                <div class="flavor-recompensa-puntos">
                                    <span class="flavor-puntos-necesarios"><?php echo number_format($recompensa->puntos_necesarios); ?></span>
                                    <span class="flavor-puntos-texto"><?php _e('puntos', 'flavor-platform'); ?></span>
                                </div>
                                <p class="flavor-recompensa-stock">
                                    <?php printf(__('%d disponibles', 'flavor-platform'), $recompensa->stock); ?>
                                </p>
                            </div>

                            <div class="flavor-recompensa-accion">
                                <?php if ($puede_canjear): ?>
                                    <button type="button" class="flavor-btn flavor-btn-primary flavor-canjear-recompensa"
                                            data-recompensa-id="<?php echo esc_attr($recompensa->id); ?>"
                                            data-puntos="<?php echo esc_attr($recompensa->puntos_necesarios); ?>">
                                        <?php _e('Canjear', 'flavor-platform'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="flavor-btn flavor-btn-disabled">
                                        <?php printf(__('Faltan %d puntos', 'flavor-platform'),
                                            $recompensa->puntos_necesarios - $puntos_usuario); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-canjear-info">
                <h4><?php _e('¿Cómo conseguir más puntos?', 'flavor-platform'); ?></h4>
                <ul>
                    <li><?php _e('Registra tus aportes de reciclaje', 'flavor-platform'); ?></li>
                    <li><?php _e('Participa en campañas de limpieza', 'flavor-platform'); ?></li>
                    <li><?php _e('Completa retos ecológicos', 'flavor-platform'); ?></li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Obtiene descripción de categoría
     */
    private function obtener_descripcion_categoria($categoria) {
        $descripciones = [
            'papel' => __('Periódicos, revistas, cartón, papel de oficina', 'flavor-platform'),
            'plastico' => __('Botellas, envases, bolsas plásticas', 'flavor-platform'),
            'vidrio' => __('Botellas, frascos, tarros de cristal', 'flavor-platform'),
            'organico' => __('Restos de comida, cáscaras, hojas', 'flavor-platform'),
            'electronico' => __('Móviles, ordenadores, electrodomésticos', 'flavor-platform'),
            'ropa' => __('Ropa, zapatos, textiles en buen estado', 'flavor-platform'),
            'aceite' => __('Aceite de cocina usado', 'flavor-platform'),
            'pilas' => __('Pilas, baterías, acumuladores', 'flavor-platform'),
        ];
        return $descripciones[$categoria] ?? '';
    }
}

// Inicializar
Flavor_Reciclaje_Frontend_Controller::get_instance();

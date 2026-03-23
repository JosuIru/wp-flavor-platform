<?php
/**
 * Frontend Controller para Mapa de Actores
 *
 * @package FlavorChatIA
 * @subpackage Modules\MapaActores
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend para Mapa de Actores
 */
class Flavor_Mapa_Actores_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Mapa_Actores_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Tablas del módulo
     */
    private $tabla_actores;
    private $tabla_relaciones;
    private $tabla_interacciones;

    /**
     * Tipos de actor
     */
    private $tipos_actor = [
        'administracion_publica' => ['nombre' => 'Administración Pública', 'icono' => '🏛️', 'color' => '#3b82f6'],
        'empresa' => ['nombre' => 'Empresa', 'icono' => '🏢', 'color' => '#10b981'],
        'institucion' => ['nombre' => 'Institución', 'icono' => '🎓', 'color' => '#8b5cf6'],
        'medio_comunicacion' => ['nombre' => 'Medio de Comunicación', 'icono' => '📰', 'color' => '#f59e0b'],
        'partido_politico' => ['nombre' => 'Partido Político', 'icono' => '🗳️', 'color' => '#ef4444'],
        'sindicato' => ['nombre' => 'Sindicato', 'icono' => '✊', 'color' => '#dc2626'],
        'ong' => ['nombre' => 'ONG', 'icono' => '💚', 'color' => '#22c55e'],
        'colectivo' => ['nombre' => 'Colectivo', 'icono' => '👥', 'color' => '#6366f1'],
        'persona' => ['nombre' => 'Persona', 'icono' => '👤', 'color' => '#64748b'],
        'otro' => ['nombre' => 'Otro', 'icono' => '📌', 'color' => '#94a3b8'],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $this->tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
        $this->tabla_interacciones = $wpdb->prefix . 'flavor_mapa_actores_interacciones';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Mapa_Actores_Frontend_Controller
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
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);

        // Shortcodes
        $shortcodes = [
            'flavor_mapa_actores' => 'shortcode_mapa',
            'flavor_mapa_actores_directorio' => 'shortcode_directorio',
            'flavor_mapa_actores_detalle' => 'shortcode_detalle',
            'flavor_mapa_actores_buscador' => 'shortcode_buscador',
            'flavor_mapa_actores_grafo' => 'shortcode_grafo',
            'flavor_mapa_actores_proponer' => 'shortcode_proponer',
            'flavor_mapa_actores_dashboard' => 'shortcode_dashboard',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_mapa_actores_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_flavor_mapa_actores_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_mapa_actores_proponer', [$this, 'ajax_proponer']);
        add_action('wp_ajax_flavor_mapa_actores_detalle', [$this, 'ajax_detalle']);
        add_action('wp_ajax_nopriv_flavor_mapa_actores_detalle', [$this, 'ajax_detalle']);
        add_action('wp_ajax_flavor_mapa_actores_relaciones', [$this, 'ajax_relaciones']);
        add_action('wp_ajax_nopriv_flavor_mapa_actores_relaciones', [$this, 'ajax_relaciones']);
    }

    /**
     * Registra los assets del módulo
     */
    public function register_assets() {
        wp_register_style(
            'flavor-mapa-actores-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/mapa-actores-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-mapa-actores-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/mapa-actores-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // D3.js para el grafo de relaciones
        wp_register_script(
            'd3-js',
            'https://d3js.org/d3.v7.min.js',
            [],
            '7.0.0',
            true
        );
    }

    /**
     * Encola los assets
     */
    private function enqueue_assets($incluir_d3 = false) {
        wp_enqueue_style('flavor-mapa-actores-frontend');
        wp_enqueue_script('flavor-mapa-actores-frontend');

        if ($incluir_d3) {
            wp_enqueue_script('d3-js');
        }

        wp_localize_script('flavor-mapa-actores-frontend', 'flavorMapaActoresConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_mapa_actores_nonce'),
            'tipos' => $this->tipos_actor,
            'strings' => [
                'buscando' => __('Buscando...', 'flavor-chat-ia'),
                'sin_resultados' => __('No se encontraron actores', 'flavor-chat-ia'),
                'error' => __('Error al procesar', 'flavor-chat-ia'),
                'propuesto' => __('Actor propuesto correctamente', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registra los tabs del dashboard
     *
     * @param array $tabs
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['mapa-actores'] = [
            'label' => __('Mapa de Actores', 'flavor-chat-ia'),
            'icon' => 'networking',
            'callback' => [$this, 'render_tab_mapa_actores'],
            'orden' => 85,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de mapa de actores en dashboard
     */
    public function render_tab_mapa_actores() {
        $this->enqueue_assets();

        global $wpdb;
        $total_actores = 0;
        $por_tipo = [];
        $recientes = [];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_actores)) {
            $total_actores = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_actores} WHERE activo = 1");

            $por_tipo = $wpdb->get_results(
                "SELECT tipo, COUNT(*) as total FROM {$this->tabla_actores} WHERE activo = 1 GROUP BY tipo ORDER BY total DESC LIMIT 5",
                ARRAY_A
            );

            $recientes = $wpdb->get_results(
                "SELECT id, nombre, tipo, ambito FROM {$this->tabla_actores} WHERE activo = 1 ORDER BY created_at DESC LIMIT 6"
            );
        }

        $total_relaciones = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_relaciones)) {
            $total_relaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_relaciones}");
        }

        ?>
        <div class="flavor-panel flavor-mapa-actores-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-networking"></span> <?php esc_html_e('Mapa de Actores', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Directorio de actores del territorio', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_actores); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Actores', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-links"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_relaciones); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Relaciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <?php foreach (array_slice($por_tipo, 0, 2) as $tipo): ?>
                    <div class="flavor-kpi-card">
                        <span class="flavor-kpi-icon"><?php echo esc_html($this->tipos_actor[$tipo['tipo']]['icono'] ?? '📌'); ?></span>
                        <div class="flavor-kpi-content">
                            <span class="flavor-kpi-value"><?php echo number_format_i18n($tipo['total']); ?></span>
                            <span class="flavor-kpi-label"><?php echo esc_html($this->tipos_actor[$tipo['tipo']]['nombre'] ?? $tipo['tipo']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($recientes)): ?>
                <div class="flavor-panel-section">
                    <h3><?php esc_html_e('Actores recientes', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-actores-grid">
                        <?php foreach ($recientes as $actor):
                            $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];
                        ?>
                            <div class="flavor-actor-card" data-id="<?php echo esc_attr($actor->id); ?>">
                                <span class="flavor-actor-icono" style="background-color:<?php echo esc_attr($tipo_info['color']); ?>">
                                    <?php echo esc_html($tipo_info['icono']); ?>
                                </span>
                                <div class="flavor-actor-info">
                                    <h4><?php echo esc_html($actor->nombre); ?></h4>
                                    <span class="flavor-actor-tipo"><?php echo esc_html($tipo_info['nombre']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mapa-actores/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php esc_html_e('Ver Mapa', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mapa-actores/directorio/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Directorio', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mapa-actores/grafo/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e('Grafo', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(home_url('/mapa-actores/proponer/')); ?>" class="flavor-btn flavor-btn-outline">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Proponer', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mapa geográfico de actores
     */
    public function shortcode_mapa($atts) {
        $this->enqueue_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        $atts = shortcode_atts([
            'tipo' => '',
            'ambito' => '',
            'lat' => '40.4168',
            'lng' => '-3.7038',
            'zoom' => '10',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-mapa-actores-mapa">
            <div id="mapa-actores-geografico"
                 data-lat="<?php echo esc_attr($atts['lat']); ?>"
                 data-lng="<?php echo esc_attr($atts['lng']); ?>"
                 data-zoom="<?php echo esc_attr($atts['zoom']); ?>"
                 style="height: 500px;">
            </div>
            <div class="flavor-mapa-leyenda">
                <?php foreach ($this->tipos_actor as $key => $tipo): ?>
                    <label class="flavor-leyenda-item">
                        <input type="checkbox" name="tipo_filtro" value="<?php echo esc_attr($key); ?>" checked>
                        <span class="flavor-leyenda-color" style="background-color:<?php echo esc_attr($tipo['color']); ?>"></span>
                        <span><?php echo esc_html($tipo['nombre']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Directorio de actores
     */
    public function shortcode_directorio($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'tipo' => '',
            'ambito' => '',
            'limite' => 24,
        ], $atts);

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_actores)) {
            return '<p class="flavor-notice">' . esc_html__('Directorio no disponible.', 'flavor-chat-ia') . '</p>';
        }

        $where = "WHERE activo = 1";
        $params = [];

        if (!empty($atts['tipo'])) {
            $where .= " AND tipo = %s";
            $params[] = sanitize_key($atts['tipo']);
        }

        if (!empty($atts['ambito'])) {
            $where .= " AND ambito = %s";
            $params[] = sanitize_key($atts['ambito']);
        }

        $limite = min(100, max(1, intval($atts['limite'])));
        $query = "SELECT * FROM {$this->tabla_actores} {$where} ORDER BY nombre ASC LIMIT %d";
        $params[] = $limite;

        $actores = $wpdb->get_results($wpdb->prepare($query, ...$params));

        ob_start();
        ?>
        <div class="flavor-mapa-actores-directorio">
            <!-- Filtros -->
            <div class="flavor-filtros-bar">
                <select id="filtro-tipo" class="flavor-select">
                    <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($this->tipos_actor as $key => $tipo): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($tipo['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filtro-ambito" class="flavor-select">
                    <option value=""><?php esc_html_e('Todos los ámbitos', 'flavor-chat-ia'); ?></option>
                    <option value="local"><?php esc_html_e('Local', 'flavor-chat-ia'); ?></option>
                    <option value="comarcal"><?php esc_html_e('Comarcal', 'flavor-chat-ia'); ?></option>
                    <option value="provincial"><?php esc_html_e('Provincial', 'flavor-chat-ia'); ?></option>
                    <option value="autonomico"><?php esc_html_e('Autonómico', 'flavor-chat-ia'); ?></option>
                    <option value="estatal"><?php esc_html_e('Estatal', 'flavor-chat-ia'); ?></option>
                </select>
                <div class="flavor-search-box">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="busqueda-actor" placeholder="<?php esc_attr_e('Buscar actor...', 'flavor-chat-ia'); ?>" class="flavor-input">
                </div>
            </div>

            <!-- Grid de actores -->
            <div id="actores-grid" class="flavor-actores-grid-full">
                <?php if (empty($actores)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-networking"></span>
                        <p><?php esc_html_e('No hay actores registrados.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($actores as $actor):
                        $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];
                    ?>
                        <article class="flavor-actor-card-full" data-id="<?php echo esc_attr($actor->id); ?>">
                            <div class="flavor-actor-header">
                                <span class="flavor-actor-icono-lg" style="background-color:<?php echo esc_attr($tipo_info['color']); ?>">
                                    <?php echo esc_html($tipo_info['icono']); ?>
                                </span>
                                <div class="flavor-actor-title">
                                    <h3><?php echo esc_html($actor->nombre); ?></h3>
                                    <span class="flavor-actor-tipo"><?php echo esc_html($tipo_info['nombre']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($actor->descripcion)): ?>
                                <p class="flavor-actor-descripcion"><?php echo esc_html(wp_trim_words($actor->descripcion, 20)); ?></p>
                            <?php endif; ?>
                            <div class="flavor-actor-meta">
                                <?php if (!empty($actor->ambito)): ?>
                                    <span><span class="dashicons dashicons-location"></span> <?php echo esc_html(ucfirst($actor->ambito)); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($actor->municipio)): ?>
                                    <span><span class="dashicons dashicons-admin-home"></span> <?php echo esc_html($actor->municipio); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-actor-actions">
                                <a href="<?php echo esc_url(home_url('/mapa-actores/actor/' . $actor->id . '/')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                                    <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de actor
     */
    public function shortcode_detalle($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $actor_id = intval($atts['id']);
        if (!$actor_id && isset($_GET['actor_id'])) {
            $actor_id = intval($_GET['actor_id']);
        }

        if (!$actor_id) {
            return '<p class="flavor-notice">' . esc_html__('Actor no especificado.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $actor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_actores} WHERE id = %d AND activo = 1",
            $actor_id
        ));

        if (!$actor) {
            return '<p class="flavor-notice">' . esc_html__('Actor no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];

        // Obtener relaciones
        $relaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.nombre as actor_nombre, a.tipo as actor_tipo
             FROM {$this->tabla_relaciones} r
             INNER JOIN {$this->tabla_actores} a ON (
                 (r.actor_origen_id = %d AND a.id = r.actor_destino_id) OR
                 (r.actor_destino_id = %d AND a.id = r.actor_origen_id)
             )
             WHERE r.actor_origen_id = %d OR r.actor_destino_id = %d
             LIMIT 10",
            $actor_id, $actor_id, $actor_id, $actor_id
        ));

        ob_start();
        ?>
        <div class="flavor-actor-detalle">
            <div class="flavor-actor-detalle-header" style="border-left-color:<?php echo esc_attr($tipo_info['color']); ?>">
                <span class="flavor-actor-icono-xl" style="background-color:<?php echo esc_attr($tipo_info['color']); ?>">
                    <?php echo esc_html($tipo_info['icono']); ?>
                </span>
                <div>
                    <h1><?php echo esc_html($actor->nombre); ?></h1>
                    <div class="flavor-actor-badges">
                        <span class="flavor-badge" style="background-color:<?php echo esc_attr($tipo_info['color']); ?>"><?php echo esc_html($tipo_info['nombre']); ?></span>
                        <?php if ($actor->ambito): ?>
                            <span class="flavor-badge flavor-badge-outline"><?php echo esc_html(ucfirst($actor->ambito)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($actor->descripcion)): ?>
                <div class="flavor-actor-seccion">
                    <h3><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo nl2br(esc_html($actor->descripcion)); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-actor-datos">
                <?php if ($actor->direccion || $actor->municipio): ?>
                    <div class="flavor-dato">
                        <span class="dashicons dashicons-location"></span>
                        <div>
                            <strong><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></strong>
                            <span><?php echo esc_html(trim($actor->direccion . ', ' . $actor->municipio . ' ' . $actor->codigo_postal, ', ')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($actor->telefono): ?>
                    <div class="flavor-dato">
                        <span class="dashicons dashicons-phone"></span>
                        <div>
                            <strong><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></strong>
                            <a href="tel:<?php echo esc_attr($actor->telefono); ?>"><?php echo esc_html($actor->telefono); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($actor->email): ?>
                    <div class="flavor-dato">
                        <span class="dashicons dashicons-email"></span>
                        <div>
                            <strong><?php esc_html_e('Email', 'flavor-chat-ia'); ?></strong>
                            <a href="mailto:<?php echo esc_attr($actor->email); ?>"><?php echo esc_html($actor->email); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($actor->web): ?>
                    <div class="flavor-dato">
                        <span class="dashicons dashicons-admin-site"></span>
                        <div>
                            <strong><?php esc_html_e('Web', 'flavor-chat-ia'); ?></strong>
                            <a href="<?php echo esc_url($actor->web); ?>" target="_blank"><?php echo esc_html($actor->web); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($relaciones)): ?>
                <div class="flavor-actor-seccion">
                    <h3><?php esc_html_e('Relaciones', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-relaciones-lista">
                        <?php foreach ($relaciones as $rel):
                            $rel_tipo_info = $this->tipos_actor[$rel->actor_tipo] ?? $this->tipos_actor['otro'];
                        ?>
                            <div class="flavor-relacion-item">
                                <span class="flavor-relacion-tipo"><?php echo esc_html(ucfirst(str_replace('_', ' ', $rel->tipo_relacion))); ?></span>
                                <span class="flavor-relacion-actor">
                                    <?php echo esc_html($rel_tipo_info['icono']); ?>
                                    <?php echo esc_html($rel->actor_nombre); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Buscador
     */
    public function shortcode_buscador($atts) {
        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-mapa-actores-buscador">
            <form id="form-buscar-actores" class="flavor-form-inline">
                <div class="flavor-search-box flavor-search-box-lg">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar actores por nombre, descripción...', 'flavor-chat-ia'); ?>" class="flavor-input">
                </div>
                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></button>
            </form>
            <div id="resultados-actores" class="flavor-actores-resultados"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Grafo de relaciones
     */
    public function shortcode_grafo($atts) {
        $this->enqueue_assets(true);

        ob_start();
        ?>
        <div class="flavor-mapa-actores-grafo">
            <div id="grafo-relaciones" style="height: 600px; background: #f8fafc; border-radius: 12px;"></div>
            <div class="flavor-grafo-controles">
                <button type="button" class="flavor-btn flavor-btn-sm" id="btn-zoom-in">+</button>
                <button type="button" class="flavor-btn flavor-btn-sm" id="btn-zoom-out">-</button>
                <button type="button" class="flavor-btn flavor-btn-sm" id="btn-reset">↻</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Proponer actor
     */
    public function shortcode_proponer($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para proponer actores.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-proponer-actor">
            <h2><?php esc_html_e('Proponer Nuevo Actor', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-form-intro"><?php esc_html_e('Ayuda a completar el mapa de actores de tu territorio.', 'flavor-chat-ia'); ?></p>

            <form id="form-proponer-actor" class="flavor-form">
                <?php wp_nonce_field('flavor_mapa_actores_nonce', 'mapa_actores_nonce'); ?>

                <div class="flavor-form-row">
                    <label for="nombre"><?php esc_html_e('Nombre del actor *', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="nombre" id="nombre" class="flavor-input" required>
                </div>

                <div class="flavor-form-grid flavor-form-grid-2">
                    <div class="flavor-form-row">
                        <label for="tipo"><?php esc_html_e('Tipo *', 'flavor-chat-ia'); ?></label>
                        <select name="tipo" id="tipo" class="flavor-select" required>
                            <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($this->tipos_actor as $key => $tipo): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($tipo['icono'] . ' ' . $tipo['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-row">
                        <label for="ambito"><?php esc_html_e('Ámbito', 'flavor-chat-ia'); ?></label>
                        <select name="ambito" id="ambito" class="flavor-select">
                            <option value="local"><?php esc_html_e('Local', 'flavor-chat-ia'); ?></option>
                            <option value="comarcal"><?php esc_html_e('Comarcal', 'flavor-chat-ia'); ?></option>
                            <option value="provincial"><?php esc_html_e('Provincial', 'flavor-chat-ia'); ?></option>
                            <option value="autonomico"><?php esc_html_e('Autonómico', 'flavor-chat-ia'); ?></option>
                            <option value="estatal"><?php esc_html_e('Estatal', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea name="descripcion" id="descripcion" class="flavor-textarea" rows="3"></textarea>
                </div>

                <div class="flavor-form-grid flavor-form-grid-2">
                    <div class="flavor-form-row">
                        <label for="municipio"><?php esc_html_e('Municipio', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="municipio" id="municipio" class="flavor-input">
                    </div>
                    <div class="flavor-form-row">
                        <label for="web"><?php esc_html_e('Web', 'flavor-chat-ia'); ?></label>
                        <input type="url" name="web" id="web" class="flavor-input" placeholder="https://">
                    </div>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Proponer Actor', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard
     */
    public function shortcode_dashboard($atts) {
        $this->enqueue_assets();
        ob_start();
        $this->render_tab_mapa_actores();
        return ob_get_clean();
    }

    /**
     * AJAX: Buscar actores
     */
    public function ajax_buscar() {
        check_ajax_referer('flavor_mapa_actores_nonce', 'nonce');

        global $wpdb;
        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $ambito = sanitize_key($_POST['ambito'] ?? '');

        $where = "WHERE activo = 1";
        $params = [];

        if (!empty($busqueda)) {
            $where .= " AND (nombre LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($tipo)) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }

        if (!empty($ambito)) {
            $where .= " AND ambito = %s";
            $params[] = $ambito;
        }

        $query = "SELECT id, nombre, tipo, ambito, municipio, descripcion FROM {$this->tabla_actores} {$where} ORDER BY nombre ASC LIMIT 50";
        $actores = $wpdb->get_results($wpdb->prepare($query, ...$params));

        $resultados = [];
        foreach ($actores as $actor) {
            $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];
            $resultados[] = [
                'id' => $actor->id,
                'nombre' => $actor->nombre,
                'tipo' => $actor->tipo,
                'tipo_nombre' => $tipo_info['nombre'],
                'icono' => $tipo_info['icono'],
                'color' => $tipo_info['color'],
                'ambito' => $actor->ambito,
                'municipio' => $actor->municipio,
                'descripcion' => wp_trim_words($actor->descripcion, 15),
            ];
        }

        wp_send_json_success(['actores' => $resultados]);
    }

    /**
     * AJAX: Proponer actor
     */
    public function ajax_proponer() {
        check_ajax_referer('flavor_mapa_actores_nonce', 'mapa_actores_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $tipo = sanitize_key($_POST['tipo'] ?? '');

        if (empty($nombre) || empty($tipo)) {
            wp_send_json_error(['message' => __('Nombre y tipo son obligatorios', 'flavor-chat-ia')]);
        }

        if (!isset($this->tipos_actor[$tipo])) {
            wp_send_json_error(['message' => __('Tipo no válido', 'flavor-chat-ia')]);
        }

        global $wpdb;

        $wpdb->insert($this->tabla_actores, [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'ambito' => sanitize_key($_POST['ambito'] ?? 'local'),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'municipio' => sanitize_text_field($_POST['municipio'] ?? ''),
            'web' => esc_url_raw($_POST['web'] ?? ''),
            'verificado' => 0,
            'activo' => 1,
            'creador_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'message' => __('Actor propuesto correctamente. Será revisado pronto.', 'flavor-chat-ia'),
            'actor_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Detalle de actor
     */
    public function ajax_detalle() {
        check_ajax_referer('flavor_mapa_actores_nonce', 'nonce');

        $actor_id = intval($_POST['actor_id'] ?? 0);
        if (!$actor_id) {
            wp_send_json_error(['message' => __('Actor no especificado', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $actor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_actores} WHERE id = %d AND activo = 1",
            $actor_id
        ));

        if (!$actor) {
            wp_send_json_error(['message' => __('Actor no encontrado', 'flavor-chat-ia')]);
        }

        $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];

        wp_send_json_success([
            'actor' => [
                'id' => $actor->id,
                'nombre' => $actor->nombre,
                'tipo' => $actor->tipo,
                'tipo_nombre' => $tipo_info['nombre'],
                'icono' => $tipo_info['icono'],
                'color' => $tipo_info['color'],
                'ambito' => $actor->ambito,
                'descripcion' => $actor->descripcion,
                'direccion' => $actor->direccion,
                'municipio' => $actor->municipio,
                'telefono' => $actor->telefono,
                'email' => $actor->email,
                'web' => $actor->web,
            ],
        ]);
    }

    /**
     * AJAX: Obtener relaciones para el grafo
     */
    public function ajax_relaciones() {
        check_ajax_referer('flavor_mapa_actores_nonce', 'nonce');

        global $wpdb;

        // Obtener actores
        $actores = $wpdb->get_results(
            "SELECT id, nombre, tipo FROM {$this->tabla_actores} WHERE activo = 1 LIMIT 100"
        );

        $nodes = [];
        foreach ($actores as $actor) {
            $tipo_info = $this->tipos_actor[$actor->tipo] ?? $this->tipos_actor['otro'];
            $nodes[] = [
                'id' => $actor->id,
                'name' => $actor->nombre,
                'group' => $actor->tipo,
                'color' => $tipo_info['color'],
            ];
        }

        // Obtener relaciones
        $relaciones = $wpdb->get_results(
            "SELECT actor_origen_id, actor_destino_id, tipo_relacion, intensidad
             FROM {$this->tabla_relaciones} LIMIT 200"
        );

        $links = [];
        foreach ($relaciones as $rel) {
            $links[] = [
                'source' => (int) $rel->actor_origen_id,
                'target' => (int) $rel->actor_destino_id,
                'type' => $rel->tipo_relacion,
                'value' => $rel->intensidad === 'fuerte' ? 3 : ($rel->intensidad === 'moderada' ? 2 : 1),
            ];
        }

        wp_send_json_success(['nodes' => $nodes, 'links' => $links]);
    }
}

// Inicializar
Flavor_Mapa_Actores_Frontend_Controller::get_instance();

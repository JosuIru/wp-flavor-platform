<?php
/**
 * Frontend Controller para Biodiversidad Local
 *
 * Controlador frontend con shortcodes, AJAX handlers y tabs para el dashboard
 * Sistema de ciencia ciudadana para catalogar especies locales
 *
 * @package FlavorChatIA
 * @subpackage Modules\BiodiversidadLocal
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Biodiversidad_Local_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Biodiversidad_Local_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_especies;
    private $tabla_avistamientos;
    private $tabla_proyectos;
    private $tabla_participantes;

    /**
     * Categorias de especies
     */
    private $categorias = [
        'flora' => ['nombre' => 'Flora', 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
        'fauna_vertebrados' => ['nombre' => 'Fauna Vertebrada', 'icono' => 'dashicons-pets', 'color' => '#f97316'],
        'fauna_invertebrados' => ['nombre' => 'Invertebrados', 'icono' => 'dashicons-admin-site-alt', 'color' => '#a855f7'],
    ];

    /**
     * Estados de conservacion IUCN
     */
    private $estados_conservacion = [
        'no_evaluada' => ['nombre' => 'No Evaluada', 'color' => '#6b7280', 'sigla' => 'NE'],
        'preocupacion_menor' => ['nombre' => 'Preocupación Menor', 'color' => '#22c55e', 'sigla' => 'LC'],
        'casi_amenazada' => ['nombre' => 'Casi Amenazada', 'color' => '#84cc16', 'sigla' => 'NT'],
        'vulnerable' => ['nombre' => 'Vulnerable', 'color' => '#eab308', 'sigla' => 'VU'],
        'en_peligro' => ['nombre' => 'En Peligro', 'color' => '#f97316', 'sigla' => 'EN'],
        'en_peligro_critico' => ['nombre' => 'En Peligro Crítico', 'color' => '#ef4444', 'sigla' => 'CR'],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_especies = $wpdb->prefix . 'flavor_biodiversidad_especies';
        $this->tabla_avistamientos = $wpdb->prefix . 'flavor_biodiversidad_avistamientos';
        $this->tabla_proyectos = $wpdb->prefix . 'flavor_biodiversidad_proyectos';
        $this->tabla_participantes = $wpdb->prefix . 'flavor_biodiversidad_participantes';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Biodiversidad_Local_Frontend_Controller
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
    public function init() {
        // Shortcodes
        add_shortcode('flavor_biodiversidad_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('flavor_biodiversidad_especie', [$this, 'shortcode_especie']);
        add_shortcode('flavor_biodiversidad_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('flavor_biodiversidad_reportar', [$this, 'shortcode_reportar']);
        add_shortcode('flavor_biodiversidad_mis_avistamientos', [$this, 'shortcode_mis_avistamientos']);
        add_shortcode('flavor_biodiversidad_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('flavor_biodiversidad_proyecto', [$this, 'shortcode_proyecto']);
        add_shortcode('flavor_biodiversidad_estadisticas', [$this, 'shortcode_estadisticas']);

        // AJAX handlers
        add_action('wp_ajax_flavor_biodiversidad_reportar', [$this, 'ajax_reportar_avistamiento']);
        add_action('wp_ajax_flavor_biodiversidad_validar', [$this, 'ajax_validar_avistamiento']);
        add_action('wp_ajax_flavor_biodiversidad_buscar_especies', [$this, 'ajax_buscar_especies']);
        add_action('wp_ajax_nopriv_flavor_biodiversidad_buscar_especies', [$this, 'ajax_buscar_especies']);
        add_action('wp_ajax_flavor_biodiversidad_unirse_proyecto', [$this, 'ajax_unirse_proyecto']);
        add_action('wp_ajax_flavor_biodiversidad_obtener_avistamientos', [$this, 'ajax_obtener_avistamientos']);
        add_action('wp_ajax_nopriv_flavor_biodiversidad_obtener_avistamientos', [$this, 'ajax_obtener_avistamientos']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs del dashboard
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['biodiversidad'] = [
            'id' => 'biodiversidad',
            'label' => __('Biodiversidad', 'flavor-chat-ia'),
            'icon' => 'dashicons-admin-site-alt3',
            'orden' => 45,
            'callback' => [$this, 'render_dashboard_tab'],
        ];

        $tabs['biodiversidad-avistamientos'] = [
            'id' => 'biodiversidad-avistamientos',
            'label' => __('Mis Avistamientos', 'flavor-chat-ia'),
            'icon' => 'dashicons-visibility',
            'orden' => 46,
            'parent' => 'biodiversidad',
            'callback' => [$this, 'render_dashboard_avistamientos'],
        ];

        return $tabs;
    }

    /**
     * Registra assets frontend
     */
    public function registrar_assets() {
        wp_register_style(
            'flavor-biodiversidad-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-biodiversidad-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/js/biodiversidad-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-biodiversidad-frontend', 'flavorBiodiversidadConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_biodiversidad_nonce'),
            'strings' => [
                'confirmar' => __('¿Confirmar esta acción?', 'flavor-chat-ia'),
                'enviando' => __('Enviando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar', 'flavor-chat-ia'),
                'exito' => __('Guardado correctamente', 'flavor-chat-ia'),
                'ubicacionRequerida' => __('Se necesita la ubicación para registrar el avistamiento', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola assets cuando se necesitan
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-biodiversidad-frontend');
        wp_enqueue_script('flavor-biodiversidad-frontend');
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Shortcode: Catalogo de especies
     */
    public function shortcode_catalogo($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'por_pagina' => 12,
        ], $atts);

        $this->enqueue_assets();

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_especies)) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('El catálogo de biodiversidad no está configurado.', 'flavor-chat-ia') . '</div>';
        }

        global $wpdb;

        $filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : $atts['categoria'];
        $filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $busqueda = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $where = "1=1";
        if (!empty($filtro_categoria)) {
            $where .= $wpdb->prepare(" AND categoria = %s", $filtro_categoria);
        }
        if (!empty($filtro_estado)) {
            $where .= $wpdb->prepare(" AND estado_conservacion = %s", $filtro_estado);
        }
        if (!empty($busqueda)) {
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $where .= $wpdb->prepare(" AND (nombre_comun LIKE %s OR nombre_cientifico LIKE %s)", $like, $like);
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['por_pagina'];

        $especies = $wpdb->get_results("
            SELECT e.*,
                   (SELECT COUNT(*) FROM {$this->tabla_avistamientos} WHERE especie_id = e.id AND estado = 'validado') as total_avistamientos
            FROM {$this->tabla_especies} e
            WHERE {$where}
            ORDER BY e.nombre_comun ASC
            LIMIT {$atts['por_pagina']} OFFSET {$offset}
        ");

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_especies} WHERE {$where}");

        // Estadísticas generales
        $stats = [
            'total_especies' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_especies}"),
            'total_avistamientos' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_avistamientos} WHERE estado = 'validado'"),
            'observadores' => $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$this->tabla_avistamientos}"),
        ];

        ob_start();
        ?>
        <div class="flavor-biodiversidad-catalogo">
            <div class="flavor-biodiversidad-header">
                <h2><?php _e('Catálogo de Biodiversidad Local', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-biodiversidad-stats-mini">
                    <span><strong><?php echo number_format($stats['total_especies']); ?></strong> <?php _e('especies', 'flavor-chat-ia'); ?></span>
                    <span><strong><?php echo number_format($stats['total_avistamientos']); ?></strong> <?php _e('avistamientos', 'flavor-chat-ia'); ?></span>
                    <span><strong><?php echo number_format($stats['observadores']); ?></strong> <?php _e('observadores', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="flavor-biodiversidad-filtros">
                <form method="get" class="flavor-filtros-form">
                    <div class="flavor-filtro-grupo">
                        <input type="text" name="q" value="<?php echo esc_attr($busqueda); ?>"
                               placeholder="<?php esc_attr_e('Buscar especie...', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="flavor-filtro-grupo">
                        <select name="categoria">
                            <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($this->categorias as $clave => $cat): ?>
                                <option value="<?php echo esc_attr($clave); ?>" <?php selected($filtro_categoria, $clave); ?>>
                                    <?php echo esc_html($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-filtro-grupo">
                        <select name="estado">
                            <option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($this->estados_conservacion as $clave => $estado): ?>
                                <option value="<?php echo esc_attr($clave); ?>" <?php selected($filtro_estado, $clave); ?>>
                                    <?php echo esc_html($estado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </form>
            </div>

            <?php if (empty($especies)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No se encontraron especies con los filtros seleccionados.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-especies-grid">
                    <?php foreach ($especies as $especie): ?>
                        <?php $this->render_card_especie($especie); ?>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total, $atts['por_pagina'], $pagina); ?>
            <?php endif; ?>

            <?php if (is_user_logged_in()): ?>
                <div class="flavor-biodiversidad-cta">
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad-local', 'reportar')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Reportar Avistamiento', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de especie
     */
    private function render_card_especie($especie) {
        $categoria_info = $this->categorias[$especie->categoria] ?? ['nombre' => $especie->categoria, 'color' => '#6b7280'];
        $estado_info = $this->estados_conservacion[$especie->estado_conservacion] ?? ['nombre' => 'No evaluada', 'color' => '#6b7280', 'sigla' => 'NE'];
        ?>
        <div class="flavor-especie-card">
            <div class="flavor-especie-imagen">
                <?php if (!empty($especie->imagen)): ?>
                    <img src="<?php echo esc_url($especie->imagen); ?>" alt="<?php echo esc_attr($especie->nombre_comun); ?>">
                <?php else: ?>
                    <div class="flavor-especie-placeholder" style="background-color: <?php echo esc_attr($categoria_info['color']); ?>20;">
                        <span class="dashicons <?php echo esc_attr($categoria_info['icono'] ?? 'dashicons-admin-site-alt'); ?>"
                              style="color: <?php echo esc_attr($categoria_info['color']); ?>"></span>
                    </div>
                <?php endif; ?>
                <span class="flavor-badge flavor-especie-estado" style="background-color: <?php echo esc_attr($estado_info['color']); ?>;">
                    <?php echo esc_html($estado_info['sigla']); ?>
                </span>
            </div>
            <div class="flavor-especie-contenido">
                <h3 class="flavor-especie-nombre">
                    <a href="<?php echo esc_url(add_query_arg('especie_id', $especie->id)); ?>">
                        <?php echo esc_html($especie->nombre_comun); ?>
                    </a>
                </h3>
                <p class="flavor-especie-cientifico"><em><?php echo esc_html($especie->nombre_cientifico); ?></em></p>
                <div class="flavor-especie-meta">
                    <span class="flavor-categoria" style="color: <?php echo esc_attr($categoria_info['color']); ?>;">
                        <span class="dashicons <?php echo esc_attr($categoria_info['icono'] ?? 'dashicons-tag'); ?>"></span>
                        <?php echo esc_html($categoria_info['nombre']); ?>
                    </span>
                    <span class="flavor-avistamientos">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo absint($especie->total_avistamientos); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de especie
     */
    public function shortcode_especie($atts) {
        $atts = shortcode_atts([
            'especie_id' => 0,
        ], $atts);

        $especie_id = absint($atts['especie_id'] ?: (isset($_GET['especie_id']) ? $_GET['especie_id'] : 0));
        if (!$especie_id) {
            return $this->shortcode_catalogo([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $especie = $wpdb->get_row($wpdb->prepare("
            SELECT e.*,
                   (SELECT COUNT(*) FROM {$this->tabla_avistamientos} WHERE especie_id = e.id AND estado = 'validado') as total_avistamientos,
                   (SELECT MAX(fecha) FROM {$this->tabla_avistamientos} WHERE especie_id = e.id AND estado = 'validado') as ultimo_avistamiento
            FROM {$this->tabla_especies} e
            WHERE e.id = %d
        ", $especie_id));

        if (!$especie) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Especie no encontrada.', 'flavor-chat-ia') . '</div>';
        }

        $categoria_info = $this->categorias[$especie->categoria] ?? ['nombre' => $especie->categoria, 'color' => '#6b7280'];
        $estado_info = $this->estados_conservacion[$especie->estado_conservacion] ?? ['nombre' => 'No evaluada', 'color' => '#6b7280'];

        // Avistamientos recientes
        $avistamientos = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, u.display_name as observador
            FROM {$this->tabla_avistamientos} a
            LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
            WHERE a.especie_id = %d AND a.estado = 'validado'
            ORDER BY a.fecha DESC
            LIMIT 10
        ", $especie_id));

        ob_start();
        ?>
        <div class="flavor-especie-detalle">
            <div class="flavor-biodiversidad-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('especie_id')); ?>">
                    <?php _e('Catálogo', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html($especie->nombre_comun); ?></span>
            </div>

            <div class="flavor-especie-header-detalle">
                <div class="flavor-especie-imagen-grande">
                    <?php if (!empty($especie->imagen)): ?>
                        <img src="<?php echo esc_url($especie->imagen); ?>" alt="<?php echo esc_attr($especie->nombre_comun); ?>">
                    <?php else: ?>
                        <div class="flavor-especie-placeholder-grande" style="background-color: <?php echo esc_attr($categoria_info['color']); ?>20;">
                            <span class="dashicons <?php echo esc_attr($categoria_info['icono'] ?? 'dashicons-admin-site-alt'); ?>"
                                  style="color: <?php echo esc_attr($categoria_info['color']); ?>; font-size: 80px; width: 80px; height: 80px;"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-especie-info-principal">
                    <div class="flavor-especie-badges">
                        <span class="flavor-badge" style="background-color: <?php echo esc_attr($categoria_info['color']); ?>;">
                            <?php echo esc_html($categoria_info['nombre']); ?>
                        </span>
                        <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>;"
                              title="<?php echo esc_attr($estado_info['nombre']); ?>">
                            <?php echo esc_html($this->estados_conservacion[$especie->estado_conservacion]['sigla'] ?? 'NE'); ?>
                            - <?php echo esc_html($estado_info['nombre']); ?>
                        </span>
                    </div>

                    <h1><?php echo esc_html($especie->nombre_comun); ?></h1>
                    <p class="flavor-nombre-cientifico"><em><?php echo esc_html($especie->nombre_cientifico); ?></em></p>

                    <?php if (!empty($especie->familia)): ?>
                        <p class="flavor-taxonomia">
                            <strong><?php _e('Familia:', 'flavor-chat-ia'); ?></strong>
                            <?php echo esc_html($especie->familia); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flavor-especie-stats-detalle">
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-valor"><?php echo absint($especie->total_avistamientos); ?></span>
                            <span class="flavor-stat-label"><?php _e('avistamientos', 'flavor-chat-ia'); ?></span>
                        </div>
                        <?php if ($especie->ultimo_avistamiento): ?>
                            <div class="flavor-stat-item">
                                <span class="flavor-stat-valor"><?php echo human_time_diff(strtotime($especie->ultimo_avistamiento)); ?></span>
                                <span class="flavor-stat-label"><?php _e('último registro', 'flavor-chat-ia'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad-local', 'reportar', ['especie_id' => $especie_id])); ?>"
                           class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Reportar avistamiento', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-especie-contenido-detalle">
                <div class="flavor-especie-main">
                    <?php if (!empty($especie->descripcion)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Descripción', 'flavor-chat-ia'); ?></h2>
                            <?php echo wp_kses_post(wpautop($especie->descripcion)); ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($especie->habitat)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Hábitat', 'flavor-chat-ia'); ?></h2>
                            <?php echo wp_kses_post(wpautop($especie->habitat)); ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($especie->comportamiento)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Comportamiento', 'flavor-chat-ia'); ?></h2>
                            <?php echo wp_kses_post(wpautop($especie->comportamiento)); ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($avistamientos)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Avistamientos Recientes', 'flavor-chat-ia'); ?></h2>
                            <div class="flavor-avistamientos-lista">
                                <?php foreach ($avistamientos as $avistamiento): ?>
                                    <div class="flavor-avistamiento-item">
                                        <div class="flavor-avistamiento-fecha">
                                            <span class="flavor-dia"><?php echo date('d', strtotime($avistamiento->fecha)); ?></span>
                                            <span class="flavor-mes"><?php echo date_i18n('M', strtotime($avistamiento->fecha)); ?></span>
                                        </div>
                                        <div class="flavor-avistamiento-info">
                                            <p class="flavor-avistamiento-ubicacion">
                                                <span class="dashicons dashicons-location"></span>
                                                <?php echo esc_html($avistamiento->ubicacion_nombre ?: __('Ubicación no especificada', 'flavor-chat-ia')); ?>
                                            </p>
                                            <?php if (!empty($avistamiento->notas)): ?>
                                                <p class="flavor-avistamiento-notas"><?php echo esc_html(wp_trim_words($avistamiento->notas, 20)); ?></p>
                                            <?php endif; ?>
                                            <p class="flavor-avistamiento-observador">
                                                <?php echo get_avatar($avistamiento->usuario_id, 20); ?>
                                                <?php echo esc_html($avistamiento->observador); ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($avistamiento->imagen)): ?>
                                            <div class="flavor-avistamiento-imagen-mini">
                                                <img src="<?php echo esc_url($avistamiento->imagen); ?>" alt="">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="flavor-especie-sidebar">
                    <?php if (!empty($especie->temporada)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Temporada', 'flavor-chat-ia'); ?></h3>
                            <p><?php echo esc_html($especie->temporada); ?></p>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($especie->amenazas)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Amenazas', 'flavor-chat-ia'); ?></h3>
                            <?php echo wp_kses_post(wpautop($especie->amenazas)); ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($especie->curiosidades)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Curiosidades', 'flavor-chat-ia'); ?></h3>
                            <?php echo wp_kses_post(wpautop($especie->curiosidades)); ?>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de avistamientos
     */
    public function shortcode_mapa($atts) {
        $atts = shortcode_atts([
            'especie_id' => 0,
            'altura' => '500px',
        ], $atts);

        $this->enqueue_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        $especie_id = absint($atts['especie_id']);

        global $wpdb;
        $where = "a.estado = 'validado' AND a.latitud IS NOT NULL AND a.longitud IS NOT NULL";
        if ($especie_id) {
            $where .= $wpdb->prepare(" AND a.especie_id = %d", $especie_id);
        }

        $avistamientos = $wpdb->get_results("
            SELECT a.*, e.nombre_comun, e.nombre_cientifico, e.categoria
            FROM {$this->tabla_avistamientos} a
            LEFT JOIN {$this->tabla_especies} e ON a.especie_id = e.id
            WHERE {$where}
            ORDER BY a.fecha DESC
            LIMIT 500
        ");

        $mapa_id = 'flavor-mapa-biodiversidad-' . uniqid();

        ob_start();
        ?>
        <div class="flavor-biodiversidad-mapa-container">
            <div id="<?php echo esc_attr($mapa_id); ?>" style="height: <?php echo esc_attr($atts['altura']); ?>;"></div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') return;

            var mapa = L.map('<?php echo esc_js($mapa_id); ?>').setView([40.4168, -3.7038], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            var avistamientos = <?php echo json_encode($avistamientos); ?>;
            var colores = <?php echo json_encode(array_map(function($c) { return $c['color']; }, $this->categorias)); ?>;

            avistamientos.forEach(function(a) {
                if (a.latitud && a.longitud) {
                    var color = colores[a.categoria] || '#6b7280';
                    var icono = L.divIcon({
                        className: 'flavor-marker-biodiversidad',
                        html: '<div style="background-color:' + color + ';width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>'
                    });

                    L.marker([parseFloat(a.latitud), parseFloat(a.longitud)], {icon: icono})
                        .bindPopup(
                            '<strong>' + a.nombre_comun + '</strong><br>' +
                            '<em>' + a.nombre_cientifico + '</em><br>' +
                            '<small>' + a.fecha + '</small>'
                        )
                        .addTo(mapa);
                }
            });

            if (avistamientos.length > 0) {
                var bounds = avistamientos
                    .filter(function(a) { return a.latitud && a.longitud; })
                    .map(function(a) { return [parseFloat(a.latitud), parseFloat(a.longitud)]; });
                if (bounds.length > 0) {
                    mapa.fitBounds(bounds, {padding: [50, 50]});
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Reportar avistamiento
     */
    public function shortcode_reportar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   sprintf(__('<a href="%s">Inicia sesión</a> para reportar un avistamiento.', 'flavor-chat-ia'), wp_login_url(flavor_current_request_url())) .
                   '</div>';
        }

        $this->enqueue_assets();

        $especie_id = isset($_GET['especie_id']) ? absint($_GET['especie_id']) : 0;
        $especie_preseleccionada = null;

        if ($especie_id) {
            global $wpdb;
            $especie_preseleccionada = $wpdb->get_row($wpdb->prepare(
                "SELECT id, nombre_comun, nombre_cientifico FROM {$this->tabla_especies} WHERE id = %d",
                $especie_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-reportar-avistamiento">
            <h2><?php _e('Reportar Avistamiento', 'flavor-chat-ia'); ?></h2>

            <form id="flavor-form-avistamiento" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_biodiversidad_nonce', 'nonce'); ?>

                <div class="flavor-form-group">
                    <label for="especie_buscar"><?php _e('Especie observada', 'flavor-chat-ia'); ?> *</label>
                    <div class="flavor-autocomplete-container">
                        <input type="text" id="especie_buscar"
                               placeholder="<?php esc_attr_e('Escribe el nombre de la especie...', 'flavor-chat-ia'); ?>"
                               value="<?php echo $especie_preseleccionada ? esc_attr($especie_preseleccionada->nombre_comun) : ''; ?>"
                               autocomplete="off">
                        <input type="hidden" name="especie_id" id="especie_id"
                               value="<?php echo esc_attr($especie_id); ?>" required>
                        <div id="especie_sugerencias" class="flavor-autocomplete-resultados"></div>
                    </div>
                    <small><?php _e('Si no encuentras la especie, puedes solicitar que se añada.', 'flavor-chat-ia'); ?></small>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="fecha"><?php _e('Fecha del avistamiento', 'flavor-chat-ia'); ?> *</label>
                        <input type="date" name="fecha" id="fecha" required
                               value="<?php echo date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="hora"><?php _e('Hora aproximada', 'flavor-chat-ia'); ?></label>
                        <input type="time" name="hora" id="hora">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="cantidad"><?php _e('Cantidad de individuos', 'flavor-chat-ia'); ?></label>
                    <input type="number" name="cantidad" id="cantidad" min="1" value="1">
                </div>

                <div class="flavor-form-group">
                    <label><?php _e('Ubicación', 'flavor-chat-ia'); ?> *</label>
                    <div class="flavor-ubicacion-container">
                        <button type="button" id="btn-obtener-ubicacion" class="flavor-btn flavor-btn-outline">
                            <span class="dashicons dashicons-location"></span>
                            <?php _e('Usar mi ubicación actual', 'flavor-chat-ia'); ?>
                        </button>
                        <span id="ubicacion-status"></span>
                    </div>
                    <input type="hidden" name="latitud" id="latitud" required>
                    <input type="hidden" name="longitud" id="longitud" required>
                    <div id="mini-mapa-ubicacion" style="height: 200px; margin-top: 10px; display: none;"></div>
                </div>

                <div class="flavor-form-group">
                    <label for="ubicacion_nombre"><?php _e('Descripción del lugar', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="ubicacion_nombre" id="ubicacion_nombre"
                           placeholder="<?php esc_attr_e('Ej: Parque del Retiro, cerca del estanque', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="notas"><?php _e('Notas adicionales', 'flavor-chat-ia'); ?></label>
                    <textarea name="notas" id="notas" rows="4"
                              placeholder="<?php esc_attr_e('Comportamiento observado, condiciones climáticas, etc.', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="imagen"><?php _e('Fotografía (opcional pero recomendada)', 'flavor-chat-ia'); ?></label>
                    <input type="file" name="imagen" id="imagen" accept="image/*">
                    <small><?php _e('Una foto ayuda a validar el avistamiento.', 'flavor-chat-ia'); ?></small>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Enviar Avistamiento', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_module_url('biodiversidad-local')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis avistamientos
     */
    public function shortcode_mis_avistamientos($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $avistamientos = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, e.nombre_comun, e.nombre_cientifico, e.categoria
            FROM {$this->tabla_avistamientos} a
            LEFT JOIN {$this->tabla_especies} e ON a.especie_id = e.id
            WHERE a.usuario_id = %d
            ORDER BY a.fecha DESC
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-avistamientos">
            <h2><?php _e('Mis Avistamientos', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($avistamientos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No has registrado ningún avistamiento todavía.', 'flavor-chat-ia'); ?>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad-local', 'reportar')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Reportar tu primer avistamiento', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-tabla-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php _e('Especie', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Ubicación', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avistamientos as $avistamiento): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg('especie_id', $avistamiento->especie_id)); ?>">
                                            <?php echo esc_html($avistamiento->nombre_comun); ?>
                                        </a>
                                        <br><small><em><?php echo esc_html($avistamiento->nombre_cientifico); ?></em></small>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($avistamiento->fecha)); ?></td>
                                    <td><?php echo esc_html($avistamiento->ubicacion_nombre ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        $estados_badge = [
                                            'pendiente' => 'warning',
                                            'validado' => 'success',
                                            'rechazado' => 'danger',
                                        ];
                                        $badge_class = $estados_badge[$avistamiento->estado] ?? 'secondary';
                                        ?>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html(ucfirst($avistamiento->estado)); ?>
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
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos de conservacion
     */
    public function shortcode_proyectos($atts) {
        $this->enqueue_assets();

        global $wpdb;
        $proyectos = $wpdb->get_results("
            SELECT p.*,
                   (SELECT COUNT(*) FROM {$this->tabla_participantes} WHERE proyecto_id = p.id AND estado = 'activo') as total_participantes
            FROM {$this->tabla_proyectos} p
            WHERE p.estado = 'activo'
            ORDER BY p.fecha_inicio DESC
        ");

        ob_start();
        ?>
        <div class="flavor-proyectos-biodiversidad">
            <h2><?php _e('Proyectos de Conservación', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($proyectos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay proyectos de conservación activos en este momento.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-proyectos-grid">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <div class="flavor-proyecto-card">
                            <?php if (!empty($proyecto->imagen)): ?>
                                <div class="flavor-proyecto-imagen">
                                    <img src="<?php echo esc_url($proyecto->imagen); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-proyecto-contenido">
                                <span class="flavor-badge"><?php echo esc_html(ucfirst($proyecto->tipo)); ?></span>
                                <h3>
                                    <a href="<?php echo esc_url(add_query_arg('proyecto_id', $proyecto->id)); ?>">
                                        <?php echo esc_html($proyecto->nombre); ?>
                                    </a>
                                </h3>
                                <p><?php echo esc_html(wp_trim_words($proyecto->descripcion, 25)); ?></p>
                                <div class="flavor-proyecto-meta">
                                    <span>
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo absint($proyecto->total_participantes); ?> <?php _e('participantes', 'flavor-chat-ia'); ?>
                                    </span>
                                    <?php if (!empty($proyecto->fecha_inicio)): ?>
                                        <span>
                                            <span class="dashicons dashicons-calendar"></span>
                                            <?php echo date_i18n(get_option('date_format'), strtotime($proyecto->fecha_inicio)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de proyecto
     */
    public function shortcode_proyecto($atts) {
        $atts = shortcode_atts([
            'proyecto_id' => 0,
        ], $atts);

        $proyecto_id = absint($atts['proyecto_id'] ?: (isset($_GET['proyecto_id']) ? $_GET['proyecto_id'] : 0));
        if (!$proyecto_id) {
            return $this->shortcode_proyectos([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $proyecto = $wpdb->get_row($wpdb->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM {$this->tabla_participantes} WHERE proyecto_id = p.id AND estado = 'activo') as total_participantes
            FROM {$this->tabla_proyectos} p
            WHERE p.id = %d
        ", $proyecto_id));

        if (!$proyecto) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Proyecto no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $usuario_actual = get_current_user_id();
        $ya_participa = false;
        if ($usuario_actual) {
            $ya_participa = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_participantes} WHERE proyecto_id = %d AND usuario_id = %d AND estado = 'activo'",
                $proyecto_id, $usuario_actual
            ));
        }

        ob_start();
        ?>
        <div class="flavor-proyecto-detalle">
            <div class="flavor-biodiversidad-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('proyecto_id')); ?>">
                    <?php _e('Proyectos', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html($proyecto->nombre); ?></span>
            </div>

            <header class="flavor-proyecto-header-detalle">
                <span class="flavor-badge flavor-badge-lg"><?php echo esc_html(ucfirst($proyecto->tipo)); ?></span>
                <h1><?php echo esc_html($proyecto->nombre); ?></h1>
                <div class="flavor-proyecto-meta-detalle">
                    <span>
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo absint($proyecto->total_participantes); ?> <?php _e('participantes', 'flavor-chat-ia'); ?>
                    </span>
                    <?php if (!empty($proyecto->ubicacion)): ?>
                        <span>
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($proyecto->ubicacion); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($usuario_actual): ?>
                    <?php if ($ya_participa): ?>
                        <span class="flavor-badge flavor-badge-success">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Ya participas en este proyecto', 'flavor-chat-ia'); ?>
                        </span>
                    <?php else: ?>
                        <button class="flavor-btn flavor-btn-primary flavor-unirse-proyecto"
                                data-proyecto-id="<?php echo esc_attr($proyecto_id); ?>">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Unirse al proyecto', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </header>

            <div class="flavor-panel">
                <h2><?php _e('Descripción', 'flavor-chat-ia'); ?></h2>
                <?php echo wp_kses_post(wpautop($proyecto->descripcion)); ?>
            </div>

            <?php if (!empty($proyecto->objetivos)): ?>
                <div class="flavor-panel">
                    <h2><?php _e('Objetivos', 'flavor-chat-ia'); ?></h2>
                    <?php echo wp_kses_post(wpautop($proyecto->objetivos)); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadisticas
     */
    public function shortcode_estadisticas($atts) {
        $this->enqueue_assets();

        global $wpdb;

        $stats = [
            'total_especies' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_especies}"),
            'total_avistamientos' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_avistamientos} WHERE estado = 'validado'"),
            'observadores' => $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$this->tabla_avistamientos}"),
            'proyectos_activos' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_proyectos} WHERE estado = 'activo'"),
        ];

        // Por categoria
        $por_categoria = $wpdb->get_results("
            SELECT categoria, COUNT(*) as total
            FROM {$this->tabla_especies}
            GROUP BY categoria
        ");

        // Por estado conservacion
        $por_estado = $wpdb->get_results("
            SELECT estado_conservacion, COUNT(*) as total
            FROM {$this->tabla_especies}
            GROUP BY estado_conservacion
        ");

        ob_start();
        ?>
        <div class="flavor-biodiversidad-estadisticas">
            <h2><?php _e('Estadísticas de Biodiversidad', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['total_especies']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Especies catalogadas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-visibility"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['total_avistamientos']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Avistamientos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-groups"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['observadores']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Observadores', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-heart"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['proyectos_activos']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Proyectos activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-stats-charts">
                <div class="flavor-panel">
                    <h3><?php _e('Especies por Categoría', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-stats-bars">
                        <?php foreach ($por_categoria as $cat):
                            $cat_info = $this->categorias[$cat->categoria] ?? ['nombre' => $cat->categoria, 'color' => '#6b7280'];
                            $porcentaje = $stats['total_especies'] > 0 ? ($cat->total / $stats['total_especies']) * 100 : 0;
                            ?>
                            <div class="flavor-stat-bar-item">
                                <div class="flavor-stat-bar-label">
                                    <span class="dashicons <?php echo esc_attr($cat_info['icono'] ?? 'dashicons-tag'); ?>"
                                          style="color: <?php echo esc_attr($cat_info['color']); ?>"></span>
                                    <?php echo esc_html($cat_info['nombre']); ?>
                                </div>
                                <div class="flavor-stat-bar-track">
                                    <div class="flavor-stat-bar-fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background-color: <?php echo esc_attr($cat_info['color']); ?>;"></div>
                                </div>
                                <span class="flavor-stat-bar-valor"><?php echo absint($cat->total); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flavor-panel">
                    <h3><?php _e('Estado de Conservación', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-estados-grid">
                        <?php foreach ($por_estado as $estado):
                            $estado_info = $this->estados_conservacion[$estado->estado_conservacion] ?? ['nombre' => $estado->estado_conservacion, 'color' => '#6b7280', 'sigla' => '?'];
                            ?>
                            <div class="flavor-estado-item" style="border-color: <?php echo esc_attr($estado_info['color']); ?>;">
                                <span class="flavor-estado-sigla" style="background-color: <?php echo esc_attr($estado_info['color']); ?>;">
                                    <?php echo esc_html($estado_info['sigla']); ?>
                                </span>
                                <span class="flavor-estado-nombre"><?php echo esc_html($estado_info['nombre']); ?></span>
                                <span class="flavor-estado-total"><?php echo absint($estado->total); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // DASHBOARD TABS
    // =========================================================

    /**
     * Render del tab principal del dashboard
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;

        $mis_avistamientos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_avistamientos} WHERE usuario_id = %d",
            $usuario_id
        ));

        $especies_observadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT especie_id) FROM {$this->tabla_avistamientos} WHERE usuario_id = %d",
            $usuario_id
        ));

        $proyectos_participando = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_participantes} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        // Ultimos avistamientos
        $ultimos = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, e.nombre_comun
            FROM {$this->tabla_avistamientos} a
            LEFT JOIN {$this->tabla_especies} e ON a.especie_id = e.id
            WHERE a.usuario_id = %d
            ORDER BY a.fecha DESC
            LIMIT 5
        ", $usuario_id));

        ?>
        <div class="flavor-dashboard-biodiversidad">
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-visibility"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($mis_avistamientos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Avistamientos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($especies_observadas); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Especies', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-heart"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($proyectos_participando); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Proyectos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <h3><?php _e('Últimos Avistamientos', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($ultimos)): ?>
                    <p class="flavor-no-datos">
                        <?php _e('No has registrado avistamientos.', 'flavor-chat-ia'); ?>
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad-local', 'reportar')); ?>"><?php _e('Reportar uno', 'flavor-chat-ia'); ?></a>
                    </p>
                <?php else: ?>
                    <ul class="flavor-lista-simple">
                        <?php foreach ($ultimos as $av): ?>
                            <li>
                                <a href="<?php echo esc_url(add_query_arg('especie_id', $av->especie_id)); ?>">
                                    <?php echo esc_html($av->nombre_comun); ?>
                                </a>
                                <span class="flavor-fecha-mini"><?php echo date_i18n('d M', strtotime($av->fecha)); ?></span>
                                <span class="flavor-badge flavor-badge-sm"><?php echo esc_html(ucfirst($av->estado)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="flavor-acciones-rapidas">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('biodiversidad-local', 'reportar')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Reportar Avistamiento', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render del tab "Mis Avistamientos"
     */
    public function render_dashboard_avistamientos() {
        echo $this->shortcode_mis_avistamientos([]);
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Reportar avistamiento
     */
    public function ajax_reportar_avistamiento() {
        check_ajax_referer('flavor_biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $especie_id = absint($_POST['especie_id'] ?? 0);
        $fecha = sanitize_text_field($_POST['fecha'] ?? '');
        $latitud = floatval($_POST['latitud'] ?? 0);
        $longitud = floatval($_POST['longitud'] ?? 0);

        if (!$especie_id || empty($fecha) || !$latitud || !$longitud) {
            wp_send_json_error(['message' => __('Especie, fecha y ubicación son requeridos.', 'flavor-chat-ia')]);
        }

        // Manejar imagen
        $imagen_url = '';
        if (!empty($_FILES['imagen']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('imagen', 0);
            if (!is_wp_error($attachment_id)) {
                $imagen_url = wp_get_attachment_url($attachment_id);
            }
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_avistamientos, [
            'especie_id' => $especie_id,
            'usuario_id' => get_current_user_id(),
            'fecha' => $fecha,
            'hora' => sanitize_text_field($_POST['hora'] ?? ''),
            'cantidad' => absint($_POST['cantidad'] ?? 1),
            'latitud' => $latitud,
            'longitud' => $longitud,
            'ubicacion_nombre' => sanitize_text_field($_POST['ubicacion_nombre'] ?? ''),
            'notas' => sanitize_textarea_field($_POST['notas'] ?? ''),
            'imagen' => $imagen_url,
            'estado' => 'pendiente',
            'fecha_registro' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Avistamiento registrado. Será revisado por moderadores.', 'flavor-chat-ia'),
                'avistamiento_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al registrar el avistamiento.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Validar avistamiento (moderadores)
     */
    public function ajax_validar_avistamiento() {
        check_ajax_referer('flavor_biodiversidad_nonce', 'nonce');

        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => __('No tienes permiso.', 'flavor-chat-ia')]);
        }

        $avistamiento_id = absint($_POST['avistamiento_id'] ?? 0);
        $accion = sanitize_text_field($_POST['accion_validar'] ?? '');

        if (!$avistamiento_id || !in_array($accion, ['validar', 'rechazar'])) {
            wp_send_json_error(['message' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $estado = $accion === 'validar' ? 'validado' : 'rechazado';

        $wpdb->update(
            $this->tabla_avistamientos,
            [
                'estado' => $estado,
                'validador_id' => get_current_user_id(),
                'fecha_validacion' => current_time('mysql'),
            ],
            ['id' => $avistamiento_id]
        );

        wp_send_json_success([
            'message' => $accion === 'validar'
                ? __('Avistamiento validado.', 'flavor-chat-ia')
                : __('Avistamiento rechazado.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Buscar especies (autocompletado)
     */
    public function ajax_buscar_especies() {
        $termino = sanitize_text_field($_POST['termino'] ?? $_GET['termino'] ?? '');

        if (strlen($termino) < 2) {
            wp_send_json_success(['especies' => []]);
        }

        global $wpdb;
        $like = '%' . $wpdb->esc_like($termino) . '%';

        $especies = $wpdb->get_results($wpdb->prepare("
            SELECT id, nombre_comun, nombre_cientifico, categoria
            FROM {$this->tabla_especies}
            WHERE nombre_comun LIKE %s OR nombre_cientifico LIKE %s
            ORDER BY nombre_comun ASC
            LIMIT 10
        ", $like, $like));

        wp_send_json_success(['especies' => $especies]);
    }

    /**
     * AJAX: Unirse a proyecto
     */
    public function ajax_unirse_proyecto() {
        check_ajax_referer('flavor_biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $proyecto_id = absint($_POST['proyecto_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$proyecto_id) {
            wp_send_json_error(['message' => __('Proyecto no válido.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Verificar si ya participa
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_participantes} WHERE proyecto_id = %d AND usuario_id = %d",
            $proyecto_id, $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya participas en este proyecto.', 'flavor-chat-ia')]);
        }

        $resultado = $wpdb->insert($this->tabla_participantes, [
            'proyecto_id' => $proyecto_id,
            'usuario_id' => $usuario_id,
            'estado' => 'activo',
            'fecha_union' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Te has unido al proyecto de conservación.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al unirse.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener avistamientos (para mapa)
     */
    public function ajax_obtener_avistamientos() {
        $especie_id = absint($_POST['especie_id'] ?? 0);

        global $wpdb;
        $where = "a.estado = 'validado' AND a.latitud IS NOT NULL";
        if ($especie_id) {
            $where .= $wpdb->prepare(" AND a.especie_id = %d", $especie_id);
        }

        $avistamientos = $wpdb->get_results("
            SELECT a.id, a.latitud, a.longitud, a.fecha, e.nombre_comun, e.nombre_cientifico, e.categoria
            FROM {$this->tabla_avistamientos} a
            LEFT JOIN {$this->tabla_especies} e ON a.especie_id = e.id
            WHERE {$where}
            LIMIT 500
        ");

        wp_send_json_success(['avistamientos' => $avistamientos]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Renderiza paginación
     */
    private function render_paginacion($total, $por_pagina, $pagina_actual) {
        $total_paginas = ceil($total / $por_pagina);
        if ($total_paginas <= 1) {
            return;
        }

        $url_base = remove_query_arg('pag');
        ?>
        <nav class="flavor-paginacion">
            <?php if ($pagina_actual > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1, $url_base)); ?>" class="flavor-pag-link">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina_actual - 2);
            $fin = min($total_paginas, $pagina_actual + 2);

            for ($i = $inicio; $i <= $fin; $i++):
                if ($i == $pagina_actual): ?>
                    <span class="flavor-pag-actual"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $i, $url_base)); ?>" class="flavor-pag-link"><?php echo $i; ?></a>
                <?php endif;
            endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1, $url_base)); ?>" class="flavor-pag-link">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endif; ?>
        </nav>
        <?php
    }
}

// Inicializar
Flavor_Biodiversidad_Local_Frontend_Controller::get_instance();
